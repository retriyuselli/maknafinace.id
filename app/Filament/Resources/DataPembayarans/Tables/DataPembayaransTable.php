<?php

namespace App\Filament\Resources\DataPembayarans\Tables;

use App\Enums\OrderStatus;
use App\Models\DataPembayaran;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DataPembayaransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.name')
                    ->label('Order Number')
                    ->searchable()
                    ->label('Project')
                    ->sortable()
                    ->copyable(),

                TextColumn::make('tgl_bayar')
                    ->label('Payment Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->formatStateUsing(fn (string $state): string => 'Rp. '.number_format($state, 0, ',', '.'))
                    ->summarize([
                        Sum::make()
                            ->formatStateUsing(fn (string $state): string => 'Rp. '.number_format($state, 0, ',', '.')),
                    ])
                    ->sortable(),

                ImageColumn::make('image')
                    ->label('Payment Proof')
                    ->circular(false)
                    ->sortable()
                    ->square(),

                TextColumn::make('keterangan')
                    ->label('Pembayaran')
                    ->searchable()
                    ->toggleable()
                    ->wrap(),
            ])
            ->defaultSort('tgl_bayar', 'desc')
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('order_status')
                    ->label('Order Status')
                    ->options(OrderStatus::class)
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('order', function (Builder $orderQuery) use ($data) {
                            $orderQuery->where('status', $data['value']);
                        });
                    }),
                SelectFilter::make('payment_method')
                    ->relationship('paymentMethod', 'name')
                    ->preload()
                    ->multiple()
                    ->label('Payment Method'),

                Filter::make('date_range')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tgl_bayar', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tgl_bayar', '<=', $date),
                            );
                    }),
            ])
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->visible(fn (?DataPembayaran $record): bool => $record && ! $record->trashed() && ! $record->order_id)
                        ->requiresConfirmation(),
                    RestoreAction::make()
                        ->visible(fn (?DataPembayaran $record): bool => $record && $record->trashed()),
                    ForceDeleteAction::make()
                        ->visible(fn (?DataPembayaran $record): bool => $record && $record->trashed())
                        ->requiresConfirmation(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('restricted_delete')
                        ->label('Hapus Terpilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $deletable = $records->filter(fn ($r) => ! $r->order_id && ! $r->trashed());
                            $skipped = $records->count() - $deletable->count();

                            $deleted = 0;
                            foreach ($deletable as $rec) {
                                $rec->delete();
                                $deleted++;
                            }

                            if ($deleted > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Hapus selesai')
                                    ->body("Berhasil menghapus {$deleted} data.")
                                    ->send();
                            }

                            if ($skipped > 0) {
                                Notification::make()
                                    ->danger()
                                    ->title('Sebagian dilewati')
                                    ->body("{$skipped} data terhubung ke Order dan tidak bisa dihapus")
                                    ->send();
                            }
                        }),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50]);
    }
}
