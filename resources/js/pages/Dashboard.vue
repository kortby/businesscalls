<script setup lang="ts">
import { Head, usePage, useForm, router, Link } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import {
    Phone,
    CheckCircle,
    XCircle,
    User as UserIcon,
    Calendar,
    Clock,
    Activity,
    Settings,
    Award,
    Plus,
    Trash2,
    Shield,
} from '@lucide/vue';
import { ref, onMounted, computed, watch } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BargeControls from '@/components/BargeControls.vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import StreakFlame from '@/components/StreakFlame.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import WebCallModal from '@/components/WebCallModal.vue';
import {
    store as storeAvailability,
    destroy as destroyAvailability,
} from '@/routes/availabilities';
import {
    store as storeBooking,
    destroy as destroyBooking,
} from '@/routes/bookings';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import type { Tenant } from '@/types';

// Define Props passed from web.php route
const props = defineProps<{
    tenant: Tenant | null;
    employees: Array<{
        id: number;
        first_name: string;
        last_name: string;
        phone: string;
        skills: string[];
        availabilities: Array<{
            id: number;
            day_of_week: number;
            start_time: string;
            end_time: string;
            is_active: boolean;
        }>;
        bookings?: Array<{
            id: number;
            customer_phone: string;
            job_details: string;
            status: string;
            scheduled_start: string;
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
    totalCallsCount: number;
    successfulBookingsCount: number;
    openJobsTodayCount: number;
    bookingStreak: number;
    averageCqs: number;
}>();

// Page info for auth checks
const page = usePage();
const currentUser = page.props.auth?.user;

// Mascot State: 0=Idle, 1=Searching, 2=Victory, 3=Error
const mascotState = ref<number>(0);

const isWebCallOpen = ref(false);
const webCallPhone = ref('');

const startWebCall = (phone: string) => {
    webCallPhone.value = phone;
    isWebCallOpen.value = true;
};

// Local lists for real-time websocket appending
const liveBookings = ref([...props.bookings]);
const liveFeed = ref<
    Array<{
        id: string;
        timestamp: string;
        type: 'searching' | 'success' | 'error';
        message: string;
    }>
>([]);

const activeCall = ref<{
    call_id: string;
    status: string;
    customer_phone: string;
    transcript: string;
    summary: string;
    duration: number | null;
    telemetry?: {
        jitter: number;
        latency: number;
        packetLoss: number;
    };
} | null>(null);

// Stats counters (reactive)
const stats = ref({
    calls: props.totalCallsCount,
    success: props.successfulBookingsCount,
    openJobsToday: props.openJobsTodayCount,
    conflicts:
        props.bookings.filter((b) => b.status === 'conflict').length || 0,
});

// Dynamic booking success rate calculation (Psi)
const successRate = computed(() => {
    if (stats.value.calls === 0) {
return 0;
}

    return Math.round((stats.value.success / stats.value.calls) * 100);
});

const streakCount = ref(props.bookingStreak);

watch(
    () => props.bookingStreak,
    (newVal) => {
        streakCount.value = newVal;
    },
);

const liveCqs = ref(props.averageCqs);
watch(
    () => props.averageCqs,
    (newVal) => {
        liveCqs.value = newVal;
    },
);

const emergencyAlerts = ref<
    Array<{
        call_id: string;
        customer_phone: string;
        details: string;
        recording_url: string | null;
    }>
>([]);

const isBarged = ref(false);
const bargeMode = ref('monitor');
const bargeSupervisorName = ref('');

const handleBargeInitiated = (payload: { mode: 'monitor' | 'barge' }) => {
    isBarged.value = true;
    bargeMode.value = payload.mode;
};

const handleBargeEnded = () => {
    isBarged.value = false;
};

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
    const days = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ];

    return days[dayNum] ?? '';
};

// Listen to WebSocket Channel
if (props.tenant) {
    // Listens to private-tenant.{id} on channel "tenant.{id}"
    useEcho(`tenant.${props.tenant.id}`, 'dispatch.updated', (payload: any) => {
        const timestamp = new Date().toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
        const uniqueId = Math.random().toString(36).substring(2, 9);

        // Append to logs feed
        liveFeed.value.unshift({
            id: uniqueId,
            timestamp,
            type: payload.type,
            message: payload.message,
        });

        // Set mascot state trigger
        if (payload.type === 'searching') {
            transitionMascot(1);
            stats.value.calls++;
        } else if (payload.type === 'success') {
            transitionMascot(2);
            stats.value.success++;

            // Re-fetch bookings or append booking dynamically
            if (payload.booking) {
                // Increment openJobsToday if scheduled start is today
                const start = new Date(payload.booking.scheduled_start);
                const today = new Date();

                if (start.toDateString() === today.toDateString()) {
                    stats.value.openJobsToday++;
                }

                // Reload booking streak from backend
                router.reload({ only: ['bookingStreak'] });

                // Ensure duplicate check
                if (
                    !liveBookings.value.some((b) => b.id === payload.booking.id)
                ) {
                    liveBookings.value.unshift(payload.booking);
                }

                // Keep max 10
                if (liveBookings.value.length > 10) {
                    liveBookings.value.pop();
                }
            }
        } else if (payload.type === 'error') {
            transitionMascot(3);
            stats.value.conflicts++;

            // If it was a cancel message, we dynamically sync bookings lists
            // Let's filter out deleted bookings by inspecting message contents or re-triggering reload
            router.reload({ only: ['bookings'] });
        } else if (payload.type === 'emergency_voicemail') {
            transitionMascot(3);
            emergencyAlerts.value.unshift({
                call_id: payload.call_id,
                customer_phone: payload.customer_phone,
                details: payload.details,
                recording_url: payload.recording_url,
            });
        }
    });

    useEcho(`tenant.${props.tenant.id}`, 'CallStarted', (payload: any) => {
        const timestamp = new Date().toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
        const uniqueId = Math.random().toString(36).substring(2, 9);

        stats.value.calls++;

        activeCall.value = {
            call_id: payload.callLog.call_id,
            status: payload.callLog.status,
            customer_phone: payload.callLog.customer_phone,
            transcript: payload.callLog.transcript || '',
            summary: payload.callLog.summary || '',
            duration: payload.callLog.duration || null,
        };

        liveFeed.value.unshift({
            id: uniqueId,
            timestamp,
            type: 'searching',
            message: `[Call Started] Live connection from customer ${payload.callLog.customer_phone}`,
        });

        transitionMascot(1);
    });

    useEcho(`tenant.${props.tenant.id}`, 'CallEnded', (payload: any) => {
        const timestamp = new Date().toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
        const uniqueId = Math.random().toString(36).substring(2, 9);

        if (
            activeCall.value &&
            activeCall.value.call_id === payload.callLog.call_id
        ) {
            activeCall.value.status = payload.callLog.status;
            activeCall.value.duration = payload.callLog.duration;
        }

        isBarged.value = false;

        liveFeed.value.unshift({
            id: uniqueId,
            timestamp,
            type: 'success',
            message: `[Call Ended] Call complete. Duration: ${payload.callLog.duration} seconds.`,
        });

        transitionMascot(2);
        router.reload({ only: ['averageCqs'] });
    });

    useEcho(`tenant.${props.tenant.id}`, 'CallAnalyzed', (payload: any) => {
        const timestamp = new Date().toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
        const uniqueId = Math.random().toString(36).substring(2, 9);

        if (
            activeCall.value &&
            activeCall.value.call_id === payload.callLog.call_id
        ) {
            activeCall.value.transcript = payload.callLog.transcript || '';
            activeCall.value.summary = payload.callLog.summary || '';
        } else {
            activeCall.value = {
                call_id: payload.callLog.call_id,
                status: payload.callLog.status,
                customer_phone: payload.callLog.customer_phone,
                transcript: payload.callLog.transcript || '',
                summary: payload.callLog.summary || '',
                duration: payload.callLog.duration || null,
            };
        }

        liveFeed.value.unshift({
            id: uniqueId,
            timestamp,
            type: 'success',
            message: `[Call Analyzed] Transcript generated for call SID: ${payload.callLog.call_id}`,
        });

        transitionMascot(0);
        router.reload({ only: ['averageCqs'] });
    });

    useEcho(`tenant.${props.tenant.id}`, 'SupervisorBarged', (payload: any) => {
        mascotState.value = 0;
        isBarged.value = true;
        bargeMode.value = payload.mode;
        bargeSupervisorName.value = payload.supervisorName;

        const timestamp = new Date().toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
        const uniqueId = Math.random().toString(36).substring(2, 9);
        liveFeed.value.unshift({
            id: uniqueId,
            timestamp,
            type: 'error',
            message: `[Supervisor Takeover] ${payload.supervisorName} has initiated call override (${payload.mode === 'barge' ? 'Barge-In Takeover' : 'Silent Monitor'}). AI Agent is muted.`,
        });
    });

    useEcho(`tenant.${props.tenant.id}`, 'WebRtcTelemetryUpdated', (payload: any) => {
        if (
            activeCall.value &&
            activeCall.value.call_id === payload.callId
        ) {
            activeCall.value.telemetry = {
                jitter: payload.jitter,
                latency: payload.latency,
                packetLoss: payload.packetLoss,
            };
        }
    });
}

