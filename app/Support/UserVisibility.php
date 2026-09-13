<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserVisibility
{
    /**
     * Instalasi Makna tunggal — tidak memfilter lintas company.
     */
    public static function companyId(?User $user = null): ?int
    {
        $user ??= Auth::user();

        return $user instanceof User ? 1 : null;
    }

    public static function constrainUsersQuery(Builder $query): Builder
    {
        return $query;
    }
}
