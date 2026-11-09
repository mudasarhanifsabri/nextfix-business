@extends('layouts.worker', ['title' => $booking->reference.' | NextFix', 'screenTitle' => $booking->service_name, 'screenBack' => route('worker.jobs.index')])

@section('content')
@php
    $stage = $booking->service_type === 'cleaning'
        ? ($booking->status === 'completed' ? 'complete' : 'work')
        : ($booking->status === 'completed' ? 'complete' : ($booking->workflow_stage === 'inspect' ? 'inspection' : ($booking->workflow_stage ?? 'inspection')));
    $approvalStages = ['admin_review', 'awaiting_customer_approval', 'awaiting_quote_payment', 'changes_requested'];
    $journeyStage = in_array($stage, $approvalStages, true) ? 'approval' : $stage;
    $afterPhotos = $booking->workPhotos->where('category', 'after');
    $beforePhotos = $booking->workPhotos->where('category', 'before');
    $completedItems = array_values(array_intersect($booking->completed_items ?? [], array_keys($checklist)));
    $completedCount = count($completedItems);
    $checklistCount = count($checklist);
    $displayProgress = (int) round(($completedCount / max($checklistCount, 1)) * 100);
    $startPhotoReady = $booking->service_type === 'cleaning' ? $beforePhotos->isNotEmpty() : $booking->inspectionPhotos->isNotEmpty();
    $completionReady = $completedCount === $checklistCount && $afterPhotos->isNotEmpty() && $startPhotoReady;
    $quoteGross = (($booking->labour_amount_minor ?? 0) + ($booking->parts_amount_minor ?? 0)) / 100;
    $inspectionCredit = min(($booking->inspection_fee_minor ?? 0) / 100, $quoteGross);
    $quoteNet = max(0, $quoteGross - $inspectionCredit);
    $journey = $booking->service_type === 'cleaning'
        ? ['before' => 'Before', 'work' => 'Clean', 'proof' => 'After', 'complete' => 'Done']
        : ['inspection' => 'Inspect', 'estimate' => 'Estimate', 'approval' => 'Company', 'work' => 'Work', 'complete' => 'Done'];
    $activeJourney = $booking->service_type === 'cleaning'
        ? ($stage === 'complete' ? 'complete' : ($afterPhotos->isNotEmpty() ? 'proof' : ($beforePhotos->isNotEmpty() ? 'work' : 'before')))
        : $journeyStage;
    $workDefaultTab = $booking->service_type === 'cleaning'
        ? ($beforePhotos->isEmpty() ? 'before' : ($completedCount < $checklistCount ? 'tasks' : 'proof'))
        : ($completedCount < $checklistCount ? ($completedCount === 0 ? 'scope' : 'tasks') : 'proof');
    $cleaningPhotoPlan = array_values(array_unique(array_merge(['Entrance / first view'], array_values($checklist), ['Final wide view'])));
@endphp

<section class="stage-job-hero">
    <div class="stage-job-hero__copy">
        <p>{{ $booking->reference }}</p>
        <h1>{{ $booking->service_name }}</h1>
        <span><i class="bi bi-geo-alt"></i> {{ $booking->unit->building }} · Unit {{ $booking->unit->unit_number }}</span>
        <small><i class="bi bi-calendar3"></i> {{ $booking->scheduled_at->timezone(config('app.timezone'))->format('D, d M · g:i A') }}</small>
    </div>
    <span class="worker-status {{ $booking->status }}">{{ $booking->operational_status_label }}</span>
</section>

<div class="stage-contact-actions">
    <a href="{{ route('chat.show', $booking) }}"><i class="bi bi-chat-dots-fill"></i><span>Chat</span></a>
    @if($booking->company->phone)
        <a href="tel:{{ preg_replace('/[^+0-9]/', '', $booking->company->phone) }}"><i class="bi bi-telephone-fill"></i><span>Call</span></a>
    @endif
</div>

<nav class="stage-journey" aria-label="Job progress">
    @php($activeIndex = array_search($activeJourney, array_keys($journey), true))
    @foreach($journey as $key => $label)
        <span class="{{ $loop->index <= $activeIndex ? 'is-active' : '' }} {{ $key === $activeJourney ? 'is-current' : '' }}">
            <i>{{ $loop->iteration }}</i><b>{{ $label }}</b>
        </span>
    @endforeach
