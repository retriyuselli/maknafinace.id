<?php

namespace App\Support;

class Site
{
    public static function showsAbsensiMenu(): bool
    {
        $override = config('app.show_absensi_menu');

        if ($override !== null && $override !== '') {
            return filter_var($override, FILTER_VALIDATE_BOOLEAN);
        }

        return self::isMaknaFinanceHost();
    }

    public static function isMaknaFinanceHost(): bool
    {
        $hosts = [
            parse_url((string) config('app.url'), PHP_URL_HOST),
        ];

        if (app()->bound('request')) {
            try {
                $hosts[] = request()->getHost();
            } catch (\Throwable) {
                // CLI / queued jobs may not have a request host.
            }
        }

        foreach ($hosts as $host) {
            $host = strtolower((string) $host);

            if ($host === 'maknafinance.id' || str_ends_with($host, '.maknafinance.id')) {
                return true;
            }
        }

        return false;
    }
}
