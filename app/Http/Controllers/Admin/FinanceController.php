<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionEntry;
use App\Models\DistributorPayable;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Client;
use App\Models\FinanceSetting;
use App\Services\FinanceInsightService;
use App\Services\FinanceReportService;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class FinanceController extends Controller
{
    public function index(FinanceInsightService $insights, FinanceReportService $reports): View
    {
        $financeTablesReady = Schema::hasTable('payments')
            && Schema::hasTable('distributor_payables')
            && Schema::hasTable('commission_entries');

        return view('admin.finance.index', [
            'invoiceCount' => Invoice::count(),
            'confirmedReceipts' => $financeTablesReady
                ? Payment::where('status', 'confirmed')->sum('reporting_amount')
                : 0,
            'outstandingDistributorPayables' => $financeTablesReady
                ? DistributorPayable::whereIn('status', ['not_due', 'due'])
                    ->selectRaw('COALESCE(SUM(adjusted_amount - paid_amount), 0) as total')
                    ->value('total')
                : 0,
            'outstandingCommissions' => $financeTablesReady
                ? CommissionEntry::whereIn('status', ['pending', 'earned', 'payable'])
                    ->selectRaw('COALESCE(SUM(adjusted_amount - paid_amount), 0) as total')
                    ->value('total')
                : 0,
            'financeTablesReady' => $financeTablesReady,
            'outstandingAging' => $financeTablesReady ? $insights->outstandingAging() : [],
            'receiptSummary' => $financeTablesReady ? $reports->receiptsSummary() : ['receipts' => 0, 'refunds' => 0, 'reversals' => 0],
            'salesDetails' => $financeTablesReady ? $reports->salesDetails() : ['count' => 0, 'gross_sales' => 0, 'average_sale' => 0, 'recent_orders' => collect()],
            'financeSettings' => $financeTablesReady ? FinanceSetting::query()->firstOrCreate([]) : null,
        ]);
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
