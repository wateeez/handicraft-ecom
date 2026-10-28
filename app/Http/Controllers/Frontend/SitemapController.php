<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AboutPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\BlogPost;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public const CACHE_KEY = 'sitemap.xml';

    public function index()
    {
        $xml = Cache::remember(self::CACHE_KEY, now()->addHour(), fn () => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function build(): string
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => Product::max('updated_at')],
            ['loc' => route('products.index'), 'lastmod' => Product::max('updated_at')],
            ['loc' => route('blog.index'), 'lastmod' => BlogPost::max('updated_at')],
        ]);
        if ($about = AboutPage::first()) {
            $urls->push(['loc' => route('about'), 'lastmod' => $about->updated_at]);
        }
        foreach ([
            'shipping_policy' => 'pages.shipping-policy',
            'returns_policy' => 'pages.returns',
        ] as $settingKey => $route) {
            $setting = SiteSetting::where('key', $settingKey)->first();
            if ($setting?->value) {
                $urls->push(['loc' => route($route), 'lastmod' => $setting->updated_at]);
            }
        }

        if (SiteSetting::get('footer_email') || SiteSetting::get('footer_phone') || SiteSetting::get('whatsapp_number')) {
            $urls->push(['loc' => route('pages.contact'), 'lastmod' => null]);
        }

        Category::query()
            ->select(['slug', 'image', 'updated_at'])
            ->orderBy('id')
            ->each(fn (Category $category) => $urls->push([
                'loc' => route('categories.show', $category),
                'lastmod' => $category->updated_at,
                'images' => array_filter([Product::mediaUrl($category->image)]),
            ]));

        Product::query()
            ->select(['slug', 'main_image', 'secondary_image', 'images', 'updated_at'])
            ->orderBy('id')
            ->each(fn (Product $product) => $urls->push([
                'loc' => route('products.show', $product->slug),
                'lastmod' => $product->updated_at,
                'images' => array_slice(array_values(array_unique(array_filter(array_merge(
                    [$product->main_image, $product->secondary_image],
                    $product->images ?? []
                )))), 0, 10),
            ]));

        BlogPost::published()
            ->where('slug', '!=', '')
            ->whereNotNull('slug')
            ->select(['slug', 'featured_image', 'updated_at'])
            ->each(fn (BlogPost $post) => $urls->push([
                'loc' => route('blog.show', $post->slug),
                'lastmod' => $post->updated_at,
                'images' => array_filter([$post->featured_image ? url($post->featured_image) : null]),
            ]));

        return view('frontend.sitemap', compact('urls'))->render();
    }
}
