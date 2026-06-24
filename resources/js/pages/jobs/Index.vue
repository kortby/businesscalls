<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Plus,
    Trash2,
    Edit,
    Wrench,
    ClipboardCheck,
    Clock,
    User,
    AlertCircle,
    Briefcase,
    CheckCircle2,
    X,
    Loader2,
    Search,
    FilterX,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import Heading from '@/components/Heading.vue';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    store as storeJob,
    update as updateJob,
    destroy as destroyJob,
} from '@/routes/jobs';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    jobs: Array<{
        id: number;
        customer_id: number;
        employee_id: number | null;
        title: string;
        description: string | null;
        status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
        steps: string[] | null;
        created_at: string;
        customer: {
            id: number;
            name: string;
            phone: string;
        };
        employee?: {
            id: number;
            first_name: string;
            last_name: string;
        } | null;
    }>;
    customers: Array<{
        id: number;
        name: string;
        phone: string;
    }>;
    employees: Array<{
        id: number;
        first_name: string;
        last_name: string;
        phone: string;
    }>;
}>();

const showAddModal = ref(false);
const showEditModal = ref(false);
const editingJob = ref<any>(null);

const newStepText = ref('');
const editNewStepText = ref('');

// Filter states
const searchQuery = ref('');
const statusFilter = ref('');
const techFilter = ref('');
const dateFilter = ref('');

const filteredJobs = computed(() => {
    return props.jobs.filter((job) => {
        // Search filter (title, description, customer name, customer phone, technician name)
        if (searchQuery.value.trim()) {
            const query = searchQuery.value.toLowerCase().trim();
            const titleMatch = job.title?.toLowerCase().includes(query);
            const descMatch = job.description?.toLowerCase().includes(query);
            const customerMatch =
                job.customer?.name?.toLowerCase().includes(query) ||
                job.customer?.phone?.includes(query);
            const techMatch = job.employee
                ? (job.employee.first_name + ' ' + job.employee.last_name)
                      .toLowerCase()
                      .includes(query)
                : 'unassigned'.includes(query);

            if (!titleMatch && !descMatch && !customerMatch && !techMatch) {
                return false;
            }
        }

        // Status filter
        if (statusFilter.value) {
            if (job.status !== statusFilter.value) {
                return false;
            }
        }

        // Tech filter
        if (techFilter.value) {
            if (techFilter.value === 'unassigned') {
                if (job.employee_id !== null) {
                    return false;
                }
            } else {
                if (job.employee_id?.toString() !== techFilter.value) {
                    return false;
                }
            }
        }

        // Date filter (comparing YYYY-MM-DD of created_at)
        if (dateFilter.value) {
            const jobDate = new Date(job.created_at)
                .toISOString()
                .split('T')[0];

            if (jobDate !== dateFilter.value) {
                return false;
            }
        }

        return true;
    });
});

const hasActiveFilters = computed(() => {
    return !!(
        searchQuery.value ||
        statusFilter.value ||
        techFilter.value ||
        dateFilter.value
    );
});

const resetFilters = () => {
    searchQuery.value = '';
    statusFilter.value = '';
    techFilter.value = '';
    dateFilter.value = '';
};

const form = useForm({
    customer_id: '',
    employee_id: '',
    title: '',
    description: '',
    status: 'pending' as 'pending' | 'in_progress' | 'completed' | 'cancelled',
    steps: [] as string[],
});

const editForm = useForm({
    customer_id: '',
    employee_id: '',
    title: '',
    description: '',
    status: 'pending' as 'pending' | 'in_progress' | 'completed' | 'cancelled',
    steps: [] as string[],
});

const addStep = (isEdit = false) => {
    if (isEdit) {
        const val = editNewStepText.value.trim();

        if (val) {
            editForm.steps.push(val);
            editNewStepText.value = '';
        }
    } else {
        const val = newStepText.value.trim();

        if (val) {
            form.steps.push(val);
            newStepText.value = '';
        }
    }
};

const removeStep = (index: number, isEdit = false) => {
    if (isEdit) {
        editForm.steps.splice(index, 1);
    } else {
        form.steps.splice(index, 1);
    }
};

