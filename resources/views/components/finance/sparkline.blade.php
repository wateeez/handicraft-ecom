@props(['values' => [], 'color' => 'var(--chart-2)', 'w' => 110, 'h' => 34])
@php
    $geo = \App\Support\SvgChart::line($values, $w, $h, 3);
    $uid = 'sp' . substr(md5(uniqid('', true)), 0, 8);
@endphp
@if($geo['line'] && collect($values)->contains(fn ($v) => (float) $v != 0.0))
    <svg viewBox="0 0 {{ $w }} {{ $h }}" width="{{ $w }}" height="{{ $h }}" class="shrink-0" aria-hidden="true" focusable="false">
        <defs><linearGradient id="{{ $uid }}" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" style="stop-color: {{ $color }}; stop-opacity: .3"/><stop offset="100%" style="stop-color: {{ $color }}; stop-opacity: 0"/></linearGradient></defs>
        <path d="{{ $geo['area'] }}" fill="url(#{{ $uid }})" class="fv-fade"/>
        <path d="{{ $geo['line'] }}" pathLength="1" fill="none" class="fv-draw" style="stroke: {{ $color }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
@endif
