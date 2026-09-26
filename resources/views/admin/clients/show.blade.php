@extends('admin.layout')

@section('header')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.clients.index') }}" class="text-truffle-extra-dark/70 hover:text-green-premium transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h2 class="text-xl font-bold flex items-center gap-2">
                {{ $client->name }} <span
                    class="text-sm font-mono text-truffle-extra-dark bg-[#F5F2EA] px-2 py-0.5 rounded">{{ $client->buyer_id }}</span>
            </h2>
            <p class="text-sm text-truffle-extra-dark">Client Profile & Order History</p>
        </div>
        <div class="ml-auto">
            <a href="{{ route('admin.clients.edit', $client) }}"
                class="px-4 py-2 border border-green-premium text-green-premium rounded-lg text-sm font-medium hover:bg-green-premium/10 transition-colors bg-cream">Edit
                Profile</a>
        </div>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Left: Profile Details --}}
        <div class="xl:col-span-1 space-y-6">
            <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-[#F5F2EA] flex justify-between items-center">
                    <h3 class="font-bold text-truffle-extra-dark">Contact Details</h3>
                    <span class="text-xs text-truffle-extra-dark/70">Since {{ $client->created_at->format('M Y') }}</span>
                </div>

                <div class="p-6 space-y-4 text-sm text-truffle-extra-dark">
                    <div class="flex justify-between items-start">
                        <span class="font-medium text-truffle-extra-dark">Company</span>
                        <span class="font-semibold text-right">{{ $client->company_name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between items-start">
                        <span class="font-medium text-truffle-extra-dark">Email</span>
                        <span class="text-right"><a href="mailto:{{ $client->email }}"
                                class="text-green-premium hover:underline">{{ $client->email ?? '-' }}</a></span>
                    </div>
                    <div class="flex justify-between items-start">
                        <span class="font-medium text-truffle-extra-dark">Phone</span>
                        <span class="text-right">{{ $client->phone ?? '-' }}</span>
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                        <h4 class="font-medium text-truffle-extra-dark mb-2">Primary Address (Shipping)</h4>
                        <address
                            class="not-italic text-right text-truffle-extra-dark leading-relaxed font-medium bg-[#F5F2EA] p-3 rounded border border-gray-100">
                            {!! nl2br(e($client->full_address)) !!}
                            @if(!$client->full_address) <span class="text-truffle-extra-dark/70 italic">No address recorded</span> @endif
                        </address>
                    </div>

                    <div class="flex justify-between items-start">
                        <span class="font-medium text-truffle-extra-dark">Shipping Country</span>
                        <span class="text-right">{{ $client->country_name ?? '-' }}</span>
                    </div>

                    @if($client->notes)
                        <div class="pt-4 border-t border-gray-100">
                            <h4 class="font-medium text-truffle-extra-dark mb-2">Remarks</h4>
                            <p
                                class="whitespace-pre-wrap text-xs bg-yellow-50 text-amber-900 border border-yellow-200 p-3 rounded">
                                {{ $client->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Order History --}}
        <div class="xl:col-span-2 space-y-6">
            <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-truffle-extra-dark">Order & Inquiry History</h3>
                    <a href="{{ route('admin.orders.create', ['client_id' => $client->id]) }}"
                        class="text-xs bg-green-premium/10 text-green-premium px-3 py-1.5 rounded-lg hover:bg-green-premium/20 font-medium font-semibold shadow-sm">+
                        Create Order</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#F5F2EA] border-b text-truffle-extra-dark">
                                <th class="px-6 py-3 font-semibold">Reference #</th>
                                <th class="px-6 py-3 font-semibold">Type</th>
                                <th class="px-6 py-3 font-semibold">Date</th>
                                <th class="px-6 py-3 font-semibold">Products</th>
                                <th class="px-6 py-3 font-semibold">Shipping</th>
                                <th class="px-6 py-3 font-semibold">Status</th>
                                <th class="px-6 py-3 font-semibold text-right">Amount</th>
                                <th class="px-6 py-3 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($client->orders as $o)
                                <tr class="hover:bg-[#F5F2EA] transition-colors">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('admin.orders.show', $o) }}"
                                            class="font-mono font-semibold text-green-premium hover:underline">{{ $o->order_number }}</a>
                                        @if($o->is_paid) <span
                                            class="text-[10px] bg-emerald-100 text-emerald-700 px-1.5 py-0.2 ml-1 rounded">PAID</span>
                                        @endif
                                        @if($o->hasReturnedItems())
                                            <span
                                                class="text-[10px] bg-red-100 text-red-700 px-1.5 py-0.2 ml-1 rounded">RETURN</span>
                                        @endif
                                        @if($o->type === 'inquiry' && $o->convertedOrder)
                                            <div class="text-[10px] text-indigo-600 mt-0.5">
                                                &rarr; Converted to
                                                <a href="{{ route('admin.orders.show', $o->convertedOrder) }}" class="hover:underline font-mono">{{ $o->convertedOrder->order_number }}</a>
                                            </div>
                                        @elseif($o->type === 'order' && $o->sourceInquiry)
                                            <div class="text-[10px] text-amber-600 mt-0.5">
                                                &larr; From inquiry
                                                <a href="{{ route('admin.orders.show', $o->sourceInquiry) }}" class="hover:underline font-mono">{{ $o->sourceInquiry->order_number }}</a>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="text-xs {{ $o->type === 'order' ? 'text-indigo-600 bg-indigo-50' : 'text-amber-600 bg-amber-50' }} px-2 py-0.5 rounded-full font-medium">{{ ucfirst($o->type) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-truffle-extra-dark">{{ $o->created_at->format('d M Y') }}</td>
                                    <td class="px-6 py-4 text-truffle-extra-dark">
                                        @php $firstItem = $o->items->first(); @endphp
                                        @if($firstItem)
                                            <div>{{ $firstItem->product_name }}</div>
                                            @if($o->items->count() > 1)
                                                <div class="text-xs text-truffle-extra-dark/70">+{{ $o->items->count() - 1 }} more item(s)</div>
                                            @endif
                                        @else
                                            <span class="text-truffle-extra-dark/70 italic">No items</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-truffle-extra-dark">{{ $o->shipping_country ?? '—' }}</div>
                                        <div class="text-xs text-truffle-extra-dark/70">{{ $o->shipping_provider_name ?? 'No provider' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $c = \App\Models\Order::STATUS_COLORS[$o->status] ?? 'gray';
                                            $map = ['gray' => 'bg-[#F5F2EA] text-truffle-extra-dark', 'blue' => 'bg-blue-100 text-blue-700', 'yellow' => 'bg-yellow-100 text-yellow-700', 'purple' => 'bg-purple-100 text-purple-700', 'green' => 'bg-green-premium/20 text-green-premium', 'red' => 'bg-red-100 text-red-700', 'indigo' => 'bg-indigo-100 text-indigo-700'];
                                        @endphp
                                        <span
                                            class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $map[$c] }}">{{ $o->status_label }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium text-truffle-extra-dark">
                                        ${{ number_format($o->grand_total, 2) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.orders.show', $o) }}"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-premium/10 text-green-premium rounded-lg text-xs font-medium hover:bg-green-premium/20 transition-colors">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-truffle-extra-dark">This client has no orders or
                                        inquiries yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
