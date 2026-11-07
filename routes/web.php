<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\WorkerJobController;
use App\Http\Controllers\SystemUpdateController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\NomodWebhookController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::view('/offline', 'offline')->name('offline');
Route::post('/webhooks/nomod', NomodWebhookController::class)->name('webhooks.nomod');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('units', UnitController::class)->except(['show', 'destroy']);
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create/{service}', [BookingController::class, 'create'])
        ->whereIn('service', ['cleaning', 'maintenance'])->name('bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/admin/system-updates', [SystemUpdateController::class, 'index'])->name('admin.updates.index');
    Route::post('/admin/system-updates', [SystemUpdateController::class, 'store'])->name('admin.updates.store');
    Route::resource('/admin/companies', CompanyController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->names('admin.companies');
    Route::post('/admin/companies/{company}/rates', [CompanyController::class, 'rates'])->name('admin.companies.rates');
    Route::get('/admin/invoices', [FinanceController::class, 'invoices'])->name('admin.invoices.index');
    Route::get('/admin/invoices/{invoice}', [FinanceController::class, 'invoice'])->name('admin.invoices.show');
    Route::get('/admin/invoices/{invoice}/pdf', [FinanceController::class, 'pdf'])->name('admin.invoices.pdf');
    Route::get('/admin/payments', [FinanceController::class, 'payments'])->name('admin.payments.index');
    Route::get('/admin/notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');
    Route::post('/admin/notifications/read-all', [NotificationController::class, 'readAll'])->name('admin.notifications.read-all');
    Route::get('/admin/notifications/{notification}', [NotificationController::class, 'read'])->name('admin.notifications.read');
    Route::get('/admin/operations', [OperationsController::class, 'index'])->name('admin.operations.index');
    Route::post('/admin/operations/{booking}/assign', [OperationsController::class, 'assign'])->name('admin.operations.assign');
    Route::post('/admin/operations/{booking}/send-estimate', [OperationsController::class, 'sendEstimate'])->name('admin.operations.send-estimate');
    Route::post('/bookings/{booking}/quotation-decision', [BookingController::class, 'quotationDecision'])->name('bookings.quotation-decision');
    Route::resource('/admin/professionals', ProfessionalController::class)->only(['index', 'create', 'store', 'show'])->names('admin.professionals');
    Route::get('/admin/integrations', [IntegrationController::class, 'index'])->name('admin.integrations.index');
    Route::post('/admin/integrations/smtp', [IntegrationController::class, 'smtp'])->name('admin.integrations.smtp');
    Route::post('/admin/integrations/nomod', [IntegrationController::class, 'nomod'])->name('admin.integrations.nomod');
    Route::get('/work/jobs', [WorkerJobController::class, 'index'])->name('worker.jobs.index');
    Route::get('/work/jobs/{booking}', [WorkerJobController::class, 'show'])->name('worker.jobs.show');
    Route::post('/work/jobs/{booking}', [WorkerJobController::class, 'update'])->name('worker.jobs.update');
});
