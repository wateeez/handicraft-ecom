<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionEntry;
use App\Models\Order;
use App\Models\OrderFinanceSnapshot;
use App\Models\OrderPayable;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Client;
use App\Models\FinanceSetting;
use App\Services\Finance\FinanceDashboardService;
use App\Services\FinanceInsightService;
use App\Services\FinanceReportService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class FinanceController extends Controller
{
    public function index(Request $request, FinanceInsightService $insights, FinanceReportService $reports, FinanceDashboardService $dashboard): View
    {
        $financeTablesReady = Schema::hasTable('payments')
            && Schema::hasTable('order_payables')
            && Schema::hasTable('commission_entries');

        return view('admin.finance.index', [
            'invoiceCount' => Invoice::count(),
            'confirmedReceipts' => $financeTablesReady
                ? Payment::whereIn('status', ['confirmed', 'reversed'])->sum('reporting_amount')
                : 0,
            'outstandingPayouts' => $financeTablesReady
                ? OrderPayable::where('status', '!=', 'paid')
                    ->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as total')
                    ->value('total')
                : 0,
            'outstandingCommissions' => $financeTablesReady
                ? CommissionEntry::whereIn('status', ['pending', 'earned', 'payable'])
                    ->selectRaw('COALESCE(SUM(adjusted_amount - paid_amount), 0) as total')
                    ->value('total')
                : 0,
            'financeTablesReady' => $financeTablesReady,
            'outstandingPayables' => $financeTablesReady
                ? OrderPayable::with(['order:id,order_number', 'vendor:id,name'])
                    ->whereColumn('paid_amount', '<', 'amount')
                    ->orderBy('payee_type')->orderBy('order_id')->limit(300)->get()
                : collect(),
            'rates' => $financeTablesReady ? app(\App\Services\Finance\FinanceRateRepository::class)->all() : [],
            'outstandingAging' => $financeTablesReady ? $insights->outstandingAging() : [],
            'receiptSummary' => $financeTablesReady
                ? $reports->receiptsSummary($request->query('from') ?: null, $request->query('to') ?: null)
                : ['receipts' => 0, 'refunds' => 0, 'reversals' => 0],
            'reportFrom' => $request->query('from'),
            'reportTo' => $request->query('to'),
            'recentReceipts' => $financeTablesReady
                ? Payment::with(['order:id,order_number', 'client:id,name'])->where('status', 'confirmed')->whereDoesntHave('reversals')->latest('id')->limit(15)->get()
                : collect(),
            'pendingPayments' => $financeTablesReady ? Payment::where('status', 'pending')->count() : 0,
            'pendingPaymentList' => $financeTablesReady
                ? Payment::with(['order:id,order_number,grand_total,currency', 'client:id,name', 'invoice:id,invoice_number', 'recordedBy:id,name'])
                    ->where('status', 'pending')->oldest('id')->limit(100)->get()
                : collect(),
            'profit' => $financeTablesReady ? $this->profitOverview() : null,
            'dash' => $financeTablesReady ? $dashboard->build($request->query('from') ?: null, $request->query('to') ?: null) : null,
            'unpaidOrders' => $financeTablesReady ? $insights->outstandingOrders() : collect(),
            'payoutBreakdown' => $financeTablesReady ? $this->payoutBreakdown() : [],
            'salesDetails' => $financeTablesReady ? $reports->salesDetails() : ['count' => 0, 'gross_sales' => 0, 'average_sale' => 0, 'recent_orders' => collect()],
            'financeSettings' => $financeTablesReady ? FinanceSetting::query()->firstOrCreate([]) : null,
        ]);
    }

    /**
     * Net profit across paid orders (latest calculation per order), plus the
     * paid orders still waiting for a calculation and the most recent ones.
     */
    private function profitOverview(): array
    {
        $latest = OrderFinanceSnapshot::query()
            ->whereIn('id', OrderFinanceSnapshot::selectRaw('MAX(id)')->groupBy('order_id'))
            ->whereHas('order', fn ($q) => $q->where('is_paid', true)->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED]));

        $awaiting = Order::query()
            ->where('type', Order::TYPE_ORDER)->where('is_paid', true)
            ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED])
            ->whereDoesntHave('financeSnapshots');

        return [
            'net_profit' => (string) ((clone $latest)->sum('net_profit_npr') ?: '0'),
            'received' => (string) ((clone $latest)->sum('received_npr') ?: '0'),
            'calculated_orders' => (clone $latest)->count(),
            'awaiting' => (clone $awaiting)->count(),
            'awaiting_orders' => (clone $awaiting)->oldest('id')->limit(10)->get(['id', 'order_number', 'created_at']),
            'recent' => (clone $latest)->with('order:id,order_number')->latest('id')->limit(5)->get(),
        ];
    }

    /**
     * Owed, paid and outstanding NPR per payee type, always listing all three.
     */
    private function payoutBreakdown(): array
    {
        $rows = OrderPayable::query()
            ->selectRaw('payee_type, COUNT(*) as orders, SUM(amount) as owed, SUM(paid_amount) as paid')
            ->groupBy('payee_type')->get()->keyBy('payee_type');

        return collect(OrderPayable::TYPES)->map(function (string $label, string $type) use ($rows) {
            $row = $rows->get($type);
            $owed = (string) ($row->owed ?? '0');
            $paid = (string) ($row->paid ?? '0');

            return [
                'label' => $label,
                'orders' => (int) ($row->orders ?? 0),
                'owed' => $owed,
                'paid' => $paid,
                'balance' => bcsub($owed, $paid, 2),
                'percent' => bccomp($owed, '0', 2) > 0 ? (int) min(100, round((float) bcdiv(bcmul($paid, '100', 4), $owed, 4))) : 0,
            ];
        })->all();
    }

    public function reportSummary(FinanceReportService $reports): JsonResponse
    {
        return response()->json($reports->receiptsSummary(
            request('from'),
            request('to'),
        ));
    }

    public function clientSummary(Client $client, FinanceInsightService $insights): JsonResponse
    {
        return response()->json($insights->clientSummary($client));
    }

    public function updateSettings(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin() || $request->user()?->hasPermission('manage_finance'), 403);
        $data = $request->validate([
            'reporting_currency' => ['required', 'string', 'size:3'],
            'default_commission_type' => ['required', 'in:percent,fixed'],
            'default_commission_value' => ['required', 'numeric', 'min:0'],
            'commission_base' => ['required', 'in:before_discount,after_discount_excluding_shipping,after_discount_including_shipping'],
            'commission_earned_trigger' => ['required', 'in:payment,delivery'],
            'loyalty_basis' => ['required', 'in:lifetime_spend,order_count'],
        ]);
        FinanceSetting::query()->firstOrCreate([])->update($data);

        return back()->with('success', 'Finance settings updated.');
    }
}
