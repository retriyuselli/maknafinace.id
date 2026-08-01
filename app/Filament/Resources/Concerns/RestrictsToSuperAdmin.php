<?php

namespace App\Filament\Resources\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait RestrictsToSuperAdmin
{
    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole('super_admin');
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }
}
