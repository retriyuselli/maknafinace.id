<?php

namespace App\Filament\Resources\Orders\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Pages\NetCashFlowReport;
use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\Order;
use App\Support\Rupiah;
use BackedEnum;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Livewire\Attributes\On;

class OrderOverview extends BaseWidget
{
    // protected ?string $pollingInterval = '5s';

    public $metrics = [
        'payments' => 0,
        'projects' => 0,
        'revenue' => 0,
        'processing' => 0,
        'total_revenue' => 0,
        'documents' => 0,
        'pending_documents' => 0,
    ];

    public function mount(): void
    {
        $this->refreshMetrics();
    }

    /**
     * Dengarkan update order dan refresh metrik
     */
    #[On('order-updated')]
    #[On('payment-received')]
    public function refreshMetrics(): void
    {
        $currentMonth = Carbon::now();

        // Query tunggal untuk mendapatkan semua metrik bulanan
        $monthlyData = Order::whereMonth('closing_date', $currentMonth->month)
            ->whereYear('closing_date', $currentMonth->year)
            ->select(
                DB::raw('COUNT(*) as total_projects'),
                DB::raw('SUM(grand_total) as monthly_revenue'),
                DB::raw('COUNT(CASE WHEN status = "'.OrderStatus::Processing->value.'" THEN 1 END) as processing_count') // Ini menghitung order dengan status "processing"
            )
            ->first();

        $this->metrics['projects'] = $monthlyData->total_projects ?? 0;
        $this->metrics['revenue'] = $monthlyData->monthly_revenue ?? 0;
        $this->metrics['processing'] = $monthlyData->processing_count ?? 0; // Menyimpan hasil hitungan
        $this->metrics['documents'] = Order::whereNotNull('doc_kontrak')->count();
        $this->metrics['pending_documents'] = Order::whereNull('doc_kontrak')->count();

        // Dapatkan pembayaran untuk order dengan status "processing"
        $this->metrics['payments'] = DataPembayaran::whereIn('order_id', function ($query) {
            $query->select('id')
                ->from('orders')
                ->where('status', OrderStatus::Processing->value);
        })->sum('nominal');

        // Hitung total pendapatan untuk tahun ini
        $this->metrics['total_revenue'] = Order::whereYear('closing_date', $currentMonth->year)
            ->sum('grand_total');

        $this->metrics['total_expenseOps'] = ExpenseOps::sum('amount');

        // Dapatkan pengeluaran untuk order dengan status "processing"
        $this->metrics['total_expense'] = Expense::whereIn('order_id', function ($query) {
            $query->select('id')
                ->from('orders')
                ->where('status', OrderStatus::Processing->value);
        })->sum('amount');
    }

    /**
     * Format mata uang dengan format Rupiah Indonesia
     */
    protected function formatCurrency(float $amount): string
    {
        return Rupiah::format($amount);
    }

    /**
     * Hitung indikator tren sederhana
     */
    protected function calculateTrend(string $metric): array
    {
        $days = 7;
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        $dateKeys = [];
        for ($i = 0; $i < $days; $i++) {
            $dateKeys[] = $startDate->copy()->addDays($i)->toDateString();
        }

        if ($metric === 'projects') {
            $rows = Order::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
                ->groupBy('d')
                ->orderBy('d', 'asc')
                ->get()
                ->pluck('c', 'd')
                ->all();

            return array_map(fn ($d) => (int) ($rows[$d] ?? 0), $dateKeys);
        }

        if ($metric === 'revenue') {
            $rows = Order::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as d, SUM(grand_total) as s')
                ->groupBy('d')
                ->orderBy('d', 'asc')
                ->get()
                ->pluck('s', 'd')
                ->all();

            return array_map(fn ($d) => (int) ($rows[$d] ?? 0), $dateKeys);
        }

        return array_fill(0, $days, 0);
    }

    protected function getStats(): array
    {
        // Buat tren sederhana untuk proyek dan pendapatan
        $projectTrend = $this->calculateTrend('projects');
        $revenueTrend = $this->calculateTrend('revenue');
        $statusTarget = OrderStatus::Processing; // Ganti dengan OrderStatus::DONE jika ingin status 'done'
        $targetOrderIds = Order::where('status', $statusTarget)->pluck('id');
        $totalPembayaranUntukTargetOrder = DataPembayaran::whereIn('order_id', $targetOrderIds)
            ->sum('nominal');
        $totalPengeluaranUntukTargetOrder = Expense::whereIn('order_id', $targetOrderIds)
            ->sum('amount');
        $sumUangDiterimaUntukTargetOrder = $totalPembayaranUntukTargetOrder - $totalPengeluaranUntukTargetOrder;

        // Deskripsi bisa disesuaikan berdasarkan statusTarget
        $descriptionText = 'Untuk order dengan status '.($statusTarget instanceof BackedEnum ? $statusTarget->value : $statusTarget);

        return [
            // Ringkasan Pembayaran Pelanggan
            Stat::make('Total Pembayaran Pelanggan', $this->formatCurrency($this->metrics['payments']))
                ->description('Total pembayaran diterima')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->url(route('reports.customer-payments', ['status' => OrderStatus::Processing->value])),

            // Ringkasan Pengeluaran Pelanggan
            Stat::make('Total Pengeluaran Pelanggan', $this->formatCurrency($this->metrics['total_expense']))
                ->description('Total pengeluaran')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('danger'),

            // Total Sisa Uang Pelanggan
            Stat::make('Total Sisa Uang Pelanggan', $this->formatCurrency($this->metrics['payments'] - $this->metrics['total_expense']))
                ->description('Total sisa uang')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            // Ringkasan Proyek Bulanan
            Stat::make('Proyek Baru Bulan Ini', $this->metrics['projects'])
                ->description('Proyek di '.now()->format('F Y'))
                ->descriptionIcon('heroicon-m-document-plus')
                ->chart($projectTrend)
                ->color('primary')
                ->url(\App\Filament\Resources\Orders\OrderResource::getUrl('view-closing', ['month' => now()->format('Y-m')]), true),

            // Ringkasan Pendapatan Bulanan
            Stat::make('Pendapatan Bulanan', $this->formatCurrency($this->metrics['revenue']))
                ->description('Pendapatan di '.now()->format('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($revenueTrend)
                ->color('success'),

            // Ringkasan Total Dokumen
            Stat::make('Total Dokumen Kontrak', $this->metrics['documents'])
                ->description('Total dokumen')
                ->description(sprintf('%d dokumen menunggu verifikasi', $this->metrics['pending_documents']))
                ->color('primary'),

            // Ringkasan Total Pendapatan
            Stat::make('Total Pendapatan', $this->formatCurrency($this->metrics['total_revenue']))
                ->description('Pendapatan keseluruhan')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->chart($revenueTrend)
                ->color('success'),

            Stat::make('Total Pengeluaran', $this->formatCurrency($this->metrics['total_expenseOps']))
                ->description('Pengeluaran keseluruhan')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger'),

            Stat::make(
                'Total Uang Diterima ('.($statusTarget instanceof BackedEnum ? $statusTarget->value : $statusTarget).')',
                ''.Number::format($sumUangDiterimaUntukTargetOrder, precision: 0, locale: 'id')
            )
                ->description($descriptionText)
                ->descriptionIcon('heroicon-m-banknotes') // Ganti ikon jika perlu
                ->color('primary') // Ganti warna jika perlu (success, warning, danger, etc.)
                ->url(NetCashFlowReport::getUrl(['status' => $statusTarget instanceof BackedEnum ? $statusTarget->value : $statusTarget])),
        ];
    }
}
