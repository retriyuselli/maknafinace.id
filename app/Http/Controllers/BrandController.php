<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BrandController extends Controller
{
    public function logo()
    {
        $path = $this->resolveCompanyAssetPath('company_logo_path', 'logo_url', 'images/logomki.png');

        return response()->file($path, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function favicon()
    {
        $path = $this->resolveCompanyAssetPath('company_favicon_path', 'favicon_url', 'images/favicon_makna.png');

        return response()->file($path, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function loginImage()
    {
        $path = $this->resolveCompanyAssetPath('company_login_image_path', 'image_login', 'images/team_makna.jpg');

        return response()->file($path, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    protected function resolveCompanyAssetPath(string $cacheKey, string $column, string $defaultPath): string
    {
        $cache = Cache::store();
        if (config('cache.default') === 'database' && ! $this->hasTable('cache')) {
            $cache = Cache::store('array');
        }

        return $cache->remember($cacheKey, 3600, function () use ($column, $defaultPath) {
            $path = public_path($defaultPath);

            if (! $this->hasTable('companies')) {
                return $path;
            }

            try {
                $assetPath = Company::query()->value($column);
                if ($assetPath && Storage::disk('public')->exists($assetPath)) {
                    return Storage::disk('public')->path($assetPath);
                }
            } catch (Throwable) {
                return $path;
            }

            return $path;
        });
    }

    protected function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}
