@extends('layouts.app')

@section('title')
{{ $post->seo_title }} - {{ $siteSettings['site_name'] }}
@endsection

@section('meta_description')
{{ $post->seo_description }}
@endsection

@section('meta_keywords')
{{ $post->meta_keywords }}
@endsection

@push('head')
    <!-- SEO Meta Tags -->
    <meta name="author" content="{{ $post->author->name ?? $siteSettings['site_name'] }}">
    <link rel="canonical" href="{{ route('blog.show', $post->slug) }}">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ route('blog.show', $post->slug) }}">
    <meta property="og:title" content="{{ $post->seo_title }}">
    <meta property="og:description" content="{{ $post->seo_description }}">
    @if($post->featured_image)
        <meta property="og:image" content="{{ url($post->featured_image) }}">
    @endif
    <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
    @if($post->author)
        <meta property="article:author" content="{{ $post->author->name }}">
    @endif
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $post->seo_title }}">
    <meta name="twitter:description" content="{{ $post->seo_description }}">
    @if($post->featured_image)
        <meta name="twitter:image" content="{{ url($post->featured_image) }}">
    @endif

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "BlogPosting",
        "headline": {{ json_encode($post->title) }},
        "description": {{ json_encode($post->seo_description) }},
        "image": {{ json_encode($post->featured_image ? url($post->featured_image) : '') }},
        "datePublished": {{ json_encode($post->published_at->toIso8601String()) }},
        "dateModified": {{ json_encode($post->updated_at->toIso8601String()) }},
        "author": {
            "@@type": "Person",
            "name": {{ json_encode($post->author->name ?? $siteSettings['site_name']) }}
        },
        "publisher": {
            "@@type": "Organization",
            "name": {{ json_encode($siteSettings['site_name']) }},
            "logo": {
                "@@type": "ImageObject",
                "url": {{ json_encode($siteSettings['navbar_logo_url'] ?? '') }}
            }
        },
        "mainEntityOfPage": {
            "@@type": "WebPage",
            "@@id": {{ json_encode(route('blog.show', $post->slug)) }}
        }
    }
    </script>
@endpush

@section('content')
<article class="container mx-auto px-4 sm:px-6 py-12">
    <!-- Breadcrumb -->
    <nav class="mb-8 text-sm">
        <ol class="flex flex-wrap items-center gap-2 text-muted-foreground">
            <li><a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a></li>
            <li><span>/</span></li>
            <li><a href="{{ route('blog.index') }}" class="hover:text-primary transition-colors">Blog</a></li>
            <li><span>/</span></li>
            <li class="text-foreground font-medium truncate max-w-xs">{{ $post->title }}</li>
        </ol>
    </nav>

    <!-- Article Header -->
    <header class="max-w-4xl mx-auto mb-12 text-center md:text-left">
        <span class="inline-block text-xs font-semibold uppercase tracking-wider text-primary mb-3">Artisan Journal</span>
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-serif font-bold text-foreground mb-6 leading-tight">
            {{ $post->title }}
        </h1>

        <!-- Meta Info -->
        <div class="flex flex-wrap items-center gap-6 text-sm text-muted-foreground mb-8 border-y border-border py-4">
            @if($post->author)
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center font-bold text-primary text-xs">
                        {{ strtoupper(substr($post->author->name, 0, 1)) }}
                    </div>
                    <span class="font-medium text-foreground">{{ $post->author->name }}</span>
                </div>
            @endif
            <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>{{ $post->published_at->format('F d, Y') }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ $post->reading_time }} min read</span>
            </div>
        </div>

        <!-- Featured Image -->
        @if($post->featured_image)
            <div class="aspect-[2/1] rounded-2xl overflow-hidden bg-card border border-border shadow-md">
                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}"
                    class="w-full h-full object-cover">
            </div>
        @endif
    </header>

    <!-- Article Content -->
    <div class="max-w-3xl mx-auto">
        <div class="prose prose-lg prose-neutral max-w-none text-foreground/90 font-serif leading-relaxed">
            {!! nl2br(e($post->content)) !!}
        </div>

        <!-- Share Buttons -->
        <div class="mt-14 pt-8 border-t border-border">
            <h3 class="text-base font-serif font-bold text-foreground mb-4 uppercase tracking-wider">Share this story</h3>
            <div class="flex flex-wrap gap-3">
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('blog.show', $post->slug)) }}" 
                    target="_blank" rel="noopener noreferrer"
                    class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium shadow-xs transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.77 7.46H14.5v-1.9c0-.9.6-1.1 1-1.1h3V.5h-4.33C10.24.5 9.5 3.44 9.5 5.32v2.15h-3v4h3v12h5v-12h3.85l.42-4z"/></svg>
                    Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('blog.show', $post->slug)) }}&text={{ urlencode($post->title) }}" 
                    target="_blank" rel="noopener noreferrer"
                    class="flex items-center gap-2 px-4 py-2.5 bg-sky-500 hover:bg-sky-600 text-white rounded-xl text-sm font-medium shadow-xs transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.44 4.83c-.8.37-1.5.38-2.22.02.93-.56.98-.96 1.32-2.02-.88.52-1.86.9-2.9 1.1-.82-.88-2-1.43-3.3-1.43-2.5 0-4.55 2.04-4.55 4.54 0 .36.03.7.1 1.04-3.77-.2-7.12-2-9.36-4.75-.4.67-.6 1.45-.6 2.3 0 1.56.8 2.95 2 3.77-.74-.03-1.44-.23-2.05-.57v.06c0 2.2 1.56 4.03 3.64 4.44-.67.2-1.37.2-2.06.08.58 1.8 2.26 3.12 4.25 3.16C5.78 18.1 3.37 18.74 1 18.46c2 1.3 4.4 2.04 6.97 2.04 8.35 0 12.92-6.92 12.92-12.93 0-.2 0-.4-.02-.6.9-.63 1.96-1.22 2.56-2.14z"/></svg>
                    Twitter
                </a>
                <a href="https://wa.me/?text={{ urlencode($post->title . ' - ' . route('blog.show', $post->slug)) }}" 
                    target="_blank" rel="noopener noreferrer"
                    class="flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-medium shadow-xs transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp
                </a>
            </div>
        </div>
    </div>

    <!-- Related Posts -->
    @if($relatedPosts->count() > 0)
        <section class="mt-16 pt-16 border-t border-border">
            <h2 class="text-2xl font-serif font-bold text-foreground mb-8 text-center">More Stories to Explore</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($relatedPosts as $related)
                    <article class="bg-card rounded-2xl overflow-hidden shadow-xs hover:shadow-xl border border-border transition-all duration-300 transform hover:-translate-y-1 group">
                        <a href="{{ route('blog.show', $related->slug) }}" class="block aspect-[16/10] overflow-hidden bg-paper/60">
                            @if($related->featured_image)
                                <img src="{{ $related->featured_image }}" alt="{{ $related->title }}"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-paper text-muted-foreground/40">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                    </svg>
                                </div>
                            @endif
                        </a>
                        <div class="p-5">
                            <p class="text-xs text-muted-foreground mb-2">{{ $related->published_at->format('M d, Y') }}</p>
                            <h3 class="font-serif font-bold text-foreground line-clamp-2 group-hover:text-primary transition-colors">
                                <a href="{{ route('blog.show', $related->slug) }}">
                                    {{ $related->title }}
                                </a>
                            </h3>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</article>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.prose {
    line-height: 1.8;
}
.prose p {
    margin-bottom: 1.5rem;
}
</style>
@endsection
