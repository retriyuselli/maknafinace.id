<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static string|\UnitEnum|null $navigationGroup = 'SDM';

    protected static ?string $recordTitleAttribute = 'name';

    public static function isSuperAdmin(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin');
    }

    public static function isTargetUserSuperAdmin($record): bool
    {
        if (! $record) {
            return false;
        }

        return $record->hasRole('super_admin');
    }

    public static function getEloquentQuery(): Builder
    {
        // Query ringan untuk list: tanpa withSum cuti / withCount / payroll.
        // Kolom sekunder di tabel disembunyikan by default.
        $query = parent::getEloquentQuery()
            ->with(['statuses', 'roles']);

        if (! static::isSuperAdmin()) {
            $user = Auth::user();
            if ($user) {
                $query->where('id', $user->id);
            }
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total user';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Cache::remember(
            'nav:users:count',
            60,
            fn (): int => (int) static::getModel()::query()->count()
        );
    }
}
