<?php

namespace App\Filament\Resources\ProspectApps;

use App\Filament\Resources\ProspectApps\Pages\CreateProspectApp;
use App\Filament\Resources\ProspectApps\Pages\EditProspectApp;
use App\Filament\Resources\ProspectApps\Pages\ListProspectApps;
use App\Filament\Resources\ProspectApps\Pages\ViewProspectApp;
use App\Filament\Resources\ProspectApps\Schemas\ProspectAppForm;
use App\Filament\Resources\ProspectApps\Tables\ProspectAppsTable;
use App\Models\ProspectApp;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProspectAppResource extends Resource
{
    protected static ?string $model = ProspectApp::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document';

    protected static ?string $navigationLabel = 'Aplikasi Prospek';

    protected static ?string $modelLabel = 'Aplikasi Prospek';

    protected static ?string $pluralModelLabel = 'Aplikasi Prospek';

    protected static string|\UnitEnum|null $navigationGroup = 'WOFINS';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ProspectAppForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProspectAppsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'industry:id,industry_name',
            ])
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
            'index' => ListProspectApps::route('/'),
            'create' => CreateProspectApp::route('/create'),
            'view' => ViewProspectApp::route('/{record}'),
            'edit' => EditProspectApp::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total aplikasi prospek';
    }
}
