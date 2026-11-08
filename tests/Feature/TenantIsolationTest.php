<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_cannot_edit_another_company_unit(): void
    {
        $a = Company::create(['name' => 'Company A']);
        $b = Company::create(['name' => 'Company B']);
        $user = User::factory()->create(['company_id' => $a->id, 'role' => 'company_manager']);
        $unit = Unit::create(['company_id' => $b->id, 'building' => 'Private Tower', 'unit_number' => '1', 'bedrooms' => 1, 'address' => 'Dubai', 'is_active' => true]);

        $this->actingAs($user)->get(route('units.edit', $unit))->assertNotFound();
    }

    public function test_booking_rejects_a_unit_from_another_company(): void
    {
        $a = Company::create(['name' => 'Company A']);
        $b = Company::create(['name' => 'Company B']);
        $user = User::factory()->create(['company_id' => $a->id, 'role' => 'company_manager']);
        $unit = Unit::create(['company_id' => $b->id, 'building' => 'Private Tower', 'unit_number' => '1', 'bedrooms' => 1, 'address' => 'Dubai', 'is_active' => true]);

        $this->actingAs($user)->post(route('bookings.store'), ['unit_id' => $unit->id, 'service_type' => 'cleaning', 'service_name' => 'Regular cleaning', 'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s')])->assertNotFound();
    }
}
