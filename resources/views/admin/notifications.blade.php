@extends('layouts.app', ['heading' => 'Notifications'])
@section('content')
<div class="page-actions"><div><p class="eyebrow">ACTIVITY CENTRE</p><h2>Notifications</h2><p class="muted">Operational alerts, finance updates and document reminders.</p></div><form method="post" action="{{ route('admin.notifications.read-all') }}">@csrf<button class="secondary">Mark all as read</button></form></div>
<section class="panel notification-list">@forelse($notifications as $note)<a class="notification-row {{ $note->read_at ? '' : 'unread' }}" href="{{ route('admin.notifications.read',$note) }}"><span class="note-icon {{ $note->data['level'] ?? 'info' }}"><i class="bi bi-bell"></i></span><div><strong>{{ $note->data['title'] ?? 'Notification' }}</strong><p>{{ $note->data['message'] ?? '' }}</p></div><time>{{ $note->created_at->diffForHumans() }}</time></a>@empty<div class="empty">No notifications yet.</div>@endforelse<div class="pager">{{ $notifications->links() }}</div></section>
@endsection
