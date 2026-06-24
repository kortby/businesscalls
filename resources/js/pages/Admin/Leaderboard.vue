<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Award,
    Clock,
    CheckCircle,
    Smile,
    Percent,
    TrendingUp,
    ChevronRight,
    HelpCircle,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

const props = defineProps<{
    leaderboard: Array<{
        id: number;
        name: string;
        jobs_completed: number;
        avg_response_time: number;
        csat: number;
        rank_index: number;
        skills: string[];
    }>;
}>();

const selectedIndex = ref(0);
const showFormulaInfo = ref(false);

const selectedTech = computed(() => {
    return props.leaderboard[selectedIndex.value] || null;
});

// Compute mascot state dynamically based on the ranking of the selected technician
const mascotState = computed(() => {
    if (!selectedTech.value) return 0;

    // Top 3 get celebratory state (2)
    if (selectedIndex.value < 3) {
        return 2;
    }

    // If rank index is low (below 50), show sad state (3)
    if (selectedTech.value.rank_index < 50.0) {
        return 3;
    }

    return 0; // default idle
});
</script>

<template>
    <Head title="Technician Performance Leaderboard" />

    <div
        class="min-h-screen bg-[#F0FDF4] p-6 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    >
        <!-- Duolingo Styled Header -->
        <header
            class="mb-8 flex flex-col gap-4 rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] sm:flex-row sm:items-center sm:justify-between dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
        >
            <div class="flex items-center gap-4">
                <div
                    class="rounded-2xl border-4 border-slate-900 bg-amber-400 p-3 text-slate-950 dark:border-slate-100"
                >
                    <Award class="h-8 w-8 stroke-[3]" />
                </div>
                <div>
                    <h1
                        class="text-2xl font-black tracking-tight uppercase sm:text-3xl"
                    >
                        Technician Leaderboard
                    </h1>
                    <p
                        class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Performance Index & Gamified Quality Rankings
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <button
                    @click="showFormulaInfo = !showFormulaInfo"
                    class="inline-flex items-center gap-2 rounded-2xl border-4 border-slate-900 bg-white px-4 py-2 text-sm font-black uppercase shadow-[3px_3px_0px_0px_rgba(15,23,42,1)] transition-all hover:translate-y-0.5 active:translate-y-1 dark:border-slate-100 dark:bg-slate-800"
                >
                    <HelpCircle class="h-5 w-5 stroke-[3] text-blue-500" />
                    Formula Details
                </button>
            </div>
        </header>

        <!-- Mathematical Formula Details Box -->
        <Transition name="fade">
            <div
                v-if="showFormulaInfo"
                class="mb-8 rounded-3xl border-4 border-slate-900 bg-blue-50 p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
            >
                <h3
                    class="mb-3 text-lg font-black tracking-tight text-blue-900 uppercase dark:text-blue-400"
                >
                    Technician Rank Index Calculation
                </h3>
                <p
                    class="text-sm leading-relaxed font-bold text-slate-700 dark:text-slate-300"
                >
                    Technicians are ranked using a weighted index score
                    calculated dynamically as follows:
                </p>
                <div
                    class="my-4 flex items-center justify-center rounded-2xl border-4 border-slate-900 bg-white p-4 font-mono text-sm font-black tracking-wide text-slate-900 dark:border-slate-100 dark:bg-slate-950 dark:text-slate-100"
                >
                    Rank Score = (15 × Completed Jobs) + (25 × (1 - Response
                    Time / 120)) + (0.6 × CSAT)
                </div>
                <div
                    class="grid grid-cols-1 gap-4 text-xs font-bold sm:grid-cols-3"
                >
                    <div
                        class="rounded-xl border-2 border-slate-900 bg-white p-3 dark:border-slate-100 dark:bg-slate-950"
                    >
                        <span class="font-black text-amber-500 uppercase"
                            >Completed Jobs Weight</span
                        >
                        <p class="mt-1 text-slate-500">
                            15 points per completed job today
                            ($J_{\text{comp}}$).
                        </p>
                    </div>
                    <div
                        class="rounded-xl border-2 border-slate-900 bg-white p-3 dark:border-slate-100 dark:bg-slate-950"
                    >
                        <span class="font-black text-blue-500 uppercase"
                            >Response Speed Term</span
                        >
                        <p class="mt-1 text-slate-500">
                            Up to 25 points based on average response time
                            ($t_{\text{response}}$) relative to a 120-minute SLA
                            buffer.
                        </p>
                    </div>
                    <div
                        class="rounded-xl border-2 border-slate-900 bg-white p-3 dark:border-slate-100 dark:bg-slate-950"
                    >
                        <span class="font-black text-emerald-500 uppercase"
                            >Customer Satisfaction Score</span
                        >
                        <p class="mt-1 text-slate-500">
                            Scaled CSAT feedback weight
                            ($w_{\text{satisfaction}} = 0.6$).
                        </p>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Main Leaderboard Layout Grid -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Left 2/3: Leaderboard List -->
            <div class="flex flex-col gap-6 lg:col-span-2">
                <div
                    class="rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h2
                        class="mb-6 text-xl font-black tracking-tight uppercase"
                    >
                        Active Rankings
                    </h2>

                    <div class="flex flex-col gap-4">
                        <div
                            v-for="(tech, idx) in leaderboard"
                            :key="tech.id"
                            @click="selectedIndex = idx"
                            class="flex cursor-pointer items-center justify-between rounded-2xl border-4 border-slate-900 p-4 transition-all duration-150"
                            :class="[
                                selectedIndex === idx
                                    ? '-translate-y-1 bg-amber-100 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:bg-slate-800'
                                    : 'bg-white hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-800',
                            ]"
                        >
                            <!-- Rank Medal / Number -->
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl border-4 border-slate-900 text-lg font-black"
                                    :class="[
                                        idx === 0
                                            ? 'bg-amber-400 text-slate-950'
                                            : idx === 1
                                              ? 'bg-slate-300 text-slate-950'
                                              : idx === 2
                                                ? 'bg-amber-700 text-white'
                                                : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                    ]"
                                >
                                    {{ idx + 1 }}
                                </div>

                                <div>
                                    <div class="text-base font-black">
                                        {{ tech.name }}
                                    </div>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <span
                                            v-for="skill in tech.skills.slice(
                                                0,
                                                2,
                                            )"
                                            :key="skill"
                                            class="rounded border border-slate-900 px-1 text-[10px] font-black uppercase dark:border-slate-100"
                                        >
                                            {{ skill }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Metrics Badges -->
                            <div class="flex items-center gap-6">
                                <div class="hidden items-center gap-3 sm:flex">
                                    <div class="text-center">
                                        <div
                                            class="text-xs font-black text-slate-400 uppercase"
                                        >
                                            Jobs
                                        </div>
                                        <div class="text-sm font-extrabold">
                                            {{ tech.jobs_completed }}
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <div
                                            class="text-xs font-black text-slate-400 uppercase"
                                        >
                                            Speed
                                        </div>
                                        <div class="text-sm font-extrabold">
                                            {{ tech.avg_response_time }}m
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <div
                                            class="text-xs font-black text-slate-400 uppercase"
                                        >
                                            CSAT
                                        </div>
                                        <div
                                            class="text-sm font-extrabold text-emerald-500"
                                        >
                                            {{ tech.csat }}%
                                        </div>
                                    </div>
                                </div>

                                <!-- Rank Index -->
                                <div class="text-right">
                                    <div
                                        class="text-[10px] font-black text-slate-400 uppercase"
                                    >
                                        Rank Index
                                    </div>
                                    <div
                                        class="text-lg font-black text-slate-950 dark:text-slate-100"
                                    >
                                        {{ tech.rank_index.toFixed(2) }}
                                    </div>
                                </div>

                                <ChevronRight
                                    class="h-6 w-6 stroke-[3] text-slate-400"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right 1/3: Rive Mascot & Selected Tech Details -->
            <div class="flex flex-col gap-8">
                <!-- Rive Performance Mascot Card -->
                <div
                    class="flex flex-col items-center justify-center rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h3
                        class="mb-4 self-start text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400"
                    >
                        Performance Coach Mascot
                    </h3>

                    <!-- Render Mascot with correct reactive properties -->
                    <div class="mb-4 aspect-square w-full max-w-[240px]">
                        <DispatcherMascot :state="mascotState" />
                    </div>

                    <div class="w-full text-center">
                        <div class="text-base font-black uppercase">
                            <span
                                v-if="mascotState === 2"
                                class="animate-pulse text-emerald-500"
                                >Top Performer Dance! 🎉</span
                            >
                            <span
                                v-else-if="mascotState === 3"
                                class="text-rose-500"
                                >Needs Coaching Boost! ⚠️</span
                            >
                            <span v-else class="text-blue-500"
                                >Steady Pace 🚀</span
                            >
                        </div>
                        <p
                            class="mt-1 text-xs font-bold text-slate-400 uppercase"
                        >
                            Selected Rank: #{{ selectedIndex + 1 }}
                        </p>
                    </div>
                </div>

                <!-- Detailed performance breakdowns -->
                <div
                    v-if="selectedTech"
                    class="rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h3
                        class="mb-4 text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400"
                    >
                        Metric Breakdown: {{ selectedTech.name }}
                    </h3>

                    <div class="space-y-4 text-sm font-bold">
                        <!-- Completed Jobs -->
                        <div>
                            <div class="mb-1 flex justify-between">
                                <span class="flex items-center gap-1.5"
                                    ><CheckCircle
                                        class="h-4 w-4 text-blue-500"
                                    />
                                    Jobs Completed</span
                                >
                                <span
                                    >{{
                                        selectedTech.jobs_completed
                                    }}
                                    jobs</span
                                >
                            </div>
                            <!-- Bar visualization -->
                            <div
                                class="h-3 w-full overflow-hidden rounded-full border-2 border-slate-900 bg-slate-100 dark:border-slate-100 dark:bg-slate-950"
                            >
                                <div
                                    class="h-full bg-blue-500"
                                    :style="{
                                        width: `${Math.min(100, (selectedTech.jobs_completed / 10) * 100)}%`,
                                    }"
                                ></div>
                            </div>
                        </div>

                        <!-- Response Speed -->
                        <div>
                            <div class="mb-1 flex justify-between">
                                <span class="flex items-center gap-1.5"
                                    ><Clock class="h-4 w-4 text-amber-500" />
                                    Avg Response Time</span
                                >
                                <span
                                    >{{
                                        selectedTech.avg_response_time
                                    }}
                                    min</span
                                >
                            </div>
                            <div
                                class="h-3 w-full overflow-hidden rounded-full border-2 border-slate-900 bg-slate-100 dark:border-slate-100 dark:bg-slate-950"
                            >
                                <!-- Speed visual: shorter response time is better (wider bar) -->
                                <div
                                    class="h-full bg-amber-400"
                                    :style="{
                                        width: `${Math.max(0, (1 - selectedTech.avg_response_time / 120) * 100)}%`,
                                    }"
                                ></div>
                            </div>
                        </div>

                        <!-- CSAT Score -->
                        <div>
                            <div class="mb-1 flex justify-between">
                                <span class="flex items-center gap-1.5"
                                    ><Smile class="h-4 w-4 text-emerald-500" />
                                    CSAT Score</span
                                >
                                <span>{{ selectedTech.csat }}%</span>
                            </div>
                            <div
                                class="h-3 w-full overflow-hidden rounded-full border-2 border-slate-900 bg-slate-100 dark:border-slate-100 dark:bg-slate-950"
                            >
                                <div
                                    class="h-full bg-emerald-500"
                                    :style="{ width: `${selectedTech.csat}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Page Animations */
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.25s ease,
        transform 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