</nav>

@if($stage === 'inspection')
    <section class="stage-workspace" data-stage-workspace data-default-stage="{{ $booking->inspectionPhotos->isEmpty() ? 'evidence' : 'issue' }}">
        <header class="stage-heading">
            <span>1</span>
            <div><small>FIRST VISIT</small><h2>Inspect the issue</h2></div>
        </header>
        <nav class="stage-tabs" aria-label="Inspection steps">
            <button type="button" data-stage-tab="issue"><i class="bi bi-ui-checks"></i> Issue</button>
            <button type="button" data-stage-tab="evidence"><i class="bi bi-camera"></i> Evidence <em>{{ $booking->inspectionPhotos->count() }}</em></button>
            <button type="button" data-stage-tab="summary"><i class="bi bi-check2-circle"></i> Review</button>
        </nav>

        <form method="post" action="{{ route('worker.jobs.update', $booking) }}" class="stage-form">
            @csrf
            <input type="hidden" name="action" value="inspection">
            <div class="stage-panel" data-stage-panel="issue">
                <label class="stage-label">Overall condition</label>
                <div class="tap-choice-grid tap-choice-grid--three">
                    @foreach(['good' => ['Good', 'bi-check-circle'], 'attention' => ['Needs attention', 'bi-exclamation-circle'], 'urgent' => ['Urgent', 'bi-lightning-charge']] as $value => [$label, $icon])
                        <label><input type="radio" name="inspection_condition" value="{{ $value }}" {{ $booking->inspection_condition === $value ? 'checked' : '' }} required><span><i class="bi {{ $icon }}"></i>{{ $label }}</span></label>
                    @endforeach
                </div>
                <label class="stage-label">What did you find?</label>
                <div class="tap-choice-grid tap-choice-grid--two">
                    @foreach(['water_leak' => ['Water leak', 'bi-droplet'], 'electrical' => ['Electrical', 'bi-lightning'], 'ac' => ['AC', 'bi-snow'], 'damage' => ['Damage', 'bi-hammer'], 'hygiene' => ['Hygiene', 'bi-stars'], 'access' => ['Access', 'bi-key'], 'safety' => ['Safety', 'bi-shield-exclamation'], 'other' => ['Other', 'bi-three-dots']] as $value => [$label, $icon])
                        <label><input type="checkbox" name="inspection_issues[]" value="{{ $value }}" {{ in_array($value, $booking->inspection_issues ?? [], true) ? 'checked' : '' }}><span><i class="bi {{ $icon }}"></i>{{ $label }}</span></label>
                    @endforeach
                </div>
                <label class="stage-field"><span>Short report <small>optional</small></span><textarea name="inspection_report" rows="3" placeholder="What is wrong and what work is needed?">{{ old('inspection_report', $booking->inspection_report) }}</textarea></label>
                <button type="button" class="stage-primary" data-stage-next="evidence">Add photo evidence <i class="bi bi-arrow-right"></i></button>
            </div>

            <div class="stage-panel" data-stage-panel="summary">
                <div class="stage-review-card">
                    <i class="bi bi-clipboard2-check"></i>
                    <div><strong>Ready to submit?</strong><p>{{ $booking->inspectionPhotos->count() }} evidence photo{{ $booking->inspectionPhotos->count() === 1 ? '' : 's' }} saved. Condition and issue selections will become read-only.</p></div>
                </div>
                <div class="stage-tip"><i class="bi bi-lightbulb"></i><span>Capture the affected area and model label before continuing.</span></div>
                <button class="stage-primary"><i class="bi bi-check2-circle"></i> Save inspection & continue</button>
            </div>
        </form>

        <div class="stage-panel" data-stage-panel="evidence">
            <form method="post" action="{{ route('worker.jobs.update', $booking) }}" enctype="multipart/form-data" data-maintenance-camera-session data-camera-title="Inspection evidence" data-camera-plan='["Reported issue","Affected part","Model or serial label","Surrounding area","Access and safety"]'>
                @csrf
                <input type="hidden" name="action" value="inspection_photo">
                <label class="guided-camera-picker">
                    <input type="file" name="inspection_photos[]" accept="image/*" capture="environment" multiple required>
                    <i class="bi bi-camera-fill"></i><span><strong>Take inspection photos</strong><small>Stay in camera · review and delete before upload</small></span><b>Open</b>
                </label>
                <button class="stage-primary"><i class="bi bi-cloud-arrow-up"></i> Upload evidence</button>
            </form>
            @if($booking->inspectionPhotos->isNotEmpty())
                <div class="compact-photo-grid" data-gallery>
                    @foreach($booking->inspectionPhotos as $photo)
                        <a href="{{ route('worker.evidence.show', $photo) }}" data-gallery-item data-src="{{ route('worker.evidence.show', $photo) }}" data-caption="Inspection · {{ $photo->captured_at->timezone(config('app.timezone'))->format('d M · g:i A') }}"><img src="{{ route('worker.evidence.show', $photo) }}" alt="Inspection evidence {{ $loop->iteration }}"><b>{{ $loop->iteration }}</b></a>
                    @endforeach
                </div>
            @endif
            <button type="button" class="stage-primary" data-stage-next="summary" {{ $booking->inspectionPhotos->isEmpty() ? 'disabled' : '' }}>Review inspection <i class="bi bi-arrow-right"></i></button>
        </div>
    </section>
