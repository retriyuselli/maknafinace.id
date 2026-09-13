<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;

class CompanyBrand
{
    public const DEFAULT_LOGO = 'images/logomki.png';

    public static function remember(?User $user = null): void
    {
        // Instalasi tunggal — tidak ada cookie branding per company.
    }

    public static function name(): string
    {
        $company = Company::query()->orderBy('id')->first();

        return (string) ($company?->company_name ?: config('app.name'));
    }
}
