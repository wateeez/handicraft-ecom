@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 py-12">
        <div class="mb-8">
            <h1 class="text-3xl sm:text-4xl font-serif font-bold text-foreground">Artisan Cart</h1>
            <p class="text-xs text-muted-foreground mt-1">Review your curated craft selections before requesting a quote.</p>
        </div>

        @if(count($cartItems) > 0)
            <div class="bg-card rounded-2xl shadow-xs border border-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Unit Price</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3.5 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider">Subtotal</th>
                                <th class="px-6 py-3.5 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wider">Remove</th>
                            </tr>
                        </thead>
                        <tbody class="bg-card divide-y divide-border">
                            @foreach($cartItems as $item)
                                <tr class="hover:bg-muted/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-4">
                                            <div class="h-12 w-12 flex-shrink-0 rounded-xl overflow-hidden bg-muted border border-border">
                                                @if($item['product']->main_image)
                                                    <img class="h-full w-full object-cover"
                                                        src="{{ $item['product']->main_image }}" alt="{{ $item['product']->name }}">
                                                @else
                                                    <div class="h-full w-full bg-muted flex items-center justify-center text-xs text-muted-foreground">Craft</div>
                                                @endif
                                            </div>
                                            <div>
                                                <a href="{{ route('products.show', $item['product']->slug ?? $item['product']->id) }}"
                                                    class="text-sm font-serif font-bold text-foreground hover:text-primary transition-colors block">
                                                    {{ $item['product']->name }}
                                                </a>

                                                <div class="text-[11px] text-muted-foreground mt-1 flex items-center gap-2">
                                                    <span>{{ $item['product']->formatted_length }} &times; {{ $item['product']->formatted_width }} &times; {{ $item['product']->formatted_height }} cm</span>
                                                    <span>&bull;</span>
                                                    <span>{{ $item['product']->formatted_weight }} kg</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="font-serif font-bold text-primary">${{ number_format($item['unit_price'], 2) }}</div>
                                        @if($item['product']->discount_price)
                                            <div class="text-[10px] text-muted-foreground line-through">
                                                ${{ number_format($item['product']->price, 2) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <form action="{{ route('cart.update') }}" method="POST" class="flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="line_key" value="{{ $item['line_key'] }}">
                                            <input type="number" name="quantity" value="{{ $item['quantity'] }}" 
                                                min="{{ $item['product']->min_quantity }}" 
                                                class="w-16 rounded-lg border border-input bg-background p-1.5 text-center text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-ring"
                                                onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-serif font-bold text-foreground">
                                        ${{ number_format($item['subtotal'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <form action="{{ route('cart.remove') }}" method="POST" class="inline-block">
                                            @csrf
                                            <input type="hidden" name="line_key" value="{{ $item['line_key'] }}">
                                            <button type="submit" class="p-1.5 rounded-lg text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors" title="Remove Item">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-start mt-8 gap-6">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-primary hover:underline">
                    &larr; Continue Exploring Products
                </a>

                <div class="w-full md:w-96 bg-card border border-border p-6 rounded-2xl shadow-xs">
                    <h3 class="font-serif font-bold text-base text-foreground mb-4">Cart Summary</h3>
                    <div class="flex justify-between items-center mb-3 pb-3 border-b border-border text-sm">
                        <span class="text-muted-foreground">Subtotal Estimate</span>
                        <span class="font-serif font-bold text-lg text-foreground">${{ number_format($subtotal, 2) }}</span>
                    </div>
                    <p class="text-[11px] text-muted-foreground mb-5 leading-relaxed">
                        Worldwide shipping calculated at the next step. No direct payment required at this stage.
                    </p>
                    <a href="{{ route('checkout') }}"
                        class="w-full bg-primary text-primary-foreground text-center py-3 rounded-xl font-semibold text-xs tracking-wide uppercase hover:bg-primary/90 transition-all flex items-center justify-center gap-2 shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Proceed to Quote
                    </a>
                </div>
            </div>
        @else
            <div class="bg-card border border-border rounded-3xl p-16 text-center max-w-lg mx-auto shadow-xs">
                <div class="w-16 h-16 mx-auto rounded-full bg-primary/10 text-primary flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h3 class="font-serif font-bold text-xl text-foreground mb-2">Your Cart is Empty</h3>
                <p class="text-muted-foreground text-xs max-w-xs mx-auto mb-6">Discover unique handcrafted masterworks from local artisans.</p>
                <a href="{{ route('home') }}" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-semibold uppercase tracking-wide hover:bg-primary/90 transition-colors shadow-xs">
                    Start Shopping
                </a>
            </div>
        @endif
    </div>
@endsection
