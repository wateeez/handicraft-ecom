@extends('layouts.app')

@section('title', 'Shipping Policy - ' . ($siteSettings['site_name'] ?? 'Handicraft'))

@section('content')
    <div class="container mx-auto px-4 sm:px-6 py-12">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-10">
                <span class="inline-block text-xs font-semibold uppercase tracking-wider text-primary mb-2">Customer Care</span>
                <h1 class="text-3xl sm:text-4xl font-serif font-bold text-foreground">Global Shipping & Delivery</h1>
            </div>
            
            <div class="bg-card rounded-2xl border border-border shadow-xs p-8 sm:p-10">
                @if($content)
                    <div class="prose prose-neutral max-w-none text-foreground/90 font-serif leading-relaxed">
                        {!! nl2br(e($content)) !!}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-muted-foreground/30 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-foreground font-medium">Shipping policy content is currently being updated.</p>
                        <p class="text-sm text-muted-foreground mt-2">Please reach out via our contact channels or WhatsApp for immediate delivery questions.</p>
                    </div>
                @endif
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-primary font-semibold text-sm hover:underline">
                    <span>&larr;</span> Back to Storefront
                </a>
            </div>
        </div>
    </div>
@endsection
