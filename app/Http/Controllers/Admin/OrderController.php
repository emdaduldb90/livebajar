<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'processing', 'shipped', 'delivered', 'cancelled'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $orders = Order::query()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(function ($nested) use ($search) {
                $nested->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('customer_email', 'like', '%'.$search.'%');
            }))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders', 'filters'));
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'coupon']);

        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'shipped', 'delivered', 'cancelled'])],
            'payment_status' => ['required', Rule::in(['pending', 'paid', 'failed', 'refunded'])],
        ]);

        DB::transaction(function () use ($order, $data): void {
            $order = Order::query()->with('items')->lockForUpdate()->findOrFail($order->id);

            if ($order->status === 'cancelled' && $data['status'] !== 'cancelled') {
                throw ValidationException::withMessages(['status' => 'Cancelled orders cannot be reactivated. Create a new order instead.']);
            }

            if ($order->status !== 'cancelled' && $data['status'] === 'cancelled') {
                $productIds = $order->items->pluck('product_id')->filter()->sort()->values();
                $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                foreach ($order->items as $item) {
                    if ($item->product_id !== null && $products->has($item->product_id)) {
                        $products->get($item->product_id)->increment('stock_quantity', $item->quantity);
                    }
                }
            }

            $order->update($data);
        }, 3);

        return to_route('admin.orders.show', $order)->with('success', 'Order status updated.');
    }
}
