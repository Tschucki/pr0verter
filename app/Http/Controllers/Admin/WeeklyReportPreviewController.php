<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WeeklyReportStatsService;
use Illuminate\Contracts\View\View;

class WeeklyReportPreviewController extends Controller
{
    public function __invoke(WeeklyReportStatsService $stats): View
    {
        $data = $stats->buildReportData(now());

        return view('weekly-report', ['data' => $data]);
    }
}
