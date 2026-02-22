<?php

namespace App\Filament\Resources\SimulasiProduks\Schemas;

use App\Enums\MonthEnum;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\SimulasiProduks\SimulasiProdukResource;
use App\Models\Product;
use App\Models\Prospect;
use App\Models\SimulasiProduk;
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
                                            $new_total_price = 0;
                                            $new_penambahan = 0;
                                            $new_pengurangan = 0;

                                            if ($state) {
                                                $product = Product::find($state);
                                                if ($product) {
                                                    $new_total_price = $product->product_price ?? 0;
                                                    $new_penambahan = $product->penambahan_publish ?? 0;
                                                    $new_pengurangan = $product->pengurangan ?? 0;
                                                }
                                            }

                                            $set('total_price', $new_total_price);
                                            $set('penambahan', $new_penambahan);
                                            $set('pengurangan', $new_pengurangan);

                                            SimulasiProdukResource::recalculateGrandTotal($get, $set);
                                        })
                                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                            $new_total_price = 0;
                                            $new_penambahan = 0;
                                            $new_pengurangan = 0;

                                            if ($state) {
                                                $product = Product::find($state);
                                                if ($product) {
                                                    $new_total_price = $product->product_price ?? 0;
                                                    $new_penambahan = $product->penambahan_publish ?? 0;
                                                    $new_pengurangan = $product->pengurangan ?? 0;
                                                }
                                            }

                                            $set('total_price', $new_total_price);
                                            $set('penambahan', $new_penambahan);
                                            $set('pengurangan', $new_pengurangan);

                                            SimulasiProdukResource::recalculateGrandTotal($get, $set);
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
                                    TextInput::make('total_price')
                                        ->label('Base Total Price')
                                        ->prefix('Rp')
                                        ->mask(RawJs::make('$money($input)'))
                                        ->stripCharacters(',')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0)
                                        ->reactive()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            SimulasiProdukResource::recalculateGrandTotal($get, $set);
                                        })
                                        ->formatStateUsing(fn ($state) => number_format((float) $state, 0, '.', ','))
                                        ->helperText('Nilai ini otomatis diambil dari Product Price (harga paket dasar sebelum potongan/pengurangan dan penambahan publish) dan akan ikut berubah jika harga produk diperbarui lalu simulasi di-refresh.'),
                                    TextInput::make('promo')
                                        ->label('Potongan Harga (Promo)')
                                        ->prefix('Rp')
                                        ->mask(RawJs::make('$money($input)'))
                                        ->stripCharacters(',')
                                        ->default(0)
                                        ->reactive()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => SimulasiProdukResource::recalculateGrandTotal($get, $set))
                                        ->formatStateUsing(fn ($state) => number_format((float) $state, 0, '.', ',')),
                                    TextInput::make('penambahan')
                                        ->label('Penambahan Biaya')
                                        ->prefix('Rp')
                                        ->mask(RawJs::make('$money($input)'))
                                        ->stripCharacters(',')
                                        ->default(0)
                                        ->readOnly()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => number_format((float) $state, 0, '.', ','))
                                        ->helperText('Nilai ini otomatis diambil dari Produk (Penambahan Publish) dan tidak dapat diubah dari simulasi.'),
                                    TextInput::make('pengurangan')
                                        ->label('Pengurangan Lain')
                                        ->prefix('Rp')
                                        ->mask(RawJs::make('$money($input)'))
                                        ->stripCharacters(',')
                                        ->default(0)
                                        ->readOnly()
                                        ->dehydrated()
                                        ->formatStateUsing(fn ($state) => number_format((float) $state, 0, '.', ','))
                                        ->helperText('Nilai ini otomatis diambil dari Produk (Total Pengurangan) dan tidak dapat diubah dari simulasi.'),
                                    TextInput::make('grand_total')
                                        ->label('Grand Total')
                                        ->prefix('Rp')
                                        ->mask(RawJs::make('$money($input)'))
                                        ->stripCharacters(',')
                                        ->readOnly()
                                        ->dehydrated()
                                        ->default(0)
                                        ->formatStateUsing(fn ($state) => number_format((float) $state, 0, '.', ','))
                                        ->helperText('Grand Total dihitung dari Base Total Price + Penambahan - Promo - Pengurangan dan akan mengikuti perubahan jika harga produk disinkronkan.'),
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
                                        ->dehydrated(),
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
                                ->default(0)
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
                                            $fail('Total Pembayaran (DP + Termin) tidak sama dengan Grand Total (Nilai Paket). Selisih: ' . number_format($difference, 0, '.', ','));
                                        }
                                    },
                                ])
                                ->formatStateUsing(fn ($state) => number_format((float) $state, 0, '.', ',')),
                        ]),
                    Tab::make('Meta Info')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Select::make('user_id')
                                ->relationship('user', 'name')
                                ->label('Created By')
                                ->required()
                                ->searchable()
                                ->disabled()
                                ->preload()
                                ->default(fn () => Auth::id())
                                ->dehydrated(),
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