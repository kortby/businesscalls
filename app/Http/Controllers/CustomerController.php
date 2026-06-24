<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CallLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CustomerController extends Controller
{
    /**
     * Display a listing of distinct customers (phone numbers) and their activity.
     */
    public function index(Request $request): InertiaResponse
    {
        // Fetch all distinct customer phone numbers from bookings
        $bookingPhones = Booking::select('customer_phone')
            ->distinct()
            ->pluck('customer_phone')
            ->toArray();

        // Fetch all distinct customer phone numbers from call logs
        $callLogPhones = CallLog::select('customer_phone')
            ->distinct()
            ->pluck('customer_phone')
            ->toArray();

        // Merge and find unique phone numbers
        $phones = array_unique(array_merge($bookingPhones, $callLogPhones));

        // Remove empty or "Unknown"
        $phones = array_filter($phones, function ($phone) {
            return ! empty($phone) && $phone !== 'Unknown';
        });

        $customers = [];

        foreach ($phones as $phone) {
            $totalBookings = Booking::where('customer_phone', $phone)->count();
            $totalCalls = CallLog::where('customer_phone', $phone)->count();

            // Get latest call activity
            $latestCall = CallLog::where('customer_phone', $phone)
                ->latest()
                ->first();

            // Extract caller name if summary contains it
            $callerName = 'Customer';
            $summary = null;
            if ($latestCall) {
                $summaryObj = json_decode($latestCall->summary, true) ?: [];
                $callerName = $summaryObj['caller_name'] ?? 'Customer';
                $summary = $summaryObj['summary'] ?? $latestCall->summary;
            }

            $customers[] = [
                'phone' => $phone,
                'name' => $callerName,
                'total_bookings' => $totalBookings,
                'total_calls' => $totalCalls,
                'latest_call_date' => $latestCall ? $latestCall->created_at->diffForHumans() : 'N/A',
                'latest_call_summary' => $summary ?: 'No call history.',
                'latest_call_status' => $latestCall ? $latestCall->status : 'N/A',
            ];
        }

        return Inertia::render('customers/Index', [
            'customers' => $customers,
        ]);
    }
}
