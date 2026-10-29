<x-layouts.store :title="$seo['title']" :seo="$seo" :tracking-context="$trackingContext">
    <div class="space-y-12">
        <nav class="text-xs text-gray-500 flex items-center gap-2" aria-label="Breadcrumb"><a href="{{ route('home') }}" class="hover:text-brandRed">Home</a><span>/</span><a href="{{ route('categories.show', ['category' => $product->category->slug]) }}" class="hover:text-brandRed">{{ $product->category->name }}</a><span>/</span><span class="text-gray-800 font-semibold truncate max-w-xs">{{ $product->name }}</span></nav>

        <div class="paper-card p-6 md:p-10 grid grid-cols-1 md:grid-cols-2 gap-10">
            @php
                $mainImageUrl = $product->image_url;
                $mainImageAlt = $product->mainImageAsset?->alt_text ?: $product->name;
                $galleryImages = $product->images;
                $hasMainImageThumbnail = $galleryImages->contains(fn ($image) => $image->url === $mainImageUrl);
                $thumbnailPosition = 0;
                $keyFeatures = $product->key_features ?? [];
                $specifications = $product->specifications ?? [];
                $faqs = $product->faqs ?? [];
                $customerReviews = $product->customer_reviews ?? [];
                $description = $product->description ?: $product->short_description;
                $reviewCount = count($customerReviews) ?: $product->reviews_count;
                $defaultDetailTab = 'specifications';
                $shareUrl = route('products.show', $product);
                $shareTracking = app(\App\Services\Tracking\TrackingContext::class)->encode(['product_id' => $product->sku]);
                $faqSchema = $faqs === [] ? null : [
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => collect($faqs)->filter(fn ($faq) => ! empty($faq['question']) && ! empty($faq['answer']))->map(fn ($faq) => [
                        '@type' => 'Question',
                        'name' => $faq['question'],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                    ])->values()->all(),
                ];
            @endphp
            <div class="space-y-4" data-product-gallery>
                <div class="aspect-square paper-card-inset overflow-hidden bg-white flex items-center justify-center p-4"><img data-gallery-main-image src="{{ $mainImageUrl }}" alt="{{ $mainImageAlt }}" width="600" height="600" class="product-gallery-main-image w-full h-full object-cover rounded-xl"></div>
                @if ($galleryImages->isNotEmpty() || $mainImageUrl)
                    <div class="product-gallery-thumbnails" aria-label="Product image gallery">
                        @if (! $hasMainImageThumbnail && $mainImageUrl)
                            @php
                                $thumbnailPosition++;
                            @endphp
                            <button type="button" data-gallery-thumbnail data-gallery-image-src="{{ $mainImageUrl }}" data-gallery-image-alt="{{ $mainImageAlt }}" data-tracking-custom="ProductImageViewed" data-tracking-properties="{{ app(\App\Services\Tracking\TrackingContext::class)->encode(['product_id' => $product->sku, 'image_position' => $thumbnailPosition]) }}" class="product-gallery-thumbnail paper-card-inset is-active" aria-label="View product image {{ $thumbnailPosition }}" aria-pressed="true"><img src="{{ $mainImageUrl }}" alt="" width="96" height="96" class="w-full h-full object-cover rounded-lg"></button>
                        @endif
                        @foreach ($galleryImages as $image)
                            @php
                                $thumbnailPosition++;
                                $isActive = $image->url === $mainImageUrl;
                                $imageAlt = $image->mediaAsset?->alt_text ?: $product->name.' image '.$thumbnailPosition;
                            @endphp
                            <button type="button" data-gallery-thumbnail data-gallery-image-src="{{ $image->url }}" data-gallery-image-alt="{{ $imageAlt }}" data-tracking-custom="ProductImageViewed" data-tracking-properties="{{ app(\App\Services\Tracking\TrackingContext::class)->encode(['product_id' => $product->sku, 'image_position' => $thumbnailPosition]) }}" class="product-gallery-thumbnail paper-card-inset {{ $isActive ? 'is-active' : '' }}" aria-label="View product image {{ $thumbnailPosition }}" aria-pressed="{{ $isActive ? 'true' : 'false' }}"><img src="{{ $image->url }}" alt="" width="96" height="96" loading="lazy" class="w-full h-full object-cover rounded-lg"></button>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="flex flex-col justify-between space-y-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3"><div class="flex flex-wrap gap-2">@foreach ($product->categories as $category)<a href="{{ route('categories.show', ['category' => $category->slug]) }}" class="text-xs font-bold uppercase tracking-wider bg-brandRed/10 text-brandRed px-3 py-1 rounded-full">{{ $category->name }}</a>@endforeach @if ($product->brand)<span class="text-xs font-bold uppercase tracking-wider bg-gray-100 text-gray-700 px-3 py-1 rounded-full">{{ $product->brand->name }}</span>@endif</div><span class="text-xs font-semibold {{ $product->stock_quantity > 0 ? 'text-emerald-600' : 'text-brandRed' }} flex items-center gap-1 whitespace-nowrap"><i class="fas fa-circle text-[8px]" aria-hidden="true"></i>{{ $product->stock_quantity > 0 ? 'In Stock & Ready' : 'Currently Unavailable' }}</span></div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900">{{ $product->name }}</h1>
                    <div class="flex items-center gap-2 text-sm"><div class="flex text-amber-500"><i class="fas fa-star" aria-hidden="true"></i></div><span class="font-bold text-gray-800">{{ number_format($product->rating, 1) }}</span><span class="text-gray-400">({{ $product->reviews_count }} customer reviews)</span></div>
                    <div class="flex items-baseline gap-3 pt-2"><span class="text-3xl font-black text-gray-900">{{ \App\Support\Money::display($product->price_cents) }}</span>@if($product->compare_at_price)<span class="text-lg text-gray-400 line-through">{{ \App\Support\Money::display($product->compare_at_price_cents) }}</span><span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded">Save {{ \App\Support\Money::display($product->compare_at_price_cents - $product->price_cents) }}</span>@endif</div>
                    <p class="text-gray-600 text-sm leading-relaxed pt-2 border-t border-gray-200">{{ $product->short_description }}</p>
                    @if ($keyFeatures !== [])
                        <section class="pt-2"><h2 class="text-sm font-black text-gray-900 mb-2">Key Features</h2><ul class="space-y-1.5 text-xs sm:text-sm text-gray-600">@foreach ($keyFeatures as $feature)<li class="flex gap-2"><i class="fas fa-check text-brandRed mt-1" aria-hidden="true"></i><span>{{ $feature }}</span></li>@endforeach</ul></section>
                    @endif
                    @if ($product->stock_quantity > 0)
                        <form action="{{ route('cart.store', $product->id) }}" method="POST" class="space-y-4 pt-4">@csrf
                            <div class="flex items-center gap-4"><label for="quantity" class="text-xs font-bold text-gray-700 uppercase tracking-wider">Quantity:</label><input id="quantity" name="quantity" type="number" min="1" max="{{ min(20, $product->stock_quantity) }}" value="1" class="w-20 py-2 px-3 paper-input font-bold text-sm text-gray-800"></div>
                            <div class="flex flex-wrap gap-4 pt-2"><button class="paper-btn flex-1 py-3.5 px-6 bg-brandRed text-white font-bold rounded-xl shadow-md hover:bg-brandRedHover flex items-center justify-center gap-2"><i class="fas fa-cart-plus" aria-hidden="true"></i><span>Add to Cart</span></button><button name="checkout" value="1" data-tracking-custom="BuyNowClicked" data-tracking-properties="{{ app(\App\Services\Tracking\TrackingContext::class)->encode(app(\App\Services\Tracking\EcommerceTrackingService::class)->customProduct($product)) }}" class="paper-btn py-3.5 px-6 bg-gray-900 text-white font-bold rounded-xl shadow-md hover:bg-gray-800">Buy Now</button></div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <section class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18rem] gap-6 items-start">
            <div class="space-y-5">
                <div class="flex items-center gap-2 text-xs text-gray-500 px-1"><span class="font-bold text-gray-700">Share:</span><a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" data-tracking-custom="ProductShared" data-tracking-properties="{{ $shareTracking }}" class="w-7 h-7 rounded-full paper-card-inset flex items-center justify-center text-brandRed hover:bg-brandRed hover:text-white" aria-label="Share on Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a><a href="https://wa.me/?text={{ urlencode($product->name.' '.$shareUrl) }}" target="_blank" rel="noopener" data-tracking-custom="ProductShared" data-tracking-properties="{{ $shareTracking }}" class="w-7 h-7 rounded-full paper-card-inset flex items-center justify-center text-brandRed hover:bg-brandRed hover:text-white" aria-label="Share on WhatsApp"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a><button type="button" data-product-share data-share-title="{{ $product->name }}" data-share-url="{{ $shareUrl }}" data-tracking-custom="ProductShared" data-tracking-properties="{{ $shareTracking }}" class="w-7 h-7 rounded-full paper-card-inset flex items-center justify-center text-brandRed hover:bg-brandRed hover:text-white" aria-label="Copy product link"><i class="fas fa-link" aria-hidden="true"></i></button></div>

                <div class="paper-card overflow-hidden" data-product-details>
                    <div class="flex overflow-x-auto border-b border-gray-200" role="tablist" aria-label="Product information">
                        <button type="button" data-product-tab="specifications" class="product-detail-tab {{ $defaultDetailTab === 'specifications' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $defaultDetailTab === 'specifications' ? 'true' : 'false' }}">Specification</button>
                        <button type="button" data-product-tab="description" class="product-detail-tab {{ $defaultDetailTab === 'description' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $defaultDetailTab === 'description' ? 'true' : 'false' }}">Description</button>
                        <button type="button" data-product-tab="questions" class="product-detail-tab" role="tab" aria-selected="false">Questions ({{ count($faqs) }})</button>
                        <button type="button" data-product-tab="reviews" class="product-detail-tab" role="tab" aria-selected="false">Reviews ({{ $reviewCount }})</button>
                    </div>

                    <div data-product-panel="specifications" class="product-detail-panel {{ $defaultDetailTab === 'specifications' ? '' : 'hidden' }}" role="tabpanel"><h2 class="text-lg font-black text-gray-900 mb-5">Specification</h2>@if ($specifications !== [])<div class="space-y-5">@foreach ($specifications as $section => $items)<section><h3 class="text-xs font-black uppercase tracking-wider text-brandRed bg-brandRed/10 rounded-lg px-3 py-2 mb-2">{{ $section }}</h3><dl class="divide-y divide-gray-200/80">@foreach ($items as $item)<div class="grid grid-cols-1 sm:grid-cols-[10rem_1fr] gap-1 sm:gap-5 py-2.5 text-sm"><dt class="font-semibold text-gray-500">{{ $item['label'] }}</dt><dd class="text-gray-800">{{ $item['value'] }}</dd></div>@endforeach</dl></section>@endforeach</div>@else<div class="product-detail-empty">Specification details will be available soon.</div>@endif</div>

                    <div data-product-panel="description" class="product-detail-panel {{ $defaultDetailTab === 'description' ? '' : 'hidden' }}" role="tabpanel"><h2 class="text-lg font-black text-gray-900 mb-4">Description</h2><div class="text-sm leading-7 text-gray-700 whitespace-pre-line">{{ $description }}</div></div>

                    <div data-product-panel="questions" class="product-detail-panel hidden" role="tabpanel"><h2 class="text-lg font-black text-gray-900 mb-2">Questions &amp; Answers</h2><p class="text-xs text-gray-500 mb-5">Product details answered for shoppers.</p>@if ($faqs !== [])<div class="space-y-4">@foreach ($faqs as $faq)<article class="paper-card-inset p-4"><h3 class="font-bold text-sm text-gray-900 flex gap-2"><span class="text-brandRed">Q.</span>{{ $faq['question'] }}</h3><p class="mt-2 text-sm text-gray-600 flex gap-2"><span class="font-bold text-emerald-600">A.</span><span>{{ $faq['answer'] }}</span></p></article>@endforeach</div>@else<div class="product-detail-empty">No questions have been added for this product yet.</div>@endif</div>

                    <div data-product-panel="reviews" class="product-detail-panel hidden" role="tabpanel"><div class="flex items-center justify-between gap-4 mb-5"><div><h2 class="text-lg font-black text-gray-900">Customer Reviews</h2><p class="text-xs text-gray-500 mt-1">{{ $reviewCount }} review{{ $reviewCount === 1 ? '' : 's' }} from customers.</p></div><div class="flex items-center gap-1 text-amber-500 text-sm"><i class="fas fa-star" aria-hidden="true"></i><span class="font-black text-gray-800">{{ number_format($product->rating, 1) }}</span></div></div>@if ($customerReviews !== [])<div class="space-y-4">@foreach ($customerReviews as $review)<article class="border-b border-gray-200 pb-4 last:border-0 last:pb-0"><div class="flex items-center gap-1 text-amber-500 text-xs">@for ($star = 1; $star <= 5; $star++)<i class="{{ $star <= $review['rating'] ? 'fas' : 'far' }} fa-star" aria-hidden="true"></i>@endfor</div><h3 class="mt-2 text-sm font-bold text-gray-900">{{ $review['name'] }}</h3><p class="mt-1 text-sm leading-6 text-gray-600">{{ $review['body'] }}</p></article>@endforeach</div>@else<div class="product-detail-empty">No customer reviews have been added yet.</div>@endif</div>
                </div>
            </div>

            @if ($relatedProducts->isNotEmpty())
                <aside class="paper-card p-5"><h2 class="text-base font-black text-gray-900 pb-3 border-b border-gray-200">Similar Products</h2><div class="divide-y divide-gray-200/80">@foreach ($relatedProducts as $relatedProduct)<a href="{{ route('products.show', $relatedProduct) }}" class="flex gap-3 py-4 group"><img src="{{ $relatedProduct->image_url }}" alt="{{ $relatedProduct->name }}" width="64" height="64" loading="lazy" class="w-16 h-16 rounded-lg object-cover paper-card-inset"><span class="min-w-0"><span class="block text-xs font-bold leading-5 text-gray-800 group-hover:text-brandRed line-clamp-2">{{ $relatedProduct->name }}</span><span class="block mt-1 text-sm font-black text-brandRed">{{ \App\Support\Money::display($relatedProduct->price_cents) }}</span></span></a>@endforeach</div></aside>
            @endif
        </section>
    </div>
    @if ($faqSchema && $faqSchema['mainEntity'] !== [])
        <script type="application/ld+json" nonce="{{ request()->attributes->get('csp_nonce') }}">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endif
    <script src="{{ asset('js/product-gallery.js') }}?v={{ filemtime(public_path('js/product-gallery.js')) }}" defer></script>
    <script src="{{ asset('js/product-detail.js') }}?v={{ filemtime(public_path('js/product-detail.js')) }}" defer></script>
</x-layouts.store>
