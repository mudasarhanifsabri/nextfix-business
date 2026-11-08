<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Professional;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkerMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_inspection_accepts_multiple_camera_batches_without_advancing_early(): void
    {
        Storage::fake('public');
        $worker = User::factory()->create(['role' => 'professional']);
        $professional = Professional::create(['user_id' => $worker->id, 'name' => 'Batch Inspector', 'role' => 'maintenance', 'phone' => '+971500000110']);
        $company = Company::create(['name' => 'Batch Evidence Homes']);
        $unit = Unit::create(['company_id' => $company->id, 'building' => 'Business Bay', 'unit_number' => '210', 'address' => 'Dubai']);
        $booking = Booking::create(['company_id' => $company->id, 'unit_id' => $unit->id, 'professional_id' => $professional->id, 'reference' => 'NFB-BATCH-1', 'service_type' => 'maintenance', 'service_name' => 'AC inspection', 'scheduled_at' => now(), 'status' => 'assigned']);

        foreach ([5, 4] as $batchSize) {
            $photos = collect(range(1, $batchSize))
                ->map(fn (int $number) => UploadedFile::fake()->image('inspection-'.$number.'.jpg'))
                ->all();

            $this->actingAs($worker)
                ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
                ->post(route('worker.jobs.update', $booking), [
                    'action' => 'inspection_photo',
                    'inspection_photos' => $photos,
                ])->assertOk()->assertJsonPath('message', 'Inspection photos saved.');
        }

        $this->assertSame(9, $booking->inspectionPhotos()->count());
        $this->assertSame('inspection', $booking->fresh()->workflow_stage);

        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), [
            'action' => 'inspection',
            'inspection_condition' => 'attention',
            'inspection_issues' => ['ac'],
            'inspection_report' => '',
        ])->assertOk()->assertJsonPath('message', 'Inspection saved. Add the estimate next.');

        $booking->refresh();
        $this->assertSame('estimate', $booking->workflow_stage);
        $this->assertStringContainsString('Ac', $booking->inspection_report);
    }

    public function test_worker_can_upload_profile_picture_and_multiple_inspection_photos(): void
    {
        Storage::fake('public');
        $worker = User::factory()->create(['role' => 'professional']);
        $professional = Professional::create(['user_id' => $worker->id, 'name' => 'Photo Worker', 'role' => 'maintenance', 'phone' => '+971500000100']);
        $company = Company::create(['name' => 'Evidence Homes']);
        $unit = Unit::create(['company_id' => $company->id, 'building' => 'Dubai Marina', 'unit_number' => '12', 'address' => 'Dubai']);
        $booking = Booking::create(['company_id' => $company->id, 'unit_id' => $unit->id, 'professional_id' => $professional->id, 'reference' => 'NFB-PHOTO-1', 'service_type' => 'maintenance', 'service_name' => 'AC issue', 'scheduled_at' => now(), 'status' => 'assigned']);

        $this->actingAs($worker)->post(route('worker.profile.update'), ['phone' => '+971500000101', 'photo' => UploadedFile::fake()->image('profile.jpg', 400, 400)])->assertRedirect();
        $this->assertNotNull($professional->fresh()->profile_photo_path);
        $this->actingAs($worker)->get(route('worker.profile.show'))->assertOk()->assertSee('Profile picture');

        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), [
            'action' => 'inspection', 'inspection_condition' => 'attention', 'inspection_report' => 'Compressor requires review.',
            'inspection_photos' => [UploadedFile::fake()->image('unit.jpg'), UploadedFile::fake()->image('compressor.jpg')],
        ])->assertRedirect();
        $this->assertDatabaseCount('job_photos', 2);
        $response = $this->actingAs($worker)->get(route('worker.jobs.show', $booking));
        $response->assertOk()->assertSee('Photo evidence · 2');
        $photo = $booking->inspectionPhotos()->first();
        $this->actingAs($worker)->get(route('worker.evidence.show', $photo))->assertOk();

        $booking->update(['workflow_stage' => 'work', 'payment_status' => 'paid', 'status' => 'assigned', 'completed_items' => ['arrival'], 'progress' => 13]);
        $this->actingAs($worker)->get(route('worker.jobs.index'))->assertOk()->assertSee('PAID · READY FOR WORK');
        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), [
            'action' => 'complete', 'completed_items' => array_keys(config('workflows.maintenance')),
        ])->assertStatus(422);
        $this->assertNotSame('completed', $booking->fresh()->status);
        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), [
            'action' => 'photo', 'work_photo_category' => 'after',
            'work_photos' => [UploadedFile::fake()->image('finished.jpg'), UploadedFile::fake()->image('panel.jpg')],
        ])->assertRedirect();
        $this->assertDatabaseHas('job_photos', ['booking_id' => $booking->id, 'category' => 'after']);
        $this->assertSame(['arrival'], $booking->fresh()->completed_items);
        $this->actingAs($worker)->get(route('worker.jobs.show', $booking))->assertOk()->assertSee('Take after-repair photos')->assertSee('2 saved');

        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), [
            'action' => 'complete', 'completed_items' => array_keys(config('workflows.maintenance')),
        ])->assertRedirect();
        $this->assertSame('completed', $booking->fresh()->status);
        $photoCount = $booking->photos()->count();
        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), [
            'action' => 'photo', 'work_photo_category' => 'after',
            'work_photos' => [UploadedFile::fake()->image('late-change.jpg')],
        ])->assertStatus(422);
        $this->actingAs($worker)->post(route('worker.jobs.update', $booking), [
            'action' => 'save', 'completed_items' => [],
        ])->assertStatus(422);
        $this->assertSame($photoCount, $booking->photos()->count());
        $this->actingAs($worker)->get(route('worker.jobs.show', $booking))
            ->assertOk()->assertSee('Job completed · record locked')
            ->assertDontSee('Save checklist progress')->assertDontSee('Save after photos');
    }
}
