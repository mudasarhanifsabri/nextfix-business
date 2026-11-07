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
        $booking = Booking::create(['company_id' => $company->id, 'unit_id' => $unit->id, 'professional_id' => $professional->id, 'reference' => 'NFB-WORK-001', 'service_type' => 'maintenance', 'service_name' => 'AC repair', 'scheduled_at' => now()->addHour(), 'status' => 'assigned']);

        $this->actingAs($worker)->get(route('worker.jobs.show', $booking))->assertOk()->assertSee('Tap when done');
        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), [
            'action' => 'hold', 'completed_items' => ['arrival', 'safety'], 'hold_reason' => 'waiting_parts',
        ])->assertRedirect();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'on_hold', 'hold_reason' => 'waiting_parts', 'progress' => 25]);
    }
}
