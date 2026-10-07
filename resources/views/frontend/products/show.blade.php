@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 py-12">

        <div class="flex flex-col md:flex-row gap-8 md:gap-12 bg-card border border-border p-4 sm:p-8 rounded-3xl shadow-sm">
            <!-- Image Section -->
            <div class="md:w-1/2">
                <!-- Main Image Display -->
                <div class="relative z-10">
                    <div id="imageContainer" class="rounded-2xl overflow-hidden mb-4 bg-muted/60 border border-border h-[45vh] sm:h-[50vh] md:h-[75vh] flex items-center justify-center relative md:cursor-crosshair card-lift">
                        @if($product->main_image)
                            <img src="{{ $product->main_image }}" alt="{{ $product->name }}"
                                class="max-h-full max-w-full object-contain transition-transform duration-300" id="mainProductImage">
                        @else
                            <span class="text-muted-foreground text-lg">No Image</span>
                        @endif
                    </div>
                
                    <!-- Zoom Result Container (Side View) -->
                    <div id="zoomResult" class="hidden fixed md:absolute left-0 md:left-[105%] top-0 md:top-0 w-full md:w-[500px] h-[500px] bg-card border border-border shadow-2xl z-50 rounded-2xl overflow-hidden glass"></div>
                </div>

                <script>
                    const container = document.getElementById('imageContainer');
                    const img = document.getElementById('mainProductImage');
                    const result = document.getElementById('zoomResult');

                    if (container && img && result && window.matchMedia('(min-width: 768px) and (hover: hover)').matches) {
                        // Wait for image to load to get natural dimensions
                        img.addEventListener('load', function() {
                            setupZoom();
                        });
                        
                        // Setup immediately if image already loaded
                        if (img.complete) {
                            setupZoom();
                        }

                        function setupZoom() {
                            container.addEventListener('mousemove', handleMouseMove);
                            container.addEventListener('mouseleave', () => {
                                result.classList.add('hidden');
                            });
                        }

                        function handleMouseMove(e) {
                            const rect = container.getBoundingClientRect();
                            const x = e.clientX - rect.left;
                            const y = e.clientY - rect.top;
                            
                            // Check if cursor is within the container
                            if (x < 0 || x > rect.width || y < 0 || y > rect.height) {
                                result.classList.add('hidden');
                                return;
                            }

                            // Show result
                            result.classList.remove('hidden');
                            
                            // Set background image
                            result.style.backgroundImage = `url('${img.src}')`;
                            result.style.backgroundRepeat = 'no-repeat';
                            
                            // Use natural image dimensions with zoom factor
                            const zoomLevel = 2.5;
                            const naturalWidth = img.naturalWidth;
                            const naturalHeight = img.naturalHeight;
                            
                            // Calculate size maintaining aspect ratio
                            result.style.backgroundSize = `${naturalWidth * zoomLevel}px ${naturalHeight * zoomLevel}px`;
                            
                            // Calculate background position
                            const xRatio = x / rect.width;
                            const yRatio = y / rect.height;
                            
                            // Position background so cursor position appears in center of result
                            const bgX = xRatio * 100;
                            const bgY = yRatio * 100;
                            
                            result.style.backgroundPosition = `${bgX}% ${bgY}%`;
                        }
                    }
                </script>
                
                <!-- Horizontal Scrollable Thumbnails -->
                @if($product->main_image || $product->secondary_image || ($product->images && count($product->images) > 0))
                    <div class="relative">
                        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                            <!-- Main image thumbnail -->
                            @if($product->main_image)
                                <img src="{{ $product->main_image }}" 
                                    onclick="document.getElementById('mainProductImage').src='{{ $product->main_image }}'"
                                    class="h-20 w-20 object-cover rounded-lg border-2 border-primary cursor-pointer hover:opacity-75 transition flex-shrink-0"
                                    alt="Main">
                            @endif
                            <!-- Secondary image thumbnail -->
                            @if($product->secondary_image)
                                <img src="{{ $product->secondary_image }}" 
                                    onclick="document.getElementById('mainProductImage').src='{{ $product->secondary_image }}'"
                                    class="h-20 w-20 object-cover rounded-lg border-2 border-truffle-medium/30 cursor-pointer hover:border-primary hover:opacity-75 transition flex-shrink-0"
                                    alt="Secondary">
                            @endif
                            <!-- Additional images -->
                            @foreach($product->images ?? [] as $image)
                                <img src="{{ $image }}" 
                                    onclick="document.getElementById('mainProductImage').src='{{ $image }}'"
                                    class="h-20 w-20 object-cover rounded-lg border-2 border-truffle-medium/30 cursor-pointer hover:border-primary hover:opacity-75 transition flex-shrink-0"
                                    alt="Product image">
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Details Section -->
            <div class="md:w-1/2">
                @if($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="mb-4">
                    <span
                        class="text-truffle-extra-dark uppercase tracking-widest text-sm font-semibold">{{ $product->category->name ?? 'Uncategorized' }}</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-serif font-bold text-truffle-extra-dark mb-4">{{ $product->name }}</h1>

                @if($product->description)
                    <div class="prose text-truffle-extra-dark mb-8 max-w-none">
                        <p>{{ $product->description }}</p>
                    </div>
                @endif

                <div class="mb-8">
                    <h3 class="font-serif font-bold text-lg mb-4 text-truffle-extra-dark">Product Specifications</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4 text-sm">
                        <dt class="text-truffle-extra-dark">Dimensions (L x W x H)</dt>
                        <dd class="font-medium text-truffle-extra-dark">{{ $product->formatted_length }}cm x {{ $product->formatted_width }}cm x
                            {{ $product->formatted_height }}cm</dd>

                        <dt class="text-truffle-extra-dark">Weight</dt>
                        <dd class="font-medium text-truffle-extra-dark">{{ $product->formatted_weight }} kg</dd>

                        <dt class="text-truffle-extra-dark">Material</dt>
                        <dd class="font-medium text-truffle-extra-dark">{{ $product->material ?? 'N/A' }}</dd>

                        <dt class="text-truffle-extra-dark">SKU</dt>
                        <dd class="font-medium text-truffle-extra-dark">{{ $product->sku ?? 'N/A' }}</dd>
                    </dl>
                </div>

                <div class="flex flex-col gap-4">
                    {{-- Order Now Button --}}
                    @if($product->is_order_now_enabled)
                        <form action="{{ route('cart.add') }}" method="POST"
                            x-data="productPurchaseForm({
                                minQuantity: {{ (int) $product->min_quantity }},
                                basePrices: { normal: {{ number_format((float) $product->price, 2, '.', '') }}, sale: {{ number_format((float) ($product->discount_price ?? $product->price), 2, '.', '') }} },
                                saleAvailable: {{ $product->hasSalePrice() ? 'true' : 'false' }}
                            })"
                            class="space-y-5">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">

                            <div class="rounded-2xl border border-truffle-medium/30 bg-[#F5F2EA] p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold uppercase tracking-wide text-truffle-extra-dark/70">Live Total</p>
                                        <div class="mt-2 text-3xl font-bold text-primary">
                                            $<span x-text="formatMoney(totalUnitPrice)"></span>
                                        </div>
                                    </div>
                                    @if($product->discount_price)
                                        <div class="text-right text-sm text-truffle-extra-dark/70">
                                            <div>Normal: ${{ number_format($product->price, 2) }}</div>
                                            <div>Sale: ${{ number_format($product->discount_price, 2) }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>





                            <div class="flex items-center gap-4">
                                <label class="font-medium text-truffle-extra-dark">Quantity:</label>
                                <div class="flex items-center border border-truffle-medium/30 rounded-full overflow-hidden">
                                    <button type="button" @click="qty = Math.max(minQuantity, qty - 1)"
                                        class="w-9 h-9 flex items-center justify-center text-truffle-extra-dark hover:text-truffle-extra-dark hover:bg-[#F5F2EA] transition text-lg leading-none select-none">-</button>
                                    <input type="number" name="quantity" x-model="qty" :min="minQuantity"
                                        class="w-10 text-center text-sm font-medium bg-transparent border-none focus:outline-none">
                                    <button type="button" @click="qty++"
                                        class="w-9 h-9 flex items-center justify-center text-truffle-extra-dark hover:text-truffle-extra-dark hover:bg-[#F5F2EA] transition text-lg leading-none select-none">+</button>
                                </div>
                            </div>
                            <button type="submit"
                                class="w-full bg-primary text-white text-lg font-bold py-4 rounded-full shadow-lg hover:opacity-90 transition transform hover:-translate-y-1">
                                Add to Cart
                            </button>
                            <p class="text-xs text-center text-truffle-extra-dark mt-2">Free shipping calculation at checkout.</p>
                        </form>
                    @endif

                    {{-- Inquiry Button / Form Toggle --}}
                    <div x-data="{ open: false }" class="mt-4">
                        @php
                            $waNumber = preg_replace('/[^0-9]/', '', $siteSettings['whatsapp_number'] ?? '');
                            $defaultTemplate = 'Hi! I am interested in: {product_name} (SKU: {sku} | Price: ${price}) - {url}';
                            $waTemplate = !empty($siteSettings['whatsapp_message_template'])
                                ? $siteSettings['whatsapp_message_template']
                                : $defaultTemplate;
                            $effectivePrice = $product->discount_price ?? $product->price;
                            $waMessage = str_replace(
                                ['{product_name}', '{sku}', '{price}', '{url}'],
                                [
                                    $product->name,
                                    $product->sku ?? 'N/A',
                                    number_format($effectivePrice, 2),
                                    url()->current(),
                                ],
                                $waTemplate
                            );
                        @endphp
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button @click="open = !open"
                                class="flex-1 bg-primary text-white text-lg font-bold py-4 rounded-full shadow-lg hover:opacity-90 transition transform hover:-translate-y-1">
                                Make an Inquiry
                            </button>

                            @if(!empty($waNumber))
                                <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMessage) }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="Chat on WhatsApp about {{ $product->name }}"
                                   class="flex w-full sm:w-auto items-center justify-center gap-2 bg-[#25D366] text-white font-bold py-3 px-6 rounded-full hover:bg-[#128C7E] transition shadow-sm">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    WhatsApp
                                </a>
                            @endif
                        </div>

                        <div x-show="open" class="mt-6 border-t pt-6" x-transition>
                            <h3 class="font-serif font-bold text-xl mb-4 text-center">Interested? Send us an inquiry</h3>
                            <form action="{{ route('inquiry.store', $product) }}" method="POST" class="space-y-4">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <input type="text" name="name" placeholder="Full Name" required
                                        class="w-full p-3 bg-[#F5F2EA] border rounded-lg focus:ring-primary focus:border-primary">
                                    <input type="email" name="email" placeholder="Email Address" required
                                        class="w-full p-3 bg-[#F5F2EA] border rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <input type="text" name="phone" placeholder="Phone Number" required
                                        class="w-full p-3 bg-[#F5F2EA] border rounded-lg focus:ring-primary focus:border-primary">
                                    <input type="text" name="country" placeholder="Country" required
                                        class="w-full p-3 bg-[#F5F2EA] border rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                                <input type="text" name="address_line" placeholder="Address Line" required
                                    class="w-full p-3 bg-[#F5F2EA] border rounded-lg focus:ring-green-premium focus:border-green-premium">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <input type="text" name="city" placeholder="City"
                                        class="w-full p-3 bg-[#F5F2EA] border rounded-lg focus:ring-primary focus:border-primary">
                                    <input type="text" name="zip_code" placeholder="Zip Code"
                                        class="w-full p-3 bg-[#F5F2EA] border rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                                <textarea name="message" rows="3" placeholder="I am interested in this product..."
                                    class="w-full p-3 bg-[#F5F2EA] border rounded-lg focus:ring-green-premium focus:border-green-premium"></textarea>

                                <button type="submit"
                                    class="w-full bg-secondary text-white font-bold py-3 rounded-lg hover:opacity-90 transition">Send
                                    Inquiry</button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Long Description Section -->
        @if($product->long_description)
            <div class="mt-12 bg-card border border-border p-5 sm:p-10 rounded-3xl shadow-xs">
                <div class="flex items-center gap-3 mb-6 border-b border-border pb-4">
                    <span class="w-2.5 h-6 rounded-full bg-primary inline-block"></span>
                    <h2 class="text-2xl sm:text-3xl font-serif font-bold text-foreground">Artisan Notes & Craft Details</h2>
                </div>
                <div class="prose prose-lg max-w-none text-foreground/80 leading-relaxed font-sans">
                    {!! nl2br(e($product->long_description)) !!}
                </div>
            </div>
        @endif

        <!-- Related Products -->
        @if($relatedProducts->count() > 0)
            <div class="mt-16">
                <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between mb-8 border-b border-border pb-4">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-serif font-bold text-foreground">You May Also Admire</h2>
                        <p class="text-xs text-muted-foreground mt-1">Hand-picked companions from the same craft collection.</p>
                    </div>
                    <a href="{{ route('home', ['category' => $product->category->slug ?? '']) }}" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                        Explore Collection
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $relProduct)
                        <a href="{{ route('products.show', $relProduct->slug ?? $relProduct->id) }}"
                            class="group bg-card rounded-2xl overflow-hidden border border-border card-lift shimmer-sweep shadow-xs flex flex-col justify-between block transition-all">
                            <div class="bg-muted aspect-[3/4] overflow-hidden relative">
                                @if($relProduct->main_image)
                                    <img src="{{ $relProduct->main_image }}" alt="{{ $relProduct->name }}"
                                        loading="lazy"
                                        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-muted-foreground text-xs">No Image</div>
                                @endif
                                @if($relProduct->discount_price)
                                    <div class="absolute top-2.5 right-2.5">
                                        <span class="bg-destructive text-white text-[10px] px-2 py-0.5 rounded-full font-bold shadow-xs">Sale</span>
                                    </div>
                                @endif
                            </div>
                            <div class="p-4 text-center">
                                <h3 class="font-serif font-bold text-sm text-foreground group-hover:text-primary transition-colors truncate mb-1">{{ $relProduct->name }}</h3>
                                <div class="text-primary font-bold text-sm font-serif">
                                    @if($relProduct->discount_price)
                                        <span class="text-muted-foreground line-through text-xs mr-1 font-normal">${{ number_format($relProduct->price, 2) }}</span>
                                        <span>${{ number_format($relProduct->discount_price, 2) }}</span>
                                    @else
                                        <span>${{ number_format($relProduct->price, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
    {{-- Alpine JS for interaction --}}
    <script>
        function productPurchaseForm(config) {
            return {
                minQuantity: config.minQuantity,
                qty: config.minQuantity,
                basePrices: config.basePrices,
                saleAvailable: config.saleAvailable,
                get basePrice() {
                    return this.saleAvailable
                        ? Number(this.basePrices.sale || 0)
                        : Number(this.basePrices.normal || 0);
                },
                get totalUnitPrice() {
                    return this.basePrice;
                },
                formatMoney(value) {
                    return Number(value || 0).toFixed(2);
                },
            };
        }
    </script>
    <script src="//unpkg.com/alpinejs" defer></script>
@endsection
