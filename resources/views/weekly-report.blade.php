<!DOCTYPE html>
<html lang="de" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wöchentlicher Statistikbericht</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-background text-foreground">

@php
    /** @var \App\ValueObjects\WeeklyReportData $data */
    $current = $data->current;
    $previous = $data->previous;
    $deltas = $data->deltas;

    $deltaBadge = function (?float $deltaPct): string {
        if ($deltaPct === null) {
            return '<span class="text-white/50">—</span>';
        }
        if ($deltaPct > 0.05) {
            return '<span class="text-emerald-400">▲ +' . number_format($deltaPct, 1, ',', '.') . ' %</span>';
        }
        if ($deltaPct < -0.05) {
            return '<span class="text-rose-400">▼ ' . number_format($deltaPct, 1, ',', '.') . ' %</span>';
        }
        return '<span class="text-white/50">◆ ±0 %</span>';
    };

    $secondaryCards = [
        ['label' => 'Häufigstes Eingabeformat', 'value' => $current->mostUsedInputExtension ?? '—', 'delta' => null, 'subline' => $previous->mostUsedInputExtension ? 'vorher: ' . $previous->mostUsedInputExtension : null],
        ['label' => 'Ø Konvertierungszeit', 'value' => \Illuminate\Support\Number::format($current->averageConversionTime, 0, locale: 'de-DE') . ' s', 'delta' => $deltas['average_conversion_time'] ?? null],
        ['label' => 'Wasserzeichen', 'value' => $current->addedWatermarks, 'delta' => $deltas['added_watermarks'] ?? null],
        ['label' => 'Unrat gecroppt', 'value' => $current->autoCropped, 'delta' => $deltas['auto_cropped'] ?? null],
        ['label' => 'Ausschnitte erstellt', 'value' => $current->trimmed, 'delta' => $deltas['trimmed'] ?? null],
        ['label' => 'Audio entfernt', 'value' => $current->removedAudio, 'delta' => $deltas['removed_audio'] ?? null],
        ['label' => 'Nur Audio', 'value' => $current->audioOnly, 'delta' => $deltas['audio_only'] ?? null],
        ['label' => 'Segmentiert', 'value' => $current->segmented, 'delta' => $deltas['segmented'] ?? null],
    ];
@endphp

@if(request()?->routeIs('admin.weekly-report.preview'))
    <div class="fixed top-2 right-2 z-50 bg-zinc-900/90 text-white text-xs rounded-md px-3 py-2 flex gap-3 items-center shadow-lg backdrop-blur">
        <span class="text-amber-300">Vorschau — wird nicht gepostet</span>
        <button type="button" data-render-png class="bg-primary hover:bg-primary/80 px-2 py-1 rounded">Als PNG rendern</button>
    </div>
    <div data-png-target class="fixed bottom-2 right-2 z-50 max-w-md hidden">
        <img class="rounded shadow-2xl border border-white/10" alt="">
    </div>
    <script>
        document.querySelector('[data-render-png]')?.addEventListener('click', async (e) => {
            e.target.disabled = true;
            try {
                const res = await fetch(@json(route('admin.weekly-report.render-png')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'image/png' },
                });
                if (res.ok) {
                    const blob = await res.blob();
                    const target = document.querySelector('[data-png-target]');
                    target.querySelector('img').src = URL.createObjectURL(blob);
                    target.classList.remove('hidden');
                }
            } finally {
                e.target.disabled = false;
            }
        });
    </script>
@endif

<div class="max-w-[1052px] mx-auto px-6 py-8">

    <div class="flex items-center gap-4 mb-8">
        <img src="{{ asset('images/pr0verter.png') }}" alt="pr0verter Logo" class="size-16 aspect-square">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-white">Wochenstatistik vom pr0verter</h1>
            <p class="text-sm text-white/60">{{ $data->from->format('d.m.Y') }} – {{ $data->to->format('d.m.Y') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-8">
        <div class="bg-white/5 rounded-xl p-6">
            <div class="text-xs uppercase tracking-wide text-white/50">Konvertierungen</div>
            <div class="mt-2 text-5xl font-bold text-white">{{ \Illuminate\Support\Number::format($current->totalConversions, locale: 'de-DE') }}</div>
            <div class="mt-2 text-sm">{!! $deltaBadge($deltas['total_conversions'] ?? null) !!} <span class="text-white/40">ggü. Vorwoche</span></div>
        </div>
        <div class="bg-white/5 rounded-xl p-6">
            <div class="text-xs uppercase tracking-wide text-white/50">Traffic</div>
            <div class="mt-2 text-5xl font-bold text-white">{{ \Illuminate\Support\Number::fileSize($current->traffic, 2) }}</div>
            <div class="mt-2 text-sm">{!! $deltaBadge($deltas['traffic'] ?? null) !!} <span class="text-white/40">ggü. Vorwoche</span></div>
        </div>
    </div>

    <div class="bg-white/5 rounded-xl p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-white/80">Wochen-Verlauf (Konvertierungen pro Tag)</h2>
            <div class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-0.5 bg-primary"></span><span class="text-white/70">Diese Woche</span></span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-0.5 bg-white/40" style="border-top:1px dashed"></span><span class="text-white/70">Letzte Woche</span></span>
            </div>
        </div>
        @include('weekly-report.charts._line', ['current' => $data->dailySeriesCurrent, 'previous' => $data->dailySeriesPrevious])
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white/5 rounded-xl p-6">
            <h2 class="text-sm font-semibold text-white/80 mb-4">Top-Quellen</h2>
            @include('weekly-report.charts._donut', ['domains' => $data->domainsTop5])
        </div>
        <div class="bg-white/5 rounded-xl p-6">
            <h2 class="text-sm font-semibold text-white/80 mb-4">Aktivität pro Stunde</h2>
            @include('weekly-report.charts._bar', ['hours' => $data->hourlyDistribution])
        </div>
    </div>

    <div class="grid grid-cols-4 gap-3 mb-8">
        @foreach ($secondaryCards as $card)
            <div class="bg-white/5 rounded-lg p-4">
                <div class="text-xs text-white/50">{{ $card['label'] }}</div>
                <div class="mt-1 text-xl font-semibold text-white">{{ $card['value'] }}</div>
                <div class="mt-1 text-xs">
                    @if (array_key_exists('delta', $card) && $card['delta'] !== null)
                        {!! $deltaBadge($card['delta']) !!}
                    @elseif (! empty($card['subline']))
                        <span class="text-white/40">{{ $card['subline'] }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="text-center text-xs text-white/50">
        <p>Erstelldatum: {{ $data->generatedAt->format('d.m.Y H:i') }} Uhr — automatisch generiert und gepostet.</p>
        <p class="mt-1 text-primary font-semibold tracking-wide">pr0verter.de</p>
    </div>

</div>
</body>
</html>
