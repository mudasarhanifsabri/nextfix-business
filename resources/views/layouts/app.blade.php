<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1267e8">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>{{ $title ?? 'NextFix Business' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark">N</span><span>NextFix<small>BUSINESS</small></span></a>
        <nav aria-label="Primary navigation">
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>⌂</span> Overview</a>
            @if(auth()->user()->role !== 'nextfix_admin')
                <a class="{{ request()->routeIs('bookings.*') ? 'active' : '' }}" href="{{ route('bookings.index') }}"><span>▣</span> Bookings</a>
                <a class="{{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}"><span>◇</span> Units</a>
            @else
                <a href="#operations"><span>▣</span> Operations</a><a href="#companies"><span>◇</span> Companies</a>
            @endif
        </nav>
        <div class="sidebar-foot">
            <div class="user-chip"><span>{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ str_replace('_', ' ', auth()->user()->role) }}</small></div></div>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="text-button">Sign out</button></form>
        </div>
    </aside>
    <main>
        <header class="topbar"><div><p class="eyebrow">{{ auth()->user()->company?->name ?? 'NextFix Operations' }}</p><h1>{{ $heading ?? 'Overview' }}</h1></div><div class="top-actions"><button class="icon-button" aria-label="Notifications">●</button><span>{{ now()->timezone('Asia/Dubai')->format('D, d M') }}</span></div></header>
        <div class="content">
            @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
            @yield('content')
        </div>
    </main>
</div>
<nav class="mobile-nav"><a href="{{ route('dashboard') }}">⌂<span>Home</span></a><a href="{{ route('bookings.index') }}">▣<span>Bookings</span></a><a href="{{ route('units.index') }}">◇<span>Units</span></a><a href="#">○<span>Account</span></a></nav>
</body>
</html>
