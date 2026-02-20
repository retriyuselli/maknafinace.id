<?php

namespace App\Services;

use App\Models\Order;

class OrderFinance
{
    public function __construct(protected Order $order)
    {
    }

    public static function for(Order $order): self
    {
        return new self($order);
    }

    public static function computeGrandTotalFromValues(float $totalPrice, float $penambahan, float $promo, float $pengurangan): float
    {
        return $totalPrice + $penambahan - $promo - $pengurangan;
    }

    public function grandTotalBase(): float
    {
        return self::computeGrandTotalFromValues(
            (float) $this->order->total_price,
            (float) $this->order->penambahan,
            (float) $this->order->promo,
            (float) $this->order->pengurangan
        );
    }

    public function paymentsTotal(): float
    {
        return (float) $this->order->dataPembayaran->sum('nominal');
    }

    public function expensesTotal(): float
    {
        return (float) $this->order->dataPengeluaran->sum('amount');
    }

    public function grandTotal(): float
    {
        return $this->grandTotalBase();
    }

    public function bayar(): float
    {
        return $this->paymentsTotal();
    }

    public function sisa(): float
    {
        return $this->grandTotal() - $this->bayar();
    }

    public function totPengeluaran(): float
    {
        return $this->expensesTotal();
    }

    public function pendapatan(): float
    {
        return $this->bayar() + (float) $this->order->penambahan;
    }

    public function pengeluaran(): float
    {
        return (float) $this->order->pengurangan + (float) $this->order->promo + $this->totPengeluaran();
    }

    public function labaKotor(): float
    {
        return $this->grandTotal() - $this->totPengeluaran();
    }

    public function uangDiterima(): float
    {
        return $this->bayar() - $this->totPengeluaran();
    }

    public function labaBersih(): float
    {
        return $this->uangDiterima();
    }

    public function pendapatanDp(): float
    {
        return $this->bayar();
    }

    public function totSisa(): float
    {
        return $this->uangDiterima();
    }
}

