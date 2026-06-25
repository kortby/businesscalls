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
    Route::get('admin/sla-diagnostics', [AdminController::class, 'slaDiagnostics'])->name('admin.sla-diagnostics');
    Route::post('admin/telephony/provision', [AdminController::class, 'provisionTelephony'])->name('admin.telephony.provision');
    Route::get('admin/loyalty', [AdminController::class, 'loyalty'])->name('admin.loyalty');
    Route::get('admin/health', [AdminController::class, 'health'])->name('admin.health');
    Route::get('admin/call-flow', [AdminController::class, 'callFlow'])->name('admin.callflow');
    Route::get('admin/reports', [AdminController::class, 'executiveReports'])->name('admin.reports');
    Route::get('admin/executive-report/download', [AdminController::class, 'downloadReport'])->name('admin.report.download');
    Route::get('admin/pre-flight-audit', [AdminController::class, 'preFlightAudit'])->name('admin.preflight');
    Route::get('admin/achievements', [AdminController::class, 'achievements'])->name('admin.achievements');
    Route::get('admin/onboarding', [AdminController::class, 'onboardingQuest'])->name('admin.onboarding');
    Route::get('admin/onboarding-setup', [AdminController::class, 'onboardingSetup'])->name('admin.onboarding-setup');
    Route::get('admin/dispatch-map', [AdminController::class, 'dispatchMap'])->name('admin.dispatch-map');
    Route::get('admin/leaderboard', [AdminController::class, 'leaderboard'])->name('admin.leaderboard');
    Route::get('admin/mascot-shop', [AdminController::class, 'mascotShop'])->name('admin.mascot-shop');
    Route::post('admin/mascot-shop/purchase', [AdminController::class, 'purchaseMascotSkin'])->name('admin.mascot-shop.purchase');
    Route::get('admin/integrations', [AdminController::class, 'integrations'])->name('admin.integrations');
    Route::post('admin/integrations', [AdminController::class, 'saveIntegration'])->name('admin.integrations.save');
    Route::post('admin/integrations/timing', [AdminController::class, 'saveTimingSettings'])->name('admin.integrations.timing');
    Route::get('admin/call-monitor', [AdminController::class, 'callMonitor'])->name('admin.call-monitor');
    Route::get('admin/supervisor-hud', [AdminController::class, 'supervisorHud'])->name('admin.supervisor-hud');
    Route::get('admin/status-hud', [AdminController::class, 'statusHud'])->name('admin.status-hud');
    Route::get('admin/audit-logs', [AdminController::class, 'auditLogs'])->name('admin.audit-logs');
    Route::get('admin/experiments', [AdminController::class, 'experiments'])->name('admin.experiments');
    Route::post('admin/experiments/denoising', [AdminController::class, 'toggleDenoising'])->name('admin.experiments.denoising');
    Route::post('admin/experiments/create', [AdminController::class, 'saveExperiment'])->name('admin.experiments.save');
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
    Route::get('docs', function () {
        $routeArticles = [];
        $routesDir = base_path('docs/routes');
        if (is_dir($routesDir)) {
            $files = scandir($routesDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || ! str_ends_with($file, '.md')) {
                    continue;
                }
                $filePath = $routesDir.'/'.$file;
                $content = file_get_contents($filePath);
                $id = str_replace('.md', '', $file);

                // Extract title (first line # Route: ... or similar)
                $title = $id;
                if (preg_match('/^#\s+(.*)$/m', $content, $m)) {
                    $title = $m[1];
                }

                // Extract summary
                $summary = 'Detailed guide for '.$title;
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if (! empty($trimmed) && ! str_starts_with($trimmed, '#') && ! str_starts_with($trimmed, '|') && ! str_starts_with($trimmed, '-')) {
                        $summary = $trimmed;
                        break;
                    }
                }

                // Determine category based on prefix and filename matches
                $category = 'Developer API Reference';
                if ($id === 'get_root' || $id === 'get_about' || $id === 'get_pricing' || $id === 'get_contact' || $id === 'get_admin_onboarding') {
                    $category = 'User Guide: Get Started';
                } elseif ($id === 'get_dashboard') {
                    $category = 'User Guide: Operations Dashboard';
                } elseif (str_starts_with($id, 'get_availabilities') || str_starts_with($id, 'post_availabilities') || str_starts_with($id, 'put_availabilities') || str_starts_with($id, 'delete_availabilities') || str_starts_with($id, 'get_bookings') || str_starts_with($id, 'post_bookings') || str_starts_with($id, 'put_bookings') || str_starts_with($id, 'delete_bookings')) {
                    $category = 'User Guide: Availability & Scheduling';
                } elseif (str_starts_with($id, 'get_conversations') || str_starts_with($id, 'post_conversations')) {
                    $category = 'User Guide: Communications';
                } elseif (str_starts_with($id, 'get_employees') || str_starts_with($id, 'post_employees') || str_starts_with($id, 'put_employees') || str_starts_with($id, 'delete_employees') || str_starts_with($id, 'get_customers') || str_starts_with($id, 'post_customers') || str_starts_with($id, 'get_jobs') || str_starts_with($id, 'post_jobs') || str_starts_with($id, 'put_jobs') || str_starts_with($id, 'delete_jobs')) {
                    $category = 'User Guide: Records Management';
                } elseif (str_starts_with($id, 'get_admin_') || str_starts_with($id, 'post_admin_')) {
                    $category = 'User Guide: Advanced Dispatch Tools';
                } elseif (str_starts_with($id, 'get_settings_') || str_starts_with($id, 'patch_settings_') || str_starts_with($id, 'delete_settings_') || str_starts_with($id, 'put_settings_') || str_starts_with($id, 'get_appearance') || str_starts_with($id, 'get_profile') || str_starts_with($id, 'patch_profile') || str_starts_with($id, 'delete_profile') || str_starts_with($id, 'get_security') || str_starts_with($id, 'put_user_password')) {
                    $category = 'User Guide: Account & Settings';
                } elseif (str_starts_with($id, 'get_technician_') || str_starts_with($id, 'post_technician_')) {
                    $category = 'User Guide: Technician Mobile App';
                }

                $routeArticles[] = [
                    'id' => $id,
                    'category' => $category,
                    'title' => $title,
                    'icon' => str_contains($id, 'get_') ? 'BookOpen' : 'Terminal',
                    'summary' => $summary,
                    'content' => $content,
                    'tags' => ['route', 'user guide', str_replace('_', ' ', $id)],
                ];
            }
        }

        return Inertia::render('Docs/Index', [
            'routeArticles' => $routeArticles,
        ]);
    })->name('docs');
});

Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

require __DIR__.'/settings.php';
