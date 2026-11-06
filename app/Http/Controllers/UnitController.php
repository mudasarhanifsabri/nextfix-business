<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(Request $request): View
    {
        return view('units.index', ['units' => Unit::where('company_id', $request->user()->company_id)->orderBy('building')->paginate(12)]);
    }

    public function create(): View
    {
        return view('units.form', ['unit' => new Unit]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $request->user()->company->units()->create($data);

        return redirect()->route('units.index')->with('status', 'Unit added successfully.');
    }

    public function edit(Request $request, Unit $unit): View
    {
        $this->ensureOwner($request, $unit);

        return view('units.form', compact('unit'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $this->ensureOwner($request, $unit);
        $unit->update($this->validated($request));

        return redirect()->route('units.index')->with('status', 'Unit updated successfully.');
    }

    private function ensureOwner(Request $request, Unit $unit): void
    {
        abort_unless($unit->company_id === $request->user()->company_id, 404);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'building' => ['required', 'string', 'max:120'], 'unit_number' => ['required', 'string', 'max:40'],
            'bedrooms' => ['required', 'integer', 'between:0,20'], 'size_sqft' => ['nullable', 'integer', 'min:1'],
            'address' => ['required', 'string', 'max:1000'], 'access_notes' => ['nullable', 'string', 'max:2000'],
            'parking_notes' => ['nullable', 'string', 'max:1000'], 'is_active' => ['boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
