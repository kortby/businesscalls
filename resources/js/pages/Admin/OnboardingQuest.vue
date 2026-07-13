<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Sparkles,
    Lock,
    Unlock,
    CheckCircle2,
    ArrowRight,
    MapPin,
    AlertCircle,
    PartyPopper,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

interface Tenant {
    id: number;
    name: string;
    slug: string;
    settings: Record<string, any>;
}

interface Milestones {
    node1: boolean;
    node2: boolean;
    node3: boolean;
    node4: boolean;
    node5: boolean;
}

const props = defineProps<{
    tenant: Tenant;
    milestones: Milestones;
    currentMilestone: number; // 1 to 5
}>();

// Mascot state: 0 = Idle, 1 = Searching, 2 = Victory, 3 = Error
const mascotState = ref<number>(0);
const alertMessage = ref<string | null>(null);

// Nodes configuration
const nodes = [
    {
        id: 1,
        title: 'Create Tenant',
        description: 'Establish tenant slug and business profile settings.',
        targetUrl: '/settings/profile', // Profile edit settings
        table: 'tenant',
        fields: 'slug, name',
    },
    {
        id: 2,
        title: 'Buy Phone Line',
        description: 'Purchase or map a dedicated telephony phone number line.',
        targetUrl: '/admin/call-monitor', // Admin dashboard call monitor to purchase
        table: 'tenants',
        fields: 'settings -> phone_number',
    },
    {
        id: 3,
        title: 'Map Shift Availabilities',
        description:
            'Establish dispatcher calendar schedules and active shifts.',
        targetUrl: '/availabilities',
        table: 'availabilities',
        fields: 'is_active',
    },
    {
        id: 4,
        title: 'Upload Knowledge Base',
        description: 'Train the agent with your PDF docs and business manuals.',
        targetUrl: '/admin/integrations',
        table: 'knowledge_bases',
        fields: 'id',
    },
    {
        id: 5,
        title: 'Test Sandbox Call',
        description: 'Execute your first sandbox WebRTC client call session.',
        targetUrl: '/admin/call-monitor',
        table: 'call_logs',
        fields: 'status == ended',
    },
];

// Check status of a node
const isCompleted = (id: number) => {
    if (id === 1) {
        return props.milestones.node1;
    }

    if (id === 2) {
        return props.milestones.node2;
    }

    if (id === 3) {
        return props.milestones.node3;
    }

    if (id === 4) {
        return props.milestones.node4;
    }

    if (id === 5) {
        return props.milestones.node5;
    }

    return false;
};

// Check if a node is locked
const isLocked = (id: number) => {
    if (id === 1) {
        return false;
    }

    // Node is locked if the previous one is not completed
    return !isCompleted(id - 1);
};

// Determine coordinates for snake path (x: Left %, y: Top %)
const getNodePosition = (id: number) => {
    // Alternating snake-like vertical coordinates
    const positions = [
        { x: 30, y: 82 },
        { x: 65, y: 64 },
        { x: 35, y: 46 },
        { x: 70, y: 28 },
        { x: 45, y: 10 },
    ];

    return positions[id - 1];
};

// Sync mascot coordinates to current active step node
const activeNodePosition = computed(() => {
    // Bound currentMilestone safely between 1 and 5
    const index = Math.max(1, Math.min(5, props.currentMilestone));

    return getNodePosition(index);
});

// Handle node interaction clicks
const handleNodeClick = (node: (typeof nodes)[0]) => {
    alertMessage.value = null;

    if (isCompleted(node.id)) {
        mascotState.value = 2; // Victory celebratory state
        setTimeout(() => (mascotState.value = 0), 3000);

        return;
    }

    if (isLocked(node.id)) {
        mascotState.value = 3; // Sad/Error state
        alertMessage.value = `Milestone Locked: Please complete the previous steps first!`;
        setTimeout(() => (mascotState.value = 0), 4000);

        return;
    }

    // Active node clicked
    mascotState.value = 1; // Searching/Scanning state
    setTimeout(() => (mascotState.value = 0), 2000);
};
</script>

