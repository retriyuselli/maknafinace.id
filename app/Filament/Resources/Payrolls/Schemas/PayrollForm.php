<?php

namespace App\Filament\Resources\Payrolls\Schemas;

use App\Models\Payroll;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;

class PayrollForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Payroll')
                    ->tabs([
                        Tab::make('Karyawan & Periode')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Informasi Karyawan')
                                    ->description('Pilih karyawan dan periode payroll')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('user_id')
                                                    ->label('Karyawan')
                                                    ->relationship('user', 'name', function (Builder $query) {
                                                        return $query->with('status')
                                                            ->whereHas('roles', function (Builder $query) {
                                                                $query->where('name', 'Office');
                                                            });
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                        if (! $state) {
                                                            return;
                                                        }
                                                        $user = User::find($state);
                                                        if (! $user) {
                                                            return;
                                                        }
                                                        $baseGaji = (int) ($user->gaji_pokok_base ?? 0);
                                                        $baseTunjangan = (int) ($user->tunjangan_base ?? 0);
                                                        $set('gaji_pokok', (string) $baseGaji);
                                                        $set('tunjangan', (string) $baseTunjangan);
                                                        $monthlySalary = Payroll::computeMonthly(
                                                            $baseGaji,
                                                            $baseTunjangan,
                                                            (int) $get('bonus'),
                                                            (int) $get('pengurangan'),
                                                        );
                                                        $set('monthly_salary', (string) $monthlySalary);
                                                        $set('annual_salary', (string) Payroll::computeAnnualBase($baseGaji, $baseTunjangan));
                                                        $set('total_compensation', (string) Payroll::computeTotalCompensationBase($baseGaji, $baseTunjangan, (int) $get('pengurangan')));
                                                    })
                                                    ->getOptionLabelUsing(function ($value): ?string {
                                                        $user = User::find($value);

                                                        return $user?->name;
                                                    })
                                                    ->getOptionLabelFromRecordUsing(function (User $record): string {
                                                        $statusName = $record->status?->status_name ?? $record->department ?? 'No Status';
                                                        $email = $record->email ? " - {$record->email}" : '';

                                                        return "{$record->name} ({$statusName}){$email}";
                                                    })
                                                    ->helperText('Pilih karyawan dengan role Office yang akan dibuatkan payroll')
                                                    ->columnSpan(2),

                                                Group::make([
                                                    Select::make('period_month')
                                                        ->label('Bulan Periode')
                                                        ->options([
                                                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                                                        ])
                                                        ->default(now()->month)
                                                        ->required()
                                                        ->live()
                                                        ->helperText('Pilih bulan periode payroll'),

                                                    Select::make('period_year')
                                                        ->label('Tahun Periode')
                                                        ->options(function () {
                                                            $currentYear = now()->year;
                                                            $years = [];
                                                            for ($year = $currentYear - 1; $year <= $currentYear + 1; $year++) {
                                                                $years[$year] = $year;
                                                            }

                                                            return $years;
                                                        })
                                                        ->default(now()->year)
                                                        ->required()
                                                        ->live()
                                                        ->helperText('Pilih tahun periode payroll'),
                                                ])
                                                    ->columnSpan(1),
                                            ]),

                                        Placeholder::make('employee_info')
                                            ->label('Info Karyawan')
                                            ->content(function (Get $get): string {
                                                $userId = $get('user_id');
                                                if (! $userId) {
                                                    return 'Pilih karyawan untuk melihat informasi';
                                                }

                                                $user = User::with('status')->find($userId);
                                                if (! $user) {
                                                    return 'Karyawan tidak ditemukan';
                                                }

                                                $hireDate = $user->hire_date?->format('d/m/Y') ?? 'No Date';

                                                $monthVal = $get('period_month');
                                                $yearVal = $get('period_year');
                                                $month = $monthVal instanceof \Illuminate\Support\Carbon ? $monthVal->month : (int) (is_numeric($monthVal) ? $monthVal : preg_replace('/[^\d]/', '', (string) $monthVal));
                                                $year = $yearVal instanceof \Illuminate\Support\Carbon ? $yearVal->year : (int) (is_numeric($yearVal) ? $yearVal : preg_replace('/[^\d]/', '', (string) $yearVal));

                                                $existingPayroll = null;
                                                if ($month && $year) {
                                                    $existingPayroll = Payroll::where('user_id', $userId)
                                                        ->where('period_month', $month)
                                                        ->where('period_year', $year)
                                                        ->first();
                                                }

                                                $info = "📅 Mulai kerja: {$hireDate}";

                                                if ($existingPayroll) {
                                                    $months = [
                                                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                                                    ];
                                                    $monthName = $months[$month] ?? 'Unknown';
                                                    $info .= "\n⚠️ Payroll untuk {$monthName} {$year} sudah ada!";
                                                }

                                                return $info;
                                            })
                                            ->visible(fn (Get $get): bool => (bool) $get('user_id')),
                                    ])->columns(1),
                            ]),

                        Tab::make('Gaji')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Informasi Gaji')
                                    ->description('Pengaturan gaji bulanan dan tahunan')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('gaji_pokok')
                                                    ->label('Gaji Pokok')
                                                    ->required()
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-currency-dollar')
                                                    ->placeholder('2,000,000')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->extraAttributes(['class' => 'bg-blue-50 text-right'])
                                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace(',', '', (string) $state))
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                                        $monthlySalary = Payroll::computeMonthly(
                                                            $state,
                                                            $get('tunjangan'),
                                                            $get('bonus'),
                                                            $get('pengurangan'),
                                                        );

                                                        $set('monthly_salary', (string) $monthlySalary);

                                                        $set('annual_salary', (string) Payroll::computeAnnualBase($state, $get('tunjangan')));
                                                        $set('total_compensation', (string) Payroll::computeTotalCompensationBase($state, $get('tunjangan'), $get('pengurangan')));
                                                    })
                                                    ->helperText('Gaji pokok tanpa tunjangan'),

                                                TextInput::make('tunjangan')
                                                    ->label('Tunjangan')
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-plus')
                                                    ->placeholder('1000000')
                                                    ->default(0)
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->extraAttributes(['class' => 'bg-gray-50 text-right'])
                                                    ->live(onBlur: true)
                                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace(',', '', (string) $state))
                                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                                        $monthlySalary = Payroll::computeMonthly(
                                                            $get('gaji_pokok'),
                                                            $state,
                                                            $get('bonus'),
                                                            $get('pengurangan'),
                                                        );

                                                        $set('monthly_salary', (string) $monthlySalary);

                                                        $set('annual_salary', (string) Payroll::computeAnnualBase($get('gaji_pokok'), $state));
                                                        $set('total_compensation', (string) Payroll::computeTotalCompensationBase($get('gaji_pokok'), $state, $get('pengurangan')));
                                                    })
                                                    ->helperText('Tunjangan dan benefit lainnya'),

                                                TextInput::make('pengurangan')
                                                    ->label('Pengurangan')
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-minus')
                                                    ->placeholder('BPJS, keterlambatan dan lainnya')
                                                    ->default(0)
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->extraAttributes(['class' => 'bg-gray-50 text-right'])
                                                    ->live(onBlur: true)
                                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace(',', '', (string) $state))
                                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                                        $monthlySalary = Payroll::computeMonthly(
                                                            $get('gaji_pokok'),
                                                            $get('tunjangan'),
                                                            $get('bonus'),
                                                            $state,
                                                        );

                                                        $set('monthly_salary', (string) $monthlySalary);

                                                        $set('annual_salary', (string) Payroll::computeAnnualBase($get('gaji_pokok'), $get('tunjangan')));
                                                        $set('total_compensation', (string) Payroll::computeTotalCompensationBase($get('gaji_pokok'), $get('tunjangan'), $state));
                                                    })
                                                    ->helperText('BPJS, keterlambatan dan lainnya'),
