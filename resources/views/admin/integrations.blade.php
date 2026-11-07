@extends('layouts.app', ['heading' => 'Integrations'])
@section('content')
<section class="hero compact"><div><p class="eyebrow">SECURE CONNECTIONS</p><h2>Email and payment integrations</h2><p>Credentials stay in the server .env and are never displayed or committed to GitHub.</p></div><span class="status confirmed">Server-managed secrets</span></section>
<div class="integration-grid">
    <section class="panel integration-card"><div class="integration-icon email"><i class="bi bi-envelope-paper"></i></div><div><p class="eyebrow">EMAIL DELIVERY</p><h3>SMTP</h3><p>Send booking confirmations, invoice notices, payment receipts and operational alerts.</p></div><span class="status {{ $smtpReady ? 'confirmed' : 'pending' }}">{{ $smtpReady ? 'Connected' : 'Setup required' }}</span><dl><dt>Host</dt><dd>{{ $smtpReady ? $smtpHost : 'Not configured' }}</dd><dt>From</dt><dd>{{ $smtpReady ? $smtpFrom : 'Not configured' }}</dd></dl><details><summary>Required .env settings</summary><pre>MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-account
MAIL_PASSWORD=your-secret-password
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=notifications@nextage.ae
MAIL_FROM_NAME="NextFix Home Services"</pre></details></section>
    <section class="panel integration-card"><div class="integration-icon payment"><i class="bi bi-credit-card-2-front"></i></div><div><p class="eyebrow">PAYMENT LINKS</p><h3>Nomod</h3><p>Create invoice payment links, reconcile successful payments and verify webhook callbacks.</p></div><span class="status {{ $nomodReady ? 'confirmed' : 'pending' }}">{{ $nomodReady ? 'Connected' : 'Disabled safely' }}</span><dl><dt>Mode</dt><dd>{{ ucfirst($nomodMode) }}</dd><dt>API credentials</dt><dd>{{ $nomodReady ? 'Available on server' : 'Not configured' }}</dd></dl><details><summary>Required .env settings</summary><pre>NOMOD_MODE=test
NOMOD_BASE_URL=https://api.nomod.com
NOMOD_API_KEY=server-secret
NOMOD_WEBHOOK_SECRET=server-secret</pre></details></section>
</div>
<section class="panel integration-roadmap"><div class="panel-head"><div><p class="eyebrow">RECOMMENDED AUTOMATIONS</p><h3>Next integration release</h3></div></div><div><article><i class="bi bi-calendar2-check"></i><span><strong>Booking confirmed</strong><small>Email company manager and add an in-app notification.</small></span></article><article><i class="bi bi-receipt"></i><span><strong>Invoice issued</strong><small>Email the PDF and attach a secure Nomod payment link.</small></span></article><article><i class="bi bi-shield-check"></i><span><strong>Payment verified</strong><small>Validate the webhook, record payment and mark invoice paid.</small></span></article><article><i class="bi bi-exclamation-diamond"></i><span><strong>Failure recovery</strong><small>Keep manual payment available and alert administrators.</small></span></article></div></section>
@endsection
