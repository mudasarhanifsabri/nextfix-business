@extends('layouts.app', ['heading' => 'Good '.(now()->hour < 12 ? 'morning' : 'afternoon').', '.explode(' ', auth()->user()->name)[0]])
@section('content')
<section class="hero"><div><p class="eyebrow">SERVICE DESK</p><h2>What can we take care of today?</h2><p>Choose a service to start a booking for one of your managed units.</p></div><div class="trust-pill"><span>✓</span> NextFix verified teams</div></section>
<div class="service-grid">
    <a class="service-card cleaning" href="{{ route('bookings.create', 'cleaning') }}"><span class="service-icon">✦</span><div><p class="eyebrow">HOUSEKEEPING</p><h3>Cleaning</h3><p>Checkout, regular and deep cleaning with agreed company rates.</p><strong>Book cleaning <span>→</span></strong></div></a>
    <a class="service-card maintenance" href="{{ route('bookings.create', 'maintenance') }}"><span class="service-icon">⌁</span><div><p class="eyebrow">TECHNICAL SERVICES</p><h3>Maintenance</h3><p>Inspections and repairs for AC, plumbing, electrical and more.</p><strong>Report an issue <span>→</span></strong></div></a>
</div>
<div class="stat-grid"><article><span>Upcoming</span><strong>{{ $upcoming->count() }}</strong><small>scheduled services</small></article><article><span>Pending approvals</span><strong>{{ $pendingCount }}</strong><small>need your attention</small></article><article><span>In progress</span><strong>{{ $activeCount }}</strong><small>active jobs</small></article></div>
<section class="panel"><div class="panel-head"><div><p class="eyebrow">UP NEXT</p><h3>Upcoming bookings</h3></div><a href="{{ route('bookings.index') }}">View all →</a></div>
@forelse($upcoming as $booking)<div class="booking-row"><div class="date-tile"><strong>{{ $booking->scheduled_at->format('d') }}</strong><span>{{ strtoupper($booking->scheduled_at->format('M')) }}</span></div><div class="booking-main"><strong>{{ $booking->service_name }}</strong><span>{{ $booking->unit->building }} · Unit {{ $booking->unit->unit_number }}</span></div><span class="status {{ $booking->status }}">{{ str_replace('_', ' ', $booking->status) }}</span><time>{{ $booking->scheduled_at->format('g:i A') }}</time></div>@empty<div class="empty"><span>○</span><h4>No upcoming bookings</h4><p>Your next confirmed service will appear here.</p></div>@endforelse
</section>
@endsection
