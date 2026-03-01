<?php

namespace App\Filament\Resources\DataPribadis\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DataPribadisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('foto')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => $record->nama_lengkap ? 'https://ui-avatars.com/api/?name='.urlencode($record->nama_lengkap).'&color=FFFFFF&background=0D83DD' : null),
                TextColumn::make('nama_lengkap')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-s-envelope'),
                TextColumn::make('nomor_telepon')
                    ->searchable()
                    ->prefix('+62')
                    ->icon('heroicon-s-phone'),
                TextColumn::make('tanggal_lahir')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('jenis_kelamin')
                    ->badge()
                    ->colors([
                        'success' => 'Laki-laki',
                        'warning' => 'Perempuan',
                    ])
                    ->searchable(),
                TextColumn::make('pekerjaan')
                    ->searchable(),
                TextColumn::make('gaji')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateHeading('Tidak ada data pribadi ditemukan')
            ->emptyStateDescription('Silakan buat data pribadi baru untuk memulai.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Data Pribadi Baru')
                    ->url(fn () => route('filament.admin.resources.data-pribadis.create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
