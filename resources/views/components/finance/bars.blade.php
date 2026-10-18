@props(['items' => [], 'height' => 220, 'currency' => '', 'summary' => 'Bar chart'])
@php

    $vbW = 640;
    $vbH = (int) $height;
    $left = 46;
    $top = 14;
    $bottom = 28;
    $plotW = $vbW - $left - 8;
    $plotH = $vbH - $top - $bottom;
    $values = collect($items)->pluck('value')->map('floatval');
    $maxV = max(0.0, (float) $values->max());
    $minV = min(0.0, (float) $values->min());
    $axisMax = \App\Support\SvgChart::niceMax($maxV);
    $axisMin = $minV < 0 ? -\App\Support\SvgChart::niceMax(abs($minV)) : 0.0;
    $span = $axisMax - $axisMin;
    $yOf = fn (float $v) => $top + ($axisMax - $v) / $span * $plotH;
    $zero = $yOf(0.0);
    $n = max(1, count($items));
    $slot = $plotW / $n;
    $barW = min(34.0, $slot * 0.62);
    // Keep axis labels from colliding: take ticks in priority order and skip any within 16px of one already kept.
    $ticks = collect([0.0, $axisMax, $axisMin, $axisMax / 2, $axisMin / 2])
        ->filter(fn ($t) => $t >= $axisMin && $t <= $axisMax)->unique()
        ->reduce(function ($kept, $t) use ($yOf) {
            return $kept->contains(fn ($k) => abs($yOf($k) - $yOf($t)) < 16) ? $kept : $kept->push($t);
        }, collect());
@endphp
<svg viewBox="0 0 {{ $vbW }} {{ $vbH }}" class="h-auto w-full" role="img" aria-label="{{ $summary }}">
    <title>{{ $summary }}</title>
    @foreach($ticks as $t)
        <line x1="{{ $left }}" x2="{{ $vbW - 8 }}" y1="{{ round($yOf($t), 2) }}" y2="{{ round($yOf($t), 2) }}" style="stroke: var(--border)" stroke-dasharray="{{ abs($t) < 0.001 ? '0' : '3 4' }}"/>
        <text x="{{ $left - 8 }}" y="{{ round($yOf($t) + 3.5, 2) }}" text-anchor="end" font-size="10" style="fill: var(--muted-foreground)">{{ \App\Support\SvgChart::compact($t) }}</text>
    @endforeach
    @foreach($items as $i => $item)
        @php
            $v = (float) $item['value'];
            $cx = $left + $slot * $i + $slot / 2;
            $y = $v >= 0 ? $yOf($v) : $zero;
            $h = max(abs($yOf($v) - $zero), $v == 0.0 ? 0 : 2);
            $color = $v >= 0 ? ($item['color'] ?? 'var(--chart-2)') : 'var(--chart-5)';
            $short = $item['short'] ?? \Illuminate\Support\Str::limit(\Illuminate\Support\Str::afterLast((string) $item['label'], '-'), 6, '');
            $bar = '<rect x="' . round($cx - $barW / 2, 2) . '" y="' . round($y, 2) . '" width="' . round($barW, 2) . '" height="' . round($h, 2) . '" rx="3" class="fv-bar ' . ($v >= 0 ? 'fv-grow-y' : 'fv-grow-yn') . '" style="--i: ' . $i . '; fill: ' . $color . '"><title>' . e($item['label']) . ': ' . e($currency) . ' ' . number_format($v, 2) . '</title></rect>';
        @endphp
        @if(!empty($item['href']))
            <a href="{{ $item['href'] }}">{!! $bar !!}</a>
        @else
            {!! $bar !!}
        @endif
        @if($n <= 14 || $i % 2 === 0)
            <text x="{{ round($cx, 2) }}" y="{{ $vbH - 9 }}" text-anchor="middle" font-size="9.5" style="fill: var(--muted-foreground)">{{ $short }}</text>
        @endif
    @endforeach
    @if($values->isEmpty() || $values->every(fn ($v) => $v == 0.0))
        <text x="{{ $vbW / 2 }}" y="{{ $top + $plotH / 2 }}" text-anchor="middle" font-size="12" style="fill: var(--muted-foreground)">No data yet</text>
    @endif
</svg>
