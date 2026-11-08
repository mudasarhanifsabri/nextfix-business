@php
    $isCompleted = $job->status === 'completed';
    $isAttention = in_array($job->status, ['on_hold', 'changes_requested'], true);
    $openUrl = $isCompleted ? route('jobs.report.show', $job) : route('worker.jobs.show', $job);
@endphp
<article class="native-job-card {{ $isCompleted ? 'completed' : '' }} {{ $isAttention ? 'attention' : '' }}">
    <div class="native-job-card__chips">
        <span class="status-chip {{ $isCompleted ? 'success' : ($isAttention ? 'warning' : 'info') }}">{{ strtoupper($job->operational_status_label) }}</span>
        @if($job->payment_status === 'paid')<span class="status-chip success">Paid</span>@endif
    </div>
    <a class="native-job-card__body" href="{{ $openUrl }}">
        <time class="native-date-tile" datetime="{{ $job->scheduled_at->toIso8601String() }}"><small>{{ $job->scheduled_at->timezone('Asia/Dubai')->format('M') }}</small><strong>{{ $job->scheduled_at->timezone('Asia/Dubai')->format('d') }}</strong><span>{{ $job->scheduled_at->timezone('Asia/Dubai')->format('g:i A') }}</span></time>
        <span class="worker-service-icon {{ $job->service_type }}"><i class="bi {{ $job->service_type === 'cleaning' ? 'bi-stars' : 'bi-tools' }}"></i></span>
        <span class="native-job-card__copy"><strong>{{ $job->service_name }}</strong><small>{{ $job->company->name }}</small><em><i class="bi bi-geo-alt"></i> {{ $job->unit->building }} · Unit {{ $job->unit->unit_number }}</em></span>
        <i class="bi bi-chevron-right native-job-card__arrow"></i>
    </a>
    <div class="native-job-card__footer"><span><i style="width:{{ $isCompleted ? 100 : $job->progress }}%"></i></span><small>{{ $isCompleted ? 'Service record ready' : $job->progress.'% complete' }}</small><a href="{{ route('chat.show', $job) }}" aria-label="Chat about {{ $job->reference }}"><i class="bi bi-chat-dots"></i></a></div>
</article>
