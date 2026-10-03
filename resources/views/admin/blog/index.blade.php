@extends('admin.layout')

@section('header')
    <h2 class="text-xl font-semibold text-foreground leading-tight">
        Manage Blog Posts
    </h2>
@endsection

@section('content')
<div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center mb-6">
    <h3 class="text-lg font-medium">All Posts</h3>
    <a href="{{ route('admin.blog.create') }}" class="bg-green-premium text-white px-4 py-2 rounded-lg hover:bg-green-800 transition flex items-center justify-center gap-2 w-full sm:w-auto">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Post
    </a>
</div>

<div class="bg-cream rounded-lg shadow-sm">
    <!-- Filters -->
    <div class="p-4 border-b border-truffle-medium/30">
        <form action="{{ route('admin.blog.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="Search posts..."
                    class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>
            <div>
                <select name="status" class="px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                </select>
            </div>
            <button type="submit" class="bg-[#F5F2EA] text-truffle-extra-dark px-4 py-2 rounded-lg hover:bg-[#E8E2D2] transition">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.blog.index') }}" class="text-truffle-extra-dark px-4 py-2 hover:text-truffle-extra-dark">Clear</a>
            @endif
        </form>
    </div>

    <!-- Posts Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#F5F2EA]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Post</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Author</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Published</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Priority</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-truffle-extra-dark uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($posts as $post)
                    <tr class="hover:bg-[#F5F2EA]">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                @if($post->featured_image)
                                    <img src="{{ $post->featured_image }}" alt="" class="w-16 h-12 object-cover rounded">
                                @else
                                    <div class="w-16 h-12 bg-[#E8E2D2] rounded flex items-center justify-center">
                                        <svg class="w-6 h-6 text-truffle-extra-dark/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <h3 class="font-medium text-truffle-extra-dark">{{ Str::limit($post->title, 50) }}</h3>
                                    <p class="text-sm text-truffle-extra-dark">{{ Str::limit($post->short_excerpt, 60) }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-truffle-extra-dark">
                            {{ $post->author->name ?? 'Unknown' }}
                        </td>
                        <td class="px-6 py-4">
                            @if($post->is_published)
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-premium/20 text-green-800">Published</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Draft</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-truffle-extra-dark">
                            {{ $post->published_at ? $post->published_at->format('M d, Y') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-truffle-extra-dark">
                            {{ $post->priority }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('blog.show', $post->slug) }}" target="_blank" 
                                    class="text-truffle-extra-dark hover:text-truffle-extra-dark" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.blog.edit', $post) }}" 
                                    class="text-blue-500 hover:text-blue-700" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <button type="button" onclick="confirmDelete({{ $post->id }})" 
                                    class="text-red-500 hover:text-red-700" title="Delete">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                <form id="delete-form-{{ $post->id }}" action="{{ route('admin.blog.destroy', $post) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-truffle-extra-dark">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                            </svg>
                            <p>No blog posts found.</p>
                            <a href="{{ route('admin.blog.create') }}" class="text-green-premium hover:underline mt-2 inline-block">Create your first post</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($posts->hasPages())
        <div class="p-4 border-t border-truffle-medium/30">
            {{ $posts->links() }}
        </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-cream rounded-lg p-6 max-w-sm mx-4">
        <h3 class="text-lg font-semibold text-truffle-extra-dark mb-4">Delete Blog Post</h3>
        <p class="text-truffle-extra-dark mb-4">Enter your password to confirm deletion:</p>
        <input type="password" id="deletePassword" class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg mb-4" placeholder="Your password">
        <p id="deleteError" class="text-red-500 text-sm mb-4 hidden">Invalid password</p>
        <div class="flex justify-end gap-3">
            <button onclick="closeDeleteModal()" class="px-4 py-2 text-truffle-extra-dark hover:text-truffle-extra-dark">Cancel</button>
            <button onclick="submitDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Delete</button>
        </div>
    </div>
</div>

<script>
let deletePostId = null;

function confirmDelete(postId) {
    deletePostId = postId;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
    document.getElementById('deletePassword').value = '';
    document.getElementById('deleteError').classList.add('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
    deletePostId = null;
}

function submitDelete() {
    const password = document.getElementById('deletePassword').value;
    
    fetch('{{ route("admin.verify-password") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ password: password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            document.getElementById('delete-form-' + deletePostId).submit();
        } else {
            document.getElementById('deleteError').classList.remove('hidden');
        }
    });
}
</script>
@endsection
