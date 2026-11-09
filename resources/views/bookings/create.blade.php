@extends('layouts.app', ['heading' => $service === 'cleaning' ? 'Book cleaning' : 'Request maintenance', 'mobileFocus' => true])
@section('content')
@php
    $nativeScheduled = \Illuminate\Support\Carbon::parse(old('scheduled_at', now()->addDay()->setTime(10, 0)));
    $nativeImages = ['touch_up' => 'cleaning-hero.webp', 'normal_materials' => 'cleaning-hero.webp', 'normal_linen' => 'checkout-cleaning.webp', 'client_in_stay' => 'checkout-cleaning.webp', 'deep_materials' => 'deep-cleaning.webp', 'deep_linen' => 'deep-cleaning.webp', 'intensive_deep' => 'deep-cleaning.webp'];
@endphp
@if($service === 'cleaning')
    @include('bookings.partials.mobile-cleaning-wizard')
@else
<div class="company-native-only native-booking-screen">
    <a class="native-back" href="{{ route('dashboard') }}"><i class="bi bi-arrow-left"></i><span>{{ $service === 'cleaning' ? 'Book cleaning' : 'Maintenance' }}</span></a>
    <form class="native-booking-form" method="post" action="{{ route('bookings.store') }}" enctype="multipart/form-data" data-native-booking @if($service === 'cleaning') data-cleaning-booking data-rates-active="{{ $rateCard && $rateCard->status === 'approved' ? '1' : '0' }}" data-booking-rates='@json($bookingRates)' @endif>
        @csrf
        <input type="hidden" name="service_type" value="{{ $service }}">
        <select class="native-hidden-select" name="unit_id" required aria-label="Selected property">@if($units->isEmpty())<option value="">Add a unit before booking</option>@else @foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('unit_id', $units->first()->id) == $unit->id)>{{ $unit->building }} · Unit {{ $unit->unit_number }}</option>@endforeach @endif</select>

        @if($service === 'maintenance')
            <section class="native-booking-banner"><img src="/images/app/company/maintenance-hero.webp" alt="NextFix technician servicing an air conditioner"><span></span><div><small>INSPECTION FIRST</small><h2>Expert care<br>for every unit.</h2><p>Approve the quotation before work begins.</p></div></section>
        @endif

        <section class="native-booking-section"><div class="native-section-title"><div><small>STEP 1</small><h3>Choose a unit</h3></div><span>{{ $units->count() }} {{ str('unit')->plural($units->count()) }}</span></div><div class="native-unit-strip">@forelse($units as $unit)<button type="button" class="native-unit-option {{ old('unit_id', $units->first()->id) == $unit->id ? 'active' : '' }}" data-native-unit="{{ $unit->id }}" aria-pressed="{{ old('unit_id', $units->first()->id) == $unit->id ? 'true' : 'false' }}"><span class="native-unit-photo"><img src="/images/app/company/unit-dubai.webp" alt="{{ $unit->building }} unit {{ $unit->unit_number }}"><i class="bi bi-check-lg"></i></span><span class="native-unit-copy"><strong>Unit {{ $unit->unit_number }}</strong><small>{{ $unit->building }}</small><em>{{ $unit->rate_unit_label }}</em></span></button>@empty<a class="native-add-unit" href="{{ route('units.create') }}"><i class="bi bi-plus-circle"></i>Add your first unit</a>@endforelse</div></section>

        <section class="native-booking-section"><div class="native-section-title"><div><small>STEP 2</small><h3>{{ $service === 'cleaning' ? 'Choose a cleaning service' : 'What needs attention?' }}</h3></div>@if($service === 'cleaning')<span data-booking-unit-label></span>@endif</div>
            @if($service === 'cleaning')
                <div class="native-cleaning-options">@foreach($cleaningTypes as $code => $label)<label class="native-cleaning-option contract-choice" data-rate-code="{{ $code }}"><input type="radio" name="service_name" value="{{ $label }}" @checked(old('service_name') === $label)><span class="native-choice-image"><img src="/images/app/company/{{ $nativeImages[$code] }}" alt="{{ $label }}"><i class="bi bi-check-lg"></i></span><span class="native-cleaning-copy"><strong>{{ str($label)->replace(' + ', ' & ') }}</strong><small data-rate-price>Checking rate…</small></span></label>@endforeach</div><div class="no-contracted-service" data-no-contracted-service hidden><i class="bi bi-receipt"></i><strong>No approved service for this unit type</strong><a href="{{ route('rates.show') }}">Review rates</a></div>
            @else
                @php($nativeChoices = [['AC inspection','bi-snow','blue'],['Plumbing inspection','bi-droplet','coral'],['Electrical inspection','bi-lightning-charge','amber'],['General maintenance','bi-tools','green']])
                <div class="native-maintenance-options">@foreach($nativeChoices as $index => $choice)<label class="native-maintenance-option {{ $choice[2] }}"><input type="radio" name="service_name" value="{{ $choice[0] }}" @checked(old('service_name', $nativeChoices[0][0]) === $choice[0])><span><i class="bi {{ $choice[1] }}"></i><b>{{ str($choice[0])->before(' inspection') }}</b><em class="bi bi-check-circle-fill"></em></span></label>@endforeach</div>
            @endif
        </section>

        <section class="native-booking-section native-schedule"><div class="native-section-title"><div><small>STEP 3</small><h3>Select date and time</h3></div><span>{{ $nativeScheduled->format('F Y') }}</span></div><input type="hidden" name="scheduled_at" value="{{ $nativeScheduled->format('Y-m-d\TH:i') }}" data-native-scheduled required><div class="native-date-strip">@for($day = 1; $day <= 7; $day++)@php($date = now()->addDays($day))<button type="button" class="native-date-option {{ $date->isSameDay($nativeScheduled) ? 'active' : '' }}" data-native-date="{{ $date->format('Y-m-d') }}"><small>{{ $date->format('D') }}</small><strong>{{ $date->format('d') }}</strong></button>@endfor</div><h4>Select a time</h4><div class="native-time-strip">@foreach(['08:30'=>'8:30 AM','10:00'=>'10:00 AM','12:00'=>'12:00 PM','14:00'=>'2:00 PM','16:00'=>'4:00 PM'] as $value => $label)<button type="button" class="native-time-option {{ $nativeScheduled->format('H:i') === $value ? 'active' : '' }}" data-native-time="{{ $value }}">{{ $label }}</button>@endforeach</div></section>

        <details class="native-booking-details"><summary><i class="bi bi-chat-left-text"></i><span><strong>Add notes or photos</strong><small>{{ $service === 'maintenance' ? 'Show the issue to help the technician prepare' : 'Optional access details for the cleaning team' }}</small></span><i class="bi bi-chevron-down"></i></summary><label>Notes<textarea name="notes" placeholder="Access instructions or helpful details…">{{ old('notes') }}</textarea></label><label class="native-photo-upload"><input type="file" name="booking_photos[]" accept="image/jpeg,image/png,image/webp" multiple data-photo-input data-preview-target="native-booking-photo-preview"><i class="bi bi-camera"></i><span><strong>Add photos</strong><small>Camera or gallery · up to 8</small></span></label><div id="native-booking-photo-preview" class="booking-photo-preview"></div></details>

        <div class="native-booking-submit"><span><small>Your agreed rate</small><strong data-booking-summary-title>{{ $service === 'cleaning' ? 'Choose a service' : 'Inspection payment' }}</strong><em data-booking-summary-copy>{{ $service === 'cleaning' ? 'Select a service to see the price.' : 'Credited after quotation approval.' }}</em></span><button type="submit" @disabled($units->isEmpty() || !$rateCard || $rateCard->status !== 'approved')>Continue <i class="bi bi-arrow-right"></i></button></div>
    </form>
