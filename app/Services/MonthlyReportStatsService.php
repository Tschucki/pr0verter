<?php

declare(strict_types=1);

namespace App\Services;

use App\ValueObjects\MonthlyReportData;
use Carbon\CarbonInterface;

class MonthlyReportStatsService extends ReportStatsService
{
    /**
     * Builds the report for the calendar month $inMonth falls into, compared
     * against the calendar month before it.
     */
    public function buildReportData(CarbonInterface $inMonth): MonthlyReportData
    {
        $from = $inMonth->copy()->startOfMonth();
        $to = $inMonth->copy()->endOfMonth();
        $previousFrom = $from->copy()->subMonthNoOverflow()->startOfMonth();
        $previousTo = $previousFrom->copy()->endOfMonth();

        $current = $this->statsForRange($from, $to);
        $previous = $this->statsForRange($previousFrom, $previousTo);

        return new MonthlyReportData(
            from: $from,
            to: $to,
            previousFrom: $previousFrom,
            previousTo: $previousTo,
            generatedAt: now(),
            current: $current,
            previous: $previous,
            deltas: $this->computeDeltas($current, $previous),
            dailySeriesCurrent: $this->dailySeries($from, $from->daysInMonth),
            dailySeriesPrevious: $this->dailySeries($previousFrom, $previousFrom->daysInMonth),
            domainsTop5: $this->topDomains($from, $to),
            hourlyDistribution: $this->hourlyDistribution($from, $to),
        );
    }
}
