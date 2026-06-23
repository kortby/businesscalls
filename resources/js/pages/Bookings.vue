<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { store as storeBooking, update as updateBooking, destroy as destroyBooking } from '@/routes/bookings';
import { 
    Card, 
    CardHeader, 
    CardTitle, 
    CardDescription, 
    CardContent 
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
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
    Calendar, 
    Clock, 
    User as UserIcon, 
    Phone, 
    Plus, 
    Trash2, 
    Edit2,
    CheckCircle,
    XCircle,
    Activity,
    Search
} from '@lucide/vue';

// Define Props from BookingController@index
const props = defineProps<{
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
            id: number;
            first_name: string;
            last_name: string;
        };
    }>;
}>();

// Search and Filter state
const searchQuery = ref('');
const filterTechId = ref<string>('all');

const filteredBookings = computed(() => {
    return props.bookings.filter(b => {
        const matchesSearch = b.customer_phone.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                              b.job_details.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesTech = filterTechId.value === 'all' || b.employee.id.toString() === filterTechId.value;
        return matchesSearch && matchesTech;
    });
});

// Modals State
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const selectedBooking = ref<any>(null);

// Forms
const createForm = useForm({
    employee_id: '',
    customer_phone: '',
    job_details: '',
    scheduled_start: '',
});

const editForm = useForm({
    employee_id: '',
    customer_phone: '',
    job_details: '',
    scheduled_start: '',
});

const openCreateModal = () => {
    createForm.reset();
    if (props.employees.length > 0) {
        createForm.employee_id = props.employees[0].id.toString();
    }
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    selectedCreateBookingDate.value = `${yyyy}-${mm}-${dd}`;
    
    isCreateOpen.value = true;
};

const openEditModal = (booking: any) => {
    selectedBooking.value = booking;
    
    // Format datetime-local: YYYY-MM-DDTHH:MM
    const date = new Date(booking.scheduled_start);
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    const hh = String(date.getHours()).padStart(2, '0');
    const min = String(date.getMinutes()).padStart(2, '0');
    const formattedDate = `${yyyy}-${mm}-${dd}T${hh}:${min}`;

    selectedEditBookingDate.value = `${yyyy}-${mm}-${dd}`;

    editForm.employee_id = booking.employee.id.toString();
    editForm.customer_phone = booking.customer_phone;
    editForm.job_details = booking.job_details;
    editForm.scheduled_start = formattedDate;
    
    isEditOpen.value = true;
};

const submitCreate = () => {
    createForm.post(storeBooking.url(), {
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
        }
    });
};

const submitEdit = () => {
    editForm.put(updateBooking.url(selectedBooking.value.id), {
        onSuccess: () => {
            isEditOpen.value = false;
            editForm.reset();
        }
    });
};

const cancelBooking = (id: number) => {
    if (confirm('Are you sure you want to cancel this booking appointment?')) {
        router.delete(destroyBooking.url(id));
    }
};

// --- Time Helper Methods ---
const getDayName = (dayNum: number): string => {
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return days[dayNum] ?? '';
};

const formatTime12h = (hours: number, minutes: number): string => {
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hr = hours % 12 || 12;
    const min = String(minutes).padStart(2, '0');
    return `${hr}:${min} ${ampm}`;
};

