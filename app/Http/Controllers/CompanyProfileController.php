<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function show(Request $request): View
    {
        abort_unless($request->user()->role === 'company_manager', 403);
        return view('company.profile', ['company' => $request->user()->company]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'company_manager', 403);
        $company = $request->user()->company;
        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', Rule::unique('companies')->ignore($company)],
            'billing_email' => ['nullable', 'email'],
            'billing_address' => ['required', 'string', 'max:1000'],
        ]);
        $company->update($data);
        $request->user()->update(['name' => $data['contact_name']]);
        return back()->with('status', 'Company profile updated.');
    }
}
