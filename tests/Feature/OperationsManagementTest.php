<?php

namespace Tests\Feature;

use App\Models\User;
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
            'skills' => ['Deep cleaning'], 'working_days' => ['Mon', 'Tue', 'Wed'],
        ])->assertRedirect();

        $this->assertDatabaseHas('professionals', ['email' => 'aisha@example.test', 'role' => 'cleaner']);
    }

    public function test_company_manager_cannot_view_admin_operations(): void
    {
        $manager = User::factory()->create(['role' => 'company_manager']);

        $this->actingAs($manager)->get(route('admin.operations.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.integrations.index'))->assertForbidden();
    }
}
