@props(['percent' => 0, 'color' => 'var(--chart-2)', 'size' => 84, 'label' => ''])
@php
    $r = 34;
    $c = round(2 * M_PI * $r, 3);
    $pct = max(0, min(100, (int) $percent));
    $dash = round($c * $pct / 100, 3);
@endphp
<div class="relative shrink-0" style="width: {{ $size }}px; height: {{ $size }}px">
    <svg viewBox="0 0 84 84" class="h-full w-full -rotate-90" role="img" aria-label="{{ $label }} {{ $pct }}% paid">
        <circle cx="42" cy="42" r="{{ $r }}" fill="none" stroke-width="9" style="stroke: var(--muted)"/>
        <circle cx="42" cy="42" r="{{ $r }}" fill="none" stroke-width="9" stroke-linecap="round" class="fv-seg" style="--c: {{ $c }}; stroke: {{ $color }}; stroke-dasharray: {{ $dash }} {{ round($c - $dash, 3) }}"/>
    </svg>
    <span class="absolute inset-0 flex items-center justify-center text-sm font-bold tabular-nums">{{ $pct }}%</span>
</div>
