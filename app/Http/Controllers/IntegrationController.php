<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        ]);
    }
}
