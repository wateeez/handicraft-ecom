@extends('admin.layout')

@section('header')
    <h2 class="text-2xl font-semibold text-truffle-extra-dark">Edit Blog Post</h2>
@endsection

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.blog.update', $blog) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-cream rounded-lg shadow-sm">
            <!-- Basic Info -->
            <div class="p-6 border-b border-truffle-medium/30">
                <h3 class="text-lg font-semibold text-truffle-extra-dark mb-4">Post Content</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Title *</label>
                        <input type="text" name="title" value="{{ old('title', $blog->title) }}" required
                            class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            placeholder="Enter post title">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Slug</label>
                        <input type="text" value="{{ $blog->slug }}" disabled
                            class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg bg-[#F5F2EA] text-truffle-extra-dark">
                        <p class="text-xs text-truffle-extra-dark mt-1">URL: {{ route('blog.show', $blog->slug) }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Excerpt (Short Summary)</label>
                        <textarea name="excerpt" rows="2"
                            class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            placeholder="Brief summary for listings and search results (max 500 chars)">{{ old('excerpt', $blog->excerpt) }}</textarea>
                        @error('excerpt')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Content *</label>
                        <textarea name="content" rows="15" required
                            class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            placeholder="Write your blog post content here...">{{ old('content', $blog->content) }}</textarea>
                        @error('content')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-truffle-extra-dark mt-1">Tip: Use blank lines to separate paragraphs.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Featured Image</label>
                        @if($blog->featured_image)
                            <div class="mb-3 p-4 bg-[#F5F2EA] rounded-lg border border-truffle-medium/30">
                                <img src="{{ asset($blog->featured_image) }}" alt="" class="w-48 h-32 object-cover rounded mb-2">
                                <label class="flex items-center text-sm text-red-600 cursor-pointer">
                                    <input type="checkbox" name="remove_featured_image" value="1" class="mr-2">
                                    Remove current image
                                </label>
                            </div>
                        @endif
                        <input type="file" name="featured_image" accept="image/*"
                            class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        @error('featured_image')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-truffle-extra-dark mt-1">Recommended: 1200x630px for optimal social sharing</p>
                    </div>
                </div>
            </div>

            <!-- SEO Settings -->
            <div class="p-6 border-b border-truffle-medium/30">
                <h3 class="text-lg font-semibold text-truffle-extra-dark mb-4">SEO Settings</h3>
                <p class="text-sm text-truffle-extra-dark mb-4">Optimize your post for search engines. Leave blank to use defaults.</p>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Meta Title</label>
                        <input type="text" name="meta_title" value="{{ old('meta_title', $blog->meta_title) }}" maxlength="70"
                            class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            placeholder="SEO title (max 70 characters)">
                        @error('meta_title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-truffle-extra-dark mt-1">If empty, the post title will be used.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Meta Description</label>
                        <textarea name="meta_description" rows="2" maxlength="160"
                            class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            placeholder="SEO description (max 160 characters)">{{ old('meta_description', $blog->meta_description) }}</textarea>
                        @error('meta_description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-truffle-extra-dark mt-1">If empty, the excerpt will be used.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Meta Keywords</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $blog->meta_keywords) }}"
                            class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            placeholder="keyword1, keyword2, keyword3">
                        @error('meta_keywords')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Publish Settings -->
            <div class="p-6 border-b border-truffle-medium/30">
                <h3 class="text-lg font-semibold text-truffle-extra-dark mb-4">Publishing</h3>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_published" id="is_published" value="1"
                            class="h-4 w-4 text-green-premium focus:ring-green-500 border-truffle-medium/30 rounded"
                            {{ $blog->is_published ? 'checked' : '' }}>
                        <label for="is_published" class="ml-2 block text-sm text-truffle-extra-dark">
                            Publish this post
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Publish Date</label>
                        <input type="datetime-local" name="published_at" 
                            value="{{ old('published_at', $blog->published_at ? $blog->published_at->format('Y-m-d\TH:i') : '') }}"
                            class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <p class="text-xs text-truffle-extra-dark mt-1">Schedule for future or leave empty to publish now.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Priority</label>
                        <input type="number" name="priority" value="{{ old('priority', $blog->priority) }}" min="0"
                            class="w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <p class="text-xs text-truffle-extra-dark mt-1">Higher priority posts appear first (0 = default)</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="p-6 bg-[#F5F2EA] flex justify-end gap-4">
                <a href="{{ route('admin.blog.index') }}" class="px-4 py-2 text-truffle-extra-dark hover:text-truffle-extra-dark">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-green-premium text-white rounded-lg hover:bg-green-800 transition">
                    Update Post
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
