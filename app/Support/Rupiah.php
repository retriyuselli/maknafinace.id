<?php

namespace App\Support;

class Rupiah
{
    public static function parse(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $clean = preg_replace('/\D+/', '', $value);

            if ($clean === '' || $clean === null) {
                return 0.0;
            }

            return (float) $clean;
        }

        return (float) $value;
    }

    public static function format(float|int $amount, bool $withPrefix = false): string
    {
        $normalized = (float) $amount;
        $formatted = number_format($normalized, 0, ',', '.');

        return $withPrefix ? 'Rp '.$formatted : $formatted;
    }
}

