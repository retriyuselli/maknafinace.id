<?php

namespace App\Filament\Resources\Concerns;

use App\Support\Site;

trait VisibleOnMaknaFinance
{
    public static function shouldRegisterNavigation(): bool
    {
        return Site::showsAbsensiMenu() && parent::shouldRegisterNavigation();
    }

    public static function canAccess(): bool
    {
        return Site::showsAbsensiMenu() && parent::canAccess();
    }
}
