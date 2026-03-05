<?php

namespace App\Filament\Resources\Orders\Pages;

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

    public function mount(): void
    {
        $monthQuery = request()->query('month');
        $yearNumeric = request()->query('year');
        $monthNumeric = request()->query('month');

        if (is_numeric($yearNumeric) && is_numeric($monthNumeric)) {
            $year = (int) $yearNumeric;
            $month = (int) $monthNumeric;
            if ($year >= 2000 && $month >= 1 && $month <= 12) {
                $this->year = $year;
                $this->month = $month;
                return;
            }
        }

        if (is_string($monthQuery) && preg_match('/^\d{4}-\d{2}$/', $monthQuery)) {
            $parsed = Carbon::createFromFormat('Y-m', $monthQuery);
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
        $target = Carbon::create($this->year, $this->month, 1);
        $orders = Order::query()
            ->with(['prospect:id,name_event', 'employee:id,name', 'user:id,name', 'items:order_id,quantity,unit_price'])
            ->whereNotNull('closing_date')
            ->whereMonth('closing_date', $this->month)
            ->whereYear('closing_date', $this->year)
            ->orderBy('closing_date', 'desc')
            ->get([
                'id',
                'number',
                'grand_total',
                'closing_date',
                'prospect_id',
                'employee_id',
                'user_id',
            ]);

        return [
            'orders' => $orders,
            'monthLabel' => $target->translatedFormat('F Y'),
            'selectedMonth' => sprintf('%04d-%02d', $this->year, $this->month),
            'totals' => [
                'projects' => $orders->count(),
                'revenue' => (int) $orders->sum('grand_total'),
                'paid' => (int) $orders->sum('bayar'),
                'remaining' => (int) $orders->sum('sisa'),
            ],
        ];
    }
}
