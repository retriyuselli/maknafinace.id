<?php

namespace App\Filament\Resources\PenugasanJadwals;

use App\Filament\Resources\Concerns\RestrictsToSuperAdmin;
use App\Filament\Resources\PenugasanJadwals\Pages\CreatePenugasanJadwal;
use App\Filament\Resources\PenugasanJadwals\Pages\EditPenugasanJadwal;
use App\Filament\Resources\PenugasanJadwals\Pages\ListPenugasanJadwals;
use App\Filament\Resources\PenugasanJadwals\Schemas\PenugasanJadwalForm;
use App\Filament\Resources\PenugasanJadwals\Tables\PenugasanJadwalsTable;
use App\Models\PenugasanJadwal;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PenugasanJadwalResource extends Resource
{
    use RestrictsToSuperAdmin;
    protected static ?string $model = PenugasanJadwal::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Penugasan Jadwal';

    protected static ?string $modelLabel = 'Penugasan Jadwal';

    protected static ?string $pluralModelLabel = 'Penugasan Jadwal';

    protected static string|\UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return PenugasanJadwalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PenugasanJadwalsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPenugasanJadwals::route('/'),
            'create' => CreatePenugasanJadwal::route('/create'),
            'edit' => EditPenugasanJadwal::route('/{record}/edit'),
        ];
    }
}
