<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Trophy,
    Flame,
    Award,
    Star,
    Sparkles,
    Lock,
    Unlock,
    CheckCircle2,
    ArrowLeft,
    PhoneCall,
    Mic,
} from '@lucide/vue';
import { ref } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

interface Milestone {
    level: number;
    name: string;
    target: number;
    unlocked: boolean;
}

interface Achievement {
    id: string;
    name: string;
    description: string;
    metric: number;
    unit: string;
    milestones: Milestone[];
}

const props = defineProps<{
    achievements: Achievement[];
    averageCqs: number;
    streak: number;
    totalBookings: number;
    totalCustomVoices: number;
    totalCampaigns: number;
}>();

const mascotState = ref<number>(0);
const clickedCardId = ref<string | null>(null);

// Get the current active milestone tier name and next target
const getMilestoneInfo = (achievement: Achievement) => {
    let activeLevel = 0;
    let nextTarget = achievement.milestones[0].target;
    let nextName = achievement.milestones[0].name;
    let currentTier = 'None';

    achievement.milestones.forEach((m) => {
        if (m.unlocked) {
            activeLevel = m.level;
            currentTier = m.name;
        }
    });

    const nextMilestone = achievement.milestones.find((m) => !m.unlocked);
    if (nextMilestone) {
        nextTarget = nextMilestone.target;
        nextName = nextMilestone.name;
    } else {
        // All unlocked
        nextTarget =
            achievement.milestones[achievement.milestones.length - 1].target;
        nextName = 'Max';
    }

    return {
        activeLevel,
        currentTier,
        nextTarget,
        nextName,
        allUnlocked: !nextMilestone,
    };
};

// Calculate progress percentage
const getProgressPercentage = (achievement: Achievement) => {
    const info = getMilestoneInfo(achievement);
    if (info.allUnlocked) return 100;

    // Calculate progress towards the next milestone
    const progress = (achievement.metric / info.nextTarget) * 100;
    return Math.min(100, Math.max(0, progress));
};

// Handle Hover to trigger mascot states
const handleCardHover = (achievement: Achievement, isHovered: boolean) => {
    if (!isHovered) {
        mascotState.value = 0; // Return to Idle
        return;
    }

    const info = getMilestoneInfo(achievement);
    if (info.activeLevel > 0) {
        mascotState.value = 2; // Victory state for unlocked levels
    } else {
        mascotState.value = 3; // Sad/Error state for locked/un-achieved
    }
};

// Click effect (confetti & mascot celebration)
const triggerCelebration = (achievement: Achievement) => {
    clickedCardId.value = achievement.id;
    mascotState.value = 2; // Victory pose

    setTimeout(() => {
        clickedCardId.value = null;
    }, 1500);
};
</script>

