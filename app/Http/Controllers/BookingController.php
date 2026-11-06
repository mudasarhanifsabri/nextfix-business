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
        return view('bookings.index', ['bookings' => Booking::where('company_id', $request->user()->company_id)->with('unit')->latest('scheduled_at')->paginate(15)]);
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
}
