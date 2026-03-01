<?php

namespace App\Filament\Resources\SopCategories;

use App\Filament\Resources\SopCategories\Pages\CreateSopCategory;
use App\Filament\Resources\SopCategories\Pages\EditSopCategory;
use App\Filament\Resources\SopCategories\Pages\ListSopCategories;
use App\Filament\Resources\SopCategories\Schemas\SopCategoryForm;
use App\Filament\Resources\SopCategories\Tables\SopCategoriesTable;
use App\Models\SopCategory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SopCategoryResource extends Resource
{
    protected static ?string $model = SopCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Kategori SOP';

    protected static ?string $modelLabel = 'Kategori SOP';

    protected static ?string $pluralModelLabel = 'Kategori SOP';

    protected static string|\UnitEnum|null $navigationGroup = 'Administrasi';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SopCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SopCategoriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSopCategories::route('/'),
            'create' => CreateSopCategory::route('/create'),
            'edit' => EditSopCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total kategori SOP';
    }
}
