<?php

return [
    'installed_version' => env('NEXTFIX_VERSION', '1.0.0-dev'),
    'repository_path' => env('NEXTFIX_REPOSITORY'),
    'remote' => env('NEXTFIX_GIT_REMOTE', 'origin'),
    'branch' => env('NEXTFIX_BRANCH', 'main'),
    'state_path' => env('NEXTFIX_RELEASE_STATE', storage_path('app/releases/current.json')),
    'script_path' => env('NEXTFIX_DEPLOY_SCRIPT'),
    'git_timeout_seconds' => (int) env('NEXTFIX_GIT_TIMEOUT', 60),
    'timeout_seconds' => (int) env('NEXTFIX_DEPLOY_TIMEOUT', 900),
    'lock_seconds' => (int) env('NEXTFIX_DEPLOY_LOCK', 1200),
];
