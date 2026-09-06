<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MonthlyReportStatsService;
use Illuminate\Contracts\View\View;

class MonthlyReportPreviewController extends Controller
{
    public function __invoke(MonthlyReportStatsService $stats): View
    {
        $data = $stats->buildReportData(now());

        return view('monthly-report', ['data' => $data]);
    }
}
