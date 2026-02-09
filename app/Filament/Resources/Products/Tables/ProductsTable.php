<?php

namespace App\Filament\Resources\Products\Tables;

use App\Exports\ProductExport;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->formatStateUsing(fn (string $state): string => Str::title($state))
                    ->tooltip(fn (Product $record): string => $record->price)
                    ->copyable()
                    ->copyMessage('Product name copied')
                    ->copyMessageDuration(1500)
                    ->description(function (Product $record): string {
                        $priceValue = $record->price;
                        if ($priceValue === null || ! is_numeric($priceValue)) {
                            return 'Rp -';
                        }

                        return 'Rp '.number_format((int) $priceValue, 0, '.', ',');
                    }),

                TextColumn::make('slug')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Vendors')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state > 3 => 'success',
                        $state > 1 => 'info',
                        default => 'warning',
                    })
                    ->tooltip('Number of vendors associated with this product'),

                TextColumn::make('unique_orders_count')
                    ->label('In Orders')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->tooltip('Number of unique orders this product is part of.'),

                TextColumn::make('total_quantity_sold')
                    ->label('Total Sold')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, '.', ','))
                    ->sortable()
                    ->alignCenter()
                    ->tooltip('Total quantity of this product sold across all orders.'),

                TextColumn::make('price')
                    ->label('Product Price')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                    ->sortable()
                    ->alignEnd()
                    ->badge(),

                TextColumn::make('product_price')
                    ->label('Total Publish Price')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('pengurangan')
                    ->label('Pengurangan')
                    ->getStateUsing(fn ($record) => $record->pengurangans->sum('amount'))
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                    ->alignEnd()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state == 0 ? 'warning' : 'danger'),

                TextColumn::make('penambahan')
                    ->label('Penambahan Publish')
                    ->getStateUsing(fn ($record) => $record->penambahanHarga->sum('harga_publish'))
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                    ->alignEnd()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state == 0 ? 'warning' : 'success'),

                TextColumn::make('pax')
                    ->label('Capacity')
                    ->suffix(' pax')
                    ->alignCenter()
                    ->sortable()
                    ->numeric(
                        decimalPlaces: 0,
                        thousandsSeparator: '.',
                    )
                    ->color(fn (int $state): string => match (true) {
                        $state > 1000 => 'success',
                        $state > 500 => 'info',
                        default => 'gray',
                    }),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Status')
                    ->alignCenter()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->tooltip(fn (bool $state): string => $state ? 'Product is active' : 'Product is inactive'),

                IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved')
                    ->alignCenter()
                    ->trueIcon('heroicon-s-hand-thumb-up')
                    ->falseIcon('heroicon-s-hand-thumb-down')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->tooltip(fn (bool $state): string => $state ? 'Product is approved' : 'Product is not approved'),
                TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn (Product $record): string => 'Created: '.$record->created_at->diffForHumans()),

                TextColumn::make('updated_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),

                SelectFilter::make('is_approved')
                    ->label('Approved')
                    ->options([
                        1 => 'Approved',
                        0 => 'Not Approved',
                    ]),

                Filter::make('vendor_usage')
                    ->label('Vendor Usage')
                    ->schema([
                        Select::make('usage')
                            ->label('Filter')
                            ->options([
                                'with' => 'Dengan Vendor',
                                'without' => 'Tanpa Vendor',
                            ])
                            ->placeholder('Semua Produk'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            ($data['usage'] ?? null) === 'with',
                            fn (Builder $q): Builder => $q->whereHas('items'),
                        )->when(
                            ($data['usage'] ?? null) === 'without',
                            fn (Builder $q): Builder => $q->whereDoesntHave('items'),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! empty($data['usage'])) {
                            return 'Vendor: '.($data['usage'] === 'with' ? 'Ada' : 'Tidak Ada');
                        }

                        return null;
                    }),

                Filter::make('price_range')
                    ->label('Rentang Harga')
                    ->schema([
                        TextInput::make('min')
                            ->numeric()
                            ->placeholder('Min'),
                        TextInput::make('max')
                            ->numeric()
                            ->placeholder('Max'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $min = $data['min'] ?? null;
                        $max = $data['max'] ?? null;

                        return $query
                            ->when($min !== null && $min !== '', fn (Builder $q): Builder => $q->where('price', '>=', $min))
                            ->when($max !== null && $max !== '', fn (Builder $q): Builder => $q->where('price', '<=', $max));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $min = $data['min'] ?? null;
                        $max = $data['max'] ?? null;

                        if ($min !== null || $max !== null) {
                            if ($min && $max) {
                                return 'Harga: Rp '.$min.' - Rp '.$max;
                            }
                            if ($min) {
                                return 'Harga >= Rp '.$min;
                            }
                            if ($max) {
                                return 'Harga <= Rp '.$max;
                            }
                        }

                        return null;
                    }),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),

                    // Aksi Preview Detail
                    Action::make('preview_details')
                        ->label('Preview Detail')
                        ->icon('heroicon-o-eye')
                        ->color('info') // Warna tombol/link
                        ->url(fn (Product $record): string => route('products.details', ['product' => $record, 'action' => 'preview'])) // <-- Use 'products.details'
                        ->openUrlInNewTab() // Buka di tab baru
                        ->tooltip('Lihat detail lengkap produk di tab baru'),
                    DeleteAction::make(),
                    Action::make('duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalDescription('Do you want to duplicate this product and its vendor relations?')
                        ->modalSubmitActionLabel('Yes, duplicate product')
                        ->action(function (Product $record) {
                            // Duplicate main product
                            $attributes = $record->only([
                                'category_id',
                                'price',
                                'is_active',
                                'pax',
                            ]);

                            $duplicate = new Product($attributes);
                            $duplicate->name = "{$record->name} (Copy)";
                            $duplicate->slug = Product::generateUniqueSlug($duplicate->name);
                            $duplicate->save();

                            // Duplicate vendor relationships with all fields
                            foreach ($record->items as $item) {
                                $duplicate->items()->create([
                                    'vendor_id' => $item->vendor_id,
                                    'harga_publish' => $item->harga_publish,
                                    'quantity' => $item->quantity,
                                    'price_public' => $item->price_public,
                                    'total_price' => $item->total_price,
                                    'harga_vendor' => $item->harga_vendor,
                                    'description' => $item->description,
                                ]);
                            }

                            Notification::make()
                                ->success()
                                ->title('Product duplicated successfully')
                                ->send();
                        })
                        ->tooltip('Duplicate this product'),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Product $record) {
                            $record->update(['is_approved' => true]);
                            Notification::make()->title('Product Approved')->success()->send();
                        })
                        ->visible(function (Product $record): bool {
                            /** @var User $user */
                            $user = Auth::user();
                            return ! $record->is_approved && $user?->hasRole('super_admin');
                        })
                        ->tooltip('Approve this product'),

                    Action::make('disapprove')
                        ->label('Disapprove')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Product $record) {
                            $record->update(['is_approved' => false]);
                            Notification::make()->title('Product Disapproved')->warning()->send();
                        })
                        ->visible(function (Product $record): bool {
                            /** @var User $user */
                            $user = Auth::user();
                            return $record->is_approved && $user?->hasRole('super_admin');
                        })
                        ->tooltip('Disapprove this product'),

                ])
                    ->tooltip('Available actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Ganti ExportBulkAction bawaan Filament
                    BulkAction::make('export_selected_maatwebsite')
                        ->label('Export Selected (Excel)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            return Excel::download(new ProductExport($records->pluck('id')->toArray()), 'products_export_'.now()->format('YmdHis').'.xlsx');
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation(),
                    RestoreBulkAction::make(),
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['is_active' => true]);
                            Notification::make()
                                ->title('Products Activated')
                                ->body(count($records).' product(s) have been activated.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['is_active' => false]);
                            Notification::make()
                                ->title('Products Deactivated')
                                ->body(count($records).' product(s) have been deactivated.')
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-s-hand-thumb-up')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['is_approved' => true]);
                            Notification::make()
                                ->title('Products Approved')
                                ->body(count($records).' product(s) have been approved.')
                                ->success()
                                ->send();
                        })
                        ->visible(function (): bool {
                            /** @var User $user */
                            $user = Auth::user();
                            return $user->hasRole('super_admin');
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50]);
    }
}