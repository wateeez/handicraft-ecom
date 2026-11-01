<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('subCategories')->get();
        $maxDepth = SubCategory::MAX_DEPTH;
        return view('admin.categories.index', compact('categories', 'maxDepth'));
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
            $request->validate([
                'name' => 'required',
                'parent_id' => 'exists:categories,id',
                // The "Add Child Sub-Category" form sends child=1 and must name a parent sub-category.
                'parent_sub_category_id' => 'nullable|required_if:child,1|exists:sub_categories,id',
            ], [
                'parent_sub_category_id.required_if' => 'Please choose the parent sub-category.',
            ]);

            $parentSub = $request->parent_sub_category_id
                ? SubCategory::find($request->parent_sub_category_id)
                : null;
            $this->assertValidParent($parentSub, (int) $request->parent_id);

            SubCategory::create([
                'category_id' => $request->parent_id,
                'parent_id' => $parentSub?->id,
                'name' => $request->name,
                'slug' => SubCategory::uniqueSlug($request->name, $parentSub),
            ]);
            return back()->with('success', 'Sub-Category created.');
        } else {
            // Every category must have at least one sub-category, so the first one is created with it.
            $request->validate([
                'name' => 'required',
                'first_sub_category' => 'required|string|max:255',
                'meta_title' => 'nullable|string|max:70',
                'meta_description' => 'nullable|string|max:200',
            ], [
                'first_sub_category.required' => 'Please enter the first sub-category for this category.',
            ]);
            DB::transaction(function () use ($request) {
                $category = Category::create([
                    'name' => $request->name,
                    'slug' => Str::slug($request->name),
                    'description' => $request->description,
                    'meta_title' => $request->meta_title,
                    'meta_description' => $request->meta_description,
                ]);
                SubCategory::create([
                    'category_id' => $category->id,
                    'name' => $request->first_sub_category,
                    'slug' => SubCategory::uniqueSlug($request->first_sub_category),
                ]);
            });
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
        $categories = Category::with('subCategories')->get();
        // A sub-category cannot be moved under itself or one of its own children.
        $excludedParentIds = $subcategory->selfAndDescendantIds();
        $maxParentDepth = SubCategory::MAX_DEPTH - $subcategory->subtreeHeight();
        return view('admin.categories.edit-sub', compact('subcategory', 'categories', 'excludedParentIds', 'maxParentDepth'));
    }

    public function updateSubCategory(Request $request, SubCategory $subcategory)
    {
        // A child keeps a (required) parent sub-category; a top-level sub-category never gets one.
        $isChild = $subcategory->parent_id !== null;
        $request->validate([
            'name' => 'required',
            'category_id' => 'required|exists:categories,id',
            'parent_sub_category_id' => [$isChild ? 'required' : 'nullable', 'exists:sub_categories,id'],
        ], [
            'parent_sub_category_id.required' => 'Please choose the parent sub-category.',
        ]);

        $parentSub = $isChild ? SubCategory::find($request->parent_sub_category_id) : null;
        $this->assertValidParent($parentSub, (int) $request->category_id, $subcategory);
        if ((int) $request->category_id !== (int) $subcategory->category_id
            && $error = $this->lastSubCategoryError($subcategory, 'moved to another category', $subcategory->selfAndDescendantIds())) {
            return back()->withInput()->with('error', $error);
        }

        DB::transaction(function () use ($request, $subcategory, $parentSub) {
            $nameOrParentChanged = $subcategory->name !== $request->name
                || (int) $subcategory->parent_id !== (int) $parentSub?->id;

            $subcategory->update([
                'name' => $request->name,
                'slug' => $nameOrParentChanged
                    ? SubCategory::uniqueSlug($request->name, $parentSub, $subcategory->id)
                    : $subcategory->slug,
                'category_id' => $request->category_id,
                'parent_id' => $parentSub?->id,
            ]);

            // Keep the whole branch, and the products filed in it, under the same category.
            $branchIds = $subcategory->selfAndDescendantIds();
            SubCategory::whereIn('id', $branchIds)->update(['category_id' => $request->category_id]);
            \App\Models\Product::whereIn('sub_category_id', $branchIds)->update(['category_id' => $request->category_id]);
        });

        return redirect()->route('admin.categories.index')->with('success', 'Sub-Category updated.');
    }

    public function destroySubCategory(SubCategory $subcategory)
    {
        // Children move up on delete, so they still count towards the category.
        if ($error = $this->lastSubCategoryError($subcategory, 'deleted', [$subcategory->id])) {
            return back()->with('error', $error);
        }
        $subcategory->delete();
        return back()->with('success', 'Sub-Category deleted.');
    }

    /**
     * A category must always keep at least one sub-category once $leavingIds are gone;
     * returns why not, or null if allowed.
     */
    private function lastSubCategoryError(SubCategory $subcategory, string $action, array $leavingIds): ?string
    {
        $hasOthers = SubCategory::where('category_id', $subcategory->category_id)
            ->whereNotIn('id', $leavingIds)
            ->exists();

        return $hasOthers
            ? null
            : "'{$subcategory->name}' is the only sub-category in its category, so it cannot be {$action}. Add another sub-category first.";
    }

    /**
     * The parent sub-category must be in the same category, must not be the
     * sub-category itself or one of its descendants, and must leave the branch
     * within SubCategory::MAX_DEPTH levels.
     */
    private function assertValidParent(?SubCategory $parent, int $categoryId, ?SubCategory $subcategory = null): void
    {
        if (!$parent) {
            return;
        }

        $fail = fn (string $message) => throw ValidationException::withMessages(['parent_sub_category_id' => $message]);

        if ((int) $parent->category_id !== $categoryId) {
            $fail('The parent sub-category must belong to the selected category.');
        }

        if ($subcategory && in_array($parent->id, $subcategory->selfAndDescendantIds(), true)) {
            $fail('A sub-category cannot be placed under itself or one of its own sub-categories.');
        }

        $branchHeight = $subcategory ? $subcategory->subtreeHeight() : 1;
        if ($parent->depth() + $branchHeight > SubCategory::MAX_DEPTH) {
            $fail('Sub-categories can only be nested ' . SubCategory::MAX_DEPTH . ' levels deep.');
        }
    }
}
