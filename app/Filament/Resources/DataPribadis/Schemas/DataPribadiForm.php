<?php

namespace App\Filament\Resources\DataPribadis\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class DataPribadiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Personal')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_lengkap')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama lengkap'),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('contoh@domain.com')
                            ->unique(ignoreRecord: true),
                        TextInput::make('nomor_telepon')
                            ->tel()
                            ->prefix('+62')
                            ->placeholder('81234567890')
                            ->telRegex('/^[0-9]{9,15}$/')
                            ->maxLength(20),
                        DatePicker::make('tanggal_lahir')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Select::make('jenis_kelamin')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ])
                            ->placeholder('Pilih jenis kelamin'),
                        FileUpload::make('foto')
                            ->image()
                            ->imageEditor()
                            ->maxSize(1024)
                            ->columnSpanFull()
                            ->helperText('Unggah foto profil (maks. 1MB).'),
                        Textarea::make('alamat')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Masukkan alamat lengkap'),
                    ]),
                Section::make('Informasi Pekerjaan')
                    ->columns(2)
                    ->schema([
                        TextInput::make('pekerjaan')
                            ->maxLength(255)
                            ->placeholder('Masukkan pekerjaan saat ini'),
                        TextInput::make('gaji')
                            ->numeric()
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->placeholder('0'),
                        Textarea::make('motivasi_kerja')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Jelaskan motivasi kerja Anda'),
                        RichEditor::make('pelatihan')
                            ->columnSpanFull()
                            ->placeholder('Pelatihan yang pernah diikuti di Makna'),
                    ]),
            ]);
    }
}
