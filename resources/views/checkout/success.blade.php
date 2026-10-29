<x-layouts.store title="Order Confirmed | Live Bajar">
    <div class="paper-card p-8 max-w-md w-full mx-auto my-12 text-center">
        <div class="w-20 h-20 bg-brandRed text-white rounded-full flex items-center justify-center text-4xl mx-auto mb-5 shadow-lg"><i class="fas fa-check" aria-hidden="true"></i></div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Order Placed Successfully!</h1>
        <p class="text-gray-600 text-sm mb-6">Thank you for shopping with Live Bajar. Your order has been placed and is being processed.</p>
        <div class="paper-card-inset p-4 mb-6 text-left text-sm text-gray-700"><div class="flex justify-between py-1 border-b border-gray-200"><span>Order ID:</span><strong>{{ $order->order_number }}</strong></div><div class="flex justify-between py-1 border-b border-gray-200"><span>Payment:</span><strong>{{ match($order->payment_method) {'cod' => 'Cash on Delivery', 'bkash' => 'bKash Mobile Banking', default => 'Credit / Debit Card'} }}</strong></div><div class="flex justify-between py-1"><span>Total Amount:</span><strong class="text-brandRed">{{ \App\Support\Money::display($order->total_cents) }}</strong></div></div>
        <a href="{{ route('home') }}" class="block w-full py-3 px-6 bg-brandRed text-white font-semibold rounded-xl shadow-md hover:bg-brandRedHover transition-all">Return to Home</a>
    </div>
</x-layouts.store>
