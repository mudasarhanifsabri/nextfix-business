<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_manager_can_view_and_update_profile(): void
    {
        $company = Company::create(['name'=>'Profile Co','contact_name'=>'Old Name','email'=>'old@example.test','phone'=>'0500000000','billing_address'=>'Dubai','country'=>'UAE']);
        $manager = User::factory()->create(['role'=>'company_manager','company_id'=>$company->id]);
        $this->actingAs($manager)->get(route('company.profile.show'))->assertOk()->assertSee('Company contact details');
        $this->actingAs($manager)->post(route('company.profile.update'), ['contact_name'=>'New Name','phone'=>'0501111111','email'=>'new@example.test','billing_email'=>'billing@example.test','billing_address'=>'Business Bay, Dubai'])->assertRedirect();
        $this->assertDatabaseHas('companies',['id'=>$company->id,'contact_name'=>'New Name']);
    }

    public function test_admin_can_request_nomod_refund_with_password(): void
    {
        $admin = User::factory()->create(['role'=>'nextfix_admin','password'=>'secret-pass']);
        $company = Company::create(['name'=>'Refund Co']);
        $payment = Payment::create(['company_id'=>$company->id,'reference'=>'PAY-1','method'=>'nomod','status'=>'completed','amount_minor'=>10000,'currency'=>'AED','paid_at'=>now()]);
        $this->actingAs($admin)->post(route('admin.payments.refund',$payment), ['amount'=>'50.00','reason'=>'Partial service','password_confirmation_admin'=>'secret-pass'])->assertRedirect();
        $this->assertDatabaseHas('payments',['id'=>$payment->id,'status'=>'refund_pending','refund_amount_minor'=>5000,'refund_reason'=>'Partial service']);
    }
}
