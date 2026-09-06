<?php

declare(strict_types=1);

use App\Models\User;

it('renders the monthly report preview page for admin users', function (): void {
    $admin = User::factory()->create(['admin' => true]);

    $this->actingAs($admin);

    $page = visit(route('admin.monthly-report.preview'));

    $page->assertSee('Monatsstatistik')
        ->assertSee('Konvertierungen')
        ->assertSee('Traffic')
        ->assertSee('Top-Quellen');
});

it('shows the preview toolbar with the render-png button', function (): void {
    $admin = User::factory()->create(['admin' => true]);

    $this->actingAs($admin);

    $page = visit(route('admin.monthly-report.preview'));

    $page->assertVisible('[data-render-png]')
        ->assertSee('Als PNG rendern')
        ->assertButtonEnabled('Als PNG rendern');
});
