<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Professional;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_responses_include_browser_security_headers(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $policy = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
    }

    public function test_each_portal_has_a_route_level_role_boundary(): void
    {
        $company = Company::create(['name' => 'Security Company']);
        $manager = User::factory()->create(['role' => 'company_manager', 'company_id' => $company->id]);
        $admin = User::factory()->create(['role' => 'nextfix_admin']);
        $worker = User::factory()->create(['role' => 'professional']);
        Professional::create(['user_id' => $worker->id, 'name' => 'Security Worker', 'role' => 'cleaner', 'phone' => '+971500000901']);

        $this->actingAs($manager)->get(route('admin.integrations.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('worker.home'))->assertForbidden();
        $this->actingAs($admin)->get(route('bookings.index'))->assertForbidden();
        $this->actingAs($worker)->get(route('rates.show'))->assertForbidden();
    }

    public function test_notification_cannot_redirect_a_user_to_an_external_site(): void
    {
        $admin = User::factory()->create(['role' => 'nextfix_admin']);
        $admin->notifyNow(new AdminAlert('Unsafe destination', 'This link must stay inside NextFix.', 'https://attacker.example/phishing'));
        $notification = $admin->notifications()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.notifications.read', $notification))
            ->assertRedirect(route('admin.notifications.index'));
    }

    public function test_session_idle_lifetime_is_bounded(): void
    {
        $this->assertGreaterThanOrEqual(15, config('session.lifetime'));
        $this->assertLessThanOrEqual(240, config('session.lifetime'));
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
    }
}
