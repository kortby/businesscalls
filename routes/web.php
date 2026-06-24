<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\StripeBillingController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ConversationsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ServiceJobController;
use App\Http\Controllers\StripeWebhookController;
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
    Route::get('admin/diagnostics', [AdminController::class, 'diagnostics'])->name('admin.diagnostics');
    Route::get('admin/loyalty', [AdminController::class, 'loyalty'])->name('admin.loyalty');
    Route::get('technician/dashboard', [TechnicianController::class, 'dashboard'])->name('technician.dashboard');

    Route::get('dashboard', function () {
        $user = auth()->user();
        if ($user && $user->employee) {
            return redirect()->route('technician.dashboard');
        }
        if ($user && $user->tenant_id) {
            TenantScope::setTenantId($user->tenant_id);
        }

        $tenant = $user ? Tenant::find($user->tenant_id) : null;
        if ($tenant && $tenant->is_test_mode) {
            $employees = [
                [
                    'id' => 9991,
                    'first_name' => 'Alice (Simulated)',
                    'last_name' => 'Smith',
                    'phone' => '555-0199',
                    'skills' => ['hvac', 'electrical'],
                    'availabilities' => [
                        ['id' => 99901, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '17:00', 'is_active' => true],
                        ['id' => 99902, 'day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '17:00', 'is_active' => true],
                    ],
                    'bookings' => [
                        ['id' => 99911, 'customer_phone' => '555-0101', 'job_details' => 'AC Maintenance Service', 'status' => 'booked', 'scheduled_start' => now()->startOfDay()->addHours(9)->toDateTimeString()],
                    ],
                ],
                [
                    'id' => 9992,
                    'first_name' => 'Bob (Simulated)',
                    'last_name' => 'Jones',
                    'phone' => '555-0299',
                    'skills' => ['plumbing'],
                    'availabilities' => [
                        ['id' => 99903, 'day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '18:00', 'is_active' => true],
                    ],
                    'bookings' => [
                        ['id' => 99922, 'customer_phone' => '555-0102', 'job_details' => 'Leaky Pipe Repair', 'status' => 'booked', 'scheduled_start' => now()->startOfDay()->addHours(13)->toDateTimeString()],
                    ],
                ],
            ];

            $bookingsList = [
                [
                    'id' => 99911,
                    'customer_phone' => '555-0101',
                    'job_details' => 'AC Maintenance Service',
                    'status' => 'booked',
                    'scheduled_start' => now()->startOfDay()->addHours(9)->toDateTimeString(),
                    'employee' => ['first_name' => 'Alice (Simulated)', 'last_name' => 'Smith'],
                ],
                [
                    'id' => 99922,
                    'customer_phone' => '555-0102',
                    'job_details' => 'Leaky Pipe Repair',
                    'status' => 'booked',
                    'scheduled_start' => now()->startOfDay()->addHours(13)->toDateTimeString(),
                    'employee' => ['first_name' => 'Bob (Simulated)', 'last_name' => 'Jones'],
                ],
            ];

            return Inertia::render('Dashboard', [
                'tenant' => $tenant,
                'employees' => $employees,
                'bookings' => $bookingsList,
                'totalCallsCount' => 2,
                'successfulBookingsCount' => 2,
                'openJobsTodayCount' => 2,
                'bookingStreak' => 5,
                'averageCqs' => 0.95,
            ]);
        }

        // Calculate Stats
        $totalCallsCount = CallLog::count();
        $successfulBookingsCount = Booking::where('status', 'booked')->count();
        $openJobsTodayCount = Booking::whereDate('scheduled_start', now()->startOfDay())->where('status', 'booked')->count();
        $averageCqs = CallLog::whereNotNull('call_quality_score')->avg('call_quality_score') ?? 1.0;

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
            'tenant' => $tenant,
            'employees' => Employee::with(['availabilities', 'bookings'])->get(),
            'bookings' => Booking::with('employee')->latest()->take(10)->get(),
            'totalCallsCount' => $totalCallsCount,
            'successfulBookingsCount' => $successfulBookingsCount,
            'openJobsTodayCount' => $openJobsTodayCount,
            'bookingStreak' => $bookingStreak,
            'averageCqs' => (float) $averageCqs,
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

    Route::get('conversations', [ConversationsController::class, 'index'])->name('conversations.index');
    Route::post('conversations/{conversation}/messages', [ConversationsController::class, 'storeMessage'])->name('conversations.messages.store');

    Route::get('api/billing/portal', [StripeBillingController::class, 'portal'])->name('billing.portal');
    Route::post('api/billing/checkout', [StripeBillingController::class, 'checkout'])->name('billing.checkout');

    Route::resource('employees', EmployeeController::class);
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::resource('jobs', ServiceJobController::class);
});

Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

require __DIR__.'/settings.php';