@endif

@if($booking->inspection_report && !in_array($stage, ['inspection', 'estimate'], true))
    <details class="stage-history-card">
        <summary><i class="bi bi-check-circle-fill"></i><span><small>INSPECTION SAVED</small><strong>First inspection</strong><em>Tap to view the submitted report</em></span><i class="bi bi-chevron-down"></i></summary>
        <div class="stage-history-card__body">
            <p>{{ $booking->inspection_report }}</p>
            @if($booking->inspectionPhotos->isNotEmpty())
                <div class="compact-photo-grid" data-gallery>
                    @foreach($booking->inspectionPhotos as $photo)
                        <a href="{{ route('worker.evidence.show', $photo) }}" data-gallery-item data-src="{{ route('worker.evidence.show', $photo) }}" data-caption="Inspection evidence"><img src="{{ route('worker.evidence.show', $photo) }}" alt="Inspection evidence {{ $loop->iteration }}"></a>
                    @endforeach
                </div>
            @endif
        </div>
    </details>
@endif

@if($stage === 'estimate')
    <section class="stage-workspace estimate-workspace" data-stage-workspace data-default-stage="labour">
        <header class="stage-heading">
            <span>2</span>
            <div><small>QUICK QUOTATION</small><h2>Labour & parts estimate</h2></div>
        </header>
        <div class="estimate-inspection-summary">
            <header><span><i class="bi bi-clipboard2-check-fill"></i><b>Inspection summary</b></span><small>{{ $booking->inspectionPhotos->count() }} photos</small></header>
            <p>{{ $booking->inspection_report ?: 'Inspection completed.' }}</p>
            @if($booking->inspectionPhotos->isNotEmpty())
                <div class="estimate-evidence-strip" data-gallery>
                    @foreach($booking->inspectionPhotos->take(4) as $photo)
                        <a href="{{ route('worker.evidence.show', $photo) }}" data-gallery-item data-src="{{ route('worker.evidence.show', $photo) }}" data-caption="Inspection evidence {{ $loop->iteration }}"><img src="{{ route('worker.evidence.show', $photo) }}" alt="Inspection evidence {{ $loop->iteration }}"></a>
                    @endforeach
                </div>
            @endif
        </div>
        <nav class="stage-tabs" aria-label="Estimate steps">
            <button type="button" data-stage-tab="labour"><i class="bi bi-person-workspace"></i> Labour</button>
            <button type="button" data-stage-tab="parts"><i class="bi bi-tools"></i> Parts</button>
            <button type="button" data-stage-tab="review"><i class="bi bi-receipt"></i> Review</button>
        </nav>
        <form method="post" action="{{ route('worker.jobs.update', $booking) }}" class="stage-form" data-estimate-form data-inspection-credit="{{ ($booking->inspection_fee_minor ?? 0) / 100 }}">
            @csrf
            <input type="hidden" name="action" value="estimate">
            <div class="stage-panel" data-stage-panel="labour">
                <label class="stage-label">Hours required</label>
                <div class="number-stepper">
                    <button type="button" data-number-step="-0.5" aria-label="Reduce hours"><i class="bi bi-dash"></i></button>
                    <input type="number" name="labour_hours" value="{{ old('labour_hours', $booking->labour_hours ?? 1) }}" min="0" max="999" step="0.5" required>
                    <span>hours</span>
                    <button type="button" data-number-step="0.5" aria-label="Add hours"><i class="bi bi-plus"></i></button>
                </div>
                <label class="money-field"><span>Labour total</span><b>AED</b><input type="number" name="labour_cost" value="{{ old('labour_cost', ($booking->labour_amount_minor ?? 0) / 100) }}" min="0" step="0.01" inputmode="decimal" required></label>
                <button type="button" class="stage-primary" data-stage-next="parts">Add parts <i class="bi bi-arrow-right"></i></button>
            </div>
            <div class="stage-panel" data-stage-panel="parts">
                <div class="quick-choice-row">
                    <button type="button" data-no-parts><i class="bi bi-slash-circle"></i> No parts</button>
                    <button type="button" data-focus-parts><i class="bi bi-plus-circle"></i> Add parts</button>
                </div>
                <label class="money-field"><span>Parts total</span><b>AED</b><input type="number" name="parts_cost" value="{{ old('parts_cost', ($booking->parts_amount_minor ?? 0) / 100) }}" min="0" step="0.01" inputmode="decimal" required></label>
                <label class="stage-field"><span>Parts or materials</span><textarea name="parts_required" rows="3" placeholder="Example: thermostat, valve, cable">{{ old('parts_required', $booking->parts_required) }}</textarea></label>
                <button type="button" class="stage-primary" data-stage-next="review">Review quotation <i class="bi bi-arrow-right"></i></button>
            </div>
            <div class="stage-panel" data-stage-panel="review">
                <div class="estimate-review">
                    <div><span>Labour + parts</span><strong data-estimate-gross>AED 0.00</strong></div>
                    <div class="credit"><span>Inspection fee credit</span><strong>− AED {{ number_format(($booking->inspection_fee_minor ?? 0) / 100, 2) }}</strong></div>
                    <div class="total"><span>Company pays before VAT</span><strong data-estimate-net>AED 0.00</strong></div>
                </div>
                <label class="stage-field"><span>Message to company <small>optional</small></span><textarea name="estimate_note" rows="3" placeholder="Warranty, lead time or useful note">{{ old('estimate_note', $booking->estimate_note) }}</textarea></label>
                <div class="stage-tip"><i class="bi bi-lock"></i><span>The quotation locks after submission. The company approves and pays.</span></div>
                <button class="stage-primary"><i class="bi bi-send-check"></i> Send estimate for approval</button>
            </div>
        </form>
    </section>
