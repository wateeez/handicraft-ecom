@extends('layouts.app')

@section('title', $pageTitle . ' | ' . $siteSettings['site_name'])
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($content), 160))
@section('canonical', url()->current())

@section('content')
    <article class="container mx-auto px-4 sm:px-6 py-12">
        <div class="mx-auto max-w-4xl rounded-2xl border border-border bg-card p-8 shadow-xs sm:p-10">
            <header class="mb-8 border-b border-border pb-6">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-primary">{{ $eyebrow }}</p>
                <h1 class="font-serif text-3xl font-bold text-foreground sm:text-4xl">{{ $heading }}</h1>
                <p class="mt-3 text-sm text-muted-foreground">
                    Last updated <time datetime="{{ $lastUpdated->toDateString() }}">{{ $lastUpdated->format('F j, Y') }}</time>
                </p>
            </header>
            <div class="prose max-w-none whitespace-pre-line leading-relaxed text-foreground/90">{{ $content }}</div>
        </div>
    </article>
@endsection
