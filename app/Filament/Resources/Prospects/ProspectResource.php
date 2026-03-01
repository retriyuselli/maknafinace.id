<?php

namespace App\Filament\Resources\Prospects;

use App\Filament\Resources\Prospects\Pages\CreateProspect;
use App\Filament\Resources\Prospects\Pages\EditProspect;
use App\Filament\Resources\Prospects\Pages\ListProspects;
use App\Filament\Resources\Prospects\Schemas\ProspectForm;
use App\Filament\Resources\Prospects\Tables\ProspectsTable;
use App\Models\Prospect;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProspectResource extends Resource
{
    protected static ?string $model = Prospect::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Prospek';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return ProspectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProspectsTable::configure($table);
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
            'index' => ListProspects::route('/'),
            'create' => CreateProspect::route('/create'),
            'edit' => EditProspect::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user:id,name', 'latestOrder'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $modelClass = static::$model;

        return (string) $modelClass::whereDoesntHave('orders')
            ->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Calon client yang terdaftar';
    }
}
