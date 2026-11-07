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
    }

    public function test_company_manager_cannot_view_admin_operations(): void
    {
        $manager = User::factory()->create(['role' => 'company_manager']);

        $this->actingAs($manager)->get(route('admin.operations.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.integrations.index'))->assertForbidden();
    }
}
