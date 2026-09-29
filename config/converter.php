<?php

declare(strict_types=1);

return [
    'default_operations' => [
        App\Conversion\MediaOperations\RemoveExifDataFilterOperation::class,
    ],
    'default_format_operations' => [

    ],
    'binaries' => [
        'ffmpeg' => config('laravel-ffmpeg.ffmpeg.binaries'),
        'ffprobe' => config('laravel-ffmpeg.ffprobe.binaries'),
        'yt-dlp' => env('YT_DLP_PATH', 'yt-dlp'),
    ],
    'download' => [
        // Maximum runtime of a yt-dlp download in seconds.
        'timeout' => (int) env('DOWNLOAD_TIMEOUT', 600),
    ],
    'cookies' => [
        'file' => storage_path('app/youtube-cookie/cookies.txt'),
    ],
];
