<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ProFeatures
{
    public static function actorIsSuperAdmin(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && method_exists($user, 'hasRole')
            && $user->hasRole('super_admin');
    }

    public static function allows(string $feature = PricingPlans::FEATURE_PAYROLL): bool
    {
        return true;
    }
}
