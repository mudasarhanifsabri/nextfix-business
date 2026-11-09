<?php

namespace App\Http\Controllers;

use App\Models\AppTheme;
use App\Services\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class ThemeController extends Controller
{
    public function index(Request $request, ThemeManager $themes)
    {
        abort_unless($request->user()?->role === 'nextfix_admin', 403);
        $scope = $request->query('scope', 'company');
        abort_unless(is_string($scope) && array_key_exists($scope, ThemeManager::SCOPES), 404);
        $record = AppTheme::query()->where('scope', $scope)->firstOrFail();

        return view('admin.themes.index', [
            'scope' => $scope, 'record' => $record,
            'configuration' => $themes->sanitize($record->configuration),
            'scopes' => ThemeManager::SCOPES, 'presets' => ThemeManager::PRESETS,
            'slides' => $themes->slides($record->configuration, $scope), 'actions' => $themes->actions($scope),
        ]);
    }

    public function publish(Request $request, ThemeManager $themes)
    {
        abort_unless($request->user()?->role === 'nextfix_admin', 403);
        $data = $request->validate([
            'scope' => ['required', Rule::in(array_keys(ThemeManager::SCOPES))],
            'revision' => ['required', 'integer', 'min:0'],
            'preset' => ['required', Rule::in(array_keys(ThemeManager::PRESETS))],
            'appearance' => ['required', Rule::in(['light', 'dark', 'system'])],
            'primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/D'],
            'secondary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/D'],
            'sliders' => ['sometimes', 'array:0,1,2', 'size:3'],
            'sliders.*.title' => ['required_with:sliders', 'string', 'max:70'],
            'sliders.*.caption' => ['nullable', 'string', 'max:150'],
            'sliders.*.action' => ['required_with:sliders', Rule::in(array_keys($themes->actions(is_string($request->input('scope')) ? $request->input('scope') : '')))],
            'sliders.*.order' => ['required_with:sliders', 'integer', 'between:0,2'],
            'sliders.*.remove_image' => ['sometimes', 'boolean'],
            'sliders.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=480,min_height=240,max_width=6000,max_height=6000'],
        ]);

        $uploaded = [];
        try {
            DB::transaction(function () use ($request, $data, $themes, &$uploaded): void {
                $record = AppTheme::query()->where('scope', $data['scope'])->lockForUpdate()->firstOrFail();
                abort_unless($record->revision === (int) $data['revision'], 409, 'The theme changed in another tab. Reload before publishing.');
                $configuration = array_intersect_key($data, array_flip(['preset', 'appearance', 'primary', 'secondary']));
                $sliders = $themes->slides($record->configuration, $data['scope']);
                if (isset($data['sliders'])) {
                    foreach ($data['sliders'] as $index => $slide) {
                        $slide['caption'] = $slide['caption'] ?? '';
                        $sliders[$index] = array_replace($sliders[$index], array_intersect_key($slide, array_flip(['title', 'caption', 'action', 'order'])));
                        if ($slide['remove_image'] ?? false) {
                            $sliders[$index]['image'] = null;
                        }
                        if ($request->hasFile('sliders.'.$index.'.image')) {
                            $path = $request->file('sliders.'.$index.'.image')->store('themes', 'public');
                            $uploaded[] = $path;
                            $sliders[$index]['image'] = $path;
                        }
                    }
                }
                $configuration['sliders'] = $sliders;
                $record->update([
                    'previous_configuration' => $record->configuration,
                    'configuration' => $configuration,
                    'revision' => $record->revision + 1, 'updated_by' => $request->user()->id,
                ]);
            });
        } catch (Throwable $error) {
            Storage::disk('public')->delete($uploaded);
            throw $error;
        }

        return redirect()->route('admin.themes.index', ['scope' => $data['scope']])->with('status', 'Theme published. It will appear when users next open or refresh a screen.');
    }

    public function restore(Request $request)
    {
        abort_unless($request->user()?->role === 'nextfix_admin', 403);
        $data = $request->validate([
            'scope' => ['required', Rule::in(array_keys(ThemeManager::SCOPES))],
            'revision' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $record = AppTheme::query()->where('scope', $data['scope'])->lockForUpdate()->firstOrFail();
            abort_unless($record->revision === (int) $data['revision'], 409, 'The theme changed in another tab. Reload before restoring.');
            abort_if($record->previous_configuration === null, 409, 'No previous theme is available.');
            $current = $record->configuration;
            $record->update([
                'configuration' => $record->previous_configuration, 'previous_configuration' => $current,
                'revision' => $record->revision + 1, 'updated_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('admin.themes.index', ['scope' => $data['scope']])->with('status', 'Previous theme restored.');
    }
}
