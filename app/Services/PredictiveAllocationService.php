<?php

namespace App\Services;

use App\Events\TechnicianAllocated;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class PredictiveAllocationService
{
    /**
     * Compute workload metrics and allocate the booking to the optimal technician.
     */
    public function allocateTechnician(Tenant $tenant, Booking $booking, ?string $requiredSkill = null): ?Employee
    {
        // 1. Get technicians matching required skill (if specified) under tenant scope
        $query = Employee::where('tenant_id', $tenant->id);

        $employees = $query->get();

        if ($requiredSkill) {
            $employees = $employees->filter(function ($emp) use ($requiredSkill) {
                $skills = $emp->skills;
                if (is_array($skills)) {
                    return in_array($requiredSkill, $skills);
                }

                return false;
            });
        }

        if ($employees->isEmpty()) {
            // Fallback to all employees if skill filter is too strict
            $employees = Employee::where('tenant_id', $tenant->id)->get();
        }

        if ($employees->isEmpty()) {
            return null;
        }

        $bestEmployee = null;
        $highestScore = -1.0;

        $sevenDaysAgo = now()->subDays(7);

        foreach ($employees as $employee) {
            // Completed jobs in last 7 days
            $completedJobs = Booking::where('employee_id', $employee->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $sevenDaysAgo)
                ->count();

            // Hours logged in last 7 days
            $bookingsInWeek = Booking::where('employee_id', $employee->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $sevenDaysAgo)
                ->get();

            $hoursLogged = 0.0;
            foreach ($bookingsInWeek as $b) {
                if ($b->completed_at && $b->en_route_at) {
                    $hoursLogged += $b->completed_at->diffInMinutes($b->en_route_at) / 60.0;
                } else {
                    $hoursLogged += 2.0; // Sensible default: 2 hours per job
                }
            }

            // Average travel time
            $averageTravelTime = Booking::where('employee_id', $employee->id)
                ->where('completed_at', '>=', $sevenDaysAgo)
                ->avg('travel_time') ?? 15.0; // default 15 minutes

            // Workload index
            $workload = $completedJobs + ($hoursLogged / 8.0);

            // Compute Predictive Resource Allocation Index (Omega_allocation)
            // Lower workload and travel time yields higher score
            $score = (100.0 / (1.0 + $workload)) + (50.0 / (1.0 + ($averageTravelTime / 15.0)));

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestEmployee = $employee;
            }
        }

        if ($bestEmployee) {
            // Update booking with the chosen employee
            $booking->employee_id = $bestEmployee->id;
            $booking->save();

            // Broadcast status change via Laravel Reverb
            $employeeName = "{$bestEmployee->first_name} {$bestEmployee->last_name}";
            event(new TechnicianAllocated(
                $tenant->id,
                $bestEmployee->id,
                $employeeName,
                'allocated',
                (float) $highestScore
            ));

            Log::info("Predictive dispatch matching complete. Booking {$booking->id} assigned to {$employeeName} with score ".round($highestScore, 2));
        }

        return $bestEmployee;
    }
}
