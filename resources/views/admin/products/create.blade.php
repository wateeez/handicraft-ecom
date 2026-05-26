@extends('admin.layout')

@section('header')
    <h2 class="font-semibold text-xl text-truffle-extra-dark leading-tight">
        Add New Product
    </h2>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto bg-cream p-6 rounded-lg shadow">
        @if($errors->any())
            <div class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <p class="font-semibold mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Info -->
                <div class="col-span-2">
                    <h3 class="text-lg font-medium border-b pb-2 mb-4">Basic Information</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">Product Name</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('name') border-red-500 @enderror"
                        required>
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">SKU (Unique Identifier)</label>
                    <input type="text" name="sku" value="{{ old('sku') }}"
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
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }} data-subcategories="{{ json_encode($category->subCategories) }}">{{ $category->name }}</option>
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
                function updateSubCategories(selectedSubCategoryId = null) {
                    const categorySelect = document.getElementById('category_id');
                    const subCategorySelect = document.getElementById('sub_category_id');
                    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
                    
                    // Clear existing options
                    subCategorySelect.innerHTML = '<option value="">Select Sub-Category</option>';
                    
                    if (selectedOption && selectedOption.value) {
                        const subCategories = JSON.parse(selectedOption.getAttribute('data-subcategories') || '[]');
                        subCategories.forEach(subCat => {
                            const option = document.createElement('option');
                            option.value = subCat.id;
                            option.textContent = subCat.name;
                            if (selectedSubCategoryId && subCat.id == selectedSubCategoryId) {
                                option.selected = true;
                            }
                            subCategorySelect.appendChild(option);
                        });
                    }
                }

                // Run on page load for old input
                document.addEventListener('DOMContentLoaded', function() {
                    const oldCategoryId = "{{ old('category_id') }}";
                    const oldSubCategoryId = "{{ old('sub_category_id') }}";
                    if (oldCategoryId) {
                        updateSubCategories(oldSubCategoryId);
                    }
                });
                </script>

                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">Price ($)</label>
                    <input type="number" step="0.01" name="price" value="{{ old('price') }}"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('price') border-red-500 @enderror"
                        required>
                    @error('price')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">Discount Price ($) (Optional)</label>
                    <input type="number" step="0.01" name="discount_price" value="{{ old('discount_price') }}"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2">
                </div>


                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">Material (Optional)</label>
                    <input type="text" name="material" value="{{ old('material') }}" placeholder="e.g., Wood, Cotton, Metal"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2">
                </div>

                <!-- Shipping -->
                <div class="col-span-2 mt-4">
                    <h3 class="text-lg font-medium border-b pb-2 mb-4">Shipping & Dimensions</h3>
                </div>

                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark">Weight (kg)</label>
                    <input type="number" step="0.001" name="weight" value="{{ old('weight') }}"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('weight') border-red-500 @enderror"
                        required>
                    @error('weight')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark">Length (cm)</label>
                        <input type="number" step="0.01" name="length" value="{{ old('length') }}"
                            class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('length') border-red-500 @enderror"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark">Width (cm)</label>
                        <input type="number" step="0.01" name="width" value="{{ old('width') }}"
                            class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('width') border-red-500 @enderror"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark">Height (cm)</label>
                        <input type="number" step="0.01" name="height" value="{{ old('height') }}"
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
                    <input type="file" name="main_image" accept="image/*"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('main_image') border-red-500 @enderror">
                    <p class="text-xs text-truffle-extra-dark mt-1">Accepted formats: JPG, PNG, GIF, WebP &mdash; max 2 MB</p>
                    @error('main_image')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Additional Images (Multiple)</label>
                    <input type="file" name="images[]" multiple accept="image/*"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2 @error('images') border-red-500 @enderror">
                    <p class="text-xs text-truffle-extra-dark mt-1">You can select multiple images at once &mdash; max 2 MB each</p>
                    @error('images.*')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Short Description (Optional)</label>
                    <textarea name="description" rows="2"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                        placeholder="Brief product summary">{{ old('description') }}</textarea>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Long Description</label>
                    <textarea name="long_description" rows="6"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                        placeholder="Detailed product description with formatting">{{ old('long_description') }}</textarea>
                    <p class="text-xs text-truffle-extra-dark mt-1">This will be displayed prominently on the product page</p>
                </div>

                <!-- Settings -->
                <div class="col-span-2 mt-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_order_now_enabled" id="is_order_now_enabled"
                            class="h-4 w-4 text-green-premium focus:ring-green-500 border-truffle-medium/30 rounded" checked>
                        <label for="is_order_now_enabled" class="ml-2 block text-sm text-truffle-extra-dark">
                            Enable "Order Now" Button (User can purchase directly)
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
                            {{ old('is_new_arrival') ? 'checked' : '' }}>
                        <label for="is_new_arrival" class="ml-2 block text-sm text-truffle-extra-dark">
                            New Arrival
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_featured" id="is_featured" value="1"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-truffle-medium/30 rounded"
                            {{ old('is_featured') ? 'checked' : '' }}>
                        <label for="is_featured" class="ml-2 block text-sm text-truffle-extra-dark">
                            Featured Product
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_recommended" id="is_recommended" value="1"
                            class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-truffle-medium/30 rounded"
                            {{ old('is_recommended') ? 'checked' : '' }}>
                        <label for="is_recommended" class="ml-2 block text-sm text-truffle-extra-dark">
                            Recommended
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_on_sale" id="is_on_sale" value="1"
                            class="h-4 w-4 text-red-600 focus:ring-red-500 border-truffle-medium/30 rounded"
                            {{ old('is_on_sale') ? 'checked' : '' }}>
                        <label for="is_on_sale" class="ml-2 block text-sm text-truffle-extra-dark">
                            On Sale
                        </label>
                    </div>
                </div>

                <div class="col-span-2 md:col-span-1 mt-4">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Carousel Priority</label>
                    <input type="number" name="carousel_priority" value="{{ old('carousel_priority', 0) }}" min="0"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2">
                    <p class="text-xs text-truffle-extra-dark mt-1">Higher priority products appear first in carousels (0 = default)</p>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <a href="{{ route('admin.products.index') }}"
                    class="bg-[#E8E2D2] text-truffle-extra-dark px-4 py-2 rounded mr-4">Cancel</a>
                <button type="submit" class="bg-green-premium text-white px-4 py-2 rounded shadow hover:bg-green-800">Save
                    Product</button>
            </div>
        </form>
    </div>
@endsection
