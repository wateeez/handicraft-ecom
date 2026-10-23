@extends('admin.layout')

@section('header')
    <h2 class="text-xl font-semibold text-foreground leading-tight">{{ $vendor->exists ? 'Edit vendor' : 'Add vendor' }}</h2>
@endsection

@section('content')
<div class="max-w-2xl space-y-6 p-4 sm:p-6">
    <form method="POST" action="{{ $vendor->exists ? route('admin.finance.vendors.update', $vendor) : route('admin.finance.vendors.store') }}" class="space-y-4 rounded-md border border-border bg-card p-5">
        @csrf
        @if($vendor->exists) @method('PUT') @endif

        <label class="block text-sm">Name
            <input name="name" value="{{ old('name', $vendor->name) }}" required maxlength="255" class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
            @error('name')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm">Email
                <input type="email" name="email" value="{{ old('email', $vendor->email) }}" maxlength="255" class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                @error('email')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
            </label>
            <label class="block text-sm">Phone
                <input name="phone" value="{{ old('phone', $vendor->phone) }}" maxlength="50" class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                @error('phone')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
            </label>
        </div>
        <label class="block text-sm">Address
            <textarea name="address" rows="2" maxlength="1000" class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">{{ old('address', $vendor->address) }}</textarea>
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm">Cost price (% of MRP)
                <input type="number" step="0.01" min="0" max="100" name="cost_price_pct" value="{{ old('cost_price_pct', $vendor->cost_price_pct !== null ? (float) $vendor->cost_price_pct : '') }}" required class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                @error('cost_price_pct')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
            </label>
            <label class="block text-sm">Vendor commission (% of cost price)
                <input type="number" step="0.01" min="0" max="100" name="commission_pct" value="{{ old('commission_pct', $vendor->commission_pct !== null ? (float) $vendor->commission_pct : '') }}" required class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">
                @error('commission_pct')<span class="mt-1 block text-xs text-red-700">{{ $message }}</span>@enderror
            </label>
        </div>
        <label class="block text-sm">Notes
            <textarea name="notes" rows="3" maxlength="2000" class="mt-1 w-full rounded-md border border-border bg-card px-3 py-2">{{ old('notes', $vendor->notes) }}</textarea>
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $vendor->is_active))> Active
        </label>
        <p class="text-xs text-muted-foreground">Changing these terms only affects future calculations. Past order snapshots keep the rates they used.</p>
        <div class="flex gap-3">
            <button class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Save</button>
            <a href="{{ route('admin.finance.vendors.index') }}" class="rounded-md border border-border px-4 py-2 text-sm">Cancel</a>
        </div>
    </form>
</div>
@endsection
