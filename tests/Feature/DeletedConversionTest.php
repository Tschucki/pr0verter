<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Events\ConversionProgressEvent;
use App\Events\ConversionUpdated;
use App\Events\DownloadProgress;
use App\Models\Conversion;
use App\Models\Statistic;
use Illuminate\Support\Facades\DB;

// A running job keeps its model instance while the row can vanish: a new
// upload in the same session, the hourly cleanup or an expired session cascade.

it('does not broadcast download progress for a deleted conversion', function (): void {
    $conversion = Conversion::factory()->create();
    $conversion->delete();

    $event = new DownloadProgress($conversion->id, 'clip.mp4', '45.3%', '12.34MiB', '1.23MiB/s', '00:10');

    expect($event->sessionId)->toBeNull()
        ->and($event->broadcastWhen())->toBeFalse();
});

it('does not broadcast conversion progress for a deleted conversion', function (): void {
    $conversion = Conversion::factory()->create();
    $conversion->delete();

    $event = new ConversionProgressEvent($conversion->id, '10', '20', '1.0');

    expect($event->broadcastWhen())->toBeFalse();
});

it('does not broadcast an update for a deleted conversion', function (): void {
    $conversion = Conversion::factory()->create();
    $conversion->delete();

    $event = new ConversionUpdated($conversion->id);

    expect($event->conversion)->toBe([])
        ->and($event->broadcastWhen())->toBeFalse();
});

it('still broadcasts for an existing conversion', function (): void {
    $conversion = Conversion::factory()->create();

    $event = new ConversionUpdated($conversion->id);

    expect($event->sessionId)->toBe($conversion->session_id)
        ->and($event->conversion['id'])->toBe($conversion->id)
        ->and($event->broadcastWhen())->toBeTrue();
});

it('ignores updates on a conversion whose row is gone', function (): void {
    $conversion = Conversion::factory()->create();

    // Bypasses the model events like the session and file cascades do.
    DB::table('statistics')->where('conversion_id', $conversion->id)->delete();
    DB::table('conversions')->where('id', $conversion->id)->delete();

    $conversion->update([
        'status' => ConversionStatus::FAILED,
        'error_message' => 'boom',
    ]);

    expect(Statistic::where('conversion_id', $conversion->id)->exists())->toBeFalse();
});
