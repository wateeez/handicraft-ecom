<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Contracts\ForexRateProvider;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinancePaymentController extends Controller
{
    public function store(Request $request, Order $order, FinanceService $financeService): RedirectResponse
    {
        $this->authorize('create', Payment::class);
        $data = $request->validate([
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'reporting_amount' => ['required', 'numeric', 'gt:0'],
            'reporting_exchange_rate' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            'received_on' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'confirm_overpayment' => ['nullable', 'boolean'],
        ]);

        // Guard against recording the same money twice (e.g. after "Mark as paid").
        if (strtoupper($data['currency'] ?? $order->currency) === strtoupper((string) $order->currency) && !$request->boolean('confirm_overpayment')) {
            $received = (float) $order->payments()->whereIn('status', ['pending', 'confirmed', 'reversed'])->sum('amount');
            if ($received + (float) $data['amount'] > (float) $order->grand_total + 0.005) {
                return back()->withInput()->withErrors([
                    'amount' => sprintf(
                        'This would bring payments to %s %s, above the order total of %s. Tick "I understand" to record it anyway.',
                        $order->currency,
                        number_format($received + (float) $data['amount'], 2),
                        number_format((float) $order->grand_total, 2)
                    ),
                    'confirm_overpayment' => 'required',
                ]);
            }
        }
        unset($data['confirm_overpayment']);

        $financeService->recordPayment($order, $data, $request->user());

        return $this->returnTo($request)->with('success', 'Payment recorded and awaiting confirmation.');
    }

    public function forexRate(ForexRateProvider $forex): JsonResponse
    {
        $rate = $forex->refresh();

        return $rate === null
            ? response()->json(['message' => 'Could not fetch a rate right now.'], 503)
            : response()->json(['rate' => $rate]);
    }

    public function confirm(Request $request, Payment $payment, FinanceService $financeService): RedirectResponse
    {
        $this->authorize('update', $payment);
        $financeService->confirmPayment($payment, $request->user());

        return $this->returnTo($request)->with('success', 'Payment confirmed.');
    }

    public function reverse(Request $request, Payment $payment, FinanceService $financeService): RedirectResponse
    {
        $this->authorize('reverse', $payment);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5']]);
        $financeService->reversePayment($payment, $request->user(), $data['reason']);

        return $this->returnTo($request)->with('success', 'Payment reversed through an immutable correction entry.');
    }

    public function confirmRefund(Request $request, Refund $refund, FinanceService $financeService): RedirectResponse
    {
        $financeService->confirmRefund($refund, $request->user());

        return back()->with('success', 'Refund confirmed.');
    }

    /**
     * Forms on the Finance page send return_to so the user lands back on the Receipts tab.
     * Only that one known URL is honoured, anything else falls back to the previous page.
     */
    private function returnTo(Request $request): RedirectResponse
    {
        $target = route('admin.finance.index') . '#receipts';

        return $request->input('return_to') === $target ? redirect($target) : back();
    }
}
