<?php

namespace App\Filament\Resources\Products;

use App\Exports\ProductExport;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\RawJs;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static ?string $modelLabel = 'Produk';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(ProductForm::configure());
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Data Produk yang telah dibuat dan dikelola';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
            'view' => ViewProduct::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount([
                'orders as unique_orders_count',
            ])
            // Bonus: Ini juga akan mengaktifkan kolom 'Total Sold'
            ->withSum('orderItems as total_quantity_sold', 'quantity');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return static::mutateFormDataBeforeSave($data);
    }

    protected function mutateFormDataBeforeUpdate(array $data): array
    {
        // Preserve existing image if not changed
        // This logic might need adjustment based on how FileUpload handles empty states
        // For now, we assume $data will not contain 'image' if it's not being updated.
        return static::mutateFormDataBeforeSave($data);
    }

    /**
     * Mutate form data before saving (both create and update).
     * This method recalculates product_price, pengurangan, penambahan, and price on the server-side
     * based on the submitted repeater data to ensure data integrity.
     */
    protected static function mutateFormDataBeforeSave(array $data): array
    {
        // Helper function to clean currency string values and convert to float
        $cleanCurrencyValue = function ($value): int {
            return ProductForm::stripCurrency($value);
        };

        // 1. Recalculate 'product_price' from 'items' (vendor repeater)
        $calculatedProductPrice = 0;
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                // 'price_public' is 'harga_publish' * 'quantity' for each vendor item
                $calculatedProductPrice += $cleanCurrencyValue($item['price_public'] ?? '0');
            }
        }
        $data['product_price'] = $calculatedProductPrice;

        // 2. Recalculate 'pengurangan' from 'itemsPengurangan' (discount repeater)
        $calculatedPengurangan = 0;
        if (isset($data['itemsPengurangan']) && is_array($data['itemsPengurangan'])) {
            foreach ($data['itemsPengurangan'] as $item) {
                $calculatedPengurangan += $cleanCurrencyValue($item['amount'] ?? '0');
            }
        }
        $data['pengurangan'] = $calculatedPengurangan;

        // 3. Recalculate 'penambahan_publish' and 'penambahan_vendor' from 'penambahanHarga' (addition repeater)
        $calculatedPenambahanPublish = 0;
        $calculatedPenambahanVendor = 0;
        if (isset($data['penambahanHarga']) && is_array($data['penambahanHarga'])) {
            foreach ($data['penambahanHarga'] as $key => $item) {
                $calculatedPenambahanPublish += $cleanCurrencyValue($item['harga_publish'] ?? '0');
                $calculatedPenambahanVendor += $cleanCurrencyValue($item['harga_vendor'] ?? '0');

                // Set amount field to harga_publish for compatibility with database
                $data['penambahanHarga'][$key]['amount'] = $cleanCurrencyValue($item['harga_publish'] ?? '0');
            }
        }
        $data['penambahan_publish'] = $calculatedPenambahanPublish;
        $data['penambahan_vendor'] = $calculatedPenambahanVendor;

        // 4. Recalculate final 'price'
        $data['price'] = $data['product_price'] - $data['pengurangan'] + $data['penambahan_publish'];

        return $data;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Product Details')
                    ->tabs([
                        Tab::make('Basic Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Product Name')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('name')
                                                    ->placeholder('-'),
                                                TextEntry::make('pax')
                                                    ->label('Capacity (pax)')
                                                    ->suffix(' people')
                                                    ->placeholder('-'),
                                                TextEntry::make('stock')
                                                    ->weight('bold')
                                                    ->suffix(' units')
                                                    ->color(fn (string $state): string => $state > 0 ? 'primary' : 'danger'),
                                            ]),
                                    ]),
                                Section::make('Facilities')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('product_price')
                                                    ->label('Total Publish Price')
                                                    ->weight('bold')
                                                    ->color('primary')
                                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                                                    ->helperText('Total Harga Publish Vendor')
                                                    ->placeholder('-'),
                                                TextEntry::make('pengurangan')
                                                    ->label('Pengurangan Harga')
                                                    ->weight('bold')
                                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                                                    ->helperText('Total Pengurangan')
                                                    ->color('danger')
                                                    ->placeholder('-'),
                                            ]),
                                    ]),
                                Section::make('Product Status')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                IconEntry::make('is_active')
                                                    ->label('Product Status')
                                                    ->boolean(),
                                                IconEntry::make('is_approved')
                                                    ->label('Approval Status')
                                                    ->boolean()
                                                    ->visible(function () {
                                                        /** @var User $user */
                                                        $user = Auth::user();
                                                        return $user->hasRole('super_admin');
                                                    }),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ]),

                        Tab::make('Basic Facilities')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextEntry::make('product_price')
                                            ->label('Total Publish Price')
                                            ->weight('bold')
                                            ->color('primary') // Warna untuk total harga vendor
                                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                                            ->helperText('Sum of all vendor prices'),

                                        TextEntry::make('calculatedPriceVendor')
                                            ->label('Total Vendor Cost')
                                            ->weight('bold')
                                            ->color('warning')
                                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                                            ->helperText('Sum of all vendor prices')
                                            ->state(function (Product $record): int {
                                                return $record->items->sum(function ($item) {
                                                    // Access the accessor: $item->harga_vendor * $item->quantity
                                                    return $item->harga_vendor;
                                                });
                                            }),
                                    ]),
                                Section::make()
                                    ->schema([
                                        RepeatableEntry::make('items')
                                            ->schema([
                                                TextEntry::make('vendor.name')
                                                    ->label('Vendor Name')
                                                    ->placeholder('-'),
                                                TextEntry::make('harga_publish')
                                                    ->label('Published Price')
                                                    ->weight('bold')
                                                    ->color('info')
                                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                                                    ->placeholder('-'),
                                                TextEntry::make('quantity')
                                                    ->placeholder('-')
                                                    ->color('gray'),
                                                TextEntry::make('price_public')
                                                    ->label('Calculated Public Price')
                                                    ->weight('bold')
                                                    ->color('primary')
                                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                                                    ->placeholder('-'),
                                                TextEntry::make('harga_vendor')
                                                    ->label('Vendor Unit Cost')
                                                    ->weight('bold')
                                                    ->color('warning')
                                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                                                    ->placeholder('-'),
                                                TextEntry::make('calculate_price_vendor')
                                                    ->label('Calculated Vendor Cost')
                                                    ->weight('bold')
                                                    ->color('warning')
                                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                                                    ->placeholder('-'), // Will use ProductVendor's accessor
                                                TextEntry::make('description')
                                                    ->label('Fasilitas')
                                                    ->columnSpanFull()
                                                    ->html()
                                                    ->placeholder('Keterangan Fasilitas'),
                                            ])
                                            ->columns(4) // Adjusted columns due to new entry
                                            ->grid(1)
                                            ->contained(true),
                                    ]),
                            ]),

                        Tab::make('Pengurangan Harga')
                            ->icon('heroicon-o-receipt-refund')
                            ->label('Pengurangan Harga (Jika Ada)')
                            ->schema([
                                TextEntry::make('pengurangan')
                                    ->label('Total Pengurangan')
                                    ->color('danger') // Warna untuk total pengurangan
                                    ->weight('bold')
                                    ->placeholder('-')
                                    ->state(function (Product $record): int {
                                        // Jika 'pengurangan' adalah kolom di tabel Product
                                        // return $record->pengurangan ?? 0;
                                        // Jika 'pengurangan' dihitung dari relasi itemsPengurangan
                                        return $record->itemsPengurangan()->sum('amount');
                                    })
                                    ->helperText('Sum of all discount items'),
                                RepeatableEntry::make('itemsPengurangan')
                                    ->label('Discount Items')
                                    ->schema([
                                        TextEntry::make('description')
                                            ->label('Description')
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                        TextEntry::make('amount')
                                            ->label('Discount Value')
                                            ->color('warning') // Warna untuk nilai diskon
                                            ->weight('bold')
                                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                                            ->placeholder('-'),
                                        TextEntry::make('notes')
                                            ->label('Notes')
                                            ->columnSpanFull()
                                            ->html()
                                            ->placeholder('No notes.'),
                                    ])
                                    ->columns(2)
                                    ->grid(1)
                                    ->contained(true),
                            ]),

                        Tab::make('Penambahan Harga')
                            ->icon('heroicon-o-plus-circle')
                            ->label('Penambahan Harga (Jika Ada)')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('penambahan_publish')
                                            ->label('Total Penambahan Publish Price')
                                            ->color('success') // Warna untuk total penambahan
                                            ->weight('bold')
                                            ->placeholder('-')
                                            ->state(function (Product $record): int {
                                                // Ambil dari kolom penambahan_publish atau hitung dari relasi harga_publish
                                                return $record->penambahan_publish ?? $record->penambahanHarga()->sum('harga_publish');
                                            })
                                            ->helperText('Sum of all additional publish prices'),
                                        TextEntry::make('penambahan_vendor')
                                            ->label('Total Penambahan Vendor Price')
                                            ->color('warning') // Warna untuk vendor price
                                            ->weight('bold')
                                            ->placeholder('-')
                                            ->state(function (Product $record): int {
                                                // Ambil dari kolom penambahan_vendor atau hitung dari relasi harga_vendor
                                                return $record->penambahan_vendor ?? $record->penambahanHarga()->sum('harga_vendor');
                                            })
                                            ->helperText('Sum of all additional vendor prices'),
                                    ]),
                                RepeatableEntry::make('penambahanHarga')
                                    ->label('Additional Items')
                                    ->schema([
                                        TextEntry::make('vendor.name')
                                            ->label('Vendor Name')
                                            ->placeholder('-')
                                            ->weight('bold')
                                            ->color('info'),
                                        TextEntry::make('harga_publish')
                                            ->label('Publish Price')
                                            ->color('success') // Warna untuk harga publish
                                            ->weight('bold')
                                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                                            ->placeholder('-'),
                                        TextEntry::make('harga_vendor')
                                            ->label('Vendor Price')
                                            ->color('warning') // Warna untuk harga vendor
                                            ->weight('bold')
                                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, '.', ','))
                                            ->placeholder('-'),
                                        TextEntry::make('description')
                                            ->label('Description')
                                            ->columnSpanFull()
                                            ->html()
                                            ->placeholder('No description.'),
                                    ])
                                    ->columns(3)
                                    ->grid(1)
                                    ->contained(true),
                            ]),
                        Tab::make('Timestamps')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created On')
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->label('Last Modified')
                                    ->dateTime(),
                                TextEntry::make('user.name') // Jika ada relasi user
                                    ->label('Created by')
                                    ->placeholder('-')
                                    ->visible(fn (Product $record) => $record->user !== null),
                                TextEntry::make('lastEditedBy.name')
                                    ->label('Last Edited By')
                                    ->placeholder('-')
                                    ->state(function (Product $record): string {
                                        if ($record->lastEditedBy) {
                                            return $record->lastEditedBy->name;
                                        }

                                        // Fallback untuk data lama yang belum memiliki track editor
                                        if ($record->updated_at && $record->created_at && $record->updated_at->ne($record->created_at)) {
                                            return 'Modified on '.$record->updated_at->format('M d, Y H:i');
                                        }

                                        return 'No modifications yet';
                                    })
                                    ->helperText('Track who made the last changes to this product'),
                            ])->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}