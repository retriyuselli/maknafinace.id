<?php

namespace App\Filament\Resources\Prospects\Pages;

use App\Filament\Resources\Prospects\ProspectResource;
use App\Models\Prospect;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class ProspectsThisWeek extends Page
{
    protected static string $resource = ProspectResource::class;

    protected static ?string $title = 'Prospek Minggu Ini';

    protected string $view = 'filament.resources.prospects.pages.prospects-this-week';

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
        $from = Carbon::now()->startOfWeek()->startOfDay();
        $until = Carbon::now()->endOfWeek()->endOfDay();

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

