@extends('layouts.app')

@section('title', 'Blog - ' . $siteSettings['site_name'])
@section('meta_description', 'Read our latest blog posts, tips, and insights.')
@if(request()->filled('search') || request()->filled('page'))
    @section('robots', 'noindex, follow')
@endif

@section('content')
<div class="container mx-auto px-4 sm:px-6 py-12">
    <!-- Header -->
    <div class="text-center mb-12">
        <span class="inline-block text-xs font-semibold uppercase tracking-wider text-primary mb-2">Artisan Chronicles</span>
        <h1 class="text-4xl sm:text-5xl font-serif font-bold text-foreground mb-4">Stories of Craft & Heritage</h1>
        <p class="text-muted-foreground max-w-2xl mx-auto text-base">Discover the traditions, master artisans, and cultural heritage behind every handcrafted treasure.</p>
    </div>

    <!-- Search -->
    <div class="max-w-xl mx-auto mb-14">
        <form action="{{ route('blog.index') }}" method="GET" class="flex gap-2">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="Search stories, crafts, traditions..."
                    class="w-full px-5 py-3.5 bg-card border border-border rounded-xl text-foreground placeholder:text-muted-foreground/60 focus:ring-2 focus:ring-primary/30 focus:border-primary transition outline-none shadow-xs">
            </div>
            <button type="submit" class="px-6 py-3.5 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-xl shadow-sm hover:shadow transition-all">
                Search
            </button>
        </form>
    </div>

    <!-- Blog Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @forelse($posts as $post)
            <article class="bg-card rounded-2xl overflow-hidden shadow-xs hover:shadow-xl border border-border transition-all duration-300 transform hover:-translate-y-1 flex flex-col group">
                <!-- Featured Image -->
                <a href="{{ route('blog.show', $post->slug) }}" class="block aspect-[16/10] overflow-hidden bg-paper/60 relative">
                    @if($post->featured_image)
                        <img src="{{ $post->featured_image }}" alt="{{ $post->title }}"
                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-paper text-muted-foreground/40">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                            </svg>
                        </div>
                    @endif
                </a>

                <!-- Content -->
                <div class="p-6 sm:p-7 flex-1 flex flex-col justify-between">
                    <div>
                        <!-- Meta -->
                        <div class="flex items-center gap-4 text-xs font-medium text-muted-foreground mb-3">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $post->published_at->format('M d, Y') }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $post->reading_time }} min read
                            </span>
                        </div>

                        <!-- Title -->
                        <h2 class="text-xl font-serif font-bold text-foreground mb-3 line-clamp-2 leading-snug group-hover:text-primary transition-colors">
                            <a href="{{ route('blog.show', $post->slug) }}">
                                {{ $post->title }}
                            </a>
                        </h2>

                        <!-- Excerpt -->
                        <p class="text-muted-foreground text-sm mb-4 line-clamp-3 leading-relaxed">
                            {{ $post->short_excerpt }}
                        </p>
                    </div>

                    <!-- Read More -->
                    <div class="pt-4 border-t border-border/50">
                        <a href="{{ route('blog.show', $post->slug) }}" 
                            class="inline-flex items-center text-primary font-semibold text-sm hover:underline gap-1.5 group/link">
                            Read Story
                            <svg class="w-4 h-4 transition-transform group-hover/link:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full text-center py-16 bg-card rounded-2xl border border-border p-8">
                <svg class="w-16 h-16 mx-auto mb-4 text-muted-foreground/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
                <h3 class="text-xl font-serif font-bold text-foreground mb-2">No articles found</h3>
                <p class="text-muted-foreground text-sm">
                    @if(request('search'))
                        No stories match "{{ request('search') }}". <a href="{{ route('blog.index') }}" class="text-primary font-semibold hover:underline">View all stories</a>
                    @else
                        Our artisans are writing new stories. Please check back soon.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($posts->hasPages())
        <div class="mt-12 flex justify-center">
            {{ $posts->appends(request()->query())->links() }}
        </div>
    @endif
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endsection
