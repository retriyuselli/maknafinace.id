<?php

namespace App\Filament\Resources\PengaturanAbsensis\Tables;

use App\Filament\Resources\PengaturanAbsensis\PengaturanAbsensiResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PengaturanAbsensisTable
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
                TextColumn::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('jam_pulang')
                    ->label('Jam Pulang')
                    ->time('H:i')
                    ->sortable(),
                IconColumn::make('wajib_foto')
                    ->label('Wajib Foto')
                    ->boolean()
                    ->alignCenter(),
                IconColumn::make('wajib_lokasi')
                    ->label('Wajib Lokasi')
                    ->boolean()
                    ->alignCenter(),
                IconColumn::make('tolak_jika_di_luar_radius')
                    ->label('Tolak Luar Radius')
                    ->boolean()
                    ->alignCenter(),
                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
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
            ->defaultSort('nama', 'asc')
            ->emptyStateDescription('Belum ada pengaturan absensi. Buat aturan jam kerja dan geofence.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Pengaturan')
                    ->url(fn (): string => PengaturanAbsensiResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
