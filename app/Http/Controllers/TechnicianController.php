<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CallLog;
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

        $activeCalls = [];
        $phones = $bookings->pluck('customer_phone')->filter()->toArray();
        if (! empty($phones)) {
            $activeCalls = CallLog::whereIn('customer_phone', $phones)
                ->where('status', 'ongoing')
                ->get()
                ->map(fn ($cl) => [
                    'call_id' => $cl->call_id,
                    'customer_phone' => $cl->customer_phone,
                ])
                ->toArray();
        }

        return Inertia::render('technician/Dashboard', [
            'employee' => $employee,
            'bookings' => $bookings,
            'jCompleted' => $j_completed,
            'tScheduled' => $t_scheduled,
            'sumTravel' => $sum_travel,
            'performanceScore' => $performanceScore,
            'activeCalls' => $activeCalls,
            'passkeys' => $user->passkeys()->get()->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Show the technician skill-up progression roadmap view.
     */
    public function skillUp(Request $request): InertiaResponse
    {
        $user = $request->user();

        if ($user->tenant_id) {
            TenantScope::setTenantId($user->tenant_id);
        }

        $employee = Employee::where('user_id', $user->id)->first();

        if (! $employee) {
            abort(403, 'User is not linked to any technician employee profile.');
        }

        $today = now()->startOfDay();
        $bookings = Booking::where('employee_id', $employee->id)
            ->whereDate('scheduled_start', $today)
            ->get();

        $mascotState = 0; // Idle

        // State 3 (Sad Error): If route delay occurs (ETA exceeded scheduled start)
        $hasDelay = $bookings->contains(function ($booking) {
            return in_array($booking->status, ['booked', 'en_route']) && $booking->scheduled_start->isPast();
        });

        // State 1 (Scanning / Active Emergency): If there are active emergency bookings
        $hasEmergency = $bookings->contains(function ($booking) {
            if (in_array($booking->status, ['completed', 'canceled'])) {
                return false;
            }
            if ($booking->priority_state === 'emergency') {
                return true;
            }

            return ! empty($booking->urgency_markers) && count($booking->urgency_markers) > 0;
        });

        // State 2 (Victory / Positive CSAT): If technician has completed bookings with positive CSAT
        $completedPhones = $bookings->where('status', 'completed')->pluck('customer_phone')->filter()->toArray();
        $hasPositiveCsat = false;
        if (! empty($completedPhones)) {
            $hasPositiveCsat = CallLog::whereIn('customer_phone', $completedPhones)
                ->where('csat_score', '>=', 80)
                ->exists();
        }

        if ($hasEmergency) {
            $mascotState = 1; // Scanning/Triage
        } elseif ($hasDelay) {
            $mascotState = 3; // Sad Error/Delay
        } elseif ($hasPositiveCsat || $bookings->where('status', 'completed')->isNotEmpty()) {
            $mascotState = 2; // Victory/Happy
        }

        return Inertia::render('technician/SkillUp', [
            'employee' => $employee,
            'mascotState' => $mascotState,
            'hasEmergency' => $hasEmergency,
            'hasDelay' => $hasDelay,
            'hasPositiveCsat' => $hasPositiveCsat,
            'milestones' => [
                [
                    'id' => 1,
                    'title' => 'Emergency Triage Hero',
                    'description' => 'Successfully handle a high urgency trade call.',
                    'status' => $hasEmergency ? 'active' : 'completed',
                    'icon' => '🔥',
                ],
                [
                    'id' => 2,
                    'title' => '5 Consecutive Five-Star Reviews',
                    'description' => 'Maintain top client satisfaction ratings across calls.',
                    'status' => $hasPositiveCsat ? 'completed' : 'locked',
                    'icon' => '⭐️',
                ],
                [
                    'id' => 3,
                    'title' => 'Fastest Diagnostic ETA',
                    'description' => 'Arrive on site within 5 minutes of predicted route schedule.',
                    'status' => $hasDelay ? 'locked' : 'completed',
                    'icon' => '⚡',
                ],
                [
                    'id' => 4,
                    'title' => 'Master Heat Pump Certified',
                    'description' => 'Verify advanced heat pump diagnostics through call logs.',
                    'status' => 'locked',
                    'icon' => '❄️',
                ],
            ],
        ]);
    }
}
