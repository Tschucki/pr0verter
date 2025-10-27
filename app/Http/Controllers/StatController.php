<?php

namespace App\Http\Controllers;

use App\Enums\ConversionStatus;
use App\Models\Statistic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Inertia\Inertia;
use Throwable;

class StatController extends Controller
{
    /**
     * @var string[]
     * */
    public const YOUTUBE_SYNONYMS = [
        'youtube.com',
        'www.youtube.com',
        'youtu.be',
        'm.youtube.com',
        'music.youtube.com',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
    ];

    public function __invoke(Request $request)
    {
        return Inertia::render('Stats', [
            'stats' => $this->getStats(),
        ]);
    }

    private function getStats()
    {
        $stats = [];

        $allUrls = Statistic::where('status', ConversionStatus::FINISHED)->whereNotNull('url')->pluck('url');

        $urls = null;

        try {
            $urls = $allUrls->groupBy(static function ($url) {
                return parse_url($url, PHP_URL_HOST);
            });

            $urlsWithCounts = $urls->map(fn ($item) => $item->count());

            $synonyms = self::YOUTUBE_SYNONYMS;

            $youtubeCount = 0;
            foreach ($synonyms as $synonym) {
                if (isset($urlsWithCounts[$synonym])) {
                    $youtubeCount += $urlsWithCounts[$synonym];
                    unset($urlsWithCounts[$synonym]);
                }
            }

            if ($youtubeCount > 0) {
                $urlsWithCounts->put('youtube.com', $youtubeCount);
            }

            $urls = $urlsWithCounts->sortDesc();
        } catch (Throwable $th) {
            Log::error('Could not group urls by domain', ['exception' => $th]);
        }

        $stats['today_conversions'] = [
            'title' => 'Heutige Konvertierungen',
            'value' => Statistic::whereDate('created_at', now())->where('status', ConversionStatus::FINISHED)->count(),
        ];

        $stats['favorite_url'] = [
            'title' => 'Beliebteste Download-URL',
            'value' => $urls ? $urls->keys()->first() : 'Keine URLs vorhanden',
        ];

        $stats['currently_converting'] = [
            'title' => 'Aktuell konvertierende Videos',
            'value' => Statistic::whereIn('status', [ConversionStatus::PROCESSING, ConversionStatus::PREPARING, ConversionStatus::DOWNLOADING])->where('created_at', '>', now()->subHour())->count(),
        ];

        $stats['uploaded_size'] = [
            'title' => 'Traffic für hochgeladene Videos',
            'value' => Number::fileSize(Statistic::sum('size'), 2),
        ];

        $stats['extensions'] = [
            'title' => 'Am häufigsten hochgeladene Dateiendung',
            'value' => Statistic::select('extension')
                ->groupBy('extension')
                ->orderByRaw('COUNT(extension) DESC')
                ->first()->extension,
        ];

        $stats['finished'] = [
            'title' => 'Erfolgreiche Konvertierungen',
            'value' => Number::format(Statistic::where('status', ConversionStatus::FINISHED)->count()),
        ];

        $stats['average_conversion_time'] = [
            'title' => 'Durchschnittliche Konvertierungszeit',
            'value' => Number::format(Statistic::where('status', ConversionStatus::FINISHED)->avg('conversion_time'), 2, locale: 'de-DE') . ' Sekunden',
        ];

        $stats['favorite_time_to_convert'] = [
            'title' => 'Beliebteste Konvertierungszeit',
            'value' => Statistic::selectRaw('HOUR(created_at) as hour, COUNT(id) as count')
                ->groupBy('hour')
                ->orderByRaw('COUNT(id) DESC')
                ->first()->hour . ' Uhr',
        ];

        $stats['added_watermarks'] = [
            'title' => 'Wasserzeichen hinzugefügt',
            'value' => Number::format(Statistic::where('watermark', true)->where('status', ConversionStatus::FINISHED)->count()) . ' Wasserzeichen',
        ];

        $stats['auto_crop'] = [
            'title' => 'Automatisch zugeschnittene Videos',
            'value' => Number::format(Statistic::where('auto_crop', true)->where('status', ConversionStatus::FINISHED)->count()) . ' Videos',
        ];

        $stats['trimmed'] = [
            'title' => 'Videos zugeschnitten',
            'value' => Number::format(Statistic::where('status', ConversionStatus::FINISHED)->where(function (Builder $query) {
                $query->whereNotNull('trim_start')->orWhereNotNull('trim_end');
            })->count()) . ' Videos',
        ];

        $stats['removed_audio'] = [
            'title' => 'Audio entfernt',
            'value' => Number::format(Statistic::where('audio', false)->count()) . ' mal',
        ];

        $stats['audio_only'] = [
            'title' => 'Nur Audio extrahiert',
            'value' => Number::format(Statistic::where('audio_only', true)->count()) . ' mal',
        ];

        return $stats;
    }
}
