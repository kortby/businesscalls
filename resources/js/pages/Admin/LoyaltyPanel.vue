<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Award,
    Flame,
    CheckCircle,
    XCircle,
    ChevronRight,
    Star,
    Coins,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

const props = defineProps<{
    repeatCustomerStreak: number;
    monthlyVipDispatches: number;
    customerLoyaltyScore: number;
    billingStatus: string;
    disputesOpened: number;
}>();

// Make props reactive for simulation controls
const streak = ref(props.repeatCustomerStreak);
const loyaltyScore = ref(props.customerLoyaltyScore);
const billing = ref(props.billingStatus);
const disputes = ref(props.disputesOpened);

const mascotState = computed(() => {
    if (billing.value === 'late' || disputes.value > 0) {
        return 3; // Error / Sad
    }

    if (loyaltyScore.value >= 80 && streak.value >= 5) {
        return 2; // Victory / Celebratory
    }

    return 0; // Idle
});

const simulateGoodState = () => {
    streak.value = 12;
    loyaltyScore.value = 95.0;
    billing.value = 'paid';
    disputes.value = 0;
};

const simulateErrorState = () => {
    billing.value = 'late';
    disputes.value = 2;
};

const simulateIdleState = () => {
    streak.value = 3;
    loyaltyScore.value = 55.0;
    billing.value = 'paid';
    disputes.value = 0;
};
</script>

