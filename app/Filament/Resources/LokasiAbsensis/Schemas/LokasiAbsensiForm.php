<?php

namespace App\Filament\Resources\LokasiAbsensis\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LokasiAbsensiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lokasi Kantor')
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Kantor Pusat')
                            ->columnSpanFull(),

                        ViewField::make('peta_picker')
                            ->label('Pilih di Peta')
                            ->view('filament.forms.components.lokasi-map-picker')
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->helperText('Klik peta, geser pin, atau cari alamat. Koordinat lintang/bujur terisi otomatis.'),

                        TextInput::make('lintang')
                            ->label('Lintang (Latitude)')
                            ->required()
                            ->numeric()
                            ->step(0.0000001)
                            ->live(onBlur: true)
                            ->helperText('Bisa diisi manual atau dari peta'),
                        TextInput::make('bujur')
                            ->label('Bujur (Longitude)')
                            ->required()
                            ->numeric()
                            ->step(0.0000001)
                            ->live(onBlur: true),
                        TextInput::make('radius_meter')
                            ->label('Radius')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(150)
                            ->suffix('meter')
                            ->live(debounce: 500)
                            ->helperText('Lingkaran hijau di peta mengikuti nilai ini'),
                        TextInput::make('urutan')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Toggle::make('aktif')
                            ->label('Aktif')
                            ->default(true),
                        Textarea::make('alamat')
                            ->label('Alamat')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
