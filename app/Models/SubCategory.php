<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SubCategory extends Model
{
    /**
     * How many levels of sub-categories may sit below a category
     * (Category → Sub-category → Sub-sub-category).
     */
    public const MAX_DEPTH = 2;

    protected $guarded = [];

    protected static function booted(): void
    {
        // Deleting a sub-category moves its children and products up one level
        // instead of deleting or orphaning them.
        static::deleting(function (SubCategory $subCategory) {
            static::where('parent_id', $subCategory->id)
                ->update(['parent_id' => $subCategory->parent_id]);

            Product::where('sub_category_id', $subCategory->id)
                ->update(['sub_category_id' => $subCategory->parent_id]);
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function parent()
    {
        return $this->belongsTo(SubCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(SubCategory::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Ancestors from the top-level sub-category down to (not including) this one.
     */
    public function ancestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current && !$ancestors->contains('id', $current->id)) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * 1 for a sub-category directly under a category, 2 for one nested below that, etc.
     */
    public function depth(): int
    {
        return $this->ancestors()->count() + 1;
    }

    public function pathName(string $separator = ' › '): string
    {
        return $this->ancestors()->push($this)->pluck('name')->implode($separator);
    }

    /**
     * IDs of every sub-category nested anywhere below this one.
     */
    public function descendantIds(): array
    {
        $ids = [];
        $frontier = [$this->id];

        while ($frontier) {
            $frontier = static::whereIn('parent_id', $frontier)
                ->whereNotIn('id', $ids)
                ->pluck('id')
                ->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    public function selfAndDescendantIds(): array
    {
        return array_merge([$this->id], $this->descendantIds());
    }

    /**
     * Number of levels in this sub-category's branch, counting itself (a leaf is 1).
     */
    public function subtreeHeight(): int
    {
        $height = 1;
        $frontier = [$this->id];

        while ($frontier = static::whereIn('parent_id', $frontier)->pluck('id')->all()) {
            $height++;
        }

        return $height;
    }

    /**
     * Sub-category slugs are unique site-wide, so a clashing name falls back to
     * "{parent-slug}-{name}" and then a numeric suffix.
     */
    public static function uniqueSlug(string $name, ?SubCategory $parent = null, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $taken = fn (string $slug) => static::where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if (!$taken($base)) {
            return $base;
        }

        if ($parent) {
            $base = $parent->slug . '-' . $base;
            if (!$taken($base)) {
                return $base;
            }
        }

        $i = 2;
        while ($taken("{$base}-{$i}")) {
            $i++;
        }

        return "{$base}-{$i}";
    }
}
