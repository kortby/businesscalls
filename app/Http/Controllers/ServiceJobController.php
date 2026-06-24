<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use App\Models\ServiceJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ServiceJobController extends Controller
{
    /**
     * Display a listing of service jobs with customer and employee lists.
     */
    public function index(Request $request): InertiaResponse
    {
        $jobs = ServiceJob::with(['customer', 'employee'])
            ->latest()
            ->get();

        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone']);
        $employees = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'phone']);

        return Inertia::render('jobs/Index', [
            'jobs' => $jobs,
            'customers' => $customers,
            'employees' => $employees,
        ]);
    }

    /**
     * Store a newly created service job.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenantId = TenantScope::getTenantId();

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'string', 'in:pending,in_progress,completed,cancelled'],
            'steps' => ['nullable', 'array'],
        ]);

        ServiceJob::create([
            'tenant_id' => $tenantId,
            'customer_id' => $validated['customer_id'],
            'employee_id' => $validated['employee_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'steps' => $validated['steps'] ?? [],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Service job created successfully.')]);

        return redirect()->back();
    }

    /**
     * Update the specified service job.
     */
    public function update(Request $request, ServiceJob $job): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'string', 'in:pending,in_progress,completed,cancelled'],
            'steps' => ['nullable', 'array'],
        ]);

        $job->update([
            'customer_id' => $validated['customer_id'],
            'employee_id' => $validated['employee_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'steps' => $validated['steps'] ?? [],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Service job updated successfully.')]);

        return redirect()->back();
    }

    /**
     * Remove the specified service job.
     */
    public function destroy(ServiceJob $job): RedirectResponse
    {
        $job->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Service job deleted successfully.')]);

        return redirect()->back();
    }
}
