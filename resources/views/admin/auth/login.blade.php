<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login | Live Bajar</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}?v={{ filemtime(public_path('vendor/fontawesome/css/all.min.css')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
</head>
<body class="min-h-screen flex items-center justify-center p-5">
    <section class="paper-card w-full max-w-md p-7 md:p-9">
        <a href="{{ route('home') }}" class="flex items-center gap-3 mb-8"><span class="w-12 h-12 rounded-xl bg-brandRed text-white flex items-center justify-center shadow-lg"><i class="fas fa-lock" aria-hidden="true"></i></span><span><span class="block font-black text-xl text-gray-900">Live <span class="text-brandRed">Bajar</span></span><span class="block text-[10px] uppercase tracking-widest text-gray-500 font-bold">Secure Admin Access</span></span></a>
        <h1 class="text-2xl font-black text-gray-900">Sign in</h1><p class="text-sm text-gray-600 mt-1 mb-6">Use your administrator credentials to manage the store.</p>
        @if($errors->any())<div class="bg-red-50 border-l-4 border-brandRed text-red-900 text-sm font-medium p-3 mb-5" role="alert">{{ $errors->first() }}</div>@endif
        <form action="{{ route('admin.login.store') }}" method="POST" class="space-y-5">@csrf
            <div><label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="paper-input w-full py-3 px-4 text-sm"></div>
            <div><label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Password</label><input id="password" name="password" type="password" required autocomplete="current-password" class="paper-input w-full py-3 px-4 text-sm"></div>
            <label class="flex items-center gap-2 text-sm text-gray-600"><input name="remember" type="checkbox" value="1" class="accent-brandRed"><span>Remember me on this device</span></label>
            <button class="paper-btn w-full bg-brandRed text-white py-3.5 font-bold rounded-xl hover:bg-brandRedHover"><i class="fas fa-right-to-bracket mr-2" aria-hidden="true"></i>Sign in securely</button>
        </form>
    </section>
</body>
</html>
