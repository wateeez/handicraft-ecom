<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $siteSettings['site_name'] . ' - Premium Ecommerce')</title>
    <meta name="description" content="@yield('meta_description', 'Premium curated products for your lifestyle')">
    <meta name="keywords" content="@yield('meta_keywords', 'ecommerce, products, shopping')">
    @if(!empty($siteSettings['favicon_url']))
        <link rel="icon" type="image/x-icon" href="{{ $siteSettings['favicon_url'] }}">
    @endif
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Noto+Serif:ital,wght@0,400;0,600;0,700;1,400&display=swap"
        rel="stylesheet">
    <style>
        .site-header {
            background-color: rgba(253, 251, 247, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-color: var(--color-border);
        }

        .site-footer {
            background-color: var(--color-foreground);
            color: var(--color-background);
        }

        .site-footer-muted {
            color: rgba(250, 247, 242, 0.72);
        }

        .site-footer-divider {
            border-color: rgba(250, 247, 242, 0.14);
        }

        h1,
        h2,
        h3,
        .serif {
            font-family: 'Noto Serif', serif;
            text-wrap: balance;
        }
    </style>
</head>

<body class="bg-background text-foreground flex flex-col min-h-screen m-0 p-0 selection:bg-primary/25 selection:text-foreground">

    <!-- Header -->
    <header class="site-header sticky top-0 z-50 border-b-2 shadow-sm">
        <div
            class="container mx-auto px-4 sm:px-6 py-4 flex flex-col gap-3 md:gap-6 md:flex-row md:justify-between md:items-center">
            <div class="flex items-center justify-between w-full md:w-auto">
                <!-- Logo / Store Name -->
                <a href="{{ route('home') }}" class="flex items-center space-x-3">
                    @if(!empty($siteSettings['navbar_logo_url']))
                        <img src="{{ $siteSettings['navbar_logo_url'] }}"
                            alt="{{ $siteSettings['site_name'] }}" class="h-12 w-auto object-contain">
                    @else
                        <span class="text-2xl font-bold font-serif text-truffle-extra-dark tracking-wide">
                            {{ $siteSettings['site_name'] }}
                        </span>
                    @endif
                </a>
                <!-- Mobile quick actions -->
                <div class="flex items-center gap-4 md:hidden">
                    <a href="{{ route('cart.index') }}"
                        class="relative text-foreground hover:text-primary flex items-center gap-1.5 transition-colors duration-200"
                        aria-label="Cart">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <span class="text-sm font-serif font-medium">Cart</span>
                    </a>
                </div>
            </div>

            <!-- Search, Cart, Profile -->
            <div class="w-full flex items-center justify-center md:flex-1 md:px-8">
                <!-- Search Bar -->
                <div class="w-full max-w-2xl">
                    <form action="{{ route('home') }}" method="GET" class="relative">
                        <input type="text" name="search" placeholder="Search handcrafted masterworks..."
                            value="{{ request('search') }}"
                            class="w-full bg-card border border-border rounded-full py-2.5 sm:py-3 pl-5 pr-12 text-sm sm:text-base text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all duration-200 shadow-xs">
                        <button type="submit"
                            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-primary transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Cart -->
            <div class="hidden md:flex items-center justify-end space-x-6 w-full md:w-auto">
                <!-- Cart -->
                <a href="{{ route('cart.index') }}"
                    class="relative text-foreground hover:text-primary flex items-center gap-2 transition-colors duration-200 group">
                    <svg class="w-6 h-6 transform group-hover:scale-110 transition-transform duration-200" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <span class="text-[15px] font-serif font-semibold tracking-tight">My Cart</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
        @if(session('success'))
            <div class="bg-green-premium/20 border-l-4 border-green-premium text-green-premium p-4 container mx-auto mt-4 px-4 sm:px-6"
                role="alert">
                <p class="font-bold">Success</p>
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="site-footer py-12">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- About Column -->
                <div class="md:col-span-2">
                    <div class="flex items-center space-x-3 mb-4">
                        @if(!empty($siteSettings['footer_logo_url']))
                            <img src="{{ $siteSettings['footer_logo_url'] }}"
                                alt="{{ $siteSettings['site_name'] }}" class="h-10 w-auto object-contain">
                        @else
                            <span class="text-xl font-bold font-serif tracking-wide">
                                {{ $siteSettings['site_name'] }}
                            </span>
                        @endif
                    </div>
                    <p class="site-footer-muted text-sm mb-4 max-w-sm">
                        Curated collection of exquisite handicrafts directly from authentic artisan cooperatives.
                    </p>

                    <!-- Contact Details -->
                    <ul class="site-footer-muted space-y-2 text-sm">
                        @if($siteSettings['footer_address'])
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-1 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{!! nl2br(e($siteSettings['footer_address'])) !!}</span>
                            </li>
                        @endif
                        @if($siteSettings['footer_phone'])
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $siteSettings['footer_phone']) }}"
                                    class="hover:text-gold">{{ $siteSettings['footer_phone'] }}</a>
                            </li>
                        @endif
                        @if($siteSettings['footer_email'])
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <a href="mailto:{{ $siteSettings['footer_email'] }}"
                                    class="hover:text-gold">{{ $siteSettings['footer_email'] }}</a>
                            </li>
                        @endif
                        @if($siteSettings['footer_hours'])
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $siteSettings['footer_hours'] }}</span>
                            </li>
                        @endif
                    </ul>
                </div>

                <!-- Quick Links Column -->
                <div>
                    <h4 class="font-bold mb-4">Quick Links</h4>
                    <ul class="site-footer-muted space-y-2 text-sm">
                        <li><a href="{{ route('blog.index') }}" class="hover:text-gold">Blog</a></li>
                        <li><a href="{{ route('home', ['filter' => 'new-arrivals']) }}" class="hover:text-gold">New
                                Arrivals</a></li>
                        <li><a href="{{ route('home', ['filter' => 'featured']) }}" class="hover:text-gold">Featured
                                Products</a></li>
                        <li><a href="{{ route('home', ['filter' => 'on-sale']) }}" class="hover:text-gold">On Sale</a>
                        </li>
                        <li><a href="{{ route('pages.shipping-policy') }}" class="hover:text-gold">Shipping Policy</a>
                        </li>
                    </ul>
                </div>

                <!-- QR Code Column -->
                <div class="flex flex-col items-center md:items-end">
                    @if($siteSettings['footer_qr_code'] && $siteSettings['footer_qr_code']->value)
                        <div class="text-center md:text-right">
                            <h4 class="font-bold mb-4">Scan to Connect</h4>
                            @php
                                $whatsappNumber = $siteSettings['whatsapp_number'] ?? null;
                                $whatsappLink = $whatsappNumber ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsappNumber) : '#';
                            @endphp
                            <a href="{{ $whatsappLink }}" target="_blank" rel="noopener noreferrer"
                                title="Chat on WhatsApp">
                                <img src="{{ $siteSettings['footer_qr_code_url'] }}"
                                    alt="WhatsApp QR Code"
                                    class="w-32 h-32 object-contain bg-cream p-2 rounded hover:shadow-lg transition cursor-pointer">
                            </a>
                            <p class="site-footer-muted text-xs mt-2">Tap to chat on WhatsApp</p>
                        </div>
                    @endif
                </div>
            </div>
            <div class="site-footer-divider site-footer-muted mt-8 border-t pt-8 text-center text-sm">
                &copy; 2026 {{ $siteSettings['site_name'] }}. All rights reserved.
            </div>
        </div>
    </footer>
</body>

</html>
