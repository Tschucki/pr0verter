<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WeeklyReportRenderer;
use App\Services\WeeklyReportStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RenderWeeklyReportPngController extends Controller
{
    public function __invoke(
        Request $request,
        WeeklyReportStatsService $stats,
        WeeklyReportRenderer $renderer,
    ): BinaryFileResponse {
        $data = $stats->buildReportData(now());
        $html = view('weekly-report', ['data' => $data])->render();

        Storage::disk('local')->makeDirectory('weekly-reports');
        $path = Storage::disk('local')->path('weekly-reports/preview-' . $request->user()->id . '.png');

        $renderer->renderPng($html, $path);

        return response()->file($path, ['Content-Type' => 'image/png']);
    }
}
