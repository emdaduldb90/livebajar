<x-layouts.store title="Secure Checkout | Live Bajar" :tracking-context="$trackingContext">
    <div class="space-y-8">
        <div class="flex items-center gap-3">
            <a href="{{ route('cart.index') }}" class="paper-btn p-2.5 text-gray-700 hover:text-brandRed" title="Back to Cart">
                <i class="fas fa-arrow-left" aria-hidden="true"></i><span class="sr-only">Back to cart</span>
            </a>
            <h1 class="text-3xl font-black text-gray-900">Secure Checkout</h1>
        </div>

        <form action="{{ route('checkout.store') }}" method="POST" class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            @csrf

            <div class="space-y-6 lg:col-span-2">
                <section class="paper-card space-y-6 p-6 md:p-8">
                    <h2 class="flex items-center gap-2 border-b border-gray-200 pb-4 text-lg font-bold text-gray-900">
                        <i class="fas fa-truck-fast text-brandRed" aria-hidden="true"></i>
                        <span>Shipping &amp; Contact Information</span>
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="name" class="text-xs font-bold uppercase tracking-wider text-gray-700">Full Name *</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="John Doe" class="paper-input w-full px-4 py-3 text-sm">
                        </div>
                        <div class="space-y-2">
                            <label for="phone" class="text-xs font-bold uppercase tracking-wider text-gray-700">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required autocomplete="tel" placeholder="+880 1700-000000" class="paper-input w-full px-4 py-3 text-sm">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="email" class="text-xs font-bold uppercase tracking-wider text-gray-700">Email Address (Optional)</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="john@example.com" class="paper-input w-full px-4 py-3 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="address" class="text-xs font-bold uppercase tracking-wider text-gray-700">Address *</label>
                        <input type="text" id="address" name="address" value="{{ old('address') }}" required autocomplete="street-address" placeholder="House 12, Road 5, Block A" class="paper-input w-full px-4 py-3 text-sm">
                    </div>

                    <fieldset class="space-y-3">
                        <legend class="text-xs font-bold uppercase tracking-wider text-gray-700">Delivery Area *</legend>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach($shippingOptions as $zone => $option)
                                <label data-shipping-card class="paper-card-inset flex cursor-pointer items-center gap-3 border p-4 transition-colors hover:border-brandRed @if($selectedShippingZone === $zone) border-brandRed ring-1 ring-brandRed @endif">
                                    <input
                                        type="radio"
                                        name="shipping_zone"
                                        value="{{ $zone }}"
                                        data-shipping-option
                                        data-fee-cents="{{ $option['fee_cents'] }}"
                                        data-fee-label="{{ $option['fee_label'] }}"
                                        @checked($selectedShippingZone === $zone)
                                        class="accent-brandRed"
                                    >
                                    <span>
                                        <span class="block text-sm font-bold text-gray-800">{{ $option['label'] }}</span>
                                        <span class="block text-xs text-gray-500">Select this delivery area</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="space-y-2">
                        <label for="notes" class="text-xs font-bold uppercase tracking-wider text-gray-700">Order Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="2" maxlength="2000" placeholder="Special delivery instructions..." class="paper-input w-full resize-none px-4 py-3 text-sm">{{ old('notes') }}</textarea>
                    </div>
                </section>

                <section class="paper-card space-y-6 p-6 md:p-8">
                    <h2 class="flex items-center gap-2 border-b border-gray-200 pb-4 text-lg font-bold text-gray-900">
                        <i class="fas fa-wallet text-brandRed" aria-hidden="true"></i>
                        <span>Payment Method</span>
                    </h2>

                    <div class="space-y-3">
                        <label class="paper-card-inset flex cursor-pointer items-center justify-between p-4 transition-colors hover:border-brandRed">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="payment_method" value="cod" @checked(old('payment_method', 'cod') === 'cod') class="accent-brandRed">
                                <span><span class="block text-sm font-bold text-gray-800">Cash on Delivery (COD)</span><span class="block text-xs text-gray-500">Pay securely with cash upon package delivery.</span></span>
                            </span>
                            <i class="fas fa-money-bill-wave text-xl text-brandRed" aria-hidden="true"></i>
                        </label>
                        <label class="paper-card-inset flex cursor-pointer items-center justify-between p-4 transition-colors hover:border-brandRed">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="payment_method" value="bkash" @checked(old('payment_method') === 'bkash') class="accent-brandRed">
                                <span><span class="block text-sm font-bold text-gray-800">bKash / Mobile Banking</span><span class="block text-xs text-gray-500">Instant mobile wallet payment gateway.</span></span>
                            </span>
                            <i class="fas fa-mobile-screen text-xl text-pink-600" aria-hidden="true"></i>
                        </label>
                        <label class="paper-card-inset flex cursor-pointer items-center justify-between p-4 transition-colors hover:border-brandRed">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="payment_method" value="card" @checked(old('payment_method') === 'card') class="accent-brandRed">
                                <span><span class="block text-sm font-bold text-gray-800">Credit / Debit Card</span><span class="block text-xs text-gray-500">Visa, MasterCard, American Express.</span></span>
                            </span>
                            <i class="fas fa-credit-card text-xl text-blue-600" aria-hidden="true"></i>
                        </label>
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <div class="paper-card sticky top-28 space-y-6 p-6">
                    <h2 class="border-b border-gray-200 pb-4 text-lg font-bold text-gray-900">Order Summary</h2>
                    <div class="max-h-60 space-y-3 overflow-y-auto pr-2">
                        @foreach($summary['items'] as $item)
                            <div class="flex items-center justify-between border-b border-gray-200/50 pb-2 text-xs">
                                <div class="flex items-center gap-2"><span class="font-bold text-gray-700">{{ $item['quantity'] }}x</span><span class="max-w-[140px] truncate text-gray-800">{{ $item['product']->name }}</span></div>
                                <span class="font-bold text-gray-900">{{ \App\Support\Money::display($item['line_total_cents']) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-2 pt-2 text-sm">
                        <div class="flex justify-between text-gray-600"><span>Subtotal</span><span class="font-semibold text-gray-800">{{ \App\Support\Money::display($summary['subtotal_cents']) }}</span></div>
                        @if($summary['discount_cents'] > 0)
                            <div class="flex justify-between font-semibold text-emerald-600"><span>Discount</span><span>-{{ \App\Support\Money::display($summary['discount_cents']) }}</span></div>
                        @endif
                        <div class="flex justify-between text-gray-600"><span>Shipping</span><span data-checkout-shipping class="font-semibold text-gray-800">{{ $summary['shipping_label'] }}</span></div>
                        <div class="flex justify-between border-t border-gray-200 pt-3 text-lg font-black text-gray-900">
                            <span>Total</span>
                            <span data-checkout-total data-subtotal-cents="{{ $summary['subtotal_cents'] }}" data-discount-cents="{{ $summary['discount_cents'] }}" data-currency-symbol="{{ config('store.currency_symbol') }}" class="text-brandRed">{{ \App\Support\Money::display($summary['total_cents']) }}</span>
                        </div>
                    </div>

                    <button class="paper-btn flex w-full items-center justify-center gap-2 rounded-xl bg-brandRed py-4 font-bold text-white shadow-lg hover:bg-brandRedHover">
                        <i class="fas fa-lock text-xs" aria-hidden="true"></i><span>Place Order Now</span>
                    </button>
                </div>
            </aside>
        </form>
    </div>

    <script src="{{ asset('js/checkout.js') }}?v={{ filemtime(public_path('js/checkout.js')) }}" defer></script>
</x-layouts.store>
