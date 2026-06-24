<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { store as storeEmployee, update as updateEmployee, destroy as destroyEmployee } from '@/routes/employees';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
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
import { Plus, Trash2, Edit, Wrench, ShieldAlert } from '@lucide/vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    employees: Array<{
        id: number;
        first_name: string;
        last_name: string;
        phone: string;
        skills: string[];
        notification_preference: string;
        user_id: number | null;
        user?: {
            email: string;
        } | null;
    }>;
}>();

const showAddModal = ref(false);
const showEditModal = ref(false);
const editingEmployee = ref<any>(null);

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    skills: [] as string[],
    notification_preference: 'sms',
    email: '',
});

const editForm = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    skills: [] as string[],
    notification_preference: 'sms',
});

const newSkill = ref('');

const addSkill = (isEdit = false) => {
    const val = newSkill.value.trim().toLowerCase();
    if (!val) return;
    if (isEdit) {
        if (!editForm.skills.includes(val)) {
            editForm.skills.push(val);
        }
    } else {
        if (!form.skills.includes(val)) {
            form.skills.push(val);
        }
    }
    newSkill.value = '';
};

const removeSkill = (index: number, isEdit = false) => {
    if (isEdit) {
        editForm.skills.splice(index, 1);
    } else {
        form.skills.splice(index, 1);
    }
};

const submitAdd = () => {
    form.post(storeEmployee.url(), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
};

const openEditModal = (employee: any) => {
    editingEmployee.value = employee;
    editForm.first_name = employee.first_name;
    editForm.last_name = employee.last_name;
    editForm.phone = employee.phone;
    editForm.skills = [...(employee.skills || [])];
    editForm.notification_preference = employee.notification_preference;
    showEditModal.value = true;
};

const submitUpdate = () => {
    editForm.put(updateEmployee.url(editingEmployee.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editingEmployee.value = null;
        },
    });
};

const deleteEmployee = (id: number) => {
    if (confirm('Are you sure you want to remove this technician? This action is tracked in compliance logs.')) {
        router.delete(destroyEmployee.url(id));
    }
};
</script>

