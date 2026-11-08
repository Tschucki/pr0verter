<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Enums\SubtitleMode;
use App\Jobs\ConversionJob;
use App\Jobs\DownloadVideoJob;
use App\Models\Conversion;
use App\Services\Pr0verterYoutubeDl;
use App\Services\VideoAnalysisService;
use FFMpeg\Exception\RuntimeException as FFMpegRuntimeException;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use YoutubeDl\Entity\Video;
use YoutubeDl\Entity\VideoCollection;

beforeEach(function (): void {
    app()->alias(Pr0verterYoutubeDl::class, 'pr0verter-yt-dlp');

    Storage::fake('conversions');
});

function pendingUrlConversion(array $attributes = []): Conversion
{
    return Conversion::factory()->create([
        'file_id' => null,
        'url' => 'https://example.com/video',
        'status' => ConversionStatus::PENDING,
        'subtitle_mode' => SubtitleMode::None,
        'audio_quality' => 1.0,
        ...$attributes,
    ]);
}

it('queues downloads on the downloader queue', function (): void {
    Bus::fake([DownloadVideoJob::class]);

    $conversion = pendingUrlConversion();

    Bus::assertDispatched(
        DownloadVideoJob::class,
        fn (DownloadVideoJob $job): bool => $job->conversionId === $conversion->id && $job->queue === 'downloader',
    );
});

it('skips a download that was canceled while it waited in the queue', function (): void {
    Bus::fake([DownloadVideoJob::class, ConversionJob::class]);

    $conversion = pendingUrlConversion();
    $conversion->update(['status' => ConversionStatus::CANCELED]);

    $this->mock(Pr0verterYoutubeDl::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('download');
    });

    (new DownloadVideoJob($conversion->id))->handle();

    expect($conversion->fresh()->status)->toBe(ConversionStatus::CANCELED);
    Bus::assertNotDispatched(ConversionJob::class);
});

it('aborts a running download once the conversion is canceled', function (): void {
    Bus::fake([DownloadVideoJob::class, ConversionJob::class]);

    $conversion = pendingUrlConversion();
    $progress = null;

    $this->mock(Pr0verterYoutubeDl::class, function (MockInterface $mock) use (&$progress, $conversion): void {
        $mock->shouldReceive('onProgress')->andReturnUsing(function (Closure $callback) use (&$progress, $mock) {
            $progress = $callback;

            return $mock;
        });
        $mock->shouldReceive('download')->once()->andReturnUsing(function () use (&$progress, $conversion) {
            Conversion::whereKey($conversion->id)->update(['status' => ConversionStatus::CANCELED]);

            $progress('clip.mp4', '10.0', '1.00MiB', '1.00MiB/s', '00:10');

            return new VideoCollection([Mockery::mock(Video::class)]);
        });
    });

    (new DownloadVideoJob($conversion->id))->handle();

    expect($conversion->fresh())
        ->status->toBe(ConversionStatus::CANCELED)
        ->file_id->toBeNull();
    Bus::assertNotDispatched(ConversionJob::class);
});

it('stops quietly when the conversion is deleted during the download', function (): void {
    Bus::fake([DownloadVideoJob::class, ConversionJob::class]);

    $conversion = pendingUrlConversion();
    $progress = null;

    $this->mock(Pr0verterYoutubeDl::class, function (MockInterface $mock) use (&$progress, $conversion): void {
        $mock->shouldReceive('onProgress')->andReturnUsing(function (Closure $callback) use (&$progress, $mock) {
            $progress = $callback;

            return $mock;
        });
        $mock->shouldReceive('download')->once()->andReturnUsing(function () use (&$progress, $conversion) {
            $conversion->delete();

            $progress('clip.mp4', '10.0', '1.00MiB', '1.00MiB/s', '00:10');

            return new VideoCollection([Mockery::mock(Video::class)]);
        });
    });

    (new DownloadVideoJob($conversion->id))->handle();

    expect(Conversion::find($conversion->id))->toBeNull();
    Bus::assertNotDispatched(ConversionJob::class);
});

it('marks a download as failed when the worker gives up on it', function (): void {
    Bus::fake([DownloadVideoJob::class]);

    $conversion = pendingUrlConversion(['status' => ConversionStatus::DOWNLOADING]);

    (new DownloadVideoJob($conversion->id))->failed(new MaxAttemptsExceededException('too many attempts'));

    expect($conversion->fresh())
        ->status->toBe(ConversionStatus::FAILED)
        ->error_message->not->toBeNull();
});

it('leaves a canceled download alone when the worker gives up on it', function (): void {
    Bus::fake([DownloadVideoJob::class]);

    $conversion = pendingUrlConversion(['status' => ConversionStatus::CANCELED]);

    (new DownloadVideoJob($conversion->id))->failed(new MaxAttemptsExceededException('too many attempts'));

    expect($conversion->fresh()->status)->toBe(ConversionStatus::CANCELED);
});

it('fails the conversion with a readable message when the file cannot be probed', function (): void {
    $conversion = Conversion::factory()->create([
        'status' => ConversionStatus::PENDING,
        'audio_quality' => 1.0,
    ]);

    $this->mock(VideoAnalysisService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('analyze')->once()->andThrow(new FFMpegRuntimeException('Unable to probe broken.mp4'));
    });

    (new ConversionJob($conversion->id))->handle();

    expect($conversion->fresh())
        ->status->toBe(ConversionStatus::FAILED)
        ->error_message->toBe('Die Datei ist beschädigt oder kein gültiges Video.');
});

it('keeps a conversion canceled while it waited in the queue', function (): void {
    $conversion = Conversion::factory()->create([
        'status' => ConversionStatus::CANCELED,
        'audio_quality' => 1.0,
    ]);

    $this->mock(VideoAnalysisService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('analyze');
    });

    (new ConversionJob($conversion->id))->handle();

    expect($conversion->fresh()->status)->toBe(ConversionStatus::CANCELED);
});

it('marks a conversion as failed when the worker gives up on it', function (): void {
    $conversion = Conversion::factory()->create([
        'status' => ConversionStatus::PROCESSING,
        'audio_quality' => 1.0,
    ]);

    (new ConversionJob($conversion->id))->failed(new MaxAttemptsExceededException('too many attempts'));

    expect($conversion->fresh())
        ->status->toBe(ConversionStatus::FAILED)
        ->error_message->not->toBeNull();
});

it('does not touch a finished conversion when the worker gives up afterwards', function (): void {
    $conversion = Conversion::factory()->create([
        'status' => ConversionStatus::FINISHED,
        'audio_quality' => 1.0,
    ]);

    (new ConversionJob($conversion->id))->failed(new MaxAttemptsExceededException('too many attempts'));

    expect($conversion->fresh()->status)->toBe(ConversionStatus::FINISHED);
});
