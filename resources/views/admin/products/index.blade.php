@extends('admin.layout')

@section('header')
    <h2 class="font-semibold text-xl text-truffle-extra-dark leading-tight">
        Manage Products
    </h2>
@endsection

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center mb-6">
        <div>
            <h3 class="text-xl font-serif font-bold text-foreground">Catalog & Inventory</h3>
            <p class="text-xs text-muted-foreground mt-0.5">Manage handicraft listings, pricing, and dimensional weights</p>
        </div>
        <a href="{{ route('admin.products.create') }}"
            class="bg-primary hover:bg-primary/90 text-primary-foreground font-semibold py-2.5 px-5 rounded-xl shadow-sm hover:shadow transition-all text-center w-full sm:w-auto inline-flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add New Product
        </a>
    </div>

    @if(session('success'))
    <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 px-4 py-3 rounded-xl mb-6 flex items-center gap-2 text-sm">
        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-500/10 border border-red-500/30 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-2 text-sm">
        <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
        {{ session('error') }}
    </div>
    @endif

    <!-- Bulk Operations Section -->
    <div class="bg-cream rounded-lg shadow-sm p-6 mb-6 border border-truffle-medium/30">
        <div class="flex items-center mb-4">
            <svg class="w-6 h-6 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            <h2 class="text-xl font-semibold text-truffle-extra-dark">Bulk Operations</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- CSV Import/Export -->
            <div class="border border-truffle-medium/30 rounded-lg p-4 bg-gradient-to-br from-blue-50 to-blue-100">
                <h3 class="text-lg font-semibold text-truffle-extra-dark mb-3 flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Step 1: CSV Product Data
                </h3>
                <p class="text-sm text-truffle-extra-dark mb-4">Import or export product information (no images)</p>
                
                <div class="space-y-3">
                    <!-- Download Template -->
                    <a href="{{ route('admin.products.template') }}" 
                        class="block w-full bg-cream text-blue-600 border-2 border-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition-colors text-center font-medium">
                        Download Template CSV
                    </a>

                    <!-- Export Products -->
                    <a href="{{ route('admin.products.export') }}" 
                        class="block w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-center font-medium">
                        Export Current Products
                    </a>

                    <!-- Import Products -->
                    <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        <label class="block">
                            <span class="text-sm font-medium text-truffle-extra-dark mb-1 block">Upload CSV File:</span>
                            <input type="file" name="products_file" accept=".csv" required
                                class="block w-full text-sm text-truffle-extra-dark file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </label>
                        <button type="submit" 
                            class="w-full bg-green-premium text-white px-4 py-2 rounded-lg hover:bg-green-800 transition-colors font-medium">
                            Import Products
                        </button>
                    </form>
                </div>

                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-xs text-truffle-extra-dark">
                    <strong>Required fields:</strong> name, sku, category, price, weight, length, width, height<br>
                    <strong>Category lookup:</strong> Use exact category name from your system<br>
                    <strong>Booleans:</strong> Use true/false or 1/0
                </div>
            </div>

            <!-- Bulk Image Upload -->
            <div class="border border-truffle-medium/30 rounded-lg p-4 bg-gradient-to-br from-purple-50 to-purple-100">
                <h3 class="text-lg font-semibold text-truffle-extra-dark mb-3 flex items-center">
                    <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Step 2: Bulk Image Upload
                </h3>
                <p class="text-sm text-truffle-extra-dark mb-4">Upload ZIP file with product images</p>

                <form action="{{ route('admin.products.bulk-images') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <label class="block">
                        <span class="text-sm font-medium text-truffle-extra-dark mb-1 block">Upload ZIP File (max 50MB):</span>
                        <input type="file" name="images_zip" accept=".zip" required
                            class="block w-full text-sm text-truffle-extra-dark file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                    </label>
                    <button type="submit" 
                        class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors font-medium">
                        Upload Product Images
                    </button>
                </form>

                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded text-xs text-truffle-extra-dark space-y-1">
                    <strong>Image Naming Convention:</strong>
                    <ul class="list-disc list-inside ml-2 space-y-1">
                        <li><code class="bg-cream px-1 rounded">SKU.jpg</code> -> Main image</li>
                        <li><code class="bg-cream px-1 rounded">SKU_0.jpg</code> -> Secondary image</li>
                        <li><code class="bg-cream px-1 rounded">SKU_1.jpg</code>, <code class="bg-cream px-1 rounded">SKU_2.jpg</code> -> Additional images (3rd, 4th, etc.)</li>
                    </ul>
                    <p class="mt-2"><strong>Example:</strong> CH001.jpg, CH001_0.jpg, CH001_1.png, CH001_2.jpg</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-card p-5 rounded-2xl mb-6 border border-border shadow-xs">
        <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1 w-full">
                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Search Products</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Product Name or SKU"
                    class="w-full px-4 py-2.5 bg-paper/50 border border-border rounded-xl text-foreground text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
            </div>
            <div class="w-full md:w-64">
                <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Category</label>
                <select name="category_id"
                    class="w-full px-4 py-2.5 bg-paper/50 border border-border rounded-xl text-foreground text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-primary hover:bg-primary/90 text-primary-foreground font-semibold px-5 py-2.5 rounded-xl text-sm transition shadow-xs">Filter</button>
            @if(request()->anyFilled(['search', 'category_id']))
                <a href="{{ route('admin.products.index') }}" class="text-muted-foreground hover:text-foreground text-sm font-medium px-4 py-2.5 transition">Clear</a>
            @endif
        </form>
    </div>

    <div class="bg-card rounded-2xl border border-border shadow-xs overflow-x-auto">
        <table class="min-w-full divide-y divide-border">
            <thead class="bg-paper/60">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Name</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Category</th>
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
                            <div class="flex items-center">
                                @if($product->main_image)
                                    <img class="h-10 w-10 rounded-lg object-cover mr-3 border border-border shadow-xs" src="{{ $product->main_image }}" alt="">
                                @else
                                    <div class="h-10 w-10 rounded-lg bg-paper text-muted-foreground border border-border flex items-center justify-center mr-3 text-[10px] font-mono">
                                        No Img</div>
                                @endif
                                <div class="text-sm font-medium text-foreground">{{ $product->name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                            {{ $product->category->name ?? 'Uncategorized' }}
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
