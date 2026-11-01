<?php

namespace App\Models;

use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    protected static function booted(): void
    {
        $forget = fn () => \Illuminate\Support\Facades\Cache::forget(\App\Http\Controllers\Frontend\SitemapController::CACHE_KEY);
        static::saved($forget);
        static::deleted($forget);
    }

    use HasFactory;
    protected $fillable = [
        'name', 'slug', 'description', 'image', 'meta_title', 'meta_description'
    ];

    protected $guarded = [];

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class);
    }

    /**
     * Sub-categories sitting directly under this category (no parent sub-category).
     */
    public function topSubCategories()
    {
        return $this->hasMany(SubCategory::class)->whereNull('parent_id');
    }

    /**
     * All sub-categories in tree order, each as ['sub' => SubCategory, 'depth' => int, 'path' => string].
     * Uses the loaded subCategories relation, so eager-load it to avoid extra queries.
     */
    public function flattenedSubCategories(): \Illuminate\Support\Collection
    {
        $byParent = $this->subCategories->groupBy(fn ($sub) => $sub->parent_id ?? 0);
        $rows = collect();

        $walk = function ($parentId, int $depth, string $prefix) use (&$walk, $byParent, $rows) {
            foreach ($byParent->get($parentId, collect()) as $sub) {
                $path = $prefix === '' ? $sub->name : $prefix . ' › ' . $sub->name;
                $rows->push(['sub' => $sub, 'depth' => $depth, 'path' => $path]);
                $walk($sub->id, $depth + 1, $path);
            }
        };
        $walk(0, 1, '');

        return $rows;
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
