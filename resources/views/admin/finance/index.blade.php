@extends('admin.layout')

@section('header')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-foreground leading-tight">Finance overview</h2>
            <p class="mt-1 text-sm text-muted-foreground">Receipts, profit, payouts and balances at a glance.</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 rounded-md border border-border px-4 py-2 text-sm font-semibold hover:bg-muted/40">Browse orders &rarr;</a>
    </div>
@endsection

@section('content')
@php
    $user = auth()->user();
    $currency = $financeSettings->reporting_currency ?? 'NPR';
    $money = fn ($v, $c = null) => ($c ?: $currency) . ' ' . number_format((float) $v, 2);
    $maxAging = max(1, collect($outstandingAging)->max('balance') ?? 1);
    $netReceipts = (float) $receiptSummary['receipts'] - (float) $receiptSummary['refunds'] - (float) $receiptSummary['reversals'];
    $maxBar = max(1, (float) $receiptSummary['receipts'], (float) $receiptSummary['refunds'], (float) $receiptSummary['reversals']);

    $series = $dash['receipts'] ?? null;
    $pf = $dash['profit'] ?? null;
    $netProfit = $profit['net_profit'] ?? '0';
    $profitNegative = bccomp($netProfit, '0', 2) < 0;
    $tabs = ['overview' => 'Overview', 'receipts' => 'Receipts', 'profit' => 'Profit', 'payouts' => 'Payouts', 'receivables' => 'Receivables', 'sales' => 'Sales'];
    $tabBadges = ['payouts' => collect($payoutBreakdown)->filter(fn ($r) => bccomp($r['balance'], '0', 2) > 0)->count(), 'receipts' => $pendingPayments];
    $costItems = $pf ? [
        ['label' => 'Vendor', 'value' => $pf['parts']['vendor'], 'color' => 'var(--chart-1)'],
        ['label' => 'CSR', 'value' => $pf['parts']['csr'], 'color' => 'var(--chart-4)'],
        ['label' => 'Data entry', 'value' => $pf['parts']['data_entry'], 'color' => 'var(--chart-3)'],
        ['label' => 'Gateway fees', 'value' => $pf['parts']['gateway'], 'color' => 'var(--chart-5)'],
        ['label' => 'Shipping', 'value' => $pf['parts']['shipping'], 'color' => 'var(--muted-foreground)'],
        ['label' => 'Net profit', 'value' => $pf['parts']['net'], 'color' => 'var(--chart-2)'],
    ] : [];
    $payeeColors = ['Vendor' => 'var(--chart-1)', 'CSR' => 'var(--chart-4)', 'Data entry' => 'var(--chart-3)'];
