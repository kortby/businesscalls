<script setup lang="ts">
import { ref, computed } from 'vue';
import {
    Calendar,
    Clock,
    MapPin,
    User,
    CheckCircle2,
    AlertTriangle,
    Sparkles,
    Filter,
    Shield,
    Droplets,
    Wind,
    Zap,
    Lock,
    Navigation,
    Layers,
    Plus,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';

type TradeCategory = 'all' | 'plumbing' | 'hvac' | 'electrical' | 'locksmith';

const activeTrade = ref<TradeCategory>('all');

const technicians = ref([
    {
        id: 1,
        name: 'Marcus Vance',
        role: 'Master Plumber',
        trade: 'plumbing',
        avatar: 'MV',
        status: 'En Route',
        statusColor: 'bg-emerald-500 text-white',
        shift: '08:00 - 17:00',
        activeJob: 'Main Drain Cleaning & Leak Isolation',
        location: 'Downtown North (Zone 2)',
        nextAvailable: '14:30',
        bufferProtected: '1.5h Travel Buffer Active',
        skills: ['Drain Cleaning', 'Water Heaters', 'Emergency Triage'],
    },
    {
        id: 2,
        name: 'Amanda Ross',
        role: 'EPA 608 HVAC Tech',
        trade: 'hvac',
        avatar: 'AR',
        status: 'In Progress',
        statusColor: 'bg-indigo-500 text-white',
        shift: '07:30 - 16:30',
        activeJob: 'AC Compressor Diagnostic & Freon Refill',
        location: 'Westside Business Park',
        nextAvailable: '15:15',
        bufferProtected: '1.5h Travel Buffer Active',
        skills: ['AC Diagnostics', 'Freon EPA 608', 'Heat Pumps'],
    },
    {
        id: 3,
        name: 'Devon Lane',
        role: 'Master Electrician',
        trade: 'electrical',
        avatar: 'DL',
        status: 'Available',
        statusColor: 'bg-amber-500 text-slate-950',
        shift: '09:00 - 18:00',
        activeJob: 'Standby for Urgent Dispatch',
        location: 'Central Metro Hub',
        nextAvailable: 'Immediate',
        bufferProtected: 'Clear Gap',
        skills: ['200A Panels', 'EV Chargers', 'Short Circuit Triage'],
    },
    {
        id: 4,
        name: 'Sarah Connor',
        role: 'Access & Locksmith Specialist',
        trade: 'locksmith',
        avatar: 'SC',
        status: 'En Route',
        statusColor: 'bg-teal-500 text-white',
        shift: '08:00 - 16:00',
        activeJob: 'Commercial Master Keying & Smart Lock Installation',
        location: 'Financial District Plaza',
        nextAvailable: '16:00',
        bufferProtected: '1.5h Travel Buffer Active',
        skills: ['24/7 Lockout', 'Smart Access', 'Commercial Rekey'],
    },
]);

const filteredTechnicians = computed(() => {
    if (activeTrade.value === 'all') return technicians.value;
    return technicians.value.filter((t) => t.trade === activeTrade.value);
});

const isSimulatingNewJob = ref(false);
const jobAssignedMessage = ref<string | null>(null);

const simulateAutoDispatch = () => {
    isSimulatingNewJob.value = true;
    jobAssignedMessage.value = null;

    setTimeout(() => {
        isSimulatingNewJob.value = false;
        jobAssignedMessage.value = 'AI Voice Receptionist received call → Emergency Pipe Leak matched to Marcus Vance with 1.5h travel buffer auto-calculated!';
    }, 1200);
};
</script>

<template>
    <section id="dispatch-board-showcase" class="border-b bg-slate-50 py-16 dark:bg-slate-950/40 md:py-24">
        <div class="container mx-auto px-4 sm:px-6">
            <!-- Header -->
            <div class="mx-auto mb-12 max-w-[800px] space-y-4 text-center">
                <div class="inline-flex items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3.5 py-1 text-xs font-bold text-indigo-600 uppercase tracking-wider dark:text-indigo-400">
                    <Layers class="h-3.5 w-3.5 text-indigo-500" />
                    <span>Visual Dispatch Control Center</span>
                </div>

                <h2 class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl md:text-5xl">
                    Live <span class="bg-gradient-to-r from-indigo-600 via-purple-600 to-emerald-500 bg-clip-text text-transparent dark:from-indigo-400 dark:via-purple-400 dark:to-emerald-400">Visual Dispatch Board</span> for Trade Contractors
                </h2>

                <p class="text-base leading-relaxed text-muted-foreground sm:text-lg">
                    See technician shifts, active jobs, emergency routes, and automatic 1.5-hour travel buffers calculated in real-time as calls convert into booked jobs.
                </p>
            </div>

            <!-- Dispatch Board Container Card -->
            <div class="rounded-2xl border bg-card/90 shadow-2xl backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/90">
                <!-- Toolbar Bar -->
                <div class="flex flex-col items-start justify-between gap-4 border-b p-5 sm:flex-row sm:items-center sm:p-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Filter Trade:</span>
                        <button
                            @click="activeTrade = 'all'"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer"
                            :class="activeTrade === 'all' ? 'bg-primary text-primary-foreground shadow-xs' : 'bg-accent/60 text-muted-foreground hover:bg-accent'"
                        >
                            All Trades (4)
                        </button>
                        <button
                            @click="activeTrade = 'plumbing'"
                            class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer"
                            :class="activeTrade === 'plumbing' ? 'bg-blue-600 text-white shadow-xs' : 'bg-accent/60 text-muted-foreground hover:bg-accent'"
                        >
                            <Droplets class="h-3 w-3" /> Plumbing
                        </button>
                        <button
                            @click="activeTrade = 'hvac'"
                            class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer"
                            :class="activeTrade === 'hvac' ? 'bg-sky-600 text-white shadow-xs' : 'bg-accent/60 text-muted-foreground hover:bg-accent'"
                        >
                            <Wind class="h-3 w-3" /> HVAC
                        </button>
                        <button
                            @click="activeTrade = 'electrical'"
                            class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer"
                            :class="activeTrade === 'electrical' ? 'bg-amber-500 text-slate-950 shadow-xs' : 'bg-accent/60 text-muted-foreground hover:bg-accent'"
                        >
                            <Zap class="h-3 w-3" /> Electrical
                        </button>
                        <button
                            @click="activeTrade = 'locksmith'"
                            class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer"
                            :class="activeTrade === 'locksmith' ? 'bg-teal-600 text-white shadow-xs' : 'bg-accent/60 text-muted-foreground hover:bg-accent'"
                        >
                            <Lock class="h-3 w-3" /> Locksmith
                        </button>
                    </div>

                    <!-- Dispatch Action Button -->
                    <button
                        @click="simulateAutoDispatch"
                        :disabled="isSimulatingNewJob"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-md transition-all hover:bg-emerald-700 active:scale-95 disabled:opacity-50 cursor-pointer"
                    >
                        <Sparkles class="h-4 w-4" />
                        <span>{{ isSimulatingNewJob ? 'Routing Call to Board...' : 'Simulate AI Dispatch Assignment' }}</span>
                    </button>
                </div>

                <!-- Simulation Notification Banner -->
                <div v-if="jobAssignedMessage" class="flex items-center gap-3 border-b bg-emerald-500/10 px-6 py-3 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                    <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-500" />
                    <span>{{ jobAssignedMessage }}</span>
                </div>

                <!-- Board Timeline & Technicians Grid -->
                <div class="divide-y divide-border">
                    <div
                        v-for="tech in filteredTechnicians"
                        :key="tech.id"
                        class="flex flex-col gap-4 p-5 transition-colors hover:bg-accent/20 sm:p-6 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <!-- Tech Profile Info -->
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-sm font-bold text-white shadow-md">
                                {{ tech.avatar }}
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2.5">
                                    <h3 class="text-base font-bold text-foreground">{{ tech.name }}</h3>
                                    <Badge :class="tech.statusColor" class="px-2 py-0.5 text-[10px] font-extrabold uppercase">
                                        {{ tech.status }}
                                    </Badge>
                                </div>
                                <p class="text-xs text-muted-foreground font-medium">{{ tech.role }} • Shift: {{ tech.shift }}</p>
                                <div class="flex flex-wrap gap-1.5 pt-1">
                                    <span v-for="skill in tech.skills" :key="skill" class="rounded bg-accent/60 px-2 py-0.5 text-[10px] font-medium text-muted-foreground">
                                        {{ skill }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Active Job & Travel Buffer Card -->
                        <div class="flex flex-col space-y-2 rounded-xl border bg-background/80 p-3.5 text-xs lg:w-[420px]">
                            <div class="flex items-center justify-between font-bold text-foreground">
                                <span class="flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400">
                                    <Navigation class="h-3.5 w-3.5" /> {{ tech.activeJob }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-muted-foreground">
                                <span class="flex items-center gap-1"><MapPin class="h-3 w-3" /> {{ tech.location }}</span>
                                <span class="flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                                    <Clock class="h-3 w-3" /> Next Free: {{ tech.nextAvailable }}
                                </span>
                            </div>
                            <!-- Travel Buffer Visual Indicator -->
                            <div class="mt-1 flex items-center gap-2 rounded bg-indigo-500/10 px-2.5 py-1 text-[11px] font-bold text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-300">
                                <Shield class="h-3.5 w-3.5" />
                                <span>{{ tech.bufferProtected }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
