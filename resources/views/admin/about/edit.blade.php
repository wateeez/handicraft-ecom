@extends('admin.layout')

@section('header')
    <h2 class="text-2xl font-semibold text-truffle-extra-dark">About Us Page</h2>
@endsection

@section('content')
@php
    $input = 'w-full px-4 py-2 border border-truffle-medium/30 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent';
    $metaTitle = old('meta_title', $page->meta_title);
    $metaDescription = old('meta_description', $page->meta_description);
@endphp
<div class="max-w-4xl" x-data="aboutForm(@js($sections), {{ mb_strlen($metaTitle) }}, {{ mb_strlen($metaDescription) }})">
    @include('admin.partials.flash')
    <p class="mb-4 text-sm text-truffle-extra-dark/70">
        Public page: <a href="{{ route('about') }}" target="_blank" class="underline">{{ route('about') }}</a>.
        Section text supports Markdown (bold, links, lists). Raw HTML is removed. Use <code>{contact_email}</code> and <code>{shop_url}</code> as placeholders.
    </p>

    <form action="{{ route('admin.about.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-cream rounded-lg shadow-sm mb-6">
            <div class="p-6 space-y-4">
                <h3 class="text-lg font-semibold text-truffle-extra-dark">Search listing</h3>
                <div>
                    <label class="flex justify-between text-sm font-medium text-truffle-extra-dark mb-2">Meta title *
                        <span class="text-xs font-normal" :class="t > 60 ? 'text-red-600' : 'text-truffle-extra-dark/60'"><span x-text="t"></span>/60</span></label>
                    <input type="text" name="meta_title" value="{{ $metaTitle }}" required maxlength="60" class="{{ $input }}" @input="t = $event.target.value.length">
                    @error('meta_title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="flex justify-between text-sm font-medium text-truffle-extra-dark mb-2">Meta description *
                        <span class="text-xs font-normal" :class="d > 160 ? 'text-red-600' : 'text-truffle-extra-dark/60'"><span x-text="d"></span>/160</span></label>
                    <textarea name="meta_description" rows="2" required maxlength="160" class="{{ $input }}" @input="d = $event.target.value.length">{{ $metaDescription }}</textarea>
                    @error('meta_description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="bg-cream rounded-lg shadow-sm mb-6">
            <div class="p-6 space-y-4">
                <h3 class="text-lg font-semibold text-truffle-extra-dark">Hero</h3>
                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Heading (the page's single H1) *</label>
                    <input type="text" name="hero_heading" value="{{ old('hero_heading', $page->hero_heading) }}" required class="{{ $input }}">
                    @error('hero_heading')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Intro *</label>
                    <textarea name="hero_intro" rows="4" required class="{{ $input }}">{{ old('hero_intro', $page->hero_intro) }}</textarea>
                    @error('hero_intro')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Hero image (max 2 MB)</label>
                    @if($heroImageUrl)
                        <img src="{{ $heroImageUrl }}" alt="{{ $page->hero_image_alt }}" class="mb-2 h-32 rounded-lg object-cover">
                        <label class="mb-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_hero_image" value="1"> Remove current image</label>
                    @endif
                    <input type="file" name="hero_image" accept="image/*" class="{{ $input }}">
                    @error('hero_image')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-truffle-extra-dark mb-2">Image alt text (required when an image is set)</label>
                    <input type="text" name="hero_image_alt" value="{{ old('hero_image_alt', $page->hero_image_alt) }}" class="{{ $input }}">
                    @error('hero_image_alt')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="bg-cream rounded-lg shadow-sm mb-6">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-truffle-extra-dark">Sections</h3>
                    <button type="button" @click="addSection()" class="px-3 py-1.5 text-sm rounded-lg bg-green-600 text-white">Add section</button>
                </div>
                @error('sections')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                <template x-for="(s, i) in sections" :key="s.uid">
                    <div class="border border-truffle-medium/30 rounded-lg p-4 space-y-3">
                        <input type="hidden" :name="`sections[${i}][id]`" :value="s.id ?? ''">
                        <input type="hidden" :name="`sections[${i}][key]`" :value="s.key ?? ''">
                        <div class="flex flex-wrap items-center gap-2">
                            <input type="text" :name="`sections[${i}][heading]`" x-model="s.heading" required placeholder="Section heading (H2)" class="{{ $input }} flex-1 min-w-[12rem]">
                            <label class="flex items-center gap-1 text-sm">
                                <input type="hidden" :name="`sections[${i}][is_visible]`" value="0">
                                <input type="checkbox" :name="`sections[${i}][is_visible]`" value="1" x-model="s.is_visible"> Visible
                            </label>
                            <button type="button" @click="move(i, -1)" :disabled="i === 0" class="px-2 py-1 border rounded disabled:opacity-30" aria-label="Move up">↑</button>
                            <button type="button" @click="move(i, 1)" :disabled="i === sections.length - 1" class="px-2 py-1 border rounded disabled:opacity-30" aria-label="Move down">↓</button>
                            <button type="button" @click="if (confirm('Delete this section?')) sections.splice(i, 1)" class="px-2 py-1 border border-red-300 text-red-600 rounded">Delete</button>
                        </div>
                        <textarea :name="`sections[${i}][body]`" x-model="s.body" rows="5" placeholder="Body text (Markdown)" class="{{ $input }}"></textarea>

                        <div class="rounded-lg bg-white/50 p-3">
                            <p class="mb-2 text-sm font-medium text-truffle-extra-dark">List items (optional, shown as a bulleted list: bold keyword + sentence)</p>
                            <template x-for="(it, j) in s.items" :key="it.uid">
                                <div class="mb-2 flex gap-2">
                                    <input type="text" :name="`sections[${i}][items][${j}][term]`" x-model="it.term" placeholder="Keyword" class="{{ $input }} w-40">
                                    <input type="text" :name="`sections[${i}][items][${j}][text]`" x-model="it.text" placeholder="Sentence" class="{{ $input }} flex-1">
                                    <button type="button" @click="s.items.splice(j, 1)" class="px-2 border rounded" aria-label="Remove item">✕</button>
                                </div>
                            </template>
                            <button type="button" @click="s.items.push({uid: uid(), term: '', text: ''})" class="text-sm underline">Add item</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <button type="submit" class="px-6 py-2.5 rounded-lg bg-green-600 text-white font-medium">Save About Us page</button>
    </form>
</div>

<script>
    function aboutForm(initial, titleLen, descLen) {
        let n = 0;
        const uid = () => ++n;
        const norm = (s) => ({
            uid: uid(), id: s.id ?? null, key: s.key ?? null, heading: s.heading ?? '', body: s.body ?? '',
            is_visible: s.is_visible === undefined ? true : !!Number(s.is_visible),
            items: (s.items || []).map(it => ({uid: uid(), term: it.term ?? '', text: it.text ?? ''})),
        });
        return {
            t: titleLen, d: descLen, uid,
            sections: initial.map(norm),
            addSection() { this.sections.push(norm({})); },
            move(i, dir) {
                const j = i + dir;
                if (j < 0 || j >= this.sections.length) return;
                [this.sections[i], this.sections[j]] = [this.sections[j], this.sections[i]];
            },
        };
    }
</script>
@endsection
