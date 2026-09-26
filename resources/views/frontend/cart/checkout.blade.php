@extends('layouts.app')

@section('title', 'Get a Quote')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 py-12" x-data="checkout()">
        <h1 class="text-3xl font-serif font-bold text-truffle-extra-dark mb-2">Get a Quote</h1>
        <p class="text-truffle-extra-dark/70 mb-8 text-sm">Fill in your details below and we'll send you a personalised quote via email.</p>

        <div class="flex flex-col md:flex-row gap-10">
            <!-- Form Section -->
            <div class="w-full md:w-2/3">
                <form action="{{ route('checkout.submit-quote') }}" method="POST" id="quote-form">
                    @csrf

                    {{-- hidden fields populated by Alpine --}}
                    <input type="hidden" name="shipping_cost" id="shipping_cost_input" value="0">
                    <input type="hidden" name="shipping_provider" id="shipping_provider_input" value="">

                    @if ($errors->any())
                        <div class="mb-6 bg-red-500/10 border border-red-500/30 rounded-xl p-4 text-red-700">
                            <ul class="list-disc list-inside text-sm space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Contact Info -->
                    <div class="bg-card p-6 md:p-8 rounded-2xl border border-border shadow-xs mb-6">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">1</div>
                            <h2 class="text-xl font-serif font-bold text-foreground">Contact Information</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Full Name</label>
                                <input type="text" name="name" value="{{ old('name', $inquiry->name ?? '') }}"
                                    placeholder="Jane Doe"
                                    class="w-full px-4 py-3 bg-paper/50 border border-border rounded-xl text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition"
                                    required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Email Address</label>
                                <input type="email" name="email" value="{{ old('email', $inquiry->email ?? '') }}"
                                    placeholder="jane@example.com"
                                    class="w-full px-4 py-3 bg-paper/50 border border-border rounded-xl text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition"
                                    required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Phone Number (Optional)</label>
                                <input type="text" name="phone" value="{{ old('phone', $inquiry->phone ?? '') }}"
                                    placeholder="+1 (555) 000-0000"
                                    class="w-full px-4 py-3 bg-paper/50 border border-border rounded-xl text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="bg-card p-6 md:p-8 rounded-2xl border border-border shadow-xs mb-6">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">2</div>
                            <h2 class="text-xl font-serif font-bold text-foreground">Shipping Destination</h2>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Street Address</label>
                                <input type="text" name="address" value="{{ old('address', $inquiry->address_line ?? '') }}"
                                    placeholder="123 Artisan Lane, Suite 4"
                                    class="w-full px-4 py-3 bg-paper/50 border border-border rounded-xl text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition"
                                    required>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">City</label>
                                    <input type="text" name="city" value="{{ old('city', $inquiry->city ?? '') }}"
                                        placeholder="City / Town"
                                        class="w-full px-4 py-3 bg-paper/50 border border-border rounded-xl text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition"
                                        required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Postal / Zip Code</label>
                                    <input type="text" name="zip_code" value="{{ old('zip_code', $inquiry->zip_code ?? '') }}"
                                        placeholder="Zip Code"
                                        class="w-full px-4 py-3 bg-paper/50 border border-border rounded-xl text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Country / Territory</label>
                                @if(isset($inquiry) && $inquiry->country)
                                    <div class="p-3 bg-paper/60 border border-border rounded-xl font-medium text-foreground">
                                        {{ $availableCountriesOptions[array_search($inquiry->country, array_column($availableCountriesOptions, 'value'))]['label'] ?? $inquiry->country }}
                                    </div>
                                    <input type="hidden" name="country" value="{{ $inquiry->country }}" x-model="country">
                                @else
                                    <select name="country" x-model="country" @change="fetchShippingRates"
                                        class="w-full px-4 py-3 bg-paper/50 border border-border rounded-xl text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition"
                                        required>
                                        <option value="">Select Country</option>
                                        @foreach($availableCountriesOptions as $opt)
                                            <option value="{{ $opt['value'] }}" {{ old('country', $inquiry->country ?? '') == $opt['value'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                <p class="text-xs text-red-500 mt-1.5" x-show="shippingError && shippingError !== 'Over Weight'" x-text="shippingError"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Method -->
                    <div class="bg-card p-6 md:p-8 rounded-2xl border border-border shadow-xs mb-6" x-show="loadingRates || rates.length > 0 || shippingError">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">3</div>
                            <h2 class="text-xl font-serif font-bold text-foreground">Shipping Method</h2>
                        </div>

                        <!-- Over Weight banner -->
                        <div x-show="shippingError === 'Over Weight'" x-cloak
                            class="flex items-start gap-4 p-5 rounded-xl border-2 border-[#8f3222] bg-[#8f3222]/[0.08]">
                            <div class="shrink-0 w-11 h-11 rounded-full bg-[#8f3222] text-white flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-lg font-serif font-bold text-[#8f3222] tracking-wide">Over Weight</p>
                                <p class="text-sm text-[#8f3222] mt-1">This order exceeds the maximum weight we can ship to this destination. Please reduce the quantity or contact us for a custom freight quote.</p>
                            </div>
                        </div>

                        <!-- Loading state -->
                        <div x-show="loadingRates" class="flex items-center gap-3 text-sm text-muted-foreground py-3">
                            <svg class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Calculating precise courier rates...
                        </div>

                        <div class="space-y-3" x-show="rates.length > 0 && shippingError !== 'Over Weight'">
                            <template x-for="rate in rates" :key="rate.provider_name + rate.price">
                                <label class="flex items-center justify-between p-4 border rounded-xl cursor-pointer transition-all duration-200"
                                    :class="selectedProvider === rate.provider_name ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-border bg-card hover:bg-paper/40'">
                                    <div class="flex items-center gap-3.5">
                                        <input type="radio" name="shipping_rate_display" :value="rate.price"
                                            @change="selectShipping(rate)"
                                            class="h-4 w-4 text-primary focus:ring-primary border-border">
                                        <div>
                                            <span class="block text-sm font-semibold text-foreground" x-text="rate.provider_name"></span>
                                            <span class="block text-xs text-muted-foreground" x-text="'Zone: ' + rate.details.zone"></span>
                                        </div>
                                    </div>
                                    <span class="text-base font-bold text-primary" x-text="'$' + rate.price"></span>
                                </label>
                            </template>
                        </div>

                        <p class="text-sm text-muted-foreground mt-3" x-show="!loadingRates && rates.length === 0 && !shippingError">
                            Select a destination country above to review available courier methods.
                        </p>
                    </div>

                    <!-- Get Quote CTA -->
                    <div class="bg-card p-6 md:p-8 rounded-2xl border border-border shadow-xs mb-6">
                        <h2 class="text-xl font-serif font-bold mb-2 text-foreground">Request Your Personalized Quote</h2>
                        <p class="text-sm text-muted-foreground mb-6 leading-relaxed">
                            Once submitted, our artisans and logistics team will prepare a formal invoice with guaranteed shipping, transit timelines, and seamless payment methods.
                        </p>

                        <div class="flex items-start gap-3.5 bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 mb-6">
                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
                            </svg>
                            <p class="text-sm text-foreground/90">
                                <strong class="font-semibold">No upfront charge.</strong> Zero payment is required today. You'll receive a detailed quote with direct checkout options within 24 hours.
                            </p>
                        </div>

                        <button type="submit"
                            id="submit-quote-btn"
                            class="w-full bg-primary hover:bg-primary/90 text-primary-foreground text-base sm:text-lg font-semibold py-4 rounded-xl shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Send Quote Request
                        </button>
                    </div>

                </form>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="w-full md:w-1/3">
                <div class="bg-card p-6 rounded-2xl border border-border shadow-xs sticky top-24">
                    <h3 class="text-lg font-serif font-bold mb-4 border-b border-border pb-3 text-foreground">Order Summary</h3>
                    <div class="space-y-4 mb-4">
                        @foreach($items as $item)
                            <div class="border-b border-border/50 pb-3 last:border-b-0">
                                <div class="flex justify-between text-sm">
                                    <span class="text-foreground font-medium">{{ $item['product']->name }}
                                        <span class="text-muted-foreground font-normal">(x{{ $item['quantity'] }})</span>
                                    </span>
                                    <span class="font-semibold text-foreground">${{ number_format($item['subtotal'], 2) }}</span>
                                </div>

                                <div class="text-xs text-muted-foreground mt-1.5 flex items-center gap-3 flex-wrap">
                                    <span class="inline-flex items-center">
                                        <svg class="w-3 h-3 mr-1 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                        </svg>
                                        {{ $item['product']->formatted_length }} &times; {{ $item['product']->formatted_width }} &times;
                                        {{ $item['product']->formatted_height }} cm
                                    </span>
                                    <span class="inline-flex items-center">
                                        <svg class="w-3 h-3 mr-1 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                        </svg>
                                        {{ $item['product']->formatted_weight }} kg
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-muted-foreground">
                                    Unit: ${{ number_format($item['unit_price'] ?? ($item['subtotal'] / max($item['quantity'], 1)), 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-border pt-4 space-y-2.5">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span class="font-semibold text-foreground">${{ number_format($subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Est. Shipping</span>
                            <span class="font-semibold text-primary" x-text="shippingCost > 0 ? '$' + shippingCost : 'TBD'">TBD</span>
                        </div>
                        <div class="flex justify-between text-lg font-serif font-bold border-t border-border pt-3 mt-2 text-foreground">
                            <span>Estimated Total</span>
                            <span class="text-primary" x-text="'$' + (parseFloat({{ $subtotal }}) + parseFloat(shippingCost)).toFixed(2)"></span>
                        </div>
                    </div>

                    <p class="text-xs text-muted-foreground mt-4 text-center">Final shipping & transit insurance confirmed in quotation email.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function checkout() {
            return {
                country: '{{ old('country', $inquiry->country ?? '') }}',
                rates: [],
                shippingCost: 0,
                shippingError: null,
                loadingRates: false,
                selectedProvider: null,
                token: '{{ $token ?? '' }}',

                init() {
                    if (this.country) {
                        this.fetchShippingRates();
                    }
                },

                async fetchShippingRates() {
                    if (!this.country) return;

                    this.shippingError = null;
                    this.rates = [];
                    this.loadingRates = true;

                    try {
                        const response = await fetch('{{ route("checkout.calculate-shipping") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                country: this.country,
                                token: this.token
                            })
                        });

                        const data = await response.json();

                        if (data.over_weight) {
                            this.shippingError = 'Over Weight';
                        } else if (data.rates && data.rates.length > 0) {
                            this.rates = data.rates;
                        } else {
                            this.shippingError = 'No shipping rates found for this location.';
                        }
                    } catch (e) {
                        console.error(e);
                        this.shippingError = 'Error calculating shipping.';
                    } finally {
                        this.loadingRates = false;
                    }
                },

                selectShipping(rate) {
                    this.shippingCost = rate.price;
                    this.selectedProvider = rate.provider_name;
                    document.getElementById('shipping_cost_input').value = rate.price;
                    document.getElementById('shipping_provider_input').value = rate.provider_name;
                }
            }
        }

        // Prevent double-submit
        document.getElementById('quote-form').addEventListener('submit', function () {
            const btn = document.getElementById('submit-quote-btn');
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                Sending…`;
        });
    </script>
    <script src="//unpkg.com/alpinejs" defer></script>
@endsection
