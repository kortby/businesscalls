<?php

namespace App\Http\Controllers;

use App\Events\DispatchUpdated;
use App\Jobs\SendTechnicianAlertJob;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Employee;
use App\Rules\ReCaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BookingController extends Controller
{
    /**
     * Display a listing of appointments.
     */
    public function index(Request $request): InertiaResponse
    {
        return Inertia::render('Bookings', [
            'employees' => Employee::with(['availabilities', 'bookings'])->orderBy('first_name')->get(),
            'bookings' => Booking::with('employee')->latest()->get(),
        ]);
    }

    /**
     * Store a newly created manual booking in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('tenant_id', auth()->user()->tenant_id),
            ],
            'customer_phone' => ['required', 'string', 'max:20'],
            'job_details' => ['required', 'string', 'max:255'],
            'scheduled_start' => ['required', 'date'],
            'recaptcha_token' => [
                app()->environment('testing') ? 'nullable' : 'required',
                new ReCaptcha,
            ],
        ]);

        $employeeId = $validated['employee_id'];
        $requestedTimeCarbon = Carbon::parse($validated['scheduled_start']);
        $dayOfWeek = $requestedTimeCarbon->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        $timeOnly = $requestedTimeCarbon->format('H:i:s');

        // 1. Verify employee shift availability
        $isAvailable = Availability::where('employee_id', $employeeId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('start_time', '<=', $timeOnly)
            ->where('end_time', '>=', $timeOnly)
            ->exists();

        if (! $isAvailable) {
            throw ValidationException::withMessages([
                'scheduled_start' => ['The technician is not scheduled to work during this shift.'],
            ]);
        }

        // 2. Verify overlap schedules (1.5-hour buffer)
        $bufferMinutes = 90;
        $startBuffer = $requestedTimeCarbon->copy()->subMinutes($bufferMinutes);
        $endBuffer = $requestedTimeCarbon->copy()->addMinutes($bufferMinutes);

        $hasOverlap = Booking::where('employee_id', $employeeId)
            ->where('status', 'booked')
            ->whereBetween('scheduled_start', [$startBuffer, $endBuffer])
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'scheduled_start' => ['This booking conflicts with an existing technician appointment (1.5-hour travel buffer enforced).'],
            ]);
        }

        $employee = Employee::find($employeeId);

        // 3. Create the booking
        $booking = Booking::create([
            'employee_id' => $employeeId,
            'customer_phone' => $validated['customer_phone'],
            'job_details' => $validated['job_details'],
            'status' => 'booked',
            'scheduled_start' => $requestedTimeCarbon,
        ]);

        SendTechnicianAlertJob::dispatch($booking);

        // 4. Trigger mascot search -> victory animation live sequence on the dashboard
        $tenantId = auth()->user()->tenant_id;
        if ($tenantId) {
            event(new DispatchUpdated($tenantId, [
                'type' => 'searching',
                'message' => "Manual booking requested for {$employee->first_name} {$employee->last_name}...",
            ]));

            event(new DispatchUpdated($tenantId, [
                'type' => 'success',
                'message' => "Manual booking confirmed for customer {$validated['customer_phone']} at {$requestedTimeCarbon->format('Y-m-d H:i')}.",
                'booking' => $booking->load('employee'),
            ]));
        }

        return redirect()->back();
    }

    /**
     * Update the specified booking in storage.
     */
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('tenant_id', auth()->user()->tenant_id),
            ],
            'customer_phone' => ['required', 'string', 'max:20'],
            'job_details' => ['required', 'string', 'max:255'],
            'scheduled_start' => ['required', 'date'],
        ]);

        $employeeId = $validated['employee_id'];
        $requestedTimeCarbon = Carbon::parse($validated['scheduled_start']);
        $dayOfWeek = $requestedTimeCarbon->dayOfWeek;
        $timeOnly = $requestedTimeCarbon->format('H:i:s');

        // 1. Verify employee shift availability
        $isAvailable = Availability::where('employee_id', $employeeId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('start_time', '<=', $timeOnly)
            ->where('end_time', '>=', $timeOnly)
            ->exists();

        if (! $isAvailable) {
            throw ValidationException::withMessages([
                'scheduled_start' => ['The technician is not scheduled to work during this shift.'],
            ]);
        }

        // 2. Verify overlap schedules (1.5-hour buffer) excluding current booking
        $bufferMinutes = 90;
        $startBuffer = $requestedTimeCarbon->copy()->subMinutes($bufferMinutes);
        $endBuffer = $requestedTimeCarbon->copy()->addMinutes($bufferMinutes);

        $hasOverlap = Booking::where('employee_id', $employeeId)
            ->where('status', 'booked')
            ->where('id', '!=', $booking->id)
            ->whereBetween('scheduled_start', [$startBuffer, $endBuffer])
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'scheduled_start' => ['This booking conflicts with an existing technician appointment (1.5-hour travel buffer enforced).'],
            ]);
        }

        $employee = Employee::find($employeeId);

        $booking->update([
            'employee_id' => $employeeId,
            'customer_phone' => $validated['customer_phone'],
            'job_details' => $validated['job_details'],
            'scheduled_start' => $requestedTimeCarbon,
        ]);

        // Trigger mascot search -> victory animation live sequence on the dashboard
        $tenantId = auth()->user()->tenant_id;
        if ($tenantId) {
            event(new DispatchUpdated($tenantId, [
                'type' => 'searching',
                'message' => "Manual booking update requested for {$employee->first_name} {$employee->last_name}...",
            ]));

            event(new DispatchUpdated($tenantId, [
                'type' => 'success',
                'message' => "Manual booking updated for customer {$validated['customer_phone']} at {$requestedTimeCarbon->format('Y-m-d H:i')}.",
                'booking' => $booking->load('employee'),
            ]));
        }

        return redirect()->back();
    }

    /**
     * Remove the specified booking from storage.
     */
    public function destroy(Booking $booking): RedirectResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $employee = $booking->employee;
        $customerPhone = $booking->customer_phone;

        $booking->delete();

        if ($tenantId) {
            event(new DispatchUpdated($tenantId, [
                'type' => 'error', // Trigger mascot conflict/alert state
                'message' => "Booking for {$customerPhone} assigned to {$employee->first_name} {$employee->last_name} has been canceled.",
            ]));
        }

        return redirect()->back();
    }
}
