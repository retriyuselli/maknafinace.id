<?php

namespace App\Filament\Resources\Sops;

use App\Filament\Resources\Sops\Pages\CreateSop;
use App\Filament\Resources\Sops\Pages\EditSop;
use App\Filament\Resources\Sops\Pages\ListSops;
use App\Filament\Resources\Sops\Pages\ViewSop;
use App\Filament\Resources\Sops\Schemas\SopForm;
use App\Filament\Resources\Sops\Tables\SopsTable;
use App\Models\Sop;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SopResource extends Resource
{
    protected static ?string $model = Sop::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'SOP';

    protected static ?string $modelLabel = 'SOP';

    protected static ?string $pluralModelLabel = 'SOP';

    protected static string|\UnitEnum|null $navigationGroup = 'Administrasi';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return SopForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SopsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['category', 'creator', 'updater']);
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
            'index' => ListSops::route('/'),
            'create' => CreateSop::route('/create'),
            'edit' => EditSop::route('/{record}/edit'),
            'view' => ViewSop::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total SOP aktif/nonaktif';
    }
}
