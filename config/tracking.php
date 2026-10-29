<?php

return [
    /*
     * Tracking is enabled for production by default. Local and test environments
     * stay silent unless they are explicitly opted in, so development never
     * accidentally sends events to a live advertising dataset.
     */
    'enabled' => env('TRACKING_ENABLED', true),
    'allow_non_production' => env('TRACKING_ALLOW_NON_PRODUCTION', false),
    'debug' => env('TRACKING_DEBUG', false),

    /*
     * The storefront includes a basic optional-tracking preference control.
     * A fuller consent-management platform can set
     * window.LiveBajarConsent.analytics to true/false. A stored "denied"
     * decision is always honoured by the browser adapter.
     */
    'require_consent' => env('TRACKING_REQUIRE_CONSENT', false),

    'currency' => env('TRACKING_CURRENCY', env('STORE_CURRENCY', 'BDT')),
];
