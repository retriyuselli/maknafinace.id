<?php

namespace App\Services;

use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\PendapatanLain;
use App\Models\PengeluaranLain;
use Carbon\Carbon;

class FinanceSummaryService
{
    /**
     * @return array{from: string, to: string}
     */
    public function resolvePeriod(?string $from, ?string $to): array
    {
        $end = $to ? Carbon::parse($to)->startOfDay() : now()->startOfDay();
        $start = $from ? Carbon::parse($from)->startOfDay() : $end->copy()->startOfMonth();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(string $from, string $to): array
    {
        $inflow = $this->cashInflow($from, $to);
        $outflow = $this->cashOutflow($from, $to);

        $prevEnd = Carbon::parse($from)->subDay();
        $prevStart = $prevEnd->copy()->subDays(
            Carbon::parse($from)->diffInDays(Carbon::parse($to))
        );
        $prevFrom = $prevStart->toDateString();
        $prevTo = $prevEnd->toDateString();

        $prevIn = $this->cashInflow($prevFrom, $prevTo);
        $prevOut = $this->cashOutflow($prevFrom, $prevTo);

        return [
            'period' => ['from' => $from, 'to' => $to],
            'inflow' => $inflow,
            'outflow' => $outflow,
            'net_cash' => $inflow['total'] - $outflow['total'],
            'comparison' => [
                'period' => ['from' => $prevFrom, 'to' => $prevTo],
                'previous_inflow' => $prevIn['total'],
                'previous_outflow' => $prevOut['total'],
                'previous_net_cash' => $prevIn['total'] - $prevOut['total'],
            ],
        ];
    }

    /**
     * @return array{wedding_payments: int, other_income: int, total: int}
     */
    public function cashInflow(string $from, string $to): array
    {
        $wedding = (int) DataPembayaran::query()
            ->whereBetween('tgl_bayar', [$from, $to])
            ->sum('nominal');

        $other = (int) PendapatanLain::query()
            ->whereBetween('tgl_bayar', [$from, $to])
            ->sum('nominal');

        return [
            'wedding_payments' => $wedding,
            'other_income' => $other,
            'total' => $wedding + $other,
        ];
    }

    /**
     * @return array{wedding_expenses: int, operational: int, other_expenses: int, total: int}
     */
    public function cashOutflow(string $from, string $to): array
    {
        $wedding = (int) Expense::query()
            ->whereBetween('date_expense', [$from, $to])
            ->sum('amount');

        $ops = (int) ExpenseOps::query()
            ->whereBetween('date_expense', [$from, $to])
            ->sum('amount');

        $other = (int) PengeluaranLain::query()
            ->whereBetween('date_expense', [$from, $to])
            ->sum('amount');

        return [
            'wedding_expenses' => $wedding,
            'operational' => $ops,
            'other_expenses' => $other,
            'total' => $wedding + $ops + $other,
        ];
    }
}
