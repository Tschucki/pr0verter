<?php

declare(strict_types=1);

use App\Models\Statistic;
use App\Services\WeeklyReportStatsService;
use App\ValueObjects\WeeklyReportData;

it('aggregates basic counts and traffic for a range', function (): void {
    $from = now()->startOfWeek();
    $to = $from->copy()->addDays(6)->endOfDay();

    Statistic::factory()->count(3)->create([
        'created_at' => $from->copy()->addDay(),
        'extension' => 'mp4',
        'size' => 100,
        'audio' => true,
        'auto_crop' => false,
        'watermark' => false,
    ]);
    Statistic::factory()->watermarked()->create([
        'created_at' => $from->copy()->addDays(2),
        'size' => 200,
    ]);
    Statistic::factory()->failed()->create([
        'created_at' => $from->copy()->addDay(),
        'size' => 999,
    ]);

    $service = app(WeeklyReportStatsService::class);
    $stats = $service->statsForRange($from, $to);

    expect($stats->totalConversions)->toBe(4);
    expect($stats->traffic)->toBe(500); // 3*100 + 200, FAILED ignoriert
    expect($stats->addedWatermarks)->toBe(1);
    expect($stats->mostUsedInputExtension)->toBe('mp4');
});

it('returns zeros for an empty range', function (): void {
    $from = now()->subDays(14);
    $to = now()->subDays(7);

    $stats = app(WeeklyReportStatsService::class)->statsForRange($from, $to);

    expect($stats->totalConversions)->toBe(0);
    expect($stats->traffic)->toBe(0);
    expect($stats->mostUsedInputExtension)->toBeNull();
    expect($stats->averageConversionTime)->toBe(0);
});

it('counts trimmed when only trim_start is set', function (): void {
    $from = now()->startOfWeek();
    $to = $from->copy()->addDays(6)->endOfDay();

    Statistic::factory()->state(['trim_start' => 10, 'trim_end' => null])->create([
        'created_at' => $from->copy()->addDay(),
    ]);

    $stats = app(WeeklyReportStatsService::class)->statsForRange($from, $to);

    expect($stats->trimmed)->toBe(1);
});

it('ignores statistics outside the range', function (): void {
    $from = now()->startOfWeek();
    $to = $from->copy()->addDays(6)->endOfDay();

    Statistic::factory()->create(['created_at' => $from->copy()->subDay()]);
    Statistic::factory()->create(['created_at' => $to->copy()->addDay()]);

    $stats = app(WeeklyReportStatsService::class)->statsForRange($from, $to);

    expect($stats->totalConversions)->toBe(0);
});

it('builds report data with current and previous week', function (): void {
    $weekEnd = now()->endOfDay();
    $thisWeekDay = $weekEnd->copy()->subDays(2);
    $lastWeekDay = $weekEnd->copy()->subDays(9);

    Statistic::factory()->count(10)->create(['created_at' => $thisWeekDay, 'size' => 100]);
    Statistic::factory()->count(5)->create(['created_at' => $lastWeekDay, 'size' => 100]);

    $data = app(WeeklyReportStatsService::class)->buildReportData($weekEnd);

    expect($data)->toBeInstanceOf(WeeklyReportData::class);
    expect($data->current->totalConversions)->toBe(10);
    expect($data->previous->totalConversions)->toBe(5);
});

it('computes delta_pct for total_conversions when previous > 0', function (): void {
    $weekEnd = now()->endOfDay();

    Statistic::factory()->count(12)->create(['created_at' => $weekEnd->copy()->subDays(2)]);
    Statistic::factory()->count(10)->create(['created_at' => $weekEnd->copy()->subDays(9)]);

    $data = app(WeeklyReportStatsService::class)->buildReportData($weekEnd);

    // (12 - 10) / 10 * 100 = 20.0
    expect($data->deltas['total_conversions'])->toBe(20.0);
});

it('returns null delta_pct when previous is zero', function (): void {
    $weekEnd = now()->endOfDay();

    Statistic::factory()->count(7)->create(['created_at' => $weekEnd->copy()->subDays(2)]);
    // Vorwoche: 0 Statistics

    $data = app(WeeklyReportStatsService::class)->buildReportData($weekEnd);

    expect($data->deltas['total_conversions'])->toBeNull();
});

it('returns -100 delta_pct when current drops to zero', function (): void {
    $weekEnd = now()->endOfDay();

    Statistic::factory()->count(8)->create(['created_at' => $weekEnd->copy()->subDays(9)]);
    // Aktuelle Woche: 0 Statistics

    $data = app(WeeklyReportStatsService::class)->buildReportData($weekEnd);

    expect($data->deltas['total_conversions'])->toBe(-100.0);
});

it('returns 7 daily buckets per week', function (): void {
    $weekEnd = now()->endOfDay();

    $data = app(WeeklyReportStatsService::class)->buildReportData($weekEnd);

    expect($data->dailySeriesCurrent)->toHaveCount(7);
    expect($data->dailySeriesPrevious)->toHaveCount(7);
    foreach ($data->dailySeriesCurrent as $bucket) {
        expect($bucket)->toHaveKeys(['day', 'count']);
    }
});

it('returns 24 hourly buckets initialized to zero', function (): void {
    $weekEnd = now()->endOfDay();

    Statistic::factory()->create([
        'created_at' => $weekEnd->copy()->subDays(2)->setHour(14),
    ]);

    $data = app(WeeklyReportStatsService::class)->buildReportData($weekEnd);

    expect($data->hourlyDistribution)->toHaveCount(24);
    expect($data->hourlyDistribution[14])->toBe(1);
    expect($data->hourlyDistribution[3])->toBe(0); // Stunde ohne Daten
});

it('aggregates youtube synonyms into a single domain in donut data', function (): void {
    $weekEnd = now()->endOfDay();
    $day = $weekEnd->copy()->subDays(2);

    Statistic::factory()->create(['created_at' => $day, 'url' => 'https://youtube.com/watch?v=a']);
    Statistic::factory()->create(['created_at' => $day, 'url' => 'https://youtu.be/b']);
    Statistic::factory()->create(['created_at' => $day, 'url' => 'https://m.youtube.com/watch?v=c']);
    Statistic::factory()->create(['created_at' => $day, 'url' => 'https://vimeo.com/d']);

    $data = app(WeeklyReportStatsService::class)->buildReportData($weekEnd);

    $youtube = collect($data->domainsTop5)->firstWhere('domain', 'youtube.com');
    expect($youtube)->not->toBeNull();
    expect($youtube['count'])->toBe(3);
});

it('caps top domains at 5 with rest grouped as Sonstige', function (): void {
    $weekEnd = now()->endOfDay();
    $day = $weekEnd->copy()->subDays(2);

    foreach (['a.com', 'b.com', 'c.com', 'd.com', 'e.com', 'f.com', 'g.com'] as $i => $host) {
        // a.com 7×, b.com 6×, c.com 5×, d.com 4×, e.com 3×, f.com 2×, g.com 1×
        Statistic::factory()->count(7 - $i)->create([
            'created_at' => $day,
            'url' => "https://{$host}/x",
        ]);
    }

    $data = app(WeeklyReportStatsService::class)->buildReportData($weekEnd);

    $domains = collect($data->domainsTop5);
    expect($domains)->toHaveCount(6); // 5 Top + Sonstige
    expect($domains->last()['domain'])->toBe('Sonstige');
    expect($domains->last()['count'])->toBe(3); // f.com (2) + g.com (1) = 3
});
