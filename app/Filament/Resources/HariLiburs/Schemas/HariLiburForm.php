<?php

namespace App\Filament\Resources\HariLiburs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HariLiburForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Hari Libur')
                    ->schema([
                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->native(false),
                        TextInput::make('nama')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Hari Raya Idul Fitri'),
                        Toggle::make('nasional')
                            ->label('Libur Nasional')
                            ->default(true)
                            ->helperText('Centang jika libur resmi nasional'),
                        Toggle::make('tetap_masuk')
                            ->label('Tetap Masuk')
                            ->default(false)
                            ->helperText('Jika aktif, tanggal ini tetap dihitung hari kerja'),
                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
