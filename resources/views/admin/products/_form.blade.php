@php
    $mainCategoryId = (int) old('category_id', $product->category_id);
    $selectedAdditionalCategoryIds = collect(old('additional_category_ids', $product->exists ? $product->categories->pluck('id')->all() : []))
        ->map(fn ($id) => (int) $id)
        ->reject(fn (int $id) => $id === $mainCategoryId)
        ->all();
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div><label for="name" class="admin-label">Product Name *</label><input id="name" name="name" value="{{ old('name', $product->name) }}" required maxlength="180" class="paper-input admin-input"></div>
    <div><label for="category_id" class="admin-label">Category *</label><select id="category_id" name="category_id" required class="paper-input admin-input">@foreach($categories as $category)<option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (hidden)' }}</option>@endforeach</select></div>
    <fieldset class="md:col-span-2 paper-card-inset p-4">
        <legend class="admin-label px-1">Additional categories</legend>
        <p class="admin-help mb-3">The primary category above is always included. Select any other categories where this product should also appear.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach ($categories as $category)
                @if ((int) $category->id !== $mainCategoryId)
                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700">
                        <input type="checkbox" name="additional_category_ids[]" value="{{ $category->id }}" @checked(in_array((int) $category->id, $selectedAdditionalCategoryIds, true)) class="accent-brandRed w-4 h-4">
                        <span>{{ $category->name }}{{ $category->is_active ? '' : ' (hidden)' }}</span>
                    </label>
                @endif
            @endforeach
        </div>
    </fieldset>
    <div><label for="brand_id" class="admin-label">Brand</label><select id="brand_id" name="brand_id" class="paper-input admin-input"><option value="">No brand</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected((int) old('brand_id', $product->brand_id) === $brand->id)>{{ $brand->name }}{{ $brand->is_active ? '' : ' (hidden)' }}</option>@endforeach</select><p class="admin-help">Create brands from the Brands menu.</p></div>
    <div><label for="sku" class="admin-label">SKU *</label><input id="sku" name="sku" value="{{ old('sku', $product->sku) }}" required maxlength="64" class="paper-input admin-input"></div>
    <div><label for="slug" class="admin-label">URL Slug *</label><input id="slug" name="slug" value="{{ old('slug', $product->slug) }}" required maxlength="200" pattern="[a-z0-9]+(-[a-z0-9]+)*" class="paper-input admin-input"></div>
    <div><label for="badge" class="admin-label">Badge</label><input id="badge" name="badge" value="{{ old('badge', $product->badge) }}" maxlength="40" placeholder="e.g. Best Seller" class="paper-input admin-input"></div>
    <div class="md:col-span-2"><label for="short_description" class="admin-label">Short Description *</label><input id="short_description" name="short_description" value="{{ old('short_description', $product->short_description) }}" required maxlength="500" class="paper-input admin-input"></div>
    <div class="md:col-span-2"><label for="description" class="admin-label">Full Description</label><textarea id="description" name="description" rows="5" maxlength="10000" class="paper-input admin-input resize-y">{{ old('description', $product->description) }}</textarea></div>

    <div class="md:col-span-2 border-t border-gray-200 pt-5"><h2 class="font-black text-gray-900">SEO</h2><p class="admin-help mt-1">Optional search title, description and keywords for this product page.</p></div>
    <div><label for="meta_title" class="admin-label">SEO Title</label><input id="meta_title" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}" maxlength="70" class="paper-input admin-input"></div>
    <div><label for="meta_keywords" class="admin-label">SEO Keywords</label><input id="meta_keywords" name="meta_keywords" value="{{ old('meta_keywords', $product->meta_keywords) }}" maxlength="255" class="paper-input admin-input"></div>
    <div class="md:col-span-2"><label for="meta_description" class="admin-label">SEO Description</label><textarea id="meta_description" name="meta_description" rows="3" maxlength="160" class="paper-input admin-input resize-y">{{ old('meta_description', $product->meta_description) }}</textarea></div>

    <div class="md:col-span-2 border-t border-gray-200 pt-5"><h2 class="font-black text-gray-900">Product Detail Page Content</h2><p class="admin-help mt-1">These fields appear in the Key Features, Specification, Questions and Reviews sections on the product page. Leave a field empty to hide that section.</p></div>
    <div class="md:col-span-2"><label for="key_features_text" class="admin-label">Key Features</label><textarea id="key_features_text" name="key_features_text" rows="5" maxlength="10000" placeholder="Display: 8.7 inch IPS LCD&#10;Battery: 7020 mAh&#10;Warranty: 1 year service warranty" class="paper-input admin-input resize-y">{{ old('key_features_text', $product->keyFeaturesText()) }}</textarea><p class="admin-help">Write one feature per line. Example: <strong>Display: 8.7 inch IPS LCD</strong></p></div>
    <div class="md:col-span-2"><label for="specifications_text" class="admin-label">Specifications</label><textarea id="specifications_text" name="specifications_text" rows="8" maxlength="30000" placeholder="Main Features | Display Type | IPS LCD, 90Hz&#10;Main Features | Screen Size | 8.7 inches&#10;Battery | Capacity | 7020 mAh&#10;Warranty | Warranty | 1 year service warranty" class="paper-input admin-input resize-y">{{ old('specifications_text', $product->specificationsText()) }}</textarea><p class="admin-help">One row per line: <strong>Section | Feature name | Feature value</strong>. Rows using the same section name are grouped together.</p></div>
    <div class="md:col-span-2"><label for="faqs_text" class="admin-label">Questions &amp; Answers</label><textarea id="faqs_text" name="faqs_text" rows="5" maxlength="20000" placeholder="Does it support Wi-Fi? | Yes, it supports Wi-Fi connectivity.&#10;What is the warranty? | One year service warranty." class="paper-input admin-input resize-y">{{ old('faqs_text', $product->faqsText()) }}</textarea><p class="admin-help">One question per line: <strong>Question | Answer</strong></p></div>
    <div class="md:col-span-2"><label for="customer_reviews_text" class="admin-label">Customer Reviews</label><textarea id="customer_reviews_text" name="customer_reviews_text" rows="5" maxlength="30000" placeholder="5 | Rahim Ahmed | Excellent product and fast delivery.&#10;4 | Fatema Begum | Good quality for the price." class="paper-input admin-input resize-y">{{ old('customer_reviews_text', $product->customerReviewsText()) }}</textarea><p class="admin-help">One review per line: <strong>Rating (1–5) | Customer name | Review text</strong></p></div>

    <div><label for="price" class="admin-label">Selling Price ({{ config('store.currency_symbol') }}) *</label><input id="price" name="price" type="number" min="0" step="0.01" value="{{ old('price', $product->price) }}" required class="paper-input admin-input"></div>
    <div><label for="compare_at_price" class="admin-label">Original / Compare Price ({{ config('store.currency_symbol') }})</label><input id="compare_at_price" name="compare_at_price" type="number" min="0" step="0.01" value="{{ old('compare_at_price', $product->compare_at_price) }}" class="paper-input admin-input"></div>
    <div><label for="purchase_price" class="admin-label">Purchase Cost ({{ config('store.currency_symbol') }})</label><input id="purchase_price" name="purchase_price" type="number" min="0" step="0.01" value="{{ old('purchase_price', $product->purchase_price) }}" class="paper-input admin-input"><p class="admin-help">Admin only. This is never shown on the storefront.</p></div>
    <div><label for="stock_quantity" class="admin-label">Stock Quantity *</label><input id="stock_quantity" name="stock_quantity" type="number" min="0" step="1" value="{{ old('stock_quantity', $product->stock_quantity) }}" required class="paper-input admin-input"></div>
    <div><label for="rating" class="admin-label">Rating *</label><input id="rating" name="rating" type="number" min="0" max="5" step="0.1" value="{{ old('rating', $product->rating) }}" required class="paper-input admin-input"></div>
    <div><label for="reviews_count" class="admin-label">Review Count *</label><input id="reviews_count" name="reviews_count" type="number" min="0" step="1" value="{{ old('reviews_count', $product->reviews_count) }}" required class="paper-input admin-input"></div>

    <div class="md:col-span-2 border-t border-gray-200 pt-5"><h2 class="font-black text-gray-900">Product Images</h2><p class="admin-help mt-1">PNG, JPG, JPEG or WEBP only. Maximum 5 MB and 4096 × 4096 pixels per file. Uploaded files are automatically saved as optimized WebP (target: 50–70 KB).</p></div>
    <div><label for="image_upload" class="admin-label">Upload Main Image</label><input id="image_upload" name="image_upload" type="file" accept="image/png,image/jpeg,image/webp" class="paper-input admin-input py-2"><p class="admin-help">Upload takes priority over the image link below and is saved as optimized WebP.</p></div>
    <div><label for="image_url" class="admin-label">Or use Image Link / Local Path</label><input id="image_url" name="image_url" type="text" value="{{ old('image_url', $product->image_url) }}" maxlength="2048" placeholder="https://... or products/folder/image.png" class="paper-input admin-input"></div>
    <div class="md:col-span-2"><label for="gallery_uploads" class="admin-label">Upload Gallery Images</label><input id="gallery_uploads" name="gallery_uploads[]" type="file" multiple accept="image/png,image/jpeg,image/webp" class="paper-input admin-input py-2"><p class="admin-help">You can add up to 12 gallery images at a time. Alt text can be edited in File Manager.</p></div>
    @if ($product->exists && $product->images->isNotEmpty())
        <div class="md:col-span-2"><p class="admin-label">Current Gallery</p><div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">@foreach ($product->images as $image)<label class="paper-card-inset p-2 space-y-2"><img src="{{ $image->url }}" alt="{{ $image->mediaAsset?->alt_text ?: $product->name }}" class="w-full aspect-square object-cover rounded-lg"><span class="flex items-center gap-2 text-xs font-bold text-red-700"><input type="checkbox" name="remove_gallery_images[]" value="{{ $image->id }}" class="accent-brandRed">Remove from gallery</span></label>@endforeach</div></div>
    @endif

    <div class="md:col-span-2 flex flex-wrap gap-6"><label class="flex items-center gap-2 text-sm font-semibold text-gray-700"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) class="accent-brandRed w-4 h-4"><span>Show as featured</span></label><label class="flex items-center gap-2 text-sm font-semibold text-gray-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="accent-brandRed w-4 h-4"><span>Visible on storefront</span></label></div>
</div>
