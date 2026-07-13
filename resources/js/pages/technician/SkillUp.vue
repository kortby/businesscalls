<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Wrench,
    Award,
    ChevronLeft,
    Lock,
    CheckCircle2,
    AlertTriangle,
    Play,
} from '@lucide/vue';
import { ref } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

interface Milestone {
    id: number;
    title: string;
    description: string;
    status: 'locked' | 'active' | 'completed';
    icon: string;
}

const props = defineProps<{
    employee: {
        id: number;
        first_name: string;
        last_name: string;
    };
    mascotState: number;
    hasEmergency: boolean;
    hasDelay: boolean;
    hasPositiveCsat: boolean;
    milestones: Milestone[];
}>();

const selectedMilestone = ref<Milestone | null>(props.milestones[0] || null);

const selectMilestone = (milestone: Milestone) => {
    selectedMilestone.value = milestone;
};

// Offset path coordinates for the Duolingo path layout
const getOffsetClass = (index: number) => {
    const offsets = [
        'translate-x-0',
        'translate-x-6',
        'translate-x-0',
        '-translate-x-6',
    ];

    return offsets[index % offsets.length];
};
</script>

<template>
    <Head title="Technician Portal - Skill Up Roadmap" />

    <div
        class="flex min-h-screen flex-col items-center bg-slate-950 p-4 pb-12 font-sans text-slate-100"
    >
        <!-- Main Card Container -->
        <div
            class="flex min-h-[85vh] w-full max-w-md flex-col overflow-hidden rounded-3xl border-4 border-slate-950 bg-slate-900 shadow-2xl"
        >
            <!-- Header -->
            <header
                class="flex items-center justify-between border-b border-slate-800 bg-slate-950 p-4"
            >
                <Link
                    href="/technician/dashboard"
                    class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-800 bg-slate-900 text-slate-400 transition-colors hover:text-white"
                >
                    <ChevronLeft class="h-5 w-5" />
                </Link>
                <div class="text-center">
                    <h1
                        class="text-sm font-black tracking-wider text-slate-200 uppercase"
                    >
                        Skill Up Roadmap
                    </h1>
                    <p
                        class="text-[10px] font-bold tracking-widest text-amber-500 uppercase"
                    >
                        Level Up Your Rank
                    </p>
                </div>
                <div class="flex h-9 w-9 items-center justify-center text-lg">
                    🏆
                </div>
            </header>

            <!-- Mascot Header status -->
            <div
                class="flex flex-col items-center justify-center gap-4 border-b border-slate-950 bg-slate-950/40 p-6 text-center"
            >
                <div class="relative h-32 w-32">
                    <DispatcherMascot :state="mascotState" skin="gold" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-black text-white">
                        Rank: Senior Field Expert
                    </h3>
                    <p class="max-w-xs text-xs text-slate-400">
                        <span
                            v-if="mascotState === 1"
                            class="font-bold text-amber-400"
                            >⚠️ Active Triage Incident:</span
                        >
                        <span
                            v-else-if="mascotState === 2"
                            class="font-bold text-emerald-400"
                            >🎉 Targets Hit!</span
                        >
                        <span
                            v-else-if="mascotState === 3"
                            class="font-bold text-rose-400"
                            >🛑 Delay Detected!</span
                        >
                        <span v-else class="text-slate-400"
                            >Complete assignments to unlock new
                            milestones.</span
                        >
                    </p>
                </div>
            </div>

            <!-- Path & Map Panel -->
            <main class="relative flex flex-1 flex-col justify-start p-6">
                <!-- Vertical SVG Connecting Line for Duolingo style path -->
                <div
                    class="pointer-events-none absolute top-10 bottom-24 left-1/2 z-0 w-2 -translate-x-1/2 border-l-4 border-dashed border-slate-800"
                ></div>

                <!-- Path Nodes Loop -->
                <div
                    class="relative z-10 my-4 flex flex-col items-center gap-10"
                >
                    <div
                        v-for="(milestone, idx) in milestones"
                        :key="milestone.id"
                        class="flex flex-col items-center transition-all duration-300"
                        :class="[getOffsetClass(idx)]"
                    >
                        <!-- Node Circle Button -->
                        <button
                            @click="selectMilestone(milestone)"
                            class="relative flex h-16 w-16 cursor-pointer items-center justify-center rounded-full border-4 text-2xl shadow-lg transition-all duration-200 hover:scale-105 active:scale-95"
                            :class="[
                                milestone.status === 'completed'
                                    ? 'border-emerald-700 bg-emerald-500 text-white shadow-emerald-950/20'
                                    : milestone.status === 'active'
                                      ? 'animate-bounce border-amber-700 bg-amber-500 text-white shadow-amber-950/20'
                                      : 'border-slate-950 bg-slate-800 text-slate-500 shadow-slate-950/40',
                            ]"
                            style="border-bottom-width: 7px"
                        >
                            <!-- Crown or Lock overlay -->
                            <span
                                v-if="milestone.status === 'locked'"
                                class="absolute -top-1 -right-1 rounded-full border border-slate-800 bg-slate-950 p-0.5 text-[10px] text-slate-400"
                            >
                                <Lock class="h-3 w-3" />
                            </span>
                            <span
                                v-else-if="milestone.status === 'completed'"
                                class="absolute -top-1 -right-1 rounded-full border border-slate-950 bg-emerald-600 p-0.5 text-[10px] text-white"
                            >
                                <CheckCircle2 class="h-3 w-3" />
                            </span>
                            <span
                                v-else
                                class="absolute -top-1 -right-1 animate-pulse rounded-full border border-slate-950 bg-amber-600 p-0.5 text-[10px] text-white"
                            >
                                <Play class="h-3 w-3 fill-current" />
                            </span>

                            {{ milestone.icon }}
                        </button>

                        <span
                            class="mt-2 max-w-[120px] text-center text-[10px] font-black tracking-wider uppercase"
                            :class="[
                                milestone.status === 'completed'
                                    ? 'text-emerald-400'
                                    : milestone.status === 'active'
                                      ? 'text-amber-400'
                                      : 'text-slate-500',
                            ]"
                        >
                            {{ milestone.title }}
                        </span>
                    </div>
                </div>

                <!-- Milestone Detail Drawer Panel -->
                <div
                    v-if="selectedMilestone"
                    class="mt-auto animate-in space-y-3 rounded-2xl border-2 border-slate-800 bg-slate-950/80 p-4 shadow-xl duration-350 fade-in slide-in-from-bottom-2"
                >
                    <div class="flex items-center justify-between">
                        <h4
                            class="text-xs font-black tracking-widest text-slate-400 uppercase"
                        >
                            Milestone Details
                        </h4>
                        <span
                            class="rounded border px-2 py-0.5 text-[8px] font-black tracking-wider uppercase"
                            :class="[
                                selectedMilestone.status === 'completed'
                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
                                    : selectedMilestone.status === 'active'
                                      ? 'border-amber-500/20 bg-amber-500/10 text-amber-400'
                                      : 'border-slate-800 bg-slate-900 text-slate-500',
                            ]"
                        >
                            {{ selectedMilestone.status }}
                        </span>
                    </div>
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-800 bg-slate-900 text-xl shadow-inner"
                        >
                            {{ selectedMilestone.icon }}
                        </div>
                        <div>
                            <h3
                                class="text-sm leading-tight font-black text-white"
                            >
                                {{ selectedMilestone.title }}
                            </h3>
                            <p
                                class="text-slate-455 mt-1 text-xs leading-normal"
                            >
                                {{ selectedMilestone.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer
                class="border-t border-slate-800/60 bg-slate-950 p-4 text-center text-[9px] font-bold tracking-wider text-slate-600 uppercase"
            >
                Rank milestones reset every 30 days
            </footer>
        </div>
    </div>
</template>

<style scoped>
/* Heavy borders and playful aesthetics mimicking Duolingo */
button {
    box-shadow: 0 4px 0 #000;
}
button:active {
    transform: translateY(3px);
    box-shadow: 0 1px 0 #000;
}
</style>
