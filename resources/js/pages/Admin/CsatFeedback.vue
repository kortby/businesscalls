<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
const showMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null);

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

    <div class="min-h-screen bg-slate-900 py-8 px-4 text-slate-100 dark:bg-slate-950">
        <div class="max-w-6xl mx-auto space-y-8">
            <!-- Header Panel -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155]">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-emerald-500 border-b-4 border-emerald-700 rounded-2xl">
                        <Smile class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tight text-white uppercase">CSAT & Performance Index</h1>
                        <p class="text-xs text-slate-400 font-bold uppercase mt-1 tracking-wider">
                            Real-time satisfaction scoring, SLA triggers & dynamic call-steering
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        @click="simulateEmergencyCall"
                        :disabled="processing"
                        class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-black uppercase tracking-wider py-3 px-6 rounded-2xl border-b-4 border-indigo-850 active:border-b-0 active:mt-1 transition-all duration-75 text-xs shadow-lg cursor-pointer"
                    >
                        Simulate Emergency
                    </button>
                    <button
                        @click="simulateCallDropped"
                        class="bg-rose-600 hover:bg-rose-500 text-white font-black uppercase tracking-wider py-3 px-6 rounded-2xl border-b-4 border-rose-850 active:border-b-0 active:mt-1 transition-all duration-75 text-xs shadow-lg cursor-pointer"
                    >
                        Simulate Drop
                    </button>
                    <button
                        @click="resolveAllErrors"
                        class="bg-slate-700 hover:bg-slate-650 text-white font-black uppercase tracking-wider py-3 px-6 rounded-2xl border-b-4 border-slate-900 active:border-b-0 active:mt-1 transition-all duration-75 text-xs shadow-lg cursor-pointer"
                    >
                        Resolve Alerts
                    </button>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Rive Mascot Card -->
                <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155] flex flex-col items-center justify-center min-h-[360px] relative overflow-hidden">
                    <h2 class="text-lg font-black uppercase tracking-wider text-slate-300 mb-4">Dispatcher Mascot</h2>
                    <div class="w-full max-w-[220px] aspect-square flex items-center justify-center">
                        <DispatcherMascot :state="mascotState" />
                    </div>
                </div>

                <!-- CSAT Metric Card -->
                <div class="lg:col-span-2 bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155] flex flex-col justify-between">
                    <div>
                        <h2 class="text-xl font-black uppercase tracking-wider text-white mb-6 border-b-4 border-slate-700 pb-3">
                            Performance Efficiency Index
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div class="bg-slate-900 border-4 border-slate-700 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-[0_4px_0_#334155]">
                                <span class="text-xs text-slate-400 font-bold uppercase tracking-wide">Weekly Avg CSAT Index</span>
                                <span class="text-4xl font-black mt-2" :class="weeklyPhi >= 0.85 ? 'text-emerald-450' : 'text-rose-400'">
                                    {{ (weeklyPhi * 100).toFixed(1) }}%
                                </span>
                            </div>

                            <div class="bg-slate-900 border-4 border-slate-700 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-[0_4px_0_#334155]">
                                <span class="text-xs text-slate-400 font-bold uppercase tracking-wide">Ongoing Streams</span>
                                <span class="text-4xl font-black text-indigo-400 mt-2 flex items-center gap-2">
                                    <Activity class="w-6 h-6 animate-pulse" v-if="processing" />
                                    {{ processing ? 'Active' : 'Idle' }}
                                </span>
                            </div>

                            <div class="bg-slate-900 border-4 border-slate-700 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-[0_4px_0_#334155]">
                                <span class="text-xs text-slate-400 font-bold uppercase tracking-wide">SLA Outage Warnings</span>
                                <span class="text-4xl font-black mt-2" :class="recentError ? 'text-rose-400' : 'text-emerald-450'">
                                    {{ recentError ? 'Alert' : 'None' }}
                                </span>
                            </div>
                        </div>

                        <!-- SLA & CSAT Metrics Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b-2 border-slate-700 text-xs font-black uppercase tracking-wider text-slate-400">
                                        <th class="py-2.5 px-4">Integration Target</th>
                                        <th class="py-2.5 px-4">Event Type</th>
                                        <th class="py-2.5 px-4">Target Action</th>
                                        <th class="py-2.5 px-4 text-right">Mascot Input</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700 text-sm font-bold text-slate-300">
                                    <!-- CSAT Complete -->
                                    <tr class="hover:bg-slate-900/30 transition-colors">
                                        <td class="py-3 px-4 flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                            <span>CSAT Complete</span>
                                        </td>
                                        <td class="py-3 px-4 font-mono text-xs text-slate-450">call_analyzed</td>
                                        <td class="py-3 px-4 text-slate-400">Log Scorecard</td>
                                        <td class="py-3 px-4 text-right text-emerald-400 font-mono text-xs">State Trigger 2</td>
                                    </tr>

                                    <!-- Call Dropped -->
                                    <tr class="hover:bg-slate-900/30 transition-colors">
                                        <td class="py-3 px-4 flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            <span>Call Dropped</span>
                                        </td>
                                        <td class="py-3 px-4 font-mono text-xs text-slate-450">call_ended (error status)</td>
                                        <td class="py-3 px-4 text-slate-400">Trigger Alert</td>
                                        <td class="py-3 px-4 text-right text-rose-400 font-mono text-xs">State Trigger 3</td>
                                    </tr>

                                    <!-- Ongoing Call -->
                                    <tr class="hover:bg-slate-900/30 transition-colors">
                                        <td class="py-3 px-4 flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                            <span>Ongoing Call</span>
                                        </td>
                                        <td class="py-3 px-4 font-mono text-xs text-slate-450">call_started</td>
                                        <td class="py-3 px-4 text-slate-400">Live Captions</td>
                                        <td class="py-3 px-4 text-right text-indigo-400 font-mono text-xs">State Trigger 1</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Notice alerts box -->
                    <div v-if="showMessage" class="mt-6 p-4 border-4 rounded-2xl text-xs font-black uppercase tracking-wide flex items-center gap-3"
                        :class="showMessage.type === 'success' ? 'bg-emerald-950/40 text-emerald-400 border-emerald-700 shadow-[0_4px_0_#047857]' : 'bg-rose-950/40 text-rose-400 border-rose-700 shadow-[0_4px_0_#be123c]'"
                    >
                        <CheckCircle class="w-5 h-5 flex-shrink-0" v-if="showMessage.type === 'success'" />
                        <ShieldAlert class="w-5 h-5 flex-shrink-0" v-else />
                        <span>{{ showMessage.text }}</span>
                    </div>
                </div>
            </div>

            <!-- Mathematical Formula Explanation Card -->
            <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155]">
                <h2 class="text-xl font-black uppercase tracking-wider text-white mb-4 border-b-4 border-slate-700 pb-3">
                    CSAT Index Formulation
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <div class="space-y-4 text-sm font-bold text-slate-350">
                        <p>
                            We calculate the Customer Satisfaction and Agent Performance Efficiency Index ($\Phi_{\text{CSAT}}$) using the following normalized formula:
                        </p>
                        <div class="bg-slate-900 border-2 border-slate-700 rounded-2xl p-4 font-mono text-xs text-slate-300 text-center leading-relaxed">
                            $$\Phi_{\text{CSAT}}=\sum_{c\in C}\left(\alpha\cdot S_c+\beta\cdot\left(1-\frac{\tau_c}{T_{\text{max}}}\right)\right)\cdot\mu_{\text{resolution}}$$
                        </div>
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>$S_c$</strong>: Call satisfaction score (1-5 scale) scaled to $[0.0, 1.0]$.</li>
                            <li><strong>$\tau_c$</strong>: Telephony response latency in milliseconds.</li>
                            <li><strong>$T_{\text{max}}$</strong>: Threshold limit (configured to {{ tMax }}ms).</li>
                            <li><strong>$\mu_{\text{resolution}}$</strong>: Binary indicator (1 if booking is successfully resolved, 0 otherwise).</li>
                            <li><strong>$\alpha, \beta$</strong>: Weight ratios (configured to $\alpha$ = {{ alpha }}, $\beta$ = {{ beta }}).</li>
                        </ul>
                    </div>

                    <div class="bg-slate-900 border-2 border-slate-700 rounded-2xl p-4 font-mono text-xs text-slate-300 space-y-2">
                        <div class="text-indigo-400 font-bold uppercase tracking-wider border-b border-slate-700 pb-2 flex justify-between items-center">
                            <span>SLA Target Weight Parameters</span>
                            <span class="text-[9px] bg-slate-800 text-slate-400 px-2 py-0.5 rounded-md">Config</span>
                        </div>
                        <div class="space-y-1">
                            <div>Satisfaction Weight ($\alpha$): {{ alpha }}</div>
                            <div>Latency Weight ($\beta$): {{ beta }}</div>
                            <div>Max Latency Threshold ($T_{\text{max}}$): {{ tMax }}ms</div>
                            <div>Baseline CSAT Threshold: 0.85</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Telephony Calls Table -->
            <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155]">
                <h2 class="text-xl font-black uppercase tracking-wider text-white mb-6 border-b-4 border-slate-700 pb-3">
                    Recent Telephony & CSAT Evaluations
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b-2 border-slate-700 text-xs font-black uppercase tracking-wider text-slate-400">
                                <th class="py-2.5 px-4">Call Identifier</th>
                                <th class="py-2.5 px-4">Customer Phone</th>
                                <th class="py-2.5 px-4">CSAT Score</th>
                                <th class="py-2.5 px-4">Latency (ms)</th>
                                <th class="py-2.5 px-4">Booking Resolution</th>
                                <th class="py-2.5 px-4">Created At</th>
                                <th class="py-2.5 px-4 text-right">$\Phi_{\text{CSAT}}$ Index</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700 text-sm font-bold text-slate-300">
                            <tr v-for="call in callLogs" :key="call.id" class="hover:bg-slate-900/30 transition-colors">
                                <td class="py-3.5 px-4 font-mono text-xs text-slate-400">{{ call.call_id }}</td>
                                <td class="py-3.5 px-4 flex items-center gap-2">
                                    <Phone class="w-4 h-4 text-slate-450" />
                                    <span>{{ call.customer_phone }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span v-if="call.csat_score !== null" class="text-amber-400 font-black">
                                        {{ (call.csat_score / 20).toFixed(1) }} / 5.0
                                    </span>
                                    <span v-else class="text-slate-500 font-normal">Pending</span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-xs">
                                    {{ call.latency !== null ? call.latency + 'ms' : 'N/A' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span v-if="call.resolution === 1" class="text-emerald-400 flex items-center gap-1">
                                        <CheckCircle class="w-4 h-4" /> Resolved
                                    </span>
                                    <span v-else class="text-slate-500 flex items-center gap-1">
                                        <HelpCircle class="w-4 h-4" /> Unresolved
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-450 text-xs">{{ call.created_at }}</td>
                                <td class="py-3.5 px-4 text-right font-black text-indigo-400">
                                    {{ call.phi_csat.toFixed(3) }}
                                </td>
                            </tr>
                            <tr v-if="callLogs.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-500 font-bold uppercase tracking-wider">
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
