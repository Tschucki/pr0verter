@php
    /**
     * @var array<int, int> $hours  Index 0..23 → Anzahl
     */
    $width = 480;
    $height = 180;
    $padX = 24;
    $padY = 24;
    $innerW = $width - 2 * $padX;
    $innerH = $height - 2 * $padY;
    $barGap = 2;
    $barWidth = ($innerW - 23 * $barGap) / 24;
    $maxValue = max(max($hours), 1);
    $maxHourIndex = array_keys($hours, max($hours))[0] ?? 0;
@endphp

<svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full h-auto" xmlns="http://www.w3.org/2000/svg">
    @foreach ($hours as $hour => $count)
        @php
            $h = $innerH * ($count / $maxValue);
            $x = $padX + $hour * ($barWidth + $barGap);
            $y = $padY + $innerH - $h;
            $isPeak = $hour === $maxHourIndex && $count > 0;
            $color = $isPeak ? 'var(--color-primary, #ff6700)' : 'rgba(255,255,255,0.25)';
        @endphp
        <rect x="{{ round($x, 1) }}" y="{{ round($y, 1) }}"
              width="{{ round($barWidth, 1) }}" height="{{ round($h, 1) }}"
              rx="1.5" fill="{{ $color }}" />
    @endforeach

    @foreach ([0, 6, 12, 18] as $tick)
        @php $x = $padX + $tick * ($barWidth + $barGap) + $barWidth / 2; @endphp
        <text x="{{ round($x, 1) }}" y="{{ $height - 6 }}" text-anchor="middle"
              fill="rgba(255,255,255,0.6)" font-size="10">{{ str_pad((string) $tick, 2, '0', STR_PAD_LEFT) }}</text>
    @endforeach
</svg>
