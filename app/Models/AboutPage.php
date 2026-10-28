<?php

namespace App\Models;

use App\Http\Controllers\Frontend\SitemapController;
use App\Support\AboutContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class AboutPage extends Model
{
    public const CACHE_KEY = 'about_page.data';

    protected $fillable = [
        'meta_title', 'meta_description', 'hero_heading', 'hero_intro', 'hero_image_path', 'hero_image_alt',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(SitemapController::CACHE_KEY);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(AboutSection::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Page data prepared for the public view (cached; flushed whenever the admin saves).
     * Body text is rendered to sanitized HTML here so the view only prints it.
     */
    public static function publicData(): ?array
    {
        return Cache::remember(self::CACHE_KEY, now()->addDay(), function () {
            $page = static::query()->first();
            if (! $page) {
                return null;
            }

            return [
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'hero_heading' => $page->hero_heading,
                'hero_intro' => $page->hero_intro,
                'hero_image_url' => Product::mediaUrl($page->hero_image_path) ?: null,
                'hero_image_alt' => $page->hero_image_alt,
                'updated_at' => $page->updated_at,
                'sections' => $page->sections()->where('is_visible', true)->get()->map(fn (AboutSection $s) => [
                    'key' => $s->key,
                    'heading' => $s->heading,
                    'html' => AboutContent::render($s->body),
                    'items' => collect($s->items ?? [])->map(fn ($i) => [
                        'term' => (string) ($i['term'] ?? ''),
                        'text' => (string) ($i['text'] ?? ''),
                    ])->filter(fn ($i) => $i['term'] !== '' || $i['text'] !== '')->values()->all(),
                ])->all(),
            ];
        });
    }
}
