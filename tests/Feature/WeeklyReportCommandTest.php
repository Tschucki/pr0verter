<?php

declare(strict_types=1);

use App\Console\Commands\WeeklyReportCommand;
use App\Models\Statistic;
use App\Services\Pr0PostService;
use App\Services\WeeklyReportRenderer;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

it('delegates rendering to WeeklyReportRenderer and posts via Pr0PostService', function (): void {
    Storage::fake('local');

    Statistic::factory()->count(3)->create([
        'created_at' => now()->subDays(2),
        'extension' => 'mp4',
    ]);

    $renderer = $this->mock(WeeklyReportRenderer::class, function (MockInterface $m): void {
        $m->shouldReceive('renderPng')
            ->once()
            ->withArgs(function (string $html, string $path): bool {
                return str_contains($path, 'weekly-reports/')
                    && str_ends_with($path, '-stats.png');
            });
    });

    $poster = $this->mock(Pr0PostService::class, function (MockInterface $m): void {
        $m->shouldReceive('postImage')->once();
    });

    $exitCode = $this->artisan(WeeklyReportCommand::class)->run();

    expect($exitCode)->toBe(0);
});

it('skips post when there are zero conversions in the current week', function (): void {
    // Keine Statistics in der aktuellen Woche

    $renderer = $this->mock(WeeklyReportRenderer::class, function (MockInterface $m): void {
        $m->shouldNotReceive('renderPng');
    });

    $poster = $this->mock(Pr0PostService::class, function (MockInterface $m): void {
        $m->shouldNotReceive('postImage');
    });

    $exitCode = $this->artisan(WeeklyReportCommand::class)->run();

    expect($exitCode)->toBe(0);
});

it('returns failure exit code when renderer throws', function (): void {
    Statistic::factory()->count(3)->create(['created_at' => now()->subDays(2)]);

    $this->mock(WeeklyReportRenderer::class, function (MockInterface $m): void {
        $m->shouldReceive('renderPng')->andThrow(new \RuntimeException('chromium gone'));
    });

    $this->mock(Pr0PostService::class, function (MockInterface $m): void {
        $m->shouldNotReceive('postImage');
    });

    $exitCode = $this->artisan(WeeklyReportCommand::class)->run();

    expect($exitCode)->toBe(1);
});

it('returns failure exit code when poster throws but keeps the PNG', function (): void {
    Storage::fake('local');
    Statistic::factory()->count(3)->create(['created_at' => now()->subDays(2)]);

    $this->mock(WeeklyReportRenderer::class, function (MockInterface $m): void {
        $m->shouldReceive('renderPng')->once()->andReturnUsing(function (string $html, string $path): void {
            file_put_contents($path, 'fake-png-bytes');
        });
    });

    $this->mock(Pr0PostService::class, function (MockInterface $m): void {
        $m->shouldReceive('postImage')->andThrow(new \RuntimeException('pr0gramm api down'));
    });

    $exitCode = $this->artisan(WeeklyReportCommand::class)->run();

    expect($exitCode)->toBe(1);
    // PNG bleibt im Storage liegen
    $files = Storage::disk('local')->files('weekly-reports');
    expect($files)->toHaveCount(1);
});
