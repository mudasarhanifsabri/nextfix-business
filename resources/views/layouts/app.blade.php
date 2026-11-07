<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1267e8">
    <link rel="manifest" href="/manifest.webmanifest">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>{{ $title ?? 'NextFix Business' }}</title>
    @vite(['resources/css/app.css', 'resources/css/ultimate.css', 'resources/css/customer.css', 'resources/css/quote-flow.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand brand-image"><img src="/images/brand/nextfix-square.png" alt="NextFix Home Services"><span>NextFix<small>BUSINESS</small></span></a>
        <nav aria-label="Primary navigation">
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>⌂</span> Overview</a>
            @if(auth()->user()->role !== 'nextfix_admin')
                <a class="{{ request()->routeIs('bookings.*') ? 'active' : '' }}" href="{{ route('bookings.index') }}"><span>▣</span> Bookings</a>
                <a class="{{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}"><span>◇</span> Units</a>
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
                <div class="nav-group {{ request()->routeIs('admin.integrations.*', 'admin.updates.*') ? 'open' : '' }}">
                    <a class="nav-parent {{ request()->routeIs('admin.integrations.*', 'admin.updates.*') ? 'active' : '' }}" href="{{ route('admin.integrations.index') }}"><span>⚙</span> Administration <b>⌄</b></a>
                    <div class="nav-sub"><a class="{{ request()->routeIs('admin.integrations.*') ? 'active' : '' }}" href="{{ route('admin.integrations.index') }}">Integrations</a><a class="{{ request()->routeIs('admin.updates.*') ? 'active' : '' }}" href="{{ route('admin.updates.index') }}">System updates</a></div>
                </div>
            @endif
        </nav>
        <div class="sidebar-foot">
            <div class="user-chip"><span>{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ str_replace('_', ' ', auth()->user()->role) }}</small></div></div>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="text-button">Sign out</button></form>
        </div>
    </aside>
    <main>
        <header class="topbar"><div><p class="eyebrow">{{ auth()->user()->company?->name ?? 'NextFix Operations' }}</p><h1>{{ $heading ?? 'Overview' }}</h1></div><div class="top-actions">
            <div class="dropdown" data-notification-feed="{{ route('notifications.feed') }}" data-last-notification="{{ auth()->user()->notifications()->latest()->value('id') }}"><button class="icon-button bell-button" data-bs-toggle="dropdown" aria-label="Notifications"><i class="bi bi-bell"></i><b data-notification-count @if(!auth()->user()->unreadNotifications->count()) hidden @endif>{{ auth()->user()->unreadNotifications->count() }}</b></button><div class="dropdown-menu dropdown-menu-end notification-menu"><div class="notification-menu-head"><strong>Notifications</strong><a href="{{ route('admin.notifications.index') }}">View all</a></div>@forelse(auth()->user()->notifications()->limit(5)->get() as $note)<a class="dropdown-item {{ $note->read_at ? '' : 'unread' }}" href="{{ route('admin.notifications.read', $note) }}"><span class="note-dot {{ $note->data['level'] ?? 'info' }}"></span><div><strong>{{ $note->data['title'] ?? 'Notification' }}</strong><small>{{ $note->data['message'] ?? '' }}</small><time>{{ $note->created_at->diffForHumans() }}</time></div></a>@empty<div class="empty small">No notifications yet.</div>@endforelse</div></div>
            <span>{{ now()->timezone(config('app.timezone'))->format('D, d M') }}</span></div></header>
        <div class="content">
            @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
            @yield('content')
        </div>
    </main>
</div>
<div class="toast-container position-fixed top-0 end-0 p-3">@if(session('status'))<div class="toast show border-0 shadow" role="alert"><div class="toast-header"><span class="note-dot success"></span><strong class="me-auto">NextFix</strong><button type="button" class="btn-close" data-bs-dismiss="toast"></button></div><div class="toast-body">{{ session('status') }}</div></div>@endif</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@if(auth()->user()->role !== 'nextfix_admin')<nav class="mobile-nav"><a href="{{ route('dashboard') }}">⌂<span>Home</span></a><a href="{{ route('bookings.index') }}">▣<span>Bookings</span></a><a href="{{ route('units.index') }}">◇<span>Units</span></a><a href="#">○<span>Account</span></a></nav>@endif
</body>
</html>
