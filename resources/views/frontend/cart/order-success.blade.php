@extends('layouts.app')

@section('title', 'Order Confirmed')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center px-4 py-16">
    <div class="max-w-lg w-full bg-card rounded-2xl shadow-xl border border-border p-8 sm:p-10 text-center">

        {{-- Success icon --}}
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shadow-inner">
                <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5"
                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>

        <h1 class="text-3xl font-serif font-bold text-foreground mb-2">Thank you for your order!</h1>
        <p class="text-muted-foreground mb-6 text-sm">
            Your transaction was successful and your artisan shipment is being prepared.
        </p>

        <div class="bg-paper/60 rounded-xl border border-border px-6 py-4 mb-6 text-left space-y-2.5 text-sm text-foreground">
            <div class="flex justify-between items-center">
                <span class="text-muted-foreground">Order reference</span>
                <span class="font-mono font-semibold text-foreground">#{{ $order->order_number }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-muted-foreground">Total paid</span>
                <span class="font-bold text-primary">${{ number_format($order->grand_total, 2) }}</span>
            </div>
            @if ($order->client?->email)
            <div class="flex justify-between items-center">
                <span class="text-muted-foreground">Confirmation to</span>
                <span class="font-medium text-foreground truncate max-w-[200px]">{{ $order->client->email }}</span>
            </div>
            @endif
        </div>

        <div class="mb-8 rounded-xl border border-border bg-paper/40 px-6 py-5 text-left">
            <h2 class="mb-3 text-sm font-serif font-bold text-foreground uppercase tracking-wider">Order Summary</h2>
            <div class="space-y-3">
                @foreach($order->items as $item)
                    <div class="border-b border-border/50 pb-3 last:border-b-0 last:pb-0">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-medium text-foreground text-sm">{{ $item->product_name }}</div>
                                <div class="mt-0.5 text-xs text-muted-foreground">
                                    Qty {{ $item->quantity }} &times; ${{ number_format($item->unit_price, 2) }}
                                </div>
                            </div>
                            <div class="font-semibold text-foreground text-sm">
                                ${{ number_format($item->line_total, 2) }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <a href="{{ route('home') }}"
           class="inline-flex items-center justify-center bg-primary hover:bg-primary/90 text-primary-foreground font-semibold px-8 py-3.5 rounded-xl shadow-md hover:shadow-lg transition-all duration-200">
            Continue Exploring
        </a>
    </div>
</div>
@endsection
