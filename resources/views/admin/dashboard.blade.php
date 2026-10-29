<x-layouts.admin heading="Store Overview">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
        <div class="admin-stat"><div><p class="admin-stat-label">Products</p><p class="admin-stat-number">{{ number_format($stats['products']) }}</p></div><span class="admin-stat-icon bg-blue-50 text-blue-600"><i class="fas fa-box" aria-hidden="true"></i></span></div>
        <div class="admin-stat"><div><p class="admin-stat-label">Pending Orders</p><p class="admin-stat-number">{{ number_format($stats['pending_orders']) }}</p></div><span class="admin-stat-icon bg-amber-50 text-amber-600"><i class="fas fa-clock" aria-hidden="true"></i></span></div>
        <div class="admin-stat"><div><p class="admin-stat-label">Low Stock</p><p class="admin-stat-number">{{ number_format($stats['low_stock']) }}</p></div><span class="admin-stat-icon bg-red-50 text-brandRed"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i></span></div>
        <div class="admin-stat"><div><p class="admin-stat-label">Today’s Sales</p><p class="admin-stat-number">{{ \App\Support\Money::display((int) round($stats['today_sales'] * 100)) }}</p></div><span class="admin-stat-icon bg-emerald-50 text-emerald-600"><i class="fas fa-chart-line" aria-hidden="true"></i></span></div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <section class="paper-card p-5 md:p-6 xl:col-span-2">
            <div class="flex justify-between items-center mb-5"><div><h2 class="font-black text-gray-900 text-lg">Recent Orders</h2><p class="text-sm text-gray-500">Latest customer orders</p></div><a href="{{ route('admin.orders.index') }}" class="text-sm font-bold text-brandRed hover:underline">View all &rarr;</a></div>
            @if($recentOrders->isEmpty())<p class="text-sm text-gray-500 py-8 text-center">No orders yet.</p>@else
                <div class="overflow-x-auto"><table class="admin-table"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>@foreach($recentOrders as $order)<tr><td class="font-bold text-gray-900">{{ $order->order_number }}</td><td><p class="font-semibold text-gray-800">{{ $order->customer_name }}</p><p class="text-xs text-gray-500">{{ $order->customer_email }}</p></td><td class="font-bold">{{ \App\Support\Money::display($order->total_cents) }}</td><td><span class="admin-status admin-status-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td><td><a href="{{ route('admin.orders.show', $order) }}" class="text-brandRed hover:underline text-sm font-bold">Open</a></td></tr>@endforeach</tbody></table></div>
            @endif
        </section>
        <section class="paper-card p-5 md:p-6">
            <div class="flex justify-between items-center mb-5"><div><h2 class="font-black text-gray-900 text-lg">Low Stock Alert</h2><p class="text-sm text-gray-500">5 or fewer units left</p></div><a href="{{ route('admin.products.index') }}" class="text-sm font-bold text-brandRed hover:underline">Products</a></div>
            @if($lowStockProducts->isEmpty())<div class="py-10 text-center text-emerald-700"><i class="fas fa-circle-check text-3xl mb-3" aria-hidden="true"></i><p class="font-bold">Stock levels look good.</p></div>@else
                <ul class="space-y-3">@foreach($lowStockProducts as $product)<li class="paper-card-inset p-3 flex items-center justify-between gap-3"><div class="min-w-0"><p class="font-bold text-sm text-gray-800 truncate">{{ $product->name }}</p><p class="text-xs text-gray-500">{{ $product->slug }}</p></div><span class="bg-red-50 text-brandRed text-xs font-black rounded-full px-2.5 py-1 whitespace-nowrap">{{ $product->stock_quantity }} left</span></li>@endforeach</ul>
            @endif
        </section>
    </div>
</x-layouts.admin>
