@extends('admin.layout')

@section('header')
    <div>
        <h2 class="text-xl font-semibold text-foreground leading-tight">Finance</h2>
        <p class="mt-1 text-sm text-muted-foreground">Receipts, invoices, commissions, and distributor balances.</p>
    </div>
@endsection

@section('content')
<div class="space-y-6 p-4 sm:p-6">
    @unless($financeTablesReady)
        <div class="flex items-start gap-3 rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950" role="status">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            <div>
                <p class="font-semibold">Finance tables are not ready</p>
                <p class="mt-1">Apply the finance migration to enable receipt, distributor, and commission totals.</p>
            </div>
        </div>
    @endunless

    <section aria-labelledby="finance-summary-heading">
        <div class="mb-3 flex items-end justify-between gap-3">
            <div>
                <h3 id="finance-summary-heading" class="text-base font-semibold">Financial summary</h3>
                <p class="mt-1 text-xs text-muted-foreground">Receipt and balance totals are shown in the reporting currency.</p>
            </div>
        </div>
        <div class="grid gap-px overflow-hidden rounded-md border border-border bg-border sm:grid-cols-2 xl:grid-cols-4">
            <article class="min-w-0 bg-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Invoices</p>
                <p class="mt-3 text-2xl font-semibold tabular-nums">{{ number_format($invoiceCount) }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Issued and draft records</p>
            </article>
            <article class="min-w-0 bg-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Confirmed receipts</p>
                <p class="mt-3 break-words text-2xl font-semibold tabular-nums">{{ number_format($confirmedReceipts, 2) }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Confirmed payments received</p>
            </article>
            <article class="min-w-0 bg-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Distributor outstanding</p>
                <p class="mt-3 break-words text-2xl font-semibold tabular-nums">{{ number_format($outstandingDistributorPayables, 2) }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Open payable balances</p>
            </article>
            <article class="min-w-0 bg-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Commission outstanding</p>
                <p class="mt-3 break-words text-2xl font-semibold tabular-nums">{{ number_format($outstandingCommissions, 2) }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Pending and payable commissions</p>
            </article>
        </div>
    </section>

    @if($financeTablesReady)
        <section class="rounded-md border border-border bg-card" aria-labelledby="sales-details-heading">
            <div class="border-b border-border px-5 py-4">
                <h3 id="sales-details-heading" class="font-semibold">Sales details</h3>
                <p class="mt-1 text-sm text-muted-foreground">Paid sales excluding cancelled and fully refunded orders.</p>
            </div>
            <div class="grid gap-px border-b border-border bg-border sm:grid-cols-3">
                <article class="bg-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Paid sales</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($salesDetails['count']) }}</p>
                </article>
                <article class="bg-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Gross sales</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($salesDetails['gross_sales'], 2) }}</p>
                </article>
                <article class="bg-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Average sale</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($salesDetails['average_sale'], 2) }}</p>
                </article>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                        <tr><th class="px-5 py-3">Order</th><th class="px-5 py-3">Buyer</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Total</th></tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($salesDetails['recent_orders'] as $sale)
                            <tr>
                                <td class="px-5 py-3 font-medium"><a class="hover:underline" href="{{ route('admin.orders.show', $sale) }}">{{ $sale->order_number }}</a></td>
                                <td class="px-5 py-3">{{ $sale->client?->name ?? $sale->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ $sale->created_at?->format('M j, Y') }}</td>
                                <td class="px-5 py-3">{{ $sale->status_label }}</td>
                                <td class="px-5 py-3 text-right font-medium tabular-nums">{{ number_format($sale->grand_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-muted-foreground">No paid sales recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if($financeTablesReady && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_finance')))
        <details class="rounded-md border border-border bg-card">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 text-sm font-semibold">
                <span>Finance settings</span>
                <svg class="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7zm7.94-3.5a7.95 7.95 0 00-.13-1.43l2.02-1.57-2-3.46-2.4.97a7.97 7.97 0 00-2.47-1.43L14.6 2h-4l-.36 2.98a7.97 7.97 0 00-2.47 1.43l-2.4-.97-2 3.46 2.02 1.57A7.95 7.95 0 005.26 12c0 .49.04.97.13 1.43l-2.02 1.57 2 3.46 2.4-.97a7.97 7.97 0 002.47 1.43L10.6 22h4l.36-2.98a7.97 7.97 0 002.47-1.43l2.4.97 2-3.46-2.02-1.57c.09-.46.13-.94.13-1.43z" />
                </svg>
            </summary>
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
        </details>
    @endif

    @if($financeTablesReady)
        <section aria-labelledby="finance-aging-heading">
            <h3 id="finance-aging-heading" class="mb-3 text-base font-semibold">Outstanding balance aging</h3>
            <div class="grid gap-px overflow-hidden rounded-md border border-border bg-border sm:grid-cols-3">
                @foreach(['0_7' => '0–7 days', '8_30' => '8–30 days', '31_plus' => '31+ days'] as $bucket => $label)
                    <article class="bg-card p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ $label }}</p>
                        <p class="mt-3 text-2xl font-semibold tabular-nums">{{ number_format(data_get($outstandingAging, "{$bucket}.balance", 0), 2) }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">{{ data_get($outstandingAging, "{$bucket}.count", 0) }} unpaid or partially paid order(s)</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="rounded-md border border-border bg-card p-5" aria-labelledby="finance-chart-heading">
            <h3 id="finance-chart-heading" class="font-semibold">Finance activity</h3>
            <p class="mt-1 text-sm text-muted-foreground">Confirmed receipts and financial corrections in the reporting currency.</p>
            <div class="mt-3 flex gap-2">
                <input id="reportFrom" type="date" class="rounded-md border border-border bg-background px-3 py-1.5 text-sm">
                <input id="reportTo" type="date" class="rounded-md border border-border bg-background px-3 py-1.5 text-sm">
                <button id="refreshFinanceChart" type="button" class="rounded-md border border-border px-3 py-1.5 text-sm font-semibold">Refresh</button>
            </div>
            <div class="mt-5 h-72"><canvas id="financeActivityChart"></canvas></div>
        </section>
    @endif

    @if($financeTablesReady)
        <section class="rounded-md border border-border bg-card p-5" x-data="{ clientId: '', summary: null, loading: false }">
            <h3 class="font-semibold">Client finance lookup</h3>
            <p class="mt-1 text-sm text-muted-foreground">Load derived purchase metrics from confirmed payments and refunds.</p>
            <div class="mt-4 flex max-w-lg gap-2">
                <input x-model="clientId" type="number" min="1" placeholder="Client ID" class="w-full rounded-md border border-border bg-background px-3 py-2">
                <button @click="loading = true; fetch(`/admin/finance/clients/${clientId}/summary`, {headers:{Accept:'application/json'}}).then(r => r.json()).then(data => summary = data).finally(() => loading = false)" class="rounded-md border border-border px-4 py-2 text-sm font-semibold">Load</button>
            </div>
            <template x-if="summary"><div class="mt-4 grid gap-3 sm:grid-cols-3 text-sm"><div><span class="text-muted-foreground">Lifetime spend</span><p class="font-semibold" x-text="summary.lifetime_spend"></p></div><div><span class="text-muted-foreground">Paid orders</span><p class="font-semibold" x-text="summary.total_orders"></p></div><div><span class="text-muted-foreground">Average order</span><p class="font-semibold" x-text="summary.average_order_value"></p></div></div></template>
        </section>
    @endif

    @if($financeTablesReady && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('record_payments')))
        <section class="rounded-md border border-border bg-card p-5" x-data="{ orderId: '' }">
            <h3 class="font-semibold">Record payment</h3>
            <p class="mt-1 text-sm text-muted-foreground">Creates a pending payment; a separate authorized user confirms it.</p>
            <form class="mt-4 grid gap-3 md:grid-cols-3" :action="`/admin/finance/orders/${orderId}/payments`" method="POST">
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
        </section>
    @endif

    <section class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(16rem,0.8fr)]">
        <div class="rounded-md border border-border bg-card">
            <div class="border-b border-border px-5 py-4">
                <h3 class="font-semibold">Payment workflow</h3>
                <p class="mt-1 text-sm text-muted-foreground">Record receipts against an order, then have an authorized team member confirm them.</p>
            </div>
            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-emerald-50 text-emerald-800" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2 0-3.5.8-3.5 2s1.5 2 3.5 2 3.5.8 3.5 2-1.5 2-3.5 2m0-8V6m0 12v-2m8-4a8 8 0 11-16 0 8 8 0 0116 0z" /></svg>
                    </span>
                    <div>
                        <p class="text-sm font-medium">Start from the order record</p>
                        <p class="mt-1 text-sm text-muted-foreground">Open an order to review its invoices and payment history or record a new receipt.</p>
                    </div>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-md border border-border px-4 py-2 text-sm font-semibold transition-colors hover:bg-muted">
                    Browse orders
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7l7 7-7 7" /></svg>
                </a>
            </div>
        </div>

        <aside class="rounded-md border border-border bg-muted/40 p-5">
            <h3 class="font-semibold">Available here</h3>
            <ul class="mt-3 space-y-2 text-sm text-muted-foreground">
                <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-700"></span>Receipt recording and confirmation</li>
                <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-700"></span>Payment reversals with an audit reason</li>
                <li class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-700"></span>Invoice and order-level payment history</li>
            </ul>
            <p class="mt-4 border-t border-border pt-3 text-xs leading-relaxed text-muted-foreground">Commission and distributor payout totals are summarized above; their management screens are not available yet.</p>
        </aside>
    </div>
</div>
@endsection

@section('scripts')
@if($financeTablesReady)
<script>
document.addEventListener('DOMContentLoaded', async () => {
    let chart;
    const loadChart = async () => {
    const params = new URLSearchParams({ from: document.getElementById('reportFrom').value, to: document.getElementById('reportTo').value });
    const response = await fetch(`{{ route('admin.finance.report-summary') }}?${params}`, { headers: { Accept: 'application/json' } });
    if (!response.ok || !window.Chart) return;
    const data = await response.json();
    chart?.destroy();
    chart = new window.Chart(document.getElementById('financeActivityChart'), {
        type: 'bar',
        data: { labels: ['Receipts', 'Refunds', 'Reversals'], datasets: [{ label: 'Amount', data: [data.receipts, data.refunds, data.reversals], backgroundColor: ['#15803d', '#b91c1c', '#c2410c'] }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
    });
    };
    document.getElementById('refreshFinanceChart').addEventListener('click', loadChart);
    loadChart();
});
</script>
@endif
@endsection
