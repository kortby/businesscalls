<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\Scopes\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TechnicianController extends Controller
{
    /**
     * Show the technician passkey login view.
     */
    public function login(): InertiaResponse
    {
        return Inertia::render('technician/Login');
    }

    /**
     * Show the technician mobile PWA dashboard.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        if ($user->tenant_id) {
            TenantScope::setTenantId($user->tenant_id);
        }

        $employee = Employee::where('user_id', $user->id)->first();

        if (! $employee) {
            return redirect()->route('dashboard')->with('error', 'User is not linked to any technician employee profile.');
        }

        $today = now()->startOfDay();

        // 1. Fetch technician's assigned jobs scheduled for today
        $bookings = Booking::where('employee_id', $employee->id)
            ->whereDate('scheduled_start', $today)
            ->oldest('scheduled_start')
            ->get();

        // 2. Compute scoring logic variables
        // j_completed = count of successfully completed jobs today
        $j_completed = Booking::where('employee_id', $employee->id)
            ->whereDate('scheduled_start', $today)
            ->where('status', 'completed')
            ->count();

        // t_scheduled = overall scheduled shifts window in hours today
        $dayOfWeek = now()->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        $availability = $employee->availabilities()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        $t_scheduled = 0.0;
        if ($availability) {
            $start = Carbon::parse($availability->start_time);
            $end = Carbon::parse($availability->end_time);
            $t_scheduled = round($start->diffInMinutes($end) / 60, 4);
        }

        // sum_travel = sum total of transit travel times today (stored in hours)
        $sum_travel = (float) Booking::where('employee_id', $employee->id)
            ->whereDate('scheduled_start', $today)
            ->sum('travel_time');

        // Calculate performance scoring logic Lambda
        $denominator = $t_scheduled + $sum_travel;
        $performanceScore = 0.0;
        if ($denominator > 0) {
            $performanceScore = round($j_completed / $denominator, 2);
        }

        return Inertia::render('technician/Dashboard', [
            'employee' => $employee,
            'bookings' => $bookings,
            'jCompleted' => $j_completed,
            'tScheduled' => $t_scheduled,
            'sumTravel' => $sum_travel,
            'performanceScore' => $performanceScore,
            'passkeys' => $user->passkeys()->get()->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
            ]),
        ]);
    }
}
