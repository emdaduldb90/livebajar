<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div><label for="name" class="admin-label">Brand Name *</label><input id="name" name="name" value="{{ old('name', $brand->name) }}" required maxlength="120" class="paper-input admin-input"></div>
    <div><label for="slug" class="admin-label">URL Slug</label><input id="slug" name="slug" value="{{ old('slug', $brand->slug) }}" maxlength="140" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="Generated from brand name if blank" class="paper-input admin-input"></div>
    <label class="md:col-span-2 flex items-center gap-2 text-sm font-semibold text-gray-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand->is_active)) class="accent-brandRed w-4 h-4"><span>Available for new products</span></label>
</div>
