@php
    $sliderManager = app(\App\Services\ThemeManager::class);
    $sliderScope = auth()->user()?->role === 'nextfix_admin' ? 'admin' : (auth()->user()?->role === 'professional' ? 'worker' : 'company');
    $sliderConfiguration = $sliderManager->configuration($sliderScope);
    $themeSlides = collect($sliderManager->slides($sliderConfiguration, $sliderScope))->sortBy('order')->values();
@endphp
@if($sliderConfiguration['preset'] !== 'classic')
<section class="theme-carousel" data-theme-carousel aria-label="NextFix service highlights" aria-roledescription="carousel" tabindex="0">
@foreach($themeSlides as $slide)
    <article class="theme-slide {{ $loop->first ? 'is-current' : '' }}" data-theme-slide aria-label="{{ $loop->iteration }} of {{ $themeSlides->count() }}" @if(!$loop->first) hidden @endif>
        @if($slide['image'])<img class="theme-slide-photo" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($slide['image']) }}" alt="" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
        @else<div class="theme-slide-photo theme-photo-{{ $slide['position'] }}" aria-hidden="true"></div>@endif
        <div class="theme-slide-copy"><h2>{{ $slide['title'] }}</h2><p>{{ $slide['caption'] }}</p><a class="primary" href="{{ $sliderManager->slideUrl($slide['action']) }}">{{ $sliderManager->actions($sliderScope)[$slide['action']] }} <i class="bi bi-arrow-right"></i></a></div>
    </article>
@endforeach
<div class="theme-carousel-controls"><button type="button" data-theme-slide-prev aria-label="Previous banner"><i class="bi bi-chevron-left"></i></button><div>@foreach($themeSlides as $slide)<button type="button" data-theme-slide-to="{{ $loop->index }}" aria-label="Show banner {{ $loop->iteration }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"></button>@endforeach</div><button type="button" data-theme-slide-next aria-label="Next banner"><i class="bi bi-chevron-right"></i></button></div>
</section>
@endif
