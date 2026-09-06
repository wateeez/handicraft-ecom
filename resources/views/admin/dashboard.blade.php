@extends('admin.layout')

@section('header')
    <div class="flex flex-col">
        <h2 class="font-serif font-bold text-2xl text-foreground tracking-tight">
            Executive Overview
        </h2>
        <p class="text-xs text-muted-foreground mt-0.5">Welcome back, here is what is happening in your craft boutique today.</p>
    </div>
@endsection

@php
    use Illuminate\Support\Facades\Cache;
@endphp

@section('content')
    <!-- Top Stat Cards (Artisanal Earthy Palette) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Products Stat -->
        <a href="{{ route('admin.products.index') }}"
            class="bg-card rounded-2xl p-6 border border-border card-lift relative overflow-hidden group shadow-xs block">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground font-sans">Products</span>
                    <h3 class="text-3xl font-bold font-serif text-foreground mt-2 number-pop">{{ $totalProducts ?? 0 }}</h3>
                    <p class="text-xs text-muted-foreground mt-2 flex items-center gap-1">
                        <span class="inline-block w-2 h-2 rounded-full bg-chart-1"></span>
                        Active in catalog
                    </p>
                </div>
                <div class="p-3.5 rounded-2xl bg-chart-1/10 text-chart-1 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-chart-1/30 group-hover:bg-chart-1 transition-colors"></div>
        </a>

        <!-- Categories Stat -->
        <a href="{{ route('admin.categories.index') }}"
            class="bg-card rounded-2xl p-6 border border-border card-lift relative overflow-hidden group shadow-xs block">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground font-sans">Categories</span>
                    <h3 class="text-3xl font-bold font-serif text-foreground mt-2 number-pop">{{ $totalCategories ?? 0 }}</h3>
                    <p class="text-xs text-muted-foreground mt-2 flex items-center gap-1">
                        <span class="inline-block w-2 h-2 rounded-full bg-chart-2"></span>
                        Curated collections
                    </p>
                </div>
                <div class="p-3.5 rounded-2xl bg-chart-2/10 text-chart-2 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-chart-2/30 group-hover:bg-chart-2 transition-colors"></div>
        </a>

        <!-- Inquiries Stat -->
        <a href="{{ route('admin.orders.index', ['type' => 'inquiry']) }}"
            class="bg-card rounded-2xl p-6 border border-border card-lift relative overflow-hidden group shadow-xs block">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground font-sans">Inquiries</span>
                    <h3 class="text-3xl font-bold font-serif text-foreground mt-2 number-pop">{{ $totalInquiries ?? 0 }}</h3>
                    <p class="text-xs text-muted-foreground mt-2 flex items-center gap-1">
                        <span class="inline-block w-2 h-2 rounded-full bg-chart-3"></span>
                        Pending review
                    </p>
                </div>
                <div class="p-3.5 rounded-2xl bg-chart-3/15 text-chart-3 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-chart-3/30 group-hover:bg-chart-3 transition-colors"></div>
        </a>

        <!-- Total Orders / Clients Stat -->
        <a href="{{ route('admin.orders.index') }}"
            class="bg-card rounded-2xl p-6 border border-border card-lift relative overflow-hidden group shadow-xs block">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground font-sans">Orders & Invoices</span>
                    <h3 class="text-3xl font-bold font-serif text-foreground mt-2 number-pop">{{ $totalOrders ?? 0 }}</h3>
                    <p class="text-xs text-muted-foreground mt-2 flex items-center gap-1">
                        <span class="inline-block w-2 h-2 rounded-full bg-chart-4"></span>
                        {{ $totalClients ?? 0 }} registered clients
                    </p>
                </div>
                <div class="p-3.5 rounded-2xl bg-chart-4/15 text-chart-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-chart-4/30 group-hover:bg-chart-4 transition-colors"></div>
        </a>
    </div>

    <!-- Quick Actions Banner & Maintenance Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Quick Actions & Links -->
        <div class="lg:col-span-2 bg-card rounded-2xl border border-border p-6 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-serif font-bold text-lg text-foreground">Boutique Management Actions</h3>
                    <p class="text-xs text-muted-foreground">Jump directly to common artisan catalog and sales tasks.</p>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full bg-secondary text-secondary-foreground font-medium border border-border">
                    Quick Access
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <a href="{{ route('admin.products.create') }}"
                    class="p-4 rounded-xl border border-border bg-background hover:bg-muted card-lift text-center group transition-all">
                    <div class="w-10 h-10 mx-auto rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <span class="text-xs font-semibold text-foreground block">New Product</span>
                </a>

                <a href="{{ route('admin.orders.create') }}"
                    class="p-4 rounded-xl border border-border bg-background hover:bg-muted card-lift text-center group transition-all">
                    <div class="w-10 h-10 mx-auto rounded-xl bg-chart-2/15 text-chart-2 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="text-xs font-semibold text-foreground block">Create Order</span>
                </a>

                <a href="{{ route('admin.categories.index') }}"
                    class="p-4 rounded-xl border border-border bg-background hover:bg-muted card-lift text-center group transition-all">
                    <div class="w-10 h-10 mx-auto rounded-xl bg-chart-3/20 text-chart-3 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    </div>
                    <span class="text-xs font-semibold text-foreground block">Categories</span>
                </a>

                <a href="{{ route('admin.settings.index') }}"
                    class="p-4 rounded-xl border border-border bg-background hover:bg-muted card-lift text-center group transition-all">
                    <div class="w-10 h-10 mx-auto rounded-xl bg-chart-4/15 text-chart-4 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                    </div>
                    <span class="text-xs font-semibold text-foreground block">Settings</span>
                </a>
            </div>
        </div>

        <!-- Maintenance & System Mode -->
        <div class="bg-card rounded-2xl border border-border p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground font-sans">Store Visibility</span>
                    @if(Cache::get('maintenance_mode'))
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-destructive/15 text-destructive border border-destructive/20 animate-pulse-glow">
                            Maintenance Mode
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                            Live to Public
                        </span>
                    @endif
                </div>
                <h4 class="font-serif font-bold text-base text-foreground">Maintenance Controller</h4>
                <p class="text-xs text-muted-foreground mt-1">
                    @if(Cache::get('maintenance_mode'))
                        Storefront is temporarily hidden for updates. Only authenticated admins have access.
                    @else
                        Storefront is fully public and active for shoppers worldwide.
                    @endif
                </p>
            </div>

            <div class="pt-5 border-t border-border mt-4">
                <button onclick="document.getElementById('maintenanceModal').classList.remove('hidden')"
                    class="w-full py-2.5 px-4 rounded-xl text-xs font-semibold tracking-wide uppercase transition-all duration-200 {{ Cache::get('maintenance_mode') ? 'bg-primary text-primary-foreground hover:bg-primary/90 shadow-xs' : 'bg-muted hover:bg-secondary text-foreground border border-border' }}">
                    {{ Cache::get('maintenance_mode') ? 'Deactivate Maintenance (Go Live)' : 'Toggle Maintenance Mode' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Recent Orders & Inquiries Split View -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Orders -->
        <div class="bg-card rounded-2xl border border-border shadow-xs overflow-hidden">
            <div class="p-5 border-b border-border flex items-center justify-between">
                <div>
                    <h3 class="font-serif font-bold text-base text-foreground">Recent Orders</h3>
                    <p class="text-xs text-muted-foreground">Latest transactions and purchases.</p>
                </div>
                <a href="{{ route('admin.orders.index', ['type' => 'order']) }}" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                    View All
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <div class="divide-y divide-border">
                @if(isset($recentOrders) && $recentOrders->count() > 0)
                    @foreach($recentOrders as $order)
                        <div class="p-4 flex items-center justify-between hover:bg-muted/40 transition-colors">
                            <div class="min-w-0 flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 font-serif font-bold text-xs">
                                    #
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-sm font-semibold text-foreground hover:text-primary transition-colors block truncate">
                                        {{ $order->order_number }}
                                    </a>
                                    <span class="text-xs text-muted-foreground block truncate">
                                        {{ $order->client?->name ?? 'Guest Buyer' }} &bull; {{ $order->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 ml-3">
                                <span class="text-sm font-bold font-serif text-foreground block">
                                    ${{ number_format($order->grand_total ?? 0, 2) }}
                                </span>
                                <span class="inline-block text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full mt-0.5 bg-secondary text-secondary-foreground border border-border">
                                    {{ ucfirst($order->status ?? 'unprocessed') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="p-8 text-center text-muted-foreground text-sm">
                        No orders recorded yet.
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Inquiries -->
        <div class="bg-card rounded-2xl border border-border shadow-xs overflow-hidden">
            <div class="p-5 border-b border-border flex items-center justify-between">
                <div>
                    <h3 class="font-serif font-bold text-base text-foreground">Recent Inquiries</h3>
                    <p class="text-xs text-muted-foreground">Requests for custom handicraft quotes.</p>
                </div>
                <a href="{{ route('admin.orders.index', ['type' => 'inquiry']) }}" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                    View All
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <div class="divide-y divide-border">
                @if(isset($recentInquiries) && $recentInquiries->count() > 0)
                    @foreach($recentInquiries as $inquiry)
                        <div class="p-4 flex items-center justify-between hover:bg-muted/40 transition-colors">
                            <div class="min-w-0 flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-chart-3/20 text-chart-3 flex items-center justify-center flex-shrink-0 font-serif font-bold text-xs">
                                    INQ
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('admin.orders.show', $inquiry) }}" class="text-sm font-semibold text-foreground hover:text-primary transition-colors block truncate">
                                        {{ $inquiry->order_number }}
                                    </a>
                                    <span class="text-xs text-muted-foreground block truncate">
                                        {{ $inquiry->client?->name ?? 'Prospect' }} &bull; {{ $inquiry->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 ml-3">
                                <a href="{{ route('admin.orders.show', $inquiry) }}"
                                    class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium bg-primary/10 text-primary hover:bg-primary hover:text-primary-foreground transition-all">
                                    Reply & Quote
                                </a>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="p-8 text-center text-muted-foreground text-sm">
                        No customer inquiries pending.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Password Modal for Maintenance Mode -->
    <div id="maintenanceModal" class="hidden fixed inset-0 bg-foreground/40 backdrop-blur-xs overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
        <div class="relative p-6 border border-border w-full max-w-md shadow-2xl rounded-2xl bg-card animate-scale-in">
            <div class="text-center">
                <div class="w-12 h-12 mx-auto rounded-full bg-primary/10 text-primary flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-serif font-bold text-foreground">Confirm Storefront Action</h3>
                <p class="text-xs text-muted-foreground mt-1 px-4">
                    Enter your administrator password to {{ Cache::get('maintenance_mode') ? 'disable' : 'enable' }} maintenance mode.
                </p>
                <form action="{{ route('admin.maintenance.toggle') }}" method="POST" class="mt-5">
                    @csrf
                    <input type="password" name="password" placeholder="Admin Password" required
                        class="w-full rounded-xl border border-input bg-background px-4 py-2.5 text-sm text-foreground shadow-xs focus:border-primary focus:ring-2 focus:ring-ring outline-none mb-4">
                    <div class="flex gap-2.5 justify-end">
                        <button type="button"
                            onclick="document.getElementById('maintenanceModal').classList.add('hidden')"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-muted text-muted-foreground hover:bg-secondary hover:text-foreground transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-5 py-2 rounded-xl text-xs font-semibold bg-primary text-primary-foreground hover:bg-primary/90 shadow-xs transition-colors">
                            Confirm Change
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