<template>
    <Head title="Employee Management" />

    <div class="px-6 py-6 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <Heading
                title="Technicians Directory"
                description="Create and manage your active mobile service technician profiles"
            />
            <Button
                @click="showAddModal = true"
                class="bg-emerald-500 hover:bg-emerald-400 text-white font-black tracking-wide uppercase px-5 py-2.5 rounded-xl border-2 border-emerald-500 border-b-4 border-emerald-700 hover:border-emerald-600 active:border-b-0 active:translate-y-1 transition-all cursor-pointer shadow-md text-xs flex items-center gap-1.5"
            >
                <Plus class="h-4 w-4" /> Add Technician
            </Button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
                v-for="employee in employees" 
                :key="employee.id"
                class="border-3 border-b-6 border-slate-300 dark:border-slate-800 rounded-2xl bg-card p-5 relative flex flex-col justify-between"
            >
                <div class="space-y-4">
                    <div class="flex justify-between items-start gap-4">
                        <div class="space-y-1">
                            <h3 class="text-base font-black text-slate-900 dark:text-white leading-tight">
                                {{ employee.first_name }} {{ employee.last_name }}
                            </h3>
                            <p class="text-xs text-muted-foreground font-bold">{{ employee.phone }}</p>
                            <p v-if="employee.user?.email" class="text-[10px] text-indigo-500 font-semibold italic">
                                Account: {{ employee.user.email }}
                            </p>
                        </div>
                        <Wrench class="h-6 w-6 text-indigo-500/30" />
                    </div>

                    <!-- Skills profile -->
                    <div class="space-y-1.5">
                        <div class="text-[9px] font-black uppercase tracking-wider text-muted-foreground">Skills Profile</div>
                        <div class="flex flex-wrap gap-1">
                            <Badge 
                                v-for="skill in employee.skills" 
                                :key="skill"
                                variant="outline"
                                class="text-[9px] font-bold uppercase border-slate-300"
                            >
                                {{ skill }}
                            </Badge>
                            <div v-if="!employee.skills || employee.skills.length === 0" class="text-[10px] text-amber-600 italic">
                                No specific skills tagged.
                            </div>
                        </div>
                    </div>

                    <!-- Alert preference -->
                    <div class="space-y-1">
                        <div class="text-[9px] font-black uppercase tracking-wider text-muted-foreground">Dispatch Alerts</div>
                        <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 capitalize">
                            Via {{ employee.notification_preference }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-3">
                    <button
                        @click="openEditModal(employee)"
                        class="text-xs font-bold text-slate-600 hover:text-indigo-600 flex items-center gap-1 cursor-pointer transition-colors"
                    >
                        <Edit class="h-3.5 w-3.5" /> Edit Profile
                    </button>
                    <button
                        @click="deleteEmployee(employee.id)"
                        class="text-xs font-bold text-rose-500 hover:text-rose-700 flex items-center gap-1 cursor-pointer transition-colors"
                    >
                        <Trash2 class="h-3.5 w-3.5" /> Remove
                    </button>
                </div>
            </div>
        </div>

        <div v-if="employees.length === 0" class="border-2 border-dashed rounded-2xl p-12 text-center text-muted-foreground italic font-semibold text-sm">
            No technicians registered. Add a profile above!
        </div>

        <!-- Add Technician Modal -->
        <Dialog :open="showAddModal" @update:open="showAddModal = $event">
            <DialogContent class="sm:max-w-md rounded-2xl border-3 border-slate-300">
                <DialogHeader>
                    <DialogTitle class="text-lg font-black uppercase text-slate-900 dark:text-white">Add Technician</DialogTitle>
                    <DialogDescription class="text-xs font-medium">Create a new service technician profile.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitAdd" class="space-y-4 pt-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="first_name" class="text-[10px] font-black uppercase">First Name</Label>
                            <Input id="first_name" v-model="form.first_name" required placeholder="John" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="last_name" class="text-[10px] font-black uppercase">Last Name</Label>
                            <Input id="last_name" v-model="form.last_name" required placeholder="Doe" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="phone" class="text-[10px] font-black uppercase">Phone Number</Label>
                            <Input id="phone" v-model="form.phone" required placeholder="555-010-1001" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="email" class="text-[10px] font-black uppercase">Email Address (Optional)</Label>
                            <Input id="email" type="email" v-model="form.email" placeholder="john.doe@company.com" />
                            <span class="text-[8px] text-muted-foreground leading-none">Auto-creates login profile if provided.</span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="pref" class="text-[10px] font-black uppercase">Alert Preference</Label>
                        <Select v-model="form.notification_preference">
                            <SelectTrigger id="pref" class="w-full">
                                <SelectValue placeholder="Select channel" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="sms">SMS Only</SelectItem>
                                <SelectItem value="email">Email Only</SelectItem>
                                <SelectItem value="both">Both Channels</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-[10px] font-black uppercase">Skills Tags</Label>
                        <div class="flex gap-2">
                            <Input v-model="newSkill" @keydown.enter.prevent="addSkill(false)" placeholder="plumbing, HVAC..." class="flex-1" />
                            <Button type="button" size="sm" @click="addSkill(false)" class="border border-indigo-500 font-bold text-xs uppercase px-3 py-1.5">Add</Button>
                        </div>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <Badge 
                                v-for="(skill, index) in form.skills" 
                                :key="skill"
                                class="text-[9px] font-bold uppercase gap-1 px-2 py-0.5"
                            >
                                {{ skill }}
                                <span @click="removeSkill(index, false)" class="text-[8px] text-rose-400 hover:text-rose-600 cursor-pointer ml-1 font-bold">x</span>
                            </Badge>
                        </div>
                    </div>

                    <DialogFooter class="pt-4 border-t gap-2">
                        <Button type="button" variant="ghost" @click="showAddModal = false" class="text-xs uppercase font-bold">Cancel</Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-emerald-500 hover:bg-emerald-400 text-white font-black tracking-wide uppercase px-6 py-2.5 rounded-xl border-2 border-emerald-500 border-b-4 border-emerald-700 hover:border-emerald-600 active:border-b-0 cursor-pointer transition-all shadow-md text-xs"
                        >
                            Save Technician
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Technician Modal -->
        <Dialog :open="showEditModal" @update:open="showEditModal = $event">
            <DialogContent class="sm:max-w-md rounded-2xl border-3 border-slate-300">
                <DialogHeader>
                    <DialogTitle class="text-lg font-black uppercase text-slate-900 dark:text-white">Edit Profile</DialogTitle>
                    <DialogDescription class="text-xs font-medium">Update technician settings.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitUpdate" class="space-y-4 pt-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="edit_first_name" class="text-[10px] font-black uppercase">First Name</Label>
                            <Input id="edit_first_name" v-model="editForm.first_name" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="edit_last_name" class="text-[10px] font-black uppercase">Last Name</Label>
                            <Input id="edit_last_name" v-model="editForm.last_name" required />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit_phone" class="text-[10px] font-black uppercase">Phone Number</Label>
                        <Input id="edit_phone" v-model="editForm.phone" required />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit_pref" class="text-[10px] font-black uppercase">Alert Preference</Label>
                        <Select v-model="editForm.notification_preference">
                            <SelectTrigger id="edit_pref" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="sms">SMS Only</SelectItem>
                                <SelectItem value="email">Email Only</SelectItem>
                                <SelectItem value="both">Both Channels</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-[10px] font-black uppercase">Skills Tags</Label>
                        <div class="flex gap-2">
                            <Input v-model="newSkill" @keydown.enter.prevent="addSkill(true)" placeholder="add new skill..." class="flex-1" />
                            <Button type="button" size="sm" @click="addSkill(true)" class="border border-indigo-500 font-bold text-xs uppercase px-3 py-1.5">Add</Button>
                        </div>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <Badge 
                                v-for="(skill, index) in editForm.skills" 
                                :key="skill"
                                class="text-[9px] font-bold uppercase gap-1 px-2 py-0.5"
                            >
                                {{ skill }}
                                <span @click="removeSkill(index, true)" class="text-[8px] text-rose-400 hover:text-rose-600 cursor-pointer ml-1 font-bold">x</span>
                            </Badge>
                        </div>
                    </div>

                    <DialogFooter class="pt-4 border-t gap-2">
                        <Button type="button" variant="ghost" @click="showEditModal = false" class="text-xs uppercase font-bold">Cancel</Button>
                        <Button
                            type="submit"
                            :disabled="editForm.processing"
                            class="bg-emerald-500 hover:bg-emerald-400 text-white font-black tracking-wide uppercase px-6 py-2.5 rounded-xl border-2 border-emerald-500 border-b-4 border-emerald-700 hover:border-emerald-600 active:border-b-0 cursor-pointer transition-all shadow-md text-xs"
                        >
                            Update Profile
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
