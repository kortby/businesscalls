<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { Head, usePage, useForm, router } from '@inertiajs/vue3';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useEcho } from '@laravel/echo-vue';
import { store as storeAvailability, destroy as destroyAvailability } from '@/routes/availabilities';
import { store as storeBooking, destroy as destroyBooking } from '@/routes/bookings';
import { 
    Card, 
    CardHeader, 
    CardTitle, 
    CardDescription, 
    CardContent 
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    Trash2
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
            
            // Re-fetch bookings or append booking dynamically
            if (payload.booking) {
                // Ensure duplicate check
                if (!liveBookings.value.some(b => b.id === payload.booking.id)) {
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
        }
    });
}

// Watch props.bookings to sync liveBookings list on direct Inertia requests
watch(() => props.bookings, (newVal) => {
    liveBookings.value = [...newVal];
    stats.value.success = newVal.filter(b => b.status === 'booked').length;
}, { deep: true });

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
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        type: 'success',
        message: 'AI Voice Dispatch System initialized. Monitoring calls...'
    });
    checkUrlViewParam();
});

watch(() => page.url, () => {
    checkUrlViewParam();
});

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

const openShiftModal = (employeeId: number, firstName: string, lastName: string) => {
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
        }
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
        }
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
    return props.employees.find(e => e.id === activeTechId.value) || null;
});

const selectedBookingDate = ref<string>('');

watch(() => bookingForm.scheduled_start, (newVal) => {
    if (newVal && newVal.includes('T')) {
        const datePart = newVal.split('T')[0];
        if (datePart && datePart !== selectedBookingDate.value) {
            selectedBookingDate.value = datePart;
        }
    }
});

