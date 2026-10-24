<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('subCategories')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function show($id)
    {
        // Try to find the category
        $category = Category::find($id);
        
        // If category doesn't exist, abort with 404
        if (!$category) {
            abort(404, 'Category not found');
        }
        
        // Categories don't have individual show pages, redirect to index
        return redirect()->route('admin.categories.index');
    }

    public function store(Request $request)
    {
        // Handle SubCategory Store (if creating sub) or Main Category
        if ($request->has('parent_id') && $request->parent_id) {
            $request->validate(['name' => 'required', 'parent_id' => 'exists:categories,id']);
            SubCategory::create([
                'category_id' => $request->parent_id,
                'name' => $request->name,
                'slug' => Str::slug($request->name)
            ]);
            return back()->with('success', 'Sub-Category created.');
        } else {
            $request->validate([
                'name' => 'required',
                'meta_title' => 'nullable|string|max:70',
                'meta_description' => 'nullable|string|max:200',
            ]);
            Category::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
            ]);
            return redirect()->route('admin.categories.index')->with('success', 'Category created.');
        }
    }

    // Simplified for this task: We'll assume editing is simple or just show index
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:200',
        ]);
        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
        ]);
        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }

    // Sub-Category Methods
    public function editSubCategory(SubCategory $subcategory)
    {
        $categories = Category::all();
        return view('admin.categories.edit-sub', compact('subcategory', 'categories'));
    }

    public function updateSubCategory(Request $request, SubCategory $subcategory)
    {
        $request->validate([
            'name' => 'required',
            'category_id' => 'required|exists:categories,id'
        ]);
        
        $subcategory->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'category_id' => $request->category_id
        ]);
        
        return redirect()->route('admin.categories.index')->with('success', 'Sub-Category updated.');
    }

    public function destroySubCategory(SubCategory $subcategory)
    {
        $subcategory->delete();
        return back()->with('success', 'Sub-Category deleted.');
    }
}
