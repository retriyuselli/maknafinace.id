<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\Invoice;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\ExpensesRelationManager;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Order;
use App\Models\OrderAmendment;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderFinancialSnapshot;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Proyek Wedding';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-shopping-cart';

    protected static ?int $navigationSort = 1;

    private static function getCachedNavigationBadgeCount(): int
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $modelClass */
        $modelClass = static::$model;

        return Cache::remember(
            'nav:orders:processing_count',
            60,
            fn (): int => (int) $modelClass::where('status', \App\Enums\OrderStatus::Processing->value)->count()
        );
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ExpensesRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getCachedNavigationBadgeCount();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['prospect.name_event', 'number'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view-closing' => Pages\ViewClosing::route('/view-closing'),
            'customer-expenses' => Pages\CustomerExpenses::route('/customer-expenses'),
            'customer-payments' => Pages\CustomerPayments::route('/customer-payments'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
            'invoice' => Invoice::route('/{record}/invoice'),
        ];
    }

    /**
     * Override the base query to include soft-deleted records.
     * This allows the TrashedFilter to work correctly.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        $query->with([
            'prospect:id,name_event,date_lamaran,date_akad,date_resepsi',
            'employee:id,name',
            'user:id,name',
            'items.product:id,name',
        ]);

        if (Auth::check()) {
            $uid = Auth::id();
            if ($uid) {
                $isPrivileged = DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('model_has_roles.model_type', User::class)
                    ->where('model_has_roles.model_id', $uid)
                    ->whereIn('roles.name', ['super_admin', 'Finance', 'admin_am'])
                    ->exists();

                if ($isPrivileged) {
                    return $query;
                }
            }
        }

        // Other users can only access their own orders (as Account Manager)
        return $query->where('user_id', Auth::user()->id);
    }

    public static function getItemsRepeater(): Repeater
    {
        $financialLocked = function (mixed $livewire = null): bool {
            $order = null;

            if (is_object($livewire) && method_exists($livewire, 'getRecord')) {
                $record = $livewire->getRecord();
                $order = $record instanceof Order ? $record : null;
            }

            return $order?->financialFieldsLockedFor(Auth::user()) ?? false;
        };

        return Repeater::make('items')
            ->relationship()
            ->schema([
                Select::make('product_id')
                    ->label('Product')
                    ->options(function (): array {
                        return once(fn (): array => Product::query()
                            ->where('stock', '>', 1)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all());
                    })
                    ->required()
                    ->reactive()
                    ->live()
                    ->disabled(fn ($livewire): bool => $financialLocked($livewire))
                    ->dehydrated()
                    ->afterStateHydrated(function (Set $set, Get $get, $state) {
                        $product = Product::find($state);
                        $set('stock', $product?->stock ?? 0);
                        // Jangan timpa snapshot yang sudah tersimpan di order item.
                    })
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $product = Product::with(['penambahanHarga.vendor', 'pengurangans', 'items'])->find($state);
                        if (! $product) {
                            $set('stock', 0);
                            $set('unit_price', 0);
                            $set('unit_penambahan', 0);
                            $set('unit_pengurangan', 0);
                            $set('penambahan_lines', []);
                            $set('pengurangan_lines', []);
                            self::updateTotalPrice($get, $set);

                            return;
                        }

                        $snapshot = self::snapshotPricingFromProduct($product);
                        $set('stock', $snapshot['stock']);
                        $set('unit_price', $snapshot['unit_price']);
                        $set('unit_penambahan', $snapshot['unit_penambahan']);
                        $set('unit_pengurangan', $snapshot['unit_pengurangan']);
                        $set('penambahan_lines', $snapshot['penambahan_lines']);
                        $set('pengurangan_lines', $snapshot['pengurangan_lines']);
                        self::updateTotalPrice($get, $set);
                    })
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->columnSpan([
                        'md' => 5,
                    ])
                    ->searchable(),
                TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->default(1)
                    ->columnSpan([
                        'md' => 1,
                    ])
                    ->minValue(1)
                    ->required()
                    ->reactive()
                    ->disabled(fn ($livewire): bool => $financialLocked($livewire))
                    ->dehydrated()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $stock = $get('stock');
                        if ($state > $stock) {
                            $set('quantity', $stock);
                            Notification::make()->title('Stock tidak mencukupi')->warning()->send();
                        }
                        self::updateTotalPrice($get, $set);
                    }),
                TextInput::make('stock')
                    ->label('Stok')
                    ->disabled()
                    ->dehydrated(false)
                    ->numeric()
                    ->columnSpan([
                        'md' => 1,
                    ]),
                TextInput::make('unit_price')
                    ->label('Unit Price')
                    ->disabled()
                    ->dehydrated()
                    ->prefix('Rp. ')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->dehydrateStateUsing(fn ($state) => is_numeric($state) ? (int) $state : (int) preg_replace('/[^\d]/', '', (string) $state))
                    ->required()
                    ->columnSpan([
                        'md' => 3,
                    ]),
                TextInput::make('unit_penambahan')
                    ->label('Penambahan / unit')
                    ->disabled()
                    ->dehydrated()
                    ->prefix('Rp. ')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->dehydrateStateUsing(fn ($state) => is_numeric($state) ? (int) $state : (int) preg_replace('/[^\d]/', '', (string) $state))
                    ->helperText('Snapshot dari produk. Perbarui lewat “Ambil ulang dari Produk”.')
                    ->columnSpan([
                        'default' => 1,
                        'md' => 5,
                    ]),
                TextInput::make('unit_pengurangan')
                    ->label('Pengurangan / unit')
                    ->disabled()
                    ->dehydrated()
                    ->prefix('Rp. ')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->dehydrateStateUsing(fn ($state) => is_numeric($state) ? (int) $state : (int) preg_replace('/[^\d]/', '', (string) $state))
                    ->helperText('Snapshot dari produk. Perbarui lewat “Ambil ulang dari Produk”.')
                    ->columnSpan([
                        'default' => 1,
                        'md' => 5,
                    ]),
                \Filament\Forms\Components\Hidden::make('penambahan_lines')
                    ->dehydrated()
                    ->default([]),
                \Filament\Forms\Components\Hidden::make('pengurangan_lines')
                    ->dehydrated()
                    ->default([]),
            ])
            ->collapsible()
            ->reorderable(fn ($livewire): bool => ! $financialLocked($livewire))
            ->cloneable(fn ($livewire): bool => ! $financialLocked($livewire))
            ->reactive()
            ->addable(fn ($livewire): bool => ! $financialLocked($livewire))
            ->deletable(fn ($livewire): bool => ! $financialLocked($livewire))
            ->itemLabel(fn (array $state): ?string => Product::find($state['product_id'])?->name)
            ->extraItemActions([
                Action::make('openProduct')
                    ->tooltip('Buka master produk (ubah di sana, lalu kembali & Ambil ulang dari Produk)')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(function (array $arguments, Repeater $component): ?string {
                        $itemData = $component->getRawItemState($arguments['item']);
                        $product = Product::find($itemData['product_id'] ?? null);
                        if (! $product) {
                            return null;
                        }

                        return ProductResource::getUrl('edit', ['record' => $product]);
                    }, shouldOpenInNewTab: true)
                    ->hidden(fn (array $arguments, Repeater $component): bool => blank($component->getRawItemState($arguments['item'])['product_id'] ?? null)),
                Action::make('syncFromProduct')
                    ->tooltip('Ambil harga/penambahan/pengurangan dari produk ini')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->modalHeading('Ambil ulang dari Produk?')
                    ->modalSubmitActionLabel('Terapkan')
                    ->form(function ($livewire): array {
                        $order = self::resolveOrderFromLivewire($livewire);
                        if (! $order?->isContractSigned()) {
                            return [];
                        }

                        return [
                            Textarea::make('reason')
                                ->label('Alasan Amandemen Keuangan')
                                ->required()
                                ->rows(3)
                                ->helperText('Langsung disimpan dan tercatat di riwayat amandemen.')
                                ->placeholder('Contoh: penambahan lighting sesuai request klien'),
                        ];
                    })
                    ->action(function (array $arguments, array $data, Get $get, Set $set, $livewire): void {
                        self::runProductSyncAction(
                            get: $get,
                            set: $set,
                            livewire: $livewire,
                            reason: trim((string) ($data['reason'] ?? '')),
                            onlyItemKey: is_string($arguments['item'] ?? null) ? $arguments['item'] : null,
                        );
                    })
                    ->visible(function ($livewire): bool {
                        $order = self::resolveOrderFromLivewire($livewire);
                        $user = Auth::user();

                        if (! $order || ! $user instanceof User) {
                            return false;
                        }

                        if (! $order->isContractSigned()) {
                            return true;
                        }

                        return $user->canApplyOrderFinancialAmendment();
                    })
                    ->hidden(fn (array $arguments, Repeater $component): bool => blank($component->getRawItemState($arguments['item'])['product_id'] ?? null)),
            ])
            ->defaultItems(1)
            ->hiddenLabel()
            ->columns([
                'default' => 1,
                'md' => 10,
            ])
            ->reactive()
            ->afterStateUpdated(function (Get $get, Set $set) {
                self::updateTotalPrice($get, $set);
            });
    }

    public static function resolveOrderFromLivewire(mixed $livewire): ?Order
    {
        if (! is_object($livewire) || ! method_exists($livewire, 'getRecord')) {
            return null;
        }

        $record = $livewire->getRecord();

        return $record instanceof Order ? $record : null;
    }

    /**
     * Sync form dari Produk; jika order sudah TTD, simpan + catat amandemen sekali jalan.
     */
    public static function runProductSyncAction(
        Get $get,
        Set $set,
        mixed $livewire,
        string $reason = '',
        ?string $onlyItemKey = null,
    ): void {
        $order = self::resolveOrderFromLivewire($livewire);
        $user = Auth::user();

        if ($order?->isContractSigned()) {
            if (! $user instanceof User || ! $user->canApplyOrderFinancialAmendment()) {
                Notification::make()
                    ->title('Tidak berhak amandemen')
                    ->danger()
                    ->send();

                return;
            }

            if ($reason === '') {
                Notification::make()
                    ->title('Alasan amandemen wajib')
                    ->danger()
                    ->send();

                return;
            }
        }

        $before = $order ? OrderFinancialSnapshot::fromOrder($order) : null;
        $count = self::syncItemsFromProducts($get, $set, $onlyItemKey, $livewire);

        if ($count < 1) {
            Notification::make()
                ->title('Tidak ada item yang diperbarui')
                ->body('Pastikan ada produk di daftar item, lalu coba lagi.')
                ->warning()
                ->send();

            return;
        }

        if ($order && $order->isContractSigned() && $user instanceof User) {
            $itemsState = self::resolveFormItems($get, $livewire);
            self::persistSyncedItemsAndAmend($order, $itemsState, $reason, $user, $before ?? []);

            if (is_object($livewire) && method_exists($livewire, 'reloadAfterProductSync')) {
                $livewire->reloadAfterProductSync();
            }

            Notification::make()
                ->title('Amandemen tersimpan')
                ->body("Snapshot {$count} item diambil dari Produk, angka order diperbarui, dan riwayat tercatat.")
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title("Snapshot {$count} item diambil dari Produk")
            ->body('Angka form sudah diperbarui. Simpan order bila ingin menyimpan perubahan.')
            ->success()
            ->send();
    }

    /**
     * @param  array<string|int, mixed>  $itemsState
     * @param  array<string, mixed>  $before
     */
    public static function persistSyncedItemsAndAmend(
        Order $order,
        array $itemsState,
        string $reason,
        User $user,
        array $before,
    ): void {
        DB::transaction(function () use ($order, $itemsState, $reason, $user, $before): void {
            $totalPrice = 0;
            $totalPenambahan = 0;
            $totalPengurangan = 0;

            foreach ($itemsState as $item) {
                if (! is_array($item) || empty($item['product_id'])) {
                    continue;
                }

                $query = OrderProduct::query()->where('order_id', $order->id);
                if (! empty($item['id'])) {
                    $query->where('id', $item['id']);
                } else {
                    $query->where('product_id', $item['product_id']);
                }

                $orderProduct = $query->first();
                if (! $orderProduct) {
                    continue;
                }

                $qty = max(1, (int) ($item['quantity'] ?? $orderProduct->quantity ?? 1));
                $unitPrice = self::normalizeMoney($item['unit_price'] ?? 0);
                $unitPenambahan = self::normalizeMoney($item['unit_penambahan'] ?? 0);
                $unitPengurangan = self::normalizeMoney($item['unit_pengurangan'] ?? 0);

                $penambahanLines = OrderFinancialSnapshot::normalizeLines($item['penambahan_lines'] ?? []);
                $penguranganLines = OrderFinancialSnapshot::normalizeLines($item['pengurangan_lines'] ?? []);

                if ($penambahanLines === [] || $penguranganLines === []) {
                    $product = Product::query()
                        ->with(['penambahanHarga.vendor', 'pengurangans', 'items'])
                        ->find($item['product_id']);
                    if ($product) {
                        $pricing = self::snapshotPricingFromProduct($product);
                        if ($penambahanLines === []) {
                            $penambahanLines = $pricing['penambahan_lines'];
                        }
                        if ($penguranganLines === []) {
                            $penguranganLines = $pricing['pengurangan_lines'];
                        }
                    }
                }

                $orderProduct->update([
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_penambahan' => $unitPenambahan,
                    'unit_pengurangan' => $unitPengurangan,
                    'penambahan_lines' => $penambahanLines,
                    'pengurangan_lines' => $penguranganLines,
                ]);

                $totalPrice += $unitPrice * $qty;
                $totalPenambahan += $unitPenambahan * $qty;
                $totalPengurangan += $unitPengurangan * $qty;
            }

            $order->promo = (int) ($order->promo ?? 0);
            $order->total_price = $totalPrice;
            $order->penambahan = $totalPenambahan;
            $order->pengurangan = $totalPengurangan;
            $order->save();

            $after = OrderFinancialSnapshot::fromOrder($order->fresh('items'));

            if (OrderFinancialSnapshot::hasChanged($before, $after)) {
                OrderAmendment::create([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'reason' => $reason,
                    'before' => $before,
                    'after' => $after,
                    'diff' => OrderFinancialSnapshot::diff($before, $after),
                ]);
            }
        });
    }

    /**
     * Salin harga/penambahan/pengurangan terkini dari master Produk ke snapshot item order.
     *
     * @return int Jumlah item yang diperbarui
     */
    public static function syncItemsFromProducts(Get $get, Set $set, ?string $onlyItemKey = null, mixed $livewire = null): int
    {
        $items = self::resolveFormItems($get, $livewire);
        if ($items === []) {
            return 0;
        }

        $productIds = collect($items)
            ->filter(fn ($item) => is_array($item) && ! empty($item['product_id']))
            ->pluck('product_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        if ($productIds === []) {
            return 0;
        }

        $products = Product::query()
            ->with(['penambahanHarga.vendor', 'pengurangans', 'items'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $synced = 0;

        foreach ($items as $key => $item) {
            if ($onlyItemKey !== null && (string) $key !== (string) $onlyItemKey) {
                continue;
            }

            if (! is_array($item) || empty($item['product_id'])) {
                continue;
            }

            $product = $products->get($item['product_id']);
            if (! $product) {
                continue;
            }

            $snapshot = self::snapshotPricingFromProduct($product);
            $items[$key]['unit_price'] = $snapshot['unit_price'];
            $items[$key]['unit_penambahan'] = $snapshot['unit_penambahan'];
            $items[$key]['unit_pengurangan'] = $snapshot['unit_pengurangan'];
            $items[$key]['penambahan_lines'] = $snapshot['penambahan_lines'];
            $items[$key]['pengurangan_lines'] = $snapshot['pengurangan_lines'];
            $items[$key]['stock'] = $snapshot['stock'];
            $synced++;
        }

        if ($synced < 1) {
            return 0;
        }

        self::writeFormItems($set, $livewire, $items);
        self::recalculateTotalsFromItems($items, $get, $set, $livewire);

        return $synced;
    }

    /**
     * @return array{
     *     unit_price: int,
     *     unit_penambahan: int,
     *     unit_pengurangan: int,
     *     stock: int,
     *     penambahan_lines: list<array{id: int|null, name: string, amount: int}>,
     *     pengurangan_lines: list<array{id: int|null, name: string, amount: int}>
     * }
     */
    public static function snapshotPricingFromProduct(Product $product): array
    {
        $product->loadMissing(['penambahanHarga.vendor', 'pengurangans', 'items']);

        $unitPrice = (int) ($product->product_price ?? 0);
        $unitPenambahan = (int) ($product->penambahan_publish ?? 0);
        $unitPengurangan = (int) ($product->pengurangan ?? 0);

        $penambahanLines = $product->penambahanHarga
            ->map(function ($row) {
                $name = trim((string) ($row->vendor?->name ?? ''));
                if ($name === '') {
                    $name = trim(strip_tags((string) ($row->description ?? '')));
                }

                return [
                    'id' => $row->id ? (int) $row->id : null,
                    'name' => $name !== '' ? $name : 'Penambahan',
                    'amount' => (int) ($row->harga_publish ?? $row->amount ?? 0),
                ];
            })
            ->filter(fn (array $line) => $line['amount'] !== 0)
            ->values()
            ->all();

        $penguranganLines = $product->pengurangans
            ->map(function ($row) {
                $name = trim(strip_tags((string) ($row->description ?? '')));

                return [
                    'id' => $row->id ? (int) $row->id : null,
                    'name' => $name !== '' ? $name : 'Pengurangan',
                    'amount' => (int) ($row->amount ?? 0),
                ];
            })
            ->filter(fn (array $line) => $line['amount'] !== 0)
            ->values()
            ->all();

        if ($unitPenambahan === 0 && $penambahanLines !== []) {
            $unitPenambahan = (int) collect($penambahanLines)->sum('amount');
        }

        if ($unitPengurangan === 0 && $penguranganLines !== []) {
            $unitPengurangan = (int) collect($penguranganLines)->sum('amount');
        }

        if ($unitPrice === 0 && $product->items->isNotEmpty()) {
            $unitPrice = (int) $product->items->sum(
                fn ($row) => (int) ($row->price_public ?? 0)
            );
        }

        return [
            'unit_price' => $unitPrice,
            'unit_penambahan' => $unitPenambahan,
            'unit_pengurangan' => $unitPengurangan,
            'stock' => (int) ($product->stock ?? 0),
            'penambahan_lines' => OrderFinancialSnapshot::normalizeLines($penambahanLines),
            'pengurangan_lines' => OrderFinancialSnapshot::normalizeLines($penguranganLines),
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    protected static function resolveFormItems(Get $get, mixed $livewire = null): array
    {
        foreach (['/items', 'items', '../items', '../../items', '../../../items'] as $path) {
            $candidate = $get($path);
            if (is_array($candidate) && $candidate !== []) {
                return $candidate;
            }
        }

        if (is_object($livewire) && isset($livewire->data) && is_array($livewire->data)) {
            $candidate = $livewire->data['items'] ?? null;
            if (is_array($candidate) && $candidate !== []) {
                return $candidate;
            }
        }

        return [];
    }

    /**
     * @param  array<string|int, mixed>  $items
     */
    protected static function writeFormItems(Set $set, mixed $livewire, array $items): void
    {
        $set('/items', $items);
        $set('items', $items);

        if (is_object($livewire) && isset($livewire->data) && is_array($livewire->data)) {
            $livewire->data['items'] = $items;
        }
    }

    /**
     * @param  array<string|int, mixed>  $items
     */
    protected static function recalculateTotalsFromItems(array $items, Get $get, Set $set, mixed $livewire = null): void
    {
        $calculatedTotalPrice = 0;
        $calculatedProductPengurangan = 0;
        $calculatedProductPenambahan = 0;

        foreach ($items as $item) {
            if (! is_array($item) || empty($item['product_id']) || empty($item['quantity'])) {
                continue;
            }

            $quantity = (int) ($item['quantity'] ?? 0);
            $calculatedTotalPrice += self::normalizeMoney($item['unit_price'] ?? 0) * $quantity;
            $calculatedProductPenambahan += self::normalizeMoney($item['unit_penambahan'] ?? 0) * $quantity;
            $calculatedProductPengurangan += self::normalizeMoney($item['unit_pengurangan'] ?? 0) * $quantity;
        }

        $promo = self::normalizeMoney(
            (is_object($livewire) && isset($livewire->data['promo']))
                ? $livewire->data['promo']
                : ($get('/promo') ?? $get('promo') ?? 0)
        );

        $grandTotal = Order::computeGrandTotalFromValues(
            $calculatedTotalPrice,
            $calculatedProductPenambahan,
            $promo,
            $calculatedProductPengurangan
        );

        $set('/total_price', $calculatedTotalPrice);
        $set('/pengurangan', $calculatedProductPengurangan);
        $set('/penambahan', $calculatedProductPenambahan);
        $set('/grand_total', $grandTotal);
        $set('total_price', $calculatedTotalPrice);
        $set('pengurangan', $calculatedProductPengurangan);
        $set('penambahan', $calculatedProductPenambahan);
        $set('grand_total', $grandTotal);

        if (is_object($livewire) && isset($livewire->data) && is_array($livewire->data)) {
            $livewire->data['total_price'] = $calculatedTotalPrice;
            $livewire->data['pengurangan'] = $calculatedProductPengurangan;
            $livewire->data['penambahan'] = $calculatedProductPenambahan;
            $livewire->data['grand_total'] = $grandTotal;
        }

        self::updateDependentFinancialFields($get, $set);
    }

    public static function updateTotalPrice(Get $get, Set $set): void
    {
        $selectedProducts = collect(self::resolveFormItems($get))
            ->filter(fn ($item) => is_array($item) && ! empty($item['product_id']) && ! empty($item['quantity']));

        $calculatedTotalPrice = 0;
        $calculatedProductPengurangan = 0;
        $calculatedProductPenambahan = 0;

        foreach ($selectedProducts as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = self::normalizeMoney($item['unit_price'] ?? 0);
            $unitPenambahan = self::normalizeMoney($item['unit_penambahan'] ?? 0);
            $unitPengurangan = self::normalizeMoney($item['unit_pengurangan'] ?? 0);

            $calculatedTotalPrice += $unitPrice * $quantity;
            $calculatedProductPenambahan += $unitPenambahan * $quantity;
            $calculatedProductPengurangan += $unitPengurangan * $quantity;
        }

        $set('/total_price', $calculatedTotalPrice);
        $set('/pengurangan', $calculatedProductPengurangan);
        $set('/penambahan', $calculatedProductPenambahan);

        $promo = self::normalizeMoney($get('/promo') ?? $get('promo') ?? 0);
        $grandTotal = Order::computeGrandTotalFromValues(
            $calculatedTotalPrice,
            $calculatedProductPenambahan,
            $promo,
            $calculatedProductPengurangan
        );
        $set('/grand_total', $grandTotal);

        self::updateDependentFinancialFields($get, $set);
    }

    public static function normalizeMoney(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }

    public static function updateExchangePaid(Get $get, Set $set): void
    {
        $paidAmount = $get('/paid_amount') ?? $get('paid_amount') ?? 0;
        $totalPrice = $get('/total_price') ?? $get('total_price') ?? 0;
        $promoPrice = $get('/promo') ?? $get('promo') ?? 0;
        $penambahanPrice = $get('/penambahan') ?? $get('penambahan') ?? 0;
        $penguranganPrice = $get('/pengurangan') ?? $get('pengurangan') ?? 0;
        $exchangePaid = $totalPrice - $paidAmount - $promoPrice - $penguranganPrice + $penambahanPrice;
        $set('/change_amount', $exchangePaid);
    }

    public static function updateDependentFinancialFields(Get $get, Set $set): void
    {
        $normalize = fn ($v) => is_numeric($v) ? (int) $v : (int) preg_replace('/[^\d]/', '', (string) $v);
        $total_price = $normalize($get('/total_price') ?? $get('total_price') ?? 0);
        $pengurangan_val = $normalize($get('/pengurangan') ?? $get('pengurangan') ?? 0);
        $promo_val = $normalize($get('/promo') ?? $get('promo') ?? 0);
        $penambahan_val = $normalize($get('/penambahan') ?? $get('penambahan') ?? 0);
        $grandTotal = Order::computeGrandTotalFromValues(
            $total_price,
            $penambahan_val,
            $promo_val,
            $pengurangan_val
        );
        $set('/grand_total', $grandTotal);

        $paymentItems = $get('/Jika Ada Pembayaran') ?? $get('Jika Ada Pembayaran') ?? [];
        $bayar = 0;
        if (is_array($paymentItems)) {
            foreach ($paymentItems as $paymentItem) {
                $nominalValue = $normalize($paymentItem['nominal'] ?? 0);
                $bayar += $nominalValue;
            }
        }
        $set('/bayar', $bayar);

        // Hitung 'sisa'
        $sisa = $grandTotal - $bayar;
        $set('/sisa', $sisa);

        // Update 'is_paid'
        $set('/is_paid', $sisa <= 0);

        // Update 'closing_date' based on the first payment date
        self::updateClosingDate($get, $set);
    }

    public static function updateClosingDate(Get $get, Set $set): void
    {
        $paymentItems = $get('/Jika Ada Pembayaran') ?? $get('Jika Ada Pembayaran') ?? [];
        if (! empty($paymentItems)) {
            // Urutkan pembayaran berdasarkan tgl_bayar untuk mendapatkan yang paling awal
            usort($paymentItems, function ($a, $b) {
                return strtotime($a['tgl_bayar'] ?? 'now') <=> strtotime($b['tgl_bayar'] ?? 'now');
            });
            if (isset($paymentItems[0]['tgl_bayar']) && ! empty($paymentItems[0]['tgl_bayar'])) {
                $set('/closing_date', Carbon::parse($paymentItems[0]['tgl_bayar'])->format('Y-m-d'));

                return; // Keluar setelah menemukan tanggal pembayaran pertama
            }
        }
        // Jika tidak ada pembayaran, bisa di-set ke default atau dibiarkan (tergantung kebutuhan)
        // $set('closing_date', now()->format('Y-m-d')); // Atau biarkan saja jika tidak ada pembayaran
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total proyek yang sedang diproses';
    }
}