<template>
    <Head title="Onboarding Quest Map" />

    <div class="mx-auto max-w-5xl space-y-6 p-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 rounded-2xl border-b-4 border-slate-700 bg-slate-900 p-6 text-white shadow-sm md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1
                    class="flex items-center gap-2 text-3xl font-black tracking-wider uppercase"
                >
                    <Sparkles class="h-8 w-8 animate-pulse text-amber-400" />
                    Onboarding Quest Map
                </h1>
                <p class="mt-1 text-sm font-bold text-slate-300">
                    Complete milestones to customize and launch your
                    multi-tenant dispatcher agency!
                </p>
            </div>
            <div
                class="flex items-center gap-2 rounded-xl border-2 border-slate-700 bg-slate-800 px-4 py-2 text-xs font-bold"
            >
                <span
                    class="h-2 w-2 animate-ping rounded-full bg-emerald-500"
                ></span>
                Active Tenant: {{ tenant.name }}
            </div>
        </div>

        <!-- Alert messages -->
        <div
            v-if="alertMessage"
            class="flex animate-bounce items-center gap-2 rounded-xl border-4 border-rose-500 bg-rose-50 p-4 text-xs font-black tracking-wider text-rose-700 uppercase dark:bg-rose-950/20 dark:text-rose-400"
        >
            <AlertCircle class="h-5 w-5 flex-shrink-0" />
            {{ alertMessage }}
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left 2 Cols: Dynamic Interactive Map (Duolingo Path) -->
            <div class="flex flex-col gap-4 lg:col-span-2">
                <div
                    class="relative aspect-[3/4] min-h-[550px] w-full overflow-hidden rounded-2xl border-4 border-slate-700 bg-slate-800/40 p-4 shadow-inner"
                >
                    <!-- Background Grid Pattern -->
                    <div
                        class="absolute inset-0 bg-[radial-gradient(#475569_1px,transparent_1px)] [background-size:16px_16px] opacity-25"
                    ></div>

                    <!-- Journey Path Drawing (SVG Connector Line) -->
                    <svg
                        class="pointer-events-none absolute inset-0 h-full w-full"
                    >
                        <path
                            d="M 30 82 C 60 78, 65 72, 65 64 C 65 56, 35 56, 35 46 C 35 36, 70 38, 70 28 C 70 18, 45 18, 45 10"
                            fill="none"
                            stroke="#475569"
                            stroke-width="12"
                            stroke-linecap="round"
                            class="opacity-40"
                        />
                        <path
                            d="M 30 82 C 60 78, 65 72, 65 64 C 65 56, 35 56, 35 46 C 35 36, 70 38, 70 28 C 70 18, 45 18, 45 10"
                            fill="none"
                            stroke="#58cc02"
                            stroke-width="6"
                            stroke-linecap="round"
                            class="transition-all duration-500"
                        />
                    </svg>

                    <!-- Mascot Sync Overlay (Positioned dynamically on active step) -->
                    <div
                        class="pointer-events-none absolute z-20 h-24 w-24 transition-all duration-700 ease-out"
                        :style="{
                            left: `calc(${activeNodePosition.x}% - 48px)`,
                            top: `calc(${activeNodePosition.y}% - 90px)`,
                        }"
                    >
                        <div
                            class="relative h-full w-full animate-bounce drop-shadow-[0_8px_16px_rgba(0,0,0,0.3)]"
                            style="animation-duration: 2.5s"
                        >
                            <DispatcherMascot
                                :state="mascotState"
                                :skin="'standard'"
                            />
                        </div>
                    </div>

                    <!-- Render Interactive Path Circular Nodes -->
                    <div
                        v-for="node in nodes"
                        :key="node.id"
                        class="absolute z-10 transition-all duration-300"
                        :style="{
                            left: `${getNodePosition(node.id).x}%`,
                            top: `${getNodePosition(node.id).y}%`,
                            transform: 'translate(-50%, -50%)',
                        }"
                    >
                        <button
                            @click="handleNodeClick(node)"
                            class="group relative flex h-16 w-16 items-center justify-center rounded-full border-4 font-black shadow-md transition-all hover:scale-110 active:scale-95"
                            :class="[
                                isCompleted(node.id)
                                    ? 'border-b-[8px] border-emerald-600 bg-emerald-500 text-white'
                                    : isLocked(node.id)
                                      ? 'cursor-not-allowed border-b-[8px] border-slate-600 bg-slate-500 text-slate-300'
                                      : 'animate-pulse border-b-[8px] border-amber-500 bg-amber-400 text-white',
                            ]"
                        >
                            <!-- Inner indicator lock/unlock -->
                            <CheckCircle2
                                v-if="isCompleted(node.id)"
                                class="h-7 w-7"
                            />
                            <Lock
                                v-else-if="isLocked(node.id)"
                                class="h-6 w-6"
                            />
                            <span v-else class="text-lg font-black">{{
                                node.id
                            }}</span>

                            <!-- Floating Label Tooltip (Duolingo Popover) -->
                            <div
                                class="absolute bottom-full z-30 mb-3 hidden w-56 flex-col items-center text-center group-hover:flex"
                            >
                                <div
                                    class="rounded-xl border-2 border-slate-700 bg-slate-900 p-3 text-white shadow-lg"
                                >
                                    <div
                                        class="flex items-center justify-center gap-1 text-xs font-black tracking-wider uppercase"
                                    >
                                        <span
                                            v-if="isCompleted(node.id)"
                                            class="text-emerald-400"
                                            >Complete ✓</span
                                        >
                                        <span
                                            v-else-if="isLocked(node.id)"
                                            class="text-slate-400"
                                            >Locked 🔒</span
                                        >
                                        <span
                                            v-else
                                            class="animate-pulse text-amber-400"
                                            >In Progress 🚀</span
                                        >
                                    </div>
                                    <h4 class="mt-1 text-sm font-black">
                                        {{ node.title }}
                                    </h4>
                                    <p
                                        class="mt-1 text-[10px] leading-normal font-medium text-slate-300"
                                    >
                                        {{ node.description }}
                                    </p>
                                </div>
                                <!-- arrow pointer -->
                                <div
                                    class="-mt-1.5 h-3 w-3 rotate-45 border-r-2 border-b-2 border-slate-700 bg-slate-900"
                                ></div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Detail List & Actions Card -->
            <div class="flex flex-col gap-6">
                <!-- Mascot Action Board Card -->
                <div
                    class="flex flex-col gap-4 rounded-2xl border-4 border-slate-700 bg-card p-6 shadow-sm"
                >
                    <h3
                        class="flex items-center gap-2 border-b-2 pb-3 text-lg font-black tracking-tight text-foreground"
                    >
                        <PartyPopper class="h-5 w-5 text-amber-500" />
                        Milestone Dashboard
                    </h3>

                    <!-- Status overview -->
                    <div class="space-y-4">
                        <div
                            v-for="node in nodes"
                            :key="node.id"
                            class="flex items-start gap-3 rounded-xl border-2 p-3 transition-all"
                            :class="[
                                isCompleted(node.id)
                                    ? 'border-emerald-100 bg-emerald-50/20 dark:border-emerald-950/25 dark:bg-emerald-950/10'
                                    : isLocked(node.id)
                                      ? 'border-slate-100 bg-slate-50/50 opacity-70 dark:border-slate-800/40 dark:bg-slate-900/10'
                                      : 'border-amber-200 bg-amber-50/20 ring-2 ring-amber-500/10 dark:border-amber-950/25 dark:bg-amber-950/10',
                            ]"
                        >
                            <div class="mt-0.5">
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-full border text-xs font-black"
                                    :class="[
                                        isCompleted(node.id)
                                            ? 'border-emerald-600 bg-emerald-500 text-white'
                                            : isLocked(node.id)
                                              ? 'border-slate-400 bg-slate-300 text-slate-600 dark:border-slate-600 dark:bg-slate-700'
                                              : 'animate-pulse border-amber-500 bg-amber-400 text-white',
                                    ]"
                                >
                                    {{ node.id }}
                                </span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <h4
                                    class="text-xs font-black tracking-tight text-foreground uppercase"
                                >
                                    {{ node.title }}
                                </h4>
                                <p
                                    class="mt-0.5 text-[10px] font-medium text-muted-foreground"
                                >
                                    {{ node.description }}
                                </p>

                                <!-- Table mapping details -->
                                <div
                                    class="mt-2 flex flex-wrap gap-x-2 gap-y-0.5 font-mono text-[9px] text-slate-400"
                                >
                                    <span
                                        >Table:
                                        <strong
                                            class="text-slate-500 dark:text-slate-300"
                                            >{{ node.table }}</strong
                                        ></span
                                    >
                                    <span
                                        >Field:
                                        <strong
                                            class="text-slate-500 dark:text-slate-300"
                                            >{{ node.fields }}</strong
                                        ></span
                                    >
                                </div>

                                <!-- Action Go Link -->
                                <div
                                    v-if="
                                        !isCompleted(node.id) &&
                                        !isLocked(node.id)
                                    "
                                    class="mt-3"
                                >
                                    <Link
                                        :href="node.targetUrl"
                                        class="inline-flex items-center gap-1 text-[10px] font-black text-amber-600 uppercase transition-colors hover:text-amber-500 dark:text-amber-400"
                                    >
                                        Proceed Setup
                                        <ArrowRight class="h-3 w-3" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
