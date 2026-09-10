@php
    /** @var \App\ValueObjects\MonthlyReportData $data */
    $isPreview = request()?->routeIs('admin.monthly-report.preview') ?? false;

    $dayCount = count($data->dailySeriesCurrent);

    // Beschriftet den 1., 5., 10., … und immer den letzten Tag des Monats —
    // aber nur, wenn er nicht direkt neben dem vorherigen Label klebt.
    $lineLabels = [];
    foreach ([1, 5, 10, 15, 20, 25] as $day) {
        if ($day <= $dayCount) {
            $lineLabels[$day - 1] = $day . '.';
        }
    }
    if ($dayCount - 25 >= 3) {
        $lineLabels[$dayCount - 1] = $dayCount . '.';
    }

    $busiestDay = $data->busiestDay();
    $trendSubline = $busiestDay === null
        ? null
        : 'Stärkster Tag: ' . \Carbon\Carbon::parse($busiestDay['day'])->format('d.m.')
            . ' mit ' . \Illuminate\Support\Number::format($busiestDay['count'], locale: 'de-DE') . ' Konvertierungen';
@endphp

@include('reports._report', [
    'data' => $data,
    'title' => 'Monatsstatistik vom pr0verter',
    'periodLabel' => $data->from->locale('de')->translatedFormat('F Y'),
    'comparisonLabel' => 'ggü. ' . $data->previousFrom->locale('de')->translatedFormat('F Y'),
    'trendTitle' => 'Monats-Verlauf (Konvertierungen pro Tag)',
    'legendCurrent' => $data->from->locale('de')->translatedFormat('F'),
    'legendPrevious' => $data->previousFrom->locale('de')->translatedFormat('F'),
    'lineLabels' => $lineLabels,
    'trendSubline' => $trendSubline,
    'renderRoute' => $isPreview ? route('admin.monthly-report.render-png') : null,
])
