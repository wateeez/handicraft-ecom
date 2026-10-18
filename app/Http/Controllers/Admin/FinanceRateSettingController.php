<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FinanceRateSettingsRequest;
use App\Services\Finance\FinanceRateRepository;
use Illuminate\Http\RedirectResponse;

class FinanceRateSettingController extends Controller
{
    public function edit(): RedirectResponse
    {
        return redirect()->route('admin.finance.index', ['tool' => 'settings']);
    }

    public function update(FinanceRateSettingsRequest $request, FinanceRateRepository $rates): RedirectResponse
    {
        $rates->setMany($request->safe()->except('include_shipping_in_commission') + [
            'include_shipping_in_commission' => $request->boolean('include_shipping_in_commission') ? '1' : '0',
        ]);

        return redirect()->route('admin.finance.index', ['tool' => 'settings'])->with('success', 'Rates saved. Existing snapshots are unchanged; recalculate an order to apply them.');
    }
}
