<?php

namespace App\Filament\Resources\Concerns;

use App\Models\User;
use App\Support\Site;
use Illuminate\Support\Facades\Auth;

trait RestrictsToSuperAdmin
{
    public static function shouldRegisterNavigation(): bool
    {
        return Site::showsAbsensiMenu() && parent::shouldRegisterNavigation();
    }

    public static function canAccess(): bool
    {
        if (! Site::showsAbsensiMenu()) {
            return false;
        }

        $user = Auth::user();

        return $user instanceof User && $user->hasRole('super_admin');
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }
}
