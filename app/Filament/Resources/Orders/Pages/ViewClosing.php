<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;

class ViewClosing extends Page
{
    protected static string $resource = OrderResource::class;

    protected static ?string $slug = 'view-closing';

    protected static ?string $title = 'Closing Bulan Ini';

    protected string $view = 'filament.resources.orders.pages.view-closing';

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

        $monthParam = request()->query('month');
        $yearParam = request()->query('year');

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
        $orders = Order::query()
            ->with([
                'prospect:id,name_event',
                'employee:id,name',
                'user:id,name',
                'items:order_id,quantity,unit_price',
                'dataPembayaran:id,order_id,payment_method_id,nominal,tgl_bayar',
                'dataPembayaran.paymentMethod:id,name,is_cash,bank_name,no_rekening',
            ])
            ->whereNotNull('closing_date')
            ->when($this->status !== 'all', function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->month !== 0, function ($query) {
                $query->whereMonth('closing_date', $this->month);
            })
            ->whereYear('closing_date', $this->year)
            ->orderBy('closing_date', 'desc')
            ->get([
                'id',
                'number',
                'status',
                'closing_date',
                'prospect_id',
                'employee_id',
                'user_id',
                'total_price',
                'promo',
                'penambahan',
                'pengurangan',
            ]);

        $years = Order::query()
            ->whereNotNull('closing_date')
            ->when($this->status !== 'all', function ($query) {
                $query->where('status', $this->status);
            })
            ->selectRaw('YEAR(closing_date) as y')
            ->distinct()
            ->orderBy('y', 'desc')
            ->pluck('y')
            ->all();

        return [
            'orders' => $orders,
            'monthLabel' => $this->month === 0 ? (string) $this->year : $target->translatedFormat('F Y'),
            'selectedMonth' => $this->month === 0 ? 'all' : sprintf('%04d-%02d', $this->year, $this->month),
            'years' => $years,
            'status' => $this->status,
            'statusOptions' => array_merge(
                [['value' => 'all', 'label' => 'All']],
                collect(OrderStatus::cases())
                    ->map(fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->getLabel()])
                    ->all()
            ),
            'totals' => [
                'projects' => $orders->count(),
                'revenue' => (int) $orders->sum('grand_total'),
                'paid' => (int) $orders->sum('bayar'),
                'remaining' => (int) $orders->sum('sisa'),
            ],
        ];
    }
}
