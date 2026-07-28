<?php

namespace App\Filament\Resources\BankStatements\Widgets;

use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\PengeluaranLain;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class BankStatementOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $previousMonth = $currentMonth->copy()->subMonth();
        $startOfPreviousMonth = $previousMonth->copy()->startOfMonth();
        $endOfPreviousMonth = $previousMonth->copy()->endOfMonth();

        $totalCurrentCredit = (int) DataPembayaran::query()
            ->whereBetween('tgl_bayar', [$startOfMonth, $endOfMonth])
            ->sum('nominal');
        $totalPreviousCredit = (int) DataPembayaran::query()
            ->whereBetween('tgl_bayar', [$startOfPreviousMonth, $endOfPreviousMonth])
            ->sum('nominal');

        $totalCurrentDebit = $this->sumExpensesBetween($startOfMonth, $endOfMonth);
        $totalPreviousDebit = $this->sumExpensesBetween($startOfPreviousMonth, $endOfPreviousMonth);

        $currentMonthLabel = $currentMonth->translatedFormat('F Y');
        $previousMonthLabel = $previousMonth->translatedFormat('F Y');

        return [
            Stat::make('Uang Masuk (Bulan Berjalan)', 'Rp '.Number::format($totalCurrentCredit, 0))
                ->description($currentMonthLabel)
                ->color('success')
                ->url($this->getDetailUrl('income', 'current'))
                ->chart($this->getIncomeTrendData()),

            Stat::make('Uang Masuk (Bulan Lalu)', 'Rp '.Number::format($totalPreviousCredit, 0))
                ->description($previousMonthLabel)
                ->color('info')
                ->url($this->getDetailUrl('income', 'previous')),

            Stat::make('Uang Keluar (Bulan Berjalan)', 'Rp '.Number::format($totalCurrentDebit, 0))
                ->description($currentMonthLabel)
                ->color('warning')
                ->url($this->getDetailUrl('expense', 'current'))
                ->chart($this->getExpenseTrendData()),

            Stat::make('Uang Keluar (Bulan Lalu)', 'Rp '.Number::format($totalPreviousDebit, 0))
                ->description($previousMonthLabel)
                ->color('gray')
                ->url($this->getDetailUrl('expense', 'previous')),
        ];
    }

    private function getIncomeTrendData(): array
    {
        $payments = DataPembayaran::query()
            ->where('tgl_bayar', '>=', Carbon::now()->subDays(7))
            ->orderBy('tgl_bayar')
            ->get(['tgl_bayar', 'nominal']);

        return $this->aggregateDailyTrend($payments, 'tgl_bayar', 'nominal');
    }

    private function getExpenseTrendData(): array
    {
        $expenses = Expense::query()
            ->where('date_expense', '>=', Carbon::now()->subDays(7))
            ->orderBy('date_expense')
            ->get(['date_expense', 'amount']);
        $expenseOps = ExpenseOps::query()
            ->where('date_expense', '>=', Carbon::now()->subDays(7))
            ->orderBy('date_expense')
            ->get(['date_expense', 'amount']);
        $pengeluaranLain = PengeluaranLain::query()
            ->where('date_expense', '>=', Carbon::now()->subDays(7))
            ->orderBy('date_expense')
            ->get(['date_expense', 'amount']);

        $expenseTrend = $this->aggregateDailyTrend($expenses, 'date_expense', 'amount');
        $expenseOpsTrend = $this->aggregateDailyTrend($expenseOps, 'date_expense', 'amount');
        $pengeluaranTrend = $this->aggregateDailyTrend($pengeluaranLain, 'date_expense', 'amount');

        $trendData = [];
        for ($i = 0; $i < 7; $i++) {
            $trendData[] = ($expenseTrend[$i] ?? 0) + ($expenseOpsTrend[$i] ?? 0) + ($pengeluaranTrend[$i] ?? 0);
        }

        return $trendData;
    }

    private function aggregateDailyTrend($rows, string $dateField, string $amountField): array
    {
        $trendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayRows = $rows->filter(function ($row) use ($date, $dateField) {
                return Carbon::parse($row->{$dateField})->isSameDay($date);
            });

            $trendData[] = (float) $dayRows->sum($amountField);
        }

        return $trendData;
    }

    private function sumExpensesBetween(Carbon $startDate, Carbon $endDate): int
    {
        $expense = (int) Expense::query()->whereBetween('date_expense', [$startDate, $endDate])->sum('amount');
        $expenseOps = (int) ExpenseOps::query()->whereBetween('date_expense', [$startDate, $endDate])->sum('amount');
        $pengeluaranLain = (int) PengeluaranLain::query()->whereBetween('date_expense', [$startDate, $endDate])->sum('amount');

        return $expense + $expenseOps + $pengeluaranLain;
    }

    private function getDetailUrl(string $metric, string $period): string
    {
        return route('filament.admin.resources.bank-statements.cashflow-detail', [
            'metric' => $metric,
            'period' => $period,
        ]);
    }

    protected function getColumns(): int
    {
        return 2;
    }

    public function getDisplayName(): string
    {
        return 'Ringkasan Rekening Koran';
    }
}
