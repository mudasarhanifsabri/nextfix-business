<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->role === 'nextfix_admin', 403);

        return view('admin.integrations', [
            'smtpReady' => config('mail.default') === 'smtp' && filled(config('mail.mailers.smtp.host')) && filled(config('mail.mailers.smtp.username')),
            'smtpHost' => config('mail.mailers.smtp.host'),
            'smtpFrom' => config('mail.from.address'),
            'nomodReady' => filled(config('services.nomod.api_key')) && filled(config('services.nomod.base_url')),
            'nomodMode' => config('services.nomod.mode', 'disabled'),
            'smtpPort' => config('mail.mailers.smtp.port', 587),
            'smtpScheme' => config('mail.mailers.smtp.scheme', 'tls'),
            'smtpUsername' => config('mail.mailers.smtp.username'),
            'smtpFromName' => config('mail.from.name'),
            'smtpSecretSaved' => filled(config('mail.mailers.smtp.password')),
            'nomodBaseUrl' => config('services.nomod.base_url', 'https://api.nomod.com'),
            'nomodSecretSaved' => filled(config('services.nomod.api_key')),
        ]);
    }

    public function smtp(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'password_confirmation_admin' => ['required', 'current_password'],
            'host' => ['required', 'string', 'max:190'], 'port' => ['required', 'integer', 'between:1,65535'],
            'scheme' => ['required', Rule::in(['tls', 'ssl'])], 'username' => ['required', 'string', 'max:190'],
            'smtp_password' => ['nullable', 'string', 'max:500'], 'from_address' => ['required', 'email', 'max:190'],
            'from_name' => ['required', 'string', 'max:120'],
        ]);
        foreach (['host', 'port', 'scheme', 'username', 'from_address', 'from_name'] as $key) {
            IntegrationSetting::put('smtp.'.$key, $data[$key]);
        }
        if (filled($data['smtp_password'] ?? null)) {
            IntegrationSetting::put('smtp.password', $data['smtp_password']);
        }

        return back()->with('status', 'SMTP settings saved securely.');
    }

    public function nomod(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'password_confirmation_admin' => ['required', 'current_password'],
            'mode' => ['required', Rule::in(['disabled', 'test', 'live'])],
            'base_url' => ['required', 'url', 'max:255'], 'api_key' => ['nullable', 'string', 'max:500'],
            'webhook_secret' => ['nullable', 'string', 'max:500'],
        ]);
        IntegrationSetting::put('nomod.mode', $data['mode']);
        IntegrationSetting::put('nomod.base_url', rtrim($data['base_url'], '/'));
        if (filled($data['api_key'] ?? null)) IntegrationSetting::put('nomod.api_key', $data['api_key']);
        if (filled($data['webhook_secret'] ?? null)) IntegrationSetting::put('nomod.webhook_secret', $data['webhook_secret']);

        return back()->with('status', 'Nomod settings saved securely.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'nextfix_admin', 403);
    }
}
