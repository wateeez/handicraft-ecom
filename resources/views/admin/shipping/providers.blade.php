@extends('admin.layout')

@section('header')
    <div>
        <h2 class="text-xl font-semibold text-foreground leading-tight">Provider Settings</h2>
        <p class="text-xs text-muted-foreground mt-0.5">Manage shipping carriers and providers</p>
    </div>
@endsection

@section('content')
<div class="p-6">
    @if(session('success'))
    <div class="bg-green-premium/20 border border-green-400 text-green-premium px-4 py-3 rounded mb-6">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        {{ session('error') }}
    </div>
    @endif

    <!-- Create New Provider Form -->
    <div class="bg-cream rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center mb-4">
            <svg class="w-6 h-6 text-green-premium mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <h2 class="text-xl font-semibold text-truffle-extra-dark">Add Shipping Provider</h2>
        </div>
        
        <form action="{{ route('admin.shipping.providers.store') }}" method="POST">
            @csrf
            <div class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Provider Name *</label>
                    <input type="text" name="name" placeholder="e.g., FedEx, UPS, DHL, USPS" 
                        class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                    <p class="text-xs text-truffle-extra-dark mt-1">Enter the shipping carrier or provider name</p>
                </div>
                <button type="submit" 
                    class="bg-green-premium text-white px-6 py-2.5 rounded-lg hover:bg-green-800 transition-colors font-medium flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Provider
                </button>
            </div>
        </form>
    </div>

    <!-- Existing Providers List -->
    <div class="bg-cream rounded-lg shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-blue-50 to-blue-100 px-6 py-4 border-b border-blue-200">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-truffle-extra-dark">Shipping Providers</h2>
                <span class="bg-blue-600 text-white text-sm px-3 py-1 rounded-full font-medium">
                    {{ $providers->count() }} {{ $providers->count() === 1 ? 'Provider' : 'Providers' }}
                </span>
            </div>
        </div>
        
        @if($providers->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
            @foreach($providers as $provider)
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-truffle-medium/30 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-2 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-truffle-extra-dark">{{ $provider->name }}</h3>
                    </div>
                </div>
                <div class="text-xs text-truffle-extra-dark bg-cream rounded px-3 py-2 mb-3">
                    <span class="font-medium">{{ $provider->rates->count() }}</span> shipping {{ $provider->rates->count() === 1 ? 'rate' : 'rates' }} configured
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.shipping.providers.show', $provider) }}" 
                        class="flex-1 text-center bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                        Manage Rates
                    </a>
                    <form action="{{ route('admin.shipping.providers.destroy', $provider) }}" method="POST" class="inline-block"
                        onsubmit="event.preventDefault(); openDeleteModal(this, 'Enter your password to delete this provider and all its rates.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                            class="bg-red-50 text-red-600 px-3 py-2 rounded-lg hover:bg-red-100 transition-colors text-sm font-medium">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-truffle-extra-dark/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
            </svg>
            <p class="mt-4 text-truffle-extra-dark">No shipping providers added yet</p>
            <p class="text-sm text-truffle-extra-dark/70 mt-1">Add providers to configure shipping rates</p>
        </div>
        @endif
    </div>
</div>
@endsection
