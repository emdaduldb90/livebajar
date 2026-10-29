<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin Panel | Live Bajar' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}?v={{ filemtime(public_path('vendor/fontawesome/css/all.min.css')) }}">
</head>
<body>
    <div class="min-h-screen lg:flex">
        <aside class="bg-gray-900 text-white lg:w-72 lg:min-h-screen p-5 flex flex-col gap-8">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-brandRed flex items-center justify-center shadow-lg"><i class="fas fa-cart-shopping" aria-hidden="true"></i></div>
                <div><p class="font-black text-lg leading-tight">Live Bajar</p><p class="text-[10px] tracking-widest uppercase text-gray-400">Admin Panel</p></div>
            </a>
            <nav class="grid grid-cols-2 gap-2 lg:block lg:space-y-2" aria-label="Admin navigation">
                <a href="{{ route('admin.dashboard') }}" class="admin-nav {{ request()->routeIs('admin.dashboard') ? 'admin-nav-active' : '' }}"><i class="fas fa-chart-pie" aria-hidden="true"></i><span>Dashboard</span></a>
                <a href="{{ route('admin.products.index') }}" class="admin-nav {{ request()->routeIs('admin.products.*') ? 'admin-nav-active' : '' }}"><i class="fas fa-box" aria-hidden="true"></i><span>Products</span></a>
                <a href="{{ route('admin.brands.index') }}" class="admin-nav {{ request()->routeIs('admin.brands.*') ? 'admin-nav-active' : '' }}"><i class="fas fa-tags" aria-hidden="true"></i><span>Brands</span></a>
                <a href="{{ route('admin.categories.index') }}" class="admin-nav {{ request()->routeIs('admin.categories.*') ? 'admin-nav-active' : '' }}"><i class="fas fa-folder-tree" aria-hidden="true"></i><span>Categories</span></a>
                <a href="{{ route('admin.pages.index') }}" class="admin-nav {{ request()->routeIs('admin.pages.*') ? 'admin-nav-active' : '' }}"><i class="fas fa-file-lines" aria-hidden="true"></i><span>Pages</span></a>
                <a href="{{ route('admin.orders.index') }}" class="admin-nav {{ request()->routeIs('admin.orders.*') ? 'admin-nav-active' : '' }}"><i class="fas fa-receipt" aria-hidden="true"></i><span>Orders</span></a>
                <a href="{{ route('admin.tracking.index') }}" class="admin-nav {{ request()->routeIs('admin.tracking.*') ? 'admin-nav-active' : '' }}"><i class="fas fa-chart-simple" aria-hidden="true"></i><span>Tracking &amp; AI</span></a>
                <a href="{{ route('admin.profit.index') }}" class="admin-nav {{ request()->routeIs('admin.profit.*') ? 'admin-nav-active' : '' }}"><i class="fas fa-chart-line" aria-hidden="true"></i><span>Profit</span></a>
                <a href="{{ route('admin.media.index') }}" class="admin-nav {{ request()->routeIs('admin.media.*') ? 'admin-nav-active' : '' }}"><i class="fas fa-images" aria-hidden="true"></i><span>File Manager</span></a>
                <details @if(request()->routeIs('admin.seo.*')) open @endif>
                    <summary class="admin-nav cursor-pointer"><i class="fas fa-magnifying-glass-chart" aria-hidden="true"></i><span>SEO</span></summary>
                    <div class="pl-4 space-y-1">
                        <a href="{{ route('admin.seo.facebook-commerce') }}" class="admin-nav text-xs {{ request()->routeIs('admin.seo.facebook-commerce*') ? 'admin-nav-active' : '' }}"><i class="fas fa-meta" aria-hidden="true"></i><span>Facebook Commerce</span></a>
                        <a href="{{ route('admin.seo.sitemap') }}" class="admin-nav text-xs {{ request()->routeIs('admin.seo.sitemap*') ? 'admin-nav-active' : '' }}"><i class="fas fa-sitemap" aria-hidden="true"></i><span>Sitemap.xml</span></a>
                    </div>
                </details>
                <a href="{{ route('admin.coupons.index') }}" class="admin-nav {{ request()->routeIs('admin.coupons.*') ? 'admin-nav-active' : '' }}"><i class="fas fa-ticket" aria-hidden="true"></i><span>Coupons</span></a>
                <a href="{{ route('admin.settings.edit') }}" class="admin-nav {{ request()->routeIs('admin.settings.*') ? 'admin-nav-active' : '' }}"><i class="fas fa-sliders" aria-hidden="true"></i><span>Settings</span></a>
                <a href="{{ route('admin.account.edit') }}" class="admin-nav {{ request()->routeIs('admin.account.*') ? 'admin-nav-active' : '' }}"><i class="fas fa-user-shield" aria-hidden="true"></i><span>Account Security</span></a>
            </nav>
            <div class="lg:mt-auto space-y-3 border-t border-white/10 pt-5">
                <a href="{{ route('home') }}" class="admin-nav"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i><span>View Store</span></a>
                <form action="{{ route('admin.logout') }}" method="POST">@csrf<button class="admin-nav w-full text-left"><i class="fas fa-right-from-bracket" aria-hidden="true"></i><span>Logout</span></button></form>
            </div>
        </aside>
        <section class="flex-1 min-w-0">
            <header class="bg-white/80 backdrop-blur border-b border-gray-200 px-5 md:px-8 py-5 flex items-center justify-between"><div><p class="text-xs font-bold text-brandRed uppercase tracking-wider">Management</p><h1 class="text-xl font-black text-gray-900">{{ $heading ?? 'Admin Dashboard' }}</h1></div><div class="text-right"><p class="text-sm font-bold text-gray-800">{{ auth()->user()->name }}</p><p class="text-xs text-gray-500">Administrator</p></div></header>
            <main class="p-5 md:p-8 max-w-7xl">
                @if(session('success'))<div class="paper-card p-4 mb-6 flex gap-3 text-sm font-semibold text-emerald-900 border-l-4 border-l-emerald-500" role="status"><i class="fas fa-check-circle text-emerald-600 text-lg" aria-hidden="true"></i><span>{{ session('success') }}</span></div>@endif
                @if($errors->any())<div class="paper-card p-4 mb-6 flex gap-3 text-sm font-semibold text-red-900 border-l-4 border-l-brandRed" role="alert"><i class="fas fa-exclamation-circle text-brandRed text-lg" aria-hidden="true"></i><span>{{ $errors->first() }}</span></div>@endif
                {{ $slot }}
            </main>
        </section>
    </div>
</body>
</html>
