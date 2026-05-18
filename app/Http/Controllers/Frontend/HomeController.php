<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Cache filter-tab counts for 60 seconds to avoid repeated DB hits
        // that cause 504 timeouts on cold starts / high-latency PostgreSQL connections.
        $newArrivalsCount = Cache::remember('count_new_arrivals', 60, fn () =>
            Product::where('is_new_arrival', true)->count()
        );
        $featuredCount = Cache::remember('count_featured', 60, fn () =>
            Product::where('is_featured', true)->count()
        );
        $recommendedCount = Cache::remember('count_recommended', 60, fn () =>
            Product::where('is_recommended', true)->count()
        );
        $onSaleCount = Cache::remember('count_on_sale', 60, fn () =>
            Product::where('is_on_sale', true)->whereNotNull('discount_price')->count()
        );

        $query = Product::query();

        // Apply filter based on tab selection
        $filter = $request->get('filter');
        switch ($filter) {
            case 'new-arrivals':
                $query->where('is_new_arrival', true)->orderBy('carousel_priority', 'desc');
                break;
            case 'featured':
                $query->where('is_featured', true)->orderBy('carousel_priority', 'desc');
                break;
            case 'recommended':
                $query->where('is_recommended', true)->orderBy('carousel_priority', 'desc');
                break;
            case 'on-sale':
                $query->where('is_on_sale', true)->whereNotNull('discount_price')->orderBy('carousel_priority', 'desc');
                break;
            case 'discounted':
                $query->whereNotNull('discount_price')
                    ->whereColumn('discount_price', '<', 'price')
                    ->orderBy('carousel_priority', 'desc');
                break;
            case 'most-sold':
                // No orders table exists currently; using inquiries count as a proxy for popularity.
                $query->withCount('inquiries')->orderByDesc('inquiries_count');
                break;
            default:
                // All products - no special filter
                break;
        }

        // Filter by Category
        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filter by SubCategory
        if ($request->has('subcategory')) {
            $query->whereHas('subCategory', function ($q) use ($request) {
                $q->where('slug', $request->subcategory);
            });
        }

        // Filter by Search Term
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('long_description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sort = $request->get('sort');
        if ($sort) {
            // Ensure sorting is deterministic and overrides any previous ordering.
            $query->reorder();

            switch ($sort) {
                case 'price-asc':
                    $query->orderByRaw('COALESCE(discount_price, price) asc');
                    break;
                case 'price-desc':
                    $query->orderByRaw('COALESCE(discount_price, price) desc');
                    break;
                case 'name-asc':
                    $query->orderBy('name', 'asc');
                    break;
                case 'name-desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'newest':
                default:
                    $query->latest();
                    break;
            }
        } else {
            $query->latest();
        }

        $products = $query->paginate(16);
        $categories = Cache::remember('all_categories_with_counts', 120, fn () =>
            Category::with('subCategories')->withCount('products')->get()
        );

        return view('frontend.home', compact(
            'products', 
            'categories',
            'newArrivalsCount',
            'featuredCount',
            'recommendedCount',
            'onSaleCount'
        ));
    }
}
