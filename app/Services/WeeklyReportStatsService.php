<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConversionStatus;
use App\Models\Statistic;
use App\ValueObjects\WeeklyReportData;
use App\ValueObjects\WeeklyStats;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeeklyReportStatsService
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

    public function statsForRange(CarbonInterface $from, CarbonInterface $to): WeeklyStats
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

        return new WeeklyStats(
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

    public function buildReportData(CarbonInterface $weekEnd): WeeklyReportData
    {
        $from = $weekEnd->copy()->subDays(7);
        $to = $weekEnd->copy();
        $previousFrom = $from->copy()->subDays(7);
        $previousTo = $from->copy();

        $current = $this->statsForRange($from, $to);
        $previous = $this->statsForRange($previousFrom, $previousTo);

        return new WeeklyReportData(
            from: $from,
            to: $to,
            generatedAt: now(),
            current: $current,
            previous: $previous,
            deltas: $this->computeDeltas($current, $previous),
            dailySeriesCurrent: $this->dailySeries($from, $to),
            dailySeriesPrevious: $this->dailySeries($previousFrom, $previousTo),
            domainsTop5: $this->topDomains($from, $to),
            hourlyDistribution: $this->hourlyDistribution($from, $to),
        );
    }

    /**
     * @return array<string, ?float>
     */
    private function computeDeltas(WeeklyStats $current, WeeklyStats $previous): array
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
     * @return array<int, array{day: string, count: int}>
     */
    private function dailySeries(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = Statistic::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', ConversionStatus::FINISHED)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as c')
            ->groupBy('day')
            ->pluck('c', 'day')
            ->mapWithKeys(fn ($count, $day) => [(string) $day => (int) $count])
            ->all();

        $series = [];
        for ($i = 0; $i < 7; $i++) {
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
    private function topDomains(CarbonInterface $from, CarbonInterface $to): array
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
    private function hourlyDistribution(CarbonInterface $from, CarbonInterface $to): array
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
