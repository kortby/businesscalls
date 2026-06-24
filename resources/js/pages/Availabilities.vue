<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Calendar,
    Clock,
    User as UserIcon,
    Plus,
    Trash2,
    Edit2,
    CheckCircle,
    XCircle,
} from '@lucide/vue';
import { ref, computed } from 'vue';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    store as storeAvailability,
    update as updateAvailability,
    destroy as destroyAvailability,
} from '@/routes/availabilities';

// Define Props from AvailabilityController@index
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
    }>;
    availabilities: Array<{
        id: number;
        day_of_week: number;
        start_time: string;
        end_time: string;
        is_active: boolean;
        employee: {
            id: number;
            first_name: string;
            last_name: string;
        };
    }>;
}>();

// Filter state
const filterTechId = ref<string>('all');

const filteredAvailabilities = computed(() => {
    return props.availabilities.filter((a) => {
        return (
            filterTechId.value === 'all' ||
            a.employee.id.toString() === filterTechId.value
        );
    });
});

// Modals State
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const selectedAvailability = ref<any>(null);

// Forms
const createForm = useForm({
    employee_id: '',
    day_of_week: '1', // Default Monday
    start_time: '08:00',
    end_time: '17:00',
    is_active: true,
});

const editForm = useForm({
    employee_id: '',
    day_of_week: '1',
    start_time: '08:00',
    end_time: '17:00',
    is_active: true,
});

const openCreateModal = () => {
    createForm.reset();

    if (props.employees.length > 0) {
        createForm.employee_id = props.employees[0].id.toString();
    }

    isCreateOpen.value = true;
};

const openEditModal = (avail: any) => {
    selectedAvailability.value = avail;

    // Format times "HH:MM:SS" -> "HH:MM"
    const cleanStart = avail.start_time.substring(0, 5);
    const cleanEnd = avail.end_time.substring(0, 5);

    editForm.employee_id = avail.employee.id.toString();
    editForm.day_of_week = avail.day_of_week.toString();
    editForm.start_time = cleanStart;
    editForm.end_time = cleanEnd;
    editForm.is_active = avail.is_active;

    isEditOpen.value = true;
};

const submitCreate = () => {
    createForm.post(storeAvailability.url(), {
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
        },
    });
};

const submitEdit = () => {
    editForm.put(updateAvailability.url(selectedAvailability.value.id), {
        onSuccess: () => {
            isEditOpen.value = false;
            editForm.reset();
        },
    });
};

const deleteShift = (id: number) => {
    if (confirm('Are you sure you want to delete this shift availability?')) {
        router.delete(destroyAvailability.url(id));
    }
};

// --- Helpers ---
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

