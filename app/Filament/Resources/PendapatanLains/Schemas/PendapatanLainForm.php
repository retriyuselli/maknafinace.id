<?php

namespace App\Filament\Resources\PendapatanLains\Schemas;

use App\Models\Vendor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class PendapatanLainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Pendapatan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Pendapatan')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                Select::make('vendor_id')
                                    ->relationship('vendor', 'name')
                                    ->options(function () {
                                        return Vendor::where('status', 'vendor')
                                            ->pluck('name', 'id');
                                    })
                                    ->label('Vendor')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->columnSpan(1)
                                    ->helperText('Pilih vendor jika pendapatan berasal dari vendor tertentu'),

                                Select::make('payment_method_id')
                                    ->relationship('paymentMethod', 'name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->is_cash ? 'Kas/Tunai' : ($record->bank_name ? "{$record->bank_name} - {$record->no_rekening}" : $record->name))
                                    ->label('Metode Pembayaran')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('nominal')
                                    ->required()
                                    ->prefix('IDR')
                                    ->placeholder('0')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->inputMode('numeric')
                                    ->label('Nominal')
                                    ->helperText('Masukkan nominal pendapatan'),

                                DatePicker::make('tgl_bayar')
                                    ->label('Tanggal Pendapatan')
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->required()
                                    ->helperText('Pilih tanggal ketika pendapatan diterima'),

                                Select::make('kategori_transaksi')
                                    ->options([
                                        'uang_masuk' => 'Uang Masuk',
                                    ])
                                    ->default('uang_masuk')
                                    ->required()
                                    ->disabled()
                                    ->helperText('Otomatis diatur sebagai Uang Masuk.'),

                                FileUpload::make('image')
                                    ->label('Bukti Pendapatan')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('pendapatan-lain')
                                    ->downloadable()
                                    ->openable()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(1024)
                                    ->columnSpanFull()
                                    ->helperText('Upload bukti pendapatan (JPEG, PNG, WEBP, max 5MB)'),

                                Textarea::make('keterangan')
                                    ->label('Keterangan')
                                    ->required()
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->columnSpanFull()
                                    ->helperText('Jelaskan detail pendapatan ini (max 1000 karakter)'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
