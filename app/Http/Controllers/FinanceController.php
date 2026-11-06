<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
}
