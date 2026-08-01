<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserExpirationOverview extends BaseWidget
{
    protected function getStats(): array
    {
        try {
            $now = Carbon::now();
            $inSevenDays = $now->copy()->addDays(7);

            // Satu query agregat — ganti 4× COUNT terpisah
            $stats = User::query()
                ->toBase()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN expire_date IS NULL OR expire_date > ? THEN 1 ELSE 0 END) as active', [$now])
                ->selectRaw('SUM(CASE WHEN expire_date IS NOT NULL AND expire_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as expiring_soon', [$now, $inSevenDays])
                ->selectRaw('SUM(CASE WHEN expire_date IS NOT NULL AND expire_date <= ? THEN 1 ELSE 0 END) as expired', [$now])
                ->first();

            return [
                Stat::make('Total User', (int) ($stats->total ?? 0))
                    ->description('Total semua pengguna')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('info'),

                Stat::make('User Aktif', (int) ($stats->active ?? 0))
                    ->description('Pengguna yang masih aktif')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),

                Stat::make('Akan Kedaluwarsa', (int) ($stats->expiring_soon ?? 0))
                    ->description('Dalam 7 hari ke depan')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('warning'),

                Stat::make('Sudah Kedaluwarsa', (int) ($stats->expired ?? 0))
                    ->description('Perlu diperpanjang')
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color('danger'),
            ];
        } catch (Exception $e) {
            return [
                Stat::make('Error', 'Tidak dapat memuat data')
                    ->description('Error: '.$e->getMessage())
                    ->color('gray'),
            ];
        }
    }
}
