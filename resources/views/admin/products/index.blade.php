<x-layouts.admin heading="Products">
    <div class="flex flex-col lg:flex-row gap-4 justify-between mb-6">
        <form method="GET" class="flex gap-2 w-full lg:max-w-md">
            <input name="q" value="{{ $search }}" type="search" placeholder="Search product name..." class="paper-input px-4 py-2.5 text-sm flex-1">
            <button class="paper-btn px-4 py-2.5 text-sm font-bold">Search</button>
        </form>
        <a href="{{ route('admin.products.create') }}" class="paper-btn bg-brandRed text-white px-5 py-3 font-bold text-sm rounded-xl hover:bg-brandRedHover inline-flex items-center justify-center gap-2"><i class="fas fa-plus" aria-hidden="true"></i>Add Product</a>
    </div>
    <div class="paper-card p-3 md:p-5 overflow-x-auto">
        <table class="admin-table">
            <thead><tr><th>Product</th><th>Categories / Brand</th><th>Price</th><th>Stock</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td><div class="flex items-center gap-3 min-w-[240px]"><img src="{{ $product->image_url }}" alt="" width="42" height="42" class="w-11 h-11 rounded-lg object-cover bg-white"><div><p class="font-bold text-gray-900">{{ $product->name }}</p><p class="text-xs text-gray-500">{{ $product->sku }}</p></div></div></td>
                        <td><p>{{ $product->categories->pluck('name')->join(', ') ?: $product->category->name }}</p><p class="text-xs text-gray-500">{{ $product->brand?->name ?: 'No brand' }}</p></td>
                        <td class="font-bold">{{ \App\Support\Money::display($product->price_cents) }}</td>
                        <td><span class="{{ $product->stock_quantity <= 5 ? 'text-brandRed font-black' : 'text-gray-700' }}">{{ $product->stock_quantity }}</span></td>
                        <td><span class="admin-status {{ $product->is_active ? 'admin-status-delivered' : 'admin-status-cancelled' }}">{{ $product->is_active ? 'Active' : 'Archived' }}</span></td>
                        <td><div class="flex justify-end gap-3"><a href="{{ route('admin.products.edit', $product) }}" class="text-sm font-bold text-brandRed hover:underline">Edit</a><form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product? Existing orders will keep their item history.')">@csrf @method('DELETE')<button class="text-sm font-bold text-gray-500 hover:text-brandRed">Delete</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-500 py-10">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</x-layouts.admin>
