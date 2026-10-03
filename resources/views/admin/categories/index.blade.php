@extends('admin.layout')

@section('header')
    <h2 class="text-xl font-semibold text-foreground leading-tight">
        Manage Categories
    </h2>
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

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
                    <label class="block text-sm font-medium text-truffle-extra-dark">Description</label>
                    <textarea name="description" rows="2"
                        class="mt-1 block w-full rounded-md border-truffle-medium/30 shadow-sm focus:border-green-500 focus:ring-green-500 border p-2"></textarea>
                </div>
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
                                @forelse($category->subCategories as $sub)
                                    <div class="inline-flex items-center bg-[#F5F2EA] rounded-full px-3 py-1 text-xs font-semibold text-truffle-extra-dark mr-2 mb-1">
                                        <span>{{ $sub->name }}</span>
                                        <a href="{{ route('admin.subcategories.edit', $sub) }}" 
                                           class="ml-2 text-indigo-600 hover:text-indigo-900 p-1 rounded-full hover:bg-[#E8E2D2]" 
                                           title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.subcategories.destroy', $sub) }}" 
                                              method="POST" 
                                              class="inline-block ml-1"
                                              onsubmit="event.preventDefault(); openDeleteModal(this, 'Enter your password to delete this sub-category.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 p-1 rounded-full hover:bg-[#E8E2D2]" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <span class="text-xs text-truffle-extra-dark/70">None</span>
                                @endforelse
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
@endsection
