<?php

use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = auth()->user();
        if ($user && $user->tenant_id) {
            TenantScope::setTenantId($user->tenant_id);
        }

        return Inertia::render('Dashboard', [
            'tenant' => $user ? Tenant::find($user->tenant_id) : null,
            'employees' => Employee::with('availabilities')->get(),
            'bookings' => Booking::with('employee')->latest()->take(10)->get(),
        ]);
    })->name('dashboard');
});

require __DIR__.'/settings.php';
