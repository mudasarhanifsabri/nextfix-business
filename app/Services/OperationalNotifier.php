<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Company;
use App\Models\CompanyRateCard;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Support\Collection;

class OperationalNotifier
{
    public function companyRegistered(Company $company, User $manager): void
    {
        $this->send([$manager], new AdminAlert(
            'Welcome to NextFix Business',
            'Your company workspace is ready. Add your units while the NextFix team prepares your service rate card.',
            route('dashboard'),
            'success',
            'Open company workspace',
            ['Company' => $company->name],
            'company:'.$company->id.':welcome',
        ));
        $this->send($this->admins(), new AdminAlert(
            'New company registration',
            $company->name.' registered and is ready for onboarding.',
            route('admin.companies.show', $company),
            'info',
            'Review company',
            ['Contact' => $manager->name, 'Email' => $manager->email],
            'company:'.$company->id.':registered',
        ));
    }

    public function rateCardReady(Company $company, CompanyRateCard $card): void
    {
        $this->send($this->companyManagers($company), new AdminAlert(
            'Rate card ready for approval',
            'NextFix sent a new service rate card for your review. Approve it to activate those prices for bookings.',
            route('rates.show'),
            'info',
            'Review rate card',
            ['Company' => $company->name],
            'rate-card:'.$card->id.':submitted:'.optional($card->submitted_at)->timestamp,
        ));
    }

    public function rateCardDecision(Company $company, CompanyRateCard $card, string $decision): void
    {
        $approved = $decision === 'approve';
        $this->send($this->admins(), new AdminAlert(
            $approved ? 'Rate card approved' : 'Rate review requested',
            $approved
                ? $company->name.' approved the service rate card. These prices are now active.'
                : $company->name.' requested a review of the service rate card.',
            route('admin.companies.rates.show', $company),
            $approved ? 'success' : 'warning',
            'Open rate card',
            $approved ? [] : ['Review note' => $card->review_note],
            'rate-card:'.$card->id.':'.$decision.':'.optional($card->updated_at)->timestamp,
        ));
    }

    public function bookingCreated(Booking $booking): void
    {
        $booking->loadMissing(['company', 'unit']);
        $paymentMessage = $booking->service_type === 'maintenance'
            ? 'Pay the inspection fee to confirm and unlock professional assignment.'
            : 'Your service request was received and the NextFix team will confirm the assignment.';
        $this->send($this->companyManagers($booking->company), new AdminAlert(
            'Booking received · '.$booking->reference,
            $booking->service_name.' was requested for '.$this->location($booking).'. '.$paymentMessage,
            route('bookings.index'),
            'success',
            $booking->service_type === 'maintenance' ? 'Review and pay' : 'View booking',
            $this->bookingDetails($booking),
            'booking:'.$booking->id.':created:company',
        ));
        $this->send($this->admins(), new AdminAlert(
            'New booking · '.$booking->reference,
            $booking->company->name.' requested '.$booking->service_name.' at '.$this->location($booking).'.',
            route('admin.operations.index'),
            'info',
            'Open operations',
            $this->bookingDetails($booking),
            'booking:'.$booking->id.':created:admin',
        ));
    }

    public function bookingAssigned(Booking $booking, ?int $previousProfessionalId = null): void
    {
        $booking->loadMissing(['company', 'unit', 'professional.user']);
        if ($booking->professional?->user) {
            $this->send([$booking->professional->user], new AdminAlert(
                'New job assigned · '.$booking->reference,
                $booking->service_name.' at '.$this->location($booking).' is assigned to you.',
                route('worker.jobs.show', $booking),
                'info',
                'Open job',
                $this->bookingDetails($booking),
                'booking:'.$booking->id.':assigned:'.$booking->professional_id,
            ));
        }
        $this->send($this->companyManagers($booking->company), new AdminAlert(
            $booking->professional_id ? 'Professional assigned' : 'Assignment updated',
            $booking->professional_id
                ? $booking->professional->name.' is assigned to '.$booking->reference.'.'
                : 'The professional assignment for '.$booking->reference.' is being updated.',
            route('bookings.index'),
            'info',
            'View booking',
            $this->bookingDetails($booking),
            'booking:'.$booking->id.':assignment:'.($booking->professional_id ?: 'removed').':'.optional($booking->updated_at)->timestamp,
        ));

        if ($previousProfessionalId && $previousProfessionalId !== $booking->professional_id) {
            $previous = User::whereHas('professional', fn ($query) => $query->whereKey($previousProfessionalId))->first();
            $this->send($previous ? [$previous] : [], new AdminAlert(
                'Job assignment changed',
                $booking->reference.' is no longer assigned to you.',
                route('worker.jobs.index'),
                'warning',
                'View my jobs',
                [],
                'booking:'.$booking->id.':unassigned:'.$previousProfessionalId,
            ));
        }
    }

