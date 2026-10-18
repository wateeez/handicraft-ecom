<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VendorRequest;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(): View
    {
        return view('admin.finance.vendors.index', [
            'vendors' => Vendor::withCount('orders')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.finance.vendors.form', ['vendor' => new Vendor(['is_active' => true])]);
    }

    public function store(VendorRequest $request): RedirectResponse
    {
        Vendor::create($this->payload($request));

        return redirect()->route('admin.finance.vendors.index')->with('success', 'Vendor created.');
    }

    public function edit(Vendor $vendor): View
    {
        return view('admin.finance.vendors.form', compact('vendor'));
    }

    public function update(VendorRequest $request, Vendor $vendor): RedirectResponse
    {
        // Past orders are unaffected: their snapshots store the rates used.
        $vendor->update($this->payload($request));

        return redirect()->route('admin.finance.vendors.index')->with('success', 'Vendor updated.');
    }

    public function toggle(Vendor $vendor): RedirectResponse
    {
        $vendor->update(['is_active' => !$vendor->is_active]);

        return back()->with('success', $vendor->is_active ? 'Vendor activated.' : 'Vendor deactivated.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $vendor->delete();

        return redirect()->route('admin.finance.vendors.index')->with('success', 'Vendor deleted. Past orders keep their recorded figures.');
    }

    private function payload(VendorRequest $request): array
    {
        return $request->safe()->except('is_active') + ['is_active' => $request->boolean('is_active')];
    }
}
