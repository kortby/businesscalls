<?php

use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('/about', 'About')->name('about');
Route::inertia('/pricing', 'Pricing')->name('pricing');
Route::inertia('/contact', 'Contact')->name('contact');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = auth()->user();
        if ($user && $user->tenant_id) {
            TenantScope::setTenantId($user->tenant_id);
        }

        return Inertia::render('Dashboard', [
            'tenant' => $user ? Tenant::find($user->tenant_id) : null,
            'employees' => Employee::with(['availabilities', 'bookings'])->get(),
            'bookings' => Booking::with('employee')->latest()->take(10)->get(),
        ]);
    })->name('dashboard');

    Route::get('availabilities', [AvailabilityController::class, 'index'])->name('availabilities.index');
    Route::post('availabilities', [AvailabilityController::class, 'store'])->name('availabilities.store');
    Route::put('availabilities/{availability}', [AvailabilityController::class, 'update'])->name('availabilities.update');
    Route::delete('availabilities/{availability}', [AvailabilityController::class, 'destroy'])->name('availabilities.destroy');

    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::put('bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
    Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
});

require __DIR__.'/settings.php';
