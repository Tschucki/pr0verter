<?php

namespace App\Console\Commands;

use App\Enums\ConversionStatus;
use App\Models\Statistic;
use App\Services\Pr0PostService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Spatie\Browsershot\Browsershot;
use Spatie\Image\Image;
use Throwable;

class WeeklyReportCommand extends Command
{
    public const YOUTUBE_SYNONYMS = [
        'youtube.com',
        'www.youtube.com',
        'youtu.be',
        'm.youtube.com',
        'music.youtube.com',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
    ];

    protected $signature = 'app:generate-weekly-report';

    protected $description = 'Generates a image for the weekly report and posts it on pr0gramm';

    public function handle()
    {
        $from = now()->subDays(7);
        $to = now();
        $fileName = $from->format('Y-m-d-H') . '-to-' . $to->format('Y-m-d-H') . '-stats.png';
        $imagePath = Storage::disk('local')->path('weekly-reports/' . $fileName);

        Storage::disk('local')->makeDirectory('weekly-reports');

        $stats = $this->getStats($from, $to);

        Browsershot::html(view('weekly-report', [
            'statistics' => $stats,
            'from' => $from,
            'to' => $to,
            'generatedAt' => now(),
        ])->render())
            ->noSandbox()
            ->deviceScaleFactor(3)
            ->fullPage()
            ->disableCaptureURLs()
            ->preventUnsuccessfulResponse()
            ->delay(2000)
            ->hideBrowserHeaderAndFooter()
            ->setOption('args', [
                '--disable-web-security',
                '--waitForFonts',
            ])
            ->waitUntilNetworkIdle()
            ->windowSize(1052, 0)
            ->setNodeBinary(config('binaries.node'))
            ->setNpmBinary(config('binaries.npm'))
            ->save($imagePath);

        $image = Image::load($imagePath);
        $image->width(1052)->save();

        $pr0PostService = new Pr0PostService;

        $pr0PostService->postImage($imagePath,
            'Wöchentlicher Bericht vom ' . $from->format('d.m.Y') . ' bis ' . $to->format('d.m.Y') . ' | https://pr0verter.de | https://github.com/Tschucki/pr0verter',
            ['pr0verter', 'Statistiken', 'Wochenstatistik', 'das pr0 programmiert', 'sfw', 'image', 'api']
        );
    }

    protected function getStats($from, $to)
    {
        $newBaseQuery = function () use ($from, $to) {
            return Statistic::whereBetween('created_at', [$from, $to])
                ->where('status', ConversionStatus::FINISHED);
        };

        $allUrls = $newBaseQuery()->whereNotNull('url')->pluck('url');

        $urls = null;

        try {
            $urls = $allUrls->groupBy(static function ($url) {
                return parse_url($url, PHP_URL_HOST);
            });

            $urlsWithCounts = $urls->map(fn ($item) => $item->count());

            $synonyms = self::YOUTUBE_SYNONYMS;

            $youtubeCount = 0;
            foreach ($synonyms as $synonym) {
                if (isset($urlsWithCounts[$synonym])) {
                    $youtubeCount += $urlsWithCounts[$synonym];
                    unset($urlsWithCounts[$synonym]);
                }
            }

            if ($youtubeCount > 0) {
                $urlsWithCounts->put('youtube.com', $youtubeCount);
            }

            $urls = $urlsWithCounts->sortDesc();
        } catch (Throwable $th) {
            Log::error('Could not group urls by domain', ['exception' => $th]);
        }

        return [
            'total_conversions' => $newBaseQuery()->count(),
            'favorite_url' => $urls->keys()->first() ?? '/',
            'traffic' => Number::fileSize((int) $newBaseQuery()->sum('size'), 2),
            'most_used_input_extension' => $newBaseQuery()->select('extension')
                ->groupBy('extension')
                ->orderByRaw('COUNT(extension) DESC')
                ->first()?->extension,
            'average_conversion_time' => Number::format((int) $newBaseQuery()->avg('conversion_time'), 0, locale: 'de-DE') . ' Sekunden',
            'favorite_time_to_convert' => $newBaseQuery()->selectRaw('HOUR(created_at) as hour, COUNT(id) as count')
                ->groupBy('hour')
                ->orderByRaw('COUNT(id) DESC')
                ->first()?->hour . ' Uhr',
            'added_watermarks' => $newBaseQuery()->where('watermark', true)->count(),
            'auto_copped' => $newBaseQuery()->where('auto_crop', true)->count(),
            'trimmed' => $newBaseQuery()->where(function (Builder $query) {
                $query->whereNotNull('trim_start')->orWhereNotNull('trim_end');
            })->count(),
            'removed_audio' => $newBaseQuery()->where('audio', false)->count(),
            'audio_only' => $newBaseQuery()->where('audio_only', true)->count(),
            'segmented' => $newBaseQuery()->whereJsonLength('segments', '>', 0)->count(),
        ];
    }
}
