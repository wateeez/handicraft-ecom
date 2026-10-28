<?php

namespace Tests\Feature;

use App\Models\AboutPage;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AboutPageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(string $roleName): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::where('name', $roleName)->firstOrFail());

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'meta_title' => 'About us',
            'meta_description' => 'Short description.',
            'hero_heading' => 'Heading',
            'hero_intro' => 'Intro text.',
            'sections' => [
                ['heading' => 'First', 'body' => 'Body one', 'is_visible' => '1'],
            ],
        ], $overrides);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(AboutPageSeeder::class);
        $this->seed(AboutPageSeeder::class);

        $this->assertSame(1, AboutPage::count());
        $this->assertSame(6, AboutPage::first()->sections()->count());
    }

    public function test_public_page_has_one_h1_and_valid_json_ld(): void
    {
        $this->seed(AboutPageSeeder::class);

        $response = $this->get('/about-us')->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $response->assertSee('Our Vision', false)->assertSee('info@handicraftnepalnp.com', false);
        $response->assertSee('<link rel="canonical" href="' . route('about') . '">', false);

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $about = collect($m[1])->map(fn ($j) => json_decode($j, true, 512, JSON_THROW_ON_ERROR))
            ->firstWhere('@type', 'AboutPage');
        $this->assertNotNull($about);
        $this->assertSame('Organization', $about['mainEntity']['@type']);
        $this->assertSame([], $about['mainEntity']['sameAs']);
    }

    public function test_hidden_sections_do_not_render(): void
    {
        $this->seed(AboutPageSeeder::class);

        $this->get('/about-us')->assertDontSee('Our Artisans and Partners');
    }

    public function test_old_about_url_redirects_permanently(): void
    {
        $this->get('/about')->assertRedirect('/about-us')->assertStatus(301);
    }

    public function test_page_is_in_sitemap_and_llms(): void
    {
        $this->seed(AboutPageSeeder::class);

        $this->get('/sitemap.xml')->assertSee(route('about'), false);
        $this->get('/llms.txt')->assertSee(route('about'), false);
    }

    public function test_only_users_with_manage_settings_can_edit(): void
    {
        $this->seed(AboutPageSeeder::class);

        $this->get('/admin/about')->assertRedirect();
        $this->actingAs($this->adminWith('super_admin'))->get('/admin/about')->assertOk();
    }

    public function test_user_without_manage_settings_is_forbidden(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, AboutPageSeeder::class]);
        $role = Role::whereDoesntHave('permissions', fn ($q) => $q->where('name', 'manage_settings'))->first();
        $this->assertNotNull($role);

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);
        $this->actingAs($user)->put('/admin/about', $this->payload())->assertForbidden();
    }

    public function test_saving_clears_cache_and_strips_unsafe_html(): void
    {
        $this->seed(AboutPageSeeder::class);
        $this->get('/about-us')->assertOk();
        $this->assertTrue(Cache::has(AboutPage::CACHE_KEY));

        $this->actingAs($this->adminWith('super_admin'))->put('/admin/about', $this->payload([
            'meta_title' => 'New title',
            'sections' => [[
                'heading' => 'Safe <script>alert(1)</script>',
                'body' => "Hello <script>alert('x')</script> **bold** [bad](javascript:alert(1)) <img src=x onerror=alert(1)>",
                'is_visible' => '1',
            ]],
        ]))->assertRedirect(route('admin.about.edit'));

        $this->assertFalse(Cache::has(AboutPage::CACHE_KEY));

        $html = $this->get('/about-us')->assertOk()->assertSee('New title', false)->getContent();
        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringNotContainsString("alert('x')", $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertSame(1, AboutPage::first()->sections()->count());
    }

    public function test_meta_limits_and_hero_alt_are_validated(): void
    {
        $this->seed(AboutPageSeeder::class);
        $admin = $this->adminWith('super_admin');

        $this->actingAs($admin)->put('/admin/about', $this->payload([
            'meta_title' => str_repeat('a', 61),
            'meta_description' => str_repeat('a', 161),
        ]))->assertSessionHasErrors(['meta_title', 'meta_description']);

        \Illuminate\Support\Facades\Storage::fake('public');
        $this->actingAs($admin)->put('/admin/about', $this->payload([
            'hero_image' => \Illuminate\Http\UploadedFile::fake()->image('hero.jpg'),
        ]))->assertSessionHasErrors('hero_image_alt');
    }
}
