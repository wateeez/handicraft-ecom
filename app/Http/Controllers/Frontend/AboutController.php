<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AboutPage;
use App\Models\SiteSetting;
use App\Support\AboutContent;

class AboutController extends Controller
{
    public function show()
    {
        $page = AboutPage::publicData();
        abort_unless($page, 404);

        $siteSettings = SiteSetting::get('social_profiles', '');
        $sameAs = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', (string) $siteSettings) ?: []),
            fn ($url) => filter_var($url, FILTER_VALIDATE_URL)
        ));

        $logo = SiteSetting::where('key', 'navbar_logo')->first()?->getLogoUrl();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'AboutPage',
            '@id' => route('about') . '#webpage',
            'url' => route('about'),
            'name' => $page['meta_title'],
            'description' => $page['meta_description'],
            'dateModified' => $page['updated_at']->toIso8601String(),
            'mainEntity' => array_filter([
                '@type' => 'Organization',
                '@id' => url('/') . '/#organization',
                'name' => config('site.name'),
                'url' => config('site.main_url'),
                'logo' => $logo,
                'email' => AboutContent::contactEmail(),
                'areaServed' => config('site.area_served'),
                'description' => $page['hero_intro'],
                'sameAs' => $sameAs,
            ], fn ($v) => $v !== null && $v !== ''),
        ];

        return view('frontend.pages.about', [
            'page' => $page,
            'schema' => $schema,
            'contactEmail' => AboutContent::contactEmail(),
        ]);
    }
}
