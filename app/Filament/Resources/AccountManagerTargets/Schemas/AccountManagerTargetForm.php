<?php

namespace App\Filament\Resources\AccountManagerTargets\Schemas;

use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;

class AccountManagerTargetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name', function (Builder $query) {
                        return $query->whereHas('roles', function ($q) {
                            $q->where('name', 'Account Manager');
                        });
                    })
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('year')
                    ->options(function () {
                        $currentYear = Carbon::now()->year;
                        $years = [];
                        for ($i = -2; $i <= 3; $i++) {
                            $year = $currentYear + $i;
                            $years[$year] = $year;
                        }

                        return $years;
                    })
                    ->default(Carbon::now()->year)
                    ->required(),
                Select::make('month')
                    ->options(function () {
                        $months = [];
                        for ($m = 1; $m <= 12; $m++) {
                            $months[$m] = Carbon::createFromDate(null, $m, 1)->format('F');
                        }

                        return $months;
                    })
                    ->required(),
                TextInput::make('target_amount')
                    ->required()
                    ->label('Target')
                    ->prefix('Rp. ')
                    ->default(0)
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                    ->placeholder('1,000,000,000'),
                TextInput::make('achieved_amount')
                    ->prefix('Rp. ')
                    ->default(0)
                    ->label('Pencapaian')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                    ->readOnly()
                    ->helperText('Otomatis dihitung dari orders'),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'achieved' => 'Achieved',
                        'on_track' => 'On Track',
                        'behind' => 'Behind',
                        'failed' => 'Failed',
                        'overachieved' => 'Overachieved',
                    ])
                    ->default('pending')
                    ->required(),
            ]);
    }
}
