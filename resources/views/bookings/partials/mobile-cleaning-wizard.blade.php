@php
    $selectedUnitId = (string) old('unit_id', optional($units->first())->id);
    $selectedService = old('service_code');
    $unitBuildings = $units->pluck('building')->filter()->unique()->values();
@endphp
<div class="company-native-only booking-wizard" data-booking-wizard>
    <header class="booking-wizard__header">
        <a href="{{ route('dashboard') }}" aria-label="Go back" data-wizard-header-back><i class="bi bi-arrow-left"></i></a>
        <strong data-wizard-header-title>Book cleaning</strong>
        <span></span>
    </header>

    <div class="booking-wizard__progress-shell">
        <p><b data-wizard-step-count>1 of 3</b><span aria-hidden="true">·</span><strong data-wizard-step-copy>Choose unit</strong></p>
        <nav class="booking-wizard__progress" aria-label="Booking progress">
            @foreach([1 => 'Unit', 2 => 'Service', 3 => 'Schedule'] as $number => $label)
                <button type="button" class="{{ $number === 1 ? 'active' : '' }}" data-wizard-step="{{ $number }}" @disabled($number > 1)>
                    <b>{{ $number }}</b><span>{{ $label }}</span>
                </button>
                @if($number < 3)<i></i>@endif
            @endforeach
        </nav>
    </div>

    <form method="post" action="{{ route('bookings.store') }}" enctype="multipart/form-data"
          data-cleaning-booking data-rates-active="{{ $rateCard && $rateCard->status === 'approved' ? '1' : '0' }}"
          data-booking-rates='@json($bookingRates)' data-extra-rates='@json($extraRates)'>
        @csrf
        <input type="hidden" name="service_type" value="cleaning">
        <input type="hidden" name="service_name" value="{{ old('service_name') }}" data-service-name>
        <select class="native-hidden-select" name="unit_id" required aria-label="Selected property">
            @foreach($units as $unit)<option value="{{ $unit->id }}" @selected($selectedUnitId === (string) $unit->id)>{{ $unit->building }} · Unit {{ $unit->unit_number }}</option>@endforeach
        </select>

        @if($errors->any())
            <div class="booking-wizard__errors" role="alert"><i class="bi bi-exclamation-circle"></i><div><strong>Please check your booking</strong><span>{{ $errors->first() }}</span></div></div>
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
                <div class="booking-wizard__title booking-wizard__title--list"><h2>Choose a unit</h2><small>{{ $units->count() }} {{ $units->count() === 1 ? 'unit' : 'units' }}</small></div>
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
                <a class="booking-wizard__empty" href="{{ route('units.create') }}"><i class="bi bi-building-add"></i><strong>Add your first unit</strong><span>A unit is required before booking.</span></a>
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
                <label class="booking-wizard__search"><i class="bi bi-search"></i><input type="search" placeholder="Search cleaning services" data-service-search autocomplete="off"><button type="button" data-search-clear aria-label="Clear search"><i class="bi bi-x"></i></button></label>
                <details class="booking-wizard__filters">
                    <summary aria-label="Filter services"><i class="bi bi-sliders2"></i></summary>
                    <div class="booking-wizard__chips" data-service-filters>
                        <button type="button" class="active" data-service-filter="all">All</button>
                        <button type="button" data-service-filter="standard">Regular</button>
                        <button type="button" data-service-filter="deep">Deep</button>
                        <button type="button" data-service-filter="linen">Linen</button>
                    </div>
                </details>
            </div>
            <div class="booking-wizard__service-list" data-service-list>
                @foreach($cleaningTypes as $code => $label)
                    @php($meta = $cleaningMetadata[$code])
                    <label class="booking-wizard__service" data-rate-code="{{ $code }}" data-service-category="{{ $meta['category'] }}" data-service-image="/images/app/company/{{ $meta['image'] }}" data-service-description="{{ $meta['description'] }}" data-service-search-text="{{ str($label.' '.$meta['description'])->lower() }}">
                        <input type="radio" name="service_code" value="{{ $code }}" data-service-label="{{ $label }}" @checked($selectedService === $code)>
                        <img src="/images/app/company/{{ $meta['image'] }}" alt="{{ $label }}">
                        <span><strong>{{ $label }}</strong><small>{{ $meta['description'] }}</small><em data-rate-price>Checking rate…</em></span>
                        <i class="bi bi-check-lg"></i>
                    </label>
                @endforeach
                <div class="booking-wizard__service-shortcuts" aria-label="Linen and add-on services">
                    <button type="button" data-service-filter-shortcut="linen">
                        <img src="/images/app/company/service-linen.webp" alt="">
                        <span><strong>Linen service</strong><small>Fresh linen and towel options</small></span><i class="bi bi-chevron-right"></i>
                    </button>
                    <button type="button" data-open-addons>
                        <img src="/images/app/company/service-normal-materials.webp" alt="">
                        <span><strong>Add-ons</strong><small>Amenities, sofa and carpet care</small></span><i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
            <div class="no-contracted-service booking-wizard__empty" data-no-contracted-service hidden><i class="bi bi-receipt"></i><strong>No approved service for this unit type</strong><a href="{{ route('rates.show') }}">Review rates</a></div>

            @if($extraRates !== [])
                <details class="booking-wizard__addons">
                    <summary><span><i class="bi bi-plus-circle"></i><b>Linen & add-ons</b><small>Optional · approved contract prices</small></span><i class="bi bi-chevron-down"></i></summary>
                    <div class="booking-wizard__addon-groups">
                        @foreach($extraRates as $sectionCode => $section)
                            <details class="booking-wizard__addon-group"><summary><span><strong>{{ $section['title'] }}</strong><small>{{ $section['unit'] }}</small></span><i class="bi bi-chevron-down"></i></summary><div class="booking-wizard__addon-items">
                                @foreach($section['items'] as $itemCode => $item)
                                    @php($oldQuantity = (int) old("extras.{$sectionCode}.{$itemCode}", 0))
                                    <label class="booking-wizard__addon" data-addon-row>
                                        <span><b>{{ $item['label'] }}</b><small>AED {{ number_format($item['amount'] / 100, 2) }}</small></span>
                                        <span class="booking-wizard__qty"><button type="button" data-qty-minus aria-label="Remove one">−</button><input type="number" name="extras[{{ $sectionCode }}][{{ $itemCode }}]" value="{{ $oldQuantity }}" min="0" max="20" data-extra-input data-extra-section="{{ $sectionCode }}" data-extra-code="{{ $itemCode }}" data-extra-label="{{ $item['label'] }}" data-extra-price="{{ $item['amount'] }}" readonly><button type="button" data-qty-plus aria-label="Add one">+</button></span>
                                    </label>
                                @endforeach
                            </div></details>
                        @endforeach
                    </div>
                </details>
            @endif

            <div class="booking-wizard__action"><i class="booking-wizard__action-icon bi bi-stars"></i><span><strong data-booking-summary-title>Choose a service</strong><small>Agreed contract rate</small></span><button type="button" data-wizard-next="3" disabled>Continue <i class="bi bi-arrow-right"></i></button></div>
        </section>

        <section class="booking-wizard__panel" data-wizard-panel="3" hidden>
            <input type="hidden" name="scheduled_at" value="{{ $nativeScheduled->format('Y-m-d\TH:i') }}" data-native-scheduled required>
            <div class="booking-wizard__review-card booking-wizard__review-card--unit">
                <img src="/images/app/company/unit-dubai.webp" alt="Selected holiday-home unit">
                <span><strong data-wizard-review-unit>Choose a unit</strong><em data-wizard-review-unit-type>Unit type</em></span>
                <button type="button" data-wizard-back="1" aria-label="Change unit"><i class="bi bi-chevron-right"></i></button>
            </div>
            <div class="booking-wizard__review-card booking-wizard__review-card--service">
                <img src="/images/app/company/service-touch-up.webp" alt="Selected cleaning service" data-wizard-review-image>
                <span><strong data-wizard-review-service>Choose a service</strong><em data-wizard-review-description>Selected cleaning service</em></span>
                <button type="button" data-wizard-back="2" aria-label="Change service"><i class="bi bi-chevron-right"></i></button>
            </div>

            <div class="booking-wizard__title booking-wizard__title--schedule"><h2>Select date</h2><small>{{ $nativeScheduled->format('F Y') }}</small></div>
            <div class="booking-wizard__date-strip">@for($day = 1; $day <= 7; $day++)@php($date = now()->addDays($day))<button type="button" class="native-date-option {{ $date->isSameDay($nativeScheduled) ? 'active' : '' }}" data-native-date="{{ $date->format('Y-m-d') }}"><small>{{ $date->format('D') }}</small><strong>{{ $date->format('d') }}</strong></button>@endfor</div>
            <h3>Select time</h3>
            <div class="booking-wizard__time-strip">@foreach(['08:30'=>'8:30 AM','10:00'=>'10:00 AM','12:00'=>'12:00 PM','14:00'=>'2:00 PM','16:00'=>'4:00 PM'] as $value => $label)<button type="button" class="native-time-option {{ $nativeScheduled->format('H:i') === $value ? 'active' : '' }}" data-native-time="{{ $value }}">{{ $label }}</button>@endforeach</div>

            <details class="native-booking-details"><summary><i class="bi bi-chat-left-text"></i><span><strong>Add notes or photos</strong><small>Optional access details for the cleaning team</small></span><i class="bi bi-chevron-down"></i></summary><label>Notes<textarea name="notes" placeholder="Access instructions or helpful details…">{{ old('notes') }}</textarea></label><label class="native-photo-upload"><input type="file" name="booking_photos[]" accept="image/jpeg,image/png,image/webp" multiple data-photo-input data-preview-target="native-booking-photo-preview"><i class="bi bi-camera"></i><span><strong>Add photos</strong><small>Camera or gallery · up to 8</small></span></label><div id="native-booking-photo-preview" class="booking-photo-preview"></div></details>

            <div class="booking-wizard__rate-card">
                <i class="bi bi-tag"></i><span><small>Agreed rate</small><strong data-wizard-review-total>—</strong></span>
                <span><i class="bi bi-calendar2-check"></i><small>Free cancellation up to<br>2 hours before booking.</small></span>
            </div>
            <section class="booking-wizard__breakdown" aria-label="Booking price breakdown">
                <div><strong>Price breakdown</strong><small>Approved company rates</small></div>
                <p><span>Cleaning service</span><b data-wizard-review-base>—</b></p>
                <p><span>Linen &amp; add-ons</span><b data-wizard-review-extra-total>AED 0.00</b></p>
                <p class="total"><span>Total</span><b data-wizard-review-total>—</b></p>
            </section>
            <div data-wizard-review-addons hidden class="booking-wizard__addon-summary"><i class="bi bi-plus-square"></i><span><small>Add-ons</small><strong data-wizard-review-addon-copy>—</strong></span></div>

            <div class="booking-wizard__action booking-wizard__action--confirm"><button type="submit" @disabled(!$rateCard || $rateCard->status !== 'approved')><i class="bi bi-lock"></i> Pay now</button></div>
        </section>
    </form>
</div>
