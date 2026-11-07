<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Professional;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->actingAs($worker)->get(route('worker.jobs.show', $booking))->assertOk()->assertSee('Tap when done');
        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), [
            'action' => 'hold', 'completed_items' => ['arrival', 'safety'], 'hold_reason' => 'waiting_parts',
        ])->assertRedirect();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'on_hold', 'hold_reason' => 'waiting_parts', 'progress' => 25]);
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

        $this->get(route('dashboard'))->assertRedirect(route('worker.jobs.index'));
        $this->get(route('worker.jobs.index'))->assertOk()->assertSee('Assigned jobs');
    }

    public function test_worker_can_submit_inspection_and_estimate_for_admin_approval(): void
    {
        $worker = User::factory()->create(['role' => 'professional']);
        $admin = User::factory()->create(['role' => 'nextfix_admin']);
        $professional = Professional::create(['user_id' => $worker->id, 'name' => 'Inspector', 'role' => 'maintenance', 'phone' => '+971500000097']);
        $company = Company::create(['name' => 'Inspection Homes']);
        $unit = Unit::create(['company_id' => $company->id, 'building' => 'Marina Tower', 'unit_number' => '88', 'address' => 'Dubai']);
        $booking = Booking::create(['company_id' => $company->id, 'unit_id' => $unit->id, 'professional_id' => $professional->id, 'reference' => 'NFB-INSPECT-1', 'service_type' => 'maintenance', 'service_name' => 'Leak repair', 'scheduled_at' => now(), 'status' => 'assigned']);

        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), ['action' => 'inspection', 'inspection_condition' => 'urgent', 'inspection_issues' => ['water_leak'], 'inspection_report' => 'Pipe is leaking.'])->assertRedirect();
        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), ['action' => 'estimate', 'labour_hours' => 2, 'labour_cost' => 150, 'parts_cost' => 75, 'parts_required' => 'Replacement valve'])->assertRedirect();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'workflow_stage' => 'awaiting_approval', 'labour_amount_minor' => 15000, 'parts_amount_minor' => 7500]);
        $this->actingAs($worker)->get(route('worker.jobs.show', $booking))->assertOk()->assertSee('Waiting for office approval')->assertDontSee('Tap when done');

        $this->actingAs($admin)->post(route('admin.operations.approve', $booking))->assertRedirect();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'workflow_stage' => 'work', 'status' => 'assigned']);
        $this->actingAs($worker)->get(route('worker.jobs.show', $booking))->assertOk()->assertSee('Tap when done');
    }
}