    public function inspectionSubmitted(Booking $booking): void
    {
        $booking->loadMissing(['company', 'unit', 'professional']);
        $this->send($this->companyManagers($booking->company), new AdminAlert(
            'Inspection completed · '.$booking->reference,
            'The first inspection is complete. NextFix is preparing the labour and parts quotation.',
            route('bookings.index'),
            'info',
            'View service',
            $this->bookingDetails($booking),
            'booking:'.$booking->id.':inspection:company:'.optional($booking->updated_at)->timestamp,
        ));
        $this->send($this->admins(), new AdminAlert(
            'Inspection submitted · '.$booking->reference,
            ($booking->professional?->name ?: 'The assigned professional').' submitted the inspection report.',
            route('jobs.report.show', $booking),
            'info',
            'Review inspection',
            $this->bookingDetails($booking),
            'booking:'.$booking->id.':inspection:admin:'.optional($booking->updated_at)->timestamp,
        ));
    }

    public function quotationReady(Booking $booking): void
    {
        $booking->loadMissing(['company', 'unit', 'professional']);
        $amount = $booking->labour_amount_minor + $booking->parts_amount_minor;
        $this->send($this->companyManagers($booking->company), new AdminAlert(
            'Quotation ready · '.$booking->reference,
            'Review the inspection, labour, parts and inspection-fee credit, then approve or request changes.',
            route('bookings.index').'#quotation-'.$booking->id,
            'warning',
            'Review quotation',
            $this->bookingDetails($booking) + ['Quoted amount' => $this->money($amount, $booking->currency)],
            'booking:'.$booking->id.':quotation:company:'.optional($booking->quotation_sent_at ?? $booking->estimate_submitted_at)->timestamp,
        ));
        $this->send($this->admins(), new AdminAlert(
            'Quotation submitted · '.$booking->reference,
            ($booking->professional?->name ?: 'The assigned professional').' submitted the quotation for '.$booking->company->name.'.',
            route('admin.operations.index'),
            'info',
            'Review quotation',
            $this->bookingDetails($booking) + ['Quoted amount' => $this->money($amount, $booking->currency)],
            'booking:'.$booking->id.':quotation:admin:'.optional($booking->quotation_sent_at ?? $booking->estimate_submitted_at)->timestamp,
        ));
    }

    public function quotationApproved(Booking $booking, Invoice $invoice, bool $paid): void
    {
        $booking->loadMissing(['company', 'unit', 'professional.user']);
        $companyAlert = new AdminAlert(
            $paid ? 'Quotation approved · work authorized' : 'Invoice ready · payment required',
            $paid
                ? 'Your quotation is approved and fully credited. The assigned professional can begin the approved work.'
                : 'Your quotation is approved. Pay the invoice balance to authorize the repair.',
            $paid ? route('jobs.report.show', $booking) : route('bookings.index'),
            $paid ? 'success' : 'warning',
            $paid ? 'View service' : 'View invoice and pay',
            $this->bookingDetails($booking) + ['Invoice' => $invoice->number, 'Amount due' => $this->money($invoice->amount_minor, $invoice->currency)],
            'booking:'.$booking->id.':quotation-approved:company:'.$invoice->id,
        );
        $this->send($this->companyManagers($booking->company), $companyAlert);
        $this->send($booking->professional?->user ? [$booking->professional->user] : [], new AdminAlert(
            'Quotation approved · '.$booking->reference,
            $paid ? 'The company approved the quotation. Work is authorized.' : 'The company approved the quotation. Work will unlock after invoice payment.',
            route('worker.jobs.show', $booking),
            $paid ? 'success' : 'warning',
            'Open job',
            ['Company' => $booking->company->name, 'Invoice' => $invoice->number],
            'booking:'.$booking->id.':quotation-approved:worker:'.$invoice->id,
        ));
        $this->send($this->admins(), new AdminAlert(
            'Customer approved quotation · '.$booking->reference,
            $booking->company->name.' approved invoice '.$invoice->number.'. '.($paid ? 'No balance remains.' : 'Payment is pending.'),
            route('admin.payments.index'),
            $paid ? 'success' : 'info',
            'Open payments',
            ['Invoice' => $invoice->number, 'Amount' => $this->money($invoice->amount_minor, $invoice->currency)],
            'booking:'.$booking->id.':quotation-approved:admin:'.$invoice->id,
        ));
    }