@endif

@if($booking->estimate_submitted_at)
    <details class="stage-history-card">
        <summary><i class="bi bi-check-circle-fill"></i><span><small>QUOTATION SENT</small><strong>Labour & parts estimate</strong><em>Tap to view the locked quotation</em></span><i class="bi bi-chevron-down"></i></summary>
        <div class="stage-history-card__body estimate-mini-summary">
            <div><span>Labour</span><strong>{{ $booking->labour_hours }} hr · AED {{ number_format(($booking->labour_amount_minor ?? 0) / 100, 2) }}</strong></div>
            <div><span>Parts</span><strong>AED {{ number_format(($booking->parts_amount_minor ?? 0) / 100, 2) }}</strong></div>
            @if($booking->parts_required)<p>{{ $booking->parts_required }}</p>@endif
            <a class="stage-secondary" href="{{ route('bookings.quotation.pdf', $booking) }}"><i class="bi bi-file-earmark-pdf"></i> Quotation PDF</a>
        </div>
    </details>
@endif

@if(in_array($stage, $approvalStages, true))
    <section class="approval-wait-card {{ $stage === 'changes_requested' ? 'needs-changes' : '' }}">
        <div class="approval-wait-card__top"><i class="bi {{ $stage === 'changes_requested' ? 'bi-arrow-repeat' : 'bi-hourglass-split' }}"></i><span><small>{{ $stage === 'changes_requested' ? 'COMPANY REPLIED' : 'COMPANY DECISION' }}</small><h2>{{ $stage === 'changes_requested' ? 'Changes requested' : ($stage === 'awaiting_quote_payment' ? 'Approved · waiting for payment' : 'Waiting for company approval') }}</h2></span></div>
        @if($booking->customer_decision_note)<p class="decision-note">{{ $booking->customer_decision_note }}</p>@endif
        <div class="approval-status-line">
            @foreach(['Submitted', 'Reviewed', 'Approved', 'Paid'] as $label)
                @php($done = $loop->index === 0 || ($loop->index === 1 && in_array($stage, ['changes_requested', 'awaiting_quote_payment'], true)) || ($loop->index === 2 && $stage === 'awaiting_quote_payment') || ($loop->index === 3 && $booking->payment_status === 'paid'))
                <span class="{{ $done ? 'done' : '' }}"><i class="bi {{ $done ? 'bi-check' : 'bi-circle' }}"></i><b>{{ $label }}</b></span>
            @endforeach
        </div>
        <div class="approval-actions">
            <a href="{{ route('bookings.quotation.pdf', $booking) }}"><i class="bi bi-file-earmark-pdf"></i> Quotation</a>
            <a href="{{ route('chat.show', $booking) }}"><i class="bi bi-chat-dots"></i> Message company</a>
        </div>
    </section>
