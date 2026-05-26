@extends('admin.layout')

@section('header')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.orders.show', $invoice->order) }}"
            class="text-truffle-extra-dark/70 hover:text-green-premium transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h2 class="text-xl font-bold flex items-center gap-2">
                Invoice <span class="font-mono text-indigo-700">{{ $invoice->invoice_number }}</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded
                        {{ $invoice->status === 'issued' ? 'bg-green-premium/20 text-green-premium' : '' }}
                        {{ $invoice->status === 'voided' ? 'bg-red-100 text-red-700' : '' }}
                        {{ $invoice->status === 'draft' ? 'bg-yellow-100 text-yellow-700' : '' }}">
                    {{ strtoupper($invoice->status) }}
                </span>
            </h2>
            <p class="text-sm text-truffle-extra-dark">For Order #{{ $invoice->order->order_number }}</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Top Action Bar --}}
        <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 p-4 flex justify-between items-center">
            <div class="text-sm text-truffle-extra-dark">
                <strong>Generated on:</strong> {{ $invoice->created_at->format('d M Y, H:i') }} by
                {{ $invoice->generatedBy?->name ?? 'System' }}
                @if($invoice->status === 'issued')
                    <br><strong>Issued on:</strong> {{ $invoice->issued_at->format('d M Y, H:i') }}
                @elseif($invoice->status === 'voided')
                    <br><strong>Voided on:</strong> {{ $invoice->voided_at->format('d M Y, H:i') }}
                    by {{ $invoice->voidedBy?->name ?? 'System' }} &mdash; <span class="italic text-red-600">Reason:
                        {{ $invoice->void_reason }}</span>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.invoices.download', $invoice) }}"
                    class="px-4 py-2 border border-truffle-medium/30 text-truffle-extra-dark rounded-lg hover:bg-[#F5F2EA] font-medium text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download PDF
                </a>

                @if($invoice->status === 'draft' && auth()->user()->hasPermission('manage_invoices'))
                    <form action="{{ route('admin.invoices.issue', $invoice) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 bg-green-premium hover:bg-green-800 text-white rounded-lg font-medium text-sm transition-colors"
                            onclick="return confirm('Ensure all details are correct. Issuing an invoice makes it an official record. Proceed?')">
                            Issue Invoice
                        </button>
                    </form>
                @endif

                @if($invoice->isVoidable() && auth()->user()->hasPermission('void_invoices'))
                    <button type="button"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium text-sm transition-colors"
                        onclick="document.getElementById('voidModal').classList.remove('hidden')">
                        Void Invoice
                    </button>
                @endif
            </div>
        </div>

        {{-- Invoice Preview Pane --}}
        <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 p-8">
            <h3 class="text-sm font-semibold text-truffle-extra-dark uppercase tracking-wider mb-6 text-center border-b pb-4">Invoice
                Preview (Snapshot Data)</h3>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <h4 class="text-truffle-extra-dark/70 font-semibold mb-2">Billed To</h4>
                    <div class="text-truffle-extra-dark text-sm">
                        <strong>{{ $invoice->client_snapshot['name'] ?? 'Walk-in Customer' }}</strong><br>
                        @if(isset($invoice->client_snapshot['company']) && $invoice->client_snapshot['company'])
                            {{ $invoice->client_snapshot['company'] }}<br>
                        @endif
                        @if(isset($invoice->client_snapshot['address']))
                            {{ $invoice->client_snapshot['address'] }}<br>
                            {{ $invoice->client_snapshot['city'] ?? '' }}
                            {{ $invoice->client_snapshot['state'] ? ', ' . $invoice->client_snapshot['state'] : '' }}
                            {{ $invoice->client_snapshot['zip_code'] ?? '' }}<br>
                            {{ $invoice->client_snapshot['country'] ?? '' }}
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <h4 class="text-truffle-extra-dark/70 font-semibold mb-2">Details</h4>
                    <div class="text-truffle-extra-dark text-sm">
                        <strong>Invoice #:</strong> {{ $invoice->invoice_number }}<br>
                        <strong>Date:</strong> {{ ($invoice->issued_at ?? $invoice->created_at)->format('d M Y') }}<br>
                        <strong>Order Ref:</strong> {{ $invoice->order->order_number }}
                    </div>
                </div>
            </div>

            <table class="w-full text-left text-sm mb-6 border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-800 text-truffle-extra-dark">
                        <th class="py-2">Description</th>
                        <th class="py-2 text-right">Qty</th>
                        <th class="py-2 text-right">Unit Price</th>
                        <th class="py-2 text-right">Discount</th>
                        <th class="py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-truffle-extra-dark">
                    @foreach($invoice->financial_snapshot['items'] as $item)
                        <tr>
                            <td class="py-3">
                                <span class="font-medium text-truffle-extra-dark">{{ $item['product_name'] }}</span>
                                @if($item['product_sku'])
                                <div class="text-xs text-truffle-extra-dark/70">SKU: {{ $item['product_sku'] }}</div> @endif

                            </td>
                            <td class="py-3 text-right">{{ $item['quantity'] }}</td>
                            <td class="py-3 text-right">${{ number_format($item['unit_price'], 2) }}</td>
                            <td class="py-3 text-right text-xs">
                                @if($item['item_discount_amount'] > 0)
                                    -${{ number_format($item['item_discount_amount'], 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 text-right font-medium text-truffle-extra-dark">${{ number_format($item['line_total'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="flex justify-end border-t-2 border-gray-800 pt-4">
                <div class="w-64 space-y-2 text-sm text-truffle-extra-dark">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span>${{ number_format($invoice->financial_snapshot['subtotal'], 2) }}</span>
                    </div>
                    @if($invoice->financial_snapshot['item_discount_total'] > 0)
                        <div class="flex justify-between text-red-500">
                            <span>Item Discounts</span>
                            <span>-${{ number_format($invoice->financial_snapshot['item_discount_total'], 2) }}</span>
                        </div>
                    @endif
                    @if($invoice->financial_snapshot['order_discount_amount'] > 0)
                        <div class="flex justify-between text-red-500">
                            <span>Order Discount</span>
                            <span>-${{ number_format($invoice->financial_snapshot['order_discount_amount'], 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between border-b pb-2">
                        <span>Shipping</span>
                        <span>${{ number_format($invoice->financial_snapshot['shipping_cost'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-truffle-extra-dark pt-2">
                        <span>Total Due</span>
                        <span>${{ number_format($invoice->financial_snapshot['grand_total'], 2) }}</span>
                    </div>
                </div>
            </div>

            @if($invoice->status === 'voided')
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none overflow-hidden">
                    <div
                        class="text-9xl font-black text-red-600 opacity-10 transform -rotate-12 select-none border-8 border-red-600 rounded-3xl px-8">
                        VOID</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Void Modal --}}
    @if($invoice->isVoidable() && auth()->user()->hasPermission('void_invoices'))
        <div id="voidModal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50">
            <div class="bg-cream rounded-xl shadow-xl p-6 w-full max-w-md">
                <h3 class="text-xl font-bold text-red-600 mb-2">Void Invoice</h3>
                <p class="text-sm text-truffle-extra-dark mb-4">Are you absolutely sure you want to void Invoice
                    #{{ $invoice->invoice_number }}? This action requires a mandatory reason and cannot be undone.</p>

                <form action="{{ route('admin.invoices.void', $invoice) }}" method="POST">
                    @csrf
                    <label class="block text-sm font-medium text-truffle-extra-dark mb-1">Reason for Voiding <span
                            class="text-red-500">*</span></label>
                    <textarea name="void_reason" rows="3"
                        class="w-full border-truffle-medium/30 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500 mb-4"
                        required></textarea>

                    <div class="flex justify-end gap-2">
                        <button type="button"
                            class="px-4 py-2 text-truffle-extra-dark hover:bg-[#F5F2EA] rounded-lg font-medium text-sm transition-colors"
                            onclick="document.getElementById('voidModal').classList.add('hidden')">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg font-medium text-sm hover:bg-red-700 transition-colors">Confirm
                            Void</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endsection
