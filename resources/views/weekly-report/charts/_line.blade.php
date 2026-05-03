@php
    /**
     * @var array<int, array{day: string, count: int}> $current
     * @var array<int, array{day: string, count: int}> $previous
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

    $pointFor = function (int $i, int $count) use ($padX, $padY, $innerW, $innerH, $maxValue): array {
        $x = $padX + ($innerW * $i / 6);
        $y = $padY + $innerH * (1 - $count / $maxValue);
        return [round($x, 1), round($y, 1)];
    };

    $polyline = function (array $series) use ($pointFor): string {
        $pts = [];
        foreach ($series as $i => $bucket) {
            [$x, $y] = $pointFor($i, $bucket['count']);
            $pts[] = "{$x},{$y}";
        }
        return implode(' ', $pts);
    };

    $labels = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
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

    {{-- Vorwoche (gestrichelt) --}}
    <polyline points="{{ $polyline($previous) }}"
              fill="none" stroke="rgba(255,255,255,0.4)"
              stroke-width="2" stroke-dasharray="4 4" />

    {{-- Aktuelle Woche (durchgezogen) --}}
    <polyline points="{{ $polyline($current) }}"
              fill="none" stroke="var(--color-primary, #ff6700)"
              stroke-width="2.5" />

    {{-- Punkte aktuelle Woche --}}
    @foreach ($current as $i => $bucket)
        @php [$x, $y] = $pointFor($i, $bucket['count']); @endphp
        <circle cx="{{ $x }}" cy="{{ $y }}" r="3" fill="var(--color-primary, #ff6700)" />
    @endforeach

    {{-- X-Achse Labels --}}
    @foreach ($labels as $i => $label)
        @php $x = $padX + ($innerW * $i / 6); @endphp
        <text x="{{ $x }}" y="{{ $height - 8 }}" text-anchor="middle"
              fill="rgba(255,255,255,0.6)" font-size="11">{{ $label }}</text>
    @endforeach
</svg>
