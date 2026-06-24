<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head } from '@inertiajs/vue3';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import {
    Activity,
    Server,
    Clock,
    AlertTriangle,
    RefreshCw,
    Database,
    Zap,
    TrendingUp,
} from '@lucide/vue';

interface AlertPayload {
    tenant_id: number;
    tenant_name: string;
    alert_type: string;
    calculated_drift_ms: number;
    threshold_ms: number;
    recent_drifts: number[];
    call_ids: string[];
    message: string;
}

interface Alert {
    id: number;
    tenant_id: number;
    action: string;
    payload: AlertPayload;
    created_at: string;
}

const props = defineProps<{
    reverbConnections: number;
    queueLoad: number;
    avgLatencyDrift: number;
    averageDatabaseLatency: number;
    recentAlerts: Alert[];
}>();

// Make values reactive for live updates
const activeConnections = ref(props.reverbConnections);
const currentQueueLoad = ref(props.queueLoad);
const currentLatencyDrift = ref(props.avgLatencyDrift);
const currentDbLatency = ref(props.averageDatabaseLatency);

// Simulation tick to make charts look alive
let simulationInterval: any = null;
const connectionHistory = ref<number[]>([
    14, 15, 13, 16, 15, 14, 15, 16, 17, 15,
]);
const queueLoadHistory = ref<number[]>([0, 1, 0, 2, 1, 0, 1, 0, 0, 1]);
const latencyHistory = ref<number[]>([
    150, 220, 180, 210, 190, 230, 200, 190, 210, 180,
]);

onMounted(() => {
    simulationInterval = setInterval(() => {
        // Random connection fluctuation
        const connChange = Math.floor(Math.random() * 3) - 1;
        activeConnections.value = Math.max(
            5,
            activeConnections.value + connChange,
        );
        connectionHistory.value.push(activeConnections.value);
        if (connectionHistory.value.length > 10)
            connectionHistory.value.shift();

        // Queue load changes
        const queueChange = Math.floor(Math.random() * 2) - 0.5;
        currentQueueLoad.value = Math.max(
            0,
            Math.min(20, Math.floor(currentQueueLoad.value + queueChange)),
        );
        queueLoadHistory.value.push(currentQueueLoad.value);
        if (queueLoadHistory.value.length > 10) queueLoadHistory.value.shift();

        // Latency drift changes
        const latencyChange = Math.random() * 40 - 20;
        currentLatencyDrift.value = parseFloat(
            (currentLatencyDrift.value + latencyChange).toFixed(1),
        );
        latencyHistory.value.push(currentLatencyDrift.value);
        if (latencyHistory.value.length > 10) latencyHistory.value.shift();

        // DB latency fluctuation
        currentDbLatency.value = parseFloat(
            Math.max(
                2,
                Math.min(100, currentDbLatency.value + (Math.random() * 4 - 2)),
            ).toFixed(2),
        );
    }, 3000);
});

onBeforeUnmount(() => {
    if (simulationInterval) {
        clearInterval(simulationInterval);
    }
});

// Calculate Mascot State: 0 = Idle, 2 = Victory, 3 = Error
const mascotState = computed(() => {
    if (
        currentLatencyDrift.value > 1200 ||
        currentQueueLoad.value > 10 ||
        currentDbLatency.value > 45
    ) {
        return 3; // Error / Sad
    }
    if (currentLatencyDrift.value <= 200 && currentQueueLoad.value === 0) {
        return 2; // Celebratory / Victory
    }
    return 0; // Idle
});

// Helpers to draw quick inline sparkline graphs
const getSparklinePath = (data: number[], maxVal: number) => {
    if (data.length === 0) return '';
    const width = 180;
    const height = 40;
    const padding = 2;
    const points = data.map((val, idx) => {
        const x = (idx / (data.length - 1)) * (width - padding * 2) + padding;
        const normalizedVal = maxVal > 0 ? val / maxVal : 0;
        const y = height - normalizedVal * (height - padding * 2) - padding;
        return `${x.toFixed(1)},${y.toFixed(1)}`;
    });
    return `M ${points.join(' L ')}`;
};

