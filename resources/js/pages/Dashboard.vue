<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import { useEcho } from '@laravel/echo-vue';
import { 
    Phone, 
    CheckCircle, 
    XCircle, 
    User as UserIcon, 
    Calendar, 
    Clock, 
    ShieldAlert, 
    Activity,
    Settings,
    Award
} from '@lucide/vue';

// Define Props passed from web.php route
const props = defineProps<{
    tenant: {
        id: number;
        slug: string;
        name: string;
        plan: string;
        settings: Record<string, any>;
        secret_key: string;
    } | null;
    employees: Array<{
        id: number;
        first_name: string;
        last_name: string;
        phone: string;
        skills: string[];
        availabilities: Array<{
            day_of_week: number;
            start_time: string;
            end_time: string;
            is_active: boolean;
        }>;
    }>;
    bookings: Array<{
        id: number;
        customer_phone: string;
        job_details: string;
        status: string;
        scheduled_start: string;
        employee: {
            first_name: string;
            last_name: string;
        };
    }>;
}>();

// Page info for auth checks
const page = usePage();
const currentUser = page.props.auth?.user;

// Mascot State: 0=Idle, 1=Searching, 2=Victory, 3=Error
const mascotState = ref<number>(0);

// Local lists for real-time websocket appending
const liveBookings = ref([...props.bookings]);
const liveFeed = ref<Array<{
    id: string;
    timestamp: string;
    type: 'searching' | 'success' | 'error';
    message: string;
}>>([]);

// Stats counters (reactive)
const stats = ref({
    calls: props.bookings.length + 3, // initial dummy call simulation offset
    success: props.bookings.filter(b => b.status === 'booked').length,
    conflicts: 1, // initial dummy configuration validation conflict
});

// Sound effects or delay mascot state resets
const transitionMascot = (newState: number) => {
    mascotState.value = newState;
    
    // Reset to idle (0) after 6 seconds if victory or error
    if (newState === 2 || newState === 3) {
        setTimeout(() => {
            if (mascotState.value === newState) {
                mascotState.value = 0;
            }
        }, 6000);
    }
};

// Helper for mapping day numbers to names
const getDayName = (dayNum: number): string => {
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return days[dayNum] ?? '';
};

// Listen to WebSocket Channel
if (props.tenant) {
    // Listens to private-tenant.{id} on channel "tenant.{id}"
    useEcho(`tenant.${props.tenant.id}`, 'dispatch.updated', (payload: any) => {
        const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const uniqueId = Math.random().toString(36).substring(2, 9);

        // Append to logs feed
        liveFeed.value.unshift({
            id: uniqueId,
            timestamp,
            type: payload.type,
            message: payload.message
        });

        // Set mascot state trigger
        if (payload.type === 'searching') {
            transitionMascot(1);
            stats.value.calls++;
        } else if (payload.type === 'success') {
            transitionMascot(2);
            stats.value.success++;
            
            // Append booking dynamically
            if (payload.booking) {
                liveBookings.value.unshift(payload.booking);
                // Keep max 10
                if (liveBookings.value.length > 10) {
                    liveBookings.value.pop();
                }
            }
        } else if (payload.type === 'error') {
            transitionMascot(3);
            stats.value.conflicts++;
        }
    });
}

onMounted(() => {
    // Add default log
    liveFeed.value.push({
        id: 'init',
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        type: 'success',
        message: 'AI Voice Dispatch System initialized. Monitoring calls...'
    });
});
</script>

