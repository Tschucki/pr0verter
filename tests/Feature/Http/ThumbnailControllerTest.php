<?php

declare(strict_types=1);

use App\Events\ConversionFinished;
use App\Events\ConversionUpdated;
use App\Models\Conversion;
use App\Models\File;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Event::fake([ConversionUpdated::class, ConversionFinished::class]);
    Storage::fake('conversions');
});

it('returns 404 when thumbnail_path is null', function (): void {
    $conversion = Conversion::factory()->create(['thumbnail_path' => null]);

    $this->get(route('conversions.thumbnail', $conversion))
        ->assertNotFound();
});

it('returns 404 when thumbnail file is missing on disk', function (): void {
    $file = File::factory()->create(['public' => true]);
    $conversion = Conversion::factory()->create([
        'file_id' => $file->id,
        'thumbnail_path' => 'thumbnails/missing.jpg',
    ]);

    $this->get(route('conversions.thumbnail', $conversion))
        ->assertNotFound();
});

it('serves the jpg with public cache header for public files', function (): void {
    $file = File::factory()->create(['public' => true]);
    $conversion = Conversion::factory()->create([
        'file_id' => $file->id,
        'thumbnail_path' => 'thumbnails/pub.jpg',
    ]);
    Storage::disk('conversions')->put(
        'thumbnails/pub.jpg',
        file_get_contents(__DIR__ . '/../../Fixtures/red.jpg')
    );

    $response = $this->get(route('conversions.thumbnail', $conversion));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('image/jpeg');
    expect($response->headers->get('Cache-Control'))->toContain('public');
    expect($response->headers->get('Cache-Control'))->toContain('max-age=86400');
});

it('returns 403 when file is non-public and session does not match', function (): void {
    $file = File::factory()->create(['public' => false, 'session_id' => 'other-session']);
    $conversion = Conversion::factory()->create([
        'file_id' => $file->id,
        'thumbnail_path' => 'thumbnails/priv.jpg',
    ]);
    Storage::disk('conversions')->put('thumbnails/priv.jpg', 'bytes');

    $this->get(route('conversions.thumbnail', $conversion))
        ->assertForbidden();
});

it('serves the jpg with private cache header for own session', function (): void {
    $sessionId = (string) Str::random(40);

    $file = File::factory()->create(['public' => false, 'session_id' => $sessionId]);
    $conversion = Conversion::factory()->create([
        'file_id' => $file->id,
        'thumbnail_path' => 'thumbnails/own.jpg',
    ]);
    Storage::disk('conversions')->put(
        'thumbnails/own.jpg',
        file_get_contents(__DIR__ . '/../../Fixtures/red.jpg')
    );

    $response = $this->withCookie(
        config('session.cookie'),
        $sessionId,
    )->get(route('conversions.thumbnail', $conversion));

    $response->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('private');
    expect($response->headers->get('Cache-Control'))->toContain('max-age=300');
});
