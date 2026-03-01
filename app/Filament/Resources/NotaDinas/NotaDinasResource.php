<?php

namespace App\Filament\Resources\NotaDinas;

use App\Filament\Resources\NotaDinas\Pages\CreateNotaDinas;
use App\Filament\Resources\NotaDinas\Pages\EditNotaDinas;
use App\Filament\Resources\NotaDinas\Pages\ListNotaDinas;
use App\Filament\Resources\NotaDinas\Pages\ViewNd;
use App\Filament\Resources\NotaDinas\Schemas\NotaDinasForm;
use App\Filament\Resources\NotaDinas\Tables\NotaDinasTable;
use App\Models\NotaDinas;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotaDinasResource extends Resource
{
    protected static ?string $model = NotaDinas::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Nota Dinas';

    protected static ?string $modelLabel = 'Nota Dinas';

    protected static ?string $pluralModelLabel = 'Nota Dinas';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    public static function form(Schema $schema): Schema
    {
        return NotaDinasForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotaDinasTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'pengirim:id,name',
                'penerima:id,name',
                'approver:id,name',
            ])
            ->withCount('details')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\NotaDinasDetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotaDinas::route('/'),
            'create' => CreateNotaDinas::route('/create'),
            'edit' => EditNotaDinas::route('/{record}/edit'),
            'view-nd' => ViewNd::route('/{record}/view-nd'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total nota dinas';
    }
}
