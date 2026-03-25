<?php

namespace App\Filament\Resources\NotaDinasDetails\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PengeluaranJenis;
use App\Models\NotaDinasDetail;
use App\Models\OrderProduct;
use App\Models\ProductPenambahan;
use App\Models\ProductVendor;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\Str;

class NotaDinasDetailForm
{
    public static function configure(Schema $schema): Schema
    {
        $supportsProductLinking = SchemaFacade::hasColumn('nota_dinas_details', 'order_product_id')
            && SchemaFacade::hasColumn('nota_dinas_details', 'product_vendor_id');

        return $schema
            ->components([
                Section::make('Nota Dinas')
                    ->icon('heroicon-o-rectangle-stack')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Select::make('nota_dinas_id')
                                    ->label('Nota Dinas')
                                    ->relationship('notaDinas', 'no_nd', fn ($query) => $query->latest())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                    ]),

                Section::make('Invoice')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('invoice_number')
                                ->label('Nomor Invoice')
                                ->maxLength(255)
                                ->placeholder('INV-001'),

                            Select::make('status_invoice')
                                ->label('Status Invoice')
                                ->options([
                                    'belum_dibayar' => 'Belum Dibayar',
                                    'menunggu' => 'Menunggu Pembayaran',
                                    'sudah_dibayar' => 'Sudah Dibayar',
                                ])
                                ->default('belum_dibayar')
                                ->required(),
                        ]),

                        FileUpload::make('invoice_file')
                            ->label('File Invoice')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->directory('nota-dinas/invoices')
                            ->visibility('private')
                            ->downloadable()
                            ->previewable(),
                    ]),
                
                Section::make('Pembayaran')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        TextInput::make('keperluan')
                            ->label('Keperluan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Misal: Dekorasi, Catering, Fotografer'),

                        Grid::make(3)->schema([
                            Select::make('jenis_pengeluaran')
                                ->label('Jenis Pengeluaran')
                                ->options([
                                    PengeluaranJenis::WEDDING->value => 'Wedding',
                                    PengeluaranJenis::OPERASIONAL->value => 'Operasional',
                                    PengeluaranJenis::LAIN_LAIN->value => 'Lain-lain',
                                ])
                                ->required()
                                ->default(PengeluaranJenis::WEDDING->value)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state !== PengeluaranJenis::WEDDING->value) {
                                        $set('order_id', null);
                                        $set('order_product_id', null);
                                        $set('product_vendor_id', null);
                                        $set('vendor_id', null);
                                        $set('bank_name', null);
                                        $set('bank_account', null);
                                        $set('account_holder', null);
                                    }
                                }),

                            Select::make('payment_stage')
                                ->label('Tahap Pembayaran')
                                ->options(function (callable $get) use ($supportsProductLinking): array {
                                    $options = [
                                        'DP' => 'DP (Down Payment)',
                                        'Payment 1' => 'Payment 1',
                                        'Payment 2' => 'Payment 2',
                                        'Payment 3' => 'Payment 3',
                                        'Final Payment' => 'Final Payment',
                                        'Additional' => 'Additional',
                                    ];

                                    if (! $supportsProductLinking) {
                                        return $options;
                                    }

                                    if ($get('jenis_pengeluaran') !== PengeluaranJenis::WEDDING->value) {
                                        return $options;
                                    }

                                    $orderId = $get('order_id');
                                    $productVendorId = $get('product_vendor_id');
                                    if (! $orderId || ! $productVendorId) {
                                        return $options;
                                    }

                                    $current = $get('payment_stage');

                                    $usedStages = NotaDinasDetail::query()
                                        ->where('jenis_pengeluaran', PengeluaranJenis::WEDDING->value)
                                        ->where('order_id', $orderId)
                                        ->where('product_vendor_id', $productVendorId)
                                        ->pluck('payment_stage')
                                        ->all();

                                    $isCreate = empty($get('id'));
                                    if ($isCreate && in_array('Final Payment', $usedStages, true)) {
                                        return $options;
                                    }

                                    foreach ($usedStages as $used) {
                                        if ($used === 'Additional') {
                                            continue;
                                        }

                                        if ($current && $used === $current) {
                                            continue;
                                        }

                                        unset($options[$used]);
                                    }

                                    return $options;
                                })
                                ->default('DP')
                                ->live(),

                            TextInput::make('jumlah_transfer')
                                ->label('Jumlah Transfer')
                                ->required()
                                ->prefix('Rp. ')
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters(',')
                                ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                ->placeholder('0'),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('order_id')
                                ->label('Event (Order)')
                                ->relationship('order', 'name', fn ($query) => $query->whereIn('status', [OrderStatus::Processing, OrderStatus::Done]))
                                ->searchable()
                                ->preload()
                                ->columnSpan('full')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('order_product_id', null);
                                    $set('product_vendor_id', null);
                                    $set('vendor_id', null);
                                    $set('bank_name', null);
                                    $set('bank_account', null);
                                    $set('account_holder', null);
                                    $set('keperluan', null);
                                })
                                ->required(fn (callable $get): bool => $get('jenis_pengeluaran') === PengeluaranJenis::WEDDING->value)
                                ->visible(fn (Get $get): bool => $get('jenis_pengeluaran') === PengeluaranJenis::WEDDING->value)
                                ->placeholder('Pilih order (Processing / Done)'),

                            Select::make('order_product_id')
                                ->label('Produk (Order)')
                                ->searchable()
                                ->getSearchResultsUsing(function (string $search, callable $get): array {
                                    $orderId = $get('order_id');
                                    if (! $orderId) {
                                        return [];
                                    }

                                    return OrderProduct::query()
                                        ->with('product')
                                        ->where('order_id', $orderId)
                                        ->when(
                                            SchemaFacade::hasColumn('order_products', 'deleted_at'),
                                            fn ($q) => $q->whereNull('order_products.deleted_at'),
                                        )
                                        ->when($search !== '', function ($q) use ($search) {
                                            $q->whereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                                        })
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(function (OrderProduct $item): array {
                                            $name = $item->product?->name ?? 'Produk';
                                            $qty = (int) ($item->quantity ?? 0);

                                            return [$item->id => "{$name} (Qty: {$qty})"];
                                        })
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(function ($value): ?string {
                                    if (! $value) {
                                        return null;
                                    }

                                    $item = OrderProduct::with('product')->find($value);
                                    if (! $item) {
                                        return null;
                                    }

                                    $name = $item->product?->name ?? 'Produk';
                                    $qty = (int) ($item->quantity ?? 0);

                                    return "{$name} (Qty: {$qty})";
                                })
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('product_vendor_id', null);
                                    $set('vendor_id', null);
                                    $set('bank_name', null);
                                    $set('bank_account', null);
                                    $set('account_holder', null);
                                    $set('keperluan', null);
                                })
                                ->required(fn (callable $get): bool => $supportsProductLinking && $get('jenis_pengeluaran') === PengeluaranJenis::WEDDING->value)
                                ->columnSpan('full')
                                ->visible(fn (callable $get): bool => $supportsProductLinking && $get('jenis_pengeluaran') === PengeluaranJenis::WEDDING->value && filled($get('order_id'))),

                            Select::make('product_vendor_id')
                                ->label('Vendor (Produk)')
                                ->searchable()
                                ->getSearchResultsUsing(function (string $search, callable $get): array {
                                    $orderId = $get('order_id');
                                    $orderProductId = $get('order_product_id');
                                    if (! $orderId || ! $orderProductId) {
                                        return [];
                                    }

                                    $orderProduct = OrderProduct::query()
                                        ->whereKey($orderProductId)
                                        ->where('order_id', $orderId)
                                        ->first();

                                    if (! $orderProduct) {
                                        return [];
                                    }

                                    $finalProductVendorIds = [];
                                    if (SchemaFacade::hasColumn('nota_dinas_details', 'product_vendor_id')) {
                                        $finalProductVendorIds = NotaDinasDetail::query()
                                            ->where('jenis_pengeluaran', PengeluaranJenis::WEDDING->value)
                                            ->where('order_id', $orderId)
                                            ->where('payment_stage', 'Final Payment')
                                            ->whereNotNull('product_vendor_id')
                                            ->pluck('product_vendor_id')
                                            ->all();
                                    }

                                    // Get from Product Vendors (Fasilitas Dasar)
                                    $productVendors = ProductVendor::query()
                                        ->with('vendor')
                                        ->where('product_id', $orderProduct->product_id)
                                        ->when(
                                            SchemaFacade::hasColumn('product_vendors', 'deleted_at'),
                                            fn ($q) => $q->whereNull('product_vendors.deleted_at'),
                                        )
                                        ->when(count($finalProductVendorIds) > 0, fn ($q) => $q->whereNotIn('id', $finalProductVendorIds))
                                        ->when($search !== '', function ($q) use ($search) {
                                            $q->where(function ($q) use ($search) {
                                                $q->where('description', 'like', "%{$search}%")
                                                    ->orWhereHas('vendor', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                                            });
                                        })
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(function (ProductVendor $pv): array {
                                            $vendorName = $pv->vendor?->name ?? 'Vendor';
                                            return ['PV_' . $pv->id => $vendorName . ' (Fasilitas Dasar)'];
                                        })
                                        ->toArray();

                                    // Get from Product Penambahan (Additional)
                                    $productPenambahans = ProductPenambahan::query()
                                        ->with('vendor')
                                        ->where('product_id', $orderProduct->product_id)
                                        ->when(
                                            SchemaFacade::hasColumn('product_penambahans', 'deleted_at'),
                                            fn ($q) => $q->whereNull('product_penambahans.deleted_at'),
                                        )
                                        ->when($search !== '', function ($q) use ($search) {
                                            $q->where(function ($q) use ($search) {
                                                $q->where('description', 'like', "%{$search}%")
                                                    ->orWhereHas('vendor', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                                            });
                                        })
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(function (ProductPenambahan $pp): array {
                                            $vendorName = $pp->vendor?->name ?? 'Vendor';
                                            return ['PP_' . $pp->id => $vendorName . ' (Penambahan)'];
                                        })
                                        ->toArray();

                                    return array_merge($productVendors, $productPenambahans);
                                })
                                ->getOptionLabelUsing(function ($value): ?string {
                                    if (! $value) {
                                        return null;
                                    }

                                    if (str_starts_with($value, 'PP_')) {
                                        $id = str_replace('PP_', '', $value);
                                        $pp = ProductPenambahan::with('vendor')->find($id);
                                        if (! $pp) return null;
                                        return ($pp->vendor?->name ?? 'Vendor') . ' (Penambahan)';
                                    }

                                    $id = str_replace('PV_', '', $value);
                                    // Fallback for existing data that might not have PV_ prefix yet
                                    if (is_numeric($value)) {
                                        $id = $value;
                                    }

                                    $pv = ProductVendor::with('vendor')->find($id);
                                    if (! $pv) {
                                        return null;
                                    }

                                    return ($pv->vendor?->name ?? 'Vendor') . ' (Fasilitas Dasar)';
                                })
                                ->reactive()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (! $state) {
                                        $set('vendor_id', null);
                                        $set('bank_name', null);
                                        $set('bank_account', null);
                                        $set('account_holder', null);
                                        $set('keperluan', null);

                                        return;
                                    }

                                    if (str_starts_with($state, 'PP_')) {
                                        $id = str_replace('PP_', '', $state);
                                        $pp = ProductPenambahan::with('vendor', 'product')->find($id);
                                        if (! $pp || ! $pp->vendor) return;

                                        $set('vendor_id', $pp->vendor_id);
                                        $set('bank_name', $pp->vendor->bank_name);
                                        $set('bank_account', $pp->vendor->bank_account);
                                        $set('account_holder', $pp->vendor->account_holder);
                                        return;
                                    }

                                    $id = str_replace('PV_', '', $state);
                                    if (is_numeric($state)) {
                                        $id = $state;
                                    }

                                    $pv = ProductVendor::with('vendor', 'product')->find($id);
                                    if (! $pv || ! $pv->vendor) {
                                        return;
                                    }

                                    $set('vendor_id', $pv->vendor_id);
                                    $set('bank_name', $pv->vendor->bank_name);
                                    $set('bank_account', $pv->vendor->bank_account);
                                    $set('account_holder', $pv->vendor->account_holder);
                                })
                                ->dehydrateStateUsing(function ($state) {
                                    if (!$state) return null;
                                    // If it's a Penambahan, we can't save it to product_vendor_id
                                    // so we save null to bypass the constraint.
                                    if (str_starts_with($state, 'PP_')) {
                                        return null;
                                    }
                                    
                                    // If it's PV_ return just the ID
                                    return str_replace('PV_', '', $state);
                                })
                                ->afterStateHydrated(function (\Filament\Forms\Components\Select $component, $state, ?NotaDinasDetail $record) {
                                    if (!$record) return;

                                    // Restore state for Fasilitas Dasar
                                    if (filled($record->product_vendor_id)) {
                                        $component->state('PV_' . $record->product_vendor_id);
                                        return;
                                    }

                                    // Restore state for Penambahan (workaround via vendor_id)
                                    if (blank($record->product_vendor_id) && filled($record->vendor_id) && filled($record->order_product_id)) {
                                        $orderProduct = OrderProduct::find($record->order_product_id);
                                        if ($orderProduct) {
                                            $pp = ProductPenambahan::where('product_id', $orderProduct->product_id)
                                                ->where('vendor_id', $record->vendor_id)
                                                ->first();
                                            if ($pp) {
                                                $component->state('PP_' . $pp->id);
                                            }
                                        }
                                    }
                                })
                                ->required(fn (callable $get): bool => $supportsProductLinking && $get('jenis_pengeluaran') === PengeluaranJenis::WEDDING->value)
                                ->columnSpan('full')
                                ->visible(fn (callable $get): bool => $supportsProductLinking && $get('jenis_pengeluaran') === PengeluaranJenis::WEDDING->value && filled($get('order_product_id'))),

                            TextInput::make('event')
                                ->label('Event')
                                ->maxLength(255)
                                ->columnSpan('full')
                                ->placeholder('Nama event/acara')
                                ->visible(fn (Get $get): bool => $get('jenis_pengeluaran') !== PengeluaranJenis::WEDDING->value),
                        ]),
                    ]),

                Section::make('Rekening')
                    ->icon('heroicon-o-banknotes')
                    ->description('Vendor menentukan data rekening. Untuk Wedding, vendor bisa terkunci mengikuti Vendor (Produk).')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('vendor_id')
                                    ->label('Vendor')
                                    ->relationship('vendor', 'name', fn ($query) => $query->whereIn('status', ['vendor', 'product']))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->getOptionLabelUsing(function ($value): ?string {
                                        if (! $value) {
                                            return null;
                                        }

                                        return Vendor::find($value)?->name;
                                    })
                                    ->helperText(function (callable $get) use ($supportsProductLinking): ?string {
                                        if ($supportsProductLinking && $get('jenis_pengeluaran') === PengeluaranJenis::WEDDING->value && filled($get('product_vendor_id'))) {
                                            return 'Vendor dikunci mengikuti Vendor (Produk).';
                                        }

                                        return null;
                                    })
                                    ->dehydrated()
                                    ->disabled(fn (callable $get): bool => $supportsProductLinking && $get('jenis_pengeluaran') === PengeluaranJenis::WEDDING->value && filled($get('product_vendor_id')))
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (! $state) {
                                            $set('bank_name', null);
                                            $set('bank_account', null);
                                            $set('account_holder', null);

                                            return;
                                        }

                                        $vendor = Vendor::find($state);
                                        if ($vendor) {
                                            $set('bank_name', $vendor->bank_name);
                                            $set('bank_account', $vendor->bank_account);
                                            $set('account_holder', $vendor->account_holder);
                                        }
                                    })
                                    ->suffixAction(
                                        Action::make('openVendor')
                                            ->label('Edit Vendor')
                                            ->icon('heroicon-o-pencil-square')
                                            ->color('primary')
                                            ->url(fn ($state): string => $state ? route('filament.admin.resources.vendors.edit', ['record' => $state]) : '#')
                                            ->openUrlInNewTab()
                                            ->visible(fn ($state): bool => ! empty($state))
                                    )
                                    ->createOptionForm([
                                        Grid::make()
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Nama Vendor')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function ($state, callable $set, ?Vendor $record) {
                                                        if ($state === null) {
                                                            $set('slug', '');

                                                            return;
                                                        }

                                                        $slug = Str::slug($state);

                                                        $exists = Vendor::where('slug', $slug)
                                                            ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                                            ->exists();

                                                        if ($exists) {
                                                            $slug = $slug.'-'.now()->timestamp;
                                                        }

                                                        $set('slug', $slug);
                                                    })
                                                    ->placeholder('Contoh: Studio Foto Makmur'),

                                                TextInput::make('slug')
                                                    ->label('Slug')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->required()
                                                    ->helperText('Auto-generated dari nama vendor'),

                                                Select::make('category_id')
                                                    ->label('Kategori')
                                                    ->relationship('category', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->placeholder('Pilih kategori vendor'),

                                                Select::make('status')
                                                    ->label('Status')
                                                    ->options([
                                                        'vendor' => 'Vendor',
                                                        'product' => 'Product',
                                                    ])
                                                    ->default('vendor')
                                                    ->required(),

                                                TextInput::make('pic_name')
                                                    ->label('Contact Person')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Nama PIC/Contact Person'),

                                                TextInput::make('phone')
                                                    ->label('No. Telepon')
                                                    ->tel()
                                                    ->required()
                                                    ->prefix('+62')
                                                    ->maxLength(255)
                                                    ->placeholder('812XXXXXXXX')
                                                    ->helperText('Tanpa angka 0 di depan'),

                                                Textarea::make('address')
                                                    ->label('Alamat')
                                                    ->required()
                                                    ->rows(2)
                                                    ->columnSpanFull()
                                                    ->placeholder('Alamat lengkap vendor'),

                                                Textarea::make('description')
                                                    ->label('Deskripsi Singkat')
                                                    ->rows(3)
                                                    ->columnSpanFull()
                                                    ->maxLength(500)
                                                    ->placeholder('Deskripsi singkat tentang vendor dan layanannya'),
                                            ]),

                                        Section::make('Informasi Bank')
                                            ->description('Data rekening untuk transfer pembayaran')
                                            ->schema([
                                                Grid::make()
                                                    ->columns(2)
                                                    ->schema([
                                                        TextInput::make('bank_name')
                                                            ->label('Nama Bank')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->prefix('Bank ')
                                                            ->placeholder('BCA / Mandiri / BNI'),

                                                        TextInput::make('bank_account')
                                                            ->label('Nomor Rekening')
                                                            ->required()
                                                            ->numeric()
                                                            ->maxLength(255)
                                                            ->placeholder('1234567890'),

                                                        TextInput::make('account_holder')
                                                            ->label('Nama')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->columnSpanFull()
                                                            ->placeholder('Nama sesuai rekening bank')
                                                            ->helperText('Masukkan nama persis seperti di rekening bank'),
                                                    ]),
                                            ]),
                                    ])
                                    ->columnSpanFull(),

                                TextInput::make('account_holder')
                                    ->label('Nama')
                                    ->dehydrated()
                                    ->readOnly()
                                    ->maxLength(255)
                                    ->placeholder('Otomatis terisi'),

                                TextInput::make('bank_name')
                                    ->label('Nama Bank')
                                    ->dehydrated()
                                    ->readOnly()
                                    ->maxLength(255)
                                    ->placeholder('Otomatis terisi')
                                    ->required(),

                                TextInput::make('bank_account')
                                    ->label('Nomor Rekening')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->maxLength(255)
                                    ->placeholder('Otomatis terisi')
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