</div>
@endif

<div class="company-desktop-only">
@if(!$rateCard || $rateCard->status !== 'approved')<div class="review-banner booking-rate-warning"><i class="bi bi-receipt"></i><div><strong>Rate card approval required</strong><p>Your company’s service prices are not active yet. <a href="{{ route('rates.show') }}">Open Rates</a> to review the proposal or ask NextFix for an update.</p></div></div>@endif
<div class="booking-progress"><div><span>1</span><strong>Service</strong></div><i></i><div class="active"><span>2</span><strong>Details</strong></div><i></i><div><span>3</span><strong>Confirmed</strong></div></div>
<div class="booking-layout">
<form class="panel booking-form-card" method="post" action="{{ route('bookings.store') }}" enctype="multipart/form-data" @if($service === 'cleaning') data-cleaning-booking data-rates-active="{{ $rateCard && $rateCard->status === 'approved' ? '1' : '0' }}" data-booking-rates='@json($bookingRates)' data-extra-rates='@json($extraRates)' @endif>@csrf<input type="hidden" name="service_type" value="{{ $service }}">
    <div class="booking-form-head"><span class="booking-service-badge {{ $service }}"><i class="bi {{ $service === 'cleaning' ? 'bi-stars' : 'bi-tools' }}"></i></span><div><p class="eyebrow">{{ $service === 'cleaning' ? 'APPROVED CLEANING MENU' : 'MAINTENANCE SERVICE' }}</p><h2>{{ $service === 'cleaning' ? 'Choose the clean you agreed' : 'What needs attention?' }}</h2><p>{{ $service === 'cleaning' ? 'Select a unit first. We will show only its contracted services and exact price.' : 'We’ve selected sensible defaults. Review them and send your request.' }}</p></div></div>
    <div class="smart-form">
        <label><span>Property</span><select name="unit_id" required>@if($units->isEmpty())<option value="">Add a unit before booking</option>@else @foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('unit_id', $units->first()->id) == $unit->id)>{{ $unit->building }} · Unit {{ $unit->unit_number }} · {{ $unit->rate_unit_label }}</option>@endforeach @endif</select></label>
        @if($service === 'cleaning')
            <fieldset><legend>Available services <small data-booking-unit-label></small></legend><div class="choice-grid contracted-choice-grid">@foreach($cleaningTypes as $code => $label)@php($icon = str_contains($code, 'deep') ? 'bi-stars' : (str_contains($code, 'linen') ? 'bi-basket2' : 'bi-house-check'))<label class="choice-card contract-choice" data-rate-code="{{ $code }}"><input type="radio" name="service_name" value="{{ $label }}" @checked(old('service_name') === $label)><span><i class="bi {{ $icon }}"></i><b>{{ $label }}</b><small data-rate-price>Checking rate…</small><em><i class="bi bi-check2"></i></em></span></label>@endforeach</div><div class="no-contracted-service" data-no-contracted-service hidden><i class="bi bi-receipt"></i><strong>No approved cleaning service for this unit type</strong><a href="{{ route('rates.show') }}">Review contract rates</a></div></fieldset>
            @if($extraRates !== [])
                <details class="booking-desktop-addons">
                    <summary><span><i class="bi bi-plus-circle"></i><b>Linen & add-ons</b><small>Optional · only approved contract rates</small></span><i class="bi bi-chevron-down"></i></summary>
                    <div>
                        @foreach($extraRates as $sectionCode => $section)
                            <section><header><strong>{{ $section['title'] }}</strong><small>{{ $section['unit'] }}</small></header>
                                @foreach($section['items'] as $itemCode => $item)
                                    <label><span><b>{{ $item['label'] }}</b><small>AED {{ number_format($item['amount'] / 100, 2) }}</small></span><input type="number" min="0" max="20" value="{{ (int) old("extras.{$sectionCode}.{$itemCode}", 0) }}" name="extras[{{ $sectionCode }}][{{ $itemCode }}]" data-extra-input data-extra-section="{{ $sectionCode }}" data-extra-code="{{ $itemCode }}" data-extra-label="{{ $item['label'] }}" data-extra-price="{{ $item['amount'] }}"></label>
                                @endforeach
                            </section>
                        @endforeach
                    </div>
                </details>
            @endif
        @else
            <fieldset><legend>Issue category</legend><div class="choice-grid">@php($choices = [['AC inspection','bi-snow'],['Plumbing inspection','bi-droplet'],['Electrical inspection','bi-lightning-charge'],['General maintenance','bi-tools']]) @foreach($choices as $index => $choice)<label class="choice-card"><input type="radio" name="service_name" value="{{ $choice[0] }}" @checked(old('service_name', $choices[0][0]) === $choice[0])><span><i class="bi {{ $choice[1] }}"></i><b>{{ $choice[0] }}</b><small>{{ $index === 0 ? 'Recommended' : 'Select option' }}</small></span></label>@endforeach</div></fieldset>
        @endif
        <label><span>Preferred date and time</span><input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i')) }}" min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" required></label>
        <label><span>Anything the team should know? <small>Optional</small></span><textarea name="notes" placeholder="Access instructions, guest check-in time, issue details…">{{ old('notes') }}</textarea></label>
        <div class="booking-photo-proof"><div><span>{{ $service === 'maintenance' ? 'Show the issue' : 'Helpful property photos' }} <small>Optional</small></span><p>{{ $service === 'maintenance' ? 'Add clear photos of the fault, equipment or affected area. This helps the worker arrive prepared.' : 'Add access or condition photos only when they help the cleaning team.' }}</p></div><label><input type="file" name="booking_photos[]" accept="image/jpeg,image/png,image/webp" multiple data-photo-input data-preview-target="booking-photo-preview"><span><i class="bi bi-camera"></i><strong>Take or add photos</strong><small>Up to 8 images</small></span></label><div id="booking-photo-preview" class="booking-photo-preview"></div>@error('booking_photos.*')<p class="error">{{ $message }}</p>@enderror</div>
    </div>
    <div class="booking-submit"><div><i class="bi {{ $service === 'cleaning' ? 'bi-receipt' : 'bi-shield-lock' }}"></i><span><strong data-booking-summary-title>{{ $service === 'cleaning' ? 'Choose an approved service' : 'Secure inspection payment' }}</strong><small data-booking-summary-copy>{{ $service === 'cleaning' ? 'The exact contract price will appear after you select a unit and service.' : 'The approved inspection fee is credited when you approve the repair quotation.' }}</small></span></div><button class="primary" @disabled($units->isEmpty() || !$rateCard || $rateCard->status !== 'approved')>{{ $service === 'cleaning' ? 'Book cleaning' : 'Continue to inspection payment' }} <i class="bi bi-arrow-right"></i></button></div>
</form>
<aside class="booking-help"><span class="help-icon"><i class="bi {{ $service === 'cleaning' ? 'bi-stars' : 'bi-tools' }}"></i></span><h3>{{ $service === 'cleaning' ? 'Direct cleaning booking' : 'Inspection-first maintenance' }}</h3>@if($service === 'cleaning')<ul><li><i class="bi bi-check2"></i> Only your approved services appear</li><li><i class="bi bi-check2"></i> Exact unit rate shown before booking</li><li><i class="bi bi-check2"></i> Track cleaner and progress in the app</li></ul><p>No inspection or repair quotation is required for cleaning.</p>@else<ul><li><i class="bi bi-check2"></i> Pay inspection fee securely with Nomod</li><li><i class="bi bi-check2"></i> Receive report, photos and quotation</li><li><i class="bi bi-check2"></i> Inspection fee credited after approval</li></ul><p>Repair work starts only after your approval and final payment.</p>@endif</aside>
</div>
</div>
@endsection
