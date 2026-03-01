<?php

namespace App\Filament\Resources\Statuses;

use App\Filament\Resources\Statuses\Pages\CreateStatus;
use App\Filament\Resources\Statuses\Pages\EditStatus;
use App\Filament\Resources\Statuses\Pages\ListStatuses;
use App\Filament\Resources\Statuses\Schemas\StatusForm;
use App\Filament\Resources\Statuses\Tables\StatusesTable;
use App\Models\Status;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class StatusResource extends Resource
{
    protected static ?string $model = Status::class;

    protected static string|\UnitEnum|null $navigationGroup = 'SDM';

    protected static ?string $navigationLabel = 'Status Karyawan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    public static function form(Schema $schema): Schema
    {
        return StatusForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StatusesTable::configure($table);
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
            'index' => ListStatuses::route('/'),
            'create' => CreateStatus::route('/create'),
            'edit' => EditStatus::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
