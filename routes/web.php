<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SystemUpdateController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::view('/offline', 'offline')->name('offline');

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
});
