<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class LatestGitRelease
{
    public function inspect(bool $fetch = false): array
    {
        $repository = config('deployment.repository_path');
        $remote = config('deployment.remote', 'origin');
        $branch = config('deployment.branch', 'main');

        throw_unless($repository && is_dir($repository.'/.git'), new RuntimeException('The server Git repository is not configured.'));

        if ($fetch) {
            $this->run(['git', '-C', $repository, 'fetch', '--quiet', $remote, $branch]);
        }

        $commit = strtolower(trim($this->run(['git', '-C', $repository, 'rev-parse', "{$remote}/{$branch}"])));
        throw_unless(preg_match('/^[a-f0-9]{40}$/', $commit), new RuntimeException('Git returned an invalid commit.'));

        $timestamp = (int) trim($this->run(['git', '-C', $repository, 'show', '-s', '--format=%ct', $commit]));
        $notes = trim($this->run(['git', '-C', $repository, 'show', '-s', '--format=%s', $commit]));
        $installed = $this->installed();

        return [
            'release_id' => $commit,
            'commit' => $commit,
            'version' => 'main-'.CarbonImmutable::createFromTimestamp($timestamp, config('app.timezone'))->format('Ymd-His').'-'.substr($commit, 0, 7),
            'notes' => $notes,
            'branch' => $branch,
            'installed_commit' => $installed['commit'] ?? null,
            'update_available' => ! isset($installed['commit']) || ! hash_equals($installed['commit'], $commit),
        ];
    }

    public function installed(): array
    {
        $path = config('deployment.state_path');
        if (! $path || ! File::isFile($path)) {
            return ['version' => config('deployment.installed_version'), 'commit' => null];
        }

        $state = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

        return is_array($state) ? $state : [];
    }

    protected function run(array $command): string
    {
        $process = new Process($command);
        $process->setTimeout((int) config('deployment.git_timeout_seconds', 60));
        $process->run();
        throw_unless($process->isSuccessful(), new RuntimeException('Unable to read the latest GitHub release: '.trim($process->getErrorOutput())));

        return $process->getOutput();
    }
}
