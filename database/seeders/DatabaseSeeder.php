<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Company;
use App\Models\CompanyRateCard;
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
        CompanyRateCard::create([
            'company_id' => $company->id,
            'status' => 'approved',
            'maintenance_inspection_minor' => 15000,
            'rates' => $this->demoRates(),
            'approved_at' => now(),
        ]);
        $manager = User::create(['company_id' => $company->id, 'name' => 'Maya Khan', 'email' => 'manager@demo.test', 'password' => Hash::make('password'), 'role' => 'company_manager', 'email_verified_at' => now()]);
        User::create(['name' => 'NextFix Admin', 'email' => 'admin@nextfix.test', 'password' => Hash::make('password'), 'role' => 'nextfix_admin', 'email_verified_at' => now()]);

        $marina = Unit::create(['company_id' => $company->id, 'building' => 'Marina Heights', 'unit_number' => '1204', 'bedrooms' => 2, 'rate_unit_type' => '2_bhk', 'size_sqft' => 1180, 'address' => 'Dubai Marina, Dubai', 'access_notes' => 'Collect access card from reception.', 'is_active' => true]);
        $downtown = Unit::create(['company_id' => $company->id, 'building' => 'Burj Vista', 'unit_number' => '805', 'bedrooms' => 1, 'rate_unit_type' => '1_bhk', 'size_sqft' => 820, 'address' => 'Downtown Dubai, Dubai', 'is_active' => true]);

        Booking::create(['company_id' => $company->id, 'unit_id' => $marina->id, 'reference' => 'NFB-261107-A4K9P', 'service_type' => 'cleaning', 'service_name' => 'Checkout cleaning', 'scheduled_at' => now()->addDay()->setTime(11, 0), 'status' => 'confirmed', 'payment_status' => 'paid', 'amount_minor' => 32000, 'currency' => 'AED']);
        Booking::create(['company_id' => $company->id, 'unit_id' => $downtown->id, 'reference' => 'NFB-261108-M7W2C', 'service_type' => 'maintenance', 'service_name' => 'AC inspection', 'scheduled_at' => now()->addDays(2)->setTime(14, 30), 'status' => 'awaiting_payment', 'payment_status' => 'pending', 'amount_minor' => 15000, 'currency' => 'AED', 'notes' => 'Bedroom AC is not cooling.']);
    }

    private function demoRates(): array
    {
        return [
            'cleaning' => [
                '1_bhk' => ['touch_up' => 12000, 'normal_materials' => 16000, 'normal_linen' => 21000, 'client_in_stay' => 22000, 'deep_materials' => 28000, 'deep_linen' => 34000, 'intensive_deep' => 42000],
                '2_bhk' => ['touch_up' => 15000, 'normal_materials' => 20000, 'normal_linen' => 27000, 'client_in_stay' => 28500, 'deep_materials' => 35000, 'deep_linen' => 43000, 'intensive_deep' => 52000],
            ],
            'extras' => [
                'additional_linen' => ['pillow_case' => 1000, 'bed_sheet' => 2500, 'duvet_cover' => 3000, 'bath_mat' => 1200, 'face_towel' => 800, 'hand_towel' => 1000, 'bath_towel' => 1800, 'pool_towel' => 2200],
                'carpet' => ['0_8x3' => 4500, '1_5x2_3' => 6000, '2x3' => 7500, '3x4' => 12000, '3x5' => 15000],
                'mattress' => ['single' => 9000, 'double' => 12000, 'queen' => 14000, 'king' => 16000, 'super_king' => 18000],
                'sofa_bed' => ['2_seater' => 12000, '3_seater' => 15000, '4_seater' => 18000],
                'sofa' => ['1_seater' => 5000, '2_seater' => 9000, '3_seater' => 12000, '4_seater' => 15000, '5_seater' => 18000],
            ],
        ];
    }
}
