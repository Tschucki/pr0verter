<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use YoutubeDl\YoutubeDl;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(YoutubeDl::class, function () {
            return (new YoutubeDl)->setBinPath(config('converter.binaries.yt-dlp'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
