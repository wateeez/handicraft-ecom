@extends('admin.layout')

@section('header')
    <div>
        <h2 class="text-xl font-semibold text-foreground leading-tight">Profit &amp; commission &mdash; Order #{{ $order->order_number }}</h2>
        <p class="mt-1 text-sm text-muted-foreground">Every step of the calculation, from the snapshot recorded at calculation time.</p>
    </div>
@endsection

@section('content')
@php
    $usd = fn ($v) => 'USD ' . number_format((float) $v, 2);
    $npr = fn ($v) => 'NPR ' . number_format((float) $v, 2);
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 4), '0'), '.') . '%';
@endphp
<div class="space-y-6 p-4 sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.orders.show', $order) }}" class="text-sm text-muted-foreground hover:text-foreground">&larr; Back to order</a>
        <div class="flex gap-4 text-sm">
            <a href="{{ route('admin.finance.index', ['tool' => 'settings']) }}" class="font-semibold text-primary">Rates</a>
            @if($canManage)<a href="{{ route('admin.finance.vendors.index') }}" class="font-semibold text-primary">Vendors</a>@endif
        </div>
    </div>

    @if(strtoupper($order->currency ?? 'USD') !== 'USD')
        <div class="rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950" role="status">
            This order is in {{ $order->currency }}, but the profit calculation works in USD. Check the product price and shipping below before calculating.
        </div>
    @endif

    @if($snapshot)
        <section class="rounded-md border border-border bg-card">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4">
                <div>
                    <h3 class="text-base font-semibold">Version {{ $snapshot->version }}@if($latest && $snapshot->id === $latest->id) <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Latest</span>@endif</h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ $snapshot->created_at->format('M d, Y H:i') }} &middot; by {{ $snapshot->calculatedBy?->name ?? 'System' }}
                        &middot; Vendor {{ $snapshot->vendor_name ?? '—' }}
                        @if($snapshot->invoice) &middot; Invoice {{ $snapshot->invoice->invoice_number }}@endif
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Net profit</p>
                    <p class="text-2xl font-semibold tabular-nums {{ (float) $snapshot->net_profit_npr < 0 ? 'text-red-700' : '' }}">{{ $npr($snapshot->net_profit_npr) }}</p>
                </div>
            </header>
            <dl class="divide-y divide-border text-sm">
                @foreach([
                    ['Product price', $usd($snapshot->product_price_usd), null],
                    ['Shipping charged', $usd($snapshot->shipping_usd), null],
                    ['1. Selling price', $usd($snapshot->selling_price_usd), 'product + shipping'],
                    ['2. Gateway fee', $usd($snapshot->gateway_fee_usd), $pct($snapshot->gateway_fee_pct) . ' of selling price'],
                    ['3. Received', $usd($snapshot->received_usd), 'selling price − gateway fee'],
                    ['Forex rate', number_format((float) $snapshot->forex_rate, 4) . ' NPR/USD', null],
                    ['4. Received (NPR)', $npr($snapshot->received_npr), 'received × forex'],
                    ['5a. MRP', $npr($snapshot->mrp_npr), 'product price × forex'],
                    ['5b. Vendor cost price', $npr($snapshot->cost_price_npr), $pct($snapshot->vendor_cost_price_pct) . ' of MRP'],
                    ['5c. Vendor commission', $npr($snapshot->vendor_commission_npr), $pct($snapshot->vendor_commission_pct) . ' of cost price'],
                    ['5d. Vendor total', $npr($snapshot->vendor_total_npr), 'cost price + commission'],
                    ['6. Balance after vendor', $npr($snapshot->balance_after_vendor_npr), 'received − vendor total'],
                    ['7. Commission base', $usd($snapshot->commission_base_usd), $snapshot->include_shipping_in_commission ? 'product + shipping' : 'product only'],
                    ['8a. CSR', $usd($snapshot->csr_usd), $pct($snapshot->csr_pct) . ' of base'],
                    ['8b. CSR (NPR)', $npr($snapshot->csr_npr), 'at forex ' . number_format((float) $snapshot->forex_rate, 4)],
                    ['9a. Data entry', $usd($snapshot->data_entry_usd), $pct($snapshot->data_entry_pct) . ' of base'],
                    ['9b. Data entry rate used', number_format((float) $snapshot->data_entry_rate_used, 4), (float) $snapshot->forex_rate >= (float) $snapshot->de_threshold_rate ? 'real rate (≥ ' . (float) $snapshot->de_threshold_rate . ')' : 'fixed rate (real rate below ' . (float) $snapshot->de_threshold_rate . ')'],
                    ['9c. Data entry (NPR)', $npr($snapshot->data_entry_npr), null],
                    ['10. Leftover', $npr($snapshot->leftover_npr), 'balance − CSR − data entry'],
                    ['Actual shipping cost', $npr($snapshot->actual_shipping_cost_npr), null],
                    ['11. Net profit', $npr($snapshot->net_profit_npr), 'leftover − actual shipping cost'],
                ] as [$label, $value, $note])
                    <div class="flex flex-wrap items-baseline justify-between gap-2 px-5 py-2.5">
                        <dt>{{ $label }} @if($note)<span class="ml-1 text-xs text-muted-foreground">{{ $note }}</span>@endif</dt>
                        <dd class="font-medium tabular-nums">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @else
        <div class="rounded-md border border-border bg-card p-5 text-sm text-muted-foreground">This order has no profit calculation yet.</div>
    @endif

    <section class="rounded-md border border-border bg-card" aria-labelledby="payouts-heading">
        <header class="border-b border-border px-5 py-4">
            <h3 id="payouts-heading" class="text-base font-semibold">Payouts (NPR)</h3>
            <p class="mt-1 text-xs text-muted-foreground">Vendor, CSR and data entry payments. Amounts come from the latest calculation once the order is paid.</p>
        </header>
        @if($payables->isEmpty())
            <p class="px-5 py-4 text-sm text-muted-foreground">
                @if(!$order->is_paid) Nothing is payable until the order is marked paid.
                @elseif(!$latest) Calculate the order's profit to create the payouts.
                @else Payouts will appear after the next calculation.
                @endif
            </p>
        @else
            <div class="divide-y divide-border">
                @foreach(\App\Models\OrderPayable::TYPES as $type => $typeLabel)
                    @php $payable = $payables->get($type); @endphp
                    @if($payable)
                        <div class="px-5 py-4">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <p class="text-sm font-semibold">{{ $typeLabel }}@if($payable->vendor) <span class="font-normal text-muted-foreground">&middot; {{ $payable->vendor->name }}</span>@endif</p>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $payable->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : ($payable->status === 'partial' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700') }}">{{ ucfirst($payable->status) }}</span>
                            </div>
                            <dl class="mt-2 grid grid-cols-3 gap-2 text-sm">
                                <div><dt class="text-xs text-muted-foreground">Owed</dt><dd class="tabular-nums">{{ $npr($payable->amount) }}</dd></div>
                                <div><dt class="text-xs text-muted-foreground">Paid</dt><dd class="tabular-nums">{{ $npr($payable->paid_amount) }}</dd></div>
                                <div><dt class="text-xs text-muted-foreground">Balance</dt><dd class="font-medium tabular-nums">{{ $npr($payable->balance) }}</dd></div>
                            </dl>
                            @if($payable->payments->isNotEmpty())
                                <ul class="mt-3 space-y-1 text-xs text-muted-foreground">
                                    @foreach($payable->payments as $payment)
                                        <li>{{ $payment->paid_on->format('M d, Y') }} &middot; {{ $npr($payment->amount) }} &middot; {{ $payment->method }}@if($payment->reference) &middot; Ref {{ $payment->reference }}@endif &middot; by {{ $payment->recordedBy?->name ?? 'System' }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
            @if($canManage)
                <form method="POST" action="{{ route('admin.finance.orders.payouts.store', $order) }}" class="grid gap-3 border-t border-border p-5 sm:grid-cols-2">
                    @csrf
                    <p class="text-sm font-semibold sm:col-span-2">Record a payment</p>
                    <label class="block text-sm">Pay to
                        <select name="payee_type" required class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                            @foreach(\App\Models\OrderPayable::TYPES as $type => $typeLabel)
                                @if($payables->has($type))<option value="{{ $type }}" @selected(old('payee_type') === $type)>{{ $typeLabel }}</option>@endif
                            @endforeach
                        </select>
                        @error('payee_type')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm">Amount (NPR)
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                        @error('amount')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm">Paid on
                        <input type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                        @error('paid_on')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm">Method
                        <input name="method" value="{{ old('method') }}" required maxlength="100" placeholder="Bank transfer, cash, eSewa…" class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                        @error('method')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm">Reference
                        <input name="reference" value="{{ old('reference') }}" maxlength="255" class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                    </label>
                    <label class="block text-sm">Notes
                        <input name="notes" value="{{ old('notes') }}" maxlength="2000" class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                    </label>
                    <div class="sm:col-span-2"><button class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Record payment</button></div>
                </form>
            @endif
        @endif
    </section>

    @if($canManage)
        <section class="rounded-md border border-border bg-card">
            <header class="border-b border-border px-5 py-4">
                <h3 class="text-base font-semibold">{{ $latest ? 'Recalculate' : 'Calculate' }}</h3>
                <p class="mt-1 text-xs text-muted-foreground">Uses the current rates ({{ (float) $rates['gateway_fee_pct'] }}% gateway, {{ (float) $rates['csr_pct'] }}% CSR, {{ (float) $rates['data_entry_pct'] }}% data entry) and the chosen vendor's current terms. This adds a new version; earlier versions are kept.</p>
            </header>
            <form method="POST" action="{{ route('admin.finance.orders.profit.store', $order) }}" class="grid gap-4 p-5 sm:grid-cols-2">
                @csrf
                <label class="block text-sm">Vendor
                    <select name="vendor_id" required class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                        <option value="">Select vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $defaults['vendor_id']) === (string) $vendor->id)>{{ $vendor->name }} ({{ (float) $vendor->cost_price_pct }}% / {{ (float) $vendor->commission_pct }}%)</option>
                        @endforeach
                    </select>
                    @error('vendor_id')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm">Forex rate (NPR per USD)
                    <span class="mt-1 flex gap-2">
                        <input type="number" step="0.0001" min="0" name="forex_rate" id="forex-rate" value="{{ old('forex_rate', $defaults['forex_rate']) }}" required class="w-full rounded-md border border-border bg-card px-3 py-2">
                        <button type="button" id="forex-refresh" data-url="{{ route('admin.finance.forex.refresh') }}" class="shrink-0 rounded-md border border-border px-3 py-2 text-sm hover:bg-muted">Get latest</button>
                    </span>
                    <span id="forex-status" class="mt-1 block text-xs text-muted-foreground" aria-live="polite"></span>
                    @error('forex_rate')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm">Product price (USD)
                    <input type="number" step="0.01" min="0" name="product_price_usd" value="{{ old('product_price_usd', $defaults['product_price_usd']) }}" required class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                    @error('product_price_usd')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm">Shipping charged (USD)
                    <input type="number" step="0.01" min="0" name="shipping_usd" value="{{ old('shipping_usd', $defaults['shipping_usd']) }}" required class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                    @error('shipping_usd')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm sm:col-span-2">Actual shipping cost (NPR, optional)
                    <input type="number" step="0.01" min="0" name="actual_shipping_cost_npr" value="{{ old('actual_shipping_cost_npr', $defaults['actual_shipping_cost_npr']) }}" class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                    @error('actual_shipping_cost_npr')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
                </label>
                <div class="sm:col-span-2"><button class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">{{ $latest ? 'Recalculate (new version)' : 'Calculate' }}</button></div>
            </form>
        </section>
    @endif

    @if($snapshots->count() > 1)
        <section class="rounded-md border border-border bg-card">
            <h3 class="border-b border-border px-5 py-4 text-base font-semibold">History</h3>
            <ul class="divide-y divide-border text-sm">
                @foreach($snapshots as $row)
                    <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                        <a href="{{ route('admin.finance.orders.profit.show', [$order, 'version' => $row->version]) }}" class="font-semibold text-primary">Version {{ $row->version }}</a>
                        <span class="text-muted-foreground">{{ $row->created_at->format('M d, Y H:i') }} &middot; {{ $row->vendor_name ?? '—' }}</span>
                        <span class="tabular-nums">{{ $npr($row->net_profit_npr) }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection

@section('scripts')
<script>
document.getElementById('forex-refresh')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const status = document.getElementById('forex-status');
    button.disabled = true;
    status.textContent = 'Fetching latest NRB rate…';
    try {
        const response = await fetch(button.dataset.url, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Request failed');
        document.getElementById('forex-rate').value = data.rate;
        status.textContent = 'Updated to ' + data.rate + ' NPR per USD.';
    } catch (error) {
        status.textContent = error.message || 'Could not fetch a rate right now.';
    } finally {
        button.disabled = false;
    }
});
</script>
@endsection