// Watch props.bookings to sync liveBookings list on direct Inertia requests
watch(
    () => props.bookings,
    (newVal) => {
        liveBookings.value = [...newVal];
        stats.value.success = newVal.filter(
            (b) => b.status === 'booked',
        ).length;
    },
    { deep: true },
);

const checkUrlViewParam = () => {
    const params = new URLSearchParams(window.location.search);
    const view = params.get('view');

    if (view === 'timeline' || view === 'profiles') {
        activeViewTab.value = view;
    }
};

onMounted(() => {
    // Add default log
    liveFeed.value.push({
        id: 'init',
        timestamp: new Date().toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
        }),
        type: 'success',
        message: 'AI Voice Dispatch System initialized. Monitoring calls...',
    });
    checkUrlViewParam();
});

watch(
    () => page.url,
    () => {
        checkUrlViewParam();
    },
);

// --- Modal State Management ---
const isShiftModalOpen = ref(false);
const isBookingModalOpen = ref(false);

const selectedEmployeeName = ref('');

// --- Shift Form ---
const shiftForm = useForm({
    employee_id: '',
    day_of_week: '1', // Default Monday as string
    start_time: '08:00',
    end_time: '17:00',
    is_active: true,
});

const openShiftModal = (
    employeeId: number,
    firstName: string,
    lastName: string,
) => {
    shiftForm.reset();
    shiftForm.employee_id = employeeId.toString();
    selectedEmployeeName.value = `${firstName} ${lastName}`;
    isShiftModalOpen.value = true;
};

const submitShift = () => {
    shiftForm.post(storeAvailability.url(), {
        onSuccess: () => {
            isShiftModalOpen.value = false;
            shiftForm.reset();
        },
    });
};

const deleteShift = (id: number) => {
    if (confirm('Are you sure you want to delete this shift availability?')) {
        router.delete(destroyAvailability.url(id));
    }
};

// --- Booking Form ---
const bookingForm = useForm({
    employee_id: '',
    customer_phone: '',
    job_details: '',
    scheduled_start: '',
    recaptcha_token: 'dummy-recaptcha-token',
});

const openBookingModal = () => {
    bookingForm.reset();

    if (props.employees.length > 0) {
        bookingForm.employee_id = props.employees[0].id.toString();
    }

    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    selectedBookingDate.value = `${yyyy}-${mm}-${dd}`;
    isBookingModalOpen.value = true;
};

const submitBooking = () => {
    bookingForm.post(storeBooking.url(), {
        onSuccess: () => {
            isBookingModalOpen.value = false;
            bookingForm.reset();
        },
    });
};

const cancelBooking = (id: number) => {
    if (confirm('Are you sure you want to cancel this booking appointment?')) {
        router.delete(destroyBooking.url(id));
    }
};

// --- Visual Weekly Scheduler State & Helpers ---
const activeTechId = ref<number | null>(null);
const activeViewTab = ref<'profiles' | 'timeline'>('timeline');

if (props.employees.length > 0) {
    activeTechId.value = props.employees[0].id;
}

const activeEmployee = computed(() => {
    if (!activeTechId.value) {
        return null;
    }

    return props.employees.find((e) => e.id === activeTechId.value) || null;
});

const selectedBookingDate = ref<string>('');

watch(
    () => bookingForm.scheduled_start,
    (newVal) => {
        if (newVal && newVal.includes('T')) {
            const datePart = newVal.split('T')[0];

            if (datePart && datePart !== selectedBookingDate.value) {
                selectedBookingDate.value = datePart;
            }
        }
    },
);

const bookingSlotsForSelectedDate = computed(() => {
    const empId = parseInt(bookingForm.employee_id);
    const dateStr = selectedBookingDate.value;

    if (!empId || !dateStr) {
        return [];
    }

    const employee = props.employees.find((e) => e.id === empId);

    if (!employee) {
        return [];
    }

    const parsedDate = new Date(dateStr + 'T00:00:00');
    const dayOfWeek = parsedDate.getDay();

    const dayShifts = employee.availabilities.filter(
        (a) => a.day_of_week === dayOfWeek && a.is_active,
    );
    const hours = [
        '08:00',
        '09:00',
        '10:00',
        '11:00',
        '12:00',
        '13:00',
        '14:00',
        '15:00',
        '16:00',
        '17:00',
        '18:00',
    ];
    const slots = [];

    for (const hrStr of hours) {
        const hr = parseInt(hrStr.split(':')[0]);
        const timeVal = hr * 100;

        const isInsideShift = dayShifts.some((s) => {
            const startClean = s.start_time.replace(/:/g, '').substring(0, 4);
            const endClean = s.end_time.replace(/:/g, '').substring(0, 4);

            return (
                timeVal >= parseInt(startClean) && timeVal <= parseInt(endClean)
            );
        });

        if (!isInsideShift) {
            slots.push({
                time: hrStr,
                status: 'off-shift',
                label: 'Off Shift',
                message: 'Technician off shift',
            });
            continue;
        }

        const targetDateTime = new Date(`${dateStr}T${hrStr}:00`).getTime();
        const bufferMs = 90 * 60 * 1000;

        const conflict = employee.bookings?.find((b) => {
            if (b.status !== 'booked') {
                return false;
            }

            const bTime = new Date(b.scheduled_start).getTime();

            return Math.abs(targetDateTime - bTime) <= bufferMs;
        });

        if (conflict) {
            const confTime = new Date(conflict.scheduled_start);
            const isExact = confTime.getHours() === hr;
            slots.push({
                time: hrStr,
                status: isExact ? 'booked' : 'buffer-conflict',
                label: isExact ? 'Booked' : 'Buffer Conflict',
                message: isExact
                    ? `Booked: ${conflict.customer_phone}`
                    : `Buffer: Overlap at ${formatTime12h(confTime.getHours(), confTime.getMinutes())}`,
            });
        } else {
            slots.push({
                time: hrStr,
                status: 'available',
                label: 'Available',
                message: 'Available',
            });
        }
    }

    return slots;
});

const selectSlotTime = (hourStr: string) => {
    bookingForm.scheduled_start = `${selectedBookingDate.value}T${hourStr}`;
};

const currentWeekDates = ref<string[]>([]);
const daysOrder = [1, 2, 3, 4, 5, 6, 0]; // Monday to Sunday
const hourlySlots = [
    '08:00',
    '09:00',
    '10:00',
    '11:00',
    '12:00',
    '13:00',
    '14:00',
    '15:00',
    '16:00',
    '17:00',
    '18:00',
];

const initWeekDates = () => {
    const current = new Date();
    const currentDay = current.getDay(); // 0 (Sun) to 6 (Sat)
    const weekMap: string[] = new Array(7);

    for (let i = 0; i < 7; i++) {
        const d = new Date(current);
        d.setDate(current.getDate() - currentDay + i);
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        weekMap[i] = `${yyyy}-${mm}-${dd}`;
    }

    currentWeekDates.value = weekMap;
};

initWeekDates();

const getShiftForSlot = (employee: any, dayOfWeek: number, hourStr: string) => {
    if (!employee || !employee.availabilities) {
        return null;
    }

    const timeVal = parseInt(hourStr.replace(':', ''));

    return employee.availabilities.find((a: any) => {
        if (a.day_of_week !== dayOfWeek || !a.is_active) {
            return false;
        }

        const startClean = a.start_time.replace(/:/g, '').substring(0, 4);
        const endClean = a.end_time.replace(/:/g, '').substring(0, 4);
        const startVal = parseInt(startClean);
        const endVal = parseInt(endClean);

        return timeVal >= startVal && timeVal < endVal;
    });
};

const addMinutesToTimeVal = (timeVal: number, mins: number): number => {
    let hr = Math.floor(timeVal / 100);
    let mn = timeVal % 100;
    mn += mins;

    while (mn >= 60) {
        hr += 1;
        mn -= 60;
    }

    while (mn < 0) {
        hr -= 1;
        mn += 60;
    }

    return hr * 100 + mn;
};

