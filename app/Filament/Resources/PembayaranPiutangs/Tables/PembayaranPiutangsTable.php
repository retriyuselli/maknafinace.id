<?php

namespace App\Filament\Resources\PembayaranPiutangs\Tables;

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
use Illuminate\Support\Facades\Auth;

class PembayaranPiutangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_pembayaran')
                    ->label('Nomor Pembayaran')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('piutang.nomor_piutang')
                    ->label('Nomor Piutang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('piutang.nama_debitur')
                    ->label('Debitur')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('total_pembayaran')
                    ->label('Total Pembayaran')
                    ->numeric()
                    ->prefix('Rp. ')
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('paymentMethod.name')
                    ->label('Metode'),

                TextColumn::make('tanggal_pembayaran')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'dikonfirmasi' => 'success',
                        'dibatalkan' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('dikonfirmasiOleh.name')
                    ->label('Dikonfirmasi Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'dikonfirmasi' => 'Dikonfirmasi',
                        'dibatalkan' => 'Dibatalkan',
                    ]),

                SelectFilter::make('payment_method_id')
                    ->label('Metode Pembayaran')
                    ->relationship('paymentMethod', 'name'),

                Filter::make('tanggal_pembayaran')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('dari'),
                        \Filament\Forms\Components\DatePicker::make('sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_pembayaran', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_pembayaran', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('konfirmasi')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (\App\Models\PembayaranPiutang $record) {
                        $record->update([
                            'status' => 'dikonfirmasi',
                            'dikonfirmasi_oleh' => Auth::id(),
                        ]);
                    })
                    ->visible(fn (\App\Models\PembayaranPiutang $record) => $record->status === 'pending'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
