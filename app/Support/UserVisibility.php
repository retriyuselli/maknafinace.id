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

    public static function constrainCompanyQuery(Builder $query, string $column = 'company_id'): Builder
    {
        return $query;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function stampCompanyId(array $data, ?string $fromUserColumn = null): array
    {
        unset($data['company_id']);

        return $data;
    }

    public static function isSingleSeatPlan(?User $actor = null): bool
    {
        return false;
    }
}
