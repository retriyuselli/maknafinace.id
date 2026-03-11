<?php

namespace App\Filament\Resources\Documentations\Schemas;

use App\Enums\ResourceEnum;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DocumentationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('documentation_category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state)))
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                RichEditor::make('content')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('keywords')
                    ->maxLength(255)
                    ->hint('Kata kunci untuk pencarian, pisahkan dengan koma'),
                Select::make('related_resource')
                    ->options(ResourceEnum::class)
                    ->searchable()
                    ->hint('Pilih resource terkait untuk dokumentasi ini'),
                TextInput::make('order')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_published')
                    ->required()
                    ->default(false),
            ]);
    }
}
