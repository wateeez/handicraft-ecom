<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AboutSection extends Model
{
    protected $fillable = ['about_page_id', 'key', 'heading', 'body', 'items', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'is_visible' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn () => AboutPage::flushCache();
        static::saved($flush);
        static::deleted($flush);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(AboutPage::class, 'about_page_id');
    }
}
