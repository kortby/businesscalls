<?php

use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\TechnicianController;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('/about', 'About')->name('about');
Route::inertia('/pricing', 'Pricing')->name('pricing');
Route::inertia('/contact', 'Contact')->name('contact');

Route::get('technician/login', [TechnicianController::class, 'login'])->name('technician.login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('technician/dashboard', [TechnicianController::class, 'dashboard'])->name('technician.dashboard');

    Route::get('dashboard', function () {
        $user = auth()->user();
        if ($user && $user->employee) {
            return redirect()->route('technician.dashboard');
        }
        if ($user && $user->tenant_id) {
            TenantScope::setTenantId($user->tenant_id);
        }

        // Calculate Stats
        $totalCallsCount = CallLog::count();
        $successfulBookingsCount = Booking::where('status', 'booked')->count();
        $openJobsTodayCount = Booking::whereDate('scheduled_start', now()->startOfDay())->where('status', 'booked')->count();

        // Calculate daily booking streak
        $bookingStreak = 0;
        $date = now()->startOfDay();
        $hasBookingsToday = Booking::whereDate('scheduled_start', $date)->exists();
        if (! $hasBookingsToday) {
            $date = $date->subDay();
        }
        while (Booking::whereDate('scheduled_start', $date)->exists()) {
            $bookingStreak++;
            $date = $date->subDay();
        }

        return Inertia::render('Dashboard', [
            'tenant' => $user ? Tenant::find($user->tenant_id) : null,
            'employees' => Employee::with(['availabilities', 'bookings'])->get(),
            'bookings' => Booking::with('employee')->latest()->take(10)->get(),
            'totalCallsCount' => $totalCallsCount,
            'successfulBookingsCount' => $successfulBookingsCount,
            'openJobsTodayCount' => $openJobsTodayCount,
            'bookingStreak' => $bookingStreak,
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
