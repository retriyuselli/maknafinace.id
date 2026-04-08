<?php

namespace App\Filament\Resources\PendapatanLains;

use App\Filament\Resources\PendapatanLains\Pages\CreatePendapatanLain;
use App\Filament\Resources\PendapatanLains\Pages\EditPendapatanLain;
use App\Filament\Resources\PendapatanLains\Pages\ListPendapatanLains;
use App\Filament\Resources\PendapatanLains\Schemas\PendapatanLainForm;
use App\Filament\Resources\PendapatanLains\Tables\PendapatanLainsTable;
use App\Filament\Resources\PendapatanLains\Widgets\PendapatanLainOverviewWidget;
use App\Models\PendapatanLain;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;

class PendapatanLainResource extends Resource
{
    protected static ?string $model = PendapatanLain::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isGloballySearchable = false;

    protected static ?string $navigationLabel = 'Pendapatan Lain';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    public static function form(Schema $schema): Schema
    {
        return PendapatanLainForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PendapatanLainsTable::configure($table);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Finance';
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
            'index' => ListPendapatanLains::route('/'),
            'create' => CreatePendapatanLain::route('/create'),
            'edit' => EditPendapatanLain::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'vendor:id,name',
                'paymentMethod:id,name',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    private static function getCachedNavigationBadgeStats(): array
    {
        $modelClass = static::getModel();

        return Cache::remember(
            'nav:pendapatan_lains:stats',
            60,
            fn (): array => [
                'count' => (int) $modelClass::whereNull('deleted_at')->count(),
                'total' => (float) $modelClass::whereNull('deleted_at')->sum('nominal'),
            ],
        );
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (static::getCachedNavigationBadgeStats()['count'] ?? 0);
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = (int) (static::getCachedNavigationBadgeStats()['count'] ?? 0);

        if ($count > 50) {
            return 'success';
        } elseif ($count > 20) {
            return 'warning';
        } else {
            return 'primary';
        }
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $totalRevenue = (float) (static::getCachedNavigationBadgeStats()['total'] ?? 0);
        $formattedRevenue = 'IDR '.number_format($totalRevenue, 0, ',', '.');

        return "Pendapatan lain perusahaan.\nTotal: {$formattedRevenue}";
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getWidgets(): array
    {
        return [
            PendapatanLainOverviewWidget::class,
        ];
    }
}
