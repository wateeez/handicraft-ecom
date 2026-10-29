@can('edit-storefront-products')
    <button type="button"
        data-edit-product="{{ json_encode([
            'name' => $product->name,
            'price' => $product->price,
            'discount_price' => $product->discount_price,
            'description' => $product->description,
            'long_description' => $product->long_description,
            'is_order_now_enabled' => (bool) $product->is_order_now_enabled,
            'update_url' => route('storefront.products.update', $product),
            'dashboard_url' => route('admin.products.edit', $product),
            'page_url' => route('products.show', $product->slug ?? $product->id),
        ]) }}"
        class="{{ $class ?? '' }} inline-flex items-center gap-1.5 rounded-full border border-white/60 bg-white/30 px-3 py-1.5 text-xs font-semibold text-truffle-dark backdrop-blur-md backdrop-saturate-150 shadow-[inset_0_1px_0_rgba(255,255,255,0.7),inset_0_-1px_0_rgba(0,0,0,0.05),0_4px_16px_rgba(0,0,0,0.12)] hover:bg-white/50 hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_6px_20px_rgba(0,0,0,0.16)] transition"
        title="Edit this product">
        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M13.586 3.586a2 2 0 1 1 2.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 3 14.172V17h2.828l8.38-8.379-2.83-2.828Z"/></svg>
        Edit
    </button>
@endcan
