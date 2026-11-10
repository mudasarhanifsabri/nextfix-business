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
        Unit::create(['company_id' => $company->id, 'building' => 'Creek Horizon', 'unit_number' => '830', 'bedrooms' => 3, 'rate_unit_type' => '3_bhk', 'size_sqft' => 1510, 'address' => 'Dubai Creek Harbour, Dubai', 'is_active' => true]);
        Unit::create(['company_id' => $company->id, 'building' => 'Palm Tower', 'unit_number' => '840', 'bedrooms' => 0, 'rate_unit_type' => 'studio', 'size_sqft' => 610, 'address' => 'Palm Jumeirah, Dubai', 'is_active' => true]);

        Booking::create(['company_id' => $company->id, 'unit_id' => $marina->id, 'reference' => 'NFB-261107-A4K9P', 'service_type' => 'cleaning', 'service_name' => 'Checkout cleaning', 'scheduled_at' => now()->addDay()->setTime(11, 0), 'status' => 'confirmed', 'payment_status' => 'paid', 'amount_minor' => 32000, 'currency' => 'AED']);
        Booking::create(['company_id' => $company->id, 'unit_id' => $downtown->id, 'reference' => 'NFB-261108-M7W2C', 'service_type' => 'maintenance', 'service_name' => 'AC inspection', 'scheduled_at' => now()->addDays(2)->setTime(14, 30), 'status' => 'awaiting_payment', 'payment_status' => 'pending', 'amount_minor' => 15000, 'currency' => 'AED', 'notes' => 'Bedroom AC is not cooling.']);
    }

    private function demoRates(): array
    {
        $cleaning = [];
        $baseRates = [
            'studio' => 10000,
            '1_bhk' => 12000,
            '2_bhk' => 15000,
            '3_bhk' => 18000,
            '3_bhk_maids' => 20000,
            '4_bhk' => 23000,
            '5_bhk' => 27000,
        ];

        foreach ($baseRates as $unitType => $base) {
            $cleaning[$unitType] = [
                'touch_up' => $base,
                'normal_materials' => $base + 4000,
                'normal_linen' => $base + 9000,
                'client_in_stay' => $base + 10000,
                'deep_materials' => $base + 16000,
                'deep_linen' => $base + 23000,
                'intensive_deep' => $base + 32000,
            ];
        }

        return [
            'cleaning' => $cleaning,
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
