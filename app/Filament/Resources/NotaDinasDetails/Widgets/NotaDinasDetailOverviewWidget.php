<?php

namespace App\Filament\Resources\NotaDinasDetails\Widgets;

use App\Models\NotaDinasDetail;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class NotaDinasDetailOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $currentYear = Carbon::now()->year;

        $totalDetails = NotaDinasDetail::count();
        $totalTransferThisYear = NotaDinasDetail::whereYear('created_at', $currentYear)->sum('jumlah_transfer');
        $invoicesRecorded = NotaDinasDetail::whereNotNull('invoice_number')->count();
        $invoicesPaid = NotaDinasDetail::where('status_invoice', 'sudah_dibayar')->count();
        $invoicesUnpaid = NotaDinasDetail::where(function ($q) {
            $q->whereNull('status_invoice')->orWhere('status_invoice', '!=', 'sudah_dibayar');
        })->count();

        $transferTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = Carbon::now()->subMonths($i);
            $transferTrend[] = NotaDinasDetail::whereMonth('created_at', $d->month)
                ->whereYear('created_at', $d->year)
                ->sum('jumlah_transfer');
        }

        $currentMonth = Carbon::now()->month;
        $largestTransactionRecord = NotaDinasDetail::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->orderByDesc('jumlah_transfer')
            ->first();
        $largestTransactionThisMonth = (int) ($largestTransactionRecord->jumlah_transfer ?? 0);
        $totalTransferCurrentMonth = NotaDinasDetail::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('jumlah_transfer');

        return [
            Stat::make('Total Detail', (string) $totalDetails)
                ->description('Semua detail ND')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),
            Stat::make('Transfer Tahun Ini', ''.Number::format($totalTransferThisYear, 0))
                ->description('Tahun '.$currentYear)
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info')
                ->chart($transferTrend),
            Stat::make('Total Transaksi Bulan Ini', ''.Number::format($totalTransferCurrentMonth, 0))
                ->description('Bulan '.Carbon::now()->translatedFormat('F').' '.$currentYear)
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->url(route('filament.admin.resources.nota-dinas-details.current-month')),
            Stat::make('Invoice Tercatat', (string) $invoicesRecorded)
                ->description('Memiliki nomor invoice')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('secondary'),
            Stat::make('Sudah Dibayar', (string) $invoicesPaid)
                ->description('Status invoice dibayar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Belum Dibayar', (string) $invoicesUnpaid)
                ->description('Perlu tindak lanjut')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
            Stat::make(
                'Transaksi Terbesar Bulan Ini',
                ''.Number::format($largestTransactionThisMonth, 0)
            )
                ->description('Bulan '.Carbon::now()->translatedFormat('F').' '.$currentYear)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('danger')
                ->url($largestTransactionRecord ? route('filament.admin.resources.nota-dinas-details.edit', ['record' => $largestTransactionRecord->id]) : null),
        ];
    }
}
