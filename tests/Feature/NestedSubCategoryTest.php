<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class NestedSubCategoryTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private SubCategory $wallArt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create(['name' => 'Home Decor', 'slug' => 'home-decor']);
        $this->wallArt = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Wall Art',
            'slug' => 'wall-art',
        ]);
    }

    private function asAdmin(): static
    {
        $role = Role::create(['name' => 'r_' . uniqid(), 'display_name' => 'Test']);
        foreach (['view_categories', 'manage_categories', 'manage_products'] as $name) {
            $role->permissions()->attach(Permission::firstOrCreate(['name' => $name], ['display_name' => $name])->id);
        }
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role->id);

        return $this->actingAs($user);
    }

    private function nested(string $name, SubCategory $parent): SubCategory
    {
        return SubCategory::create([
            'category_id' => $parent->category_id,
            'parent_id' => $parent->id,
            'name' => $name,
            'slug' => SubCategory::uniqueSlug($name, $parent),
        ]);
    }

    public function test_admin_can_create_a_sub_category_under_a_sub_category(): void
    {
        $this->asAdmin()
            ->post(route('admin.categories.store'), [
                'child' => 1,
                'parent_id' => $this->category->id,
                'parent_sub_category_id' => $this->wallArt->id,
                'name' => 'Paintings',
            ])
            ->assertSessionHasNoErrors();

        $paintings = SubCategory::where('name', 'Paintings')->firstOrFail();
        $this->assertSame($this->wallArt->id, (int) $paintings->parent_id);
        $this->assertSame('Wall Art › Paintings', $paintings->pathName());
    }

    public function test_nesting_is_limited_to_max_depth(): void
    {
        $paintings = $this->nested('Paintings', $this->wallArt);

        $this->asAdmin()
            ->post(route('admin.categories.store'), [
                'parent_id' => $this->category->id,
                'parent_sub_category_id' => $paintings->id,
                'name' => 'Oil Paintings',
            ])
            ->assertSessionHasErrors('parent_sub_category_id');

        $this->assertDatabaseMissing('sub_categories', ['name' => 'Oil Paintings']);
    }

    public function test_parent_must_belong_to_the_same_category(): void
    {
        $other = Category::factory()->create();

        $this->asAdmin()
            ->post(route('admin.categories.store'), [
                'parent_id' => $other->id,
                'parent_sub_category_id' => $this->wallArt->id,
                'name' => 'Paintings',
            ])
            ->assertSessionHasErrors('parent_sub_category_id');
    }

    public function test_child_form_requires_a_parent_sub_category(): void
    {
        $this->asAdmin()
            ->post(route('admin.categories.store'), [
                'child' => 1,
                'parent_id' => $this->category->id,
                'parent_sub_category_id' => '',
                'name' => 'Orphan',
            ])
            ->assertSessionHasErrors(['parent_sub_category_id' => 'Please choose the parent sub-category.']);

        $this->assertDatabaseMissing('sub_categories', ['name' => 'Orphan']);
    }

    public function test_editing_a_child_requires_a_parent_sub_category(): void
    {
        $paintings = $this->nested('Paintings', $this->wallArt);

        $this->asAdmin()
            ->put(route('admin.subcategories.update', $paintings), [
                'name' => 'Paintings',
                'category_id' => $this->category->id,
                'parent_sub_category_id' => '',
            ])
            ->assertSessionHasErrors('parent_sub_category_id');

        $this->assertSame($this->wallArt->id, (int) $paintings->fresh()->parent_id);
    }

    public function test_editing_a_sub_category_never_turns_it_into_a_child(): void
    {
        $mirrors = SubCategory::create(['category_id' => $this->category->id, 'name' => 'Mirrors', 'slug' => 'mirrors']);

        $this->asAdmin()
            ->put(route('admin.subcategories.update', $mirrors), [
                'name' => 'Mirrors',
                'category_id' => $this->category->id,
                'parent_sub_category_id' => $this->wallArt->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($mirrors->fresh()->parent_id);
    }

    public function test_moving_a_branch_to_another_category_moves_children_and_products(): void
    {
        SubCategory::create(['category_id' => $this->category->id, 'name' => 'Mirrors', 'slug' => 'mirrors']);
        $paintings = $this->nested('Paintings', $this->wallArt);
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'sub_category_id' => $paintings->id,
        ]);
        $other = Category::factory()->create();

        $this->asAdmin()
            ->put(route('admin.subcategories.update', $this->wallArt), [
                'name' => 'Wall Art',
                'category_id' => $other->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($other->id, (int) $paintings->fresh()->category_id);
        $this->assertSame($other->id, (int) $product->fresh()->category_id);
        $this->assertSame('wall-art', $this->wallArt->fresh()->slug);
    }

    public function test_duplicate_names_get_a_unique_slug(): void
    {
        $decor = SubCategory::create([
            'category_id' => $this->category->id,
            'name' => 'Paintings',
            'slug' => 'paintings',
        ]);

        $paintings = $this->nested('Paintings', $this->wallArt);

        $this->assertSame('paintings', $decor->slug);
        $this->assertSame('wall-art-paintings', $paintings->slug);
    }

    public function test_deleting_a_sub_category_moves_children_and_products_up(): void
    {
        $paintings = $this->nested('Paintings', $this->wallArt);
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'sub_category_id' => $this->wallArt->id,
        ]);

        $this->wallArt->delete();

        $this->assertNull($paintings->fresh()->parent_id);
        $this->assertNull($product->fresh()->sub_category_id);

        $hanging = $this->nested('Macrame', $paintings);
        $onHanging = Product::factory()->create([
            'category_id' => $this->category->id,
            'sub_category_id' => $hanging->id,
        ]);
        $hanging->delete();

        $this->assertSame($paintings->id, (int) $onHanging->fresh()->sub_category_id);
    }

    public function test_storefront_filter_includes_nested_sub_categories(): void
    {
        $paintings = $this->nested('Paintings', $this->wallArt);
        $inParent = Product::factory()->create([
            'category_id' => $this->category->id,
            'sub_category_id' => $this->wallArt->id,
            'name' => 'Parent Level Tapestry',
        ]);
        $inChild = Product::factory()->create([
            'category_id' => $this->category->id,
            'sub_category_id' => $paintings->id,
            'name' => 'Child Level Thangka',
        ]);

        $this->get(route('home', ['category' => 'home-decor', 'subcategory' => 'wall-art']))
            ->assertOk()
            ->assertSee($inParent->name)
            ->assertSee($inChild->name);

        $this->get(route('home', ['category' => 'home-decor', 'subcategory' => $paintings->slug]))
            ->assertOk()
            ->assertDontSee($inParent->name)
            ->assertSee($inChild->name);
    }

    public function test_path_name_and_descendant_ids(): void
    {
        $paintings = $this->nested('Paintings', $this->wallArt);

        $this->assertSame('Wall Art > Paintings', $paintings->pathName(' > '));
        $this->assertSame(
            [$this->wallArt->id, $paintings->id],
            $this->wallArt->selfAndDescendantIds()
        );
    }

    public function test_pages_render_the_nested_tree(): void
    {
        $paintings = $this->nested('Paintings', $this->wallArt);

        $this->asAdmin()->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Paintings')
            ->assertSee('data-add-child-sub="' . $this->wallArt->id . '"', false)
            ->assertDontSee('data-add-child-sub="' . $paintings->id . '"', false);

        // Wall Art has a child, so moving it under another sub-category would exceed MAX_DEPTH.
        $this->asAdmin()->get(route('admin.subcategories.edit', $this->wallArt))
            ->assertOk()
            ->assertDontSee('<option value="' . $paintings->id . '" data-category=', false);

        $this->get(route('categories.show', $this->category))
            ->assertOk()
            ->assertSee('Wall Art')
            ->assertSee('subcategory=' . $paintings->slug, false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('subcategory=' . $paintings->slug, false);
    }

    private function importCsv(array $rows, string $header = 'name,sku,category,sub_category,price,weight,length,width,height')
    {
        $lines = [$header];
        foreach ($rows as [$name, $sku, $subCategory]) {
            $lines[] = "\"{$name}\",{$sku},Home Decor,\"{$subCategory}\",50,1,10,10,10";
        }

        return $this->asAdmin()->post(route('admin.products.import'), [
            'products_file' => UploadedFile::fake()->createWithContent('products.csv', implode("\n", $lines)),
        ]);
    }

    public function test_bulk_import_assigns_child_sub_categories(): void
    {
        $paintings = $this->nested('Paintings', $this->wallArt);
        SubCategory::create(['category_id' => $this->category->id, 'name' => 'Mirrors', 'slug' => 'mirrors']);

        $this->importCsv([
            ['Thangka', 'BULK-1', 'Wall Art > Paintings'],
            ['Tapestry', 'BULK-2', 'Wall Art'],
            ['Watercolour', 'BULK-3', 'Paintings'],
            ['Typo Item', 'BULK-4', 'Wall Art > Nope'],
            ['No Sub Item', 'BULK-7', ''],
        ])->assertSessionHas('success', fn ($message) => str_contains($message, '3 products created')
            && str_contains($message, '2 rows failed'))
            ->assertSessionHas('import_errors', [
                "Line 5 ('Typo Item'): Sub-category 'Wall Art > Nope' not found in 'Home Decor' (for a child use 'Parent > Child')",
                "Line 6 ('No Sub Item'): Sub-category is required",
            ]);

        $this->assertSame($paintings->id, (int) Product::where('sku', 'BULK-1')->value('sub_category_id'));
        $this->assertSame($this->wallArt->id, (int) Product::where('sku', 'BULK-2')->value('sub_category_id'));
        $this->assertSame($paintings->id, (int) Product::where('sku', 'BULK-3')->value('sub_category_id'));
        $this->assertDatabaseMissing('products', ['sku' => 'BULK-4']);
        $this->assertDatabaseMissing('products', ['sku' => 'BULK-7']);
    }

    public function test_bulk_import_requires_a_path_when_a_name_is_ambiguous(): void
    {
        SubCategory::create(['category_id' => $this->category->id, 'name' => 'Paintings', 'slug' => 'paintings']);
        $nestedPaintings = $this->nested('Paintings', $this->wallArt);

        $this->importCsv([
            ['Ambiguous', 'BULK-5', 'Paintings'],
            ['Explicit', 'BULK-6', 'Wall Art > Paintings'],
        ]);

        $this->assertDatabaseMissing('products', ['sku' => 'BULK-5']);
        $this->assertSame($nestedPaintings->id, (int) Product::where('sku', 'BULK-6')->value('sub_category_id'));
    }

    public function test_export_writes_child_paths_that_reimport(): void
    {
        $paintings = $this->nested('Paintings', $this->wallArt);
        Product::factory()->create([
            'category_id' => $this->category->id,
            'sub_category_id' => $paintings->id,
            'sku' => 'EXP-1',
        ]);

        $csv = $this->asAdmin()->get(route('admin.products.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('"Wall Art > Paintings"', $csv);
    }

    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Form Product',
            'sku' => 'FORM-' . uniqid(),
            'price' => 40,
            'category_id' => $this->category->id,
            'weight' => 1,
            'length' => 10,
            'width' => 10,
            'height' => 10,
        ], $overrides);
    }

    public function test_product_form_requires_a_sub_category_but_not_a_child(): void
    {
        $paintings = $this->nested('Paintings', $this->wallArt);

        $this->asAdmin()->post(route('admin.products.store'), $this->productPayload(['sku' => 'FORM-NONE']))
            ->assertSessionHasErrors(['sub_category_id' => 'Please choose a sub-category. If the list is empty, add a sub-category to this category first.']);
        $this->assertDatabaseMissing('products', ['sku' => 'FORM-NONE']);

        $this->asAdmin()->post(route('admin.products.store'), $this->productPayload(['sku' => 'FORM-SUB', 'sub_category_id' => $this->wallArt->id]))
            ->assertSessionHasNoErrors();
        $this->asAdmin()->post(route('admin.products.store'), $this->productPayload(['sku' => 'FORM-CHILD', 'sub_category_id' => $paintings->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame($this->wallArt->id, (int) Product::where('sku', 'FORM-SUB')->value('sub_category_id'));
        $this->assertSame($paintings->id, (int) Product::where('sku', 'FORM-CHILD')->value('sub_category_id'));
    }

    public function test_product_needs_a_sub_category_even_if_the_category_has_none(): void
    {
        $legacy = Category::factory()->create();

        $this->asAdmin()->post(route('admin.products.store'), $this->productPayload(['sku' => 'FORM-PLAIN', 'category_id' => $legacy->id]))
            ->assertSessionHasErrors('sub_category_id');

        $this->assertDatabaseMissing('products', ['sku' => 'FORM-PLAIN']);
    }

    public function test_creating_a_category_requires_and_creates_its_first_sub_category(): void
    {
        $this->asAdmin()->post(route('admin.categories.store'), ['name' => 'Jewellery'])
            ->assertSessionHasErrors(['first_sub_category' => 'Please enter the first sub-category for this category.']);
        $this->assertDatabaseMissing('categories', ['name' => 'Jewellery']);

        $this->asAdmin()->post(route('admin.categories.store'), ['name' => 'Jewellery', 'first_sub_category' => 'Necklaces'])
            ->assertSessionHasNoErrors();

        $jewellery = Category::where('name', 'Jewellery')->firstOrFail();
        $this->assertSame(['Necklaces'], $jewellery->subCategories()->pluck('name')->all());
        $this->assertNull($jewellery->subCategories()->value('parent_id'));
    }

    public function test_the_last_sub_category_cannot_be_deleted_or_moved_away(): void
    {
        $other = Category::factory()->create();

        $this->asAdmin()->delete(route('admin.subcategories.destroy', $this->wallArt))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'only sub-category'));
        $this->assertDatabaseHas('sub_categories', ['id' => $this->wallArt->id]);

        // Its child moves with it, so it still leaves the category empty.
        $this->nested('Paintings', $this->wallArt);
        $this->asAdmin()->put(route('admin.subcategories.update', $this->wallArt), [
            'name' => 'Wall Art',
            'category_id' => $other->id,
        ])->assertSessionHas('error');
        $this->assertSame($this->category->id, (int) $this->wallArt->fresh()->category_id);
    }

    public function test_product_forms_render_sub_and_child_selects(): void
    {
        $paintings = $this->nested('Paintings', $this->wallArt);
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'sub_category_id' => $paintings->id,
        ]);

        $this->asAdmin()->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('id="sub_category_parent"', false)
            ->assertSee('Child Sub-Category (Optional)');

        $this->asAdmin()->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('name="sub_category_id" id="sub_category_id" value="' . $paintings->id . '"', false);
    }

    public function test_homepage_shows_breadcrumb_and_child_chips_for_the_selected_sub_category(): void
    {
        $paintings = $this->nested('Paintings', $this->wallArt);
        $hangings = $this->nested('Wall Hangings', $this->wallArt);

        // On the parent: breadcrumb ends at Wall Art, chips offer its children.
        $this->get(route('home', ['category' => 'home-decor', 'subcategory' => 'wall-art']))
            ->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSeeInOrder(['Home Decor', 'Wall Art'])
            ->assertSee('All Wall Art')
            ->assertSee('subcategory=' . $hangings->slug, false);

        // On a child: breadcrumb shows the full path and the child's mobile branch starts open.
        $this->get(route('home', ['category' => 'home-decor', 'subcategory' => $paintings->slug]))
            ->assertOk()
            ->assertSeeInOrder(['Home Decor', 'Wall Art', 'Paintings'])
            ->assertSee('aria-current="page">Paintings', false)
            ->assertSee('aria-controls="subcats-' . $this->category->id . '-' . $this->wallArt->id . '"' . "\n" . '                                                                aria-expanded="true"', false);

        // No sub-category selected: no breadcrumb.
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('aria-label="Breadcrumb"', false);
    }

    public function test_bulk_import_accepts_header_variants_and_the_admin_separator(): void
    {
        $paintings = $this->nested('Paintings', $this->wallArt);

        $this->importCsv(
            [['Header Variant', 'BULK-H1', 'Wall Art › Paintings']],
            'Name,SKU,Category,Sub Category,Price,Weight,Length,Width,Height'
        )->assertSessionHas('success', fn ($message) => str_contains($message, '1 products created'));

        $this->assertSame($paintings->id, (int) Product::where('sku', 'BULK-H1')->value('sub_category_id'));
    }

    public function test_bulk_import_rejects_a_file_without_the_sub_category_column(): void
    {
        $this->asAdmin()->post(route('admin.products.import'), [
            'products_file' => UploadedFile::fake()->createWithContent('old.csv', "name,sku,category,price,weight,length,width,height\nOld,OLD-1,Home Decor,5,1,1,1,1"),
        ])->assertSessionHas('error', fn ($message) => str_contains($message, 'missing required column(s): sub_category'));

        $this->assertDatabaseMissing('products', ['sku' => 'OLD-1']);
    }

    public function test_bulk_import_that_imports_nothing_is_reported_as_an_error(): void
    {
        $this->importCsv([['Nope', 'BULK-N1', 'Missing Sub']])
            ->assertSessionHas('error', fn ($message) => str_contains($message, '1 rows failed'))
            ->assertSessionMissing('success');
    }

    public function test_category_list_and_template_use_the_live_categories(): void
    {
        $this->nested('Paintings', $this->wallArt);

        $list = $this->asAdmin()->get(route('admin.products.categories-csv'))->assertOk()->streamedContent();
        $this->assertStringContainsString('"Home Decor","Wall Art",Sub-category', $list);
        $this->assertStringContainsString('"Home Decor","Wall Art > Paintings","Child sub-category"', $list);

        $template = $this->asAdmin()->get(route('admin.products.template'))->assertOk()->streamedContent();
        $this->assertStringContainsString('"Home Decor","Wall Art > Paintings"', $template);
        $this->assertStringNotContainsString('Furniture', $template);

        // The downloaded template imports cleanly as-is.
        $this->asAdmin()->post(route('admin.products.import'), [
            'products_file' => UploadedFile::fake()->createWithContent('template.csv', $template),
        ])->assertSessionHas('success', fn ($message) => str_contains($message, '3 products created') && !str_contains($message, 'failed'));
    }

    public function test_bulk_page_lists_import_errors_and_links_the_category_list(): void
    {
        $this->asAdmin()->withSession(['import_errors' => ["Line 3 ('Lamp'): Sub-category is required"]])
            ->get(route('admin.products.bulk'))
            ->assertOk()
            ->assertSee('1 row(s) were not imported')
            ->assertSee("Line 3 ('Lamp'): Sub-category is required")
            ->assertSee(route('admin.products.categories-csv'), false);
    }
}
