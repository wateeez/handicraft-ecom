<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DistributorPayoutService;
use App\Models\DistributorPayoutBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DistributorPayoutController extends Controller
{
    public function store(Request $request, DistributorPayoutService $payoutService): RedirectResponse
    {
        $this->authorize('create', DistributorPayoutBatch::class);
        $data = $request->validate([
            'distributor_id' => ['required', 'integer', 'exists:distributors,id'],
            'paid_on' => ['required', 'date'],
            'currency' => ['required', 'string', 'size:3'],
            'method' => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.payable_id' => ['required', 'integer', 'exists:distributor_payables,id'],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $payoutService->settle($data, $request->user());

        return back()->with('success', 'Distributor payout recorded.');
    }
}
