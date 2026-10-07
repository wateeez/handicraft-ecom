@extends('admin.layout')

@section('header')
    <div>
        <h2 class="text-xl font-semibold text-foreground leading-tight">Client Management</h2>
        <p class="text-xs text-muted-foreground mt-0.5">Manage buyers, wholesale clients, and contact details</p>
    </div>
@endsection

@section('content')
    <div class="space-y-4">
        <div class="flex justify-end">
            <a href="{{ route('admin.clients.create') }}"
                class="w-full sm:w-auto px-4 py-2 text-center bg-green-premium text-white rounded-lg text-sm font-medium hover:bg-green-800 transition-colors">+ New Client</a>
        </div>
        <form method="GET" action="{{ route('admin.clients.index') }}"
            class="bg-cream p-4 rounded-xl border border-truffle-medium/30 flex flex-col sm:flex-row gap-4 sm:items-end">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-truffle-extra-dark mb-1">Search Clients</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Name, Email, Phone, Buyer ID..."
                    class="w-full border-truffle-medium/30 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>
            <button type="submit"
                class="w-full sm:w-auto px-6 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gray-800 hover:bg-gray-900">Search</button>
            @if(request('search'))
                <a href="{{ route('admin.clients.index') }}"
                    class="w-full sm:w-auto px-4 py-2 text-center text-sm font-medium text-truffle-extra-dark bg-[#F5F2EA] rounded-lg hover:bg-[#E8E2D2]">Clear</a>
            @endif
        </form>

        <div class="bg-cream rounded-xl shadow-sm border border-truffle-medium/30 overflow-x-auto">
            <table class="min-w-[700px] text-left text-sm">
                <thead>
                    <tr class="bg-[#F5F2EA] border-b">
                        <th class="px-6 py-3 font-semibold text-truffle-extra-dark">Client / Company</th>
                        <th class="px-6 py-3 font-semibold text-truffle-extra-dark">ID</th>
                        <th class="px-6 py-3 font-semibold text-truffle-extra-dark">Contact</th>
                        <th class="px-6 py-3 font-semibold text-truffle-extra-dark text-center">Orders</th>
                        <th class="px-6 py-3 font-semibold text-truffle-extra-dark text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($clients as $c)
                        <tr class="hover:bg-[#F5F2EA] transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-truffle-extra-dark">{{ $c->name }}</div>
                                <div class="text-xs text-truffle-extra-dark">{{ $c->company_name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-truffle-extra-dark">{{ $c->buyer_id }}</td>
                            <td class="px-6 py-4">
                                <div class="text-truffle-extra-dark">{{ $c->email ?? '-' }}</div>
                                <div class="text-xs text-truffle-extra-dark">{{ $c->phone ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-indigo-100 bg-indigo-600 rounded-full">{{ $c->orders_count }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.clients.show', $c) }}"
                                    class="text-green-premium hover:text-green-800 font-medium px-2 py-1 bg-green-premium/10 rounded">View /
                                    Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-truffle-extra-dark">No clients found matching your
                                criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $clients->withQueryString()->links() }}
    </div>
@endsection
