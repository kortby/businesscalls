<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import {
    Calendar,
    Flame,
    CheckCircle,
    AlertOctagon,
    HelpCircle,
    Activity,
    Play,
    RefreshCw,
    Sparkles,
    Send,
} from '@lucide/vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

const props = defineProps<{
    bookingStreak: number;
    totalBookingsCount: number;
    speechPerformanceIndex: number;
    calendarGrid: Array<{
        date: string;
        day: string;
        count: number;
        has_bookings: boolean;
    }>;
    webhookActiveCount: number;
}>();

// Mascot state: 0 = Idle, 1 = Scanning, 2 = Victory, 3 = Error
const currentStreak = ref<number>(props.bookingStreak);
const mascotState = ref<number>(currentStreak.value >= 7 ? 2 : 0);
const isPolling = ref(false);
const showNotification = ref<{ type: 'success' | 'error'; text: string } | null>(null);
const simulatedWebhooksCount = ref(props.webhookActiveCount || 2);
const activeWebhookFailures = ref(0);

// Initialize default state based on loaded streak value
onMounted(() => {
    if (currentStreak.value >= 7) {
        mascotState.value = 2; // Celebratory victory animation
    } else {
        mascotState.value = 0; // Idle
    }
});

// Calculate metrics
const computedPerformanceIndex = computed(() => {
    return (props.speechPerformanceIndex * 100).toFixed(1);
});

// Trigger scanning simulation
const simulatePollingMetrics = () => {
    isPolling.value = true;
    mascotState.value = 1; // Scanning radar animation
    showNotification.value = null;

    setTimeout(() => {
        isPolling.value = false;
        if (activeWebhookFailures.value > 0 || currentStreak.value === 0) {
            mascotState.value = 3; // Error
            showNotification.value = {
                type: 'error',
                text: 'Metrics poll completed: Webhook failures or broken streak detected.',
            };
        } else {
            mascotState.value = currentStreak.value >= 7 ? 2 : 0;
            showNotification.value = {
                type: 'success',
                text: 'Metrics poll completed: All voice telemetry lines operating normal.',
            };
        }
    }, 2500);
};

// Simulate Webhook dispatch failure
const triggerWebhookFailure = () => {
    activeWebhookFailures.value++;
    mascotState.value = 3; // Immediately transition to sad error state
    showNotification.value = {
        type: 'error',
        text: 'System Alert: Outbound Webhook dispatch returned HTTP status 503!',
    };
};

// Reset failure status
const resetWebhookFailures = () => {
    activeWebhookFailures.value = 0;
    mascotState.value = currentStreak.value >= 7 ? 2 : 0;
    showNotification.value = null;
};

// Adjust Streak values
const breakBookingStreak = () => {
    currentStreak.value = 0;
    mascotState.value = 3; // Transition to sad error state
    showNotification.value = {
        type: 'error',
        text: 'Streak Broken! 0 consecutive days of active dispatch. Mascot is demotivated.',
    };
};

const achieveSevenDayStreak = () => {
    currentStreak.value = 7;
    mascotState.value = 2; // Celebration/victory state trigger
    showNotification.value = {
        type: 'success',
        text: 'Victory! 7-day booking streak achieved. Celebratory mascot animation active! 🎉',
    };
};

const incrementStreak = () => {
    currentStreak.value++;
    if (currentStreak.value >= 7) {
        mascotState.value = 2;
    }
    showNotification.value = {
        type: 'success',
        text: `Streak incremented to ${currentStreak.value} days. Keep up the active dispatch!`,
    };
};
</script>

