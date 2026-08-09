<?php

namespace App\Providers;

use App\Models\Account;
use App\Observers\AccountObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
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
        Model::unguard();

        $this->trustConfiguredProxies();
    }

    /**
     * Honour X-Forwarded-* from the proxies we have been told to trust.
     *
     * This deliberately lives here rather than in bootstrap/app.php. The
     * closure passed to withMiddleware() runs on afterResolving(HttpKernel),
     * which happens before Laravel parses the .env file — so env() there is
     * always null and the setting would silently never apply. Provider boot()
     * runs after the environment and config are loaded, and TrustProxies
     * exposes static setters precisely so it can be configured this late.
     */
    protected function trustConfiguredProxies(): void
    {
        $proxies = config('proxies.trusted');

        if (blank($proxies)) {
            return;
        }

        TrustProxies::at(
            $proxies === '*' ? '*' : array_map('trim', explode(',', (string) $proxies)),
        );

        TrustProxies::withHeaders(
            Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO,
        );
    }
}
