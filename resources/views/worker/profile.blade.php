@extends('layouts.worker', ['title' => 'My profile | NextFix'])
@section('content')
<section class="worker-profile-hero"><div class="worker-profile-photo">@if($professional->profile_photo_path)<img src="{{ route('worker.profile.photo') }}" alt="{{ $professional->name }}">@else<span>{{ strtoupper(substr($professional->name, 0, 2)) }}</span>@endif</div><div><p>FIELD PROFILE</p><h1>{{ $professional->name }}</h1><span>{{ ucfirst($professional->role) }} · {{ ucwords(str_replace('_', ' ', $professional->status)) }}</span></div></section>
<form class="worker-profile-form" method="post" action="{{ route('worker.profile.update') }}" enctype="multipart/form-data">@csrf
<label class="profile-photo-picker"><i class="bi bi-camera"></i><span><strong>Profile picture</strong><small>Take a photo or choose JPG, PNG or WebP · max 5 MB</small></span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
<label>Phone number<input name="phone" value="{{ old('phone', $professional->phone) }}" required inputmode="tel"></label>
<div class="profile-readonly"><span>Email<strong>{{ $professional->email ?: $professional->user->email }}</strong></span><span>Skills<strong>{{ collect($professional->skills ?: ['General service'])->join(' · ') }}</strong></span><span>Working days<strong>{{ collect($professional->working_days ?: ['As scheduled'])->join(' · ') }}</strong></span></div>
@error('photo')<p class="worker-form-error">{{ $message }}</p>@enderror<button class="worker-save">Save profile</button></form>
@endsection
