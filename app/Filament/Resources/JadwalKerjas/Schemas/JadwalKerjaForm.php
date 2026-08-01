<?php

namespace App\Filament\Resources\JadwalKerjas\Schemas;

use App\Models\HariJadwalKerja;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JadwalKerjaForm
{
    public static function defaultHariRows(): array
    {
        return collect(HariJadwalKerja::HARI_LABEL)
            ->map(fn (string $label, int $hari) => [
                'hari' => $hari,
                'hari_kerja' => ! in_array($hari, [0, 6], true),
                'jam_masuk' => in_array($hari, [0, 6], true) ? null : '09:00',
                'jam_pulang' => in_array($hari, [0, 6], true) ? null : '18:00',
                'menit_istirahat' => 60,
            ])
            ->values()
            ->all();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Jadwal Kerja')
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Kantor Reguler'),
                        TextInput::make('kode')
                            ->label('Kode')
                            ->maxLength(32)
                            ->unique(ignoreRecord: true)
                            ->placeholder('REG'),
                        Toggle::make('default')
                            ->label('Jadwal Default')
                            ->helperText('Digunakan bila karyawan belum punya penugasan'),
                        Toggle::make('aktif')
                            ->label('Aktif')
                            ->default(true),
                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
                Section::make('Detail Hari')
                    ->schema([
                        Repeater::make('hariJadwalKerjas')
                            ->relationship()
                            ->label('Hari')
                            ->schema([
                                Select::make('hari')
                                    ->label('Hari')
                                    ->options(HariJadwalKerja::HARI_LABEL)
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                                Toggle::make('hari_kerja')
                                    ->label('Hari Kerja')
                                    ->live(),
                                TimePicker::make('jam_masuk')
                                    ->label('Jam Masuk')
                                    ->seconds(false)
                                    ->visible(fn ($get) => (bool) $get('hari_kerja')),
                                TimePicker::make('jam_pulang')
                                    ->label('Jam Pulang')
                                    ->seconds(false)
                                    ->visible(fn ($get) => (bool) $get('hari_kerja')),
                                TextInput::make('menit_istirahat')
                                    ->label('Istirahat')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(60)
                                    ->suffix('menit')
                                    ->visible(fn ($get) => (bool) $get('hari_kerja')),
                            ])
                            ->columns(5)
                            ->default(self::defaultHariRows())
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
