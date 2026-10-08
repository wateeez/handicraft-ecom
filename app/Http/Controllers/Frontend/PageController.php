<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;

class PageController extends Controller
{
    public function shippingPolicy()
    {
        $setting = SiteSetting::where('key', 'shipping_policy')->first();

        return view('frontend.pages.shipping-policy', [
            'content' => $setting?->value ?? '',
            'lastUpdated' => $setting?->updated_at,
        ]);
    }

    public function about()
    {
        return $this->contentPage(
            'about_content',
            'About ' . SiteSetting::get('site_name', 'Handicraft Nepal NP'),
            'Our Story',
            'About'
        );
    }

    public function returns()
    {
        return $this->contentPage(
            'returns_policy',
            'Returns & Refunds',
            'Customer Care',
            'Returns Policy'
        );
    }

    public function contact()
    {
        $hasContactDetails = SiteSetting::get('footer_email')
            || SiteSetting::get('footer_phone')
            || SiteSetting::get('whatsapp_number');

        abort_unless($hasContactDetails, 404);

        return view('frontend.pages.contact');
    }

    private function contentPage(string $key, string $heading, string $eyebrow, string $pageTitle)
    {
        $setting = SiteSetting::where('key', $key)->first();
        abort_unless($setting?->value, 404);

        return view('frontend.pages.content', [
            'content' => $setting->value,
            'heading' => $heading,
            'eyebrow' => $eyebrow,
            'pageTitle' => $pageTitle,
            'lastUpdated' => $setting->updated_at,
        ]);
    }
}
