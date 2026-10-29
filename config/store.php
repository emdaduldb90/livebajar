<?php

return [
    'currency' => env('STORE_CURRENCY', 'BDT'),
    'currency_symbol' => env('STORE_CURRENCY_SYMBOL', '৳'),
    'free_shipping_threshold_cents' => (int) env('STORE_FREE_SHIPPING_THRESHOLD_CENTS', 15000),
    'shipping_fee_cents' => (int) env('STORE_SHIPPING_FEE_CENTS', 1500),
    'cart_max_quantity' => (int) env('STORE_CART_MAX_QUANTITY', 20),

    /*
     * Checkout delivery areas are kept in configuration so the displayed option,
     * the client-side summary and the server-side order total always use the
     * same source of truth.
     */
    'default_shipping_zone' => 'dhaka',
    'shipping_zones' => [
        'dhaka' => [
            'label' => 'Dhaka - 80 TK',
            'fee_label' => '80 TK',
            'city' => 'Dhaka',
            'fee_cents' => 8000,
        ],
        'outside_dhaka' => [
            'label' => 'Outside Dhaka - 130 TK',
            'fee_label' => '130 TK',
            'city' => 'Outside Dhaka',
            'fee_cents' => 13000,
        ],
    ],
];
