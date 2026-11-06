@extends('layouts.app', ['heading' => $service === 'cleaning' ? 'Book cleaning' : 'Request maintenance'])
@section('content')
<div class="booking-progress"><div><span>1</span><strong>Service</strong></div><i></i><div class="active"><span>2</span><strong>Details</strong></div><i></i><div><span>3</span><strong>Confirmed</strong></div></div>
<div class="booking-layout">
<form class="panel booking-form-card" method="post" action="{{ route('bookings.store') }}">@csrf<input type="hidden" name="service_type" value="{{ $service }}">
    <div class="booking-form-head"><span class="booking-service-badge {{ $service }}"><i class="bi {{ $service === 'cleaning' ? 'bi-stars' : 'bi-tools' }}"></i></span><div><p class="eyebrow">{{ $service === 'cleaning' ? 'CLEANING SERVICE' : 'MAINTENANCE SERVICE' }}</p><h2>{{ $service === 'cleaning' ? 'Let’s make it guest-ready' : 'What needs attention?' }}</h2><p>We’ve selected sensible defaults. Review them and send your request.</p></div></div>
    <div class="smart-form">
        <label><span>Property</span><select name="unit_id" required>@if($units->isEmpty())<option value="">Add a unit before booking</option>@else @foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('unit_id', $units->first()->id) == $unit->id)>{{ $unit->building }} · Unit {{ $unit->unit_number }}</option>@endforeach @endif</select></label>
        <fieldset><legend>{{ $service === 'cleaning' ? 'Cleaning type' : 'Issue category' }}</legend><div class="choice-grid">@php($choices = $service === 'cleaning' ? [['Checkout cleaning','bi-door-open'],['Regular cleaning','bi-calendar2-check'],['Deep cleaning','bi-stars']] : [['AC inspection','bi-snow'],['Plumbing inspection','bi-droplet'],['Electrical inspection','bi-lightning-charge'],['General maintenance','bi-tools']]) @foreach($choices as $index => $choice)<label class="choice-card"><input type="radio" name="service_name" value="{{ $choice[0] }}" @checked(old('service_name', $choices[0][0]) === $choice[0])><span><i class="bi {{ $choice[1] }}"></i><b>{{ $choice[0] }}</b><small>{{ $index === 0 ? 'Recommended' : 'Select option' }}</small></span></label>@endforeach</div></fieldset>
        <label><span>Preferred date and time</span><input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i')) }}" min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" required></label>
        <label><span>Anything the team should know? <small>Optional</small></span><textarea name="notes" placeholder="Access instructions, guest check-in time, issue details…">{{ old('notes') }}</textarea></label>
    </div>
    <div class="booking-submit"><div><i class="bi bi-shield-check"></i><span><strong>No online payment required</strong><small>We’ll confirm the schedule and price with you.</small></span></div><button class="primary" @disabled($units->isEmpty())>Request booking <i class="bi bi-arrow-right"></i></button></div>
</form>
<aside class="booking-help"><span class="help-icon"><i class="bi bi-chat-heart"></i></span><h3>Simple and transparent</h3><ul><li><i class="bi bi-check2"></i> No payment gateway</li><li><i class="bi bi-check2"></i> Price confirmed before work</li><li><i class="bi bi-check2"></i> Track status in the app</li></ul><p>Need something special? Add it in the notes and our team will review it.</p></aside>
</div>
@endsection
