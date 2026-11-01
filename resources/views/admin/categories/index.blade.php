@extends('admin.layout')

@section('header')
    <h2 class="text-xl font-semibold text-foreground leading-tight">
        Manage Categories
    </h2>
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

        <!-- Add New Category -->
        <div class="bg-cream p-6 rounded-lg shadow h-fit">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Add New Category</h3>
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Category Name</label>
                    <input type="text" name="name"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                        required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-truffle-extra-dark">First Sub-Category</label>
                    <input type="text" name="first_sub_category" value="{{ old('first_sub_category') }}"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                        required>
                    <p class="text-xs text-truffle-extra-dark/70 mt-1">Every category needs at least one sub-category. You can add more later.</p>
                    @error('first_sub_category')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Description</label>
                    <textarea name="description" rows="2"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"></textarea>
                </div>
                @include('admin.partials.seo-fields', ['model' => null, 'hint' => 'category name and description', 'wrapperClass' => 'mb-4'])
                <button type="submit"
                    class="bg-green-premium text-white px-4 py-2 rounded shadow hover:bg-green-800 w-full">Create
                    Category</button>
            </form>
        </div>

        <!-- Add New Sub-Category -->
        <div class="bg-cream p-6 rounded-lg shadow h-fit">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Add Sub-Category</h3>
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Parent Category</label>
                    <select name="parent_id"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                        required>
                        <option value="">Select Parent...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Sub-Category Name</label>
                    <input type="text" name="name"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                        required>
                </div>
                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 w-full">Create
                    Sub-Category</button>
            </form>
        </div>

        <!-- Add New Child Sub-Category -->
        <div class="bg-cream p-6 rounded-lg shadow h-fit" id="add-child-sub-category">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Add Child Sub-Category</h3>
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf
                <input type="hidden" name="child" value="1">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Parent Category</label>
                    <select name="parent_id" id="child_category_id"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                        required>
                        <option value="">Select Parent...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('child') && old('parent_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Parent Sub-Category</label>
                    <select name="parent_sub_category_id" id="child_parent_sub_category_id"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                        required>
                        <option value="">Select Sub-Category...</option>
                        @foreach($categories as $cat)
                            @foreach($cat->flattenedSubCategories() as $row)
                                @if($row['depth'] < $maxDepth)
                                    <option value="{{ $row['sub']->id }}" data-category="{{ $cat->id }}" hidden>{{ $row['path'] }}</option>
                                @endif
                            @endforeach
                        @endforeach
                    </select>
                    @error('parent_sub_category_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-truffle-extra-dark">Child Sub-Category Name</label>
                    <input type="text" name="name"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"
                        required>
                </div>
                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 w-full">Create
                    Child Sub-Category</button>
            </form>
        </div>

    </div>

    <!-- List -->
    <div class="mt-8 bg-cream rounded-lg shadow overflow-hidden">
        <h3 class="text-lg font-medium p-6 border-b">Existing Categories</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#F5F2EA]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">
                            Sub-Categories</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-cream divide-y divide-gray-200">
                    @foreach($categories as $category)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-truffle-extra-dark">
                                {{ $category->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-truffle-extra-dark">
                                {{ $category->slug }}
                            </td>
                            <td class="px-6 py-4 text-sm text-truffle-extra-dark">
                                @if($category->subCategories->isNotEmpty())
                                    @include('admin.categories._sub-tree', ['category' => $category, 'maxDepth' => $maxDepth])
                                @else
                                    <span class="text-xs text-truffle-extra-dark/70">None</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.categories.edit', $category) }}"
                                    class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline-block"
                                    onsubmit="event.preventDefault(); openDeleteModal(this, 'Enter your password to delete this category.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function () {
            const categorySelect = document.getElementById('child_category_id');
            const parentSelect = document.getElementById('child_parent_sub_category_id');

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

            // "+" on a sub-category pre-fills the child form to add a child under it.
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-add-child-sub]');
                if (!btn) return;
                categorySelect.value = btn.dataset.addChildCategory;
                filterParents();
                parentSelect.value = btn.dataset.addChildSub;
                const form = document.getElementById('add-child-sub-category');
                form.scrollIntoView({ behavior: 'smooth', block: 'center' });
                form.querySelector('input[name="name"]').focus({ preventScroll: true });
            });
        })();
    </script>
@endsection
