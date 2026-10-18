<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordBulkPayoutRequest;
use App\Models\OrderPayable;
use App\Services\Finance\OrderPayoutService;
use Illuminate\Http\RedirectResponse;

class BulkPayoutController extends Controller
{
    public function store(RecordBulkPayoutRequest $request, OrderPayoutService $payouts): RedirectResponse
    {
        $data = $request->validated();
        $count = $payouts->recordBulkPayment(
            $data['payee_type'],
            $data['payable_ids'],
            collect($data)->only(['paid_on', 'method', 'reference', 'notes'])->all(),
            $request->user(),
        );

        return redirect(route('admin.finance.index') . '#payouts')
            ->with('success', OrderPayable::TYPES[$data['payee_type']] . " payment recorded for {$count} order(s).");
    }
}
