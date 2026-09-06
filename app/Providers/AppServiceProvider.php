<?php

namespace App\Providers;

use App\Services\Pr0verterYoutubeDl;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('pr0verter-yt-dlp', function () {
            return (new Pr0verterYoutubeDl)->setBinPath(config('converter.binaries.yt-dlp'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewPulse', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('admin', function (User $user) {
            return $user->isAdmin();
        });

        RateLimiter::for('weekly-report-render', function (Request $request) {
            return Limit::perMinute(5)->by((string) $request->user()?->id);
        });

        RateLimiter::for('monthly-report-render', function (Request $request) {
            return Limit::perMinute(5)->by((string) $request->user()?->id);
        });
    }
}
