<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Unit;
use App\Services\NomodPaymentLinks;
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
            'paymentsDue' => Booking::where('company_id', $companyId)->with('unit')->whereIn('status', ['awaiting_inspection_payment', 'awaiting_quote_payment'])->latest()->get(),
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
        $company = $request->user()->company;
        $rateKey = match ($data['service_name']) { 'Deep cleaning' => 'deep_cleaning_rate_minor', 'Regular cleaning' => 'regular_cleaning_rate_minor', default => 'checkout_cleaning_rate_minor' };
        $amount = $data['service_type'] === 'maintenance' ? $company->inspection_rate_minor : $company->{$rateKey};
        $booking = Booking::create($data + [
            'company_id' => $request->user()->company_id, 'unit_id' => $unit->id,
            'reference' => 'NFB-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'status' => $data['service_type'] === 'maintenance' ? 'awaiting_inspection_payment' : 'pending', 'payment_status' => 'pending', 'amount_minor' => $amount, 'inspection_fee_minor' => $data['service_type'] === 'maintenance' ? $amount : 0, 'currency' => 'AED',
        ]);
        if ($data['service_type'] === 'maintenance') {
            $successUrl = route('bookings.index', ['nf_booking' => $booking->id, 'nf_payment' => 'inspection']);
            $url = rescue(fn () => app(NomodPaymentLinks::class)->create('Inspection '.$booking->reference, [['name'=>'Maintenance inspection fee','amount'=>number_format($amount/100,2,'.',''),'quantity'=>1]], 'NextFix booking '.$booking->reference.' inspection payment.', $successUrl, route('bookings.index')), null, false);
            $booking->update(['inspection_payment_url' => $url]);
        }

        return redirect()->route('bookings.index')->with('status', 'Booking requested. We will confirm the schedule and price with you.');
    }

    public function quotationDecision(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->company_id === $request->user()->company_id, 403);
        abort_unless($booking->workflow_stage === 'awaiting_customer_approval', 422, 'This quotation is not awaiting your decision.');
        $data = $request->validate(['decision' => ['required', 'in:approve,changes'], 'decision_note' => ['nullable', 'string', 'max:1000']]);

        if ($data['decision'] === 'approve') {
            $booking->update(['workflow_stage' => 'work', 'status' => 'assigned', 'estimate_approved_at' => now(), 'customer_decision_at' => now(), 'customer_decision_note' => $data['decision_note'] ?? null]);
            $credit = $booking->inspection_fee_paid_at ? $booking->inspection_fee_minor : 0;
            $subtotal = max(0, $booking->labour_amount_minor + $booking->parts_amount_minor - $credit);
            $tax = (int) round($subtotal * .05);
            $invoice = Invoice::create(['company_id'=>$booking->company_id,'number'=>'INV-Q-'.now()->format('ymd').'-'.$booking->id,'public_token'=>hash('sha256',$booking->reference.now()),'issued_at'=>today(),'due_at'=>today()->addDays($booking->company->payment_terms ?: 7),'status'=>$subtotal + $tax > 0 ? 'sent' : 'paid','line_items'=>[['description'=>'Labour ('.$booking->labour_hours.' hours)','quantity'=>1,'unit_price_minor'=>$booking->labour_amount_minor],['description'=>'Parts: '.($booking->parts_required ?: 'As quoted'),'quantity'=>1,'unit_price_minor'=>$booking->parts_amount_minor],['description'=>'Inspection fee credit','quantity'=>1,'unit_price_minor'=>-$credit]],'subtotal_minor'=>$subtotal,'tax_minor'=>$tax,'amount_minor'=>$subtotal+$tax,'currency'=>'AED','notes'=>'Approved maintenance quotation for '.$booking->reference]);
            $paid = ($subtotal + $tax) === 0;
            $successUrl = route('bookings.index', ['nf_booking' => $booking->id, 'nf_payment' => 'quotation']);
            $paymentUrl = $paid ? null : rescue(fn () => app(NomodPaymentLinks::class)->create('Quotation '.$booking->reference, [['name'=>'Approved maintenance work','amount'=>number_format(($subtotal+$tax)/100,2,'.',''),'quantity'=>1]], 'NextFix booking '.$booking->reference.' quotation payment. Inspection credit AED '.number_format($credit/100,2).' applied.', $successUrl, route('bookings.index')), null, false);
            $booking->update(['workflow_stage'=>$paid ? 'work' : 'awaiting_quote_payment','status'=>$paid ? 'assigned' : 'awaiting_quote_payment','estimate_approved_at'=>now(),'customer_decision_at'=>now(),'customer_decision_note'=>$data['decision_note'] ?? null,'quotation_invoice_id'=>$invoice->id,'quotation_payment_url'=>$paymentUrl]);
            return back()->with('status', $paid ? 'Quotation approved. Work is authorized.' : 'Quotation approved. Pay the remaining balance to authorize work.');
        }

        $request->validate(['decision_note' => ['required', 'string', 'max:1000']]);
        $reason = $data['decision_note'] ?? 'Please revise the quotation.';
        $booking->update(['workflow_stage' => 'estimate', 'status' => 'changes_requested', 'customer_decision_at' => now(), 'customer_decision_note' => $reason]);
        return back()->with('status', 'Changes requested. NextFix will revise the quotation.');
    }
}
