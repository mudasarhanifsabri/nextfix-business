@extends('layouts.app', ['heading' => 'Operations'])
@section('content')
<section class="operations-hero"><div><p class="eyebrow">LIVE SERVICE CONTROL</p><h2>Dispatch with clarity</h2><p>Plan cleaning and maintenance work across every company from one calendar.</p></div><div class="operations-kpis"><span><b>{{ $todayCount }}</b>Today</span><span><b>{{ $activeCount }}</b>Active</span><span><b>{{ $attentionCount }}</b>Attention</span></div></section>

<form class="operations-filters" method="get">
    <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
    <label>Company<select name="company"><option value="">All companies</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) request('company') === (string) $company->id)>{{ $company->name }}</option>@endforeach</select></label>
    <label>Service<select name="service"><option value="">All services</option><option value="cleaning" @selected(request('service') === 'cleaning')>Cleaning</option><option value="maintenance" @selected(request('service') === 'maintenance')>Maintenance</option></select></label>
    <label>Status<select name="status"><option value="">All statuses</option>@foreach(['pending','confirmed','assigned','in_progress','completed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select></label>
    <button class="primary">Apply filters</button><a class="secondary" href="{{ route('admin.operations.index') }}">Reset</a>
</form>

<section class="panel operations-calendar">
    <div class="calendar-toolbar"><a href="{{ route('admin.operations.index', array_merge(request()->except('month', 'page'), ['month' => $month->subMonth()->format('Y-m')])) }}" aria-label="Previous month"><i class="bi bi-chevron-left"></i></a><div><p class="eyebrow">DISPATCH CALENDAR</p><h3>{{ $month->format('F Y') }}</h3></div><a href="{{ route('admin.operations.index', array_merge(request()->except('month', 'page'), ['month' => $month->addMonth()->format('Y-m')])) }}" aria-label="Next month"><i class="bi bi-chevron-right"></i></a></div>
    <div class="calendar-weekdays">@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $weekday)<span>{{ $weekday }}</span>@endforeach</div>
    <div class="calendar-grid">
        @foreach($days as $day)
        @php($dayBookings = $calendarBookings->get($day->format('Y-m-d'), collect()))
        <article class="calendar-day {{ $day->month !== $month->month ? 'outside' : '' }} {{ $day->isToday() ? 'today' : '' }}">
            <header><span>{{ $day->day }}</span>@if($dayBookings->count())<b>{{ $dayBookings->count() }}</b>@endif</header>
            <div>@foreach($dayBookings->take(3) as $booking)<span class="calendar-event {{ $booking->service_type }}" title="{{ $booking->company->name }} · {{ $booking->service_name }}"><i></i>{{ $booking->scheduled_at->format('g:i') }} {{ Str::limit($booking->company->name, 14) }}</span>@endforeach @if($dayBookings->count() > 3)<small>+{{ $dayBookings->count() - 3 }} more</small>@endif</div>
        </article>
        @endforeach
    </div>
</section>

<section class="panel operations-queue"><div class="panel-head"><div><p class="eyebrow">MANAGE BOOKINGS</p><h3>Service queue</h3></div><span>{{ $bookings->total() }} matching jobs</span></div><div class="table-wrap"><table><thead><tr><th>Reference</th><th>Company / unit</th><th>Service</th><th>Schedule</th><th>Payment</th><th>Status</th></tr></thead><tbody>@forelse($bookings as $booking)<tr><td><strong>{{ $booking->reference }}</strong></td><td>{{ $booking->company->name }}<small>{{ $booking->unit->building }} · {{ $booking->unit->unit_number }}</small></td><td>{{ $booking->service_name }}</td><td>{{ $booking->scheduled_at->format('d M Y') }}<small>{{ $booking->scheduled_at->format('g:i A') }}</small></td><td><span class="status {{ $booking->payment_status }}">{{ $booking->payment_status }}</span></td><td><span class="status {{ $booking->status }}">{{ str_replace('_', ' ', $booking->status) }}</span></td></tr>@empty<tr><td colspan="6"><div class="empty"><h4>No jobs match these filters</h4><p>Adjust the filters or choose another month.</p></div></td></tr>@endforelse</tbody></table></div>@if($bookings->hasPages())<div class="pager">{{ $bookings->links() }}</div>@endif</section>
@endsection
