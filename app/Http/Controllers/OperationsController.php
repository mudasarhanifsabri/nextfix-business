<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Professional;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $request->validate(['month' => ['nullable', 'date_format:Y-m'], 'date' => ['nullable', 'date_format:Y-m-d']]);
        $month = CarbonImmutable::createFromFormat('Y-m', (string) $request->input('month', now()->format('Y-m')))
            ->startOfMonth();

        $query = Booking::with(['company', 'unit', 'professional'])
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')))
            ->when($request->filled('service'), fn ($builder) => $builder->where('service_type', $request->string('service')))
            ->when($request->filled('company'), fn ($builder) => $builder->where('company_id', $request->integer('company')));

        $calendarBookings = (clone $query)
            ->whereBetween('scheduled_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->orderBy('scheduled_at')->get()->groupBy(fn ($booking) => $booking->scheduled_at->format('Y-m-d'));

        $calendarStart = $month->startOfWeek(CarbonImmutable::MONDAY);
        $calendarEnd = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        $selectedDate = $request->filled('date') ? CarbonImmutable::createFromFormat('Y-m-d', (string) $request->input('date')) : null;
        $days = collect();
        for ($day = $calendarStart; $day->lte($calendarEnd); $day = $day->addDay()) {
            $days->push($day);
        }

        return view('admin.operations', [
            'bookings' => (clone $query)->latest('scheduled_at')->paginate(10)->withQueryString(),
            'calendarBookings' => $calendarBookings,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'professionals' => Professional::where('status', '!=', 'off_duty')->orderBy('name')->get(['id', 'name', 'role', 'status']),
            'month' => $month,
            'days' => $days,
            'selectedDate' => $selectedDate,
            'selectedDayBookings' => $selectedDate ? (clone $query)->whereDate('scheduled_at', $selectedDate)->orderBy('scheduled_at')->get() : collect(),
            'approvalBookings' => Booking::with(['company', 'unit', 'professional'])->where('workflow_stage', 'awaiting_approval')->oldest('estimate_submitted_at')->get(),
            'todayCount' => Booking::whereDate('scheduled_at', today())->count(),
            'attentionCount' => Booking::whereIn('status', ['pending', 'awaiting_payment'])->count(),
            'activeCount' => Booking::whereIn('status', ['confirmed', 'assigned', 'on_the_way', 'in_progress'])->count(),
        ]);
    }

    public function assign(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['professional_id' => ['nullable', 'integer', 'exists:professionals,id']]);
        $booking->update(['professional_id' => $data['professional_id'] ?? null]);

        return back()->with('status', $booking->professional_id ? 'Professional assigned to '.$booking->reference.'.' : 'Assignment removed.');
    }

    public function approve(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($booking->workflow_stage === 'awaiting_approval', 422, 'This estimate is not waiting for approval.');
        $booking->update(['workflow_stage' => 'work', 'status' => 'assigned', 'estimate_approved_at' => now()]);
        return back()->with('status', 'Estimate approved. The professional can begin work.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'nextfix_admin', 403);
    }
}
