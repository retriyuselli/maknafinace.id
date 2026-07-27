<?php

namespace App\Filament\Resources\LogAbsensis\Schemas;

use App\Models\LogAbsensi;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class LogAbsensiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Log')
                    ->description('Data absen dari perangkat (umumnya hanya dibaca)')
                    ->schema([
                        Select::make('user_id')
                            ->label('Karyawan')
                            ->relationship('user', 'name')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('jenis')
                            ->label('Jenis')
                            ->options([
                                LogAbsensi::JENIS_MASUK => 'Masuk',
                                LogAbsensi::JENIS_PULANG => 'Pulang',
                            ])
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('waktu')
                            ->label('Waktu')
                            ->seconds(false)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('lintang')
                            ->label('Lintang')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('bujur')
                            ->label('Bujur')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('jarak_ke_kantor_meter')
                            ->label('Jarak ke Kantor')
                            ->numeric()
                            ->suffix('m')
                            ->disabled()
                            ->dehydrated(false),
                        Toggle::make('dalam_radius')
                            ->label('Dalam Radius')
                            ->disabled()
                            ->dehydrated(false),
                        Placeholder::make('foto_absensi_preview')
                            ->label('Foto Absensi')
                            ->content(function (?LogAbsensi $record): HtmlString|string {
                                $url = $record?->temporaryFotoUrl();

                                if (! $url) {
                                    return '-';
                                }

                                return new HtmlString(
                                    '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer">'
                                    .'<img src="'.e($url).'" alt="Foto absensi" style="max-width: 18rem; border-radius: 0.75rem;" />'
                                    .'</a>'
                                );
                            })
                            ->columnSpanFull(),
                        TextInput::make('nama_perangkat')
                            ->label('Nama Perangkat')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('alamat_ip')
                            ->label('Alamat IP')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),
                Section::make('Validasi')
                    ->description('Flag validasi admin')
                    ->schema([
                        Toggle::make('valid')
                            ->label('Valid')
                            ->helperText('Nonaktifkan jika log ini ditolak/tidak dihitung'),
                        Textarea::make('alasan_tolak')
                            ->label('Alasan Tolak')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
