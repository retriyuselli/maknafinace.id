<?php

namespace App\Filament\Resources\SimulasiProduks\Tables;

use App\Models\SimulasiProduk;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SimulasiProduksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->poll('5s')
            ->columns([
                TextColumn::make('prospect.name_event')
                    ->label('Prospect Name')
                    ->searchable()->sortable()
                    ->weight('bold')
                    ->formatStateUsing(fn (string $state): string => Str::title($state))
                    ->description(fn (SimulasiProduk $record): string => $record->product ?  ''.$record
                        ->product->name : Str::limit($record->notes ?? '', 30)),
                TextColumn::make('total_price')
                    ->label('Base Price')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('promo')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignEnd(),
                TextColumn::make('penambahan')
                    ->label('Addition')
                    ->money('IDR')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignEnd(),
                TextColumn::make('pengurangan')
                    ->label('Reduction')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)->alignEnd(),
                TextColumn::make('grand_total')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),
                TextColumn::make('user.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([ActionGroup::make([
                EditAction::make(),
                Action::make('view_simulasi')
                    ->label('View Simulasi')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->url(fn (SimulasiProduk $record) => route('simulasi.show', $record))
                    ->openUrlInNewTab(),
                DeleteAction::make()]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()]),
            ])
            ->emptyStateHeading('No Simulations Found')
            ->emptyStateDescription('Create your first simulation to get started.')
            ->emptyStateIcon('heroicon-o-calculator')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create Simulation')
                    ->icon('heroicon-m-plus')
                    ->url(route('filament.admin.resources.simulasi-produks.create'))
                    ->button(),
            ])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->poll('60s');
    }
}

