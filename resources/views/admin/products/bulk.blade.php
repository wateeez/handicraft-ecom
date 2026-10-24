@extends('admin.layout')

@section('header')
    <div>
    <h2 class="text-xl font-semibold text-foreground leading-tight">
        Bulk Upload
    </h2>
    <p class="text-xs text-muted-foreground mt-0.5">Add or update many products at once: import details first, then attach images</p>
    </div>
@endsection

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to products
        </a>
    </div>

    @if($errors->any())
    <div class="bg-red-500/10 border border-red-500/30 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="bg-card rounded-2xl border border-border shadow-xs overflow-hidden">
        <div>
            <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-border">

                <!-- Step 1: CSV -->
                <div class="p-6 space-y-5">
                    <div class="flex items-start gap-3">
                        <span class="w-7 h-7 rounded-full bg-primary text-primary-foreground text-sm font-semibold flex items-center justify-center flex-shrink-0">1</span>
                        <div>
                            <h3 class="font-semibold text-foreground leading-tight">Product details (CSV)</h3>
                            <p class="text-sm text-muted-foreground mt-0.5">Import or export product information. Images are added in step 2.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <a href="{{ route('admin.products.template') }}"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-border bg-paper/50 text-foreground text-sm font-medium hover:bg-paper hover:border-primary/40 transition">
                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M5 20h14a2 2 0 002-2V8l-5-5H7a2 2 0 00-2 2v13a2 2 0 002 2z"/></svg>
                            Download template
                        </a>
                        <a href="{{ route('admin.products.export') }}"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-border bg-paper/50 text-foreground text-sm font-medium hover:bg-paper hover:border-primary/40 transition">
                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/></svg>
                            Export products
                        </a>
                    </div>

                    <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data"
                        x-data="{ file: null, over: false }" class="space-y-3">
                        @csrf
                        <label @dragover.prevent="over = true" @dragleave.prevent="over = false"
                            @drop.prevent="over = false; $refs.input.files = $event.dataTransfer.files; file = $refs.input.files[0]?.name"
                            :class="over ? 'border-primary bg-primary/5' : 'border-border bg-paper/50 hover:border-primary/40'"
                            class="flex flex-col items-center justify-center gap-1 text-center px-4 py-6 rounded-xl border-2 border-dashed cursor-pointer transition">
                            <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 13h6m-6 4h6M7 21h10a2 2 0 002-2V9l-5-5H7a2 2 0 00-2 2v13a2 2 0 002 2z"/></svg>
                            <span class="text-sm font-medium text-foreground" x-text="file ?? 'Drop your CSV here or click to browse'"></span>
                            <span class="text-xs text-muted-foreground">.csv files only</span>
                            <input x-ref="input" type="file" name="products_file" accept=".csv" required class="sr-only"
                                @change="file = $event.target.files[0]?.name">
                        </label>
                        <button type="submit"
                            class="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-semibold px-4 py-2.5 rounded-xl text-sm shadow-xs transition">
                            Import products
                        </button>
                    </form>

                    <div class="rounded-xl bg-brass/10 border border-brass/30 px-4 py-3 text-xs text-foreground space-y-1">
                        <p><span class="font-semibold">Required columns:</span> name, sku, category, price, weight, length, width, height</p>
                        <p><span class="font-semibold">Category:</span> use the exact category name from your system</p>
                        <p><span class="font-semibold">Yes/no fields:</span> use true/false or 1/0</p>
                    </div>
                </div>

                <!-- Step 2: Images -->
                <div class="p-6 space-y-5">
                    <div class="flex items-start gap-3">
                        <span class="w-7 h-7 rounded-full bg-primary text-primary-foreground text-sm font-semibold flex items-center justify-center flex-shrink-0">2</span>
                        <div>
                            <h3 class="font-semibold text-foreground leading-tight">Product images (ZIP)</h3>
                            <p class="text-sm text-muted-foreground mt-0.5">Upload one ZIP of images named after your product SKUs.</p>
                        </div>
                    </div>

                    <form action="{{ route('admin.products.bulk-images') }}" method="POST" enctype="multipart/form-data"
                        x-data="{ file: null, over: false }" class="space-y-3">
                        @csrf
                        <label @dragover.prevent="over = true" @dragleave.prevent="over = false"
                            @drop.prevent="over = false; $refs.input.files = $event.dataTransfer.files; file = $refs.input.files[0]?.name"
                            :class="over ? 'border-primary bg-primary/5' : 'border-border bg-paper/50 hover:border-primary/40'"
                            class="flex flex-col items-center justify-center gap-1 text-center px-4 py-6 rounded-xl border-2 border-dashed cursor-pointer transition">
                            <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-sm font-medium text-foreground" x-text="file ?? 'Drop your ZIP here or click to browse'"></span>
                            <span class="text-xs text-muted-foreground">.zip files only, up to 50MB</span>
                            <input x-ref="input" type="file" name="images_zip" accept=".zip" required class="sr-only"
                                @change="file = $event.target.files[0]?.name">
                        </label>
                        <button type="submit"
                            class="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-semibold px-4 py-2.5 rounded-xl text-sm shadow-xs transition">
                            Upload images
                        </button>
                    </form>

                    <div class="rounded-xl bg-paper/60 border border-border px-4 py-3 text-xs text-foreground">
                        <p class="font-semibold mb-2">How to name your images</p>
                        <ul class="space-y-1.5">
                            <li class="flex items-center justify-between gap-3"><code class="bg-card border border-border px-1.5 py-0.5 rounded">SKU.jpg</code><span class="text-muted-foreground">Main image</span></li>
                            <li class="flex items-center justify-between gap-3"><code class="bg-card border border-border px-1.5 py-0.5 rounded">SKU_0.jpg</code><span class="text-muted-foreground">Second image</span></li>
                            <li class="flex items-center justify-between gap-3"><code class="bg-card border border-border px-1.5 py-0.5 rounded">SKU_1.jpg, SKU_2.jpg…</code><span class="text-muted-foreground">Third image onwards</span></li>
                        </ul>
                        <p class="mt-2 text-muted-foreground">Example: CH001.jpg, CH001_0.jpg, CH001_1.png, CH001_2.jpg</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
