<?php

namespace Database\Seeders;

use App\Models\AboutPage;
use Illuminate\Database\Seeder;

/**
 * Seeds the About Us page. Safe to run repeatedly: existing records (and any admin edits) are left untouched.
 * {contact_email} and {shop_url} are replaced when the page is rendered.
 */
class AboutPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = AboutPage::firstOrCreate([], [
            'meta_title' => 'About Handicraft Nepal | Handmade Nepali Handicrafts & Buddhist Statues',
            'meta_description' => "Learn how Handicraft Nepal preserves Nepal's artistic heritage through ethical, handcrafted products made by skilled artisans and exported worldwide.",
            'hero_heading' => "Handicraft Nepal: Preserving Nepal's Artistic Heritage",
            'hero_intro' => "Handicraft Nepal is an organization founded to promote, preserve and share the historical and traditional heritage of Nepalese handicrafts with the world. We were built on a simple vision: to bring Nepalese handmade products to overseas markets while keeping alive the rich artistic skills and craft techniques that have been passed down through generations.",
        ]);

        $sections = [
            'our-craft-our-people' => [
                'heading' => 'Our Craft, Our People',
                'body' => "We offer a range of exclusive product lines, including handcrafted Buddhist metal statues. They are made by highly skilled artisans in an environmentally friendly way. Our artisans and partner workshops work across different areas of Nepal, and our traditional, highly qualified craftspeople create both regular collections and new designs to meet the needs of customers around the world.\n\nWe welcome customized designs and bring them to life on our products, because we believe every design begins with imagination.\n\n[Browse our collection]({shop_url})",
            ],
            'ethical-production' => [
                'heading' => 'Our Commitment to Ethical Production',
                'body' => 'Handicraft Nepal does not employ child labor and strongly condemns the practice. Every source we use is 100% free of child labor.',
            ],
            'our-vision' => [
                'heading' => 'Our Vision',
                'items' => [
                    ['term' => 'Promote', 'text' => 'Nepalese craft products in the export market.'],
                    ['term' => 'Preserve', 'text' => "Nepal's rich artistic skills and craft techniques."],
                    ['term' => 'Motivate', 'text' => 'craft producers by providing professional training tailored to their needs.'],
                    ['term' => 'Train', 'text' => 'women and young men from low-income families through work-based training, opening up a wider range of employment opportunities.'],
                    ['term' => 'Empower women', 'text' => 'to take a leading role in economic activity through organized production, collection and marketing systems.'],
                    ['term' => 'Strengthen communities', 'text' => 'so that local people and other beneficiaries can take responsibility for themselves and their future.'],
                    ['term' => 'Create opportunity', 'text' => 'by making use of locally available natural resources and manpower, especially for underprivileged members of society.'],
                    ['term' => 'Guarantee quality', 'text' => 'so that our valued customers are fully satisfied.'],
                ],
            ],
            'trusted-across-continents' => [
                'heading' => 'Trusted Across Continents',
                'body' => 'We serve customers in the diverse markets of Europe and America, and we are delighted that they are happy with our service and quality. The steadily growing demand for our products is proof of that trust.',
            ],
            'cant-find-what-youre-looking-for' => [
                'heading' => "Can't Find What You're Looking For?",
                'body' => "Our range is far larger than what appears in our online shop. If you don't see the item you want, email us at [{contact_email}](mailto:{contact_email}) with the product name and item code, and we'll be happy to help.",
            ],
            'our-artisans-and-partners' => [
                'heading' => 'Our Artisans and Partners',
                'body' => 'Placeholder: describe your artisans, partner workshops or vendors here, then make this section visible.',
                'is_visible' => false,
            ],
        ];

        $order = 0;
        foreach ($sections as $key => $data) {
            $page->sections()->firstOrCreate(['key' => $key], $data + ['sort_order' => $order, 'is_visible' => true]);
            $order++;
        }
    }
}
