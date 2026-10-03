@extends('admin.layout')

@section('header')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.orders.index') }}" class="text-truffle-extra-dark/70 hover:text-green-premium transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h2 class="text-xl font-bold flex items-center gap-2">
                Order <span class="font-mono text-green-premium">{{ $order->order_number }}</span>
                @if($order->is_paid)
                    <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded font-medium">PAID</span>
                @endif
            </h2>
            <p class="text-sm text-truffle-extra-dark">
                Created {{ $order->created_at->format('M d, Y H:i') }} by {{ $order->creator?->name ?? 'System' }}
            </p>
            @if($order->type === 'inquiry' && $order->convertedOrder)
                <p class="text-xs text-indigo-600 mt-0.5">
                    &rarr; Converted to order
                    <a href="{{ route('admin.orders.show', $order->convertedOrder) }}" class="font-mono font-semibold hover:underline">{{ $order->convertedOrder->order_number }}</a>
                </p>
            @elseif($order->type === 'order' && $order->sourceInquiry)
                <p class="text-xs text-amber-600 mt-0.5">
                    &larr; Converted from inquiry
                    <a href="{{ route('admin.orders.show', $order->sourceInquiry) }}" class="font-mono font-semibold hover:underline">{{ $order->sourceInquiry->order_number }}</a>
                </p>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Left Column: Main Details --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- Status and Quick Actions --}}
            <div
                class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <p class="text-sm text-truffle-extra-dark mb-1">Current Status</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="px-3 py-1 rounded-full text-sm font-semibold 
                                                {{ $statusColors[$order->status] === 'gray' ? 'bg-[#F5F2EA] text-truffle-extra-dark' : '' }}
                                                {{ $statusColors[$order->status] === 'blue' ? 'bg-blue-100 text-blue-700' : '' }}
                                                {{ $statusColors[$order->status] === 'yellow' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                                {{ $statusColors[$order->status] === 'purple' ? 'bg-purple-100 text-purple-700' : '' }}
                                                {{ $statusColors[$order->status] === 'green' ? 'bg-green-premium/20 text-green-premium' : '' }}
                                                {{ $statusColors[$order->status] === 'orange' ? 'bg-orange-100 text-orange-700' : '' }}
                                                {{ $statusColors[$order->status] === 'red' ? 'bg-red-100 text-red-700' : '' }}
                                                {{ $statusColors[$order->status] === 'indigo' ? 'bg-indigo-100 text-indigo-700' : '' }}">
                            {{ $order->status_label }}
                        </span>

                        @if($order->isReturnEligible() || $order->hasReturnedItems())
                            <span class="px-3 py-1 rounded-full text-sm font-semibold
                                {{ $order->return_summary_color === 'gray' ? 'bg-[#F5F2EA] text-truffle-extra-dark' : '' }}
                                {{ $order->return_summary_color === 'amber' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $order->return_summary_color === 'red' ? 'bg-red-100 text-red-700' : '' }}">
                                Return: {{ $order->return_summary_label }}
                            </span>

                            @if(auth()->user()->hasPermission('manage_orders'))
                                @if($order->isReturnEligible())
                                    <form action="{{ route('admin.orders.mark-all-returned', $order) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100">
                                            Mark all returned
                                        </button>
                                    </form>
                                @endif

                                @if($order->hasReturnedItems())
                                    <form action="{{ route('admin.orders.clear-all-returns', $order) }}" method="POST" class="inline" onsubmit="return confirm('Clear all return records for this order?')">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200">
                                            Clear returns
                                        </button>
                                    </form>
                                @endif
                            @endif
                        @endif

                        @if($order->is_merged)
                            <span
                                class="px-2 py-1 bg-amber-100 text-amber-800 text-xs rounded font-medium flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                Merged into #{{ clone $order->mergedIntoOrder()->first()?->order_number ?? '?' }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex gap-2 flex-wrap">
                    @if(!$order->isFinanciallyLocked() && !$order->is_merged && !in_array($order->status, ['cancelled', 'delivered']))
                        <a href="{{ route('admin.orders.edit', $order) }}"
                            class="px-4 py-2 border border-truffle-medium/30 text-truffle-extra-dark rounded-lg hover:bg-[#F5F2EA] transition-colors text-sm font-medium">Edit
                            Details</a>
                    @endif

                    @if($order->type === 'inquiry' && $order->checkout_token && !$order->is_paid)
                        <button onclick="copyCheckoutLink('{{ url('/checkout/' . $order->checkout_token) }}', this)"
                            class="px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-lg text-sm font-medium hover:bg-indigo-100 transition-colors flex items-center gap-2 relative">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            Copy Link
                            <span
                                class="copy-feedback absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 transition-opacity whitespace-nowrap pointer-events-none fade-out shadow-md blur-none z-50">Copied!</span>
                        </button>
                    @endif

                    @if($order->type === 'inquiry' && !$order->is_paid && !in_array($order->status, ['cancelled']))
                        <form action="{{ route('admin.orders.generate-checkout-link', $order) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 bg-violet-50 border border-violet-200 text-violet-700 rounded-lg text-sm font-medium hover:bg-violet-100 transition-colors flex items-center gap-2"
                                onclick="return {{ $order->checkout_token ? "confirm('This will invalidate the previous link. Continue?')" : 'true' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                {{ $order->checkout_token ? 'Regenerate Link' : 'Generate Payment Link' }}
                            </button>
                        </form>
                    @endif

                    @if(!$order->is_paid && auth()->user()->hasAnyRole(['super_admin', 'admin']))
                        <form action="{{ route('admin.orders.mark-paid', $order) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium flex items-center gap-2"
                                onclick="return confirm('Mark as PAID? This will lock order totals. Proceed?')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Mark Paid
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Line Items --}}
            <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-[#F5F2EA]">
                    <h3 class="font-bold text-truffle-extra-dark">Order Items</h3>
                    <span class="text-sm text-truffle-extra-dark">{{ $order->items->sum('quantity') }} items</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#F5F2EA] text-truffle-extra-dark border-b">
                                <th class="px-6 py-3 font-medium">Product</th>
                                <th class="px-6 py-3 font-medium text-right">Price</th>
                                <th class="px-6 py-3 font-medium text-center">Qty</th>
                                <th class="px-6 py-3 font-medium text-right">Discount</th>
                                <th class="px-6 py-3 font-medium text-right">Total</th>
                                @if($order->isReturnEligible())
                                    <th class="px-6 py-3 font-medium text-center">Return</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-truffle-extra-dark">
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-truffle-extra-dark">{{ $item->product_name }}</div>

                                        <div class="text-xs text-truffle-extra-dark">SKU: {{ $item->product_sku }} | Wt:
                                            {{ $item->weight_kg }}kg
                                            @if(isset($item->product_snapshot['length']) && $item->product_snapshot['length'])
                                                | Dim:
                                                {{ $item->product_snapshot['length'] }}x{{ $item->product_snapshot['width'] }}x{{ $item->product_snapshot['height'] }}cm
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @php $netPrice = $item->quantity > 0 ? $item->line_total / $item->quantity : $item->unit_price; @endphp
                                        ${{ number_format($netPrice, 2) }}
                                        @if($item->option_price > 0)
                                            <div class="text-[10px] text-truffle-extra-dark/70">
                                                base: ${{ number_format($item->base_unit_price, 2) }} + option:
                                                ${{ number_format($item->option_price, 2) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center font-medium">{{ $item->quantity }}</td>
                                    <td class="px-6 py-4 text-right">
                                        @if($item->item_discount_amount > 0)
                                            <div class="text-red-500">Ref: -${{ number_format($item->item_discount_amount, 2) }}</div>
                                            <div class="text-[10px] text-truffle-extra-dark/70">
                                                orig: ${{ number_format($item->unit_price, 2) }}
                                            </div>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-truffle-extra-dark">
                                        ${{ number_format($item->line_total, 2) }}</td>
                                    @if($order->isReturnEligible())
                                        <td class="px-6 py-4 text-center" x-data="{ open: false }">
                                            @php
                                                $returnColorMap = ['gray' => 'bg-[#F5F2EA] text-truffle-extra-dark', 'amber' => 'bg-amber-100 text-amber-700', 'red' => 'bg-red-100 text-red-700'];
                                                $returnBadge = $returnColorMap[$item->return_status_color] ?? 'bg-[#F5F2EA] text-truffle-extra-dark';
                                            @endphp
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $returnBadge }}">
                                                {{ $item->return_status_label }}
                                            </span>
                                            @if($item->returned_quantity > 0)
                                                <div class="text-[10px] text-truffle-extra-dark/70 mt-0.5">{{ $item->returned_quantity }}/{{ $item->quantity }} returned</div>
                                            @endif

                                            @if(auth()->user()->hasPermission('manage_orders'))
                                                <div class="mt-1.5 flex items-center justify-center gap-1">
                                                    @if($item->returnable_quantity > 0)
                                                        <button type="button" @click="open = !open"
                                                            class="text-xs px-2 py-1 bg-amber-50 text-amber-700 rounded hover:bg-amber-100 font-medium">
                                                            Mark Return
                                                        </button>
                                                    @endif
                                                    @if($item->return_status !== 'none')
                                                        <form action="{{ route('admin.orders.items.undo-return', [$order, $item]) }}" method="POST"
                                                            onsubmit="return confirm('Reverse this return record?')">
                                                            @csrf
                                                            <button type="submit"
                                                                class="text-xs px-2 py-1 bg-red-50 text-red-600 rounded hover:bg-red-100 font-medium">
                                                                Undo
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>

                                                <form x-show="open" x-cloak action="{{ route('admin.orders.items.return', [$order, $item]) }}"
                                                    method="POST" class="mt-2 p-2 bg-amber-50 border border-amber-100 rounded-lg text-left space-y-1.5">
                                                    @csrf
                                                    <div>
                                                        <label class="block text-[10px] font-semibold text-amber-700">Qty (max {{ $item->returnable_quantity }})</label>
                                                        <input type="number" name="quantity" min="1" max="{{ $item->returnable_quantity }}"
                                                            value="{{ $item->returnable_quantity }}"
                                                            class="w-full text-xs border-amber-300 rounded">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-semibold text-amber-700">Reason</label>
                                                        <input type="text" name="reason" placeholder="e.g. damaged in transit"
                                                            class="w-full text-xs border-amber-300 rounded">
                                                    </div>
                                                    <button type="submit"
                                                        class="w-full text-xs px-2 py-1 bg-amber-600 text-white rounded hover:bg-amber-700 font-medium">
                                                        Confirm Return
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Financial Totals Summary --}}
                <div class="px-6 py-4 border-t border-gray-100 bg-[#F5F2EA] flex justify-end">
                    <div class="w-full max-w-sm space-y-2 text-sm">
                        <div class="flex justify-between text-truffle-extra-dark font-medium">
                            <span>Subtotal (Net)</span>
                            <span>${{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        @if($order->order_discount_amount > 0)
                            <div class="flex justify-between text-red-500">
                                <span>Order Discount
                                    ({{ $order->order_discount_type === 'percent' ? $order->order_discount_value . '%' : 'Fixed' }})</span>
                                <span>-${{ number_format($order->order_discount_amount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-truffle-extra-dark pb-2 border-b border-truffle-medium/30">
                            <span>Shipping Cost</span>
                            <span>+${{ number_format($order->shipping_cost, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold text-truffle-extra-dark">
                            <span>Grand Total</span>
                            <span>${{ number_format($order->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Client Details --}}
            <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 p-6">
                <h3 class="font-bold text-truffle-extra-dark mb-4 border-b pb-2">Client Information
                    @if($order->client_snapshot) <span class="text-xs font-normal text-truffle-extra-dark/70 ml-2">(Snapshot at order
                    creation)</span> @endif
                </h3>
                @php $c = $order->client_snapshot ?? ($order->client ? app(\App\Services\OrderService::class)->buildClientSnapshot($order->client) : null); @endphp

                @if($c)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6">
                        <div>
                            <p class="text-xs text-truffle-extra-dark mb-1">Name</p>
                            <p class="font-medium text-truffle-extra-dark">{{ $c['name'] }} <span
                                    class="text-truffle-extra-dark/70 text-sm ml-1">{{ $c['buyer_id'] ?? '' }}</span></p>
                        </div>
                        <div>
                            <p class="text-xs text-truffle-extra-dark mb-1">Company</p>
                            <p class="text-truffle-extra-dark">{{ $c['company'] ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-truffle-extra-dark mb-1">Contact Details</p>
                            <p class="text-truffle-extra-dark">{{ $c['email'] ?? 'No email' }}</p>
                            <p class="text-truffle-extra-dark">{{ $c['phone'] ?? 'No phone' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-truffle-extra-dark mb-1">Shipping Address</p>
                            <address class="text-truffle-extra-dark not-italic">
                                {{ $c['address'] }}<br>
                                {{ $c['city'] }} {{ $c['state'] ? ', ' . $c['state'] : '' }} {{ $c['zip_code'] }}<br>
                                {{ country_name($c['country'] ?? null) }}
                            </address>
                        </div>
                        <div>
                            <p class="text-xs text-truffle-extra-dark mb-1">Shipping Country</p>
                            <p class="text-truffle-extra-dark font-medium">{{ country_name($c['country'] ?? null) ?? 'Not specified' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-truffle-extra-dark mb-1">Shipping Provider</p>
                            @if($order->shippingProvider)
                                <p class="text-truffle-extra-dark font-medium">{{ $order->shippingProvider->name }}</p>
                            @else
                                <p class="text-truffle-extra-dark/70 italic">Not yet assigned</p>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-truffle-extra-dark italic">No client associated with this order.</p>
                @endif
                {{-- Notes / Inquiry Message --}}
                @if($order->notes)
                    <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30">
                        <div class="px-6 py-4 border-b border-gray-100 bg-[#F5F2EA] flex items-center gap-2">
                            <svg class="w-5 h-5 text-truffle-extra-dark/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            <h3 class="font-bold text-truffle-extra-dark">Notes / Inquiry Message</h3>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-truffle-extra-dark whitespace-pre-wrap text-sm leading-relaxed">{{ $order->notes }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right Column (Sidebar) --}}
            <div class="space-y-6">

                @if(auth()->user()->hasPermission('view_finance') || auth()->user()->hasPermission('record_payments') || auth()->user()->hasPermission('confirm_payments'))
                    @php
                        $financePayments = $order->payments()->with(['recordedBy', 'confirmedBy'])->latest()->get();
                        $canRecordPayments = auth()->user()->hasPermission('record_payments');
                        $canConfirmPayments = auth()->user()->hasPermission('confirm_payments');
                    @endphp
                    <section class="overflow-hidden rounded-xl border border-truffle-medium/30 bg-cream shadow-sm">
                        <div class="flex items-start justify-between gap-3 border-b border-truffle-medium/20 bg-[#F5F2EA] px-5 py-4">
                            <div>
                                <h3 class="font-bold text-truffle-extra-dark">Payments</h3>
                                <p class="mt-1 text-xs text-truffle-extra-dark/70">Order total: {{ $order->currency ?? 'USD' }} {{ number_format((float) $order->grand_total, 2) }}</p>
                            </div>
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-truffle-extra-dark">{{ $financePayments->count() }} records</span>
                        </div>

                        @if($financePayments->isEmpty())
                            <p class="px-5 py-4 text-sm italic text-truffle-extra-dark/70">No payment records yet.</p>
                        @else
                            <ul class="divide-y divide-truffle-medium/20">
                                @foreach($financePayments as $payment)
                                    <li class="space-y-3 px-5 py-4">
                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                            <div>
                                                <p class="font-semibold text-truffle-extra-dark">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</p>
                                                <p class="mt-1 text-xs text-truffle-extra-dark/70">{{ ucfirst(str_replace('_', ' ', $payment->method)) }} · {{ $payment->received_on?->format('M d, Y') ?? 'Date unavailable' }}</p>
                                                @if($payment->reference)
                                                    <p class="mt-1 break-all text-xs text-truffle-extra-dark/70">Ref: {{ $payment->reference }}</p>
                                                @endif
                                            </div>
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $payment->status === 'confirmed' ? 'bg-emerald-100 text-emerald-800' : ($payment->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700') }}">{{ $payment->status }}</span>
                                        </div>
                                        <div class="text-xs text-truffle-extra-dark/70">
                                            Reporting amount: {{ number_format((float) $payment->reporting_amount, 2) }}
                                            <span class="mx-1">·</span>
                                            Recorded by {{ $payment->recordedBy?->name ?? 'System' }}
                                        </div>
                                        @if($payment->notes)
                                            <p class="whitespace-pre-wrap text-xs text-truffle-extra-dark/70">{{ $payment->notes }}</p>
                                        @endif
                                        @if($canConfirmPayments && $payment->status === 'pending')
                                            <form action="{{ route('admin.finance.payments.confirm', $payment) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="rounded-md bg-emerald-700 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-emerald-800">Confirm receipt</button>
                                            </form>
                                        @elseif($canConfirmPayments && $payment->status === 'confirmed')
                                            <form action="{{ route('admin.finance.payments.reverse', $payment) }}" method="POST" class="space-y-2" onsubmit="return confirm('Reverse this confirmed payment? A correction entry will be added to the ledger.')">
                                                @csrf
                                                <label class="block text-xs font-semibold text-truffle-extra-dark" for="payment-reversal-reason-{{ $payment->id }}">Reversal reason</label>
                                                <textarea id="payment-reversal-reason-{{ $payment->id }}" name="reason" rows="2" minlength="5" required class="w-full rounded-md border border-truffle-medium/40 bg-white text-sm focus:border-primary focus:ring-primary" placeholder="Explain why this payment is being reversed"></textarea>
                                                <button type="submit" class="rounded-md border border-red-300 px-3 py-2 text-xs font-semibold text-red-800 transition-colors hover:bg-red-50">Reverse payment</button>
                                            </form>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if($canRecordPayments)
                            <details class="border-t border-truffle-medium/20">
                                <summary class="cursor-pointer list-none px-5 py-4 text-sm font-semibold text-green-premium hover:bg-[#F5F2EA]">Record a payment</summary>
                                <form action="{{ route('admin.finance.payments.store', $order) }}" method="POST" class="space-y-3 px-5 pb-5" x-data="{ amount: @js(old('amount', '')), rate: @js(old('reporting_exchange_rate', '1')) }">
                                    @csrf
                                    <div>
                                        <label for="payment-invoice-id" class="mb-1 block text-xs font-semibold text-truffle-extra-dark">Invoice</label>
                                        <select id="payment-invoice-id" name="invoice_id" class="w-full rounded-md border border-truffle-medium/40 bg-white text-sm focus:border-primary focus:ring-primary">
                                            <option value="">No invoice selected</option>
                                            @foreach($order->invoices->where('status', '!=', 'voided') as $invoice)
                                                <option value="{{ $invoice->id }}" @selected(old('invoice_id') == $invoice->id)>{{ $invoice->invoice_number }} · {{ ucfirst($invoice->status) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label for="payment-amount" class="mb-1 block text-xs font-semibold text-truffle-extra-dark">Amount *</label>
                                            <input id="payment-amount" name="amount" type="number" min="0.01" step="0.01" required x-model="amount" value="{{ old('amount') }}" class="w-full rounded-md border border-truffle-medium/40 bg-white text-sm focus:border-primary focus:ring-primary">
                                            @error('amount')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="payment-currency" class="mb-1 block text-xs font-semibold text-truffle-extra-dark">Currency</label>
                                            <input id="payment-currency" name="currency" type="text" maxlength="3" value="{{ old('currency', $order->currency ?? 'USD') }}" class="w-full rounded-md border border-truffle-medium/40 bg-white text-sm uppercase focus:border-primary focus:ring-primary">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label for="payment-exchange-rate" class="mb-1 block text-xs font-semibold text-truffle-extra-dark">Reporting exchange rate *</label>
                                            <input id="payment-exchange-rate" name="reporting_exchange_rate" type="number" min="0.00000001" step="0.00000001" required x-model="rate" value="{{ old('reporting_exchange_rate', '1') }}" class="w-full rounded-md border border-truffle-medium/40 bg-white text-sm focus:border-primary focus:ring-primary">
                                            @error('reporting_exchange_rate')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="payment-reporting-amount" class="mb-1 block text-xs font-semibold text-truffle-extra-dark">Reporting amount *</label>
                                            <input id="payment-reporting-amount" name="reporting_amount" type="number" min="0.01" step="0.01" required readonly x-bind:value="amount !== '' && rate !== '' ? (Number(amount) * Number(rate)).toFixed(2) : ''" class="w-full rounded-md border border-truffle-medium/40 bg-gray-50 text-sm focus:border-primary focus:ring-primary">
                                            <p class="mt-1 text-[11px] text-truffle-extra-dark/70">Calculated from amount × exchange rate.</p>
                                            @error('reporting_amount')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label for="payment-method" class="mb-1 block text-xs font-semibold text-truffle-extra-dark">Method *</label>
                                            <input id="payment-method" name="method" type="text" maxlength="100" required value="{{ old('method') }}" placeholder="Bank transfer, cash..." class="w-full rounded-md border border-truffle-medium/40 bg-white text-sm focus:border-primary focus:ring-primary">
                                            @error('method')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label for="payment-received-on" class="mb-1 block text-xs font-semibold text-truffle-extra-dark">Received on *</label>
                                            <input id="payment-received-on" name="received_on" type="date" required value="{{ old('received_on', now()->toDateString()) }}" class="w-full rounded-md border border-truffle-medium/40 bg-white text-sm focus:border-primary focus:ring-primary">
                                            @error('received_on')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                    <div>
                                        <label for="payment-reference" class="mb-1 block text-xs font-semibold text-truffle-extra-dark">Reference</label>
                                        <input id="payment-reference" name="reference" type="text" maxlength="255" value="{{ old('reference') }}" class="w-full rounded-md border border-truffle-medium/40 bg-white text-sm focus:border-primary focus:ring-primary">
                                    </div>
                                    <div>
                                        <label for="payment-notes" class="mb-1 block text-xs font-semibold text-truffle-extra-dark">Notes</label>
                                        <textarea id="payment-notes" name="notes" rows="2" class="w-full rounded-md border border-truffle-medium/40 bg-white text-sm focus:border-primary focus:ring-primary">{{ old('notes') }}</textarea>
                                    </div>
                                    @error('invoice_id')<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                                    <button type="submit" class="w-full rounded-md bg-green-premium px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:opacity-90">Save payment for confirmation</button>
                                </form>
                            </details>
                        @endif
                    </section>
                @endif

                {{-- Invoices Panel --}}
                <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 p-6">
                    <h3 class="font-bold text-truffle-extra-dark mb-4 border-b pb-2 flex justify-between items-center">
                        Invoices
                        @if(!$order->hasActiveInvoice() && auth()->user()->hasPermission('manage_invoices'))
                            <form action="{{ route('admin.invoices.store', $order) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded hover:bg-indigo-100 font-medium">+
                                    Generate</button>
                            </form>
                        @endif
                    </h3>

                    @if($order->invoices->isEmpty())
                        <p class="text-sm text-truffle-extra-dark italic mb-3">No invoices generated yet.</p>
                        @if(auth()->user()->hasPermission('manage_invoices'))
                            <form action="{{ route('admin.invoices.store', $order) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Generate Invoice
                                </button>
                            </form>
                        @endif
                    @else
                        <ul class="space-y-3">
                            @foreach($order->invoices as $inv)
                                <li class="flex items-center justify-between p-3 border rounded-lg hover:bg-[#F5F2EA]">
                                    <div>
                                        <a href="{{ route('admin.invoices.show', $inv) }}"
                                            class="font-mono text-sm text-indigo-600 hover:underline font-semibold">{{ $inv->invoice_number }}</a>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span
                                                class="text-xs font-medium {{ $inv->status === 'issued' ? 'text-green-premium' : ($inv->status === 'voided' ? 'text-red-500' : 'text-yellow-600') }}">
                                                {{ strtoupper($inv->status) }}
                                            </span>
                                            <span class="text-xs text-truffle-extra-dark/70">{{ $inv->created_at->format('M d') }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.invoices.download', $inv) }}"
                                        class="text-truffle-extra-dark/70 hover:text-indigo-600" title="Download PDF">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                        </svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Workflow / Status Transition Panel --}}
                <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 p-6">
                    <h3 class="font-bold text-truffle-extra-dark mb-2 border-b pb-2">Update Status</h3>

                    <form action="{{ route('admin.orders.update-status', $order) }}" method="POST"
                        x-data="{ nextStatus: '{{ empty($allowedTransitions) ? '' : $allowedTransitions[0] }}' }">
                        @csrf
                        <div class="space-y-3 pt-2">
                            <select name="status" x-model="nextStatus"
                                class="w-full text-sm border-truffle-medium/30 rounded-lg focus:border-green-500 focus:ring-green-500">
                                <option value="">-- Manual Override --</option>
                                @foreach($allowedTransitions as $statusKey)
                                    <option value="{{ $statusKey }}">{{ \App\Models\Order::STATUS_LABELS[$statusKey] }}</option>
                                @endforeach
                            </select>

                            <div x-show="nextStatus === ''"
                                class="mt-2 p-3 bg-red-50 border border-red-100 rounded-lg text-sm text-red-700">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="override" value="1"
                                        class="text-red-500 border-red-300 focus:ring-red-500">
                                    <strong>Enable Override</strong> (Admin only)
                                </label>
                                <p class="text-xs mt-1 text-red-600 ml-6">Warning: Bypassing workflow rules may disrupt
                                    logic.
                                </p>
                                <select name="status"
                                    class="w-full mt-2 text-sm border-red-300 rounded-lg text-red-900 focus:border-red-500 focus:ring-red-500"
                                    x-bind:disabled="nextStatus !== ''">
                                    @foreach(\App\Models\Order::STATUSES as $sk)
                                        @if($sk !== \App\Models\Order::STATUS_CONVERTED)
                                            <option value="{{ $sk }}" {{ $sk === $order->status ? 'disabled' : '' }}>
                                                {{ \App\Models\Order::STATUS_LABELS[$sk] }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            {{-- Dispatch Fields --}}
                            <div x-show="nextStatus === 'dispatched'"
                                class="space-y-3 mt-3 p-3 bg-purple-50 rounded-lg border border-purple-100">
                                <div>
                                    <label class="block text-xs font-semibold text-purple-700 mb-1">Provider *</label>
                                    <select name="shipping_provider_id" class="w-full text-sm border-purple-300 rounded-lg">
                                        <option value="">Select Provider...</option>
                                        @foreach($shippingProviders as $p)
                                            <option value="{{ $p->id }}" {{ $order->shipping_provider_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-purple-700 mb-1">Tracking Number
                                        *</label>
                                    <input type="text" name="tracking_number" value="{{ $order->tracking_number }}"
                                        class="w-full text-sm border-purple-300 rounded-lg">
                                </div>
                            </div>

                            {{-- Cancel Field --}}
                            <div x-show="nextStatus === 'cancelled'"
                                class="space-y-3 mt-3 p-3 bg-red-50 rounded-lg border border-red-100">
                                <div>
                                    <label class="block text-xs font-semibold text-red-700 mb-1">Reason for Cancellation
                                        *</label>
                                    <textarea name="cancellation_reason" rows="2"
                                        class="w-full text-sm border-red-300 rounded-lg" placeholder="Required"></textarea>
                                </div>
                            </div>

                            <button type="submit"
                                class="w-full py-2 bg-gray-800 text-white text-sm font-medium rounded-lg hover:bg-gray-900 transition-colors">
                                Update Status
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Audit Timeline --}}
                <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 overflow-hidden">
                    <h3 class="font-bold text-truffle-extra-dark p-4 border-b bg-[#F5F2EA] flex items-center gap-2">
                        <svg class="w-5 h-5 text-truffle-extra-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Activity Timeline
                    </h3>
                    <div class="p-4 max-h-[400px] overflow-y-auto">
                        <div class="relative border-l-2 border-truffle-medium/30 ml-3 space-y-6 pb-4">
                            @foreach($order->auditLogs as $log)
                                <div class="relative pl-6">
                                    <span
                                        class="absolute -left-[14px] bg-cream rounded-full text-lg shadow-sm border border-gray-100 w-6 h-6 flex items-center justify-center">
                                        {!! $log->action_icon !!}
                                    </span>
                                    <div class="flex justify-between items-start">
                                        <p class="text-sm font-semibold text-truffle-extra-dark">{{ $log->action_label }}</p>
                                        <span
                                            class="text-[10px] text-truffle-extra-dark/70 font-mono">{{ $log->created_at->format('M d, H:i') }}</span>
                                    </div>
                                    <p class="text-xs text-truffle-extra-dark mt-0.5">by {{ $log->user?->name ?? 'System' }}</p>
                                    @if($log->description)
                                        <p class="text-sm text-truffle-extra-dark mt-1.5 bg-[#F5F2EA] p-2 rounded border border-gray-100">
                                            {{ $log->description }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </div>
@endsection

    @section('scripts')
        <script>
            function copyCheckoutLink(url, btn) {
                navigator.clipboard.writeText(url).then(() => {
                    const feedback = btn.querySelector('.copy-feedback');
                    if (feedback) {
                        feedback.classList.remove('opacity-0');
                        feedback.classList.add('opacity-100');
                        setTimeout(() => {
                            feedback.classList.remove('opacity-100');
                            feedback.classList.add('opacity-0');
                        }, 2000);
                    }
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                    alert("Could not copy link to clipboard.");
                });
            }
        </script>
    @endsection
