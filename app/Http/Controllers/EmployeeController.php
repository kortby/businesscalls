<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the employees.
     */
    public function index(Request $request): InertiaResponse
    {
        $employees = Employee::with(['availabilities'])
            ->orderBy('first_name')
            ->get();

        return Inertia::render('employees/Index', [
            'employees' => $employees,
        ]);
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'skills' => ['nullable', 'array'],
            'notification_preference' => ['required', 'string', 'in:sms,email,both'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $tenantId = TenantScope::getTenantId();

        $userId = null;
        if (! empty($validated['email'])) {
            // Check if user already exists
            $existingUser = User::where('email', $validated['email'])->first();
            if ($existingUser) {
                $userId = $existingUser->id;
            } else {
                // Create user profile for login access
                $user = User::create([
                    'name' => $validated['first_name'].' '.$validated['last_name'],
                    'email' => $validated['email'],
                    'password' => Hash::make('password'), // temporary password
                    'tenant_id' => $tenantId,
                ]);
                $userId = $user->id;
            }
        }

        Employee::create([
            'tenant_id' => $tenantId,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'skills' => $validated['skills'] ?? [],
            'notification_preference' => $validated['notification_preference'],
            'user_id' => $userId,
        ]);

        // Compliance log plan updates or auditor entries
        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'action' => 'technician_added',
            'ip_address' => $request->ip(),
            'browser_agent' => $request->userAgent(),
            'payload' => [
                'name' => $validated['first_name'].' '.$validated['last_name'],
                'phone' => $validated['phone'],
            ],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technician profile created successfully.')]);

        return redirect()->back();
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'skills' => ['nullable', 'array'],
            'notification_preference' => ['required', 'string', 'in:sms,email,both'],
        ]);

        $employee->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'skills' => $validated['skills'] ?? [],
            'notification_preference' => $validated['notification_preference'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technician profile updated successfully.')]);

        return redirect()->back();
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete(); // automatically triggers technician_removed AuditLog in Employee.php booted deleted hook

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technician profile removed successfully.')]);

        return redirect()->back();
    }
}
