<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class NomodPaymentReconciler
{
    public function reconcile(): array
    {
        if (config('services.nomod.mode') === 'disabled' || ! filled(config('services.nomod.api_key'))) {
            throw new RuntimeException('Nomod API is not configured.');
        }

        $response = Http::timeout(25)
            ->withHeaders(['X-API-KEY' => config('services.nomod.api_key')])
            ->get(rtrim(config('services.nomod.base_url'), '/').'/v1/charges', ['page_size' => 100]);

        if (! $response->successful()) throw new RuntimeException('Nomod could not return the charge list.');

        $matched = 0;
        foreach ($response->json('results', []) as $charge) {
            if (! in_array(strtolower((string) ($charge['status'] ?? '')), ['paid', 'completed'], true)) continue;
            $successUrl = (string) ($charge['success_url'] ?? $charge['successUrl'] ?? '');
            parse_str((string) parse_url($successUrl, PHP_URL_QUERY), $query);
            $bookingId = filter_var($query['nf_booking'] ?? null, FILTER_VALIDATE_INT);
            $paymentType = strtolower((string) ($query['nf_payment'] ?? ''));
            if (! $bookingId || ! in_array($paymentType, ['inspection', 'cleaning', 'quotation'], true)) continue;

            $matched += DB::transaction(fn () => $this->settle($bookingId, $paymentType, $charge));
        }

        return ['matched' => $matched, 'checked' => count($response->json('results', []))];
    }

    private function settle(int $bookingId, string $paymentType, array $charge): int
    {
        $booking = Booking::query()->lockForUpdate()->find($bookingId);
        if (! $booking || $booking->payment_status === 'paid') return 0;

        $expectedMinor = $paymentType === 'inspection' ? $booking->inspection_fee_minor : $booking->quotationInvoice?->amount_minor;
        $amountMinor = (int) round(((float) ($charge['total'] ?? 0)) * 100);
        $currency = strtoupper((string) ($charge['currency'] ?? ''));
        if (! $expectedMinor || $amountMinor !== (int) $expectedMinor || $currency !== $booking->currency) return 0;

        $gatewayReference = (string) ($charge['id'] ?? $charge['objectId'] ?? '');
        if ($gatewayReference === '') return 0;
        Payment::firstOrCreate(['gateway_reference' => $gatewayReference], [
            'company_id' => $booking->company_id,
            'invoice_id' => $paymentType === 'inspection' ? null : $booking->quotation_invoice_id,
            'reference' => 'NOMOD-'.Str::upper(Str::random(12)), 'method' => 'nomod', 'status' => 'completed',
            'amount_minor' => $amountMinor, 'currency' => $currency, 'paid_at' => now(),
            'notes' => ucfirst($paymentType).' payment for '.$booking->reference.' · reconciled from Nomod API',
        ]);

        if ($paymentType === 'inspection') {
            $booking->update(['payment_status' => 'paid', 'inspection_fee_paid_at' => now(), 'status' => 'pending', 'workflow_stage' => 'inspect']);
        } else {
            $booking->update(['payment_status' => 'paid', 'status' => 'assigned', 'workflow_stage' => 'work']);
            $booking->quotationInvoice?->update(['status' => 'paid']);
        }
        return 1;
    }
}
