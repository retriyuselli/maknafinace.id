<?php

namespace App\Filament\Resources\LogAbsensis;

use App\Filament\Resources\LogAbsensis\Pages\CreateLogAbsensi;
use App\Filament\Resources\LogAbsensis\Pages\EditLogAbsensi;
use App\Filament\Resources\LogAbsensis\Pages\ListLogAbsensis;
use App\Filament\Resources\LogAbsensis\Schemas\LogAbsensiForm;
use App\Filament\Resources\LogAbsensis\Tables\LogAbsensisTable;
use App\Models\LogAbsensi;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LogAbsensiResource extends Resource
{
    protected static ?string $model = LogAbsensi::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Log Absensi';

    protected static ?string $modelLabel = 'Log Absensi';

    protected static ?string $pluralModelLabel = 'Log Absensi';

    protected static string|\UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return LogAbsensiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LogAbsensisTable::configure($table);
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
            'index' => ListLogAbsensis::route('/'),
            'create' => CreateLogAbsensi::route('/create'),
            'edit' => EditLogAbsensi::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
