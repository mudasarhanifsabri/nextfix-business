<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->role === 'professional') {
            return redirect()->route('worker.home');
        }
        if ($user->role === 'nextfix_admin') {
            return view('admin.dashboard', [
                'companyCount' => Company::count(),
                'todayCount' => Booking::whereDate('scheduled_at', today())->count(),
                'pendingCount' => Booking::where('payment_status', 'pending')->count(),
                'revenue' => Payment::where('status', 'completed')->sum('amount_minor'),
                'outstanding' => Invoice::whereIn('status', ['sent', 'overdue'])->sum('amount_minor'),
                'overdueCount' => Invoice::where('status', 'overdue')->count(),
                'bookings' => Booking::with(['company', 'unit'])->latest('scheduled_at')->limit(5)->get(),
            ]);
        }

        $bookings = Booking::where('company_id', $user->company_id)->with('unit')->latest('scheduled_at')->get();

        return view('dashboard', [
            'upcoming' => $bookings->where('scheduled_at', '>=', now())->take(4),
            'unitCount' => $user->company ? $user->company->units()->where('is_active', true)->count() : 0,
            'pendingCount' => $bookings->where('payment_status', 'pending')->count(),
            'activeCount' => $bookings->whereIn('status', ['confirmed', 'assigned', 'on_the_way', 'in_progress'])->count(),
            'quotationCount' => $bookings->where('workflow_stage', 'awaiting_customer_approval')->count(),
        ]);
    }
}
