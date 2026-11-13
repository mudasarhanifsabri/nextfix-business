@php
    $selectedUnitId = (string) old('unit_id', optional($units->first())->id);
    $selectedIssue = old('service_name', 'AC inspection');
    $unitBuildings = $units->pluck('building')->filter()->unique()->values();
    $maintenanceChoices = [
        ['value' => 'AC inspection', 'label' => 'Air conditioning', 'description' => 'Cooling, airflow or AC fault.', 'category' => 'cooling', 'icon' => 'bi-snow', 'tone' => 'blue'],
        ['value' => 'Plumbing inspection', 'label' => 'Plumbing', 'description' => 'Leak, blockage or water issue.', 'category' => 'water', 'icon' => 'bi-droplet', 'tone' => 'coral'],
        ['value' => 'Electrical inspection', 'label' => 'Electrical', 'description' => 'Power, lighting or socket issue.', 'category' => 'electrical', 'icon' => 'bi-lightning-charge', 'tone' => 'amber'],
        ['value' => 'Appliance inspection', 'label' => 'Appliances', 'description' => 'Washer, fridge, oven or appliance fault.', 'category' => 'appliances', 'icon' => 'bi-plug', 'tone' => 'green'],
        ['value' => 'Water heater inspection', 'label' => 'Water heater', 'description' => 'No hot water, heating or tank leak.', 'category' => 'water', 'icon' => 'bi-thermometer-sun', 'tone' => 'coral'],
        ['value' => 'Door and lock inspection', 'label' => 'Doors & locks', 'description' => 'Door, handle, lock or access problem.', 'category' => 'fixtures', 'icon' => 'bi-key', 'tone' => 'blue'],
        ['value' => 'Carpentry inspection', 'label' => 'Carpentry & fixtures', 'description' => 'Cabinet, shelf, furniture or fitting.', 'category' => 'fixtures', 'icon' => 'bi-hammer', 'tone' => 'amber'],
        ['value' => 'Painting inspection', 'label' => 'Painting & walls', 'description' => 'Wall damage, stain, crack or touch-up.', 'category' => 'fixtures', 'icon' => 'bi-paint-bucket', 'tone' => 'coral'],
        ['value' => 'General maintenance', 'label' => 'Other maintenance', 'description' => 'Another apartment repair or inspection.', 'category' => 'general', 'icon' => 'bi-tools', 'tone' => 'green'],
    ];
    $inspectionMinor = (int) ($rateCard?->maintenance_inspection_minor ?? 0);
@endphp

