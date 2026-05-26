@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 py-12">
        <h1 class="text-3xl font-serif font-bold text-truffle-extra-dark mb-8">Shopping Cart</h1>

        @if(count($cartItems) > 0)
            <div class="bg-cream rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-[#F5F2EA]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Product
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Price
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Quantity
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Total
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-cream divide-y divide-gray-200">
                            @foreach($cartItems as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                @if($item['product']->main_image)
                                                    <img class="h-10 w-10 rounded-full object-cover"
                                                        src="{{ $item['product']->main_image }}" alt="">
                                                @else
                                                    <div class="h-10 w-10 rounded-full bg-[#E8E2D2]"></div>
                                                @endif
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-truffle-extra-dark">{{ $item['product']->name }}</div>

                                                <div class="text-xs text-truffle-extra-dark mt-1">
                                                    <span class="inline-flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                                        </svg>
                                                        {{ $item['product']->formatted_length }} &times; {{ $item['product']->formatted_width }} &times; {{ $item['product']->formatted_height }} cm
                                                    </span>
                                                    <span class="mx-2">|</span>
                                                    <span class="inline-flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                                        </svg>
                                                        {{ $item['product']->formatted_weight }} kg
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-truffle-extra-dark">
                                        <div class="font-semibold text-primary">${{ number_format($item['unit_price'], 2) }}</div>
                                        @if($item['product']->discount_price)
                                            <div class="text-[11px] text-truffle-extra-dark/60">
                                                Normal price: <strike>${{ number_format($item['product']->price, 2) }}</strike>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-truffle-extra-dark">
                                        <form action="{{ route('cart.update') }}" method="POST" class="flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="line_key" value="{{ $item['line_key'] }}">
                                            <input type="number" name="quantity" value="{{ $item['quantity'] }}" 
                                                min="{{ $item['product']->min_quantity }}" 
                                                class="w-20 border rounded p-1 text-center"
                                                onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        ${{ number_format($item['subtotal'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <form action="{{ route('cart.remove') }}" method="POST" class="inline-block">
                                            @csrf
                                            <input type="hidden" name="line_key" value="{{ $item['line_key'] }}">
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            <div class="flex justify-end mt-8">
                <div class="w-full md:w-1/3 bg-cream p-6 rounded-lg shadow">
                    <div class="flex justify-between mb-4">
                        <span class="text-truffle-extra-dark">Subtotal</span>
                        <span class="font-bold text-truffle-extra-dark">${{ number_format($subtotal, 2) }}</span>
                    </div>
                    <p class="text-xs text-truffle-extra-dark mb-6">Shipping & taxes calculated at checkout.</p>
                    <a href="{{ route('checkout') }}"
                        class="block w-full bg-primary text-white text-center py-3 rounded-lg font-bold hover:opacity-90 transition">Proceed
                        to Checkout</a>
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-truffle-extra-dark text-lg mb-6">Your cart is empty.</p>
                <a href="{{ route('home') }}" class="text-primary hover:underline">Continue Shopping</a>
            </div>
        @endif
    </div>
@endsection
