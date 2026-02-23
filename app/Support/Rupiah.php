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
            $s = trim($value);
            if ($s === '') {
                return 0.0;
            }
            if (preg_match('/^\d+\.\d{1,6}$/', $s)) {
                return (float) $s;
            }
            $clean = preg_replace('/\D+/', '', $s);
            return $clean === '' ? 0.0 : (float) $clean;
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
