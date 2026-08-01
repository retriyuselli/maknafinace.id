<?php

namespace App\Filament\Resources\PengajuanLemburs;

use App\Filament\Resources\PengajuanLemburs\Pages\CreatePengajuanLembur;
use App\Filament\Resources\PengajuanLemburs\Pages\EditPengajuanLembur;
use App\Filament\Resources\PengajuanLemburs\Pages\ListPengajuanLemburs;
use App\Filament\Resources\PengajuanLemburs\Schemas\PengajuanLemburForm;
use App\Filament\Resources\PengajuanLemburs\Tables\PengajuanLembursTable;
use App\Models\PengajuanLembur;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PengajuanLemburResource extends Resource
{
    protected static ?string $model = PengajuanLembur::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-moon';

    protected static ?string $navigationLabel = 'Pengajuan Lembur';

    protected static ?string $modelLabel = 'Pengajuan Lembur';

    protected static ?string $pluralModelLabel = 'Pengajuan Lembur';

    protected static string|\UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return PengajuanLemburForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PengajuanLembursTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPengajuanLemburs::route('/'),
            'create' => CreatePengajuanLembur::route('/create'),
            'edit' => EditPengajuanLembur::route('/{record}/edit'),
        ];
    }
}
