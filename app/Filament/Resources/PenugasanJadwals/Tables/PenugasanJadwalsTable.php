<?php

namespace App\Filament\Resources\PenugasanJadwals\Tables;

use App\Filament\Resources\PenugasanJadwals\PenugasanJadwalResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PenugasanJadwalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jadwalKerja.nama')
                    ->label('Jadwal')
                    ->badge()
                    ->sortable(),
                TextColumn::make('berlaku_dari')
                    ->label('Dari')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('berlaku_sampai')
                    ->label('Sampai')
                    ->date('d M Y')
                    ->placeholder('Masih berlaku')
                    ->sortable(),
                TextColumn::make('catatan')
                    ->limit(30)
                    ->toggleable(),
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
            ->defaultSort('berlaku_dari', 'desc')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Tambah Penugasan')
                    ->url(fn (): string => PenugasanJadwalResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
