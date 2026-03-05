<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\DataPembayaran;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;

class CustomerPayments extends Page
{
    protected static string $resource = OrderResource::class;

    protected static ?string $slug = 'customer-payments';

    protected static ?string $title = 'Pembayaran Pelanggan';

    protected string $view = 'filament.resources.orders.pages.customer-payments';

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

        $yearNumeric = request()->query('year');
        $monthNumeric = request()->query('month');
        $monthQuery = request()->query('month');

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

        $payments = DataPembayaran::query()
            ->with(['order.prospect:id,name_event', 'paymentMethod:id,name,is_cash,bank_name,no_rekening'])
            ->whereYear('tgl_bayar', $this->year)
            ->whereMonth('tgl_bayar', $this->month)
            ->when($this->status !== 'all', function ($query) {
                $query->whereHas('order', function ($q) {
                    $q->where('status', $this->status);
                });
            })
            ->orderBy('tgl_bayar', 'desc')
            ->get([
                'id',
                'order_id',
                'payment_method_id',
                'tgl_bayar',
                'nominal',
                'keterangan',
            ]);

        $totals = [
            'count' => $payments->count(),
            'amount' => (int) $payments->sum('nominal'),
        ];

        $years = DataPembayaran::query()
            ->when($this->status !== 'all', function ($query) {
                $query->whereHas('order', function ($q) {
                    $q->where('status', $this->status);
                });
            })
            ->selectRaw('YEAR(tgl_bayar) as y')
            ->distinct()
            ->orderBy('y', 'desc')
            ->pluck('y')
            ->all();

        return [
            'payments' => $payments,
            'monthLabel' => $target->translatedFormat('F Y'),
            'selectedMonth' => sprintf('%04d-%02d', $this->year, $this->month),
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
