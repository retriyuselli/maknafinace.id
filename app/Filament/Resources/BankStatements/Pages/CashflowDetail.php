<?php

namespace App\Filament\Resources\BankStatements\Pages;

use App\Filament\Resources\BankStatements\BankStatementResource;
use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\PengeluaranLain;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;

class CashflowDetail extends Page
{
    protected static string $resource = BankStatementResource::class;

    protected string $view = 'filament.resources.bank-statement-resource.pages.cashflow-detail';

    protected static ?string $title = 'Detail Nilai Widget Keuangan';

    public string $metric = 'income';

    public string $period = 'current';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public array $summary = [];

    public function mount(): void
    {
        $metric = (string) request()->query('metric', 'income');
        $period = (string) request()->query('period', 'current');
        $startDate = (string) request()->query('start_date', '');
        $endDate = (string) request()->query('end_date', '');

        $this->metric = in_array($metric, ['income', 'expense'], true) ? $metric : 'income';
        $this->period = in_array($period, ['current', 'previous'], true) ? $period : 'current';
        $this->startDate = $this->isValidDate($startDate) ? $startDate : null;
        $this->endDate = $this->isValidDate($endDate) ? $endDate : null;
        $this->summary = $this->buildSummary();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url()->route('filament.admin.resources.bank-statements.index') => 'Rekening Koran',
            '#' => 'Detail Nilai Widget',
        ];
    }

    protected function buildSummary(): array
    {
        ['start' => $startDate, 'end' => $endDate, 'label' => $periodLabel] = $this->resolvePeriodRange();

        if ($this->metric === 'income') {
            $total = (int) DataPembayaran::query()
                ->whereBetween('tgl_bayar', [$startDate, $endDate])
                ->sum('nominal');

            return [
                'title' => 'Detail Uang Masuk',
                'period_label' => $periodLabel,
                'formula' => 'Total Uang Masuk = SUM(DataPembayaran.nominal)',
                'total' => $total,
                'active_metric' => $this->metric,
                'active_period' => $this->period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'sources' => [
                    [
                        'source_key' => 'data_pembayaran',
                        'name' => 'Data Pembayaran',
                        'value' => $total,
                        'description' => 'Semua nominal pembayaran pada tanggal bayar periode terpilih.',
                        'detail_url' => $this->getSourceDetailUrl('data_pembayaran'),
                    ],
                ],
            ];
        }

        $expense = (int) Expense::query()
            ->whereBetween('date_expense', [$startDate, $endDate])
            ->sum('amount');
        $expenseOps = (int) ExpenseOps::query()
            ->whereBetween('date_expense', [$startDate, $endDate])
            ->sum('amount');
        $pengeluaranLain = (int) PengeluaranLain::query()
            ->whereBetween('date_expense', [$startDate, $endDate])
            ->sum('amount');

        return [
            'title' => 'Detail Uang Keluar',
            'period_label' => $periodLabel,
            'formula' => 'Total Uang Keluar = SUM(Expense.amount) + SUM(ExpenseOps.amount) + SUM(PengeluaranLain.amount)',
            'total' => $expense + $expenseOps + $pengeluaranLain,
            'active_metric' => $this->metric,
            'active_period' => $this->period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'sources' => [
                [
                    'source_key' => 'expense',
                    'name' => 'Pengeluaran Wedding',
                    'value' => $expense,
                    'description' => 'Total pengeluaran proyek/wedding pada periode terpilih.',
                    'detail_url' => $this->getSourceDetailUrl('expense'),
                ],
                [
                    'source_key' => 'expense_ops',
                    'name' => 'Pengeluaran Operasional',
                    'value' => $expenseOps,
                    'description' => 'Total pengeluaran operasional harian pada periode terpilih.',
                    'detail_url' => $this->getSourceDetailUrl('expense_ops'),
                ],
                [
                    'source_key' => 'pengeluaran_lain',
                    'name' => 'Pengeluaran Lain',
                    'value' => $pengeluaranLain,
                    'description' => 'Total pengeluaran lainnya pada periode terpilih.',
                    'detail_url' => $this->getSourceDetailUrl('pengeluaran_lain'),
                ],
            ],
        ];
    }

    private function getSourceDetailUrl(string $source): string
    {
        return route('filament.admin.resources.bank-statements.cashflow-source-detail', [
            'metric' => $this->metric,
            'period' => $this->period,
            'source' => $source,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ]);
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
