<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductForm
{
    /**
     * Cache for vendor data to avoid repeated database queries
     */
    protected static array $vendorCache = [];

    public static function configure(): array
    {
        return [
            Tabs::make('Product Details')
                ->tabs([
                    Tab::make('Basic Information')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->live(onBlur: true)
                                    ->maxLength(255)
                                    ->placeholder('nama pengantin_lokasi_pax')
                                    ->afterStateUpdated(fn (string $state, Set $set) => $set('slug', Str::slug($state))),

                                Hidden::make('slug')
                                    ->disabled()
                                    ->dehydrated()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Otomatis dibuat dari nama'),

                                FileUpload::make('image')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('products')
                                    ->downloadable(),

                                Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->required()
                                    ->preload()
                                    ->placeholder('Select a category')
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (string $state, Set $set) => $set('slug', Str::slug($state))),
                                        TextInput::make('slug')
                                            ->disabled()
                                            ->dehydrated()
                                            ->maxLength(255)
                                            ->unique(Category::class, 'slug', ignoreRecord: true),
                                        Textarea::make('description')
                                            ->maxLength(1000)
                                            ->placeholder('Category description'),
                                    ])
                                    ->createOptionAction(
                                        fn (Action $action) => $action
                                            ->modalHeading('Create new category')
                                            ->modalSubmitActionLabel('Create category')
                                    ),

                                Select::make('parent_id')
                                    ->label('Parent Product')
                                    ->relationship('parent', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select a parent product (optional)')
                                    ->helperText('Jika produk ini adalah varian (child), pilih produk utamanya (parent).'),

                                TextInput::make('pax')
                                    ->label('Capacity (pax)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1000)
                                    ->suffix('people')
                                    ->placeholder('1000'),

                                TextInput::make('price')
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->label('Product Price')
                                    ->reactive()
                                    ->minValue(0)
                                    ->live()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format(is_numeric($state) ? $state : self::stripCurrency($state), 0, '.', ','))
                                    ->dehydrateStateUsing(fn ($state) => self::stripCurrency($state))
                                    ->helperText('Total Harga Publish - Total Pengurangan + Total Penambahan')
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        if ($record) {
                                            $final = (int) ($record->product_price ?? 0)
                                                - (int) ($record->pengurangan ?? 0)
                                                + (int) ($record->penambahan_publish ?? 0);
                                            $component->state($final);
                                        }
                                    }),

                                TextInput::make('stock')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(10)
                                    ->suffix('units')
                                    ->placeholder('0')
                                    ->helperText('pastikan di isi dengan angka 10'),

                            ]),

                            Section::make('Product Status')
                                ->schema([
                                    Toggle::make('is_active')
                                        ->label('Product Status')
                                        ->helperText('Aktifkan untuk menampilkan/menyembunyikan produk')
                                        ->default(true)
                                        ->onIcon('heroicon-s-check-circle')
                                        ->offIcon('heroicon-s-x-circle')
                                        ->onColor('success')
                                        ->offColor('danger'),
                                    Toggle::make('is_approved')
                                        ->label('Approval Status')
                                        ->helperText('Setujui atau tolak produk')
                                        ->default(false)
                                        ->onIcon('heroicon-s-hand-thumb-up')
                                        ->offIcon('heroicon-s-hand-thumb-down')
                                        ->onColor('success')
                                        ->offColor('danger')
                                        ->visible(function () {
                                            /** @var User $user */
                                            $user = Auth::user();
                                            return $user->hasRole('super_admin');
                                        }),
                                ])
                                ->collapsible(),
                        ]),

                    Tab::make('Basic Facilities')
                        ->icon('heroicon-o-cube')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('product_price')
                                        ->formatStateUsing(fn ($state) => number_format(self::stripCurrency($state), 0, '.', ','))
                                        ->dehydrateStateUsing(fn ($state) => self::stripCurrency($state))
                                        ->prefix('Rp')
                                        ->label('Total Publish Price')
                                        ->readOnly()
                                        ->live()
                                        ->minValue(0)
                                        ->dehydrated(true)
                                        ->helperText('Dihitung otomatis dari harga vendor')
                                        ->afterStateHydrated(function ($component, $state, $record) {
                                            if ($record) {
                                                $total = $record->items->sum(function ($item) {
                                                    $qty = (int) ($item->quantity ?? 1);
                                                    $publish = (int) ($item->harga_publish ?? 0);
                                                    $pricePublic = (int) ($item->price_public ?? ($publish * $qty));
                                                    return $pricePublic;
                                                });
                                                $component->state($total);
                                            }
                                        }),
                                    TextInput::make('vendorTotal')
                                        ->formatStateUsing(fn ($state) => number_format(self::stripCurrency($state), 0, '.', ','))
                                        ->dehydrateStateUsing(fn ($state) => self::stripCurrency($state))
                                        ->prefix('Rp')
                                        ->label('Total Vendor Price')
                                        ->readOnly()
                                        ->minValue(0)
                                        ->helperText('Jumlah dari semua harga vendor')
                                        ->afterStateHydrated(function ($component, $state, $record) {
                                            if ($record) {
                                                $total = $record->items->sum('total_price');
                                                $component->state((int) $total);
                                            }
                                        }),
                                ]),
                            self::getVendorRepeater(),
                        ]),
                    Tab::make('Pengurangan Harga')
                        ->icon('heroicon-o-receipt-refund')
                        ->label('Pengurangan Harga (Jika Ada)')
                        ->schema([
                            TextInput::make('pengurangan')
                                ->label('Total Pengurangan')
                                ->readOnly()
                                ->default(0)
                                ->live()
                                ->dehydrated()
                                ->formatStateUsing(fn ($state) => number_format(is_numeric($state) ? $state : self::stripCurrency($state), 0, '.', ','))
                                ->dehydrateStateUsing(fn ($state) => self::stripCurrency($state))
                                ->prefix('Rp')
                                ->helperText('Dihitung otomatis dari harga diskon')
                                ->afterStateHydrated(function ($component, $state, $record) {
                                    if ($record) {
                                        $total = $record->pengurangans->sum('amount');
                                        $component->state($total);
                                    }
                                }),
                            self::getDiscountRepeater(),
                        ]),
                    Tab::make('Penambahan Harga')
                        ->icon('heroicon-o-plus-circle')
                        ->label('Penambahan Harga (Jika Ada)')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('penambahan_publish')
                                        ->label('Total Publish Price')
                                        ->readOnly()
                                        ->default(0)
                                        ->live()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => number_format(self::stripCurrency($state), 0, '.', ','))
                                        ->dehydrateStateUsing(fn ($state) => self::stripCurrency($state))
                                        ->prefix('Rp')
                                        ->helperText('Dihitung otomatis dari penambahan harga publish')
                                        ->afterStateHydrated(function ($component, $state, $record) {
                                            if ($record) {
                                                $total = $record->penambahanHarga->sum('harga_publish');
                                                $component->state($total);
                                            }
                                        }),
                                    TextInput::make('penambahan_vendor')
                                        ->label('Total Vendor Price')
                                        ->readOnly()
                                        ->default(0)
                                        ->live()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => number_format(self::stripCurrency($state), 0, '.', ','))
                                        ->dehydrateStateUsing(fn ($state) => self::stripCurrency($state))
                                        ->prefix('Rp')
                                        ->helperText('Dihitung otomatis dari penambahan harga vendor')
                                        ->afterStateHydrated(function ($component, $state, $record) {
                                            if ($record) {
                                                $total = $record->penambahanHarga->sum('harga_vendor');
                                                $component->state($total);
                                            }
                                        }),
                                ]),
                            self::getAdditionRepeater(),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function getVendorRepeater()
    {
        return Repeater::make('items')
            ->label('Vendors')
            ->relationship()
            ->schema([
                Grid::make(4)
                    ->schema([
                        Select::make('vendor_id')
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a vendor')
                            ->required()
                            ->live()
                            ->reactive()
                            ->afterStateHydrated(function (Set $set, Get $get, $state) {
                                if ($state) {
                                    self::updateVendorData($set, $state);
                                    self::calculatePrices($get, $set);
                                }
                            })
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if ($state) {
                                    self::updateVendorData($set, $state);
                                    self::calculatePrices($get, $set);
                                }
                            })
                            ->columnSpan([
                                'md' => 5,
                            ]),

                        TextInput::make('harga_publish')
                            ->label('Published Price')
                            ->prefix('Rp')
                            ->formatStateUsing(fn ($state) => number_format(self::stripCurrency($state), 0, '.', ','))
                            ->dehydrateStateUsing(fn ($state) => self::stripCurrency($state))
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                self::calculatePrices($get, $set);
                            }),

                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                self::calculatePrices($get, $set);
                            }),

                        TextInput::make('price_public')
                            ->label('Calculated Public Price')
                            ->readOnly()
                            ->prefix('Rp')
                            ->formatStateUsing(fn ($state) => number_format(self::stripCurrency($state), 0, '.', ','))
                            ->dehydrateStateUsing(fn ($state) => self::stripCurrency($state))
                            ->helperText('Harga Publish x Quantity'),

                        TextInput::make('harga_vendor')
                            ->label('Vendor Unit Cost')
                            ->prefix('Rp')
                            ->readOnly()
                            ->formatStateUsing(fn ($state) => number_format(self::stripCurrency($state), 0, '.', ','))
                            ->dehydrateStateUsing(fn ($state) => self::stripCurrency($state))
                            ->helperText('Harga Vendor per unit'),

                        TextInput::make('total_price')
                            ->label('Calculated Vendor Cost')
                            ->readOnly()
                            ->prefix('Rp')
                            ->formatStateUsing(fn ($state) => number_format(self::stripCurrency($state), 0, '.', ','))
                            ->dehydrateStateUsing(fn ($state) => self::stripCurrency($state))
                            ->helperText('Harga Vendor x Quantity'),

                        RichEditor::make('description')
                            ->label('Fasilitas')
                            ->placeholder('Keterangan Fasilitas')
                            ->columnSpanFull(),
                    ]),
            ])
            ->extraItemActions([
                Action::make('openVendor')
                    ->label('Open Vendor')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('info')
                    ->url(function (array $arguments, Repeater $component): ?string {
                        $itemData = $component->getRawItemState($arguments['item']);
                        $vendorId = $itemData['vendor_id'] ?? null;
                        if (! $vendorId) {
                            return null;
                        }
                        $vendor = static::getVendorData($vendorId);

                        return $vendor ? VendorResource::getUrl('edit', ['record' => $vendor]) : null;
                    }, shouldOpenInNewTab: true)
                    ->hidden(fn (array $arguments, Repeater $component): bool => blank($component->getRawItemState($arguments['item'])['vendor_id'] ?? null)),
            ])
            ->defaultItems(1)
            ->collapsed()
            ->itemLabel(fn (array $state): ?string => $state['vendor_id']
                    ? static::getVendorData($state['vendor_id'])?->name ?? 'Unnamed Vendor'
                    : 'New Facility'
            )
            ->reorderable()
            ->cloneable()
            ->reactive()
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set) {
                $itemsArray = $get('items') ?? [];

                $totalProductPrice = collect($itemsArray)
                    ->sum(function ($item) {
                        $val = $item['price_public'] ?? 0;
                        return self::stripCurrency($val);
                    });

                $set('product_price', self::formatCurrency($totalProductPrice));

                $totalVendorPrice = collect($itemsArray)
                    ->sum(function ($item) {
                        $val = $item['harga_vendor'] ?? 0;
                        return self::stripCurrency($val);
                    });

                $set('vendorTotal', self::formatCurrency($totalVendorPrice));

                $penguranganVal = self::getCleanInt($get, '../pengurangan');
                $finalPrice = $totalProductPrice - $penguranganVal;
                $set('../price', self::formatCurrency($finalPrice));
            })
            ->columns(1);
    }

    protected static function getDiscountRepeater()
    {
        return Repeater::make('itemsPengurangan')
            ->relationship()
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('description')
                            ->label('Nama Vendor')
                            ->required()
                            ->columnSpan(3),

                        TextInput::make('amount')
                            ->label('Discount Value')
                            ->required()
                            ->prefix('Rp')
                            ->rules(['min:0'])
                            ->formatStateUsing(fn ($state) => number_format(is_numeric($state) ? $state : self::stripCurrency($state), 0, '.', ','))
                            ->dehydrateStateUsing(fn ($state) => self::stripCurrency($state))
                            ->columnSpan(3),

                        RichEditor::make('notes')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                    ]),
            ])
            ->defaultItems(0)
            ->collapsed()
            ->itemLabel(fn (array $state): ?string => $state['description'] ?? 'New Discount Item'
            )
            ->reorderable()
            ->cloneable()
            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                $totalPengurangan = collect($state)
                    ->sum(function ($item) {
                        $amountStr = $item['amount'] ?? '0';
                        return self::stripCurrency($amountStr);
                    });

                $set('pengurangan', self::formatCurrency($totalPengurangan));

                $productPriceVal = self::getCleanInt($get, '../product_price');
                $penambahanVal = self::getCleanInt($get, '../penambahan_publish');
                $finalPrice = $productPriceVal - $totalPengurangan + $penambahanVal;
                $set('../price', self::formatCurrency($finalPrice));
            })
            ->addActionLabel('Add Discount')
            ->columns(1);
    }

    protected static function getAdditionRepeater()
    {
        return Repeater::make('penambahanHarga')
            ->relationship()
            ->schema([
                Grid::make(4)
                    ->schema([
                        Select::make('vendor_id')
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a vendor')
                            ->required()
                            ->live()
                            ->reactive()
                            ->afterStateHydrated(function (Set $set, Get $get, $state) {
                                if ($state) {
                                    self::updateAdditionVendorData($set, $state);
                                    self::calculateAdditionPrices($get, $set);
                                }
                            })
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if ($state) {
                                    self::updateAdditionVendorData($set, $state);
                                    self::calculateAdditionPrices($get, $set);
                                }
                            })
                            ->columnSpan([
                                'md' => 2,
                            ]),

                        TextInput::make('harga_publish')
                            ->label('Published Price')
                            ->prefix('Rp')
                            ->formatStateUsing(fn ($state) => number_format(is_numeric($state) ? $state : self::stripCurrency($state), 0, '.', ','))
                            ->dehydrateStateUsing(fn ($state) => self::stripCurrency($state))
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                self::calculateAdditionPrices($get, $set);
                            }),

                        TextInput::make('harga_vendor')
                            ->label('Vendor Price')
                            ->prefix('Rp')
                            ->formatStateUsing(fn ($state) => number_format(is_numeric($state) ? $state : self::stripCurrency($state), 0, '.', ','))
                            ->dehydrateStateUsing(fn ($state) => self::stripCurrency($state))
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                self::calculateAdditionPrices($get, $set);
                            }),

                        RichEditor::make('description')
                            ->label('Description/Notes')
                            ->placeholder('Additional notes for this item')
                            ->columnSpanFull(),
                    ]),
            ])
            ->extraItemActions([
                Action::make('openVendor')
                    ->label('Open Vendor')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('info')
                    ->url(function (array $arguments, Repeater $component): ?string {
                        $itemData = $component->getRawItemState($arguments['item']);
                        $vendorId = $itemData['vendor_id'] ?? null;
                        if (! $vendorId) {
                            return null;
                        }
                        $vendor = static::getVendorData($vendorId);

                        return $vendor ? VendorResource::getUrl('edit', ['record' => $vendor]) : null;
                    }, shouldOpenInNewTab: true)
                    ->hidden(fn (array $arguments, Repeater $component): bool => blank($component->getRawItemState($arguments['item'])['vendor_id'] ?? null)),
            ])
            ->defaultItems(0)
            ->collapsed()
            ->itemLabel(fn (array $state): ?string => $state['vendor_id']
                    ? static::getVendorData($state['vendor_id'])?->name ?? 'Unnamed Vendor'
                    : 'New Addition Item'
            )
            ->reorderable()
            ->cloneable()
            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                $totalPenambahanPublish = collect($state)
                    ->sum(function ($item) {
                        $val = $item['harga_publish'] ?? 0;
                        return self::stripCurrency($val);
                    });

                $totalPenambahanVendor = collect($state)
                    ->sum(function ($item) {
                        $val = $item['harga_vendor'] ?? 0;
                        return self::stripCurrency($val);
                    });

                $set('penambahan_publish', self::formatCurrency($totalPenambahanPublish));
                $set('penambahan_vendor', self::formatCurrency($totalPenambahanVendor));

                $productPriceVal = self::getCleanInt($get, '../product_price');
                $penguranganVal = self::getCleanInt($get, '../pengurangan');
                $finalPrice = $productPriceVal - $penguranganVal + $totalPenambahanPublish;
                $set('../price', self::formatCurrency($finalPrice));
            })
            ->addActionLabel('Add Additional Item')
            ->columns(1);
    }

    protected static function getVendorData($vendorId): ?object
    {
        if (! isset(static::$vendorCache[$vendorId])) {
            static::$vendorCache[$vendorId] = Vendor::find($vendorId);
        }

        return static::$vendorCache[$vendorId];
    }

    protected static function updateVendorData(Set $set, $vendorId): void
    {
        $vendor = static::getVendorData($vendorId);
        if ($vendor) {
            $active = $vendor->activePrice();
            $h_publish = $active?->harga_publish ?? $vendor->harga_publish;
            $h_vendor = $active?->harga_vendor ?? $vendor->harga_vendor;
            
            $set('harga_publish', self::formatCurrency((int) $h_publish));
            $set('harga_vendor', self::formatCurrency((int) $h_vendor));
            $set('description', $vendor->description);
        }
    }

    protected static function updateAdditionVendorData(Set $set, $vendorId): void
    {
        $vendor = static::getVendorData($vendorId);
        if ($vendor) {
            $active = $vendor->activePrice();
            $h_publish = $active?->harga_publish ?? $vendor->harga_publish;
            $h_vendor = $active?->harga_vendor ?? $vendor->harga_vendor;
            
            $set('harga_publish', self::formatCurrency((int) $h_publish));
            $set('harga_vendor', self::formatCurrency((int) $h_vendor));
            $set('description', $vendor->description);
        }
    }

    protected static function calculateAdditionPrices(Get $get, Set $set): void
    {
        $harga_publish = self::getCleanInt($get, 'harga_publish');
        $harga_vendor = self::getCleanInt($get, 'harga_vendor');

        self::calculateTotalAdditionPrice($get, $set);
    }

    protected static function getCleanInt(Get $get, string $path): int
    {
        return self::stripCurrency($get($path));
    }

    public static function stripCurrency($val): int
    {
        if (is_string($val)) {
            $val = str_replace(['.', ','], '', $val);
        }
        return (int) ($val ?? 0);
    }

    public static function formatCurrency(int $value): string
    {
        return number_format($value, 0, '.', ',');
    }

    protected static function calculatePrices(Get $get, Set $set): void
    {
        $harga_publish = self::getCleanInt($get, 'harga_publish');
        $harga_vendor = self::getCleanInt($get, 'harga_vendor');
        $quantity = (int) ($get('quantity') ?? 1);
        
        $price_public = $harga_publish * $quantity;
        $set('price_public', self::formatCurrency($price_public));

        $total_price = $harga_vendor * $quantity;
        $set('total_price', $total_price);

        self::calculateTotalProductPrice($get, $set);
    }

    protected static function calculateTotalProductPrice(Get $get, Set $set): void
    {
        $items = $get('../../items') ?? [];

        $total_price = collect($items)
            ->sum(function ($item) {
                $val = $item['price_public'] ?? 0;
                return self::stripCurrency($val);
            });

        $set('../../product_price', self::formatCurrency($total_price));

        $total_vendor_price = collect($items)
            ->sum(function ($item) {
                $val = $item['harga_vendor'] ?? 0;
                return self::stripCurrency($val);
            });
        
        $set('../../vendorTotal', self::formatCurrency($total_vendor_price));

        self::updateFinalProductPrice($get, $set);
    }

    protected static function calculateTotalAdditionPrice(Get $get, Set $set): void
    {
        $additionItems = $get('../../penambahanHarga') ?? [];

        $total_publish_price = collect($additionItems)
            ->sum(function ($item) {
                $val = $item['harga_publish'] ?? 0;
                return self::stripCurrency($val);
            });

        $total_vendor_price = collect($additionItems)
            ->sum(function ($item) {
                $val = $item['harga_vendor'] ?? 0;
                return self::stripCurrency($val);
            });

        $set('../../penambahan_publish', self::formatCurrency($total_publish_price));
        $set('../../penambahan_vendor', self::formatCurrency($total_vendor_price));

        self::updateFinalProductPriceWithAdditions($get, $set);
    }

    protected static function updateFinalProductPrice(Get $get, Set $set): void
    {
        $productPriceFromVendors = self::getCleanInt($get, '../../product_price');
        $totalPenguranganFromDiscounts = self::getCleanInt($get, '../../pengurangan');
        $totalPenambahanFromAdditions = self::getCleanInt($get, '../../penambahan_publish');

        $finalPrice = $productPriceFromVendors - $totalPenguranganFromDiscounts + $totalPenambahanFromAdditions;
        $set('../../price', self::formatCurrency($finalPrice));
    }

    protected static function updateFinalProductPriceWithAdditions(Get $get, Set $set): void
    {
        $productPriceFromVendors = self::getCleanInt($get, '../../product_price');
        $totalPenguranganFromDiscounts = self::getCleanInt($get, '../../pengurangan');
        $totalPenambahanFromAdditions = self::getCleanInt($get, '../../penambahan_publish');

        $finalPrice = $productPriceFromVendors - $totalPenguranganFromDiscounts + $totalPenambahanFromAdditions;
        $set('../../price', self::formatCurrency($finalPrice));
    }
}