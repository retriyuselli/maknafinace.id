<?php

namespace App\Filament\Resources\DocumentationCategories;

use App\Filament\Resources\DocumentationCategories\Pages;
use App\Filament\Resources\DocumentationCategories\Schemas\DocumentationCategoryForm;
use App\Filament\Resources\DocumentationCategories\Tables\DocumentationCategoriesTable;
use App\Models\DocumentationCategory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;

class DocumentationCategoryResource extends Resource
{
    protected static ?string $model = DocumentationCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static string|UnitEnum|null $navigationGroup = 'Knowledge Base';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return DocumentationCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentationCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentationCategories::route('/'),
            'create' => Pages\CreateDocumentationCategory::route('/create'),
            'edit' => Pages\EditDocumentationCategory::route('/{record}/edit'),
        ];
    }
}
