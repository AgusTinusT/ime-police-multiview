<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        if (request()->isSecure() || request()->server('HTTP_X_FORWARDED_PROTO') === 'https' || request()->server('HTTPS') === 'on') {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);
    }
}

