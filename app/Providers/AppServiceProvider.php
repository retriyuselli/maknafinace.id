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

        View::share('companyName', Cache::remember('company_name', 3600, function () {
            if (Schema::hasTable('companies')) {
                return Company::value('company_name');
            }

            return config('app.name');
        }));

        View::share('companyAddress', Cache::remember('company_address', 3600, function () {
            if (Schema::hasTable('companies')) {
                return Company::value('address');
            }

            return null;
        }));

        View::share('companyEmail', Cache::remember('company_email', 3600, function () {
            if (Schema::hasTable('companies')) {
                return Company::value('email');
            }

            return null;
        }));

        View::share('companyPhone', Cache::remember('company_phone', 3600, function () {
            if (Schema::hasTable('companies')) {
                return Company::value('phone');
            }

            return null;
        }));

        View::share('companyLogoUrl', Cache::remember('company_logo_url', 3600, function () {
            if (Schema::hasTable('companies')) {
                $path = Company::value('logo_url');
                if ($path && Storage::disk('public')->exists($path)) {
                    return asset('storage/'.ltrim($path, '/'));
                }
            }

            return asset('images/logomki.png');
        }));

        View::share('companyFaviconUrl', Cache::remember('company_favicon_url', 3600, function () {
            if (Schema::hasTable('companies')) {
                $path = Company::value('favicon_url');
                if ($path && Storage::disk('public')->exists($path)) {
                    return asset('storage/'.ltrim($path, '/'));
                }
            }

            return asset('images/favicon_makna.png');
        }));

        View::share('companyBrandVersion', Cache::remember('company_brand_version', 60, function () {
            if (Schema::hasTable('companies')) {
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
    }
}
