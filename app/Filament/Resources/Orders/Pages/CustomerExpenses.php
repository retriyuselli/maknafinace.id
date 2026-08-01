<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Expense;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;

class CustomerExpenses extends Page
{
    protected static string $resource = OrderResource::class;

    protected static ?string $slug = 'customer-expenses';

    protected static ?string $title = 'Pengeluaran Pelanggan';

    protected string $view = 'filament.resources.orders.pages.customer-expenses';

    public int $year;
    public int $month;
    public string $status;

    public function mount(): void
    {
        $status = request()->query('status');
        if (is_string($status)) {
            $valid = in_array($status, array_map(fn ($s) => $s->value, OrderStatus::cases()), true);
            $this->status = $status === 'all' ? 'all' : ($valid ? $status : 'all');
        } else {
            $this->status = 'all';
        }

        $yearParam = request()->query('year');
        $monthParam = request()->query('month');

        if (is_numeric($yearParam) && is_string($monthParam) && $monthParam === 'all') {
            $this->year = (int) $yearParam;
            $this->month = 0;
            return;
        }

        if (is_numeric($yearParam) && is_numeric($monthParam)) {
            $year = (int) $yearParam;
            $month = (int) $monthParam;
            if ($year >= 2000 && $month >= 1 && $month <= 12) {
                $this->year = $year;
                $this->month = $month;
                return;
            }
        }

        if (is_string($monthParam) && preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            $parsed = Carbon::createFromFormat('Y-m', $monthParam);
            $this->year = (int) $parsed->year;
            $this->month = (int) $parsed->month;
            return;
        }

        $now = Carbon::now();
        $this->year = (int) $now->year;
        $this->month = (int) $now->month;
    }

    protected function getViewData(): array
    {
        $target = Carbon::create($this->year, max($this->month, 1), 1);

        $expenses = Expense::query()
            ->with(['order.prospect:id,name_event', 'paymentMethod:id,name,is_cash,bank_name,no_rekening'])
            ->whereYear('date_expense', $this->year)
            ->when($this->month !== 0, function ($query) {
                $query->whereMonth('date_expense', $this->month);
            })
            ->when($this->status !== 'all', function ($query) {
                $query->whereHas('order', function ($q) {
                    $q->where('status', $this->status);
                });
            })
            ->orderBy('date_expense', 'desc')
            ->get([
                'id',
                'order_id',
                'payment_method_id',
                'date_expense',
                'amount',
                'note',
                'kategori_transaksi',
            ]);

        $totals = [
            'count' => $expenses->count(),
            'amount' => (int) $expenses->sum('amount'),
        ];

        $years = Expense::query()
            ->when($this->status !== 'all', function ($query) {
                $query->whereHas('order', function ($q) {
                    $q->where('status', $this->status);
                });
            })
            ->selectRaw('YEAR(date_expense) as y')
            ->distinct()
            ->orderBy('y', 'desc')
            ->pluck('y')
            ->all();

        return [
            'expenses' => $expenses,
            'monthLabel' => $this->month === 0 ? (string) $this->year : $target->translatedFormat('F Y'),
            'selectedMonth' => $this->month === 0 ? 'all' : sprintf('%04d-%02d', $this->year, $this->month),
            'selectedYear' => $this->year,
            'status' => $this->status,
            'totals' => $totals,
            'years' => $years,
            'statusOptions' => array_merge(
                [['value' => 'all', 'label' => 'All']],
                collect(OrderStatus::cases())
                    ->map(fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->getLabel()])
                    ->all()
            ),
        ];
    }
}
