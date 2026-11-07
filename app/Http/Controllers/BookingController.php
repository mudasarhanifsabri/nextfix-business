<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;
        return view('bookings.index', [
            'bookings' => Booking::where('company_id', $companyId)->with('unit')->latest('scheduled_at')->paginate(15),
            'quotations' => Booking::where('company_id', $companyId)->with('unit')->where('workflow_stage', 'awaiting_customer_approval')->oldest('quotation_sent_at')->get(),
        ]);
    }

    public function create(Request $request, string $service): View
    {
        return view('bookings.create', ['service' => $service, 'units' => Unit::where('company_id', $request->user()->company_id)->where('is_active', true)->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'unit_id' => ['required', 'integer'], 'service_type' => ['required', 'in:cleaning,maintenance'],
            'service_name' => ['required', 'string', 'max:120'], 'scheduled_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $unit = Unit::where('company_id', $request->user()->company_id)->findOrFail($data['unit_id']);
        Booking::create($data + [
            'company_id' => $request->user()->company_id, 'unit_id' => $unit->id,
            'reference' => 'NFB-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'status' => 'pending', 'payment_status' => 'manual', 'amount_minor' => 0, 'currency' => 'AED',
        ]);

        return redirect()->route('bookings.index')->with('status', 'Booking requested. We will confirm the schedule and price with you.');
    }

    public function quotationDecision(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->company_id === $request->user()->company_id, 403);
        abort_unless($booking->workflow_stage === 'awaiting_customer_approval', 422, 'This quotation is not awaiting your decision.');
        $data = $request->validate(['decision' => ['required', 'in:approve,changes'], 'decision_note' => ['nullable', 'string', 'max:1000']]);

        if ($data['decision'] === 'approve') {
            $booking->update(['workflow_stage' => 'work', 'status' => 'assigned', 'estimate_approved_at' => now(), 'customer_decision_at' => now(), 'customer_decision_note' => $data['decision_note'] ?? null]);
            return back()->with('status', 'Quotation approved. The assigned professional can begin work.');
        }

        $booking->update(['workflow_stage' => 'estimate', 'status' => 'changes_requested', 'customer_decision_at' => now(), 'customer_decision_note' => $data['decision_note'] ?? 'Please revise the quotation.']);
        return back()->with('status', 'Changes requested. NextFix will revise the quotation.');
    }
}
