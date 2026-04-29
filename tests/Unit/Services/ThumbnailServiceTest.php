<?php

declare(strict_types=1);

use App\Models\Conversion;
use App\Models\File;
use App\Services\ThumbnailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class, RefreshDatabase::class);

function makeSampleVideo(string $disk, string $filename, int $duration = 2): void
{
    $path = Storage::disk($disk)->path($filename);
    @mkdir(dirname($path), 0755, true);
    $cmd = sprintf(
        'ffmpeg -f lavfi -i color=c=red:s=320x240:r=10:d=%d -c:v libx264 -preset ultrafast -y %s 2>&1',
        $duration,
        escapeshellarg($path),
    );
    exec($cmd, $output, $code);
    expect($code)->toBe(0, 'ffmpeg sample failed: ' . implode("\n", $output));
}

function makeConversion(): Conversion
{
    return Conversion::factory()->create();
}

beforeEach(function (): void {
    Event::fake();
    Storage::fake('conversions');
    Storage::fake('local');
});

it('delete removes file when path set', function (): void {
    $conversion = makeConversion();
    Storage::disk('conversions')->put('thumbnails/' . $conversion->id . '.jpg', 'fake-bytes');
    $conversion->thumbnail_path = 'thumbnails/' . $conversion->id . '.jpg';
    $conversion->save();

    expect(Storage::disk('conversions')->exists($conversion->thumbnail_path))->toBeTrue();

    app(ThumbnailService::class)->delete($conversion);

    expect(Storage::disk('conversions')->exists('thumbnails/' . $conversion->id . '.jpg'))->toBeFalse();
});

it('delete is no-op when path null', function (): void {
    $conversion = makeConversion();
    expect($conversion->thumbnail_path)->toBeNull();

    app(ThumbnailService::class)->delete($conversion);

    expect(Storage::disk('conversions')->files('thumbnails'))->toBeEmpty();
});

it('captureSourceFrame writes a JPG', function (): void {
    $conversion = makeConversion();
    $file = File::factory()->create([
        'disk' => 'conversions',
        'filename' => 'sources/sample-' . $conversion->id . '.mp4',
    ]);
    $conversion->setRelation('file', $file);

    makeSampleVideo('conversions', $file->filename, 2);

    $relative = app(ThumbnailService::class)->captureSourceFrame($conversion, 2.0);

    expect($relative)->toBe('thumbnails/' . $conversion->id . '.jpg');
    expect(Storage::disk('conversions')->exists($relative))->toBeTrue();

    $absolute = Storage::disk('conversions')->path($relative);
    $bytes = file_get_contents($absolute);
    expect(substr($bytes, 0, 3))->toBe("\xFF\xD8\xFF");

    $conversion->refresh();
    expect($conversion->thumbnail_path)->toBe($relative);
});

it('captureSourceFrame returns null and logs warning on missing source', function (): void {
    $conversion = makeConversion();
    $file = File::factory()->create([
        'disk' => 'conversions',
        'filename' => 'sources/does-not-exist-' . $conversion->id . '.mp4',
    ]);
    $conversion->setRelation('file', $file);

    Log::shouldReceive('warning')->once();

    $result = app(ThumbnailService::class)->captureSourceFrame($conversion, 2.0);

    expect($result)->toBeNull();
});

it('captureOutputFrame overwrites the source thumbnail at the same path', function (): void {
    $conversion = makeConversion();
    $sourceFile = File::factory()->create([
        'disk' => 'conversions',
        'filename' => 'sources/source-' . $conversion->id . '.mp4',
    ]);
    $conversion->setRelation('file', $sourceFile);

    // Build a source video and an output video (different content).
    makeSampleVideo('conversions', $sourceFile->filename, 2);

    $outputFilename = 'outputs/output-' . $conversion->id . '.mp4';
    $outputPath = Storage::disk('conversions')->path($outputFilename);
    @mkdir(dirname($outputPath), 0755, true);
    $cmd = sprintf(
        'ffmpeg -f lavfi -i color=c=blue:s=320x240:r=10:d=2 -c:v libx264 -preset ultrafast -y %s 2>&1',
        escapeshellarg($outputPath),
    );
    exec($cmd, $output, $code);
    expect($code)->toBe(0, 'ffmpeg blue sample failed: ' . implode("\n", $output));

    $service = app(ThumbnailService::class);

    $relative = $service->captureSourceFrame($conversion, 2.0);
    expect($relative)->toBe('thumbnails/' . $conversion->id . '.jpg');

    $beforeMd5 = md5_file(Storage::disk('conversions')->path($relative));

    $relative2 = $service->captureOutputFrame($conversion, 'conversions', $outputFilename, 2.0);
    expect($relative2)->toBe($relative);

    $afterMd5 = md5_file(Storage::disk('conversions')->path($relative));
    expect($afterMd5)->not->toBe($beforeMd5);
});

it('storeFromYoutubeDl adopts an existing JPG and resizes it', function (): void {
    $conversion = makeConversion();

    // Create a 1280x720 JPG on the faked local disk.
    $localFakeRoot = storage_path('framework/testing/disks/local');
    $sourceJpgRelative = 'youtube-dl/big-' . $conversion->id . '.jpg';
    $sourceJpg = $localFakeRoot . '/' . $sourceJpgRelative;
    @mkdir(dirname($sourceJpg), 0755, true);

    $img = imagecreatetruecolor(1280, 720);
    $color = imagecolorallocate($img, 200, 50, 50);
    imagefilledrectangle($img, 0, 0, 1280, 720, $color);
    imagejpeg($img, $sourceJpg, 90);
    imagedestroy($img);

    expect(file_exists($sourceJpg))->toBeTrue();

    $relative = app(ThumbnailService::class)->storeFromYoutubeDl($conversion, $sourceJpg);

    expect($relative)->toBe('thumbnails/' . $conversion->id . '.jpg');
    expect(Storage::disk('conversions')->exists($relative))->toBeTrue();

    $absolute = Storage::disk('conversions')->path($relative);
    [$w, $h] = getimagesize($absolute);
    expect(max($w, $h))->toBeLessThanOrEqual(480);

    expect(file_exists($sourceJpg))->toBeFalse();

    $conversion->refresh();
    expect($conversion->thumbnail_path)->toBe($relative);
});

it('storeFromYoutubeDl returns null and logs warning on missing source path', function (): void {
    $conversion = makeConversion();

    Log::shouldReceive('warning')->once();

    $result = app(ThumbnailService::class)->storeFromYoutubeDl($conversion, '/tmp/does-not-exist-' . uniqid() . '.jpg');

    expect($result)->toBeNull();
});
