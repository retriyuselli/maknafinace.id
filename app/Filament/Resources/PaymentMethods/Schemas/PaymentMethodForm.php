<?php

namespace App\Filament\Resources\PaymentMethods\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Rekening')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('nama pemilik rekening')
                            ->maxLength(255),
                        TextInput::make('bank_name')
                            ->prefix('Bank ')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('cabang')
                            ->placeholder('cabang bank (opsional)')
                            ->maxLength(255),
                        TextInput::make('no_rekening')
                            ->required()
                            ->numeric(),
                        Toggle::make('is_cash')
                            ->required(),
                    ])->columns(2),
                Section::make('Saldo Awal')
                    ->description('Isi jika rekening ini memiliki saldo sebelum dicatat di sistem. Saldo ini akan menjadi titik awal perhitungan.')
                    ->schema([
                        TextInput::make('opening_balance')
                            ->label('Saldo Awal (Opening Balance)')
                            ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                            ->prefix('Rp')
                            ->required()
                            ->default(0)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(','),
                        DatePicker::make('opening_balance_date')
                            ->label('Tanggal Saldo Awal')
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d M Y'),
                    ])->columns(2),
            ]);
    }
}