<template>
    <Head title="Achievements Dashboard" />

    <div
        class="min-h-screen bg-slate-950 p-6 font-sans text-slate-100 selection:bg-emerald-500 selection:text-white"
    >
        <!-- Header -->
        <header
            class="mb-8 flex flex-col justify-between gap-4 border-b-4 border-slate-900 pb-6 md:flex-row md:items-center"
        >
            <div>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border-2 border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-black tracking-widest text-emerald-400 uppercase"
                >
                    <Trophy class="h-3 w-3 fill-emerald-400 text-emerald-400" />
                    Gamified Milestones
                </span>
                <h1
                    class="mt-2 text-4xl font-extrabold tracking-tight text-white uppercase md:text-5xl"
                >
                    Tenant
                    <span
                        class="bg-gradient-to-r from-amber-400 via-orange-500 to-yellow-500 bg-clip-text text-transparent"
                        >Achievements</span
                    >
                </h1>
                <p
                    class="mt-1 text-xs font-semibold tracking-wider text-slate-400 uppercase"
                >
                    Unlock badges and level up your dispatch center
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Link
                    href="/dashboard"
                    class="flex cursor-pointer items-center gap-2 rounded-2xl border-4 border-slate-800 bg-slate-900 px-5 py-2.5 text-xs font-black tracking-wider text-white uppercase shadow-[0_4px_0_#1e293b] transition-all hover:translate-y-0.5 hover:bg-slate-800 hover:shadow-[0_2px_0_#1e293b] active:translate-y-1 active:shadow-none"
                >
                    <ArrowLeft class="h-4 w-4" /> Back to Dashboard
                </Link>
            </div>
        </header>

        <!-- Main Layout Grid -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Left Column: Rive Mascot Center & Stats Summary -->
            <div class="flex flex-col gap-6 lg:col-span-1">
                <!-- Rive Mascot Card -->
                <div
                    class="relative flex flex-col items-center justify-center overflow-hidden rounded-3xl border-4 border-slate-900 bg-slate-900/50 p-6 text-center"
                >
                    <h3
                        class="mb-4 self-start text-xs font-black tracking-widest text-slate-400 uppercase"
                    >
                        Mascot Reaction
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
                                >Woohoo! Looking Great! 🎉</span
                            >
                            <span
                                v-else-if="mascotState === 3"
                                class="text-rose-400"
                                >Keep pushing for targets! 💪</span
                            >
                            <span v-else class="text-amber-400"
                                >Always ready to dispatch</span
                            >
                        </div>
                        <p class="px-4 text-xs text-slate-400">
                            Hover over cards to see unlocked status. Click cards
                            to trigger celebration!
                        </p>
                    </div>
                </div>

                <!-- Call Quality Score (CSAT) Summary -->
                <div
                    class="space-y-4 rounded-3xl border-4 border-slate-900 bg-slate-900/30 p-6"
                >
                    <h3
                        class="text-xs font-black tracking-widest text-slate-400 uppercase"
                    >
                        Quality Score Status
                    </h3>

                    <div
                        class="rounded-2xl border-2 border-slate-900 bg-slate-950 p-4 text-center"
                    >
                        <span
                            class="block text-[10px] font-bold text-slate-500 uppercase"
                            >Avg Quality Score (CQS)</span
                        >
                        <span
                            class="mt-1 block font-mono text-3xl font-extrabold text-white"
                        >
                            {{ (averageCqs * 100).toFixed(1) }}%
                        </span>
                        <div
                            class="mt-3 flex items-center justify-center gap-1.5"
                        >
                            <span
                                :class="
                                    averageCqs >= 0.9
                                        ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
                                        : 'border-amber-500/20 bg-amber-500/10 text-amber-500'
                                "
                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[9px] font-black tracking-wider uppercase"
                            >
                                {{
                                    averageCqs >= 0.9
                                        ? 'A Grade'
                                        : 'Needs Review'
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Achievements & Progress List -->
            <div class="flex flex-col gap-6 lg:col-span-2">
                <div
                    class="space-y-6 rounded-3xl border-4 border-slate-900 bg-slate-900/30 p-6"
                >
                    <div class="flex items-center justify-between">
                        <h2
                            class="text-lg font-black tracking-widest text-slate-200 uppercase"
                        >
                            Your Milestone Roadmap
                        </h2>
                        <span
                            class="rounded-lg bg-slate-900 px-3 py-1 font-mono text-xs font-bold text-emerald-400"
                        >
                            {{
                                achievements.filter(
                                    (a) => getMilestoneInfo(a).activeLevel > 0,
                                ).length
                            }}/{{ achievements.length }} Active
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <!-- Achievement Card -->
                        <div
                            v-for="achievement in achievements"
                            :key="achievement.id"
                            @mouseenter="handleCardHover(achievement, true)"
                            @mouseleave="handleCardHover(achievement, false)"
                            @click="triggerCelebration(achievement)"
                            :class="[
                                clickedCardId === achievement.id
                                    ? 'scale-[1.02] border-emerald-500/80 bg-emerald-950/10 shadow-[0_0_25px_rgba(16,185,129,0.15)]'
                                    : 'border-slate-900 bg-slate-900/30 hover:border-slate-800 hover:bg-slate-900/50',
                                'relative cursor-pointer rounded-3xl border-4 p-6 transition-all duration-300',
                            ]"
                        >
                            <!-- Celebrate Confetti Effect Overlay -->
                            <div
                                v-if="clickedCardId === achievement.id"
                                class="pointer-events-none absolute inset-0 overflow-hidden rounded-[20px]"
                            >
                                <div
                                    class="absolute inset-0 animate-pulse bg-emerald-500/5"
                                ></div>
                                <div
                                    class="absolute -top-10 left-1/4 h-2 w-2 animate-ping rounded-full bg-emerald-400"
                                ></div>
                                <div
                                    class="absolute -top-10 left-2/4 h-3 w-3 animate-ping rounded-full bg-yellow-400 delay-75"
                                ></div>
                                <div
                                    class="absolute -top-10 left-3/4 h-2.5 w-2.5 animate-ping rounded-full bg-teal-400 delay-150"
                                ></div>
                            </div>

                            <div
                                class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
                            >
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <!-- Dynamic Icons -->
                                        <div
                                            :class="{
                                                'border-amber-500/20 bg-amber-500/10 text-amber-400':
                                                    achievement.id ===
                                                    'bookings_streak',
                                                'border-emerald-500/20 bg-emerald-500/10 text-emerald-400':
                                                    achievement.id ===
                                                    'total_bookings',
                                                'border-indigo-500/20 bg-indigo-500/10 text-indigo-400':
                                                    achievement.id ===
                                                    'custom_voices',
                                                'border-rose-500/20 bg-rose-500/10 text-rose-400':
                                                    achievement.id ===
                                                    'campaigns',
                                            }"
                                            class="rounded-xl border p-2"
                                        >
                                            <Flame
                                                v-if="
                                                    achievement.id ===
                                                    'bookings_streak'
                                                "
                                                class="h-5 w-5"
                                            />
                                            <PhoneCall
                                                v-else-if="
                                                    achievement.id ===
                                                    'total_bookings'
                                                "
                                                class="h-5 w-5"
                                            />
                                            <Mic
                                                v-else-if="
                                                    achievement.id ===
                                                    'custom_voices'
                                                "
                                                class="h-5 w-5"
                                            />
                                            <Award v-else class="h-5 w-5" />
                                        </div>
                                        <h3
                                            class="text-base font-black tracking-tight text-white uppercase"
                                        >
                                            {{ achievement.name }}
                                        </h3>
                                    </div>
                                    <p
                                        class="max-w-md text-xs leading-relaxed font-medium text-slate-400"
                                    >
                                        {{ achievement.description }}
                                    </p>
                                </div>

                                <div class="flex flex-col items-end gap-1">
                                    <span
                                        class="text-xs font-semibold text-slate-500 uppercase"
                                        >Current Metrics</span
                                    >
                                    <span
                                        class="font-mono text-2xl font-black text-white"
                                    >
                                        {{ achievement.metric }}
                                        <span
                                            class="text-xs font-bold text-slate-400 uppercase"
                                            >{{ achievement.unit }}</span
                                        >
                                    </span>
                                </div>
                            </div>

                            <!-- Milestone Tier Badges -->
                            <div class="mt-5 grid grid-cols-3 gap-3">
                                <div
                                    v-for="m in achievement.milestones"
                                    :key="m.level"
                                    :class="[
                                        m.unlocked
                                            ? m.level === 1
                                                ? 'border-amber-800 bg-amber-950/20 font-bold text-amber-500 shadow-[inset_0_2px_4px_rgba(245,158,11,0.05)]'
                                                : m.level === 2
                                                  ? 'border-slate-600 bg-slate-800/30 font-bold text-slate-300 shadow-[inset_0_2px_4px_rgba(148,163,184,0.05)]'
                                                  : 'border-yellow-500 bg-yellow-500/10 font-black text-yellow-400 shadow-[0_0_15px_rgba(234,179,8,0.15)]'
                                            : 'border-slate-900 bg-slate-950/50 text-slate-600 opacity-60',
                                        'flex items-center justify-between rounded-2xl border-2 px-3 py-2 text-[10px] tracking-widest uppercase transition-all duration-300',
                                    ]"
                                >
                                    <span
                                        class="flex items-center gap-1.5 font-black"
                                    >
                                        <Sparkles
                                            v-if="m.unlocked && m.level === 3"
                                            class="h-3 w-3 animate-spin text-yellow-400"
                                        />
                                        {{ m.name }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <span class="font-mono font-bold">{{
                                            m.target
                                        }}</span>
                                        <CheckCircle2
                                            v-if="m.unlocked"
                                            class="h-3.5 w-3.5 fill-current/10 text-current"
                                        />
                                        <Lock
                                            v-else
                                            class="h-3 w-3 text-slate-700"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Track (Duolingo style) -->
                            <div class="mt-5">
                                <div
                                    class="mb-1.5 flex items-center justify-between text-[9px] font-black tracking-wider text-slate-500 uppercase"
                                >
                                    <span
                                        >Progress to
                                        {{
                                            getMilestoneInfo(achievement)
                                                .nextName
                                        }}</span
                                    >
                                    <span
                                        class="font-mono text-xs font-black text-slate-300"
                                    >
                                        {{ achievement.metric }}/{{
                                            getMilestoneInfo(achievement)
                                                .nextTarget
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="h-4 w-full overflow-hidden rounded-full border-2 border-slate-900 bg-slate-950 p-0.5"
                                >
                                    <div
                                        :style="{
                                            width: `${getProgressPercentage(achievement)}%`,
                                        }"
                                        class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-green-500 shadow-[0_0_8px_rgba(52,211,153,0.3)] transition-all duration-500"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
