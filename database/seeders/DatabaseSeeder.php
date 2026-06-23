<?php

namespace Database\Seeders;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Reset tenant scope context for seed process
        TenantScope::setTenantId(null);

        // 2. Create the Tenant
        $tenant = Tenant::create([
            'name' => 'Apex Contracting Services',
            'slug' => 'apex-contracting',
            'plan' => 'Enterprise',
            'settings' => [
                'prompt' => 'You are the AI voice dispatcher for Apex Contracting Services. Act professional, friendly, and efficient. Enforce technician active shifts and the mandatory 1.5-hour travel buffer on all bookings.',
                'phone_mappings' => [
                    '+15550192834' => 'plumbing-dispatch',
                    '+15559876543' => 'hvac-dispatch',
                ],
                'emergency_parameters' => [
                    'after_hours' => false,
                    'escalation_phone' => '+15553334444',
                ],
            ],
            'secret_key' => 'apex-super-secret-telephony-webhook-key',
        ]);

        // Explicitly set scope to the newly created tenant for subsequent model operations
        TenantScope::setTenantId($tenant->id);

        // 3. Create the Administrative User
        User::create([
            'name' => 'Apex Admin',
            'email' => 'admin@apex.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        // Create a secondary user for testing login
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        // 4. Create the Employees (Technicians)
        $plumber = Employee::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '555-010-1001',
            'skills' => ['plumbing', 'drain-clearing', 'sewer-repair', 'water-heaters'],
        ]);

        $hvac = Employee::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '555-020-2002',
            'skills' => ['ac-diagnostics', 'heat-pump-install', 'ductwork', 'ventilation'],
        ]);

        $electrician = Employee::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Mike',
            'last_name' => 'Miller',
            'phone' => '555-030-3003',
            'skills' => ['high-voltage', 'breaker-boxes', 'generator-wiring', 'ev-charging'],
        ]);

        $appliance = Employee::create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Sarah',
            'last_name' => 'Connor',
            'phone' => '555-040-0404',
            'skills' => ['refrigerator-repair', 'dryer-maintenance', 'gas-stoves', 'dishwasher-install'],
        ]);

        // 5. Create Availabilities (Work Shifts)
        // John Doe: Mon, Wed, Fri (08:00 - 16:00)
        foreach ([1, 3, 5] as $day) {
            Availability::create([
                'tenant_id' => $tenant->id,
                'employee_id' => $plumber->id,
                'day_of_week' => $day,
                'start_time' => '08:00',
                'end_time' => '16:00',
                'is_active' => true,
            ]);
        }

        // Jane Smith: Tue, Thu (09:00 - 17:00), Sat (10:00 - 15:00)
        foreach ([2, 4] as $day) {
            Availability::create([
                'tenant_id' => $tenant->id,
                'employee_id' => $hvac->id,
                'day_of_week' => $day,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_active' => true,
            ]);
        }
        Availability::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $hvac->id,
            'day_of_week' => 6, // Sat
            'start_time' => '10:00',
            'end_time' => '15:00',
            'is_active' => true,
        ]);

        // Mike Miller: Mon, Tue, Thu, Fri (08:00 - 17:00)
        foreach ([1, 2, 4, 5] as $day) {
            Availability::create([
                'tenant_id' => $tenant->id,
                'employee_id' => $electrician->id,
                'day_of_week' => $day,
                'start_time' => '08:00',
                'end_time' => '17:00',
                'is_active' => true,
            ]);
        }

        // Sarah Connor: Wed, Fri (08:00 - 17:00)
        foreach ([3, 5] as $day) {
            Availability::create([
                'tenant_id' => $tenant->id,
                'employee_id' => $appliance->id,
                'day_of_week' => $day,
                'start_time' => '08:00',
                'end_time' => '17:00',
                'is_active' => true,
            ]);
        }

        // 6. Create Bookings (Aligned with Current Week so they render on the Dashboard calendar)
        // Get start of current week (Monday)
        $startOfWeek = Carbon::now()->startOfWeek(); // Monday

        // John Doe: Monday at 10:00
        Booking::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $plumber->id,
            'customer_phone' => '555-901-2093',
            'job_details' => 'Sump pump overflow diagnostic & backup battery installation',
            'status' => 'booked',
            'scheduled_start' => $startOfWeek->copy()->addHours(10), // Monday 10:00
        ]);

        // John Doe: Wednesday at 13:00
        Booking::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $plumber->id,
            'customer_phone' => '555-894-3294',
            'job_details' => 'Leaky main water line valve replacement',
            'status' => 'booked',
            'scheduled_start' => $startOfWeek->copy()->addDays(2)->addHours(13), // Wednesday 13:00
        ]);

        // Jane Smith: Tuesday at 11:00
        Booking::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $hvac->id,
            'customer_phone' => '555-321-4567',
            'job_details' => 'AC condenser fan motor replacement and level check',
            'status' => 'booked',
            'scheduled_start' => $startOfWeek->copy()->addDays(1)->addHours(11), // Tuesday 11:00
        ]);

        // Jane Smith: Thursday at 14:30
        Booking::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $hvac->id,
            'customer_phone' => '555-456-7890',
            'job_details' => 'Furnace tune-up & smart thermostat upgrade',
            'status' => 'booked',
            'scheduled_start' => $startOfWeek->copy()->addDays(3)->addHours(14)->addMinutes(30), // Thursday 14:30
        ]);

        // Mike Miller: Monday at 08:30
        Booking::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $electrician->id,
            'customer_phone' => '555-678-9012',
            'job_details' => 'EV charging station outlet wiring and load panel check',
            'status' => 'booked',
            'scheduled_start' => $startOfWeek->copy()->addHours(8)->addMinutes(30), // Monday 08:30
        ]);

        // Mike Miller: Friday at 15:00
        Booking::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $electrician->id,
            'customer_phone' => '555-789-0123',
            'job_details' => 'Whole-home surge protector installation in panel',
            'status' => 'booked',
            'scheduled_start' => $startOfWeek->copy()->addDays(4)->addHours(15), // Friday 15:00
        ]);

        // Sarah Connor: Wednesday at 09:00
        Booking::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $appliance->id,
            'customer_phone' => '555-890-1234',
            'job_details' => 'Commercial freezer refrigerator diagnostic & coolant recharge',
            'status' => 'booked',
            'scheduled_start' => $startOfWeek->copy()->addDays(2)->addHours(9), // Wednesday 09:00
        ]);

        // Reset scope at end of seed
        TenantScope::setTenantId(null);
    }
}
