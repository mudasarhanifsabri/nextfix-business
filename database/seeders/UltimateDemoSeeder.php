<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UltimateDemoSeeder extends Seeder
{
    public function run(): void
    {
        $names = ['Palmstay Holiday Homes', 'Blue Horizon Stays', 'Marina Key Vacation Homes', 'Urban Nest Hospitality', 'Golden Dunes Properties', 'Vista Luxe Rentals', 'Downtown Keys', 'Emirates Living Management'];
        $admin = User::where('role', 'nextfix_admin')->first();

        foreach ($names as $index => $name) {
            $slug = Str::slug($name);
            $company = Company::updateOrCreate(['name' => $name], [
                'legal_name' => $name.' LLC', 'trade_license_number' => 'DED-'.(810000 + $index), 'trn' => '100'.str_pad((string) $index, 12, '0'),
                'industry' => $index % 2 ? 'Property Management' : 'Holiday Homes', 'contact_name' => ['Maya Khan', 'Omar Ali', 'Sara Noor', 'Daniel George'][$index % 4],
                'email' => 'manager@'.$slug.'.demo', 'phone' => '+971 50 '.str_pad((string) (5500000 + $index), 7, '0'), 'billing_email' => 'accounts@'.$slug.'.demo',
                'billing_address' => ($index % 2 ? 'Business Bay' : 'Dubai Marina').', Dubai', 'city' => 'Dubai', 'country' => 'United Arab Emirates', 'payment_terms' => 30,
                'status' => $index === 7 ? 'onboarding' : 'active',
            ]);
            User::updateOrCreate(['email' => 'manager'.$index.'@nextfix.demo'], ['company_id' => $company->id, 'name' => $company->contact_name, 'password' => Hash::make('Demo12345!'), 'role' => 'company_manager', 'email_verified_at' => now()]);

            for ($u = 1; $u <= 4; $u++) {
                $unit = Unit::updateOrCreate(['company_id' => $company->id, 'building' => ['Marina Gate', 'Burj Vista', 'Creek Horizon', 'Palm Tower'][$u - 1], 'unit_number' => ($index + 2).str_pad((string) $u, 2, '0')], [
                    'bedrooms' => ($u % 3) + 1, 'size_sqft' => 700 + ($u * 190), 'address' => ['Dubai Marina', 'Downtown Dubai', 'Dubai Creek Harbour', 'Palm Jumeirah'][$u - 1].', Dubai', 'is_active' => true,
                ]);
                for ($b = 1; $b <= 3; $b++) {
                    Booking::updateOrCreate(['reference' => sprintf('NFB-%02d%02d-%02d%02d', $index + 1, $u, $b, 26)], [
                        'company_id' => $company->id, 'unit_id' => $unit->id, 'service_type' => $b === 3 ? 'maintenance' : 'cleaning',
                        'service_name' => $b === 3 ? 'AC inspection' : ($b === 2 ? 'Deep cleaning' : 'Checkout cleaning'),
                        'scheduled_at' => now()->addDays(($index * 2) + $u + $b), 'status' => ['confirmed', 'assigned', 'completed'][$b - 1],
                        'payment_status' => $b === 3 ? 'pending' : 'paid', 'amount_minor' => 18000 + ($u * 6500), 'currency' => 'AED',
                    ]);
                }
            }

            for ($n = 1; $n <= 3; $n++) {
                $subtotal = 75000 + ($index * 5000) + ($n * 10000);
                $tax = (int) round($subtotal * .05);
                $total = $subtotal + $tax;
                $invoice = Invoice::updateOrCreate(['number' => sprintf('INV-2026-%04d', ($index * 3) + $n)], [
                    'company_id' => $company->id, 'public_token' => hash('sha256', $slug.$n), 'issued_at' => now()->subDays(40 - ($n * 10)), 'due_at' => now()->subDays(10 - ($n * 8)),
                    'status' => ['paid', 'overdue', 'sent'][$n - 1], 'line_items' => [['description' => 'Managed cleaning and maintenance services', 'quantity' => 1, 'unit_price_minor' => $subtotal]],
                    'subtotal_minor' => $subtotal, 'tax_minor' => $tax, 'amount_minor' => $total, 'currency' => 'AED', 'notes' => 'Thank you for your business.',
                ]);
                if ($n === 1) {
                    Payment::updateOrCreate(['reference' => sprintf('PAY-%02d-%02d', $index + 1, $n)], ['company_id' => $company->id, 'invoice_id' => $invoice->id, 'method' => $index % 2 ? 'bank_transfer' : 'card', 'status' => 'completed', 'amount_minor' => $total, 'currency' => 'AED', 'paid_at' => now()->subDays(15), 'gateway_reference' => 'TXN'.str_pad((string) $invoice->id, 8, '0')]);
                }
            }
            CompanyDocument::updateOrCreate(['company_id' => $company->id, 'type' => 'trade_license'], ['name' => 'Trade License', 'expires_at' => now()->addMonths($index + 1)]);
            CompanyDocument::updateOrCreate(['company_id' => $company->id, 'type' => 'vat_certificate'], ['name' => 'VAT Certificate']);
        }

        if ($admin && $admin->notifications()->count() === 0) {
            $admin->notifyNow(new AdminAlert('Payment received', 'AED 892.50 received from Palmstay Holiday Homes.', route('admin.payments.index'), 'success'));
            $admin->notifyNow(new AdminAlert('Invoice overdue', 'Invoice INV-2026-0002 is now overdue.', route('admin.invoices.index'), 'warning'));
            $admin->notifyNow(new AdminAlert('Document expires soon', 'A company trade license expires within 30 days.', route('admin.companies.index'), 'warning'));
        }
    }
}
