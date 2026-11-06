<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_download_an_invoice(): void
    {
        $admin = User::factory()->create(['role' => 'nextfix_admin']);
        $company = Company::create(['name' => 'Finance Demo']);
        $invoice = Invoice::create(['company_id' => $company->id, 'number' => 'INV-TEST-1', 'issued_at' => today(), 'due_at' => today()->addMonth(), 'status' => 'sent', 'line_items' => [['description' => 'Service', 'quantity' => 1, 'unit_price_minor' => 10000]], 'subtotal_minor' => 10000, 'tax_minor' => 500, 'amount_minor' => 10500, 'currency' => 'AED']);

        $this->actingAs($admin)->get(route('admin.invoices.show', $invoice))->assertOk()->assertSee('INV-TEST-1');
        $this->actingAs($admin)->get(route('admin.invoices.pdf', $invoice))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_bell_notification_can_be_marked_read(): void
    {
        $admin = User::factory()->create(['role' => 'nextfix_admin']);
        $admin->notifyNow(new AdminAlert('Test alert', 'Testing the bell.', route('dashboard')));
        $notification = $admin->unreadNotifications()->firstOrFail();

        $this->actingAs($admin)->get(route('admin.notifications.read', $notification))->assertRedirect(route('dashboard'));
        $this->assertNotNull($notification->fresh()->read_at);
    }
}
