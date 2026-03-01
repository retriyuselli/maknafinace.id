<?php

namespace App\Filament\Resources\NotaDinasDetails;

use App\Filament\Resources\NotaDinasDetails\Pages\CreateNotaDinasDetail;
use App\Filament\Resources\NotaDinasDetails\Pages\EditNotaDinasDetail;
use App\Filament\Resources\NotaDinasDetails\Pages\ListNotaDinasDetails;
use App\Filament\Resources\NotaDinasDetails\Schemas\NotaDinasDetailForm;
use App\Filament\Resources\NotaDinasDetails\Tables\NotaDinasDetailsTable;
use App\Models\NotaDinasDetail;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotaDinasDetailResource extends Resource
{
    protected static ?string $model = NotaDinasDetail::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Detail Nota Dinas';

    protected static ?string $modelLabel = 'Detail Nota Dinas';

    protected static ?string $pluralModelLabel = 'Detail Nota Dinas';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    public static function form(Schema $schema): Schema
    {
        return NotaDinasDetailForm::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'order',
                'notaDinas:id,no_nd,status',
                'vendor:id,name',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function table(Table $table): Table
    {
        return NotaDinasDetailsTable::configure($table);
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
            'index' => ListNotaDinasDetails::route('/'),
            'create' => CreateNotaDinasDetail::route('/create'),
            'edit' => EditNotaDinasDetail::route('/{record}/edit'),
            'current-month' => Pages\CurrentMonthReport::route('/current-month'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total detail nota dinas';
    }
}
