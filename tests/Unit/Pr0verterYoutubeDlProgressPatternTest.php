<?php

declare(strict_types=1);

use App\Services\Pr0verterYoutubeDl;

function matchProgress(string $line): ?array
{
    return preg_match(Pr0verterYoutubeDl::PROGRESS_PATTERN, $line, $match) === 1 ? $match : null;
}

it('parses a regular progress line', function () {
    $match = matchProgress('[download]  45.3% of   12.34MiB at    1.23MiB/s ETA 00:10');

    expect($match['percentage'])->toBe('45.3%')
        ->and(trim($match['size']))->toBe('12.34MiB')
        ->and($match['speed'])->toBe('1.23MiB/s')
        ->and($match['eta'])->toBe('00:10');
});

it('parses progress lines with an estimated size from fragmented downloads', function () {
    $match = matchProgress('[download]   7.0% of ~  98.76MiB at  512.00KiB/s ETA 03:12 (frag 3/42)');

    expect($match['percentage'])->toBe('7.0%')
        ->and($match['size'])->toBe('~  98.76MiB')
        ->and($match['speed'])->toBe('512.00KiB/s')
        ->and($match['eta'])->toBe('03:12');
});

it('parses progress lines with an unknown speed', function () {
    $match = matchProgress('[download]   0.0% of   12.34MiB at  Unknown B/s ETA Unknown');

    expect($match['percentage'])->toBe('0.0%')
        ->and($match['speed'])->toBe('Unknown B/s')
        ->and($match['eta'])->toBe('Unknown');
});

it('parses the finished line', function () {
    $match = matchProgress('[download] 100% of   12.34MiB in 00:00:05 at 2.41MiB/s');

    expect($match['percentage'])->toBe('100%')
        ->and($match['totalTime'])->toBe('00:00:05');
});
