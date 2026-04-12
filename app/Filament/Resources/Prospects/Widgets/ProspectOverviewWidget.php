<?php

namespace App\Filament\Resources\Prospects\Widgets;

use App\Filament\Resources\Prospects\ProspectResource;
use App\Models\Prospect;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProspectOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $monthProspects = Prospect::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $withOrders = Prospect::whereHas('orders')->count();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $weekProspects = Prospect::whereBetween('created_at', [$weekStart, $weekEnd])
            ->count();

        $todayProspects = Prospect::whereDate('created_at', Carbon::today())->count();
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();
        $weekStartDate = Carbon::now()->startOfWeek()->toDateString();
        $weekEndDate = Carbon::now()->endOfWeek()->toDateString();

        return [
            Stat::make('Dengan Order', $withOrders)
                ->icon('heroicon-o-clipboard-document-check')
                ->color('success'),

            Stat::make('Prospek Bulan Ini', $monthProspects)
                ->icon('heroicon-o-users')
                ->color('primary')
                ->url(ProspectResource::getUrl('index', [
                    'tableFilters' => [
                        'created_at' => [
                            'from_date' => $monthStart,
                            'until_date' => $monthEnd,
                        ],
                    ],
                ])),

            Stat::make('Prospek Minggu Ini', $weekProspects)
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->url(ProspectResource::getUrl('index', [
                    'tableFilters' => [
                        'created_at' => [
                            'from_date' => $weekStartDate,
                            'until_date' => $weekEndDate,
                        ],
                    ],
                ])),

            Stat::make('Prospek Hari Ini', $todayProspects)
                ->icon('heroicon-o-clock')
                ->color('info'),
        ];
    }
}
