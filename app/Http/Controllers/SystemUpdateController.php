<?php

namespace App\Http\Controllers;

use App\Jobs\RunApprovedDeployment;
use App\Models\DeploymentRun;
use App\Services\ApprovedRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemUpdateController extends Controller
{
    public function index(Request $request, ApprovedRelease $release): View
    {
        $this->authorizeAdmin($request);

        return view('admin.updates', [
            'installedVersion' => config('deployment.installed_version'),
            'release' => $release->read(),
            'scriptReady' => filled(config('deployment.script_path')) && is_file(config('deployment.script_path')),
            'runs' => DeploymentRun::with('user')->latest()->limit(20)->get(),
        ]);
    }

    public function store(Request $request, ApprovedRelease $approvedRelease): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['release_id' => ['required', 'string'], 'password' => ['required', 'current_password']]);
        $release = $approvedRelease->read();
        abort_unless($release && hash_equals($release['release_id'], $data['release_id']), 422, 'The approved release changed. Refresh and review it again.');
        abort_unless(filled(config('deployment.script_path')) && is_file(config('deployment.script_path')), 503, 'The restricted deployment runner is not configured.');

        $active = DeploymentRun::whereIn('status', ['queued', 'running'])->exists();
        abort_if($active, 409, 'Another deployment is already queued or running.');

        $run = DeploymentRun::create([
            'user_id' => $request->user()->id,
            'release_id' => $release['release_id'],
            'version' => $release['version'],
            'status' => 'queued',
            'stage' => 'queued',
            'log' => 'Deployment requested by an authorized administrator.',
        ]);
        RunApprovedDeployment::dispatch($run->id);

        return redirect()->route('admin.updates.index')->with('status', "Release {$run->version} queued for deployment.");
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'nextfix_admin', 403);
    }
}
