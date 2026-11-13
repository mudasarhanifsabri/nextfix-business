<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyRateCard;
use App\Services\OperationalNotifier;
use App\Support\RateCatalogue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RateCardController extends Controller
{
    public function admin(Request $request, Company $company): View
    {
        abort_unless($request->user()->role === 'nextfix_admin', 403);

        return view('admin.companies.rates', $this->viewData($company, $company->rateCard()->firstOrNew()));
    }

    public function save(Request $request, Company $company): RedirectResponse
    {
        abort_unless($request->user()->role === 'nextfix_admin', 403);
        $request->validate([
            'maintenance_inspection' => ['nullable', 'numeric', 'min:0'],
            'rates' => ['nullable', 'array'],
            'cancellation_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'transport_fee' => ['nullable', 'numeric', 'min:0'],
            'action' => ['required', 'in:draft,submit'],
        ]);

        $rates = [];
        foreach (RateCatalogue::codes() as $code) {
            $value = data_get($request->input('rates', []), $code);
            if ($value !== null && $value !== '') {
                abort_unless(is_numeric($value) && (float) $value >= 0, 422, 'Every rate must be a positive AED amount.');
                data_set($rates, $code, (int) round((float) $value * 100));
            }
        }

        $submit = $request->string('action')->toString() === 'submit';
        $card = $company->rateCard()->firstOrNew();
        $card->fill([
            'status' => $submit ? 'pending_approval' : 'draft',
            'maintenance_inspection_minor' => $request->filled('maintenance_inspection') ? (int) round((float) $request->input('maintenance_inspection') * 100) : null,
            'rates' => $rates,
            'terms' => [
                'cancellation_percent' => $request->filled('cancellation_percent') ? (float) $request->input('cancellation_percent') : null,
                'transport_fee_minor' => $request->filled('transport_fee') ? (int) round((float) $request->input('transport_fee') * 100) : null,
            ],
            'review_note' => null,
            'submitted_at' => $submit ? now() : null,
            'approved_at' => null,
            'review_requested_at' => null,
        ])->save();

        if ($submit) {
            app(OperationalNotifier::class)->rateCardReady($company, $card);
        }

        return back()->with('status', $submit ? 'Rate card sent to the company for approval.' : 'Rate card saved as draft.');
    }

    public function show(Request $request): View
    {
        abort_unless($request->user()->role === 'company_manager', 403);
        $company = $request->user()->company;

        return view('rates.show', $this->viewData($company, $company->rateCard()->firstOrNew()));
    }

    public function decision(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'company_manager', 403);
        $data = $request->validate(['decision' => ['required', 'in:approve,review'], 'review_note' => ['nullable', 'string', 'max:1500']]);
        $card = $request->user()->company->rateCard;
        abort_unless($card && $card->status === 'pending_approval', 422, 'This rate card is not awaiting approval.');

        if ($data['decision'] === 'approve') {
            $card->update(['status' => 'approved', 'approved_at' => now(), 'review_note' => null, 'review_requested_at' => null]);
            $message = 'Rate card approved. These prices are now active for bookings.';
            $title = 'Rate card approved';
            $level = 'success';
        } else {
            $request->validate(['review_note' => ['required', 'string', 'max:1500']]);
            $card->update(['status' => 'review_requested', 'review_note' => $data['review_note'], 'review_requested_at' => now(), 'approved_at' => null]);
            $message = 'Review requested. NextFix will revise the rate card.';
            $title = 'Rate review requested';
            $level = 'warning';
        }

        app(OperationalNotifier::class)->rateCardDecision(
            $request->user()->company,
            $card,
            $data['decision'],
            $data['review_note'] ?? null
        );

        return back()->with('status', $message);
    }

    private function viewData(Company $company, CompanyRateCard $card): array
    {
        return [
            'company' => $company,
            'rateCard' => $card,
            'unitTypes' => RateCatalogue::unitTypes(),
            'cleaningTypes' => RateCatalogue::cleaningTypes(),
            'sections' => RateCatalogue::sections(),
        ];
    }
}
