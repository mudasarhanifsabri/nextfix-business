<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaInstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_and_worker_receive_their_own_install_manifest(): void
    {
        $company = User::factory()->create(['role' => 'company_manager']);
        $admin = User::factory()->create(['role' => 'nextfix_admin']);
        $worker = User::factory()->create(['role' => 'professional']);
        Professional::create(['user_id' => $worker->id, 'name' => 'PWA Worker', 'role' => 'maintenance', 'phone' => '+971500000111']);

        $this->actingAs($company)->get(route('dashboard'))->assertOk()->assertSee('/manifest-company.webmanifest', false)->assertSee('apple-touch-icon', false);
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('/manifest-admin.webmanifest', false)->assertSee('data-install-app', false);
        $this->actingAs($worker)->get(route('worker.jobs.index'))->assertOk()->assertSee('/manifest-worker.webmanifest', false)->assertSee('data-install-app', false);
    }

    public function test_all_manifests_have_required_android_and_ios_assets(): void
    {
        foreach (['company', 'admin', 'worker'] as $app) {
            $manifest = json_decode(file_get_contents(public_path("manifest-{$app}.webmanifest")), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('standalone', $manifest['display']);
            $this->assertNotEmpty($manifest['id']);
            $this->assertNotEmpty($manifest['start_url']);
            $this->assertCount(3, $manifest['icons']);
        }
        foreach (['icon-192.png', 'icon-512.png', 'icon-maskable-512.png', 'apple-touch-icon.png'] as $icon) {
            $this->assertFileExists(public_path("images/pwa/{$icon}"));
        }
    }
}
