<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    CheckCircle,
    XCircle,
    Activity,
    Clock,
    Phone,
    TrendingUp,
    Settings,
    Smile,
    ShieldAlert,
    HelpCircle,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

const props = defineProps<{
    tenant: any;
    callLogs: any[];
    weeklyAvgPhi: number;
    isProcessing: boolean;
    hasRecentError: boolean;
    alpha: number;
    beta: number;
    tMax: number;
}>();

const weeklyPhi = ref(props.weeklyAvgPhi);
const processing = ref(props.isProcessing);
const recentError = ref(props.hasRecentError);
const showMessage = ref<{ type: 'success' | 'error'; text: string } | null>(
    null,
);

// Rive mascot state: 0 = Idle, 1 = Scanning, 2 = Victory, 3 = Error
const mascotState = computed(() => {
    if (recentError.value || weeklyPhi.value < 0.85) {
        return 3; // Error (sad mascot state)
    }

    if (processing.value) {
        return 1; // Scanning radar animation
    }

    if (weeklyPhi.value >= 0.85) {
        return 2; // Victory celebratory animation
    }

    return 0; // Idle
});

const simulateEmergencyCall = () => {
    processing.value = true;
    recentError.value = false;
    showMessage.value = {
        type: 'success',
        text: 'Dynamic Steering: Emergency call active! Voice switched to authoritative Domi tone, silenceTimeout set to 5.0s.',
    };
    setTimeout(() => {
        processing.value = false;
    }, 4000);
};

const simulateCallDropped = () => {
    recentError.value = true;
    showMessage.value = {
        type: 'error',
        text: 'SLA Alert: call_ended with error status! Dispatched notification to supervisor, mascot set to sad error state.',
    };
};

const resolveAllErrors = () => {
    recentError.value = false;
    weeklyPhi.value = 0.89;
    showMessage.value = {
        type: 'success',
        text: 'System Restored: Alerts cleared and CSAT index reset to optimal (0.89).',
    };
};
</script>

