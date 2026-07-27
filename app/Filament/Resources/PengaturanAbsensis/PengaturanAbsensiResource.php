<?php

namespace App\Filament\Resources\PengaturanAbsensis;

use App\Filament\Resources\Concerns\RestrictsToSuperAdmin;
use App\Filament\Resources\PengaturanAbsensis\Pages\CreatePengaturanAbsensi;
use App\Filament\Resources\PengaturanAbsensis\Pages\EditPengaturanAbsensi;
use App\Filament\Resources\PengaturanAbsensis\Pages\ListPengaturanAbsensis;
use App\Filament\Resources\PengaturanAbsensis\Schemas\PengaturanAbsensiForm;
use App\Filament\Resources\PengaturanAbsensis\Tables\PengaturanAbsensisTable;
use App\Models\PengaturanAbsensi;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PengaturanAbsensiResource extends Resource
{
    use RestrictsToSuperAdmin;
    protected static ?string $model = PengaturanAbsensi::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Absensi';

    protected static ?string $modelLabel = 'Pengaturan Absensi';

    protected static ?string $pluralModelLabel = 'Pengaturan Absensi';

    protected static string|\UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return PengaturanAbsensiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PengaturanAbsensisTable::configure($table);
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
            'index' => ListPengaturanAbsensis::route('/'),
            'create' => CreatePengaturanAbsensi::route('/create'),
            'edit' => EditPengaturanAbsensi::route('/{record}/edit'),
        ];
    }
}
