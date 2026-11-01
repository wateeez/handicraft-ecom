@extends('admin.layout')

@section('header')
    <h2 class="font-semibold text-xl text-truffle-extra-dark leading-tight">
        Edit Sub-Category
    </h2>
@endsection

@section('content')
    <div class="max-w-xl mx-auto bg-cream p-6 rounded-lg shadow">
        <form action="{{ route('admin.subcategories.update', $subcategory) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium text-truffle-extra-dark">Parent Category</label>
                <select name="category_id" id="edit_category_id"
                    class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                    required>
                    <option value="">Select Parent...</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $subcategory->category_id == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if($subcategory->parent_id)
                <div class="mb-4">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Parent Sub-Category</label>
                    <select name="parent_sub_category_id" id="edit_parent_sub_category_id"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                        required>
                        <option value="">Select Sub-Category...</option>
                        @foreach($categories as $cat)
                            @foreach($cat->flattenedSubCategories() as $row)
                                @if(!in_array($row['sub']->id, $excludedParentIds) && $row['depth'] <= $maxParentDepth)
                                    <option value="{{ $row['sub']->id }}" data-category="{{ $cat->id }}"
                                        {{ old('parent_sub_category_id', $subcategory->parent_id) == $row['sub']->id ? 'selected' : '' }}>
                                        {{ $row['path'] }}
                                    </option>
                                @endif
                            @endforeach
                        @endforeach
                    </select>
                    @error('parent_sub_category_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="mb-4">
                <label class="block text-sm font-medium text-truffle-extra-dark">{{ $subcategory->parent_id ? 'Child Sub-Category Name' : 'Sub-Category Name' }}</label>
                <input type="text" name="name" value="{{ $subcategory->name }}"
                    class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                    required>
            </div>

            <div class="flex justify-between">
                <a href="{{ route('admin.categories.index') }}" class="text-truffle-extra-dark hover:text-truffle-extra-dark">Cancel</a>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700">Update
                    Sub-Category</button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const categorySelect = document.getElementById('edit_category_id');
            const parentSelect = document.getElementById('edit_parent_sub_category_id');
            if (!parentSelect) return;

            // Only offer parent sub-categories from the chosen category.
            function filterParents() {
                Array.from(parentSelect.options).forEach(function (option) {
                    if (!option.value) return;
                    option.hidden = option.dataset.category !== categorySelect.value;
                });
                const selected = parentSelect.options[parentSelect.selectedIndex];
                if (selected && selected.hidden) parentSelect.value = '';
            }
            categorySelect.addEventListener('change', filterParents);
            filterParents();
        })();
    </script>
@endsection