const bookingSlotsForSelectedDate = computed(() => {
    const empId = parseInt(bookingForm.employee_id);
    const dateStr = selectedBookingDate.value;
    if (!empId || !dateStr) {
        return [];
    }
    
    const employee = props.employees.find(e => e.id === empId);
    if (!employee) {
        return [];
    }
    
    const parsedDate = new Date(dateStr + 'T00:00:00');
    const dayOfWeek = parsedDate.getDay();
    
    const dayShifts = employee.availabilities.filter(a => a.day_of_week === dayOfWeek && a.is_active);
    const hours = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
    const slots = [];
    
    for (const hrStr of hours) {
        const hr = parseInt(hrStr.split(':')[0]);
        const timeVal = hr * 100;
        
        const isInsideShift = dayShifts.some(s => {
            const startClean = s.start_time.replace(/:/g, '').substring(0, 4);
            const endClean = s.end_time.replace(/:/g, '').substring(0, 4);
            return timeVal >= parseInt(startClean) && timeVal <= parseInt(endClean);
        });
        
        if (!isInsideShift) {
            slots.push({
                time: hrStr,
                status: 'off-shift',
                label: 'Off Shift',
                message: 'Technician off shift'
            });
            continue;
        }
        
        const targetDateTime = new Date(`${dateStr}T${hrStr}:00`).getTime();
        const bufferMs = 90 * 60 * 1000;
        
        const conflict = employee.bookings?.find(b => {
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
                    : `Buffer: Overlap at ${formatTime12h(confTime.getHours(), confTime.getMinutes())}`
            });
        } else {
            slots.push({
                time: hrStr,
                status: 'available',
                label: 'Available',
                message: 'Available'
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
const hourlySlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

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

const getBookingForSlot = (employee: any, dayOfWeek: number, hourStr: string) => {
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

const handleQuickBook = (employeeId: number, dayOfWeek: number, hourStr: string) => {
    bookingForm.reset();
    bookingForm.employee_id = employeeId.toString();
    const dateStr = currentWeekDates.value[dayOfWeek];
    selectedBookingDate.value = dateStr;
    bookingForm.scheduled_start = `${dateStr}T${hourStr}`;
    isBookingModalOpen.value = true;
};

const handleQuickShift = (employeeId: number, dayOfWeek: number, hourStr: string) => {
    shiftForm.reset();
    shiftForm.employee_id = employeeId.toString();
    shiftForm.day_of_week = dayOfWeek.toString();
    
    const startHour = parseInt(hourStr.split(':')[0]);
    const endHour = Math.min(startHour + 8, 22);
    
    shiftForm.start_time = hourStr;
    shiftForm.end_time = `${String(endHour).padStart(2, '0')}:00`;
    
    const employee = props.employees.find(e => e.id === employeeId);
    selectedEmployeeName.value = employee ? `${employee.first_name} ${employee.last_name}` : '';
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
        return { status: 'idle', message: 'Enter details and a date/time to check availability.' };
    }
    
    const employee = props.employees.find(e => e.id === empId);
    if (!employee) {
        return { status: 'idle', message: 'Select a technician.' };
    }
    
    const parsedDate = new Date(dateVal);
    const dayOfWeek = parsedDate.getDay(); // 0 is Sunday, 1 is Monday, etc.
    
    const hours = parsedDate.getHours();
    const minutes = parsedDate.getMinutes();
    const timeVal = hours * 100 + minutes;
    
    // 1. Shift check
    const activeShifts = employee.availabilities.filter(a => a.day_of_week === dayOfWeek && a.is_active);
    const matchingShift = activeShifts.find(a => {
        const startClean = a.start_time.replace(/:/g, '').substring(0, 4);
        const endClean = a.end_time.replace(/:/g, '').substring(0, 4);
        const startVal = parseInt(startClean);
        const endVal = parseInt(endClean);
        return timeVal >= startVal && timeVal <= endVal;
    });
    
    if (!matchingShift) {
        return { 
            status: 'error', 
            message: `Technician is not scheduled to work on ${getDayName(dayOfWeek)} at ${formatTime12h(hours, minutes)}.` 
        };
    }
    
    // 2. Buffer overlap check
    const currentBookingTime = parsedDate.getTime();
    const bufferMs = 90 * 60 * 1000;
    
    const conflictingBooking = employee.bookings?.find(b => {
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
            message: `Travel buffer conflict! Overlaps with booking at ${formatTime12h(confTime.getHours(), confTime.getMinutes())} (90-minute travel buffer violated).`
        };
    }
    
    return {
        status: 'success',
        message: `Technician is available! Works shift ${matchingShift.start_time.substring(0,5)} - ${matchingShift.end_time.substring(0,5)}.`
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
        return { status: 'error', message: 'End time must be after start time.' };
    }
    
    const employee = props.employees.find(e => e.id === empId);
    if (!employee) {
        return { status: 'idle', message: 'Select a technician.' };
    }
    
    const startVal = parseInt(start.replace(':', ''));
    const endVal = parseInt(end.replace(':', ''));
    
    const conflictingShift = employee.availabilities.find(a => {
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
            message: `This shift overlaps with an existing shift (${conflictingShift.start_time.substring(0,5)} - ${conflictingShift.end_time.substring(0,5)}) on ${getDayName(day)}.`
        };
    }
    
    return {
        status: 'success',
        message: 'Shift time is open and has no conflicts.'
    };
});
</script>

<template>
    <Head title="AI Dispatcher Dashboard" />

    <div class="bg-background text-foreground p-6 min-h-screen">
        <!-- Dashboard Top Header -->
        <div class="mb-8 flex flex-col md:flex-row items-center justify-between gap-4 border-b pb-6">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
                    <AppLogoIcon class="h-6 w-6 text-primary-foreground fill-current" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-foreground">
                        {{ tenant?.name ?? 'businesscalls' }}
                    </h1>
                    <div class="flex items-center gap-2 mt-1">
                        <p class="text-xs text-muted-foreground uppercase tracking-wider">
                            Active Plan:
                        </p>
                        <Badge variant="secondary" class="font-semibold uppercase tracking-widest text-[10px]">{{ tenant?.plan ?? 'Trial' }}</Badge>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 rounded-lg border bg-card px-3 py-1.5 text-xs font-medium shadow-xs">
                    <Settings class="h-4 w-4 text-muted-foreground animate-spin-slow" />
                    <span class="text-muted-foreground">Webhook Status:</span>
                    <Badge class="bg-emerald-500 hover:bg-emerald-500 text-white font-semibold text-[10px] py-0.5 px-2">Live</Badge>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Calls Received Card -->
            <Card class="transition-all hover:shadow-md">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <span class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Total Calls Managed</span>
                    <Phone class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold tracking-tight">{{ stats.calls }}</div>
                    <p class="text-[10px] text-muted-foreground uppercase tracking-widest mt-1">Real-time counter</p>
                </CardContent>
            </Card>

            <!-- Successful Bookings Card -->
            <Card class="transition-all hover:shadow-md">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <span class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Successful Bookings</span>
                    <CheckCircle class="h-4 w-4 text-emerald-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">{{ stats.success }}</div>
                    <p class="text-[10px] text-muted-foreground uppercase tracking-widest mt-1">Mascot State: Victory</p>
                </CardContent>
            </Card>

            <!-- Overlaps/Conflicts Card -->
            <Card class="transition-all hover:shadow-md">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <span class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Conflicts Blocked</span>
                    <XCircle class="h-4 w-4 text-rose-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold tracking-tight text-rose-600 dark:text-rose-400">{{ stats.conflicts }}</div>
                    <p class="text-[10px] text-muted-foreground uppercase tracking-widest mt-1">Overlap buffers enforced</p>
                </CardContent>
            </Card>
        </section>

        <!-- Main Layout Split -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- LEFT PANEL: Mascot Rendering and Technician Shifts (2/3 columns) -->
            <div class="lg:col-span-2 flex flex-col gap-8">
                
                <!-- Rive Mascot Container -->
                <Card class="shadow-sm">
                    <CardHeader class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b">
                        <div>
                            <CardTitle class="text-xl font-bold uppercase tracking-wider">AI Dispatcher Mascot</CardTitle>
                            <CardDescription class="text-xs font-medium uppercase tracking-widest">Interactive WebGL State machine</CardDescription>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <!-- Dev Animation State Simulator -->
                            <Button variant="outline" size="sm" @click="transitionMascot(0)" class="text-xs font-bold">Idle</Button>
                            <Button variant="secondary" size="sm" @click="transitionMascot(1)" class="text-xs font-bold bg-yellow-100 text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400">Scanning</Button>
                            <Button variant="secondary" size="sm" @click="transitionMascot(2)" class="text-xs font-bold bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400">Victory</Button>
                            <Button variant="secondary" size="sm" @click="transitionMascot(3)" class="text-xs font-bold bg-rose-100 text-rose-800 hover:bg-rose-200 dark:bg-rose-900/30 dark:text-rose-400">Conflict</Button>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <div class="flex flex-col md:flex-row items-center gap-8 justify-center">
                            <div class="w-full md:w-1/2 aspect-square max-w-[260px] bg-accent/35 rounded-xl border p-2">
                                <DispatcherMascot :state="mascotState" />
                            </div>
                            <div class="w-full md:w-1/2 flex flex-col justify-center gap-3">
                                <div class="bg-accent/40 border p-4 rounded-xl">
                                    <h3 class="text-sm font-semibold uppercase text-foreground flex items-center gap-2">
                                        <Activity class="h-4 w-4 text-primary" /> State Machine Rules
                                    </h3>
                                    <ul class="text-xs text-muted-foreground font-medium list-disc pl-4 mt-2 space-y-1">
                                        <li>State 0: Idle monitoring</li>
                                        <li>State 1: Real-time scan (incoming API tool Call)</li>
                                        <li>State 2: Happy validation (booking confirmation)</li>
                                        <li>State 3: Conflict response (overlaps or out-of-shift)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Active Technicians Dispatch Center -->
                <Card class="shadow-sm">
                    <CardHeader class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b">
                        <div>
                            <CardTitle class="text-xl font-bold uppercase tracking-wider">Technician Dispatch Center</CardTitle>
                            <CardDescription class="text-xs font-medium uppercase tracking-widest text-muted-foreground">Manage profiles, shifts, and visual schedules</CardDescription>
                        </div>
                        <div class="flex bg-accent/40 p-1 rounded-lg border">
                            <button 
                                @click="activeViewTab = 'timeline'"
                                :class="activeViewTab === 'timeline' ? 'bg-background text-foreground shadow-xs border' : 'text-muted-foreground hover:text-foreground'"
                                class="text-xs font-bold px-3 py-1.5 rounded-md transition-all cursor-pointer flex items-center gap-1.5"
                            >
                                <Calendar class="h-3.5 w-3.5" /> Weekly Scheduler
                            </button>
                            <button 
                                @click="activeViewTab = 'profiles'"
                                :class="activeViewTab === 'profiles' ? 'bg-background text-foreground shadow-xs border' : 'text-muted-foreground hover:text-foreground'"
                                class="text-xs font-bold px-3 py-1.5 rounded-md transition-all cursor-pointer flex items-center gap-1.5"
                            >
                                <UserIcon class="h-3.5 w-3.5" /> Profiles & Skills
                            </button>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <!-- TIMELINE VIEW -->
                        <div v-if="activeViewTab === 'timeline'" class="space-y-6">
                            <!-- Tech Select Tabs -->
                            <div class="flex flex-wrap gap-2 pb-2 border-b">
                                <button 
                                    v-for="employee in employees" 
                                    :key="employee.id"
                                    @click="activeTechId = employee.id"
                                    :class="activeTechId === employee.id ? 'bg-primary text-primary-foreground font-bold' : 'bg-secondary hover:bg-secondary/80 text-secondary-foreground font-semibold'"
                                    class="text-xs px-3.5 py-1.5 rounded-lg transition-all cursor-pointer shadow-sm"
                                >
                                    {{ employee.first_name }} {{ employee.last_name }}
                                </button>
                                <div v-if="employees.length === 0" class="text-xs font-semibold text-muted-foreground py-1">
                                    No technicians found.
                                </div>
                            </div>

                            <!-- Selected Tech Calendar Grid -->
                            <div v-if="activeTechId" class="space-y-4 text-foreground">
                                <div class="hidden md:grid grid-cols-12 gap-2 text-center text-[10px] font-bold uppercase tracking-wider text-muted-foreground pb-1 border-b">
                                    <div class="col-span-2 text-left">Day of Week</div>
                                    <div v-for="hour in hourlySlots" :key="hour" class="col-span-1 border-l font-mono">
                                        {{ hour }}
                                    </div>
                                </div>

                                <div 
                                    v-for="dayNum in daysOrder" 
                                    :key="dayNum"
                                    class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center py-2 border-b last:border-b-0"
                                >
                                    <!-- Day label -->
                                    <div class="col-span-1 md:col-span-2 font-bold text-xs flex flex-row md:flex-col justify-between items-center md:items-start">
                                        <span>{{ getDayName(dayNum) }}</span>
                                        <span class="text-[10px] text-muted-foreground font-medium">{{ currentWeekDates[dayNum] }}</span>
                                    </div>

                                    <!-- Grid columns for hours on mobile/desktop -->
                                    <div class="col-span-1 md:col-span-10 grid grid-cols-3 sm:grid-cols-6 md:grid-cols-11 gap-1.5">
                                        <div 
                                            v-for="hour in hourlySlots" 
                                            :key="hour"
                                            class="aspect-[2.5/1] md:aspect-auto md:h-12 rounded relative transition-all border flex flex-col justify-center items-center text-center overflow-hidden"
                                        >
                                            <!-- Desktop top label or mobile details -->
                                            <div class="md:hidden absolute top-1 left-1 text-[8px] font-mono text-muted-foreground">{{ hour }}</div>

                                            <template v-if="getBookingForSlot(activeEmployee, dayNum, hour)">
                                                <!-- Booked appointment or buffer -->
                                                <div 
                                                    v-if="getBookingForSlot(activeEmployee, dayNum, hour)?.type === 'exact'"
                                                    class="w-full h-full bg-blue-500/10 dark:bg-blue-500/5 border border-blue-500/35 text-blue-700 dark:text-blue-300 flex flex-col justify-between p-1 select-none"
                                                >
                                                    <div class="text-[7.5px] font-extrabold truncate w-full">
                                                        📞 {{ getBookingForSlot(activeEmployee, dayNum, hour)?.booking.customer_phone }}
                                                    </div>
                                                    <div class="text-[7px] text-blue-600/90 dark:text-blue-400/90 truncate w-full font-medium leading-none">
                                                        {{ getBookingForSlot(activeEmployee, dayNum, hour)?.booking.job_details }}
                                                    </div>
                                                    <button 
                                                        @click="cancelBooking(getBookingForSlot(activeEmployee, dayNum, hour)?.booking.id)"
                                                        class="absolute top-0.5 right-0.5 text-blue-500 hover:text-rose-600 transition-colors cursor-pointer"
                                                        title="Cancel Booking"
                                                    >
                                                        <Trash2 class="h-2 w-2" />
                                                    </button>
                                                </div>
                                                <div 
                                                    v-else
                                                    class="w-full h-full bg-slate-500/10 dark:bg-slate-400/5 border border-slate-500/20 text-slate-500 dark:text-slate-400 border-dotted flex items-center justify-center text-[8px] font-semibold tracking-tighter select-none"
                                                >
                                                    🚗 Buffer
                                                </div>
                                            </template>
                                            
                                            <template v-else-if="getShiftForSlot(activeEmployee, dayNum, hour)">
                                                <!-- On Shift - Open Slot to book -->
                                                <button 
                                                    @click="handleQuickBook(activeTechId, dayNum, hour)"
                                                    class="w-full h-full bg-emerald-500/5 hover:bg-emerald-500/15 dark:bg-emerald-500/5 dark:hover:bg-emerald-500/10 border border-emerald-500/25 border-dashed text-emerald-600 dark:text-emerald-400 rounded flex flex-col items-center justify-center gap-0.5 transition-all cursor-pointer font-bold group"
                                                    title="Quick Book Slot"
                                                >
                                                    <Plus class="h-3 w-3 text-emerald-500 group-hover:scale-125 transition-transform" />
                                                    <span class="text-[7.5px] uppercase tracking-wider">Book</span>
                                                </button>
                                            </template>
                                            
                                            <template v-else>
                                                <!-- Out of Shift - Click to add shift -->
                                                <button 
                                                    @click="handleQuickShift(activeTechId, dayNum, hour)"
                                                    class="w-full h-full bg-slate-100/60 dark:bg-slate-900/40 hover:bg-slate-200/80 dark:hover:bg-slate-900 border border-border border-dashed text-slate-400 dark:text-slate-600 flex flex-col items-center justify-center gap-0.5 transition-all cursor-pointer"
                                                    title="Technician Off Shift. Click to add shift."
                                                >
                                                    <span class="text-[8px] tracking-tight font-medium">Off Shift</span>
                                                    <span class="text-[7px] text-slate-400/70 hover:text-primary transition-colors">+ Shift</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PROFILES VIEW -->
                        <div v-else-if="activeViewTab === 'profiles'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div 
                                v-for="employee in employees" 
                                :key="employee.id"
                                class="bg-card p-4 rounded-xl border transition-all hover:shadow-xs flex flex-col justify-between"
                            >
                                <div>
                                    <div class="flex items-center justify-between gap-3 mb-3">
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-10 w-10 border">
                                                <AvatarFallback class="bg-accent text-accent-foreground font-semibold">
                                                    {{ employee.first_name[0] }}{{ employee.last_name[0] }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <h3 class="font-bold text-foreground leading-tight">{{ employee.first_name }} {{ employee.last_name }}</h3>
                                                <p class="text-xs text-muted-foreground">{{ employee.phone }}</p>
                                            </div>
                                        </div>
                                        <Button 
                                            variant="outline" 
                                            size="sm" 
                                            class="h-7 text-[10px] font-semibold gap-1 px-2.5"
                                            @click="openShiftModal(employee.id, employee.first_name, employee.last_name)"
                                        >
                                            <Plus class="h-3 w-3" /> Add Shift
                                        </Button>
                                    </div>

                                    <!-- Skills Badges -->
                                    <div class="mb-3">
                                        <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground mb-1">Skills Profile</div>
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
                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground mb-1">Weekly Shifts</div>
                                    <div class="space-y-1 max-h-[120px] overflow-y-auto pr-1">
                                        <div 
                                            v-for="avail in employee.availabilities" 
                                            :key="avail.id"
                                            class="flex items-center justify-between text-[11px] font-medium text-muted-foreground bg-accent/20 px-2 py-1 rounded border border-border group"
                                        >
                                            <span>{{ getDayName(avail.day_of_week) }}</span>
                                            <div class="flex items-center gap-2">
                                                <span class="text-foreground/90 font-mono">{{ avail.start_time.substring(0,5) }} - {{ avail.end_time.substring(0,5) }}</span>
                                                <button 
                                                    @click="deleteShift(avail.id)"
                                                    class="text-muted-foreground hover:text-rose-600 transition-colors opacity-70 hover:opacity-100 cursor-pointer"
                                                    title="Delete Shift"
                                                >
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                        <div v-if="employee.availabilities.length === 0" class="text-xs font-semibold text-amber-600/90 py-1 italic">
                                            No active shifts registered.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="employees.length === 0" class="col-span-2 text-center py-6 font-semibold text-muted-foreground">
                                No technician profiles configured. Please seed the database.
                            </div>
                        </div>
                    </CardContent>
                </Card>

            </div>

            <!-- RIGHT PANEL: Live Feed Logs and Active Bookings (1/3 column) -->
            <div class="flex flex-col gap-8">
                
                <!-- Live Event Logs Feed -->
                <Card class="bg-slate-950 border border-slate-800 text-emerald-400 flex flex-col h-[350px]">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3 border-b border-slate-800/60">
                        <CardTitle class="text-sm font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-2">
                            <Activity class="h-4 w-4 animate-pulse text-emerald-400" /> Dispatch Terminal
                        </CardTitle>
                        <Badge variant="outline" class="text-[9px] font-bold border-emerald-500/30 text-emerald-400 bg-emerald-500/10 uppercase px-1.5 py-0.5 rounded">
                            WebSocket
                        </Badge>
                    </CardHeader>
                    <CardContent class="flex-1 overflow-y-auto space-y-3 font-mono text-xs pt-4 pr-1 scrollbar-thin">
                        <div 
                            v-for="log in liveFeed" 
                            :key="log.id"
                            :class="{
                                'text-yellow-400': log.type === 'searching',
                                'text-emerald-400': log.type === 'success',
                                'text-rose-400': log.type === 'error'
                            }"
                            class="pb-2 border-b border-slate-900"
                        >
                            <span class="text-slate-600">[{{ log.timestamp }}]</span>
                            <span class="font-bold uppercase ml-1">[{{ log.type }}]</span>
                            <p class="mt-0.5 text-slate-300 leading-tight">{{ log.message }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Bookings Feed -->
                <Card class="flex-1 flex flex-col shadow-sm">
                    <CardHeader class="pb-3 border-b flex flex-row items-center justify-between space-y-0 gap-4">
                        <div>
                            <CardTitle class="text-lg font-bold uppercase tracking-wider flex items-center gap-2">
                                <Calendar class="h-5 w-5 text-muted-foreground" /> Appointments
                            </CardTitle>
                            <CardDescription class="text-[10px] font-medium uppercase tracking-widest text-muted-foreground">
                                Confirmed bookings
                            </CardDescription>
                        </div>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            class="h-8 text-[11px] font-bold gap-1 px-3"
                            @click="openBookingModal"
                        >
                            <Plus class="h-3.5 w-3.5" /> Book Slot
                        </Button>
                    </CardHeader>
                    <CardContent class="flex-1 overflow-y-auto pt-4 space-y-4 max-h-[450px] pr-1">
                        <div 
                            v-for="booking in liveBookings" 
                            :key="booking.id"
                            class="bg-accent/15 p-3 rounded-lg border transition-all hover:bg-accent/25 relative group"
                        >
                            <!-- Cancel Button overlay -->
                            <button
                                @click="cancelBooking(booking.id)"
                                class="absolute top-2.5 right-2.5 text-muted-foreground hover:text-rose-600 transition-colors opacity-70 hover:opacity-100 cursor-pointer"
                                title="Cancel Booking"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>

                            <div class="flex justify-between items-start mb-2 pr-6">
                                <Badge variant="secondary" class="text-[9px] font-bold uppercase bg-emerald-100/50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded">
                                    {{ booking.status }}
                                </Badge>
                                <div class="text-[11px] font-medium text-muted-foreground flex items-center gap-1">
                                    <Clock class="h-3 w-3" /> 
                                    {{ new Date(booking.scheduled_start).toLocaleString([], {month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) }}
                                </div>
                            </div>
                            
                            <div class="text-xs font-bold text-foreground mb-1">
                                Customer: {{ booking.customer_phone }}
                            </div>
                            <div class="text-xs font-medium text-muted-foreground mb-2 italic">
                                "{{ booking.job_details }}"
                            </div>
                            
                            <Separator class="my-2" />
                            
                            <div class="text-[10px] font-semibold uppercase text-muted-foreground flex items-center gap-1.5">
                                <UserIcon class="h-3 w-3 text-muted-foreground" /> Tech: 
                                <span class="text-foreground font-bold">{{ booking.employee.first_name }} {{ booking.employee.last_name }}</span>
                            </div>
                        </div>
                        <div v-if="liveBookings.length === 0" class="text-center py-8 font-semibold text-muted-foreground text-xs">
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
                        Assign new weekly shift hours for {{ selectedEmployeeName }}.
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
                        <p v-if="shiftForm.errors.day_of_week" class="text-xs text-rose-500 mt-1">{{ shiftForm.errors.day_of_week }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="start_time">Start Time</Label>
                            <Input id="start_time" type="time" v-model="shiftForm.start_time" />
                            <p v-if="shiftForm.errors.start_time" class="text-xs text-rose-500 mt-1">{{ shiftForm.errors.start_time }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="end_time">End Time</Label>
                            <Input id="end_time" type="time" v-model="shiftForm.end_time" />
                            <p v-if="shiftForm.errors.end_time" class="text-xs text-rose-500 mt-1">{{ shiftForm.errors.end_time }}</p>
                        </div>
                    </div>

                    <!-- Live Shift Validation Status -->
                    <div 
                        v-if="shiftValidation.status !== 'idle'" 
                        :class="{
                            'bg-emerald-500/10 border-emerald-500/25 text-emerald-800 dark:text-emerald-400': shiftValidation.status === 'success',
                            'bg-rose-500/10 border-rose-500/25 text-rose-800 dark:text-rose-400': shiftValidation.status === 'error'
                        }"
                        class="p-3 rounded-lg border text-xs font-semibold flex items-center gap-2 mt-2"
                    >
                        <CheckCircle v-if="shiftValidation.status === 'success'" class="h-4 w-4 shrink-0 text-emerald-500" />
                        <XCircle v-else class="h-4 w-4 shrink-0 text-rose-500" />
                        <span>{{ shiftValidation.message }}</span>
                    </div>

                    <DialogFooter class="pt-4 border-t">
                        <Button type="button" variant="outline" @click="isShiftModalOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="shiftForm.processing || shiftValidation.status === 'error'">Add Shift</Button>
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
                        Manually book an appointment. Select a date and click an available time slot.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitBooking" class="space-y-4 pt-4">
                    <div class="space-y-2">
                        <Label for="employee_id">Assign Technician</Label>
                        <Select v-model="bookingForm.employee_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Select a technician" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="emp in employees" :key="emp.id" :value="emp.id.toString()">
                                    {{ emp.first_name }} {{ emp.last_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="bookingForm.errors.employee_id" class="text-xs text-rose-500 mt-1">{{ bookingForm.errors.employee_id }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="customer_phone">Customer Phone</Label>
                        <Input id="customer_phone" type="text" placeholder="(555) 019-2834" v-model="bookingForm.customer_phone" />
                        <p v-if="bookingForm.errors.customer_phone" class="text-xs text-rose-500 mt-1">{{ bookingForm.errors.customer_phone }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 border-y py-4 my-2">
                        <!-- Booking Date Picker -->
                        <div class="space-y-2">
                            <Label for="booking_date">Target Date</Label>
                            <Input id="booking_date" type="date" v-model="selectedBookingDate" />
                        </div>

                        <!-- Availability Slots Grid -->
                        <div class="space-y-2">
                            <Label>Available Time Slots for {{ selectedBookingDate }}</Label>
                            <div v-if="bookingSlotsForSelectedDate.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-1.5 p-2 border rounded-lg bg-accent/10 max-h-[140px] overflow-y-auto pr-1">
                                <button
                                    v-for="slot in bookingSlotsForSelectedDate"
                                    :key="slot.time"
                                    type="button"
                                    :disabled="slot.status !== 'available'"
                                    :class="{
                                        'bg-emerald-500/10 border-emerald-500/30 hover:bg-emerald-500/20 text-emerald-800 dark:text-emerald-400': slot.status === 'available' && bookingForm.scheduled_start !== `${selectedBookingDate}T${slot.time}`,
                                        'bg-emerald-500 text-white font-bold border-emerald-600 shadow-xs': slot.status === 'available' && bookingForm.scheduled_start === `${selectedBookingDate}T${slot.time}`,
                                        'bg-rose-500/5 border-rose-500/15 text-rose-500 opacity-60 line-through cursor-not-allowed': slot.status === 'booked',
                                        'bg-yellow-500/5 border-yellow-500/15 text-yellow-600 opacity-60 line-through cursor-not-allowed': slot.status === 'buffer-conflict',
                                        'bg-slate-100/50 dark:bg-slate-900/50 border-slate-200 text-slate-400 opacity-40 cursor-not-allowed': slot.status === 'off-shift'
                                    }"
                                    class="text-[10px] font-bold px-2 py-1.5 rounded-lg border text-center transition-all cursor-pointer flex flex-col justify-center items-center"
                                    :title="slot.message"
                                    @click="selectSlotTime(slot.time)"
                                >
                                    <span>{{ slot.time }}</span>
                                    <span class="text-[7px] font-normal uppercase leading-none tracking-tighter mt-0.5">{{ slot.label }}</span>
                                </button>
                            </div>
                            <div v-else class="text-xs font-semibold text-rose-500 p-2 border rounded-lg bg-rose-500/5 text-center">
                                No active shifts scheduled for this technician on this day.
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Selected Appointment Time</Label>
                        <div class="p-3 border rounded-lg bg-muted/30 font-mono text-xs flex items-center justify-between">
                            <span v-if="bookingForm.scheduled_start" class="font-bold text-foreground">
                                {{ new Date(bookingForm.scheduled_start).toLocaleString([], {month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'}) }}
                            </span>
                            <span v-else class="text-muted-foreground italic">No time slot selected yet</span>
                        </div>
                        <p v-if="bookingForm.errors.scheduled_start" class="text-xs text-rose-500 mt-1">{{ bookingForm.errors.scheduled_start }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="job_details">Job Details</Label>
                        <Textarea id="job_details" placeholder="Leaky copper pipe repair in kitchen" v-model="bookingForm.job_details" />
                        <p v-if="bookingForm.errors.job_details" class="text-xs text-rose-500 mt-1">{{ bookingForm.errors.job_details }}</p>
                    </div>

                    <!-- Live Booking Validation Status -->
                    <div 
                        v-if="bookingValidation.status !== 'idle'" 
                        :class="{
                            'bg-emerald-500/10 border-emerald-500/25 text-emerald-800 dark:text-emerald-400': bookingValidation.status === 'success',
                            'bg-yellow-500/10 border-yellow-500/25 text-yellow-800 dark:text-yellow-400': bookingValidation.status === 'warning',
                            'bg-rose-500/10 border-rose-500/25 text-rose-800 dark:text-rose-400': bookingValidation.status === 'error'
                        }"
                        class="p-3 rounded-lg border text-xs font-semibold flex items-center gap-2 mt-2"
                    >
                        <CheckCircle v-if="bookingValidation.status === 'success'" class="h-4 w-4 shrink-0 text-emerald-500" />
                        <Activity v-else-if="bookingValidation.status === 'warning'" class="h-4 w-4 shrink-0 text-yellow-500 animate-pulse" />
                        <XCircle v-else class="h-4 w-4 shrink-0 text-rose-500" />
                        <span>{{ bookingValidation.message }}</span>
                    </div>

                    <DialogFooter class="pt-4 border-t">
                        <Button type="button" variant="outline" @click="isBookingModalOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="bookingForm.processing || bookingValidation.status !== 'success'">Confirm Booking</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
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
