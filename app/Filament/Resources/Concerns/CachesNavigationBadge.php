<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Support\Facades\Cache;

trait CachesNavigationBadge
{
    public static function getNavigationBadge(): ?string
    {
        $key = 'nav:badge:'.md5(static::class);

        return (string) Cache::remember(
            $key,
            60,
            fn (): int => (int) static::getModel()::query()->count()
        );
    }
}
