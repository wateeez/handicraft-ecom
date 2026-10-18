<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\ForexRateProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CalculateOrderFinanceRequest;
use App\Models\Order;
use App\Models\Vendor;
use App\Services\Finance\FinanceRateRepository;
use App\Services\Finance\OrderFinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderProfitController extends Controller
{
    public function show(Request $request, Order $order, OrderFinanceService $service, FinanceRateRepository $rates): View
    {
        $snapshots = $order->financeSnapshots()->with(['calculatedBy', 'invoice'])->get();
        $latest = $snapshots->first();
        $shown = $request->filled('version')
            ? $snapshots->firstWhere('version', (int) $request->query('version'))
            : $latest;

        return view('admin.finance.order-profit', [
            'order' => $order,
            'snapshots' => $snapshots,
            'snapshot' => $shown,
            'latest' => $latest,
            'defaults' => $service->formDefaults($order, $latest),
            'vendors' => Vendor::active()->orderBy('name')->get(),
            'rates' => $rates->all(),
            'payables' => $order->payables()->with(['payments.recordedBy', 'vendor'])->get()->keyBy('payee_type'),
            'canManage' => $request->user()->hasPermission('manage_finance'),
        ]);
    }

    public function refreshForex(ForexRateProvider $forex): JsonResponse
    {
        $rate = $forex->refresh();

        return $rate === null
            ? response()->json(['message' => 'Could not fetch a rate right now.'], 503)
            : response()->json(['rate' => $rate]);
    }

    public function store(CalculateOrderFinanceRequest $request, Order $order, OrderFinanceService $service): RedirectResponse
    {
        $snapshot = $service->calculateAndStore($order, $request->validated(), $request->user());

        return redirect()
            ->route('admin.finance.orders.profit.show', $order)
            ->with('success', "Profit calculated (version {$snapshot->version}).");
    }
}