const getBookingForSlot = (
    employee: any,
    dayOfWeek: number,
    hourStr: string,
) => {
    if (!employee || !employee.bookings) {
        return null;
    }

    const dateStr = currentWeekDates.value[dayOfWeek];
    const hourVal = parseInt(hourStr.replace(':', ''));

    // 1. Find exact booking matching this hour
    const exactBooking = employee.bookings.find((b: any) => {
        if (b.status !== 'booked') {
            return false;
        }

        const bDate = new Date(b.scheduled_start);
        const yyyy = bDate.getFullYear();
        const mm = String(bDate.getMonth() + 1).padStart(2, '0');
        const dd = String(bDate.getDate()).padStart(2, '0');
        const bDateStr = `${yyyy}-${mm}-${dd}`;

        if (bDateStr !== dateStr) {
            return false;
        }

        return bDate.getHours() === parseInt(hourStr.split(':')[0]);
    });

    if (exactBooking) {
        return { type: 'exact', booking: exactBooking };
    }

    // 2. Find overlap booking within 90 mins (travel buffer)
    const bufferBooking = employee.bookings.find((b: any) => {
        if (b.status !== 'booked') {
            return false;
        }

        const bDate = new Date(b.scheduled_start);
        const yyyy = bDate.getFullYear();
        const mm = String(bDate.getMonth() + 1).padStart(2, '0');
        const dd = String(bDate.getDate()).padStart(2, '0');
        const bDateStr = `${yyyy}-${mm}-${dd}`;

        if (bDateStr !== dateStr) {
            return false;
        }

        const bTimeVal = bDate.getHours() * 100 + bDate.getMinutes();
        const bStartVal = addMinutesToTimeVal(bTimeVal, -90);
        const bEndVal = addMinutesToTimeVal(bTimeVal, 90);

        return hourVal >= bStartVal && hourVal <= bEndVal;
    });

    if (bufferBooking) {
        return { type: 'buffer', booking: bufferBooking };
    }

    return null;
};

const handleQuickBook = (
    employeeId: number,
    dayOfWeek: number,
    hourStr: string,
) => {
    bookingForm.reset();
    bookingForm.employee_id = employeeId.toString();
    const dateStr = currentWeekDates.value[dayOfWeek];
    selectedBookingDate.value = dateStr;
    bookingForm.scheduled_start = `${dateStr}T${hourStr}`;
    isBookingModalOpen.value = true;
};

const handleQuickShift = (
    employeeId: number,
    dayOfWeek: number,
    hourStr: string,
) => {
    shiftForm.reset();
    shiftForm.employee_id = employeeId.toString();
    shiftForm.day_of_week = dayOfWeek.toString();

    const startHour = parseInt(hourStr.split(':')[0]);
    const endHour = Math.min(startHour + 8, 22);

    shiftForm.start_time = hourStr;
    shiftForm.end_time = `${String(endHour).padStart(2, '0')}:00`;

    const employee = props.employees.find((e) => e.id === employeeId);
    selectedEmployeeName.value = employee
        ? `${employee.first_name} ${employee.last_name}`
        : '';
    isShiftModalOpen.value = true;
};

// --- Computed Real-Time Validators ---

const formatTime12h = (hours: number, minutes: number): string => {
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hr = hours % 12 || 12;
    const min = String(minutes).padStart(2, '0');

    return `${hr}:${min} ${ampm}`;
};

const bookingValidation = computed(() => {
    const empId = parseInt(bookingForm.employee_id);
    const dateVal = bookingForm.scheduled_start; // "YYYY-MM-DDTHH:MM"

    if (!empId || !dateVal) {
        return {
            status: 'idle',
            message: 'Enter details and a date/time to check availability.',
        };
    }

    const employee = props.employees.find((e) => e.id === empId);

    if (!employee) {
        return { status: 'idle', message: 'Select a technician.' };
    }

    const parsedDate = new Date(dateVal);
    const dayOfWeek = parsedDate.getDay(); // 0 is Sunday, 1 is Monday, etc.

    const hours = parsedDate.getHours();
    const minutes = parsedDate.getMinutes();
    const timeVal = hours * 100 + minutes;

    // 1. Shift check
    const activeShifts = employee.availabilities.filter(
        (a) => a.day_of_week === dayOfWeek && a.is_active,
    );
    const matchingShift = activeShifts.find((a) => {
        const startClean = a.start_time.replace(/:/g, '').substring(0, 4);
        const endClean = a.end_time.replace(/:/g, '').substring(0, 4);
        const startVal = parseInt(startClean);
        const endVal = parseInt(endClean);

        return timeVal >= startVal && timeVal <= endVal;
    });

    if (!matchingShift) {
        return {
            status: 'error',
            message: `Technician is not scheduled to work on ${getDayName(dayOfWeek)} at ${formatTime12h(hours, minutes)}.`,
        };
    }

    // 2. Buffer overlap check
    const currentBookingTime = parsedDate.getTime();
    const bufferMs = 90 * 60 * 1000;

    const conflictingBooking = employee.bookings?.find((b) => {
        if (b.status !== 'booked') {
            return false;
        }

        const bTime = new Date(b.scheduled_start).getTime();

        return Math.abs(currentBookingTime - bTime) <= bufferMs;
    });

    if (conflictingBooking) {
        const confTime = new Date(conflictingBooking.scheduled_start);

        return {
            status: 'warning',
            message: `Travel buffer conflict! Overlaps with booking at ${formatTime12h(confTime.getHours(), confTime.getMinutes())} (90-minute travel buffer violated).`,
        };
    }

    return {
        status: 'success',
        message: `Technician is available! Works shift ${matchingShift.start_time.substring(0, 5)} - ${matchingShift.end_time.substring(0, 5)}.`,
    };
});

const shiftValidation = computed(() => {
    const empId = parseInt(shiftForm.employee_id);
    const day = parseInt(shiftForm.day_of_week);
    const start = shiftForm.start_time; // "HH:MM"
    const end = shiftForm.end_time; // "HH:MM"

    if (!empId || isNaN(day) || !start || !end) {
        return { status: 'idle', message: 'Enter start and end times.' };
    }

    if (start >= end) {
        return {
            status: 'error',
            message: 'End time must be after start time.',
        };
    }

    const employee = props.employees.find((e) => e.id === empId);

    if (!employee) {
        return { status: 'idle', message: 'Select a technician.' };
    }

    const startVal = parseInt(start.replace(':', ''));
    const endVal = parseInt(end.replace(':', ''));

    const conflictingShift = employee.availabilities.find((a) => {
        if (a.day_of_week !== day || !a.is_active) {
            return false;
        }

        const startClean = a.start_time.replace(/:/g, '').substring(0, 4);
        const endClean = a.end_time.replace(/:/g, '').substring(0, 4);
        const aStart = parseInt(startClean);
        const aEnd = parseInt(endClean);

        return startVal < aEnd && endVal > aStart;
    });

    if (conflictingShift) {
        return {
            status: 'error',
            message: `This shift overlaps with an existing shift (${conflictingShift.start_time.substring(0, 5)} - ${conflictingShift.end_time.substring(0, 5)}) on ${getDayName(day)}.`,
        };
    }

    return {
        status: 'success',
        message: 'Shift time is open and has no conflicts.',
    };
});
</script>

