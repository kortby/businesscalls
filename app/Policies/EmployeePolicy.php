<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any employees directory.
     */
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view a specific employee.
     */
    public function view(User $user, Employee $employee): bool
    {
        if ($user->tenant_id !== $employee->tenant_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can create new employees.
     */
    public function create(User $user): bool
    {
        return $user->isSupervisor() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update an employee profile.
     */
    public function update(User $user, Employee $employee): bool
    {
        if ($user->tenant_id !== $employee->tenant_id) {
            return false;
        }

        if ($user->isSupervisor() || $user->isAdmin()) {
            return true;
        }

        // Technicians can update ONLY their own employee record
        return $user->employee?->id === $employee->id;
    }

    /**
     * Determine whether the user can delete an employee.
     */
    public function delete(User $user, Employee $employee): bool
    {
        if ($user->tenant_id !== $employee->tenant_id) {
            return false;
        }

        // Only Admins and Supervisors can delete employees
        return $user->isSupervisor() || $user->isAdmin();
    }
}