TextInput::make('bonus')
                                                    ->label('Bonus')
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-gift')
                                                    ->placeholder('1000000')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->default(0)
                                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace(',', '', (string) $state))
                                                    ->live()
                                                    ->extraAttributes(['class' => 'bg-gray-50 text-right'])
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        $monthlySalary = Payroll::computeMonthly(
                                                            $get('gaji_pokok'),
                                                            $get('tunjangan'),
                                                            $state,
                                                            $get('pengurangan'),
                                                        );

                                                        $set('monthly_salary', (string) $monthlySalary);

                                                        $set('annual_salary', (string) Payroll::computeAnnualBase($get('gaji_pokok'), $get('tunjangan')));
                                                        $set('total_compensation', (string) Payroll::computeTotalCompensationBase($get('gaji_pokok'), $get('tunjangan'), $get('pengurangan')));
                                                    })
                                                    ->helperText('Bonus bulanan (termasuk dalam gaji bulanan)'),
                                                
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('monthly_salary')
                                                    ->label('Total Gaji Bulanan')
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-calculator')
                                                    ->readOnly()
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->formatStateUsing(fn ($state) => $state === null ? null : number_format((int) str_replace(',', '', (string) $state), 0, '.', ','))
                                                    ->dehydrated(false)
                                                    ->afterStateHydrated(function (TextInput $component, $state, $record) {
                                                        if ($record) {
                                                            $monthly = Payroll::computeMonthly(
                                                                $record->gaji_pokok ?? 0,
                                                                $record->tunjangan ?? 0,
                                                                $record->bonus ?? 0,
                                                                $record->pengurangan ?? 0,
                                                            );

                                                            $component->state((string) (int) $monthly);
                                                        }
                                                    })
                                                    ->helperText('Otomatis: (Gaji Pokok + Tunjangan + Bonus) - Pengurangan')
                                                    ->extraAttributes(['class' => 'bg-blue-50 text-right']),
                                                TextInput::make('annual_salary')
                                                    ->label('Gaji Tahunan')
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-calculator')
                                                    ->readOnly()
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->formatStateUsing(fn ($state) => $state === null ? null : number_format((int) str_replace(',', '', (string) $state), 0, '.', ','))
                                                    ->dehydrated(false)
                                                    ->afterStateHydrated(function (TextInput $component, $state, $record) {
                                                        if ($record) {
                                                            $component->state((string) (int) Payroll::computeAnnualBase($record->gaji_pokok ?? 0, $record->tunjangan ?? 0));
                                                        }
                                                    })
                                                    ->helperText('Otomatis: (Gaji Pokok + Tunjangan) × 12')
                                                    ->extraAttributes(['class' => 'bg-gray-50 text-right']),
                                                TextInput::make('total_compensation')
                                                    ->label('Total Kompensasi')
                                                    ->prefix('Rp')
                                                    ->suffixIcon('heroicon-m-calculator')
                                                    ->readOnly()
                                                    ->dehydrated(false)
                                                    ->live()
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->formatStateUsing(fn ($state) => $state === null ? null : number_format((int) str_replace(',', '', (string) $state), 0, '.', ','))
                                                    ->afterStateHydrated(function (TextInput $component, $state, $record) {
                                                        if ($record) {
                                                            $component->state((string) (int) Payroll::computeTotalCompensationBase($record->gaji_pokok ?? 0, $record->tunjangan ?? 0, $record->pengurangan ?? 0));
                                                        }
                                                    })
                                                    ->helperText('Total: Gaji Tahunan (tanpa bonus terpisah)')
                                                    ->extraAttributes(['class' => 'bg-gray-50 text-right']),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Review')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Section::make('Informasi Review')
                                    ->description('Jadwal review gaji dan performa')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                DatePicker::make('last_review_date')
                                                    ->label('Tanggal Review Terakhir')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ->helperText('Kapan terakhir kali direview'),

                                                DatePicker::make('next_review_date')
                                                    ->label('Tanggal Review Berikutnya')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ->helperText('Jadwal review berikutnya')
                                                    ->afterOrEqual('today'),
                                            ]),

                                        Textarea::make('notes')
                                            ->label('Catatan')
                                            ->placeholder('Catatan tambahan mengenai payroll ini...')
                                            ->rows(3)
                                            ->maxLength(1000)
                                            ->helperText('Catatan internal (maksimal 1000 karakter)'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

}
