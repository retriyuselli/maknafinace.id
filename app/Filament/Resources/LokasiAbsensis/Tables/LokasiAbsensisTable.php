<?php

namespace App\Filament\Resources\LokasiAbsensis\Tables;

use App\Filament\Resources\LokasiAbsensis\LokasiAbsensiResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LokasiAbsensisTable
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
                TextColumn::make('lintang')
                    ->label('Lintang')
                    ->sortable(),
                TextColumn::make('bujur')
                    ->label('Bujur')
                    ->sortable(),
                TextColumn::make('radius_meter')
                    ->label('Radius')
                    ->numeric()
                    ->suffix(' m')
                    ->sortable()
                    ->alignCenter(),
                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                TernaryFilter::make('aktif')
                    ->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('urutan', 'asc')
            ->emptyStateDescription('Belum ada lokasi kantor. Tambahkan titik geofence untuk absensi.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Tambah Lokasi')
                    ->url(fn (): string => LokasiAbsensiResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
