<?php

namespace App\Filament\Resources\DataPembayarans\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class InvoiceStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // 1. Total Belum Lunas (All time)
        $belumLunasCount = Order::where('is_paid', false)->count();

        // 2. Total Lunas (All time)
        $lunasCount = Order::where('is_paid', true)->count();

        // Current Month & Year
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // 3. Acara Bulan Ini Belum Lunas
        // Asumsi: Tanggal acara menggunakan 'date_resepsi' dari relasi prospect
        $acaraBulanIniBelumLunas = Order::where('is_paid', false)
            ->whereHas('prospect', function ($query) use ($currentMonth, $currentYear) {
                $query->whereMonth('date_resepsi', $currentMonth)
                      ->whereYear('date_resepsi', $currentYear);
            })
            ->count();

        // 4. Acara Bulan Ini Lunas
        $acaraBulanIniLunas = Order::where('is_paid', true)
            ->whereHas('prospect', function ($query) use ($currentMonth, $currentYear) {
                $query->whereMonth('date_resepsi', $currentMonth)
                      ->whereYear('date_resepsi', $currentYear);
            })
            ->count();

        return [
            Stat::make('Belum Lunas', $belumLunasCount)
                ->description('Total invoice belum lunas')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Lunas', $lunasCount)
                ->description('Total invoice lunas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Acara Bulan Ini (Belum Lunas)', $acaraBulanIniBelumLunas)
                ->description('Acara bulan ini yang belum lunas')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),

            Stat::make('Acara Bulan Ini (Lunas)', $acaraBulanIniLunas)
                ->description('Acara bulan ini yang sudah lunas')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
        ];
    }
}
