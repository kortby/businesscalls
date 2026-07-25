<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Phone,
    Calendar,
    ClipboardList,
    Clock,
    MessageSquare,
    AlertCircle,
    Plus,
    Upload,
    FileSpreadsheet,
    Loader2,
    UserPlus,
    Mail,
    FileText,
    Edit,
    Trash2,
    Wrench,
    User,
    CheckCircle2,
    Eye,
    ExternalLink,
    CalendarDays,
} from '@lucide/vue';
import { ref } from 'vue';
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
    store as storeCustomer,
    update as updateCustomer,
    destroy as destroyCustomer,
    importMethod as importCustomer,
} from '@/routes/customers';

defineOptions({ layout: AppLayout });

interface ServiceJobItem {
    id: number;
    title: string;
    description: string;
    status: string;
    steps: string[];
    employee_name: string;
    created_at: string;
}

interface BookingItem {
    id: number;
    service_type: string;
    requested_time: string;
    status: string;
    technician_name: string;
    created_at: string;
}

interface CallLogItem {
    id: number;
    summary: string;
    status: string;
    duration: string;
    created_at: string;
}

interface CustomerItem {
    id: number | null;
    phone: string;
    name: string;
    email: string;
    notes: string;
    total_jobs: number;
    total_bookings: number;
    total_calls: number;
    latest_call_date: string;
    latest_call_summary: string;
    latest_call_status: string;
    is_profile: boolean;
    jobs?: ServiceJobItem[];
    bookings?: BookingItem[];
    call_logs?: CallLogItem[];
}

defineProps<{
    customers: CustomerItem[];
    permissions?: {
        canCreate: boolean;
        canUpdate: boolean;
        canDelete: boolean;
        canImport: boolean;
    };
}>();

const showAddModal = ref(false);
const showEditModal = ref(false);
const showImportModal = ref(false);
const showDetailModal = ref(false);

const editingCustomer = ref<any>(null);
const selectedDetailCustomer = ref<CustomerItem | null>(null);
const activeDetailTab = ref<'jobs' | 'bookings' | 'calls' | 'notes'>('jobs');

const fileInput = ref<HTMLInputElement | null>(null);
const fileError = ref<string | null>(null);
const selectedFileName = ref<string | null>(null);

const form = useForm({
    name: '',
    phone: '',
    email: '',
    notes: '',
});

const editForm = useForm({
    name: '',
    phone: '',
    email: '',
    notes: '',
});

const importForm = useForm({
    csv_file: null as File | null,
});

const openAddModal = (initialData?: { name?: string; phone?: string }) => {
    form.reset();
    form.clearErrors();

    if (initialData) {
        form.name = initialData.name || '';
        form.phone = initialData.phone || '';
    }

    showAddModal.value = true;
};

const openDetailModal = (customer: CustomerItem) => {
    selectedDetailCustomer.value = customer;
    activeDetailTab.value = 'jobs';
    showDetailModal.value = true;
};

const submitAdd = () => {
    form.post(storeCustomer.url(), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
};

const openEditModal = (customer: any) => {
    editingCustomer.value = customer;
    editForm.name = customer.name;
    editForm.phone = customer.phone;
    editForm.email = customer.email || '';
    editForm.notes = customer.notes || '';
    showEditModal.value = true;
};

const submitUpdate = () => {
    if (!editingCustomer.value?.id) return;
    editForm.put(updateCustomer.url(editingCustomer.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editingCustomer.value = null;
        },
    });
};

const deleteCustomer = (id: number) => {
    if (
        confirm(
            'Are you sure you want to delete this customer profile? This action will be logged in compliance audit trails.',
        )
    ) {
        router.delete(destroyCustomer.url(id));
    }
};

const triggerFileSelect = () => {
    fileInput.value?.click();
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const files = target.files;
    fileError.value = null;
    selectedFileName.value = null;
    importForm.csv_file = null;

    if (files && files.length > 0) {
        const file = files[0];
        const extension = file.name.split('.').pop()?.toLowerCase();

        if (
            extension !== 'csv' &&
            file.type !== 'text/csv' &&
            file.type !== 'application/vnd.ms-excel'
        ) {
            fileError.value = 'Please select a valid CSV file (.csv).';

            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            fileError.value = 'File size exceeds the 2MB limit.';

            return;
        }

        importForm.csv_file = file;
        selectedFileName.value = file.name;
    }
};

