<?php

namespace App\Filament\Resources\LokasiAbsensis;

use App\Filament\Resources\Concerns\RestrictsToSuperAdmin;
use App\Filament\Resources\LokasiAbsensis\Pages\CreateLokasiAbsensi;
use App\Filament\Resources\LokasiAbsensis\Pages\EditLokasiAbsensi;
use App\Filament\Resources\LokasiAbsensis\Pages\ListLokasiAbsensis;
use App\Filament\Resources\LokasiAbsensis\Schemas\LokasiAbsensiForm;
use App\Filament\Resources\LokasiAbsensis\Tables\LokasiAbsensisTable;
use App\Models\LokasiAbsensi;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LokasiAbsensiResource extends Resource
{
    use RestrictsToSuperAdmin;
    protected static ?string $model = LokasiAbsensi::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Lokasi Kantor';

    protected static ?string $modelLabel = 'Lokasi Kantor';

    protected static ?string $pluralModelLabel = 'Lokasi Kantor';

    protected static string|\UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return LokasiAbsensiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LokasiAbsensisTable::configure($table);
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
            'index' => ListLokasiAbsensis::route('/'),
            'create' => CreateLokasiAbsensi::route('/create'),
            'edit' => EditLokasiAbsensi::route('/{record}/edit'),
        ];
    }
}
