<?php

namespace App\Filament\Resources\Absensis\Tables;

use App\Exports\AbsensiExport;
use App\Filament\Resources\Absensis\AbsensiResource;
use App\Models\Absensi;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;

class AbsensisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Absensi::STATUS_HADIR => 'Hadir',
                        Absensi::STATUS_TERLAMBAT => 'Terlambat',
                        Absensi::STATUS_ALFA => 'Alfa',
                        Absensi::STATUS_CUTI => 'Cuti',
                        Absensi::STATUS_LIBUR => 'Libur',
                        Absensi::STATUS_LIBUR_MINGGUAN => 'Libur Mingguan',
                        Absensi::STATUS_SETENGAH_HARI => 'Setengah Hari',
                        Absensi::STATUS_REMOTE => 'Remote',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        Absensi::STATUS_HADIR => 'success',
                        Absensi::STATUS_TERLAMBAT => 'warning',
                        Absensi::STATUS_ALFA => 'danger',
                        Absensi::STATUS_CUTI => 'info',
                        Absensi::STATUS_LIBUR, Absensi::STATUS_LIBUR_MINGGUAN => 'gray',
                        Absensi::STATUS_SETENGAH_HARI => 'warning',
                        Absensi::STATUS_REMOTE => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('jam_pulang')
                    ->label('Jam Pulang')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('menit_terlambat')
                    ->label('Terlambat')
                    ->numeric()
                    ->suffix(' mnt')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('menit_kerja')
                    ->label('Menit Kerja')
                    ->numeric()
                    ->suffix(' mnt')
                    ->sortable()
                    ->placeholder('-')
                    ->alignCenter(),
                TextColumn::make('sumber')
                    ->label('Sumber')
                    ->badge()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Absensi::STATUS_HADIR => 'Hadir',
                        Absensi::STATUS_TERLAMBAT => 'Terlambat',
                        Absensi::STATUS_ALFA => 'Alfa',
                        Absensi::STATUS_CUTI => 'Cuti',
                        Absensi::STATUS_LIBUR => 'Libur',
                        Absensi::STATUS_LIBUR_MINGGUAN => 'Libur Mingguan',
                        Absensi::STATUS_SETENGAH_HARI => 'Setengah Hari',
                        Absensi::STATUS_REMOTE => 'Remote',
                    ]),
                Filter::make('tanggal')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('dari')
                            ->label('Dari')
                            ->native(false),
                        DatePicker::make('sampai')
                            ->label('Sampai')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('export_excel')
                        ->label('Export Excel')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->action(function (Collection $records) {
                            return Excel::download(
                                new AbsensiExport($records->loadMissing('user')),
                                'absensi-terpilih-'.now()->format('YmdHis').'.xlsx'
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('tanggal', 'desc')
            ->emptyStateDescription('Belum ada data rekap absensi.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Absensi')
                    ->url(fn (): string => AbsensiResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
