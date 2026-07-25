<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        Gate::authorize('viewAny', Employee::class);

        $employees = Employee::with(['user', 'availabilities'])
            ->orderBy('first_name')
            ->get();

        $user = $request->user();

        return Inertia::render('employees/Index', [
            'employees' => $employees,
            'permissions' => [
                'canCreate' => $user ? Gate::allows('create', Employee::class) : false,
                'canDelete' => $user ? Gate::allows('delete', new Employee(['tenant_id' => $user->tenant_id])) : false,
            ],
        ]);
    }

    /**
     * Display the specified employee.
     */
    public function show(Request $request, Employee $employee)
    {
        Gate::authorize('view', $employee);

        if ($request->wantsJson()) {
            return response()->json($employee->load(['user', 'availabilities', 'bookings']));
        }

        return Inertia::render('employees/Index', [
            'employees' => Employee::with(['user', 'availabilities'])->where('id', $employee->id)->get(),
        ]);
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Employee::class);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'role' => ['nullable', 'string', 'in:admin,supervisor,technician'],
            'skills' => ['nullable', 'array'],
            'notification_preference' => ['required', 'string', 'in:sms,email,both'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $role = $validated['role'] ?? 'technician';

        $tenantId = TenantScope::getTenantId();

        $userId = null;
        if (! empty($validated['email'])) {
            $existingUser = User::where('email', $validated['email'])->first();
            if ($existingUser) {
                $userId = $existingUser->id;
                $existingUser->update([
                    'role' => $role,
                    'is_supervisor' => in_array($role, ['admin', 'supervisor']),
                ]);
            } else {
                $user = User::create([
                    'name' => $validated['first_name'].' '.$validated['last_name'],
                    'email' => $validated['email'],
                    'password' => Hash::make('password'),
                    'tenant_id' => $tenantId,
                    'role' => $role,
                    'is_supervisor' => in_array($role, ['admin', 'supervisor']),
                ]);
                $userId = $user->id;
            }
        }

        Employee::create([
            'tenant_id' => $tenantId,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'role' => $role,
            'skills' => $validated['skills'] ?? [],
            'notification_preference' => $validated['notification_preference'],
            'user_id' => $userId,
        ]);

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'action' => 'technician_added',
            'ip_address' => $request->ip(),
            'browser_agent' => $request->userAgent(),
            'payload' => [
                'name' => $validated['first_name'].' '.$validated['last_name'],
                'phone' => $validated['phone'],
                'role' => $role,
            ],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employee profile created successfully.')]);

        return redirect()->back();
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'role' => ['nullable', 'string', 'in:admin,supervisor,technician'],
            'skills' => ['nullable', 'array'],
            'notification_preference' => ['required', 'string', 'in:sms,email,both'],
        ]);

        $updateData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'skills' => $validated['skills'] ?? [],
            'notification_preference' => $validated['notification_preference'],
        ];

        if (! empty($validated['role']) && ($request->user()->isAdmin() || $request->user()->isSupervisor())) {
            $updateData['role'] = $validated['role'];
            if ($employee->user) {
                $employee->user->update([
                    'role' => $validated['role'],
                    'is_supervisor' => in_array($validated['role'], ['admin', 'supervisor']),
                ]);
            }
        }

        $employee->update($updateData);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employee profile updated successfully.')]);

        return redirect()->back();
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        Gate::authorize('delete', $employee);

        $employee->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employee profile removed successfully.')]);

        return redirect()->back();
    }
}
