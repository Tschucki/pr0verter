<?php

declare(strict_types=1);

// Setup-Hinweis: Vor dem ersten Lauf einmalig Playwright-Browser installieren:
//   npx playwright install chromium

it('hides every other option while the raw download switch is on', function (): void {
    $page = visit(route('home'));

    $page->assertVisible('#rawDownload')
        ->assertVisible('#audio_only')
        ->assertVisible('#autoCrop')
        ->assertVisible('#maxSize');

    $page->click('#rawDownload');

    $page->assertMissing('#audio_only')
        ->assertMissing('#audio')
        ->assertMissing('#autoCrop')
        ->assertMissing('#maxSize')
        ->assertMissing('#watermark')
        ->assertMissing('#subtitleMode')
        ->assertVisible('#rawDownload');

    // Hidden, but still mounted: unmounting them drops their vee-validate
    // values, which is what broke the switch back on.
    $page->assertPresent('#audio_only')
        ->assertPresent('#maxSize');
});

it('restores every option after switching raw download off again', function (): void {
    $page = visit(route('home'));

    // Toggling on unmounted the fields before, which dropped their vee-validate
    // values — the audio_only-dependent options then stayed hidden forever.
    $page->click('#rawDownload')
        ->click('#rawDownload');

    $page->assertVisible('#audio_only')
        ->assertVisible('#audio')
        ->assertVisible('#audioQuality')
        ->assertVisible('#maxSize')
        ->assertVisible('#autoCrop')
        ->assertVisible('#watermark')
        ->assertVisible('#subtitleMode')
        ->assertVisible('#trimStart')
        ->assertVisible('#trimEnd');
});
