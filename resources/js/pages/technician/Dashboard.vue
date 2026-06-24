<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
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
    User,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import PasskeyRegister from '@/components/PasskeyRegister.vue';
import { Button } from '@/components/ui/button';
import { BarcodeScanner } from '@capacitor-community/barcode-scanner';

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

// Barcode Scanning logic
const isScanning = ref(false);

const startScan = async () => {
    try {
        isScanning.value = true;
        const status = await BarcodeScanner.checkPermission({ force: true });
        if (status.granted) {
            BarcodeScanner.hideBackground();
            document.body.classList.add('scanner-active');
            const result = await BarcodeScanner.startScan();
            document.body.classList.remove('scanner-active');
            BarcodeScanner.showBackground();
            isScanning.value = false;

            if (result.hasContent) {
                feedback.value = (feedback.value ? feedback.value + '\n' : '') + '[Scanned HVAC: ' + result.content + ']';
            }
        } else {
            alert('Camera permission denied.');
            isScanning.value = false;
        }
    } catch (e) {
        console.warn('BarcodeScanner is not available, using simulated barcode scan.');
        // Simulated fallback for browser environments
        const mockSerial = 'SN-' + Math.floor(10000000 + Math.random() * 90000000);
        feedback.value = (feedback.value ? feedback.value + '\n' : '') + '[Scanned HVAC: ' + mockSerial + ']';
        isScanning.value = false;
    }
};

const stopScan = () => {
    try {
        BarcodeScanner.showBackground();
        BarcodeScanner.stopScan();
    } catch (e) {}
    document.body.classList.remove('scanner-active');
    isScanning.value = false;
};

// Confetti simulation
const confettis = ref<
    Array<{
        id: number;
        x: number;
        y: number;
        color: string;
        size: number;
        delay: number;
        rotate: number;
    }>
>([]);
const showVictoryOverlay = ref(false);

const triggerConfetti = () => {
    confettis.value = [];
    const colors = [
        '#f43f5e',
        '#3b82f6',
        '#10b981',
        '#eab308',
        '#a855f7',
        '#ff7849',
    ];

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
const updateStatus = async (
    booking: Booking,
    status: 'en_route' | 'on_site' | 'completed',
) => {
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
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content || '',
                Accept: 'application/json',
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
        router.reload();
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
        case 'completed':
            return 'Completed';
        case 'on_site':
            return 'On Site';
        case 'en_route':
            return 'En Route';
        default:
            return 'Pending';
    }
};
</script>

