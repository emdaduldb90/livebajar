<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->startOfDay();
        $stats = [
            'products' => Product::query()->count(),
            'low_stock' => Product::query()->active()->where('stock_quantity', '<=', 5)->count(),
            'pending_orders' => Order::query()->where('status', 'pending')->count(),
            'today_sales' => (float) Order::query()
                ->whereNotIn('status', ['cancelled'])
                ->where('created_at', '>=', $today)
                ->sum('total'),
        ];

        $recentOrders = Order::query()->latest()->limit(8)->get();
        $lowStockProducts = Product::query()->active()
            ->where('stock_quantity', '<=', 5)
            ->orderBy('stock_quantity')
            ->limit(8)
            ->get(['id', 'name', 'slug', 'stock_quantity']);

        return view('admin.dashboard', compact('stats', 'recentOrders', 'lowStockProducts'));
    }
}
