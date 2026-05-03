@php
    /**
     * @var array<int, array{domain: string, count: int}> $domains
     */
    $total = array_sum(array_column($domains, 'count'));
    $size = 180;
    $center = $size / 2;
    $radius = 70;
    $strokeWidth = 28;
    $circumference = 2 * M_PI * $radius;

    $palette = [
        'var(--color-primary, #ff6700)',
        '#3b82f6',
        '#10b981',
        '#f59e0b',
        '#ef4444',
        '#a3a3a3',
    ];

    $cursor = 0; // bisher belegte Anteile in 0..1
@endphp

<div class="flex items-center gap-6">
    <svg viewBox="0 0 {{ $size }} {{ $size }}" width="{{ $size }}" height="{{ $size }}"
         class="shrink-0" xmlns="http://www.w3.org/2000/svg">
        <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius }}"
                fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="{{ $strokeWidth }}" />

        @if ($total > 0)
            @foreach ($domains as $i => $entry)
                @php
                    $share = $entry['count'] / $total;
                    $dash = $share * $circumference;
                    $gap = $circumference - $dash;
                    $offset = -$cursor * $circumference;
                    $color = $palette[$i] ?? '#a3a3a3';
                    $cursor += $share;
                @endphp
                <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius }}"
                        fill="none" stroke="{{ $color }}"
                        stroke-width="{{ $strokeWidth }}"
                        stroke-dasharray="{{ round($dash, 2) }} {{ round($gap, 2) }}"
                        stroke-dashoffset="{{ round($offset, 2) }}"
                        transform="rotate(-90 {{ $center }} {{ $center }})" />
            @endforeach
        @endif

        <text x="{{ $center }}" y="{{ $center - 4 }}" text-anchor="middle"
              fill="white" font-size="20" font-weight="600">{{ $total }}</text>
        <text x="{{ $center }}" y="{{ $center + 14 }}" text-anchor="middle"
              fill="rgba(255,255,255,0.6)" font-size="10">Downloads</text>
    </svg>

    <ul class="text-sm space-y-1">
        @foreach ($domains as $i => $entry)
            @php
                $share = $total > 0 ? round(($entry['count'] / $total) * 100, 1) : 0;
                $color = $palette[$i] ?? '#a3a3a3';
            @endphp
            <li class="flex items-center gap-2">
                <span class="inline-block w-3 h-3 rounded-sm" style="background: {{ $color }}"></span>
                <span class="text-white/80">{{ $entry['domain'] }}</span>
                <span class="text-white/50">{{ $share }} %</span>
            </li>
        @endforeach
    </ul>
</div>
