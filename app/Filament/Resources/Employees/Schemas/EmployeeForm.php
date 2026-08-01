<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Str;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Informasi Karyawan')
                    ->tabs([
                        Tab::make('Informasi Dasar')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Detail Personal')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->placeholder('Nama lengkap (depan dan belakang)')
                                                    ->maxLength(255)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, Set $set) {
                                                        $set('slug', Str::slug($state));
                                                    }),

                                                TextInput::make('slug')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->maxLength(255),

                                                DatePicker::make('date_of_birth')
                                                    ->label('Tanggal Lahir')
                                                    ->required()
                                                    ->maxDate(now()->subYears(18))
                                                    ->displayFormat('d M Y'),

                                                FileUpload::make('photo')
                                                    ->label('Foto Profil')
                                                    ->image()
                                                    ->openable()
                                                    ->downloadable()
                                                    ->directory('employee-photos'),
                                            ]),
                                    ]),

                                Section::make('Informasi Kontak')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('email')
                                                    ->email()
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(255),

                                                TextInput::make('phone')
                                                    ->tel()
                                                    ->required()
                                                    ->maxLength(20)
                                                    ->prefix('+62')
                                                    ->telRegex('/^[0-9]{9,15}$/')
                                                    ->placeholder('8xxxxxxxxx'),

                                                TextInput::make('instagram')
                                                    ->prefix('@')
                                                    ->maxLength(255),

                                                Textarea::make('address')
                                                    ->required()
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Detail Kepegawaian')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Section::make('Posisi & Peran')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('position')
                                                    ->required()
                                                    ->options([
                                                        'Account Manager' => 'Account Manager',
                                                        'Event Manager' => 'Event Manager',
                                                        'Crew' => 'Crew',
                                                        'Finance' => 'Finance',
                                                        'Founder' => 'Founder',
                                                        'Co Founder' => 'Co Founder',
                                                        'Direktur' => 'Direktur',
                                                        'Wakil Direktur' => 'Wakil Direktur',
                                                        'Other' => 'Other',
                                                    ])
                                                    ->searchable(),

                                                Select::make('user_id')
                                                    ->relationship('user', 'name')
                                                    ->label('Akun Pengguna Terkait')
                                                    ->preload()
                                                    ->searchable()
                                                    ->createOptionForm([
                                                        TextInput::make('name')
                                                            ->required(),
                                                        TextInput::make('email')
                                                            ->required()
                                                            ->email(),
                                                        TextInput::make('password')
                                                            ->password()
                                                            ->required()
                                                            ->confirmed(),
                                                        TextInput::make('password_confirmation')
                                                            ->password()
                                                            ->required(),
                                                    ]),

                                                DatePicker::make('date_of_join')
                                                    ->label('Tanggal Bergabung')
                                                    ->required()
                                                    ->displayFormat('d M Y')
                                                    ->default(now()),

                                                DatePicker::make('date_of_out')
                                                    ->label('Tanggal Berhenti')
                                                    ->displayFormat('d M Y')
                                                    ->minDate(fn (Get $get) => $get('date_of_join')),
                                            ]),
                                    ]),

                                Section::make('Kompensasi & Perbankan')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('salary')
                                                    ->required()
                                                    ->prefix('Rp. ')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                                    ->placeholder('0'),

                                                TextInput::make('bank_name')
                                                    ->required()
                                                    ->maxLength(255),

                                                TextInput::make('no_rek')
                                                    ->label('Nomor Rekening')
                                                    ->required()
                                                    ->numeric()
                                                    ->minLength(10)
                                                    ->maxLength(20),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Dokumen & Catatan')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Grid::make(1)
                                    ->schema([
                                        FileUpload::make('kontrak')
                                            ->label('Kontrak Kerja')
                                            ->directory('employee-contracts')
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->openable()
                                            ->downloadable(),

                                        Textarea::make('note')
                                            ->label('Additional Notes')
                                            ->placeholder('Add any special considerations or notes about this employee')
                                            ->rows(3),

                                        TextInput::make('created_at_display')
                                            ->label('Dibuat')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->afterStateHydrated(function ($component, $state, ?Employee $record): void {
                                                $component->state($record?->created_at?->diffForHumans());
                                            })
                                            ->hidden(fn (?Employee $record) => $record === null),

                                        TextInput::make('updated_at_display')
                                            ->label('Diperbarui')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->afterStateHydrated(function ($component, $state, ?Employee $record): void {
                                                $component->state($record?->updated_at?->diffForHumans());
                                            })
                                            ->hidden(fn (?Employee $record) => $record === null),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
