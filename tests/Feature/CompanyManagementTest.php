<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
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

    public function test_public_company_registration_creates_onboarding_workspace(): void
    {
        $this->post(route('register.store'), [
            'company_name' => 'Jumeirah Guest Homes', 'industry' => 'Holiday Homes', 'name' => 'Maya Ali',
            'email' => 'maya@jumeirah.test', 'phone' => '+971500001111',
            'password' => 'Secure123!', 'password_confirmation' => 'Secure123!',
        ])->assertRedirect(route('dashboard'));

        $company = Company::where('name', 'Jumeirah Guest Homes')->firstOrFail();
        $this->assertSame('onboarding', $company->status);
        $this->assertAuthenticatedAs(User::where('email', 'maya@jumeirah.test')->firstOrFail());
    }

    public function test_admin_can_quick_edit_company_and_manager_login(): void
    {
        $admin = User::factory()->create(['role' => 'nextfix_admin']);
        $company = Company::create(['name' => 'Old Name', 'contact_name' => 'Old Manager', 'email' => 'old@test.ae', 'phone' => '+971500000000', 'billing_address' => 'Dubai', 'country' => 'United Arab Emirates']);
        $manager = User::factory()->create(['role' => 'company_manager', 'company_id' => $company->id, 'email' => 'old@test.ae']);

        $this->actingAs($admin)->put(route('admin.companies.update', $company), [
            'name' => 'New Name', 'contact_name' => 'New Manager', 'email' => 'new@test.ae', 'phone' => '+971500000001',
            'billing_email' => 'accounts@test.ae', 'billing_address' => 'Business Bay', 'city' => 'Dubai',
            'country' => 'United Arab Emirates', 'industry' => 'Property Management', 'payment_terms' => 15, 'status' => 'active',
        ])->assertRedirect(route('admin.companies.show', $company));

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'New Name', 'payment_terms' => 15]);
        $this->assertDatabaseHas('users', ['id' => $manager->id, 'name' => 'New Manager', 'email' => 'new@test.ae']);
    }

    public function test_login_has_no_demo_credentials_and_password_reset_can_be_requested(): void
    {
        $user = User::factory()->create();
        $this->get(route('login'))->assertOk()->assertDontSee('Admin demo')->assertDontSee('manager@demo.test')->assertSee('Forgot password?')->assertSee('Register your company');

        Notification::fake();
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }
}
