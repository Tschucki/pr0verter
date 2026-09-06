<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\CarbonInterface;

class MonthlyReportData
{
    /**
     * @param  array<string, ?float>  $deltas  Map stat-key → delta_pct (null wenn previous=0)
     * @param  array<int, array{day: string, count: int}>  $dailySeriesCurrent  Ein Wert pro Tag des Monats
     * @param  array<int, array{day: string, count: int}>  $dailySeriesPrevious  Ein Wert pro Tag des Vormonats
     * @param  array<int, array{domain: string, count: int}>  $domainsTop5  Bis zu 5 Domains + ggf. „Sonstige" als 6. Element
     * @param  array<int, int>  $hourlyDistribution  Indizes 0..23 → Anzahl
     */
    public function __construct(
        public readonly CarbonInterface $from,
        public readonly CarbonInterface $to,
        public readonly CarbonInterface $previousFrom,
        public readonly CarbonInterface $previousTo,
        public readonly CarbonInterface $generatedAt,
        public readonly ReportStats $current,
        public readonly ReportStats $previous,
        public readonly array $deltas,
        public readonly array $dailySeriesCurrent,
        public readonly array $dailySeriesPrevious,
        public readonly array $domainsTop5,
        public readonly array $hourlyDistribution,
    ) {}

    /**
     * Busiest day of the month, or null when nothing was converted at all.
     *
     * @return array{day: string, count: int}|null
     */
    public function busiestDay(): ?array
    {
        $best = null;

        foreach ($this->dailySeriesCurrent as $bucket) {
            if ($bucket['count'] > 0 && ($best === null || $bucket['count'] > $best['count'])) {
                $best = $bucket;
            }
        }

        return $best;
    }
}
