<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ItemPurchaseCodeStatus: string implements HasColor, HasIcon, HasLabel
{
    case Unused = 'unused';
    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';

    public function getLabel(): string
    {
        return match ($this) {
            self::Unused => 'Belum dipakai',
            self::Active => 'Aktif',
            self::Expired => 'Kedaluwarsa',
            self::Revoked => 'Dicabut',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Unused => 'heroicon-m-key',
            self::Active => 'heroicon-m-check-circle',
            self::Expired => 'heroicon-m-clock',
            self::Revoked => 'heroicon-m-x-circle',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unused => 'gray',
            self::Active => 'success',
            self::Expired => 'warning',
            self::Revoked => 'danger',
        };
    }
}
