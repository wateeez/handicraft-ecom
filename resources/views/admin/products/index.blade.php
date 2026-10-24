@extends('admin.layout')

@section('header')
    <div>
    <h2 class="text-xl font-semibold text-foreground leading-tight">
        Manage Products
    </h2>
    <p class="text-xs text-muted-foreground mt-0.5">Manage handicraft listings, pricing, and dimensional weights</p>
    </div>
@endsection

@section('inline_flash', '1')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div class="min-w-0">@include('admin.partials.flash', ['inline' => true])</div>
        <div class="flex flex-col sm:flex-row gap-3">
        @if(auth()->user()->hasPermission('manage_products'))
        <a href="{{ route('admin.products.bulk') }}"
            class="bg-card hover:bg-paper border border-border text-foreground font-semibold py-2.5 px-5 rounded-xl shadow-xs transition-all text-center w-full sm:w-auto inline-flex items-center justify-center gap-2">
            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            Bulk Upload
        </a>
        @endif
        <a href="{{ route('admin.products.create') }}"
            class="bg-primary hover:bg-primary/90 text-primary-foreground font-semibold py-2.5 px-5 rounded-xl shadow-sm hover:shadow transition-all text-center w-full sm:w-auto inline-flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add New Product
        </a>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-card p-5 rounded-2xl mb-6 border border-border shadow-xs">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Product name, SKU or vendor"
                        class="w-full px-4 py-2.5 bg-paper/50 border border-border rounded-xl text-foreground text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Category</label>
                    <select name="category_id" class="w-full px-4 py-2.5 bg-paper/50 border border-border rounded-xl text-foreground text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Vendor</label>
                    <select name="vendor_id" class="w-full px-4 py-2.5 bg-paper/50 border border-border rounded-xl text-foreground text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
                        <option value="">All Vendors</option>
                        <option value="none" {{ request('vendor_id') === 'none' ? 'selected' : '' }}>No vendor assigned</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->name }} ({{ $vendor->products_count }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" class="bg-primary hover:bg-primary/90 text-primary-foreground font-semibold px-5 py-2.5 rounded-xl text-sm transition shadow-xs">Filter</button>
                @if(request()->anyFilled(['search', 'category_id', 'vendor_id']))
                    <a href="{{ route('admin.products.index') }}" class="text-muted-foreground hover:text-foreground text-sm font-medium px-4 py-2.5 transition">Clear</a>
                @endif
                <span class="text-xs text-muted-foreground ml-auto">{{ $products->total() }} {{ Str::plural('product', $products->total()) }} found</span>
            </div>
        </form>
    </div>

    <div class="bg-card rounded-2xl border border-border shadow-xs overflow-x-auto">
        <table class="min-w-full divide-y divide-border">
            <thead class="bg-paper/60">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Name</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Category</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Vendor</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Price</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Dimensions</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Weight</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">SKU</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Direct Order</th>
                    <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-card divide-y divide-border/60">
                @foreach($products as $product)
                    <tr class="hover:bg-paper/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center w-64 max-w-[16rem] cursor-default"
                                x-data="{ open: false, x: 0, y: 0, place() { const r = this.$el.getBoundingClientRect(); this.x = Math.min(r.left, window.innerWidth - 400); this.y = r.bottom + 6; if (this.y + 304 > window.innerHeight) this.y = Math.max(8, r.top - 310); this.open = true; } }"
                                @mouseenter="place()" @mouseleave="open = false" @focusin="place()" @focusout="open = false" tabindex="0">
                                @if($product->main_image)
                                    <img class="h-10 w-10 flex-shrink-0 rounded-lg object-cover mr-3 border border-border shadow-xs" src="{{ $product->main_image }}" alt="">
                                @else
                                    <div class="h-10 w-10 flex-shrink-0 rounded-lg bg-paper text-muted-foreground border border-border flex items-center justify-center mr-3 text-[11px] font-mono">
                                        No Img</div>
                                @endif
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-foreground truncate">{{ $product->name }}</div>
                                    <div class="text-[11px] text-muted-foreground font-mono truncate">{{ $product->sku ?? '-' }}</div>
                                </div>

                                <div x-show="open" x-cloak x-transition.opacity.duration.150ms
                                    :style="`left:${x}px; top:${y}px`"
                                    class="fixed z-50 w-[24rem] h-[18rem] max-w-[calc(100vw-1rem)] flex flex-col overflow-hidden whitespace-normal bg-card border border-border rounded-xl shadow-xl p-4 pointer-events-none">
                                    <div class="flex-1 min-h-0 overflow-hidden">
                                        <p class="font-serif font-semibold text-foreground leading-snug break-words line-clamp-3">{{ $product->name }}</p>
                                        <p class="mt-1.5 text-xs text-muted-foreground leading-relaxed break-words line-clamp-3">{{ $product->description ? strip_tags($product->description) : 'No description added.' }}</p>
                                    </div>
                                    <dl class="mt-3 pt-3 border-t border-border flex-shrink-0 grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1.5 text-xs">
                                        <dt class="text-muted-foreground">SKU</dt><dd class="text-foreground font-mono truncate">{{ $product->sku ?? '-' }}</dd>
                                        <dt class="text-muted-foreground">Category</dt><dd class="text-foreground truncate">{{ $product->category->name ?? 'Uncategorized' }}</dd>
                                        <dt class="text-muted-foreground">Vendor</dt><dd class="text-foreground truncate">{{ $product->vendor->name ?? '—' }}</dd>
                                        <dt class="text-muted-foreground">Price</dt>
                                        <dd class="text-foreground">
                                            @if($product->discount_price)
                                                <span class="line-through text-muted-foreground mr-1">${{ number_format($product->price, 2) }}</span><span class="font-semibold text-primary">${{ number_format($product->discount_price, 2) }}</span>
                                            @else
                                                ${{ number_format($product->price, 2) }}
                                            @endif
                                        </dd>
                                        <dt class="text-muted-foreground">Size</dt><dd class="text-foreground font-mono">{{ $product->length }} &times; {{ $product->width }} &times; {{ $product->height }} cm</dd>
                                        <dt class="text-muted-foreground">Weight</dt><dd class="text-foreground font-mono">{{ $product->weight }} kg</dd>
                                        <dt class="text-muted-foreground">Stock</dt><dd class="text-foreground">{{ $product->stock ?? '-' }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                            {{ $product->category->name ?? 'Uncategorized' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                            @if($product->vendor)
                                <a href="{{ route('admin.products.index', ['vendor_id' => $product->vendor_id]) }}"
                                    class="text-primary hover:underline font-medium">{{ $product->vendor->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($product->discount_price)
                                <span class="text-muted-foreground line-through text-xs mr-1.5">${{ number_format($product->price, 2) }}</span>
                                <span class="font-bold text-primary">${{ number_format($product->discount_price, 2) }}</span>
                            @else
                                <span class="font-semibold text-foreground">${{ number_format($product->price, 2) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-muted-foreground font-mono">
                            {{ $product->length }} &times; {{ $product->width }} &times; {{ $product->height }} cm
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-muted-foreground font-mono">
                            {{ $product->weight }} kg
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-muted-foreground font-mono">
                            {{ $product->sku ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-semibold rounded-full {{ $product->is_order_now_enabled ? 'bg-emerald-500/10 text-emerald-800 border border-emerald-500/20' : 'bg-muted/50 text-muted-foreground border border-border' }}">
                                {{ $product->is_order_now_enabled ? 'Enabled' : 'Quote Only' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                            <a href="{{ route('admin.products.edit', $product) }}"
                                class="text-primary hover:underline font-semibold">Edit</a>
                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline-block"
                                onsubmit="event.preventDefault(); openDeleteModal(this, 'Enter your password to delete this product.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-5">
        {{ $products->links() }}
    </div>
@endsection
