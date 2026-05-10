<?php

declare(strict_types=1);

use App\Models\User;

it('redirects unauthenticated users', function (): void {
    $this->get(route('admin.weekly-report.preview'))
        ->assertRedirect(route('login'));
});

it('returns 403 for non-admin users', function (): void {
    $user = User::factory()->create(['admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.weekly-report.preview'))
        ->assertForbidden();
});

it('renders the weekly report blade for admins', function (): void {
    $user = User::factory()->create(['admin' => true]);

    $this->actingAs($user)
        ->get(route('admin.weekly-report.preview'))
        ->assertOk()
        ->assertSee('Wochenstatistik');
});
