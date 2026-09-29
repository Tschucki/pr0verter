<?php

declare(strict_types=1);

use App\Events\DownloadProgress;
use App\Models\Conversion;

it('converts the yt-dlp speed to megabytes per second', function (?string $speed, ?float $expected) {
    $conversion = Conversion::factory()->create();

    $event = new DownloadProgress($conversion->id, 'clip.mp4', '45.3%', '12.34MiB', $speed, '00:10');

    expect($event->speedInMegabytes)->toBe($expected);
})->with([
    'MiB/s' => ['1.23MiB/s', 1.29],
    'KiB/s' => ['512.00KiB/s', 0.52],
    'GiB/s' => ['1.00GiB/s', 1073.74],
    'bytes' => ['900.00B/s', 0.0],
    'unknown' => ['Unknown B/s', null],
    'missing' => [null, null],
]);
