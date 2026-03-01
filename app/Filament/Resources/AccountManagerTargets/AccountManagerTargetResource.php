<?php

namespace App\Filament\Resources\AccountManagerTargets;

use App\Filament\Resources\AccountManagerTargets\Pages\CreateAccountManagerTarget;
use App\Filament\Resources\AccountManagerTargets\Pages\ListAccountManagerTargets;
use App\Filament\Resources\AccountManagerTargets\Schemas\AccountManagerTargetForm;
use App\Filament\Resources\AccountManagerTargets\Tables\AccountManagerTargetsTable;
use App\Filament\Resources\AccountManagerTargets\Widgets\AmOverview;
use App\Filament\Resources\AccountManagerTargets\Widgets\AmPerformanceChart;
use App\Filament\Resources\AccountManagerTargets\Widgets\TopPerformersWidget;
use App\Models\AccountManagerTarget;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AccountManagerTargetResource extends Resource
{
    protected static ?string $model = AccountManagerTarget::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Target Manajer Akun';

    protected static ?string $modelLabel = 'Target Account Manager';

    protected static ?string $pluralModelLabel = 'Target Account Manager';

    /**
     * Check if user can access this resource
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Check if user has super_admin or Account Manager role
        $roleNames = $user->roles->pluck('name');

        return $roleNames->contains('super_admin') || $roleNames->contains('Account Manager');
    }

    /**
     * Check if user can view any records
     */
    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    /**
     * Check if user can view specific record
     */
    public static function canView(Model $record): bool
    {
        return static::canAccess();
    }

    /**
     * Check if user can create records
     */
    public static function canCreate(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Only super_admin can create
        return $user->roles->where('name', 'super_admin')->count() > 0;
    }

    /**
     * Check if user can edit records
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Only super_admin can edit
        return $user->roles->where('name', 'super_admin')->count() > 0;
    }

    /**
     * Check if user can delete records
     */
    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Only super_admin can delete
        return $user->roles->where('name', 'super_admin')->count() > 0;
    }

    /**
     * Get the Eloquent query builder for the resource
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc');

        // Filter resigned users: only show targets up to their resignation month
        $query->whereHas('user', function ($q) {
            $q->whereNull('last_working_date')
                ->orWhereRaw('(account_manager_targets.year * 100 + account_manager_targets.month) <= (YEAR(last_working_date) * 100 + MONTH(last_working_date))');
        });

        $user = Auth::user();

        // If user is Account Manager, only show their own targets
        if ($user) {
            $isAccountManager = $user->roles->where('name', 'Account Manager')->count() > 0;
            $isSuperAdmin = $user->roles->where('name', 'super_admin')->count() > 0;

            if ($isAccountManager && ! $isSuperAdmin) {
                $query->where('user_id', $user->id);
            }
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return AccountManagerTargetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountManagerTargetsTable::configure($table);
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
            'index' => ListAccountManagerTargets::route('/'),
            'create' => CreateAccountManagerTarget::route('/create'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            AmOverview::class,
            AmPerformanceChart::class,
            TopPerformersWidget::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
