<div class="flex-shrink-0 w-64 snap-start">
    <a href="{{ route('products.show', $product->slug ?? $product->id) }}" class="group bg-cream rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 h-full block">
        <!-- Image -->
        <div class="relative aspect-[3/4] overflow-hidden bg-[#F5F2EA]">
            @if($product->main_image)
                <img src="{{ $product->main_image }}" alt="{{ $product->name }}" loading="lazy" decoding="async"
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
            @else
                <div class="w-full h-full flex items-center justify-center text-truffle-extra-dark/70">No Image</div>
            @endif

            <!-- Badge -->
            @if(isset($badge))
                @php
                    $colorClasses = [
                        'green' => 'bg-primary',
                        'blue' => 'bg-secondary',
                        'purple' => 'bg-accent',
                        'red' => 'bg-red-600',
                    ];
                    $bgClass = $colorClasses[$badgeColor ?? 'green'] ?? 'bg-primary';
                @endphp
                <div class="absolute top-3 left-3">
                    <span class="{{ $bgClass }} text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider shadow-lg">
                        {{ $badge }}
                    </span>
                </div>
            @endif

            <!-- Discount Badge -->
            @if($product->discount_price && $product->price > $product->discount_price)
                @php
                    $discountPercent = round((($product->price - $product->discount_price) / $product->price) * 100);
                @endphp
                <div class="absolute top-3 right-3">
                    <span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full font-bold shadow-lg">
                        -{{ $discountPercent }}%
                    </span>
                </div>
            @endif

            <!-- Quick View Overlay -->
            <div class="absolute inset-x-0 bottom-0 p-4 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity duration-300 flex justify-center pb-6 bg-gradient-to-t from-black/50 to-transparent">
                <span
                    class="bg-cream text-truffle-extra-dark px-5 py-2 rounded-full font-medium group-hover:bg-green-premium group-hover:text-white transition shadow-lg transform translate-y-4 group-hover:translate-y-0 duration-300 text-sm">
                    View Details
                </span>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4 text-center">
            <h3 class="text-xl leading-tight font-serif font-bold text-truffle-extra-dark mb-2 truncate" title="{{ $product->name }}">
                {{ $product->name }}
            </h3>
            <div class="text-green-premium font-bold font-sans tabular-nums">
                @if($product->discount_price)
                    <span class="text-truffle-extra-dark/70 line-through text-sm mr-1">${{ number_format($product->price, 2) }}</span>
                    <span class="text-lg">${{ number_format($product->discount_price, 2) }}</span>
                @else
                    <span class="text-lg">${{ number_format($product->price, 2) }}</span>
                @endif
            </div>
        </div>
    </a>
</div>
