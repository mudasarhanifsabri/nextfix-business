<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,viewport-fit=cover">
    <meta name="theme-color" content="#1267e8">
    <link rel="manifest" href="{{ auth()->user()->role === 'nextfix_admin' ? '/manifest-admin.webmanifest' : '/manifest-company.webmanifest' }}">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/pwa/apple-touch-icon.png">
    <link rel="icon" type="image/png" href="/images/pwa/icon-192.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ auth()->user()->role === 'nextfix_admin' ? 'NextFix Admin' : 'NextFix' }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <title>{{ $title ?? 'NextFix Business' }}</title>
    @vite(['resources/css/app.css', 'resources/css/chat.css', 'resources/css/ultimate.css', 'resources/css/customer.css', 'resources/css/company-mobile.css', 'resources/css/company-native.css', 'resources/css/booking-wizard.css', 'resources/css/booking-evidence.css', 'resources/css/quote-flow.css', 'resources/css/job-report.css', 'resources/css/operations-refunds.css', 'resources/css/notifications.css', 'resources/css/units.css', 'resources/css/navigation-fix.css', 'resources/js/app.js'])
@include('partials.theme-head')
</head>
<body class="{{ auth()->user()->role === 'company_manager' ? 'company-app' : 'admin-app' }} {{ !empty($mobileFocus) ? 'mobile-focus-screen' : '' }}">
@php
    $unreadCount = auth()->user()->unreadNotifications()->count();
    $isCompanyApp = auth()->user()->role === 'company_manager';
    $isAdminApp = auth()->user()->role === 'nextfix_admin';
    $companyMobileTitle = $mobileTitle ?? match (true) {
        request()->routeIs('bookings.*') => 'Bookings',
        request()->routeIs('units.*') => 'Units',
        request()->routeIs('rates.*') => 'Rates',
        request()->routeIs('company.profile.*') => 'Profile',
        request()->routeIs('admin.notifications.*') => 'Alerts',
        default => $heading ?? 'NextFix',
    };
    $adminMobileTitle = $mobileTitle ?? $heading ?? 'NextFix Admin';
@endphp
@include('partials.pwa-splash')
<div class="app-shell">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand brand-image"><img src="/images/brand/nextfix-square.png" alt="NextFix Home Services"><span>NextFix<small>BUSINESS</small></span></a>
        <nav aria-label="Primary navigation">
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>⌂</span> Overview</a>
            @if(auth()->user()->role !== 'nextfix_admin')
                <a class="{{ request()->routeIs('bookings.*') ? 'active' : '' }}" href="{{ route('bookings.index') }}"><span>▣</span> Bookings</a>
                <a class="{{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}"><span>◇</span> Units</a>
                <a class="{{ request()->routeIs('rates.*') ? 'active' : '' }}" href="{{ route('rates.show') }}"><span>◫</span> Rates</a>
                <a class="{{ request()->routeIs('company.profile.*') ? 'active' : '' }}" href="{{ route('company.profile.show') }}"><span>◉</span> Profile</a>
            @else
                <a class="{{ request()->routeIs('admin.operations.*') ? 'active' : '' }}" href="{{ route('admin.operations.index') }}"><span>▣</span> Operations</a>
                <a class="{{ request()->routeIs('admin.professionals.*') ? 'active' : '' }}" href="{{ route('admin.professionals.index') }}"><span>◉</span> Workforce</a>
                <div class="nav-group {{ request()->routeIs('admin.companies.*') ? 'open' : '' }}">
                    <a class="nav-parent {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}" href="{{ route('admin.companies.index') }}"><span>◇</span> Companies <b>⌄</b></a>
                    <div class="nav-sub"><a class="{{ request()->routeIs('admin.companies.index') || request()->routeIs('admin.companies.show') ? 'active' : '' }}" href="{{ route('admin.companies.index') }}">List</a><a class="{{ request()->routeIs('admin.companies.create') ? 'active' : '' }}" href="{{ route('admin.companies.create') }}">Add new</a></div>
                </div>
                <a class="{{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}"><span>▤</span> Invoices</a>
                <a class="{{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}"><span>◉</span> Payments</a>
                <a class="{{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}" href="{{ route('admin.notifications.index') }}"><span>●</span> Notifications</a>
                <div class="nav-group {{ request()->routeIs('admin.integrations.*', 'admin.updates.*', 'admin.themes.*') ? 'open' : '' }}">
                    <a class="nav-parent {{ request()->routeIs('admin.integrations.*', 'admin.updates.*', 'admin.themes.*') ? 'active' : '' }}" href="{{ route('admin.integrations.index') }}"><span>⚙</span> Administration <b>⌄</b></a>
                    <div class="nav-sub"><a class="{{ request()->routeIs('admin.themes.*') ? 'active' : '' }}" href="{{ route('admin.themes.index') }}">Themes</a><a class="{{ request()->routeIs('admin.integrations.*') ? 'active' : '' }}" href="{{ route('admin.integrations.index') }}">Integrations</a><a class="{{ request()->routeIs('admin.updates.*') ? 'active' : '' }}" href="{{ route('admin.updates.index') }}">System updates</a></div>
                </div>
            @endif
        </nav>
        <div class="sidebar-foot">
            <button class="install-app-button" data-install-app hidden><i class="bi bi-download"></i> Install app</button>
            <div class="user-chip"><span>{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ str_replace('_', ' ', auth()->user()->role) }}</small></div></div>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="text-button">Sign out</button></form>
        </div>
    </aside>
    <main>
        <header class="topbar">
            @if($isCompanyApp)
                @if(request()->routeIs('dashboard'))
                    <a class="company-mobile-brand" href="{{ route('dashboard') }}" aria-label="NextFix Business home"><img src="/images/brand/nextfix-square.png" alt=""><span>NextFix <b>Business</b></span></a>
                @else
                    <span class="company-mobile-leading">@if(!empty($mobileBack))<a class="company-mobile-back" href="{{ $mobileBack }}" aria-label="Back"><i class="bi bi-arrow-left"></i></a>@endif</span>
                    <strong class="company-mobile-screen-title">{{ $companyMobileTitle }}</strong>
                @endif
            @elseif($isAdminApp)
                <span class="company-mobile-leading">@if(!empty($mobileBack))<a class="company-mobile-back" href="{{ $mobileBack }}" aria-label="Back"><i class="bi bi-arrow-left"></i></a>@endif</span>
                <strong class="company-mobile-screen-title">{{ $adminMobileTitle }}</strong>
            @endif
            <div class="topbar-copy"><p class="eyebrow">{{ auth()->user()->company?->name ?? 'NextFix Operations' }}</p><h1>{{ $heading ?? 'Overview' }}</h1></div><div class="top-actions"><button class="icon-button pwa-install-top" data-install-app hidden aria-label="Install NextFix app" title="Install app"><i class="bi bi-download"></i></button><button class="icon-button alert-sound-button" type="button" data-alert-sound aria-label="Enable alert sounds" title="Alert sounds"><i class="bi bi-volume-up"></i></button>
            <div class="dropdown" data-notification-feed="{{ url('/notifications/feed') }}" data-last-notification="{{ auth()->user()->notifications()->latest()->value('id') }}"><button class="icon-button bell-button" data-bs-toggle="dropdown" aria-label="Notifications"><i class="bi bi-bell"></i><b data-notification-count @if(!$unreadCount) hidden @endif>{{ $unreadCount > 9 ? '9+' : $unreadCount }}</b></button><div class="dropdown-menu dropdown-menu-end notification-menu"><div class="notification-menu-head"><strong>Alerts</strong><a href="{{ route('admin.notifications.index') }}">All</a></div>@forelse(auth()->user()->notifications()->limit(4)->get() as $note)<a class="dropdown-item {{ $note->read_at ? '' : 'unread' }}" href="{{ route('admin.notifications.read', $note) }}"><span class="note-dot {{ $note->data['level'] ?? 'info' }}"></span><div><strong>{{ $note->data['title'] ?? 'Notification' }}</strong><small>{{ \Illuminate\Support\Str::limit($note->data['message'] ?? '', 72) }}</small><time>{{ $note->created_at->diffForHumans(short: true) }}</time></div></a>@empty<div class="notification-empty"><i class="bi bi-check2-circle"></i><span>You're all caught up</span></div>@endforelse</div></div>
            <span>{{ now()->timezone(config('app.timezone'))->format('D, d M') }}</span></div></header>
        <div class="content">
            @include('partials.page-context')
            @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
            @yield('content')
        </div>
    </main>
