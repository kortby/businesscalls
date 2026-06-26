<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import { Wrench, Award, ChevronLeft, Lock, CheckCircle2, AlertTriangle, Play } from '@lucide/vue';

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
    const offsets = ['translate-x-0', 'translate-x-6', 'translate-x-0', '-translate-x-6'];
    return offsets[index % offsets.length];
};
</script>

<template>
    <Head title="Technician Portal - Skill Up Roadmap" />

    <div class="min-h-screen bg-slate-950 text-slate-100 font-sans flex flex-col items-center p-4 pb-12">
        <!-- Main Card Container -->
        <div class="w-full max-w-md bg-slate-900 rounded-3xl border-4 border-slate-950 overflow-hidden shadow-2xl flex flex-col min-h-[85vh]">
            
            <!-- Header -->
            <header class="bg-slate-950 p-4 border-b border-slate-800 flex items-center justify-between">
                <Link 
                    href="/technician/dashboard" 
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors"
                >
                    <ChevronLeft class="w-5 h-5" />
                </Link>
                <div class="text-center">
                    <h1 class="text-sm font-black text-slate-200 uppercase tracking-wider">Skill Up Roadmap</h1>
                    <p class="text-[10px] text-amber-500 font-bold uppercase tracking-widest">Level Up Your Rank</p>
                </div>
                <div class="w-9 h-9 flex items-center justify-center text-lg">
                    🏆
                </div>
            </header>

            <!-- Mascot Header status -->
            <div class="p-6 bg-slate-950/40 border-b border-slate-950 flex flex-col items-center justify-center gap-4 text-center">
                <div class="h-32 w-32 relative">
                    <DispatcherMascot :state="mascotState" skin="gold" />
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-black text-white">
                        Rank: Senior Field Expert
                    </h3>
                    <p class="text-xs text-slate-400 max-w-xs">
                        <span v-if="mascotState === 1" class="text-amber-400 font-bold">⚠️ Active Triage Incident:</span>
                        <span v-else-if="mascotState === 2" class="text-emerald-400 font-bold">🎉 Targets Hit!</span>
                        <span v-else-if="mascotState === 3" class="text-rose-400 font-bold">🛑 Delay Detected!</span>
                        <span v-else class="text-slate-400">Complete assignments to unlock new milestones.</span>
                    </p>
                </div>
            </div>

            <!-- Path & Map Panel -->
            <main class="flex-1 p-6 flex flex-col justify-start relative">
                
                <!-- Vertical SVG Connecting Line for Duolingo style path -->
                <div class="absolute left-1/2 top-10 bottom-24 -translate-x-1/2 w-2 border-l-4 border-dashed border-slate-800 pointer-events-none z-0"></div>

                <!-- Path Nodes Loop -->
                <div class="flex flex-col items-center gap-10 relative z-10 my-4">
                    <div 
                        v-for="(milestone, idx) in milestones" 
                        :key="milestone.id"
                        class="flex flex-col items-center transition-all duration-300"
                        :class="[getOffsetClass(idx)]"
                    >
                        <!-- Node Circle Button -->
                        <button
                            @click="selectMilestone(milestone)"
                            class="w-16 h-16 rounded-full border-4 flex items-center justify-center text-2xl shadow-lg relative transition-all duration-200 hover:scale-105 active:scale-95 cursor-pointer"
                            :class="[
                                milestone.status === 'completed'
                                    ? 'bg-emerald-500 border-emerald-700 text-white shadow-emerald-950/20'
                                    : milestone.status === 'active'
                                    ? 'bg-amber-500 border-amber-700 text-white shadow-amber-950/20 animate-bounce'
                                    : 'bg-slate-800 border-slate-950 text-slate-500 shadow-slate-950/40'
                            ]"
                            style="border-bottom-width: 7px;"
                        >
                            <!-- Crown or Lock overlay -->
                            <span v-if="milestone.status === 'locked'" class="absolute -top-1 -right-1 bg-slate-950 text-slate-400 p-0.5 rounded-full text-[10px] border border-slate-800">
                                <Lock class="w-3 h-3" />
                            </span>
                            <span v-else-if="milestone.status === 'completed'" class="absolute -top-1 -right-1 bg-emerald-600 text-white p-0.5 rounded-full text-[10px] border border-slate-950">
                                <CheckCircle2 class="w-3 h-3" />
                            </span>
                            <span v-else class="absolute -top-1 -right-1 bg-amber-600 text-white p-0.5 rounded-full text-[10px] border border-slate-950 animate-pulse">
                                <Play class="w-3 h-3 fill-current" />
                            </span>
                            
                            {{ milestone.icon }}
                        </button>

                        <span class="mt-2 text-[10px] font-black uppercase tracking-wider max-w-[120px] text-center"
                            :class="[
                                milestone.status === 'completed' ? 'text-emerald-400' :
                                milestone.status === 'active' ? 'text-amber-400' : 'text-slate-500'
                            ]"
                        >
                            {{ milestone.title }}
                        </span>
                    </div>
                </div>

                <!-- Milestone Detail Drawer Panel -->
                <div 
                    v-if="selectedMilestone"
                    class="mt-auto bg-slate-950/80 rounded-2xl border-2 border-slate-800 p-4 space-y-3 shadow-xl animate-in fade-in slide-in-from-bottom-2 duration-350"
                >
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">Milestone Details</h4>
                        <span 
                            class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider border"
                            :class="[
                                selectedMilestone.status === 'completed'
                                    ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                                    : selectedMilestone.status === 'active'
                                    ? 'bg-amber-500/10 text-amber-400 border-amber-500/20'
                                    : 'bg-slate-900 text-slate-500 border-slate-800'
                            ]"
                        >
                            {{ selectedMilestone.status }}
                        </span>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-xl border border-slate-800 shadow-inner">
                            {{ selectedMilestone.icon }}
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-white leading-tight">{{ selectedMilestone.title }}</h3>
                            <p class="text-xs text-slate-455 mt-1 leading-normal">
                                {{ selectedMilestone.description }}
                            </p>
                        </div>
                    </div>
                </div>

            </main>

            <!-- Footer -->
            <footer class="bg-slate-950 p-4 border-t border-slate-800/60 text-center text-[9px] text-slate-600 font-bold uppercase tracking-wider">
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
