@props(['title' => 'Live Bajar - Ultimate Paper-Crafted Shopping Experience', 'trackingContext' => null, 'seo' => []])
@php
    $pageTrackingEvents = is_array($trackingContext) && is_array($trackingContext['events'] ?? null) ? $trackingContext['events'] : [];
    $flashedTrackingEvents = app(\App\Services\Tracking\TrackingEventStore::class)->pull();
    $trackingPayload = ['events' => array_merge($flashedTrackingEvents, $pageTrackingEvents)];
    $trackingPayloadEncoded = app(\App\Services\Tracking\TrackingContext::class)->encode($trackingPayload);
    $trackingIntelligenceConfig = app(\App\Services\Tracking\TrackingSettingsService::class)->publicConfig();
    $trackingEnabled = (bool) config('tracking.enabled') && (app()->environment('production') || (bool) config('tracking.allow_non_production'));
    $directBrowserTracking = ! $trackingIntelligenceConfig['test_mode'] && (
        $trackingIntelligenceConfig['meta']['browser_enabled']
        || $trackingIntelligenceConfig['tiktok']['browser_enabled']
        || $trackingIntelligenceConfig['ga4']['browser_enabled']
        || $trackingIntelligenceConfig['google_ads']['browser_enabled']
    );
    $trackingUserAgent = (string) request()->userAgent();
    $trackingExcluded = (auth()->check() && (bool) auth()->user()?->is_admin)
        || preg_match('/bot|crawler|spider|slurp|preview|headless/i', $trackingUserAgent) === 1
        || (! app()->environment('production') && ! (bool) config('tracking.allow_non_production'));
    $trackingConsentNeeded = ! $trackingExcluded && (((bool) config('tracking.require_consent') && $trackingEnabled)
        || ($trackingIntelligenceConfig['require_consent'] && ($trackingIntelligenceConfig['collection_enabled'] || $directBrowserTracking)));
    $trackingIntelligenceEncoded = app(\App\Services\Tracking\TrackingContext::class)->encode($trackingIntelligenceConfig + ['skip_tracking' => $trackingExcluded]);
    $headerMenu = $siteSettings['header_menu'] ?? [['label' => 'Home', 'url' => '/'], ['label' => 'Shop', 'url' => '/shop']];
    $footer = $siteSettings['footer'] ?? [];
    $footerQuickLinks = $footer['quick_links'] ?? [['label' => 'Home Page', 'url' => '/'], ['label' => 'Product Catalog & Filters', 'url' => '/shop'], ['label' => 'Shopping Cart', 'url' => '/cart'], ['label' => 'Checkout Securely', 'url' => '/checkout']];
    $footerCareLinks = $footer['care_links'] ?? [['label' => 'Fast delivery', 'url' => '/fast-delivery'], ['label' => 'Secure checkout', 'url' => '/secure-checkout'], ['label' => '30-day return policy', 'url' => '/30-day-return-policy'], ['label' => '24/7 dedicated support', 'url' => '/dedicated-support']];
    $serviceNotice = $siteSettings['service_notice'] ?? [];
    $heroImage = data_get($siteSettings, 'hero.image');
    $heroImageUrl = is_string($heroImage) && $heroImage !== '' && ! \Illuminate\Support\Str::startsWith($heroImage, ['http://', 'https://', '//']) ? asset('storage/'.ltrim($heroImage, '/')) : $heroImage;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="index,follow">
    <title>{{ $title ?? 'Live Bajar - Ultimate Paper-Crafted Shopping Experience' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <meta name="description" content="{{ $seo['description'] ?? 'Shop curated products at Live Bajar.' }}">
    @if (! empty($seo['keywords']))<meta name="keywords" content="{{ $seo['keywords'] }}">@endif
    <link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">
    @if (request()->routeIs('home') && $heroImageUrl)<link rel="preload" as="image" href="{{ $heroImageUrl }}" fetchpriority="high">@endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/tracking-consent.css') }}?v={{ filemtime(public_path('css/tracking-consent.css')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}?v={{ filemtime(public_path('vendor/fontawesome/css/all.min.css')) }}">
</head>
<body class="selection:bg-brandRed selection:text-white" data-tracking-enabled="{{ $trackingEnabled ? '1' : '0' }}" data-tracking-debug="{{ config('tracking.debug') ? '1' : '0' }}" data-tracking-require-consent="{{ config('tracking.require_consent') ? '1' : '0' }}" data-tracking-excluded="{{ $trackingExcluded ? '1' : '0' }}">
    @php($cartCount = app(\App\Services\CartService::class)->count())

    <header class="sticky top-0 z-40 backdrop-blur-md bg-[#f9f7f2]/80 border-b border-white/50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 cursor-pointer group" aria-label="Live Bajar home">
                <div class="relative w-12 h-12 paper-card flex items-center justify-center bg-white group-hover:scale-105 transition-transform overflow-hidden">
                    <svg viewBox="0 0 100 100" class="w-9 h-9" aria-hidden="true">
                        <path d="M15 30 H25 L35 65 H75 L85 35 H30" fill="none" stroke="#2d2d2d" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="40" cy="75" r="8" fill="#d62828"/>
                        <circle cx="70" cy="75" r="8" fill="#d62828"/>
                        <path d="M45 40 Q55 25 65 40 T85 40" fill="#d62828"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 logo-text tracking-tight flex items-center gap-1">Live <span class="text-brandRed">Bajar</span></h1>
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-semibold">Paper Crafted Store</p>
                </div>
            </a>

            <form action="{{ route('products.index') }}" method="GET" class="hidden md:flex items-center flex-1 max-w-md mx-8">
                <label class="relative w-full">
                    <span class="sr-only">Search products</span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search products, categories..." class="w-full py-2.5 pl-11 pr-4 paper-input text-sm text-gray-800 placeholder-gray-500">
                    <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 text-sm" aria-hidden="true"></i>
                </label>
            </form>

            <nav class="flex items-center gap-4 sm:gap-6" aria-label="Primary navigation">
                @foreach ($headerMenu as $menuItem)
                    <a href="{{ $menuItem['url'] ?? '/' }}" class="font-medium text-sm text-gray-700 hover:text-brandRed transition-colors hidden sm:block">{{ $menuItem['label'] ?? 'Menu' }}</a>
                @endforeach
                <a href="{{ route('cart.index') }}" class="paper-btn p-3 relative flex items-center justify-center text-gray-700 hover:text-brandRed transition-all" title="View Cart" aria-label="View cart">
                    <i class="fas fa-shopping-cart text-lg" aria-hidden="true"></i>
                    <span class="absolute -top-1.5 -right-1.5 bg-brandRed text-white text-[11px] font-bold w-5 h-5 rounded-full flex items-center justify-center shadow-md">{{ $cartCount }}</span>
                </a>
                <details class="md:hidden relative">
                    <summary class="paper-btn p-3 text-gray-700 list-none cursor-pointer"><i class="fas fa-bars text-lg" aria-hidden="true"></i><span class="sr-only">Open menu</span></summary>
                    <div class="absolute right-0 top-14 w-72 paper-card p-4 flex flex-col gap-3 z-50">
                        <form action="{{ route('products.index') }}" method="GET" class="relative w-full mb-2">
                            <label>
                                <span class="sr-only">Search products</span>
                                <input type="search" name="q" placeholder="Search products..." class="w-full py-2 pl-10 pr-4 paper-input text-sm text-gray-800">
                                <i class="fas fa-search absolute left-3.5 top-3 text-gray-400 text-xs" aria-hidden="true"></i>
                            </label>
                        </form>
                        @foreach ($headerMenu as $menuItem)<a href="{{ $menuItem['url'] ?? '/' }}" class="text-left py-2 px-3 font-medium text-gray-700 hover:bg-black/5 rounded-lg">{{ $menuItem['label'] ?? 'Menu' }}</a>@endforeach
                        <a href="{{ route('cart.index') }}" class="text-left py-2 px-3 font-medium text-gray-700 hover:bg-black/5 rounded-lg flex justify-between items-center"><span>Cart</span><span class="bg-brandRed text-white text-xs px-2 py-0.5 rounded-full">{{ $cartCount }}</span></a>
                    </div>
                </details>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 min-h-[calc(100vh-180px)]">
        @if (session('success'))
            <div class="paper-card p-4 mb-6 text-sm font-semibold flex items-center gap-3 border-l-4 border-l-emerald-500 text-emerald-900" role="status"><i class="fas fa-check-circle text-emerald-600 text-lg" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="paper-card p-4 mb-6 text-sm font-semibold flex items-start gap-3 border-l-4 border-l-brandRed text-red-900" role="alert"><i class="fas fa-exclamation-circle text-brandRed text-lg mt-0.5" aria-hidden="true"></i><div>{{ $errors->first() }}</div></div>
        @endif
        {{ $slot }}
    </main>

    <aside id="livebajar-consent" class="lb-consent" data-enabled="{{ $trackingConsentNeeded ? '1' : '0' }}" hidden aria-label="Privacy choices">
        <div class="lb-consent__content">
            <div class="lb-consent__copy"><strong class="lb-consent__title">Your privacy choices</strong><p class="lb-consent__description">Optional analytics and advertising measurement use browser events and, when configured, limited server events. Choose whether to allow this tracking. Essential store functions work either way.</p></div>
            <div class="lb-consent__actions">
                <button type="button" data-consent-choice="denied" class="lb-consent__button lb-consent__button--reject">Reject optional tracking</button>
                <button type="button" data-consent-choice="granted" class="lb-consent__button lb-consent__button--accept">Allow optional tracking</button>
            </div>
        </div>
    </aside>

    <div id="livebajar-tracking-context" hidden data-context="{{ $trackingPayloadEncoded }}"></div>
    <div id="livebajar-tracking-config" hidden data-config="{{ $trackingIntelligenceEncoded }}"></div>
    <script src="{{ asset('js/tracking-consent.js') }}?v={{ filemtime(public_path('js/tracking-consent.js')) }}" defer></script>
    <script src="{{ asset('js/tracking.js') }}?v={{ filemtime(public_path('js/tracking.js')) }}" defer></script>
    <script src="{{ asset('js/tracking-intelligence.js') }}?v={{ filemtime(public_path('js/tracking-intelligence.js')) }}" defer></script>

    <footer class="paper-card rounded-none border-t border-white/60 bg-[#f4f0e6] mt-8 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <div class="flex items-center gap-2 mb-4"><div class="w-8 h-8 paper-card flex items-center justify-center bg-white"><i class="fas fa-shopping-cart text-brandRed text-sm" aria-hidden="true"></i></div><h2 class="text-xl font-extrabold text-gray-900 logo-text">Live Bajar</h2></div>
                <p class="text-sm text-gray-600 leading-relaxed">{{ $footer['about'] ?? 'Your ultimate paper-crafted online marketplace. Experience tactile shopping with modern elegance, high quality curated items, and fast delivery.' }}</p>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 mb-4 text-sm uppercase tracking-wider">Quick Links</h3>
                <ul class="space-y-2 text-sm text-gray-600">@foreach($footerQuickLinks as $link)<li><a href="{{ $link['url'] ?? '/' }}" class="hover:text-brandRed transition-colors">{{ $link['label'] ?? 'Link' }}</a></li>@endforeach</ul>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 mb-4 text-sm uppercase tracking-wider">Customer Care</h3>
                <ul class="space-y-2 text-sm text-gray-600">@foreach($footerCareLinks as $link)<li><a href="{{ $link['url'] ?? '/' }}" class="hover:text-brandRed transition-colors">{{ $link['label'] ?? 'Link' }}</a></li>@endforeach</ul>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 mb-4 text-sm uppercase tracking-wider">{{ $footer['newsletter_title'] ?? 'Newsletter' }}</h3>
                <p class="text-sm text-gray-600 mb-3">{{ $footer['newsletter_text'] ?? 'Subscribe to get special offers, free giveaways, and once-in-a-lifetime deals.' }}</p>
                <div class="flex gap-2"><input type="email" placeholder="Your email address" class="paper-input py-2 px-3 text-xs w-full" aria-label="Newsletter email"><a href="{{ route('products.index') }}" class="paper-btn px-4 py-2 bg-brandRed text-white text-xs font-semibold hover:bg-brandRedHover">Join</a></div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 pt-6 border-t border-gray-300/60 text-center text-xs text-gray-500">&copy; {{ now()->year }} Live Bajar E-Commerce. All rights reserved. Designed with Paper-Cut Skeuomorphic Aesthetic.</div>
        @if($trackingConsentNeeded)<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-3 text-center text-xs"><button type="button" data-open-consent class="lb-privacy-choice">Privacy choices</button></div>@endif
    </footer>
</body>
</html>
