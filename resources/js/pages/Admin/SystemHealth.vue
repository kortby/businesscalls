<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Activity, Database, Cpu } from '@lucide/vue';
import { computed, onMounted } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import { callStore } from '@/lib/store';

const props = defineProps<{
    webhookRecoveryRate: number;
    averageDatabaseLockLatency: number;
    activeQueueWorkers: number;
    recentEvents: any[];
}>();

// Initialize the store's webhook events list if it's empty
onMounted(() => {
    if (callStore.recentWebhookEvents.length === 0 && props.recentEvents) {
        callStore.recentWebhookEvents = props.recentEvents.map((e) => ({
            event_id: e.event_id,
            event: e.event,
            is_duplicate: e.is_duplicate,
            timestamp: e.timestamp,
            url: e.url || '',
        }));
    }
});

// Computed mascot state based on recovery rate and database lock latency
const mascotState = computed(() => {
    // If recovery rate drops below 95% or DB latency spikes above 20ms, show error (3)
    if (
        props.webhookRecoveryRate < 95.0 ||
        props.averageDatabaseLockLatency > 20.0
    ) {
        return 3;
    }

    // Otherwise celebratory victory state (2)
    return 2;
});

// Format ISO timestamps to local time
const formatTime = (isoString: string) => {
    try {
        const date = new Date(isoString);

        return date.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    } catch {
        return isoString;
    }
};
</script>

