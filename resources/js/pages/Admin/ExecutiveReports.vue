<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Award,
    TrendingUp,
    Download,
    AlertCircle,
    CheckCircle2,
    Calendar,
    Activity,
    Smile,
} from '@lucide/vue';
import { computed } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

const props = defineProps<{
    averageCqs: number;
    bookingsCount: number;
    avgLatencyDrift: number;
    weeklyPerformanceTargetMet: boolean;
}>();

// Computed mascot state: 0 = Idle, 2 = Victory, 3 = Error
const mascotState = computed(() => {
    // If call quality is low or latency is excessively high, show error state
    if (props.averageCqs < 0.85 || props.avgLatencyDrift > 300.0) {
        return 3;
    }
    // If target met, show victory state, else idle
    return props.weeklyPerformanceTargetMet ? 2 : 0;
});

// Helper for formatting percentage
const formatCqs = (score: number) => {
    return (score * 100).toFixed(1) + '%';
};

const handleDownload = () => {
    window.location.href = '/admin/executive-report/download';
};
</script>

<template>
    <Head title="Executive Performance Reports" />

    <div class="min-h-screen bg-[#F0FDF4] p-6 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        <!-- Header -->
        <header
            class="mb-8 flex flex-col gap-4 rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)] sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-4">
                <div class="rounded-2xl border-4 border-slate-900 bg-[#3B82F6] p-3 text-white dark:border-slate-100">
                    <Award class="h-8 w-8 stroke-[3]" />
                </div>
                <div>
                    <h1 class="text-2xl font-black tracking-tight uppercase sm:text-3xl">Executive Analytics</h1>
                    <p class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">
                        Weekly Performance Index & Mascot Telemetry
                    </p>
                </div>
            </div>
            <button
                class="flex items-center justify-center gap-2 rounded-2xl border-4 border-slate-900 bg-emerald-500 px-6 py-3 text-sm font-black uppercase text-white shadow-[2px_2px_0px_0px_rgba(15,23,42,1)] transition-transform hover:-translate-y-0.5 active:translate-y-0 dark:border-slate-100 dark:shadow-[2px_2px_0px_0px_rgba(255,255,255,1)]"
                @click="handleDownload"
            >
                <Download class="h-5 w-5 stroke-[3]" />
                Download PDF Report
            </button>
        </header>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Left Side: Mascot Feedback -->
            <div class="flex flex-col gap-6 lg:col-span-1">
                <div
                    class="flex flex-col items-center rounded-3xl border-4 border-slate-900 bg-white p-6 text-center shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h3 class="mb-4 text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400">
                        Dispatcher Mascot
                    </h3>
                    <div class="relative mb-4 flex h-60 w-60 items-center justify-center rounded-2xl border-4 border-slate-900 bg-slate-50 dark:border-slate-100 dark:bg-slate-850">
                        <DispatcherMascot :state="mascotState" />
                    </div>

                    <div class="mt-2 w-full">
                        <div
                            v-if="mascotState === 2"
                            class="rounded-xl border-4 border-emerald-900 bg-emerald-100 p-3 text-sm font-bold text-emerald-800 dark:border-emerald-400 dark:bg-emerald-950 dark:text-emerald-300"
                        >
                            <span class="flex items-center justify-center gap-2">
                                <CheckCircle2 class="h-5 w-5" />
                                Target Met: Great Job! 🎉
                            </span>
                        </div>
                        <div
                            v-else-if="mascotState === 3"
                            class="rounded-xl border-4 border-rose-900 bg-rose-100 p-3 text-sm font-bold text-rose-800 dark:border-rose-400 dark:bg-rose-950 dark:text-rose-300"
                        >
                            <span class="flex items-center justify-center gap-2">
                                <AlertCircle class="h-5 w-5" />
                                Critical: System Performance Drop! ⚠️
                            </span>
                        </div>
                        <div
                            v-else
                            class="rounded-xl border-4 border-amber-900 bg-amber-100 p-3 text-sm font-bold text-amber-800 dark:border-amber-400 dark:bg-amber-950 dark:text-amber-300"
                        >
                            <span class="flex items-center justify-center gap-2">
                                <Activity class="h-5 w-5" />
                                Monitoring active channels...
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Performance Metrics Dashboard -->
            <div class="flex flex-col gap-6 lg:col-span-2">
                <!-- Metrics Cards Row -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div
                        class="rounded-2xl border-4 border-slate-900 bg-white p-5 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase text-slate-400">Total Bookings</span>
                            <Calendar class="h-5 w-5 text-[#3B82F6]" />
                        </div>
                        <p class="mt-2 text-3xl font-black">{{ bookingsCount }}</p>
                    </div>

                    <div
                        class="rounded-2xl border-4 border-slate-900 bg-white p-5 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase text-slate-400">Avg Call Quality</span>
                            <Smile class="h-5 w-5 text-emerald-500" />
                        </div>
                        <p class="mt-2 text-3xl font-black">{{ formatCqs(averageCqs) }}</p>
                    </div>

                    <div
                        class="rounded-2xl border-4 border-slate-900 bg-white p-5 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase text-slate-400">Latency Drift</span>
                            <TrendingUp class="h-5 w-5 text-amber-500" />
                        </div>
                        <p class="mt-2 text-3xl font-black">{{ avgLatencyDrift.toFixed(1) }}ms</p>
                    </div>
                </div>

                <!-- Performance Goals -->
                <div
                    class="rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h3 class="mb-4 text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400">
                        Operational Status & Goals
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between rounded-xl border-2 border-slate-900 bg-slate-50 p-4 dark:border-slate-100 dark:bg-slate-800">
                            <div>
                                <h4 class="font-black">Weekly Booking Target (>= 8)</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Assures healthy booking acquisition metrics.</p>
                            </div>
                            <span
                                class="rounded-lg px-3 py-1 text-xs font-black uppercase text-white"
                                :class="weeklyPerformanceTargetMet ? 'bg-emerald-500' : 'bg-amber-500'"
                            >
                                {{ weeklyPerformanceTargetMet ? 'Met' : 'Not Met' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between rounded-xl border-2 border-slate-900 bg-slate-50 p-4 dark:border-slate-100 dark:bg-slate-800">
                            <div>
                                <h4 class="font-black">Average Call Quality Score Threshold (>= 85%)</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Monitors dispatcher customer satisfaction levels.</p>
                            </div>
                            <span
                                class="rounded-lg px-3 py-1 text-xs font-black uppercase text-white"
                                :class="averageCqs >= 0.85 ? 'bg-emerald-500' : 'bg-rose-500'"
                            >
                                {{ averageCqs >= 0.85 ? 'Healthy' : 'Warning' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between rounded-xl border-2 border-slate-900 bg-slate-50 p-4 dark:border-slate-100 dark:bg-slate-800">
                            <div>
                                <h4 class="font-black">Conversational Latency Drift (<= 300ms)</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Tracks delays in AI agent text-to-speech loops.</p>
                            </div>
                            <span
                                class="rounded-lg px-3 py-1 text-xs font-black uppercase text-white"
                                :class="avgLatencyDrift <= 300.0 ? 'bg-emerald-500' : 'bg-rose-500'"
                            >
                                {{ avgLatencyDrift <= 300.0 ? 'Healthy' : 'High Latency' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
