<?php

namespace Tests\Feature;

use App\Models\AppTheme;
use App\Models\User;
use App\Services\ThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThemeManagerTest extends TestCase
{
    use RefreshDatabase;

    private function settings(array $overrides = []): array
    {
        return array_replace(['scope' => 'company', 'revision' => 0, 'preset' => 'colorful', 'appearance' => 'light', 'primary' => '#2455f5', 'secondary' => '#8851d5'], $overrides);
    }

    public function test_only_admin_can_view_publish_and_restore_themes(): void
    {
        $this->get(route('admin.themes.index'))->assertRedirect(route('login'));
        foreach (['company_manager', 'professional'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(route('admin.themes.index'))->assertForbidden();
            $this->post(route('admin.themes.publish'), $this->settings())->assertForbidden();
            $this->post(route('admin.themes.restore'), ['scope' => 'company', 'revision' => 0])->assertForbidden();
        }
        $this->assertSame(0, AppTheme::where('scope', 'company')->firstOrFail()->revision);
    }

    public function test_preview_never_publishes_and_classic_is_the_default(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'nextfix_admin']))
            ->get(route('admin.themes.index'))->assertOk()->assertSee('Live preview')->assertSee('Publish theme');
        $this->assertSame('classic', app(ThemeManager::class)->configuration('company')['preset']);
        $this->assertSame(0, AppTheme::where('scope', 'company')->firstOrFail()->revision);
    }

    public function test_publish_is_scoped_and_restore_is_reversible(): void
    {
        $admin = User::factory()->create(['role' => 'nextfix_admin']);
        $this->actingAs($admin)->post(route('admin.themes.publish'), $this->settings())
            ->assertRedirect(route('admin.themes.index', ['scope' => 'company']));
        $record = AppTheme::where('scope', 'company')->firstOrFail();
        $this->assertSame('colorful', $record->configuration['preset']);
        $this->assertSame('classic', $record->previous_configuration['preset']);
        $this->assertSame($admin->id, $record->updated_by);
        $this->assertSame('classic', app(ThemeManager::class)->configuration('worker')['preset']);
        $this->assertSame('classic', app(ThemeManager::class)->configuration('admin')['preset']);
        $this->post(route('admin.themes.restore'), ['scope' => 'company', 'revision' => 1])->assertRedirect();
        $record->refresh();
        $this->assertSame('classic', $record->configuration['preset']);
        $this->assertSame('colorful', $record->previous_configuration['preset']);
        $this->assertSame(2, $record->revision);
    }

    public function test_stale_publish_and_restore_do_not_overwrite_a_newer_theme(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'nextfix_admin']))
            ->post(route('admin.themes.publish'), $this->settings())->assertRedirect();
        $this->post(route('admin.themes.publish'), $this->settings(['preset' => 'premium']))->assertStatus(409);
        $this->post(route('admin.themes.restore'), ['scope' => 'company', 'revision' => 0])->assertStatus(409);
        $this->assertSame('colorful', app(ThemeManager::class)->configuration('company')['preset']);
    }

    public function test_css_injection_unknown_presets_and_unknown_scopes_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'nextfix_admin']))
            ->post(route('admin.themes.publish'), $this->settings([
                'primary' => '#fff; background:url(https://bad.test)',
                'preset' => 'unknown', 'scope' => 'provider', 'appearance' => 'script',
            ]))->assertSessionHasErrors(['primary', 'preset', 'scope', 'appearance']);
        $this->assertSame(0, AppTheme::where('scope', 'company')->firstOrFail()->revision);
    }

    public function test_worker_mode_is_separate_and_admin_navigation_contains_theme_manager(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'nextfix_admin']))
            ->post(route('admin.themes.publish'), $this->settings(['scope' => 'worker', 'appearance' => 'system']))->assertRedirect();
        $this->assertSame('system', app(ThemeManager::class)->configuration('worker')['appearance']);
        $this->get(route('admin.themes.index', ['scope' => 'worker']))->assertOk()
            ->assertSee('Worker / Handyman app')->assertSee('Quick actions')->assertSee('root.dataset.theme = "classic"', false);
    }

    public function test_restore_without_history_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'nextfix_admin']))
            ->post(route('admin.themes.restore'), ['scope' => 'company', 'revision' => 0])->assertStatus(409);
    }

    public function test_stored_configuration_is_sanitized_and_button_foreground_has_contrast(): void
    {
        $manager = app(ThemeManager::class);
        $this->assertSame('#1267e8', $manager->sanitize(['primary' => '</style>'])['primary']);
        $this->assertSame('classic', $manager->sanitize(['preset' => ['invalid']])['preset']);
        $this->assertSame('#ffffff', $manager->foreground('#000000'));
        $this->assertSame('#000000', $manager->foreground('#ffffff'));
    }

    public function test_banner_upload_caption_order_and_restore_preserve_the_previous_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'nextfix_admin']);
        $slides = app(ThemeManager::class)->slides([], 'company');
        $slides[0]['title'] = 'Fresh for every guest';
        $slides[0]['caption'] = 'Your own company banner';
        $slides[0]['order'] = 2;
        $slides[0]['image'] = UploadedFile::fake()->createWithContent('banner.png', file_get_contents(public_path('images/themes/service-scenes-v1.png')));
        $this->actingAs($admin)->post(route('admin.themes.publish'), $this->settings(['sliders' => $slides]))->assertRedirect();
        $stored = AppTheme::where('scope', 'company')->firstOrFail()->configuration;
        $path = $stored['sliders'][0]['image'];
        Storage::disk('public')->assertExists($path);
        $safe = app(ThemeManager::class)->configuration('company');
        $this->assertSame($path, $safe['sliders'][0]['image']);
        $this->assertSame('Fresh for every guest', $safe['sliders'][0]['title']);
        $this->assertSame(2, $safe['sliders'][0]['order']);
        $this->post(route('admin.themes.publish'), $this->settings(['revision' => 1]))->assertRedirect();
        $this->post(route('admin.themes.restore'), ['scope' => 'company', 'revision' => 2])->assertRedirect();
        Storage::disk('public')->assertExists($path);
        $this->assertSame($path, AppTheme::where('scope', 'company')->firstOrFail()->configuration['sliders'][0]['image']);
    }

    public function test_banner_destinations_and_image_types_are_allowlisted(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'nextfix_admin']));
        $slides = app(ThemeManager::class)->slides([], 'company');
        $slides[0]['action'] = 'admin.payments.index';
        $slides[0]['image'] = UploadedFile::fake()->createWithContent('bad.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->post(route('admin.themes.publish'), $this->settings(['sliders' => $slides]))->assertSessionHasErrors(['sliders.0.action', 'sliders.0.image']);
        $this->assertSame(0, AppTheme::where('scope', 'company')->firstOrFail()->revision);
    }

    public function test_corrupt_stored_banner_paths_and_destinations_are_not_rendered(): void
    {
        $slides = app(ThemeManager::class)->slides(['sliders' => [
            ['image' => '../../private/file.png', 'action' => 'https://bad.test'],
        ]], 'company');
        $this->assertNull($slides[0]['image']);
        $this->assertSame('cleaning', $slides[0]['action']);
    }
}
