<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Company;
use App\Models\CompanyRateCard;
use App\Models\Unit;
use App\Models\User;
use App\Support\RateCatalogue;
use App\Support\WorkChecklist;
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

        $this->assertDatabaseHas('bookings', ['unit_id' => $unit->id, 'amount_minor' => 50000, 'workflow_stage' => 'work']);
        $this->assertDatabaseHas('job_photos', ['uploaded_by_user_id' => $manager->id, 'category' => 'booking_issue']);
    }

    public function test_booking_form_only_offers_the_companys_exact_approved_services(): void
    {
        $company = Company::create(['name' => 'Contract Homes']);
        $manager = User::factory()->create(['role' => 'company_manager', 'company_id' => $company->id]);
        $unit = Unit::create([
            'company_id' => $company->id,
            'building' => 'Canal Residence',
            'unit_number' => '1204',
            'rate_unit_type' => '1_bhk',
            'bedrooms' => 1,
            'address' => 'Business Bay, Dubai',
            'is_active' => true,
        ]);
        CompanyRateCard::create([
            'company_id' => $company->id,
            'status' => 'approved',
            'rates' => ['cleaning' => [
                '1_bhk' => [
                    'touch_up' => 12000,
                    'normal_linen' => 18500,
                    'deep_materials' => null,
                ],
            ]],
        ]);

        $this->actingAs($manager)
            ->get(route('bookings.create', 'cleaning'))
            ->assertOk()
            ->assertSee('Touch-up cleaning')
            ->assertSee('Normal cleaning + linen, towels &amp; amenities', false)
            ->assertSee('"touch_up":12000', false)
            ->assertSee('"normal_linen":18500', false);

        $this->actingAs($manager)->post(route('bookings.store'), [
            'unit_id' => $unit->id,
            'service_type' => 'cleaning',
            'service_name' => 'Unapproved cleaning package',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('service_name');

        $this->assertDatabaseCount('bookings', 0);

        $this->actingAs($manager)->post(route('bookings.store'), [
            'unit_id' => $unit->id,
            'service_type' => 'cleaning',
            'service_name' => 'Touch-up cleaning',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('bookings', [
            'unit_id' => $unit->id,
            'service_name' => 'Touch-up cleaning',
            'amount_minor' => 12000,
        ]);
    }

    public function test_cleaning_booking_saves_priced_add_ons_and_worker_tasks(): void
    {
        $company = Company::create(['name' => 'Linen Homes']);
        $manager = User::factory()->create(['role' => 'company_manager', 'company_id' => $company->id]);
        $unit = Unit::create([
            'company_id' => $company->id,
            'building' => 'Creek Residence',
            'unit_number' => '806',
            'rate_unit_type' => '1_bhk',
            'bedrooms' => 1,
            'address' => 'Dubai Creek Harbour, Dubai',
            'is_active' => true,
        ]);
        CompanyRateCard::create([
            'company_id' => $company->id,
            'status' => 'approved',
            'rates' => [
                'cleaning' => ['1_bhk' => ['normal_linen' => 18500]],
                'extras' => [
                    'additional_linen' => ['bath_towel' => 2500],
                    'carpet' => ['2x3' => 7000],
                ],
            ],
        ]);

        $this->actingAs($manager)->post(route('bookings.store'), [
            'unit_id' => $unit->id,
            'service_type' => 'cleaning',
            'service_code' => 'normal_linen',
            'service_name' => 'This client value must not control pricing',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'extras' => [
                'additional_linen' => ['bath_towel' => 2],
                'carpet' => ['2x3' => 1],
            ],
        ])->assertRedirect(route('bookings.index'));

        $booking = Booking::with('unit')->sole();
        $this->assertSame('normal_linen', $booking->service_code);
        $this->assertSame('Normal cleaning + linen, towels & amenities', $booking->service_name);
        $this->assertSame(30500, $booking->amount_minor);
        $this->assertCount(3, $booking->booking_items);
        $this->assertSame(5000, $booking->booking_items[1]['total_minor']);

        $tasks = WorkChecklist::for($booking);
        $this->assertContains('Linen & towels', $tasks);
        $this->assertContains('Bath towel × 2', $tasks);
        $this->assertContains('2 × 3 m × 1', $tasks);
    }

    public function test_cleaning_booking_rejects_an_unapproved_add_on(): void
    {
        $company = Company::create(['name' => 'Protected Rates']);
        $manager = User::factory()->create(['role' => 'company_manager', 'company_id' => $company->id]);
        $unit = Unit::create([
            'company_id' => $company->id,
            'building' => 'Marina View',
            'unit_number' => '402',
            'rate_unit_type' => '1_bhk',
            'bedrooms' => 1,
            'address' => 'Dubai Marina, Dubai',
            'is_active' => true,
        ]);
        CompanyRateCard::create([
            'company_id' => $company->id,
            'status' => 'approved',
            'rates' => ['cleaning' => ['1_bhk' => ['touch_up' => 12000]]],
        ]);

        $this->actingAs($manager)->post(route('bookings.store'), [
            'unit_id' => $unit->id,
            'service_type' => 'cleaning',
            'service_code' => 'touch_up',
            'service_name' => 'Touch-up cleaning',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'extras' => ['additional_linen' => ['bath_towel' => 1]],
        ])->assertSessionHasErrors('extras.additional_linen.bath_towel');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_demo_rate_card_covers_every_supported_unit_and_cleaning_service(): void
    {
        $this->seed();

        $card = CompanyRateCard::firstOrFail();

        foreach (array_keys(RateCatalogue::unitTypes()) as $unitType) {
            foreach (array_keys(RateCatalogue::cleaningTypes()) as $serviceCode) {
                $rate = data_get($card->rates, "cleaning.{$unitType}.{$serviceCode}");

                $this->assertIsInt($rate, "Missing demo rate for {$unitType}.{$serviceCode}");
                $this->assertGreaterThan(0, $rate, "Demo rate must be positive for {$unitType}.{$serviceCode}");
            }
        }

        $this->assertSame(15000, $card->maintenance_inspection_minor);
        $this->assertDatabaseCount('units', 4);
    }

    public function test_maintenance_mobile_booking_uses_the_three_step_list_wizard(): void
    {
        $company = Company::create(['name' => 'Maintenance Homes']);
        $manager = User::factory()->create(['role' => 'company_manager', 'company_id' => $company->id]);
        Unit::create([
            'company_id' => $company->id,
            'building' => 'Canal Residence',
            'unit_number' => '1204',
            'rate_unit_type' => '2_bhk',
            'bedrooms' => 2,
            'address' => 'Business Bay, Dubai',
            'is_active' => true,
        ]);
        CompanyRateCard::create([
            'company_id' => $company->id,
            'status' => 'approved',
            'maintenance_inspection_minor' => 15000,
            'rates' => ['cleaning' => []],
        ]);

        $this->actingAs($manager)
            ->get(route('bookings.create', 'maintenance'))
            ->assertOk()
            ->assertSee('data-wizard-kind="maintenance"', false)
            ->assertSee('1 of 3')
            ->assertSee('Choose a unit')
            ->assertSee('What needs attention?')
            ->assertSee('Schedule inspection')
            ->assertSee('Continue to payment');
    }
}
