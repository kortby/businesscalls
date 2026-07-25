<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine whether the user can view customer listing.
     */
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view a customer profile.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id;
    }

    /**
     * Determine whether the user can create a customer profile.
     */
    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update a customer profile.
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id;
    }

    /**
     * Determine whether the user can delete a customer profile.
     */
    public function delete(User $user, Customer $customer): bool
    {
        if ($user->tenant_id !== $customer->tenant_id) {
            return false;
        }

        // Only Admins and Supervisors can delete customer records
        return $user->isSupervisor() || $user->isAdmin();
    }

    /**
     * Determine whether the user can bulk import customers.
     */
    public function import(User $user): bool
    {
        return $user->isSupervisor() || $user->isAdmin();
    }
}