<template>
    <Head title="CSAT & SLA Performance Hub" />

    <div
        class="min-h-screen bg-slate-900 px-4 py-8 text-slate-100 dark:bg-slate-950"
    >
        <div class="mx-auto max-w-6xl space-y-8">
            <!-- Header Panel -->
            <div
                class="flex flex-col items-center justify-between gap-6 rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155] md:flex-row"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="rounded-2xl border-b-4 border-emerald-700 bg-emerald-500 p-3"
                    >
                        <Smile class="h-8 w-8 text-white" />
                    </div>
                    <div>
                        <h1
                            class="text-3xl font-black tracking-tight text-white uppercase"
                        >
                            CSAT & Performance Index
                        </h1>
                        <p
                            class="mt-1 text-xs font-bold tracking-wider text-slate-400 uppercase"
                        >
                            Real-time satisfaction scoring, SLA triggers &
                            dynamic call-steering
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        @click="simulateEmergencyCall"
                        :disabled="processing"
                        class="border-indigo-850 cursor-pointer rounded-2xl border-b-4 bg-indigo-600 px-6 py-3 text-xs font-black tracking-wider text-white uppercase shadow-lg transition-all duration-75 hover:bg-indigo-500 active:mt-1 active:border-b-0 disabled:opacity-50"
                    >
                        Simulate Emergency
                    </button>
                    <button
                        @click="simulateCallDropped"
                        class="border-rose-850 cursor-pointer rounded-2xl border-b-4 bg-rose-600 px-6 py-3 text-xs font-black tracking-wider text-white uppercase shadow-lg transition-all duration-75 hover:bg-rose-500 active:mt-1 active:border-b-0"
                    >
                        Simulate Drop
                    </button>
                    <button
                        @click="resolveAllErrors"
                        class="hover:bg-slate-650 cursor-pointer rounded-2xl border-b-4 border-slate-900 bg-slate-700 px-6 py-3 text-xs font-black tracking-wider text-white uppercase shadow-lg transition-all duration-75 active:mt-1 active:border-b-0"
                    >
                        Resolve Alerts
                    </button>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Rive Mascot Card -->
                <div
                    class="relative flex min-h-[360px] flex-col items-center justify-center overflow-hidden rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155]"
                >
                    <h2
                        class="mb-4 text-lg font-black tracking-wider text-slate-300 uppercase"
                    >
                        Dispatcher Mascot
                    </h2>
                    <div
                        class="flex aspect-square w-full max-w-[220px] items-center justify-center"
                    >
                        <DispatcherMascot :state="mascotState" />
                    </div>
                </div>

                <!-- CSAT Metric Card -->
                <div
                    class="flex flex-col justify-between rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155] lg:col-span-2"
                >
                    <div>
                        <h2
                            class="mb-6 border-b-4 border-slate-700 pb-3 text-xl font-black tracking-wider text-white uppercase"
                        >
                            Performance Efficiency Index
                        </h2>

                        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div
                                class="flex flex-col items-center justify-center rounded-2xl border-4 border-slate-700 bg-slate-900 p-4 text-center shadow-[0_4px_0_#334155]"
                            >
                                <span
                                    class="text-xs font-bold tracking-wide text-slate-400 uppercase"
                                    >Weekly Avg CSAT Index</span
                                >
                                <span
                                    class="mt-2 text-4xl font-black"
                                    :class="
                                        weeklyPhi >= 0.85
                                            ? 'text-emerald-450'
                                            : 'text-rose-400'
                                    "
                                >
                                    {{ (weeklyPhi * 100).toFixed(1) }}%
                                </span>
                            </div>

                            <div
                                class="flex flex-col items-center justify-center rounded-2xl border-4 border-slate-700 bg-slate-900 p-4 text-center shadow-[0_4px_0_#334155]"
                            >
                                <span
                                    class="text-xs font-bold tracking-wide text-slate-400 uppercase"
                                    >Ongoing Streams</span
                                >
                                <span
                                    class="mt-2 flex items-center gap-2 text-4xl font-black text-indigo-400"
                                >
                                    <Activity
                                        class="h-6 w-6 animate-pulse"
                                        v-if="processing"
                                    />
                                    {{ processing ? 'Active' : 'Idle' }}
                                </span>
                            </div>

                            <div
                                class="flex flex-col items-center justify-center rounded-2xl border-4 border-slate-700 bg-slate-900 p-4 text-center shadow-[0_4px_0_#334155]"
                            >
                                <span
                                    class="text-xs font-bold tracking-wide text-slate-400 uppercase"
                                    >SLA Outage Warnings</span
                                >
                                <span
                                    class="mt-2 text-4xl font-black"
                                    :class="
                                        recentError
                                            ? 'text-rose-400'
                                            : 'text-emerald-450'
                                    "
                                >
                                    {{ recentError ? 'Alert' : 'None' }}
                                </span>
                            </div>
                        </div>

                        <!-- SLA & CSAT Metrics Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-left">
                                <thead>
                                    <tr
                                        class="border-b-2 border-slate-700 text-xs font-black tracking-wider text-slate-400 uppercase"
                                    >
                                        <th class="px-4 py-2.5">
                                            Integration Target
                                        </th>
                                        <th class="px-4 py-2.5">Event Type</th>
                                        <th class="px-4 py-2.5">
                                            Target Action
                                        </th>
                                        <th class="px-4 py-2.5 text-right">
                                            Mascot Input
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-700 text-sm font-bold text-slate-300"
                                >
                                    <!-- CSAT Complete -->
                                    <tr
                                        class="transition-colors hover:bg-slate-900/30"
                                    >
                                        <td
                                            class="flex items-center gap-2 px-4 py-3"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-emerald-400"
                                            ></span>
                                            <span>CSAT Complete</span>
                                        </td>
                                        <td
                                            class="text-slate-450 px-4 py-3 font-mono text-xs"
                                        >
                                            call_analyzed
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">
                                            Log Scorecard
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right font-mono text-xs text-emerald-400"
                                        >
                                            State Trigger 2
                                        </td>
                                    </tr>

                                    <!-- Call Dropped -->
                                    <tr
                                        class="transition-colors hover:bg-slate-900/30"
                                    >
                                        <td
                                            class="flex items-center gap-2 px-4 py-3"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-rose-500"
                                            ></span>
                                            <span>Call Dropped</span>
                                        </td>
                                        <td
                                            class="text-slate-450 px-4 py-3 font-mono text-xs"
                                        >
                                            call_ended (error status)
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">
                                            Trigger Alert
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right font-mono text-xs text-rose-400"
                                        >
                                            State Trigger 3
                                        </td>
                                    </tr>

                                    <!-- Ongoing Call -->
                                    <tr
                                        class="transition-colors hover:bg-slate-900/30"
                                    >
                                        <td
                                            class="flex items-center gap-2 px-4 py-3"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-indigo-500"
                                            ></span>
                                            <span>Ongoing Call</span>
                                        </td>
                                        <td
                                            class="text-slate-450 px-4 py-3 font-mono text-xs"
                                        >
                                            call_started
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">
                                            Live Captions
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right font-mono text-xs text-indigo-400"
                                        >
                                            State Trigger 1
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Notice alerts box -->
                    <div
                        v-if="showMessage"
                        class="mt-6 flex items-center gap-3 rounded-2xl border-4 p-4 text-xs font-black tracking-wide uppercase"
                        :class="
                            showMessage.type === 'success'
                                ? 'border-emerald-700 bg-emerald-950/40 text-emerald-400 shadow-[0_4px_0_#047857]'
                                : 'border-rose-700 bg-rose-950/40 text-rose-400 shadow-[0_4px_0_#be123c]'
                        "
                    >
                        <CheckCircle
                            class="h-5 w-5 flex-shrink-0"
                            v-if="showMessage.type === 'success'"
                        />
                        <ShieldAlert class="h-5 w-5 flex-shrink-0" v-else />
                        <span>{{ showMessage.text }}</span>
                    </div>
                </div>
            </div>

            <!-- Mathematical Formula Explanation Card -->
            <div
                class="rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155]"
            >
                <h2
                    class="mb-4 border-b-4 border-slate-700 pb-3 text-xl font-black tracking-wider text-white uppercase"
                >
                    CSAT Index Formulation
                </h2>
                <div class="grid grid-cols-1 items-center gap-8 lg:grid-cols-2">
                    <div class="text-slate-350 space-y-4 text-sm font-bold">
                        <p>
                            We calculate the Customer Satisfaction and Agent
                            Performance Efficiency Index ($\Phi_{\text{CSAT}}$)
                            using the following normalized formula:
                        </p>
                        <div
                            class="rounded-2xl border-2 border-slate-700 bg-slate-900 p-4 text-center font-mono text-xs leading-relaxed text-slate-300"
                        >
                            $$\Phi_{\text{CSAT}}=\sum_{c\in C}\left(\alpha\cdot
                            S_c+\beta\cdot\left(1-\frac{\tau_c}{T_{\text{max}}}\right)\right)\cdot\mu_{\text{resolution}}$$
                        </div>
                        <ul class="list-disc space-y-1 pl-5">
                            <li>
                                <strong>$S_c$</strong>: Call satisfaction score
                                (1-5 scale) scaled to $[0.0, 1.0]$.
                            </li>
                            <li>
                                <strong>$\tau_c$</strong>: Telephony response
                                latency in milliseconds.
                            </li>
                            <li>
                                <strong>$T_{\text{max}}$</strong>: Threshold
                                limit (configured to {{ tMax }}ms).
                            </li>
                            <li>
                                <strong>$\mu_{\text{resolution}}$</strong>:
                                Binary indicator (1 if booking is successfully
                                resolved, 0 otherwise).
                            </li>
                            <li>
                                <strong>$\alpha, \beta$</strong>: Weight ratios
                                (configured to $\alpha$ = {{ alpha }}, $\beta$ =
                                {{ beta }}).
                            </li>
                        </ul>
                    </div>

                    <div
                        class="space-y-2 rounded-2xl border-2 border-slate-700 bg-slate-900 p-4 font-mono text-xs text-slate-300"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-700 pb-2 font-bold tracking-wider text-indigo-400 uppercase"
                        >
                            <span>SLA Target Weight Parameters</span>
                            <span
                                class="rounded-md bg-slate-800 px-2 py-0.5 text-[9px] text-slate-400"
                                >Config</span
                            >
                        </div>
                        <div class="space-y-1">
                            <div>
                                Satisfaction Weight ($\alpha$): {{ alpha }}
                            </div>
                            <div>Latency Weight ($\beta$): {{ beta }}</div>
                            <div>
                                Max Latency Threshold ($T_{\text{max}}$):
                                {{ tMax }}ms
                            </div>
                            <div>Baseline CSAT Threshold: 0.85</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Telephony Calls Table -->
            <div
                class="rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155]"
            >
                <h2
                    class="mb-6 border-b-4 border-slate-700 pb-3 text-xl font-black tracking-wider text-white uppercase"
                >
                    Recent Telephony & CSAT Evaluations
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr
                                class="border-b-2 border-slate-700 text-xs font-black tracking-wider text-slate-400 uppercase"
                            >
                                <th class="px-4 py-2.5">Call Identifier</th>
                                <th class="px-4 py-2.5">Customer Phone</th>
                                <th class="px-4 py-2.5">CSAT Score</th>
                                <th class="px-4 py-2.5">Latency (ms)</th>
                                <th class="px-4 py-2.5">Booking Resolution</th>
                                <th class="px-4 py-2.5">Created At</th>
                                <th class="px-4 py-2.5 text-right">
                                    $\Phi_{\text{CSAT}}$ Index
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-700 text-sm font-bold text-slate-300"
                        >
                            <tr
                                v-for="call in callLogs"
                                :key="call.id"
                                class="transition-colors hover:bg-slate-900/30"
                            >
                                <td
                                    class="px-4 py-3.5 font-mono text-xs text-slate-400"
                                >
                                    {{ call.call_id }}
                                </td>
                                <td class="flex items-center gap-2 px-4 py-3.5">
                                    <Phone class="text-slate-450 h-4 w-4" />
                                    <span>{{ call.customer_phone }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span
                                        v-if="call.csat_score !== null"
                                        class="font-black text-amber-400"
                                    >
                                        {{ (call.csat_score / 20).toFixed(1) }}
                                        / 5.0
                                    </span>
                                    <span
                                        v-else
                                        class="font-normal text-slate-500"
                                        >Pending</span
                                    >
                                </td>
                                <td class="px-4 py-3.5 font-mono text-xs">
                                    {{
                                        call.latency !== null
                                            ? call.latency + 'ms'
                                            : 'N/A'
                                    }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <span
                                        v-if="call.resolution === 1"
                                        class="flex items-center gap-1 text-emerald-400"
                                    >
                                        <CheckCircle class="h-4 w-4" /> Resolved
                                    </span>
                                    <span
                                        v-else
                                        class="flex items-center gap-1 text-slate-500"
                                    >
                                        <HelpCircle class="h-4 w-4" />
                                        Unresolved
                                    </span>
                                </td>
                                <td class="text-slate-450 px-4 py-3.5 text-xs">
                                    {{ call.created_at }}
                                </td>
                                <td
                                    class="px-4 py-3.5 text-right font-black text-indigo-400"
                                >
                                    {{ call.phi_csat.toFixed(3) }}
                                </td>
                            </tr>
                            <tr v-if="callLogs.length === 0">
                                <td
                                    colspan="7"
                                    class="py-8 text-center font-bold tracking-wider text-slate-500 uppercase"
                                >
                                    No completed calls found for this week
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