<template>
    <Head title="Duolingo Dispatcher Dashboard" />

    <div class="min-h-screen bg-[#f8fafc] text-slate-900 p-6 font-sans">
        <!-- Dashboard Top Header (Duolingo Style: thick flat lines) -->
        <header class="mb-8 flex flex-col md:flex-row items-center justify-between gap-4 pb-6 border-b-4 border-slate-900">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#58cc02] border-4 border-slate-900 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                    <Award class="h-10 w-10 text-white" />
                </div>
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-wider text-slate-900">
                        {{ tenant?.name ?? 'businesscalls' }}
                    </h1>
                    <p class="text-sm font-bold text-slate-500 uppercase tracking-widest mt-0.5">
                        Active Plan: <span class="text-blue-500">{{ tenant?.plan ?? 'Trial' }}</span>
                    </p>
                </div>
            </div>
            
            <!-- Quick Settings info block -->
            <div class="flex items-center gap-3 bg-white p-3 rounded-2xl border-4 border-slate-900 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]">
                <Settings class="h-6 w-6 text-slate-600 animate-spin-slow" />
                <div class="text-xs font-bold uppercase tracking-wider">
                    <span class="text-slate-400">Webhook Status:</span> 
                    <span class="text-emerald-500 ml-1">Live</span>
                </div>
            </div>
        </header>

        <!-- Stats Grid (Saturated Duolingo Colors + Bold Borders + Hover Lift) -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Calls Received Card -->
            <div class="bg-yellow-100 p-5 rounded-2xl border-4 border-slate-900 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] transition-transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-black uppercase tracking-widest text-slate-600">Total Calls Managed</span>
                    <Phone class="h-6 w-6 text-slate-800" />
                </div>
                <div class="text-4xl font-black text-slate-900">{{ stats.calls }}</div>
                <div class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-1">Real-time counter</div>
            </div>

            <!-- Successful Dispatches Card -->
            <div class="bg-emerald-100 p-5 rounded-2xl border-4 border-slate-900 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] transition-transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-black uppercase tracking-widest text-slate-600">Successful Bookings</span>
                    <CheckCircle class="h-6 w-6 text-slate-800" />
                </div>
                <div class="text-4xl font-black text-slate-900 text-emerald-700">{{ stats.success }}</div>
                <div class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-1">Mascot State: Victory</div>
            </div>

            <!-- Overlaps/Conflicts Card -->
            <div class="bg-rose-100 p-5 rounded-2xl border-4 border-slate-900 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] transition-transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-black uppercase tracking-widest text-slate-600">Conflicts Blocked</span>
                    <XCircle class="h-6 w-6 text-slate-800" />
                </div>
                <div class="text-4xl font-black text-slate-900 text-rose-700">{{ stats.conflicts }}</div>
                <div class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-1">Overlap buffers enforced</div>
            </div>
        </section>

        <!-- Main Layout Split -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- LEFT PANEL: Mascot Rendering and Technician Shifts (2/3 columns) -->
            <div class="lg:col-span-2 flex flex-col gap-8">
                
                <!-- Rive Mascot Container -->
                <div class="bg-white p-6 rounded-3xl border-4 border-slate-900 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6 pb-4 border-b-2 border-slate-100">
                        <div>
                            <h2 class="text-2xl font-black uppercase tracking-wider text-slate-900">AI Dispatcher Mascot</h2>
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Interactive WebGL State machine</p>
                        </div>
                        <div class="flex gap-2">
                            <!-- Dev Animation State Simulator -->
                            <button @click="transitionMascot(0)" class="text-xs font-black px-3 py-1.5 rounded-xl border-3 border-slate-900 bg-slate-100 hover:bg-slate-200 active:translate-y-0.5 active:shadow-[1px_1px_0px_rgba(0,0,0,1)] shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]">Idle</button>
                            <button @click="transitionMascot(1)" class="text-xs font-black px-3 py-1.5 rounded-xl border-3 border-slate-900 bg-yellow-200 hover:bg-yellow-300 active:translate-y-0.5 active:shadow-[1px_1px_0px_rgba(0,0,0,1)] shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]">Scanning</button>
                            <button @click="transitionMascot(2)" class="text-xs font-black px-3 py-1.5 rounded-xl border-3 border-slate-900 bg-emerald-200 hover:bg-emerald-300 active:translate-y-0.5 active:shadow-[1px_1px_0px_rgba(0,0,0,1)] shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]">Victory</button>
                            <button @click="transitionMascot(3)" class="text-xs font-black px-3 py-1.5 rounded-xl border-3 border-slate-900 bg-rose-200 hover:bg-rose-300 active:translate-y-0.5 active:shadow-[1px_1px_0px_rgba(0,0,0,1)] shadow-[3px_3px_0px_0px_rgba(0,0,0,1)]">Conflict</button>
                        </div>
                    </div>
                    
                    <div class="flex flex-col md:flex-row items-center gap-6 justify-center">
                        <div class="w-full md:w-1/2 aspect-square max-w-[280px]">
                            <!-- Rive WebGL canvas component -->
                            <DispatcherMascot :state="mascotState" />
                        </div>
                        <div class="w-full md:w-1/2 flex flex-col justify-center gap-3">
                            <div class="bg-blue-50 border-4 border-blue-900 p-4 rounded-2xl shadow-[4px_4px_0px_0px_rgba(30,41,59,1)]">
                                <h3 class="text-sm font-black uppercase text-blue-950 flex items-center gap-2">
                                    <Activity class="h-4 w-4" /> State Machine Rules
                                </h3>
                                <ul class="text-xs text-blue-900 font-bold list-disc pl-4 mt-2 space-y-1">
                                    <li>State 0: Idle monitoring</li>
                                    <li>State 1: Real-time scan (incoming API tool Call)</li>
                                    <li>State 2: Happy validation (booking confirmation)</li>
                                    <li>State 3: Conflict response (overlaps or out-of-shift)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Technicians Grid -->
                <div class="bg-white p-6 rounded-3xl border-4 border-slate-900 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
                    <h2 class="text-2xl font-black uppercase tracking-wider text-slate-900 mb-2">Technician Profiles</h2>
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-6">Skills matching trade categories & scheduled shifts</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div 
                            v-for="employee in employees" 
                            :key="employee.id"
                            class="bg-[#fafafa] p-4 rounded-2xl border-3 border-slate-900 shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]"
                        >
                            <div class="flex items-center gap-3 mb-3">
                                <div class="bg-blue-100 p-2.5 rounded-xl border-2 border-slate-900">
                                    <UserIcon class="h-5 w-5 text-slate-800" />
                                </div>
                                <div>
                                    <h3 class="font-black text-slate-900">{{ employee.first_name }} {{ employee.last_name }}</h3>
                                    <p class="text-xs font-bold text-slate-500">{{ employee.phone }}</p>
                                </div>
                            </div>

                            <!-- Skills Badges -->
                            <div class="mb-3">
                                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Skills Profile</div>
                                <div class="flex flex-wrap gap-1">
                                    <span 
                                        v-for="skill in employee.skills" 
                                        :key="skill"
                                        class="text-[10px] font-black uppercase bg-blue-100 text-blue-800 border-2 border-blue-900 px-2 py-0.5 rounded-full"
                                    >
                                        {{ skill }}
                                    </span>
                                </div>
                            </div>

                            <!-- Active Shift Hours -->
                            <div>
                                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Weekly Shifts</div>
                                <div class="space-y-1 max-h-[80px] overflow-y-auto pr-1">
                                    <div 
                                        v-for="avail in employee.availabilities" 
                                        :key="avail.day_of_week"
                                        class="flex items-center justify-between text-[11px] font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200"
                                    >
                                        <span>{{ getDayName(avail.day_of_week) }}</span>
                                        <span class="text-slate-600">{{ avail.start_time.substring(0,5) }} - {{ avail.end_time.substring(0,5) }}</span>
                                    </div>
                                    <div v-if="employee.availabilities.length === 0" class="text-xs font-bold text-amber-600">
                                        No active shifts registered.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="employees.length === 0" class="col-span-2 text-center py-6 font-bold text-slate-400">
                            No technician profiles configured. Please seed the database.
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT PANEL: Live Feed Logs and Active Bookings (1/3 column) -->
            <div class="flex flex-col gap-8">
                
                <!-- Live Event Logs Feed -->
                <div class="bg-slate-900 text-[#a3e635] p-6 rounded-3xl border-4 border-slate-900 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] flex flex-col h-[350px]">
                    <div class="flex items-center justify-between mb-4 border-b-2 border-[#a3e635] pb-2">
                        <h2 class="text-lg font-black uppercase tracking-wider text-[#a3e635] flex items-center gap-2">
                            <Activity class="h-5 w-5 animate-pulse" /> Dispatch Terminal
                        </h2>
                        <span class="text-[10px] font-bold uppercase border border-[#a3e635] px-1.5 py-0.5 rounded">WebSocket</span>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto space-y-3 font-mono text-xs pr-1 scrollbar-thin">
                        <div 
                            v-for="log in liveFeed" 
                            :key="log.id"
                            :class="{
                                'text-[#facc15]': log.type === 'searching',
                                'text-[#4ade80]': log.type === 'success',
                                'text-[#f87171]': log.type === 'error'
                            }"
                            class="pb-2 border-b border-slate-800"
                        >
                            <span class="text-slate-500">[{{ log.timestamp }}]</span>
                            <span class="font-black uppercase ml-1">[{{ log.type }}]</span>
                            <p class="mt-0.5 text-slate-300 leading-tight">{{ log.message }}</p>
                        </div>
                    </div>
                </div>

                <!-- Bookings Feed -->
                <div class="bg-white p-6 rounded-3xl border-4 border-slate-900 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] flex-1 flex flex-col">
                    <h2 class="text-xl font-black uppercase tracking-wider text-slate-900 mb-1 flex items-center gap-2">
                        <Calendar class="h-5 w-5 text-slate-800" /> Active Appointments
                    </h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-4">Confirmed real-time bookings</p>
                    
                    <div class="flex-1 overflow-y-auto space-y-4 max-h-[400px] pr-1">
                        <div 
                            v-for="booking in liveBookings" 
                            :key="booking.id"
                            class="bg-[#fafafa] p-3 rounded-xl border-2 border-slate-900 shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] transition-transform hover:-translate-y-0.5"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-950 px-2 py-0.5 rounded-full">
                                    {{ booking.status }}
                                </span>
                                <div class="text-[11px] font-black text-slate-500 flex items-center gap-1">
                                    <Clock class="h-3 w-3" /> 
                                    {{ new Date(booking.scheduled_start).toLocaleString([], {month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) }}
                                </div>
                            </div>
                            
                            <div class="text-xs font-bold text-slate-800 mb-1">
                                Customer: {{ booking.customer_phone }}
                            </div>
                            <div class="text-xs font-semibold text-slate-600 mb-2 italic">
                                "{{ booking.job_details }}"
                            </div>
                            
                            <div class="text-[10px] font-black uppercase text-slate-400 border-t pt-2 flex items-center gap-1.5">
                                <UserIcon class="h-3 w-3" /> Tech: 
                                <span class="text-slate-800">{{ booking.employee.first_name }} {{ booking.employee.last_name }}</span>
                            </div>
                        </div>
                        <div v-if="liveBookings.length === 0" class="text-center py-8 font-bold text-slate-400 text-xs">
                            No appointments booked yet.
                        </div>
                    </div>
                </div>

            </div>
            
        </div>
    </div>
</template>

<style scoped>
.animate-spin-slow {
    animation: spin 8s linear infinite;
}
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
