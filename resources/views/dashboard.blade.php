@extends('layouts.app', ['heading' => 'Home'])
@section('content')
@php($userName = auth()->user()->name)
<div class="company-native-only native-home">
    <section class="native-welcome">
        <div><small>Hello,</small><h2>{{ str($userName)->before(' ') }}</h2></div>
        <a class="native-avatar" href="{{ route('company.profile.show') }}">{{ strtoupper(substr($userName, 0, 1)) }}</a>
    </section>
    <a class="native-company-switch" href="{{ route('company.profile.show') }}"><i class="bi bi-buildings"></i><span><small>Your company</small><strong>{{ auth()->user()->company?->name }}</strong></span><i class="bi bi-chevron-right"></i></a>
    @if($quotationCount)<a class="native-action-alert" href="{{ route('bookings.index') }}#quotes"><i class="bi bi-receipt-cutoff"></i><span><strong>{{ $quotationCount }} quotation{{ $quotationCount > 1 ? 's' : '' }} ready</strong><small>Review the inspection and approve or request changes.</small></span><i class="bi bi-chevron-right"></i></a>@endif
    <a class="native-photo-hero" href="{{ route('bookings.create', 'cleaning') }}"><img src="/images/app/company/cleaning-hero.webp" alt="NextFix cleaner preparing a Dubai holiday home"><span class="native-photo-overlay"></span><div><small>HOLIDAY-HOME CARE</small><h2>Guest-ready.<br>Every stay.</h2><span>Book cleaning <i class="bi bi-arrow-right"></i></span></div></a>
    <div class="native-service-pair" id="services">
        <a class="native-service-tile cleaning" href="{{ route('bookings.create', 'cleaning') }}">
            <span class="native-service-label"><i class="bi bi-stars"></i><strong>Cleaning</strong></span>
            <span class="native-service-cta">Book now <i class="bi bi-arrow-right"></i></span>
            <img class="native-service-person" src="/images/app/company/cleaner-cutout.png" alt="Professional NextFix cleaner holding fresh towels">
        </a>
        <a class="native-service-tile maintenance" href="{{ route('bookings.create', 'maintenance') }}">
            <span class="native-service-label"><i class="bi bi-tools"></i><strong>Maintenance</strong></span>
            <span class="native-service-cta">Request <i class="bi bi-arrow-right"></i></span>
            <img class="native-service-person" src="/images/app/company/technician-cutout.png" alt="Professional NextFix maintenance technician">
        </a>
    </div>
    <div class="native-quick-grid"><a href="{{ route('units.index') }}"><i class="bi bi-buildings"></i><span><small>My units</small><strong>{{ $unitCount }}</strong></span><i class="bi bi-chevron-right"></i></a><a href="{{ route('bookings.index') }}"><i class="bi bi-calendar-check"></i><span><small>Bookings</small><strong>{{ $upcoming->count() }}</strong></span><i class="bi bi-chevron-right"></i></a></div>
    <section class="native-upcoming"><div class="native-section-title"><div><small>UP NEXT</small><h3>Upcoming booking</h3></div><a href="{{ route('bookings.index') }}">View all</a></div>@forelse($upcoming as $booking)<a class="native-booking-card" href="{{ route('bookings.index') }}"><img src="/images/app/company/{{ $booking->service_type === 'maintenance' ? 'maintenance-hero.webp' : 'unit-dubai.webp' }}" alt=""><span class="native-booking-copy"><strong>{{ $booking->service_name }}</strong><small>{{ $booking->unit->building }} · Unit {{ $booking->unit->unit_number }}</small><em><i class="bi bi-calendar3"></i> {{ $booking->scheduled_at->format('D, d M · g:i A') }}</em></span><i class="bi bi-chevron-right"></i></a>@empty<a class="native-empty-card" href="{{ route('bookings.create', 'cleaning') }}"><i class="bi bi-calendar-plus"></i><span><strong>No booking yet</strong><small>Book your next guest-ready service.</small></span><i class="bi bi-chevron-right"></i></a>@endforelse</section>
</div>

<div class="company-desktop-only">
    @include('partials.themed-slider')
    @if($quotationCount)<a class="quotation-alert" href="{{ route('bookings.index') }}#quotes"><span><i class="bi bi-receipt-cutoff"></i></span><div><strong>{{ $quotationCount }} maintenance quotation{{ $quotationCount > 1 ? 's' : '' }} ready</strong><small>Review the inspection, fee credit and approve or request changes.</small></div><b>Review now <i class="bi bi-arrow-right"></i></b></a>@endif
    <section class="customer-hero" data-hero-slider><div class="hero-orb orb-one"></div><div class="hero-orb orb-two"></div><div class="hero-slides"><article class="hero-slide active"><p class="eyebrow">NEXTFIX HOME SERVICES</p><h2>Your property, always guest-ready.</h2><p>Book trusted cleaning and maintenance teams in under a minute.</p><a href="#services" class="hero-cta">Book a service <i class="bi bi-arrow-down"></i></a></article><article class="hero-slide"><p class="eyebrow">FAST & TRANSPARENT</p><h2>One request. Clear progress.</h2><p>Follow every visit from confirmation through completion in one calm workspace.</p><a href="{{ route('bookings.index') }}" class="hero-cta">Track bookings <i class="bi bi-arrow-right"></i></a></article></div><div class="hero-dots"><button class="active"></button><button></button></div></section>
    <div class="section-intro" id="services"><div><p class="eyebrow">NEW SERVICE BOOKING</p><h2>What do you need today?</h2><p>Choose a service and we’ll pre-fill the most common options.</p></div></div>
    <div class="app-service-grid"><a class="app-service-card cleaning" href="{{ route('bookings.create', 'cleaning') }}"><span class="app-service-icon"><i class="bi bi-stars"></i></span><div><h3>Cleaning</h3><p>Checkout, regular and deep cleaning for guest-ready homes.</p><strong>Book cleaning <i class="bi bi-arrow-right"></i></strong></div></a><a class="app-service-card maintenance" href="{{ route('bookings.create', 'maintenance') }}"><span class="app-service-icon"><i class="bi bi-tools"></i></span><div><h3>Maintenance</h3><p>AC, plumbing, electrical and general property repairs.</p><strong>Request maintenance <i class="bi bi-arrow-right"></i></strong></div></a></div>
    <div class="stat-grid app-stats"><article><span>Upcoming</span><strong>{{ $upcoming->count() }}</strong><small>scheduled services</small></article><article><span>Needs review</span><strong>{{ $pendingCount }}</strong><small>pending confirmations</small></article><article><span>In progress</span><strong>{{ $activeCount }}</strong><small>active jobs</small></article></div>
    <section class="panel"><div class="panel-head"><div><p class="eyebrow">UP NEXT</p><h3>Upcoming bookings</h3></div><a href="{{ route('bookings.index') }}">View all →</a></div>@forelse($upcoming as $booking)<div class="booking-row"><div class="date-tile"><strong>{{ $booking->scheduled_at->format('d') }}</strong><span>{{ strtoupper($booking->scheduled_at->format('M')) }}</span></div><div class="booking-main"><strong>{{ $booking->service_name }}</strong><span>{{ $booking->unit->building }} · Unit {{ $booking->unit->unit_number }}</span></div><span class="status {{ $booking->status }}">{{ str_replace('_', ' ', $booking->status) }}</span><time>{{ $booking->scheduled_at->format('g:i A') }}</time></div>@empty<div class="empty"><h4>No upcoming bookings</h4></div>@endforelse</section>
</div>
@endsection