// --- Live Validations ---
const validateBooking = (employeeIdStr: string, scheduledStartStr: string, excludeBookingId: number | null) => {
    const empId = parseInt(employeeIdStr);
    const dateVal = scheduledStartStr;
    if (!empId || !dateVal) {
        return { status: 'idle', message: 'Enter date and time details to check availability.' };
    }
    
    const employee = props.employees.find(e => e.id === empId);
    if (!employee) {
        return { status: 'idle', message: 'Select a technician.' };
    }
    
    const parsedDate = new Date(dateVal);
    const dayOfWeek = parsedDate.getDay();
    
    const hours = parsedDate.getHours();
    const minutes = parsedDate.getMinutes();
    const timeVal = hours * 100 + minutes;
    
    // 1. Shift check
    const activeShifts = (employee.availabilities || []).filter(a => a.day_of_week === dayOfWeek && a.is_active);
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
        if (b.status !== 'booked' || b.id === excludeBookingId) {
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
};

// --- Availability Visual Slot Picker State & Helpers ---
const selectedCreateBookingDate = ref<string>('');
const selectedEditBookingDate = ref<string>('');

const createBookingSlots = computed(() => {
    const empId = parseInt(createForm.employee_id);
    const dateStr = selectedCreateBookingDate.value;
    if (!empId || !dateStr) {
        return [];
    }
    
    const employee = props.employees.find(e => e.id === empId);
    if (!employee) {
        return [];
    }
    
    const parsedDate = new Date(dateStr + 'T00:00:00');
    const dayOfWeek = parsedDate.getDay();
    
    const dayShifts = (employee.availabilities || []).filter(a => a.day_of_week === dayOfWeek && a.is_active);
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

const selectCreateSlotTime = (hourStr: string) => {
    createForm.scheduled_start = `${selectedCreateBookingDate.value}T${hourStr}`;
};

const editBookingSlots = computed(() => {
    const empId = parseInt(editForm.employee_id);
    const dateStr = selectedEditBookingDate.value;
    if (!empId || !dateStr) {
        return [];
    }
    
    const employee = props.employees.find(e => e.id === empId);
    if (!employee) {
        return [];
    }
    
    const parsedDate = new Date(dateStr + 'T00:00:00');
    const dayOfWeek = parsedDate.getDay();
    
    const dayShifts = (employee.availabilities || []).filter(a => a.day_of_week === dayOfWeek && a.is_active);
    const hours = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
    const slots = [];
    
    const excludeBookingId = selectedBooking.value?.id || null;
    
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
            if (b.status !== 'booked' || b.id === excludeBookingId) {
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

const selectEditSlotTime = (hourStr: string) => {
    editForm.scheduled_start = `${selectedEditBookingDate.value}T${hourStr}`;
};

// Synchronize dates when forms' scheduled starts are edited manually
watch(() => createForm.scheduled_start, (newVal) => {
    if (newVal && newVal.includes('T')) {
        const datePart = newVal.split('T')[0];
        if (datePart && datePart !== selectedCreateBookingDate.value) {
            selectedCreateBookingDate.value = datePart;
        }
    }
});

watch(() => editForm.scheduled_start, (newVal) => {
    if (newVal && newVal.includes('T')) {
        const datePart = newVal.split('T')[0];
        if (datePart && datePart !== selectedEditBookingDate.value) {
            selectedEditBookingDate.value = datePart;
        }
    }
});

const createValidation = computed(() => {
    return validateBooking(createForm.employee_id, createForm.scheduled_start, null);
});

const editValidation = computed(() => {
    return validateBooking(editForm.employee_id, editForm.scheduled_start, selectedBooking.value?.id || null);
});
</script>

<template>
    <Head title="Bookings Management" />

    <div class="bg-background text-foreground p-6 min-h-screen">
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b pb-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-foreground">Appointments Directory</h1>
                <p class="text-xs text-muted-foreground mt-1 uppercase tracking-wider">
                    Schedule, edit, and cancel customer dispatch bookings
                </p>
            </div>
            
            <Button @click="openCreateModal" class="font-bold flex items-center gap-1.5 shadow-sm">
                <Plus class="h-4 w-4" /> Schedule Booking
            </Button>
        </div>

        <!-- Filters Bar -->
        <div class="flex flex-col sm:flex-row gap-4 mb-6 items-center">
            <div class="relative w-full sm:max-w-xs">
                <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    type="text" 
                    placeholder="Search customer, job details..." 
                    class="pl-9"
                    v-model="searchQuery"
                />
            </div>
            <div class="w-full sm:max-w-xs">
                <Select v-model="filterTechId">
                    <SelectTrigger>
                        <SelectValue placeholder="Filter by Technician" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Technicians</SelectItem>
                        <SelectItem v-for="emp in employees" :key="emp.id" :value="emp.id.toString()">
                            {{ emp.first_name }} {{ emp.last_name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Bookings Directory -->
        <Card class="shadow-sm">
            <CardHeader class="pb-3 border-b">
                <CardTitle class="text-lg font-bold uppercase tracking-wider flex items-center gap-2">
                    <Calendar class="h-5 w-5 text-muted-foreground" /> Dispatch Log
                </CardTitle>
                <CardDescription class="text-[10px] font-medium uppercase tracking-widest text-muted-foreground">
                    All currently active service calls in system
                </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-muted-foreground">
                        <thead class="text-xs uppercase bg-accent/40 text-foreground border-b font-bold tracking-wider">
                            <tr>
                                <th scope="col" class="px-6 py-4">Customer</th>
                                <th scope="col" class="px-6 py-4">Technician</th>
                                <th scope="col" class="px-6 py-4">Job Details</th>
                                <th scope="col" class="px-6 py-4">Scheduled Time</th>
                                <th scope="col" class="px-6 py-4">Status</th>
                                <th scope="col" class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border text-foreground font-medium">
                            <tr 
                                v-for="booking in filteredBookings" 
                                :key="booking.id"
                                class="hover:bg-accent/10 transition-colors"
                            >
                                <td class="px-6 py-4 font-bold flex items-center gap-2">
                                    <Phone class="h-3.5 w-3.5 text-muted-foreground" />
                                    {{ booking.customer_phone }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="flex items-center gap-1.5 font-semibold text-muted-foreground">
                                        <UserIcon class="h-3.5 w-3.5 text-primary/70" />
                                        <span class="text-foreground font-bold">{{ booking.employee.first_name }} {{ booking.employee.last_name }}</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs italic text-muted-foreground">
                                    "{{ booking.job_details }}"
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-muted-foreground flex items-center gap-1.5 mt-1 border-none">
                                    <Clock class="h-3.5 w-3.5 text-muted-foreground" />
                                    {{ new Date(booking.scheduled_start).toLocaleString([], {month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'}) }}
                                </td>
                                <td class="px-6 py-4">
                                    <Badge variant="secondary" class="bg-emerald-100/60 text-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-500/20 font-bold uppercase text-[9px] px-2 py-0.5 rounded">
                                        {{ booking.status }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button 
                                            variant="outline" 
                                            size="sm" 
                                            class="h-8 px-2.5 font-bold"
                                            @click="openEditModal(booking)"
                                        >
                                            <Edit2 class="h-3.5 w-3.5" />
                                        </Button>
                                        <Button 
                                            variant="outline" 
                                            size="sm" 
                                            class="h-8 px-2.5 font-bold text-rose-600 hover:text-rose-700 dark:hover:text-rose-500"
                                            @click="cancelBooking(booking.id)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredBookings.length === 0">
                                <td colspan="6" class="text-center py-8 font-semibold text-muted-foreground text-xs">
                                    No matching appointments found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- --- CREATE BOOKING DIALOG --- -->
        <Dialog v-model:open="isCreateOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Schedule Appointment</DialogTitle>
                    <DialogDescription>
                        Book a new service appointment. Technician schedules and travel buffer will be validated in real-time.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitCreate" class="space-y-4 pt-4">
                    <div class="space-y-2">
                        <Label for="employee_id">Assign Technician</Label>
                        <Select v-model="createForm.employee_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Select a technician" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="emp in employees" :key="emp.id" :value="emp.id.toString()">
                                    {{ emp.first_name }} {{ emp.last_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="createForm.errors.employee_id" class="text-xs text-rose-500 mt-1">{{ createForm.errors.employee_id }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="customer_phone">Customer Phone</Label>
                        <Input id="customer_phone" type="text" placeholder="(555) 019-2834" v-model="createForm.customer_phone" />
                        <p v-if="createForm.errors.customer_phone" class="text-xs text-rose-500 mt-1">{{ createForm.errors.customer_phone }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 border-y py-4 my-2">
                        <!-- Booking Date Picker -->
                        <div class="space-y-2">
                            <Label for="create_booking_date">Target Date</Label>
                            <Input id="create_booking_date" type="date" v-model="selectedCreateBookingDate" />
                        </div>

                        <!-- Availability Slots Grid -->
                        <div class="space-y-2">
                            <Label>Available Time Slots for {{ selectedCreateBookingDate }}</Label>
                            <div v-if="createBookingSlots.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-1.5 p-2 border rounded-lg bg-accent/10 max-h-[140px] overflow-y-auto pr-1">
                                <button
                                    v-for="slot in createBookingSlots"
                                    :key="slot.time"
                                    type="button"
                                    :disabled="slot.status !== 'available'"
                                    :class="{
                                        'bg-emerald-500/10 border-emerald-500/30 hover:bg-emerald-500/20 text-emerald-800 dark:text-emerald-400': slot.status === 'available' && createForm.scheduled_start !== `${selectedCreateBookingDate}T${slot.time}`,
                                        'bg-emerald-500 text-white font-bold border-emerald-600 shadow-xs': slot.status === 'available' && createForm.scheduled_start === `${selectedCreateBookingDate}T${slot.time}`,
                                        'bg-rose-500/5 border-rose-500/15 text-rose-500 opacity-60 line-through cursor-not-allowed': slot.status === 'booked',
                                        'bg-yellow-500/5 border-yellow-500/15 text-yellow-600 opacity-60 line-through cursor-not-allowed': slot.status === 'buffer-conflict',
                                        'bg-slate-100/50 dark:bg-slate-900/50 border-slate-200 text-slate-400 opacity-40 cursor-not-allowed': slot.status === 'off-shift'
                                    }"
                                    class="text-[10px] font-bold px-2 py-1.5 rounded-lg border text-center transition-all cursor-pointer flex flex-col justify-center items-center"
                                    :title="slot.message"
                                    @click="selectCreateSlotTime(slot.time)"
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
                            <span v-if="createForm.scheduled_start" class="font-bold text-foreground">
                                {{ new Date(createForm.scheduled_start).toLocaleString([], {month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'}) }}
                            </span>
                            <span v-else class="text-muted-foreground italic">No time slot selected yet</span>
                        </div>
                        <p v-if="createForm.errors.scheduled_start" class="text-xs text-rose-500 mt-1">{{ createForm.errors.scheduled_start }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="job_details">Job Details</Label>
                        <Textarea id="job_details" placeholder="Leaky copper pipe repair in kitchen" v-model="createForm.job_details" />
                        <p v-if="createForm.errors.job_details" class="text-xs text-rose-500 mt-1">{{ createForm.errors.job_details }}</p>
                    </div>

                    <!-- Live validation alert -->
                    <div 
                        v-if="createValidation.status !== 'idle'" 
                        :class="{
                            'bg-emerald-500/10 border-emerald-500/25 text-emerald-800 dark:text-emerald-400': createValidation.status === 'success',
                            'bg-yellow-500/10 border-yellow-500/25 text-yellow-800 dark:text-yellow-400': createValidation.status === 'warning',
                            'bg-rose-500/10 border-rose-500/25 text-rose-800 dark:text-rose-400': createValidation.status === 'error'
                        }"
                        class="p-3 rounded-lg border text-xs font-semibold flex items-center gap-2 mt-2"
                    >
                        <CheckCircle v-if="createValidation.status === 'success'" class="h-4 w-4 shrink-0 text-emerald-500" />
                        <Activity v-else-if="createValidation.status === 'warning'" class="h-4 w-4 shrink-0 text-yellow-500 animate-pulse" />
                        <XCircle v-else class="h-4 w-4 shrink-0 text-rose-500" />
                        <span>{{ createValidation.message }}</span>
                    </div>

                    <DialogFooter class="pt-4 border-t">
                        <Button type="button" variant="outline" @click="isCreateOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="createForm.processing || createValidation.status !== 'success'">Confirm Booking</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- --- EDIT BOOKING DIALOG --- -->
        <Dialog v-model:open="isEditOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Edit Appointment</DialogTitle>
                    <DialogDescription>
                        Modify scheduled date/time, description or assigned technician.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitEdit" class="space-y-4 pt-4">
                    <div class="space-y-2">
                        <Label for="edit_employee_id">Assign Technician</Label>
                        <Select v-model="editForm.employee_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Select a technician" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="emp in employees" :key="emp.id" :value="emp.id.toString()">
                                    {{ emp.first_name }} {{ emp.last_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="editForm.errors.employee_id" class="text-xs text-rose-500 mt-1">{{ editForm.errors.employee_id }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit_customer_phone">Customer Phone</Label>
                        <Input id="edit_customer_phone" type="text" v-model="editForm.customer_phone" />
                        <p v-if="editForm.errors.customer_phone" class="text-xs text-rose-500 mt-1">{{ editForm.errors.customer_phone }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 border-y py-4 my-2">
                        <!-- Booking Date Picker -->
                        <div class="space-y-2">
                            <Label for="edit_booking_date">Target Date</Label>
                            <Input id="edit_booking_date" type="date" v-model="selectedEditBookingDate" />
                        </div>

                        <!-- Availability Slots Grid -->
                        <div class="space-y-2">
                            <Label>Available Time Slots for {{ selectedEditBookingDate }}</Label>
                            <div v-if="editBookingSlots.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-1.5 p-2 border rounded-lg bg-accent/10 max-h-[140px] overflow-y-auto pr-1">
                                <button
                                    v-for="slot in editBookingSlots"
                                    :key="slot.time"
                                    type="button"
                                    :disabled="slot.status !== 'available'"
                                    :class="{
                                        'bg-emerald-500/10 border-emerald-500/30 hover:bg-emerald-500/20 text-emerald-800 dark:text-emerald-400': slot.status === 'available' && editForm.scheduled_start !== `${selectedEditBookingDate}T${slot.time}`,
                                        'bg-emerald-500 text-white font-bold border-emerald-600 shadow-xs': slot.status === 'available' && editForm.scheduled_start === `${selectedEditBookingDate}T${slot.time}`,
                                        'bg-rose-500/5 border-rose-500/15 text-rose-500 opacity-60 line-through cursor-not-allowed': slot.status === 'booked',
                                        'bg-yellow-500/5 border-yellow-500/15 text-yellow-600 opacity-60 line-through cursor-not-allowed': slot.status === 'buffer-conflict',
                                        'bg-slate-100/50 dark:bg-slate-900/50 border-slate-200 text-slate-400 opacity-40 cursor-not-allowed': slot.status === 'off-shift'
                                    }"
                                    class="text-[10px] font-bold px-2 py-1.5 rounded-lg border text-center transition-all cursor-pointer flex flex-col justify-center items-center"
                                    :title="slot.message"
                                    @click="selectEditSlotTime(slot.time)"
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
                            <span v-if="editForm.scheduled_start" class="font-bold text-foreground">
                                {{ new Date(editForm.scheduled_start).toLocaleString([], {month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'}) }}
                            </span>
                            <span v-else class="text-muted-foreground italic">No time slot selected yet</span>
                        </div>
                        <p v-if="editForm.errors.scheduled_start" class="text-xs text-rose-500 mt-1">{{ editForm.errors.scheduled_start }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit_job_details">Job Details</Label>
                        <Textarea id="edit_job_details" v-model="editForm.job_details" />
                        <p v-if="editForm.errors.job_details" class="text-xs text-rose-500 mt-1">{{ editForm.errors.job_details }}</p>
                    </div>

                    <!-- Live validation alert -->
                    <div 
                        v-if="editValidation.status !== 'idle'" 
                        :class="{
                            'bg-emerald-500/10 border-emerald-500/25 text-emerald-800 dark:text-emerald-400': editValidation.status === 'success',
                            'bg-yellow-500/10 border-yellow-500/25 text-yellow-800 dark:text-yellow-400': editValidation.status === 'warning',
                            'bg-rose-500/10 border-rose-500/25 text-rose-800 dark:text-rose-400': editValidation.status === 'error'
                        }"
                        class="p-3 rounded-lg border text-xs font-semibold flex items-center gap-2 mt-2"
                    >
                        <CheckCircle v-if="editValidation.status === 'success'" class="h-4 w-4 shrink-0 text-emerald-500" />
                        <Activity v-else-if="editValidation.status === 'warning'" class="h-4 w-4 shrink-0 text-yellow-500 animate-pulse" />
                        <XCircle v-else class="h-4 w-4 shrink-0 text-rose-500" />
                        <span>{{ editValidation.message }}</span>
                    </div>

                    <DialogFooter class="pt-4 border-t">
                        <Button type="button" variant="outline" @click="isEditOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="editForm.processing || editValidation.status !== 'success'">Save Changes</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
