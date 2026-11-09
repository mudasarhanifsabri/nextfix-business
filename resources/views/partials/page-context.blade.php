@if(!request()->routeIs('dashboard', 'worker.home'))
<nav class="smart-breadcrumb" aria-label="Breadcrumb"><a href="{{ auth()->user()?->role === 'professional' ? route('worker.home') : route('dashboard') }}"><i class="bi bi-house"></i> Home</a><span aria-hidden="true">/</span><span aria-current="page">{{ $heading ?? $screenTitle ?? 'Workspace' }}</span></nav>
@endif
@if($errors->any())
<div class="smart-error-summary" role="alert"><strong>Please check your details</strong><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
@endif
