<?php

namespace App\Filament\Resources\CompanyLogos;

use App\Filament\Resources\CompanyLogos\Pages\CreateCompanyLogo;
use App\Filament\Resources\CompanyLogos\Pages\EditCompanyLogo;
use App\Filament\Resources\CompanyLogos\Pages\ListCompanyLogos;
use App\Filament\Resources\CompanyLogos\Schemas\CompanyLogoForm;
use App\Filament\Resources\CompanyLogos\Tables\CompanyLogosTable;
use App\Models\CompanyLogo;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CompanyLogoResource extends Resource
{
    protected static ?string $model = CompanyLogo::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Logo Perusahaan';

    protected static string|\UnitEnum|null $navigationGroup = 'Administrasi';

    protected static ?string $pluralModelLabel = 'Logo Perusahaan';

    public static function form(Schema $schema): Schema
    {
        return CompanyLogoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyLogosTable::configure($table);
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
            'index' => ListCompanyLogos::route('/'),
            'create' => CreateCompanyLogo::route('/create'),
            'edit' => EditCompanyLogo::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
