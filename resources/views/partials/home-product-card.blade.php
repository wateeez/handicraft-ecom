<a href="{{ route('products.show', $product->slug ?? $product->id) }}"
    class="group bg-card rounded-2xl overflow-hidden shadow-xs hover:shadow-xl transition-all duration-300 border border-border card-lift shimmer-sweep block flex flex-col justify-between">
    <!-- Image -->
    <div class="relative aspect-[3/4] overflow-hidden bg-muted">
        @if($product->main_image)
            <img src="{{ $product->main_image }}" alt="{{ $product->name }}"
                loading="lazy"
                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
        @else
            <div class="w-full h-full flex items-center justify-center text-muted-foreground text-sm font-medium">No Image</div>
        @endif

        @include('partials.admin-edit-product-button', ['class' => 'absolute top-3 left-3 z-10'])

    <!-- Discount Badge -->
        @if($product->discount_price && $product->price > $product->discount_price)
            @php
                $discountPercent = round((($product->price - $product->discount_price) / $product->price) * 100);
            @endphp
            <div class="absolute top-3 right-3">
                <span class="bg-destructive text-white text-xs px-2.5 py-1 rounded-full font-bold shadow-md animate-pulse-glow">
                    -{{ $discountPercent }}%
                </span>
            </div>
        @endif

        <!-- Actions Overlay -->
        <div
            class="absolute inset-x-0 bottom-0 p-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex justify-center pb-6 bg-gradient-to-t from-black/60 to-transparent">
            <span
                class="bg-card text-foreground px-5 py-2 rounded-full font-semibold text-xs tracking-wide group-hover:bg-primary group-hover:text-primary-foreground transition shadow-lg transform translate-y-3 group-hover:translate-y-0 duration-300">
                View Details
            </span>
        </div>
    </div>

    <!-- Content -->
    <div class="p-5 text-left flex-1 flex flex-col items-start gap-2 border-t border-border">
        @unless($product->is_order_now_enabled)
            <span
                class="bg-accent/20 text-accent-foreground border border-accent/30 text-[12px] px-2.5 py-0.5 rounded-full uppercase tracking-wider font-semibold">Inquiry
                Only</span>
        @endunless
        <h2 class="w-full text-left text-pretty text-xl sm:text-base lg:text-lg leading-snug font-serif font-bold text-truffle-dark break-words group-hover:text-primary transition-colors">{{ $product->name }}</h2>
        <div class="mt-auto pt-1 flex items-center gap-2 font-sans tabular-nums">
            <span class="inline-flex items-center rounded-lg border border-primary/40 px-3 py-1 text-xl font-bold text-primary">${{ number_format($product->discount_price ?: $product->price, 2) }}</span>
            @if($product->discount_price)
                <span class="text-muted-foreground line-through text-sm font-medium">${{ number_format($product->price, 2) }}</span>
            @endif
        </div>
    </div>
</a>