const submitAdd = () => {
    form.post(storeJob.url(), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
            newStepText.value = '';
        },
    });
};

const openEditModal = (job: any) => {
    editingJob.value = job;
    editForm.customer_id = job.customer_id.toString();
    editForm.employee_id = job.employee_id ? job.employee_id.toString() : '';
    editForm.title = job.title;
    editForm.description = job.description || '';
    editForm.status = job.status;
    editForm.steps = [...(job.steps || [])];
    editNewStepText.value = '';
    showEditModal.value = true;
};

const submitUpdate = () => {
    editForm.put(updateJob.url(editingJob.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editingJob.value = null;
        },
    });
};

const deleteJob = (id: number) => {
    if (confirm('Are you sure you want to remove this service job?')) {
        router.delete(destroyJob.url(id));
    }
};
</script>

<template>
    <Head title="Customer Jobs" />

    <div class="space-y-6 px-6 py-6">
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <Heading
                title="Service Work Orders"
                description="Manage customer jobs, assign mobile service technicians, and log step-by-step fix notes"
            />
            <Button
                @click="showAddModal = true"
                class="flex cursor-pointer items-center gap-1.5 rounded-xl border-2 border-b-4 border-emerald-500 border-emerald-700 bg-emerald-500 px-5 py-2.5 text-xs font-black tracking-wide text-white uppercase shadow-md transition-all hover:border-emerald-600 hover:bg-emerald-400 active:translate-y-1 active:border-b-0"
            >
                <Plus class="h-4 w-4" /> Create Service Job
            </Button>
        </div>

        <!-- Filter and Search Bar -->
        <div
            class="flex flex-col gap-4 rounded-2xl border-2 border-slate-200 bg-slate-50/50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/20"
        >
            <div class="flex flex-wrap items-center gap-3">
                <!-- Search input -->
                <div class="relative min-w-[280px] flex-1">
                    <Search
                        class="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground"
                    />
                    <Input
                        v-model="searchQuery"
                        placeholder="Search jobs, customers, technicians..."
                        class="rounded-xl border-2 border-slate-200 bg-white pl-9 dark:border-slate-800 dark:bg-slate-900"
                    />
                </div>

                <!-- Status select -->
                <select
                    v-model="statusFilter"
                    class="flex h-9 min-w-[150px] rounded-md border-2 border-slate-200 bg-white px-3 py-1 text-sm shadow-xs focus-visible:border-indigo-500 focus-visible:ring-3 focus-visible:ring-indigo-500/20 focus-visible:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                >
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>

                <!-- Tech select -->
                <select
                    v-model="techFilter"
                    class="flex h-9 min-w-[180px] rounded-md border-2 border-slate-200 bg-white px-3 py-1 text-sm shadow-xs focus-visible:border-indigo-500 focus-visible:ring-3 focus-visible:ring-indigo-500/20 focus-visible:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                >
                    <option value="">All Technicians</option>
                    <option value="unassigned">Unassigned</option>
                    <option
                        v-for="tech in employees"
                        :key="tech.id"
                        :value="tech.id.toString()"
                    >
                        {{ tech.first_name }} {{ tech.last_name }}
                    </option>
                </select>

                <!-- Date picker -->
                <div class="flex min-w-[150px] items-center gap-1.5">
                    <input
                        type="date"
                        v-model="dateFilter"
                        class="flex h-9 w-full rounded-md border-2 border-slate-200 bg-white px-3 py-1 text-sm shadow-xs focus-visible:border-indigo-500 focus-visible:ring-3 focus-visible:ring-indigo-500/20 focus-visible:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-white"
                    />
                </div>

                <!-- Reset button -->
                <Button
                    v-if="hasActiveFilters"
                    @click="resetFilters"
                    variant="ghost"
                    class="flex cursor-pointer items-center gap-1 text-xs font-black text-rose-500 uppercase hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/20"
                >
                    <FilterX class="h-4 w-4" /> Reset Filters
                </Button>
            </div>

            <!-- Match stats counter -->
            <div
                class="flex items-center justify-between text-[10px] font-bold text-muted-foreground"
            >
                <span
                    >Showing {{ filteredJobs.length }} of {{ jobs.length }} work
                    orders</span
                >
                <span
                    v-if="hasActiveFilters"
                    class="font-black text-indigo-500 uppercase"
                    >Filters Active</span
                >
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Jobs Dashboard Grid/List -->
            <div
                v-for="job in filteredJobs"
                :key="job.id"
                class="flex flex-col items-start justify-between gap-6 rounded-2xl border-3 border-b-6 border-slate-300 bg-card p-6 md:flex-row dark:border-slate-800"
            >
                <!-- Left Section: Details -->
                <div class="flex-1 space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge
                            :class="[
                                job.status === 'completed'
                                    ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600'
                                    : job.status === 'in_progress'
                                      ? 'border border-indigo-500/20 bg-indigo-500/10 text-indigo-600'
                                      : job.status === 'cancelled'
                                        ? 'border border-slate-500/20 bg-slate-500/10 text-slate-500'
                                        : 'border border-amber-500/20 bg-amber-500/10 text-amber-600',
                            ]"
                            class="px-2 py-0.5 text-[9px] font-black tracking-wider uppercase"
                        >
                            {{ job.status.replace('_', ' ') }}
                        </Badge>
                        <span
                            class="flex items-center gap-1 text-xs font-bold text-muted-foreground"
                        >
                            <Clock class="h-3 w-3" /> Created
                            {{ new Date(job.created_at).toLocaleDateString() }}
                        </span>
                    </div>

                    <div class="space-y-1">
                        <h3
                            class="text-base leading-snug font-black text-slate-900 dark:text-white"
                        >
                            {{ job.title }}
                        </h3>
                        <p
                            v-if="job.description"
                            class="max-w-xl text-xs font-medium text-slate-500 dark:text-slate-400"
                        >
                            {{ job.description }}
                        </p>
                    </div>

                    <!-- Client & Tech Info -->
                    <div
                        class="grid max-w-md grid-cols-1 gap-4 pt-1 sm:grid-cols-2"
                    >
                        <div
                            class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300"
                        >
                            <User class="h-4 w-4 text-indigo-500/60" />
                            <div>
                                <div
                                    class="font-bold text-slate-900 dark:text-white"
                                >
                                    {{ job.customer.name }}
                                </div>
                                <div
                                    class="font-mono text-[10px] text-muted-foreground"
                                >
                                    {{ job.customer.phone }}
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300"
                        >
                            <Wrench class="h-4 w-4 text-slate-500/60" />
                            <div>
                                <div
                                    class="font-bold text-slate-900 dark:text-white"
                                    v-if="job.employee"
                                >
                                    {{ job.employee.first_name }}
                                    {{ job.employee.last_name }}
                                </div>
                                <div
                                    class="text-[10px] font-bold text-muted-foreground italic"
                                    v-else
                                >
                                    Unassigned
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Section: Chronological Fix Steps -->
                <div
                    class="flex w-full flex-col justify-between space-y-3 rounded-xl border-2 border-slate-200 bg-slate-50/50 p-4 md:w-96 dark:border-slate-800/80 dark:bg-slate-900/40"
                >
                    <div>
                        <h4
                            class="mb-2.5 flex items-center gap-1 text-[10px] font-black tracking-wider text-slate-400 uppercase"
                        >
                            <ClipboardCheck class="h-3.5 w-3.5" />
                            <span>Fix Steps & Log Notes</span>
                        </h4>

                        <div class="max-h-40 space-y-2 overflow-y-auto pr-1">
                            <div
                                v-for="(step, index) in job.steps || []"
                                :key="index"
                                class="flex items-start gap-2 rounded-lg border border-slate-200 bg-white p-2 text-xs text-slate-600 shadow-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
                            >
                                <span
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded border border-indigo-500/20 bg-indigo-500/10 text-[9px] font-bold text-indigo-600 dark:text-indigo-400"
                                >
                                    {{ index + 1 }}
                                </span>
                                <p class="leading-normal font-semibold">
                                    {{ step }}
                                </p>
                            </div>

                            <div
                                v-if="!job.steps || job.steps.length === 0"
                                class="py-2 text-xs font-bold text-muted-foreground italic"
                            >
                                No fix notes logged yet.
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex shrink-0 items-center justify-between gap-3 border-t border-slate-200 pt-3 dark:border-slate-800/80"
                    >
                        <button
                            @click="openEditModal(job)"
                            class="flex cursor-pointer items-center gap-1 text-xs font-bold text-slate-600 transition-colors hover:text-indigo-600"
                        >
                            <Edit class="h-3.5 w-3.5" /> Edit Job & Logs
                        </button>
                        <button
                            @click="deleteJob(job.id)"
                            class="flex cursor-pointer items-center gap-1 text-xs font-bold text-rose-500 transition-colors hover:text-rose-700"
                        >
                            <Trash2 class="h-3.5 w-3.5" /> Remove
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="filteredJobs.length === 0"
                class="rounded-2xl border-2 border-dashed border-slate-300 p-12 text-center text-sm font-semibold text-muted-foreground italic dark:border-slate-800"
            >
                <span v-if="hasActiveFilters"
                    >No service jobs match your active filters. Click reset
                    above or modify search!</span
                >
                <span v-else
                    >No service work orders registered. Create one above!</span
                >
            </div>
        </div>

        <!-- Add Job Modal -->
        <Dialog :open="showAddModal" @update:open="showAddModal = $event">
            <DialogContent
                class="rounded-2xl border-3 border-slate-300 bg-white p-6 shadow-2xl sm:max-w-lg dark:border-slate-800 dark:bg-slate-900"
            >
                <DialogHeader>
                    <DialogTitle
                        class="flex items-center gap-2 text-lg font-black text-slate-900 uppercase dark:text-white"
                    >
                        <Briefcase class="h-5 w-5 text-indigo-500" />
                        <span>Create Service Job</span>
                    </DialogTitle>
                    <DialogDescription class="text-xs font-medium"
                        >Record a new customer service request or repair work
                        order.</DialogDescription
                    >
                </DialogHeader>
                <form @submit.prevent="submitAdd" class="space-y-4 pt-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label
                                for="customer_id"
                                class="text-[10px] font-black text-slate-500 uppercase"
                                >Customer (Required)</Label
                            >
                            <select
                                id="customer_id"
                                v-model="form.customer_id"
                                required
                                class="flex h-9 w-full rounded-md border-2 border-slate-200 bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-indigo-500 focus-visible:ring-3 focus-visible:ring-indigo-500/20 focus-visible:outline-none dark:border-slate-800 dark:bg-slate-900/50 dark:text-white"
                            >
                                <option
                                    value=""
                                    disabled
                                    class="dark:bg-slate-900"
                                >
                                    Select Client
                                </option>
                                <option
                                    v-for="cust in customers"
                                    :key="cust.id"
                                    :value="cust.id"
                                    class="dark:bg-slate-900"
                                >
                                    {{ cust.name }} ({{ cust.phone }})
                                </option>
                            </select>
                            <span
                                v-if="form.errors.customer_id"
                                class="text-[10px] font-bold text-rose-500"
                                >{{ form.errors.customer_id }}</span
                            >
                        </div>

                        <div class="space-y-1.5">
                            <Label
                                for="employee_id"
                                class="text-[10px] font-black text-slate-500 uppercase"
                                >Assigned Technician</Label
                            >
                            <select
                                id="employee_id"
                                v-model="form.employee_id"
                                class="flex h-9 w-full rounded-md border-2 border-slate-200 bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-indigo-500 focus-visible:ring-3 focus-visible:ring-indigo-500/20 focus-visible:outline-none dark:border-slate-800 dark:bg-slate-900/50 dark:text-white"
                            >
                                <option value="" class="dark:bg-slate-900">
                                    Unassigned / Dispatch Later
                                </option>
                                <option
                                    v-for="tech in employees"
                                    :key="tech.id"
                                    :value="tech.id"
                                    class="dark:bg-slate-900"
                                >
                                    {{ tech.first_name }} {{ tech.last_name }}
                                </option>
                            </select>
                            <span
                                v-if="form.errors.employee_id"
                                class="text-[10px] font-bold text-rose-500"
                                >{{ form.errors.employee_id }}</span
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="space-y-1.5 sm:col-span-2">
                            <Label
                                for="title"
                                class="text-[10px] font-black text-slate-500 uppercase"
                                >Job Title / Issue</Label
                            >
                            <Input
                                id="title"
                                v-model="form.title"
                                required
                                placeholder="e.g. Water heater leaking in basement"
                                class="border-2 border-slate-200 dark:border-slate-800"
                            />
                            <span
                                v-if="form.errors.title"
                                class="text-[10px] font-bold text-rose-500"
                                >{{ form.errors.title }}</span
                            >
                        </div>

                        <div class="space-y-1.5">
                            <Label
                                for="status"
                                class="text-[10px] font-black text-slate-500 uppercase"
                                >Status</Label
                            >
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="flex h-9 w-full rounded-md border-2 border-slate-200 bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-indigo-500 focus-visible:ring-3 focus-visible:ring-indigo-500/20 focus-visible:outline-none dark:border-slate-800 dark:bg-slate-900/50 dark:text-white"
                            >
                                <option
                                    value="pending"
                                    class="dark:bg-slate-900"
                                >
                                    Pending
                                </option>
                                <option
                                    value="in_progress"
                                    class="dark:bg-slate-900"
                                >
                                    In Progress
                                </option>
                                <option
                                    value="completed"
                                    class="dark:bg-slate-900"
                                >
                                    Completed
                                </option>
                                <option
                                    value="cancelled"
                                    class="dark:bg-slate-900"
                                >
                                    Cancelled
                                </option>
                            </select>
                            <span
                                v-if="form.errors.status"
                                class="text-[10px] font-bold text-rose-500"
                                >{{ form.errors.status }}</span
                            >
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="description"
                            class="text-[10px] font-black text-slate-500 uppercase"
                            >Description of work (Optional)</Label
                        >
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Specify problem scope, model details, or service items needed..."
                            rows="3"
                            class="border-2 border-slate-200 dark:border-slate-800"
                        />
                        <span
                            v-if="form.errors.description"
                            class="text-[10px] font-bold text-rose-500"
                            >{{ form.errors.description }}</span
                        >
                    </div>

                    <!-- Steps initialization -->
                    <div class="space-y-2 border-t pt-4">
                        <Label
                            class="text-[10px] font-black text-slate-500 uppercase"
                            >Initial Fix Steps / Logs</Label
                        >

                        <div class="flex gap-2">
                            <Input
                                v-model="newStepText"
                                @keydown.enter.prevent="addStep(false)"
                                placeholder="e.g. Arrived on site and shut off main valve..."
                                class="flex-1 border-2 border-slate-200 dark:border-slate-800"
                            />
                            <Button
                                type="button"
                                size="sm"
                                @click="addStep(false)"
                                class="cursor-pointer border border-indigo-500 px-4 text-xs font-bold uppercase hover:bg-indigo-500/10"
                            >
                                Add Note
                            </Button>
                        </div>

                        <!-- Rendered list of added steps -->
                        <div class="mt-2 max-h-32 space-y-1.5 overflow-y-auto">
                            <div
                                v-for="(step, index) in form.steps"
                                :key="index"
                                class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2 text-xs dark:border-slate-800/80 dark:bg-slate-900/60"
                            >
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="text-[9px] font-bold text-indigo-500"
                                        >#{{ index + 1 }}</span
                                    >
                                    <span
                                        class="font-semibold text-slate-700 dark:text-slate-300"
                                        >{{ step }}</span
                                    >
                                </div>
                                <button
                                    type="button"
                                    @click="removeStep(index, false)"
                                    class="cursor-pointer text-rose-500 hover:text-rose-700"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="flex justify-end gap-2 border-t pt-4">
                        <Button
                            type="button"
                            variant="ghost"
                            @click="showAddModal = false"
                            class="cursor-pointer text-xs font-bold uppercase"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="flex cursor-pointer items-center gap-1.5 rounded-xl border-2 border-b-4 border-emerald-500 border-emerald-700 bg-emerald-500 px-6 py-2.5 text-xs font-black tracking-wide text-white uppercase shadow-md transition-all hover:border-emerald-600 hover:bg-emerald-400 active:border-b-0"
                        >
                            <Loader2
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin"
                            />
                            <span>Create Job</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Job Modal -->
        <Dialog :open="showEditModal" @update:open="showEditModal = $event">
            <DialogContent
                class="rounded-2xl border-3 border-slate-300 bg-white p-6 shadow-2xl sm:max-w-lg dark:border-slate-800 dark:bg-slate-900"
            >
                <DialogHeader>
                    <DialogTitle
                        class="flex items-center gap-2 text-lg font-black text-slate-900 uppercase dark:text-white"
                    >
                        <Edit class="h-5 w-5 text-indigo-500" />
                        <span>Edit Work Order</span>
                    </DialogTitle>
                    <DialogDescription class="text-xs font-medium"
                        >Update the customer request, assign technicians, or
                        edit repair steps log.</DialogDescription
                    >
                </DialogHeader>
                <form @submit.prevent="submitUpdate" class="space-y-4 pt-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label
                                for="edit_customer_id"
                                class="text-[10px] font-black text-slate-500 uppercase"
                                >Customer (Required)</Label
                            >
                            <select
                                id="edit_customer_id"
                                v-model="editForm.customer_id"
                                required
                                class="flex h-9 w-full rounded-md border-2 border-slate-200 bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-indigo-500 focus-visible:ring-3 focus-visible:ring-indigo-500/20 focus-visible:outline-none dark:border-slate-800 dark:bg-slate-900/50 dark:text-white"
                            >
                                <option
                                    v-for="cust in customers"
                                    :key="cust.id"
                                    :value="cust.id"
                                    class="dark:bg-slate-900"
                                >
                                    {{ cust.name }} ({{ cust.phone }})
                                </option>
                            </select>
                            <span
                                v-if="editForm.errors.customer_id"
                                class="text-[10px] font-bold text-rose-500"
                                >{{ editForm.errors.customer_id }}</span
                            >
                        </div>

                        <div class="space-y-1.5">
                            <Label
                                for="edit_employee_id"
                                class="text-[10px] font-black text-slate-500 uppercase"
                                >Assigned Technician</Label
                            >
                            <select
                                id="edit_employee_id"
                                v-model="editForm.employee_id"
                                class="flex h-9 w-full rounded-md border-2 border-slate-200 bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-indigo-500 focus-visible:ring-3 focus-visible:ring-indigo-500/20 focus-visible:outline-none dark:border-slate-800 dark:bg-slate-900/50 dark:text-white"
                            >
                                <option value="" class="dark:bg-slate-900">
                                    Unassigned / Dispatch Later
                                </option>
                                <option
                                    v-for="tech in employees"
                                    :key="tech.id"
                                    :value="tech.id"
                                    class="dark:bg-slate-900"
                                >
                                    {{ tech.first_name }} {{ tech.last_name }}
                                </option>
                            </select>
                            <span
                                v-if="editForm.errors.employee_id"
                                class="text-[10px] font-bold text-rose-500"
                                >{{ editForm.errors.employee_id }}</span
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="space-y-1.5 sm:col-span-2">
                            <Label
                                for="edit_title"
                                class="text-[10px] font-black text-slate-500 uppercase"
                                >Job Title / Issue</Label
                            >
                            <Input
                                id="edit_title"
                                v-model="editForm.title"
                                required
                                class="border-2 border-slate-200 dark:border-slate-800"
                            />
                            <span
                                v-if="editForm.errors.title"
                                class="text-[10px] font-bold text-rose-500"
                                >{{ editForm.errors.title }}</span
                            >
                        </div>

                        <div class="space-y-1.5">
                            <Label
                                for="edit_status"
                                class="text-[10px] font-black text-slate-500 uppercase"
                                >Status</Label
                            >
                            <select
                                id="edit_status"
                                v-model="editForm.status"
                                required
                                class="flex h-9 w-full rounded-md border-2 border-slate-200 bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-indigo-500 focus-visible:ring-3 focus-visible:ring-indigo-500/20 focus-visible:outline-none dark:border-slate-800 dark:bg-slate-900/50 dark:text-white"
                            >
                                <option
                                    value="pending"
                                    class="dark:bg-slate-900"
                                >
                                    Pending
                                </option>
                                <option
                                    value="in_progress"
                                    class="dark:bg-slate-900"
                                >
                                    In Progress
                                </option>
                                <option
                                    value="completed"
                                    class="dark:bg-slate-900"
                                >
                                    Completed
                                </option>
                                <option
                                    value="cancelled"
                                    class="dark:bg-slate-900"
                                >
                                    Cancelled
                                </option>
                            </select>
                            <span
                                v-if="editForm.errors.status"
                                class="text-[10px] font-bold text-rose-500"
                                >{{ editForm.errors.status }}</span
                            >
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="edit_description"
                            class="text-[10px] font-black text-slate-500 uppercase"
                            >Description of work</Label
                        >
                        <Textarea
                            id="edit_description"
                            v-model="editForm.description"
                            rows="3"
                            class="border-2 border-slate-200 dark:border-slate-800"
                        />
                        <span
                            v-if="editForm.errors.description"
                            class="text-[10px] font-bold text-rose-500"
                            >{{ editForm.errors.description }}</span
                        >
                    </div>

                    <!-- Steps editing & logging -->
                    <div class="space-y-2 border-t pt-4">
                        <Label
                            class="text-[10px] font-black text-slate-500 uppercase"
                            >Log Fix Progress Steps</Label
                        >

                        <div class="flex gap-2">
                            <Input
                                v-model="editNewStepText"
                                @keydown.enter.prevent="addStep(true)"
                                placeholder="Add another progress note or diagnostic fix..."
                                class="flex-1 border-2 border-slate-200 dark:border-slate-800"
                            />
                            <Button
                                type="button"
                                size="sm"
                                @click="addStep(true)"
                                class="cursor-pointer border border-indigo-500 px-4 text-xs font-bold uppercase hover:bg-indigo-500/10"
                            >
                                Add Note
                            </Button>
                        </div>

                        <!-- Rendered editable text inputs for previous steps -->
                        <div class="mt-2 max-h-40 space-y-2 overflow-y-auto">
                            <div
                                v-for="(step, index) in editForm.steps"
                                :key="index"
                                class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2 shadow-xs dark:border-slate-800/80 dark:bg-slate-900/60"
                            >
                                <span
                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded border border-indigo-500/20 bg-indigo-500/10 text-[10px] font-bold text-indigo-600 dark:text-indigo-400"
                                >
                                    {{ index + 1 }}
                                </span>
                                <Input
                                    v-model="editForm.steps[index]"
                                    required
                                    class="h-8 flex-1 border border-slate-200 bg-white text-xs font-semibold dark:border-slate-800 dark:bg-slate-900"
                                />
                                <button
                                    type="button"
                                    @click="removeStep(index, true)"
                                    class="shrink-0 cursor-pointer text-rose-500 hover:text-rose-700"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="flex justify-end gap-2 border-t pt-4">
                        <Button
                            type="button"
                            variant="ghost"
                            @click="showEditModal = false"
                            class="cursor-pointer text-xs font-bold uppercase"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="editForm.processing"
                            class="flex cursor-pointer items-center gap-1.5 rounded-xl border-2 border-b-4 border-emerald-500 border-emerald-700 bg-emerald-500 px-6 py-2.5 text-xs font-black tracking-wide text-white uppercase shadow-md transition-all hover:border-emerald-600 hover:bg-emerald-400 active:border-b-0"
                        >
                            <Loader2
                                v-if="editForm.processing"
                                class="h-4 w-4 animate-spin"
                            />
                            <span>Save Changes</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