    public function quotationChangesRequested(Booking $booking, string $reason): void
    {
        $booking->loadMissing(['company', 'professional.user']);
        $alert = new AdminAlert(
            'Quotation changes requested · '.$booking->reference,
            $booking->company->name.' requested changes to the quotation.',
            route('worker.jobs.show', $booking),
            'warning',
            'Revise quotation',
            ['Reason' => $reason],
            'booking:'.$booking->id.':quotation-changes:'.optional($booking->customer_decision_at)->timestamp,
        );
        $this->send($booking->professional?->user ? [$booking->professional->user] : [], $alert);
        $this->send($this->admins(), new AdminAlert(
            $alert->title,
            $alert->message,
            route('admin.operations.index'),
            'warning',
            'Open operations',
            $alert->details,
            $alert->eventKey.':admin',
        ));
    }

    public function paymentReceived(Booking $booking, Payment $payment, string $paymentType): void
    {
        $booking->loadMissing(['company', 'unit', 'professional.user', 'quotationInvoice']);
        $label = match ($paymentType) {
            'inspection' => 'Inspection payment',
            'cleaning' => 'Cleaning payment',
            default => 'Invoice payment',
        };
        $workUnlocked = in_array($paymentType, ['cleaning', 'quotation'], true);
        $details = $this->bookingDetails($booking) + [
            'Payment' => $payment->reference,
            'Amount received' => $this->money($payment->amount_minor, $payment->currency),
            'Status' => 'Paid',
        ];
        $this->send($this->companyManagers($booking->company), new AdminAlert(
            $label.' receipt · '.$booking->reference,
            'Payment was received successfully and your receipt is ready.',
            $booking->quotation_invoice_id ? route('bookings.invoice.pdf', $booking) : route('bookings.index'),
            'success',
            $booking->quotation_invoice_id ? 'Download paid invoice' : 'View booking',
            $details,
            'payment:'.$payment->id.':receipt:company',
        ));
        $this->send($this->admins(), new AdminAlert(
            'Payment received · '.$booking->reference,
            $label.' was verified for '.$booking->company->name.'.',
            route('admin.payments.index'),
            'success',
            'Open payment record',
            $details,
            'payment:'.$payment->id.':receipt:admin',
        ));
        $this->send($booking->professional?->user ? [$booking->professional->user] : [], new AdminAlert(
            $workUnlocked ? 'Payment confirmed · work unlocked' : 'Inspection payment confirmed',
            $workUnlocked
                ? $booking->reference.' is paid. You can begin the approved work.'
                : $booking->reference.' inspection payment is confirmed.',
            route('worker.jobs.show', $booking),
            'success',
            'Open job',
            ['Booking' => $booking->reference, 'Amount' => $this->money($payment->amount_minor, $payment->currency)],
            'payment:'.$payment->id.':receipt:worker',
        ));
    }

    public function paymentStateChanged(?Booking $booking, string $state, string $title, string $message, string $level, string $objectId): void
    {
        $key = 'nomod:'.($objectId ?: 'unknown').':'.$state;
        if (! $booking) {
            $this->send($this->admins(), new AdminAlert(
                $title,
                $message.' This Nomod event could not be matched to a NextFix booking.',
                route('admin.payments.index'),
                $level,
                'Review payment event',
                ['Nomod object' => $objectId ?: 'Unknown'],
                $key.':admin',
            ));
            return;
        }

        $booking->loadMissing(['company', 'professional.user']);
        $fullMessage = $message.' Booking '.$booking->reference.'.';
        $this->send($this->companyManagers($booking->company), new AdminAlert(
            $title,
            $fullMessage,
            route('bookings.index'),
            $level,
            'Review booking',
            $this->bookingDetails($booking),
            $key.':company',
        ));
        $this->send($this->admins(), new AdminAlert(
            $title,
            $fullMessage,
            route('admin.payments.index'),
            $level,
            'Open payments',
            $this->bookingDetails($booking),
            $key.':admin',
        ));
        if (in_array($state, ['refunded', 'partially_refunded', 'disputed'], true)) {
            $this->send($booking->professional?->user ? [$booking->professional->user] : [], new AdminAlert(
                'Work paused · payment '.$state,
                $booking->reference.' is on hold while the payment is reviewed.',
                route('worker.jobs.show', $booking),
                'warning',
                'Open job',
                [],
                $key.':worker',
            ));
        }
    }

