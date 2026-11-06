<?php

namespace App\Http\Controllers;

use App\Jobs\RunApprovedDeployment;
use App\Models\DeploymentRun;
use App\Services\LatestGitRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemUpdateController extends Controller
{
    public function index(Request $request, LatestGitRelease $releases): View
    {
        $this->authorizeAdmin($request);

        try {
            $release = $releases->inspect();
            $releaseError = null;
        } catch (\Throwable $error) {
            $release = null;
            $releaseError = $error->getMessage();
        }
        $installed = $releases->installed();

        return view('admin.updates', [
            'installedVersion' => $installed['version'] ?? config('deployment.installed_version'),
            'release' => $release,
            'releaseError' => $releaseError,
            'scriptReady' => filled(config('deployment.script_path')) && is_file(config('deployment.script_path')),
            'runs' => DeploymentRun::with('user')->latest()->limit(20)->get(),
        ]);
    }

    public function store(Request $request, LatestGitRelease $releases): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $request->validate(['password' => ['required', 'current_password']]);
        abort_unless(filled(config('deployment.script_path')) && is_file(config('deployment.script_path')), 503, 'The restricted deployment runner is not configured.');

        $active = DeploymentRun::whereIn('status', ['queued', 'running'])->exists();
        abort_if($active, 409, 'Another deployment is already queued or running.');

        $release = $releases->inspect(fetch: true);
        abort_unless($release['update_available'], 422, 'NextFix is already up to date.');

        $run = DeploymentRun::create([
            'user_id' => $request->user()->id,
            'release_id' => $release['release_id'],
            'version' => $release['version'],
            'status' => 'queued',
            'stage' => 'queued',
            'log' => 'Deployment requested by an authorized administrator.',
        ]);
        RunApprovedDeployment::dispatch($run->id);

        return redirect()->route('admin.updates.index')->with('status', 'GitHub commit '.substr($run->release_id, 0, 7).' queued for deployment.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'nextfix_admin', 403);
    }
}
