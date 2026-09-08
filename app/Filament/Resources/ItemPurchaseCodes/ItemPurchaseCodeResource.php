<?php

namespace App\Filament\Resources\ItemPurchaseCodes;

use App\Filament\Resources\Concerns\CachesNavigationBadge;
use App\Filament\Resources\ItemPurchaseCodes\Pages\CreateItemPurchaseCode;
use App\Filament\Resources\ItemPurchaseCodes\Pages\EditItemPurchaseCode;
use App\Filament\Resources\ItemPurchaseCodes\Pages\ListItemPurchaseCodes;
use App\Filament\Resources\ItemPurchaseCodes\Pages\ViewItemPurchaseCode;
use App\Filament\Resources\ItemPurchaseCodes\Schemas\ItemPurchaseCodeForm;
use App\Filament\Resources\ItemPurchaseCodes\Tables\ItemPurchaseCodesTable;
use App\Models\ItemPurchaseCode;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemPurchaseCodeResource extends Resource
{
    use CachesNavigationBadge;

    protected static ?string $model = ItemPurchaseCode::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Item Purchase Code';

    protected static ?string $modelLabel = 'Item Purchase Code';

    protected static ?string $pluralModelLabel = 'Item Purchase Code';

    protected static string|\UnitEnum|null $navigationGroup = 'WOFINS';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return ItemPurchaseCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemPurchaseCodesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['prospectApp:id,full_name,company_name,email'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListItemPurchaseCodes::route('/'),
            'create' => CreateItemPurchaseCode::route('/create'),
            'view' => ViewItemPurchaseCode::route('/{record}'),
            'edit' => EditItemPurchaseCode::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Kode aktivasi aplikasi klien';
    }
}