// --- Live Validations ---
const validateShift = (
    employeeIdStr: string,
    dayOfWeekStr: string,
    startTime: string,
    endTime: string,
    excludeAvailId: number | null,
) => {
    const empId = parseInt(employeeIdStr);
    const day = parseInt(dayOfWeekStr);

    if (!empId || isNaN(day) || !startTime || !endTime) {
        return { status: 'idle', message: 'Enter start and end times.' };
    }

    if (startTime >= endTime) {
        return {
            status: 'error',
            message: 'End time must be after start time.',
        };
    }

    const employee = props.employees.find((e) => e.id === empId);

    if (!employee) {
        return { status: 'idle', message: 'Select a technician.' };
    }

    const startVal = parseInt(startTime.replace(':', ''));
    const endVal = parseInt(endTime.replace(':', ''));

    const conflictingShift = (employee.availabilities || []).find((a) => {
        if (a.day_of_week !== day || !a.is_active || a.id === excludeAvailId) {
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
};

const createValidation = computed(() => {
    return validateShift(
        createForm.employee_id,
        createForm.day_of_week,
        createForm.start_time,
        createForm.end_time,
        null,
    );
});

const editValidation = computed(() => {
    return validateShift(
        editForm.employee_id,
        editForm.day_of_week,
        editForm.start_time,
        editForm.end_time,
        selectedAvailability.value?.id || null,
    );
});
</script>

<template>
    <Head title="Shifts & Availabilities" />

    <div class="min-h-screen bg-background p-6 text-foreground">
        <!-- Header -->
        <div
            class="mb-8 flex flex-col items-start justify-between gap-4 border-b pb-6 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-foreground">
                    Technician Weekly Shifts
                </h1>
                <p
                    class="mt-1 text-xs tracking-wider text-muted-foreground uppercase"
                >
                    Register and manage work shift availability hours for
                    technicians
                </p>
            </div>

            <Button
                @click="openCreateModal"
                class="flex items-center gap-1.5 font-bold shadow-sm"
            >
                <Plus class="h-4 w-4" /> Add Availability Shift
            </Button>
        </div>

        <!-- Filters Bar -->
        <div class="mb-6 flex flex-col items-center gap-4 sm:flex-row">
            <div class="w-full sm:max-w-xs">
                <Select v-model="filterTechId">
                    <SelectTrigger>
                        <SelectValue placeholder="Filter by Technician" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Technicians</SelectItem>
                        <SelectItem
                            v-for="emp in employees"
                            :key="emp.id"
                            :value="emp.id.toString()"
                        >
                            {{ emp.first_name }} {{ emp.last_name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Availabilities Log -->
        <Card class="shadow-sm">
            <CardHeader class="border-b pb-3">
                <CardTitle
                    class="flex items-center gap-2 text-lg font-bold tracking-wider uppercase"
                >
                    <Clock class="h-5 w-5 text-muted-foreground" /> Registered
                    Work Hours
                </CardTitle>
                <CardDescription
                    class="text-[10px] font-medium tracking-widest text-muted-foreground uppercase"
                >
                    Active schedules used for AI matching & dispatch
                </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table
                        class="w-full text-left text-sm text-muted-foreground"
                    >
                        <thead
                            class="border-b bg-accent/40 text-xs font-bold tracking-wider text-foreground uppercase"
                        >
                            <tr>
                                <th scope="col" class="px-6 py-4">
                                    Technician
                                </th>
                                <th scope="col" class="px-6 py-4">
                                    Day of Week
                                </th>
                                <th scope="col" class="px-6 py-4">
                                    Working Hours
                                </th>
                                <th scope="col" class="px-6 py-4">Status</th>
                                <th scope="col" class="px-6 py-4 text-right">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-border font-medium text-foreground"
                        >
                            <tr
                                v-for="avail in filteredAvailabilities"
                                :key="avail.id"
                                class="transition-colors hover:bg-accent/10"
                            >
                                <td class="px-6 py-4">
                                    <span
                                        class="flex items-center gap-1.5 font-bold"
                                    >
                                        <UserIcon
                                            class="h-4 w-4 text-primary/70"
                                        />
                                        {{ avail.employee.first_name }}
                                        {{ avail.employee.last_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold">
                                    {{ getDayName(avail.day_of_week) }}
                                </td>
                                <td
                                    class="px-6 py-4 font-mono text-xs font-bold text-muted-foreground"
                                >
                                    {{ avail.start_time.substring(0, 5) }} -
                                    {{ avail.end_time.substring(0, 5) }}
                                </td>
                                <td class="px-6 py-4">
                                    <Badge
                                        :variant="
                                            avail.is_active
                                                ? 'secondary'
                                                : 'outline'
                                        "
                                        :class="
                                            avail.is_active
                                                ? 'border border-emerald-500/20 bg-emerald-100/60 text-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-400'
                                                : 'text-muted-foreground'
                                        "
                                        class="rounded px-2 py-0.5 text-[9px] font-bold uppercase"
                                    >
                                        {{
                                            avail.is_active
                                                ? 'Active'
                                                : 'Inactive'
                                        }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="h-8 px-2.5 font-bold"
                                            @click="openEditModal(avail)"
                                        >
                                            <Edit2 class="h-3.5 w-3.5" />
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="h-8 px-2.5 font-bold text-rose-600 hover:text-rose-700 dark:hover:text-rose-500"
                                            @click="deleteShift(avail.id)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredAvailabilities.length === 0">
                                <td
                                    colspan="5"
                                    class="py-8 text-center text-xs font-semibold text-muted-foreground"
                                >
                                    No shifts configured.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- --- CREATE SHIFT DIALOG --- -->
        <Dialog v-model:open="isCreateOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Add Technician Shift</DialogTitle>
                    <DialogDescription>
                        Assign new weekly shift hours. Conflicting shifts for
                        the technician will be checked in real-time.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitCreate" class="space-y-4 pt-4">
                    <div class="space-y-2">
                        <Label for="employee_id">Select Technician</Label>
                        <Select v-model="createForm.employee_id">
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
                            v-if="createForm.errors.employee_id"
                            class="mt-1 text-xs text-rose-500"
                        >
                            {{ createForm.errors.employee_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="day_of_week">Day of Week</Label>
                        <Select v-model="createForm.day_of_week">
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
                            v-if="createForm.errors.day_of_week"
                            class="mt-1 text-xs text-rose-500"
                        >
                            {{ createForm.errors.day_of_week }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="start_time">Start Time</Label>
                            <Input
                                id="start_time"
                                type="time"
                                v-model="createForm.start_time"
                            />
                            <p
                                v-if="createForm.errors.start_time"
                                class="mt-1 text-xs text-rose-500"
                            >
                                {{ createForm.errors.start_time }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="end_time">End Time</Label>
                            <Input
                                id="end_time"
                                type="time"
                                v-model="createForm.end_time"
                            />
                            <p
                                v-if="createForm.errors.end_time"
                                class="mt-1 text-xs text-rose-500"
                            >
                                {{ createForm.errors.end_time }}
                            </p>
                        </div>
                    </div>

                    <!-- Live validation alert -->
                    <div
                        v-if="createValidation.status !== 'idle'"
                        :class="{
                            'border-emerald-500/25 bg-emerald-500/10 text-emerald-800 dark:text-emerald-400':
                                createValidation.status === 'success',
                            'border-rose-500/25 bg-rose-500/10 text-rose-800 dark:text-rose-400':
                                createValidation.status === 'error',
                        }"
                        class="mt-2 flex items-center gap-2 rounded-lg border p-3 text-xs font-semibold"
                    >
                        <CheckCircle
                            v-if="createValidation.status === 'success'"
                            class="h-4 w-4 shrink-0 text-emerald-500"
                        />
                        <XCircle
                            v-else
                            class="h-4 w-4 shrink-0 text-rose-500"
                        />
                        <span>{{ createValidation.message }}</span>
                    </div>

                    <DialogFooter class="border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isCreateOpen = false"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="
                                createForm.processing ||
                                createValidation.status === 'error'
                            "
                            >Add Shift</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- --- EDIT SHIFT DIALOG --- -->
        <Dialog v-model:open="isEditOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Edit Technician Shift</DialogTitle>
                    <DialogDescription>
                        Modify weekly shift details or working hours.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitEdit" class="space-y-4 pt-4">
                    <div class="space-y-2">
                        <Label for="edit_employee_id">Technician</Label>
                        <Select v-model="editForm.employee_id">
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
                            v-if="editForm.errors.employee_id"
                            class="mt-1 text-xs text-rose-500"
                        >
                            {{ editForm.errors.employee_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit_day_of_week">Day of Week</Label>
                        <Select v-model="editForm.day_of_week">
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
                            v-if="editForm.errors.day_of_week"
                            class="mt-1 text-xs text-rose-500"
                        >
                            {{ editForm.errors.day_of_week }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="edit_start_time">Start Time</Label>
                            <Input
                                id="edit_start_time"
                                type="time"
                                v-model="editForm.start_time"
                            />
                            <p
                                v-if="editForm.errors.start_time"
                                class="mt-1 text-xs text-rose-500"
                            >
                                {{ editForm.errors.start_time }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="edit_end_time">End Time</Label>
                            <Input
                                id="edit_end_time"
                                type="time"
                                v-model="editForm.end_time"
                            />
                            <p
                                v-if="editForm.errors.end_time"
                                class="mt-1 text-xs text-rose-500"
                            >
                                {{ editForm.errors.end_time }}
                            </p>
                        </div>
                    </div>

                    <!-- Live validation alert -->
                    <div
                        v-if="editValidation.status !== 'idle'"
                        :class="{
                            'border-emerald-500/25 bg-emerald-500/10 text-emerald-800 dark:text-emerald-400':
                                editValidation.status === 'success',
                            'border-rose-500/25 bg-rose-500/10 text-rose-800 dark:text-rose-400':
                                editValidation.status === 'error',
                        }"
                        class="mt-2 flex items-center gap-2 rounded-lg border p-3 text-xs font-semibold"
                    >
                        <CheckCircle
                            v-if="editValidation.status === 'success'"
                            class="h-4 w-4 shrink-0 text-emerald-500"
                        />
                        <XCircle
                            v-else
                            class="h-4 w-4 shrink-0 text-rose-500"
                        />
                        <span>{{ editValidation.message }}</span>
                    </div>

                    <DialogFooter class="border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isEditOpen = false"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="
                                editForm.processing ||
                                editValidation.status === 'error'
                            "
                            >Save Changes</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
