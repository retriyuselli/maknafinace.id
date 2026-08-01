<?php

namespace App\Filament\Resources\HariLiburs;

use App\Filament\Resources\Concerns\RestrictsToSuperAdmin;
use App\Filament\Resources\HariLiburs\Pages\CreateHariLibur;
use App\Filament\Resources\HariLiburs\Pages\EditHariLibur;
use App\Filament\Resources\HariLiburs\Pages\ListHariLiburs;
use App\Filament\Resources\HariLiburs\Schemas\HariLiburForm;
use App\Filament\Resources\HariLiburs\Tables\HariLibursTable;
use App\Models\HariLibur;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class HariLiburResource extends Resource
{
    use RestrictsToSuperAdmin;
    protected static ?string $model = HariLibur::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Hari Libur';

    protected static ?string $modelLabel = 'Hari Libur';

    protected static ?string $pluralModelLabel = 'Hari Libur';

    protected static string|\UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return HariLiburForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HariLibursTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHariLiburs::route('/'),
            'create' => CreateHariLibur::route('/create'),
            'edit' => EditHariLibur::route('/{record}/edit'),
        ];
    }
}
