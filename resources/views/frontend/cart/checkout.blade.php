@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 py-12" x-data="checkout()">
        <h1 class="text-3xl font-serif font-bold text-truffle-extra-dark mb-8">Checkout</h1>

        <div class="flex flex-col md:flex-row gap-12">
            <!-- Form Section -->
            <div class="w-full md:w-2/3">
                <form action="#" method="POST" id="checkout-form"> <!-- Action would be process order -->
                    @csrf

                    <!-- Contact Info -->
                    <div class="bg-cream p-6 rounded-lg shadow mb-6">
                        <h2 class="text-xl font-bold mb-4">Contact Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" name="name" value="{{ $inquiry->name ?? '' }}" placeholder="Full Name"
                                class="w-full p-3 bg-[#F5F2EA] border rounded-lg" required>
                            <input type="email" name="email" value="{{ $inquiry->email ?? '' }}" placeholder="Email Address"
                                class="w-full p-3 bg-[#F5F2EA] border rounded-lg" required>
                            <input type="text" name="phone" value="{{ $inquiry->phone ?? '' }}" placeholder="Phone Number"
                                class="w-full p-3 bg-[#F5F2EA] border rounded-lg" required>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="bg-cream p-6 rounded-lg shadow mb-6">
                        <h2 class="text-xl font-bold mb-4">Shipping Address</h2>
                        <div class="space-y-4">
                            <input type="text" name="address" value="{{ $inquiry->address_line ?? '' }}"
                                placeholder="Address" class="w-full p-3 bg-[#F5F2EA] border rounded-lg" required>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <input type="text" name="city" value="{{ $inquiry->city ?? '' }}" placeholder="City"
                                    class="w-full p-3 bg-[#F5F2EA] border rounded-lg" required>
                                <input type="text" name="zip_code" value="{{ $inquiry->zip_code ?? '' }}"
                                    placeholder="Zip Code" class="w-full p-3 bg-[#F5F2EA] border rounded-lg" required>
                            </div>
                            <div>
                                <label class="block text-sm text-truffle-extra-dark mb-1">Shipping Destination</label>
                                @if(isset($inquiry) && $inquiry->country)
                                    <div class="p-3 bg-[#F5F2EA] border rounded-lg font-medium text-truffle-extra-dark">
                                        {{ $availableCountriesOptions[array_search($inquiry->country, array_column($availableCountriesOptions, 'value'))]['label'] ?? $inquiry->country }}
                                    </div>
                                    <input type="hidden" name="country" x-model="country">
                                @else
                                    <select name="country" x-model="country" @change="fetchShippingRates"
                                        class="w-full p-3 bg-[#F5F2EA] border rounded-lg" required>
                                        <option value="">Select Country</option>
                                        @foreach($availableCountriesOptions as $opt)
                                            <option value="{{ $opt['value'] }}" {{ ($inquiry->country ?? '') == $opt['value'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                <p class="text-xs text-red-500 mt-1" x-show="shippingError" x-text="shippingError"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Method -->
                    <div class="bg-cream p-6 rounded-lg shadow mb-6" x-show="rates.length > 0">
                        <h2 class="text-xl font-bold mb-4">Shipping Method</h2>
                        <div class="space-y-3">
                            <template x-for="rate in rates" :key="rate.provider_name + rate.price">
                                <label
                                    class="flex items-center justify-between p-4 border rounded cursor-pointer hover:bg-[#F5F2EA]">
                                    <div class="flex items-center">
                                        <input type="radio" name="shipping_rate" :value="rate.price"
                                            @change="selectShipping(rate)"
                                            class="h-4 w-4 text-primary focus:ring-primary">
                                        <div class="ml-3">
                                            <span class="block text-sm font-medium text-truffle-extra-dark"
                                                x-text="rate.provider_name"></span>
                                            <span class="block text-xs text-truffle-extra-dark"
                                                x-text="'Zone: ' + rate.details.zone"></span>
                                        </div>
                                    </div>
                                    <span class="text-sm font-bold text-truffle-extra-dark" x-text="'$' + rate.price"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Payment (PayPal integration) -->
                    <div class="bg-cream p-6 rounded-lg shadow mb-6">
                        <h2 class="text-xl font-bold mb-4">Payment</h2>
                        <p class="text-truffle-extra-dark text-sm mb-4">Please complete the form and select shipping before paying.</p>
                        
                        <!-- The PayPal buttons will render securely inside this container -->
                        <div id="paypal-button-container" class="mt-4" style="min-height:50px"></div>
                        <div id="paypal-feedback" class="text-sm text-primary hidden my-2 font-bold">Processing payment... Please wait.</div>
                    </div>

                    <button type="submit" id="native-submit"
                        class="w-full bg-green-premium text-white text-lg font-bold py-4 rounded-lg hover:bg-green-800 transition hidden">Place
                        Order</button>
                </form>
            </div>

            <!-- Summary Section -->
            <div class="w-full md:w-1/3">
                <div class="bg-cream p-6 rounded-lg shadow sticky top-24">
                    <h3 class="text-lg font-bold mb-4 border-b pb-2">Order Summary</h3>
                    <div class="space-y-4 mb-4">
                        @foreach($items as $item)
                            <div class="border-b border-gray-100 pb-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-truffle-extra-dark font-medium">{{ $item['product']->name }}
                                        (x{{ $item['quantity'] }})</span>
                                    <span class="font-medium">${{ number_format($item['subtotal'], 2) }}</span>
                                </div>

                                <div class="text-xs text-truffle-extra-dark/70 mt-1 flex items-center gap-3">
                                    <span class="inline-flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                        </svg>
                                        {{ $item['product']->formatted_length }} &times; {{ $item['product']->formatted_width }} &times;
                                        {{ $item['product']->formatted_height }} cm
                                    </span>
                                    <span class="inline-flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                        </svg>
                                        {{ $item['product']->formatted_weight }} kg
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-truffle-extra-dark/70">
                                    Unit price: ${{ number_format($item['unit_price'] ?? ($item['subtotal'] / max($item['quantity'], 1)), 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t pt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-truffle-extra-dark">Subtotal</span>
                            <span class="font-medium">${{ number_format($subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-truffle-extra-dark">Shipping</span>
                            <span class="font-medium" x-text="shippingCost > 0 ? '$' + shippingCost : '--'">--</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t pt-2 mt-2">
                            <span>Total</span>
                            <span x-text="'$' + (parseFloat({{ $subtotal }}) + parseFloat(shippingCost)).toFixed(2)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function checkout() {
            return {
                country: '{{ $inquiry->country ?? '' }}',
                rates: [],
                shippingCost: 0,
                shippingError: null,
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

                        if (data.rates && data.rates.length > 0) {
                            this.rates = data.rates;
                        } else {
                            this.shippingError = 'No shipping rates found for this location.';
                        }
                    } catch (e) {
                        console.error(e);
                        this.shippingError = 'Error calculating shipping.';
                    }
                },

                selectShipping(rate) {
                    this.shippingCost = rate.price;
                }
            }
        }
    </script>
    <script src="//unpkg.com/alpinejs" defer></script>

    @php
        // Always use config() - env() returns null when config is cached in production
        $clientId = config('services.paypal.client_id');
    @endphp
    <!-- PayPal JavaScript SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id={{ trim($clientId) }}&currency=USD&intent=capture&components=buttons"></script>
    <script>
        // Holds the local DB order ID returned by /checkout/init-order
        let localOrderId = null;
        let orderInitialised = false;

        /**
         * Step 1 - Validate form, POST customer + cart data to Laravel.
         * Laravel creates a pending Order and returns { order_id, grand_total }.
         * Returns the local order ID on success, throws on failure.
         */
        function showPayPalError(msg) {
            const el = document.getElementById('paypal-feedback');
            el.innerText = msg;
            el.classList.remove('hidden', 'text-primary', 'text-primary');
            el.classList.add('text-red-600');
        }

        async function initOrder() {
            const form = document.getElementById('checkout-form');
            const data = new FormData(form);

            // Append checkout token if present
            data.append('token', '{{ $token ?? '' }}');

            // Append selected shipping cost from the checked radio
            const shippingInput = document.querySelector('input[name="shipping_rate"]:checked');
            data.append('shipping_cost', shippingInput ? shippingInput.value : '0');

            const response = await fetch('{{ route("checkout.init-order") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: data,
            });

            const result = await response.json();

            if (!response.ok) {
                // Show each validation field error if available
                const fieldErrors = result.errors
                    ? Object.values(result.errors).flat().join(' ')
                    : null;
                throw new Error(fieldErrors || result.message || result.error || 'Could not create order.');
            }

            localOrderId = result.order_id;
            return result.order_id;
        }

        window.addEventListener('DOMContentLoaded', function () {

            if (typeof paypal === 'undefined') {
                showPayPalError('PayPal failed to load. Please refresh the page.');
                return;
            }

            try {
                paypal.Buttons({
                    // Force the standard PayPal button - always shown, including in sandbox
                    fundingSource: paypal.FUNDING.PAYPAL,

                    style: {
                        layout: 'vertical',
                        color:  'gold',
                        shape:  'rect',
                        label:  'paypal',
                        height: 45,
                    },

                    /**
                     * Step 1 - Validate form + shipping.
                     * Step 2 - Create pending DB order, then create PayPal order from server amount.
                     */
                    createOrder: async function (data, actions) {
                        const feedbackEl = document.getElementById('paypal-feedback');

                        // 1. Native HTML5 form validation (required fields, email format, etc.)
                        const form = document.getElementById('checkout-form');
                        if (!form.checkValidity()) {
                            form.reportValidity();
                            throw new Error('Please fill in all required fields before paying.');
                        }

                        // 2. Shipping must be selected
                        const shippingInput = document.querySelector('input[name="shipping_rate"]:checked');
                        if (!shippingInput) {
                            showPayPalError('Please select a shipping method before paying.');
                            throw new Error('Please select a shipping method before paying.');
                        }

                        feedbackEl.innerText = 'Preparing your order...';
                        feedbackEl.classList.remove('hidden', 'text-red-600');
                        feedbackEl.classList.add('text-primary');

                        // 3. Create pending order in DB
                        const orderId = await initOrder();

                        // 4. Create PayPal order using server-side amount
                        const response = await fetch('/api/paypal/orders', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ order_id: orderId }),
                        });

                        const orderData = await response.json();

                        if (!response.ok || orderData.error || !orderData.id) {
                            throw new Error(orderData.error || 'PayPal order creation failed.');
                        }

                        feedbackEl.classList.add('hidden');
                        return orderData.id;
                    },

                    /**
                     * Step 3 - Capture PayPal payment, then mark local order as paid.
                     */
                    onApprove: async function (data, actions) {
                        const feedbackEl = document.getElementById('paypal-feedback');
                        document.getElementById('paypal-button-container').classList.add('hidden');
                        feedbackEl.innerText = 'Processing payment... Please wait.';
                        feedbackEl.classList.remove('hidden', 'text-red-600');
                        feedbackEl.classList.add('text-primary');

                        try {
                            const response = await fetch('/api/paypal/orders/' + data.orderID + '/capture', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ order_id: localOrderId }),
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.error || 'Payment capture failed.');
                            }

                            feedbackEl.innerText = 'Payment successful! Redirecting...';
                            feedbackEl.classList.add('text-primary');

                            // Redirect to the order confirmation page
                            window.location.href = result.redirect_url;

                        } catch (err) {
                            showPayPalError('Payment failed: ' + err.message);
                            document.getElementById('paypal-button-container').classList.remove('hidden');
                            console.error(err);
                        }
                    },

                    onCancel: async function () {
                        // User closed the PayPal popup - cancel the pending DB order to avoid orphans
                        const cancelledId = localOrderId;
                        localOrderId = null;

                        if (cancelledId) {
                            try {
                                await fetch('/api/orders/' + cancelledId + '/cancel-pending', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                    },
                                });
                            } catch (e) {
                                console.warn('Could not cancel pending order:', e);
                            }
                        }

                        const feedbackEl = document.getElementById('paypal-feedback');
                        feedbackEl.classList.add('hidden');
                        document.getElementById('paypal-button-container').classList.remove('hidden');
                    },

                    onError: function (err) {
                        console.error('PayPal SDK error:', err);
                        showPayPalError('A PayPal error occurred. Please try again.');
                    },

                }).render('#paypal-button-container').catch(function (err) {
                    console.error('PayPal render error:', err);
                    showPayPalError('Could not load the PayPal button. Error: ' + (err.message || err));
                });

            } catch (err) {
                console.error('PayPal init error:', err);
                showPayPalError('PayPal initialization failed: ' + err.message);
            }

        }); // end DOMContentLoaded
    </script>
@endsection
