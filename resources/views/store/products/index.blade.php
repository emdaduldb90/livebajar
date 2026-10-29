<x-layouts.store :title="$seo['title']" :seo="$seo" :tracking-context="$trackingContext">
    <div class="space-y-8">
        <div class="paper-card p-6 md:p-8 bg-gradient-to-r from-[#f2eee3] to-[#e4dec9] flex flex-col md:flex-row justify-between items-center gap-4">
            <div><h1 class="text-3xl font-black text-gray-900">Product Catalog</h1><p class="text-sm text-gray-600 mt-1">Explore our complete collection of curated paper-crafted goods.</p></div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <aside class="space-y-6">
                @if ($selectedCategory)
                    <div class="md:hidden paper-card p-4 flex justify-between items-center">
                        <h2 class="font-bold text-gray-900">Filters</h2>
                        <button type="button" data-category-filter-toggle aria-controls="category-filter-panel" aria-expanded="false" class="text-sm font-bold text-brandRed hover:underline">Show</button>
                    </div>
                @endif
                <form id="category-filter-panel" method="GET" action="{{ $selectedCategory ? route('categories.show', ['category' => $selectedCategory->slug]) : route('products.index') }}" class="paper-card p-6 space-y-6 {{ $selectedCategory ? 'hidden md:block' : '' }}">
                    <div class="{{ $selectedCategory ? 'hidden md:flex' : 'flex' }} justify-between items-center border-b border-gray-200 pb-4"><h2 class="font-bold text-gray-900">Filters</h2><a href="{{ route('products.index') }}" class="text-xs text-brandRed font-bold hover:underline">Reset All</a></div>
                    <div class="space-y-3"><h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Categories</h3><div class="space-y-2 text-sm"><a href="{{ route('products.index') }}" class="block {{ $selectedCategory ? 'text-gray-700' : 'font-bold text-brandRed' }} hover:text-brandRed">All products</a>@foreach($categories as $category)<a href="{{ route('categories.show', ['category' => $category->slug]) }}" class="block {{ $selectedCategory?->id === $category->id ? 'font-bold text-brandRed' : 'text-gray-700' }} hover:text-brandRed">{{ $category->name }}</a>@endforeach</div></div>
                    @php($selectedMaxPrice = (int) ($filters['max_price'] ?? $priceRangeMax))
                    <div class="space-y-3 pt-4 border-t border-gray-200"><label for="max_price" class="flex justify-between text-xs font-bold text-gray-700 uppercase tracking-wider"><span>Max Price</span><span class="text-brandRed">{{ \App\Support\Money::display($selectedMaxPrice * 100) }}</span></label><input id="max_price" type="range" name="max_price" min="0" max="{{ $priceRangeMax }}" step="10" value="{{ min($selectedMaxPrice, $priceRangeMax) }}" class="w-full accent-brandRed cursor-pointer"></div>
                    <div class="space-y-3 pt-4 border-t border-gray-200"><label for="filter_q" class="text-xs font-bold text-gray-700 uppercase tracking-wider">Keyword</label><input id="filter_q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search catalog" class="paper-input py-2 px-3 text-xs w-full"></div>
                    <div class="space-y-3 pt-4 border-t border-gray-200"><label for="sort" class="text-xs font-bold text-gray-700 uppercase tracking-wider">Sort By</label><select id="sort" name="sort" class="paper-input py-2 px-3 text-xs w-full"><option value="featured" @selected(($filters['sort'] ?? 'featured') === 'featured')>Featured / Default</option><option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Price: Low to High</option><option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Price: High to Low</option><option value="rating" @selected(($filters['sort'] ?? '') === 'rating')>Highest Rated</option></select></div>
                    <button class="paper-btn w-full py-3 bg-brandRed text-white text-xs font-bold rounded-xl hover:bg-brandRedHover">Apply Filters</button>
                </form>
            </aside>
            <div class="lg:col-span-3">
                @if ($products->isEmpty())
                    <div class="paper-card p-12 text-center text-gray-500"><i class="fas fa-search text-4xl mb-3 text-gray-400" aria-hidden="true"></i><h2 class="font-bold text-lg text-gray-800">No products found</h2><p class="text-sm mt-1">Try adjusting your filters or search query.</p><a href="{{ route('products.index') }}" class="inline-block mt-4 paper-btn px-6 py-2 bg-brandRed text-white text-xs font-semibold rounded-xl">Reset Filters</a></div>
                @else
                    <div id="catalog-product-list" data-tracking-results="{{ $products->total() }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">@foreach ($products as $product)<x-product-card :product="$product" :position="$loop->iteration" />@endforeach</div>
                    <div class="mt-8">{{ $products->links() }}</div>
                @endif
            </div>
        </div>
    </div>
    @if ($selectedCategory)
        <script src="{{ asset('js/category-filter.js') }}?v={{ filemtime(public_path('js/category-filter.js')) }}" defer></script>
    @endif
</x-layouts.store>
