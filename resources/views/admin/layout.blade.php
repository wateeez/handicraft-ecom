<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard - {{ $siteSettings['site_name'] ?? 'Ecom' }}</title>
    @if(!empty($siteSettings['favicon_url']))
        <link rel="icon" type="image/x-icon" href="{{ $siteSettings['favicon_url'] }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Manrope', sans-serif;
        }
    </style>
</head>

<body class="bg-background text-foreground font-sans antialiased m-0 p-0 selection:bg-primary/20 selection:text-foreground">

    <div class="min-h-screen md:h-screen flex flex-col md:flex-row bg-background">
        <input id="adminSidebarToggle" type="checkbox" class="peer sr-only" aria-hidden="true" />

        <!-- Mobile overlay -->
        <label for="adminSidebarToggle" class="hidden peer-checked:block fixed inset-0 z-40 bg-foreground/40 backdrop-blur-xs md:hidden transition-all duration-300"
            aria-label="Close sidebar overlay"></label>

        <!-- Sidebar -->
        <aside id="adminSidebar"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-sidebar text-sidebar-foreground border-r border-sidebar-border flex flex-col transform transition-transform duration-300 ease-in-out -translate-x-full peer-checked:translate-x-0 md:static md:translate-x-0 shadow-xl md:shadow-none">
            <div class="h-16 flex items-center justify-between gap-3 border-b border-sidebar-border px-5 min-w-0 bg-sidebar">
                <div class="flex items-center gap-3 min-w-0">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 min-w-0 group">
                        @if(!empty($siteSettings['navbar_logo_url']))
                            <img src="{{ $siteSettings['navbar_logo_url'] }}"
                                alt="{{ $siteSettings['site_name'] ?? 'Ecom' }} Admin"
                                class="h-9 w-auto max-w-[140px] md:max-w-[160px] object-contain transition-transform group-hover:scale-105">
                        @else
                            <div class="w-8 h-8 rounded-lg bg-primary/20 border border-primary/30 flex items-center justify-center text-primary font-serif font-bold text-lg">
                                {{ strtoupper(substr($siteSettings['site_name'] ?? 'E', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <h1 class="text-sm font-semibold tracking-wide text-sidebar-foreground truncate">
                                    {{ $siteSettings['site_name'] ?? 'Handicraft' }}
                                </h1>
                                <span class="text-[10px] font-medium tracking-wider uppercase text-sidebar-foreground/50 block">Admin Suite</span>
                            </div>
                        @endif
                    </a>
                </div>
                
                <label for="adminSidebarToggle"
                    class="flex-shrink-0 inline-flex items-center justify-center h-8 w-8 rounded-md border border-sidebar-border text-sidebar-foreground/70 hover:text-sidebar-foreground hover:bg-sidebar-accent md:hidden cursor-pointer transition-colors"
                    role="button" tabindex="0" aria-label="Close sidebar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </label>
            </div>

            <nav class="flex-1 overflow-y-auto py-4 px-3 custom-scroll space-y-6">
                <div>
                    <div class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-sidebar-foreground/40 font-sans">
                        Overview
                    </div>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('admin.dashboard') }}"
                                class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-sidebar-primary text-sidebar-primary-foreground font-semibold shadow-sm' : 'text-sidebar-foreground/75 hover:text-sidebar-foreground hover:bg-sidebar-accent' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.dashboard') ? 'text-sidebar-primary-foreground' : 'text-sidebar-foreground/60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                                    </path>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <div class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-sidebar-foreground/40 font-sans">
                        Catalogue & Sales
                    </div>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('admin.categories.index') }}"
                                class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.categories.*') ? 'bg-sidebar-primary text-sidebar-primary-foreground font-semibold shadow-sm' : 'text-sidebar-foreground/75 hover:text-sidebar-foreground hover:bg-sidebar-accent' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.categories.*') ? 'text-sidebar-primary-foreground' : 'text-sidebar-foreground/60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                                <span>Categories</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.products.index') }}"
                                class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.products.*') ? 'bg-sidebar-primary text-sidebar-primary-foreground font-semibold shadow-sm' : 'text-sidebar-foreground/75 hover:text-sidebar-foreground hover:bg-sidebar-accent' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.products.*') ? 'text-sidebar-primary-foreground' : 'text-sidebar-foreground/60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                <span>Products</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.orders.index', ['type' => 'inquiry']) }}"
                                class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request('type') === 'inquiry' && request()->routeIs('admin.orders.*') ? 'bg-sidebar-primary text-sidebar-primary-foreground font-semibold shadow-sm' : 'text-sidebar-foreground/75 hover:text-sidebar-foreground hover:bg-sidebar-accent' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request('type') === 'inquiry' && request()->routeIs('admin.orders.*') ? 'text-sidebar-primary-foreground' : 'text-sidebar-foreground/60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <span>Inquiries</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.clients.index') }}"
                                class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.clients.*') ? 'bg-sidebar-primary text-sidebar-primary-foreground font-semibold shadow-sm' : 'text-sidebar-foreground/75 hover:text-sidebar-foreground hover:bg-sidebar-accent' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.clients.*') ? 'text-sidebar-primary-foreground' : 'text-sidebar-foreground/60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                                <span>Clients</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.orders.index') }}"
                                class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.orders.*') && request('type') !== 'inquiry' ? 'bg-sidebar-primary text-sidebar-primary-foreground font-semibold shadow-sm' : 'text-sidebar-foreground/75 hover:text-sidebar-foreground hover:bg-sidebar-accent' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.orders.*') && request('type') !== 'inquiry' ? 'text-sidebar-primary-foreground' : 'text-sidebar-foreground/60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z">
                                    </path>
                                </svg>
                                <span>Orders & Invoices</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <div class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-sidebar-foreground/40 font-sans">
                        Settings & Content
                    </div>
                    <ul class="space-y-1">
                        <li>
                            @php($shippingOpen = request()->routeIs('admin.shipping.*'))
                            <details class="group" {{ $shippingOpen ? 'open' : '' }}>
                                <summary
                                    class="cursor-pointer list-none w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.shipping.*') ? 'bg-sidebar-accent text-sidebar-foreground font-semibold' : 'text-sidebar-foreground/75 hover:text-sidebar-foreground hover:bg-sidebar-accent' }}">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-3 flex-shrink-0 text-sidebar-foreground/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0">
                                            </path>
                                        </svg>
                                        <span>Shipping</span>
                                    </div>
                                    <svg class="w-4 h-4 transition-transform duration-200 group-open:rotate-180 text-sidebar-foreground/50" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </summary>
                                <!-- Nested Shipping submenu -->
                                @php($sidebarProviders = \App\Models\ShippingProvider::orderBy('name')->get())
                                <div class="pl-4 mt-1 border-l border-sidebar-border/40 ml-4 space-y-1">
                                    <a href="{{ route('admin.shipping.zones.settings') }}"
                                        class="flex items-center px-3 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('admin.shipping.zones.settings') ? 'text-primary font-bold' : 'text-sidebar-foreground/70 hover:text-sidebar-foreground' }}">
                                        Zone Settings
                                    </a>
                                    <a href="{{ route('admin.shipping.providers.settings') }}"
                                        class="flex items-center px-3 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('admin.shipping.providers.settings') ? 'text-primary font-bold' : 'text-sidebar-foreground/70 hover:text-sidebar-foreground' }}">
                                        Providers Settings
                                    </a>
                                    @foreach($sidebarProviders as $prov)
                                        <a href="{{ route('admin.shipping.providers.show', $prov) }}"
                                            class="flex items-center px-3 py-1.5 rounded text-xs font-medium transition-colors {{ request()->routeIs('admin.shipping.providers.show') && request()->route('provider')?->id == $prov->id ? 'text-primary font-bold' : 'text-sidebar-foreground/70 hover:text-sidebar-foreground' }}">
                                            <span class="w-1.5 h-1.5 bg-accent rounded-full mr-2"></span>
                                            <span class="truncate">{{ $prov->name }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </details>
                        </li>
                        <li>
                            <a href="{{ route('admin.blog.index') }}"
                                class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.blog.*') ? 'bg-sidebar-primary text-sidebar-primary-foreground font-semibold shadow-sm' : 'text-sidebar-foreground/75 hover:text-sidebar-foreground hover:bg-sidebar-accent' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.blog.*') ? 'text-sidebar-primary-foreground' : 'text-sidebar-foreground/60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z">
                                    </path>
                                </svg>
                                <span>Blog Posts</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.settings.index') }}"
                                class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.settings.*') ? 'bg-sidebar-primary text-sidebar-primary-foreground font-semibold shadow-sm' : 'text-sidebar-foreground/75 hover:text-sidebar-foreground hover:bg-sidebar-accent' }}">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.settings.*') ? 'text-sidebar-primary-foreground' : 'text-sidebar-foreground/60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>Site Settings</span>
                            </a>
                        </li>
                        @if(auth()->user()->isSuperAdmin())
                            <li>
                                <a href="{{ route('admin.users.index') }}"
                                    class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-sidebar-primary text-sidebar-primary-foreground font-semibold shadow-sm' : 'text-sidebar-foreground/75 hover:text-sidebar-foreground hover:bg-sidebar-accent' }}">
                                    <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.users.*') ? 'text-sidebar-primary-foreground' : 'text-sidebar-foreground/60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                        </path>
                                    </svg>
                                    <span>Admin Users</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.roles.index') }}"
                                    class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.roles.*') ? 'bg-sidebar-primary text-sidebar-primary-foreground font-semibold shadow-sm' : 'text-sidebar-foreground/75 hover:text-sidebar-foreground hover:bg-sidebar-accent' }}">
                                    <svg class="w-5 h-5 mr-3 flex-shrink-0 {{ request()->routeIs('admin.roles.*') ? 'text-sidebar-primary-foreground' : 'text-sidebar-foreground/60' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                        </path>
                                    </svg>
                                    <span>Roles & Permissions</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </nav>

            <!-- Sidebar footer / quick live store link -->
            <div class="p-3 border-t border-sidebar-border bg-sidebar/50">
                <a href="{{ route('home') }}" target="_blank"
                    class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium text-sidebar-foreground/70 hover:text-sidebar-foreground hover:bg-sidebar-accent transition-colors">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span>View Live Store</span>
                    </span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 min-w-0 flex flex-col overflow-hidden bg-background">
            <!-- Header -->
            <header class="h-16 bg-card/80 backdrop-blur-md border-b border-border flex items-center justify-between px-4 sm:px-6 sticky top-0 z-20">
                <div class="flex items-center gap-3 min-w-0">
                    <label for="adminSidebarToggle"
                        class="md:hidden inline-flex items-center justify-center h-9 w-9 rounded-lg border border-border bg-card text-foreground hover:bg-muted cursor-pointer transition-colors shadow-xs"
                        role="button" tabindex="0" aria-label="Open sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>

                    <div class="min-w-0">
                        @yield('header')
                    </div>
                </div>

                <div class="flex items-center gap-3 sm:gap-4">
                    <a href="{{ route('home') }}" target="_blank"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-muted text-foreground/80 hover:text-primary hover:bg-muted/80 border border-border transition-colors"
                        title="Open Storefront">
                        <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>Storefront</span>
                    </a>

                    <div class="h-6 w-px bg-border hidden sm:block"></div>

                    <div class="flex items-center gap-2.5">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-semibold text-foreground leading-tight">{{ auth()->user()->name }}</div>
                            <div class="text-[11px] text-muted-foreground">
                                @foreach(auth()->user()->roles as $role)
                                    <span>{{ $role->display_name }}</span>{{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            </div>
                        </div>
                        <div class="h-9 w-9 bg-primary/10 border border-primary/20 rounded-full flex items-center justify-center text-primary font-bold text-sm shadow-xs">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="p-2 rounded-lg text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors" title="Logout">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                    </path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-auto bg-background p-4 sm:p-6 md:p-8 custom-scroll bg-paper">
                @if(session('success'))
                    <div class="mb-5 bg-card border-l-4 border-primary p-4 rounded-r-xl shadow-xs flex items-start gap-3 animate-fade-in-up" role="alert">
                        <div class="p-1 rounded-full bg-primary/10 text-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <p class="font-medium text-sm text-foreground">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif
                @if(session('checkout_link'))
                    <div class="mb-4 bg-violet-50 border border-violet-200 text-violet-800 px-4 py-3 rounded relative" role="alert">
                        <p class="font-semibold text-sm mb-1">Payment link ready — share this with the customer:</p>
                        <div class="flex items-center gap-2 flex-wrap">
                            <code class="text-xs bg-white border border-violet-200 px-3 py-1.5 rounded select-all break-all">{{ session('checkout_link') }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ session('checkout_link') }}').then(()=>this.textContent='Copied!')"
                                class="text-xs px-3 py-1.5 bg-violet-600 text-white rounded hover:bg-violet-700 transition-colors whitespace-nowrap">
                                Copy
                            </button>
                        </div>
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 bg-red-100 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <!-- Password Confirmation Modal for Delete Operations -->
    <div id="deletePasswordModal"
        class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-4 sm:mx-auto p-5 border w-full sm:w-96 shadow-lg rounded-md bg-cream">
            <div class="mt-3">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-truffle-extra-dark text-center mt-4">Confirm Deletion</h3>
                <div class="mt-2 px-4">
                    <p class="text-sm text-truffle-extra-dark text-center" id="deleteModalMessage">
                        Enter your password to confirm this deletion.
                    </p>
                    <div class="mt-4">
                        <input type="password" id="deletePasswordInput" placeholder="Enter your password"
                            class="w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-red-500 focus:ring-red-500 border p-2">
                        <p id="deletePasswordError" class="mt-1 text-sm text-red-600 hidden">Incorrect password. Please
                            try again.</p>
                    </div>
                    <div class="flex gap-2 justify-center mt-4">
                        <button type="button" onclick="closeDeleteModal()"
                            class="bg-[#E8E2D2] text-truffle-extra-dark px-4 py-2 rounded hover:bg-[#C5A059] transition">Cancel</button>
                        <button type="button" onclick="confirmDelete()" id="confirmDeleteBtn"
                            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Expose CSRF token globally for JS-based requests
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let pendingDeleteForm = null;

        function openDeleteModal(form, message = 'Enter your password to confirm this deletion.') {
            pendingDeleteForm = form;
            document.getElementById('deleteModalMessage').textContent = message;
            document.getElementById('deletePasswordInput').value = '';
            document.getElementById('deletePasswordError').classList.add('hidden');
            document.getElementById('deletePasswordModal').classList.remove('hidden');
            document.getElementById('deletePasswordInput').focus();
        }

        function closeDeleteModal() {
            document.getElementById('deletePasswordModal').classList.add('hidden');
            pendingDeleteForm = null;
        }

        async function confirmDelete() {
            const password = document.getElementById('deletePasswordInput').value;
            const errorEl = document.getElementById('deletePasswordError');
            const confirmBtn = document.getElementById('confirmDeleteBtn');

            if (!password) {
                errorEl.textContent = 'Please enter your password.';
                errorEl.classList.remove('hidden');
                return;
            }

            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Verifying...';

            try {
                const response = await fetch('{{ route("admin.verify-password") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ password: password })
                });

                const data = await response.json();

                if (data.success) {
                    if (pendingDeleteForm) {
                        console.log('Submitting form:', pendingDeleteForm);
                        // Remove the onsubmit handler and submit
                        pendingDeleteForm.onsubmit = null;
                        pendingDeleteForm.submit();
                    } else {
                        console.error('No pending form found');
                        closeDeleteModal();
                    }
                } else {
                    errorEl.textContent = 'Incorrect password. Please try again.';
                    errorEl.classList.remove('hidden');
                    document.getElementById('deletePasswordInput').value = '';
                    document.getElementById('deletePasswordInput').focus();
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Delete';
                }
            } catch (error) {
                errorEl.textContent = 'An error occurred. Please try again.';
                errorEl.classList.remove('hidden');
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Delete';
            }
        }

        // Sidebar open/close is CSS-only via #adminSidebarToggle (no JS needed)

        // Handle Enter key in password input
        document.getElementById('deletePasswordInput').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                confirmDelete();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>

    @yield('scripts')
</body>

</html>


