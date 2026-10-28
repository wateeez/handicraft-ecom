<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Str;

/**
 * Body text for the About Us page is stored as Markdown. Raw HTML is stripped when saving
 * and again when rendering, and the rendered output is limited to a small tag allow-list.
 */
class AboutContent
{
    private const ALLOWED_TAGS = '<p><br><strong><em><a><ul><ol><li>';

    public static function contactEmail(): string
    {
        return SiteSetting::get('footer_email') ?: config('site.contact_email');
    }

    public static function clean(?string $markdown): ?string
    {
        $markdown = preg_replace('#<(script|style)\b.*?</\1\s*>#is', '', (string) $markdown);
        $markdown = trim(strip_tags($markdown));

        return $markdown === '' ? null : $markdown;
    }

    public static function render(?string $markdown): string
    {
        if (! $markdown) {
            return '';
        }

        $markdown = strtr($markdown, [
            '{contact_email}' => self::contactEmail(),
            '{shop_url}' => config('site.shop_url'),
            '{main_url}' => config('site.main_url'),
        ]);

        $html = Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);

        return strip_tags($html, self::ALLOWED_TAGS);
    }
}
