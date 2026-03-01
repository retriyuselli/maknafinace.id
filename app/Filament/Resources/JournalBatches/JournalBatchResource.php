<?php

namespace App\Filament\Resources\JournalBatches;

use App\Filament\Resources\JournalBatches\Pages\CreateJournalBatch;
use App\Filament\Resources\JournalBatches\Pages\EditJournalBatch;
use App\Filament\Resources\JournalBatches\Pages\ListJournalBatches;
use App\Filament\Resources\JournalBatches\RelationManagers\JournalEntriesRelationManager;
use App\Filament\Resources\JournalBatches\Schemas\JournalBatchForm;
use App\Filament\Resources\JournalBatches\Tables\JournalBatchesTable;
use App\Models\JournalBatch;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JournalBatchResource extends Resource
{
    protected static ?string $model = JournalBatch::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard';

    protected static ?string $navigationLabel = 'Jurnal Umum';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return JournalBatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JournalBatchesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'createdBy:id,name',
                'approvedBy:id,name',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->where('reference_type', 'NOT LIKE', '%_reversal');
    }

    public static function getRelations(): array
    {
        return [
            JournalEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJournalBatches::route('/'),
            'create' => CreateJournalBatch::route('/create'),
            'edit' => EditJournalBatch::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total batch jurnal';
    }
}
