<button type="button" class="smart-tap-launch" data-smart-tap-open aria-haspopup="dialog" aria-controls="smart-tap-dialog"><i class="bi bi-lightning-charge"></i><span>Quick actions</span></button>
<dialog id="smart-tap-dialog" class="smart-tap-dialog" aria-labelledby="smart-tap-title">
<div class="smart-tap-head"><div><small>NEXTFIX</small><h2 id="smart-tap-title">Where do you want to go?</h2></div><button type="button" class="secondary" data-smart-tap-close aria-label="Close quick actions">✕</button></div>
<label class="smart-tap-search">Find a page<input type="search" data-smart-tap-search placeholder="Bookings, units, payments…" autocomplete="off"></label>
<p class="smart-tap-hint">Shortcuts to pages available to your account. Press Ctrl / Cmd + K to open.</p>
<nav data-smart-tap-links aria-label="Quick page shortcuts"></nav><p data-smart-tap-empty hidden>No matching pages. Try another word.</p>
</dialog>
<link rel="stylesheet" href="{{ asset('css/smart-tap.css') }}?v={{ substr(hash_file('sha256', public_path('css/smart-tap.css')), 0, 12) }}">
<script src="{{ asset('js/smart-tap.js') }}?v={{ substr(hash_file('sha256', public_path('js/smart-tap.js')), 0, 12) }}" defer></script>