@endphp
@include('admin.finance.partials.styles')
<div class="space-y-8 p-4 sm:p-6">
    @unless($financeTablesReady)
        <div class="flex items-start gap-3 rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950" role="status">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
            <div>
                <p class="font-semibold">Finance tables are not ready</p>
                <p class="mt-1">Apply the finance migrations to enable receipt, vendor, and commission totals.</p>
            </div>
        </div>
    @endunless

    {{-- Each tool opens from a button; only one panel shows at a time --}}
        @php
            $rateKeys = ['gateway_fee_pct', 'csr_pct', 'data_entry_pct', 'de_fixed_rate', 'de_threshold_rate', 'include_shipping_in_commission'];
            $canSettings = $financeTablesReady && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_finance'));
            $canRecord = $financeTablesReady && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('record_payments'));
        @endphp
        <section aria-labelledby="tools-heading" class="space-y-3" x-data="{ tool: @js(request('tool') ?: ($errors->hasAny($rateKeys) ? 'settings' : null)) }">
            <h3 id="tools-heading" class="sr-only">Finance tools</h3>
            <div class="flex flex-wrap gap-2">
                @if($canSettings)
                    <button type="button" @click="tool = tool === 'settings' ? null : 'settings'" :aria-expanded="tool === 'settings'" aria-controls="tool-settings" :class="tool === 'settings' ? 'ring-2 ring-primary ring-offset-2' : ''" class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">Finance settings</button>
                @endif
                @if($financeTablesReady)
                    <button type="button" @click="tool = tool === 'lookup' ? null : 'lookup'" :aria-expanded="tool === 'lookup'" aria-controls="tool-lookup" :class="tool === 'lookup' ? 'ring-2 ring-primary ring-offset-2' : ''" class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">Client finance lookup</button>
                @endif
                @if($canRecord)
                    <button type="button" @click="tool = tool === 'record' ? null : 'record'" :aria-expanded="tool === 'record'" aria-controls="tool-record" :class="tool === 'record' ? 'ring-2 ring-primary ring-offset-2' : ''" class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">Record payment</button>
                @endif
            </div>
    @if($canSettings)
        <div x-show="tool === 'settings'" x-cloak id="tool-settings" class="rounded-md border border-border bg-card">
            <div class="flex items-start justify-between gap-3 px-5 py-4">
                <div><h4 class="text-sm font-semibold">Finance settings</h4></div>
                <button type="button" @click="tool = null" class="rounded-md border border-border px-2.5 py-1 text-xs font-semibold hover:bg-muted/40" aria-label="Close Finance settings">Close</button>
            </div>
            <form method="POST" action="{{ route('admin.finance.settings.update') }}" class="grid gap-4 border-t border-border p-5 md:grid-cols-3">
                @csrf @method('PUT')
                <label class="text-sm">Reporting currency<input name="reporting_currency" value="{{ $financeSettings->reporting_currency }}" maxlength="3" class="mt-1 w-full rounded-md border border-border bg-background px-3 py-2 uppercase"></label>
                <label class="text-sm">Commission type<select name="default_commission_type" class="mt-1 w-full rounded-md border border-border bg-background px-3 py-2"><option value="percent" @selected($financeSettings->default_commission_type === 'percent')>Percent</option><option value="fixed" @selected($financeSettings->default_commission_type === 'fixed')>Fixed</option></select></label>
                <label class="text-sm">Commission value<input type="number" min="0" step="0.01" name="default_commission_value" value="{{ $financeSettings->default_commission_value }}" class="mt-1 w-full rounded-md border border-border bg-background px-3 py-2"></label>
                <label class="text-sm">Commission earned<select name="commission_earned_trigger" class="mt-1 w-full rounded-md border border-border bg-background px-3 py-2"><option value="payment" @selected($financeSettings->commission_earned_trigger === 'payment')>On payment</option><option value="delivery" @selected($financeSettings->commission_earned_trigger === 'delivery')>On delivery</option></select></label>
                <label class="text-sm">Loyalty basis<select name="loyalty_basis" class="mt-1 w-full rounded-md border border-border bg-background px-3 py-2"><option value="lifetime_spend" @selected($financeSettings->loyalty_basis === 'lifetime_spend')>Lifetime spend</option><option value="order_count" @selected($financeSettings->loyalty_basis === 'order_count')>Order count</option></select></label>
                <input type="hidden" name="commission_base" value="{{ $financeSettings->commission_base }}">
                <div class="flex items-end"><button class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Save settings</button></div>
            </form>

            <form method="POST" action="{{ route('admin.finance.rates.update') }}" class="space-y-4 border-t border-border p-5">
                @csrf @method('PUT')
                <div>
                    <h5 class="text-sm font-semibold">Profit &amp; commission rates</h5>
                    <p class="mt-1 text-xs text-muted-foreground">Applied to new calculations only. Saved snapshots never change.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach([
                        ['gateway_fee_pct', 'Payment gateway fee (%)', '0.01', 'between 0 and 100'],
                        ['csr_pct', 'CSR commission (%)', '0.01', 'of commission base'],
                        ['data_entry_pct', 'Data entry commission (%)', '0.01', 'of commission base'],
                        ['de_fixed_rate', 'Data entry fixed rate (NPR/USD)', '0.0001', 'used when forex is below the threshold'],
                        ['de_threshold_rate', 'Data entry threshold rate (NPR/USD)', '0.0001', 'real rate used at or above this'],
                    ] as [$key, $label, $step, $hint])
                        <label class="block text-sm">{{ $label }}
                            <input type="number" step="{{ $step }}" min="0" name="{{ $key }}" value="{{ old($key, (float) $rates[$key]) }}" required class="mt-1 w-full rounded-md border border-border bg-background px-3 py-2">
                            <span class="mt-1 block text-xs text-muted-foreground">{{ $hint }}</span>
                            @error($key)<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
                        </label>
                    @endforeach
                    <label class="flex items-center gap-2 self-end pb-2 text-sm">
                        <input type="hidden" name="include_shipping_in_commission" value="0">
                        <input type="checkbox" name="include_shipping_in_commission" value="1" @checked(old('include_shipping_in_commission', $rates['include_shipping_in_commission']))>
                        Include shipping in the commission base
                    </label>
                </div>
                <button class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Save rates</button>
            </form>
                </div>
    @endif
    @if($financeTablesReady)
        <div x-show="tool === 'lookup'" x-cloak id="tool-lookup" class="rounded-md border border-border bg-card">
            <div class="flex items-start justify-between gap-3 px-5 py-4">
                <div><h4 class="text-sm font-semibold">Client finance lookup</h4><p class="mt-1 text-xs text-muted-foreground">Load derived purchase metrics from confirmed payments and refunds.</p></div>
                <button type="button" @click="tool = null" class="rounded-md border border-border px-2.5 py-1 text-xs font-semibold hover:bg-muted/40" aria-label="Close Client finance lookup">Close</button>
            </div>
            <div class="border-t border-border p-5" x-data="{ clientId: '', summary: null, loading: false }">
                <div class="flex max-w-lg gap-2">
                    <input x-model="clientId" type="number" min="1" placeholder="Client ID" class="w-full rounded-md border border-border bg-background px-3 py-2">
                    <button @click="loading = true; fetch(`/admin/finance/clients/${clientId}/summary`, {headers:{Accept:'application/json'}}).then(r => r.json()).then(data => summary = data).finally(() => loading = false)" class="rounded-md border border-border px-4 py-2 text-sm font-semibold">Load</button>
                </div>
                <template x-if="summary"><div class="mt-4 grid gap-3 sm:grid-cols-3 text-sm"><div><span class="text-muted-foreground">Lifetime spend</span><p class="font-semibold" x-text="summary.lifetime_spend"></p></div><div><span class="text-muted-foreground">Paid orders</span><p class="font-semibold" x-text="summary.total_orders"></p></div><div><span class="text-muted-foreground">Average order</span><p class="font-semibold" x-text="summary.average_order_value"></p></div></div></template>
            </div>
                </div>
    @endif
    @if($canRecord)
        <div x-show="tool === 'record'" x-cloak id="tool-record" class="rounded-md border border-border bg-card">
            <div class="flex items-start justify-between gap-3 px-5 py-4">
                <div><h4 class="text-sm font-semibold">Record payment</h4><p class="mt-1 text-xs text-muted-foreground">Creates a pending payment; a separate authorized user confirms it.</p></div>
                <button type="button" @click="tool = null" class="rounded-md border border-border px-2.5 py-1 text-xs font-semibold hover:bg-muted/40" aria-label="Close Record payment">Close</button>
            </div>
            <div class="border-t border-border p-5" x-data="{ orderId: '' }">
                <form class="grid gap-3 md:grid-cols-3" :action="`/admin/finance/orders/${orderId}/payments`" method="POST">
                    @csrf
                    <input x-model="orderId" type="number" min="1" required placeholder="Order ID" class="rounded-md border border-border bg-background px-3 py-2">
                    <input name="amount" type="number" min="0.01" step="0.01" required placeholder="Order currency amount" class="rounded-md border border-border bg-background px-3 py-2">
                    <input name="reporting_amount" type="number" min="0.01" step="0.01" required placeholder="Reporting amount" class="rounded-md border border-border bg-background px-3 py-2">
                    <input name="reporting_exchange_rate" type="number" min="0.00000001" step="0.00000001" value="1" required class="rounded-md border border-border bg-background px-3 py-2">
                    <input name="currency" maxlength="3" placeholder="Currency (e.g. USD)" class="rounded-md border border-border bg-background px-3 py-2">
                    <select name="method" required class="rounded-md border border-border bg-background px-3 py-2"><option value="">Payment method</option><option>Bank transfer</option><option>PayPal</option><option>Card</option><option>Cash</option></select>
                    <input name="reference" placeholder="Transaction reference" class="rounded-md border border-border bg-background px-3 py-2">
                    <input name="received_on" type="date" value="{{ now()->toDateString() }}" required class="rounded-md border border-border bg-background px-3 py-2">
                    <button class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Record payment</button>
                </form>
            </div>
                </div>
    @endif
            </section>

    {{-- Headline numbers --}}
    <section aria-labelledby="kpi-heading">
        <h3 id="kpi-heading" class="sr-only">Key figures</h3>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="fv-card fv-rise rounded-lg border border-border bg-card p-5" style="--i: 0">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Confirmed receipts</p>
                    @if($series)<x-finance.sparkline :values="$series['receipts']" color="var(--chart-2)" :w="64" :h="24" />@endif
                </div>
                <p class="mt-3 break-words text-2xl font-bold tabular-nums">{{ $money($confirmedReceipts, $currency) }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Payments received and confirmed</p>
            </article>
            <article class="fv-card fv-rise rounded-lg border p-5 {{ $profitNegative ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50' }}" style="--i: 1">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Net profit</p>
                    @if($pf)<x-finance.sparkline :values="collect($pf['by_month'])->pluck('net')->all()" :color="$profitNegative ? 'var(--chart-5)' : 'var(--chart-2)'" :w="64" :h="24" />@endif
                </div>
                <p class="mt-3 break-words text-2xl font-bold tabular-nums {{ $profitNegative ? 'text-red-700' : 'text-emerald-800' }}">{{ $money($netProfit, 'NPR') }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ $profit['calculated_orders'] ?? 0 }} paid order(s) calculated</p>
            </article>
            <article class="fv-card fv-rise rounded-lg border border-border bg-card p-5" style="--i: 2">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Payouts outstanding</p>
                <p class="mt-3 break-words text-2xl font-bold tabular-nums">{{ $money($outstandingPayouts, 'NPR') }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Vendor, CSR and data entry</p>
            </article>
            <article class="fv-card fv-rise rounded-lg border border-border bg-card p-5" style="--i: 3">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Commission outstanding</p>
                <p class="mt-3 break-words text-2xl font-bold tabular-nums">{{ $money($outstandingCommissions, $currency) }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Pending and payable commissions</p>
            </article>
            <article class="fv-card fv-rise rounded-lg border border-border bg-card p-5" style="--i: 4">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Invoices</p>
                <p class="mt-3 text-2xl font-bold tabular-nums">{{ number_format($invoiceCount) }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Issued and draft</p>
            </article>
        </div>
    </section>

    @if($financeTablesReady)
        {{-- Things that need someone to act --}}
        @if($pendingPayments > 0 || ($profit['awaiting'] ?? 0) > 0)
            <section aria-labelledby="attention-heading" class="fv-rise rounded-lg border border-amber-300 bg-amber-50 p-5" style="--i: 5">
                <h3 id="attention-heading" class="text-sm font-semibold text-amber-950">Needs attention</h3>
                <ul class="mt-2 space-y-1 text-sm text-amber-950">
                    @if($pendingPayments > 0)<li><strong>{{ $pendingPayments }}</strong> payment(s) waiting for confirmation.</li>@endif
                    @if(($profit['awaiting'] ?? 0) > 0)<li><strong>{{ $profit['awaiting'] }}</strong> paid order(s) have no profit calculation yet, so no payouts are due for them. Open each one and calculate its profit:
                        <span class="mt-1 flex flex-wrap gap-2">
                            @foreach($profit['awaiting_orders'] as $awaitingOrder)
                                <a href="{{ route('admin.finance.orders.profit.show', $awaitingOrder) }}" class="inline-flex items-center rounded-md border border-amber-400 bg-white px-2.5 py-1 text-xs font-semibold hover:bg-amber-100">{{ $awaitingOrder->order_number }} &rarr;</a>
                            @endforeach
                            @if($profit['awaiting'] > $profit['awaiting_orders']->count())<span class="self-center text-xs">and {{ $profit['awaiting'] - $profit['awaiting_orders']->count() }} more</span>@endif
                        </span>
                    </li>@endif
                </ul>
            </section>
        @endif

        {{-- Detail reports as tabs. Every panel stays in the page; tabs only show/hide. --}}
        <div x-data="{
                tab: 'overview',
                tabs: @js(array_keys($tabs)),
                init() {
                    const fromHash = () => { const t = location.hash.slice(1); if (this.tabs.includes(t)) this.tab = t; };
                    fromHash();
                    window.addEventListener('hashchange', fromHash);
                },
                go(t) { this.tab = t; history.replaceState(null, '', '#' + t); },
                move(step) { const i = (this.tabs.indexOf(this.tab) + step + this.tabs.length) % this.tabs.length; this.go(this.tabs[i]); this.$nextTick(() => document.getElementById('tab-' + this.tab)?.focus()); }
            }" class="space-y-6">

            <div class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <div role="tablist" aria-label="Finance reports" class="flex min-w-max gap-1 border-b border-border" @keydown.arrow-right.prevent="move(1)" @keydown.arrow-left.prevent="move(-1)">
                    @foreach($tabs as $key => $label)
                        <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                            :aria-selected="tab === '{{ $key }}'" :tabindex="tab === '{{ $key }}' ? 0 : -1"
                            @click="go('{{ $key }}')"
                            class="fv-tab inline-flex items-center gap-2 px-4 py-3 text-sm font-semibold text-muted-foreground hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary"
                            aria-selected="{{ $key === 'overview' ? 'true' : 'false' }}">
                            {{ $label }}
                            @if(($tabBadges[$key] ?? 0) > 0)<span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-900">{{ $tabBadges[$key] }}</span>@endif
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- ============ OVERVIEW ============ --}}
            <div id="panel-overview" role="tabpanel" aria-labelledby="tab-overview" x-show="tab === 'overview'" class="space-y-6">
                <div class="grid gap-6 xl:grid-cols-5">
                    <section aria-labelledby="trend-heading" class="fv-rise rounded-lg border border-border bg-card xl:col-span-3" style="--i: 1">
                        <header class="flex items-start justify-between gap-3 border-b border-border px-5 py-4">
                            <div>
                                <h3 id="trend-heading" class="font-semibold">Receipts trend</h3>
                                <p class="mt-1 text-xs text-muted-foreground">{{ $series['filtered'] ? $series['from'] . ' to ' . $series['to'] : 'Last 30 days' }} &middot; {{ $currency }}</p>
                            </div>
                            <button type="button" @click="go('receipts')" class="text-sm font-semibold text-primary hover:underline">Details &rarr;</button>
                        </header>
                        <div class="p-5">
                            <x-finance.area-chart :labels="$series['labels']" :currency="$currency" :height="200" summary="Confirmed receipts over the selected period"
                                :series="[['name' => 'Receipts', 'color' => 'var(--chart-2)', 'values' => $series['receipts'], 'fill' => true]]" />
                        </div>
                    </section>
                    <section aria-labelledby="money-heading" class="fv-rise rounded-lg border border-border bg-card xl:col-span-2" style="--i: 2">
                        <header class="flex items-start justify-between gap-3 border-b border-border px-5 py-4">
                            <div>
                                <h3 id="money-heading" class="font-semibold">Where the money goes</h3>
                                <p class="mt-1 text-xs text-muted-foreground">Selling price of calculated orders, split in NPR.</p>
                            </div>
                            <button type="button" @click="go('profit')" class="text-sm font-semibold text-primary hover:underline">Details &rarr;</button>
                        </header>
                        <div class="p-5">
                            @if($pf['count'] > 0)
                                <x-finance.donut :items="$costItems" currency="NPR" centerLabel="Net profit" :centerValue="'NPR ' . number_format((float) $netProfit, 0)" summary="Where the selling price of calculated orders goes" />
                            @else
                                <p class="py-10 text-center text-sm text-muted-foreground">Calculate a paid order's profit to see the split.</p>
                            @endif
                        </div>
                    </section>
                </div>

                <section aria-labelledby="progress-heading" class="fv-rise rounded-lg border border-border bg-card" style="--i: 3">
                    <header class="flex items-start justify-between gap-3 border-b border-border px-5 py-4">
                        <div>
                            <h3 id="progress-heading" class="font-semibold">Payout progress</h3>
                            <p class="mt-1 text-xs text-muted-foreground">Share of each payee's owed amount that has been paid.</p>
                        </div>
                        <button type="button" @click="go('payouts')" class="text-sm font-semibold text-primary hover:underline">Details &rarr;</button>
                    </header>
                    <div class="grid gap-px bg-border sm:grid-cols-3">
                        @foreach($payoutBreakdown as $row)
                            <div class="flex items-center gap-4 bg-card p-5">
                                <x-finance.ring :percent="$row['percent']" :color="$payeeColors[$row['label']] ?? 'var(--chart-2)'" :label="$row['label']" />
                                <div class="min-w-0">
                                    <p class="font-semibold">{{ $row['label'] }}</p>
                                    <p class="text-xs text-muted-foreground">{{ $row['orders'] }} order(s)</p>
                                    <p class="mt-1 break-words text-sm tabular-nums {{ bccomp($row['balance'], '0', 2) > 0 ? 'text-amber-800' : 'text-emerald-800' }}">{{ $money($row['balance'], 'NPR') }} left</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            {{-- ============ RECEIPTS ============ --}}
            <div id="panel-receipts" role="tabpanel" aria-labelledby="tab-receipts" x-show="tab === 'receipts'" x-cloak class="space-y-6">
                <section aria-labelledby="activity-heading" class="fv-rise rounded-lg border border-border bg-card">
                    <header class="flex flex-wrap items-end justify-between gap-3 border-b border-border px-5 py-4">
                        <div>
                            <h3 id="activity-heading" class="font-semibold">Receipts activity</h3>
                            <p class="mt-1 text-xs text-muted-foreground">{{ $reportFrom || $reportTo ? 'Filtered: ' . ($reportFrom ?: 'start') . ' to ' . ($reportTo ?: 'today') : 'All time' }} &middot; {{ $currency }}</p>
                        </div>
                        <form method="GET" action="{{ route('admin.finance.index') }}#receipts" class="flex flex-wrap items-end gap-2">
                            <label class="text-xs text-muted-foreground">From <input type="date" name="from" value="{{ $reportFrom }}" class="mt-1 block rounded-md border border-border bg-background px-3 py-1.5 text-sm text-foreground"></label>
                            <label class="text-xs text-muted-foreground">To <input type="date" name="to" value="{{ $reportTo }}" class="mt-1 block rounded-md border border-border bg-background px-3 py-1.5 text-sm text-foreground"></label>
                            <button class="rounded-md border border-border px-3 py-1.5 text-sm font-semibold hover:bg-muted/40">Apply</button>
                            @if($reportFrom || $reportTo)<a href="{{ route('admin.finance.index') }}#receipts" class="px-2 py-1.5 text-sm text-muted-foreground hover:text-foreground">Clear</a>@endif
                        </form>
                    </header>
                    <div class="grid gap-6 p-5 lg:grid-cols-[minmax(0,1fr)_16rem]">
                        <div class="space-y-4">
                            @foreach([['Receipts', $receiptSummary['receipts'], 'var(--chart-2)'], ['Refunds', $receiptSummary['refunds'], 'var(--chart-3)'], ['Reversals', $receiptSummary['reversals'], 'var(--chart-5)']] as $i => [$label, $value, $color])
                                <div>
                                    <div class="flex items-baseline justify-between text-sm"><span>{{ $label }}</span><span class="font-semibold tabular-nums">{{ $money($value) }}</span></div>
                                    <div class="mt-1.5 h-3 w-full overflow-hidden rounded-full bg-muted" role="img" aria-label="{{ $label }}: {{ $money($value) }}"><div class="fv-grow-x h-3 rounded-full" style="--i: {{ $i }}; background: {{ $color }}; width: {{ (float) $value > 0 ? max(2, round((float) $value / $maxBar * 100)) : 0 }}%"></div></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="rounded-md bg-muted/40 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Net receipts</p>
                            <p class="mt-2 break-words text-2xl font-bold tabular-nums {{ $netReceipts < 0 ? 'text-red-700' : '' }}">{{ $money($netReceipts) }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">Receipts − refunds − reversals</p>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ============ PROFIT ============ --}}
            <div id="panel-profit" role="tabpanel" aria-labelledby="tab-profit" x-show="tab === 'profit'" x-cloak class="space-y-6">
                <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach([
                        ['Net profit', $money($pf['parts']['net'], 'NPR'), $profitNegative ? 'text-red-700' : 'text-emerald-800'],
                        ['Received', $money($pf['received'], 'NPR'), ''],
                        ['Orders calculated', number_format($pf['count']), ''],
                        ['Average profit / order', $money($pf['average'], 'NPR'), bccomp($pf['average'], '0', 2) < 0 ? 'text-red-700' : ''],
                    ] as $i => [$label, $value, $tone])
                        <div class="fv-card fv-rise rounded-lg border border-border bg-card p-5" style="--i: {{ $i }}">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ $label }}</dt>
                            <dd class="mt-2 break-words text-2xl font-bold tabular-nums {{ $tone }}">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="grid gap-6 xl:grid-cols-2">
                    <section aria-labelledby="byorder-heading" class="fv-rise rounded-lg border border-border bg-card" style="--i: 1">
                        <header class="border-b border-border px-5 py-4">
                            <h3 id="byorder-heading" class="font-semibold">Net profit by order</h3>
                            <p class="mt-1 text-xs text-muted-foreground">Latest 12 calculated orders, in NPR. Click a bar to open the order's profit page.</p>
                        </header>
                        <div class="p-5">
                            <x-finance.bars currency="NPR" summary="Net profit for the latest calculated orders"
                                :items="collect($pf['by_order'])->map(fn ($r) => ['label' => $r['label'], 'value' => $r['net'], 'href' => route('admin.finance.orders.profit.show', $r['order_id'])])->all()" />
                        </div>
                    </section>
                    <section aria-labelledby="bymonth-heading" class="fv-rise rounded-lg border border-border bg-card" style="--i: 2">
                        <header class="border-b border-border px-5 py-4">
                            <h3 id="bymonth-heading" class="font-semibold">Monthly net profit</h3>
                            <p class="mt-1 text-xs text-muted-foreground">Last 6 months, by when each calculation was made, in NPR.</p>
                        </header>
                        <div class="p-5">
                            <x-finance.area-chart currency="NPR" summary="Net profit per month" :labels="collect($pf['by_month'])->pluck('label')->all()"
                                :series="[['name' => 'Net profit', 'color' => 'var(--chart-1)', 'values' => collect($pf['by_month'])->pluck('net')->map(fn ($v) => max(0, $v))->all(), 'fill' => true]]" />
                        </div>
                    </section>
                </div>

                <section aria-labelledby="vendors-heading" class="fv-rise rounded-lg border border-border bg-card" style="--i: 3">
                    <header class="flex items-center justify-between gap-3 border-b border-border px-5 py-4">
                        <div>
                            <h3 id="vendors-heading" class="font-semibold">Profit by vendor</h3>
                            <p class="mt-1 text-xs text-muted-foreground">Calculated orders grouped by the vendor used, in NPR.</p>
                        </div>
                        @if($user->hasPermission('manage_finance'))<a href="{{ route('admin.finance.vendors.index') }}" class="text-sm font-semibold text-primary">Vendors</a>@endif
                    </header>
                    @php $maxVendor = max(1, collect($pf['vendors'])->map(fn ($v) => abs((float) $v['net']))->max() ?? 1); @endphp
                    <ul class="divide-y divide-border">
                        @forelse($pf['vendors'] as $i => $v)
                            <li class="px-5 py-4">
                                <div class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                                    <p class="font-medium">{{ $v['name'] }} <span class="text-xs font-normal text-muted-foreground">{{ $v['orders'] }} order(s)</span></p>
                                    <p class="font-semibold tabular-nums {{ bccomp($v['net'], '0', 2) < 0 ? 'text-red-700' : 'text-emerald-800' }}">{{ $money($v['net'], 'NPR') }}</p>
                                </div>
                                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-muted" role="img" aria-label="{{ $v['name'] }} net profit {{ $money($v['net'], 'NPR') }}"><div class="fv-grow-x h-2 rounded-full" style="--i: {{ $i }}; background: {{ bccomp($v['net'], '0', 2) < 0 ? 'var(--chart-5)' : 'var(--chart-2)' }}; width: {{ max(2, round(abs((float) $v['net']) / $maxVendor * 100)) }}%"></div></div>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-sm text-muted-foreground">No calculated orders yet.</li>
                        @endforelse
                    </ul>
                </section>

                <section aria-labelledby="profit-heading" class="fv-rise rounded-lg border border-border bg-card" style="--i: 4">
                    <header class="flex items-center justify-between gap-3 border-b border-border px-5 py-4">
                        <div>
                            <h3 id="profit-heading" class="font-semibold">Recent profit calculations</h3>
                            <p class="mt-1 text-xs text-muted-foreground">Latest version per paid order, in NPR.</p>
                        </div>
                        <a href="{{ route('admin.finance.index', ['tool' => 'settings']) }}" class="text-sm font-semibold text-primary">Profit rates</a>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                <tr><th class="px-5 py-3">Order</th><th class="px-5 py-3">Vendor</th><th class="px-5 py-3">Calculated</th><th class="px-5 py-3 text-right">Received</th><th class="px-5 py-3 text-right">Net profit</th></tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse($profit['recent'] as $row)
                                    <tr>
                                        <td class="px-5 py-3 font-medium"><a class="text-primary hover:underline" href="{{ route('admin.finance.orders.profit.show', $row->order_id) }}">{{ $row->order?->order_number ?? '#' . $row->order_id }}</a> <span class="text-xs font-normal text-muted-foreground">v{{ $row->version }}</span></td>
                                        <td class="px-5 py-3">{{ $row->vendor_name ?? '—' }}</td>
                                        <td class="px-5 py-3 text-muted-foreground">{{ $row->created_at?->format('M j, Y') }}</td>
                                        <td class="px-5 py-3 text-right tabular-nums">{{ $money($row->received_npr, 'NPR') }}</td>
                                        <td class="px-5 py-3 text-right font-semibold tabular-nums {{ (float) $row->net_profit_npr < 0 ? 'text-red-700' : 'text-emerald-800' }}">{{ $money($row->net_profit_npr, 'NPR') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-5 py-8 text-center text-muted-foreground">No profit calculations for paid orders yet. Open a paid order and use <em>Profit &amp; commission</em>.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            {{-- ============ PAYOUTS ============ --}}
            <div id="panel-payouts" role="tabpanel" aria-labelledby="tab-payouts" x-show="tab === 'payouts'" x-cloak class="space-y-6">
                <section aria-labelledby="payouts-heading" class="space-y-4">
                    <div class="fv-rise">
                        <h3 id="payouts-heading" class="font-semibold">Payouts by payee</h3>
                        <p class="mt-1 text-xs text-muted-foreground">NPR owed to the vendor, CSR and data entry for paid orders.</p>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-3">
                        @foreach($payoutBreakdown as $row)
                            <article class="fv-card fv-rise rounded-lg border border-border bg-card p-5" style="--i: {{ $loop->index + 1 }}">
                                <div class="flex items-center gap-4">
                                    <x-finance.ring :percent="$row['percent']" :color="$payeeColors[$row['label']] ?? 'var(--chart-2)'" :label="$row['label']" :size="96" />
                                    <div>
                                        <p class="text-lg font-semibold">{{ $row['label'] }}</p>
                                        <p class="text-xs text-muted-foreground">{{ $row['orders'] }} order(s) &middot; {{ $row['percent'] }}% paid</p>
                                    </div>
                                </div>
                                <dl class="mt-4 grid grid-cols-3 gap-2 text-sm">
                                    <div><dt class="text-xs text-muted-foreground">Owed</dt><dd class="break-words tabular-nums">{{ $money($row['owed'], 'NPR') }}</dd></div>
                                    <div><dt class="text-xs text-muted-foreground">Paid</dt><dd class="break-words tabular-nums">{{ $money($row['paid'], 'NPR') }}</dd></div>
                                    <div><dt class="text-xs text-muted-foreground">Balance</dt><dd class="break-words font-semibold tabular-nums {{ bccomp($row['balance'], '0', 2) > 0 ? 'text-amber-800' : 'text-emerald-800' }}">{{ $money($row['balance'], 'NPR') }}</dd></div>
                                </dl>
                            </article>
                        @endforeach
                    </div>
                </section>

                @if($user->hasPermission('manage_finance'))
                <section aria-labelledby="bulkpay-heading" class="fv-rise rounded-lg border border-border bg-card" style="--i: 3"
                    x-data="{
                        payee: @js(old('payee_type', 'vendor')),
                        rows: @js($outstandingPayables->map(fn ($p) => ['id' => (string) $p->id, 'type' => $p->payee_type, 'balance' => (float) $p->balance])->values()),
                        picked: @js(array_map('strval', (array) old('payable_ids', []))),
                        get visible() { return this.rows.filter(r => r.type === this.payee); },
                        get allPicked() { return this.visible.length > 0 && this.visible.every(r => this.picked.includes(r.id)); },
                        get total() { return this.visible.filter(r => this.picked.includes(r.id)).reduce((s, r) => s + r.balance, 0); },
                        get count() { return this.visible.filter(r => this.picked.includes(r.id)).length; },
                        toggleAll() { const ids = this.visible.map(r => r.id); this.picked = this.allPicked ? this.picked.filter(i => !ids.includes(i)) : [...new Set([...this.picked, ...ids])]; },
                        switchPayee(p) { this.payee = p; this.picked = []; }
                    }">
                    <header class="border-b border-border px-5 py-4">
                        <h3 id="bulkpay-heading" class="font-semibold">Pay outstanding balances</h3>
                        <p class="mt-1 text-xs text-muted-foreground">Pick a payee, tick the orders you have paid, and record one payment that settles each selected order&rsquo;s full balance. For a partial amount, use the order&rsquo;s profit page.</p>
                    </header>
                    <form method="POST" action="{{ route('admin.finance.payouts.bulk') }}" class="space-y-4 p-5">
                        @csrf
                        <input type="hidden" name="payee_type" :value="payee">
                        <div class="flex flex-wrap gap-2" role="group" aria-label="Payee">
                            @foreach(\App\Models\OrderPayable::TYPES as $type => $typeLabel)
                                <button type="button" @click="switchPayee('{{ $type }}')" :aria-pressed="payee === '{{ $type }}'" :class="payee === '{{ $type }}' ? 'bg-primary text-primary-foreground' : 'border border-border bg-card hover:bg-muted/40'" class="rounded-md px-4 py-2 text-sm font-semibold transition">{{ $typeLabel }}</button>
                            @endforeach
                        </div>

                        @error('payable_ids')<p class="text-sm text-red-700">{{ $message }}</p>@enderror

                        <div class="max-h-72 overflow-auto rounded-md border border-border">
                            <table class="w-full text-left text-sm">
                                <thead class="sticky top-0 bg-muted text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th class="w-10 px-4 py-3"><input type="checkbox" :checked="allPicked" @change="toggleAll()" aria-label="Select all orders for this payee"></th>
                                        <th class="px-4 py-3">Order</th><th class="px-4 py-3">Payee</th><th class="px-4 py-3 text-right">Balance (NPR)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @foreach($outstandingPayables as $p)
                                        <tr x-show="payee === '{{ $p->payee_type }}'" x-cloak>
                                            <td class="px-4 py-2.5"><input type="checkbox" name="payable_ids[]" value="{{ $p->id }}" x-model="picked" aria-label="Pay order {{ $p->order?->order_number }}"></td>
                                            <td class="px-4 py-2.5"><a class="text-primary hover:underline" href="{{ route('admin.finance.orders.profit.show', $p->order_id) }}">{{ $p->order?->order_number ?? '#' . $p->order_id }}</a></td>
                                            <td class="px-4 py-2.5 text-muted-foreground">{{ $p->payee_type === 'vendor' && $p->vendor ? $p->vendor->name : $p->label }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format((float) $p->balance, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr x-show="visible.length === 0" x-cloak><td colspan="4" class="px-4 py-8 text-center text-muted-foreground">Nothing outstanding for this payee.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <label class="block text-sm">Paid on
                                <input type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="mt-1 w-full rounded-md border border-border bg-background px-3 py-2">
                                @error('paid_on')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
                            </label>
                            <label class="block text-sm">Method
                                <input name="method" value="{{ old('method') }}" required maxlength="100" placeholder="Bank transfer" class="mt-1 w-full rounded-md border border-border bg-background px-3 py-2">
                                @error('method')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
                            </label>
                            <label class="block text-sm">Reference
                                <input name="reference" value="{{ old('reference') }}" maxlength="255" placeholder="Transaction ID (optional)" class="mt-1 w-full rounded-md border border-border bg-background px-3 py-2">
                            </label>
                            <label class="block text-sm">Notes
                                <input name="notes" value="{{ old('notes') }}" maxlength="2000" placeholder="Optional" class="mt-1 w-full rounded-md border border-border bg-background px-3 py-2">
                            </label>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-muted-foreground"><span class="font-semibold text-foreground" x-text="count"></span> order(s) selected &middot; NPR <span class="font-semibold tabular-nums text-foreground" x-text="total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></p>
                            <button :disabled="count === 0" class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-50" onclick="return confirm('Record these payments? Each selected order will be marked paid in full for this payee.')">Record payments</button>
                        </div>
                    </form>
                </section>
                @endif

                <section aria-labelledby="recentpay-heading" class="fv-rise rounded-lg border border-border bg-card" style="--i: 4">
                    <header class="border-b border-border px-5 py-4">
                        <h3 id="recentpay-heading" class="font-semibold">Recent payout payments</h3>
                        <p class="mt-1 text-xs text-muted-foreground">Latest 8 payments to vendors, CSR and data entry.</p>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                <tr><th class="px-5 py-3">Paid on</th><th class="px-5 py-3">Payee</th><th class="px-5 py-3">Order</th><th class="px-5 py-3">Method</th><th class="px-5 py-3">Recorded by</th><th class="px-5 py-3 text-right">Amount</th></tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse($dash['recentPayouts'] as $pay)
                                    <tr>
                                        <td class="px-5 py-3 text-muted-foreground">{{ $pay->paid_on->format('M j, Y') }}</td>
                                        <td class="px-5 py-3 font-medium">{{ $pay->payable?->label }}</td>
                                        <td class="px-5 py-3"><a class="text-primary hover:underline" href="{{ route('admin.finance.orders.profit.show', $pay->payable->order_id) }}">{{ $pay->payable->order?->order_number ?? '#' . $pay->payable->order_id }}</a></td>
                                        <td class="px-5 py-3">{{ $pay->method }}@if($pay->reference) <span class="text-xs text-muted-foreground">&middot; {{ $pay->reference }}</span>@endif</td>
                                        <td class="px-5 py-3 text-muted-foreground">{{ $pay->recordedBy?->name ?? 'System' }}</td>
                                        <td class="px-5 py-3 text-right font-semibold tabular-nums">{{ $money($pay->amount, 'NPR') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-5 py-8 text-center text-muted-foreground">No payout payments recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            {{-- ============ RECEIVABLES ============ --}}
            <div id="panel-receivables" role="tabpanel" aria-labelledby="tab-receivables" x-show="tab === 'receivables'" x-cloak class="space-y-6">
                <section aria-labelledby="aging-heading" class="fv-rise rounded-lg border border-border bg-card">
                    <header class="border-b border-border px-5 py-4">
                        <h3 id="aging-heading" class="font-semibold">Unpaid order balances by age</h3>
                        <p class="mt-1 text-xs text-muted-foreground">Unpaid or partly paid orders. Amounts are shown in the reporting currency and orders are not converted.</p>
                    </header>
                    <ul class="divide-y divide-border">
                        @foreach(['0_7' => '0–7 days', '8_30' => '8–30 days', '31_plus' => '31+ days'] as $bucket => $label)
                            @php
                                $balance = (float) data_get($outstandingAging, "{$bucket}.balance", 0);
                                $count = (int) data_get($outstandingAging, "{$bucket}.count", 0);
                            @endphp
                            <li class="px-5 py-4">
                                <div class="flex items-baseline justify-between gap-3">
                                    <p class="text-sm font-medium">{{ $label }}</p>
                                    <p class="text-lg font-semibold tabular-nums {{ $bucket === '31_plus' && $balance > 0 ? 'text-red-700' : '' }}">{{ $money($balance) }}</p>
                                </div>
                                <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-muted" role="img" aria-label="{{ $label }}: {{ $money($balance) }}"><div class="fv-grow-x h-2.5 rounded-full" style="--i: {{ $loop->index }}; background: {{ $bucket === '31_plus' ? 'var(--chart-5)' : ($bucket === '8_30' ? 'var(--chart-3)' : 'var(--chart-2)') }}; width: {{ $balance > 0 ? max(3, round($balance / $maxAging * 100)) : 0 }}%"></div></div>
                                <p class="mt-1 text-xs text-muted-foreground">{{ $count }} order(s)</p>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section aria-labelledby="unpaid-heading" class="fv-rise rounded-lg border border-border bg-card" style="--i: 1">
                    <header class="border-b border-border px-5 py-4">
                        <h3 id="unpaid-heading" class="font-semibold">Largest unpaid orders</h3>
                        <p class="mt-1 text-xs text-muted-foreground">Top 8 by outstanding balance, in each order's own currency.</p>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                <tr><th class="px-5 py-3">Order</th><th class="px-5 py-3">Buyer</th><th class="px-5 py-3">Age</th><th class="px-5 py-3 text-right">Total</th><th class="px-5 py-3 text-right">Paid</th><th class="px-5 py-3 text-right">Balance</th></tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse($unpaidOrders as $order)
                                    @php $age = (int) $order->created_at->diffInDays(now()); @endphp
                                    <tr>
                                        <td class="px-5 py-3 font-medium"><a class="text-primary hover:underline" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                                        <td class="px-5 py-3">{{ $order->client?->name ?? '—' }}</td>
                                        <td class="px-5 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $age > 30 ? 'bg-red-100 text-red-800' : ($age > 7 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">{{ $age }} d</span></td>
                                        <td class="px-5 py-3 text-right tabular-nums">{{ $money($order->grand_total, $order->currency) }}</td>
                                        <td class="px-5 py-3 text-right tabular-nums text-muted-foreground">{{ $money($order->net_paid, $order->currency) }}</td>
                                        <td class="px-5 py-3 text-right font-semibold tabular-nums">{{ $money($order->balance, $order->currency) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-5 py-8 text-center text-muted-foreground">Nothing outstanding. All orders are fully paid.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            {{-- ============ SALES ============ --}}
            <div id="panel-sales" role="tabpanel" aria-labelledby="tab-sales" x-show="tab === 'sales'" x-cloak class="space-y-6">
                <section aria-labelledby="sales-heading" class="fv-rise rounded-lg border border-border bg-card">
                    <header class="border-b border-border px-5 py-4">
                        <h3 id="sales-heading" class="font-semibold">Sales</h3>
                        <p class="mt-1 text-xs text-muted-foreground">Paid orders excluding cancelled and fully refunded ones.</p>
                    </header>
                    <dl class="grid gap-px border-b border-border bg-border sm:grid-cols-3">
                        <div class="bg-card p-5"><dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Paid sales</dt><dd class="mt-2 text-2xl font-bold tabular-nums">{{ number_format($salesDetails['count']) }}</dd></div>
                        <div class="bg-card p-5"><dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Gross sales</dt><dd class="mt-2 text-2xl font-bold tabular-nums">{{ $money($salesDetails['gross_sales']) }}</dd></div>
                        <div class="bg-card p-5"><dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Average sale</dt><dd class="mt-2 text-2xl font-bold tabular-nums">{{ $money($salesDetails['average_sale']) }}</dd></div>
                    </dl>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                <tr><th class="px-5 py-3">Order</th><th class="px-5 py-3">Buyer</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Total</th><th class="px-5 py-3"></th></tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse($salesDetails['recent_orders'] as $sale)
                                    <tr>
                                        <td class="px-5 py-3 font-medium"><a class="text-primary hover:underline" href="{{ route('admin.orders.show', $sale) }}">{{ $sale->order_number }}</a></td>
                                        <td class="px-5 py-3">{{ $sale->client?->name ?? $sale->name ?? '—' }}</td>
                                        <td class="px-5 py-3 text-muted-foreground">{{ $sale->created_at?->format('M j, Y') }}</td>
                                        <td class="px-5 py-3">{{ $sale->status_label }}</td>
                                        <td class="px-5 py-3 text-right font-medium tabular-nums">{{ $money($sale->grand_total, $sale->currency) }}</td>
                                        <td class="px-5 py-3 text-right"><a class="text-xs font-semibold text-primary hover:underline" href="{{ route('admin.finance.orders.profit.show', $sale) }}">Profit</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-5 py-8 text-center text-muted-foreground">No paid sales recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

    @endif
</div>
@endsection
