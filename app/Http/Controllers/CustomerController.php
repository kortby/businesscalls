<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\CallLog;
use App\Models\Customer;
use App\Models\Scopes\TenantScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CustomerController extends Controller
{
    /**
     * Display a listing of distinct customers (phone numbers) and their activity.
     */
    public function index(Request $request): InertiaResponse
    {
        // Fetch all customers from the customers table
        $customers = Customer::orderBy('name')->get()->map(function ($cust) {
            $phone = $cust->phone;
            $totalBookings = Booking::where('customer_phone', $phone)->count();
            $totalCalls = CallLog::where('customer_phone', $phone)->count();

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
                'total_bookings' => $totalBookings,
                'total_calls' => $totalCalls,
                'latest_call_date' => $latestCall ? $latestCall->created_at->diffForHumans() : 'N/A',
                'latest_call_summary' => $summary ?: 'No call history.',
                'latest_call_status' => $latestCall ? $latestCall->status : 'N/A',
                'is_profile' => true,
            ];
        })->toArray();

        // Now find any phone numbers in bookings/call_logs that don't have a Customer profile
        $profilePhones = array_column($customers, 'phone');

        $bookingPhones = Booking::select('customer_phone')->distinct()->pluck('customer_phone')->toArray();
        $callLogPhones = CallLog::select('customer_phone')->distinct()->pluck('customer_phone')->toArray();
        $allPhones = array_unique(array_merge($bookingPhones, $callLogPhones));

        foreach ($allPhones as $phone) {
            if (empty($phone) || $phone === 'Unknown' || in_array($phone, $profilePhones)) {
                continue;
            }

            $totalBookings = Booking::where('customer_phone', $phone)->count();
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
                'total_bookings' => $totalBookings,
                'total_calls' => $totalCalls,
                'latest_call_date' => $latestCall ? $latestCall->created_at->diffForHumans() : 'N/A',
                'latest_call_summary' => $summary ?: 'No call history.',
                'latest_call_status' => $latestCall ? $latestCall->status : 'N/A',
                'is_profile' => false,
            ];
        }

        return Inertia::render('customers/Index', [
            'customers' => $customers,
        ]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenantId = TenantScope::getTenantId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50', Rule::unique('customers')->where('tenant_id', $tenantId)],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        Customer::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create compliance AuditLog
        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'action' => 'customer_created',
            'ip_address' => $request->ip(),
            'browser_agent' => $request->userAgent(),
            'payload' => [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
            ],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer profile created successfully.')]);

        return redirect()->back();
    }

    /**
     * Import customers from a CSV file.
     */
    public function import(Request $request): RedirectResponse
    {
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

        // Find header positions
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

        // Skip header row
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

            // Check unique phone number per tenant
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

        // Create compliance AuditLog
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