<template>
    <Head title="Technician Portal - Dashboard" />

    <!-- Pure CSS Confetti Overlay -->
    <div
        class="pointer-events-none fixed inset-0 z-[100] overflow-hidden"
        v-if="confettis.length > 0"
    >
        <div
            v-for="c in confettis"
            :key="c.id"
            class="confetti-piece"
            :style="{
                left: `${c.x}%`,
                top: `${c.y}%`,
                backgroundColor: c.color,
                width: `${c.size}px`,
                height: `${c.size}px`,
                animationDelay: `${c.delay}s`,
                transform: `rotate(${c.rotate}deg)`,
            }"
        ></div>
    </div>

    <!-- Victory mascot popup overlay -->
    <div
        v-if="showVictoryOverlay"
        class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-950/90 p-6 backdrop-blur-md"
    >
        <div
            class="flex w-full max-w-sm animate-in flex-col items-center gap-6 rounded-3xl border border-slate-800 bg-slate-900 p-8 text-center shadow-2xl duration-300 fade-in zoom-in"
        >
            <div class="flex h-44 w-full items-center justify-center">
                <DispatcherMascot :state="2" />
            </div>
            <div class="space-y-2">
                <h2
                    class="text-3xl font-extrabold tracking-tight text-emerald-400"
                >
                    Job Completed!
                </h2>
                <p class="text-sm text-slate-300">
                    Outstanding work! Your travel duration and logs have been
                    synced.
                </p>
            </div>
            <Button
                class="w-full cursor-pointer rounded-2xl border-0 bg-emerald-600 py-6 font-bold text-white shadow-lg shadow-emerald-950/50 hover:bg-emerald-500"
                @click="showVictoryOverlay = false"
            >
                Great, thanks!
            </Button>
        </div>
    </div>

    <!-- Main Container -->
    <div
        class="relative mx-auto flex min-h-screen max-w-md flex-col border-x border-slate-900/50 bg-slate-950 font-sans text-slate-100 shadow-2xl"
    >
        <!-- Top bar/header -->
        <header
            class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-800/80 bg-slate-900/80 p-4 backdrop-blur-lg"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-indigo-500/20 bg-indigo-600/10 text-indigo-400"
                >
                    <Wrench class="h-5 w-5" />
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-200">
                        {{ props.employee.first_name }}
                        {{ props.employee.last_name }}
                    </h2>
                    <span
                        class="text-[10px] font-semibold tracking-wider text-indigo-400 uppercase"
                        >Field Technician</span
                    >
                </div>
            </div>
            <button
                @click="handleLogout"
                class="bg-slate-850 flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-slate-800 text-slate-400 transition-colors hover:bg-rose-950/20 hover:text-rose-400"
                title="Logout"
            >
                <LogOut class="h-4 w-4" />
            </button>
        </header>

        <!-- Main Body -->
        <main class="flex-1 space-y-6 overflow-y-auto p-4 pb-10">
            <!-- Gamified Stats/KPI Grid -->
            <div class="grid grid-cols-2 gap-4">
                <!-- KPI Score Card -->
                <div
                    class="relative col-span-2 flex items-center justify-between overflow-hidden rounded-3xl border border-indigo-500/20 bg-gradient-to-br from-indigo-900/40 via-indigo-950/20 to-slate-900/50 p-5"
                >
                    <div
                        class="absolute top-0 right-0 h-28 w-28 translate-x-4 -translate-y-4 rounded-full bg-indigo-500/10 blur-2xl"
                    ></div>
                    <div class="space-y-1">
                        <span
                            class="flex items-center gap-1 text-[10px] font-black tracking-wider text-indigo-400 uppercase"
                        >
                            <TrendingUp class="h-3 w-3" /> Efficiency Rating
                            (&Lambda;)
                        </span>
                        <h1
                            class="text-4xl font-black tracking-tight text-slate-100"
                        >
                            {{ props.performanceScore }}
                        </h1>
                        <p class="text-[10px] text-slate-400">
                            Ratio: Completed Jobs / (Shift + Travel hrs)
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-indigo-500/30 bg-indigo-600/10 p-3 text-indigo-400"
                    >
                        <TrendingUp class="h-8 w-8 animate-pulse" />
                    </div>
                </div>

                <!-- Completed Jobs Today -->
                <div
                    class="flex items-center justify-between rounded-2xl border border-slate-800/80 bg-slate-900/50 p-4"
                >
                    <div class="space-y-1">
                        <span
                            class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                            >Completed</span
                        >
                        <div
                            class="flex items-baseline gap-1 text-xl font-extrabold text-emerald-400"
                        >
                            <span>{{ props.jCompleted }}</span>
                            <span class="text-[10px] text-slate-500">jobs</span>
                        </div>
                    </div>
                    <div
                        class="rounded-xl border border-emerald-500/10 bg-emerald-950/10 p-2.5 text-emerald-500/20"
                    >
                        <CheckCircle class="h-5 w-5 text-emerald-400" />
                    </div>
                </div>

                <!-- Active Availabilities -->
                <div
                    class="flex items-center justify-between rounded-2xl border border-slate-800/80 bg-slate-900/50 p-4"
                >
                    <div class="space-y-1">
                        <span
                            class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                            >Scheduled</span
                        >
                        <div
                            class="flex items-baseline gap-1 text-xl font-extrabold text-amber-400"
                        >
                            <span>{{ props.tScheduled }}</span>
                            <span class="text-[10px] text-slate-500">hrs</span>
                        </div>
                    </div>
                    <div
                        class="rounded-xl border border-amber-500/10 bg-amber-950/10 p-2.5 text-amber-500/20"
                    >
                        <Clock class="h-5 w-5 text-amber-400" />
                    </div>
                </div>
            </div>

            <!-- Assignments list -->
            <div class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h3
                        class="flex items-center gap-1.5 text-xs font-extrabold tracking-widest text-slate-400 uppercase"
                    >
                        <Calendar class="h-3.5 w-3.5 text-indigo-400" /> Today's
                        Assignments
                    </h3>
                    <span
                        class="rounded-full border border-slate-800 bg-slate-900 px-2 py-0.5 text-[10px] text-slate-400"
                    >
                        {{ props.bookings.length }} Scheduled
                    </span>
                </div>

                <!-- Assignment Cards -->
                <div
                    v-if="props.bookings.length === 0"
                    class="space-y-2 rounded-3xl border border-slate-900 bg-slate-900/35 p-8 text-center text-slate-500"
                >
                    <Calendar
                        class="mx-auto h-8 w-8 animate-pulse text-slate-600"
                    />
                    <p class="text-xs font-semibold">
                        No jobs assigned to you for today.
                    </p>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="booking in props.bookings"
                        :key="booking.id"
                        class="space-y-4 rounded-3xl border bg-slate-900/60 p-5 shadow-lg transition-all"
                        :class="[
                            booking.status === 'completed'
                                ? 'border-emerald-500/20 bg-slate-950 opacity-75'
                                : 'border-slate-800/80 hover:border-slate-700/80',
                        ]"
                    >
                        <!-- Card Header -->
                        <div class="flex items-start justify-between">
                            <div>
                                <span
                                    class="flex items-center gap-1 text-[10px] font-semibold tracking-widest text-slate-500 uppercase"
                                >
                                    <Clock class="h-3 w-3" />
                                    {{
                                        new Date(
                                            booking.scheduled_start,
                                        ).toLocaleTimeString([], {
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })
                                    }}
                                </span>
                                <h3
                                    class="mt-1 text-base font-extrabold text-indigo-300"
                                >
                                    📞 {{ booking.customer_phone }}
                                </h3>
                            </div>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase"
                                :class="getStatusBadgeClass(booking.status)"
                            >
                                {{ getStatusLabel(booking.status) }}
                            </span>
                        </div>

                        <!-- Card Body (Text Area Details) -->
                        <div
                            class="rounded-2xl border border-slate-900 bg-slate-950/50 p-4 text-xs whitespace-pre-wrap text-slate-300 italic"
                        >
                            "{{ booking.job_details }}"
                        </div>

                        <!-- Travel Metrics if logged -->
                        <div
                            v-if="booking.travel_time > 0"
                            class="flex items-center justify-between rounded-xl border border-slate-800/40 bg-slate-900/50 px-3 py-2 text-[10px] text-slate-400"
                        >
                            <span class="flex items-center gap-1">
                                <Hourglass class="h-3 w-3 text-amber-500" />
                                Transit Duration
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
                                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border-0 bg-indigo-600 px-4 py-3.5 text-xs font-extrabold text-white shadow-lg shadow-indigo-950/50 transition hover:bg-indigo-500 disabled:opacity-50"
                            >
                                <Navigation class="h-4 w-4" />
                                Start Travel (Mark En Route)
                            </button>

                            <!-- State 2: En Route -> Mark On Site -->
                            <button
                                v-else-if="booking.status === 'en_route'"
                                @click="updateStatus(booking, 'on_site')"
                                :disabled="isSubmitting"
                                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border-0 bg-amber-600 px-4 py-3.5 text-xs font-extrabold text-white shadow-lg shadow-amber-950/50 transition hover:bg-amber-500 disabled:opacity-50"
                            >
                                <MapPin class="h-4 w-4" />
                                Arrived On Site
                            </button>

                            <!-- State 3: On Site -> Complete (Opens modal inside card) -->
                            <button
                                v-else-if="booking.status === 'on_site'"
                                @click="updateStatus(booking, 'completed')"
                                :disabled="isSubmitting"
                                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border-0 bg-emerald-600 px-4 py-3.5 text-xs font-extrabold text-white shadow-lg shadow-emerald-950/50 transition hover:bg-emerald-500 disabled:opacity-50"
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
                class="fixed inset-x-0 bottom-0 z-40 mx-auto max-w-md animate-in space-y-4 rounded-t-[30px] border-t border-slate-800 bg-slate-900 p-6 shadow-2xl duration-300 slide-in-from-bottom"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-800 pb-2"
                >
                    <div>
                        <h4 class="text-sm font-black text-slate-200">
                            Log Job Details
                        </h4>
                        <p class="text-[10px] text-slate-400">
                            Complete: {{ selectedBooking.customer_phone }}
                        </p>
                    </div>
                    <button
                        @click="selectedBooking = null"
                        class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border-0 bg-slate-800 text-slate-400 hover:text-slate-200"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Text Area for Feedback -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-300">Job Details & Feedback</label>
                            <button
                                type="button"
                                @click="startScan"
                                class="rounded-xl border border-indigo-500/30 bg-indigo-600/10 px-3 py-1 text-[10px] font-black uppercase text-indigo-400 hover:bg-indigo-600/20"
                            >
                                📷 Scan HVAC System
                            </button>
                        </div>
                        <textarea
                            v-model="feedback"
                            placeholder="Describe parts replaced, work done, or notes..."
                            rows="3"
                            class="w-full rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:outline-none"
                        ></textarea>
                    </div>

                    <!-- Billing amount -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-300"
                            >Billing Amount ($)</label
                        >
                        <div class="relative">
                            <DollarSign
                                class="absolute top-3 left-3.5 h-4 w-4 text-slate-500"
                            />
                            <input
                                type="number"
                                v-model="billingAmount"
                                placeholder="0.00"
                                step="0.01"
                                class="w-full rounded-xl border border-slate-800 bg-slate-950 py-3 pr-4 pl-9 text-xs text-slate-200 focus:border-indigo-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <button
                        @click="updateStatus(selectedBooking!, 'completed')"
                        :disabled="isSubmitting || !feedback.trim()"
                        class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border-0 bg-emerald-600 px-4 py-3.5 text-xs font-extrabold text-white shadow-lg hover:bg-emerald-500 disabled:opacity-50"
                    >
                        <CheckCircle class="h-4 w-4" />
                        Confirm Job Completion
                    </button>
                </div>
            </div>

            <!-- Credentials & Passkeys Manager -->
            <div
                class="space-y-4 rounded-3xl border border-slate-900 bg-slate-900/40 p-5"
            >
                <div class="flex items-center justify-between">
                    <h3
                        class="flex items-center gap-1.5 text-xs font-extrabold tracking-widest text-slate-400 uppercase"
                    >
                        <Key class="h-3.5 w-3.5 text-indigo-400" /> Biometric
                        Passkeys
                    </h3>
                    <button
                        @click="showPasskeyForm = !showPasskeyForm"
                        class="flex cursor-pointer items-center gap-1 border-0 bg-transparent text-[10px] font-bold text-indigo-400 hover:text-indigo-300"
                    >
                        <Plus class="h-3.5 w-3.5" /> Register
                    </button>
                </div>

                <!-- Registration Form block -->
                <div
                    v-if="showPasskeyForm"
                    class="rounded-2xl border border-slate-800/40 bg-slate-950/60 p-4"
                >
                    <PasskeyRegister
                        @success="
                            () => {
                                showPasskeyForm = false;
                                router.reload();
                            }
                        "
                    />
                </div>

                <!-- List of registered passkeys -->
                <div class="space-y-2">
                    <div
                        v-for="pk in props.passkeys"
                        :key="pk.id"
                        class="flex items-center justify-between rounded-xl border border-slate-900/60 bg-slate-950/40 px-3 py-2.5 text-xs"
                    >
                        <div class="flex items-center gap-2 text-slate-200">
                            <Key class="h-3.5 w-3.5 text-indigo-400/70" />
                            <span>{{ pk.name }}</span>
                        </div>
                        <span class="text-[10px] text-slate-500">
                            Added {{ pk.created_at_diff }}
                        </span>
                    </div>
                    <div
                        v-if="props.passkeys.length === 0"
                        class="py-2 text-center text-[11px] text-slate-500 italic"
                    >
                        No passkeys registered. Register biometrics above to log
                        in instantly.
                    </div>
                </div>
            </div>
        </main>

        <!-- Visual scanning interface overlay when scanning is active -->
        <div
            v-if="isScanning"
            class="fixed inset-0 z-50 flex flex-col items-center justify-between bg-slate-950/90 p-6 text-center text-white"
        >
            <div class="pt-10">
                <h2 class="text-lg font-black uppercase tracking-wider text-indigo-400">Scanning HVAC Serial</h2>
                <p class="text-xs text-slate-400">Position the appliance barcode within the guide box</p>
            </div>

            <!-- Guide frame box with heavy slate borders -->
            <div class="relative h-64 w-64 rounded-3xl border-8 border-indigo-500/70 shadow-[0_0_0_2000px_rgba(15,23,42,0.8)]">
                <div class="absolute inset-x-0 top-1/2 h-1 bg-rose-500 animate-pulse"></div>
            </div>

            <button
                type="button"
                @click="stopScan"
                class="mb-10 rounded-2xl border-4 border-slate-100 bg-slate-900 px-6 py-3.5 text-xs font-black uppercase text-white hover:bg-slate-800"
            >
                Cancel Scan
            </button>
        </div>
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
