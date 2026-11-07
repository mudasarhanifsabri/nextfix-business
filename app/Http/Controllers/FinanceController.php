<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\NomodPaymentReconciler;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class FinanceController extends Controller
{
    private function admin(Request $request): void
    {
        abort_unless($request->user()->role === 'nextfix_admin', 403);
    }

    public function invoices(Request $request): View
    {
        $this->admin($request);

        return view('admin.finance.invoices', ['invoices' => Invoice::with('company')->withSum('payments', 'amount_minor')->latest('issued_at')->paginate(20)]);
    }

    public function invoice(Request $request, Invoice $invoice): View
    {
        $this->admin($request);

        return view('admin.finance.invoice', ['invoice' => $invoice->load(['company', 'payments'])]);
    }

    public function pdf(Request $request, Invoice $invoice)
    {
        $this->admin($request);

        return Pdf::loadView('admin.finance.pdf', ['invoice' => $invoice->load(['company', 'payments'])])->download($invoice->number.'.pdf');
    }

    public function payments(Request $request): View
    {
        $this->admin($request);

        return view('admin.finance.payments', ['payments' => Payment::with(['company', 'invoice'])->latest('paid_at')->paginate(20)]);
    }

    public function reconcile(Request $request, NomodPaymentReconciler $reconciler): RedirectResponse
    {
        $this->admin($request);
        try {
            $result = $reconciler->reconcile();
            $message = $result['matched']
                ? $result['matched'].' paid Nomod payment(s) reconciled successfully.'
                : 'Nomod checked '.$result['checked'].' recent charge(s); no unmatched paid payment was found.';
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['nomod' => 'Nomod sync failed. Check the API key and server log.']);
        }
        return back()->with('status', $message);
    }
}
