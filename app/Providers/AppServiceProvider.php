<?php

namespace App\Providers;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            if (! Schema::hasTable('integration_settings')) {
                return;
            }
            $settings = IntegrationSetting::values();
            if (filled($settings['smtp.host'] ?? null)) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $settings['smtp.host'],
                    'mail.mailers.smtp.port' => (int) ($settings['smtp.port'] ?? 587),
                    'mail.mailers.smtp.scheme' => $settings['smtp.scheme'] ?? 'tls',
                    'mail.mailers.smtp.username' => $settings['smtp.username'] ?? null,
                    'mail.mailers.smtp.password' => $settings['smtp.password'] ?? null,
                    'mail.from.address' => $settings['smtp.from_address'] ?? config('mail.from.address'),
                    'mail.from.name' => $settings['smtp.from_name'] ?? config('mail.from.name'),
                ]);
            }
            config([
                'services.nomod.mode' => $settings['nomod.mode'] ?? config('services.nomod.mode'),
                'services.nomod.base_url' => $settings['nomod.base_url'] ?? config('services.nomod.base_url'),
                'services.nomod.api_key' => $settings['nomod.api_key'] ?? config('services.nomod.api_key'),
                'services.nomod.webhook_secret' => $settings['nomod.webhook_secret'] ?? config('services.nomod.webhook_secret'),
            ]);
        } catch (Throwable) {
            // Keep the application available during first deployment or key recovery.
        }
    }
}
