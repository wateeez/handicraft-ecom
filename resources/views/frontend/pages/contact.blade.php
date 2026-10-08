@extends('layouts.app')

@section('title', 'Contact | ' . $siteSettings['site_name'])
@section('meta_description', 'Contact ' . $siteSettings['site_name'] . ' for product, shipping, and order support.')
@section('canonical', route('pages.contact'))

@section('content')
    <article class="container mx-auto px-4 sm:px-6 py-12">
        <div class="mx-auto max-w-3xl rounded-2xl border border-border bg-card p-8 shadow-xs sm:p-10">
            <header class="mb-8 border-b border-border pb-6">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-primary">Customer Care</p>
                <h1 class="font-serif text-3xl font-bold text-foreground sm:text-4xl">Contact Us</h1>
                <p class="mt-3 text-muted-foreground">Ask about products, international delivery, or an existing order.</p>
            </header>
            <dl class="space-y-5">
                @if($siteSettings['footer_email'])
                    <div>
                        <dt class="text-sm font-semibold text-foreground">Email</dt>
                        <dd class="mt-1"><a class="text-primary hover:underline" href="mailto:{{ $siteSettings['footer_email'] }}">{{ $siteSettings['footer_email'] }}</a></dd>
                    </div>
                @endif
                @if($siteSettings['footer_phone'])
                    <div>
                        <dt class="text-sm font-semibold text-foreground">Phone</dt>
                        <dd class="mt-1"><a class="text-primary hover:underline" href="tel:{{ preg_replace('/[^0-9+]/', '', $siteSettings['footer_phone']) }}">{{ $siteSettings['footer_phone'] }}</a></dd>
                    </div>
                @endif
                @if($siteSettings['footer_address'])
                    <div>
                        <dt class="text-sm font-semibold text-foreground">Business address</dt>
                        <dd class="mt-1 whitespace-pre-line text-muted-foreground">{{ $siteSettings['footer_address'] }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </article>
@endsection
