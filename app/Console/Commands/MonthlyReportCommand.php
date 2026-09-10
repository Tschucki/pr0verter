<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MonthlyReportStatsService;
use App\Services\Pr0PostService;
use App\Services\ReportRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MonthlyReportCommand extends Command
{
    protected $signature = 'app:generate-monthly-report';

    protected $description = 'Generates a image for the monthly report and posts it on pr0gramm';

    public function handle(
        MonthlyReportStatsService $stats,
        ReportRenderer $renderer,
        Pr0PostService $poster,
    ): int {
        $data = $stats->buildReportData(now());

        if ($data->current->totalConversions === 0) {
            Log::info('Monthly report skipped (no conversions this month)', [
                'from' => $data->from->toIso8601String(),
                'to' => $data->to->toIso8601String(),
            ]);

            return self::SUCCESS;
        }

        $fileName = $data->from->format('Y-m') . '-monthly-stats.png';
        $imagePath = Storage::disk('local')->path('monthly-reports/' . $fileName);

        Storage::disk('local')->makeDirectory('monthly-reports');

        try {
            $html = view('monthly-report', ['data' => $data])->render();
            $renderer->renderPng($html, $imagePath);
        } catch (Throwable $e) {
            Log::error('Monthly report rendering failed', ['exception' => $e]);

            $this->error('Monthly report rendering failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        try {
            $poster->postImage(
                $imagePath,
                'Monatsbericht für ' . $data->from->locale('de')->translatedFormat('F Y')
                    . ' im Vergleich zum ' . $data->previousFrom->locale('de')->translatedFormat('F Y')
                    . ' | https://pr0verter.de | https://github.com/Tschucki/pr0verter',
                ['pr0verter', 'Statistiken', 'Monatsstatistik', 'das pr0 programmiert', 'sfw', 'image', 'api']
            );
        } catch (Throwable $e) {
            Log::error('Monthly report post failed (PNG retained)', ['exception' => $e, 'path' => $imagePath]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