<template>
    <Head title="Loyalty Leaderboard" />

    <div
        class="min-h-screen bg-slate-950 p-6 font-sans text-slate-100 selection:bg-emerald-500 selection:text-white"
    >
        <!-- Playful Header -->
        <header
            class="mb-8 flex flex-col justify-between gap-4 border-b-4 border-slate-900 pb-6 md:flex-row md:items-center"
        >
            <div>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border-2 border-amber-500/20 bg-amber-500/10 px-3 py-1 text-xs font-black tracking-widest text-amber-400 uppercase"
                >
                    <Coins class="h-3 w-3 fill-amber-400" /> Dispatch Rewards
                </span>
                <h1
                    class="mt-2 text-4xl font-extrabold tracking-tight text-white uppercase md:text-5xl"
                >
                    Loyalty
                    <span
                        class="bg-gradient-to-r from-amber-400 to-emerald-400 bg-clip-text text-transparent"
                        >Dashboard</span
                    >
                </h1>
                <p
                    class="mt-1 text-xs font-semibold tracking-wider text-slate-400 uppercase"
                >
                    Gamified contractor reward streaks & mascot verification
                </p>
            </div>

            <!-- Controls / Simulation Box -->
            <div class="flex flex-wrap items-center gap-3">
                <button
                    @click="simulateGoodState"
                    class="cursor-pointer rounded-2xl border-4 border-emerald-700 bg-emerald-600 px-4 py-2 text-xs font-black tracking-wider text-white uppercase shadow-[0_4px_0_#047857] transition-all hover:translate-y-0.5 hover:bg-emerald-500 hover:shadow-[0_2px_0_#047857] active:translate-y-1 active:shadow-none"
                >
                    Optimized State
                </button>
                <button
                    @click="simulateErrorState"
                    class="cursor-pointer rounded-2xl border-4 border-rose-700 bg-rose-600 px-4 py-2 text-xs font-black tracking-wider text-white uppercase shadow-[0_4px_0_#be123c] transition-all hover:translate-y-0.5 hover:bg-rose-500 hover:shadow-[0_2px_0_#be123c] active:translate-y-1 active:shadow-none"
                >
                    Alert/Dispute State
                </button>
                <button
                    @click="simulateIdleState"
                    class="cursor-pointer rounded-2xl border-4 border-slate-700 bg-slate-600 px-4 py-2 text-xs font-black tracking-wider text-white uppercase shadow-[0_4px_0_#334155] transition-all hover:translate-y-0.5 hover:bg-slate-500 hover:shadow-[0_2px_0_#334155] active:translate-y-1 active:shadow-none"
                >
                    Idle Reset
                </button>
            </div>
        </header>

        <!-- Main Dashboard Layout -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Left Side: Mascot Feedback -->
            <div class="flex flex-col gap-6 lg:col-span-1">
                <!-- Rive Mascot Card -->
                <div
                    class="relative flex flex-col items-center justify-center overflow-hidden rounded-3xl border-4 border-slate-900 bg-slate-900/50 p-6 text-center"
                >
                    <h3
                        class="mb-4 self-start text-xs font-black tracking-widest text-slate-400 uppercase"
                    >
                        Active Rive Mascot
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
                                >High Loyalty Score 🎉</span
                            >
                            <span
                                v-else-if="mascotState === 3"
                                class="text-rose-400"
                                >Late Payment / Dispute ⚠️</span
                            >
                            <span v-else class="text-amber-400"
                                >System Ready</span
                            >
                        </div>
                        <p class="px-4 text-xs text-slate-400">
                            <span v-if="mascotState === 2"
                                >The client's booking streak is outstanding and
                                the billing account is fully active. Excellent
                                work!</span
                            >
                            <span v-else-if="mascotState === 3"
                                >Administrative alert: Billing status is late or
                                active service disputes require
                                resolution.</span
                            >
                            <span v-else
                                >The dispatch portal is running normal
                                operations. Keep scheduling repeat jobs to
                                increase the loyalty score.</span
                            >
                        </p>
                    </div>
                </div>

                <!-- Score Progress Card -->
                <div
                    class="space-y-4 rounded-3xl border-4 border-slate-900 bg-slate-900/30 p-6"
                >
                    <h3
                        class="text-xs font-black tracking-widest text-slate-400 uppercase"
                    >
                        Loyalty Rating Index
                    </h3>

                    <div
                        class="flex items-center justify-between rounded-2xl border-2 border-slate-900 bg-slate-950 p-4"
                    >
                        <div>
                            <span
                                class="text-[10px] font-bold text-slate-500 uppercase"
                                >Tenant Rating</span
                            >
                            <span
                                class="mt-1 block font-mono text-3xl font-black text-white"
                            >
                                {{ loyaltyScore }}%
                            </span>
                        </div>
                        <div
                            class="rounded-xl border-2 border-amber-500/20 bg-amber-500/10 p-2.5"
                        >
                            <Star
                                class="h-6 w-6 fill-amber-400 text-amber-400"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Leaderboard and Milestone Badges -->
            <div class="flex flex-col gap-6 lg:col-span-2">
                <!-- Row of Streaks and Milestones -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Repeat Customer Streak Card -->
                    <div
                        class="relative flex min-h-[160px] flex-col justify-between overflow-hidden rounded-3xl border-4 border-slate-900 bg-slate-900/50 p-5"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <span
                                    class="text-xs font-black tracking-widest text-slate-400 uppercase"
                                    >Booking Streak</span
                                >
                                <h2
                                    class="mt-4 flex items-baseline gap-1 font-mono text-5xl font-black text-amber-400"
                                >
                                    {{ streak }}
                                    <span
                                        class="text-sm font-bold text-slate-400 uppercase"
                                        >Jobs</span
                                    >
                                </h2>
                            </div>
                            <div
                                class="animate-pulse rounded-2xl border-2 border-amber-500/20 bg-amber-500/10 p-3"
                            >
                                <Flame
                                    class="h-8 w-8 fill-amber-500 text-amber-500"
                                />
                            </div>
                        </div>
                        <p class="mt-2 text-xs leading-relaxed text-slate-400">
                            Repeat bookings scheduled successfully. Maintain
                            consistency to achieve the next monthly VIP Badge!
                        </p>
                    </div>

                    <!-- Monthly VIP Badge Card -->
                    <div
                        class="relative flex min-h-[160px] flex-col justify-between overflow-hidden rounded-3xl border-4 border-slate-900 bg-slate-900/50 p-5"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <span
                                    class="text-xs font-black tracking-widest text-slate-400 uppercase"
                                    >Monthly VIP Dispatches</span
                                >
                                <h2
                                    class="mt-4 flex items-baseline gap-1 font-mono text-5xl font-black text-emerald-400"
                                >
                                    {{ monthlyVipDispatches }}
                                    <span
                                        class="text-sm font-bold text-slate-400 uppercase"
                                        >Badges</span
                                    >
                                </h2>
                            </div>
                            <div
                                class="rounded-2xl border-2 border-emerald-500/20 bg-emerald-500/10 p-3"
                            >
                                <Award
                                    class="h-8 w-8 fill-emerald-400 text-emerald-400"
                                />
                            </div>
                        </div>
                        <p class="mt-2 text-xs leading-relaxed text-slate-400">
                            Contracts evaluated by the system. High-quality
                            calls increase dispatch visibility scores
                            automatically.
                        </p>
                    </div>
                </div>

                <!-- Account Health / Verification Board -->
                <div
                    class="flex flex-1 flex-col justify-between rounded-3xl border-4 border-slate-900 bg-slate-900/30 p-6"
                >
                    <div>
                        <div
                            class="mb-4 flex items-center justify-between border-b-2 border-slate-900 pb-3"
                        >
                            <h3
                                class="text-xs font-black tracking-widest text-slate-400 uppercase"
                            >
                                Account Health Telemetry
                            </h3>
                            <span
                                class="rounded-md bg-slate-900 px-2.5 py-0.5 text-[9px] font-extrabold tracking-widest text-slate-400 uppercase"
                            >
                                Real-Time Billing Scans
                            </span>
                        </div>

                        <!-- System Checklist details -->
                        <div class="mt-2 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <!-- Billing item -->
                            <div
                                class="flex items-center gap-4 rounded-2xl border-2 border-slate-900 bg-slate-950 p-4"
                            >
                                <div
                                    v-if="billing === 'paid'"
                                    class="text-emerald-400"
                                >
                                    <CheckCircle
                                        class="h-8 w-8 fill-emerald-500/10"
                                    />
                                </div>
                                <div
                                    v-else
                                    class="animate-bounce text-rose-400"
                                >
                                    <XCircle class="h-8 w-8 fill-rose-500/10" />
                                </div>
                                <div>
                                    <span
                                        class="block text-[10px] font-bold text-slate-500 uppercase"
                                        >Billing Status</span
                                    >
                                    <span
                                        class="text-sm font-extrabold tracking-tight uppercase"
                                        :class="
                                            billing === 'paid'
                                                ? 'text-emerald-400'
                                                : 'text-rose-400'
                                        "
                                    >
                                        {{
                                            billing === 'paid'
                                                ? 'Account Paid'
                                                : 'Payment Overdue'
                                        }}
                                    </span>
                                </div>
                            </div>

                            <!-- Disputes item -->
                            <div
                                class="flex items-center gap-4 rounded-2xl border-2 border-slate-900 bg-slate-950 p-4"
                            >
                                <div
                                    v-if="disputes === 0"
                                    class="text-emerald-400"
                                >
                                    <CheckCircle
                                        class="h-8 w-8 fill-emerald-500/10"
                                    />
                                </div>
                                <div
                                    v-else
                                    class="animate-bounce text-rose-400"
                                >
                                    <XCircle class="h-8 w-8 fill-rose-500/10" />
                                </div>
                                <div>
                                    <span
                                        class="block text-[10px] font-bold text-slate-500 uppercase"
                                        >Active Disputes</span
                                    >
                                    <span
                                        class="text-sm font-extrabold tracking-tight uppercase"
                                        :class="
                                            disputes === 0
                                                ? 'text-emerald-400'
                                                : 'text-rose-400'
                                        "
                                    >
                                        {{
                                            disputes === 0
                                                ? 'No Open Disputes'
                                                : `${disputes} Active Disputes`
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Duolingo style button -->
                    <div
                        class="mt-6 flex justify-end border-t-2 border-slate-900 pt-4"
                    >
                        <button
                            class="flex cursor-pointer items-center gap-1.5 rounded-2xl border-4 border-slate-800 bg-slate-900 px-6 py-3 text-xs font-black tracking-wider text-white uppercase shadow-[0_4px_0_#1e293b] transition-all hover:translate-y-0.5 hover:bg-slate-800 hover:shadow-[0_2px_0_#1e293b] active:translate-y-1 active:shadow-none"
                        >
                            View Leaderboards <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
