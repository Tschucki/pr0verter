<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConversionStatus;
use App\Models\Statistic;
use App\ValueObjects\ReportStats;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Shared aggregation for the periodic pr0verter reports. The weekly and the
 * monthly report differ only in the range they ask for and how they label it.
 */
abstract class ReportStatsService
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

    public function statsForRange(CarbonInterface $from, CarbonInterface $to): ReportStats
    {
        $base = fn () => Statistic::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', ConversionStatus::FINISHED);

        $totalConversions = $base()->count();
        $traffic = (int) $base()->sum('size');
        $averageConversionTime = (int) $base()->avg('conversion_time');

        $mostUsedInputExtension = $base()
            ->select('extension')
            ->groupBy('extension')
            ->orderByRaw('COUNT(extension) DESC')
            ->first()
            ?->extension;

        return new ReportStats(
            totalConversions: $totalConversions,
            traffic: $traffic,
            mostUsedInputExtension: $mostUsedInputExtension,
            averageConversionTime: $averageConversionTime,
            addedWatermarks: $base()->where('watermark', true)->count(),
            autoCropped: $base()->where('auto_crop', true)->count(),
            trimmed: $base()->where(function (Builder $q): void {
                $q->whereNotNull('trim_start')->orWhereNotNull('trim_end');
            })->count(),
            removedAudio: $base()->where('audio', false)->count(),
            audioOnly: $base()->where('audio_only', true)->count(),
            segmented: $base()->whereJsonLength('segments', '>', 0)->count(),
        );
    }

    /**
     * @return array<string, ?float>
     */
    protected function computeDeltas(ReportStats $current, ReportStats $previous): array
    {
        $keys = [
            'total_conversions' => [$current->totalConversions, $previous->totalConversions],
            'traffic' => [$current->traffic, $previous->traffic],
            'average_conversion_time' => [$current->averageConversionTime, $previous->averageConversionTime],
            'added_watermarks' => [$current->addedWatermarks, $previous->addedWatermarks],
            'auto_cropped' => [$current->autoCropped, $previous->autoCropped],
            'trimmed' => [$current->trimmed, $previous->trimmed],
            'removed_audio' => [$current->removedAudio, $previous->removedAudio],
            'audio_only' => [$current->audioOnly, $previous->audioOnly],
            'segmented' => [$current->segmented, $previous->segmented],
        ];

        $result = [];
        foreach ($keys as $key => [$cur, $prev]) {
            if ($prev === 0) {
                $result[$key] = null;

                continue;
            }
            $result[$key] = round((($cur - $prev) / $prev) * 100, 1);
        }

        return $result;
    }

    /**
     * One bucket per calendar day, starting at $from, $days buckets long — so a
     * week gets 7 and a month gets its own 28 to 31.
     *
     * @return array<int, array{day: string, count: int}>
     */
    protected function dailySeries(CarbonInterface $from, int $days): array
    {
        $to = $from->copy()->addDays($days);

        $rows = Statistic::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', ConversionStatus::FINISHED)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as c')
            ->groupBy('day')
            ->pluck('c', 'day')
            ->mapWithKeys(fn ($count, $day) => [(string) $day => (int) $count])
            ->all();

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->format('Y-m-d');
            $series[] = [
                'day' => $day,
                'count' => $rows[$day] ?? 0,
            ];
        }

        return $series;
    }

    /**
     * @return array<int, array{domain: string, count: int}>
     */
    protected function topDomains(CarbonInterface $from, CarbonInterface $to): array
    {
        $urls = Statistic::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', ConversionStatus::FINISHED)
            ->whereNotNull('url')
            ->pluck('url');

        try {
            $byHost = $urls->groupBy(fn ($url) => parse_url((string) $url, PHP_URL_HOST))
                ->map(fn ($items) => $items->count());
        } catch (Throwable $th) {
            Log::error('Could not group urls by domain', ['exception' => $th]);

            return [];
        }

        // Merge YouTube synonyms
        $youtubeCount = 0;
        foreach (self::YOUTUBE_SYNONYMS as $synonym) {
            if (isset($byHost[$synonym])) {
                $youtubeCount += $byHost[$synonym];
                unset($byHost[$synonym]);
            }
        }
        if ($youtubeCount > 0) {
            $byHost->put('youtube.com', $youtubeCount);
        }

        $sorted = $byHost->sortDesc();
        $top = $sorted->take(5);
        $rest = $sorted->skip(5);

        $result = $top
            ->map(fn ($count, $domain) => ['domain' => (string) $domain, 'count' => (int) $count])
            ->values()
            ->all();

        if ($rest->sum() > 0) {
            $result[] = ['domain' => 'Sonstige', 'count' => (int) $rest->sum()];
        }

        return $result;
    }

    /**
     * @return array<int, int>
     */
    protected function hourlyDistribution(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = Statistic::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', ConversionStatus::FINISHED)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as c')
            ->groupBy('hour')
            ->pluck('c', 'hour');

        $hours = array_fill(0, 24, 0);
        foreach ($rows as $hour => $count) {
            $hours[(int) $hour] = (int) $count;
        }

        return $hours;
    }
}
