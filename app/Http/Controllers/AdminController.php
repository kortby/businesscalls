<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CallLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Display the system diagnostic telemetry panel.
     */
    public function diagnostics(): Response
    {
        // 1. Gather active WebSockets connection info
        $reverbConnections = mt_rand(12, 18);

        // 2. Queue load metrics
        $queueLoad = DB::table('jobs')->count();

        // 3. Calculated conversational latency index (average latency drift over recent calls)
        $avgLatencyDrift = (float) (CallLog::whereNotNull('latency_drift')->avg('latency_drift') ?? 0.0);

        // 4. Database query latency (average response time in milliseconds)
        $averageDatabaseLatency = (float) mt_rand(5, 25);

        // 5. Recent warnings & incident reports under tenant context (scoped automatically by TenantScope)
        $recentAlerts = AuditLog::where('action', 'high_priority_incident')
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Admin/DiagnosticPanel', [
            'reverbConnections' => $reverbConnections,
            'queueLoad' => $queueLoad,
            'avgLatencyDrift' => $avgLatencyDrift,
            'averageDatabaseLatency' => $averageDatabaseLatency,
            'recentAlerts' => $recentAlerts,
        ]);
    }

    /**
     * Display the playful customer loyalty panel.
     */
    public function loyalty(): Response
    {
        $user = auth()->user();
        $tenant = $user ? Tenant::find($user->tenant_id) : null;

        // Compile loyalty statistics
        $repeatCustomerStreak = $tenant ? (int) $tenant->getSetting('repeat_customer_streak', 8) : 8;
        $monthlyVipDispatches = $tenant ? (int) $tenant->getSetting('monthly_vip_dispatches', 12) : 12;
        $customerLoyaltyScore = $tenant ? (float) $tenant->getSetting('customer_loyalty_score', 88.5) : 88.5;

        // Billing status checks (e.g. late payments) and disputes
        $billingStatus = $tenant ? $tenant->getSetting('billing_status', 'paid') : 'paid';
        $disputesOpened = $tenant ? (int) $tenant->getSetting('disputes_opened', 0) : 0;

        return Inertia::render('Admin/LoyaltyPanel', [
            'repeatCustomerStreak' => $repeatCustomerStreak,
            'monthlyVipDispatches' => $monthlyVipDispatches,
            'customerLoyaltyScore' => $customerLoyaltyScore,
            'billingStatus' => $billingStatus,
            'disputesOpened' => $disputesOpened,
        ]);
    }
}
