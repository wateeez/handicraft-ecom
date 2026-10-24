<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPost extends Model
{

    protected static function booted(): void
    {
        $forget = fn () => \Illuminate\Support\Facades\Cache::forget(\App\Http\Controllers\Frontend\SitemapController::CACHE_KEY);
        static::saved($forget);
        static::deleted($forget);
    }

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Get the author of the blog post.
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Scope for published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                     ->where('published_at', '<=', now())
                     ->orderBy('published_at', 'desc');
    }

    /**
     * Scope ordered by priority.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('published_at', 'desc');
    }

    /**
     * Generate unique slug from title.
     */
    public static function generateSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = static::where('slug', $slug);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the reading time in minutes.
     */
    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        return max(1, ceil($wordCount / 200)); // Average 200 words per minute
    }

    /**
     * Get excerpt or generate from content.
     */
    public function getShortExcerptAttribute(): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }
        return Str::limit(strip_tags($this->content), 160);
    }

    /**
     * Get meta title or fallback to title.
     */
    public function getSeoTitleAttribute(): string
    {
        return $this->meta_title ?: $this->title;
    }

    /**
     * Get meta description or fallback to excerpt.
     */
    public function getSeoDescriptionAttribute(): string
    {
        return $this->meta_description ?: $this->short_excerpt;
    }

    /**
     * Accessor for featured_image to ensure it returns a full URL.
     */
    public function getFeaturedImageAttribute($value)
    {
        return Product::mediaUrl($value);
    }
}