@endif

@if($stage === 'complete')
    <section class="completed-record-card">
        <i class="bi bi-patch-check-fill"></i>
        <div><small>VERIFIED SERVICE RECORD</small><h2>Job completed</h2><p>{{ $booking->work_completed_at?->timezone(config('app.timezone'))->format('d M Y · g:i A') }} · checklist and photos are locked.</p></div>
        <a href="{{ route('jobs.report.show', $booking) }}"><i class="bi bi-file-earmark-text"></i> View report</a>
    </section>
    <details class="completed-details-card">
        <summary><span><i class="bi bi-list-check"></i><b>Verified checklist</b><small>{{ $completedCount }} of {{ $checklistCount }} complete</small></span><i class="bi bi-chevron-down"></i></summary>
        <div>@foreach($checklist as $key => $label)<p><i class="bi bi-check-circle-fill"></i>{{ $label }}</p>@endforeach</div>
    </details>
    @if($afterPhotos->isNotEmpty())
        <section class="completed-photo-card"><header><div><small>FINAL PROOF</small><h2>Completed work</h2></div><span>{{ $afterPhotos->count() }} photos</span></header><div class="compact-photo-grid" data-gallery>@foreach($afterPhotos as $photo)<a href="{{ route('worker.evidence.show', $photo) }}" data-gallery-item data-src="{{ route('worker.evidence.show', $photo) }}" data-caption="Completed work · {{ $photo->captured_at->timezone(config('app.timezone'))->format('d M · g:i A') }}"><img src="{{ route('worker.evidence.show', $photo) }}" alt="Completed work {{ $loop->iteration }}"></a>@endforeach</div></section>
    @endif
@endif

