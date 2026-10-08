@extends('layouts.app')

@php
    $description = $category->description ?: "Browse {$category->name} handicraft items from Nepal.";
    $normalizedCategory = \Illuminate\Support\Str::lower($category->slug . ' ' . $category->name);
    $faqItems = match (true) {
        str_contains($normalizedCategory, 'statue') => [
            ['question' => 'What materials are these statues made from?', 'answer' => 'Each product page lists the specific material, dimensions, and care information for that statue.'],
            ['question' => 'How are statues prepared for international delivery?', 'answer' => 'Packaging and delivery details are confirmed for each order. Review the shipping policy or contact us before ordering.'],
        ],
        str_contains($normalizedCategory, 'painting') => [
            ['question' => 'Are these paintings original works or reproductions?', 'answer' => 'The product listing identifies the artwork type, material, dimensions, and any included presentation details.'],
            ['question' => 'Does a painting include a frame?', 'answer' => 'Frame and mounting details vary by item and are listed on the individual product page.'],
        ],
        str_contains($normalizedCategory, 'thangka') || str_contains($normalizedCategory, 'thanka') => [
            ['question' => 'What is a thangka?', 'answer' => 'A thangka is a traditional Himalayan scroll painting used for spiritual practice, learning, or display.'],
            ['question' => 'Is mounting included with a thangka?', 'answer' => 'Check the individual product page for its material, mounting, dimensions, and care information.'],
        ],
        str_contains($normalizedCategory, 'singing bowl') => [
            ['question' => 'Will every singing bowl sound the same?', 'answer' => 'Sound varies by each bowl’s size, shape, material, construction, and playing method.'],
            ['question' => 'What is included with a singing bowl?', 'answer' => 'The individual product page lists the items included with that bowl, such as a striker, mallet, or cushion.'],
        ],
        default => [],
    };
@endphp

@section('title', $category->name . ' Handicrafts | ' . $siteSettings['site_name'])
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($description), 160))
@section('canonical', route('categories.show', $category))

@push('head')
    <meta property="og:type" content="website">
    @if($category->image)
        <meta property="og:image" content="{{ \App\Models\Product::mediaUrl($category->image) }}">
    @endif
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $category->name . ' Handicrafts',
        'description' => strip_tags($description),
        'url' => route('categories.show', $category),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @if($faqItems)
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ], $faqItems),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif
@endpush

@section('content')
    <div class="container mx-auto px-4 sm:px-6 py-12">
        <nav class="mb-8 text-sm" aria-label="Breadcrumb">
            <ol class="flex items-center gap-2 text-muted-foreground">
                <li><a href="{{ route('home') }}" class="hover:text-primary">Home</a></li>
                <li aria-hidden="true">/</li>
                <li class="text-foreground">{{ $category->name }}</li>
            </ol>
        </nav>

        <header class="max-w-3xl mb-10">
            <h1 class="text-4xl font-serif font-bold text-foreground mb-4">{{ $category->name }} Handicrafts</h1>
            <p class="text-muted-foreground leading-relaxed">{{ $description }}</p>
            <p class="mt-4 text-sm leading-relaxed text-muted-foreground">Available to international buyers worldwide except Nepal and India. Prices are shown in USD; confirm delivery timing for your destination before ordering.</p>
        </header>

        @if($faqItems)
            <section class="mb-10 rounded-2xl border border-border bg-card p-6 sm:p-8" aria-labelledby="category-faq-heading">
                <h2 id="category-faq-heading" class="mb-5 text-2xl font-serif font-bold text-foreground">Frequently asked questions</h2>
                <div class="space-y-5">
                    @foreach($faqItems as $faq)
                        <div>
                            <h3 class="font-semibold text-foreground">{{ $faq['question'] }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-muted-foreground">{{ $faq['answer'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if($category->subCategories->isNotEmpty())
            <nav class="flex flex-wrap gap-3 mb-10" aria-label="{{ $category->name }} subcategories">
                @foreach($category->subCategories as $subCategory)
                    <a href="{{ route('home', ['category' => $category->slug, 'subcategory' => $subCategory->slug]) }}"
                        class="rounded-full border border-border bg-card px-4 py-2 text-sm font-medium text-foreground hover:border-primary hover:text-primary">
                        {{ $subCategory->name }}
                    </a>
                @endforeach
            </nav>
        @endif

        <section aria-labelledby="products-heading">
            <h2 id="products-heading" class="sr-only">{{ $category->name }} products</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($products as $product)
                    <article class="group overflow-hidden rounded-2xl border border-border bg-card shadow-xs transition hover:shadow-xl">
                        <a href="{{ route('products.show', $product->slug) }}" class="block">
                            <div class="aspect-[3/4] overflow-hidden bg-muted">
                                @if($product->main_image)
                                    <img src="{{ $product->main_image }}" alt="{{ $product->name }}" loading="lazy"
                                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-serif font-bold text-foreground">{{ $product->name }}</h3>
                                <p class="mt-2 font-semibold text-primary">${{ number_format($product->effective_price, 2) }}</p>
                            </div>
                        </a>
                    </article>
                @empty
                    <p class="col-span-full text-muted-foreground">Products in this collection will be available soon.</p>
                @endforelse
            </div>
            <div class="mt-10">{{ $products->links() }}</div>
        </section>
    </div>
@endsection
