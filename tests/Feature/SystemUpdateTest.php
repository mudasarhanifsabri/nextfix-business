<?php

namespace Tests\Feature;

use App\Jobs\RunApprovedDeployment;
use App\Models\DeploymentRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_cannot_access_system_updates(): void
    {
        $user = User::factory()->create(['role' => 'company_manager']);
        $this->actingAs($user)->get(route('admin.updates.index'))->assertForbidden();
    }

    public function test_admin_can_queue_only_the_approved_release_after_reauthentication(): void
    {
        Queue::fake();
        Storage::fake('local');
        $manifest = storage_path('framework/testing/approved-release.json');
        file_put_contents($manifest, json_encode([
            'release_id' => 'release-101', 'version' => '1.0.1',
            'commit' => str_repeat('a', 40), 'notes' => 'Test release.',
        ], JSON_THROW_ON_ERROR));
        $script = storage_path('framework/testing/deploy-runner');
        file_put_contents($script, '#!/bin/sh');
        config(['deployment.manifest_path' => $manifest, 'deployment.script_path' => $script]);
        $admin = User::factory()->create(['role' => 'nextfix_admin', 'password' => 'password']);

        $this->actingAs($admin)->post(route('admin.updates.store'), ['release_id' => 'release-101', 'password' => 'password'])
            ->assertRedirect(route('admin.updates.index'));

        $this->assertDatabaseHas('deployment_runs', ['release_id' => 'release-101', 'status' => 'queued']);
        Queue::assertPushed(RunApprovedDeployment::class, fn ($job) => DeploymentRun::find($job->deploymentRunId)?->release_id === 'release-101');
    }
}
