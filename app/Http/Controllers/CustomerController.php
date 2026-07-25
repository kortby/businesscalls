<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Customer;
use App\Models\Scopes\TenantScope;
use App\Models\ServiceJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CustomerController extends Controller
{
    /**
     * Display a listing of distinct customers and their activity.
     */
    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', Customer::class);

        // Fetch all registered customers from the database
        $customers = Customer::orderBy('name')->get()->map(function ($cust) {
            $phone = $cust->phone;
            $jobs = ServiceJob::where('customer_id', $cust->id)
                ->with('employee')
                ->latest()
                ->get()
                ->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'title' => $job->title,
                        'description' => $job->description ?: '',
                        'status' => $job->status,
                        'steps' => $job->steps ?: [],
                        'employee_name' => $job->employee ? ($job->employee->first_name.' '.$job->employee->last_name) : 'Unassigned',
                        'created_at' => $job->created_at->format('M d, Y g:i A'),
                    ];
                });

            $bookings = Booking::where('customer_phone', $phone)
                ->with('employee')
                ->latest()
                ->get()
                ->map(function ($b) {
                    return [
                        'id' => $b->id,
                        'service_type' => $b->service_type,
                        'requested_time' => $b->requested_time ? $b->requested_time->format('M d, Y g:i A') : 'N/A',
                        'status' => $b->status,
                        'technician_name' => $b->employee ? ($b->employee->first_name.' '.$b->employee->last_name) : 'Auto-Assigned',
                        'created_at' => $b->created_at->format('M d, Y'),
                    ];
                });

            $callLogs = CallLog::where('customer_phone', $phone)
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($c) {
                    $summaryObj = json_decode($c->summary, true) ?: [];

                    return [
                        'id' => $c->id,
                        'summary' => $summaryObj['summary'] ?? $c->summary ?? 'No call summary',
                        'status' => $c->status,
                        'duration' => $c->duration ? round($c->duration / 60, 1).' mins' : 'N/A',
                        'created_at' => $c->created_at->diffForHumans(),
                    ];
                });

            $totalBookings = count($bookings);
            $totalCalls = CallLog::where('customer_phone', $phone)->count();
            $totalJobs = count($jobs);

            $latestCall = CallLog::where('customer_phone', $phone)->latest()->first();
            $summary = null;
            if ($latestCall) {
                $summaryObj = json_decode($latestCall->summary, true) ?: [];
                $summary = $summaryObj['summary'] ?? $latestCall->summary;
            }

            return [
                'id' => $cust->id,
                'phone' => $cust->phone,
                'name' => $cust->name,
                'email' => $cust->email ?: '',
                'notes' => $cust->notes ?: '',
                'total_jobs' => $totalJobs,
                'total_bookings' => $totalBookings,
                'total_calls' => $totalCalls,
                'latest_call_date' => $latestCall ? $latestCall->created_at->diffForHumans() : 'N/A',
                'latest_call_summary' => $summary ?: 'No call history.',
                'latest_call_status' => $latestCall ? $latestCall->status : 'N/A',
                'is_profile' => true,
                'jobs' => $jobs,
                'bookings' => $bookings,
                'call_logs' => $callLogs,
            ];
        })->toArray();

        // Find any caller phone numbers without a saved Customer record
        $profilePhones = array_column($customers, 'phone');

        $bookingPhones = Booking::select('customer_phone')->distinct()->pluck('customer_phone')->toArray();
        $callLogPhones = CallLog::select('customer_phone')->distinct()->pluck('customer_phone')->toArray();
        $allPhones = array_unique(array_merge($bookingPhones, $callLogPhones));

        foreach ($allPhones as $phone) {
            if (empty($phone) || $phone === 'Unknown' || in_array($phone, $profilePhones)) {
                continue;
            }

            $bookings = Booking::where('customer_phone', $phone)
                ->with('employee')
                ->latest()
                ->get()
                ->map(function ($b) {
                    return [
                        'id' => $b->id,
                        'service_type' => $b->service_type,
                        'requested_time' => $b->requested_time ? $b->requested_time->format('M d, Y g:i A') : 'N/A',
                        'status' => $b->status,
                        'technician_name' => $b->employee ? ($b->employee->first_name.' '.$b->employee->last_name) : 'Auto-Assigned',
                        'created_at' => $b->created_at->format('M d, Y'),
                    ];
                });

            $callLogs = CallLog::where('customer_phone', $phone)
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($c) {
                    $summaryObj = json_decode($c->summary, true) ?: [];

                    return [
                        'id' => $c->id,
                        'summary' => $summaryObj['summary'] ?? $c->summary ?? 'No call summary',
                        'status' => $c->status,
                        'duration' => $c->duration ? round($c->duration / 60, 1).' mins' : 'N/A',
                        'created_at' => $c->created_at->diffForHumans(),
                    ];
                });

            $totalBookings = count($bookings);
            $totalCalls = CallLog::where('customer_phone', $phone)->count();

            $latestCall = CallLog::where('customer_phone', $phone)->latest()->first();
            $callerName = 'Customer';
            $summary = null;
            if ($latestCall) {
                $summaryObj = json_decode($latestCall->summary, true) ?: [];
                $callerName = $summaryObj['caller_name'] ?? 'Customer';
                $summary = $summaryObj['summary'] ?? $latestCall->summary;
            }

            $customers[] = [
                'id' => null,
                'phone' => $phone,
                'name' => $callerName,
                'email' => '',
                'notes' => '',
                'total_jobs' => 0,
                'total_bookings' => $totalBookings,
                'total_calls' => $totalCalls,
                'latest_call_date' => $latestCall ? $latestCall->created_at->diffForHumans() : 'N/A',
                'latest_call_summary' => $summary ?: 'No call history.',
                'latest_call_status' => $latestCall ? $latestCall->status : 'N/A',
                'is_profile' => false,
                'jobs' => [],
                'bookings' => $bookings,
                'call_logs' => $callLogs,
            ];
        }

        $user = $request->user();

        return Inertia::render('customers/Index', [
            'customers' => $customers,
            'permissions' => [
                'canCreate' => $user ? Gate::allows('create', Customer::class) : false,
                'canUpdate' => true,
                'canDelete' => $user ? Gate::allows('delete', new Customer(['tenant_id' => $user->tenant_id])) : false,
                'canImport' => $user ? Gate::allows('import', Customer::class) : false,
            ],
        ]);
    }

    /**
     * Display the specified customer record.
     */
    public function show(Request $request, Customer $customer)
    {
        Gate::authorize('view', $customer);

        $phone = $customer->phone;
        $jobs = ServiceJob::where('customer_id', $customer->id)->with('employee')->latest()->get();
        $bookings = Booking::where('customer_phone', $phone)->with('employee')->latest()->get();
        $callLogs = CallLog::where('customer_phone', $phone)->latest()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'customer' => $customer,
                'jobs' => $jobs,
                'bookings' => $bookings,
                'call_logs' => $callLogs,
            ]);
        }

        return Inertia::render('customers/Show', [
            'customer' => $customer,
            'jobs' => $jobs,
            'bookings' => $bookings,
            'call_logs' => $callLogs,
        ]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Customer::class);

        $tenantId = TenantScope::getTenantId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50', Rule::unique('customers')->where('tenant_id', $tenantId)],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'action' => 'customer_created',
            'ip_address' => $request->ip(),
            'browser_agent' => $request->userAgent(),
            'payload' => [
                'id' => $customer->id,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
            ],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer profile created successfully.')]);

        return redirect()->back();
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('update', $customer);

        $tenantId = TenantScope::getTenantId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50', Rule::unique('customers')->where('tenant_id', $tenantId)->ignore($customer->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldPhone = $customer->phone;

        $customer->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($oldPhone !== $validated['phone']) {
            Booking::where('customer_phone', $oldPhone)->update(['customer_phone' => $validated['phone']]);
            CallLog::where('customer_phone', $oldPhone)->update(['customer_phone' => $validated['phone']]);
        }

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'action' => 'customer_updated',
            'ip_address' => $request->ip(),
            'browser_agent' => $request->userAgent(),
            'payload' => [
                'id' => $customer->id,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
            ],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer profile updated successfully.')]);

        return redirect()->back();
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('delete', $customer);

        $tenantId = TenantScope::getTenantId();
        $name = $customer->name;
        $phone = $customer->phone;

        $customer->delete();

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'action' => 'customer_deleted',
            'ip_address' => $request->ip(),
            'browser_agent' => $request->userAgent(),
            'payload' => [
                'name' => $name,
                'phone' => $phone,
            ],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer profile removed successfully.')]);

        return redirect()->back();
    }

    /**
     * Import customers from a CSV file.
     */
    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('import', Customer::class);

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $data = array_map('str_getcsv', file($path));
        if (count($data) <= 1) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('The uploaded CSV is empty or invalid.')]);

            return redirect()->back();
        }

        $headers = array_map('trim', array_map('strtolower', $data[0]));

        $nameIdx = array_search('name', $headers);
        $phoneIdx = array_search('phone', $headers);
        $emailIdx = array_search('email', $headers);
        $notesIdx = array_search('notes', $headers);

        if ($nameIdx === false || $phoneIdx === false) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('The CSV must contain "name" and "phone" columns.')]);

            return redirect()->back();
        }

        $tenantId = TenantScope::getTenantId();
        $importedCount = 0;

        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            if (count($row) < 2) {
                continue;
            }

            $name = trim($row[$nameIdx] ?? '');
            $phone = trim($row[$phoneIdx] ?? '');
            $email = $emailIdx !== false ? trim($row[$emailIdx] ?? '') : null;
            $notes = $notesIdx !== false ? trim($row[$notesIdx] ?? '') : null;

            if (empty($name) || empty($phone)) {
                continue;
            }

            Customer::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'phone' => $phone,
                ],
                [
                    'name' => $name,
                    'email' => $email ?: null,
                    'notes' => $notes ?: null,
                ]
            );
            $importedCount++;
        }

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'action' => 'customers_imported',
            'ip_address' => $request->ip(),
            'browser_agent' => $request->userAgent(),
            'payload' => [
                'count' => $importedCount,
            ],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':count customers imported successfully.', ['count' => $importedCount])]);

        return redirect()->back();
    }
}
