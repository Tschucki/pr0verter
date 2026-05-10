<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\CarbonInterface;

class WeeklyReportData
{
    /**
     * @param  array<string, ?float>  $deltas  Map stat-key → delta_pct (null wenn previous=0)
     * @param  array<int, array{day: string, count: int}>  $dailySeriesCurrent  7 Werte aktuelle Woche (Mo–So)
     * @param  array<int, array{day: string, count: int}>  $dailySeriesPrevious  7 Werte Vorwoche
     * @param  array<int, array{domain: string, count: int}>  $domainsTop5  Bis zu 5 Domains + ggf. „Sonstige" als 6. Element
     * @param  array<int, int>  $hourlyDistribution  Indizes 0..23 → Anzahl
     */
    public function __construct(
        public readonly CarbonInterface $from,
        public readonly CarbonInterface $to,
        public readonly CarbonInterface $generatedAt,
        public readonly WeeklyStats $current,
        public readonly WeeklyStats $previous,
        public readonly array $deltas,
        public readonly array $dailySeriesCurrent,
        public readonly array $dailySeriesPrevious,
        public readonly array $domainsTop5,
        public readonly array $hourlyDistribution,
    ) {}
}