</div>
<div class="toast-container position-fixed top-0 end-0 p-3">@if(session('status'))<div class="toast show border-0 shadow" role="alert"><div class="toast-header"><span class="note-dot success"></span><strong class="me-auto">NextFix</strong><button type="button" class="btn-close" data-bs-dismiss="toast"></button></div><div class="toast-body">{{ session('status') }}</div></div>@endif</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
@if(auth()->user()->role !== 'nextfix_admin')
<nav class="mobile-nav"><a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-house"></i><span>Home</span></a><a class="{{ request()->routeIs('bookings.*') ? 'active' : '' }}" href="{{ route('bookings.index') }}"><i class="bi bi-calendar-check"></i><span>Bookings</span></a><a class="{{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}"><i class="bi bi-buildings"></i><span>Units</span></a><a class="mobile-alert-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}" href="{{ route('admin.notifications.index') }}"><i class="bi bi-bell"></i><b data-notification-count @if(!$unreadCount) hidden @endif>{{ $unreadCount > 9 ? '9+' : $unreadCount }}</b><span>Alerts</span></a><a class="{{ request()->routeIs('company.profile.*') ? 'active' : '' }}" href="{{ route('company.profile.show') }}"><i class="bi bi-person"></i><span>Profile</span></a></nav>
@else
<nav class="mobile-nav admin-mobile"><a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid"></i><span>Home</span></a><a class="{{ request()->routeIs('admin.operations.*') ? 'active' : '' }}" href="{{ route('admin.operations.index') }}"><i class="bi bi-calendar3"></i><span>Jobs</span></a><a class="{{ request()->routeIs('admin.professionals.*') ? 'active' : '' }}" href="{{ route('admin.professionals.index') }}"><i class="bi bi-people"></i><span>Team</span></a><a class="{{ request()->routeIs('admin.companies.*') ? 'active' : '' }}" href="{{ route('admin.companies.index') }}"><i class="bi bi-buildings"></i><span>Companies</span></a><a class="mobile-alert-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}" href="{{ route('admin.notifications.index') }}"><i class="bi bi-bell"></i><b data-notification-count @if(!$unreadCount) hidden @endif>{{ $unreadCount > 9 ? '9+' : $unreadCount }}</b><span>Alerts</span></a></nav>
@endif
@include('partials.smart-tap')
</body>
</html>
