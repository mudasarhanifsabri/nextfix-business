<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\WorkChecklist;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobReportController extends Controller
{
    public function show(Request $request, Booking $booking): View
    {
        $this->authorizeReport($request, $booking);
        $this->loadReport($booking);

        return view('job-reports.show', [
            'booking' => $booking,
            'checklist' => WorkChecklist::for($booking),
        ]);
    }

    public function pdf(Request $request, Booking $booking)
    {
        $this->authorizeReport($request, $booking);
        $this->loadReport($booking);

        return Pdf::loadView('job-reports.pdf', [
            'booking' => $booking,
            'checklist' => WorkChecklist::for($booking),
        ])->download('Job-Report-'.$booking->reference.'.pdf');
    }

    private function authorizeReport(Request $request, Booking $booking): void
    {
        $user = $request->user();
        $allowed = $user->role === 'nextfix_admin'
            || ($user->role === 'company_manager' && $user->company_id === $booking->company_id)
            || ($user->role === 'professional' && $user->professional?->id === $booking->professional_id);
        abort_unless($allowed, 403);
    }

    private function loadReport(Booking $booking): void
    {
        $booking->load(['company', 'unit', 'professional', 'quotationInvoice.payments', 'photos']);
    }
}
