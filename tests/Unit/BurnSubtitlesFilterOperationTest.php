<?php

declare(strict_types=1);

use App\Conversion\MediaOperations\BurnSubtitlesFilterOperation;
use App\Models\Conversion;
use FFMpeg\Filters\Video\VideoFilters;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;
use Tests\TestCase;

uses(TestCase::class);

it('adds a subtitles filter via closure with absolute escaped path', function () {
    Storage::fake('conversions');

    $conversion = Mockery::mock(Conversion::class)->makePartial();
    $conversion->subtitle_path = 'subs/clip.de.srt';
    $conversion->file = (object) ['disk' => 'conversions'];
    $conversion->metadata = ['height' => 1080, 'width' => 1920];

    $capturedFilter = null;

    $media = Mockery::mock(MediaOpener::class);
    $media->shouldReceive('addFilter')
        ->once()
        ->with(Mockery::on(function ($arg) use (&$capturedFilter): bool {
            if (! is_callable($arg)) {
                return false;
            }
            $filters = Mockery::mock(VideoFilters::class);
            $filters->shouldReceive('custom')
                ->once()
                ->with(Mockery::on(function (string $expr) use (&$capturedFilter): bool {
                    $capturedFilter = $expr;

                    return str_starts_with($expr, 'subtitles=')
                        && str_contains($expr, 'FontSize=10')
                        && str_contains($expr, "force_style='FontName=Arial");
                }));
            $arg($filters);

            return true;
        }))
        ->andReturnSelf();

    $op = new BurnSubtitlesFilterOperation($conversion);
    $result = $op->applyToMedia($media);

    expect($result)->toBe($media)
        ->and($capturedFilter)->not->toBeNull();
});

it('escapes colons, backslashes and quotes in path', function () {
    Storage::fake('conversions');

    $conversion = Mockery::mock(Conversion::class)->makePartial();
    $conversion->subtitle_path = "weird:path/it's.srt";
    $conversion->file = (object) ['disk' => 'conversions'];

    $capturedFilter = null;

    $media = Mockery::mock(MediaOpener::class);
    $media->shouldReceive('addFilter')
        ->once()
        ->with(Mockery::on(function ($arg) use (&$capturedFilter): bool {
            $filters = Mockery::mock(VideoFilters::class);
            $filters->shouldReceive('custom')
                ->once()
                ->with(Mockery::on(function (string $expr) use (&$capturedFilter): bool {
                    $capturedFilter = $expr;

                    return true;
                }));
            $arg($filters);

            return true;
        }))
        ->andReturnSelf();

    $op = new BurnSubtitlesFilterOperation($conversion);
    $op->applyToMedia($media);

    expect($capturedFilter)
        ->toContain('\\:')
        ->toContain("\\'");
});

it('uses absolute subtitle paths without prefixing the disk root', function () {
    Storage::fake('conversions');

    $conversion = Mockery::mock(Conversion::class)->makePartial();
    $conversion->subtitle_path = '/var/www/html/storage/app/conversions/clip.de.srt';
    $conversion->file = (object) ['disk' => 'conversions'];

    $capturedFilter = null;

    $media = Mockery::mock(MediaOpener::class);
    $media->shouldReceive('addFilter')
        ->once()
        ->with(Mockery::on(function ($arg) use (&$capturedFilter): bool {
            $filters = Mockery::mock(VideoFilters::class);
            $filters->shouldReceive('custom')
                ->once()
                ->with(Mockery::on(function (string $expr) use (&$capturedFilter): bool {
                    $capturedFilter = $expr;

                    return true;
                }));
            $arg($filters);

            return true;
        }))
        ->andReturnSelf();

    $op = new BurnSubtitlesFilterOperation($conversion);
    $op->applyToMedia($media);

    expect($capturedFilter)
        ->toContain('/var/www/html/storage/app/conversions/clip.de.srt')
        ->not->toContain('/var/www/html/storage/app/conversions/var/www/html/storage/app/conversions');
});

it('scales font size based on video height', function () {
    Storage::fake('conversions');

    $conversion = Mockery::mock(Conversion::class)->makePartial();
    $conversion->subtitle_path = 'subs/clip.de.srt';
    $conversion->file = (object) ['disk' => 'conversions'];
    $conversion->metadata = ['height' => 720, 'width' => 1280];

    $capturedFilter = null;

    $media = Mockery::mock(MediaOpener::class);
    $media->shouldReceive('addFilter')
        ->once()
        ->with(Mockery::on(function ($arg) use (&$capturedFilter): bool {
            $filters = Mockery::mock(VideoFilters::class);
            $filters->shouldReceive('custom')
                ->once()
                ->with(Mockery::on(function (string $expr) use (&$capturedFilter): bool {
                    $capturedFilter = $expr;

                    return true;
                }));
            $arg($filters);

            return true;
        }))
        ->andReturnSelf();

    $op = new BurnSubtitlesFilterOperation($conversion);
    $op->applyToMedia($media);

    expect($capturedFilter)->toContain('FontSize=10');
});

it('scales font size based on the shorter video side', function () {
    Storage::fake('conversions');

    $conversion = Mockery::mock(Conversion::class)->makePartial();
    $conversion->subtitle_path = 'subs/clip.de.srt';
    $conversion->file = (object) ['disk' => 'conversions'];
    $conversion->metadata = ['height' => 1920, 'width' => 1080];

    $capturedFilter = null;

    $media = Mockery::mock(MediaOpener::class);
    $media->shouldReceive('addFilter')
        ->once()
        ->with(Mockery::on(function ($arg) use (&$capturedFilter): bool {
            $filters = Mockery::mock(VideoFilters::class);
            $filters->shouldReceive('custom')
                ->once()
                ->with(Mockery::on(function (string $expr) use (&$capturedFilter): bool {
                    $capturedFilter = $expr;

                    return true;
                }));
            $arg($filters);

            return true;
        }))
        ->andReturnSelf();

    $op = new BurnSubtitlesFilterOperation($conversion);
    $op->applyToMedia($media);

    expect($capturedFilter)->toContain('FontSize=6');
});
