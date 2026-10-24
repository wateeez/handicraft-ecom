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

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
