<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->relationship('order', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->name ?? 'No Name')
                    ->required()
                    ->preload()
                    ->disabled()
                    ->label('Project')
                    ->searchable(),
                Select::make('vendor_id')
                    ->relationship('vendor', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->name ?? 'No Vendor')
                    ->disabled()
                    ->required()
                    ->label('Vendor')
                    ->searchable(),
                TextInput::make('note')
                    ->required()
                    ->disabled()
                    ->label('Keterangan pembayaran')
                    ->maxLength(255),
                TextInput::make('no_nd')
                    ->required()
                    ->disabled()
                    ->prefix('ND-0')
                    ->label('Nomor Nota Dinas')
                    ->numeric(),
                Select::make('kategori_transaksi')
                    ->options([
                        'uang_masuk' => 'Uang Masuk',
                        'uang_keluar' => 'Uang Keluar',
                    ])
                    ->default('uang_keluar')
                    ->disabled()
                    ->label('Tipe Transaksi')
                    ->required(),
                DatePicker::make('date_expense')
                    ->date()
                    ->disabled()
                    ->label('Tanggal pembayaran'),
                TextInput::make('amount')
                    ->required()
                    ->label('Jumlah pembayaran')
                    ->disabled()
                    ->prefix('Rp. ')
                    ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(','),
                Select::make('payment_method_id')
                    ->relationship('paymentMethod', 'no_rekening')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->no_rekening ?? 'No Account')
                    ->disabled()
                    ->label('Sumber pembayaran')
                    ->required(),
                FileUpload::make('image')
                    ->image()
                    ->disabled()
                    ->directory('expense_wedding')
                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                    ->label('Invoice'),
            ]);
    }
}
