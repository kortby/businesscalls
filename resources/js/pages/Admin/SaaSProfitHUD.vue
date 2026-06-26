<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    TrendingUp,
    Shield,
    Clock,
    DollarSign,
    Play,
    AlertCircle,
    Check,
    RefreshCw
} from '@lucide/vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

const props = defineProps<{
    tenant: any;
    estimatedRevenueRescued: number;
    billableDriveTimeSaved: number;
    completedMaintenanceAgreementsToday: number;
    capturedSavings: number;
    phiRoi: number;
    mascotState: number;
    targetRevenue: number;
    subscriptionCost: number;
}>();

// Simulation overrides to make verification interactive
const localMascotState = ref(props.mascotState);
const localRevenueRescued = ref(props.estimatedRevenueRescued);
const localDriveTimeSaved = ref(props.billableDriveTimeSaved);
const localCompletedPmToday = ref(props.completedMaintenanceAgreementsToday);

const computedSavings = computed(() => {
    return localRevenueRescued.value + localDriveTimeSaved.value;
});

const computedPhiRoi = computed(() => {
    return props.subscriptionCost > 0 ? (computedSavings.value / props.subscriptionCost) : 1.0;
});

const resetSimulation = () => {
    localMascotState.value = props.mascotState;
    localRevenueRescued.value = props.estimatedRevenueRescued;
    localDriveTimeSaved.value = props.billableDriveTimeSaved;
    localCompletedPmToday.value = props.completedMaintenanceAgreementsToday;
};

const simulateRouteOptimization = () => {
    localMascotState.value = 1; // Scanning radar state
};

const simulateGoalMet = () => {
    localMascotState.value = 2; // Victory dance trigger
    localRevenueRescued.value += 150.00;
};

const simulateBillingError = () => {
    localMascotState.value = 3; // Sad error state
};

</script>

