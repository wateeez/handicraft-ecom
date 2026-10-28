<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AboutPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'meta_title' => ['required', 'string', 'max:60'],
            'meta_description' => ['required', 'string', 'max:160'],
            'hero_heading' => ['required', 'string', 'max:255'],
            'hero_intro' => ['required', 'string', 'max:3000'],
            'hero_image' => ['nullable', 'image', 'max:2048'],
            'remove_hero_image' => ['sometimes', 'boolean'],
            'hero_image_alt' => ['nullable', 'string', 'max:255', 'required_with:hero_image'],
            'sections' => ['array', 'max:30'],
            'sections.*.id' => ['nullable', 'integer'],
            'sections.*.key' => ['nullable', 'string', 'max:255'],
            'sections.*.heading' => ['required', 'string', 'max:255'],
            'sections.*.body' => ['nullable', 'string', 'max:10000'],
            'sections.*.is_visible' => ['sometimes', 'boolean'],
            'sections.*.items' => ['nullable', 'array', 'max:30'],
            'sections.*.items.*.term' => ['nullable', 'string', 'max:100'],
            'sections.*.items.*.text' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $page = \App\Models\AboutPage::first();
            $hasImage = $this->hasFile('hero_image') || ($page?->hero_image_path && ! $this->boolean('remove_hero_image'));
            if ($hasImage && ! $this->filled('hero_image_alt')) {
                $validator->errors()->add('hero_image_alt', 'Alt text is required when a hero image is set.');
            }
        });
    }
}
