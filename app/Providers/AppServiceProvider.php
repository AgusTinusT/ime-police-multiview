<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->bootRateLimiters();
    }

    /**
     * Each feature gets its own bucket. Laravel's unnamed `throttle:N,M` on
     * authenticated routes keys only by user id, so without this all save
     * endpoints across the app would share one counter — a user who just
     * saved a bio-link could hit "Too Many Attempts" on their first squad.
     */
    protected function bootRateLimiters(): void
    {
        $perUser = fn (Request $request) => (string) ($request->user()?->getAuthIdentifier() ?: $request->ip());

        RateLimiter::for('squad-save', fn (Request $r) => Limit::perMinute(60)->by($perUser($r)));
        RateLimiter::for('bio-link-save', fn (Request $r) => Limit::perMinute(60)->by($perUser($r)));
        RateLimiter::for('bio-profile-save', fn (Request $r) => Limit::perMinute(30)->by($perUser($r)));
        RateLimiter::for('officer-profile-save', fn (Request $r) => Limit::perMinute(30)->by($perUser($r)));
        RateLimiter::for('clip-trim', fn (Request $r) => Limit::perMinute(20)->by($perUser($r)));
    }
}

