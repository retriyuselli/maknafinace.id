<?php

namespace App\Filament\Resources\BankStatements\Pages;

use App\Filament\Resources\BankStatements\BankStatementResource;
use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\PengeluaranLain;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;

class CashflowSourceDetail extends Page
{
    protected static string $resource = BankStatementResource::class;

    protected string $view = 'filament.resources.bank-statement-resource.pages.cashflow-source-detail';

    protected static ?string $title = 'Detail Sumber Data';

    public string $metric = 'income';

    public string $period = 'current';

    public string $source = 'data_pembayaran';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public array $detail = [];

    public function mount(): void
    {
        $metric = (string) request()->query('metric', 'income');
        $period = (string) request()->query('period', 'current');
        $source = (string) request()->query('source', 'data_pembayaran');
        $startDate = (string) request()->query('start_date', '');
        $endDate = (string) request()->query('end_date', '');

        $this->metric = in_array($metric, ['income', 'expense'], true) ? $metric : 'income';
        $this->period = in_array($period, ['current', 'previous'], true) ? $period : 'current';
        $this->source = in_array($source, ['data_pembayaran', 'expense', 'expense_ops', 'pengeluaran_lain'], true) ? $source : 'data_pembayaran';
        $this->startDate = $this->isValidDate($startDate) ? $startDate : null;
        $this->endDate = $this->isValidDate($endDate) ? $endDate : null;

        $this->detail = $this->buildDetail();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url()->route('filament.admin.resources.bank-statements.index') => 'Rekening Koran',
            route('filament.admin.resources.bank-statements.cashflow-detail', [
                'metric' => $this->metric,
                'period' => $this->period,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
            ]) => 'Detail Nilai Widget',
            '#' => 'Detail Sumber Data',
        ];
    }

    protected function buildDetail(): array
    {
        ['start' => $startDate, 'end' => $endDate, 'label' => $periodLabel] = $this->resolvePeriodRange();

        return match ($this->source) {
            'data_pembayaran' => $this->buildDataPembayaranDetail($startDate, $endDate, $periodLabel),
            'expense' => $this->buildExpenseDetail($startDate, $endDate, $periodLabel),
            'expense_ops' => $this->buildExpenseOpsDetail($startDate, $endDate, $periodLabel),
            'pengeluaran_lain' => $this->buildPengeluaranLainDetail($startDate, $endDate, $periodLabel),
            default => $this->buildDataPembayaranDetail($startDate, $endDate, $periodLabel),
        };
    }

    protected function buildDataPembayaranDetail(Carbon $startDate, Carbon $endDate, string $periodLabel): array
    {
        $rows = DataPembayaran::query()
            ->with(['paymentMethod:id,name,no_rekening'])
            ->whereBetween('tgl_bayar', [$startDate, $endDate])
            ->orderByDesc('tgl_bayar')
            ->limit(100)
            ->get(['id', 'tgl_bayar', 'keterangan', 'nominal', 'payment_method_id']);

        return [
            'title' => 'Detail Data Pembayaran',
            'period_label' => $periodLabel,
            'formula' => 'SUM(DataPembayaran.nominal) pada periode terpilih',
            'total' => (int) $rows->sum('nominal'),
            'row_count' => $rows->count(),
            'notes' => 'Menampilkan maksimal 100 baris terbaru untuk menjaga performa.',
            'rows' => $rows->map(fn ($row) => [
                'date' => optional($row->tgl_bayar)->format('Y-m-d'),
                'description' => (string) ($row->keterangan ?: 'Pembayaran #' . $row->id),
                'reference' => 'ID #'.$row->id,
                'account_number' => (string) ($row->paymentMethod?->no_rekening ?: '-'),
                'account_holder' => (string) ($row->paymentMethod?->name ?: '-'),
                'amount' => (int) $row->nominal,
            ])->all(),
        ];
    }

    protected function buildExpenseDetail(Carbon $startDate, Carbon $endDate, string $periodLabel): array
    {
        $rows = Expense::query()
            ->with(['paymentMethod:id,name,no_rekening'])
            ->whereBetween('date_expense', [$startDate, $endDate])
            ->orderByDesc('date_expense')
            ->limit(100)
            ->get(['id', 'date_expense', 'note', 'order_id', 'amount', 'bank_account', 'account_holder', 'payment_method_id']);

        return [
            'title' => 'Detail Pengeluaran Wedding',
            'period_label' => $periodLabel,
            'formula' => 'SUM(Expense.amount) pada periode terpilih',
            'total' => (int) $rows->sum('amount'),
            'row_count' => $rows->count(),
            'notes' => 'Menampilkan maksimal 100 baris terbaru untuk menjaga performa.',
            'rows' => $rows->map(fn ($row) => [
                'date' => optional($row->date_expense)->format('Y-m-d'),
                'description' => (string) ($row->note ?: 'Pengeluaran Wedding #' . $row->id),
                'reference' => 'ID #'.$row->id.($row->order_id ? ' | Order #'.$row->order_id : ''),
                'account_number' => (string) ($row->bank_account ?: $row->paymentMethod?->no_rekening ?: '-'),
                'account_holder' => (string) ($row->account_holder ?: $row->paymentMethod?->name ?: '-'),
                'amount' => (int) $row->amount,
            ])->all(),
        ];
    }

    protected function buildExpenseOpsDetail(Carbon $startDate, Carbon $endDate, string $periodLabel): array
    {
        $rows = ExpenseOps::query()
            ->with(['paymentMethod:id,name,no_rekening'])
            ->whereBetween('date_expense', [$startDate, $endDate])
            ->orderByDesc('date_expense')
            ->limit(100)
            ->get(['id', 'date_expense', 'name', 'note', 'amount', 'bank_account', 'account_holder', 'payment_method_id']);

        return [
            'title' => 'Detail Pengeluaran Operasional',
            'period_label' => $periodLabel,
            'formula' => 'SUM(ExpenseOps.amount) pada periode terpilih',
            'total' => (int) $rows->sum('amount'),
            'row_count' => $rows->count(),
            'notes' => 'Menampilkan maksimal 100 baris terbaru untuk menjaga performa.',
            'rows' => $rows->map(fn ($row) => [
                'date' => optional($row->date_expense)->format('Y-m-d'),
                'description' => (string) ($row->name ?: $row->note ?: 'Pengeluaran Operasional #' . $row->id),
                'reference' => 'ID #'.$row->id,
                'account_number' => (string) ($row->bank_account ?: $row->paymentMethod?->no_rekening ?: '-'),
                'account_holder' => (string) ($row->account_holder ?: $row->paymentMethod?->name ?: '-'),
                'amount' => (int) $row->amount,
            ])->all(),
        ];
    }

    protected function buildPengeluaranLainDetail(Carbon $startDate, Carbon $endDate, string $periodLabel): array
    {
        $rows = PengeluaranLain::query()
            ->with(['paymentMethod:id,name,no_rekening'])
            ->whereBetween('date_expense', [$startDate, $endDate])
            ->orderByDesc('date_expense')
            ->limit(100)
            ->get(['id', 'date_expense', 'name', 'note', 'amount', 'bank_account', 'account_holder', 'payment_method_id']);

        return [
            'title' => 'Detail Pengeluaran Lain',
            'period_label' => $periodLabel,
            'formula' => 'SUM(PengeluaranLain.amount) pada periode terpilih',
            'total' => (int) $rows->sum('amount'),
            'row_count' => $rows->count(),
            'notes' => 'Menampilkan maksimal 100 baris terbaru untuk menjaga performa.',
            'rows' => $rows->map(fn ($row) => [
                'date' => optional($row->date_expense)->format('Y-m-d'),
                'description' => (string) ($row->name ?: $row->note ?: 'Pengeluaran Lain #' . $row->id),
                'reference' => 'ID #'.$row->id,
                'account_number' => (string) ($row->bank_account ?: $row->paymentMethod?->no_rekening ?: '-'),
                'account_holder' => (string) ($row->account_holder ?: $row->paymentMethod?->name ?: '-'),
                'amount' => (int) $row->amount,
            ])->all(),
        ];
    }

    private function resolvePeriodRange(): array
    {
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();

            if ($start->gt($end)) {
                [$start, $end] = [$end, $start];
            }

            return [
                'start' => $start,
                'end' => $end,
                'label' => $start->format('d M Y').' - '.$end->format('d M Y'),
            ];
        }

        $now = Carbon::now();
        $targetMonth = $this->period === 'previous' ? $now->copy()->subMonth() : $now->copy();

        return [
            'start' => $targetMonth->copy()->startOfMonth(),
            'end' => $targetMonth->copy()->endOfMonth(),
            'label' => $targetMonth->translatedFormat('F Y'),
        ];
    }

    private function isValidDate(string $date): bool
    {
        if ($date === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));

        return checkdate($month, $day, $year);
    }
}
