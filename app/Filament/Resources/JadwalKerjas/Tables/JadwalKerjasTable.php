<?php

namespace App\Filament\Resources\JadwalKerjas\Tables;

use App\Filament\Resources\JadwalKerjas\JadwalKerjaResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class JadwalKerjasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('kode')
                    ->label('Kode')
                    ->badge()
                    ->sortable(),
                IconColumn::make('default')
                    ->label('Default')
                    ->boolean(),
                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('hari_jadwal_kerjas_count')
                    ->counts('hariJadwalKerjas')
                    ->label('Hari')
                    ->alignCenter(),
            ])
            ->filters([
                TernaryFilter::make('aktif')->label('Aktif'),
                TernaryFilter::make('default')->label('Default'),
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
            ->defaultSort('nama')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Tambah Jadwal')
                    ->url(fn (): string => JadwalKerjaResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
