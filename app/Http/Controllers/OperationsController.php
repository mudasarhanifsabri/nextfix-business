<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Company;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = CarbonImmutable::createFromFormat('Y-m', (string) $request->input('month', now()->format('Y-m')))
            ->startOfMonth();

        $query = Booking::with(['company', 'unit'])
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')))
            ->when($request->filled('service'), fn ($builder) => $builder->where('service_type', $request->string('service')))
            ->when($request->filled('company'), fn ($builder) => $builder->where('company_id', $request->integer('company')));

        $calendarBookings = (clone $query)
            ->whereBetween('scheduled_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->orderBy('scheduled_at')->get()->groupBy(fn ($booking) => $booking->scheduled_at->format('Y-m-d'));

        $calendarStart = $month->startOfWeek(CarbonImmutable::MONDAY);
        $calendarEnd = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        $days = collect();
        for ($day = $calendarStart; $day->lte($calendarEnd); $day = $day->addDay()) {
            $days->push($day);
        }

        return view('admin.operations', [
            'bookings' => (clone $query)->latest('scheduled_at')->paginate(10)->withQueryString(),
            'calendarBookings' => $calendarBookings,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'month' => $month,
            'days' => $days,
            'todayCount' => Booking::whereDate('scheduled_at', today())->count(),
            'attentionCount' => Booking::whereIn('status', ['pending', 'awaiting_payment'])->count(),
            'activeCount' => Booking::whereIn('status', ['confirmed', 'assigned', 'on_the_way', 'in_progress'])->count(),
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'nextfix_admin', 403);
    }
}
