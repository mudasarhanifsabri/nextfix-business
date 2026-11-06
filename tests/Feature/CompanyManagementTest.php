<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_a_company_and_manager(): void
    {
        $admin = User::factory()->create(['role' => 'nextfix_admin']);

        $response = $this->actingAs($admin)->post(route('admin.companies.store'), [
            'name' => 'Palm Stays', 'legal_name' => 'Palm Stays LLC', 'contact_name' => 'Sara Ali',
            'email' => 'sara@palm.test', 'phone' => '+971500000000', 'billing_address' => 'Dubai Marina',
            'country' => 'United Arab Emirates', 'payment_terms' => 30,
            'manager_password' => 'Secure123!', 'manager_password_confirmation' => 'Secure123!',
        ]);

        $company = Company::where('name', 'Palm Stays')->firstOrFail();
        $response->assertRedirect(route('admin.companies.show', $company));
        $this->assertDatabaseHas('users', ['company_id' => $company->id, 'email' => 'sara@palm.test', 'role' => 'company_manager']);
    }

    public function test_company_manager_cannot_access_admin_companies(): void
    {
        $manager = User::factory()->create(['role' => 'company_manager']);
        $this->actingAs($manager)->get(route('admin.companies.index'))->assertForbidden();
    }
}