@if($stage === 'work')
    <section class="work-stage-shell" data-stage-workspace data-default-stage="{{ $workDefaultTab }}">
        <nav class="work-stage-tabs" aria-label="Work steps">
            @if($booking->service_type === 'cleaning')
                <button type="button" data-stage-tab="before"><i class="bi bi-camera"></i><span>Before</span></button>
                <button type="button" data-stage-tab="tasks"><i class="bi bi-stars"></i><span>Clean</span></button>
            @else
                <button type="button" data-stage-tab="scope"><i class="bi bi-shield-check"></i><span>Scope</span></button>
                <button type="button" data-stage-tab="tasks"><i class="bi bi-tools"></i><span>Repair</span></button>
            @endif
            <button type="button" data-stage-tab="proof"><i class="bi bi-camera-fill"></i><span>Proof</span></button>
        </nav>

        @if($booking->service_type === 'cleaning')
            <div class="stage-panel" data-stage-panel="before">
                <div class="photo-stage-intro"><span>1</span><div><small>BEFORE CLEANING</small><h2>Show how the unit arrived</h2><p>Take a quick starting photo, then begin cleaning.</p></div></div>
                <form method="post" action="{{ route('worker.jobs.update', $booking) }}" enctype="multipart/form-data" data-maintenance-camera-session data-camera-title="Before cleaning" data-camera-plan='@json($cleaningPhotoPlan)'>
                    @csrf<input type="hidden" name="action" value="photo"><input type="hidden" name="work_photo_category" value="before">
                    <label class="guided-camera-picker"><input type="file" name="work_photos[]" accept="image/*" capture="environment" multiple required><i class="bi bi-camera-fill"></i><span><strong>Take before photos</strong><small>Guided multi-shot camera · auto batch upload</small></span><b>{{ $beforePhotos->count() ?: 'Open' }}</b></label>
                    <button class="stage-primary"><i class="bi bi-cloud-arrow-up"></i> Upload before photos</button>
                </form>
                @if($beforePhotos->isNotEmpty())<div class="compact-photo-grid" data-gallery>@foreach($beforePhotos as $photo)<a href="{{ route('worker.evidence.show', $photo) }}" data-gallery-item data-src="{{ route('worker.evidence.show', $photo) }}" data-caption="Before cleaning"><img src="{{ route('worker.evidence.show', $photo) }}" alt="Before cleaning {{ $loop->iteration }}"></a>@endforeach</div><button type="button" class="stage-primary" data-stage-next="tasks">Start cleaning <i class="bi bi-arrow-right"></i></button>@endif
            </div>
        @else
            <div class="stage-panel" data-stage-panel="scope">
                <div class="photo-stage-intro"><span><i class="bi bi-patch-check-fill"></i></span><div><small>APPROVED BY COMPANY</small><h2>Repair scope</h2><p>Only complete the approved work below.</p></div></div>
                <div class="approved-scope-grid"><div><span>Labour</span><strong>{{ $booking->labour_hours ?: 0 }} hours</strong></div><div><span>Parts</span><strong>{{ $booking->parts_required ?: 'No parts required' }}</strong></div><div class="wide"><span>Approved amount</span><strong>AED {{ number_format($quoteNet, 2) }}</strong></div></div>
                @if($booking->inspectionPhotos->isNotEmpty())<details class="inline-evidence"><summary><i class="bi bi-images"></i> View inspection evidence <span>{{ $booking->inspectionPhotos->count() }} photos</span></summary><div class="compact-photo-grid" data-gallery>@foreach($booking->inspectionPhotos as $photo)<a href="{{ route('worker.evidence.show', $photo) }}" data-gallery-item data-src="{{ route('worker.evidence.show', $photo) }}" data-caption="Inspection evidence"><img src="{{ route('worker.evidence.show', $photo) }}" alt="Inspection evidence {{ $loop->iteration }}"></a>@endforeach</div></details>@endif
                <button type="button" class="stage-primary" data-stage-next="tasks">Start approved work <i class="bi bi-arrow-right"></i></button>
            </div>
        @endif

        <div class="stage-panel" data-stage-panel="tasks">
            <form method="post" action="{{ route('worker.jobs.update', $booking) }}" class="worker-checklist compact-task-workflow" data-task-workflow data-auto-save="1">
                @csrf<input type="hidden" name="action" value="save">
                <div class="compact-progress"><header><span><small>WORK PROGRESS</small><strong data-progress-value>{{ $displayProgress }}%</strong></span><b data-progress-count>{{ $completedCount }} of {{ $checklistCount }}</b></header><div><i data-progress-bar style="width:{{ $displayProgress }}%"></i></div></div>
                <div class="task-focus-heading"><small>{{ $booking->service_type === 'cleaning' ? 'CLEAN ONE AREA' : 'APPROVED REPAIR' }}</small><h2 data-task-title>Next task</h2><span><b data-task-position>1</b> / {{ $checklistCount }}</span></div>
                <div class="worker-check-items task-focus-list">
                    @foreach($checklist as $key => $label)
                        <label data-task-card data-task-key="{{ $key }}"><input type="checkbox" name="completed_items[]" value="{{ $key }}" {{ in_array($key, $completedItems, true) ? 'checked' : '' }}><span><i class="bi bi-check-lg"></i></span><strong>{{ $label }}</strong><small>{{ in_array($key, $completedItems, true) ? 'Done' : 'Tap when finished' }}</small></label>
                    @endforeach
                </div>
                <div class="task-controls"><button type="button" data-task-prev><i class="bi bi-arrow-left"></i> Back</button><button type="button" data-task-done><i class="bi bi-check2"></i> Mark done</button><button type="button" data-task-next>Next <i class="bi bi-arrow-right"></i></button></div>
                <button class="stage-secondary task-save"><i class="bi bi-cloud-check"></i> Save progress now <small data-auto-save-state></small></button>
                <button type="button" class="stage-primary" data-stage-next="proof">Add final proof <i class="bi bi-arrow-right"></i></button>
            </form>
        </div>

        <div class="stage-panel" data-stage-panel="proof">
            <div class="photo-stage-intro"><span>{{ $booking->service_type === 'cleaning' ? '3' : '5' }}</span><div><small>FINAL PROOF</small><h2>Show the finished result</h2><p>Clear after photos confirm the work to the company.</p></div></div>
            <form method="post" action="{{ route('worker.jobs.update', $booking) }}" enctype="multipart/form-data" data-maintenance-camera-session data-camera-title="After work" data-camera-plan='@json($cleaningPhotoPlan)'>
                @csrf<input type="hidden" name="action" value="photo"><input type="hidden" name="work_photo_category" value="after">
                <label class="guided-camera-picker"><input type="file" name="work_photos[]" accept="image/*" capture="environment" multiple required><i class="bi bi-camera-fill"></i><span><strong>Take finished-work photos</strong><small>Review, delete and upload without leaving the app</small></span><b>{{ $afterPhotos->count() ?: 'Open' }}</b></label>
                <button class="stage-primary"><i class="bi bi-cloud-arrow-up"></i> Upload final photos</button>
            </form>
            @if($afterPhotos->isNotEmpty())<div class="compact-photo-grid" data-gallery>@foreach($afterPhotos as $photo)<a href="{{ route('worker.evidence.show', $photo) }}" data-gallery-item data-src="{{ route('worker.evidence.show', $photo) }}" data-caption="Completed work"><img src="{{ route('worker.evidence.show', $photo) }}" alt="Completed work {{ $loop->iteration }}"></a>@endforeach</div>@endif

            <details class="hold-card"><summary><i class="bi bi-pause-circle"></i><span><strong>Can’t finish?</strong><small>Put the job on hold with two taps</small></span><i class="bi bi-chevron-down"></i></summary><form method="post" action="{{ route('worker.jobs.update', $booking) }}">@csrf<input type="hidden" name="action" value="hold"><div class="hold-reasons">@foreach(['wet_surface' => 'Still wet', 'waiting_parts' => 'Waiting parts', 'no_access' => 'No access', 'safety_issue' => 'Safety issue', 'customer_request' => 'Customer request', 'other' => 'Other'] as $value => $label)<label><input type="radio" name="hold_reason" value="{{ $value }}" required><span>{{ $label }}</span></label>@endforeach</div><textarea name="hold_note" rows="2" placeholder="Optional short note"></textarea><button class="stage-secondary">Save job on hold</button></form></details>

            <form method="post" action="{{ route('worker.jobs.update', $booking) }}" class="complete-job-form" data-swipe-complete data-enabled="{{ $completionReady ? '1' : '0' }}">@csrf<input type="hidden" name="action" value="complete">@foreach($completedItems as $item)<input type="hidden" name="completed_items[]" value="{{ $item }}">@endforeach<div class="completion-gate"><i class="bi {{ $completionReady ? 'bi-unlock' : 'bi-lock' }}"></i><span><strong>{{ $completionReady ? 'Ready to finish' : 'Finish requirements first' }}</strong><small>{{ $completionReady ? 'Slide fully to close the job' : (($checklistCount - $completedCount).' tasks and '.($afterPhotos->isEmpty() ? 'after photos' : 'proof').' remaining') }}</small></span></div><div class="swipe-track {{ $completionReady ? '' : 'disabled' }}"><button type="button" class="swipe-knob" aria-label="Swipe to complete"><i class="bi bi-chevron-right"></i></button><span>Swipe to complete</span></div></form>
        </div>
    </section>
@endif

@endsection
