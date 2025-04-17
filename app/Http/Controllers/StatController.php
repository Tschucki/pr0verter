<?php

namespace App\Http\Controllers;

use App\Enums\ConversionStatus;
use App\Models\Statistic;
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

        $allUrls = Statistic::whereNotNull('url')->get();
        $urls = null;
        try {
            $urls = $allUrls->groupBy(static function ($item) {
                return parse_url($item->url, PHP_URL_HOST);
            });

            $synonyms = self::YOUTUBE_SYNONYMS;

            foreach ($synonyms as $synonym) {
                $mainDomain = $synonym[0];
                $urls[$mainDomain] = $urls[$mainDomain] ?? collect();
                foreach ($synonym as $domain) {
                    if ($domain !== $mainDomain) {
                        $urls[$mainDomain] = $urls[$mainDomain]->merge($urls[$domain]);
                        unset($urls[$domain]);
                    }
                }
            }

            $urls = $urls->sortByDesc(fn ($item) => $item->count());
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
            'value' => Statistic::where('status', ConversionStatus::FINISHED)->count(),
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
            'value' => Statistic::where('watermark', true)->where('status', ConversionStatus::FINISHED)->count() . ' Wasserzeichen',
        ];

        $stats['auto_crop'] = [
            'title' => 'Automatisch zugeschnittene Videos',
            'value' => Statistic::where('auto_crop', true)->where('status', ConversionStatus::FINISHED)->count() . ' Videos',
        ];

        $stats['trimmed'] = [
            'title' => 'Videos zugeschnitten',
            'value' => Statistic::whereNotNull('trim_start')->orWhereNotNull('trim_end')->count() . ' Videos',
        ];

        $stats['removed_audio'] = [
            'title' => 'Audio entfernt',
            'value' => Statistic::where('audio', false)->count() . ' mal',
        ];

        $stats['audio_only'] = [
            'title' => 'Nur Audio extrahiert',
            'value' => Statistic::where('audio_only', true)->count() . ' mal',
        ];

        return $stats;
    }
}
