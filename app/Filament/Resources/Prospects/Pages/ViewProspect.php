<?php

namespace App\Filament\Resources\Prospects\Pages;

use App\Filament\Resources\Prospects\ProspectResource;
use App\Models\Prospect;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViewProspect extends ListRecords
{
    protected static string $resource = ProspectResource::class;

    public function getTitle(): string
    {
        return match ((string) request()->query('metric')) {
            'with_orders' => 'Prospek dengan Order',
            'month' => 'Prospek Bulan Ini',
            'week' => 'Prospek Minggu Ini',
            'today' => 'Prospek Hari Ini',
            default => 'Prospek',
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_prospects')
                ->label('Kembali')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(fn () => ProspectResource::getUrl('index')),
        ];
    }

    public function table(Table $table): Table
    {
        return ProspectResource::table($table)
            ->filters([])
            ->toolbarActions([]);
    }

    protected function getTableQuery(): Builder
    {
        $metric = (string) request()->query('metric');

        $query = Prospect::query()
            ->withTrashed()
            ->with(['user:id,name', 'latestOrder']);

        return match ($metric) {
            'with_orders' => $query->whereHas('orders'),
            'month' => $query->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ]),
            'week' => $query->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]),
            'today' => $query->whereDate('created_at', Carbon::today()),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
