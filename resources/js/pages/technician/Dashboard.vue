<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import PasskeyRegister from '@/components/PasskeyRegister.vue';
import { 
    Wrench, 
    Clock, 
    Navigation, 
    MapPin, 
    CheckCircle, 
    TrendingUp, 
    Hourglass, 
    Calendar,
    DollarSign,
    LogOut,
    Key,
    Plus,
    X,
    User
} from '@lucide/vue';
import { Button } from '@/components/ui/button';

interface Booking {
    id: number;
    customer_phone: string;
    job_details: string;
    status: string;
    scheduled_start: string;
    en_route_at: string | null;
    on_site_at: string | null;
    completed_at: string | null;
    travel_time: number;
}

interface Passkey {
    id: string;
    name: string;
    created_at_diff: string;
}

const props = defineProps<{
    employee: {
        id: number;
        first_name: string;
        last_name: string;
        phone: string;
        skills: string[];
    };
    bookings: Booking[];
    jCompleted: number;
    tScheduled: number;
    sumTravel: number;
    performanceScore: number;
    passkeys: Passkey[];
}>();

const selectedBooking = ref<Booking | null>(null);
const feedback = ref('');
const billingAmount = ref('');
const isSubmitting = ref(false);
const showPasskeyForm = ref(false);

// Confetti simulation
const confettis = ref<Array<{ id: number; x: number; y: number; color: string; size: number; delay: number; rotate: number }>>([]);
const showVictoryOverlay = ref(false);

const triggerConfetti = () => {
    confettis.value = [];
    const colors = ['#f43f5e', '#3b82f6', '#10b981', '#eab308', '#a855f7', '#ff7849'];
    for (let i = 0; i < 80; i++) {
        confettis.value.push({
            id: i,
            x: Math.random() * 100,
            y: -10 - Math.random() * 20,
            color: colors[Math.floor(Math.random() * colors.length)],
            size: 6 + Math.random() * 8,
            delay: Math.random() * 1.5,
            rotate: Math.random() * 360,
        });
    }
    setTimeout(() => {
        confettis.value = [];
    }, 4500);
};

