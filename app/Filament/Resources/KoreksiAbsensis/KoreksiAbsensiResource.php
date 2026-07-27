<?php

namespace App\Filament\Resources\KoreksiAbsensis;

use App\Filament\Resources\KoreksiAbsensis\Pages\CreateKoreksiAbsensi;
use App\Filament\Resources\KoreksiAbsensis\Pages\EditKoreksiAbsensi;
use App\Filament\Resources\KoreksiAbsensis\Pages\ListKoreksiAbsensis;
use App\Filament\Resources\KoreksiAbsensis\Schemas\KoreksiAbsensiForm;
use App\Filament\Resources\KoreksiAbsensis\Tables\KoreksiAbsensisTable;
use App\Models\KoreksiAbsensi;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class KoreksiAbsensiResource extends Resource
{
    protected static ?string $model = KoreksiAbsensi::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationLabel = 'Koreksi Absensi';

    protected static ?string $modelLabel = 'Koreksi Absensi';

    protected static ?string $pluralModelLabel = 'Koreksi Absensi';

    protected static string|\UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return KoreksiAbsensiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KoreksiAbsensisTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKoreksiAbsensis::route('/'),
            'create' => CreateKoreksiAbsensi::route('/create'),
            'edit' => EditKoreksiAbsensi::route('/{record}/edit'),
        ];
    }
}