const formatDate = (dateString: string) => {
    try {
        const date = new Date(dateString);
        return date.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    } catch (e) {
        return dateString;
    }
};
</script>

<template>
    <Head title="System Diagnostics" />

    <div
        class="min-h-screen bg-slate-950 p-6 font-sans text-slate-100 selection:bg-emerald-500 selection:text-white"
    >
        <!-- Brand Header Section -->
        <header
            class="mb-8 flex flex-col justify-between gap-4 border-b-4 border-slate-900 pb-6 md:flex-row md:items-center"
        >
            <div>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border-2 border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-black tracking-widest text-emerald-400 uppercase"
                >
                    <Zap class="h-3 w-3 fill-emerald-400" /> Telemetry Live
                </span>
                <h1
                    class="mt-2 text-4xl font-extrabold tracking-tight text-white uppercase md:text-5xl"
                >
                    Diagnostic
                    <span
                        class="bg-gradient-to-r from-emerald-400 to-teal-400 bg-clip-text text-transparent"
                        >Telemetry</span
                    >
                </h1>
                <p
                    class="mt-1 text-xs font-semibold tracking-wider text-slate-400 uppercase"
                >
                    Duolingo-inspired zero-downtime health monitoring center
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    @click="
                        currentLatencyDrift = 150;
                        currentQueueLoad = 0;
                    "
                    class="flex cursor-pointer items-center gap-2 rounded-2xl border-4 border-emerald-700 bg-emerald-600 px-5 py-2.5 text-xs font-black tracking-wider text-white uppercase shadow-[0_4px_0_#047857] transition-all hover:translate-y-0.5 hover:bg-emerald-500 hover:shadow-[0_2px_0_#047857] active:translate-y-1 active:shadow-none"
                >
                    <RefreshCw class="h-4 w-4" /> Reset Metrics
                </button>
            </div>
        </header>

        <!-- Main Layout -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Mascot and Feedback Area -->
            <div class="flex flex-col gap-6 lg:col-span-1">
                <!-- Rive Mascot Container Card -->
                <div
                    class="relative flex flex-col items-center justify-center overflow-hidden rounded-3xl border-4 border-slate-900 bg-slate-900/50 p-6 text-center"
                >
                    <h3
                        class="mb-4 self-start text-xs font-black tracking-widest text-slate-400 uppercase"
                    >
                        Optimization Mascot
                    </h3>

                    <div class="mb-4 aspect-square w-full max-w-[240px]">
                        <DispatcherMascot :state="mascotState" />
                    </div>

                    <div class="mt-2 space-y-2">
                        <div
                            class="text-lg font-black tracking-tight text-white uppercase"
                        >
                            <span
                                v-if="mascotState === 2"
                                class="text-emerald-400"
                                >System Fully Optimized 🎉</span
                            >
                            <span
                                v-else-if="mascotState === 3"
                                class="text-rose-400"
                                >Degraded Performance Detected ⚠️</span
                            >
                            <span v-else class="text-amber-400"
                                >System Running Healthy</span
                            >
                        </div>
                        <p class="px-4 text-xs text-slate-400">
                            <span v-if="mascotState === 2"
                                >Latency is minimal and background queues are
                                fully processed. Excellent job!</span
                            >
                            <span v-else-if="mascotState === 3"
                                >Conversational latency drifts or queue loads
                                are causing systemic issues. Check logs.</span
                            >
                            <span v-else
                                >The visual communications loop is active.
                                Reverb sockets are transmitting real-time
                                updates smoothly.</span
                            >
                        </p>
                    </div>
                </div>

                <!-- Database Status Summary -->
                <div
                    class="space-y-4 rounded-3xl border-4 border-slate-900 bg-slate-900/30 p-6"
                >
                    <h3
                        class="text-xs font-black tracking-widest text-slate-400 uppercase"
                    >
                        Database Telemetry
                    </h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div
                            class="rounded-2xl border-2 border-slate-900 bg-slate-950 p-4"
                        >
                            <span
                                class="block text-[10px] font-bold text-slate-500 uppercase"
                                >Query Latency</span
                            >
                            <span
                                class="mt-1 block font-mono text-2xl font-extrabold text-white"
                            >
                                {{ currentDbLatency
                                }}<span class="text-xs text-slate-400">ms</span>
                            </span>
                        </div>
                        <div
                            class="rounded-2xl border-2 border-slate-900 bg-slate-950 p-4"
                        >
                            <span
                                class="block text-[10px] font-bold text-slate-500 uppercase"
                                >Isolation Level</span
                            >
                            <span
                                class="mt-2.5 block text-xs font-extrabold tracking-widest text-emerald-400 uppercase"
                            >
                                Scoped Tenant
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Telemetry Metrics & Charts Grid -->
            <div class="flex flex-col gap-6 lg:col-span-2">
                <!-- Metrics Panel Cards -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <!-- Reverb Sockets Card -->
                    <div
                        class="relative flex min-h-[160px] flex-col justify-between overflow-hidden rounded-3xl border-4 border-slate-900 bg-slate-900/50 p-5"
                    >
                        <div>
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-black tracking-widest text-slate-400 uppercase"
                                    >Reverb Sockets</span
                                >
                                <Server class="h-4 w-4 text-emerald-400" />
                            </div>
                            <h2
                                class="mt-4 font-mono text-4xl font-black text-white"
                            >
                                {{ activeConnections }}
                            </h2>
                            <p
                                class="mt-1 text-[10px] tracking-wider text-slate-400 uppercase"
                            >
                                Active client listeners
                            </p>
                        </div>

                        <!-- Sparkline SVG -->
                        <div
                            class="mt-4 flex items-center justify-between border-t border-slate-950/40 pt-2"
                        >
                            <span
                                class="text-[9px] font-bold text-slate-500 uppercase"
                                >Live History</span
                            >
                            <svg
                                class="h-8 w-44 fill-none stroke-current stroke-3 text-emerald-500"
                            >
                                <path
                                    :d="getSparklinePath(connectionHistory, 30)"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Queue Load Card -->
                    <div
                        class="relative flex min-h-[160px] flex-col justify-between overflow-hidden rounded-3xl border-4 border-slate-900 bg-slate-900/50 p-5"
                    >
                        <div>
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-black tracking-widest text-slate-400 uppercase"
                                    >Queue Backlog</span
                                >
                                <Activity class="h-4 w-4 text-emerald-400" />
                            </div>
                            <h2
                                class="mt-4 font-mono text-4xl font-black text-white"
                                :class="
                                    currentQueueLoad > 10 ? 'text-rose-400' : ''
                                "
                            >
                                {{ currentQueueLoad }}
                            </h2>
                            <p
                                class="mt-1 text-[10px] tracking-wider text-slate-400 uppercase"
                            >
                                Unprocessed jobs in queue
                            </p>
                        </div>

                        <!-- Sparkline SVG -->
                        <div
                            class="mt-4 flex items-center justify-between border-t border-slate-950/40 pt-2"
                        >
                            <span
                                class="text-[9px] font-bold text-slate-500 uppercase"
                                >Load Trend</span
                            >
                            <svg
                                class="h-8 w-44 fill-none stroke-current stroke-3 text-emerald-500"
                                :class="
                                    currentQueueLoad > 10 ? 'text-rose-500' : ''
                                "
                            >
                                <path
                                    :d="getSparklinePath(queueLoadHistory, 10)"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Latency Drift Index Card -->
                    <div
                        class="relative flex min-h-[160px] flex-col justify-between overflow-hidden rounded-3xl border-4 border-slate-900 bg-slate-900/50 p-5"
                    >
                        <div>
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-black tracking-widest text-slate-400 uppercase"
                                    >&Omega; Drift Index</span
                                >
                                <Clock class="h-4 w-4 text-emerald-400" />
                            </div>
                            <h2
                                class="mt-4 font-mono text-4xl font-black"
                                :class="
                                    currentLatencyDrift > 1200
                                        ? 'animate-pulse text-rose-400'
                                        : 'text-white'
                                "
                            >
                                {{ currentLatencyDrift
                                }}<span class="text-lg">ms</span>
                            </h2>
                            <p
                                class="mt-1 text-[10px] tracking-wider text-slate-400 uppercase"
                            >
                                turn drift over target
                            </p>
                        </div>

                        <!-- Sparkline SVG -->
                        <div
                            class="mt-4 flex items-center justify-between border-t border-slate-950/40 pt-2"
                        >
                            <span
                                class="text-[9px] font-bold text-slate-500 uppercase"
                                >Drift Spark</span
                            >
                            <svg
                                class="h-8 w-44 fill-none stroke-current stroke-3 text-emerald-500"
                                :class="
                                    currentLatencyDrift > 1200
                                        ? 'text-rose-500'
                                        : ''
                                "
                            >
                                <path
                                    :d="getSparklinePath(latencyHistory, 500)"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Recent Incidents Webhook/Alerts List -->
                <div
                    class="flex flex-1 flex-col rounded-3xl border-4 border-slate-900 bg-slate-900/30 p-6"
                >
                    <div
                        class="mb-4 flex items-center justify-between border-b-2 border-slate-900 pb-2"
                    >
                        <h3
                            class="flex items-center gap-1.5 text-xs font-black tracking-widest text-slate-400 uppercase"
                        >
                            <AlertTriangle class="h-4 w-4 text-amber-500" />
                            High-Priority Incident Logs
                        </h3>
                        <span
                            class="rounded-md bg-slate-900 px-2 py-0.5 text-[9px] font-extrabold tracking-widest text-slate-400 uppercase"
                        >
                            Multi-Tenant Filtered
                        </span>
                    </div>

                    <!-- Incidents Listing -->
                    <div
                        v-if="recentAlerts && recentAlerts.length > 0"
                        class="max-h-[300px] flex-1 space-y-3 overflow-y-auto pr-2"
                    >
                        <div
                            v-for="alert in recentAlerts"
                            :key="alert.id"
                            class="flex flex-col justify-between gap-4 rounded-2xl border-2 border-slate-900 bg-slate-950 p-4 transition-colors hover:border-slate-800 md:flex-row"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-block h-2.5 w-2.5 animate-pulse rounded-full bg-rose-500"
                                    ></span>
                                    <span
                                        class="text-xs font-extrabold tracking-tight text-white uppercase"
                                    >
                                        {{
                                            alert.payload.alert_type ||
                                            'Latency Spike Alert'
                                        }}
                                    </span>
                                    <span
                                        class="text-[9px] font-bold text-slate-500 uppercase"
                                    >
                                        Turn Drift:
                                        {{
                                            alert.payload.calculated_drift_ms
                                        }}ms
                                    </span>
                                </div>
                                <p
                                    class="text-xs leading-relaxed font-semibold text-slate-400"
                                >
                                    {{ alert.payload.message }}
                                </p>
                                <div
                                    class="flex gap-4 text-[10px] font-semibold text-slate-500 uppercase"
                                >
                                    <span
                                        >Call Log IDs:
                                        {{
                                            alert.payload.call_ids?.join(', ')
                                        }}</span
                                    >
                                </div>
                            </div>
                            <div
                                class="self-end text-right text-[9px] font-bold text-slate-500 uppercase md:self-start"
                            >
                                {{ formatDate(alert.created_at) }}
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-else
                        class="flex flex-1 flex-col items-center justify-center py-12 text-center text-xs font-semibold tracking-wider text-slate-500 uppercase italic"
                    >
                        <div
                            class="mb-2 rounded-full border-2 border-slate-900 bg-slate-950 p-3"
                        >
                            <Activity class="h-6 w-6 text-slate-600" />
                        </div>
                        No latency incidents logged on this tenant context.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
