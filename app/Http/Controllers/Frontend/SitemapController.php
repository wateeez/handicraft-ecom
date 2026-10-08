<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\BlogPost;
use App\Models\SiteSetting;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => now()],
            ['loc' => route('blog.index'), 'lastmod' => BlogPost::max('updated_at')],
        ]);
        foreach ([
            'shipping_policy' => 'pages.shipping-policy',
            'about_content' => 'pages.about',
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
            ->select(['slug', 'updated_at'])
            ->orderBy('id')
            ->each(fn (Category $category) => $urls->push([
                'loc' => route('categories.show', $category),
                'lastmod' => $category->updated_at,
            ]));

        Product::query()
            ->select(['slug', 'updated_at'])
            ->orderBy('id')
            ->each(fn (Product $product) => $urls->push([
                'loc' => route('products.show', $product->slug),
                'lastmod' => $product->updated_at,
            ]));

        BlogPost::published()
            ->select(['slug', 'updated_at'])
            ->each(fn (BlogPost $post) => $urls->push([
                'loc' => route('blog.show', $post),
                'lastmod' => $post->updated_at,
            ]));

        $xml = view('frontend.sitemap', compact('urls'))->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
