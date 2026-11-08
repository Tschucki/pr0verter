<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Models\Conversion;
use Illuminate\Support\Str;

it('cancels a URL conversion that has no file yet', function (): void {
    $sessionId = (string) Str::random(40);
    $conversion = Conversion::factory()->create([
        'session_id' => $sessionId,
        'file_id' => null,
        'url' => 'https://example.com/video',
        'status' => ConversionStatus::DOWNLOADING,
    ]);

    $this->withCredentials()
        ->withCookie(config('session.cookie'), $sessionId)
        ->patchJson(route('conversions.cancel', $conversion))
        ->assertOk()
        ->assertJson(['status' => ConversionStatus::CANCELED->value]);

    expect($conversion->fresh()->status)->toBe(ConversionStatus::CANCELED);
});

it('refuses to cancel a conversion of another session', function (): void {
    $conversion = Conversion::factory()->create([
        'file_id' => null,
        'url' => 'https://example.com/video',
        'status' => ConversionStatus::DOWNLOADING,
    ]);

    $this->withCredentials()
        ->withCookie(config('session.cookie'), (string) Str::random(40))
        ->patchJson(route('conversions.cancel', $conversion))
        ->assertForbidden();

    expect($conversion->fresh()->status)->toBe(ConversionStatus::DOWNLOADING);
});

it('answers 404 when toggling the public flag of a conversion without file', function (): void {
    $sessionId = (string) Str::random(40);
    $conversion = Conversion::factory()->create([
        'session_id' => $sessionId,
        'file_id' => null,
        'url' => 'https://example.com/video',
        'status' => ConversionStatus::DOWNLOADING,
    ]);

    $this->withCredentials()
        ->withCookie(config('session.cookie'), $sessionId)
        ->patchJson(route('conversions.toggle-public', $conversion))
        ->assertNotFound();
});
