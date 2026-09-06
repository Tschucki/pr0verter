@php
    /** @var \App\ValueObjects\WeeklyReportData $data */
    $isPreview = request()?->routeIs('admin.weekly-report.preview') ?? false;
@endphp

@include('reports._report', [
    'data' => $data,
    'title' => 'Wochenstatistik vom pr0verter',
    'periodLabel' => $data->from->format('d.m.Y') . ' – ' . $data->to->format('d.m.Y'),
    'comparisonLabel' => 'ggü. Vorwoche',
    'trendTitle' => 'Wochen-Verlauf (Konvertierungen pro Tag)',
    'legendCurrent' => 'Diese Woche',
    'legendPrevious' => 'Letzte Woche',
    'lineLabels' => ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
    'renderRoute' => $isPreview ? route('admin.weekly-report.render-png') : null,
])
