@extends('layouts.app')

@section('title', $page['meta_title'])
@section('meta_description', $page['meta_description'])
@section('canonical', route('about'))
@if($page['hero_image_url'])
    @section('og_image', $page['hero_image_url'])
    @section('og_image_alt', $page['hero_image_alt'])
@endif

@push('head')
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endpush

@section('content')
    <article class="container mx-auto px-4 sm:px-6 py-12">
        <div class="mx-auto max-w-4xl rounded-2xl border border-border bg-card p-8 shadow-xs sm:p-10">
            <header class="mb-8 border-b border-border pb-6">
                <h1 class="font-serif text-3xl font-bold text-foreground sm:text-4xl">{{ $page['hero_heading'] }}</h1>
                <p class="mt-4 leading-relaxed text-foreground/90">{{ $page['hero_intro'] }}</p>
                @if($page['hero_image_url'])
                    <img src="{{ $page['hero_image_url'] }}" alt="{{ $page['hero_image_alt'] }}"
                        class="mt-6 w-full rounded-xl object-cover" loading="eager" decoding="async">
                @endif
            </header>

            @foreach($page['sections'] as $section)
                <section class="mb-8" aria-labelledby="about-{{ $section['key'] ?: $loop->index }}">
                    <h2 id="about-{{ $section['key'] ?: $loop->index }}" class="mb-3 font-serif text-2xl font-semibold text-foreground">{{ $section['heading'] }}</h2>
                    @if($section['html'])
                        <div class="prose max-w-none leading-relaxed text-foreground/90 [&_a]:text-primary [&_a]:underline [&_p]:mb-4">{!! $section['html'] !!}</div>
                    @endif
                    @if(count($section['items']))
                        <ul class="list-disc space-y-2 pl-6 leading-relaxed text-foreground/90">
                            @foreach($section['items'] as $item)
                                <li>@if($item['term'])<strong>{{ $item['term'] }}:</strong> @endif{{ $item['text'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach

            <footer class="mt-10 flex flex-col gap-3 border-t border-border pt-6 sm:flex-row sm:items-center">
                <a href="{{ config('site.shop_url') }}"
                    class="inline-flex items-center justify-center rounded-full bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground hover:opacity-90">Shop handcrafted Buddhist statues</a>
                <a href="mailto:{{ $contactEmail }}"
                    class="inline-flex items-center justify-center rounded-full border border-border px-6 py-3 text-sm font-semibold text-foreground hover:border-primary hover:text-primary">Send us an inquiry at {{ $contactEmail }}</a>
            </footer>
            <p class="mt-4 text-xs text-muted-foreground">
                Last updated <time datetime="{{ $page['updated_at']->toDateString() }}">{{ $page['updated_at']->format('F j, Y') }}</time>
            </p>
        </div>
    </article>
@endsection
