<?php

namespace App\Filament\Resources\SimulasiProduks\Schemas;

use App\Enums\MonthEnum;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\SimulasiProduks\SimulasiProdukResource;
use App\Models\Product;
use App\Models\Prospect;
use App\Models\SimulasiProduk;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SimulasiProdukForm
{
    private static function syncPricingFromProduct($productId, Get $get, Set $set): void
    {
        $totalPrice = 0;
        $penambahan = 0;
        $pengurangan = 0;

        if ($productId) {
            $product = Product::query()->whereKey($productId)->first();
            if ($product) {
                $totalPrice = (int) ($product->product_price ?? 0);
                $penambahan = (int) ($product->penambahan_publish ?? 0);
                $pengurangan = (int) ($product->pengurangan ?? 0);

                if ($totalPrice === 0) {
                    $totalPrice = (int) $product->items()->sum('price_public');
                }

                if ($penambahan === 0) {
                    $penambahan = (int) $product->penambahanHarga()->sum('harga_publish');
                }

                if ($pengurangan === 0) {
                    $pengurangan = (int) $product->pengurangans()->sum('amount');
                }
            }
        }

        $set('total_price', $totalPrice);
        $set('penambahan', $penambahan);
        $set('pengurangan', $pengurangan);

        SimulasiProdukResource::recalculateGrandTotal($get, $set);
    }

    public static function configure(): array
    {
        return [
            Tabs::make('Tabs')
                ->tabs([
                    Tab::make('Detail Simulasi')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('Product & Pricing')
                                ->icon('heroicon-o-shopping-bag')
                                ->columns(2)
                                ->schema([
                                    Select::make('product_id')
                                        ->relationship('product', 'name')
                                        ->label('Select Base Product')
                                        ->searchable()
                                        ->preload()
                                        ->reactive()
                                        ->live()
                                        ->afterStateHydrated(function (Set $set, Get $get, $state) {
                                            self::syncPricingFromProduct($state, $get, $set);
                                        })
                                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                            self::syncPricingFromProduct($state, $get, $set);
                                        })
                                        ->columnSpanFull()
                                        ->suffixAction(
                                            Action::make('openSelectedProduct')
                                                ->icon('heroicon-m-arrow-top-right-on-square')
                                                ->tooltip('Open selected product in new tab')
                                                ->url(function (Get $get): ?string {
                                                    $productId = $get('product_id');
                                                    if (! $productId) {
                                                        return null;
                                                    }
                                                    $product = Product::find($productId);

                                                    return $product ? ProductResource::getUrl('edit', ['record' => $product]) : null;
                                                }, shouldOpenInNewTab: true)
                                                ->hidden(fn (Get $get): bool => blank($get('product_id'))))
                                        ->columnSpanFull(),
                                    Select::make('user_id')
                                        ->label('Account Manager')
                                        ->options(fn () => User::role('Account Manager')->pluck('name', 'id')->toArray())
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->default(fn () => optional(Auth::user())->roles?->contains('name', 'Account Manager') ? Auth::id() : null)
                                        ->columnSpanFull(),
                                    TextInput::make('total_price')
                                        ->label('Base Total Price')
                                        ->prefix('Rp')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0)
                                        ->reactive()
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            SimulasiProdukResource::recalculateGrandTotal($get, $set);
                                        })
                                        ->formatStateUsing(fn ($state) => number_format((float) $state, 0, '.', ','))
                                        ->helperText('Nilai ini otomatis diambil dari Product Price (harga paket dasar sebelum potongan/pengurangan dan penambahan publish) dan akan ikut berubah jika harga produk diperbarui lalu simulasi di-refresh.'),
                                    TextInput::make('penambahan')
                                        ->label('Penambahan Biaya')
                                        ->prefix('Rp')
                                        ->default(0)
                                        ->readOnly()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => number_format((float) $state, 0, '.', ','))
                                        ->helperText('Nilai ini otomatis diambil dari Produk (Penambahan Publish) dan tidak dapat diubah dari simulasi.'),
                                    TextInput::make('pengurangan')
                                        ->label('Pengurangan Lain')
                                        ->prefix('Rp')
                                        ->default(0)
                                        ->readOnly()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => number_format((float) $state, 0, '.', ','))
                                        ->helperText('Nilai ini otomatis diambil dari Produk (Total Pengurangan) dan tidak dapat diubah dari simulasi.'),
                                    TextInput::make('grand_total')
                                        ->label('Grand Total')
                                        ->prefix('Rp')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0)
                                        ->formatStateUsing(fn ($state) => number_format((float) $state, 0, '.', ','))
                                        ->helperText('Grand Total dihitung dari Base Total Price + Penambahan - Pengurangan dan akan mengikuti perubahan jika harga produk disinkronkan.'),
                                ])
                                ->columnSpanFull(),
                            Section::make('Simulation Details')
                                ->icon('heroicon-o-identification')
                                ->schema([
                                    Select::make('prospect_id')
                                        ->relationship(
                                            name: 'prospect',
                                            titleAttribute: 'name_event',
                                            modifyQueryUsing: fn (Builder $query, ?SimulasiProduk $record) => $query->whereDoesntHave('orders', function (Builder $orderQuery) {
                                                $orderQuery->whereNotNull('status');
                                            })->when($record, fn ($q) => $q->orWhere('id', $record->prospect_id)),
                                        )
                                        ->label('Select Prospect (for Simulation Name & Slug)')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->reactive()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Set $set, ?string $state) {
                                            if ($state) {
                                                $prospect = Prospect::find($state);
                                                if ($prospect && isset($prospect->name_event)) {
                                                    $set('name', $prospect->name_event);
                                                    $set('slug', Str::slug($prospect->name_event));
                                                } else {
                                                    $set('name', null);
                                                    $set('slug', null);
                                                }
                                            } else {
                                                $set('name', null);
                                                $set('slug', null);
                                            }
                                        })
                                        ->columnSpanFull(),
                                    TextInput::make('contract_number')
                                        ->label('Nomor Kontrak / Surat')
                                        ->maxLength(255)
                                        ->helperText('Isi manual jika ingin override penomoran otomatis.'),
                                    TextInput::make('name_ttd')
                                        ->label('Name TTD')
                                        ->maxLength(255),
                                    TextInput::make('title_ttd')
                                        ->label('Title TTD')
                                        ->maxLength(255),
                                    Hidden::make('name')
                                        ->dehydrated(false),

                                    TextInput::make('slug')
                                        ->required()
                                        ->maxLength(255)
                                        ->disabled()
                                        ->dehydrated()
                                        ->unique(SimulasiProduk::class, 'slug', ignoreRecord: true),
                                    RichEditor::make('notes')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),
                    Tab::make('Pola Pembayaran')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([
                            TextInput::make('grand_total_display')
                                ->label('Nilai Paket')
                                ->prefix('Rp')
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(fn (Get $get) => number_format(SimulasiProdukResource::parseCurrency($get('grand_total')), 0, '.', ',')),
                            TextInput::make('payment_dp_amount')
                                ->label('Down Payment (DP)')
                                ->prefix('Rp')
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters(',')
                                ->default(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $dp = SimulasiProdukResource::parseCurrency($state);
                                    $items = $get('payment_simulation') ?? [];
                                    $total = $dp;
                                    foreach ($items as $item) {
                                        $total += SimulasiProdukResource::parseCurrency($item['nominal'] ?? 0);
                                    }
                                    $set('total_simulation', $total);
                                })
                                ->formatStateUsing(fn ($state) => number_format((float) $state, 0, '.', ',')),
                            Repeater::make('payment_simulation')
                                ->label('Simulasi Pembayaran')
                                ->collapsed()
                                ->itemLabel(function (array $state): ?string {
                                    $bulanRaw = $state['bulan'] ?? null;
                                    $bulan = is_object($bulanRaw)
                                        ? (method_exists($bulanRaw, 'getLabel') ? $bulanRaw->getLabel() : (property_exists($bulanRaw, 'value') ? $bulanRaw->value : (string) $bulanRaw))
                                        : (is_string($bulanRaw) ? $bulanRaw : 'Termin');
                                    $tahun = (string) ($state['tahun'] ?? '');
                                    $nominalRaw = $state['nominal'] ?? 0;
                                    $nominalVal = is_numeric($nominalRaw) ? (float) $nominalRaw : (float) \App\Filament\Resources\SimulasiProduks\SimulasiProdukResource::parseCurrency($nominalRaw);

                                    return $bulan.' '.$tahun.' - Rp '.number_format($nominalVal, 0, '.', ',');
                                })
                                ->schema([
                                    TextInput::make('persen')
                                        ->label('Persen (%)')
                                        ->numeric()
                                        ->suffix('%')
                                        ->default(100)
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $grandTotal = SimulasiProdukResource::parseCurrency($get('../../grand_total'));
                                            $dp = SimulasiProdukResource::parseCurrency($get('../../payment_dp_amount'));
                                            $remaining = $grandTotal - $dp;
                                            if ($remaining > 0) {
                                                $nominal = $remaining * ($state / 100);
                                                $set('nominal', $nominal);
                                                $total = $dp;
                                                $items = $get('../../payment_simulation') ?? [];
                                                foreach ($items as $item) {
                                                    $total += SimulasiProdukResource::parseCurrency($item['nominal'] ?? 0);
                                                }
                                                $set('../../total_simulation', $total);
                                            }
                                        }),
                                    TextInput::make('nominal')
                                        ->label('Nominal')
                                        ->prefix('Rp')
                                        ->mask(RawJs::make('$money($input)'))
                                        ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                        ->stripCharacters(',')
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $grandTotal = SimulasiProdukResource::parseCurrency($get('../../grand_total'));
                                            $dp = SimulasiProdukResource::parseCurrency($get('../../payment_dp_amount'));
                                            $remaining = $grandTotal - $dp;
                                            $nominalVal = SimulasiProdukResource::parseCurrency($state);
                                            if ($remaining > 0) {
                                                $persen = ($nominalVal / $remaining) * 100;
                                                $set('persen', number_format($persen, 2));
                                            }
                                            $items = $get('../../payment_simulation') ?? [];
                                            $total = $dp;
                                            foreach ($items as $item) {
                                                $total += SimulasiProdukResource::parseCurrency($item['nominal'] ?? 0);
                                            }
                                            $set('../../total_simulation', $total);
                                        })
                                        ->formatStateUsing(fn ($state) => number_format((float) $state, 0, '.', ',')),
                                    Select::make('bulan')
                                        ->label('Bulan / Termin')
                                        ->options(MonthEnum::class),
                                    TextInput::make('tahun')
                                        ->label('Tahun')
                                        ->numeric()
                                        ->default(date('Y')),
                                ])
                                ->columns(3)
                                ->addActionLabel('Tambah Pembayaran')
                                ->reorderable(true)
                                ->reorderableWithButtons()
                                ->columnSpanFull(),
                            TextInput::make('total_simulation')
                                ->label('Total Pembayaran (DP + Termin)')
                                ->prefix('Rp')
                                ->disabled()
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters(',')
                                ->dehydrated()
                                ->default(0)
                                ->rules([
                                    fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $grandTotal = SimulasiProdukResource::parseCurrency($get('grand_total'));
                                        $currentTotal = SimulasiProdukResource::parseCurrency($value);
                                        if (abs($grandTotal - $currentTotal) > 1000) {
                                            $difference = $grandTotal - $currentTotal;
                                            $fail('Total Pembayaran (DP + Termin) tidak sama dengan Grand Total (Nilai Paket). Selisih: '.number_format($difference, 0, '.', ','));
                                        }
                                    },
                                ])
                                ->formatStateUsing(fn ($state) => number_format((float) $state, 0, '.', ',')),
                        ]),
                    Tab::make('Meta Info')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            TextInput::make('created_by_display')
                                ->label('Created By')
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateHydrated(function ($component, $state, ?SimulasiProduk $record): void {
                                    $component->state($record?->user?->name ?? '-');
                                }),
                            TextInput::make('created_at_display')
                                ->label('Dibuat')
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateHydrated(function ($component, $state, ?SimulasiProduk $record): void {
                                    $component->state($record?->created_at?->diffForHumans());
                                }),
                            TextInput::make('updated_at_display')
                                ->label('Terakhir Diubah')
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateHydrated(function ($component, $state, ?SimulasiProduk $record): void {
                                    $component->state($record?->updated_at?->diffForHumans());
                                }),
                            TextInput::make('last_edited_by_display')
                                ->label('Terakhir Diedit Oleh')
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateHydrated(function ($component, $state, ?SimulasiProduk $record): void {
                                    $component->state($record?->lastEditedBy?->name ?? '-');
                                }),
                        ])
                        ->hidden(fn (?SimulasiProduk $record) => $record === null),
                ])
                ->columnSpanFull(),
        ];
    }
}
