@extends('admin.layout')

@section('header')
    <h2 class="font-semibold text-xl text-truffle-extra-dark leading-tight">
        Edit Product: {{ $product->name }}
    </h2>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto bg-cream p-6 rounded-lg shadow">
        <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Info -->
                <div class="col-span-2">
                    <h3 class="text-lg font-medium border-b pb-2 mb-4">Basic Information</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">Product Name</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('name') border-red-500 @enderror"
                        required>
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">SKU (Unique Identifier)</label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('sku') border-red-500 @enderror"
                        required>
                    @error('sku')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">Category</label>
                    <select name="category_id" id="category_id"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('category_id') border-red-500 @enderror"
                        required onchange="updateSubCategories()">
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}
                                data-subcategories="{{ json_encode($category->subCategories) }}">
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">Sub-Category (Optional)</label>
                    <select name="sub_category_id" id="sub_category_id"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2">
                        <option value="">Select Sub-Category</option>
                    </select>
                </div>

                <script>
                function updateSubCategories() {
                    const categorySelect = document.getElementById('category_id');
                    const subCategorySelect = document.getElementById('sub_category_id');
                    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
                    const currentSubCategoryId = {{ $product->sub_category_id ?? 'null' }};
                    
                    // Clear existing options
                    subCategorySelect.innerHTML = '<option value="">Select Sub-Category</option>';
                    
                    if (selectedOption.value) {
                        const subCategories = JSON.parse(selectedOption.getAttribute('data-subcategories') || '[]');
                        subCategories.forEach(subCat => {
                            const option = document.createElement('option');
                            option.value = subCat.id;
                            option.textContent = subCat.name;
                            if (currentSubCategoryId && subCat.id == currentSubCategoryId) {
                                option.selected = true;
                            }
                            subCategorySelect.appendChild(option);
                        });
                    }
                }
                // Initialize on page load
                document.addEventListener('DOMContentLoaded', updateSubCategories);
                </script>

                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">Price ($)</label>
                    <input type="number" step="0.01" name="price" value="{{ $product->price }}"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">Discount Price ($) (Optional)</label>
                    <input type="number" step="0.01" name="discount_price" value="{{ $product->discount_price }}"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2">
                </div>


                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">Material (Optional)</label>
                    <input type="text" name="material" value="{{ old('material', $product->material) }}" placeholder="e.g., Wood, Cotton, Metal"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2">
                </div>

                <!-- Shipping -->
                <div class="col-span-2 mt-4">
                    <h3 class="text-lg font-medium border-b pb-2 mb-4">Shipping & Dimensions</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">Weight (kg)</label>
                    <input type="number" step="0.001" name="weight" value="{{ old('weight', $product->weight) }}"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('weight') border-red-500 @enderror"
                        required>
                    @error('weight')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark">Length (cm)</label>
                        <input type="number" step="0.01" name="length" value="{{ old('length', $product->length) }}"
                            class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('length') border-red-500 @enderror"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark">Width (cm)</label>
                        <input type="number" step="0.01" name="width" value="{{ old('width', $product->width) }}"
                            class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('width') border-red-500 @enderror"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark">Height (cm)</label>
                        <input type="number" step="0.01" name="height" value="{{ old('height', $product->height) }}"
                            class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('height') border-red-500 @enderror"
                            required>
                    </div>
                    @error('length') <p class="text-red-500 text-xs mt-1 col-span-1">{{ $message }}</p> @enderror
                    @error('width') <p class="text-red-500 text-xs mt-1 col-span-1">{{ $message }}</p> @enderror
                    @error('height') <p class="text-red-500 text-xs mt-1 col-span-1">{{ $message }}</p> @enderror
                </div>

                <!-- Media -->
                <div class="col-span-2 mt-4">
                    <h3 class="text-lg font-medium border-b pb-2 mb-4">Media & Details</h3>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Main Image</label>
                    <input type="file" name="main_image"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2">
                    @if($product->main_image)
                        <p class="text-xs text-truffle-extra-dark mt-1">Current: <a href="{{ $product->main_image }}" target="_blank"
                                class="text-blue-500 hover:underline">View Image</a></p>
                    @endif
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Additional Images (Multiple)</label>
                    <input type="file" name="images[]" multiple accept="image/*"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2">
                    <p class="text-xs text-truffle-extra-dark mt-1">You can select multiple images at once. New images will be added to existing ones.</p>
                    @if($product->images && count($product->images) > 0)
                        <div class="mt-2 flex gap-2 flex-wrap">
                            @foreach($product->images as $image)
                                <img src="{{ $image }}" alt="" class="h-16 w-16 object-cover rounded border">
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Short Description (Optional)</label>
                    <textarea name="description" rows="2"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                        placeholder="Brief product summary">{{ old('description', $product->description) }}</textarea>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Long Description</label>
                    <textarea name="long_description" rows="6"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                        placeholder="Detailed product description with formatting">{{ old('long_description', $product->long_description) }}</textarea>
                    <p class="text-xs text-truffle-extra-dark mt-1">This will be displayed prominently on the product page</p>
                </div>

                <!-- Settings -->
                <div class="col-span-2 mt-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_order_now_enabled" id="is_order_now_enabled" {{ $product->is_order_now_enabled ? 'checked' : '' }}
                            class="h-4 w-4 text-green-premium focus:ring-green-500 border-truffle-medium/30 rounded">
                        <label for="is_order_now_enabled" class="ml-2 block text-sm text-truffle-extra-dark">
                            Enable "Order Now" Button
                        </label>
                    </div>
                    <p class="text-xs text-truffle-extra-dark mt-1 ml-6">If disabled, only "Inquire" button will be shown.</p>
                </div>

                <!-- Carousel Options -->
                <div class="col-span-2 mt-6">
                    <h3 class="text-lg font-medium border-b pb-2 mb-4">Homepage Carousel Options</h3>
                    <p class="text-sm text-truffle-extra-dark mb-4">Select which carousels this product should appear in on the homepage.</p>
                </div>

                <div class="col-span-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_new_arrival" id="is_new_arrival" value="1"
                            class="h-4 w-4 text-green-premium focus:ring-green-500 border-truffle-medium/30 rounded"
                            {{ $product->is_new_arrival ? 'checked' : '' }}>
                        <label for="is_new_arrival" class="ml-2 block text-sm text-truffle-extra-dark">
                            New Arrival
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_featured" id="is_featured" value="1"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-truffle-medium/30 rounded"
                            {{ $product->is_featured ? 'checked' : '' }}>
                        <label for="is_featured" class="ml-2 block text-sm text-truffle-extra-dark">
                            Featured Product
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_recommended" id="is_recommended" value="1"
                            class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-truffle-medium/30 rounded"
                            {{ $product->is_recommended ? 'checked' : '' }}>
                        <label for="is_recommended" class="ml-2 block text-sm text-truffle-extra-dark">
                            Recommended
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_on_sale" id="is_on_sale" value="1"
                            class="h-4 w-4 text-red-600 focus:ring-red-500 border-truffle-medium/30 rounded"
                            {{ $product->is_on_sale ? 'checked' : '' }}>
                        <label for="is_on_sale" class="ml-2 block text-sm text-truffle-extra-dark">
                            On Sale
                        </label>
                    </div>
                </div>

                <div class="col-span-2 md:col-span-1 mt-4">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Carousel Priority</label>
                    <input type="number" name="carousel_priority" value="{{ old('carousel_priority', $product->carousel_priority ?? 0) }}" min="0"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2">
                    <p class="text-xs text-truffle-extra-dark mt-1">Higher priority products appear first in carousels (0 = default)</p>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <a href="{{ route('admin.products.index') }}"
                    class="bg-[#E8E2D2] text-truffle-extra-dark px-4 py-2 rounded mr-4">Cancel</a>
                <button type="submit" class="bg-green-premium text-white px-4 py-2 rounded shadow hover:bg-green-800">Update
                    Product</button>
            </div>
        </form>
    </div>
@endsection
