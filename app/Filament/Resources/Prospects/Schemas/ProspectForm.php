<?php

namespace App\Filament\Resources\Prospects\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

class ProspectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Informasi Acara')
                            ->description('Detail dasar acara dan tempat')
                            ->schema([
                                TextInput::make('name_event')
                                    ->label('Nama Acara')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Pernikahan Pengantin Pria & Pengantin Wanita')
                                    ->columnSpanFull(),

                                Grid::make(3)
                                    ->schema([
                                        DatePicker::make('date_lamaran')
                                            ->label('Tanggal Lamaran')
                                            ->native(false)
                                            ->displayFormat('d M Y'),

                                        DatePicker::make('date_akad')
                                            ->label('Tanggal Akad Nikah')
                                            ->native(false)
                                            ->displayFormat('d M Y'),

                                        DatePicker::make('date_resepsi')
                                            ->label('Tanggal Resepsi')
                                            ->native(false)
                                            ->displayFormat('d M Y'),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        TimePicker::make('time_lamaran')
                                            ->label('Jam Lamaran')
                                            ->seconds(false),

                                        TimePicker::make('time_akad')
                                            ->label('Jam Akad Nikah')
                                            ->seconds(false),

                                        TimePicker::make('time_resepsi')
                                            ->label('Jam Resepsi')
                                            ->seconds(false),
                                    ]),

                                TextInput::make('venue')
                                    ->label('Lokasi Venue')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Masukkan detail venue')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Informasi Klien')
                            ->description('Detail kontak untuk pasangan')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name_cpp')
                                            ->label('Nama Calon Pengantin Pria')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('name_cpw')
                                            ->label('Nama Calon Pengantin Wanita')
                                            ->required()
                                            ->maxLength(255),
                                    ]),

                                TextInput::make('phone')
                                    ->tel()
                                    ->required()
                                    ->prefix('+62')
                                    ->regex('/^[0-9]{8,15}$/')
                                    ->placeholder('812XXXXXXXX')
                                    ->helperText('Masukkan nomor tanpa angka 0 di depan'),

                                TextInput::make('address')
                                    ->label('Alamat')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Keuangan & Manajemen')
                            ->description('Detail harga dan manajemen akun')
                            ->schema([
                                TextInput::make('total_penawaran')
                                    ->label('Total Penawaran')
                                    ->required()
                                    ->prefix('Rp. ')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                    ->placeholder('0')
                                    ->helperText('Masukkan total jumlah penawaran'),

                                Select::make('user_id')
                                    ->label('Manajer Akun')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(Auth::user()->id)
                                    ->helperText('Pilih manajer akun yang bertanggung jawab'),
                            ]),

                        Section::make('Catatan Tambahan')
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Catatan')
                                    ->placeholder('Masukkan catatan tambahan atau persyaratan khusus')
                                    ->rows(5)
                                    ->default('Tidak ada catatan')
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
