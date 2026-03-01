<?php

namespace App\Filament\Resources\ChartOfAccounts\Schemas;

use App\Models\ChartOfAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ChartOfAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akun')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('account_code')
                                    ->label('Kode Akun')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(20)
                                    ->placeholder('contoh: 110000000')
                                    ->helperText('Kode akun unik'),

                                Select::make('account_type')
                                    ->label('Jenis Akun')
                                    ->options(ChartOfAccount::ACCOUNT_TYPES)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $normalBalance = ChartOfAccount::NORMAL_BALANCE[$state] ?? 'debit';
                                            $set('normal_balance', $normalBalance);
                                        }
                                    }),
                            ]),

                        TextInput::make('account_name')
                            ->label('Nama Akun')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                Select::make('parent_id')
                                    ->label('Akun Induk')
                                    ->relationship(
                                        'parent',
                                        'account_name',
                                        fn (Builder $query) => $query->where('level', '<', 3)
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (ChartOfAccount $record): string => "{$record->account_code} - {$record->account_name}")
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),

                                TextInput::make('level')
                                    ->label('Level')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->maxValue(5),

                                Select::make('normal_balance')
                                    ->label('Saldo Normal')
                                    ->options([
                                        'debit' => 'Debit',
                                        'credit' => 'Kredit',
                                    ])
                                    ->required(),
                            ]),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