<template>
    <Head title="System Health Telemetry" />

    <div
        class="min-h-screen bg-[#F0FDF4] p-6 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    >
        <!-- Dashboard Header -->
        <header
            class="mb-8 flex flex-col gap-4 rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] sm:flex-row sm:items-center sm:justify-between dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
        >
            <div class="flex items-center gap-4">
                <div
                    class="rounded-2xl border-4 border-slate-900 bg-emerald-500 p-3 text-white dark:border-slate-100"
                >
                    <Activity class="h-8 w-8 stroke-[3]" />
                </div>
                <div>
                    <h1
                        class="text-2xl font-black tracking-tight uppercase sm:text-3xl"
                    >
                        System Health
                    </h1>
                    <p
                        class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Enterprise Webhook Resilience & Telemetry Gate
                    </p>
                </div>
            </div>
            <div class="flex gap-3">
                <div
                    class="inline-flex items-center rounded-2xl border-4 border-slate-900 bg-amber-400 px-4 py-2 text-sm font-black uppercase dark:border-slate-100 dark:text-slate-950"
                >
                    🔒 Gateway Active
                </div>
            </div>
        </header>

        <!-- Main Dashboard Layout -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Left Column: Mascot Feedback -->
            <div class="flex flex-col gap-6 lg:col-span-1">
                <div
                    class="relative flex flex-col items-center justify-center overflow-hidden rounded-3xl border-4 border-slate-900 bg-white p-6 text-center shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h3
                        class="mb-4 self-start text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400"
                    >
                        Mascot Feedback
                    </h3>

                    <div class="mb-4 aspect-square w-full max-w-[240px]">
                        <DispatcherMascot :state="mascotState" />
                    </div>

                    <div class="mt-2 space-y-2">
                        <div
                            class="text-lg font-black tracking-tight uppercase"
                        >
                            <span
                                v-if="mascotState === 2"
                                class="text-emerald-600 dark:text-emerald-400"
                                >System Healthy 🎉</span
                            >
                            <span
                                v-else
                                class="text-rose-600 dark:text-rose-400"
                                >Resilience Warning ⚠️</span
                            >
                        </div>
                        <p
                            class="px-4 text-xs font-bold text-slate-500 dark:text-slate-400"
                        >
                            <span v-if="mascotState === 2">
                                Webhook delivery queues are deduplicated, and
                                database transactional lock latency is running
                                optimal.
                            </span>
                            <span v-else>
                                High latency or packet loss detected. Middleware
                                is protecting queues, but verification checks
                                are advised.
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Mathematical Formula Details Card -->
                <div
                    class="rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h3
                        class="mb-4 text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400"
                    >
                        Recovery Index Formula
                    </h3>
                    <div
                        class="flex flex-col items-center justify-center rounded-2xl border-4 border-slate-900 bg-indigo-50 p-4 text-center dark:border-slate-100 dark:bg-indigo-950/30"
                    >
                        <span
                            class="font-mono text-lg font-black text-indigo-600 dark:text-indigo-400"
                        >
                            &Phi;<sub>recovery</sub> = (1 - (F - R) / T) &times;
                            100%
                        </span>
                    </div>
                    <div
                        class="mt-4 space-y-2 text-xs font-bold text-slate-500 dark:text-slate-400"
                    >
                        <p>Where:</p>
                        <ul class="list-disc space-y-1 pl-4">
                            <li>
                                <span
                                    class="font-black text-slate-700 dark:text-slate-200"
                                    >T:</span
                                >
                                Total incoming webhook events
                            </li>
                            <li>
                                <span
                                    class="font-black text-slate-700 dark:text-slate-200"
                                    >F:</span
                                >
                                Total recorded network/delivery failures
                            </li>
                            <li>
                                <span
                                    class="font-black text-slate-700 dark:text-slate-200"
                                    >R:</span
                                >
                                Recovered webhook retry transactions
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Column: System Indicators & Live Event Stream -->
            <div class="flex flex-col gap-6 lg:col-span-2">
                <!-- Metrics Panel -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <!-- Webhook Error Recovery Rate Card -->
                    <div
                        class="rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                    >
                        <h4
                            class="text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400"
                        >
                            Recovery Rate
                        </h4>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span
                                class="text-4xl font-black tracking-tight text-emerald-500"
                            >
                                {{ webhookRecoveryRate }}%
                            </span>
                        </div>
                        <div
                            class="mt-4 h-6 w-full overflow-hidden rounded-full border-4 border-slate-900 bg-slate-100 dark:border-slate-100 dark:bg-slate-800"
                        >
                            <div
                                class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                :style="{ width: `${webhookRecoveryRate}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- DB Lock Latency Card -->
                    <div
                        class="rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                    >
                        <h4
                            class="text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400"
                        >
                            Lock Latency
                        </h4>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span
                                class="text-4xl font-black tracking-tight"
                                :class="[
                                    averageDatabaseLockLatency > 20.0
                                        ? 'text-rose-500'
                                        : 'text-indigo-500',
                                ]"
                            >
                                {{ averageDatabaseLockLatency }}ms
                            </span>
                        </div>
                        <div
                            class="mt-2 flex items-center gap-1 text-xs font-bold text-slate-500 dark:text-slate-400"
                        >
                            <Database class="h-4 w-4" /> Average DB Lock time
                        </div>
                    </div>

                    <!-- Redis Workers Card -->
                    <div
                        class="rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                    >
                        <h4
                            class="text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400"
                        >
                            Redis Workers
                        </h4>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span
                                class="text-4xl font-black tracking-tight text-amber-500"
                            >
                                {{ activeQueueWorkers }} Active
                            </span>
                        </div>
                        <div
                            class="mt-2 flex items-center gap-1 text-xs font-bold text-slate-500 dark:text-slate-400"
                        >
                            <Cpu class="h-4 w-4" /> Queue listener daemons
                        </div>
                    </div>
                </div>

                <!-- Live Stream Webhook Events List -->
                <div
                    class="flex flex-1 flex-col rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-black tracking-wider uppercase">
                            Deduplicated Webhook Live Stream
                        </h3>
                        <span class="relative flex h-3.5 w-3.5">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                            ></span>
                            <span
                                class="relative inline-flex h-3.5 w-3.5 rounded-full bg-emerald-500"
                            ></span>
                        </span>
                    </div>

                    <!-- Table of Events -->
                    <div class="flex-1 overflow-x-auto">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr
                                    class="border-b-4 border-slate-900 text-xs font-black text-slate-500 uppercase dark:border-slate-100 dark:text-slate-400"
                                >
                                    <th class="px-4 py-3">Timestamp</th>
                                    <th class="px-4 py-3">Event / Type</th>
                                    <th class="px-4 py-3">Event ID</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(
                                        event, idx
                                    ) in callStore.recentWebhookEvents"
                                    :key="idx"
                                    class="border-b-2 border-slate-200 text-sm font-bold transition-colors hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/40"
                                >
                                    <td
                                        class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        {{ formatTime(event.timestamp) }}
                                    </td>
                                    <td class="px-4 py-3 font-black">
                                        {{ event.event }}
                                    </td>
                                    <td
                                        class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-300"
                                    >
                                        {{ event.event_id }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="event.is_duplicate"
                                            class="inline-flex items-center rounded-full border-2 border-slate-900 bg-amber-400 px-2.5 py-0.5 text-xs font-black text-slate-900 uppercase"
                                        >
                                            Duplicate / Blocked
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center rounded-full border-2 border-slate-900 bg-emerald-500 px-2.5 py-0.5 text-xs font-black text-white uppercase"
                                        >
                                            Unique / Processed
                                        </span>
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        callStore.recentWebhookEvents.length ===
                                        0
                                    "
                                >
                                    <td
                                        colspan="4"
                                        class="py-8 text-center text-sm font-bold text-slate-400 uppercase"
                                    >
                                        No webhooks processed yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
