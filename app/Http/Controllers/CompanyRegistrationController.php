<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class CompanyRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'in:Holiday Homes,Property Management,Hospitality,Real Estate,Other'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:companies,email'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        [$company, $user] = DB::transaction(function () use ($data): array {
            $company = Company::create([
                'name' => $data['company_name'], 'legal_name' => $data['company_name'], 'industry' => $data['industry'],
                'contact_name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
                'billing_email' => $data['email'], 'billing_address' => 'To be completed', 'city' => 'Dubai',
                'country' => 'United Arab Emirates', 'payment_terms' => 30, 'status' => 'onboarding',
            ]);
            $user = User::create(['company_id' => $company->id, 'name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'role' => 'company_manager', 'email_verified_at' => now()]);

            return [$company, $user];
        });

        Notification::send(User::where('role', 'nextfix_admin')->get(), new AdminAlert('New company registration', $company->name.' registered and is ready for onboarding.', route('admin.companies.show', $company), 'info'));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Welcome to NextFix. Complete your units while our team prepares your rate card.');
    }
}
