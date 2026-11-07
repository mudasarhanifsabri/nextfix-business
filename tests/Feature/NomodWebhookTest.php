<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Company;
use App\Models\IntegrationSetting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NomodWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_completed_charge_marks_inspection_paid_once(): void
    {
        $company = Company::create(['name' => 'Webhook Homes']);
        User::factory()->create(['role' => 'company_manager', 'company_id' => $company->id]);
        User::factory()->create(['role' => 'nextfix_admin']);
        $unit = Unit::create(['company_id' => $company->id, 'building' => 'Webhook Tower', 'unit_number' => '1', 'address' => 'Dubai']);
        $booking = Booking::create([
            'company_id' => $company->id, 'unit_id' => $unit->id, 'reference' => 'NFB-WEBHOOK-1',
            'service_type' => 'maintenance', 'service_name' => 'AC inspection', 'scheduled_at' => now()->addDay(),
            'status' => 'awaiting_inspection_payment', 'payment_status' => 'pending', 'amount_minor' => 15000,
            'inspection_fee_minor' => 15000, 'currency' => 'AED',
        ]);

        $secretBytes = random_bytes(24);
        $secret = 'whsec_'.base64_encode($secretBytes);
        IntegrationSetting::put('nomod.webhook_secret', $secret);
        config(['services.nomod.webhook_secret' => $secret]);

        $payload = json_encode([
            'type' => 'charge.completed', 'eventId' => 'evt-test-1', 'objectId' => 'ch-test-1',
            'data' => ['currency' => 'AED', 'total' => '150.00', 'successUrl' => route('bookings.index', ['nf_booking' => $booking->id, 'nf_payment' => 'inspection'])],
        ], JSON_UNESCAPED_SLASHES);
        $messageId = 'msg-test-1';
        $timestamp = (string) now()->timestamp;
        $signature = base64_encode(hash_hmac('sha256', $messageId.'.'.$timestamp.'.'.$payload, $secretBytes, true));
        $headers = ['HTTP_SVIX_ID' => $messageId, 'HTTP_SVIX_TIMESTAMP' => $timestamp, 'HTTP_SVIX_SIGNATURE' => 'v1,'.$signature, 'CONTENT_TYPE' => 'application/json'];

        $this->call('POST', route('webhooks.nomod'), [], [], [], $headers, $payload)->assertOk();
        $this->call('POST', route('webhooks.nomod'), [], [], [], $headers, $payload)->assertOk()->assertJson(['duplicate' => true]);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'payment_status' => 'paid', 'workflow_stage' => 'inspect']);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('nomod_webhook_events', 1);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['services.nomod.webhook_secret' => 'whsec_'.base64_encode(random_bytes(24))]);
        $this->postJson(route('webhooks.nomod'), ['type' => 'charge.completed'])->assertStatus(400);
    }
}
