<?php

namespace App\Filament\Resources\NotaDinasDetails\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PengeluaranJenis;
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
use Illuminate\Support\Str;

class NotaDinasDetailForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('ND & Vendor')
                    ->icon('heroicon-o-rectangle-stack')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Select::make('nota_dinas_id')
                                    ->label('Nota Dinas')
                                    ->relationship('notaDinas', 'no_nd')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('vendor_id')
                                    ->label('Vendor')
                                    ->relationship('vendor', 'name', fn ($query) => $query->where('status', 'vendor'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
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
                                    ]),
                            ]),
                    ]),

                Section::make('Rekening')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('account_holder')
                                    ->label('Nama')
                                    ->required()
                                    ->readOnly()
                                    ->dehydrated()
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
                                ->live(),

                            Select::make('payment_stage')
                                ->label('Tahap Pembayaran')
                                ->options([
                                    'DP' => 'DP (Down Payment)',
                                    'Payment 1' => 'Payment 1',
                                    'Payment 2' => 'Payment 2',
                                    'Payment 3' => 'Payment 3',
                                    'Final Payment' => 'Final Payment',
                                    'Additional' => 'Additional',
                                ])
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
                                ->visible(fn (Get $get): bool => $get('jenis_pengeluaran') === PengeluaranJenis::WEDDING->value)
                                ->placeholder('Pilih order (Processing / Done)'),

                            TextInput::make('event')
                                ->label('Event')
                                ->maxLength(255)
                                ->columnSpan('full')
                                ->placeholder('Nama event/acara')
                                ->visible(fn (Get $get): bool => $get('jenis_pengeluaran') !== PengeluaranJenis::WEDDING->value),
                        ]),
                    ]),

                Section::make('Invoice')
                    ->icon('heroicon-o-document-text')
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
            ]);
    }
}
