<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\NomodPaymentReconciler;
use App\Services\OperationalNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

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

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $this->admin($request);
        abort_unless(in_array($payment->status, ['completed', 'paid', 'partially_refunded'], true), 422, 'Only a settled payment can be refunded.');
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.($payment->amount_minor / 100)],
            'reason' => ['required', 'string', 'max:500'],
            'password_confirmation_admin' => ['required', 'string'],
        ]);
        abort_unless(Hash::check($data['password_confirmation_admin'], $request->user()->password), 422, 'Administrator password is incorrect.');
        $minor = (int) round(((float) $data['amount']) * 100);
        $manual = $payment->method !== 'nomod';
        $payment->update([
            'refund_amount_minor' => $minor,
            'refund_reason' => $data['reason'],
            'refund_requested_at' => now(),
            'refunded_at' => $manual ? now() : null,
            'status' => $manual ? ($minor >= $payment->amount_minor ? 'refunded' : 'partially_refunded') : 'refund_pending',
            'notes' => trim(($payment->notes ? $payment->notes."\n" : '').'Refund requested by '.$request->user()->name.' · '.$data['reason']),
        ]);
        if ($manual && $payment->invoice) $payment->invoice->update(['status' => $minor >= $payment->amount_minor ? 'refunded' : 'partially_refunded']);
        app(OperationalNotifier::class)->refundRequested(
            $payment->fresh(['company.users', 'invoice']),
            $minor,
            $data['reason'],
            $manual
        );

        return back()->with('status', $manual
            ? 'Manual refund recorded.'
            : 'Refund request recorded. Process it in Nomod; the webhook will confirm the final status automatically.');
    }
}
