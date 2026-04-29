<?php

declare(strict_types=1);

use App\Enums\SubtitleMode;
use App\Enums\SubtitleStatus;
use App\Jobs\DownloadVideoJob;
use App\Models\Conversion;
use App\Services\Pr0verterYoutubeDl;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use YoutubeDl\Entity\Video;
use YoutubeDl\Entity\VideoCollection;

beforeEach(function (): void {
    // Ensure both the string alias and the concrete class resolve to the same mock.
    app()->alias(Pr0verterYoutubeDl::class, 'pr0verter-yt-dlp');
});

it('persists subtitle_status=embedded when sub-path resolved (soft mode)', function () {
    Session::start();

    $conversion = Conversion::factory()->create([
        'session_id' => Session::getId(),
        'url' => 'https://example.com/video',
        'audio_only' => false,
        'subtitle_mode' => SubtitleMode::Soft,
        'audio' => true,
    ]);

    $videoFile = new SplFileInfo('/tmp/clip.mp4');
    $video = Mockery::mock(Video::class);
    $video->shouldReceive('getFile')->andReturn($videoFile);
    $video->shouldReceive('getError')->andReturn(null);

    $this->mock(Pr0verterYoutubeDl::class, function (MockInterface $mock) use ($video) {
        $mock->shouldReceive('onProgress')->andReturnSelf();
        $mock->shouldReceive('withExtraArgs')->andReturnSelf();
        $mock->shouldReceive('download')->andReturn(new VideoCollection([$video]));
        $mock->shouldReceive('resolveSubtitlePath')->andReturn('/tmp/clip.de.srt');
        $mock->shouldReceive('getThumbnailPath')->andReturn(null);
    });

    DownloadVideoJob::dispatchSync($conversion->id);

    $conversion->refresh();
    expect($conversion->subtitle_status)->toBe(SubtitleStatus::Embedded)
        ->and($conversion->subtitle_path)->toBe('/tmp/clip.de.srt');
});

it('persists subtitle_status=unavailable when no sub-path found (burn mode)', function () {
    Session::start();

    $conversion = Conversion::factory()->create([
        'session_id' => Session::getId(),
        'url' => 'https://example.com/video',
        'audio_only' => false,
        'subtitle_mode' => SubtitleMode::Burn,
        'audio' => true,
    ]);

    $videoFile = new SplFileInfo('/tmp/clip.mp4');
    $video = Mockery::mock(Video::class);
    $video->shouldReceive('getFile')->andReturn($videoFile);
    $video->shouldReceive('getError')->andReturn(null);

    $this->mock(Pr0verterYoutubeDl::class, function (MockInterface $mock) use ($video) {
        $mock->shouldReceive('onProgress')->andReturnSelf();
        $mock->shouldReceive('withExtraArgs')->andReturnSelf();
        $mock->shouldReceive('download')->andReturn(new VideoCollection([$video]));
        $mock->shouldReceive('resolveSubtitlePath')->andReturn(null);
        $mock->shouldReceive('getThumbnailPath')->andReturn(null);
    });

    DownloadVideoJob::dispatchSync($conversion->id);

    $conversion->refresh();
    expect($conversion->subtitle_status)->toBe(SubtitleStatus::Unavailable)
        ->and($conversion->subtitle_path)->toBeNull();
});

it('does not request subs when subtitle_mode is None', function () {
    Session::start();

    $conversion = Conversion::factory()->create([
        'session_id' => Session::getId(),
        'url' => 'https://example.com/video',
        'audio_only' => false,
        'subtitle_mode' => SubtitleMode::None,
        'audio' => true,
    ]);

    $videoFile = new SplFileInfo('/tmp/clip.mp4');
    $video = Mockery::mock(Video::class);
    $video->shouldReceive('getFile')->andReturn($videoFile);
    $video->shouldReceive('getError')->andReturn(null);

    $this->mock(Pr0verterYoutubeDl::class, function (MockInterface $mock) use ($video) {
        $mock->shouldReceive('onProgress')->andReturnSelf();
        $mock->shouldNotReceive('withExtraArgs');
        $mock->shouldNotReceive('resolveSubtitlePath');
        $mock->shouldReceive('download')->andReturn(new VideoCollection([$video]));
        $mock->shouldReceive('getThumbnailPath')->andReturn(null);
    });

    DownloadVideoJob::dispatchSync($conversion->id);

    $conversion->refresh();
    expect($conversion->subtitle_status)->toBeNull();
});
