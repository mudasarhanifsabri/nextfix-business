@extends('layouts.app', ['heading' => 'Home'])
@section('content')
@if($quotationCount)<a class="quotation-alert" href="{{ route('bookings.index') }}"><span><i class="bi bi-receipt-cutoff"></i></span><div><strong>{{ $quotationCount }} maintenance quotation{{ $quotationCount > 1 ? 's' : '' }} ready</strong><small>Review the inspection, fee credit and approve or request changes.</small></div><b>Review now <i class="bi bi-arrow-right"></i></b></a>@endif
<section class="customer-hero" data-hero-slider>
    <div class="hero-orb orb-one"></div><div class="hero-orb orb-two"></div>
    <div class="hero-slides">
        <article class="hero-slide active"><p class="eyebrow">NEXTFIX HOME SERVICES</p><h2>Your property, always guest-ready.</h2><p>Book trusted cleaning and maintenance teams in under a minute.</p><a href="#services" class="hero-cta">Book a service <i class="bi bi-arrow-down"></i></a></article>
        <article class="hero-slide"><p class="eyebrow">FAST & TRANSPARENT</p><h2>One request. Clear progress.</h2><p>Follow every visit from confirmation through completion in one calm workspace.</p><a href="{{ route('bookings.index') }}" class="hero-cta">Track bookings <i class="bi bi-arrow-right"></i></a></article>
        <article class="hero-slide"><p class="eyebrow">VERIFIED SERVICE TEAMS</p><h2>Help arrives when you need it.</h2><p>Share access notes once and keep your team focused on the guest experience.</p><a href="{{ route('bookings.create', 'maintenance') }}" class="hero-cta">Report an issue <i class="bi bi-arrow-right"></i></a></article>
    </div>
    <div class="hero-visual"><span class="phone-card"><i class="bi bi-shield-check"></i><strong>NextFix verified</strong><small>Professional service teams</small></span><span class="floating-check"><i class="bi bi-check2"></i> Easy booking</span></div>
    <div class="hero-dots" aria-label="Hero slides"><button class="active" aria-label="Slide 1"></button><button aria-label="Slide 2"></button><button aria-label="Slide 3"></button></div>
</section>
<div class="section-intro" id="services"><div><p class="eyebrow">NEW SERVICE BOOKING</p><h2>What do you need today?</h2><p>Choose a service and we’ll pre-fill the most common options.</p></div><span><i class="bi bi-lightning-charge-fill"></i> Takes less than a minute</span></div>
<div class="app-service-grid">
    <a class="app-service-card cleaning" href="{{ route('bookings.create', 'cleaning') }}"><span class="app-service-icon"><i class="bi bi-stars"></i></span><span class="popular">MOST POPULAR</span><div><h3>Cleaning</h3><p>Checkout, regular and deep cleaning for guest-ready homes.</p><strong>Book cleaning <i class="bi bi-arrow-right"></i></strong></div><span class="service-art"><i class="bi bi-house-heart"></i></span></a>
    <a class="app-service-card maintenance" href="{{ route('bookings.create', 'maintenance') }}"><span class="app-service-icon"><i class="bi bi-tools"></i></span><div><h3>Maintenance</h3><p>AC, plumbing, electrical and general property repairs.</p><strong>Request maintenance <i class="bi bi-arrow-right"></i></strong></div><span class="service-art"><i class="bi bi-wrench-adjustable-circle"></i></span></a>
</div>
<div class="stat-grid app-stats"><article><span>Upcoming</span><strong>{{ $upcoming->count() }}</strong><small>scheduled services</small></article><article><span>Needs review</span><strong>{{ $pendingCount }}</strong><small>pending confirmations</small></article><article><span>In progress</span><strong>{{ $activeCount }}</strong><small>active jobs</small></article></div>
<section class="panel"><div class="panel-head"><div><p class="eyebrow">UP NEXT</p><h3>Upcoming bookings</h3></div><a href="{{ route('bookings.index') }}">View all →</a></div>
@forelse($upcoming as $booking)<div class="booking-row"><div class="date-tile"><strong>{{ $booking->scheduled_at->format('d') }}</strong><span>{{ strtoupper($booking->scheduled_at->format('M')) }}</span></div><div class="booking-main"><strong>{{ $booking->service_name }}</strong><span>{{ $booking->unit->building }} · Unit {{ $booking->unit->unit_number }}</span></div><span class="status {{ $booking->status }}">{{ str_replace('_', ' ', $booking->status) }}</span><time>{{ $booking->scheduled_at->format('g:i A') }}</time></div>@empty<div class="empty"><span>○</span><h4>No upcoming bookings</h4><p>Your next confirmed service will appear here.</p></div>@endforelse
</section>
@endsection
