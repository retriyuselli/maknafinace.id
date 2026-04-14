<?php

namespace App\Filament\Resources\Prospects\Pages;

use App\Filament\Resources\Prospects\ProspectResource;
use App\Models\Prospect;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class ProspectsThisMonth extends Page
{
    protected static string $resource = ProspectResource::class;

    protected static ?string $title = 'Prospek Bulan Ini';

    protected string $view = 'filament.resources.prospects.pages.prospects-this-month';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(fn (): string => ProspectResource::getUrl('index')),
        ];
    }

    protected function getViewData(): array
    {
        $from = Carbon::now()->startOfMonth()->startOfDay();
        $until = Carbon::now()->endOfMonth()->endOfDay();

        $prospects = Prospect::query()
            ->withTrashed()
            ->with(['user:id,name', 'latestOrder'])
            ->whereBetween('created_at', [$from, $until])
            ->orderByDesc('created_at')
            ->get();

        return [
            'prospects' => $prospects,
            'from' => $from->toDateString(),
            'until' => $until->toDateString(),
        ];
    }
}

