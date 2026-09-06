@extends('layouts.app')

@section('title', 'Quote Request Received')

@section('content')
<div class="min-h-[65vh] flex items-center justify-center px-4 py-16">
    <div class="max-w-lg w-full bg-card rounded-2xl shadow-xl border border-border p-8 sm:p-10 text-center">

        {{-- Success icon --}}
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center shadow-inner">
                <svg class="w-10 h-10 text-primary" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-3xl font-serif font-bold text-foreground mb-2">Quote Request Received</h1>
        <p class="text-muted-foreground mb-8 text-sm leading-relaxed">
            Thank you for your interest. Our artisans and logistics team have received your cart details and will prepare your tailored invoice with shipping estimates shortly.
        </p>

        <div class="bg-paper/60 rounded-xl border border-border px-6 py-5 mb-8 text-left space-y-3.5">
            <h2 class="text-sm font-serif font-bold text-foreground mb-1 uppercase tracking-wider">What happens next?</h2>
            <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-primary text-primary-foreground text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</div>
                <p class="text-sm text-foreground/90">Our logistics team verifies real-time courier weight and crating options.</p>
            </div>
            <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-primary text-primary-foreground text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</div>
                <p class="text-sm text-foreground/90">We dispatch a personalized invoice and one-click payment link directly to your inbox.</p>
            </div>
            <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-primary text-primary-foreground text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</div>
                <p class="text-sm text-foreground/90">You review the final rate and complete your order securely at your leisure.</p>
            </div>
        </div>

        <a href="{{ route('home') }}"
           class="inline-flex items-center justify-center bg-primary hover:bg-primary/90 text-primary-foreground font-semibold px-8 py-3.5 rounded-xl shadow-md hover:shadow-lg transition-all duration-200">
            Return to Storefront
        </a>
    </div>
</div>
@endsection
