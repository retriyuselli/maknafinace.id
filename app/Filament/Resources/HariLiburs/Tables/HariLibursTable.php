<?php

namespace App\Filament\Resources\HariLiburs\Tables;

use App\Filament\Resources\HariLiburs\HariLiburResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class HariLibursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                IconColumn::make('nasional')
                    ->label('Nasional')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('tetap_masuk')
                    ->label('Tetap Masuk')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('nasional')
                    ->label('Nasional'),
                TernaryFilter::make('tetap_masuk')
                    ->label('Tetap Masuk'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('tanggal', 'desc')
            ->emptyStateDescription('Belum ada hari libur. Tambahkan libur nasional atau internal.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Tambah Hari Libur')
                    ->url(fn (): string => HariLiburResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
