<?php

namespace App\Filament\Resources\Products\Tables;

use App\Exports\ProductExport;
use App\Filament\Resources\Products\ProductResource;
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
use Filament\Tables\Columns\Summarizers\Sum;
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
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->formatStateUsing(fn (string $state): string => Str::title($state))
                    ->copyable()
                    ->copyMessage('Product name copied')
                    ->copyMessageDuration(1500)
                    ->description(function (Product $record): string {
                        // Gunakan nilai dari database (fisik) atau hasil agregasi query (virtual)
                        $productPrice = $record->product_price > 0 ? $record->product_price : ($record->calculated_product_price ?? 0);
                        $pengurangan = $record->pengurangan > 0 ? $record->pengurangan : ($record->calculated_pengurangan ?? 0);
                        $penambahan = $record->penambahan_publish > 0 ? $record->penambahan_publish : ($record->calculated_penambahan_publish ?? 0);

                        // Kalkulasi nett price
                        $nettPrice = $record->price > 0 ? $record->price : ($productPrice - $pengurangan + $penambahan);
                        
                        $priceValue = $nettPrice > 0 ? $nettPrice : $productPrice;
                        
                        if ($priceValue === null || ! is_numeric($priceValue) || $priceValue == 0) {
                            return 'Rp. -';
                        }

                        return 'Rp. '.number_format((int) $priceValue, 0, '.', ',');
                    }),

                TextColumn::make('slug')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
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

                TextColumn::make('stock')
                    ->label('Stock')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state < 5 => 'warning',
                        default => 'success',
                    })
                    ->icon(fn (int $state): string => $state === 0 ? 'heroicon-m-x-circle' : 'heroicon-m-archive-box')
                    ->sortable(),

                TextColumn::make('total_quantity_sold')
                    ->label('Total Sold')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, '.', ','))
                    ->sortable()
                    ->alignCenter()
                    ->tooltip('Total quantity of this product sold across all orders.')
                    ->summarize(Sum::make()->label('Total')),

                TextColumn::make('product_price')
                    ->label('Publish Price')
                    ->getStateUsing(fn ($record) => $record->product_price > 0 
                        ? $record->product_price 
                        : ($record->calculated_product_price ?? 0))
                    ->formatStateUsing(fn ($state) => 'Rp. '.number_format((int) $state, 0, '.', ','))
                    ->sortable()
                    ->alignEnd()
                    ->tooltip('Total harga publish dari semua vendor'),

                TextColumn::make('price')
                    ->label('Nett Price')
                    ->getStateUsing(function ($record) {
                        $productPrice = $record->product_price > 0 ? $record->product_price : ($record->calculated_product_price ?? 0);
                        $pengurangan = $record->pengurangan > 0 ? $record->pengurangan : ($record->calculated_pengurangan ?? 0);
                        $penambahan = $record->penambahan_publish > 0 ? $record->penambahan_publish : ($record->calculated_penambahan_publish ?? 0);
                        
                        return $record->price > 0 
                            ? $record->price 
                            : ($productPrice - $pengurangan + $penambahan);
                    })
                    ->formatStateUsing(fn ($state) => 'Rp. '.number_format((int) $state, 0, '.', ','))
                    ->sortable()
                    ->alignEnd()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->tooltip('Harga akhir setelah diskon & penambahan')
                    ->summarize(Sum::make()->label('Total Value')->money('IDR')),

                TextColumn::make('pengurangan')
                    ->label('Pengurangan')
                    ->getStateUsing(fn ($record) => $record->pengurangan > 0 
                        ? $record->pengurangan 
                        : ($record->calculated_pengurangan ?? 0))
                    ->formatStateUsing(fn ($state) => 'Rp. '.number_format((int) $state, 0, '.', ','))
                    ->alignEnd()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state == 0 ? 'warning' : 'danger'),

                TextColumn::make('penambahan')
                    ->label('Penambahan Harga')
                    ->getStateUsing(fn ($record) => $record->penambahan_publish > 0 
                        ? $record->penambahan_publish 
                        : ($record->calculated_penambahan_publish ?? 0))
                    ->formatStateUsing(fn ($state) => 'Rp. '.number_format((int) $state, 0, '.', ','))
                    ->alignEnd()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state == 0 ? 'warning' : 'success'),

                TextColumn::make('pax')
                    ->label('Capacity')
                    ->suffix(' pax')
                    ->icon('heroicon-m-users')
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

                Filter::make('stock_status')
                    ->label('Stok')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'out' => 'Stok Habis (0)',
                                'low' => 'Stok Rendah (< 5)',
                                'available' => 'Tersedia (> 0)',
                            ])
                            ->placeholder('Semua Stok'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['status'] === 'out', fn ($q) => $q->where('stock', 0))
                            ->when($data['status'] === 'low', fn ($q) => $q->where('stock', '>', 0)->where('stock', '<', 5))
                            ->when($data['status'] === 'available', fn ($q) => $q->where('stock', '>', 0));
                    }),

                Filter::make('pax_range')
                    ->label('Kapasitas (Pax)')
                    ->schema([
                        TextInput::make('min_pax')->numeric()->placeholder('Min'),
                        TextInput::make('max_pax')->numeric()->placeholder('Max'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min_pax'], fn ($q, $pax) => $q->where('pax', '>=', $pax))
                            ->when($data['max_pax'], fn ($q, $pax) => $q->where('pax', '<=', $pax));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['min_pax'] && $data['max_pax']) {
                            return 'Pax: ' . $data['min_pax'] . ' - ' . $data['max_pax'];
                        }
                        if ($data['min_pax']) {
                            return 'Pax >= ' . $data['min_pax'];
                        }
                        if ($data['max_pax']) {
                            return 'Pax <= ' . $data['max_pax'];
                        }
                        return null;
                    }),

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
                        ->modalDescription('Do you want to duplicate this product, its vendors, and all related settings?')
                        ->modalSubmitActionLabel('Yes, duplicate product')
                        ->action(function (Product $record, Action $action) {
                            // Duplicate main product with all essential fields
                            $attributes = $record->only([
                                'category_id',
                                'stock',
                                'product_price',
                                'price',
                                'is_active',
                                'pax',
                                'pax_akad',
                                'description',
                                'image',
                                'pengurangan',
                                'free_pengurangan',
                                'penambahan',
                                'penambahan_publish',
                                'penambahan_vendor',
                                'parent_id',
                            ]);

                            $duplicate = new Product($attributes);
                            $duplicate->name = "{$record->name} (Copy)";
                            $duplicate->slug = Product::generateUniqueSlug($duplicate->name);
                            $duplicate->is_approved = false; // Reset approval for the copy
                            $duplicate->save();

                            // Duplicate vendor relationships (items)
                            foreach ($record->items as $item) {
                                $duplicate->items()->create([
                                    'vendor_id' => $item->vendor_id,
                                    'harga_publish' => $item->harga_publish,
                                    'quantity' => $item->quantity,
                                    'price_public' => $item->price_public,
                                    'total_price' => $item->total_price,
                                    'harga_vendor' => $item->harga_vendor,
                                    'description' => $item->description,
                                    'kontrak_kerjasama' => $item->kontrak_kerjasama,
                                    'simulasi_produk_id' => $item->simulasi_produk_id,
                                ]);
                            }

                            // Duplicate discounts (pengurangans)
                            foreach ($record->pengurangans as $pengurangan) {
                                $duplicate->pengurangans()->create([
                                    'description' => $pengurangan->description,
                                    'amount' => $pengurangan->amount,
                                    'notes' => $pengurangan->notes,
                                ]);
                            }

                            // Duplicate additions (penambahanHarga)
                            foreach ($record->penambahanHarga as $addition) {
                                $duplicate->penambahanHarga()->create([
                                    'vendor_id' => $addition->vendor_id,
                                    'harga_publish' => $addition->harga_publish,
                                    'harga_vendor' => $addition->harga_vendor,
                                    'description' => $addition->description,
                                    'amount' => $addition->amount,
                                ]);
                            }

                            Notification::make()
                                ->success()
                                ->title('Product duplicated successfully')
                                ->send();

                            // Use redirect directly on the action to avoid Redirector error
                            return $action->redirect(ProductResource::getUrl('edit', ['record' => $duplicate]));
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
                    BulkAction::make('disapprove_selected')
                        ->label('Disapprove Selected')
                        ->icon('heroicon-s-hand-thumb-down')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['is_approved' => false]);
                            Notification::make()
                                ->title('Products Disapproved')
                                ->body(count($records).' product(s) have been disapproved.')
                                ->warning()
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
            ->paginationPageOptions([10, 25, 50])
            ->emptyStateDescription('Silakan buat produk baru untuk memulai.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Produk Baru')
                    ->url(fn (): string => ProductResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
