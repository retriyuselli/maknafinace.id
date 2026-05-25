<?php

namespace App\Providers;

use App\Listeners\CheckUserExpirationOnLogin;
use App\Models\BankStatement;
use App\Models\Company;
use App\Models\Document;
use App\Models\LeaveRequest;
use App\Models\Order;
use App\Models\User;
use App\Observers\BankStatementObserver;
use App\Observers\DocumentObserver;
use App\Observers\LeaveRequestObserver;
use App\Observers\OrderObserver;
use App\Observers\UserObserver;
use CmsMulti\FilamentClearCache\Facades\FilamentClearCache;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $hasTable = function (string $table): bool {
            try {
                return Schema::hasTable($table);
            } catch (\Throwable $e) {
                return false;
            }
        };

        $cache = Cache::store();
        if (config('cache.default') === 'database' && ! $hasTable('cache')) {
            $cache = Cache::store('array');
        }

        // Register User Observer for auto-generating leave balances
        User::observe(UserObserver::class);

        // Register LeaveRequest Observer for auto-filling user_id
        LeaveRequest::observe(LeaveRequestObserver::class);

        // Register Order Observer for tracking last edited by
        Order::observe(OrderObserver::class);

        // Register BankStatement Observer for tracking last edited by
        BankStatement::observe(BankStatementObserver::class);

        // Register Document Observer for auto-numbering
        Document::observe(DocumentObserver::class);

        // Register login event listener for daily expiration welcome notifications
        Event::listen(
            Login::class,
            CheckUserExpirationOnLogin::class
        );

        if (env('DB_SLOW_QUERY_LOG', false)) {
            DB::listen(function ($query) {
                $threshold = (int) env('DB_SLOW_MS', 100);
                if ((int) $query->time >= $threshold) {
                    Log::warning('slow_query', [
                        'sql' => $query->sql,
                        'time_ms' => (int) $query->time,
                        'bindings' => $query->bindings,
                    ]);
                }
            });
        }

        View::share('companyName', $cache->remember('company_name', 3600, function () use ($hasTable) {
            if ($hasTable('companies')) {
                return Company::value('company_name');
            }

            return config('app.name');
        }));

        View::share('companyAddress', $cache->remember('company_address', 3600, function () use ($hasTable) {
            if ($hasTable('companies')) {
                return Company::value('address');
            }

            return null;
        }));

        View::share('companyEmail', $cache->remember('company_email', 3600, function () use ($hasTable) {
            if ($hasTable('companies')) {
                return Company::value('email');
            }

            return null;
        }));

        View::share('companyPhone', $cache->remember('company_phone', 3600, function () use ($hasTable) {
            if ($hasTable('companies')) {
                return Company::value('phone');
            }

            return null;
        }));

        View::share('companyLogoUrl', $cache->remember('company_logo_url', 3600, function () use ($hasTable) {
            if ($hasTable('companies')) {
                $path = Company::value('logo_url');
                if ($path && Storage::disk('public')->exists($path)) {
                    return asset('storage/'.ltrim($path, '/'));
                }
            }

            return asset('images/logomki.png');
        }));

        View::share('companyFaviconUrl', $cache->remember('company_favicon_url', 3600, function () use ($hasTable) {
            if ($hasTable('companies')) {
                $path = Company::value('favicon_url');
                if ($path && Storage::disk('public')->exists($path)) {
                    return asset('storage/'.ltrim($path, '/'));
                }
            }

            return asset('images/favicon_makna.png');
        }));

        View::share('companyBrandVersion', $cache->remember('company_brand_version', 60, function () use ($hasTable) {
            if ($hasTable('companies')) {
                $updatedAt = Company::query()->value('updated_at');
                if ($updatedAt) {
                    try {
                        return (int) \Illuminate\Support\Carbon::parse($updatedAt)->timestamp;
                    } catch (\Throwable $e) {
                        return 1;
                    }
                }
            }

            return 1;
        }));
        
        FilamentClearCache::addCommand('optimize:clear');

        // Log Viewer — hanya bisa diakses oleh super_admin
        Gate::define('viewLogViewer', function (?User $user): bool {
            return $user !== null && $user->hasRole('super_admin');
        });
    }
}
