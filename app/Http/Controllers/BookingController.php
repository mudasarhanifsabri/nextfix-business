<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\JobPhoto;
use App\Models\Unit;
use App\Services\NomodPaymentLinks;
use App\Services\OperationalNotifier;
use App\Support\RateCatalogue;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        return view('bookings.index', [
            'bookings' => Booking::where('company_id', $companyId)->with('unit')->latest('scheduled_at')->paginate(15),
            'quotations' => Booking::where('company_id', $companyId)->with(['unit', 'inspectionPhotos'])->where('workflow_stage', 'awaiting_customer_approval')->oldest('quotation_sent_at')->get(),
            'paymentsDue' => Booking::where('company_id', $companyId)->with(['unit', 'quotationInvoice'])->whereIn('status', ['awaiting_inspection_payment', 'awaiting_quote_payment'])->latest()->get(),
        ]);
    }

    public function create(Request $request, string $service): View
    {
        $units = Unit::where('company_id', $request->user()->company_id)
            ->where('is_active', true)
            ->orderBy('building')
            ->orderBy('unit_number')
            ->get();
        $rateCard = $request->user()->company->rateCard;
        $bookingRates = $units->mapWithKeys(function (Unit $unit) use ($rateCard): array {
            $services = [];
            foreach (RateCatalogue::cleaningTypes() as $code => $label) {
                $services[$code] = $rateCard?->amount("cleaning.{$unit->rate_unit_type}.{$code}");
            }

            return [(string) $unit->id => [
                'unit_type' => $unit->rate_unit_type,
                'unit_label' => $unit->rate_unit_label,
                'services' => $services,
            ]];
        })->all();

        $extraRates = [];
        foreach (RateCatalogue::cleaningExtraSections() as $sectionCode => $section) {
            $items = [];
            foreach ($section['items'] as $itemCode => $label) {
                $amount = $rateCard?->amount("extras.{$sectionCode}.{$itemCode}");
                if ($amount !== null) {
                    $items[$itemCode] = ['label' => $label, 'amount' => $amount];
                }
            }
            if ($items !== []) {
                $extraRates[$sectionCode] = [
                    'title' => $section['title'],
                    'unit' => $section['unit'],
                    'items' => $items,
                ];
            }
        }

        return view('bookings.create', [
            'service' => $service,
            'units' => $units,
            'rateCard' => $rateCard,
            'bookingRates' => $bookingRates,
            'cleaningTypes' => RateCatalogue::cleaningTypes(),
            'cleaningMetadata' => RateCatalogue::cleaningMetadata(),
            'extraRates' => $extraRates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'unit_id' => ['required', 'integer'], 'service_type' => ['required', 'in:cleaning,maintenance'],
            'service_name' => ['required', 'string', 'max:120'], 'scheduled_at' => ['required', 'date', 'after:now'],
            'service_code' => ['nullable', 'string', 'max:60'],
            'extras' => ['nullable', 'array'],
            'extras.*' => ['array'],
            'extras.*.*' => ['integer', 'min:0', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'booking_photos' => ['nullable', 'array', 'max:8'],
            'booking_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);
        $unit = Unit::where('company_id', $request->user()->company_id)->findOrFail($data['unit_id']);
        $company = $request->user()->company;
        $rateCard = $company->rateCard;
        if (! $rateCard || $rateCard->status !== 'approved') {
            throw ValidationException::withMessages(['service_name' => 'Your company rate card must be approved before this service can be booked.']);
        }
        $bookingItems = [];
        $serviceCode = null;

        if ($data['service_type'] === 'cleaning') {
            $serviceCode = $data['service_code'] ?? array_search($data['service_name'], RateCatalogue::cleaningTypes(), true);
            if (! is_string($serviceCode) || ! isset(RateCatalogue::cleaningTypes()[$serviceCode])) {
                $legacyCode = RateCatalogue::bookingCode($data['service_name'], $unit->rate_unit_type);
                $serviceCode = $legacyCode ? str($legacyCode)->afterLast('.')->toString() : null;
            }
            if (! $serviceCode || ! isset(RateCatalogue::cleaningTypes()[$serviceCode])) {
                throw ValidationException::withMessages(['service_name' => 'Select one of the approved cleaning services.']);
            }

            $data['service_name'] = RateCatalogue::cleaningTypes()[$serviceCode];
            $cleaningCode = "cleaning.{$unit->rate_unit_type}.{$serviceCode}";
            $amount = $rateCard->amount($cleaningCode);
            if ($amount === null) {
                throw ValidationException::withMessages(['service_name' => 'This service does not have an approved rate for the selected unit. Ask NextFix to update your rate card.']);
            }
            $bookingItems[] = [
                'kind' => 'service',
                'code' => $cleaningCode,
                'label' => $data['service_name'],
                'quantity' => 1,
                'unit_price_minor' => $amount,
                'total_minor' => $amount,
            ];

            $allowedExtras = RateCatalogue::cleaningExtraSections();
            foreach ($data['extras'] ?? [] as $sectionCode => $items) {
                if (! isset($allowedExtras[$sectionCode]) || ! is_array($items)) {
                    throw ValidationException::withMessages(['extras' => 'One of the selected add-ons is not available.']);
                }
                foreach ($items as $itemCode => $quantity) {
                    $quantity = (int) $quantity;
                    if ($quantity === 0) {
                        continue;
                    }
                    if (! isset($allowedExtras[$sectionCode]['items'][$itemCode])) {
                        throw ValidationException::withMessages(["extras.{$sectionCode}.{$itemCode}" => 'This add-on is not available.']);
                    }
                    $extraCode = "extras.{$sectionCode}.{$itemCode}";
                    $unitPrice = $rateCard->amount($extraCode);
                    if ($unitPrice === null) {
                        throw ValidationException::withMessages(["extras.{$sectionCode}.{$itemCode}" => 'This add-on is not included in your approved rate card.']);
                    }
                    $lineTotal = $unitPrice * $quantity;
                    $amount += $lineTotal;
                    $bookingItems[] = [
                        'kind' => 'extra',
                        'section' => $sectionCode,
                        'code' => $extraCode,
                        'label' => $allowedExtras[$sectionCode]['items'][$itemCode],
                        'quantity' => $quantity,
                        'unit_price_minor' => $unitPrice,
                        'total_minor' => $lineTotal,
                    ];
                }
            }
        } else {
            $amount = $rateCard->maintenance_inspection_minor;
            $bookingItems[] = [
                'kind' => 'service',
                'code' => 'maintenance.inspection',
                'label' => 'Maintenance inspection fee',
                'quantity' => 1,
                'unit_price_minor' => $amount,
                'total_minor' => $amount,
            ];
        }

        if ($amount === null) {
            throw ValidationException::withMessages(['service_name' => 'This service does not have an approved rate for the selected unit. Ask NextFix to update your rate card.']);
        }
        $booking = Booking::create([
            'company_id' => $request->user()->company_id, 'unit_id' => $unit->id,
            'reference' => 'NFB-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'service_type' => $data['service_type'], 'service_name' => $data['service_name'],
            'service_code' => $serviceCode, 'booking_items' => $bookingItems,
            'scheduled_at' => $data['scheduled_at'], 'notes' => $data['notes'] ?? null,
            'workflow_stage' => $data['service_type'] === 'maintenance' ? 'inspection' : 'work',
            'status' => $data['service_type'] === 'maintenance' ? 'awaiting_inspection_payment' : 'pending', 'payment_status' => 'pending', 'amount_minor' => $amount, 'inspection_fee_minor' => $data['service_type'] === 'maintenance' ? $amount : 0, 'currency' => 'AED',
        ]);
        foreach ($request->file('booking_photos', []) as $photo) {
            $path = $photo->store('job-evidence/'.$booking->id, 'public');
            JobPhoto::create([
                'booking_id' => $booking->id,
                'uploaded_by_user_id' => $request->user()->id,
                'path' => $path,
                'original_name' => $photo->getClientOriginalName(),
                'mime_type' => $photo->getMimeType(),
                'size' => $photo->getSize(),
                'category' => 'booking_issue',
                'captured_at' => now(),
            ]);
        }
        $paymentUrl = null;
        if ($data['service_type'] === 'maintenance') {
            $successUrl = route('bookings.index', ['nf_booking' => $booking->id, 'nf_payment' => 'inspection']);
            $paymentUrl = rescue(fn () => app(NomodPaymentLinks::class)->create('Inspection '.$booking->reference, [['name' => 'Maintenance inspection fee', 'amount' => number_format($amount / 100, 2, '.', ''), 'quantity' => 1]], 'NextFix booking '.$booking->reference.' inspection payment.', $successUrl, route('bookings.index')), null, false);
            $booking->update(['inspection_payment_url' => $paymentUrl]);
        } else {
            $invoice = Invoice::create([
                'company_id' => $booking->company_id,
                'number' => 'INV-C-'.now()->format('ymd').'-'.$booking->id,
                'public_token' => hash('sha256', $booking->reference.now().Str::random()),
                'issued_at' => today(),
                'due_at' => today(),
                'status' => 'sent',
                'line_items' => collect($bookingItems)->map(fn (array $item): array => [
                    'description' => $item['label'],
                    'quantity' => $item['quantity'],
                    'unit_price_minor' => $item['unit_price_minor'],
                ])->values()->all(),
                'subtotal_minor' => $amount,
                'tax_minor' => 0,
                'amount_minor' => $amount,
                'currency' => 'AED',
                'notes' => 'Cleaning booking '.$booking->reference,
            ]);
            $successUrl = route('bookings.index', ['nf_booking' => $booking->id, 'nf_payment' => 'cleaning']);
            $paymentUrl = rescue(fn () => app(NomodPaymentLinks::class)->create('Cleaning '.$booking->reference, [['name' => $data['service_name'], 'amount' => number_format($amount / 100, 2, '.', ''), 'quantity' => 1]], 'NextFix cleaning booking '.$booking->reference.'.', $successUrl, route('bookings.index')), null, false);
            $booking->update([
                'workflow_stage' => 'awaiting_quote_payment',
                'status' => 'awaiting_quote_payment',
                'quotation_invoice_id' => $invoice->id,
                'quotation_payment_url' => $paymentUrl,
            ]);
        }

        app(OperationalNotifier::class)->bookingCreated(
            $booking->fresh(['company.users', 'unit', 'professional.user'])
        );

        if ($paymentUrl) {
            return redirect()->away($paymentUrl);
        }

        return redirect()->route('bookings.index')->with('status', 'Booking saved. The payment link is temporarily unavailable; use Pay now from Bookings.');
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
            $invoice = Invoice::create(['company_id' => $booking->company_id, 'number' => 'INV-Q-'.now()->format('ymd').'-'.$booking->id, 'public_token' => hash('sha256', $booking->reference.now()), 'issued_at' => today(), 'due_at' => today()->addDays($booking->company->payment_terms ?: 7), 'status' => $subtotal + $tax > 0 ? 'sent' : 'paid', 'line_items' => [['description' => 'Labour ('.$booking->labour_hours.' hours)', 'quantity' => 1, 'unit_price_minor' => $booking->labour_amount_minor], ['description' => 'Parts: '.($booking->parts_required ?: 'As quoted'), 'quantity' => 1, 'unit_price_minor' => $booking->parts_amount_minor], ['description' => 'Inspection fee credit', 'quantity' => 1, 'unit_price_minor' => -$credit]], 'subtotal_minor' => $subtotal, 'tax_minor' => $tax, 'amount_minor' => $subtotal + $tax, 'currency' => 'AED', 'notes' => 'Approved maintenance quotation for '.$booking->reference]);
            $paid = ($subtotal + $tax) === 0;
            $successUrl = route('bookings.index', ['nf_booking' => $booking->id, 'nf_payment' => 'quotation']);
            $paymentUrl = $paid ? null : rescue(fn () => app(NomodPaymentLinks::class)->create('Quotation '.$booking->reference, [['name' => 'Approved maintenance work', 'amount' => number_format(($subtotal + $tax) / 100, 2, '.', ''), 'quantity' => 1]], 'NextFix booking '.$booking->reference.' quotation payment. Inspection credit AED '.number_format($credit / 100, 2).' applied.', $successUrl, route('bookings.index')), null, false);
            $booking->update(['workflow_stage' => $paid ? 'work' : 'awaiting_quote_payment', 'status' => $paid ? 'assigned' : 'awaiting_quote_payment', 'payment_status' => $paid ? 'paid' : 'pending', 'estimate_approved_at' => now(), 'customer_decision_at' => now(), 'customer_decision_note' => $data['decision_note'] ?? null, 'quotation_invoice_id' => $invoice->id, 'quotation_payment_url' => $paymentUrl]);
            app(OperationalNotifier::class)->quotationApproved(
                $booking->fresh(['company.users', 'unit', 'professional.user', 'quotationInvoice']),
                $invoice,
                $paid
            );

            if ($paymentUrl) {
                return redirect()->away($paymentUrl);
            }

            return back()->with('status', $paid ? 'Quotation approved. Work is authorized.' : 'Quotation approved. The payment link is temporarily unavailable.');
        }

        $request->validate(['decision_note' => ['required', 'string', 'max:1000']]);
        $reason = $data['decision_note'] ?? 'Please revise the quotation.';
        $booking->update(['workflow_stage' => 'estimate', 'status' => 'changes_requested', 'customer_decision_at' => now(), 'customer_decision_note' => $reason]);
        app(OperationalNotifier::class)->quotationChangesRequested(
            $booking->fresh(['company.users', 'unit', 'professional.user']),
            $reason
        );

        return back()->with('status', 'Changes requested. NextFix will revise the quotation.');
    }

    public function quotationPdf(Request $request, Booking $booking)
    {
        $this->authorizeBookingDocument($request, $booking);
        abort_unless($booking->estimate_submitted_at, 404, 'Quotation is not available yet.');
        $booking->load(['company', 'unit', 'professional']);

        return Pdf::loadView('bookings.quotation-pdf', ['booking' => $booking])
            ->download('Quotation-'.$booking->reference.'.pdf');
    }

    public function invoicePdf(Request $request, Booking $booking)
    {
        $this->authorizeBookingDocument($request, $booking);
        abort_unless($booking->quotation_invoice_id, 404, 'Invoice is not available yet.');
        $invoice = $booking->quotationInvoice()->with(['company', 'payments'])->firstOrFail();

        return Pdf::loadView('admin.finance.pdf', ['invoice' => $invoice])
            ->download(($invoice->status === 'paid' ? 'Paid-' : '').$invoice->number.'.pdf');
    }

    private function authorizeBookingDocument(Request $request, Booking $booking): void
    {
        abort_unless(
            $request->user()->role === 'nextfix_admin'
            || $booking->company_id === $request->user()->company_id
            || ($request->user()->role === 'professional' && $request->user()->professional?->id === $booking->professional_id),
            403
        );
    }
}
