<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Prospect;
use App\Support\CompanySubscription;
use App\Support\OrderFinancialSnapshot;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderForm
{
    public static function orderNumberPrefix(): string
    {
        $raw = (string) (CompanySubscription::company()?->inisial_wo ?: 'MW');
        $prefix = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $raw));

        return $prefix !== '' ? $prefix : 'MW';
    }

    public static function defaultOrderNumber(): string
    {
        $prefix = static::orderNumberPrefix();

        do {
            $number = $prefix.'-'.random_int(100000, 999999);
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }

    public static function constrainTeamRoleQuery(Builder $query, string $preferredRole): Builder
    {
        try {
            $preferred = (clone $query)->role($preferredRole);
            if ($preferred->exists()) {
                return $query->role($preferredRole);
            }
        } catch (\Throwable) {
            // Role belum ada di instalasi ini — tampilkan semua user.
        }

        return $query;
    }

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
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->role('Account Manager'),
                            )
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
                        static::privateOrderPdfUpload(
                            field: 'doc_kontrak',
                            label: 'Upload Kontrak',
                            helper: 'pastikan kontrak sudah semua ditanda tangani',
                            directory: 'doc_kontrak',
                        ),
                        static::privateOrderPdfUpload(
                            field: 'agreement_product',
                            label: 'File Persetujuan Produk',
                            helper: 'pastikan file persetujuan produk sudah semua ditanda tangani (one up level)',
                            directory: 'agreement_product',
                        ),
                        DateTimePicker::make('contract_signed_at')
                            ->label('Kontrak Ditandatangani')
                            ->native(false)
                            ->seconds(false)
                            ->helperText('Setelah diisi, angka item/harga terkunci. Koreksi lewat “Ambil ulang dari Produk” di Detail Pembayaran (modal alasan, langsung tersimpan).')
                            ->disabled(function (?Order $record): bool {
                                $user = Auth::user();
                                if (! $user instanceof \App\Models\User || ! $user->canMarkOrderContractSigned()) {
                                    return true;
                                }
                                if ($record?->isContractSigned() && ! $user->hasRole('super_admin')) {
                                    return true;
                                }

                                return false;
                            })
                            ->dehydrated(true)
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                            ]),
                        ToggleButtons::make('status')
                            ->inline()
                            ->options(OrderStatus::class)
                            ->label('Status Pesanan')
                            ->columnSpan(2)
                            ->required()
                            ->helperText('Setelah TTD, angka keuangan dikunci. Amandemen lewat “Ambil ulang dari Produk”. Status Done: edit penuh hanya Super Admin.'),
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
                            ->description(function (?Order $record): ?string {
                                if (! $record?->isContractSigned()) {
                                    return null;
                                }

                                return 'Kontrak sudah TTD. Koreksi sekali jalan: ubah master Produk → klik Ambil ulang dari Produk → isi alasan di modal → terapkan. Riwayat di langkah Riwayat Modifikasi.';
                            })
                            ->schema([
                                SchemaActions::make([
                                    Action::make('syncAllItemsFromProducts')
                                        ->label('Ambil ulang dari Produk')
                                        ->icon('heroicon-o-arrow-path')
                                        ->color('warning')
                                        ->button()
                                        ->extraAttributes([
                                            'class' => 'w-full sm:w-auto',
                                        ])
                                        ->visible(function (?Order $record): bool {
                                            $user = Auth::user();

                                            if (! $record || ! $user instanceof \App\Models\User) {
                                                return false;
                                            }

                                            if (! $record->isContractSigned()) {
                                                return true;
                                            }

                                            return $user->canApplyOrderFinancialAmendment();
                                        })
                                        ->modalHeading('Ambil ulang dari Produk')
                                        ->modalDescription('Harga, penambahan, dan pengurangan item akan diganti dari Produk terkini.')
                                        ->modalSubmitActionLabel('Terapkan & simpan')
                                        ->form(function (?Order $record): array {
                                            if (! $record?->isContractSigned()) {
                                                return [];
                                            }

                                            return [
                                                Textarea::make('reason')
                                                    ->label('Alasan Amandemen Keuangan')
                                                    ->required()
                                                    ->rows(3)
                                                    ->helperText('Langsung disimpan ke order dan masuk riwayat amandemen.')
                                                    ->placeholder('Contoh: penambahan lighting sesuai request klien tanggal …'),
                                            ];
                                        })
                                        ->action(function (array $data, Get $get, Set $set, $livewire): void {
                                            OrderResource::runProductSyncAction(
                                                get: $get,
                                                set: $set,
                                                livewire: $livewire,
                                                reason: trim((string) ($data['reason'] ?? '')),
                                            );
                                        }),
                                ]),
                                OrderResource::getItemsRepeater(),
                            ])
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
                                            $tanggal = $tglRaw ? Carbon::parse($tglRaw)->format('d M Y') : 'Tanggal?';
                                            $nominalRaw = $state['nominal'] ?? 0;
                                            $nominalVal = is_numeric($nominalRaw)
                                                ? (int) $nominalRaw
                                                : (int) preg_replace('/[^\d]/', '', (string) $nominalRaw);
                                            $nominalFmt = 'Rp. '.number_format($nominalVal, 0, '.', ',');

                                            $methodLabel = 'Metode?';
                                            try {
                                                if (isset($state['payment_method_id']) && $state['payment_method_id']) {
                                                    $pm = PaymentMethod::find($state['payment_method_id']);
                                                    if ($pm) {
                                                        $methodLabel = $pm->is_cash
                                                            ? 'Kas/Tunai'
                                                            : ($pm->bank_name ? "{$pm->bank_name} - {$pm->no_rekening}" : $pm->name);
                                                    }
                                                }
                                            } catch (Exception $e) {
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
                            ->helperText('Dari snapshot item order (bukan harga produk terkini)')
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
                            ->helperText('Dari snapshot item order (bukan harga produk terkini).'),
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
                                TextEntry::make('expenses_summary')
                                    ->label('Ringkasan')
                                    ->state(function (?Order $record): string {
                                        if (! $record) {
                                            return '-';
                                        }

                                        $count = $record->expenses()->count();
                                        $sum = (int) $record->expenses()->sum('amount');

                                        return "Total pengeluaran: {$count} item | Total nominal: Rp ".number_format($sum, 0, '.', ',');
                                    }),
                                TextEntry::make('expenses_manage')
                                    ->label('Kelola Pengeluaran')
                                    ->state('Gunakan tab Pengeluaran di bawah form untuk tambah/edit pengeluaran.'),
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
                        Section::make('Riwayat Amandemen Keuangan')
                            ->description('Alasan dan perubahan angka setelah kontrak ditandatangani.')
                            ->schema([
                                TextEntry::make('amendments_empty')
                                    ->hiddenLabel()
                                    ->state('Belum ada amandemen untuk order ini.')
                                    ->visible(fn (?Order $record): bool => ! $record || $record->amendments()->doesntExist()),
                                Repeater::make('amendments_history')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextInput::make('when')
                                            ->label('Waktu')
                                            ->disabled()
                                            ->dehydrated(true),
                                        TextInput::make('by')
                                            ->label('Oleh')
                                            ->disabled()
                                            ->dehydrated(true),
                                        Textarea::make('reason')
                                            ->label('Alasan')
                                            ->disabled()
                                            ->dehydrated(true)
                                            ->rows(2)
                                            ->columnSpanFull()
                                            ->extraInputAttributes(['class' => 'break-words']),
                                        Textarea::make('changes')
                                            ->label('Perubahan')
                                            ->disabled()
                                            ->dehydrated(true)
                                            ->rows(8)
                                            ->columnSpanFull()
                                            ->extraInputAttributes([
                                                'class' => 'break-words font-mono text-xs leading-relaxed whitespace-pre-wrap',
                                                'style' => 'min-height: 8rem;',
                                            ]),
                                    ])
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(function (array $state): string {
                                        $when = trim((string) ($state['when'] ?? ''));
                                        $by = trim((string) ($state['by'] ?? ''));
                                        $reason = trim((string) ($state['reason'] ?? ''));

                                        $parts = array_values(array_filter([
                                            $when !== '' ? $when : null,
                                            $by !== '' ? $by : null,
                                            $reason !== '' ? Str::limit($reason, 42) : null,
                                        ]));

                                        return $parts !== []
                                            ? implode(' · ', $parts)
                                            : 'Amandemen tanpa detail';
                                    })
                                    ->afterStateHydrated(function ($component, $state, ?Order $record): void {
                                        if (! $record) {
                                            $component->state([]);

                                            return;
                                        }

                                        $record->loadMissing(['amendments.user']);

                                        $component->state(
                                            $record->amendments
                                                ->take(20)
                                                ->map(fn ($amendment) => [
                                                    'when' => $amendment->created_at?->format('d M Y H:i') ?? '-',
                                                    'by' => $amendment->user?->name ?? 'Tidak diketahui',
                                                    'reason' => (string) ($amendment->reason ?? '-'),
                                                    'changes' => static::formatAmendmentDiff(
                                                        is_array($amendment->diff) ? $amendment->diff : []
                                                    ),
                                                ])
                                                ->values()
                                                ->all()
                                        );
                                    })
                                    ->visible(fn (?Order $record): bool => (bool) $record?->amendments()->exists())
                                    ->columns([
                                        'default' => 1,
                                        'md' => 2,
                                    ]),
                            ])
                            ->visible(function (?Order $record): bool {
                                $user = Auth::user();

                                return $record !== null
                                    && $user instanceof \App\Models\User
                                    && $user->canApplyOrderFinancialAmendment();
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Order $record) => $record === null),
            ])
                ->columnSpan('full')
                ->columns(3)
                ->skippable(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $diff
     */
    public static function formatAmendmentDiff(array $diff): string
    {
        if ($diff === []) {
            return '-';
        }

        $lines = [];

        $penambahanDetail = self::formatNamedAmountLines(
            'Penambahan',
            is_array($diff['penambahan_lines'] ?? null) ? $diff['penambahan_lines'] : null,
        );
        $penguranganDetail = self::formatNamedAmountLines(
            'Pengurangan',
            is_array($diff['pengurangan_lines'] ?? null) ? $diff['pengurangan_lines'] : null,
        );

        if ($penambahanDetail !== []) {
            array_push($lines, ...$penambahanDetail);
        } elseif (isset($diff['penambahan']) && is_array($diff['penambahan'])) {
            $before = (int) ($diff['penambahan']['before'] ?? 0);
            $after = (int) ($diff['penambahan']['after'] ?? 0);
            if ($before !== $after) {
                $lines[] = 'Penambahan: Rp '.number_format($after, 0, ',', '.');
            }
        }

        if ($penguranganDetail !== []) {
            if ($lines !== []) {
                $lines[] = '';
            }
            array_push($lines, ...$penguranganDetail);
        } elseif (isset($diff['pengurangan']) && is_array($diff['pengurangan'])) {
            $before = (int) ($diff['pengurangan']['before'] ?? 0);
            $after = (int) ($diff['pengurangan']['after'] ?? 0);
            if ($before !== $after) {
                if ($lines !== []) {
                    $lines[] = '';
                }
                $lines[] = 'Pengurangan: Rp '.number_format($after, 0, ',', '.');
            }
        }

        foreach ([
            'total_price' => 'Total paket',
            'promo' => 'Promo',
            'grand_total' => 'Grand total',
        ] as $key => $label) {
            if (! isset($diff[$key]) || ! is_array($diff[$key])) {
                continue;
            }

            $before = (int) ($diff[$key]['before'] ?? 0);
            $after = (int) ($diff[$key]['after'] ?? 0);
            if ($before === $after) {
                continue;
            }

            if ($lines !== []) {
                $lines[] = '';
            }
            $lines[] = $label.': Rp '.number_format($after, 0, ',', '.');
        }

        // Fallback lama: hanya total item berubah tanpa detail baris.
        if ($lines === [] && isset($diff['items']) && is_array($diff['items'])) {
            $lines = self::formatItemsAmendmentLines($diff['items']);
        }

        $text = trim(implode("\n", $lines));

        return $text !== '' ? $text : '-';
    }

    /**
     * @param  array{before?: mixed, after?: mixed}|null  $change
     * @return list<string>
     */
    protected static function formatNamedAmountLines(string $heading, ?array $change): array
    {
        if ($change === null) {
            return [];
        }

        $before = collect(OrderFinancialSnapshot::mergeLines($change['before'] ?? []))
            ->keyBy(fn (array $line) => self::lineKey($line));
        $after = collect(OrderFinancialSnapshot::mergeLines($change['after'] ?? []))
            ->keyBy(fn (array $line) => self::lineKey($line));

        if ($before->isEmpty() && $after->isEmpty()) {
            return [];
        }

        $detail = [];
        $keys = $before->keys()->merge($after->keys())->unique()->values();

        // Belum ada snapshot baris lama → tampilkan komposisi baru apa adanya.
        $markDelta = $before->isNotEmpty();

        foreach ($keys as $key) {
            $b = $before->get($key);
            $a = $after->get($key);

            if ($b && ! $a) {
                $detail[] = ($markDelta ? '− ' : '').$b['name'].': Rp '.number_format((int) $b['amount'], 0, ',', '.');

                continue;
            }

            if ($a && ! $b) {
                $detail[] = ($markDelta ? '+ ' : '').$a['name'].': Rp '.number_format((int) $a['amount'], 0, ',', '.');

                continue;
            }

            if ($a && $b && (int) $a['amount'] !== (int) $b['amount']) {
                $detail[] = $a['name'].': Rp '.number_format((int) $a['amount'], 0, ',', '.');
            }
        }

        if ($detail === []) {
            return [];
        }

        return array_merge([$heading.':'], $detail);
    }

    /**
     * @param  array{id?: int|null, name?: string, amount?: int}  $line
     */
    protected static function lineKey(array $line): string
    {
        if (isset($line['id']) && $line['id'] !== null) {
            return 'id:'.(int) $line['id'];
        }

        return 'name:'.mb_strtolower((string) ($line['name'] ?? ''));
    }

    /**
     * @param  array{before?: mixed, after?: mixed}  $change
     * @return list<string>
     */
    protected static function formatItemsAmendmentLines(array $change): array
    {
        $beforeItems = collect($change['before'] ?? [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['product_id']))
            ->keyBy(fn ($item) => (string) $item['product_id']);

        $afterItems = collect($change['after'] ?? [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['product_id']))
            ->keyBy(fn ($item) => (string) $item['product_id']);

        $keys = $beforeItems->keys()->merge($afterItems->keys())->unique()->values();
        $fieldLabels = [
            'unit_price' => 'Harga',
            'unit_penambahan' => 'Penambahan',
            'unit_pengurangan' => 'Pengurangan',
            'quantity' => 'Qty',
        ];
        $lines = [];
        $seen = [];

        foreach ($keys as $key) {
            $before = $beforeItems->get($key, []);
            $after = $afterItems->get($key, []);

            foreach ($fieldLabels as $field => $fieldLabel) {
                $b = (int) ($before[$field] ?? 0);
                $a = (int) ($after[$field] ?? 0);
                if ($b === $a || isset($seen[$fieldLabel])) {
                    continue;
                }
                $seen[$fieldLabel] = true;

                $lines[] = $field === 'quantity'
                    ? "{$fieldLabel}: {$a}"
                    : $fieldLabel.': Rp '.number_format($a, 0, ',', '.');
            }
        }

        return $lines;
    }

    protected static function privateOrderPdfUpload(string $field, string $label, string $helper, string $directory): FileUpload
    {
        $secureUrl = function (?Order $record, bool $download = false) use ($field): ?string {
            if (! $record instanceof Order) {
                return null;
            }

            $params = [
                'order' => $record,
                'field' => $field,
            ];

            if ($download) {
                $params['download'] = 1;
            }

            return route('secure-files.orders', $params);
        };

        return FileUpload::make($field)
            ->label($label)
            ->helperText($helper)
            ->required()
            ->reorderable()
            ->disk('private')
            ->visibility('private')
            ->directory($directory)
            ->acceptedFileTypes(['application/pdf'])
            ->openable()
            ->downloadable()
            ->fetchFileInformation(false)
            ->getUploadedFileUsing(function (FileUpload $component, string $file) use ($secureUrl): ?array {
                $resolved = static::resolveSensitiveStoredFile($file);
                if ($resolved === null) {
                    return null;
                }

                [$diskName, $path] = $resolved;
                $disk = Storage::disk($diskName);
                $record = $component->getRecord();

                return [
                    'name' => basename($path),
                    'size' => $disk->size($path),
                    'type' => $disk->mimeType($path) ?: 'application/pdf',
                    'url' => $secureUrl($record instanceof Order ? $record : null),
                ];
            })
            ->getOpenableFileUrlUsing(function (?string $file, $record) use ($secureUrl): ?string {
                return filled($file) ? $secureUrl($record instanceof Order ? $record : null) : null;
            })
            ->getDownloadableFileUrlUsing(function (?string $file, $record) use ($secureUrl): ?string {
                return filled($file) ? $secureUrl($record instanceof Order ? $record : null, true) : null;
            });
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    protected static function resolveSensitiveStoredFile(string $file): ?array
    {
        $path = ltrim(str_replace('\\', '/', trim($file)), '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        foreach (['private', 'public'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    return [$disk, $path];
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
