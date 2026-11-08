<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfessionalController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $professionals = Professional::query()
            ->withCount(['bookings as upcoming_count' => fn ($query) => $query->where('scheduled_at', '>=', now())])
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')->paginate(12)->withQueryString();

        return view('admin.professionals.index', compact('professionals'));
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.professionals.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::in(['cleaner', 'maintenance'])],
            'email' => ['required', 'email', 'max:190', 'unique:professionals,email', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'phone' => ['required', 'string', 'max:40'],
            'status' => ['required', Rule::in(['available', 'busy', 'off_duty'])],
            'skills' => ['nullable', 'array'], 'skills.*' => ['string', 'max:60'],
            'working_days' => ['nullable', 'array'], 'working_days.*' => ['string', Rule::in(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $professional = DB::transaction(function () use ($data): Professional {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'role' => 'professional', 'email_verified_at' => now()]);
            unset($data['password'], $data['password_confirmation']);

            return Professional::create($data + ['user_id' => $user->id]);
        });

        return redirect()->route('admin.professionals.show', $professional)->with('status', 'Professional profile created.');
    }

    public function show(Request $request, Professional $professional): View
    {
        $this->authorizeAdmin($request);
        $professional->load(['bookings' => fn ($query) => $query->with(['company', 'unit'])->latest('scheduled_at')->limit(10)]);

        return view('admin.professionals.show', compact('professional'));
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'nextfix_admin', 403);
    }
}
