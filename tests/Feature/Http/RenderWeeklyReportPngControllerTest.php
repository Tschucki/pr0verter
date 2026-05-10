<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\WeeklyReportRenderer;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

beforeEach(function (): void {
    RateLimiter::clear('weekly-report-render');
});

it('redirects unauthenticated users', function (): void {
    $this->post(route('admin.weekly-report.render-png'))
        ->assertRedirect(route('login'));
});

it('returns 403 for non-admin users', function (): void {
    $user = User::factory()->create(['admin' => false]);

    $this->actingAs($user)
        ->post(route('admin.weekly-report.render-png'))
        ->assertForbidden();
});

it('renders a PNG via the renderer for admins', function (): void {
    Storage::fake('local');
    $user = User::factory()->create(['admin' => true]);

    $this->mock(WeeklyReportRenderer::class, function (MockInterface $m): void {
        $m->shouldReceive('renderPng')
            ->once()
            ->andReturnUsing(function (string $html, string $path): void {
                file_put_contents($path, file_get_contents(__DIR__ . '/../../Fixtures/red.jpg'));
            });
    });

    $response = $this->actingAs($user)
        ->post(route('admin.weekly-report.render-png'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('image/png');
});
