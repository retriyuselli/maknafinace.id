<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Expense;
use App\Models\NotaDinas;
use App\Models\NotaDinasDetail;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Prospect;
use App\Models\Vendor;
use App\Support\Rupiah;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Step::make('Informasi Proyek')
                    ->icon('heroicon-o-information-circle')
                    ->description('Detail dasar proyek')
                    ->schema([
                        TextInput::make('number')
                            ->default('MW-'.random_int(100000, 999999))
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->maxLength(32)
                            ->unique(Order::class, 'number', ignoreRecord: true),
                        Select::make('prospect_id')
                            ->options(function (Get $get, ?Order $record) {
                                if ($record && $record->exists) {
                                    $currentId = $record->prospect_id ?? $get('prospect_id');
                                    $currentName = $record->prospect?->name_event ?? Prospect::find($currentId)?->name_event;

                                    return $currentId ? [$currentId => ($currentName ?? (string) $currentId)] : [];
                                }

                                $currentId = $get('prospect_id');
                                $query = Prospect::query()->whereDoesntHave('orders', function ($q) {
                                    $q->whereNotNull('status');
                                });
                                if ($currentId) {
                                    $query->orWhere('id', $currentId);
                                }

                                return $query->pluck('name_event', 'id')->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->unique(Order::class, 'prospect_id', ignoreRecord: true)
                            ->label('Prospek')
                            ->debounce(500)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $prospect = Prospect::find($state);
                                    if ($prospect) {
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
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),
                        TextInput::make('name')
                            ->required()
                            ->readOnly()
                            ->label('Nama Acara')
                            ->debounce(500),
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->default(Auth::user()->id)
                            ->label('Account Manager'),
                        TextInput::make('slug')
                            ->readOnly()
                            ->maxLength(255),
                        Select::make('employee_id')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->required()
                            ->label('Event Manager')
                            ->helperText('Jika belum ada isi dengan makna wedding'),
                        TextInput::make('no_kontrak')
                            ->required()
                            ->label('No. Kontrak')
                            ->maxLength(255),
                        TextInput::make('pax')
                            ->required()
                            ->label('Pax')
                            ->default(1000)
                            ->numeric(),
                        FileUpload::make('doc_kontrak')
                            ->label('Upload Kontrak')
                            ->reorderable()
                            ->required()
                            ->helperText('pastikan kontrak sudah semua ditanda tangani')
                            ->openable()
                            ->directory('doc_kontrak')
                            ->downloadable()
                            ->acceptedFileTypes(['application/pdf']),
                        FileUpload::make('agreement_product')
                            ->label('File Persetujuan Produk')
                            ->reorderable()
                            ->required()
                            ->helperText('pastikan file persetujuan produk sudah semua ditanda tangani (one up level)')
                            ->openable()
                            ->directory('agreement_product')
                            ->downloadable()
                            ->acceptedFileTypes(['application/pdf']),
                        ToggleButtons::make('status')
                            ->inline()
                            ->options(OrderStatus::class)
                            ->label('Status Pesanan')
                            ->columnSpan(2)
                            ->required()
                            ->helperText('Status Done: Finance hanya bisa view, Super Admin bisa edit.'),
                        RichEditor::make('note')
                            ->label('Keterangan Tambahan')
                            ->fileAttachmentsDirectory('orders')
                            ->columnSpan(3)
                            ->fileAttachmentsDisk('public'),
                    ]),
                Step::make('Detail Pembayaran')
                    ->icon('heroicon-o-currency-dollar')
                    ->description('Produk dan informasi pembayaran')
                    ->schema([
                        Section::make('Product dipesan')
                            ->schema([OrderResource::getItemsRepeater()])
                            ->columnSpanFull(),
                        Section::make('Data Pembayaran')
                            ->schema([
                                Repeater::make('Jika Ada Pembayaran')
                                    ->relationship('dataPembayaran')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('keterangan')
                                                ->label('Keterangan')
                                                ->prefix('Pembayaran')
                                                ->required()
                                                ->placeholder('1, 2, 3 dst'),
                                            Select::make('payment_method_id')
                                                ->relationship('paymentMethod', 'name')
                                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->is_cash ? 'Kas/Tunai' : ($record->bank_name ? "{$record->bank_name} - {$record->no_rekening}" : $record->name))
                                                ->required()
                                                ->label('Metode Pembayaran'),
                                            TextInput::make('nominal')
                                                ->prefix('Rp. ')
                                                ->label('Nominal')
                                                ->required()
                                                ->mask(RawJs::make('$money($input)'))
                                                ->stripCharacters(',')
                                                // ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                                ->debounce(800)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    if ($state !== null) {
                                                        $sanitized = is_numeric($state) ? (int) $state : (int) preg_replace('/[^\d]/', '', (string) $state);
                                                        $set('nominal', $sanitized);
                                                        OrderResource::updateDependentFinancialFields($get, $set);
                                                    }
                                                }),
                                            Select::make('kategori_transaksi')
                                                ->options([
                                                    'uang_masuk' => 'Uang Masuk',
                                                    'uang_keluar' => 'Uang Keluar',
                                                ])
                                                ->default('uang_masuk')
                                                ->label('Tipe Transaksi')
                                                ->required(),
                                            DatePicker::make('tgl_bayar')
                                                ->date()
                                                ->required()
                                                ->label('Tgl. Bayar')
                                                ->live(onBlur: true),
                                            FileUpload::make('image')
                                                ->label('Payment Proof')
                                                ->image()
                                                ->maxSize(1280)
                                                ->disk('public')
                                                ->directory('payment-proofs/'.date('Y/m'))
                                                ->visibility('public')
                                                ->downloadable()
                                                ->openable()
                                                ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                                ->helperText('Max 1MB. JPG or PNG only.'),
                                        ]),
                                    ])
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        OrderResource::updateDependentFinancialFields($get, $set);
                                    })
                                    ->addActionLabel('Tambah Pembayaran')
                                    ->label('Pembayaran')
                                    ->collapsed()
                                    ->itemLabel(
                                        function (array $state): ?string {
                                            $keterangan = $state['keterangan'] ?? 'Pembayaran';
                                            $tglRaw = $state['tgl_bayar'] ?? null;
                                            $tanggal = $tglRaw ? \Illuminate\Support\Carbon::parse($tglRaw)->format('d M Y') : 'Tanggal?';
                                            $nominalRaw = $state['nominal'] ?? 0;
                                            $nominalVal = is_numeric($nominalRaw)
                                                ? (int) $nominalRaw
                                                : (int) preg_replace('/[^\d]/', '', (string) $nominalRaw);
                                            $nominalFmt = 'Rp. '.number_format($nominalVal, 0, '.', ',');

                                            $methodLabel = 'Metode?';
                                            try {
                                                if (isset($state['payment_method_id']) && $state['payment_method_id']) {
                                                    $pm = \App\Models\PaymentMethod::find($state['payment_method_id']);
                                                    if ($pm) {
                                                        $methodLabel = $pm->is_cash
                                                            ? 'Kas/Tunai'
                                                            : ($pm->bank_name ? "{$pm->bank_name} - {$pm->no_rekening}" : $pm->name);
                                                    }
                                                }
                                            } catch (\Exception $e) {
                                            }

                                            return "{$keterangan} | {$tanggal} | {$methodLabel} | {$nominalFmt}";
                                        }
                                    ),
                            ])
                            ->columnSpanFull(),
                        TextInput::make('total_price')
                            ->prefix('Rp. ')
                            ->label('Total Paket Awal')
                            ->readOnly()
                            ->default(0)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(','),
                        Hidden::make('is_cash')
                            ->dehydrated(false),
                        TextInput::make('promo')
                            ->default(0)
                            ->prefix('Rp. ')
                            ->readOnly()
                            ->label('Promo')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state))
                            ->reactive()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $tpRaw = $get('total_price');
                                $pgRaw = $get('pengurangan');
                                $pmRaw = $get('promo');
                                $pnRaw = $get('penambahan');
                                $totalPrice = is_numeric($tpRaw) ? (int) $tpRaw : (int) preg_replace('/[^\d]/', '', (string) $tpRaw);
                                $pengurangan = is_numeric($pgRaw) ? (int) $pgRaw : (int) preg_replace('/[^\d]/', '', (string) $pgRaw);
                                $promo = is_numeric($pmRaw) ? (int) $pmRaw : (int) preg_replace('/[^\d]/', '', (string) $pmRaw);
                                $penambahan = is_numeric($pnRaw) ? (int) $pnRaw : (int) preg_replace('/[^\d]/', '', (string) $pnRaw);
                                $grandTotal = Order::computeGrandTotalFromValues(
                                    $totalPrice,
                                    $penambahan,
                                    $promo,
                                    $pengurangan
                                );
                                $set('grand_total', $grandTotal);
                                OrderResource::updateDependentFinancialFields($get, $set);
                            }),
                        TextInput::make('penambahan')
                            ->default(0)
                            ->prefix('Rp. ')
                            ->readOnly()
                            ->label('Penambahan Harga')
                            ->helperText('Auto-calculated from selected products penambahan publish price')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state))
                            ->reactive()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $tpRaw = $get('total_price');
                                $pgRaw = $get('pengurangan');
                                $pmRaw = $get('promo');
                                $pnRaw = $get('penambahan');
                                $totalPrice = is_numeric($tpRaw) ? (int) $tpRaw : (int) preg_replace('/[^\d]/', '', (string) $tpRaw);
                                $pengurangan = is_numeric($pgRaw) ? (int) $pgRaw : (int) preg_replace('/[^\d]/', '', (string) $pgRaw);
                                $promo = is_numeric($pmRaw) ? (int) $pmRaw : (int) preg_replace('/[^\d]/', '', (string) $pmRaw);
                                $penambahan = is_numeric($pnRaw) ? (int) $pnRaw : (int) preg_replace('/[^\d]/', '', (string) $pnRaw);
                                $grandTotal = Order::computeGrandTotalFromValues(
                                    $totalPrice,
                                    $penambahan,
                                    $promo,
                                    $pengurangan
                                );
                                $set('grand_total', $grandTotal);
                                OrderResource::updateDependentFinancialFields($get, $set);
                            }),
                        TextInput::make('pengurangan')
                            ->default(0)
                            ->prefix('Rp. ')
                            ->label('Total Pengurangan dari Produk (Otomatis)')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state))
                            ->readOnly()
                            ->helperText('Nilai ini dihitung otomatis dari total pengurangan semua produk dalam order.'),
                    ]),
                Step::make('Informasi Keuangan')
                    ->icon('heroicon-o-banknotes')
                    ->description('Catat detail keuangan')
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('bayar')
                                    ->reactive()
                                    ->label('Uang dibayar')
                                    ->readOnly()
                                    ->default(0)
                                    ->helperText('Pembayaran klien ke rek makna')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state))
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        if ($record) {
                                            $component->state($record->bayar);
                                        }
                                    }),
                                TextInput::make('grand_total')
                                    ->reactive()
                                    ->label('Grand Total')
                                    ->readOnly()
                                    ->default(0)
                                    ->helperText('Grand Total = Total Paket + Penambahan - Promo - Pengurangan')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state))
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        if ($record) {
                                            $component->state($record->grand_total);
                                        }
                                    }),
                                TextInput::make('tot_pengeluaran')
                                    ->reactive()
                                    ->label('Pengeluaran')
                                    ->readOnly()
                                    ->default(0)
                                    ->helperText('Total Pembayaran Ke Vendor')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state))
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        if ($record) {
                                            $component->state($record->tot_pengeluaran);
                                        }
                                    }),
                                TextInput::make('sisa')
                                    ->label('Sisa Pembayaran')
                                    ->readOnly()
                                    ->default(0)
                                    ->helperText('Sisa uang yang harus di bayar ke makna')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state))
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        if ($record) {
                                            $component->state($record->sisa);
                                        }
                                    }),
                                TextInput::make('laba_kotor')
                                    ->label('Laba Kotor')
                                    ->readOnly()
                                    ->default(0)
                                    ->helperText('Grand total - Pembayaran ke vendor')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state))
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        if ($record) {
                                            $component->state($record->laba_kotor);
                                        }
                                    }),
                                TextInput::make('uang_diterima')
                                    ->label('Uang Diterima')
                                    ->readOnly()
                                    ->default(0)
                                    ->helperText('Sisa uang yang diterima dari klien')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state))
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        if ($record) {
                                            $component->state($record->uang_diterima);
                                        }
                                    }),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                        DatePicker::make('closing_date')
                            ->date()
                            ->label('Closing Date (Otomatis dari Pembayaran Pertama)')
                            ->readOnly()
                            ->default(function (Get $get, ?Order $record): string {
                                if ($record && $record->exists) {
                                    $firstPayment = $record->dataPembayaran()->orderBy('tgl_bayar', 'asc')->first();
                                    if ($firstPayment && $firstPayment->tgl_bayar) {
                                        return Carbon::parse($firstPayment->tgl_bayar)->format('Y-m-d');
                                    }
                                }
                                $paymentItems = $get('Jika Ada Pembayaran') ?? [];
                                if (! empty($paymentItems)) {
                                    usort($paymentItems, function ($a, $b) {
                                        return strtotime($a['tgl_bayar'] ?? 'now') <=> strtotime($b['tgl_bayar'] ?? 'now');
                                    });
                                    if (isset($paymentItems[0]['tgl_bayar']) && ! empty($paymentItems[0]['tgl_bayar'])) {
                                        return Carbon::parse($paymentItems[0]['tgl_bayar'])->format('Y-m-d');
                                    }
                                }

                                return now()->format('Y-m-d');
                            })
                            ->columnSpanFull(),
                        Toggle::make('is_paid')
                            ->label('Lunas / Belum')
                            ->default(false)
                            ->disabled()
                            ->reactive()
                            ->live()
                            ->dehydrated()
                            ->onIcon('heroicon-m-bolt')
                            ->offIcon('heroicon-m-user')
                            ->helperText('Otomatis lunas jika sisa pembayaran > 0'),
                    ]),
                Step::make('Pengeluaran')
                    ->icon('heroicon-o-book-open')
                    ->description('Catat detail pengeluaran')
                    ->schema([
                        Section::make('Pengeluaran')
                            ->description('Catat pengeluaran ke vendor. Setiap vendor hanya boleh dipilih satu kali per order.')
                            ->schema([
                                Placeholder::make('expenses_summary')
                                    ->label('Ringkasan')
                                    ->content(function (?Order $record): string {
                                        if (! $record) {
                                            return '-';
                                        }

                                        $count = $record->expenses()->count();
                                        $sum = (int) $record->expenses()->sum('amount');

                                        return "Total pengeluaran: {$count} item | Total nominal: Rp ".number_format($sum, 0, '.', ',');
                                    }),
                                Placeholder::make('expenses_manage')
                                    ->label('Kelola Pengeluaran')
                                    ->content('Gunakan tab Pengeluaran di bawah form untuk tambah/edit pengeluaran.'),
                            ])->columnSpanFull(),
                    ]),
                Step::make('Riwayat Modifikasi')
                    ->icon('heroicon-o-clock')
                    ->description('Catat detail modifikasi')
                    ->schema([
                        TextInput::make('created_at_display')
                            ->label('Dibuat')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, ?Order $record): void {
                                $component->state($record?->created_at?->diffForHumans());
                            }),
                        TextInput::make('updated_at_display')
                            ->label('Terakhir Diubah')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, ?Order $record): void {
                                $component->state($record?->updated_at?->diffForHumans());
                            }),
                        TextInput::make('last_edited_by_display')
                            ->label('Terakhir Diedit Oleh')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, ?Order $record): void {
                                if ($record?->lastEditedBy) {
                                    $component->state($record->lastEditedBy->name.' pada '.$record->updated_at?->format('d M Y H:i'));
                                } else {
                                    $component->state('Belum dilacak');
                                }
                            }),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Order $record) => $record === null),
            ])
                ->columnSpan('full')
                ->columns(3)
                ->skippable(),
        ]);
    }
}
