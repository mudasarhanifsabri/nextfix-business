<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyRateCard;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UnitRateCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_tap_selected_unit_category_controls_cleaning_rate(): void
    {
        Storage::fake('public');
        $company = Company::create(['name' => 'Tap Homes']);
        $manager = User::factory()->create(['role' => 'company_manager', 'company_id' => $company->id]);
        CompanyRateCard::create([
            'company_id' => $company->id,
            'status' => 'approved',
            'rates' => ['cleaning' => [
                '3_bhk' => ['normal_linen' => 30000],
                '3_bhk_maids' => ['normal_linen' => 50000],
            ]],
        ]);

        $this->actingAs($manager)->get(route('units.create'))->assertOk()->assertSee('3 BHK + maid’s room');
        $this->actingAs($manager)->post(route('units.store'), [
            'building' => 'Marina Residence', 'unit_number' => '301', 'rate_unit_type' => '3_bhk_maids',
            'address' => 'Dubai Marina, Dubai', 'is_active' => 1,
        ])->assertRedirect(route('units.index'));

        $unit = Unit::where('company_id', $company->id)->firstOrFail();
        $this->assertSame('3_bhk_maids', $unit->rate_unit_type);
        $this->assertSame(3, $unit->bedrooms);

        $this->actingAs($manager)->post(route('bookings.store'), [
            'unit_id' => $unit->id, 'service_type' => 'cleaning', 'service_name' => 'Checkout cleaning',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'booking_photos' => [UploadedFile::fake()->image('access.jpg')],
        ])->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('bookings', ['unit_id' => $unit->id, 'amount_minor' => 50000]);
        $this->assertDatabaseHas('job_photos', ['uploaded_by_user_id' => $manager->id, 'category' => 'booking_issue']);
    }
}
