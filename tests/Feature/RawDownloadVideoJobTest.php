<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Enums\SubtitleMode;
use App\Jobs\ConversionJob;
use App\Jobs\DownloadVideoJob;
use App\Models\Conversion;
use App\Services\Pr0verterYoutubeDl;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use YoutubeDl\Entity\Video;
use YoutubeDl\Entity\VideoCollection;
use YoutubeDl\Options;

beforeEach(function (): void {
    // Ensure both the string alias and the concrete class resolve to the same mock.
    app()->alias(Pr0verterYoutubeDl::class, 'pr0verter-yt-dlp');

    Storage::fake('conversions');
    Session::start();
});

function rawUrlConversion(array $attributes = []): Conversion
{
    return Conversion::factory()->create([
        'session_id' => Session::getId(),
        'file_id' => null,
        'url' => 'https://example.com/video',
        'status' => ConversionStatus::PENDING,
        'raw_download' => true,
        'audio_only' => false,
        'audio_quality' => 1.0,
        ...$attributes,
    ]);
}

function fakeDownloadedVideo(string $filename = 'clip.mkv'): Video
{
    Storage::disk('conversions')->put($filename, 'downloaded-bytes');

    $video = Mockery::mock(Video::class);
    $video->shouldReceive('getFile')->andReturn(
        new SplFileInfo(Storage::disk('conversions')->path($filename))
    );
    $video->shouldReceive('getError')->andReturn(null);

    return $video;
}

it('asks yt-dlp for the best available quality and skips subtitles on raw downloads', function (): void {
    Bus::fake([ConversionJob::class]);

    // subtitle_mode is forced to none by ConversionSettings, but the job must
    // ignore it on its own too.
    $conversion = rawUrlConversion(['subtitle_mode' => SubtitleMode::Soft]);

    $video = fakeDownloadedVideo();
    $requestedFormat = null;

    $this->mock(Pr0verterYoutubeDl::class, function (MockInterface $mock) use ($video, &$requestedFormat): void {
        $mock->shouldReceive('onProgress')->andReturnSelf();
        $mock->shouldNotReceive('withExtraArgs');
        $mock->shouldNotReceive('resolveSubtitlePath');
        $mock->shouldReceive('download')
            ->once()
            ->withArgs(function (Options $options) use (&$requestedFormat): bool {
                $requestedFormat = $options->toArray()['format'] ?? null;

                return true;
            })
            ->andReturn(new VideoCollection([$video]));
        $mock->shouldReceive('getThumbnailPath')->andReturn(null);
    });

    DownloadVideoJob::dispatchSync($conversion->id);

    expect($requestedFormat)->toBe('bestvideo*+bestaudio/best');

    $conversion->refresh();

    expect($conversion->status)->toBe(ConversionStatus::PREPARING)
        ->and($conversion->file_id)->not->toBeNull()
        ->and($conversion->file->extension)->toBe('mkv')
        ->and($conversion->subtitle_status)->toBeNull()
        ->and($conversion->subtitle_path)->toBeNull();

    Bus::assertDispatched(
        ConversionJob::class,
        fn (ConversionJob $job): bool => $job->uniqueId() === $conversion->id,
    );
});

it('keeps forcing an mp4 container for regular conversions', function (): void {
    Bus::fake([ConversionJob::class]);

    $conversion = rawUrlConversion([
        'raw_download' => false,
        'subtitle_mode' => SubtitleMode::None,
    ]);

    $video = fakeDownloadedVideo('clip.mp4');
    $requestedFormat = null;

    $this->mock(Pr0verterYoutubeDl::class, function (MockInterface $mock) use ($video, &$requestedFormat): void {
        $mock->shouldReceive('onProgress')->andReturnSelf();
        $mock->shouldReceive('download')
            ->once()
            ->withArgs(function (Options $options) use (&$requestedFormat): bool {
                $requestedFormat = $options->toArray()['format'] ?? null;

                return true;
            })
            ->andReturn(new VideoCollection([$video]));
        $mock->shouldReceive('getThumbnailPath')->andReturn(null);
    });

    DownloadVideoJob::dispatchSync($conversion->id);

    expect($requestedFormat)
        ->toBe('bestvideo[ext=mp4]+bestaudio[ext=m4a]/bestvideo+bestaudio/best[ext=mp4]/best');
});
