<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class CompanySubscription
{
    public const RESOURCE_USERS = 'users';

    public const RESOURCE_VENDORS = 'vendors';

    public const RESOURCE_PRODUCTS = 'products';

    public const RESOURCE_ORDERS = 'orders';

    public const RESOURCE_PROSPECTS = 'prospects';

    public const RESOURCE_SIMULASI = 'simulasi';

    public const RESOURCE_PAYMENT_METHODS = 'payment_methods';

    public const RESOURCE_FIXED_ASSETS = 'fixed_assets';

    public const RESOURCE_PIUTANGS = 'piutangs';

    public const RESOURCE_PEMBAYARAN_PIUTANGS = 'pembayaran_piutangs';

    public const RESOURCE_CATEGORIES = 'categories';

    public const RESOURCE_DATA_PEMBAYARANS = 'data_pembayarans';

    public const RESOURCE_EXPENSES = 'expenses';

    public const RESOURCE_EXPENSE_OPS = 'expense_ops';

    public const RESOURCE_PENDAPATAN_LAINS = 'pendapatan_lains';

    public const RESOURCE_PENGELUARAN_LAINS = 'pengeluaran_lains';

    public static function company(?User $actor = null): ?Company
    {
        if (! Schema::hasTable('companies')) {
            return null;
        }

        return Company::query()->orderBy('id')->first();
    }

    public static function canCreate(string $resource): bool
    {
        return true;
    }

    public static function summary(string $resource): string
    {
        return 'Tak terbatas';
    }

    public static function fullMessage(string $resource): string
    {
        return 'Penambahan data tidak tersedia.';
    }

    public static function seatLimit(): ?int
    {
        return null;
    }

    public static function upgradeMessage(string $feature = PricingPlans::FEATURE_PAYROLL): string
    {
        return 'Fitur tidak tersedia.';
    }
}
