<?php

declare(strict_types=1);

// Setup-Hinweis: Vor dem ersten Lauf einmalig im App-Container Playwright-Browser installieren:
//   sail npx playwright install chromium
// Das postinstall-Hook von Playwright lädt den Browser üblicherweise im Rahmen von
// `sail npm install` herunter (PLAYWRIGHT_BROWSERS_PATH=0 platziert ihn in
// node_modules/playwright-core/.local-browsers/). Falls Pest mit "Could not find
// browser binary" o. ä. abbricht, ist obiger Befehl der Ein-Befehl-Fix.

use App\Models\User;

it('renders the weekly report preview page for admin users', function (): void {
    $admin = User::factory()->create(['admin' => true]);

    $this->actingAs($admin);

    $page = visit(route('admin.weekly-report.preview'));

    $page->assertSee('Wochenstatistik')
        ->assertSee('Konvertierungen')
        ->assertSee('Traffic')
        ->assertSee('Top-Quellen');
});

it('shows the preview toolbar with the render-png button', function (): void {
    $admin = User::factory()->create(['admin' => true]);

    $this->actingAs($admin);

    $page = visit(route('admin.weekly-report.preview'));

    $page->assertVisible('[data-render-png]')
        ->assertSee('Als PNG rendern')
        ->assertButtonEnabled('Als PNG rendern');
});

it('redirects unauthenticated visitors away from the preview', function (): void {
    $page = visit(route('admin.weekly-report.preview'));

    $page->assertPathIs('/login');
});