// Check-in status transition helper
const updateStatus = async (booking: Booking, status: 'en_route' | 'on_site' | 'completed') => {
    if (status === 'completed' && !selectedBooking.value) {
        selectedBooking.value = booking;
        feedback.value = '';
        billingAmount.value = '';
        return;
    }

    isSubmitting.value = true;
    
    try {
        const payload: Record<string, any> = { status };
        if (status === 'completed') {
            payload.feedback = feedback.value;
            if (billingAmount.value) {
                payload.billing_amount = parseFloat(billingAmount.value);
            }
        }

        const response = await fetch(`/api/bookings/${booking.id}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            throw new Error('Failed to update status');
        }

        if (status === 'completed') {
            selectedBooking.value = null;
            triggerConfetti();
            showVictoryOverlay.value = true;
        }

        // Refresh details
        router.reload({
            preserveState: true,
            preserveScroll: true,
        });
    } catch (error) {
        console.error('Error updating status:', error);
    } finally {
        isSubmitting.value = false;
    }
};

const handleLogout = () => {
    router.post('/logout');
};

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-emerald-950/20 text-emerald-400 border border-emerald-500/20';
        case 'on_site':
            return 'bg-indigo-950/20 text-indigo-400 border border-indigo-500/20';
        case 'en_route':
            return 'bg-amber-950/20 text-amber-400 border border-amber-500/20';
        default:
            return 'bg-slate-900 text-slate-400 border border-slate-800';
    }
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'completed': return 'Completed';
        case 'on_site': return 'On Site';
        case 'en_route': return 'En Route';
        default: return 'Pending';
    }
};
</script>

<template>
    <Head title="Technician Portal - Dashboard" />

    <!-- Pure CSS Confetti Overlay -->
    <div class="fixed inset-0 pointer-events-none z-[100] overflow-hidden" v-if="confettis.length > 0">
        <div v-for="c in confettis" :key="c.id" 
             class="confetti-piece"
             :style="{ 
                 left: `${c.x}%`, 
                 top: `${c.y}%`, 
                 backgroundColor: c.color, 
                 width: `${c.size}px`, 
                 height: `${c.size}px`, 
                 animationDelay: `${c.delay}s`,
                 transform: `rotate(${c.rotate}deg)`
             }"
        ></div>
    </div>

    <!-- Victory mascot popup overlay -->
    <div v-if="showVictoryOverlay" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-950/90 backdrop-blur-md p-6">
        <div class="w-full max-w-sm bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl flex flex-col items-center text-center gap-6 animate-in fade-in zoom-in duration-300">
            <div class="h-44 w-full flex items-center justify-center">
                <DispatcherMascot :state="2" />
            </div>
            <div class="space-y-2">
                <h2 class="text-3xl font-extrabold text-emerald-400 tracking-tight">Job Completed!</h2>
                <p class="text-sm text-slate-300">Outstanding work! Your travel duration and logs have been synced.</p>
            </div>
            <Button class="w-full bg-emerald-600 hover:bg-emerald-500 font-bold py-6 rounded-2xl text-white border-0 cursor-pointer shadow-lg shadow-emerald-950/50" @click="showVictoryOverlay = false">
                Great, thanks!
            </Button>
        </div>
    </div>

    <!-- Main Container -->
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans max-w-md mx-auto relative shadow-2xl border-x border-slate-900/50">
        <!-- Top bar/header -->
        <header class="sticky top-0 z-30 bg-slate-900/80 backdrop-blur-lg border-b border-slate-800/80 p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <Wrench class="h-5 w-5" />
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-200">
                        {{ props.employee.first_name }} {{ props.employee.last_name }}
                    </h2>
                    <span class="text-[10px] text-indigo-400 font-semibold uppercase tracking-wider">Field Technician</span>
                </div>
            </div>
            <button 
                @click="handleLogout"
                class="w-9 h-9 rounded-full bg-slate-850 hover:bg-rose-950/20 hover:text-rose-400 text-slate-400 border border-slate-800 flex items-center justify-center transition-colors cursor-pointer"
                title="Logout"
            >
                <LogOut class="h-4 w-4" />
            </button>
        </header>

        <!-- Main Body -->
        <main class="flex-1 p-4 space-y-6 overflow-y-auto pb-10">
            <!-- Gamified Stats/KPI Grid -->
            <div class="grid grid-cols-2 gap-4">
                <!-- KPI Score Card -->
                <div class="col-span-2 bg-gradient-to-br from-indigo-900/40 via-indigo-950/20 to-slate-900/50 border border-indigo-500/20 rounded-3xl p-5 flex items-center justify-between relative overflow-hidden">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-28 h-28 bg-indigo-500/10 rounded-full blur-2xl"></div>
                    <div class="space-y-1">
                        <span class="text-[10px] uppercase font-black text-indigo-400 tracking-wider flex items-center gap-1">
                            <TrendingUp class="h-3 w-3" /> Efficiency Rating (&Lambda;)
                        </span>
                        <h1 class="text-4xl font-black text-slate-100 tracking-tight">
                            {{ props.performanceScore }}
                        </h1>
                        <p class="text-[10px] text-slate-400">
                            Ratio: Completed Jobs / (Shift + Travel hrs)
                        </p>
                    </div>
                    <div class="bg-indigo-600/10 border border-indigo-500/30 rounded-2xl p-3 text-indigo-400">
                        <TrendingUp class="h-8 w-8 animate-pulse" />
                    </div>
                </div>

                <!-- Completed Jobs Today -->
                <div class="bg-slate-900/50 border border-slate-800/80 rounded-2xl p-4 flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Completed</span>
                        <div class="text-xl font-extrabold text-emerald-400 flex items-baseline gap-1">
                            <span>{{ props.jCompleted }}</span>
                            <span class="text-[10px] text-slate-500">jobs</span>
                        </div>
                    </div>
                    <div class="text-emerald-500/20 border border-emerald-500/10 bg-emerald-950/10 rounded-xl p-2.5">
                        <CheckCircle class="h-5 w-5 text-emerald-400" />
                    </div>
                </div>

                <!-- Active Availabilities -->
                <div class="bg-slate-900/50 border border-slate-800/80 rounded-2xl p-4 flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Scheduled</span>
                        <div class="text-xl font-extrabold text-amber-400 flex items-baseline gap-1">
                            <span>{{ props.tScheduled }}</span>
                            <span class="text-[10px] text-slate-500">hrs</span>
                        </div>
                    </div>
                    <div class="text-amber-500/20 border border-amber-500/10 bg-amber-950/10 rounded-xl p-2.5">
                        <Clock class="h-5 w-5 text-amber-400" />
                    </div>
                </div>
            </div>

            <!-- Assignments list -->
            <div class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-xs uppercase font-extrabold tracking-widest text-slate-400 flex items-center gap-1.5">
                        <Calendar class="h-3.5 w-3.5 text-indigo-400" /> Today's Assignments
                    </h3>
                    <span class="text-[10px] bg-slate-900 text-slate-400 px-2 py-0.5 rounded-full border border-slate-800">
                        {{ props.bookings.length }} Scheduled
                    </span>
                </div>

                <!-- Assignment Cards -->
                <div v-if="props.bookings.length === 0" class="bg-slate-900/35 border border-slate-900 rounded-3xl p-8 text-center text-slate-500 space-y-2">
                    <Calendar class="h-8 w-8 mx-auto text-slate-600 animate-pulse" />
                    <p class="text-xs font-semibold">No jobs assigned to you for today.</p>
                </div>

                <div v-else class="space-y-4">
                    <div 
                        v-for="booking in props.bookings" 
                        :key="booking.id"
                        class="bg-slate-900/60 border rounded-3xl p-5 space-y-4 shadow-lg transition-all"
                        :class="[
                            booking.status === 'completed' 
                                ? 'border-emerald-500/20 opacity-75 bg-slate-950' 
                                : 'border-slate-800/80 hover:border-slate-700/80'
                        ]"
                    >
                        <!-- Card Header -->
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold flex items-center gap-1">
                                    <Clock class="h-3 w-3" />
                                    {{ new Date(booking.scheduled_start).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                </span>
                                <h3 class="text-base font-extrabold text-indigo-300 mt-1">
                                    📞 {{ booking.customer_phone }}
                                </h3>
                            </div>
                            <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase" :class="getStatusBadgeClass(booking.status)">
                                {{ getStatusLabel(booking.status) }}
                            </span>
                        </div>

                        <!-- Card Body (Text Area Details) -->
                        <div class="bg-slate-950/50 border border-slate-900 rounded-2xl p-4 text-xs text-slate-300 italic whitespace-pre-wrap">
                            "{{ booking.job_details }}"
                        </div>

                        <!-- Travel Metrics if logged -->
                        <div v-if="booking.travel_time > 0" class="text-[10px] text-slate-400 bg-slate-900/50 border border-slate-800/40 rounded-xl px-3 py-2 flex items-center justify-between">
                            <span class="flex items-center gap-1">
                                <Hourglass class="h-3 w-3 text-amber-500" /> Transit Duration
                            </span>
                            <span class="font-bold text-slate-200">
                                {{ booking.travel_time }} hours
                            </span>
                        </div>

                        <!-- Action buttons based on status -->
                        <div v-if="booking.status !== 'completed'" class="pt-2">
                            <!-- State 1: Booked -> Dispatch to En Route -->
                            <button
                                v-if="booking.status === 'booked'"
                                @click="updateStatus(booking, 'en_route')"
                                :disabled="isSubmitting"
                                class="w-full bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-extrabold text-xs py-3.5 px-4 rounded-2xl flex items-center justify-center gap-2 cursor-pointer shadow-lg shadow-indigo-950/50 transition border-0"
                            >
                                <Navigation class="h-4 w-4" />
                                Start Travel (Mark En Route)
                            </button>

                            <!-- State 2: En Route -> Mark On Site -->
                            <button
                                v-else-if="booking.status === 'en_route'"
                                @click="updateStatus(booking, 'on_site')"
                                :disabled="isSubmitting"
                                class="w-full bg-amber-600 hover:bg-amber-500 disabled:opacity-50 text-white font-extrabold text-xs py-3.5 px-4 rounded-2xl flex items-center justify-center gap-2 cursor-pointer shadow-lg shadow-amber-950/50 transition border-0"
                            >
                                <MapPin class="h-4 w-4" />
                                Arrived On Site
                            </button>

                            <!-- State 3: On Site -> Complete (Opens modal inside card) -->
                            <button
                                v-else-if="booking.status === 'on_site'"
                                @click="updateStatus(booking, 'completed')"
                                :disabled="isSubmitting"
                                class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-extrabold text-xs py-3.5 px-4 rounded-2xl flex items-center justify-center gap-2 cursor-pointer shadow-lg shadow-emerald-950/50 transition border-0"
                            >
                                <CheckCircle class="h-4 w-4" />
                                Complete Assignment
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Complete Input Drawer Panel -->
            <div 
                v-if="selectedBooking" 
                class="fixed inset-x-0 bottom-0 z-40 bg-slate-900 border-t border-slate-800 max-w-md mx-auto rounded-t-[30px] p-6 shadow-2xl space-y-4 animate-in slide-in-from-bottom duration-300"
            >
                <div class="flex justify-between items-center pb-2 border-b border-slate-800">
                    <div>
                        <h4 class="text-sm font-black text-slate-200">Log Job Details</h4>
                        <p class="text-[10px] text-slate-400">Complete: {{ selectedBooking.customer_phone }}</p>
                    </div>
                    <button 
                        @click="selectedBooking = null"
                        class="w-7 h-7 rounded-full bg-slate-800 text-slate-400 hover:text-slate-200 flex items-center justify-center border-0 cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Text Area for Feedback -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-300">Job Details & Feedback</label>
                        <textarea
                            v-model="feedback"
                            placeholder="Describe parts replaced, work done, or notes..."
                            rows="3"
                            class="w-full rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:outline-none"
                        ></textarea>
                    </div>

                    <!-- Billing amount -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-300">Billing Amount ($)</label>
                        <div class="relative">
                            <DollarSign class="absolute left-3.5 top-3 h-4 w-4 text-slate-500" />
                            <input
                                type="number"
                                v-model="billingAmount"
                                placeholder="0.00"
                                step="0.01"
                                class="w-full rounded-xl border border-slate-800 bg-slate-950 py-3 pl-9 pr-4 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <button
                        @click="updateStatus(selectedBooking!, 'completed')"
                        :disabled="isSubmitting || !feedback.trim()"
                        class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 font-extrabold text-xs py-3.5 px-4 rounded-2xl flex items-center justify-center gap-2 cursor-pointer shadow-lg border-0 text-white"
                    >
                        <CheckCircle class="h-4 w-4" />
                        Confirm Job Completion
                    </button>
                </div>
            </div>

            <!-- Credentials & Passkeys Manager -->
            <div class="bg-slate-900/40 border border-slate-900 rounded-3xl p-5 space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-xs uppercase font-extrabold tracking-widest text-slate-400 flex items-center gap-1.5">
                        <Key class="h-3.5 w-3.5 text-indigo-400" /> Biometric Passkeys
                    </h3>
                    <button 
                        @click="showPasskeyForm = !showPasskeyForm"
                        class="text-[10px] text-indigo-400 hover:text-indigo-300 font-bold flex items-center gap-1 cursor-pointer bg-transparent border-0"
                    >
                        <Plus class="h-3.5 w-3.5" /> Register
                    </button>
                </div>

                <!-- Registration Form block -->
                <div v-if="showPasskeyForm" class="bg-slate-950/60 border border-slate-800/40 rounded-2xl p-4">
                    <PasskeyRegister @success="() => { showPasskeyForm = false; router.reload() }" />
                </div>

                <!-- List of registered passkeys -->
                <div class="space-y-2">
                    <div 
                        v-for="pk in props.passkeys" 
                        :key="pk.id" 
                        class="flex justify-between items-center bg-slate-950/40 border border-slate-900/60 rounded-xl px-3 py-2.5 text-xs"
                    >
                        <div class="flex items-center gap-2 text-slate-200">
                            <Key class="h-3.5 w-3.5 text-indigo-400/70" />
                            <span>{{ pk.name }}</span>
                        </div>
                        <span class="text-[10px] text-slate-500">
                            Added {{ pk.created_at_diff }}
                        </span>
                    </div>
                    <div v-if="props.passkeys.length === 0" class="text-[11px] text-slate-500 italic text-center py-2">
                        No passkeys registered. Register biometrics above to log in instantly.
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
@keyframes fall {
    0% {
        transform: translateY(0) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(110vh) rotate(720deg);
        opacity: 0;
    }
}
.confetti-piece {
    position: fixed;
    z-index: 100;
    pointer-events: none;
    animation: fall 3s linear forwards;
}
</style>
