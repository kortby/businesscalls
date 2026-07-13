<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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
import { ref, onMounted, computed } from 'vue';
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
const showNotification = ref<{
    type: 'success' | 'error';
    text: string;
} | null>(null);
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
                        class="animate-pulse rounded-2xl border-b-4 border-amber-700 bg-amber-500 p-3"
                    >
                        <Flame class="h-8 w-8 fill-white text-white" />
                    </div>
                    <div>
                        <h1
                            class="text-3xl font-black tracking-tight text-white uppercase"
                        >
                            Streak & Badges Hub
                        </h1>
                        <p
                            class="mt-1 text-xs font-bold tracking-wider text-slate-400 uppercase"
                        >
                            Daily Dispatch Streaks, Voice Quality Metrics &
                            Webhook Notifications
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        @click="simulatePollingMetrics"
                        :disabled="isPolling"
                        class="border-indigo-850 cursor-pointer rounded-2xl border-b-4 bg-indigo-600 px-6 py-3 text-xs font-black tracking-wider text-white uppercase shadow-lg transition-all duration-75 hover:bg-indigo-500 active:mt-1 active:border-b-0 disabled:opacity-50"
                    >
                        Poll Voice Telemetry
                    </button>
                    <button
                        @click="triggerWebhookFailure"
                        class="border-rose-850 cursor-pointer rounded-2xl border-b-4 bg-rose-600 px-6 py-3 text-xs font-black tracking-wider text-white uppercase shadow-lg transition-all duration-75 hover:bg-rose-500 active:mt-1 active:border-b-0"
                    >
                        Fail Webhook Delivery
                    </button>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Mascot & Controls Column -->
                <div class="flex flex-col gap-8">
                    <!-- Rive Mascot Container -->
                    <div
                        class="relative flex min-h-[350px] flex-col items-center justify-center overflow-hidden rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155]"
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

                    <!-- Interactive Simulation Controls -->
                    <div
                        class="space-y-4 rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155]"
                    >
                        <h2
                            class="text-md border-b-4 border-slate-700 pb-2 font-black tracking-wider text-white uppercase"
                        >
                            Streak Simulation Console
                        </h2>
                        <div
                            class="grid grid-cols-2 gap-2 text-xs font-black uppercase"
                        >
                            <button
                                @click="achieveSevenDayStreak"
                                class="cursor-pointer rounded-xl border-b-4 border-emerald-700 bg-emerald-500 p-3 text-white transition-all duration-75 hover:bg-emerald-400 active:mt-1 active:border-b-0"
                            >
                                7-Day Streak
                            </button>
                            <button
                                @click="breakBookingStreak"
                                class="cursor-pointer rounded-xl border-b-4 border-rose-700 bg-rose-500 p-3 text-white transition-all duration-75 hover:bg-rose-400 active:mt-1 active:border-b-0"
                            >
                                Break Streak
                            </button>
                            <button
                                @click="incrementStreak"
                                class="col-span-2 cursor-pointer rounded-xl border-b-4 border-slate-900 bg-slate-700 p-3 text-white transition-all duration-75 hover:bg-slate-600 active:mt-1 active:border-b-0"
                            >
                                Increment Streak (+1)
                            </button>
                        </div>
                        <div v-if="activeWebhookFailures > 0" class="pt-2">
                            <button
                                @click="resetWebhookFailures"
                                class="w-full cursor-pointer rounded-xl border-b-4 border-amber-700 bg-amber-500 p-3 text-xs font-black text-slate-950 uppercase transition-all duration-75 hover:bg-amber-400 active:mt-1 active:border-b-0"
                            >
                                Clear Webhook Failures
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Streak Grid & Metrics Column -->
                <div class="flex flex-col gap-8 lg:col-span-2">
                    <!-- Metrics Row -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <!-- Current Streak Badge -->
                        <div
                            class="flex items-center justify-between rounded-3xl border-4 border-slate-700 bg-slate-800 p-5 shadow-[0_6px_0_#334155]"
                        >
                            <div>
                                <span
                                    class="text-[10px] font-black tracking-wider text-slate-400 uppercase"
                                    >Current Streak</span
                                >
                                <h3 class="mt-1 text-3xl font-black text-white">
                                    {{ currentStreak }} Days
                                </h3>
                            </div>
                            <div
                                class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-3 text-amber-400"
                            >
                                <Flame class="h-7 w-7 fill-amber-500/30" />
                            </div>
                        </div>

                        <!-- Speech Performance Index -->
                        <div
                            class="flex items-center justify-between rounded-3xl border-4 border-slate-700 bg-slate-800 p-5 shadow-[0_6px_0_#334155]"
                        >
                            <div>
                                <span
                                    class="text-[10px] font-black tracking-wider text-slate-400 uppercase"
                                    >Voice Quality Index</span
                                >
                                <h3 class="mt-1 text-3xl font-black text-white">
                                    &Omega;<sub>perf</sub> =
                                    {{ computedPerformanceIndex }}%
                                </h3>
                            </div>
                            <div
                                class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-3 text-emerald-400"
                            >
                                <Activity class="h-7 w-7" />
                            </div>
                        </div>

                        <!-- Webhooks Publisher status -->
                        <div
                            class="flex items-center justify-between rounded-3xl border-4 border-slate-700 bg-slate-800 p-5 shadow-[0_6px_0_#334155]"
                        >
                            <div>
                                <span
                                    class="text-[10px] font-black tracking-wider text-slate-400 uppercase"
                                    >Webhook Status</span
                                >
                                <h3
                                    class="mt-1 text-xl font-black text-white uppercase"
                                >
                                    {{
                                        activeWebhookFailures > 0
                                            ? 'Degraded'
                                            : 'Healthy'
                                    }}
                                </h3>
                                <p
                                    class="mt-1 text-[9px] font-bold text-slate-400 uppercase"
                                >
                                    Active targets: {{ simulatedWebhooksCount }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl p-3"
                                :class="
                                    activeWebhookFailures > 0
                                        ? 'border border-rose-500/20 bg-rose-500/10 text-rose-400'
                                        : 'border border-indigo-500/20 bg-indigo-500/10 text-indigo-400'
                                "
                            >
                                <Send class="h-7 w-7" />
                            </div>
                        </div>
                    </div>

                    <!-- Notification Area -->
                    <div
                        v-if="showNotification"
                        class="rounded-3xl border-4 p-4 text-xs font-black tracking-wide uppercase"
                        :class="
                            showNotification.type === 'success'
                                ? 'border-emerald-700 bg-emerald-950/40 text-emerald-400 shadow-[0_4px_0_#047857]'
                                : 'border-rose-700 bg-rose-950/40 text-rose-400 shadow-[0_4px_0_#be123c]'
                        "
                    >
                        {{ showNotification.text }}
                    </div>

                    <!-- Streak Calendar Monthly Grid -->
                    <div
                        class="rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155]"
                    >
                        <div
                            class="mb-6 flex items-center justify-between border-b-4 border-slate-700 pb-3"
                        >
                            <h2
                                class="text-xl font-black tracking-wider text-white uppercase"
                            >
                                Daily Dispatch Active Calendar
                            </h2>
                            <span
                                class="flex items-center gap-1 text-xs font-black tracking-wider text-slate-400 uppercase"
                            >
                                <Calendar class="h-4 w-4" /> Past 30 Days
                                Activity
                            </span>
                        </div>

                        <!-- Calendar Grid -->
                        <div class="grid grid-cols-5 gap-3 md:grid-cols-10">
                            <div
                                v-for="(day, idx) in props.calendarGrid"
                                :key="idx"
                                class="group relative flex aspect-square flex-col justify-between rounded-2xl border-2 p-2 transition-all"
                                :class="[
                                    day.has_bookings
                                        ? 'border-emerald-500 bg-emerald-500/10 text-emerald-400 shadow-[0_3px_0_#047857]'
                                        : 'border-slate-700 bg-slate-900 text-slate-400 shadow-[0_3px_0_#1e293b]',
                                ]"
                            >
                                <span
                                    class="text-[9px] font-black tracking-wider uppercase"
                                    >{{ day.day }}</span
                                >
                                <div class="flex items-end justify-end">
                                    <Flame
                                        v-if="day.has_bookings"
                                        class="h-5 w-5 fill-amber-500/40 text-amber-500 drop-shadow-[0_2px_0_rgba(0,0,0,0.2)]"
                                    />
                                    <span
                                        v-else
                                        class="text-[10px] font-bold opacity-30"
                                        >-</span
                                    >
                                </div>

                                <!-- Tooltip -->
                                <div
                                    class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2 rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 text-[9px] font-black whitespace-nowrap text-white uppercase opacity-0 shadow-xl transition-opacity group-hover:opacity-100"
                                >
                                    {{ day.date }} :
                                    {{
                                        day.has_bookings
                                            ? 'Active dispatch'
                                            : 'No calls'
                                    }}
                                </div>
                            </div>
                        </div>

                        <!-- Milestone Achievements Panel -->
                        <div class="mt-8 border-t-4 border-slate-700 pt-6">
                            <h3
                                class="text-md mb-4 font-black tracking-wider text-white uppercase"
                            >
                                Milestone Badges
                            </h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div
                                    class="flex items-center gap-3 rounded-2xl border-2 border-slate-700 bg-slate-900 p-3"
                                    :class="{
                                        'border-emerald-500/40 bg-emerald-950/10':
                                            currentStreak >= 3,
                                    }"
                                >
                                    <div
                                        class="rounded-xl bg-amber-500/20 p-2 text-amber-400"
                                    >
                                        <Sparkles class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h4
                                            class="text-xs font-black text-white uppercase"
                                        >
                                            Bronze Dispatcher
                                        </h4>
                                        <p
                                            class="text-[9px] font-bold text-slate-400 uppercase"
                                        >
                                            3 Day Streak Target ({{
                                                currentStreak >= 3
                                                    ? 'Unlocked'
                                                    : 'Locked'
                                            }})
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-3 rounded-2xl border-2 border-slate-700 bg-slate-900 p-3"
                                    :class="{
                                        'border-emerald-500/40 bg-emerald-950/10':
                                            currentStreak >= 7,
                                    }"
                                >
                                    <div
                                        class="rounded-xl bg-amber-500/20 p-2 text-amber-400"
                                    >
                                        <Flame
                                            class="h-5 w-5 fill-amber-500/20"
                                        />
                                    </div>
                                    <div>
                                        <h4
                                            class="text-xs font-black text-white uppercase"
                                        >
                                            Silver Dispatcher
                                        </h4>
                                        <p
                                            class="text-[9px] font-bold text-slate-400 uppercase"
                                        >
                                            7 Day Streak Target ({{
                                                currentStreak >= 7
                                                    ? 'Unlocked'
                                                    : 'Locked'
                                            }})
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-3 rounded-2xl border-2 border-slate-700 bg-slate-900 p-3"
                                    :class="{
                                        'border-emerald-500/40 bg-emerald-950/10':
                                            currentStreak >= 15,
                                    }"
                                >
                                    <div
                                        class="rounded-xl bg-amber-500/20 p-2 text-amber-400"
                                    >
                                        <Flame
                                            class="h-5 w-5 animate-pulse fill-amber-500/30"
                                        />
                                    </div>
                                    <div>
                                        <h4
                                            class="text-xs font-black text-white uppercase"
                                        >
                                            Golden Dispatcher
                                        </h4>
                                        <p
                                            class="text-[9px] font-bold text-slate-400 uppercase"
                                        >
                                            15 Day Streak Target ({{
                                                currentStreak >= 15
                                                    ? 'Unlocked'
                                                    : 'Locked'
                                            }})
                                        </p>
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
