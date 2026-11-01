{{-- One row per sub-category, indented by depth. Expects $category and $maxDepth. --}}
@foreach($category->flattenedSubCategories() as $row)
    @php($sub = $row['sub'])
    <div class="flex items-center mb-1" style="padding-left: {{ ($row['depth'] - 1) * 1.25 }}rem">
        @if($row['depth'] > 1)
            <span class="text-truffle-extra-dark/50 mr-1" aria-hidden="true">└</span>
        @endif
        <div class="inline-flex items-center bg-[#F5F2EA] rounded-full px-3 py-1 text-xs font-semibold text-truffle-extra-dark">
            <span>{{ $sub->name }}</span>
            @if($row['depth'] < $maxDepth)
                <button type="button"
                    class="ml-2 text-green-700 hover:text-green-900 p-1 rounded-full hover:bg-[#E8E2D2]"
                    title="Add sub-category under {{ $sub->name }}"
                    data-add-child-category="{{ $category->id }}"
                    data-add-child-sub="{{ $sub->id }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </button>
            @endif
            <a href="{{ route('admin.subcategories.edit', $sub) }}"
               class="ml-1 text-indigo-600 hover:text-indigo-900 p-1 rounded-full hover:bg-[#E8E2D2]"
               title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            </a>
            <form action="{{ route('admin.subcategories.destroy', $sub) }}"
                  method="POST"
                  class="inline-block ml-1"
                  onsubmit="event.preventDefault(); openDeleteModal(this, 'Enter your password to delete this sub-category. Anything nested under it moves up one level.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600 hover:text-red-900 p-1 rounded-full hover:bg-[#E8E2D2]" title="Delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
@endforeach
