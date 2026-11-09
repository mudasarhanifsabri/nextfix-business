<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingChatController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\CompanyRegistrationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\JobReportController;
use App\Http\Controllers\NomodWebhookController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\RateCardController;
use App\Http\Controllers\SystemUpdateController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\WorkerJobController;
use App\Http\Controllers\WorkerProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::view('/offline', 'offline')->name('offline');
Route::post('/webhooks/nomod', NomodWebhookController::class)->middleware('throttle:240,1')->name('webhooks.nomod');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [CompanyRegistrationController::class, 'create'])->name('register');
    Route::post('/register', [CompanyRegistrationController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/forgot-password', [PasswordController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/booking/{booking:reference}/chat', [BookingChatController::class, 'show'])->name('chat.show.reference');
    Route::get('/bookings/{booking}/chat', [BookingChatController::class, 'show'])->name('chat.show');
    Route::get('/bookings/{booking}/chat/feed', [BookingChatController::class, 'feed'])->name('chat.feed');
    Route::post('/bookings/{booking}/chat', [BookingChatController::class, 'store'])->middleware('throttle:60,1')->name('chat.store');
    Route::get('/chat/files/{message}', [BookingChatController::class, 'file'])->name('chat.file');
    Route::get('/bookings/{booking}/quotation.pdf', [BookingController::class, 'quotationPdf'])->name('bookings.quotation.pdf');
    Route::get('/bookings/{booking}/invoice.pdf', [BookingController::class, 'invoicePdf'])->name('bookings.invoice.pdf');
    Route::get('/jobs/reference/{booking:reference}/report', [JobReportController::class, 'show'])->name('jobs.report.reference');
    Route::get('/jobs/{booking}/report', [JobReportController::class, 'show'])->name('jobs.report.show');
    Route::get('/jobs/{booking}/report.pdf', [JobReportController::class, 'pdf'])->name('jobs.report.pdf');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/admin/notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/admin/notifications/read-all', [NotificationController::class, 'readAll'])->name('admin.notifications.read-all');
    Route::get('/admin/notifications/{notification}', [NotificationController::class, 'read'])->name('admin.notifications.read');

    Route::middleware('role:company_manager')->group(function (): void {
        Route::resource('units', UnitController::class)->except(['show', 'destroy']);
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/create/{service}', [BookingController::class, 'create'])
            ->whereIn('service', ['cleaning', 'maintenance'])->name('bookings.create');
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::post('/bookings/{booking}/quotation-decision', [BookingController::class, 'quotationDecision'])->name('bookings.quotation-decision');
        Route::get('/profile', [CompanyProfileController::class, 'show'])->name('company.profile.show');
        Route::post('/profile', [CompanyProfileController::class, 'update'])->name('company.profile.update');
        Route::get('/rates', [RateCardController::class, 'show'])->name('rates.show');
        Route::post('/rates/decision', [RateCardController::class, 'decision'])->name('rates.decision');
    });

    Route::middleware('role:nextfix_admin')->group(function (): void {
        Route::get('/admin/themes', [ThemeController::class, 'index'])->name('admin.themes.index');
        Route::post('/admin/themes', [ThemeController::class, 'publish'])->name('admin.themes.publish');
        Route::post('/admin/themes/restore', [ThemeController::class, 'restore'])->name('admin.themes.restore');
        Route::get('/admin/system-updates', [SystemUpdateController::class, 'index'])->name('admin.updates.index');
        Route::post('/admin/system-updates', [SystemUpdateController::class, 'store'])->middleware('throttle:5,1')->name('admin.updates.store');
        Route::resource('/admin/companies', CompanyController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
            ->names('admin.companies');
        Route::get('/admin/companies/{company}/rates', [RateCardController::class, 'admin'])->name('admin.companies.rates.show');
        Route::post('/admin/companies/{company}/rates', [RateCardController::class, 'save'])->name('admin.companies.rates.save');
        Route::get('/admin/invoices', [FinanceController::class, 'invoices'])->name('admin.invoices.index');
        Route::get('/admin/invoices/{invoice}', [FinanceController::class, 'invoice'])->name('admin.invoices.show');
        Route::get('/admin/invoices/{invoice}/pdf', [FinanceController::class, 'pdf'])->name('admin.invoices.pdf');
        Route::get('/admin/payments', [FinanceController::class, 'payments'])->name('admin.payments.index');
        Route::post('/admin/payments/reconcile-nomod', [FinanceController::class, 'reconcile'])->middleware('throttle:10,1')->name('admin.payments.reconcile-nomod');
        Route::post('/admin/payments/{payment}/refund', [FinanceController::class, 'refund'])->middleware('throttle:10,1')->name('admin.payments.refund');
        Route::get('/admin/operations', [OperationsController::class, 'index'])->name('admin.operations.index');
        Route::post('/admin/operations/{booking}/assign', [OperationsController::class, 'assign'])->name('admin.operations.assign');
        Route::post('/admin/operations/{booking}/send-estimate', [OperationsController::class, 'sendEstimate'])->name('admin.operations.send-estimate');
        Route::resource('/admin/professionals', ProfessionalController::class)->only(['index', 'create', 'store', 'show'])->names('admin.professionals');
        Route::get('/admin/integrations', [IntegrationController::class, 'index'])->name('admin.integrations.index');
        Route::post('/admin/integrations/smtp', [IntegrationController::class, 'smtp'])->middleware('throttle:5,1')->name('admin.integrations.smtp');
        Route::post('/admin/integrations/nomod', [IntegrationController::class, 'nomod'])->middleware('throttle:5,1')->name('admin.integrations.nomod');
    });

    Route::middleware('role:professional')->group(function (): void {
        Route::get('/work/home', [WorkerJobController::class, 'home'])->name('worker.home');
        Route::get('/work/jobs', [WorkerJobController::class, 'index'])->name('worker.jobs.index');
        Route::get('/work/jobs/{booking}', [WorkerJobController::class, 'show'])->name('worker.jobs.show');
        Route::post('/work/jobs/{booking}', [WorkerJobController::class, 'update'])->name('worker.jobs.update');
        Route::get('/work/evidence/{jobPhoto}', [WorkerJobController::class, 'evidence'])->name('worker.evidence.show');
        Route::get('/work/profile', [WorkerProfileController::class, 'show'])->name('worker.profile.show');
        Route::post('/work/profile', [WorkerProfileController::class, 'update'])->name('worker.profile.update');
        Route::get('/work/profile/photo', [WorkerProfileController::class, 'photo'])->name('worker.profile.photo');
    });
});
