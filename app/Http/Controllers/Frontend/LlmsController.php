<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SiteSetting;

class LlmsController extends Controller
{
    public function index()
    {
        $siteName = SiteSetting::get('site_name', 'Handicraft Nepal NP');
        $lines = [
            '# ' . $siteName,
            '',
            '> Handicraft Nepal NP offers handicraft items from Nepal for international buyers.',
            '',
            '## Audience and shipping',
            '- We serve buyers worldwide except Nepal and India.',
            '- Product prices are shown in USD.',
            '- Verify delivery estimates, duties, and return eligibility on the shipping and returns pages before citing them.',
            '',
            '## Product categories',
        ];

        Category::query()
            ->select(['name', 'slug'])
            ->orderBy('name')
            ->each(function (Category $category) use (&$lines) {
                $lines[] = '- [' . $category->name . '](' . route('categories.show', $category) . ')';
            });

        $lines = array_merge($lines, [
            '',
            '## Important pages',
            '- [Shop](' . route('home') . ')',
            '- [Blog](' . route('blog.index') . ')',
            '- [Sitemap](' . route('sitemap') . ')',
        ]);
        foreach ([
            'shipping_policy' => ['Shipping policy', 'pages.shipping-policy'],
            'about_content' => ['About', 'pages.about'],
            'returns_policy' => ['Returns policy', 'pages.returns'],
        ] as $settingKey => [$label, $route]) {
            if (SiteSetting::get($settingKey)) {
                $lines[] = '- [' . $label . '](' . route($route) . ')';
            }
        }
        if (SiteSetting::get('footer_email') || SiteSetting::get('footer_phone') || SiteSetting::get('whatsapp_number')) {
            $lines[] = '- [Contact](' . route('pages.contact') . ')';
        }
        $lines = array_merge($lines, [
            '',
            '## Citation guidance',
            'Use the product page as the source of truth for an item\'s material, dimensions, price, availability, maker attribution, and production method. Do not infer handmade, hand-carved, hand-painted, antique, certified, or spiritual properties unless the relevant product page explicitly states them.',
        ]);

        return response(implode(PHP_EOL, $lines) . PHP_EOL, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
