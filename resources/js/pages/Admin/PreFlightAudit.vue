<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Activity,
    ShieldAlert,
    ShieldCheck,
    RefreshCw,
    Key,
    Database,
    Zap,
    CreditCard,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

interface AuditItem {
    name: string;
    status: boolean;
    details: string;
}

const props = defineProps<{
    audits: AuditItem[];
    trustScore: number;
    allPassed: boolean;
}>();

const isAuditing = ref(false);

const mascotState = computed(() => {
    return props.allPassed ? 2 : 3;
});

const triggerReaudit = () => {
    isAuditing.value = true;
    router.reload({
        onFinish: () => {
            isAuditing.value = false;
        }
    });
};
</script>

<template>
    <Head title="Pre-Flight Launch Audit" />

    <div class="min-h-screen bg-[#F0FDF4] p-6 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        <!-- Header -->
        <header
            class="mb-8 flex flex-col gap-4 rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)] sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-4">
                <div class="rounded-2xl border-4 border-slate-900 bg-indigo-500 p-3 text-white dark:border-slate-100">
                    <Activity class="h-8 w-8 stroke-[3]" />
                </div>
                <div>
                    <h1 class="text-2xl font-black tracking-tight uppercase sm:text-3xl">Pre-Flight Audit</h1>
                    <p class="text-xs font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400">
                        Live-Launch Gatekeeper & Compliance Check
                    </p>
                </div>
            </div>
            <button
                :disabled="isAuditing"
                class="flex items-center justify-center gap-2 rounded-2xl border-4 border-slate-900 bg-amber-400 px-6 py-3 text-sm font-black uppercase text-slate-900 shadow-[2px_2px_0px_0px_rgba(15,23,42,1)] transition-transform hover:-translate-y-0.5 active:translate-y-0 dark:border-slate-100 dark:shadow-[2px_2px_0px_0px_rgba(255,255,255,1)]"
                @click="triggerReaudit"
            >
                <RefreshCw class="h-5 w-5 stroke-[3]" :class="{ 'animate-spin': isAuditing }" />
                {{ isAuditing ? 'Auditing...' : 'Run Audit Check' }}
            </button>
        </header>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Left Column: Trust Score Gauge & Rive Mascot -->
            <div class="flex flex-col gap-6 lg:col-span-1">
                <!-- Trust Score Card -->
                <div
                    class="rounded-3xl border-4 border-slate-900 bg-white p-6 text-center shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h3 class="mb-4 text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400">
                        SaaS Platform Trust Score (&Theta;<sub>trust</sub>)
                    </h3>
                    
                    <!-- Big Visual Circular Gauge -->
                    <div class="relative mx-auto my-6 flex h-48 w-48 items-center justify-center rounded-full border-8 border-slate-900 bg-slate-50 dark:border-slate-100 dark:bg-slate-800">
                        <div class="text-center">
                            <span class="text-5xl font-black">{{ trustScore }}%</span>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Compliance Rate</p>
                        </div>
                    </div>

                    <div
                        v-if="allPassed"
                        class="rounded-xl border-4 border-emerald-900 bg-emerald-100 p-3 text-sm font-bold text-emerald-800 dark:border-emerald-400 dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        Ready for Production Launch! 🚀
                    </div>
                    <div
                        v-else
                        class="rounded-xl border-4 border-rose-900 bg-rose-100 p-3 text-sm font-bold text-rose-800 dark:border-rose-400 dark:bg-rose-950 dark:text-rose-300"
                    >
                        Audit Exceptions Detected! ⚠️
                    </div>
                </div>

                <!-- Mascot Card -->
                <div
                    class="rounded-3xl border-4 border-slate-900 bg-white p-6 text-center shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h3 class="mb-4 text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400">
                        System Mascot Telemetry
                    </h3>
                    <div class="relative mx-auto flex h-48 w-48 items-center justify-center rounded-2xl border-4 border-slate-900 bg-slate-50 dark:border-slate-100 dark:bg-slate-800">
                        <DispatcherMascot :state="mascotState" />
                    </div>
                </div>
            </div>

            <!-- Right Column: Audit Checklist details -->
            <div class="flex flex-col gap-6 lg:col-span-2">
                <!-- Checklist Cards -->
                <div
                    class="rounded-3xl border-4 border-slate-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:bg-slate-900 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
                >
                    <h3 class="mb-6 text-xs font-black tracking-widest text-slate-500 uppercase dark:text-slate-400">
                        Audit Gatekeeper Status
                    </h3>

                    <div class="space-y-4">
                        <div
                            v-for="(audit, index) in audits"
                            :key="index"
                            class="flex flex-col gap-4 rounded-2xl border-4 border-slate-900 bg-slate-50 p-5 dark:border-slate-100 dark:bg-slate-800 md:flex-row md:items-center md:justify-between"
                        >
                            <div class="flex items-start gap-4">
                                <div
                                    class="rounded-xl border-2 border-slate-900 p-2.5 dark:border-slate-100"
                                    :class="[
                                        audit.status
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : 'bg-rose-100 text-rose-800'
                                    ]"
                                >
                                    <ShieldCheck v-if="audit.status" class="h-6 w-6" />
                                    <ShieldAlert v-else class="h-6 w-6" />
                                </div>
                                <div>
                                    <h4 class="text-base font-black tracking-tight">{{ audit.name }}</h4>
                                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-0.5">
                                        {{ audit.details }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <span
                                    class="inline-block rounded-xl border-2 border-slate-900 px-3 py-1 text-xs font-black uppercase text-white dark:border-slate-100"
                                    :class="[
                                        audit.status ? 'bg-emerald-500' : 'bg-rose-500'
                                    ]"
                                >
                                    {{ audit.status ? 'PASS' : 'FAIL' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
