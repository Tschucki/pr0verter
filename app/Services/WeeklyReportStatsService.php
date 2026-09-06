<?php

declare(strict_types=1);

namespace App\Services;

use App\ValueObjects\WeeklyReportData;
use Carbon\CarbonInterface;

class WeeklyReportStatsService extends ReportStatsService
{
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
            dailySeriesCurrent: $this->dailySeries($from, 7),
            dailySeriesPrevious: $this->dailySeries($previousFrom, 7),
            domainsTop5: $this->topDomains($from, $to),
            hourlyDistribution: $this->hourlyDistribution($from, $to),
        );
    }
}
