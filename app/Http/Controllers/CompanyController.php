<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CompanyController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'nextfix_admin', 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $query = Company::query()->withCount(['units', 'bookings', 'users'])->latest();
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('contact_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        return view('admin.companies.index', ['companies' => $query->paginate(15)->withQueryString()]);
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'trade_license_number' => ['nullable', 'string', 'max:100'],
            'trn' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:companies,email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'billing_address' => ['required', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'payment_terms' => ['required', 'integer', 'in:0,7,15,30,45,60'],
            'status' => ['nullable', 'string', 'in:active,onboarding'],
            'manager_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $company = DB::transaction(function () use ($data): Company {
            $company = Company::create(collect($data)->except(['manager_password', 'manager_password_confirmation'])->all());
            User::create([
                'company_id' => $company->id,
                'name' => $data['contact_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['manager_password']),
                'role' => 'company_manager',
                'email_verified_at' => now(),
            ]);

            return $company;
        });

        return redirect()->route('admin.companies.show', $company)->with('status', 'Company registered successfully.');
    }

    public function show(Request $request, Company $company): View
    {
        $this->authorizeAdmin($request);
        $company->loadCount(['units', 'bookings', 'users', 'invoices', 'documents'])
            ->load(['users', 'units', 'bookings' => fn ($q) => $q->latest('scheduled_at')->limit(10), 'invoices' => fn ($q) => $q->latest()->limit(10), 'documents' => fn ($q) => $q->latest()->limit(10)]);

        return view('admin.companies.show', compact('company'));
    }

}