<template>
    <Head title="Streak & Badges Hub" />

    <div class="min-h-screen bg-slate-900 py-8 px-4 text-slate-100 dark:bg-slate-950">
        <div class="max-w-6xl mx-auto space-y-8">
            <!-- Header Panel -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155]">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-amber-500 border-b-4 border-amber-700 rounded-2xl animate-pulse">
                        <Flame class="w-8 h-8 text-white fill-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tight text-white uppercase">Streak & Badges Hub</h1>
                        <p class="text-xs text-slate-400 font-bold uppercase mt-1 tracking-wider">
                            Daily Dispatch Streaks, Voice Quality Metrics & Webhook Notifications
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        @click="simulatePollingMetrics"
                        :disabled="isPolling"
                        class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-black uppercase tracking-wider py-3 px-6 rounded-2xl border-b-4 border-indigo-850 active:border-b-0 active:mt-1 transition-all duration-75 text-xs shadow-lg cursor-pointer"
                    >
                        Poll Voice Telemetry
                    </button>
                    <button
                        @click="triggerWebhookFailure"
                        class="bg-rose-600 hover:bg-rose-500 text-white font-black uppercase tracking-wider py-3 px-6 rounded-2xl border-b-4 border-rose-850 active:border-b-0 active:mt-1 transition-all duration-75 text-xs shadow-lg cursor-pointer"
                    >
                        Fail Webhook Delivery
                    </button>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Mascot & Controls Column -->
                <div class="flex flex-col gap-8">
                    <!-- Rive Mascot Container -->
                    <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155] flex flex-col items-center justify-center min-h-[350px] relative overflow-hidden">
                        <h2 class="text-lg font-black uppercase tracking-wider text-slate-300 mb-4">Dispatcher Mascot</h2>
                        <div class="w-full max-w-[220px] aspect-square flex items-center justify-center">
                            <DispatcherMascot :state="mascotState" />
                        </div>
                    </div>

                    <!-- Interactive Simulation Controls -->
                    <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155] space-y-4">
                        <h2 class="text-md font-black uppercase tracking-wider text-white border-b-4 border-slate-700 pb-2">
                            Streak Simulation Console
                        </h2>
                        <div class="grid grid-cols-2 gap-2 text-xs font-black uppercase">
                            <button @click="achieveSevenDayStreak" class="bg-emerald-500 hover:bg-emerald-400 text-white p-3 rounded-xl border-b-4 border-emerald-700 active:border-b-0 active:mt-1 transition-all duration-75 cursor-pointer">
                                7-Day Streak
                            </button>
                            <button @click="breakBookingStreak" class="bg-rose-500 hover:bg-rose-400 text-white p-3 rounded-xl border-b-4 border-rose-700 active:border-b-0 active:mt-1 transition-all duration-75 cursor-pointer">
                                Break Streak
                            </button>
                            <button @click="incrementStreak" class="col-span-2 bg-slate-700 hover:bg-slate-600 text-white p-3 rounded-xl border-b-4 border-slate-900 active:border-b-0 active:mt-1 transition-all duration-75 cursor-pointer">
                                Increment Streak (+1)
                            </button>
                        </div>
                        <div v-if="activeWebhookFailures > 0" class="pt-2">
                            <button @click="resetWebhookFailures" class="w-full bg-amber-500 hover:bg-amber-400 text-slate-950 font-black uppercase text-xs p-3 rounded-xl border-b-4 border-amber-700 active:border-b-0 active:mt-1 transition-all duration-75 cursor-pointer">
                                Clear Webhook Failures
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Streak Grid & Metrics Column -->
                <div class="lg:col-span-2 flex flex-col gap-8">
                    <!-- Metrics Row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Current Streak Badge -->
                        <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-5 shadow-[0_6px_0_#334155] flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Current Streak</span>
                                <h3 class="text-3xl font-black text-white mt-1">{{ currentStreak }} Days</h3>
                            </div>
                            <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                <Flame class="w-7 h-7 fill-amber-500/30" />
                            </div>
                        </div>

                        <!-- Speech Performance Index -->
                        <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-5 shadow-[0_6px_0_#334155] flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Voice Quality Index</span>
                                <h3 class="text-3xl font-black text-white mt-1">&Omega;<sub>perf</sub> = {{ computedPerformanceIndex }}%</h3>
                            </div>
                            <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <Activity class="w-7 h-7" />
                            </div>
                        </div>

                        <!-- Webhooks Publisher status -->
                        <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-5 shadow-[0_6px_0_#334155] flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Webhook Status</span>
                                <h3 class="text-xl font-black text-white mt-1 uppercase">{{ activeWebhookFailures > 0 ? 'Degraded' : 'Healthy' }}</h3>
                                <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">Active targets: {{ simulatedWebhooksCount }}</p>
                            </div>
                            <div class="p-3 rounded-2xl" :class="activeWebhookFailures > 0 ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20'">
                                <Send class="w-7 h-7" />
                            </div>
                        </div>
                    </div>

                    <!-- Notification Area -->
                    <div v-if="showNotification" class="p-4 border-4 rounded-3xl text-xs font-black uppercase tracking-wide"
                        :class="showNotification.type === 'success' ? 'bg-emerald-950/40 text-emerald-400 border-emerald-700 shadow-[0_4px_0_#047857]' : 'bg-rose-950/40 text-rose-400 border-rose-700 shadow-[0_4px_0_#be123c]'"
                    >
                        {{ showNotification.text }}
                    </div>

                    <!-- Streak Calendar Monthly Grid -->
                    <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155]">
                        <div class="flex items-center justify-between mb-6 border-b-4 border-slate-700 pb-3">
                            <h2 class="text-xl font-black uppercase tracking-wider text-white">Daily Dispatch Active Calendar</h2>
                            <span class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1">
                                <Calendar class="w-4 h-4" /> Past 30 Days Activity
                            </span>
                        </div>

                        <!-- Calendar Grid -->
                        <div class="grid grid-cols-5 md:grid-cols-10 gap-3">
                            <div
                                v-for="(day, idx) in props.calendarGrid"
                                :key="idx"
                                class="aspect-square flex flex-col justify-between p-2 rounded-2xl border-2 transition-all relative group"
                                :class="[
                                    day.has_bookings
                                        ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400 shadow-[0_3px_0_#047857]'
                                        : 'bg-slate-900 border-slate-700 text-slate-400 shadow-[0_3px_0_#1e293b]'
                                ]"
                            >
                                <span class="text-[9px] font-black uppercase tracking-wider">{{ day.day }}</span>
                                <div class="flex justify-end items-end">
                                    <Flame v-if="day.has_bookings" class="w-5 h-5 text-amber-500 fill-amber-500/40 drop-shadow-[0_2px_0_rgba(0,0,0,0.2)]" />
                                    <span v-else class="text-[10px] font-bold opacity-30">-</span>
                                </div>

                                <!-- Tooltip -->
                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-slate-950 text-white text-[9px] font-black uppercase rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity whitespace-nowrap z-50 shadow-xl border border-slate-800">
                                    {{ day.date }} : {{ day.has_bookings ? 'Active dispatch' : 'No calls' }}
                                </div>
                            </div>
                        </div>

                        <!-- Milestone Achievements Panel -->
                        <div class="mt-8 border-t-4 border-slate-700 pt-6">
                            <h3 class="text-md font-black uppercase tracking-wider text-white mb-4">Milestone Badges</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="flex items-center gap-3 p-3 bg-slate-900 rounded-2xl border-2 border-slate-700" :class="{ 'border-emerald-500/40 bg-emerald-950/10': currentStreak >= 3 }">
                                    <div class="p-2 rounded-xl bg-amber-500/20 text-amber-400">
                                        <Sparkles class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black uppercase text-white">Bronze Dispatcher</h4>
                                        <p class="text-[9px] text-slate-400 font-bold uppercase">3 Day Streak Target ({{ currentStreak >= 3 ? 'Unlocked' : 'Locked' }})</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-slate-900 rounded-2xl border-2 border-slate-700" :class="{ 'border-emerald-500/40 bg-emerald-950/10': currentStreak >= 7 }">
                                    <div class="p-2 rounded-xl bg-amber-500/20 text-amber-400">
                                        <Flame class="w-5 h-5 fill-amber-500/20" />
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black uppercase text-white">Silver Dispatcher</h4>
                                        <p class="text-[9px] text-slate-400 font-bold uppercase">7 Day Streak Target ({{ currentStreak >= 7 ? 'Unlocked' : 'Locked' }})</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-slate-900 rounded-2xl border-2 border-slate-700" :class="{ 'border-emerald-500/40 bg-emerald-950/10': currentStreak >= 15 }">
                                    <div class="p-2 rounded-xl bg-amber-500/20 text-amber-400">
                                        <Flame class="w-5 h-5 fill-amber-500/30 animate-pulse" />
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black uppercase text-white">Golden Dispatcher</h4>
                                        <p class="text-[9px] text-slate-400 font-bold uppercase">15 Day Streak Target ({{ currentStreak >= 15 ? 'Unlocked' : 'Locked' }})</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
