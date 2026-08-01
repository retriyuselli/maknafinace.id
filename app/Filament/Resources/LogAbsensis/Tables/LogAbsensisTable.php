<?php

namespace App\Filament\Resources\LogAbsensis\Tables;

use App\Models\LogAbsensi;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LogAbsensisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        LogAbsensi::JENIS_MASUK => 'Masuk',
                        LogAbsensi::JENIS_PULANG => 'Pulang',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        LogAbsensi::JENIS_MASUK => 'success',
                        LogAbsensi::JENIS_PULANG => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('waktu')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('dalam_radius')
                    ->label('Dalam Radius')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Ya' : 'Tidak')
                    ->color(fn ($state): string => $state ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('jarak_ke_kantor_meter')
                    ->label('Jarak')
                    ->numeric()
                    ->suffix(' m')
                    ->sortable()
                    ->placeholder('-'),
                ImageColumn::make('path_foto')
                    ->label('Foto')
                    ->getStateUsing(fn (LogAbsensi $record): ?string => $record->temporaryFotoUrl(now()->addHour()))
                    ->checkFileExistence(false)
                    ->square()
                    ->toggleable(),
                TextColumn::make('foto_link')
                    ->label('File')
                    ->state(fn (LogAbsensi $record): string => $record->path_foto ? 'Buka Foto' : '-')
                    ->url(fn (LogAbsensi $record): ?string => $record->temporaryFotoUrl(now()->addHour()))
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->iconPosition(IconPosition::After)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('valid')
                    ->label('Valid')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('nama_perangkat')
                    ->label('Perangkat')
                    ->toggleable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options([
                        LogAbsensi::JENIS_MASUK => 'Masuk',
                        LogAbsensi::JENIS_PULANG => 'Pulang',
                    ]),
                TernaryFilter::make('valid')
                    ->label('Valid'),
                TernaryFilter::make('dalam_radius')
                    ->label('Dalam Radius'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ])
            ->defaultSort('waktu', 'desc')
            ->emptyStateDescription('Belum ada log absensi dari perangkat.');
    }
}
