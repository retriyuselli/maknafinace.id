<?php

namespace App\Filament\Resources\Industries;

use App\Filament\Resources\Industries\Pages\CreateIndustry;
use App\Filament\Resources\Industries\Pages\EditIndustry;
use App\Filament\Resources\Industries\Pages\ListIndustries;
use App\Filament\Resources\Industries\Schemas\IndustryForm;
use App\Filament\Resources\Industries\Tables\IndustriesTable;
use App\Models\Industry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class IndustryResource extends Resource
{
    protected static ?string $model = Industry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Industri Wofins';

    protected static string|\UnitEnum|null $navigationGroup = 'WOFINS';

    public static function form(Schema $schema): Schema
    {
        return IndustryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IndustriesTable::configure($table);
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
            'index' => ListIndustries::route('/'),
            'create' => CreateIndustry::route('/create'),
            'edit' => EditIndustry::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total kategori industri';
    }
}
