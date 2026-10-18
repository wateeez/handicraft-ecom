@extends('admin.layout')

@section('header')
    <div>
        <h2 class="text-xl font-semibold text-foreground leading-tight">Vendors</h2>
        <p class="mt-1 text-sm text-muted-foreground">Cost price and commission terms used in order profit calculations.</p>
    </div>
@endsection

@section('content')
<div class="space-y-6 p-4 sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        @if(auth()->user()->hasPermission('view_finance'))
        <a href="{{ route('admin.finance.index') }}" class="text-sm text-muted-foreground hover:text-foreground">&larr; Finance</a>
        @else
        <span></span>
        @endif
        <a href="{{ route('admin.finance.vendors.create') }}" class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Add vendor</a>
    </div>

    <div class="overflow-x-auto rounded-md border border-border bg-card">
        <table class="min-w-full text-sm">
            <thead class="border-b border-border text-left text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3 text-right">Cost price %</th>
                    <th class="px-4 py-3 text-right">Commission %</th>
                    <th class="px-4 py-3 text-right">Orders</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($vendors as $vendor)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $vendor->name }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ rtrim(rtrim(number_format((float) $vendor->cost_price_pct, 4), '0'), '.') }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ rtrim(rtrim(number_format((float) $vendor->commission_pct, 4), '0'), '.') }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $vendor->orders_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $vendor->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">{{ $vendor->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.finance.vendors.edit', $vendor) }}" class="text-sm font-semibold text-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.finance.vendors.toggle', $vendor) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-sm font-semibold text-muted-foreground hover:text-foreground">{{ $vendor->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.finance.vendors.destroy', $vendor) }}" onsubmit="return confirm('Delete this vendor? Past orders keep their recorded figures.')">
                                    @csrf @method('DELETE')
                                    <button class="text-sm font-semibold text-red-700">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-muted-foreground">No vendors yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
