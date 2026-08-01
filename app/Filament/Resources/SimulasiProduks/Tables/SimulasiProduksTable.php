<?php

namespace App\Filament\Resources\SimulasiProduks\Tables;

use App\Enums\OrderStatus;
use App\Models\SimulasiProduk;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SimulasiProduksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('prospect.name_event')
                    ->label('Prospect Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? Str::title($state) : '-')
                    ->description(fn (SimulasiProduk $record): string => $record->product
                        ? Str::title(Str::lower((string) ($record->product->name ?? '')))
                        : Str::title(Str::lower(Str::limit($record->notes ?? '', 30)))),
                TextColumn::make('order_status_display')
                    ->label('Status Pesanan')
                    ->badge()
                    ->getStateUsing(function (SimulasiProduk $record): string {
                        $latestOrder = $record->prospect?->latestOrder;

                        if (! $latestOrder) {
                            return 'no_order';
                        }

                        if ($latestOrder->status instanceof OrderStatus) {
                            return $latestOrder->status->value;
                        }

                        return filled($latestOrder->status) ? (string) $latestOrder->status : 'unknown';
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'processing',
                        'primary' => 'done',
                        'danger' => 'cancelled',
                        'gray' => 'no_order',
                        'secondary' => 'unknown',
                    ])
                    ->formatStateUsing(function (string $state): string {
                        if ($state === 'no_order') {
                            return 'Belum Ada Order';
                        }

                        return OrderStatus::tryFrom($state)?->getLabel() ?? 'Tidak Diketahui';
                    })
                    ->sortable(false),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable()
                    ->searchable(),
                TextColumn::make('total_price')
                    ->label('Base Price')
                    ->formatStateUsing(fn ($state) => 'Rp. '.number_format((float) $state, 0, '.', ','))
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('penambahan')
                    ->label('Addition')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Rp. '.number_format((float) $state, 0, '.', ','))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignEnd(),
                TextColumn::make('pengurangan')
                    ->label('Reduction')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Rp. '.number_format((float) $state, 0, '.', ','))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignEnd(),
                TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Rp. '.number_format((float) $state, 0, '.', ','))
                    ->alignEnd()
                    ->weight(FontWeight::Bold)
                    ->summarize(Sum::make()->label('Total')),
                TextColumn::make('user.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('order_status')
                    ->label('Status Pesanan')
                    ->options([
                        'no_order' => 'Belum Ada Order',
                        OrderStatus::Pending->value => OrderStatus::Pending->getLabel(),
                        OrderStatus::Processing->value => OrderStatus::Processing->getLabel(),
                        OrderStatus::Done->value => OrderStatus::Done->getLabel(),
                        OrderStatus::Cancelled->value => OrderStatus::Cancelled->getLabel(),
                        'unknown' => 'Tidak Diketahui',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if (! filled($value)) {
                            return $query;
                        }

                        if ($value === 'no_order') {
                            return $query->whereDoesntHave('prospect.orders');
                        }

                        if ($value === 'unknown') {
                            return $query->whereHas('prospect.latestOrder', fn (Builder $q) => $q->whereNull('status'));
                        }

                        return $query->whereHas('prospect.latestOrder', fn (Builder $q) => $q->where('status', $value));
                    }),
                SelectFilter::make('user_id')
                    ->label('Created By')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('view_simulasi')
                        ->label('View Simulasi')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->url(fn (SimulasiProduk $record) => route('simulasi.show', $record))
                        ->openUrlInNewTab(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No Simulations Found')
            ->emptyStateDescription('Create your first simulation to get started.')
            ->emptyStateIcon('heroicon-o-calculator')
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50])
            ->poll('60s');
    }
}
