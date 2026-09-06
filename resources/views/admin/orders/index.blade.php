@extends('admin.layout')

@section('header')
    <div>
        <h2 class="text-xl font-serif font-bold text-foreground">Order & Inquiry Management</h2>
        <p class="text-xs text-muted-foreground mt-0.5">Track artisan quotations, invoices, and customer communications</p>
    </div>
@endsection

@section('content')
    <div class="space-y-6">

        {{-- Header Actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('admin.orders.index') }}"
                    class="px-4 py-2 rounded-xl text-xs font-semibold uppercase tracking-wider transition-all {{ !request('type') ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-card border border-border text-muted-foreground hover:text-foreground hover:bg-paper/50' }}">
                    All
                </a>
                <a href="{{ route('admin.orders.index', ['type' => 'order']) }}"
                    class="px-4 py-2 rounded-xl text-xs font-semibold uppercase tracking-wider transition-all {{ request('type') === 'order' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-card border border-border text-muted-foreground hover:text-foreground hover:bg-paper/50' }}">
                    Direct Orders
                </a>
                <a href="{{ route('admin.orders.index', ['type' => 'inquiry']) }}"
                    class="px-4 py-2 rounded-xl text-xs font-semibold uppercase tracking-wider transition-all {{ request('type') === 'inquiry' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-card border border-border text-muted-foreground hover:text-foreground hover:bg-paper/50' }}">
                    Quote Inquiries
                </a>
            </div>
            <a href="{{ route('admin.orders.create') }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-semibold rounded-xl shadow-xs hover:shadow transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Order / Inquiry
            </a>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.orders.index') }}"
            class="bg-card rounded-2xl border border-border p-5 flex flex-wrap gap-4 items-end shadow-xs">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Order #, client name, buyer ID..."
                    class="w-full bg-paper/50 border border-border rounded-xl px-4 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Status</label>
                <select name="status"
                    class="w-full bg-paper/50 border border-border rounded-xl px-4 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full bg-paper/50 border border-border rounded-xl px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-full bg-paper/50 border border-border rounded-xl px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
            </div>
            <button type="submit"
                class="px-5 py-2.5 bg-primary hover:bg-primary/90 text-primary-foreground rounded-xl text-sm font-semibold transition shadow-xs">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                <a href="{{ route('admin.orders.index', array_filter(['type' => request('type')])) }}"
                    class="px-4 py-2.5 bg-paper/60 text-muted-foreground hover:text-foreground rounded-xl text-sm font-medium transition">
                    Clear
                </a>
            @endif
        </form>

        {{-- Orders Table --}}
        <div class="bg-cream rounded-xl border border-truffle-medium/30 overflow-hidden">
            @if($orders->count())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-[#F5F2EA]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-truffle-extra-dark uppercase">Order #</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-truffle-extra-dark uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-truffle-extra-dark uppercase">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-truffle-extra-dark uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-truffle-extra-dark uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-truffle-extra-dark uppercase">Invoice</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-truffle-extra-dark uppercase">Date</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-truffle-extra-dark uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($orders as $order)
                                @php
                                    $color = $statusColors[$order->status] ?? 'gray';
                                    $colorMap = [
                                        'gray' => 'bg-[#F5F2EA] text-truffle-extra-dark',
                                        'blue' => 'bg-blue-100 text-blue-700',
                                        'yellow' => 'bg-yellow-100 text-yellow-700',
                                        'purple' => 'bg-purple-100 text-purple-700',
                                        'green' => 'bg-green-premium/20 text-green-premium',
                                        'red' => 'bg-red-100 text-red-700',
                                    ];
                                    $badgeClass = $colorMap[$color] ?? 'bg-[#F5F2EA] text-truffle-extra-dark';
                                @endphp
                                <tr class="hover:bg-[#F5F2EA] transition-colors">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                            class="font-mono font-semibold text-green-premium hover:underline text-sm">
                                            {{ $order->order_number }}
                                        </a>
                                        @if($order->is_paid)
                                            <span
                                                class="ml-1 text-xs bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded font-medium">PAID</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="text-xs font-medium {{ $order->type === 'order' ? 'text-indigo-700 bg-indigo-50' : 'text-amber-700 bg-amber-50' }} px-2 py-0.5 rounded-full">
                                            {{ ucfirst($order->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($order->client)
                                            <a href="{{ route('admin.clients.show', $order->client) }}"
                                                class="text-sm font-medium text-truffle-extra-dark hover:text-green-premium">
                                                {{ $order->client->name }}
                                            </a>
                                            <div class="text-xs text-truffle-extra-dark/70">{{ $order->client->buyer_id }}</div>
                                        @else
                                            <span class="text-sm text-truffle-extra-dark/70 italic">No client</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                            {{ $order->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-truffle-extra-dark">
                                        ${{ number_format($order->grand_total, 2) }}</td>
                                    <td class="px-4 py-3">
                                        @if($order->latestInvoice)
                                            @php $inv = $order->latestInvoice; @endphp
                                            <a href="{{ route('admin.invoices.show', $inv) }}"
                                                class="text-xs font-mono text-indigo-600 hover:underline">
                                                {{ $inv->invoice_number }}
                                            </a>
                                            <div
                                                class="text-xs {{ $inv->status === 'issued' ? 'text-green-premium' : ($inv->status === 'voided' ? 'text-red-500' : 'text-yellow-600') }}">
                                                {{ ucfirst($inv->status) }}
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-300">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-truffle-extra-dark">{{ $order->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-right flex items-center justify-end gap-2">
                                        @if($order->type === 'inquiry' && $order->checkout_token && !$order->is_paid)
                                            <button onclick="copyCheckoutLink('{{ url('/checkout/' . $order->checkout_token) }}', this)"
                                                class="relative text-indigo-600 hover:text-indigo-800 p-1.5 rounded-lg hover:bg-indigo-50 transition-colors"
                                                title="Copy Checkout Link">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                <span
                                                    class="copy-feedback absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 transition-opacity whitespace-nowrap pointer-events-none z-50">Copied!</span>
                                            </button>
                                        @endif
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-premium/10 text-green-premium rounded-lg text-xs font-medium hover:bg-green-premium/20 transition-colors">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($orders->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $orders->withQueryString()->links() }}
                    </div>
                @endif

            @else
                <div class="text-center py-16 text-truffle-extra-dark/70">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="font-medium">No orders found</p>
                    <p class="text-sm mt-1">Create a new order or adjust filters</p>
                    <a href="{{ route('admin.orders.create') }}"
                        class="mt-4 inline-block px-4 py-2 bg-green-premium text-white text-sm rounded-lg hover:bg-green-800">
                        Create First Order
                    </a>
                </div>
            @endif
        </div>

        {{ $orders->withQueryString()->links() }}
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
