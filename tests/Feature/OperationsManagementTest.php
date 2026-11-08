<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Booking;
use App\Models\Company;
use App\Models\CompanyRateCard;
use App\Models\Professional;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_operations_calendar_and_integrations(): void
    {
        $admin = User::factory()->create(['role' => 'nextfix_admin']);

        $this->actingAs($admin)->get(route('admin.operations.index'))
            ->assertOk()->assertSee('Dispatch with clarity')->assertSee(now()->format('F Y'));
        $this->actingAs($admin)->get(route('admin.operations.index', ['month' => now()->format('Y-m'), 'date' => now()->format('Y-m-d')]))
            ->assertOk()->assertSee('SELECTED DAY')->assertSee(now()->format('l, d F Y'));
        $this->actingAs($admin)->get(route('admin.integrations.index'))
            ->assertOk()->assertSee('SMTP')->assertSee('Nomod');
        $this->actingAs($admin)->get(route('admin.professionals.index'))
            ->assertOk()->assertSee('Cleaner', false)->assertSee('maintenance team', false);
    }

    public function test_admin_can_create_a_professional_profile(): void
    {
        $admin = User::factory()->create(['role' => 'nextfix_admin']);

        $this->actingAs($admin)->post(route('admin.professionals.store'), [
            'name' => 'Aisha Rahman', 'role' => 'cleaner', 'phone' => '+971500000001',
            'email' => 'aisha@example.test', 'status' => 'available',
            'password' => 'Worker123!', 'password_confirmation' => 'Worker123!',
            'skills' => ['Deep cleaning'], 'working_days' => ['Mon', 'Tue', 'Wed'],
        ])->assertRedirect();

        $this->assertDatabaseHas('professionals', ['email' => 'aisha@example.test', 'role' => 'cleaner']);
        $this->assertDatabaseHas('users', ['email' => 'aisha@example.test', 'role' => 'professional']);
    }

    public function test_admin_can_save_encrypted_integration_settings_after_reauthentication(): void
    {
        $admin = User::factory()->create(['role' => 'nextfix_admin', 'password' => 'password']);

        $this->actingAs($admin)->post(route('admin.integrations.smtp'), [
            'password_confirmation_admin' => 'password', 'host' => 'smtp.example.test', 'port' => 587,
            'scheme' => 'tls', 'username' => 'mailer', 'smtp_password' => 'smtp-secret-value',
            'from_address' => 'notifications@example.test', 'from_name' => 'NextFix',
        ])->assertRedirect();

        $stored = \Illuminate\Support\Facades\DB::table('integration_settings')->where('key', 'smtp.password')->value('value');
        $this->assertNotSame('smtp-secret-value', $stored);
        $this->actingAs($admin)->get(route('admin.integrations.index'))->assertOk()->assertDontSee('smtp-secret-value');
    }

    public function test_company_manager_cannot_view_admin_operations(): void
    {
        $manager = User::factory()->create(['role' => 'company_manager']);

        $this->actingAs($manager)->get(route('admin.operations.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.integrations.index'))->assertForbidden();
    }

    public function test_worker_can_save_assigned_job_progress_and_hold_reason(): void
    {
        $worker = User::factory()->create(['role' => 'professional']);
        $professional = Professional::create(['user_id' => $worker->id, 'name' => 'Sam Worker', 'role' => 'maintenance', 'phone' => '+971500000099']);
        $company = Company::create(['name' => 'Test Homes']);
        $unit = Unit::create(['company_id' => $company->id, 'building' => 'Test Tower', 'unit_number' => '101', 'address' => 'Dubai']);
        $booking = Booking::create(['company_id' => $company->id, 'unit_id' => $unit->id, 'professional_id' => $professional->id, 'reference' => 'NFB-WORK-001', 'service_type' => 'maintenance', 'service_name' => 'AC repair', 'scheduled_at' => now()->addHour(), 'status' => 'assigned', 'workflow_stage' => 'work']);

        $this->actingAs($worker)->get(route('worker.home'))->assertOk()->assertSee('AC repair')->assertSee('Continue your work');
        $this->actingAs($worker)->get(route('worker.jobs.show', $booking))
            ->assertOk()->assertSee('Repair checklist')->assertSee('Before repair reference')
            ->assertSee('Take after-repair photos');
        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), [
            'action' => 'hold', 'completed_items' => ['arrival', 'safety'], 'hold_reason' => 'waiting_parts',
        ])->assertRedirect();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'on_hold', 'hold_reason' => 'waiting_parts', 'progress' => 25]);
    }

    public function test_cleaning_job_uses_direct_cleaning_workflow_without_maintenance_estimate(): void
    {
        $worker = User::factory()->create(['role' => 'professional']);
        $professional = Professional::create(['user_id' => $worker->id, 'name' => 'Cleaning Worker', 'role' => 'cleaner', 'phone' => '+971500000090']);
        $company = Company::create(['name' => 'Guest Ready Homes']);
        $unit = Unit::create(['company_id' => $company->id, 'building' => 'Palm Residence', 'unit_number' => '44', 'address' => 'Dubai']);
        $booking = Booking::create(['company_id' => $company->id, 'unit_id' => $unit->id, 'professional_id' => $professional->id, 'reference' => 'NFB-CLEAN-001', 'service_type' => 'cleaning', 'service_name' => 'Checkout cleaning', 'scheduled_at' => now()->addHour(), 'status' => 'confirmed', 'workflow_stage' => 'work']);

        $this->actingAs($worker)->get(route('worker.jobs.show', $booking))
            ->assertOk()->assertSee('Before photo')
            ->assertSee('Bedroom 1')->assertSee('Guest supplies')
            ->assertSee('Final guest-ready check')
            ->assertDontSee('no inspection quotation')
            ->assertDontSee('First inspection')->assertDontSee('Labour & parts estimate');
        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), [
            'action' => 'complete', 'completed_items' => array_keys(\App\Support\WorkChecklist::for($booking)),
        ])->assertStatus(422);
    }

    public function test_professional_login_redirects_to_worker_app(): void
    {
        $worker = User::factory()->create([
            'role' => 'professional',
            'password' => 'worker-password',
        ]);
        Professional::create([
            'user_id' => $worker->id,
            'name' => 'Login Worker',
            'role' => 'cleaner',
            'phone' => '+971500000098',
        ]);

        $this->post(route('login.store'), [
            'email' => $worker->email,
            'password' => 'worker-password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertRedirect(route('worker.home'));
        $this->get(route('worker.home'))->assertOk()->assertSee('You are all caught up');
        $this->get(route('worker.jobs.index'))->assertOk()->assertSee('Active jobs');
    }

    public function test_worker_can_submit_inspection_and_estimate_for_admin_approval(): void
    {
        Storage::fake('public');
        $worker = User::factory()->create(['role' => 'professional']);
        $admin = User::factory()->create(['role' => 'nextfix_admin']);
        $professional = Professional::create(['user_id' => $worker->id, 'name' => 'Inspector', 'role' => 'maintenance', 'phone' => '+971500000097']);
        $company = Company::create(['name' => 'Inspection Homes']);
        $manager = User::factory()->create(['role' => 'company_manager', 'company_id' => $company->id]);
        $unit = Unit::create(['company_id' => $company->id, 'building' => 'Marina Tower', 'unit_number' => '88', 'address' => 'Dubai']);
        $booking = Booking::create(['company_id' => $company->id, 'unit_id' => $unit->id, 'professional_id' => $professional->id, 'reference' => 'NFB-INSPECT-1', 'service_type' => 'maintenance', 'service_name' => 'Leak repair', 'scheduled_at' => now(), 'status' => 'assigned']);

        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), [
            'action' => 'inspection',
            'inspection_condition' => 'urgent',
            'inspection_issues' => ['water_leak'],
            'inspection_report' => 'Pipe is leaking.',
            'inspection_photos' => [UploadedFile::fake()->image('leaking-pipe.jpg')],
        ])->assertRedirect();
        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), ['action' => 'estimate', 'labour_hours' => 2, 'labour_cost' => 150, 'parts_cost' => 75, 'parts_required' => 'Replacement valve'])->assertRedirect();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'workflow_stage' => 'awaiting_customer_approval']);
        $this->assertDatabaseCount('notifications', 2);
        $this->actingAs($worker)->get(route('worker.jobs.show', $booking))->assertOk()->assertSee('Waiting for company approval')->assertSee('INSPECTION SAVED')->assertSee('QUOTATION SENT')->assertSee('Pipe is leaking.')->assertSee('Replacement valve')->assertDontSee('Tap when done')->assertDontSee('Send estimate for approval')->assertDontSee('Save inspection & continue');
        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), ['action' => 'inspection', 'inspection_condition' => 'good', 'inspection_report' => 'Attempted edit.'])->assertStatus(422);
        $this->actingAs($manager)->get(route('bookings.index'))->assertOk()->assertSee('Quotations ready for review')->assertSee('AED 236.25');
        $this->actingAs($manager)->get(route('bookings.quotation.pdf', $booking))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($manager)->post(route('bookings.quotation-decision', $booking), ['decision' => 'approve'])->assertRedirect();
        $this->actingAs($worker)->get(route('admin.notifications.index'))->assertOk()->assertSee('Work alerts')->assertSee('Quotation approved');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'workflow_stage' => 'awaiting_quote_payment', 'status' => 'awaiting_quote_payment']);
        $this->assertDatabaseHas('invoices', ['company_id' => $company->id, 'subtotal_minor' => 22500, 'tax_minor' => 1125, 'amount_minor' => 23625]);
        $this->actingAs($manager)->get(route('bookings.invoice.pdf', $booking->fresh()))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($worker)->get(route('worker.jobs.show', $booking))->assertOk()->assertDontSee('Tap when done');
    }

    public function test_admin_can_send_full_rate_card_and_company_can_approve_it(): void
    {
        $admin = User::factory()->create(['role' => 'nextfix_admin']);
        $company = Company::create(['name' => 'Rate Homes']);
        $manager = User::factory()->create(['role' => 'company_manager', 'company_id' => $company->id]);
        $this->actingAs($admin)->post(route('admin.companies.rates.save', $company), [
            'action' => 'submit', 'maintenance_inspection' => 175,
            'rates' => ['cleaning' => ['1_bhk' => ['normal_linen' => 280]], 'extras' => ['basic_maintenance' => ['wc_clogged' => 150]]],
            'cancellation_percent' => 50, 'transport_fee' => 50,
        ])->assertRedirect();
        $this->assertDatabaseHas('company_rate_cards', ['company_id' => $company->id, 'status' => 'pending_approval', 'maintenance_inspection_minor' => 17500]);
        $this->actingAs($manager)->get(route('rates.show'))->assertOk()->assertSee('AED 175.00')->assertSee('Approve rates');
        $this->actingAs($manager)->post(route('rates.decision'), ['decision' => 'approve'])->assertRedirect();
        $this->assertDatabaseHas('company_rate_cards', ['company_id' => $company->id, 'status' => 'approved']);
    }

    public function test_maintenance_booking_uses_company_inspection_rate(): void
    {
        $company = Company::create(['name'=>'Fee Homes']);
        CompanyRateCard::create(['company_id'=>$company->id,'status'=>'approved','maintenance_inspection_minor'=>18500]);
        $manager = User::factory()->create(['role'=>'company_manager','company_id'=>$company->id]);
        $unit = Unit::create(['company_id'=>$company->id,'building'=>'Fee Tower','unit_number'=>'2','address'=>'Dubai']);
        $this->actingAs($manager)->post(route('bookings.store'), ['unit_id'=>$unit->id,'service_type'=>'maintenance','service_name'=>'AC inspection','scheduled_at'=>now()->addDay()->format('Y-m-d H:i:s')])->assertRedirect(route('bookings.index'));
        $this->assertDatabaseHas('bookings', ['company_id'=>$company->id,'inspection_fee_minor'=>18500,'amount_minor'=>18500,'status'=>'awaiting_inspection_payment']);
    }
}
