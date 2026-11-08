<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Professional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaunchReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_account_pages_render_without_demo_data(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
        $this->get(route('password.request'))->assertOk();
        $this->get(route('offline'))->assertOk();
    }

    public function test_admin_core_pages_render_with_an_empty_operational_database(): void
    {
        $admin = User::factory()->create(['role' => 'nextfix_admin']);

        foreach (['dashboard', 'admin.operations.index', 'admin.companies.index', 'admin.professionals.index', 'admin.invoices.index', 'admin.payments.index', 'admin.notifications.index', 'admin.integrations.index', 'admin.updates.index'] as $routeName) {
            $this->actingAs($admin)->get(route($routeName))->assertOk();
        }
    }

    public function test_company_core_pages_render_before_any_units_or_bookings_exist(): void
    {
        $company = Company::create(['name' => 'Launch Company']);
        $manager = User::factory()->create(['role' => 'company_manager', 'company_id' => $company->id]);

        foreach (['dashboard', 'bookings.index', 'units.index', 'rates.show', 'company.profile.show', 'admin.notifications.index'] as $routeName) {
            $this->actingAs($manager)->get(route($routeName))->assertOk();
        }
    }

    public function test_worker_core_pages_render_before_first_assignment(): void
    {
        $worker = User::factory()->create(['role' => 'professional']);
        Professional::create(['user_id' => $worker->id, 'name' => 'Launch Worker', 'role' => 'cleaner', 'phone' => '+971500000120']);

        foreach (['worker.home', 'worker.jobs.index', 'worker.profile.show', 'admin.notifications.index'] as $routeName) {
            $this->actingAs($worker)->get(route($routeName))->assertOk();
        }
    }
}
