<?php

namespace App\Providers;

use App\Services\Pr0verterYoutubeDl;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
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
    }
}