<template>
    <Head title="AI Dispatcher Dashboard" />

    <!-- Supervisor Barge Overlay Indicator Flags -->
    <div
        v-if="isBarged"
        class="pointer-events-none fixed top-20 right-6 z-50 flex animate-bounce flex-col gap-2"
    >
        <div
            class="flex items-center gap-2 rounded-xl border-2 border-white bg-red-600 px-4 py-2 text-[10px] font-black tracking-widest text-white uppercase shadow-lg"
        >
            <Shield class="h-4 w-4 text-white" />
            Active Override:
            {{ bargeMode === 'barge' ? 'Barge-In Takeover' : 'Silent Monitor' }}
        </div>
        <div
            class="rounded-lg border border-red-500/20 bg-slate-900/90 px-3 py-1 text-center text-[9px] font-bold text-red-400 shadow-md"
        >
            Supervisor Override Active
        </div>
    </div>

    <div
        class="min-h-screen bg-background p-6 text-foreground transition-all duration-500"
        :class="{ 'ring-8 ring-red-600/40 ring-inset': isBarged }"
    >
        <!-- Dashboard Top Header -->
        <div
            class="mb-8 flex flex-col items-center justify-between gap-4 border-b pb-6 md:flex-row"
        >
            <div class="flex items-center gap-4">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm"
                >
                    <AppLogoIcon
                        class="h-6 w-6 fill-current text-primary-foreground"
                    />
                </div>
                <div>
                    <h1
                        class="text-3xl font-bold tracking-tight text-foreground"
                    >
                        {{ tenant?.name ?? 'businesscalls' }}
                    </h1>
                    <div class="mt-1 flex items-center gap-2">
                        <p
                            class="text-xs tracking-wider text-muted-foreground uppercase"
                        >
                            Active Plan:
                        </p>
                        <Badge
                            variant="secondary"
                            class="text-[10px] font-semibold tracking-widest uppercase"
                            >{{ tenant?.plan ?? 'Trial' }}</Badge
                        >
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div
                    class="flex items-center gap-2 rounded-lg border bg-card px-3 py-1.5 text-xs font-medium shadow-xs"
                >
                    <Settings
                        class="animate-spin-slow h-4 w-4 text-muted-foreground"
                    />
                    <span class="text-muted-foreground">Webhook Status:</span>
                    <Badge
                        class="bg-emerald-500 px-2 py-0.5 text-[10px] font-semibold text-white hover:bg-emerald-500"
                        >Live</Badge
                    >
                </div>
            </div>
        </div>

        <!-- Emergency Voicemail Alerts Banner -->
        <div v-if="emergencyAlerts.length > 0" class="mb-8 space-y-4">
            <div
                v-for="alert in emergencyAlerts"
                :key="alert.call_id"
                class="flex animate-pulse flex-col items-start justify-between gap-4 rounded-3xl border-4 border-b-8 border-rose-500 bg-rose-50 p-5 shadow-md md:flex-row md:items-center dark:bg-rose-950/20"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="shrink-0 rounded-2xl bg-rose-500 p-3 text-white"
                    >
                        <XCircle class="h-6 w-6" />
                    </div>
                    <div>
                        <h3
                            class="text-base font-black tracking-tight text-rose-950 uppercase dark:text-rose-200"
                        >
                            🚨 Emergency Voicemail Received
                        </h3>
                        <p
                            class="mt-0.5 text-xs font-bold text-rose-700/80 dark:text-rose-400/80"
                        >
                            From: {{ alert.customer_phone }}
                        </p>
                        <p
                            class="mt-2 rounded-xl border border-rose-200/50 bg-white/40 p-3 text-sm font-semibold text-slate-800 dark:bg-black/20 dark:text-slate-200"
                        >
                            {{ alert.details }}
                        </p>
                    </div>
                </div>
                <div
                    class="flex items-center justify-end gap-2 self-stretch md:self-auto"
                >
                    <a
                        v-if="alert.recording_url"
                        :href="alert.recording_url"
                        target="_blank"
                        class="cursor-pointer rounded-xl border-2 border-b-4 border-rose-500 border-rose-700 bg-rose-500 px-4 py-2.5 text-xs font-black tracking-wide text-white uppercase hover:border-rose-600 hover:bg-rose-400 active:border-b-0"
                    >
                        Listen Voicemail
                    </a>
                    <button
                        @click="
                            emergencyAlerts = emergencyAlerts.filter(
                                (a) => a.call_id !== alert.call_id,
                            )
                        "
                        class="cursor-pointer rounded-xl border border-b-4 bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200/80 hover:bg-slate-300"
                    >
                        Dismiss
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Grid (Duolingo Exaggerated Style) -->
        <section
            class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-6"
        >
            <!-- Calls Managed -->
            <div
                class="flex flex-col justify-between rounded-3xl border-4 border-b-8 border-indigo-400 bg-indigo-50/50 p-5 dark:border-indigo-800 dark:bg-indigo-950/20"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-black tracking-wider text-indigo-700 uppercase dark:text-indigo-400"
                        >Total Calls</span
                    >
                    <Phone class="h-5 w-5 text-indigo-500" />
                </div>
                <div class="mt-4">
                    <div
                        class="text-4xl font-black text-indigo-950 dark:text-indigo-200"
                    >
                        {{ stats.calls }}
                    </div>
                    <p
                        class="mt-1 text-[10px] font-bold tracking-widest text-indigo-700/60 uppercase dark:text-indigo-400/60"
                    >
                        Managed Live
                    </p>
                </div>
            </div>

            <!-- Successful Bookings -->
            <div
                class="flex flex-col justify-between rounded-3xl border-4 border-b-8 border-emerald-400 bg-emerald-50/50 p-5 dark:border-emerald-800 dark:bg-emerald-950/20"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-black tracking-wider text-emerald-700 uppercase dark:text-emerald-400"
                        >Booked Jobs</span
                    >
                    <CheckCircle class="h-5 w-5 text-emerald-500" />
                </div>
                <div class="mt-4">
                    <div
                        class="text-4xl font-black text-emerald-950 dark:text-emerald-200"
                    >
                        {{ stats.success }}
                    </div>
                    <p
                        class="mt-1 text-[10px] font-bold tracking-widest text-emerald-700/60 uppercase dark:text-emerald-400/60"
                    >
                        Confirmed
                    </p>
                </div>
            </div>

            <!-- Success Rate (Psi) -->
            <div
                class="flex flex-col justify-between rounded-3xl border-4 border-b-8 border-amber-400 bg-amber-50/50 p-5 dark:border-amber-800 dark:bg-amber-950/20"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-black tracking-wider text-amber-700 uppercase dark:text-amber-400"
                        >Success Rate</span
                    >
                    <Activity class="h-5 w-5 text-amber-500" />
                </div>
                <div class="mt-4">
                    <div
                        class="text-4xl font-black text-amber-950 dark:text-amber-200"
                    >
                        {{ successRate }}%
                    </div>
                    <p
                        class="mt-1 text-[10px] font-bold tracking-widest text-amber-700/60 uppercase dark:text-amber-400/60"
                    >
                        Ψ Calculation
                    </p>
                </div>
            </div>

            <!-- Open Jobs Today -->
            <div
                class="flex flex-col justify-between rounded-3xl border-4 border-b-8 border-sky-400 bg-sky-50/50 p-5 dark:border-sky-800 dark:bg-sky-950/20"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-black tracking-wider text-sky-700 uppercase dark:text-sky-400"
                        >Jobs Today</span
                    >
                    <Calendar class="h-5 w-5 text-sky-500" />
                </div>
                <div class="mt-4">
                    <div
                        class="text-4xl font-black text-sky-950 dark:text-sky-200"
                    >
                        {{ stats.openJobsToday }}
                    </div>
                    <p
                        class="mt-1 text-[10px] font-bold tracking-widest text-sky-700/60 uppercase dark:text-sky-400/60"
                    >
                        Scheduled
                    </p>
                </div>
            </div>

            <!-- Daily Booking Streak -->
            <div
                class="relative flex flex-col justify-between overflow-hidden rounded-3xl border-4 border-b-8 border-orange-400 bg-orange-50/50 p-5 dark:border-orange-800 dark:bg-orange-950/20"
            >
                <div class="z-10 flex items-center justify-between">
                    <span
                        class="text-xs font-black tracking-wider text-orange-700 uppercase dark:text-orange-400"
                        >Daily Streak</span
                    >
                    <Award class="h-5 w-5 text-orange-500" />
                </div>
                <div class="z-10 mt-2 flex items-end justify-between">
                    <div>
                        <div
                            class="text-4xl font-black text-orange-950 dark:text-orange-200"
                        >
                            {{ streakCount }}
                        </div>
                        <p
                            class="mt-1 text-[10px] font-bold tracking-widest text-orange-700/60 uppercase dark:text-orange-400/60"
                        >
                            Days Active
                        </p>
                    </div>
                    <!-- Streak Flame Animation -->
                    <div class="-mr-2 -mb-2 shrink-0">
                        <StreakFlame :streak="streakCount" />
                    </div>
                </div>
            </div>

            <!-- Connection Health (CQS) -->
            <div
                class="flex flex-col justify-between rounded-3xl border-4 border-b-8 border-violet-400 bg-violet-50/50 p-5 dark:border-violet-800 dark:bg-violet-950/20"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-black tracking-wider text-violet-700 uppercase dark:text-violet-400"
                        >Connection Health</span
                    >
                    <Activity class="h-5 w-5 text-violet-500" />
                </div>
                <div class="mt-4">
                    <div
                        class="text-4xl font-black text-violet-950 dark:text-violet-200"
                    >
                        {{ Math.round(liveCqs * 100) }}%
                    </div>
                    <div class="mt-2 flex items-center gap-1.5">
                        <span
                            class="text-[10px] leading-none font-bold tracking-widest text-violet-700/60 uppercase dark:text-violet-400/60"
                            >CQS:</span
                        >
                        <Badge
                            :class="{
                                'border-none bg-emerald-500 text-white hover:bg-emerald-500':
                                    liveCqs >= 0.85,
                                'border-none bg-amber-500 text-white hover:bg-amber-500':
                                    liveCqs >= 0.7 && liveCqs < 0.85,
                                'border-none bg-rose-500 text-white hover:bg-rose-500':
                                    liveCqs < 0.7,
                            }"
                            class="px-1.5 py-0.5 text-[9px] font-black"
                        >
                            {{
                                liveCqs >= 0.85
                                    ? 'Great'
                                    : liveCqs >= 0.7
                                      ? 'Good'
                                      : 'Poor'
                            }}
                        </Badge>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Layout Split -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- LEFT PANEL: Mascot Rendering and Technician Shifts (2/3 columns) -->
            <div class="flex flex-col gap-8 lg:col-span-2">
                <!-- Rive Mascot Container -->
                <Card class="shadow-sm">
                    <CardHeader
                        class="flex flex-col items-start justify-between gap-4 border-b pb-4 sm:flex-row sm:items-center"
                    >
                        <div>
                            <CardTitle
                                class="text-xl font-bold tracking-wider uppercase"
                                >AI Dispatcher Mascot</CardTitle
                            >
                            <CardDescription
                                class="text-xs font-medium tracking-widest uppercase"
                                >Interactive WebGL State
                                machine</CardDescription
                            >
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <!-- Dev Animation State Simulator -->
                            <Button
                                variant="outline"
                                size="sm"
                                @click="transitionMascot(0)"
                                class="text-xs font-bold"
                                >Idle</Button
                            >
                            <Button
                                variant="secondary"
                                size="sm"
                                @click="transitionMascot(1)"
                                class="bg-yellow-100 text-xs font-bold text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400"
                                >Scanning</Button
                            >
                            <Button
                                variant="secondary"
                                size="sm"
                                @click="transitionMascot(2)"
                                class="bg-emerald-100 text-xs font-bold text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400"
                                >Victory</Button
                            >
                            <Button
                                variant="secondary"
                                size="sm"
                                @click="transitionMascot(3)"
                                class="bg-rose-100 text-xs font-bold text-rose-800 hover:bg-rose-200 dark:bg-rose-900/30 dark:text-rose-400"
                                >Conflict</Button
                            >
                        </div>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <div
                            class="flex flex-col items-center justify-center gap-8 md:flex-row"
                        >
                            <div
                                class="aspect-square w-full max-w-[260px] rounded-xl border bg-accent/35 p-2 md:w-1/2"
                            >
                                <DispatcherMascot :state="mascotState" />
                            </div>
                            <div
                                class="flex w-full flex-col justify-center gap-3 md:w-1/2"
                            >
                                <!-- Live Telemetry / Transcript Container -->
                                <div
                                    class="relative rounded-xl border border-primary/20 bg-accent/40 p-4"
                                >
                                    <div
                                        class="mb-2 flex items-center justify-between"
                                    >
                                        <h3
                                            class="flex items-center gap-1.5 text-xs font-black text-foreground uppercase"
                                        >
                                            <Activity
                                                class="h-4 w-4 animate-pulse text-primary"
                                            />
                                            Telemetry & Transcript
                                        </h3>
                                        <span
                                            v-if="activeCall"
                                            :class="{
                                                'border-yellow-500/20 bg-yellow-500/10 text-yellow-500':
                                                    activeCall.status ===
                                                    'ongoing',
                                                'border-emerald-500/20 bg-emerald-500/10 text-emerald-500':
                                                    activeCall.status ===
                                                    'ended',
                                            }"
                                            class="inline-flex items-center rounded-md border bg-background px-1.5 py-0.5 text-[9px] font-black tracking-wider uppercase"
                                        >
                                            {{ activeCall.status }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                            >Listening...</span
                                        >
                                    </div>

                                    <div
                                        v-if="activeCall"
                                        class="space-y-2 text-xs"
                                    >
                                        <div
                                            class="grid grid-cols-2 gap-2 border-b pb-2 font-semibold"
                                        >
                                            <div>
                                                <span
                                                    class="block text-[9px] tracking-wider text-muted-foreground uppercase"
                                                    >Phone</span
                                                >
                                                <span
                                                    class="font-bold text-foreground"
                                                    >{{
                                                        activeCall.customer_phone
                                                    }}</span
                                                >
                                            </div>
                                            <div>
                                                <span
                                                    class="block text-[9px] tracking-wider text-muted-foreground uppercase"
                                                    >Duration</span
                                                >
                                                <span
                                                    class="font-mono text-foreground"
                                                    >{{
                                                        activeCall.duration
                                                            ? activeCall.duration +
                                                              's'
                                                            : 'Ongoing...'
                                                    }}</span
                                                >
                                            </div>
                                        </div>

                                        <!-- WebRTC Telemetry Live Diagnostics -->
                                        <div v-if="activeCall.telemetry" class="grid grid-cols-3 gap-2 border-b pb-2 text-center text-[10px]">
                                            <div class="rounded bg-slate-950/40 p-1 border border-slate-900">
                                                <span class="block text-[8px] tracking-wider text-slate-400 uppercase">Jitter</span>
                                                <span class="font-mono font-bold" :class="activeCall.telemetry.jitter > 30 ? 'text-red-400' : 'text-emerald-400'">
                                                    {{ activeCall.telemetry.jitter.toFixed(1) }}ms
                                                </span>
                                            </div>
                                            <div class="rounded bg-slate-950/40 p-1 border border-slate-900">
                                                <span class="block text-[8px] tracking-wider text-slate-400 uppercase">Latency</span>
                                                <span class="font-mono font-bold" :class="activeCall.telemetry.latency > 250 ? 'text-red-400' : 'text-emerald-400'">
                                                    {{ activeCall.telemetry.latency.toFixed(0) }}ms
                                                </span>
                                            </div>
                                            <div class="rounded bg-slate-950/40 p-1 border border-slate-900">
                                                <span class="block text-[8px] tracking-wider text-slate-400 uppercase">Loss</span>
                                                <span class="font-mono font-bold" :class="activeCall.telemetry.packetLoss > 2 ? 'text-red-400' : 'text-emerald-400'">
                                                    {{ activeCall.telemetry.packetLoss.toFixed(1) }}%
                                                </span>
                                            </div>
                                        </div>

                                        <div class="space-y-1">
                                            <span
                                                class="block text-[9px] tracking-wider text-muted-foreground uppercase"
                                                >Speech Transcript</span
                                            >
                                            <div
                                                class="max-h-[80px] overflow-y-auto rounded-lg bg-slate-950/40 p-2 font-mono text-[10px] leading-tight text-slate-300"
                                            >
                                                {{
                                                    activeCall.transcript ||
                                                    '(Listening for voice agent speech...)'
                                                }}
                                            </div>
                                        </div>

                                        <div
                                            v-if="activeCall.summary"
                                            class="mt-2 space-y-1 border-t pt-2"
                                        >
                                            <span
                                                class="block text-[9px] tracking-wider text-muted-foreground uppercase"
                                                >AI Log Summary</span
                                            >
                                            <p
                                                class="text-[10px] leading-tight text-muted-foreground italic"
                                            >
                                                "{{ activeCall.summary }}"
                                            </p>
                                        </div>

                                        <!-- Supervisor Call Override Widget -->
                                        <div
                                            v-if="
                                                activeCall.status ===
                                                    'ongoing' && props.tenant
                                            "
                                            class="mt-4 border-t pt-4"
                                        >
                                            <BargeControls
                                                :activeCall="activeCall"
                                                :isTestMode="
                                                    props.tenant.is_test_mode
                                                "
                                                @barge_initiated="
                                                    handleBargeInitiated
                                                "
                                                @barge_ended="handleBargeEnded"
                                            />
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        class="py-6 text-center text-xs text-muted-foreground italic"
                                    >
                                        System is idle. Telephone lines are
                                        clear.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Active Technicians Dispatch Center -->
                <Card class="relative shadow-sm">
                    <!-- Lock Overlay -->
                    <div
                        v-if="tenant?.settings?.dispatch_locked"
                        class="absolute inset-0 z-50 flex flex-col items-center justify-center rounded-xl bg-slate-900/60 p-6 text-center backdrop-blur-xs"
                    >
                        <div
                            class="max-w-sm space-y-4 rounded-2xl border-3 border-rose-500 bg-card p-6 shadow-xl"
                        >
                            <XCircle
                                class="mx-auto h-12 w-12 animate-bounce text-rose-500"
                            />
                            <h3
                                class="text-lg font-black tracking-tight text-slate-950 uppercase dark:text-white"
                            >
                                Dispatch Panel Locked
                            </h3>
                            <p
                                class="text-xs font-semibold text-muted-foreground"
                            >
                                Your recent subscription payment failed. Please
                                update your payment method to unlock scheduling
                                services.
                            </p>
                            <Link
                                href="/settings/billing"
                                class="inline-block cursor-pointer rounded-xl border-2 border-b-4 border-rose-500 border-rose-700 bg-rose-500 px-6 py-2.5 text-xs font-black tracking-wide text-white uppercase shadow-md transition-all hover:border-rose-600 hover:bg-rose-400 active:border-b-0"
                            >
                                Manage Subscription
                            </Link>
                        </div>
                    </div>

                    <CardHeader
                        class="flex flex-col justify-between gap-4 border-b pb-4 sm:flex-row sm:items-center"
                    >
                        <div>
                            <CardTitle
                                class="text-xl font-bold tracking-wider uppercase"
                                >Technician Dispatch Center</CardTitle
                            >
                            <CardDescription
                                class="text-xs font-medium tracking-widest text-muted-foreground uppercase"
                                >Manage profiles, shifts, and visual
                                schedules</CardDescription
                            >
                        </div>
                        <div class="flex rounded-lg border bg-accent/40 p-1">
                            <button
                                @click="activeViewTab = 'timeline'"
                                :class="
                                    activeViewTab === 'timeline'
                                        ? 'border bg-background text-foreground shadow-xs'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                                class="flex cursor-pointer items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-bold transition-all"
                            >
                                <Calendar class="h-3.5 w-3.5" /> Weekly
                                Scheduler
                            </button>
                            <button
                                @click="activeViewTab = 'profiles'"
                                :class="
                                    activeViewTab === 'profiles'
                                        ? 'border bg-background text-foreground shadow-xs'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                                class="flex cursor-pointer items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-bold transition-all"
                            >
                                <UserIcon class="h-3.5 w-3.5" /> Profiles &
                                Skills
                            </button>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <!-- TIMELINE VIEW -->
                        <div
                            v-if="activeViewTab === 'timeline'"
                            class="space-y-6"
                        >
                            <!-- Tech Select Tabs -->
                            <div class="flex flex-wrap gap-2 border-b pb-2">
                                <button
                                    v-for="employee in employees"
                                    :key="employee.id"
                                    @click="activeTechId = employee.id"
                                    :class="
                                        activeTechId === employee.id
                                            ? 'bg-primary font-bold text-primary-foreground'
                                            : 'bg-secondary font-semibold text-secondary-foreground hover:bg-secondary/80'
                                    "
                                    class="cursor-pointer rounded-lg px-3.5 py-1.5 text-xs shadow-sm transition-all"
                                >
                                    {{ employee.first_name }}
                                    {{ employee.last_name }}
                                </button>
                                <div
                                    v-if="employees.length === 0"
                                    class="py-1 text-xs font-semibold text-muted-foreground"
                                >
                                    No technicians found.
                                </div>
                            </div>

                            <!-- Selected Tech Calendar Grid -->
                            <div
                                v-if="activeTechId"
                                class="space-y-4 text-foreground"
                            >
                                <div
                                    class="hidden grid-cols-12 gap-2 border-b pb-1 text-center text-[10px] font-bold tracking-wider text-muted-foreground uppercase md:grid"
                                >
                                    <div class="col-span-2 text-left">
                                        Day of Week
                                    </div>
                                    <div
                                        v-for="hour in hourlySlots"
                                        :key="hour"
                                        class="col-span-1 border-l font-mono"
                                    >
                                        {{ hour }}
                                    </div>
                                </div>

                                <div
                                    v-for="dayNum in daysOrder"
                                    :key="dayNum"
                                    class="grid grid-cols-1 items-center gap-2 border-b py-2 last:border-b-0 md:grid-cols-12"
                                >
                                    <!-- Day label -->
                                    <div
                                        class="col-span-1 flex flex-row items-center justify-between text-xs font-bold md:col-span-2 md:flex-col md:items-start"
                                    >
                                        <span>{{ getDayName(dayNum) }}</span>
                                        <span
                                            class="text-[10px] font-medium text-muted-foreground"
                                            >{{
                                                currentWeekDates[dayNum]
                                            }}</span
                                        >
                                    </div>

                                    <!-- Grid columns for hours on mobile/desktop -->
                                    <div
                                        class="col-span-1 grid grid-cols-3 gap-1.5 sm:grid-cols-6 md:col-span-10 md:grid-cols-11"
                                    >
                                        <div
                                            v-for="hour in hourlySlots"
                                            :key="hour"
                                            class="relative flex aspect-[2.5/1] flex-col items-center justify-center overflow-hidden rounded border text-center transition-all md:aspect-auto md:h-12"
                                        >
                                            <!-- Desktop top label or mobile details -->
                                            <div
                                                class="absolute top-1 left-1 font-mono text-[8px] text-muted-foreground md:hidden"
                                            >
                                                {{ hour }}
                                            </div>

                                            <template
                                                v-if="
                                                    getBookingForSlot(
                                                        activeEmployee,
                                                        dayNum,
                                                        hour,
                                                    )
                                                "
                                            >
                                                <!-- Booked appointment or buffer -->
                                                <div
                                                    v-if="
                                                        getBookingForSlot(
                                                            activeEmployee,
                                                            dayNum,
                                                            hour,
                                                        )?.type === 'exact'
                                                    "
                                                    class="flex h-full w-full flex-col justify-between border border-blue-500/35 bg-blue-500/10 p-1 text-blue-700 select-none dark:bg-blue-500/5 dark:text-blue-300"
                                                >
                                                    <div
                                                        class="w-full truncate text-[7.5px] font-extrabold"
                                                    >
                                                        📞
                                                        {{
                                                            getBookingForSlot(
                                                                activeEmployee,
                                                                dayNum,
                                                                hour,
                                                            )?.booking
                                                                .customer_phone
                                                        }}
                                                    </div>
                                                    <div
                                                        class="w-full truncate text-[7px] leading-none font-medium text-blue-600/90 dark:text-blue-400/90"
                                                    >
                                                        {{
                                                            getBookingForSlot(
                                                                activeEmployee,
                                                                dayNum,
                                                                hour,
                                                            )?.booking
                                                                .job_details
                                                        }}
                                                    </div>
                                                    <button
                                                        @click="
                                                            cancelBooking(
                                                                getBookingForSlot(
                                                                    activeEmployee,
                                                                    dayNum,
                                                                    hour,
                                                                )?.booking.id,
                                                            )
                                                        "
                                                        class="absolute top-0.5 right-0.5 cursor-pointer text-blue-500 transition-colors hover:text-rose-600"
                                                        title="Cancel Booking"
                                                    >
                                                        <Trash2
                                                            class="h-2 w-2"
                                                        />
                                                    </button>
                                                </div>
                                                <div
                                                    v-else
                                                    class="flex h-full w-full items-center justify-center border border-dotted border-slate-500/20 bg-slate-500/10 text-[8px] font-semibold tracking-tighter text-slate-500 select-none dark:bg-slate-400/5 dark:text-slate-400"
                                                >
                                                    🚗 Buffer
                                                </div>
                                            </template>

                                            <template
                                                v-else-if="
                                                    getShiftForSlot(
                                                        activeEmployee,
                                                        dayNum,
                                                        hour,
                                                    )
                                                "
                                            >
                                                <!-- On Shift - Open Slot to book -->
                                                <button
                                                    @click="
                                                        handleQuickBook(
                                                            activeTechId,
                                                            dayNum,
                                                            hour,
                                                        )
                                                    "
                                                    class="group flex h-full w-full cursor-pointer flex-col items-center justify-center gap-0.5 rounded border border-dashed border-emerald-500/25 bg-emerald-500/5 font-bold text-emerald-600 transition-all hover:bg-emerald-500/15 dark:bg-emerald-500/5 dark:text-emerald-400 dark:hover:bg-emerald-500/10"
                                                    title="Quick Book Slot"
                                                >
                                                    <Plus
                                                        class="h-3 w-3 text-emerald-500 transition-transform group-hover:scale-125"
                                                    />
                                                    <span
                                                        class="text-[7.5px] tracking-wider uppercase"
                                                        >Book</span
                                                    >
                                                </button>
                                            </template>

                                            <template v-else>
                                                <!-- Out of Shift - Click to add shift -->
                                                <button
                                                    @click="
                                                        handleQuickShift(
                                                            activeTechId,
                                                            dayNum,
                                                            hour,
                                                        )
                                                    "
                                                    class="flex h-full w-full cursor-pointer flex-col items-center justify-center gap-0.5 border border-dashed border-border bg-slate-100/60 text-slate-400 transition-all hover:bg-slate-200/80 dark:bg-slate-900/40 dark:text-slate-600 dark:hover:bg-slate-900"
                                                    title="Technician Off Shift. Click to add shift."
                                                >
                                                    <span
                                                        class="text-[8px] font-medium tracking-tight"
                                                        >Off Shift</span
                                                    >
                                                    <span
                                                        class="text-[7px] text-slate-400/70 transition-colors hover:text-primary"
                                                        >+ Shift</span
                                                    >
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PROFILES VIEW -->
                        <div
                            v-else-if="activeViewTab === 'profiles'"
                            class="grid grid-cols-1 gap-6 md:grid-cols-2"
                        >
                            <div
                                v-for="employee in employees"
                                :key="employee.id"
                                class="flex flex-col justify-between rounded-xl border bg-card p-4 transition-all hover:shadow-xs"
                            >
                                <div>
                                    <div
                                        class="mb-3 flex items-center justify-between gap-3"
                                    >
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-10 w-10 border">
                                                <AvatarFallback
                                                    class="bg-accent font-semibold text-accent-foreground"
                                                >
                                                    {{ employee.first_name[0]
                                                    }}{{
                                                        employee.last_name[0]
                                                    }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <h3
                                                    class="leading-tight font-bold text-foreground"
                                                >
                                                    {{ employee.first_name }}
                                                    {{ employee.last_name }}
                                                </h3>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{ employee.phone }}
                                                </p>
                                            </div>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="h-7 gap-1 px-2.5 text-[10px] font-semibold"
                                            @click="
                                                openShiftModal(
                                                    employee.id,
                                                    employee.first_name,
                                                    employee.last_name,
                                                )
                                            "
                                        >
                                            <Plus class="h-3 w-3" /> Add Shift
                                        </Button>
                                    </div>

                                    <!-- Skills Badges -->
                                    <div class="mb-3">
                                        <div
                                            class="mb-1 text-[10px] font-semibold tracking-wider text-muted-foreground uppercase"
                                        >
                                            Skills Profile
                                        </div>
                                        <div class="flex flex-wrap gap-1">
                                            <Badge
                                                v-for="skill in employee.skills"
                                                :key="skill"
                                                variant="outline"
                                                class="text-[10px] font-bold uppercase"
                                            >
                                                {{ skill }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>

                                <!-- Active Shift Hours -->
                                <div>
                                    <div
                                        class="mb-1 text-[10px] font-semibold tracking-wider text-muted-foreground uppercase"
                                    >
                                        Weekly Shifts
                                    </div>
                                    <div
                                        class="max-h-[120px] space-y-1 overflow-y-auto pr-1"
                                    >
                                        <div
                                            v-for="avail in employee.availabilities"
                                            :key="avail.id"
                                            class="group flex items-center justify-between rounded border border-border bg-accent/20 px-2 py-1 text-[11px] font-medium text-muted-foreground"
                                        >
                                            <span>{{
                                                getDayName(avail.day_of_week)
                                            }}</span>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="font-mono text-foreground/90"
                                                    >{{
                                                        avail.start_time.substring(
                                                            0,
                                                            5,
                                                        )
                                                    }}
                                                    -
                                                    {{
                                                        avail.end_time.substring(
                                                            0,
                                                            5,
                                                        )
                                                    }}</span
                                                >
                                                <button
                                                    @click="
                                                        deleteShift(avail.id)
                                                    "
                                                    class="cursor-pointer text-muted-foreground opacity-70 transition-colors hover:text-rose-600 hover:opacity-100"
                                                    title="Delete Shift"
                                                >
                                                    <Trash2
                                                        class="h-3.5 w-3.5"
                                                    />
                                                </button>
                                            </div>
                                        </div>
                                        <div
                                            v-if="
                                                employee.availabilities
                                                    .length === 0
                                            "
                                            class="py-1 text-xs font-semibold text-amber-600/90 italic"
                                        >
                                            No active shifts registered.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="employees.length === 0"
                                class="col-span-2 py-6 text-center font-semibold text-muted-foreground"
                            >
                                No technician profiles configured. Please seed
                                the database.
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- RIGHT PANEL: Live Feed Logs and Active Bookings (1/3 column) -->
            <div class="flex flex-col gap-8">
                <!-- Live Event Logs Feed -->
                <Card
                    class="flex h-[350px] flex-col border border-slate-800 bg-slate-950 text-emerald-400"
                >
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 border-b border-slate-800/60 pb-3"
                    >
                        <CardTitle
                            class="flex items-center gap-2 text-sm font-bold tracking-wider text-emerald-400 uppercase"
                        >
                            <Activity
                                class="h-4 w-4 animate-pulse text-emerald-400"
                            />
                            Dispatch Terminal
                        </CardTitle>
                        <Badge
                            variant="outline"
                            class="rounded border-emerald-500/30 bg-emerald-500/10 px-1.5 py-0.5 text-[9px] font-bold text-emerald-400 uppercase"
                        >
                            WebSocket
                        </Badge>
                    </CardHeader>
                    <CardContent
                        class="flex-1 scrollbar-thin space-y-3 overflow-y-auto pt-4 pr-1 font-mono text-xs"
                    >
                        <div
                            v-for="log in liveFeed"
                            :key="log.id"
                            :class="{
                                'text-yellow-400': log.type === 'searching',
                                'text-emerald-400': log.type === 'success',
                                'text-rose-400': log.type === 'error',
                            }"
                            class="border-b border-slate-900 pb-2"
                        >
                            <span class="text-slate-600"
                                >[{{ log.timestamp }}]</span
                            >
                            <span class="ml-1 font-bold uppercase"
                                >[{{ log.type }}]</span
                            >
                            <p class="mt-0.5 leading-tight text-slate-300">
                                {{ log.message }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Bookings Feed -->
                <Card class="flex flex-1 flex-col shadow-sm">
                    <CardHeader
                        class="flex flex-row items-center justify-between gap-4 space-y-0 border-b pb-3"
                    >
                        <div>
                            <CardTitle
                                class="flex items-center gap-2 text-lg font-bold tracking-wider uppercase"
                            >
                                <Calendar
                                    class="h-5 w-5 text-muted-foreground"
                                />
                                Appointments
                            </CardTitle>
                            <CardDescription
                                class="text-[10px] font-medium tracking-widest text-muted-foreground uppercase"
                            >
                                Confirmed bookings
                            </CardDescription>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-8 gap-1 px-3 text-[11px] font-bold"
                            @click="openBookingModal"
                        >
                            <Plus class="h-3.5 w-3.5" /> Book Slot
                        </Button>
                    </CardHeader>
                    <CardContent
                        class="max-h-[450px] flex-1 space-y-4 overflow-y-auto pt-4 pr-1"
                    >
                        <div
                            v-for="booking in liveBookings"
                            :key="booking.id"
                            class="group relative rounded-lg border bg-accent/15 p-3 transition-all hover:bg-accent/25"
                        >
                            <!-- Cancel Button overlay -->
                            <button
                                @click="cancelBooking(booking.id)"
                                class="absolute top-2.5 right-2.5 cursor-pointer text-muted-foreground opacity-70 transition-colors hover:text-rose-600 hover:opacity-100"
                                title="Cancel Booking"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>

                            <div
                                class="mb-2 flex items-start justify-between pr-6"
                            >
                                <Badge
                                    variant="secondary"
                                    class="rounded border border-emerald-500/20 bg-emerald-100/50 px-2 py-0.5 text-[9px] font-bold text-emerald-800 uppercase dark:bg-emerald-900/30 dark:text-emerald-400"
                                >
                                    {{ booking.status }}
                                </Badge>
                                <div
                                    class="flex items-center gap-1 text-[11px] font-medium text-muted-foreground"
                                >
                                    <Clock class="h-3 w-3" />
                                    {{
                                        new Date(
                                            booking.scheduled_start,
                                        ).toLocaleString([], {
                                            month: 'short',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })
                                    }}
                                </div>
                            </div>

                            <div
                                class="mb-1 flex items-center justify-between text-xs font-bold text-foreground"
                            >
                                <span
                                    >Customer:
                                    {{ booking.customer_phone }}</span
                                >
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    class="h-6 w-6 rounded-full text-muted-foreground hover:bg-indigo-500/10 hover:text-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400"
                                    @click="
                                        startWebCall(booking.customer_phone)
                                    "
                                >
                                    <Phone class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                            <div
                                class="mb-2 text-xs font-medium text-muted-foreground italic"
                            >
                                "{{ booking.job_details }}"
                            </div>

                            <Separator class="my-2" />

                            <div
                                class="flex items-center gap-1.5 text-[10px] font-semibold text-muted-foreground uppercase"
                            >
                                <UserIcon
                                    class="h-3 w-3 text-muted-foreground"
                                />
                                Tech:
                                <span class="font-bold text-foreground"
                                    >{{ booking.employee.first_name }}
                                    {{ booking.employee.last_name }}</span
                                >
                            </div>
                        </div>
                        <div
                            v-if="liveBookings.length === 0"
                            class="py-8 text-center text-xs font-semibold text-muted-foreground"
                        >
                            No appointments booked yet.
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- --- AVAILABILITY FORM DIALOG --- -->
        <Dialog v-model:open="isShiftModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Add Technician Shift</DialogTitle>
                    <DialogDescription>
                        Assign new weekly shift hours for
                        {{ selectedEmployeeName }}.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitShift" class="space-y-4 pt-4">
                    <div class="space-y-2">
                        <Label for="day_of_week">Day of Week</Label>
                        <Select v-model="shiftForm.day_of_week">
                            <SelectTrigger>
                                <SelectValue placeholder="Select a day" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="0">Sunday</SelectItem>
                                <SelectItem value="1">Monday</SelectItem>
                                <SelectItem value="2">Tuesday</SelectItem>
                                <SelectItem value="3">Wednesday</SelectItem>
                                <SelectItem value="4">Thursday</SelectItem>
                                <SelectItem value="5">Friday</SelectItem>
                                <SelectItem value="6">Saturday</SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="shiftForm.errors.day_of_week"
                            class="mt-1 text-xs text-rose-500"
                        >
                            {{ shiftForm.errors.day_of_week }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="start_time">Start Time</Label>
                            <Input
                                id="start_time"
                                type="time"
                                v-model="shiftForm.start_time"
                            />
                            <p
                                v-if="shiftForm.errors.start_time"
                                class="mt-1 text-xs text-rose-500"
                            >
                                {{ shiftForm.errors.start_time }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="end_time">End Time</Label>
                            <Input
                                id="end_time"
                                type="time"
                                v-model="shiftForm.end_time"
                            />
                            <p
                                v-if="shiftForm.errors.end_time"
                                class="mt-1 text-xs text-rose-500"
                            >
                                {{ shiftForm.errors.end_time }}
                            </p>
                        </div>
                    </div>

                    <!-- Live Shift Validation Status -->
                    <div
                        v-if="shiftValidation.status !== 'idle'"
                        :class="{
                            'border-emerald-500/25 bg-emerald-500/10 text-emerald-800 dark:text-emerald-400':
                                shiftValidation.status === 'success',
                            'border-rose-500/25 bg-rose-500/10 text-rose-800 dark:text-rose-400':
                                shiftValidation.status === 'error',
                        }"
                        class="mt-2 flex items-center gap-2 rounded-lg border p-3 text-xs font-semibold"
                    >
                        <CheckCircle
                            v-if="shiftValidation.status === 'success'"
                            class="h-4 w-4 shrink-0 text-emerald-500"
                        />
                        <XCircle
                            v-else
                            class="h-4 w-4 shrink-0 text-rose-500"
                        />
                        <span>{{ shiftValidation.message }}</span>
                    </div>

                    <DialogFooter class="border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isShiftModalOpen = false"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="
                                shiftForm.processing ||
                                shiftValidation.status === 'error'
                            "
                            >Add Shift</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- --- MANUAL BOOKING FORM DIALOG --- -->
        <Dialog v-model:open="isBookingModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Schedule Appointment</DialogTitle>
                    <DialogDescription>
                        Manually book an appointment. Select a date and click an
                        available time slot.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitBooking" class="space-y-4 pt-4">
                    <div class="space-y-2">
                        <Label for="employee_id">Assign Technician</Label>
                        <Select v-model="bookingForm.employee_id">
                            <SelectTrigger>
                                <SelectValue
                                    placeholder="Select a technician"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="emp in employees"
                                    :key="emp.id"
                                    :value="emp.id.toString()"
                                >
                                    {{ emp.first_name }} {{ emp.last_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="bookingForm.errors.employee_id"
                            class="mt-1 text-xs text-rose-500"
                        >
                            {{ bookingForm.errors.employee_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="customer_phone">Customer Phone</Label>
                        <Input
                            id="customer_phone"
                            type="text"
                            placeholder="(555) 019-2834"
                            v-model="bookingForm.customer_phone"
                        />
                        <p
                            v-if="bookingForm.errors.customer_phone"
                            class="mt-1 text-xs text-rose-500"
                        >
                            {{ bookingForm.errors.customer_phone }}
                        </p>
                    </div>

                    <div class="my-2 grid grid-cols-1 gap-4 border-y py-4">
                        <!-- Booking Date Picker -->
                        <div class="space-y-2">
                            <Label for="booking_date">Target Date</Label>
                            <Input
                                id="booking_date"
                                type="date"
                                v-model="selectedBookingDate"
                            />
                        </div>

                        <!-- Availability Slots Grid -->
                        <div class="space-y-2">
                            <Label
                                >Available Time Slots for
                                {{ selectedBookingDate }}</Label
                            >
                            <div
                                v-if="bookingSlotsForSelectedDate.length > 0"
                                class="grid max-h-[140px] grid-cols-3 gap-1.5 overflow-y-auto rounded-lg border bg-accent/10 p-2 pr-1 sm:grid-cols-4"
                            >
                                <button
                                    v-for="slot in bookingSlotsForSelectedDate"
                                    :key="slot.time"
                                    type="button"
                                    :disabled="slot.status !== 'available'"
                                    :class="{
                                        'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 hover:bg-emerald-500/20 dark:text-emerald-400':
                                            slot.status === 'available' &&
                                            bookingForm.scheduled_start !==
                                                `${selectedBookingDate}T${slot.time}`,
                                        'border-emerald-600 bg-emerald-500 font-bold text-white shadow-xs':
                                            slot.status === 'available' &&
                                            bookingForm.scheduled_start ===
                                                `${selectedBookingDate}T${slot.time}`,
                                        'cursor-not-allowed border-rose-500/15 bg-rose-500/5 text-rose-500 line-through opacity-60':
                                            slot.status === 'booked',
                                        'cursor-not-allowed border-yellow-500/15 bg-yellow-500/5 text-yellow-600 line-through opacity-60':
                                            slot.status === 'buffer-conflict',
                                        'cursor-not-allowed border-slate-200 bg-slate-100/50 text-slate-400 opacity-40 dark:bg-slate-900/50':
                                            slot.status === 'off-shift',
                                    }"
                                    class="flex cursor-pointer flex-col items-center justify-center rounded-lg border px-2 py-1.5 text-center text-[10px] font-bold transition-all"
                                    :title="slot.message"
                                    @click="selectSlotTime(slot.time)"
                                >
                                    <span>{{ slot.time }}</span>
                                    <span
                                        class="mt-0.5 text-[7px] leading-none font-normal tracking-tighter uppercase"
                                        >{{ slot.label }}</span
                                    >
                                </button>
                            </div>
                            <div
                                v-else
                                class="rounded-lg border bg-rose-500/5 p-2 text-center text-xs font-semibold text-rose-500"
                            >
                                No active shifts scheduled for this technician
                                on this day.
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Selected Appointment Time</Label>
                        <div
                            class="flex items-center justify-between rounded-lg border bg-muted/30 p-3 font-mono text-xs"
                        >
                            <span
                                v-if="bookingForm.scheduled_start"
                                class="font-bold text-foreground"
                            >
                                {{
                                    new Date(
                                        bookingForm.scheduled_start,
                                    ).toLocaleString([], {
                                        month: 'short',
                                        day: 'numeric',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    })
                                }}
                            </span>
                            <span v-else class="text-muted-foreground italic"
                                >No time slot selected yet</span
                            >
                        </div>
                        <p
                            v-if="bookingForm.errors.scheduled_start"
                            class="mt-1 text-xs text-rose-500"
                        >
                            {{ bookingForm.errors.scheduled_start }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="job_details">Job Details</Label>
                        <Textarea
                            id="job_details"
                            placeholder="Leaky copper pipe repair in kitchen"
                            v-model="bookingForm.job_details"
                        />
                        <p
                            v-if="bookingForm.errors.job_details"
                            class="mt-1 text-xs text-rose-500"
                        >
                            {{ bookingForm.errors.job_details }}
                        </p>
                    </div>

                    <!-- Simulated reCAPTCHA Widget (Duolingo flat style) -->
                    <div
                        class="flex items-center justify-between rounded-2xl border-2 border-indigo-500/20 bg-indigo-500/5 p-4 shadow-xs"
                    >
                        <div class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                checked
                                disabled
                                class="h-5 w-5 cursor-not-allowed rounded-lg border-2 border-indigo-500 bg-slate-900/50 text-indigo-600"
                            />
                            <span class="text-xs font-extrabold text-slate-200"
                                >I'm not a robot</span
                            >
                        </div>
                        <div
                            class="text-right text-[9px] leading-tight font-semibold tracking-wider text-slate-500 uppercase"
                        >
                            reCAPTCHA verified<br />
                            <span class="text-indigo-400">Privacy & Terms</span>
                        </div>
                    </div>

                    <!-- Live Booking Validation Status -->
                    <div
                        v-if="bookingValidation.status !== 'idle'"
                        :class="{
                            'border-emerald-500/25 bg-emerald-500/10 text-emerald-800 dark:text-emerald-400':
                                bookingValidation.status === 'success',
                            'border-yellow-500/25 bg-yellow-500/10 text-yellow-800 dark:text-yellow-400':
                                bookingValidation.status === 'warning',
                            'border-rose-500/25 bg-rose-500/10 text-rose-800 dark:text-rose-400':
                                bookingValidation.status === 'error',
                        }"
                        class="mt-2 flex items-center gap-2 rounded-lg border p-3 text-xs font-semibold"
                    >
                        <CheckCircle
                            v-if="bookingValidation.status === 'success'"
                            class="h-4 w-4 shrink-0 text-emerald-500"
                        />
                        <Activity
                            v-else-if="bookingValidation.status === 'warning'"
                            class="h-4 w-4 shrink-0 animate-pulse text-yellow-500"
                        />
                        <XCircle
                            v-else
                            class="h-4 w-4 shrink-0 text-rose-500"
                        />
                        <span>{{ bookingValidation.message }}</span>
                    </div>

                    <DialogFooter class="border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isBookingModalOpen = false"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="
                                bookingForm.processing ||
                                bookingValidation.status !== 'success'
                            "
                            >Confirm Booking</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <WebCallModal
            :is-open="isWebCallOpen"
            :phone="webCallPhone"
            @close="isWebCallOpen = false"
            @call_started="() => transitionMascot(1)"
            @call_ended="() => transitionMascot(0)"
        />
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
