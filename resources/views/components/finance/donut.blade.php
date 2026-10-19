@props(['items' => [], 'centerLabel' => '', 'centerValue' => '', 'currency' => '', 'summary' => 'Breakdown'])
@php

    $geo = \App\Support\SvgChart::donut(collect($items)->pluck('value')->all(), 42);
    $total = collect($items)->sum(fn ($i) => max(0, (float) $i['value']));
@endphp
<div class="flex flex-col items-center gap-5 sm:flex-row">
    <div class="relative h-44 w-44 shrink-0">
        <svg viewBox="0 0 120 120" class="h-full w-full" role="img" aria-label="{{ $summary }}">
            <title>{{ $summary }}</title>
            <circle cx="60" cy="60" r="42" fill="none" style="stroke: var(--muted)" stroke-width="14"/>
            @foreach($items as $i => $item)
                @php $seg = $geo['segments'][$i]; @endphp
                @if($seg['dash'] > 0)
                    <circle cx="60" cy="60" r="42" fill="none" stroke-width="14" transform="rotate(-90 60 60)"
                        class="fv-seg fv-bar" style="--i: {{ $i }}; --c: {{ $geo['circumference'] }}; stroke: {{ $item['color'] }}; stroke-dasharray: {{ $seg['dash'] }} {{ $seg['gap'] }}; stroke-dashoffset: {{ $seg['offset'] }}">
                        <title>{{ $item['label'] }}: {{ $currency }} {{ number_format((float) $item['value'], 2) }} ({{ number_format($seg['fraction'] * 100, 1) }}%)</title>
                    </circle>
                @endif
            @endforeach
        </svg>
        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center px-6 text-center">
            <span class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{{ $centerLabel }}</span>
            <span class="mt-0.5 break-all text-sm font-bold tabular-nums leading-tight">{{ $centerValue }}</span>
        </div>
    </div>
    <ul class="w-full min-w-0 flex-1 space-y-2 text-sm">
        @foreach($items as $i => $item)
            @php $pct = $total > 0 ? max(0, (float) $item['value']) / $total * 100 : 0; @endphp
            <li class="fv-rise flex items-center gap-2" style="--i: {{ $i + 2 }}">
                <span class="inline-block h-3 w-3 shrink-0 rounded-sm" style="background: {{ $item['color'] }}"></span>
                <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                <span class="tabular-nums text-muted-foreground">{{ number_format($pct, 1) }}%</span>
                <span class="w-32 text-right font-medium tabular-nums">{{ $currency }} {{ number_format((float) $item['value'], 2) }}</span>
            </li>
        @endforeach
    </ul>
</div>
