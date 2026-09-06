<?php

declare(strict_types=1);

use App\Models\Statistic;
use App\Services\MonthlyReportStatsService;
use App\ValueObjects\MonthlyReportData;
use Carbon\Carbon;

beforeEach(function (): void {
    // März 2026 hat 31 Tage, der Vormonat Februar 2026 nur 28 — genau die
    // Konstellation, in der eine fest verdrahtete Serienlänge auffliegen würde.
    Carbon::setTestNow(Carbon::parse('2026-03-15 12:00:00'));
});

it('builds report data for the calendar month and the one before it', function (): void {
    Statistic::factory()->count(3)->create([
        'created_at' => Carbon::parse('2026-03-04 10:00:00'),
        'size' => 100,
    ]);
    Statistic::factory()->count(2)->create([
        'created_at' => Carbon::parse('2026-02-17 10:00:00'),
        'size' => 50,
    ]);

    $data = app(MonthlyReportStatsService::class)->buildReportData(now());

    expect($data)->toBeInstanceOf(MonthlyReportData::class)
        ->and($data->from->format('Y-m-d H:i:s'))->toBe('2026-03-01 00:00:00')
        ->and($data->to->format('Y-m-d H:i:s'))->toBe('2026-03-31 23:59:59')
        ->and($data->previousFrom->format('Y-m-d H:i:s'))->toBe('2026-02-01 00:00:00')
        ->and($data->previousTo->format('Y-m-d H:i:s'))->toBe('2026-02-28 23:59:59')
        ->and($data->current->totalConversions)->toBe(3)
        ->and($data->current->traffic)->toBe(300)
        ->and($data->previous->totalConversions)->toBe(2)
        ->and($data->previous->traffic)->toBe(100);
});

it('ignores conversions outside both months', function (): void {
    Statistic::factory()->create(['created_at' => Carbon::parse('2026-01-31 23:00:00')]);
    Statistic::factory()->create(['created_at' => Carbon::parse('2026-04-01 00:30:00')]);

    $data = app(MonthlyReportStatsService::class)->buildReportData(now());

    expect($data->current->totalConversions)->toBe(0)
        ->and($data->previous->totalConversions)->toBe(0);
});

it('returns one daily bucket per day of each month', function (): void {
    $data = app(MonthlyReportStatsService::class)->buildReportData(now());

    expect($data->dailySeriesCurrent)->toHaveCount(31)
        ->and($data->dailySeriesPrevious)->toHaveCount(28)
        ->and($data->dailySeriesCurrent[0]['day'])->toBe('2026-03-01')
        ->and($data->dailySeriesCurrent[30]['day'])->toBe('2026-03-31')
        ->and($data->dailySeriesPrevious[27]['day'])->toBe('2026-02-28');
});

it('counts conversions into the right daily bucket', function (): void {
    Statistic::factory()->count(4)->create(['created_at' => Carbon::parse('2026-03-09 08:00:00')]);
    Statistic::factory()->create(['created_at' => Carbon::parse('2026-03-31 23:30:00')]);

    $data = app(MonthlyReportStatsService::class)->buildReportData(now());

    $byDay = collect($data->dailySeriesCurrent)->pluck('count', 'day');

    expect($byDay['2026-03-09'])->toBe(4)
        ->and($byDay['2026-03-31'])->toBe(1)
        ->and($byDay['2026-03-10'])->toBe(0);
});

it('reports the busiest day of the month', function (): void {
    Statistic::factory()->count(2)->create(['created_at' => Carbon::parse('2026-03-05 08:00:00')]);
    Statistic::factory()->count(5)->create(['created_at' => Carbon::parse('2026-03-12 08:00:00')]);

    $data = app(MonthlyReportStatsService::class)->buildReportData(now());

    expect($data->busiestDay())->toBe(['day' => '2026-03-12', 'count' => 5]);
});

it('has no busiest day when nothing was converted', function (): void {
    $data = app(MonthlyReportStatsService::class)->buildReportData(now());

    expect($data->busiestDay())->toBeNull();
});

it('computes deltas against the previous month', function (): void {
    Statistic::factory()->count(6)->create(['created_at' => Carbon::parse('2026-03-04 10:00:00')]);
    Statistic::factory()->count(4)->create(['created_at' => Carbon::parse('2026-02-04 10:00:00')]);

    $data = app(MonthlyReportStatsService::class)->buildReportData(now());

    expect($data->deltas['total_conversions'])->toBe(50.0);
});

it('returns a null delta when the previous month was empty', function (): void {
    Statistic::factory()->count(3)->create(['created_at' => Carbon::parse('2026-03-04 10:00:00')]);

    $data = app(MonthlyReportStatsService::class)->buildReportData(now());

    expect($data->deltas['total_conversions'])->toBeNull();
});
