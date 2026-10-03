<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\FinanceService;
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
        ]);

        $financeService->recordPayment($order, $data, $request->user());

        return back()->with('success', 'Payment recorded and awaiting confirmation.');
    }

    public function confirm(Request $request, Payment $payment, FinanceService $financeService): RedirectResponse
    {
        $this->authorize('update', $payment);
        $financeService->confirmPayment($payment, $request->user());

        return back()->with('success', 'Payment confirmed.');
    }

    public function reverse(Request $request, Payment $payment, FinanceService $financeService): RedirectResponse
    {
        $this->authorize('update', $payment);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5']]);
        $financeService->reversePayment($payment, $request->user(), $data['reason']);

        return back()->with('success', 'Payment reversed through an immutable correction entry.');
    }

    public function confirmRefund(Request $request, Refund $refund, FinanceService $financeService): RedirectResponse
    {
        $financeService->confirmRefund($refund, $request->user());

        return back()->with('success', 'Refund confirmed.');
    }
}
