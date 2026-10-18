@props(['labels' => [], 'series' => [], 'height' => 220, 'currency' => '', 'summary' => 'Trend chart'])
@php

    $uid = 'ac' . substr(md5(uniqid('', true)), 0, 8);
    $vbW = 640;
    $vbH = (int) $height;
    $left = 46;
    $top = 14;
    $bottom = 26;
    $plotW = $vbW - $left - 8;
    $plotH = $vbH - $top - $bottom;

    $all = collect($series)->flatMap(fn ($s) => $s['values'])->map('floatval');
    $axisMax = \App\Support\SvgChart::niceMax((float) ($all->max() ?? 0));
    $hasData = $all->contains(fn ($v) => $v > 0);
    $n = count($labels);
    $showDots = $n <= 40;
    $ticks = 4;

    $drawn = collect($series)->values()->map(function ($s, $i) use ($plotW, $plotH, $axisMax) {
        return $s + ['geo' => \App\Support\SvgChart::line($s['values'], $plotW, $plotH, 6, $axisMax), 'i' => $i];
    });

    $labelIdx = $n <= 1 ? [0] : collect(range(0, min(5, $n - 1)))->map(fn ($k) => (int) round($k * ($n - 1) / min(5, $n - 1)))->unique()->values()->all();
@endphp
<figure {{ $attributes->merge(["class" => "w-full"]) }}>
    <svg viewBox="0 0 {{ $vbW }} {{ $vbH }}" class="h-auto w-full" role="img" aria-label="{{ $summary }}">
        <title>{{ $summary }}</title>
        <defs>
            @foreach($drawn as $s)
                <linearGradient id="{{ $uid }}-g{{ $s['i'] }}" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" style="stop-color: {{ $s['color'] }}; stop-opacity: .28"/>
                    <stop offset="100%" style="stop-color: {{ $s['color'] }}; stop-opacity: 0"/>
                </linearGradient>
            @endforeach
        </defs>

        @for($t = 0; $t <= $ticks; $t++)
            @php $y = $top + $plotH - ($t / $ticks) * ($plotH - 12) - 6; @endphp
            <line x1="{{ $left }}" x2="{{ $vbW - 8 }}" y1="{{ round($y, 2) }}" y2="{{ round($y, 2) }}" style="stroke: var(--border)" stroke-dasharray="{{ $t === 0 ? '0' : '3 4' }}"/>
            <text x="{{ $left - 8 }}" y="{{ round($y + 3.5, 2) }}" text-anchor="end" font-size="10" style="fill: var(--muted-foreground)">{{ \App\Support\SvgChart::compact($axisMax * $t / $ticks) }}</text>
        @endfor

        @foreach($labelIdx as $idx)
            @php $x = $n <= 1 ? $plotW / 2 : 6 + $idx * ($plotW - 12) / ($n - 1); @endphp
            <text x="{{ round($left + $x, 2) }}" y="{{ $vbH - 8 }}" text-anchor="{{ $idx === 0 ? 'start' : ($idx === $n - 1 ? 'end' : 'middle') }}" font-size="10" style="fill: var(--muted-foreground)">{{ $labels[$idx] ?? '' }}</text>
        @endforeach

        <g transform="translate({{ $left }}, {{ $top }})">
            @foreach($drawn as $s)
                @if(($s['fill'] ?? false) && $s['geo']['area'])
                    <path d="{{ $s['geo']['area'] }}" fill="url(#{{ $uid }}-g{{ $s['i'] }})" class="fv-fade" style="--i: {{ $s['i'] }}"/>
                @endif
                @if($s['geo']['line'])
                    <path d="{{ $s['geo']['line'] }}" pathLength="1" fill="none" class="fv-draw" style="--i: {{ $s['i'] }}; stroke: {{ $s['color'] }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                @endif
                @foreach($s['geo']['points'] as $p => $pt)
                    @if($showDots || $p === count($s['geo']['points']) - 1)
                        <circle cx="{{ $pt[0] }}" cy="{{ $pt[1] }}" r="{{ $showDots ? 2.5 : 3.5 }}" class="fv-fade" style="--i: {{ $s['i'] + 2 }}; fill: var(--card); stroke: {{ $s['color'] }}" stroke-width="2"/>
                    @endif
                    <circle cx="{{ $pt[0] }}" cy="{{ $pt[1] }}" r="9" fill="transparent" class="fv-dot"><title>{{ $s['name'] }} · {{ $labels[$p] ?? '' }}: {{ $currency }} {{ number_format((float) ($s['values'][$p] ?? 0), 2) }}</title></circle>
                @endforeach
            @endforeach
        </g>

        @unless($hasData)
            <text x="{{ $vbW / 2 }}" y="{{ $top + $plotH / 2 }}" text-anchor="middle" font-size="12" style="fill: var(--muted-foreground)">No activity in this period</text>
        @endunless
    </svg>
    <figcaption class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-muted-foreground">
        @foreach($drawn as $s)
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full" style="background: {{ $s['color'] }}"></span>{{ $s['name'] }}</span>
        @endforeach
    </figcaption>
</figure>
