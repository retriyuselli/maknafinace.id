<?php

namespace App\Services;

use App\Models\Order;

class OrderFinance
{
    public function __construct(protected Order $order) {}

    public static function for(Order $order): self
    {
        return new self($order);
    }

    public static function computeGrandTotalFromValues($totalPrice, $penambahan, $promo, $pengurangan)
    {
        return $totalPrice + $penambahan - $promo - $pengurangan;
    }

    public function grandTotalBase()
    {
        $totalPrice = (int) ($this->order->total_price ?? 0);

        if ($totalPrice === 0) {
            $items = $this->order->relationLoaded('items')
                ? $this->order->items
                : $this->order->items()->get(['quantity', 'unit_price']);

            $totalPrice = $items->sum(function ($item) {
                $qty = (int) ($item->quantity ?? 0);
                $unit = (int) ($item->unit_price ?? 0);

                return $qty * $unit;
            });
        }

        return self::computeGrandTotalFromValues(
            $totalPrice,
            (int) ($this->order->penambahan ?? 0),
            (int) ($this->order->promo ?? 0),
            (int) ($this->order->pengurangan ?? 0)
        );
    }

    public function paymentsTotal()
    {
        return $this->order->dataPembayaran->sum('nominal');
    }

    public function expensesTotal()
    {
        return $this->order->dataPengeluaran->sum('amount');
    }

    public function grandTotal()
    {
        return $this->grandTotalBase();
    }

    public function bayar()
    {
        return $this->paymentsTotal();
    }

    public function sisa()
    {
        return $this->grandTotal() - $this->bayar();
    }

    public function totPengeluaran()
    {
        return $this->expensesTotal();
    }

    public function pendapatan()
    {
        return $this->bayar() + $this->order->penambahan;
    }

    public function pengeluaran()
    {
        return $this->order->pengurangan + $this->order->promo + $this->totPengeluaran();
    }

    public function labaKotor()
    {
        return $this->grandTotal() - $this->totPengeluaran();
    }

    public function uangDiterima()
    {
        return $this->bayar() - $this->totPengeluaran();
    }

    public function labaBersih()
    {
        return $this->uangDiterima();
    }

    public function pendapatanDp()
    {
        return $this->bayar();
    }

    public function totSisa()
    {
        return $this->uangDiterima();
    }
}