<template>
    <Head title="SaaS Profit & ROI HUD" />

    <div class="min-h-screen bg-[#F0FDF4] p-6 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        <!-- Header Section -->
        <header class="mb-8 flex flex-col gap-4 rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] sm:flex-row sm:items-center sm:justify-between dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]">
            <div class="flex items-center gap-4">
                <div class="rounded-2xl border-4 border-slate-900 bg-emerald-500 p-3 text-white dark:border-slate-100">
                    <TrendingUp class="h-8 w-8 stroke-[3]" />
                </div>
                <div>
                    <h1 class="text-2xl font-black tracking-tight uppercase sm:text-3xl">
                        SaaS Profit & ROI HUD
                    </h1>
                    <p class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">
                        Real-Time Platform Performance, Savings & Value Capture
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <span class="inline-flex items-center rounded-2xl border-4 border-slate-900 bg-[#3B82F6] px-4 py-2 text-sm font-black text-white uppercase dark:border-slate-100">
                    ACTIVE AGREEMENT: {{ tenant.plan }}
                </span>
            </div>
        </header>

        <!-- KPI Grid -->
        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-4">
            <!-- Estimated Revenue Rescued -->
            <div class="flex items-center gap-4 rounded-2xl border-4 border-slate-900 bg-white p-4 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]">
                <div class="rounded-xl border-4 border-slate-900 bg-amber-400 p-2.5 text-slate-950">
                    <DollarSign class="h-6 w-6 stroke-[3]" />
                </div>
                <div>
                    <div class="text-2xl font-black text-amber-500">
                        ${{ localRevenueRescued.toFixed(2) }}
                    </div>
                    <div class="text-xs font-bold text-slate-500 uppercase dark:text-slate-400">
                        Revenue Rescued
                    </div>
                </div>
            </div>

            <!-- Drive Time Saved -->
            <div class="flex items-center gap-4 rounded-2xl border-4 border-slate-900 bg-white p-4 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]">
                <div class="rounded-xl border-4 border-slate-900 bg-blue-400 p-2.5 text-slate-950">
                    <Clock class="h-6 w-6 stroke-[3]" />
                </div>
                <div>
                    <div class="text-2xl font-black text-blue-500">
                        ${{ localDriveTimeSaved.toFixed(2) }}
                    </div>
                    <div class="text-xs font-bold text-slate-500 uppercase dark:text-slate-400">
                        Drive Time Saved
                    </div>
                </div>
            </div>

            <!-- PM Agreements Completed -->
            <div class="flex items-center gap-4 rounded-2xl border-4 border-slate-900 bg-white p-4 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]">
                <div class="rounded-xl border-4 border-slate-900 bg-emerald-400 p-2.5 text-slate-950">
                    <Shield class="h-6 w-6 stroke-[3]" />
                </div>
                <div>
                    <div class="text-2xl font-black text-emerald-500">
                        {{ localCompletedPmToday }}
                    </div>
                    <div class="text-xs font-bold text-slate-500 uppercase dark:text-slate-400">
                        PM Checkups Today
                    </div>
                </div>
            </div>

            <!-- Total Savings -->
            <div class="flex items-center gap-4 rounded-2xl border-4 border-slate-900 bg-emerald-500 p-4 text-white shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]">
                <div class="rounded-xl border-4 border-white bg-white p-2.5 text-emerald-600">
                    <TrendingUp class="h-6 w-6 stroke-[3]" />
                </div>
                <div>
                    <div class="text-2xl font-black">
                        ${{ computedSavings.toFixed(2) }}
                    </div>
                    <div class="text-xs font-bold text-emerald-100 uppercase">
                        Total Captured Value
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Display Panel -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Left Side: Custom Metric Meters & ROI Index Card -->
            <div class="flex flex-col gap-8 lg:col-span-2">
                <div class="flex flex-col rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]">
                    <h2 class="mb-6 text-xl font-black tracking-tight uppercase">
                        ROI Metrics Meter
                    </h2>

                    <!-- ROI Index Meter -->
                    <div class="mb-8 rounded-2xl border-4 border-slate-900 bg-[#F8FAFC] p-6 dark:border-slate-100 dark:bg-slate-950">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-black tracking-widest text-slate-400 uppercase">Platform ROI Index (&Phi;<sub>ROI</sub>)</span>
                                <h3 class="text-3xl font-black text-slate-800 dark:text-slate-100">
                                    {{ computedPhiRoi.toFixed(2) }}x
                                </h3>
                            </div>
                            <span class="rounded-xl border-4 border-slate-900 bg-emerald-400 px-3 py-1.5 text-xs font-black text-slate-950 uppercase dark:border-slate-100">
                                {{ computedPhiRoi >= 1.0 ? 'Profitable' : 'Payback Period' }}
                            </span>
                        </div>

                        <!-- Progress Bar Meter -->
                        <div class="h-6 w-full rounded-full border-4 border-slate-900 bg-slate-200 overflow-hidden dark:border-slate-100">
                            <div class="h-full bg-emerald-500 transition-all duration-500 ease-out" 
                                 :style="{ width: `${Math.min(100, (computedPhiRoi / 5.0) * 100)}%` }">
                            </div>
                        </div>
                        <div class="mt-2 flex justify-between text-xs font-bold text-slate-400">
                            <span>0.0x Break-even</span>
                            <span>2.5x Target</span>
                            <span>5.0x+ Multiplier</span>
                        </div>
                    </div>

                    <!-- Target Goals Tracker -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="rounded-2xl border-4 border-slate-900 p-4 dark:border-slate-100">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-black uppercase">Daily Revenue Goal</span>
                                <Check v-if="computedSavings >= targetRevenue" class="h-5 w-5 text-emerald-500" />
                                <AlertCircle v-else class="h-5 w-5 text-amber-500" />
                            </div>
                            <div class="text-2xl font-black">
                                ${{ computedSavings.toFixed(2) }} / ${{ targetRevenue.toFixed(2) }}
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Goal matches targeted after-hours intake revenue.</p>
                        </div>

                        <div class="rounded-2xl border-4 border-slate-900 p-4 dark:border-slate-100">
                            <span class="text-sm font-black uppercase block mb-2">SaaS Pricing Factor</span>
                            <div class="text-2xl font-black">
                                ${{ subscriptionCost }}/mo
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Monthly cost based on active plan level.</p>
                        </div>
                    </div>
                </div>

                <!-- Simulation Controls for Walkthrough Verification -->
                <div class="rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]">
                    <h3 class="mb-4 text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400">
                        Interactive Walkthrough Controls
                    </h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <button @click="simulateRouteOptimization" 
                                class="duo-btn duo-btn-info text-xs flex items-center justify-center gap-1">
                            <Play class="h-3.5 w-3.5 stroke-[3]" />
                            Optimize Route
                        </button>
                        <button @click="simulateGoalMet" 
                                class="duo-btn duo-btn-success text-xs flex items-center justify-center gap-1">
                            <Check class="h-3.5 w-3.5 stroke-[3]" />
                            Goal Met
                        </button>
                        <button @click="simulateBillingError" 
                                class="duo-btn duo-btn-warning text-xs flex items-center justify-center gap-1"
                                style="background-color: #ef4444; box-shadow: 0 4px 0 0 #b91c1c; color: white;">
                            <AlertCircle class="h-3.5 w-3.5 stroke-[3]" />
                            Billing Error
                        </button>
                        <button @click="resetSimulation" 
                                class="duo-btn duo-btn-muted text-xs flex items-center justify-center gap-1">
                            <RefreshCw class="h-3.5 w-3.5 stroke-[3]" />
                            Reset HUD
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Rive WebGL Mascot Display -->
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center justify-center rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]">
                    <h3 class="mb-4 self-start text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400">
                        AI Dispatch Advisor
                    </h3>

                    <!-- Render Mascot Canvas Component -->
                    <div class="mb-4 aspect-square w-full max-w-[240px]">
                        <DispatcherMascot
                            :state="localMascotState"
                            skin="standard"
                        />
                    </div>

                    <div class="w-full rounded-2xl border-4 border-slate-900 bg-slate-50 p-4 text-xs font-bold dark:border-slate-100 dark:bg-slate-950">
                        <div class="mb-1 flex justify-between">
                            <span>SaaS Health State:</span>
                            <span :class="[localMascotState === 3 ? 'text-rose-500' : 'text-emerald-500']">
                                {{ localMascotState === 3 ? 'ALERT DETECTED' : 'OPERATIONAL' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span>Active Rive Trigger:</span>
                            <span class="font-black text-amber-500 uppercase">State {{ localMascotState }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Duolingo Chunky Geometric Button Styling */
.duo-btn {
    border-radius: 1rem;
    border: 4px solid #0f172a;
    padding: 0.75rem 1rem;
    font-size: 0.825rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    cursor: pointer;
}

.dark .duo-btn {
    border-color: #f1f5f9;
}

.duo-btn:hover {
    transform: translateY(2px);
}

.duo-btn:active {
    transform: translateY(4px);
}

.duo-btn-warning {
    background-color: #fbbf24;
    color: #0f172a;
    box-shadow: 0 4px 0 0 #d97706;
}
.duo-btn-warning:hover {
    box-shadow: 0 2px 0 0 #d97706;
}
.duo-btn-warning:active {
    box-shadow: 0 0px 0 0 #d97706;
}

.duo-btn-success {
    background-color: #10b981;
    color: #ffffff;
    box-shadow: 0 4px 0 0 #059669;
}
.duo-btn-success:hover {
    box-shadow: 0 2px 0 0 #059669;
}
.duo-btn-success:active {
    box-shadow: 0 0px 0 0 #059669;
}

.duo-btn-info {
    background-color: #3b82f6;
    color: #ffffff;
    box-shadow: 0 4px 0 0 #2563eb;
}
.duo-btn-info:hover {
    box-shadow: 0 2px 0 0 #2563eb;
}
.duo-btn-info:active {
    box-shadow: 0 0px 0 0 #2563eb;
}

.duo-btn-muted {
    background-color: #e2e8f0;
    color: #0f172a;
    box-shadow: 0 4px 0 0 #cbd5e1;
}
.dark .duo-btn-muted {
    background-color: #334155;
    color: #ffffff;
    box-shadow: 0 4px 0 0 #1e293b;
}
.duo-btn-muted:hover {
    box-shadow: 0 2px 0 0 #cbd5e1;
}
.dark .duo-btn-muted:hover {
    box-shadow: 0 2px 0 0 #1e293b;
}
.duo-btn-muted:active {
    box-shadow: 0 0px 0 0 #cbd5e1;
}
.dark .duo-btn-muted:active {
    box-shadow: 0 0px 0 0 #1e293b;
}
</style>
