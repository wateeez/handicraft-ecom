<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AboutPageRequest;
use App\Models\AboutPage;
use App\Models\Product;
use App\Support\AboutContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AboutPageController extends Controller
{
    public function edit()
    {
        $page = AboutPage::with('sections')->firstOrNew([], [
            'meta_title' => '', 'meta_description' => '', 'hero_heading' => '', 'hero_intro' => '',
        ]);

        $sections = old('sections', $page->sections->map(fn ($s) => [
            'id' => $s->id,
            'key' => $s->key,
            'heading' => $s->heading,
            'body' => $s->body,
            'is_visible' => $s->is_visible,
            'items' => $s->items ?? [],
        ])->all());

        return view('admin.about.edit', [
            'page' => $page,
            'sections' => array_values($sections),
            'heroImageUrl' => Product::mediaUrl($page->hero_image_path) ?: null,
        ]);
    }

    public function update(AboutPageRequest $request)
    {
        $data = $request->validated();
        $disk = Product::mediaDisk();

        DB::transaction(function () use ($request, $data, $disk) {
            $page = AboutPage::firstOrNew();

            $page->fill([
                'meta_title' => strip_tags($data['meta_title']),
                'meta_description' => strip_tags($data['meta_description']),
                'hero_heading' => strip_tags($data['hero_heading']),
                'hero_intro' => strip_tags($data['hero_intro']),
                'hero_image_alt' => isset($data['hero_image_alt']) ? strip_tags($data['hero_image_alt']) : null,
            ]);

            if ($request->hasFile('hero_image')) {
                $this->deleteImage($page, $disk);
                $page->hero_image_path = $request->file('hero_image')->store('about', $disk);
            } elseif ($request->boolean('remove_hero_image')) {
                $this->deleteImage($page, $disk);
                $page->hero_image_path = null;
                $page->hero_image_alt = null;
            }

            $page->save();

            $keepIds = [];
            foreach (array_values($data['sections'] ?? []) as $order => $row) {
                $items = collect($row['items'] ?? [])
                    ->map(fn ($i) => [
                        'term' => trim(strip_tags($i['term'] ?? '')),
                        'text' => trim(strip_tags($i['text'] ?? '')),
                    ])
                    ->filter(fn ($i) => $i['term'] !== '' || $i['text'] !== '')
                    ->values()->all();

                $attrs = [
                    'heading' => strip_tags($row['heading']),
                    'body' => AboutContent::clean($row['body'] ?? null),
                    'items' => $items ?: null,
                    'sort_order' => $order,
                    'is_visible' => (bool) ($row['is_visible'] ?? false),
                ];

                $section = ! empty($row['id']) ? $page->sections()->find($row['id']) : null;
                if ($section) {
                    $section->update($attrs);
                } else {
                    $section = $page->sections()->create($attrs + ['key' => Str::slug($attrs['heading']) . '-' . Str::lower(Str::random(4))]);
                }
                $keepIds[] = $section->id;
            }

            $page->sections()->whereNotIn("id", $keepIds)->get()->each->delete();
            $page->touch(); // keeps updated_at (sitemap lastmod) current even if only sections changed
            AboutPage::flushCache();
        });

        return redirect()->route('admin.about.edit')->with('success', 'About Us page saved.');
    }

    private function deleteImage(AboutPage $page, string $disk): void
    {
        $path = $page->hero_image_path;
        if ($path && ! filter_var($path, FILTER_VALIDATE_URL)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
