<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordPayoutPaymentRequest;
use App\Models\Order;
use App\Models\OrderPayable;
use App\Services\Finance\OrderPayoutService;
use Illuminate\Http\RedirectResponse;

class OrderPayoutController extends Controller
{
    public function store(RecordPayoutPaymentRequest $request, Order $order, OrderPayoutService $payouts): RedirectResponse
    {
        $payouts->recordPayment($order, $request->validated(), $request->user());

        return redirect()
            ->route('admin.finance.orders.profit.show', $order)
            ->with('success', OrderPayable::TYPES[$request->validated('payee_type')] . ' payment recorded.');
    }
}
