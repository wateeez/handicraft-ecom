<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_includes_public_category_and_product_urls(): void
    {
        $category = Category::factory()->create(['slug' => 'wooden-crafts']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'hand-carved-bowl',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('categories.show', $category), false)
            ->assertSee(route('products.show', $product->slug), false);
    }
}