<div class="company-native-only booking-wizard booking-wizard--maintenance" data-booking-wizard data-wizard-kind="maintenance"
     data-wizard-title-unit="Maintenance" data-wizard-title-service="Choose issue" data-wizard-title-schedule="Schedule inspection">
    <header class="booking-wizard__header">
        <a href="{{ route('dashboard') }}" aria-label="Go back" data-wizard-header-back><i class="bi bi-arrow-left"></i></a>
        <strong data-wizard-header-title>Maintenance</strong>
        <span></span>
    </header>

    <div class="booking-wizard__progress-shell">
        <p><b data-wizard-step-count>1 of 3</b><span aria-hidden="true">·</span><strong data-wizard-step-copy>Choose unit</strong></p>
        <nav class="booking-wizard__progress" aria-label="Maintenance request progress">
            @foreach([1 => 'Unit', 2 => 'Issue', 3 => 'Schedule'] as $number => $label)
                <button type="button" class="{{ $number === 1 ? 'active' : '' }}" data-wizard-step="{{ $number }}" @disabled($number > 1)>
                    <b>{{ $number }}</b><span>{{ $label }}</span>
                </button>
                @if($number < 3)<i></i>@endif
            @endforeach
        </nav>
    </div>

    <form method="post" action="{{ route('bookings.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="service_type" value="maintenance">
        <input type="hidden" name="service_name" value="{{ $selectedIssue }}" data-service-name>
        <select class="native-hidden-select" name="unit_id" required aria-label="Selected property">
            @foreach($units as $unit)<option value="{{ $unit->id }}" @selected($selectedUnitId === (string) $unit->id)>{{ $unit->building }} · Unit {{ $unit->unit_number }}</option>@endforeach
        </select>

        @if($errors->any())
            <div class="booking-wizard__errors" role="alert"><i class="bi bi-exclamation-circle"></i><div><strong>Please check your request</strong><span>{{ $errors->first() }}</span></div></div>
        @endif

        <section class="booking-wizard__panel" data-wizard-panel="1">
            @if($units->isNotEmpty())
                <div class="booking-wizard__search-row">
                    <label class="booking-wizard__search"><i class="bi bi-search"></i><input type="search" placeholder="Search unit, building or area" data-unit-search autocomplete="off"><button type="button" data-search-clear aria-label="Clear search"><i class="bi bi-x"></i></button></label>
                    <details class="booking-wizard__filters">
                        <summary aria-label="Filter units"><i class="bi bi-sliders2"></i></summary>
                        <div class="booking-wizard__chips" data-unit-filters>
                            <button type="button" class="active" data-unit-filter="all">All</button>
                            @foreach($unitBuildings as $building)<button type="button" data-unit-filter="{{ str($building)->lower() }}">{{ $building }}</button>@endforeach
                        </div>
                    </details>
                </div>
                <div class="booking-wizard__title booking-wizard__title--list"><h2>Choose a unit</h2><small>{{ $units->count() }} {{ $units->count() === 1 ? 'unit' : 'units' }}</small><button type="button" aria-label="Filter units"><i class="bi bi-sliders2"></i></button></div>
                <div class="booking-wizard__unit-list" data-unit-list>
                    @foreach($units as $unit)
                        <button type="button" class="booking-wizard__unit {{ $selectedUnitId === (string) $unit->id ? 'active' : '' }}"
                                data-native-unit="{{ $unit->id }}" data-unit-search-text="{{ str($unit->building.' '.$unit->unit_number.' '.$unit->address)->lower() }}"
                                data-unit-building="{{ str($unit->building)->lower() }}" data-unit-building-label="{{ $unit->building }}" data-unit-type="{{ $unit->rate_unit_label }}" data-unit-title="Unit {{ $unit->unit_number }}" data-unit-subtitle="{{ $unit->building }} · {{ $unit->rate_unit_label }}">
                            <img src="/images/app/company/unit-dubai.webp" alt="">
                            <span><strong>Unit {{ $unit->unit_number }}</strong><small>{{ $unit->building }}</small><em>{{ $unit->rate_unit_label }}</em></span>
                            <i class="booking-wizard__favorite bi {{ $loop->odd ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                            <i class="booking-wizard__select bi bi-check-lg"></i>
                        </button>
                    @endforeach
                </div>
                <div class="booking-wizard__empty" data-unit-empty hidden><i class="bi bi-search"></i><strong>No matching unit</strong><span>Try a unit number or another building.</span></div>
            @else
                <a class="booking-wizard__empty" href="{{ route('units.create') }}"><i class="bi bi-building-add"></i><strong>Add your first unit</strong><span>A unit is required before requesting maintenance.</span></a>
            @endif
            <div class="booking-wizard__action"><i class="booking-wizard__action-icon bi bi-buildings"></i><span><strong data-wizard-unit-summary>Choose a unit</strong><small data-wizard-unit-type>Unit type</small></span><button type="button" data-wizard-next="2" @disabled($units->isEmpty())>Continue <i class="bi bi-arrow-right"></i></button></div>
        </section>

        <section class="booking-wizard__panel" data-wizard-panel="2" hidden>
            <button class="booking-wizard__selected-unit" type="button" data-wizard-back="1">
                <img src="/images/app/company/unit-dubai.webp" alt="Selected holiday-home unit">
                <span><small>Selected unit</small><strong data-wizard-selected-unit>Choose a unit</strong></span>
                <i class="bi bi-pencil"></i>
            </button>
            <div class="booking-wizard__search-row">
                <label class="booking-wizard__search"><i class="bi bi-search"></i><input type="search" placeholder="Search maintenance services" data-service-search autocomplete="off"><button type="button" data-search-clear aria-label="Clear search"><i class="bi bi-x"></i></button></label>
                <details class="booking-wizard__filters">
                    <summary aria-label="Filter services"><i class="bi bi-sliders2"></i></summary>
                    <div class="booking-wizard__chips" data-service-filters>
                        <button type="button" class="active" data-service-filter="all">All</button>
                        <button type="button" data-service-filter="cooling">Cooling</button>
                        <button type="button" data-service-filter="water">Water</button>
                        <button type="button" data-service-filter="electrical">Electrical</button>
                        <button type="button" data-service-filter="appliances">Appliances</button>
                        <button type="button" data-service-filter="fixtures">Fixtures</button>
                    </div>
                </details>
            </div>
            <div class="booking-wizard__title booking-wizard__title--list"><h2>What needs attention?</h2><small>Inspection first</small></div>
            <div class="booking-wizard__service-list">
                @foreach($maintenanceChoices as $choice)
                    <label class="booking-wizard__service booking-wizard__service--issue" data-wizard-service data-service-category="{{ $choice['category'] }}"
                           data-service-image="/images/app/company/maintenance-hero.webp" data-service-description="{{ $choice['description'] }}"
                           data-service-search-text="{{ str($choice['label'].' '.$choice['description'])->lower() }}">
                        <input type="radio" name="maintenance_issue" value="{{ $choice['value'] }}" data-service-label="{{ $choice['value'] }}" data-service-icon="{{ $choice['icon'] }}" data-service-tone="{{ $choice['tone'] }}" @checked($selectedIssue === $choice['value'])>
                        <div class="booking-wizard__issue-art {{ $choice['tone'] }}"><i class="bi {{ $choice['icon'] }}"></i></div>
                        <span><strong>{{ $choice['label'] }}</strong><small>{{ $choice['description'] }}</small><em>Inspection first</em></span>
                        <i class="bi bi-check-lg"></i>
                    </label>
                @endforeach
            </div>
            <div class="booking-wizard__action"><i class="booking-wizard__action-icon bi bi-tools"></i><span><strong data-booking-summary-title>{{ $selectedIssue }}</strong><small>Inspection first</small></span><button type="button" data-wizard-next="3">Continue <i class="bi bi-arrow-right"></i></button></div>
        </section>

        <section class="booking-wizard__panel" data-wizard-panel="3" hidden>
            <input type="hidden" name="scheduled_at" value="{{ $nativeScheduled->format('Y-m-d\TH:i') }}" data-native-scheduled required>
            <div class="booking-wizard__review-card booking-wizard__review-card--unit">
                <img src="/images/app/company/unit-dubai.webp" alt="Selected holiday-home unit">
                <span><strong data-wizard-review-unit>Choose a unit</strong><em data-wizard-review-unit-type>Unit type</em></span>
                <button type="button" data-wizard-back="1" aria-label="Change unit"><i class="bi bi-chevron-right"></i></button>
            </div>
            <div class="booking-wizard__review-card booking-wizard__review-card--service">
                <div class="booking-wizard__review-icon blue" data-wizard-review-icon><i class="bi bi-snow"></i></div>
                <span><strong data-wizard-review-service>{{ $selectedIssue }}</strong><em data-wizard-review-description>Cooling, airflow or AC fault.</em></span>
                <button type="button" data-wizard-back="2" aria-label="Change issue"><i class="bi bi-chevron-right"></i></button>
            </div>

            <div class="booking-wizard__title booking-wizard__title--schedule"><h2>Select date</h2><small>{{ $nativeScheduled->format('F Y') }}</small></div>
            <div class="booking-wizard__date-strip">@for($day = 1; $day <= 7; $day++)@php($date = now()->addDays($day))<button type="button" class="native-date-option {{ $date->isSameDay($nativeScheduled) ? 'active' : '' }}" data-native-date="{{ $date->format('Y-m-d') }}"><small>{{ $date->format('D') }}</small><strong>{{ $date->format('d') }}</strong></button>@endfor</div>
            <h3>Select time</h3>
            <div class="booking-wizard__time-strip">@foreach(['08:30'=>'8:30 AM','10:00'=>'10:00 AM','12:00'=>'12:00 PM','14:00'=>'2:00 PM','16:00'=>'4:00 PM'] as $value => $label)<button type="button" class="native-time-option {{ $nativeScheduled->format('H:i') === $value ? 'active' : '' }}" data-native-time="{{ $value }}">{{ $label }}</button>@endforeach</div>

            <details class="native-booking-details"><summary><i class="bi bi-chat-left-text"></i><span><strong>Add notes or photos</strong><small>Show the issue to help the technician prepare</small></span><i class="bi bi-chevron-down"></i></summary><label>Notes<textarea name="notes" placeholder="What is wrong? Add access details if helpful.">{{ old('notes') }}</textarea></label><label class="native-photo-upload"><input type="file" name="booking_photos[]" accept="image/jpeg,image/png,image/webp" multiple data-photo-input data-preview-target="maintenance-booking-photo-preview"><i class="bi bi-camera"></i><span><strong>Add issue photos</strong><small>Camera or gallery · up to 8</small></span></label><div id="maintenance-booking-photo-preview" class="booking-photo-preview"></div></details>

            <div class="booking-wizard__rate-card">
                <i class="bi bi-shield-check"></i><span><small>Inspection fee</small><strong>AED {{ number_format($inspectionMinor / 100, 2) }}</strong></span>
                <span><i class="bi bi-arrow-repeat"></i><small>Credited in full when<br>the quotation is approved.</small></span>
            </div>
            <section class="booking-wizard__breakdown" aria-label="Maintenance payment breakdown">
                <div><strong>Payment breakdown</strong><small>Inspection first</small></div>
                <p><span>Inspection visit</span><b>AED {{ number_format($inspectionMinor / 100, 2) }}</b></p>
                <p><span>Credit after quotation approval</span><b>− AED {{ number_format($inspectionMinor / 100, 2) }}</b></p>
                <p class="total"><span>Due now</span><b>AED {{ number_format($inspectionMinor / 100, 2) }}</b></p>
            </section>
            <div class="booking-wizard__action booking-wizard__action--confirm"><button type="submit" @disabled($units->isEmpty() || !$rateCard || $rateCard->status !== 'approved' || $inspectionMinor <= 0)><i class="bi bi-lock"></i> Pay inspection fee</button></div>
        </section>
    </form>
</div>
