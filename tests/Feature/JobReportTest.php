<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Professional;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_company_and_assigned_worker_can_view_job_report_and_pdf(): void
    {
        $company = Company::create(['name' => 'Report Homes']);
        $unit = Unit::create(['company_id' => $company->id, 'building' => 'Dubai Marina', 'unit_number' => '1402', 'address' => 'Dubai']);
        $manager = User::factory()->create(['role' => 'company_manager', 'company_id' => $company->id]);
        $admin = User::factory()->create(['role' => 'nextfix_admin']);
        $worker = User::factory()->create(['role' => 'professional']);
        $professional = Professional::create(['user_id' => $worker->id, 'name' => 'Report Worker', 'role' => 'cleaner', 'phone' => '+971500000001']);
        $booking = Booking::create(['company_id' => $company->id, 'unit_id' => $unit->id, 'professional_id' => $professional->id, 'reference' => 'NFB-REPORT-1', 'service_type' => 'cleaning', 'service_name' => 'Checkout cleaning', 'scheduled_at' => now(), 'status' => 'completed', 'payment_status' => 'paid', 'progress' => 100, 'completed_items' => [], 'work_completed_at' => now()]);

        foreach ([$admin, $manager, $worker] as $user) {
            $this->actingAs($user)->get(route('jobs.report.show', $booking))->assertOk()->assertSee('NFB-REPORT-1');
            $this->actingAs($user)->get(route('jobs.report.pdf', $booking))->assertOk()->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_unrelated_company_and_worker_cannot_view_report(): void
    {
        $company = Company::create(['name' => 'Owner']);
        $unit = Unit::create(['company_id' => $company->id, 'building' => 'Tower', 'unit_number' => '1', 'address' => 'Dubai']);
        $booking = Booking::create(['company_id' => $company->id, 'unit_id' => $unit->id, 'reference' => 'PRIVATE', 'service_type' => 'cleaning', 'service_name' => 'Cleaning', 'scheduled_at' => now(), 'status' => 'pending']);
        $otherCompany = Company::create(['name' => 'Other']);
        $otherManager = User::factory()->create(['role' => 'company_manager', 'company_id' => $otherCompany->id]);
        $otherWorker = User::factory()->create(['role' => 'professional']);
        Professional::create(['user_id' => $otherWorker->id, 'name' => 'Other Worker', 'role' => 'cleaner', 'phone' => '+971500000002']);

        $this->actingAs($otherManager)->get(route('jobs.report.show', $booking))->assertForbidden();
        $this->actingAs($otherWorker)->get(route('jobs.report.pdf', $booking))->assertForbidden();
    }
}
