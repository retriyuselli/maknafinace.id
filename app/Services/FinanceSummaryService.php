<?php

namespace App\Services;

use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\Order;
use App\Models\PendapatanLain;
use App\Models\PengeluaranLain;
use App\Models\Prospect;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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

    public function scopedOrdersQuery(): Builder
    {
        return Order::query()->with([
            'prospect:id,name_event,name_cpp,name_cpw,venue,phone,address,date_lamaran,date_akad,date_resepsi',
            'user:id,name',
            'dataPembayaran:id,order_id,nominal,tgl_bayar,keterangan,payment_method_id',
            'dataPengeluaran:id,order_id,amount,date_expense,note,vendor_id,payment_stage',
            'expenses:id,order_id,amount,date_expense,note,vendor_id,payment_stage',
        ]);
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int|float>}
     */
    public function projects(?string $status = null, int $perPage = 20): array
    {
        $query = $this->scopedOrdersQuery()->latest('id');

        if ($status) {
            $query->where('status', $status);
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(min(max($perPage, 1), 50));

        $data = collect($paginator->items())->map(fn (Order $order) => $this->projectSummary($order))->values()->all();

        $metaTotals = [
            'total_grand_total' => 0,
            'total_payments' => 0,
            'total_expenses' => 0,
            'total_net_cash_flow' => 0,
        ];

        foreach ($data as $row) {
            $metaTotals['total_grand_total'] += $row['grand_total'];
            $metaTotals['total_payments'] += $row['paid_amount'];
            $metaTotals['total_expenses'] += $row['expenses_total'];
            $metaTotals['total_net_cash_flow'] += $row['net_cash_flow'];
        }

        return [
            'data' => $data,
            'meta' => array_merge([
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ], $metaTotals),
        ];
    }

    public function scopedProspectsQuery(): Builder
    {
        return Prospect::query()->with([
            'user:id,name',
            'latestOrder',
        ]);
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function prospects(?string $status = null, int $perPage = 20): array
    {
        $query = $this->scopedProspectsQuery()->latest('id');
        $this->applyProspectStatusFilter($query, $status);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(min(max($perPage, 1), 50));

        $base = $this->scopedProspectsQuery();
        $allCount = (clone $base)->count();
        $warmCount = (clone $base)->doesntHave('orders')->count();

        return [
            'data' => collect($paginator->items())
                ->map(fn (Prospect $prospect) => $this->prospectSummary($prospect))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'all_count' => $allCount,
                'warm_count' => $warmCount,
                'with_order_count' => max(0, $allCount - $warmCount),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function projectSummary(Order $order): array
    {
        $finance = OrderFinance::for($order);
        $paid = $finance->paymentsTotal();
        $expenses = $finance->expensesTotal();
        $grand = $finance->grandTotal();
        $prospect = $order->prospect;

        return [
            'id' => $order->id,
            'slug' => $order->slug,
            'name' => $order->name ?: ($prospect?->name_event),
            'number' => $order->number,
            'status' => $this->enumValue($order->status),
            'closing_date' => optional($order->closing_date)?->toDateString(),
            'account_manager' => $order->user?->name,
            'prospect' => $prospect ? [
                'id' => $prospect->id,
                'name_event' => $prospect->name_event,
                'date_lamaran' => optional($prospect->date_lamaran)?->toDateString(),
                'date_akad' => optional($prospect->date_akad)?->toDateString(),
                'date_resepsi' => optional($prospect->date_resepsi)?->toDateString(),
            ] : null,
            'grand_total' => $grand,
            'paid_amount' => $paid,
            'remaining' => $grand - $paid,
            'expenses_total' => $expenses,
            'net_cash_flow' => $paid - $expenses,
            'gross_profit' => $grand - $expenses,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function prospectSummary(Prospect $prospect): array
    {
        $order = $prospect->latestOrder;
        $orderStatus = $order
            ? $this->enumValue($order->status)
            : 'no_order';

        return [
            'id' => $prospect->id,
            'name_event' => $prospect->name_event,
            'name_cpp' => $prospect->name_cpp,
            'name_cpw' => $prospect->name_cpw,
            'venue' => $prospect->venue,
            'phone' => $prospect->phone,
            'address' => $prospect->address,
            'date_lamaran' => optional($prospect->date_lamaran)?->toDateString(),
            'time_lamaran' => $this->formatClock($prospect->time_lamaran),
            'date_akad' => optional($prospect->date_akad)?->toDateString(),
            'time_akad' => $this->formatClock($prospect->time_akad),
            'date_resepsi' => optional($prospect->date_resepsi)?->toDateString(),
            'time_resepsi' => $this->formatClock($prospect->time_resepsi),
            'total_penawaran' => (int) ($prospect->total_penawaran ?? 0),
            'notes' => $prospect->notes,
            'account_manager' => $prospect->user?->name,
            'order_status' => $orderStatus ?: 'no_order',
            'order' => $order ? [
                'id' => $order->id,
                'name' => $order->name ?: $prospect->name_event,
                'number' => $order->number,
                'status' => $this->enumValue($order->status),
            ] : null,
        ];
    }

    private function applyProspectStatusFilter(Builder $query, ?string $status): void
    {
        if (! $status) {
            return;
        }

        match ($status) {
            'no_order' => $query->doesntHave('orders'),
            'has_order' => $query->has('orders'),
            'pending', 'processing', 'done', 'cancelled' => $query->whereHas(
                'orders',
                fn (Builder $orders) => $orders->where('status', $status)
            ),
            default => null,
        };
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function formatClock(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
