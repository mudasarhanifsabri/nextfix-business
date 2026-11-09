@extends('layouts.app', ['heading' => 'Contract rates', 'mobileTitle' => 'Rates'])
@section('content')
<div class="rate-page-head customer">
    <div><p class="eyebrow">YOUR NEXTFIX AGREEMENT</p><h2>Your approved services</h2><p>Choose a unit to see exactly what your team can book and the agreed price.</p></div>
    <span class="rate-state {{ $rateCard->status ?? 'draft' }}"><i class="bi bi-patch-check"></i> {{ str_replace('_',' ', $rateCard->status ?? 'not sent') }}</span>
</div>

@if(!$rateCard->exists || $rateCard->status === 'draft')
    <section class="panel empty-rate-card"><i class="bi bi-file-earmark-text"></i><h3>Your rate card is being prepared</h3><p>NextFix has not sent a rate proposal yet. Booking prices will remain unavailable until you approve one.</p></section>
@else
    <section class="contract-rate-hero">
        <div class="contract-rate-copy"><span><i class="bi bi-stars"></i></span><div><p class="eyebrow">ACTIVE BOOKING MENU</p><h3>Cleaning rates by property type</h3><p>Only priced services can be selected during booking. No calculations or rate searching required.</p></div></div>
        <a href="{{ route('bookings.create', 'cleaning') }}" class="primary"><i class="bi bi-calendar2-check"></i> Book cleaning</a>
    </section>

    @php
        $firstUnit = collect($unitTypes)->keys()->first(fn($unit) => collect($cleaningTypes)->contains(fn($label, $service) => data_get($rateCard->rates ?? [], "cleaning.$unit.$service") !== null)) ?? array_key_first($unitTypes);
    @endphp
    <section class="panel contracted-services" data-rate-browser data-active-unit="{{ $firstUnit }}">
        <div class="panel-head contract-browser-head"><div><p class="eyebrow">CLEANING</p><h3>Pick a property type</h3><p>Prices shown below are approved and ready to book.</p></div><span>All amounts AED</span></div>
        <div class="contract-unit-tabs" role="tablist" aria-label="Property type">
            @foreach($unitTypes as $unit => $unitLabel)
                @php($count = collect($cleaningTypes)->filter(fn($label, $service) => data_get($rateCard->rates ?? [], "cleaning.$unit.$service") !== null)->count())
                <button type="button" class="{{ $unit === $firstUnit ? 'active' : '' }}" data-rate-unit="{{ $unit }}" role="tab" aria-selected="{{ $unit === $firstUnit ? 'true' : 'false' }}">
                    <strong>{{ $unitLabel }}</strong><small>{{ $count }} {{ \Illuminate\Support\Str::plural('service', $count) }}</small>
                </button>
            @endforeach
        </div>
        <div class="contract-service-panels">
            @foreach($unitTypes as $unit => $unitLabel)
                <div class="contract-service-grid" data-rate-panel="{{ $unit }}" @if($unit !== $firstUnit) hidden @endif>
                    @foreach($cleaningTypes as $service => $serviceLabel)
                        @php($minor = data_get($rateCard->rates ?? [], "cleaning.$unit.$service"))
                        @if($minor !== null)
                            <article><span class="contract-service-icon"><i class="bi {{ str_contains($service, 'deep') ? 'bi-stars' : (str_contains($service, 'linen') ? 'bi-basket2' : 'bi-house-check') }}"></i></span><div><small>{{ $unitLabel }}</small><strong>{{ $serviceLabel }}</strong></div><b>AED {{ number_format($minor/100,2) }}</b><a href="{{ route('bookings.create', 'cleaning') }}" aria-label="Book {{ $serviceLabel }}"><i class="bi bi-arrow-right"></i></a></article>
                        @endif
                    @endforeach
                    @if(!collect($cleaningTypes)->contains(fn($label, $service) => data_get($rateCard->rates ?? [], "cleaning.$unit.$service") !== null))
                        <div class="contract-empty"><i class="bi bi-receipt"></i><strong>No cleaning service priced for {{ $unitLabel }}</strong><span>Ask NextFix to add this unit type to your agreement.</span></div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <section class="panel inspection-rate-card customer-inspection-rate">
        <div class="rate-icon"><i class="bi bi-clipboard2-pulse"></i></div>
        <div><p class="eyebrow">MAINTENANCE · INSPECTION FIRST</p><h3>One inspection fee for every issue</h3><p>Pay before the visit. Approve the repair quotation and this fee is credited in full.</p></div>
        <strong class="inspection-price">{{ $rateCard->maintenance_inspection_minor === null ? 'Not quoted' : 'AED '.number_format($rateCard->maintenance_inspection_minor/100,2) }}</strong>
        <a href="{{ route('bookings.create', 'maintenance') }}" class="secondary">Report issue <i class="bi bi-arrow-right"></i></a>
    </section>

    <details class="panel rate-section full-contract-reference">
        <summary><span><small>CONTRACT REFERENCE</small><strong>View full cleaning price matrix</strong></span><i class="bi bi-chevron-down"></i></summary>
        <div class="rate-table-wrap"><table class="rate-matrix readonly"><thead><tr><th>Unit</th>@foreach($cleaningTypes as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>@foreach($unitTypes as $unit => $unitLabel)<tr><th>{{ $unitLabel }}</th>@foreach($cleaningTypes as $service => $serviceLabel)@php($minor=data_get($rateCard->rates ?? [], "cleaning.$unit.$service"))<td>{{ $minor === null ? '—' : number_format($minor/100,2) }}</td>@endforeach</tr>@endforeach</tbody></table></div>
    </details>

    <div class="rate-section-grid contract-extras">
        @foreach($sections as $section => $sectionData)
            <details class="panel rate-section"><summary><span><small>{{ strtoupper($sectionData['unit']) }}</small><strong>{{ $sectionData['title'] }}</strong></span><i class="bi bi-chevron-down"></i></summary><div class="rate-item-grid readonly">@foreach($sectionData['items'] as $item => $label)@php($minor=data_get($rateCard->rates ?? [], "extras.$section.$item"))<div><span>{{ $label }}</span><strong>{{ $minor === null ? '—' : 'AED '.number_format($minor/100,2) }}</strong></div>@endforeach</div></details>
        @endforeach
    </div>

    <section class="panel customer-terms"><div><small>Cancellation after arrival</small><strong>{{ data_get($rateCard->terms ?? [], 'cancellation_percent') === null ? 'Not specified' : data_get($rateCard->terms, 'cancellation_percent').'%' }}</strong></div><div><small>Extra transport / pull-out</small>@php($transport=data_get($rateCard->terms ?? [], 'transport_fee_minor'))<strong>{{ $transport === null ? 'Not specified' : 'AED '.number_format($transport/100,2) }}</strong></div></section>

    @if($rateCard->status === 'pending_approval')
        <section class="panel rate-decision"><div><p class="eyebrow">ACTION REQUIRED</p><h3>Approve this rate card?</h3><p>Approval activates quoted prices for future bookings. Request a review if anything needs changing.</p></div><form method="post" action="{{ route('rates.decision') }}">@csrf<textarea name="review_note" placeholder="Optional for approval. Required when requesting a review."></textarea><div><button class="secondary" name="decision" value="review"><i class="bi bi-chat-left-text"></i> Request review</button><button class="primary" name="decision" value="approve"><i class="bi bi-check2-circle"></i> Approve rates</button></div></form></section>
    @elseif($rateCard->status === 'approved')
        <div class="approval-confirmed"><i class="bi bi-patch-check-fill"></i><span><strong>Approved and active</strong><small>Approved {{ $rateCard->approved_at?->timezone('Asia/Dubai')->format('d M Y, g:i A') }}</small></span></div>
    @elseif($rateCard->status === 'review_requested')
        <div class="review-banner"><i class="bi bi-clock-history"></i><div><strong>Review requested</strong><p>{{ $rateCard->review_note }}</p></div></div>
    @endif
@endif
@endsection
