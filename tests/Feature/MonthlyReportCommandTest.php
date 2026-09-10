<?php

declare(strict_types=1);

use App\Console\Commands\MonthlyReportCommand;
use App\Models\Statistic;
use App\Services\Pr0PostService;
use App\Services\ReportRenderer;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

it('delegates rendering to ReportRenderer and posts via Pr0PostService', function (): void {
    Storage::fake('local');

    Statistic::factory()->count(3)->create([
        'created_at' => now()->startOfMonth()->addHours(6),
        'extension' => 'mp4',
    ]);

    $this->mock(ReportRenderer::class, function (MockInterface $m): void {
        $m->shouldReceive('renderPng')
            ->once()
            ->withArgs(function (string $html, string $path): bool {
                return str_contains($path, 'monthly-reports/')
                    && str_ends_with($path, '-monthly-stats.png');
            });
    });

    $this->mock(Pr0PostService::class, function (MockInterface $m): void {
        $m->shouldReceive('postImage')->once();
    });

    $exitCode = $this->artisan(MonthlyReportCommand::class)->run();

    expect($exitCode)->toBe(0);
});

it('skips post when there are zero conversions in the current month', function (): void {
    Storage::fake('local');

    // Nur Daten aus dem Vormonat — der aktuelle Monat bleibt leer.
    Statistic::factory()->count(2)->create([
        'created_at' => now()->startOfMonth()->subDays(3),
    ]);

    $this->mock(ReportRenderer::class, function (MockInterface $m): void {
        $m->shouldNotReceive('renderPng');
    });

    $this->mock(Pr0PostService::class, function (MockInterface $m): void {
        $m->shouldNotReceive('postImage');
    });

    $exitCode = $this->artisan(MonthlyReportCommand::class)->run();

    expect($exitCode)->toBe(0);
});

it('returns failure exit code when the renderer throws', function (): void {
    Storage::fake('local');

    Statistic::factory()->create(['created_at' => now()->startOfMonth()->addHours(6)]);

    $this->mock(ReportRenderer::class, function (MockInterface $m): void {
        $m->shouldReceive('renderPng')->once()->andThrow(new RuntimeException('chromium missing'));
    });

    $this->mock(Pr0PostService::class, function (MockInterface $m): void {
        $m->shouldNotReceive('postImage');
    });

    $exitCode = $this->artisan(MonthlyReportCommand::class)->run();

    expect($exitCode)->toBe(1);
});

it('returns failure exit code when posting throws but keeps the PNG', function (): void {
    Storage::fake('local');

    Statistic::factory()->create(['created_at' => now()->startOfMonth()->addHours(6)]);

    $renderedPath = null;

    $this->mock(ReportRenderer::class, function (MockInterface $m) use (&$renderedPath): void {
        $m->shouldReceive('renderPng')
            ->once()
            ->andReturnUsing(function (string $html, string $path) use (&$renderedPath): void {
                $renderedPath = $path;
                file_put_contents($path, 'png-bytes');
            });
    });

    $this->mock(Pr0PostService::class, function (MockInterface $m): void {
        $m->shouldReceive('postImage')->once()->andThrow(new RuntimeException('pr0gramm down'));
    });

    $exitCode = $this->artisan(MonthlyReportCommand::class)->run();

    expect($exitCode)->toBe(1)
        ->and($renderedPath)->not->toBeNull()
        ->and(file_exists($renderedPath))->toBeTrue();
});
