<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return redirect()->route('home');
    }

    public function show($slug)
    {
        // Try slug first, then id fallback
        $product = Product::where('slug', $slug)->first();
        if (!$product) {
            $product = Product::find($slug);
        }
        if (!$product)
            abort(404);

        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(3)
            ->get();

        return view('frontend.products.show', compact('product', 'relatedProducts'));
    }

    public function category(Category $category)
    {
        $category->load('subCategories');

        $products = $category->products()
            ->latest()
            ->paginate(16)
            ->withQueryString();

        return view('frontend.categories.show', compact('category', 'products'));
    }
}
