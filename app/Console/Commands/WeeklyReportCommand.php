<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Pr0PostService;
use App\Services\WeeklyReportRenderer;
use App\Services\WeeklyReportStatsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class WeeklyReportCommand extends Command
{
    protected $signature = 'app:generate-weekly-report';

    protected $description = 'Generates a image for the weekly report and posts it on pr0gramm';

    public function handle(
        WeeklyReportStatsService $stats,
        WeeklyReportRenderer $renderer,
        Pr0PostService $poster,
    ): int {
        $data = $stats->buildReportData(now());

        if ($data->current->totalConversions === 0) {
            Log::info('Weekly report skipped (no conversions this week)', [
                'from' => $data->from->toIso8601String(),
                'to' => $data->to->toIso8601String(),
            ]);

            return self::SUCCESS;
        }

        $fileName = $data->from->format('Y-m-d-H') . '-to-' . $data->to->format('Y-m-d-H') . '-stats.png';
        $imagePath = Storage::disk('local')->path('weekly-reports/' . $fileName);

        Storage::disk('local')->makeDirectory('weekly-reports');

        try {
            $html = view('weekly-report', ['data' => $data])->render();
            $renderer->renderPng($html, $imagePath);
        } catch (Throwable $e) {
            Log::error('Weekly report rendering failed', ['exception' => $e]);

            $this->error('Weekly report rendering failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        try {
            $poster->postImage(
                $imagePath,
                'Wöchentlicher Bericht vom ' . $data->from->format('d.m.Y') . ' bis ' . $data->to->format('d.m.Y') . ' | https://pr0verter.de | https://github.com/Tschucki/pr0verter',
                ['pr0verter', 'Statistiken', 'Wochenstatistik', 'das pr0 programmiert', 'sfw', 'image', 'api']
            );
        } catch (Throwable $e) {
            Log::error('Weekly report post failed (PNG retained)', ['exception' => $e, 'path' => $imagePath]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
