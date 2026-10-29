<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfitController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $items = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'delivered')
            ->where('orders.payment_status', 'paid')
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('orders.created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('orders.created_at', '<=', $to));

        $summary = (clone $items)->selectRaw(
            'COALESCE(SUM(order_items.line_total), 0) AS revenue, '
            .'COALESCE(SUM(COALESCE(order_items.purchase_price, 0) * order_items.quantity), 0) AS cost, '
            .'COALESCE(SUM(CASE WHEN order_items.purchase_price IS NULL THEN order_items.quantity ELSE 0 END), 0) AS missing_cost_units, '
            .'COUNT(DISTINCT orders.id) AS order_count'
        )->first();

        $revenue = (float) ($summary->revenue ?? 0);
        $cost = (float) ($summary->cost ?? 0);
        $profit = $revenue - $cost;

        $productProfit = (clone $items)
            ->selectRaw(
                'order_items.product_id, order_items.sku, order_items.product_name, '
                .'SUM(order_items.quantity) AS units_sold, '
                .'SUM(order_items.line_total) AS revenue, '
                .'SUM(COALESCE(order_items.purchase_price, 0) * order_items.quantity) AS cost, '
                .'SUM(order_items.line_total) - SUM(COALESCE(order_items.purchase_price, 0) * order_items.quantity) AS profit, '
                .'SUM(CASE WHEN order_items.purchase_price IS NULL THEN order_items.quantity ELSE 0 END) AS missing_cost_units'
            )
            ->groupBy('order_items.product_id', 'order_items.sku', 'order_items.product_name')
            ->orderByDesc('profit')
            ->limit(100)
            ->get();

        return view('admin.profit.index', compact('filters', 'summary', 'revenue', 'cost', 'profit', 'productProfit'));
    }
}
