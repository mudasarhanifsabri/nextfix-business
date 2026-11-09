@extends('layouts.app', ['heading' => 'Theme Manager'])
@section('content')
<nav class="theme-tabs" aria-label="Choose application">
@foreach($scopes as $key => $label)
    <a href="{{ route('admin.themes.index', ['scope' => $key]) }}" @if($scope === $key) aria-current="page" @endif>{{ $label }}</a>
@endforeach
</nav>
<div class="theme-layout">
<section>
<form method="post" action="{{ route('admin.themes.publish') }}" class="theme-manager" data-theme-form enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="scope" value="{{ $scope }}">
    <input type="hidden" name="revision" value="{{ $record->revision }}">
    <h2>{{ $scopes[$scope] }}</h2>
    <p class="theme-note">Choose an appearance for this app. Preview changes here, then publish when ready. Other apps keep their own theme.</p>
    @if($errors->any())<div class="error" role="alert"><strong>Please check these settings:</strong><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
    <fieldset><legend class="eyebrow">THEME PRESET</legend><div class="theme-presets">
    @foreach($presets as $key => $preset)
        <label><input type="radio" name="preset" value="{{ $key }}" data-primary="{{ $preset['primary'] }}" data-secondary="{{ $preset['secondary'] }}" @checked(old('preset', $configuration['preset']) === $key)><span>{{ $preset['name'] }}</span><span class="theme-swatches"><i style="background:{{ $preset['primary'] }}"></i><i style="background:{{ $preset['secondary'] }}"></i></span><small>{{ $key === 'classic' ? 'Keep the familiar layout' : ($key === 'colorful' ? 'Blue, violet and service photos' : 'Calm teal and warm accents') }}</small></label>
    @endforeach
    </div></fieldset>
    <div class="theme-fields">
        <label>Appearance<select name="appearance">@foreach(['light' => 'Light', 'dark' => 'Dark', 'system' => 'Follow device'] as $key => $label)<option value="{{ $key }}" @selected(old('appearance', $configuration['appearance']) === $key)>{{ $label }}</option>@endforeach</select></label>
        <label>Primary color<input type="color" name="primary" value="{{ old('primary', $configuration['primary']) }}" required></label>
        <label>Secondary color<input type="color" name="secondary" value="{{ old('secondary', $configuration['secondary']) }}" required></label>
    </div>
    <details class="theme-slider-editor"><summary>Slider pictures, captions and destinations</summary>
        <p class="theme-note">Three banners per app. JPG, PNG or WebP, up to 5 MB; at least 480 × 240 pixels. Images are decorative; the title and caption carry the meaning.</p>
        @foreach($slides as $index => $slide)
        <fieldset class="theme-slider-fields" data-slider-editor="{{ $index }}" data-current-image="{{ $slide['image'] ? \Illuminate\Support\Facades\Storage::disk('public')->url($slide['image']) : '' }}"><legend>Banner {{ $index + 1 }}</legend>
            <label>Title<input name="sliders[{{ $index }}][title]" maxlength="70" value="{{ old('sliders.'.$index.'.title', $slide['title']) }}" required></label>
            <label>Caption<input name="sliders[{{ $index }}][caption]" maxlength="150" value="{{ old('sliders.'.$index.'.caption', $slide['caption']) }}"></label>
            <label>Destination<select name="sliders[{{ $index }}][action]">@foreach($actions as $route => $label)<option value="{{ $route }}" @selected(old('sliders.'.$index.'.action', $slide['action']) === $route)>{{ $label }}</option>@endforeach</select></label>
            <label>Order<select name="sliders[{{ $index }}][order]">@foreach([0,1,2] as $order)<option value="{{ $order }}" @selected((int) old('sliders.'.$index.'.order', $slide['order']) === $order)>{{ $order + 1 }}</option>@endforeach</select></label>
            @if($slide['image'])<img class="theme-current-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($slide['image']) }}" alt="Current banner {{ $index + 1 }}"><label class="check"><input type="checkbox" name="sliders[{{ $index }}][remove_image]" value="1">Reset to included picture</label>@endif
            <label>Replace picture<input type="file" name="sliders[{{ $index }}][image]" accept="image/jpeg,image/png,image/webp"></label>
        </fieldset>
        @endforeach
    </details>
    <p class="theme-note">Classic + Light preserves the existing design. Colorful adds the service photography from your design concept. Bookings, rates, invoices, payments and permissions stay connected to the same application.</p>
    <p class="theme-note">Your published choice appears on the next page load. Preview uses illustrative records; it does not create bookings.</p>
    <div class="theme-actions"><button type="button" class="secondary" data-theme-preview-button>Preview changes</button><button class="primary" type="submit">Publish theme</button></div>
</form>
<form class="theme-manager theme-restore" method="post" action="{{ route('admin.themes.restore') }}">
    @csrf<input type="hidden" name="scope" value="{{ $scope }}"><input type="hidden" name="revision" value="{{ $record->revision }}">
    <h2>Restore previous theme</h2><p class="theme-note">Return this app to its previous published appearance. You can also select Current / Classic above.</p>
    <button class="secondary" @disabled($record->previous_configuration === null)>Restore previous theme</button>
</form>
</section>
<aside class="theme-preview-wrap" aria-label="Theme preview">
    <p aria-live="polite">Live preview · Changes here are not published</p>
    <div class="theme-preview" data-theme-preview data-preview-scope="{{ $scope }}" data-theme="{{ old('preset', $configuration['preset']) }}">
        <div class="preview-brand">N / NextFix {{ $scope === 'admin' ? 'Admin' : ($scope === 'worker' ? 'Work' : 'Business') }}</div>
        <h3>{{ $scope === 'admin' ? 'Operations overview' : ($scope === 'worker' ? 'Let’s get to work' : 'Hello, Ayyan') }}</h3>
        <div class="preview-banner" data-preview-banner><strong>{{ $scope === 'admin' ? 'Every unit. Taken care of.' : 'Guest-ready. Every stay.' }}</strong><span>{{ $scope === 'worker' ? 'View assigned jobs' : ($scope === 'admin' ? 'Open operations' : 'Book cleaning') }} →</span></div>
        <div class="preview-dots" aria-hidden="true">● · ·</div>
        <div class="preview-banner-controls"><button type="button" data-preview-slide="0">1</button><button type="button" data-preview-slide="1">2</button><button type="button" data-preview-slide="2">3</button></div><div class="preview-services"><div><i class="bi bi-stars"></i>{{ $scope === 'admin' ? 'Bookings' : 'Cleaning' }}</div><div><i class="bi bi-tools"></i>{{ $scope === 'admin' ? 'Approvals' : 'Maintenance' }}</div></div>
        <div class="preview-summary"><div><small>{{ $scope === 'worker' ? 'Assigned today' : 'Upcoming' }}</small><strong>6</strong></div><div><small>{{ $scope === 'admin' ? 'Need review' : 'In progress' }}</small><strong>3</strong></div></div>
        <div class="preview-job"><strong>Unit 528 · Riviera 18</strong><small>Regular cleaning · Tomorrow, 10:00 AM</small></div>
    </div>
</aside>
</div>
@endsection
