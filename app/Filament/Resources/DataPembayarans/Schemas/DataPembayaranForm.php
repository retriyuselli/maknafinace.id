<?php

namespace App\Filament\Resources\DataPembayarans\Schemas;

use App\Models\Order;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class DataPembayaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->relationship('order', 'name')
                    ->searchable()
                    ->disabled()
                    ->preload()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $order = Order::find($state);
                            $set('nominal', $order?->sisa ?? 0);
                        }
                    }),

                TextInput::make('keterangan')
                    ->label('Keterangan')
                    ->disabled()
                    ->prefix('Pembayaran')
                    ->placeholder('1, 2, 3 dst'),

                TextInput::make('nominal')
                    ->label('Nominal')
                    ->disabled()
                    ->readOnly()
                    ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->prefix('Rp. ')
                    ->required()
                    ->minValue(0)
                    ->rules(['max:999999999'])
                    ->columnSpan(['md' => 1]),

                Select::make('kategori_transaksi')
                    ->options([
                        'uang_masuk' => 'Uang Masuk',
                        'uang_keluar' => 'Uang Keluar',
                    ])
                    ->default('uang_masuk')
                    ->disabled()
                    ->label('Tipe Transaksi')
                    ->required(),

                Select::make('payment_method_id')
                    ->relationship('paymentMethod', 'name')
                    ->required()
                    ->searchable()
                    ->disabled()
                    ->preload(),

                DatePicker::make('tgl_bayar')
                    ->label('Payment Date')
                    ->required()
                    ->disabled(),

                FileUpload::make('image')
                    ->label('Payment Proof')
                    ->disabled()
                    ->image()
                    ->maxSize(1280)
                    ->disk('public')
                    ->directory('payment-proofs/'.date('Y/m'))
                    ->visibility('public')
                    ->downloadable()
                    ->openable()
                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                    ->helperText('Max 1MB. JPG or PNG only.'),
            ])->columns(3);
    }
}
