<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Phone, Calendar, ClipboardList, Clock, MessageSquare, AlertCircle } from '@lucide/vue';

defineOptions({ layout: AppLayout });

defineProps<{
    customers: Array<{
        phone: string;
        name: string;
        total_bookings: number;
        total_calls: number;
        latest_call_date: string;
        latest_call_summary: string;
        latest_call_status: string;
    }>;
}>();
</script>

<template>
    <Head title="Customers Directory" />

    <div class="px-6 py-6 space-y-6">
        <Heading
            title="Customers & Callers"
            description="Overview of customer profiles, booking volumes, and voice AI transcript history"
        />

        <div class="grid grid-cols-1 gap-6">
            <!-- Customer List Container -->
            <div class="border-3 border-b-6 border-slate-300 dark:border-slate-800 rounded-2xl bg-card p-6">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b-2 border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase tracking-wider text-muted-foreground pb-3">
                                <th class="pb-3 pr-4">Customer Details</th>
                                <th class="pb-3 px-4">Contact Phone</th>
                                <th class="pb-3 px-4 text-center">Calls Logged</th>
                                <th class="pb-3 px-4 text-center">Bookings Aligned</th>
                                <th class="pb-3 px-4">Latest Interaction</th>
                                <th class="pb-3 pl-4">Telemetry Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            <tr 
                                v-for="customer in customers" 
                                :key="customer.phone"
                                class="hover:bg-muted/10 transition-colors"
                            >
                                <!-- Name -->
                                <td class="py-4 pr-4 font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                                    <div class="h-8 w-8 rounded-lg bg-indigo-500/10 border-2 border-indigo-500/30 flex items-center justify-center text-xs font-black text-indigo-600">
                                        {{ customer.name[0] }}
                                    </div>
                                    {{ customer.name }}
                                </td>

                                <!-- Phone -->
                                <td class="py-4 px-4 font-mono font-bold text-xs text-slate-600 dark:text-slate-400">
                                    {{ customer.phone }}
                                </td>

                                <!-- Calls Count -->
                                <td class="py-4 px-4 text-center">
                                    <Badge variant="outline" class="font-bold border-indigo-500/30 text-indigo-600 bg-indigo-500/5 px-2.5">
                                        {{ customer.total_calls }} calls
                                    </Badge>
                                </td>

                                <!-- Bookings Count -->
                                <td class="py-4 px-4 text-center">
                                    <Badge variant="outline" class="font-bold border-emerald-500/30 text-emerald-600 bg-emerald-500/5 px-2.5">
                                        {{ customer.total_bookings }} bookings
                                    </Badge>
                                </td>

                                <!-- Latest Summary -->
                                <td class="py-4 px-4 max-w-xs md:max-w-sm truncate text-xs font-medium text-slate-500 dark:text-slate-400" :title="customer.latest_call_summary">
                                    {{ customer.latest_call_summary }}
                                </td>

                                <!-- Status & Time -->
                                <td class="py-4 pl-4 space-y-1">
                                    <div class="flex items-center gap-1.5">
                                        <Badge 
                                            :class="[
                                                customer.latest_call_status === 'ended' 
                                                    ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' 
                                                    : customer.latest_call_status === 'ongoing' 
                                                        ? 'bg-blue-500/10 text-blue-500 border border-blue-500/20 animate-pulse' 
                                                        : 'bg-slate-500/10 text-slate-500 border border-slate-500/20'
                                            ]"
                                            class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5"
                                        >
                                            {{ customer.latest_call_status || 'N/A' }}
                                        </Badge>
                                    </div>
                                    <div class="text-[9px] font-bold text-muted-foreground flex items-center gap-1">
                                        <Clock class="h-3 w-3" /> {{ customer.latest_call_date }}
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="customers.length === 0">
                                <td colspan="6" class="py-8 text-center text-muted-foreground font-semibold italic">
                                    No customer interactions or bookings recorded yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
