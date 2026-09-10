<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MonthlyReportStatsService;
use App\Services\ReportRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RenderMonthlyReportPngController extends Controller
{
    public function __invoke(
        Request $request,
        MonthlyReportStatsService $stats,
        ReportRenderer $renderer,
    ): BinaryFileResponse {
        $data = $stats->buildReportData(now());
        $html = view('monthly-report', ['data' => $data])->render();

        Storage::disk('local')->makeDirectory('monthly-reports');
        $path = Storage::disk('local')->path('monthly-reports/preview-' . $request->user()->id . '.png');

        $renderer->renderPng($html, $path);

        return response()->file($path, ['Content-Type' => 'image/png']);
    }
}
