<?php

namespace App\Jobs;

use App\Models\DeploymentRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use Throwable;

class RunApprovedDeployment implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $deploymentRunId) {}

    public function handle(): void
    {
        $run = DeploymentRun::findOrFail($this->deploymentRunId);
        $lock = Cache::lock('nextfix:deployment', config('deployment.lock_seconds'));
        if (! $lock->get()) {
            $run->update(['status' => 'failed', 'stage' => 'lock', 'finished_at' => now(), 'log' => $run->log."\nDeployment lock unavailable."]);

            return;
        }

        try {
            throw_unless(preg_match('/^[a-f0-9]{40}$/', $run->release_id), new \RuntimeException('Queued Git commit is invalid.'));
            $script = config('deployment.script_path');
            throw_unless($script && is_file($script), new \RuntimeException('Restricted deployment script is unavailable.'));

            $run->update(['status' => 'running', 'stage' => 'preflight', 'started_at' => now()]);
            $process = new Process(['bash', $script, $run->release_id, $run->version, (string) $run->id]);
            $process->setEnv(array_merge($_SERVER, $_ENV, [
                'PATH' => '/opt/cpanel/composer/bin:/usr/local/cpanel/3rdparty/bin:/usr/local/bin:/usr/bin:/bin:'.($_SERVER['PATH'] ?? $_ENV['PATH'] ?? ''),
                'NEXTFIX_REPOSITORY' => (string) config('deployment.repository_path'),
                'NEXTFIX_GIT_REMOTE' => (string) config('deployment.remote', 'origin'),
                'NEXTFIX_BRANCH' => (string) config('deployment.branch', 'main'),
                'NEXTFIX_RELEASE_STATE' => (string) config('deployment.state_path'),
            ]));
            $process->setTimeout(config('deployment.timeout_seconds'));
            $process->run(function (string $type, string $output) use ($run): void {
                $clean = preg_replace('/(token|password|secret|key)\s*[=:]\s*\S+/i', '$1=[REDACTED]', $output);
                preg_match_all('/stage=([a-z_]+)/', $clean, $matches);
                $stage = $matches[1] ? end($matches[1]) : $run->stage;
                $run->update(['stage' => $stage, 'log' => mb_substr($run->log."\n".$clean, -12000)]);
            });
            throw_unless($process->isSuccessful(), new \RuntimeException('Deployment runner exited with code '.$process->getExitCode().'. Review the deployment log below.'));
            $run->update(['status' => 'success', 'stage' => 'complete', 'finished_at' => now()]);
        } catch (Throwable $error) {
            $run->update(['status' => 'failed', 'stage' => 'failed', 'finished_at' => now(), 'log' => mb_substr($run->log."\n".$error->getMessage(), -12000)]);
            throw $error;
        } finally {
            $lock->release();
        }
    }
}
