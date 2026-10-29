@props(['product', 'position' => null])
@php
    $productTracking = app(\App\Services\Tracking\EcommerceTrackingService::class)->product($product, 1, $position);
    $productTrackingEncoded = app(\App\Services\Tracking\TrackingContext::class)->encode($productTracking);
    $productCategories = $product->categories->isNotEmpty() ? $product->categories : collect([$product->category]);
@endphp

<div class="paper-card p-4 flex flex-col justify-between group hover:-translate-y-1 transition-all">
    <div>
        <a href="{{ route('products.show', $product) }}" data-tracking-ecommerce="Product Clicked" data-tracking-properties="{{ $productTrackingEncoded }}" class="relative block aspect-square paper-card-inset mb-4 overflow-hidden bg-white cursor-pointer">
            <img src="{{ $product->image_url }}" alt="{{ $product->mainImageAsset?->alt_text ?: $product->name }}" width="600" height="600" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            @if ($product->badge)<span class="absolute top-3 left-3 bg-brandRed text-white text-[10px] font-bold px-2.5 py-1 rounded-full shadow">{{ $product->badge }}</span>@endif
        </a>
        <div class="flex flex-wrap gap-x-2 gap-y-1">
            @foreach ($productCategories as $category)
                @if ($category)
                    <a href="{{ route('categories.show', ['category' => $category->slug]) }}" class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider hover:text-brandRed">{{ $category->name }}</a>
                @endif
            @endforeach
        </div>
        <a href="{{ route('products.show', $product) }}" data-tracking-ecommerce="Product Clicked" data-tracking-properties="{{ $productTrackingEncoded }}" class="block font-bold text-gray-800 text-sm mt-1 line-clamp-1 hover:text-brandRed">{{ $product->name }}</a>
        <div class="flex items-center gap-1.5 mt-2 text-amber-500 text-xs"><i class="fas fa-star" aria-hidden="true"></i><span class="font-bold text-gray-700">{{ number_format($product->rating, 1) }}</span><span class="text-gray-400">({{ $product->reviews_count }})</span></div>
    </div>
    <div class="mt-4 pt-3 border-t border-gray-200/60 flex items-center justify-between">
        <div><span class="text-lg font-black text-gray-900">{{ \App\Support\Money::display($product->price_cents) }}</span>@if ($product->compare_at_price)<span class="text-xs text-gray-400 line-through ml-1.5">{{ \App\Support\Money::display($product->compare_at_price_cents) }}</span>@endif</div>
        <form action="{{ route('cart.store', $product->id) }}" method="POST">@csrf<input type="hidden" name="quantity" value="1"><button class="paper-btn p-2.5 bg-brandRed text-white hover:bg-brandRedHover rounded-xl shadow-sm" title="Add to Cart" aria-label="Add {{ $product->name }} to cart"><i class="fas fa-cart-plus text-sm" aria-hidden="true"></i></button></form>
    </div>
</div>
