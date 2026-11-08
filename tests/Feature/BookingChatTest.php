<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Professional;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_participants_can_chat_and_other_company_is_denied(): void
    {
        $company = Company::create(['name' => 'Host One']);
        $other = Company::create(['name' => 'Host Two']);
        $unit = Unit::create(['company_id'=>$company->id,'building'=>'Tower','unit_number'=>'1','address'=>'Dubai']);
        $manager = User::factory()->create(['company_id'=>$company->id,'role'=>'company_manager']);
        $outsider = User::factory()->create(['company_id'=>$other->id,'role'=>'company_manager']);
        $workerUser = User::factory()->create(['role'=>'professional']);
        $professional = Professional::create(['user_id'=>$workerUser->id,'name'=>'Worker','role'=>'cleaner','email'=>$workerUser->email,'phone'=>'+971500000000','status'=>'active']);
        $booking = Booking::create(['company_id'=>$company->id,'unit_id'=>$unit->id,'professional_id'=>$professional->id,'reference'=>'NFB-CHAT-1','service_type'=>'cleaning','service_name'=>'Cleaning','scheduled_at'=>now()->addDay(),'status'=>'assigned','payment_status'=>'paid','currency'=>'AED']);

        $this->actingAs($manager)->postJson(route('chat.store',$booking), ['body'=>'Please use the access code.'])->assertCreated()->assertJsonPath('message.body','Please use the access code.');
        $this->actingAs($workerUser)->get(route('chat.show',$booking))->assertOk()->assertSee('Please use the access code.');
        $this->actingAs($outsider)->get(route('chat.show',$booking))->assertForbidden();
    }

    public function test_booking_workspace_is_visible_only_to_participants(): void
    {
        $company = Company::create(['name'=>'Host One']);
        $other = Company::create(['name'=>'Host Two']);
        $unit = Unit::create(['company_id'=>$company->id,'building'=>'Tower','unit_number'=>'1','address'=>'Dubai']);
        $manager = User::factory()->create(['company_id'=>$company->id,'role'=>'company_manager']);
        $outsider = User::factory()->create(['company_id'=>$other->id,'role'=>'company_manager']);
        $booking = Booking::create(['company_id'=>$company->id,'unit_id'=>$unit->id,'reference'=>'NFB-WORKSPACE-1','service_type'=>'cleaning','service_name'=>'Cleaning','scheduled_at'=>now()->addDay(),'status'=>'pending','payment_status'=>'pending','currency'=>'AED']);

        $this->actingAs($manager)->get(route('bookings.show',$booking))->assertOk()->assertSee('Booking workspace')->assertSee('Documents and payment');
        $this->actingAs($outsider)->get(route('bookings.show',$booking))->assertForbidden();
    }
}
