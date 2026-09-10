<?php

declare(strict_types=1);

use App\Conversion\MediaOperations\MaxSizeOperation;
use App\Enums\ConversionStatus;
use App\Jobs\ConversionJob;
use App\Models\Conversion;
use App\Models\File;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Event::fake();
    Storage::fake('conversions');
    Session::start();
});

/**
 * A real clip with both a video and an audio stream — audio_only has to strip
 * the video away, so a video-only sample would not exercise anything.
 */
function makeAudioVideoSample(string $filename, int $duration = 2): void
{
    $path = Storage::disk('conversions')->path($filename);
    @mkdir(dirname($path), 0755, true);

    $cmd = sprintf(
        'ffmpeg -f lavfi -i color=c=red:s=320x240:r=10:d=%d -f lavfi -i sine=frequency=440:duration=%d '
        . '-c:v libx264 -preset ultrafast -c:a aac -shortest -y %s 2>&1',
        $duration,
        $duration,
        escapeshellarg($path),
    );

    exec($cmd, $output, $code);

    expect($code)->toBe(0, 'ffmpeg sample failed: ' . implode("\n", $output));
}

function makeAudioOnlyConversion(): Conversion
{
    $sessionId = Session::getId();

    makeAudioVideoSample('clip.mp4');

    $file = File::factory()->create([
        'session_id' => $sessionId,
        'disk' => 'conversions',
        'filename' => 'clip.mp4',
        'extension' => 'mp4',
        'mime_type' => 'video/mp4',
        'size' => Storage::disk('conversions')->size('clip.mp4'),
    ]);

    return Conversion::factory()->create([
        'session_id' => $sessionId,
        'file_id' => $file->id,
        'status' => ConversionStatus::PENDING,
        'audio_only' => true,
        'audio' => true,
        'audio_quality' => 1.0,
    ]);
}

it('converts an uploaded video to mp3 when audio_only is set', function (): void {
    $conversion = makeAudioOnlyConversion();

    ConversionJob::dispatchSync($conversion->id);

    $conversion->refresh();

    expect($conversion->status)->toBe(ConversionStatus::FINISHED)
        ->and($conversion->error_message)->toBeNull()
        ->and($conversion->downloadable)->toBeTrue()
        ->and($conversion->file->extension)->toBe('mp3')
        ->and($conversion->file->filename)->toBe('clip.mp3');

    expect(Storage::disk('conversions')->exists('clip.mp3'))->toBeTrue()
        ->and(Storage::disk('conversions')->size('clip.mp3'))->toBeGreaterThan(0)
        ->and(Storage::disk('conversions')->exists('clip.mp4'))->toBeFalse();
});

it('applies the audio-only format operations to an Mp3 without a type error', function (): void {
    $conversion = Conversion::factory()->create([
        'audio_only' => true,
        'audio' => true,
        'audio_quality' => 1.0,
    ]);

    $format = new Mp3;

    foreach ($conversion->getFormatOperations() as $operation) {
        $format = $operation->applyToFormat($format);
    }

    expect($format)->toBeInstanceOf(Mp3::class)
        ->and($conversion->getMediaOperations())->toBe([]);
});

it('leaves an audio format untouched in the max size operation', function (): void {
    $conversion = makeAudioOnlyConversion();
    $conversion->update(['max_size' => 50]);

    // Bitrate targeting is video-only — an Mp3 has to pass straight through
    // instead of hitting setKiloBitrate().
    $format = new Mp3;
    $result = (new MaxSizeOperation($conversion->fresh()))->applyToFormat($format);

    expect($result)->toBe($format);
});

it('still builds media operations for regular conversions', function (): void {
    $conversion = Conversion::factory()->create([
        'audio_only' => false,
        'audio' => true,
        'audio_quality' => 1.0,
    ]);

    expect($conversion->getMediaOperations())->not->toBeEmpty();
});
