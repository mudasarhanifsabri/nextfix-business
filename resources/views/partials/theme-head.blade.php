@php
    $themeScope = auth()->user()?->role === 'nextfix_admin' ? 'admin' : (auth()->user()?->role === 'professional' ? 'worker' : 'company');
    $themeManager = app(\App\Services\ThemeManager::class);
    $appTheme = $themeManager->configuration($themeScope);
@endphp
<style>:root{--nf-primary:{{ $appTheme['primary'] }};--nf-secondary:{{ $appTheme['secondary'] }};--nf-on-primary:{{ $themeManager->foreground($appTheme['primary']) }};--nf-on-secondary:{{ $themeManager->foreground($appTheme['secondary']) }}}</style>
<script>
    (() => {
        const root = document.documentElement;
        root.dataset.theme = @json($appTheme['preset']);
        root.dataset.appearance = @json($appTheme['appearance']);
        root.classList.toggle('nf-themed', root.dataset.theme !== 'classic' || root.dataset.appearance !== 'light' || @json($appTheme['primary']) !== '#1267e8' || @json($appTheme['secondary']) !== '#6758d4');
        root.dataset.colorMode = root.dataset.appearance === 'system'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : root.dataset.appearance;
    })();
</script>
<link rel="stylesheet" href="{{ asset('css/themes.css') }}?v={{ substr(hash_file('sha256', public_path('css/themes.css')), 0, 12) }}">
<script src="{{ asset('js/themes.js') }}?v={{ substr(hash_file('sha256', public_path('js/themes.js')), 0, 12) }}" defer></script>