const submitImport = () => {
    if (!importForm.csv_file) {
        fileError.value = 'Please select a CSV file first.';

        return;
    }

    importForm.post(importCustomer.url(), {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
            selectedFileName.value = null;

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};
</script>

<template>
    <Head title="Customers Directory" />

    <div class="space-y-6 px-6 py-6">
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <Heading
                title="Customers & Callers"
                description="Overview of customer profiles, booking volumes, and voice AI transcript history"
            />
            <div class="flex items-center gap-3">
                <Button
                    @click="showImportModal = true"
                    class="flex cursor-pointer items-center gap-1.5 rounded-xl border-3 border-b-6 border-slate-300 bg-white px-5 py-2.5 text-xs font-black tracking-wide text-slate-800 uppercase shadow-md transition-all hover:bg-slate-50 active:translate-y-0.5 active:border-b-3 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                    <Upload class="h-4 w-4 text-slate-500" /> Import CSV
                </Button>
                <Button
                    @click="openAddModal()"
                    class="flex cursor-pointer items-center gap-1.5 rounded-xl border-2 border-b-4 border-emerald-500 border-emerald-700 bg-emerald-500 px-5 py-2.5 text-xs font-black tracking-wide text-white uppercase shadow-md transition-all hover:border-emerald-600 hover:bg-emerald-400 active:translate-y-1 active:border-b-0"
                >
                    <Plus class="h-4 w-4" /> Add Customer
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Customer List Container -->
            <div
                class="rounded-2xl border-3 border-b-6 border-slate-300 bg-card p-6 dark:border-slate-800"
            >
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead>
                            <tr
                                class="border-b-2 border-slate-200 pb-3 text-[10px] font-black tracking-wider text-muted-foreground uppercase dark:border-slate-800"
                            >
                                <th class="pr-4 pb-3">Customer Details</th>
                                <th class="px-4 pb-3">Contact Phone</th>
                                <th class="px-4 pb-3 text-center">Jobs Done</th>
                                <th class="px-4 pb-3 text-center">Calls Logged</th>
                                <th class="px-4 pb-3 text-center">Bookings Aligned</th>
                                <th class="px-4 pb-3">Latest Interaction</th>
                                <th class="px-4 pb-3">Telemetry Status</th>
                                <th class="pb-3 pl-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100 dark:divide-slate-800/80"
                        >
                            <tr
                                v-for="customer in customers"
                                :key="customer.phone"
                                class="transition-colors hover:bg-muted/10"
                            >
                                <!-- Name & Email (Clickable to open details) -->
                                <td
                                    class="py-4 pr-4 font-black text-slate-900 dark:text-white"
                                >
                                    <div
                                        class="group flex cursor-pointer items-center gap-2.5"
                                        @click="openDetailModal(customer)"
                                    >
                                        <div
                                            :class="[
                                                customer.is_profile
                                                    ? 'border-indigo-500/30 bg-indigo-500/10 text-indigo-600 group-hover:bg-indigo-500 group-hover:text-white'
                                                    : 'border-slate-500/30 bg-slate-500/10 text-slate-600 dark:text-slate-400',
                                            ]"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg border-2 text-xs font-black transition-all"
                                        >
                                            {{ customer.name[0] }}
                                        </div>
                                        <div>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span class="group-hover:text-indigo-600 group-hover:underline dark:group-hover:text-indigo-400">{{ customer.name }}</span>
                                                <Badge
                                                    v-if="!customer.is_profile"
                                                    variant="outline"
                                                    class="cursor-pointer border-slate-300 bg-slate-100 px-1.5 py-0 text-[9px] font-bold text-slate-700 hover:border-indigo-500/30 hover:bg-indigo-500/10 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                                    @click.stop="
                                                        openAddModal({
                                                            name: customer.name,
                                                            phone: customer.phone,
                                                        })
                                                    "
                                                    title="Click to register this caller as a customer profile"
                                                >
                                                    <UserPlus
                                                        class="mr-0.5 h-2.5 w-2.5"
                                                    />
                                                    Raw Caller
                                                </Badge>
                                            </div>
                                            <div
                                                v-if="customer.email"
                                                class="mt-0.5 flex items-center gap-1 text-[10px] font-medium text-muted-foreground"
                                            >
                                                <Mail
                                                    class="h-3 w-3 text-slate-400"
                                                />
                                                <span>{{
                                                    customer.email
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Phone -->
                                <td
                                    class="px-4 py-4 font-mono text-xs font-bold text-slate-600 dark:text-slate-400"
                                >
                                    {{ customer.phone }}
                                </td>

                                <!-- Jobs Count (Clickable) -->
                                <td class="px-4 py-4 text-center">
                                    <Badge
                                        variant="outline"
                                        @click="openDetailModal(customer)"
                                        class="cursor-pointer border-indigo-500/30 bg-indigo-500/10 px-2.5 py-1 font-bold text-indigo-700 hover:bg-indigo-500 hover:text-white dark:text-indigo-300"
                                    >
                                        <Wrench class="mr-1 h-3 w-3 inline" />
                                        {{ customer.total_jobs }} jobs
                                    </Badge>
                                </td>

                                <!-- Calls Count -->
                                <td class="px-4 py-4 text-center">
                                    <Badge
                                        variant="outline"
                                        class="border-slate-500/30 bg-slate-500/5 px-2.5 font-bold text-slate-600 dark:text-slate-400"
                                    >
                                        {{ customer.total_calls }} calls
                                    </Badge>
                                </td>

                                 <!-- Bookings Count -->
                                <td class="px-4 py-4 text-center">
                                    <Badge
                                        variant="outline"
                                        class="border-emerald-500/30 bg-emerald-500/5 px-2.5 font-bold text-emerald-600"
                                    >
                                        {{ customer.total_bookings }} bookings
                                    </Badge>
                                </td>
                                <!-- Latest Summary -->
                                <td
                                    class="max-w-xs truncate px-4 py-4 text-xs font-medium text-slate-500 md:max-w-sm dark:text-slate-400"
                                    :title="customer.latest_call_summary"
                                >
                                    {{ customer.latest_call_summary }}
                                </td>

                                <!-- Status & Time -->
                                <td class="px-4 py-4 space-y-1">
                                    <div class="flex items-center gap-1.5">
                                        <Badge
                                            :class="[
                                                customer.latest_call_status ===
                                                'ended'
                                                    ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-500'
                                                    : customer.latest_call_status ===
                                                        'ongoing'
                                                      ? 'animate-pulse border border-blue-500/20 bg-blue-500/10 text-blue-500'
                                                      : 'border border-slate-500/20 bg-slate-500/10 text-slate-500',
                                            ]"
                                            class="px-2 py-0.5 text-[9px] font-black tracking-wider uppercase"
                                        >
                                            {{
                                                customer.latest_call_status ||
                                                'N/A'
                                            }}
                                        </Badge>
                                    </div>
                                    <div
                                        class="flex items-center gap-1 text-[9px] font-bold text-muted-foreground"
                                    >
                                        <Clock class="h-3 w-3" />
                                        {{ customer.latest_call_date }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 pl-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openDetailModal(customer)"
                                            class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50/50 px-2.5 py-1 text-xs font-bold text-indigo-700 transition-colors hover:bg-indigo-600 hover:text-white dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-600 dark:hover:text-white"
                                            title="View Customer Profile & Jobs History"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                            <span>Details & Jobs</span>
                                        </button>
                                        <button
                                            v-if="customer.is_profile"
                                            @click="openEditModal(customer)"
                                            class="flex cursor-pointer items-center gap-1 rounded-lg border border-slate-200 px-2 py-1 text-xs font-bold text-slate-600 transition-colors hover:border-indigo-500/30 hover:bg-indigo-50 hover:text-indigo-600 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800"
                                            title="Edit Customer Profile"
                                        >
                                            <Edit class="h-3.5 w-3.5" />
                                            <span>Edit</span>
                                        </button>
                                        <button
                                            v-if="customer.is_profile && permissions?.canDelete !== false"
                                            @click="deleteCustomer(customer.id!)"
                                            class="flex cursor-pointer items-center gap-1 rounded-lg border border-rose-200 px-2 py-1 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50 dark:border-rose-950 dark:text-rose-400 dark:hover:bg-rose-950/50"
                                            title="Delete Customer Profile"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="customers.length === 0">
                                <td
                                    colspan="6"
                                    class="py-8 text-center font-semibold text-muted-foreground italic"
                                >
                                    No customer interactions or bookings
                                    recorded yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Customer Modal -->
        <Dialog :open="showAddModal" @update:open="showAddModal = $event">
            <DialogContent
                class="rounded-2xl border-3 border-slate-300 bg-white p-6 shadow-2xl sm:max-w-md dark:border-slate-800 dark:bg-slate-900"
            >
                <DialogHeader>
                    <DialogTitle
                        class="flex items-center gap-2 text-lg font-black text-slate-900 uppercase dark:text-white"
                    >
                        <UserPlus class="h-5 w-5 text-indigo-500" />
                        <span>Add Customer Profile</span>
                    </DialogTitle>
                    <DialogDescription class="text-xs font-medium"
                        >Create a new customer profile. Phone number must be
                        unique per tenant.</DialogDescription
                    >
                </DialogHeader>
                <form @submit.prevent="submitAdd" class="space-y-4 pt-4">
                    <div class="space-y-1.5">
                        <Label
                            for="name"
                            class="text-[10px] font-black text-slate-500 uppercase dark:text-slate-400"
                            >Full Name</Label
                        >
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            placeholder="e.g. John Doe"
                            class="border-2 border-slate-200 dark:border-slate-800"
                        />
                        <span
                            v-if="form.errors.name"
                            class="text-[10px] font-bold text-rose-500"
                            >{{ form.errors.name }}</span
                        >
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="phone"
                            class="text-[10px] font-black text-slate-500 uppercase dark:text-slate-400"
                            >Phone Number</Label
                        >
                        <Input
                            id="phone"
                            v-model="form.phone"
                            required
                            placeholder="e.g. +15551112222"
                            class="border-2 border-slate-200 dark:border-slate-800"
                        />
                        <span
                            v-if="form.errors.phone"
                            class="text-[10px] font-bold text-rose-500"
                            >{{ form.errors.phone }}</span
                        >
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="email"
                            class="text-[10px] font-black text-slate-500 uppercase dark:text-slate-400"
                            >Email Address (Optional)</Label
                        >
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            placeholder="e.g. john@example.com"
                            class="border-2 border-slate-200 dark:border-slate-800"
                        />
                        <span
                            v-if="form.errors.email"
                            class="text-[10px] font-bold text-rose-500"
                            >{{ form.errors.email }}</span
                        >
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="notes"
                            class="text-[10px] font-black text-slate-500 uppercase dark:text-slate-400"
                            >Internal Notes (Optional)</Label
                        >
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Any preferences or service history details..."
                            rows="3"
                            class="border-2 border-slate-200 dark:border-slate-800"
                        />
                        <span
                            v-if="form.errors.notes"
                            class="text-[10px] font-bold text-rose-500"
                            >{{ form.errors.notes }}</span
                        >
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
                            <span>Save Customer</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Import CSV Modal -->
        <Dialog :open="showImportModal" @update:open="showImportModal = $event">
            <DialogContent
                class="rounded-2xl border-3 border-slate-300 bg-white p-6 shadow-2xl sm:max-w-md dark:border-slate-800 dark:bg-slate-900"
            >
                <DialogHeader>
                    <DialogTitle
                        class="flex items-center gap-2 text-lg font-black text-slate-900 uppercase dark:text-white"
                    >
                        <FileSpreadsheet class="h-5 w-5 text-indigo-500" />
                        <span>Bulk Import Customers</span>
                    </DialogTitle>
                    <DialogDescription class="text-xs font-medium"
                        >Upload a CSV file containing your customer list.
                        Existing profiles matching phone numbers will be
                        updated.</DialogDescription
                    >
                </DialogHeader>
                <form @submit.prevent="submitImport" class="space-y-4 pt-4">
                    <div
                        class="space-y-1.5 rounded-xl border-2 border-indigo-100 bg-indigo-50/50 p-3 text-xs text-slate-600 dark:border-indigo-900/50 dark:bg-indigo-950/20 dark:text-slate-400"
                    >
                        <div
                            class="flex items-center gap-1 font-bold text-indigo-600 dark:text-indigo-400"
                        >
                            <FileText class="h-3.5 w-3.5" /> CSV Schema
                            Requirements:
                        </div>
                        <p class="leading-relaxed">
                            Your CSV file must include headers in the first row.
                            The columns
                            <code
                                class="rounded bg-slate-100 px-1 font-bold text-slate-800 dark:bg-slate-800 dark:text-slate-200"
                                >name</code
                            >
                            and
                            <code
                                class="rounded bg-slate-100 px-1 font-bold text-slate-800 dark:bg-slate-800 dark:text-slate-200"
                                >phone</code
                            >
                            are mandatory. Columns
                            <code
                                class="rounded bg-slate-100 px-1 font-bold text-slate-800 dark:bg-slate-800 dark:text-slate-200"
                                >email</code
                            >
                            and
                            <code
                                class="rounded bg-slate-100 px-1 font-bold text-slate-800 dark:bg-slate-800 dark:text-slate-200"
                                >notes</code
                            >
                            are optional.
                        </p>
                        <div
                            class="rounded border border-slate-200 bg-white p-2 pt-1.5 font-mono text-[9px] text-muted-foreground select-all dark:border-slate-800/80 dark:bg-slate-950"
                        >
                            name,phone,email,notes<br />
                            Alice Smith,+15551234567,alice@example.com,Vip
                            customer<br />
                            Bob Jones,+15559876543,,Prefers morning calls
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label
                            class="text-[10px] font-black text-slate-500 uppercase dark:text-slate-400"
                            >Upload CSV File</Label
                        >
                        <input
                            type="file"
                            ref="fileInput"
                            accept=".csv"
                            class="hidden"
                            @change="handleFileChange"
                        />
                        <div
                            @click="triggerFileSelect"
                            class="flex cursor-pointer flex-col items-center justify-center space-y-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50/50 p-8 text-center transition-colors hover:border-indigo-500 dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-indigo-500"
                        >
                            <Upload class="h-8 w-8 text-slate-400" />
                            <div
                                class="text-xs font-bold text-slate-700 dark:text-slate-300"
                            >
                                {{
                                    selectedFileName ||
                                    'Click to select CSV file'
                                }}
                            </div>
                            <div class="text-[10px] text-muted-foreground">
                                CSV files up to 2MB only
                            </div>
                        </div>
                        <span
                            v-if="fileError"
                            class="mt-1 block text-[10px] font-bold text-rose-500"
                            >{{ fileError }}</span
                        >
                        <span
                            v-if="importForm.errors.csv_file"
                            class="mt-1 block text-[10px] font-bold text-rose-500"
                            >{{ importForm.errors.csv_file }}</span
                        >
                    </div>

                    <DialogFooter class="flex justify-end gap-2 border-t pt-4">
                        <Button
                            type="button"
                            variant="ghost"
                            @click="showImportModal = false"
                            class="cursor-pointer text-xs font-bold uppercase"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="
                                importForm.processing || !importForm.csv_file
                            "
                            class="flex cursor-pointer items-center gap-1.5 rounded-xl border-2 border-b-4 border-indigo-600 border-indigo-800 bg-indigo-600 px-6 py-2.5 text-xs font-black tracking-wide text-white uppercase shadow-md transition-all hover:border-indigo-700 hover:bg-indigo-500 active:border-b-0 disabled:pointer-events-none disabled:opacity-50"
                        >
                            <Loader2
                                v-if="importForm.processing"
                                class="h-4 w-4 animate-spin"
                            />
                            <span>Import Customers</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Customer Modal -->
        <Dialog :open="showEditModal" @update:open="showEditModal = $event">
            <DialogContent
                class="rounded-2xl border-3 border-slate-300 bg-white p-6 shadow-2xl sm:max-w-md dark:border-slate-800 dark:bg-slate-900"
            >
                <DialogHeader>
                    <DialogTitle
                        class="flex items-center gap-2 text-lg font-black text-slate-900 uppercase dark:text-white"
                    >
                        <Edit class="h-5 w-5 text-indigo-500" />
                        <span>Edit Customer Profile</span>
                    </DialogTitle>
                    <DialogDescription class="text-xs font-medium"
                        >Update customer contact details and notes.</DialogDescription
                    >
                </DialogHeader>
                <form @submit.prevent="submitUpdate" class="space-y-4 pt-4">
                    <div class="space-y-1.5">
                        <Label
                            for="edit_name"
                            class="text-[10px] font-black text-slate-500 uppercase dark:text-slate-400"
                            >Full Name</Label
                        >
                        <Input
                            id="edit_name"
                            v-model="editForm.name"
                            required
                            class="border-2 border-slate-200 dark:border-slate-800"
                        />
                        <span
                            v-if="editForm.errors.name"
                            class="text-[10px] font-bold text-rose-500"
                            >{{ editForm.errors.name }}</span
                        >
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="edit_phone"
                            class="text-[10px] font-black text-slate-500 uppercase dark:text-slate-400"
                            >Phone Number</Label
                        >
                        <Input
                            id="edit_phone"
                            v-model="editForm.phone"
                            required
                            class="border-2 border-slate-200 dark:border-slate-800"
                        />
                        <span
                            v-if="editForm.errors.phone"
                            class="text-[10px] font-bold text-rose-500"
                            >{{ editForm.errors.phone }}</span
                        >
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="edit_email"
                            class="text-[10px] font-black text-slate-500 uppercase dark:text-slate-400"
                            >Email Address (Optional)</Label
                        >
                        <Input
                            id="edit_email"
                            type="email"
                            v-model="editForm.email"
                            class="border-2 border-slate-200 dark:border-slate-800"
                        />
                        <span
                            v-if="editForm.errors.email"
                            class="text-[10px] font-bold text-rose-500"
                            >{{ editForm.errors.email }}</span
                        >
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="edit_notes"
                            class="text-[10px] font-black text-slate-500 uppercase dark:text-slate-400"
                            >Internal Notes (Optional)</Label
                        >
                        <Textarea
                            id="edit_notes"
                            v-model="editForm.notes"
                            rows="3"
                            class="border-2 border-slate-200 dark:border-slate-800"
                        />
                        <span
                            v-if="editForm.errors.notes"
                            class="text-[10px] font-bold text-rose-500"
                            >{{ editForm.errors.notes }}</span
                        >
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
                            class="flex cursor-pointer items-center gap-1.5 rounded-xl border-2 border-b-4 border-indigo-600 border-indigo-800 bg-indigo-600 px-6 py-2.5 text-xs font-black tracking-wide text-white uppercase shadow-md transition-all hover:border-indigo-700 hover:bg-indigo-500 active:border-b-0"
                        >
                            <Loader2
                                v-if="editForm.processing"
                                class="h-4 w-4 animate-spin"
                            />
                            <span>Update Profile</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Customer Details & Jobs History Modal -->
        <Dialog :open="showDetailModal" @update:open="showDetailModal = $event">
            <DialogContent
                class="rounded-3xl border-3 border-slate-300 bg-white p-6 shadow-2xl sm:max-w-3xl dark:border-slate-800 dark:bg-slate-900"
            >
                <DialogHeader v-if="selectedDetailCustomer">
                    <div class="flex items-start justify-between gap-4 border-b pb-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl border-2 border-indigo-500/30 bg-indigo-500/10 text-lg font-black text-indigo-600 dark:text-indigo-400"
                            >
                                {{ selectedDetailCustomer.name[0] }}
                            </div>
                            <div>
                                <DialogTitle class="text-xl font-black text-slate-900 uppercase dark:text-white flex items-center gap-2">
                                    <span>{{ selectedDetailCustomer.name }}</span>
                                    <Badge
                                        v-if="selectedDetailCustomer.is_profile"
                                        class="bg-indigo-500/10 text-indigo-600 border border-indigo-500/30 text-[9px] font-bold uppercase"
                                    >
                                        Verified Profile
                                    </Badge>
                                </DialogTitle>
                                <DialogDescription class="mt-1 flex flex-wrap items-center gap-3 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                    <span class="flex items-center gap-1 font-mono">
                                        <Phone class="h-3.5 w-3.5 text-slate-400" />
                                        {{ selectedDetailCustomer.phone }}
                                    </span>
                                    <span v-if="selectedDetailCustomer.email" class="flex items-center gap-1">
                                        <Mail class="h-3.5 w-3.5 text-slate-400" />
                                        {{ selectedDetailCustomer.email }}
                                    </span>
                                </DialogDescription>
                            </div>
                        </div>
                    </div>
                </DialogHeader>

                <div v-if="selectedDetailCustomer" class="space-y-6 pt-2">
                    <!-- Navigation Tabs -->
                    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                        <button
                            @click="activeDetailTab = 'jobs'"
                            :class="[
                                activeDetailTab === 'jobs'
                                    ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300'
                                    : 'border-transparent text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800',
                            ]"
                            class="flex cursor-pointer items-center gap-1.5 rounded-xl border-2 px-3.5 py-1.5 text-xs font-bold transition-all"
                        >
                            <Wrench class="h-4 w-4" />
                            <span>Jobs Done ({{ selectedDetailCustomer.jobs?.length || selectedDetailCustomer.total_jobs }})</span>
                        </button>
                        <button
                            @click="activeDetailTab = 'bookings'"
                            :class="[
                                activeDetailTab === 'bookings'
                                    ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300'
                                    : 'border-transparent text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800',
                            ]"
                            class="flex cursor-pointer items-center gap-1.5 rounded-xl border-2 px-3.5 py-1.5 text-xs font-bold transition-all"
                        >
                            <CalendarDays class="h-4 w-4" />
                            <span>AI Bookings ({{ selectedDetailCustomer.bookings?.length || selectedDetailCustomer.total_bookings }})</span>
                        </button>
                        <button
                            @click="activeDetailTab = 'calls'"
                            :class="[
                                activeDetailTab === 'calls'
                                    ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300'
                                    : 'border-transparent text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800',
                            ]"
                            class="flex cursor-pointer items-center gap-1.5 rounded-xl border-2 px-3.5 py-1.5 text-xs font-bold transition-all"
                        >
                            <Phone class="h-4 w-4" />
                            <span>Call Logs ({{ selectedDetailCustomer.call_logs?.length || selectedDetailCustomer.total_calls }})</span>
                        </button>
                    </div>

                    <!-- Tab Content: Service Jobs -->
                    <div v-if="activeDetailTab === 'jobs'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-black tracking-wider text-slate-500 uppercase">Service Jobs & Dispatch History</h4>
                        </div>

                        <div v-if="selectedDetailCustomer.jobs && selectedDetailCustomer.jobs.length > 0" class="space-y-3 max-h-96 overflow-y-auto pr-1">
                            <div
                                v-for="job in selectedDetailCustomer.jobs"
                                :key="job.id"
                                class="rounded-2xl border-2 border-slate-200 bg-slate-50/50 p-4 transition-all hover:border-indigo-500/40 dark:border-slate-800 dark:bg-slate-900/50"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h5 class="text-sm font-black text-slate-900 dark:text-white">{{ job.title }}</h5>
                                        <p v-if="job.description" class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ job.description }}</p>
                                    </div>
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

                                <!-- Tech & Date info -->
                                <div class="mt-3 flex flex-wrap items-center gap-4 text-[11px] font-semibold text-slate-500 dark:text-slate-400 border-t pt-2 dark:border-slate-800">
                                    <span class="flex items-center gap-1">
                                        <User class="h-3.5 w-3.5 text-indigo-500" />
                                        Technician: <strong class="text-slate-800 dark:text-slate-200">{{ job.employee_name }}</strong>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Clock class="h-3.5 w-3.5 text-slate-400" />
                                        Logged: {{ job.created_at }}
                                    </span>
                                </div>

                                <!-- Job Steps Checklist -->
                                <div v-if="job.steps && job.steps.length > 0" class="mt-3 space-y-1 bg-white dark:bg-slate-950 p-2.5 rounded-xl border">
                                    <div class="text-[10px] font-black uppercase text-slate-400">Workflow Progress Checklist:</div>
                                    <div v-for="(step, idx) in job.steps" :key="idx" class="flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300">
                                        <CheckCircle2 class="h-3.5 w-3.5 text-emerald-500 flex-shrink-0" />
                                        <span>{{ step }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="rounded-2xl border-2 border-dashed border-slate-200 p-8 text-center dark:border-slate-800">
                            <Wrench class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-700" />
                            <p class="mt-2 text-xs font-semibold text-slate-500 italic">No formal service jobs created for this customer yet.</p>
                        </div>
                    </div>

                    <!-- Tab Content: AI Bookings -->
                    <div v-if="activeDetailTab === 'bookings'" class="space-y-4">
                        <div v-if="selectedDetailCustomer.bookings && selectedDetailCustomer.bookings.length > 0" class="space-y-2 max-h-96 overflow-y-auto pr-1">
                            <div
                                v-for="booking in selectedDetailCustomer.bookings"
                                :key="booking.id"
                                class="flex items-center justify-between rounded-xl border-2 border-slate-200 bg-slate-50/50 p-3 text-xs dark:border-slate-800 dark:bg-slate-900/50"
                            >
                                <div class="space-y-0.5">
                                    <div class="font-bold text-slate-900 uppercase dark:text-white flex items-center gap-2">
                                        <span>{{ booking.service_type }}</span>
                                        <Badge variant="outline" class="text-[9px] font-bold uppercase border-indigo-500/20 text-indigo-600">
                                            {{ booking.status }}
                                        </Badge>
                                    </div>
                                    <div class="text-[11px] text-muted-foreground flex items-center gap-2">
                                        <span>Time: {{ booking.requested_time }}</span>
                                        <span>•</span>
                                        <span>Tech: {{ booking.technician_name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="rounded-2xl border-2 border-dashed border-slate-200 p-8 text-center dark:border-slate-800">
                            <CalendarDays class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-700" />
                            <p class="mt-2 text-xs font-semibold text-slate-500 italic">No AI receptionist bookings found for this customer.</p>
                        </div>
                    </div>

                    <!-- Tab Content: Call Logs -->
                    <div v-if="activeDetailTab === 'calls'" class="space-y-4">
                        <div v-if="selectedDetailCustomer.call_logs && selectedDetailCustomer.call_logs.length > 0" class="space-y-2 max-h-96 overflow-y-auto pr-1">
                            <div
                                v-for="call in selectedDetailCustomer.call_logs"
                                :key="call.id"
                                class="rounded-xl border-2 border-slate-200 bg-slate-50/50 p-3 text-xs dark:border-slate-800 dark:bg-slate-900/50"
                            >
                                <div class="flex items-center justify-between font-bold text-slate-800 dark:text-slate-200">
                                    <span class="flex items-center gap-1.5">
                                        <Phone class="h-3.5 w-3.5 text-indigo-500" />
                                        Call Log #{{ call.id }}
                                    </span>
                                    <span class="text-[10px] text-muted-foreground font-normal">{{ call.created_at }}</span>
                                </div>
                                <p class="mt-1 text-slate-600 dark:text-slate-400 italic">"{{ call.summary }}"</p>
                            </div>
                        </div>
                        <div v-else class="rounded-2xl border-2 border-dashed border-slate-200 p-8 text-center dark:border-slate-800">
                            <Phone class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-700" />
                            <p class="mt-2 text-xs font-semibold text-slate-500 italic">No call history recorded for this customer.</p>
                        </div>
                    </div>
                </div>

                <DialogFooter class="flex justify-end border-t pt-4">
                    <Button
                        type="button"
                        variant="ghost"
                        @click="showDetailModal = false"
                        class="cursor-pointer text-xs font-bold uppercase"
                        >Close</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
