<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\NomodWebhookEvent;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class NomodWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        if (! $this->signatureIsValid($request, $rawBody)) {
            return response()->json(['received' => false, 'message' => 'Invalid webhook signature.'], 400);
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload) || ! filled($payload['eventId'] ?? null) || ! filled($payload['type'] ?? null)) {
            return response()->json(['received' => false, 'message' => 'Invalid payload.'], 400);
        }

        if (NomodWebhookEvent::where('event_id', $payload['eventId'])->exists()) {
            return response()->json(['received' => true, 'duplicate' => true]);
        }

        try {
            DB::transaction(function () use ($payload): void {
                $event = NomodWebhookEvent::create([
                    'event_id' => $payload['eventId'],
                    'event_type' => $payload['type'],
                    'object_id' => $payload['objectId'] ?? null,
                    'payload' => $payload,
                ]);

                if ($payload['type'] === 'charge.completed') {
                    $this->completePayment($payload);
                }

                $event->update(['processed_at' => now()]);
            });
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['received' => false, 'message' => 'Webhook processing failed.'], 500);
        }

        return response()->json(['received' => true]);
    }

    private function signatureIsValid(Request $request, string $rawBody): bool
    {
        $secret = (string) config('services.nomod.webhook_secret');
        $messageId = (string) $request->header('svix-id');
        $timestamp = (string) $request->header('svix-timestamp');
        $signatures = (string) $request->header('svix-signature');

        if (! Str::startsWith($secret, 'whsec_') || $messageId === '' || ! ctype_digit($timestamp) || $signatures === '') return false;
        if (abs(now()->timestamp - (int) $timestamp) > 300) return false;

        $key = base64_decode(Str::after($secret, 'whsec_'), true);
        if ($key === false) return false;
        $expected = base64_encode(hash_hmac('sha256', $messageId.'.'.$timestamp.'.'.$rawBody, $key, true));

        foreach (preg_split('/\s+/', trim($signatures)) as $signature) {
            if (Str::startsWith($signature, 'v1,') && hash_equals($expected, Str::after($signature, 'v1,'))) return true;
        }
        return false;
    }

    private function completePayment(array $payload): void
    {
        $data = $payload['data'] ?? [];
        $successUrl = (string) ($data['successUrl'] ?? $data['success_url'] ?? '');
        parse_str((string) parse_url($successUrl, PHP_URL_QUERY), $query);
        $bookingId = filter_var($query['nf_booking'] ?? null, FILTER_VALIDATE_INT);
        $paymentType = $query['nf_payment'] ?? null;

        if (! $bookingId || ! in_array($paymentType, ['inspection', 'quotation'], true)) {
            throw new \RuntimeException('Nomod payment is missing its NextFix booking reference.');
        }

        $booking = Booking::query()->lockForUpdate()->findOrFail($bookingId);
        $invoice = $paymentType === 'quotation' ? $booking->quotation_invoice_id : null;
        $expectedMinor = $paymentType === 'inspection' ? $booking->inspection_fee_minor : $booking->quotationInvoice?->amount_minor;
        $currency = strtoupper((string) ($data['currency'] ?? ''));
        $amountMinor = (int) round(((float) ($data['total'] ?? 0)) * 100);
        if ($currency !== $booking->currency || ! $expectedMinor || $amountMinor !== (int) $expectedMinor) {
            throw new \RuntimeException('Nomod payment amount or currency does not match the booking.');
        }

        $objectId = (string) ($payload['objectId'] ?? $data['id'] ?? '');
        Payment::firstOrCreate(['gateway_reference' => $objectId], [
            'company_id' => $booking->company_id,
            'invoice_id' => $invoice,
            'reference' => 'NOMOD-'.Str::upper(Str::random(12)),
            'method' => 'nomod',
            'status' => 'completed',
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'paid_at' => now(),
            'notes' => ucfirst($paymentType).' payment for '.$booking->reference,
        ]);

        if ($paymentType === 'inspection') {
            $booking->update(['payment_status' => 'paid', 'inspection_fee_paid_at' => now(), 'status' => 'pending', 'workflow_stage' => 'inspect']);
        } else {
            $booking->update(['payment_status' => 'paid', 'status' => 'assigned', 'workflow_stage' => 'work']);
            $booking->quotationInvoice?->update(['status' => 'paid']);
        }

        User::where('company_id', $booking->company_id)->get()->each->notify(new AdminAlert(
            'Payment confirmed',
            'AED '.number_format($amountMinor / 100, 2).' received for '.$booking->reference.'.',
            route('bookings.index'),
            'success'
        ));
        User::where('role', 'nextfix_admin')->get()->each->notify(new AdminAlert(
            'Nomod payment received',
            'AED '.number_format($amountMinor / 100, 2).' received for '.$booking->reference.'.',
            route('admin.payments.index'),
            'success'
        ));
    }
}
