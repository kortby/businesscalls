<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
    FileText
} from '@lucide/vue';

defineOptions({ layout: AppLayout });

defineProps<{
    customers: Array<{
        id: number | null;
        phone: string;
        name: string;
        email: string;
        notes: string;
        total_bookings: number;
        total_calls: number;
        latest_call_date: string;
        latest_call_summary: string;
        latest_call_status: string;
        is_profile: boolean;
    }>;
}>();

const showAddModal = ref(false);
const showImportModal = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const fileError = ref<string | null>(null);
const selectedFileName = ref<string | null>(null);

const form = useForm({
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

const submitAdd = () => {
    form.post(route('customers.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
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
        if (extension !== 'csv' && file.type !== 'text/csv' && file.type !== 'application/vnd.ms-excel') {
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
    importForm.post(route('customers.import'), {
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

    <div class="px-6 py-6 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <Heading
                title="Customers & Callers"
                description="Overview of customer profiles, booking volumes, and voice AI transcript history"
            />
            <div class="flex items-center gap-3">
                <Button
                    @click="showImportModal = true"
                    class="bg-white hover:bg-slate-50 text-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800 dark:text-slate-200 border-3 border-b-6 border-slate-300 dark:border-slate-800 rounded-xl font-black tracking-wide uppercase px-5 py-2.5 active:border-b-3 active:translate-y-0.5 transition-all cursor-pointer shadow-md text-xs flex items-center gap-1.5"
                >
                    <Upload class="h-4 w-4 text-slate-500" /> Import CSV
                </Button>
                <Button
                    @click="openAddModal()"
                    class="bg-emerald-500 hover:bg-emerald-400 text-white font-black tracking-wide uppercase px-5 py-2.5 rounded-xl border-2 border-emerald-500 border-b-4 border-emerald-700 hover:border-emerald-600 active:border-b-0 active:translate-y-1 transition-all cursor-pointer shadow-md text-xs flex items-center gap-1.5"
                >
                    <Plus class="h-4 w-4" /> Add Customer
                </Button>
            </div>
        </div>

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
                                <!-- Name & Email -->
                                <td class="py-4 pr-4 font-black text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-2.5">
                                        <div 
                                            :class="[
                                                customer.is_profile 
                                                    ? 'bg-indigo-500/10 border-indigo-500/30 text-indigo-600' 
                                                    : 'bg-slate-500/10 border-slate-500/30 text-slate-600 dark:text-slate-400'
                                            ]"
                                            class="h-8 w-8 rounded-lg border-2 flex items-center justify-center text-xs font-black"
                                        >
                                            {{ customer.name[0] }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span>{{ customer.name }}</span>
                                                <Badge 
                                                    v-if="!customer.is_profile" 
                                                    variant="outline" 
                                                    class="text-[9px] font-bold border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-1.5 py-0 cursor-pointer hover:bg-indigo-500/10 hover:text-indigo-600 hover:border-indigo-500/30"
                                                    @click="openAddModal({ name: customer.name, phone: customer.phone })"
                                                    title="Click to register this caller as a customer profile"
                                                >
                                                    <UserPlus class="h-2.5 w-2.5 mr-0.5" /> Raw Caller
                                                </Badge>
                                            </div>
                                            <div v-if="customer.email" class="text-[10px] font-medium text-muted-foreground flex items-center gap-1 mt-0.5">
                                                <Mail class="h-3 w-3 text-slate-400" />
                                                <span>{{ customer.email }}</span>
                                            </div>
                                        </div>
                                    </div>
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

        <!-- Add Customer Modal -->
        <Dialog :open="showAddModal" @update:open="showAddModal = $event">
            <DialogContent class="sm:max-w-md rounded-2xl border-3 border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl p-6">
                <DialogHeader>
                    <DialogTitle class="text-lg font-black uppercase text-slate-900 dark:text-white flex items-center gap-2">
                        <UserPlus class="h-5 w-5 text-indigo-500" />
                        <span>Add Customer Profile</span>
                    </DialogTitle>
                    <DialogDescription class="text-xs font-medium">Create a new customer profile. Phone number must be unique per tenant.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitAdd" class="space-y-4 pt-4">
                    <div class="space-y-1.5">
                        <Label for="name" class="text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">Full Name</Label>
                        <Input id="name" v-model="form.name" required placeholder="e.g. John Doe" class="border-2 border-slate-200 dark:border-slate-800" />
                        <span v-if="form.errors.name" class="text-[10px] font-bold text-rose-500">{{ form.errors.name }}</span>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="phone" class="text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">Phone Number</Label>
                        <Input id="phone" v-model="form.phone" required placeholder="e.g. +15551112222" class="border-2 border-slate-200 dark:border-slate-800" />
                        <span v-if="form.errors.phone" class="text-[10px] font-bold text-rose-500">{{ form.errors.phone }}</span>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="email" class="text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">Email Address (Optional)</Label>
                        <Input id="email" type="email" v-model="form.email" placeholder="e.g. john@example.com" class="border-2 border-slate-200 dark:border-slate-800" />
                        <span v-if="form.errors.email" class="text-[10px] font-bold text-rose-500">{{ form.errors.email }}</span>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="notes" class="text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">Internal Notes (Optional)</Label>
                        <Textarea id="notes" v-model="form.notes" placeholder="Any preferences or service history details..." rows="3" class="border-2 border-slate-200 dark:border-slate-800" />
                        <span v-if="form.errors.notes" class="text-[10px] font-bold text-rose-500">{{ form.errors.notes }}</span>
                    </div>

                    <DialogFooter class="pt-4 border-t gap-2 flex justify-end">
                        <Button type="button" variant="ghost" @click="showAddModal = false" class="text-xs uppercase font-bold cursor-pointer">Cancel</Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-emerald-500 hover:bg-emerald-400 text-white font-black tracking-wide uppercase px-6 py-2.5 rounded-xl border-2 border-emerald-500 border-b-4 border-emerald-700 hover:border-emerald-600 active:border-b-0 cursor-pointer transition-all shadow-md text-xs flex items-center gap-1.5"
                        >
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <span>Save Customer</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Import CSV Modal -->
        <Dialog :open="showImportModal" @update:open="showImportModal = $event">
            <DialogContent class="sm:max-w-md rounded-2xl border-3 border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl p-6">
                <DialogHeader>
                    <DialogTitle class="text-lg font-black uppercase text-slate-900 dark:text-white flex items-center gap-2">
                        <FileSpreadsheet class="h-5 w-5 text-indigo-500" />
                        <span>Bulk Import Customers</span>
                    </DialogTitle>
                    <DialogDescription class="text-xs font-medium">Upload a CSV file containing your customer list. Existing profiles matching phone numbers will be updated.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitImport" class="space-y-4 pt-4">
                    <div class="bg-indigo-50/50 dark:bg-indigo-950/20 border-2 border-indigo-100 dark:border-indigo-900/50 rounded-xl p-3 text-xs space-y-1.5 text-slate-600 dark:text-slate-400">
                        <div class="font-bold flex items-center gap-1 text-indigo-600 dark:text-indigo-400">
                            <FileText class="h-3.5 w-3.5" /> CSV Schema Requirements:
                        </div>
                        <p class="leading-relaxed">
                            Your CSV file must include headers in the first row. The columns <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded font-bold text-slate-800 dark:text-slate-200">name</code> and <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded font-bold text-slate-800 dark:text-slate-200">phone</code> are mandatory. Columns <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded font-bold text-slate-800 dark:text-slate-200">email</code> and <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded font-bold text-slate-800 dark:text-slate-200">notes</code> are optional.
                        </p>
                        <div class="pt-1.5 font-mono text-[9px] bg-white dark:bg-slate-950 p-2 rounded border border-slate-200 dark:border-slate-800/80 text-muted-foreground select-all">
                            name,phone,email,notes<br>
                            Alice Smith,+15551234567,alice@example.com,Vip customer<br>
                            Bob Jones,+15559876543,,Prefers morning calls
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">Upload CSV File</Label>
                        <input
                            type="file"
                            ref="fileInput"
                            accept=".csv"
                            class="hidden"
                            @change="handleFileChange"
                        />
                        <div 
                            @click="triggerFileSelect"
                            class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-indigo-500 dark:hover:border-indigo-500 rounded-xl p-8 text-center cursor-pointer transition-colors bg-slate-50/50 dark:bg-slate-900/50 space-y-2 flex flex-col items-center justify-center"
                        >
                            <Upload class="h-8 w-8 text-slate-400" />
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                {{ selectedFileName || 'Click to select CSV file' }}
                            </div>
                            <div class="text-[10px] text-muted-foreground">
                                CSV files up to 2MB only
                            </div>
                        </div>
                        <span v-if="fileError" class="text-[10px] font-bold text-rose-500 block mt-1">{{ fileError }}</span>
                        <span v-if="importForm.errors.csv_file" class="text-[10px] font-bold text-rose-500 block mt-1">{{ importForm.errors.csv_file }}</span>
                    </div>

                    <DialogFooter class="pt-4 border-t gap-2 flex justify-end">
                        <Button type="button" variant="ghost" @click="showImportModal = false" class="text-xs uppercase font-bold cursor-pointer">Cancel</Button>
                        <Button
                            type="submit"
                            :disabled="importForm.processing || !importForm.csv_file"
                            class="bg-indigo-600 hover:bg-indigo-500 text-white font-black tracking-wide uppercase px-6 py-2.5 rounded-xl border-2 border-indigo-600 border-b-4 border-indigo-800 hover:border-indigo-700 active:border-b-0 cursor-pointer transition-all shadow-md text-xs flex items-center gap-1.5 disabled:opacity-50 disabled:pointer-events-none"
                        >
                            <Loader2 v-if="importForm.processing" class="h-4 w-4 animate-spin" />
                            <span>Import Customers</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
