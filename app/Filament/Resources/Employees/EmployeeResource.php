<?php

namespace App\Filament\Resources\Employees;

use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\Employees\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\Resources\Employees\Tables\EmployeesTable;
use App\Filament\Resources\Employees\Widgets\EmployeeOverviewWidget;
use App\Models\Employee;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static string|\UnitEnum|null $navigationGroup = 'SDM';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isGloballySearchable = false;

    protected static ?string $navigationLabel = 'Karyawan';

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
        ];
    }

    private static function getCachedNavigationBadgeCount(): int
    {
        $modelClass = static::getModel();

        return Cache::remember(
            'nav:employees:active_count',
            60,
            fn (): int => (int) $modelClass::query()
                ->where('date_of_join', '<=', now())
                ->where(function (Builder $query) {
                    $query->whereNull('date_of_out')
                        ->orWhere('date_of_out', '>=', now());
                })
                ->count()
        );
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getCachedNavigationBadgeCount();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            EmployeeOverviewWidget::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'position'];
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Karyawan aktif';
    }
}
