<?php

return [
    'installed_version' => env('NEXTFIX_VERSION', '1.0.0-dev'),
    'manifest_path' => env('NEXTFIX_RELEASE_MANIFEST', storage_path('app/releases/approved.json')),
    'script_path' => env('NEXTFIX_DEPLOY_SCRIPT'),
    'timeout_seconds' => (int) env('NEXTFIX_DEPLOY_TIMEOUT', 900),
    'lock_seconds' => (int) env('NEXTFIX_DEPLOY_LOCK', 1200),
];
