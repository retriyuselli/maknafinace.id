<?php

namespace App\Filament\Resources\FixedAssets;

use App\Filament\Resources\FixedAssets\Pages\CreateFixedAsset;
use App\Filament\Resources\FixedAssets\Pages\DepreciationHistory;
use App\Filament\Resources\FixedAssets\Pages\EditFixedAsset;
use App\Filament\Resources\FixedAssets\Pages\ListFixedAssets;
use App\Filament\Resources\FixedAssets\Pages\ViewFixedAsset;
use App\Filament\Resources\FixedAssets\Schemas\FixedAssetForm;
use App\Filament\Resources\FixedAssets\Tables\FixedAssetsTable;
use App\Models\FixedAsset;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class FixedAssetResource extends Resource
{
    protected static ?string $model = FixedAsset::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Aset Tetap';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return FixedAssetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FixedAssetsTable::configure($table);
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
            'index' => ListFixedAssets::route('/'),
            'create' => CreateFixedAsset::route('/create'),
            'view' => ViewFixedAsset::route('/{record}'),
            'edit' => EditFixedAsset::route('/{record}/edit'),
            'depreciation-history' => DepreciationHistory::route('/{record}/depreciation-history'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total aset tetap terdaftar';
    }
}
