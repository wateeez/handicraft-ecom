@can('edit-storefront-products')
    <button type="button"
        data-edit-product="{{ json_encode([
            'name' => $product->name,
            'price' => $product->price,
            'discount_price' => $product->discount_price,
            'description' => $product->description,
            'long_description' => $product->long_description,
            'is_order_now_enabled' => (bool) $product->is_order_now_enabled,
            'update_url' => route('admin.products.inline-update', $product),
            'dashboard_url' => route('admin.products.edit', $product),
            'page_url' => route('products.show', $product->slug ?? $product->id),
        ]) }}"
        class="{{ $class ?? '' }} inline-flex items-center gap-1.5 rounded-full bg-truffle-dark px-3 py-1.5 text-xs font-semibold text-white shadow-md hover:bg-primary transition"
        title="Edit this product">
        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M13.586 3.586a2 2 0 1 1 2.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 3 14.172V17h2.828l8.38-8.379-2.83-2.828Z"/></svg>
        Edit
    </button>
@endcan
