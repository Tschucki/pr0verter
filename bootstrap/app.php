<?php

declare(strict_types=1);

use App\Console\Commands\WeeklyReportCommand;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'logout',
            'livewire/update',
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('app:cleanup')->hourly();
        $schedule->command(WeeklyReportCommand::class)->sundays()->at('19:30')->timezone('Europe/Berlin');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
