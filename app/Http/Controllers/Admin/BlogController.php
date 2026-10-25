<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::with('author');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }

        $posts = $query->latest()->paginate(10);

        return view('admin.blog.index', compact('posts'));
    }

    public function create()
    {
        return view('admin.blog.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|image|max:2048',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0',
        ]);

        $data = $request->all();
        $data['slug'] = BlogPost::generateSlug($request->title);
        $data['author_id'] = Auth::id();
        $data['is_published'] = $request->has('is_published');
        $data['priority'] = $request->input('priority', 0);

        if ($data['is_published'] && !$request->filled('published_at')) {
            $data['published_at'] = now();
        } elseif ($request->filled('published_at')) {
            $data['published_at'] = $request->published_at;
        }

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('blog', \App\Models\Product::mediaDisk());
            $data['featured_image'] = $path; // store path; media_url() resolves for display
        }

        BlogPost::create($data);

        return redirect()->route('admin.blog.index')->with('success', 'Blog post created successfully.');
    }

    public function edit(BlogPost $blog)
    {
        return view('admin.blog.edit', compact('blog'));
    }

    public function update(Request $request, BlogPost $blog)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|image|max:2048',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0',
        ]);

        $data = $request->all();
        
        // Update slug if title changed
        if ($request->title !== $blog->title || $blog->slug === '') {
            $data['slug'] = BlogPost::generateSlug($request->title, $blog->id);
        }

        $data['is_published'] = $request->has('is_published');
        $data['priority'] = $request->input('priority', 0);

        // Handle publish date
        if ($data['is_published'] && !$blog->published_at && !$request->filled('published_at')) {
            $data['published_at'] = now();
        } elseif ($request->filled('published_at')) {
            $data['published_at'] = $request->published_at;
        }

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image if it exists and is a relative path (not a legacy full URL)
            $rawPath = $blog->getRawOriginal('featured_image');
            if ($rawPath && ! filter_var($rawPath, FILTER_VALIDATE_URL)) {
                Storage::disk(\App\Models\Product::mediaDisk())->delete($rawPath);
            }
            $path = $request->file('featured_image')->store('blog', \App\Models\Product::mediaDisk());
            $data['featured_image'] = $path;
        }

        // Handle image removal
        if ($request->has('remove_featured_image')) {
            $rawPath = $blog->getRawOriginal('featured_image');
            if ($rawPath && ! filter_var($rawPath, FILTER_VALIDATE_URL)) {
                Storage::disk(\App\Models\Product::mediaDisk())->delete($rawPath);
            }
            $data['featured_image'] = null;
        }

        $blog->update($data);

        return redirect()->route('admin.blog.index')->with('success', 'Blog post updated successfully.');
    }

    public function destroy(BlogPost $blog)
    {
        // Delete featured image if it's a relative path (not a legacy full URL)
        $rawPath = $blog->getRawOriginal('featured_image');
        if ($rawPath && ! filter_var($rawPath, FILTER_VALIDATE_URL)) {
            Storage::disk(\App\Models\Product::mediaDisk())->delete($rawPath);
        }

        $blog->delete();

        return redirect()->route('admin.blog.index')->with('success', 'Blog post deleted successfully.');
    }
}
