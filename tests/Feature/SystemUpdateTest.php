<?php

namespace Tests\Feature;

use App\Jobs\RunApprovedDeployment;
use App\Models\DeploymentRun;
use App\Models\User;
use App\Services\LatestGitRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SystemUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_cannot_access_system_updates(): void
    {
        $user = User::factory()->create(['role' => 'company_manager']);
        $this->actingAs($user)->get(route('admin.updates.index'))->assertForbidden();
    }

    public function test_admin_can_queue_the_latest_github_commit_after_reauthentication(): void
    {
        Queue::fake();
        $script = storage_path('framework/testing/deploy-runner');
        file_put_contents($script, '#!/bin/sh');
        config(['deployment.script_path' => $script]);
        $commit = str_repeat('a', 40);
        $release = [
            'release_id' => $commit, 'commit' => $commit, 'version' => 'main-20261106-aaaaaaa',
            'notes' => 'Test release.', 'branch' => 'main', 'installed_commit' => null, 'update_available' => true,
        ];
        $this->mock(LatestGitRelease::class)
            ->shouldReceive('inspect')->once()->with(true)->andReturn($release);
        $admin = User::factory()->create(['role' => 'nextfix_admin', 'password' => 'password']);

        $this->actingAs($admin)->post(route('admin.updates.store'), ['password' => 'password'])
            ->assertRedirect(route('admin.updates.index'));

        $this->assertDatabaseHas('deployment_runs', ['release_id' => $commit, 'status' => 'queued']);
        Queue::assertPushed(RunApprovedDeployment::class, fn ($job) => DeploymentRun::find($job->deploymentRunId)?->release_id === $commit);
    }

    public function test_updates_page_survives_a_partial_release_state_file(): void
    {
        $state = storage_path('framework/testing/partial-release-state.json');
        file_put_contents($state, '{"version":');
        config(['deployment.state_path' => $state]);
        $this->assertSame(
            ['version' => config('deployment.installed_version'), 'commit' => null],
            (new LatestGitRelease)->installed(),
        );

        $this->mock(LatestGitRelease::class)
            ->shouldReceive('inspect')->once()->with(true)->andThrow(new \RuntimeException('Git is temporarily busy'))
            ->shouldReceive('installed')->once()->andReturn(['version' => '1.0.0-dev', 'commit' => null]);

        $admin = User::factory()->create(['role' => 'nextfix_admin']);

        $this->actingAs($admin)->get(route('admin.updates.index'))
            ->assertOk()
            ->assertSee('GitHub check unavailable')
            ->assertSee('Git is temporarily busy');
    }
}
