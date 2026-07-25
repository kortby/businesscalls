<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Phone,
    Mail,
    Wrench,
    CalendarDays,
    Clock,
    User,
    CheckCircle2,
    Plus,
    FileText,
} from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as customersIndex } from '@/routes/customers';

defineOptions({ layout: AppLayout });

interface Employee {
    id: number;
    first_name: string;
    last_name: string;
}

interface ServiceJob {
    id: number;
    title: string;
    description: string | null;
    status: string;
    steps: string[] | null;
    created_at: string;
    employee?: Employee | null;
}

interface Booking {
    id: number;
    service_type: string;
    requested_time: string | null;
    status: string;
    created_at: string;
    employee?: Employee | null;
}

interface CallLog {
    id: number;
    summary: string | null;
    status: string;
    duration: number | null;
    created_at: string;
}

interface Customer {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    notes: string | null;
    created_at: string;
}

const props = defineProps<{
    customer: Customer;
    jobs: ServiceJob[];
    bookings: Booking[];
    call_logs: CallLog[];
}>();

const activeTab = ref<'jobs' | 'bookings' | 'calls' | 'notes'>('jobs');
</script>

<template>
    <Head :title="`Customer: ${customer.name}`" />

    <div class="space-y-6 px-6 py-6">
        <!-- Top Back Navigation & Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <Link
                    :href="customersIndex.url()"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border-2 border-slate-200 bg-white text-slate-600 transition-all hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
                >
                    <ArrowLeft class="h-4 w-4" />
                </Link>
                <div>
                    <Heading
                        :title="customer.name"
                        :description="`Customer profile #${customer.id} history & service job logs`"
                    />
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Badge variant="outline" class="border-indigo-500/30 bg-indigo-500/10 px-3 py-1 font-bold text-indigo-600">
                    <Wrench class="mr-1 h-3.5 w-3.5 inline" />
                    {{ jobs.length }} Jobs Total
                </Badge>
            </div>
        </div>

        <!-- Customer Summary Header Card -->
        <div class="rounded-3xl border-3 border-b-6 border-slate-300 bg-card p-6 shadow-md dark:border-slate-800">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl border-3 border-indigo-500/30 bg-indigo-500/10 text-2xl font-black text-indigo-600 dark:text-indigo-400">
                        {{ customer.name[0] }}
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ customer.name }}</h2>
                        <div class="mt-1 flex flex-wrap items-center gap-4 text-xs font-bold text-slate-500 dark:text-slate-400">
                            <span class="flex items-center gap-1 font-mono">
                                <Phone class="h-3.5 w-3.5 text-slate-400" />
                                {{ customer.phone }}
                            </span>
                            <span v-if="customer.email" class="flex items-center gap-1">
                                <Mail class="h-3.5 w-3.5 text-slate-400" />
                                {{ customer.email }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3 text-center border-t pt-4 md:border-t-0 md:pt-0 border-slate-200 dark:border-slate-800">
                    <div class="rounded-2xl border-2 border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900">
                        <div class="text-lg font-black text-indigo-600 dark:text-indigo-400">{{ jobs.length }}</div>
                        <div class="text-[10px] font-bold uppercase text-slate-400">Jobs Done</div>
                    </div>
                    <div class="rounded-2xl border-2 border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900">
                        <div class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ bookings.length }}</div>
                        <div class="text-[10px] font-bold uppercase text-slate-400">Bookings</div>
                    </div>
                    <div class="rounded-2xl border-2 border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900">
                        <div class="text-lg font-black text-slate-700 dark:text-slate-300">{{ call_logs.length }}</div>
                        <div class="text-[10px] font-bold uppercase text-slate-400">Calls</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs & Content -->
        <div class="rounded-3xl border-3 border-b-6 border-slate-300 bg-card p-6 dark:border-slate-800">
            <div class="flex items-center gap-2 border-b border-slate-200 pb-3 dark:border-slate-800">
                <button
                    @click="activeTab = 'jobs'"
                    :class="[
                        activeTab === 'jobs'
                            ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300'
                            : 'border-transparent text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800',
                    ]"
                    class="flex cursor-pointer items-center gap-2 rounded-xl border-2 px-4 py-2 text-xs font-bold transition-all"
                >
                    <Wrench class="h-4 w-4" />
                    <span>Jobs Done ({{ jobs.length }})</span>
                </button>
                <button
                    @click="activeTab = 'bookings'"
                    :class="[
                        activeTab === 'bookings'
                            ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300'
                            : 'border-transparent text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800',
                    ]"
                    class="flex cursor-pointer items-center gap-2 rounded-xl border-2 px-4 py-2 text-xs font-bold transition-all"
                >
                    <CalendarDays class="h-4 w-4" />
                    <span>AI Bookings ({{ bookings.length }})</span>
                </button>
                <button
                    @click="activeTab = 'calls'"
                    :class="[
                        activeTab === 'calls'
                            ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300'
                            : 'border-transparent text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800',
                    ]"
                    class="flex cursor-pointer items-center gap-2 rounded-xl border-2 px-4 py-2 text-xs font-bold transition-all"
                >
                    <Phone class="h-4 w-4" />
                    <span>Call Logs ({{ call_logs.length }})</span>
                </button>
            </div>

            <div class="pt-6">
                <!-- Tab Content: Service Jobs -->
                <div v-if="activeTab === 'jobs'" class="space-y-4">
                    <div v-if="jobs.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div
                            v-for="job in jobs"
                            :key="job.id"
                            class="flex flex-col justify-between rounded-2xl border-2 border-slate-200 bg-slate-50/50 p-5 transition-all hover:border-indigo-500/40 dark:border-slate-800 dark:bg-slate-900/50"
                        >
                            <div>
                                <div class="flex items-start justify-between gap-3">
                                    <h4 class="text-sm font-black text-slate-900 dark:text-white">{{ job.title }}</h4>
                                    <Badge
                                        :class="[
                                            job.status === 'completed'
                                                ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-600'
                                                : job.status === 'in_progress'
                                                  ? 'border-blue-500/30 bg-blue-500/10 text-blue-600'
                                                  : job.status === 'cancelled'
                                                    ? 'border-rose-500/30 bg-rose-500/10 text-rose-600'
                                                    : 'border-amber-500/30 bg-amber-500/10 text-amber-600',
                                        ]"
                                        class="px-2.5 py-0.5 text-[9px] font-black uppercase border"
                                    >
                                        {{ job.status.replace('_', ' ') }}
                                    </Badge>
                                </div>
                                <p v-if="job.description" class="mt-2 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">{{ job.description }}</p>
                            </div>

                            <div class="mt-4 border-t pt-3 border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs font-semibold text-slate-500">
                                <span class="flex items-center gap-1">
                                    <User class="h-3.5 w-3.5 text-indigo-500" />
                                    {{ job.employee ? (job.employee.first_name + ' ' + job.employee.last_name) : 'Unassigned' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center dark:border-slate-800">
                        <Wrench class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-700" />
                        <p class="mt-3 text-sm font-semibold text-slate-500 italic">No formal service jobs created for {{ customer.name }} yet.</p>
                    </div>
                </div>

                <!-- Tab Content: Bookings -->
                <div v-if="activeTab === 'bookings'" class="space-y-4">
                    <div v-if="bookings.length > 0" class="space-y-3">
                        <div
                            v-for="b in bookings"
                            :key="b.id"
                            class="flex items-center justify-between rounded-2xl border-2 border-slate-200 bg-slate-50/50 p-4 text-xs dark:border-slate-800 dark:bg-slate-900/50"
                        >
                            <div class="space-y-1">
                                <div class="font-bold text-slate-900 uppercase dark:text-white flex items-center gap-2">
                                    <span>{{ b.service_type }}</span>
                                    <Badge variant="outline" class="text-[9px] font-bold uppercase border-indigo-500/20 text-indigo-600">
                                        {{ b.status }}
                                    </Badge>
                                </div>
                                <div class="text-[11px] text-muted-foreground">
                                    Requested: {{ b.requested_time || 'N/A' }} • Assigned Tech: {{ b.employee ? (b.employee.first_name + ' ' + b.employee.last_name) : 'Auto-Assigned' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center dark:border-slate-800">
                        <CalendarDays class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-700" />
                        <p class="mt-3 text-sm font-semibold text-slate-500 italic">No AI receptionist bookings found for {{ customer.name }}.</p>
                    </div>
                </div>

                <!-- Tab Content: Call Logs -->
                <div v-if="activeTab === 'calls'" class="space-y-4">
                    <div v-if="call_logs.length > 0" class="space-y-3">
                        <div
                            v-for="c in call_logs"
                            :key="c.id"
                            class="rounded-2xl border-2 border-slate-200 bg-slate-50/50 p-4 text-xs dark:border-slate-800 dark:bg-slate-900/50"
                        >
                            <div class="flex items-center justify-between font-bold text-slate-800 dark:text-slate-200">
                                <span class="flex items-center gap-1.5">
                                    <Phone class="h-3.5 w-3.5 text-indigo-500" />
                                    Voice Interaction Log #{{ c.id }}
                                </span>
                            </div>
                            <p class="mt-2 text-slate-600 dark:text-slate-400 italic">"{{ c.summary || 'No call summary recorded.' }}"</p>
                        </div>
                    </div>
                    <div v-else class="rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center dark:border-slate-800">
                        <Phone class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-700" />
                        <p class="mt-3 text-sm font-semibold text-slate-500 italic">No phone calls recorded for {{ customer.name }}.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
