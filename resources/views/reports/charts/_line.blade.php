@php
    /**
     * @var array<int, array{day: string, count: int}> $current
     * @var array<int, array{day: string, count: int}> $previous
     * @var array<int, string> $labels  Punkt-Index → X-Achsen-Beschriftung
     */
    $width = 1000;
    $height = 240;
    $padX = 40;
    $padY = 30;
    $innerW = $width - 2 * $padX;
    $innerH = $height - 2 * $padY;

    $maxValue = max(
        collect($current)->max('count'),
        collect($previous)->max('count'),
        1,
    );

    // Beide Serien laufen über die volle Breite, auch wenn die Zeiträume
    // unterschiedlich viele Tage haben (28 vs. 31).
    $pointFor = function (int $i, int $count, int $total) use ($padX, $padY, $innerW, $innerH, $maxValue): array {
        $x = $padX + ($innerW * $i / max($total - 1, 1));
        $y = $padY + $innerH * (1 - $count / $maxValue);
        return [round($x, 1), round($y, 1)];
    };

    $polyline = function (array $series) use ($pointFor): string {
        $total = count($series);
        $pts = [];
        foreach ($series as $i => $bucket) {
            [$x, $y] = $pointFor($i, $bucket['count'], $total);
            $pts[] = "{$x},{$y}";
        }
        return implode(' ', $pts);
    };

    $currentTotal = count($current);
    // Bei vielen Punkten (Monat) werden die Marker zu dicht.
    $showMarkers = $currentTotal <= 14;
@endphp

<svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full h-auto" xmlns="http://www.w3.org/2000/svg">
    {{-- Y-Achse Tick-Linien --}}
    @for ($t = 0; $t <= 4; $t++)
        @php
            $y = $padY + $innerH * (1 - $t / 4);
            $value = (int) round($maxValue * $t / 4);
        @endphp
        <line x1="{{ $padX }}" y1="{{ $y }}" x2="{{ $padX + $innerW }}" y2="{{ $y }}"
              stroke="rgba(255,255,255,0.08)" stroke-width="1" />
        <text x="{{ $padX - 8 }}" y="{{ $y + 4 }}" text-anchor="end"
              fill="rgba(255,255,255,0.5)" font-size="10">{{ $value }}</text>
    @endfor

    {{-- Vorperiode (gestrichelt) --}}
    <polyline points="{{ $polyline($previous) }}"
              fill="none" stroke="rgba(255,255,255,0.4)"
              stroke-width="2" stroke-dasharray="4 4" />

    {{-- Aktuelle Periode (durchgezogen) --}}
    <polyline points="{{ $polyline($current) }}"
              fill="none" stroke="var(--color-primary, #ff6700)"
              stroke-width="2.5" />

    {{-- Punkte aktuelle Periode --}}
    @if ($showMarkers)
        @foreach ($current as $i => $bucket)
            @php [$x, $y] = $pointFor($i, $bucket['count'], $currentTotal); @endphp
            <circle cx="{{ $x }}" cy="{{ $y }}" r="3" fill="var(--color-primary, #ff6700)" />
        @endforeach
    @endif

    {{-- X-Achse Labels --}}
    @foreach ($labels as $i => $label)
        @php $x = $padX + ($innerW * $i / max($currentTotal - 1, 1)); @endphp
        <text x="{{ round($x, 1) }}" y="{{ $height - 8 }}" text-anchor="middle"
              fill="rgba(255,255,255,0.6)" font-size="11">{{ $label }}</text>
    @endforeach
</svg>
