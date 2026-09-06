<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;

it('schedules the monthly report on the last day of the month at 19:30 Berlin time', function (): void {
    // withSchedule() hängt an Artisan::starting — die Konsolen-Application muss
    // also erst existieren, bevor der Schedule seine Events kennt.
    app(Kernel::class)->all();

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'app:generate-monthly-report'));

    expect($event)->not->toBeNull();

    // Laravel splices the current month's last day into the expression on every
    // schedule:run, so February resolves to 28 and January to 31.
    expect($event->expression)->toBe('30 19 ' . now()->endOfMonth()->day . ' * *')
        ->and((string) $event->timezone)->toBe('Europe/Berlin');
});
