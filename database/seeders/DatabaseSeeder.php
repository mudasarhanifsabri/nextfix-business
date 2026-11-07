<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::create(['name' => 'Palmstay Holiday Homes', 'contact_name' => 'Maya Khan', 'phone' => '+971 50 555 0188', 'billing_address' => 'Dubai, United Arab Emirates']);
        $manager = User::create(['company_id' => $company->id, 'name' => 'Maya Khan', 'email' => 'manager@demo.test', 'password' => Hash::make('password'), 'role' => 'company_manager', 'email_verified_at' => now()]);
        User::create(['name' => 'NextFix Admin', 'email' => 'admin@nextfix.test', 'password' => Hash::make('password'), 'role' => 'nextfix_admin', 'email_verified_at' => now()]);

        $marina = Unit::create(['company_id' => $company->id, 'building' => 'Marina Heights', 'unit_number' => '1204', 'bedrooms' => 2, 'rate_unit_type' => '2_bhk', 'size_sqft' => 1180, 'address' => 'Dubai Marina, Dubai', 'access_notes' => 'Collect access card from reception.', 'is_active' => true]);
        $downtown = Unit::create(['company_id' => $company->id, 'building' => 'Burj Vista', 'unit_number' => '805', 'bedrooms' => 1, 'rate_unit_type' => '1_bhk', 'size_sqft' => 820, 'address' => 'Downtown Dubai, Dubai', 'is_active' => true]);

        Booking::create(['company_id' => $company->id, 'unit_id' => $marina->id, 'reference' => 'NFB-261107-A4K9P', 'service_type' => 'cleaning', 'service_name' => 'Checkout cleaning', 'scheduled_at' => now()->addDay()->setTime(11, 0), 'status' => 'confirmed', 'payment_status' => 'paid', 'amount_minor' => 32000, 'currency' => 'AED']);
        Booking::create(['company_id' => $company->id, 'unit_id' => $downtown->id, 'reference' => 'NFB-261108-M7W2C', 'service_type' => 'maintenance', 'service_name' => 'AC inspection', 'scheduled_at' => now()->addDays(2)->setTime(14, 30), 'status' => 'awaiting_payment', 'payment_status' => 'pending', 'amount_minor' => 15000, 'currency' => 'AED', 'notes' => 'Bedroom AC is not cooling.']);
    }
}
