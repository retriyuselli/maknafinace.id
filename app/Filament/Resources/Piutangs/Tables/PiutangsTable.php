<?php

namespace App\Filament\Resources\Piutangs\Tables;

use App\Enums\JenisPiutang;
use App\Enums\StatusPiutang;
use App\Models\Piutang;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PiutangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_piutang')
                    ->label('Nomor Piutang')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('jenis_piutang')
                    ->label('Jenis')
                    ->formatStateUsing(fn ($state) => $state instanceof JenisPiutang ? $state->getLabel() : JenisPiutang::from($state)->getLabel())
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof JenisPiutang ? $state->value : $state) {
                        'operasional' => 'warning',
                        'pribadi' => 'danger',
                        'bisnis' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('nama_debitur')
                    ->label('Debitur')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('kontak_debitur')
                    ->label('Kontak')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_piutang')
                    ->label('Total Piutang')
                    ->numeric()
                    ->prefix('Rp. ')
                    ->sortable(),

                TextColumn::make('sudah_dibayar')
                    ->label('Sudah Dibayar')
                    ->numeric()
                    ->prefix('Rp. ')
                    ->sortable(),

                TextColumn::make('sisa_piutang')
                    ->label('Sisa Piutang')
                    ->numeric()
                    ->prefix('Rp. ')
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('tanggal_jatuh_tempo')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state instanceof StatusPiutang ? $state->getLabel() : StatusPiutang::from($state)->getLabel())
                    ->badge()
                    ->color(fn ($state) => $state instanceof StatusPiutang ? $state->getColor() : StatusPiutang::from($state)->getColor()),

                TextColumn::make('prioritas')
                    ->label('Prioritas')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'rendah' => 'gray',
                        'sedang' => 'info',
                        'tinggi' => 'warning',
                        'mendesak' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('jenis_piutang')
                    ->label('Jenis Piutang')
                    ->options(JenisPiutang::getOptions()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusPiutang::getOptions()),

                Filter::make('jatuh_tempo')
                    ->label('Akan Jatuh Tempo')
                    ->query(fn (Builder $query): Builder => $query->akanJatuhTempo(7)),

                Filter::make('sudah_jatuh_tempo')
                    ->label('Sudah Jatuh Tempo')
                    ->query(fn (Builder $query): Builder => $query->jatuhTempo()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('terima_pembayaran')
                    ->label('Terima Pembayaran')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    // ->url(fn (Piutang $record) => PembayaranPiutangResource::getUrl('create', ['piutang_id' => $record->id]))
                    ->visible(fn (Piutang $record) => in_array($record->status, [StatusPiutang::AKTIF, StatusPiutang::DIBAYAR_SEBAGIAN, StatusPiutang::JATUH_TEMPO])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal_jatuh_tempo', 'asc');
    }
}
