<?php

namespace App\Filament\Resources\LeaveTypes\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaveTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Jenis Cuti')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('max_days_per_year')
                    ->label('Maksimal Hari/Tahun')
                    ->numeric()
                    ->sortable()
                    ->suffix(' hari')
                    ->alignCenter(),
                TextColumn::make('approved_count')
                    ->label('Disetujui')
                    ->getStateUsing(function ($record) {
                        return $record->leaveRequests()->where('status', 'approved')->count();
                    })
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->sortable(false),
                TextColumn::make('pending_count')
                    ->label('Menunggu')
                    ->getStateUsing(function ($record) {
                        return $record->leaveRequests()->where('status', 'pending')->count();
                    })
                    ->badge()
                    ->color('warning')
                    ->alignCenter()
                    ->sortable(false),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->sortable()
                    ->color('info'),
                TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Aktif')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->badge()
                    ->color(fn ($state) => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => $state ? 'Dihapus' : 'Aktif'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Filter Status')
                    ->placeholder('Semua Data')
                    ->trueLabel('Hanya yang Dihapus')
                    ->falseLabel('Tanpa yang Dihapus'),
                Filter::make('max_days_range')
                    ->label('Range Maksimal Hari')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('max_days_from')
                            ->label('Dari')
                            ->numeric()
                            ->suffix('hari'),
                        \Filament\Forms\Components\TextInput::make('max_days_to')
                            ->label('Sampai')
                            ->numeric()
                            ->suffix('hari'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['max_days_from'] ?? null,
                                fn (Builder $query, $days): Builder => $query->where('max_days_per_year', '>=', $days),
                            )
                            ->when(
                                $data['max_days_to'] ?? null,
                                fn (Builder $query, $days): Builder => $query->where('max_days_per_year', '<=', $days),
                            );
                    }),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\RestoreAction::make()
                    ->successNotificationTitle('Jenis cuti berhasil dipulihkan'),
                \Filament\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Jenis Cuti')
                    ->modalDescription('Apakah Anda yakin ingin menghapus jenis cuti ini? Data akan dipindahkan ke trash dan dapat dipulihkan.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->successNotificationTitle('Jenis cuti berhasil dihapus'),
                \Filament\Actions\ForceDeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Permanen Jenis Cuti')
                    ->modalDescription('Apakah Anda yakin ingin menghapus permanen jenis cuti ini? Data tidak dapat dipulihkan!')
                    ->modalSubmitActionLabel('Ya, Hapus Permanen')
                    ->successNotificationTitle('Jenis cuti berhasil dihapus permanen'),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\RestoreBulkAction::make()
                        ->successNotificationTitle('Jenis cuti terpilih berhasil dipulihkan'),
                    \Filament\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Jenis Cuti Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus jenis cuti yang dipilih? Data akan dipindahkan ke trash dan dapat dipulihkan.')
                        ->modalSubmitActionLabel('Ya, Hapus Semua')
                        ->successNotificationTitle('Jenis cuti terpilih berhasil dihapus'),
                    \Filament\Actions\ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Permanen Jenis Cuti Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus permanen jenis cuti yang dipilih? Data tidak dapat dipulihkan!')
                        ->modalSubmitActionLabel('Ya, Hapus Permanen Semua')
                        ->successNotificationTitle('Jenis cuti terpilih berhasil dihapus permanen'),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }
}
