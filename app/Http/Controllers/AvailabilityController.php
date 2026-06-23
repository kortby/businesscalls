<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AvailabilityController extends Controller
{
    /**
     * Display a listing of shift availabilities.
     */
    public function index(Request $request): InertiaResponse
    {
        return Inertia::render('Availabilities', [
            'employees' => Employee::with(['availabilities'])->orderBy('first_name')->get(),
            'availabilities' => Availability::with('employee')->latest()->get(),
        ]);
    }

    /**
     * Store a newly created availability shift in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('tenant_id', auth()->user()->tenant_id),
            ],
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['boolean'],
        ]);

        // Check for overlapping shifts for the same technician on the same day
        $hasOverlap = Availability::where('employee_id', $validated['employee_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->where('is_active', true)
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'start_time' => ['This shift overlaps with an existing scheduled shift for this technician.'],
            ]);
        }

        Availability::create([
            'employee_id' => $validated['employee_id'],
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->back();
    }

    /**
     * Update the specified availability shift in storage.
     */
    public function update(Request $request, Availability $availability): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('tenant_id', auth()->user()->tenant_id),
            ],
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['boolean'],
        ]);

        // Check for overlapping shifts for the same technician on the same day, excluding current ID
        $hasOverlap = Availability::where('employee_id', $validated['employee_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->where('is_active', true)
            ->where('id', '!=', $availability->id)
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'start_time' => ['This shift overlaps with an existing scheduled shift for this technician.'],
            ]);
        }

        $availability->update([
            'employee_id' => $validated['employee_id'],
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->back();
    }

    /**
     * Remove the specified availability shift from storage.
     */
    public function destroy(Availability $availability): RedirectResponse
    {
        $availability->delete();

        return redirect()->back();
    }
}
