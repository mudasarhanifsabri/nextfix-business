<?php

namespace App\Services;

use App\Models\AppTheme;
use Illuminate\Support\Facades\Schema;

class ThemeManager
{
    public const SCOPES = ['company' => 'Company app', 'worker' => 'Worker / Handyman app', 'admin' => 'Admin panel'];

    public const PRESETS = [
        'classic' => ['name' => 'Current / Classic', 'primary' => '#1267e8', 'secondary' => '#6758d4'],
        'colorful' => ['name' => 'Colorful / Photo', 'primary' => '#2455f5', 'secondary' => '#8851d5'],
        'premium' => ['name' => 'Premium / Teal', 'primary' => '#087f8c', 'secondary' => '#876233'],
    ];

    public function defaults(): array
    {
        return ['preset' => 'classic', 'appearance' => 'light', 'primary' => '#1267e8', 'secondary' => '#6758d4'];
    }

    public function configuration(string $scope): array
    {
        // Existing deployments remain available before the additive migration runs.
        if (! Schema::hasTable('app_themes')) {
            return $this->defaults();
        }

        $stored = AppTheme::query()->where('scope', $scope)->value('configuration');

        return $this->sanitize(is_array($stored) ? $stored : []) + ['sliders' => $this->slides(is_array($stored) ? $stored : [], $scope)];
    }

    public function sanitize(array $configuration): array
    {
        $defaults = $this->defaults();
        foreach (['preset', 'appearance', 'primary', 'secondary'] as $key) {
            $value = $configuration[$key] ?? null;
            $allowed = match ($key) {
                'preset' => is_string($value) && isset(self::PRESETS[$value]),
                'appearance' => in_array($value, ['light', 'dark', 'system'], true),
                default => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/D', $value),
            };
            if ($allowed) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    public function actions(string $scope): array
    {
        return match ($scope) {
            'admin' => ['dashboard' => 'Overview', 'admin.operations.index' => 'Operations', 'admin.companies.index' => 'Companies', 'admin.payments.index' => 'Payments'],
            'worker' => ['worker.home' => 'Home', 'worker.jobs.index' => 'My jobs', 'worker.profile.show' => 'Profile', 'admin.notifications.index' => 'Alerts'],
            default => ['dashboard' => 'Home', 'cleaning' => 'Book cleaning', 'maintenance' => 'Maintenance', 'bookings.index' => 'Bookings', 'units.index' => 'Units'],
        };
    }

    public function slides(array $configuration, string $scope): array
    {
        $defaults = match ($scope) {
            'worker' => [
                ['title' => 'Your skills. Their comfort.', 'caption' => 'Your next service is a tap away.', 'action' => 'worker.jobs.index', 'image' => null, 'position' => 1],
                ['title' => 'Ready for every stay.', 'caption' => 'Follow your checklist and capture the details.', 'action' => 'worker.jobs.index', 'image' => null, 'position' => 0],
                ['title' => 'Your team, connected.', 'caption' => 'Keep up with your latest job updates.', 'action' => 'admin.notifications.index', 'image' => null, 'position' => 2],
            ],
            'admin' => [
                ['title' => 'Every unit. Taken care of.', 'caption' => 'Keep your operations moving.', 'action' => 'admin.operations.index', 'image' => null, 'position' => 2],
                ['title' => 'The right team, on time.', 'caption' => 'Review assignments and service progress.', 'action' => 'admin.operations.index', 'image' => null, 'position' => 0],
                ['title' => 'Clear payments. Clear progress.', 'caption' => 'Review the latest payment activity.', 'action' => 'admin.payments.index', 'image' => null, 'position' => 1],
            ],
            default => [
                ['title' => 'Guest-ready. Every stay.', 'caption' => 'Trusted cleaning for your holiday homes.', 'action' => 'cleaning', 'image' => null, 'position' => 0],
                ['title' => 'Expert care for every unit.', 'caption' => 'Book a maintenance inspection.', 'action' => 'maintenance', 'image' => null, 'position' => 1],
                ['title' => 'Your portfolio, connected.', 'caption' => 'All your properties in one place.', 'action' => 'units.index', 'image' => null, 'position' => 2],
            ],
        };
        $slides = $configuration['sliders'] ?? [];
        foreach ($defaults as $index => &$slide) {
            $stored = is_array($slides[$index] ?? null) ? $slides[$index] : [];
            foreach (['title', 'caption'] as $key) {
                if (is_string($stored[$key] ?? null)) {
                    $slide[$key] = $stored[$key];
                }
            }
            if (is_string($stored['action'] ?? null) && isset($this->actions($scope)[$stored['action']])) {
                $slide['action'] = $stored['action'];
            }
            if (is_string($stored['image'] ?? null) && preg_match('~^themes/[a-zA-Z0-9/_-]+\\.(jpg|jpeg|png|webp)$~D', $stored['image'])) {
                $slide['image'] = $stored['image'];
            }
            $slide['order'] = max(0, min(2, (int) ($stored['order'] ?? $index)));
        }
        unset($slide);

        return $defaults;
    }

    public function slideUrl(string $action): string
    {
        return in_array($action, ['cleaning', 'maintenance'], true)
            ? route('bookings.create', $action) : route($action);
    }

    public function foreground(string $hex): string
    {
        $values = array_map(function ($channel) {
            $value = hexdec($channel) / 255;

            return $value <= .04045 ? $value / 12.92 : (($value + .055) / 1.055) ** 2.4;
        }, str_split(substr($hex, 1), 2));
        $luminance = .2126 * $values[0] + .7152 * $values[1] + .0722 * $values[2];

        return ($luminance + .05) / .05 > 1.05 / ($luminance + .05) ? '#000000' : '#ffffff';
    }
}
