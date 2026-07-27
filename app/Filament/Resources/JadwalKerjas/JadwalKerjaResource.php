<?php

namespace App\Filament\Resources\JadwalKerjas;

use App\Filament\Resources\Concerns\RestrictsToSuperAdmin;
use App\Filament\Resources\JadwalKerjas\Pages\CreateJadwalKerja;
use App\Filament\Resources\JadwalKerjas\Pages\EditJadwalKerja;
use App\Filament\Resources\JadwalKerjas\Pages\ListJadwalKerjas;
use App\Filament\Resources\JadwalKerjas\Schemas\JadwalKerjaForm;
use App\Filament\Resources\JadwalKerjas\Tables\JadwalKerjasTable;
use App\Models\JadwalKerja;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class JadwalKerjaResource extends Resource
{
    use RestrictsToSuperAdmin;
    protected static ?string $model = JadwalKerja::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Jadwal Kerja';

    protected static ?string $modelLabel = 'Jadwal Kerja';

    protected static ?string $pluralModelLabel = 'Jadwal Kerja';

    protected static string|\UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return JadwalKerjaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JadwalKerjasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJadwalKerjas::route('/'),
            'create' => CreateJadwalKerja::route('/create'),
            'edit' => EditJadwalKerja::route('/{record}/edit'),
        ];
    }
}
