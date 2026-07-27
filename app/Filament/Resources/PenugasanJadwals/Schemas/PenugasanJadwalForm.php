<?php

namespace App\Filament\Resources\PenugasanJadwals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PenugasanJadwalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Penugasan')
                    ->schema([
                        Select::make('user_id')
                            ->label('Karyawan')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('jadwal_kerja_id')
                            ->label('Jadwal Kerja')
                            ->relationship('jadwalKerja', 'nama', fn ($query) => $query->where('aktif', true))
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('berlaku_dari')
                            ->label('Berlaku Dari')
                            ->required()
                            ->native(false)
                            ->default(now()),
                        DatePicker::make('berlaku_sampai')
                            ->label('Berlaku Sampai')
                            ->native(false)
                            ->helperText('Kosongkan jika masih berlaku'),
                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
