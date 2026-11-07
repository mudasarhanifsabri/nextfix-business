<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkerProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('worker.profile', ['professional' => $this->professional($request)]);
    }

    public function update(Request $request): RedirectResponse
    {
        $professional = $this->professional($request);
        $data = $request->validate(['phone' => ['required', 'string', 'max:40'], 'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        $updates = ['phone' => $data['phone']];
        if ($request->hasFile('photo')) {
            if ($professional->profile_photo_path) Storage::disk('public')->delete($professional->profile_photo_path);
            $updates['profile_photo_path'] = $request->file('photo')->store('worker-profiles', 'public');
        }
        $professional->update($updates);
        return back()->with('status', 'Profile updated.');
    }

    public function photo(Request $request): BinaryFileResponse
    {
        $professional = $this->professional($request);
        abort_unless($professional->profile_photo_path && Storage::disk('public')->exists($professional->profile_photo_path), 404);
        return response()->file(Storage::disk('public')->path($professional->profile_photo_path), ['Cache-Control' => 'private, max-age=3600']);
    }

    private function professional(Request $request)
    {
        abort_unless($request->user()?->role === 'professional' && $request->user()->professional, 403);
        return $request->user()->professional;
    }
}
