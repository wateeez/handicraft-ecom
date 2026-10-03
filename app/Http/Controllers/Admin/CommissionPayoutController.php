<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CommissionService;
use App\Models\CommissionPayoutBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommissionPayoutController extends Controller
{
    public function store(Request $request, CommissionService $commissionService): RedirectResponse
    {
        $this->authorize('create', CommissionPayoutBatch::class);
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:commission_recipients,id'],
            'paid_on' => ['required', 'date'],
            'currency' => ['required', 'string', 'size:3'],
            'method' => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.commission_entry_id' => ['required', 'integer', 'exists:commission_entries,id'],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $commissionService->settle($data, $request->user());

        return back()->with('success', 'Commission payout recorded.');
    }
}
