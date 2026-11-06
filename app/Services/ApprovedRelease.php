<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class ApprovedRelease
{
    public function read(): ?array
    {
        $path = config('deployment.manifest_path');
        if (! $path || ! File::isFile($path)) {
            return null;
        }

        $manifest = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        foreach (['release_id', 'version', 'commit', 'notes'] as $key) {
            if (! isset($manifest[$key]) || ! is_string($manifest[$key])) {
                throw new RuntimeException("Approved release manifest is missing {$key}.");
            }
        }

        if (! preg_match('/^[a-f0-9]{40}$/', $manifest['commit'])) {
            throw new RuntimeException('Approved release commit must be a full immutable SHA.');
        }

        return $manifest;
    }
}