    public function jobStatusChanged(Booking $booking, string $action): void
    {
        $booking->loadMissing(['company', 'professional']);
        $title = match ($action) {
            'hold' => 'Job placed on hold',
            'resume' => 'Job resumed',
            default => 'Job completed',
        };
        $message = match ($action) {
            'hold' => $booking->reference.' is paused: '.str_replace('_', ' ', (string) $booking->hold_reason).'.',
            'resume' => $booking->reference.' has resumed.',
            default => $booking->reference.' was completed by '.($booking->professional?->name ?: 'the service team').'. The report is ready.',
        };
        $details = $this->bookingDetails($booking);
        if ($action === 'hold' && filled($booking->hold_note)) {
            $details['Note'] = $booking->hold_note;
        }
        $eventMoment = $action === 'complete' ? $booking->work_completed_at : $booking->updated_at;
        $this->send($this->companyManagers($booking->company), new AdminAlert(
            $title.' · '.$booking->reference,
            $message,
            $action === 'complete' ? route('jobs.report.show', $booking) : route('bookings.index'),
            $action === 'hold' ? 'warning' : 'success',
            $action === 'complete' ? 'View job report' : 'View booking',
            $details,
            'booking:'.$booking->id.':'.$action.':company:'.optional($eventMoment)->timestamp,
        ));
        $this->send($this->admins(), new AdminAlert(
            $title.' · '.$booking->reference,
            $message,
            $action === 'complete' ? route('jobs.report.show', $booking) : route('admin.operations.index'),
            $action === 'hold' ? 'warning' : 'success',
            $action === 'complete' ? 'View job report' : 'Open operations',
            $details,
            'booking:'.$booking->id.':'.$action.':admin:'.optional($eventMoment)->timestamp,
        ));
    }

    public function refundRequested(Payment $payment): void
    {
        $payment->loadMissing(['company', 'invoice']);
        $booking = $payment->invoice ? Booking::where('quotation_invoice_id', $payment->invoice_id)->first() : null;
        $details = [
            'Payment' => $payment->reference,
            'Refund amount' => $this->money((int) $payment->refund_amount_minor, $payment->currency),
            'Reason' => $payment->refund_reason,
            'Status' => str_replace('_', ' ', $payment->status),
        ];
        $this->send($this->companyManagers($payment->company), new AdminAlert(
            $payment->refunded_at ? 'Refund recorded' : 'Refund requested',
            $payment->refunded_at
                ? 'The refund was recorded for payment '.$payment->reference.'.'
                : 'A refund request was created for payment '.$payment->reference.'. You will be notified when Nomod confirms it.',
            $booking ? route('bookings.index') : route('dashboard'),
            'warning',
            'View account',
            $details,
            'payment:'.$payment->id.':refund-requested:company:'.optional($payment->refund_requested_at)->timestamp,
        ));
        $this->send($this->admins(), new AdminAlert(
            $payment->refunded_at ? 'Manual refund recorded' : 'Nomod refund action required',
            $payment->refunded_at
                ? 'A manual refund was recorded for '.$payment->company->name.'.'
                : 'Complete the refund in Nomod. The webhook will update NextFix automatically.',
            route('admin.payments.index'),
            'warning',
            'Open payment record',
            $details,
            'payment:'.$payment->id.':refund-requested:admin:'.optional($payment->refund_requested_at)->timestamp,
        ));
    }

    public function send(iterable $recipients, AdminAlert $notification): void
    {
        $users = collect($recipients)
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id');

        $users->each(function (User $user) use ($notification): void {
            if ($notification->eventKey && $user->notifications()->where('data->event_key', $notification->eventKey)->exists()) {
                return;
            }

            try {
                $user->notifyNow(clone $notification);
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }

    private function companyManagers(Company $company): Collection
    {
        return $company->relationLoaded('users')
            ? $company->users->where('role', 'company_manager')
            : $company->users()->where('role', 'company_manager')->get();
    }

    private function admins(): Collection
    {
        return User::where('role', 'nextfix_admin')->get();
    }

    private function bookingDetails(Booking $booking): array
    {
        return [
            'Booking' => $booking->reference,
            'Service' => $booking->service_name,
            'Location' => $this->location($booking),
            'Scheduled' => optional($booking->scheduled_at)->timezone(config('app.timezone'))->format('d M Y, g:i A'),
        ];
    }

    private function location(Booking $booking): string
    {
        return trim(($booking->unit?->building ?: 'Property').' · Unit '.($booking->unit?->unit_number ?: '—'));
    }

    private function money(int $minor, string $currency = 'AED'): string
    {
        return strtoupper($currency).' '.number_format($minor / 100, 2);
    }
}
