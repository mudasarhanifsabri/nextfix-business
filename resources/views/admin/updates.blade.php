@extends('layouts.app', ['heading' => 'System updates'])
@section('content')
<section class="hero compact"><div><p class="eyebrow">ONE-CLICK GITHUB DEPLOYMENT</p><h2>Update NextFix safely</h2><p>One click fetches the latest code from GitHub main, then backs up, migrates, activates and health-checks it.</p></div><span class="status {{ $scriptReady ? 'confirmed' : 'pending' }}">{{ $scriptReady ? 'Runner ready' : 'Runner not configured' }}</span></section>
<div class="update-grid">
<section class="panel update-card" data-github-update-check><div class="panel-head"><div><p class="eyebrow">INSTALLED</p><h3>Version {{ $installedVersion }}</h3></div><span class="github-live"><i></i> Live GitHub check</span></div><div class="update-body">@if($release)<p class="eyebrow">GITHUB {{ strtoupper($release['branch']) }}</p><h2>{{ $release['update_available'] ? 'New update available' : 'Software is up to date' }}</h2><p>{{ $release['notes'] }}</p><dl><dt>Latest commit</dt><dd><code>{{ $release['commit'] }}</code></dd></dl>
<form method="post" action="{{ route('admin.updates.store') }}">@csrf<label>Confirm administrator password<input type="password" name="password" required autocomplete="current-password"></label><button class="primary wide" @disabled(!$scriptReady || !$release['update_available'])>{{ $release['update_available'] ? 'Update all code from GitHub' : 'Already up to date' }}</button></form>
@else<div class="empty"><h4>GitHub check unavailable</h4><p>{{ $releaseError }}</p></div>@endif</div></section>
<section class="panel safeguards"><div class="panel-head"><div><p class="eyebrow">AUTOMATIC PIPELINE</p><h3>What one click does</h3></div></div><ul><li>✓ Fetches the latest GitHub main branch</li><li>✓ Locks the exact 40-character commit</li><li>✓ Refuses non-fast-forward or locally modified code</li><li>✓ Installs production Composer dependencies</li><li>✓ Runs database migrations and Laravel optimization</li><li>✓ Preserves the server-only .env and records deployment logs</li></ul></section></div>
@php
    $progressStages = [
        'queued' => [8, 'Update queued'],
        'preflight' => [16, 'Checking server'],
        'fetch' => [30, 'Fetching GitHub'],
        'activate' => [48, 'Activating new code'],
        'dependencies' => [65, 'Installing dependencies'],
        'migrate' => [80, 'Updating database'],
        'optimize' => [94, 'Optimizing application'],
        'complete' => [100, 'Update complete'],
    ];
    [$progressPercent, $progressLabel] = $progressStages[$activeRun?->stage ?? 'queued'] ?? [12, 'Preparing update'];
@endphp
@if($activeRun)
<section class="panel deployment-progress" data-deployment-progress>
    <div class="deployment-progress-head"><div><p class="eyebrow">LIVE DEPLOYMENT</p><h3>{{ $progressLabel }}</h3></div><strong>{{ $progressPercent }}%</strong></div>
    <div class="deployment-progress-track" role="progressbar" aria-label="Deployment progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progressPercent }}"><span style="width: {{ $progressPercent }}%"></span></div>
    <div class="deployment-progress-meta"><span>Commit {{ substr($activeRun->release_id, 0, 7) }}</span><span>The page refreshes automatically</span></div>
</section>
@endif
@if(!$activeRun && $latestRun?->status === 'failed')
<section class="panel deployment-error">
    <div><span class="deployment-error-icon">!</span><div><p class="eyebrow">LAST UPDATE FAILED</p><h3>{{ ucfirst($latestRun->stage) }}</h3><p>The code was left in its last safe state. Open the technical log below for the exact server error.</p></div></div>
    <details><summary>Show technical log</summary><pre>{{ $latestRun->log }}</pre></details>
</section>
@endif
<section class="panel history">
    <div class="panel-head deployment-history-head"><div><p class="eyebrow">RELEASE HISTORY</p><h3>Deployment runs</h3></div><span>{{ $runs->total() }} total</span></div>
    <div class="table-wrap"><table><thead><tr><th>Version</th><th>Requested by</th><th>Status</th><th>Stage</th><th>Started</th><th>Finished</th></tr></thead><tbody>
    @forelse($runs as $run)
        <tr><td><strong>{{ $run->version }}</strong><small>{{ $run->release_id }}</small></td><td>{{ $run->user->name }}</td><td><span class="status {{ $run->status === 'success' ? 'confirmed' : ($run->status === 'failed' ? 'pending' : 'in_progress') }}">{{ $run->status }}</span></td><td>{{ $run->stage }}</td><td>{{ $run->started_at?->format('d M Y, g:i A') ?? '—' }}</td><td>{{ $run->finished_at?->format('d M Y, g:i A') ?? '—' }}</td></tr>
        <tr class="deployment-log-row"><td colspan="6"><details><summary>View deployment log</summary><pre>{{ $run->log }}</pre></details></td></tr>
    @empty
        <tr><td colspan="6"><div class="empty"><p>No deployment runs recorded.</p></div></td></tr>
    @endforelse
    </tbody></table></div>
    @if($runs->hasPages())
    <nav class="deployment-pagination" aria-label="Deployment history pages">
        <span>Page {{ $runs->currentPage() }} of {{ $runs->lastPage() }}</span>
        <div>
            @if($runs->onFirstPage())<span class="disabled">Previous</span>@else<a href="{{ $runs->previousPageUrl() }}">Previous</a>@endif
            @if($runs->hasMorePages())<a href="{{ $runs->nextPageUrl() }}">Next</a>@else<span class="disabled">Next</span>@endif
        </div>
    </nav>
    @endif
</section>
@endsection
