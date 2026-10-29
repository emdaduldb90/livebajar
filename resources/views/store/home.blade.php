<x-layouts.store :title="$seo['title']" :seo="$seo" :tracking-context="$trackingContext">
    @php
        $hero = $settings['hero'] ?? [];
        $heroImage = $hero['image'] ?? '';
        $heroImageUrl = $heroImage !== '' && ! \Illuminate\Support\Str::startsWith($heroImage, ['http://', 'https://', '//']) ? asset('storage/'.ltrim($heroImage, '/')) : $heroImage;
        $trustBlocks = $settings['trust_blocks'] ?? [
            ['icon' => 'fa-truck-fast', 'title' => 'Lightning Fast Delivery', 'text' => 'Free delivery on orders over ৳150.00'],
            ['icon' => 'fa-shield-halved', 'title' => 'Secure Paper Payments', 'text' => '100% secure checkout systems'],
            ['icon' => 'fa-headset', 'title' => '24/7 Dedicated Support', 'text' => 'Always ready to assist you'],
        ];
    @endphp
    <div class="space-y-16">
        <section class="paper-card p-8 sm:p-12 md:p-16 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-8 bg-gradient-to-br from-[#fbf9f4] to-[#ede7d5]" data-tracking-visible-event="Viewed Promotion" data-tracking-visible-properties="{{ app(\App\Services\Tracking\TrackingContext::class)->encode(['creative' => 'new_paper_crafted_collection']) }}">
            <div class="max-w-xl z-10 space-y-6">
                <span class="inline-block py-1 px-3 bg-brandRed/10 text-brandRed font-bold text-xs uppercase tracking-wider rounded-full">{{ $hero['badge'] ?? 'New Paper-Crafted Collection 2026' }}</span>
                <h2 class="text-3xl sm:text-5xl font-black text-gray-900 leading-tight logo-text">{{ $hero['title'] ?? 'Experience Shopping in' }} <span class="text-brandRed">{{ $hero['accent'] ?? 'Tangible Style' }}</span></h2>
                <p class="text-gray-600 text-base leading-relaxed">{{ $hero['description'] ?? 'Welcome to Live Bajar. Discover handpicked electronics, apparel, home decor, and organic groceries presented with an exquisite paper aesthetic.' }}</p>
                <div class="flex flex-wrap gap-4 pt-2">
                    <a href="{{ $hero['primary_url'] ?? route('products.index') }}" class="paper-btn px-8 py-3.5 bg-brandRed text-white font-bold rounded-xl shadow-lg hover:bg-brandRedHover flex items-center gap-2" data-tracking-ecommerce="Clicked Promotion" data-tracking-properties="{{ app(\App\Services\Tracking\TrackingContext::class)->encode(['creative' => 'new_paper_crafted_collection']) }}"><span>{{ $hero['primary_text'] ?? 'Explore Shop' }}</span><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    <a href="{{ $hero['secondary_url'] ?? route('categories.show', ['category' => 'electronics']) }}" class="paper-btn px-6 py-3.5 font-semibold text-gray-800 rounded-xl">{{ $hero['secondary_text'] ?? 'View Electronics' }}</a>
                </div>
            </div>
            <div class="relative w-full max-w-sm aspect-square paper-card-inset flex items-center justify-center p-4 bg-gradient-to-tr from-[#e8e2ce] to-[#f7f4ed]">
                @if ($heroImageUrl)
                    <img src="{{ $heroImageUrl }}" alt="{{ $hero['image_alt'] ?? 'Live Bajar hero image' }}" fetchpriority="high" decoding="async" class="relative block w-full aspect-square object-cover rounded-xl">
                @else
                    <div class="absolute inset-4 border-2 border-dashed border-gray-400/40 rounded-xl flex flex-col items-center justify-center text-center p-4">
                        <div class="w-24 h-24 paper-card flex items-center justify-center bg-white mb-4 shadow-xl"><svg viewBox="0 0 100 100" class="w-16 h-16" aria-hidden="true"><path d="M15 30 H25 L35 65 H75 L85 35 H30" fill="none" stroke="#2d2d2d" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="40" cy="75" r="8" fill="#d62828"/><circle cx="70" cy="75" r="8" fill="#d62828"/><path d="M45 40 Q55 25 65 40 T85 40" fill="#d62828"/></svg></div>
                        <h3 class="font-bold text-gray-800 text-lg">Live Bajar Cart</h3><p class="text-xs text-gray-500 mt-1">Paper Cut Elegance</p>
                    </div>
                @endif
            </div>
        </section>

        <section>
            <div class="flex justify-between items-center mb-8"><h2 class="text-2xl font-black text-gray-900 tracking-tight">Top Categories</h2><a href="{{ route('products.index') }}" class="text-sm font-bold text-brandRed hover:underline">View All &rarr;</a></div>
            @php($icons = ['electronics' => 'fa-headphones-simple', 'fashion' => 'fa-shirt', 'home-living' => 'fa-couch', 'groceries' => 'fa-basket-shopping'])
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">@foreach ($categories as $category)<a href="{{ route('categories.show', ['category' => $category->slug]) }}" class="paper-card p-6 text-center cursor-pointer hover:-translate-y-1 transition-all group"><div class="w-16 h-16 paper-card-inset mx-auto flex items-center justify-center mb-4 text-brandRed text-2xl group-hover:bg-brandRed group-hover:text-white transition-colors"><i class="fas {{ $icons[$category->slug] ?? 'fa-bag-shopping' }}" aria-hidden="true"></i></div><h3 class="font-bold text-gray-800 text-sm">{{ $category->name }}</h3><p class="text-xs text-gray-500 mt-1">{{ $category->description }}</p></a>@endforeach</div>
        </section>

        <section>
            <div class="flex justify-between items-center mb-8"><h2 class="text-2xl font-black text-gray-900 tracking-tight">Featured Products</h2><a href="{{ route('products.index') }}" class="text-sm font-bold text-brandRed hover:underline">Browse Catalog &rarr;</a></div>
            <div id="featured-product-list" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">@foreach ($featuredProducts as $product)<x-product-card :product="$product" :position="$loop->iteration" />@endforeach</div>
        </section>

        <section class="paper-card p-8 grid grid-cols-1 md:grid-cols-3 gap-6 bg-[#f4f0e6]">@foreach ($trustBlocks as $block)<div class="flex items-center gap-4"><div class="w-12 h-12 paper-card flex items-center justify-center bg-white text-brandRed text-xl"><i class="fas {{ $block['icon'] ?? 'fa-circle-check' }}" aria-hidden="true"></i></div><div><h3 class="font-bold text-gray-800 text-sm">{{ $block['title'] ?? '' }}</h3><p class="text-xs text-gray-600">{{ $block['text'] ?? '' }}</p></div></div>@endforeach</section>
    </div>
</x-layouts.store>
