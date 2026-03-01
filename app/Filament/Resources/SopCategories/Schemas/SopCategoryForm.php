<?php

namespace App\Filament\Resources\SopCategories\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SopCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kategori')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Kategori')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama kategori SOP'),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->placeholder('Masukkan deskripsi kategori (opsional)'),

                        Grid::make(2)
                            ->schema([
                                ColorPicker::make('color')
                                    ->label('Warna')
                                    ->default('#3B82F6')
                                    ->hex(),

                                TextInput::make('icon')
                                    ->label('Icon')
                                    ->placeholder('heroicon-o-folder')
                                    ->helperText('Gunakan icon Heroicons (contoh: heroicon-o-folder)'),
                            ]),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Kategori yang tidak aktif tidak akan ditampilkan'),
                    ])
                    ->columns(1),
            ]);
    }
}
