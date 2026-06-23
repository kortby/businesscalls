<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { 
    Bot, 
    Sparkles, 
    AlertCircle, 
    Plus, 
    Trash2, 
    DollarSign,
    Terminal,
    BookOpen,
    Info
} from '@lucide/vue';

defineOptions({
    layout: SettingsLayout,
});

const props = defineProps<{
    tenant: {
        id: number;
        name: string;
        settings: Record<string, any>;
    };
    settings: {
        ai_prompt?: string;
        emergency_fee?: string;
        emergency_rules?: string;
        pricing_list?: Array<{ service: string; price: string }>;
    };
}>();

// Form setup with initial values or defaults
const form = useForm({
    ai_prompt: props.settings.ai_prompt || 'Act professional, friendly, and efficient. Enforce technician active shifts and the mandatory 1.5-hour travel buffer on all bookings.',
    emergency_fee: props.settings.emergency_fee || '$150',
    emergency_rules: props.settings.emergency_rules || 'Escalate critical water leaks immediately.',
    pricing_list: props.settings.pricing_list || [
        { service: 'Plumbing Repair', price: '$120/hr' },
        { service: 'HVAC Tune-up', price: '$149 Flat' },
        { service: 'Electrical Diagnostics', price: '$95/hr' }
    ]
});

const isSuccess = ref(false);

const addPriceItem = () => {
    form.pricing_list.push({ service: '', price: '' });
};

const removePriceItem = (index: number) => {
    form.pricing_list.splice(index, 1);
};

const submit = () => {
    form.patch('/settings/prompt', {
        preserveScroll: true,
        onSuccess: () => {
            isSuccess.value = true;
            setTimeout(() => {
                isSuccess.value = false;
            }, 3000);
        }
    });
};
</script>

<template>
    <Head title="AI Prompt & pricing settings" />

    <div class="space-y-8 pb-12">
        <!-- Main Bold Header Section -->
        <div class="border-4 border-b-8 border-indigo-400 bg-indigo-50 dark:bg-indigo-950/20 dark:border-indigo-800 rounded-3xl p-6 shadow-xs flex flex-col md:flex-row items-center gap-6">
            <div class="h-16 w-16 bg-indigo-500 text-white rounded-2xl flex items-center justify-center border-2 border-b-6 border-indigo-700 shadow-sm shrink-0">
                <Bot class="h-10 w-10 animate-bounce" />
            </div>
            <div>
                <h2 class="text-2xl font-black text-indigo-900 dark:text-indigo-300 tracking-tight">AI Dispatcher Configurator</h2>
                <p class="text-sm text-indigo-700 dark:text-indigo-400 font-semibold mt-1">
                    Manage your voice agent's intelligence, custom instructions, emergency routing, and service rates.
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- 1. AI Instructions Card (Duolingo Style) -->
            <div class="border-3 border-b-6 border-slate-300 dark:border-slate-800 rounded-2xl bg-card p-6 relative">
                <div class="flex items-center gap-2 mb-4 border-b pb-3">
                    <Sparkles class="h-5 w-5 text-indigo-500" />
                    <span class="text-base font-black uppercase tracking-wider text-card-foreground">Voice Agent Prompt Instructions</span>
                </div>

                <div class="space-y-4">
                    <div class="bg-amber-500/10 border-2 border-amber-500/20 p-4 rounded-xl flex gap-3 items-start">
                        <Info class="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                        <div class="text-xs text-amber-800 dark:text-amber-300 leading-normal">
                            <span class="font-bold uppercase tracking-wider block mb-1">Dynamic Prompt Placeholders:</span>
                            You can customize your prompt with double curly braces values. Our system automatically binds these variables at runtime:
                            <div class="grid grid-cols-2 gap-2 mt-2 font-mono text-[10px]">
                                <span v-pre class="bg-white/50 dark:bg-black/20 px-1.5 py-0.5 rounded border"><code>{{business_name}}</code></span>
                                <span v-pre class="bg-white/50 dark:bg-black/20 px-1.5 py-0.5 rounded border"><code>{{custom_instructions}}</code></span>
                                <span v-pre class="bg-white/50 dark:bg-black/20 px-1.5 py-0.5 rounded border"><code>{{emergency_fee}}</code></span>
                                <span v-pre class="bg-white/50 dark:bg-black/20 px-1.5 py-0.5 rounded border"><code>{{service_list}}</code></span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-xs font-bold uppercase tracking-wider text-muted-foreground" for="ai_prompt">Custom AI Prompt Rules</Label>
                        <textarea
                            id="ai_prompt"
                            v-model="form.ai_prompt"
                            rows="4"
                            class="w-full rounded-xl border-2 border-slate-300 dark:border-slate-800 p-3 text-sm focus:border-indigo-500 focus:ring-0 focus:outline-hidden dark:bg-slate-900/30 transition-all"
                            placeholder="Enter instructions for the receptionist agent..."
                        ></textarea>
                        <p v-if="form.errors.ai_prompt" class="text-xs text-rose-500 mt-1 font-bold">{{ form.errors.ai_prompt }}</p>
                    </div>
                </div>
            </div>

            <!-- 2. Emergency Rules Card (Duolingo Style) -->
            <div class="border-3 border-b-6 border-slate-300 dark:border-slate-800 rounded-2xl bg-card p-6">
                <div class="flex items-center gap-2 mb-4 border-b pb-3">
                    <AlertCircle class="h-5 w-5 text-rose-500" />
                    <span class="text-base font-black uppercase tracking-wider text-card-foreground">Emergency Escalation Rules</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-1 space-y-2">
                        <Label class="text-xs font-bold uppercase tracking-wider text-muted-foreground" for="emergency_fee">Emergency Booking Fee</Label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-slate-400 font-bold">$</span>
                            <input
                                id="emergency_fee"
                                type="text"
                                v-model="form.emergency_fee"
                                class="w-full pl-7 rounded-xl border-2 border-slate-300 dark:border-slate-800 p-2.5 text-sm font-bold focus:border-indigo-500 focus:ring-0 focus:outline-hidden dark:bg-slate-900/30 transition-all"
                                placeholder="150"
                            />
                        </div>
                        <p v-if="form.errors.emergency_fee" class="text-xs text-rose-500 mt-1 font-bold">{{ form.errors.emergency_fee }}</p>
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <Label class="text-xs font-bold uppercase tracking-wider text-muted-foreground" for="emergency_rules">Emergency Handling Rules</Label>
                        <input
                            id="emergency_rules"
                            type="text"
                            v-model="form.emergency_rules"
                            class="w-full rounded-xl border-2 border-slate-300 dark:border-slate-800 p-2.5 text-sm focus:border-indigo-500 focus:ring-0 focus:outline-hidden dark:bg-slate-900/30 transition-all"
                            placeholder="What rules qualify as a call escalation?"
                        />
                        <p v-if="form.errors.emergency_rules" class="text-xs text-rose-500 mt-1 font-bold">{{ form.errors.emergency_rules }}</p>
                    </div>
                </div>
            </div>

            <!-- 3. Service Pricing Card (Duolingo Style) -->
            <div class="border-3 border-b-6 border-slate-300 dark:border-slate-800 rounded-2xl bg-card p-6">
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <div class="flex items-center gap-2">
                        <DollarSign class="h-5 w-5 text-emerald-500" />
                        <span class="text-base font-black uppercase tracking-wider text-card-foreground">Service Pricing List</span>
                    </div>
                    <Button 
                        type="button" 
                        variant="outline" 
                        size="sm" 
                        @click="addPriceItem" 
                        class="text-xs font-bold flex items-center gap-1 border-2 border-b-4 hover:border-b-2 active:border-b-0 cursor-pointer transition-all"
                    >
                        <Plus class="h-3.5 w-3.5" /> Add Service
                    </Button>
                </div>

                <div class="space-y-3 max-h-[250px] overflow-y-auto pr-1">
                    <div 
                        v-for="(item, index) in form.pricing_list" 
                        :key="index"
                        class="flex items-center gap-4 bg-muted/20 border-2 border-slate-200 dark:border-slate-800/80 rounded-xl p-3"
                    >
                        <div class="flex-1">
                            <input
                                type="text"
                                v-model="item.service"
                                class="w-full rounded-lg border-2 border-slate-300 dark:border-slate-800 p-1.5 text-xs focus:border-indigo-500 focus:ring-0 focus:outline-hidden dark:bg-slate-950/40"
                                placeholder="Service Name (e.g. Toilet Repair)"
                                required
                            />
                        </div>
                        <div class="w-1/3">
                            <input
                                type="text"
                                v-model="item.price"
                                class="w-full rounded-lg border-2 border-slate-300 dark:border-slate-800 p-1.5 text-xs font-mono font-bold focus:border-indigo-500 focus:ring-0 focus:outline-hidden dark:bg-slate-950/40 text-emerald-600 dark:text-emerald-400"
                                placeholder="Price (e.g. $89 flat)"
                                required
                            />
                        </div>
                        <Button 
                            type="button" 
                            variant="ghost" 
                            size="sm" 
                            @click="removePriceItem(index)"
                            class="text-rose-500 hover:text-rose-600 hover:bg-rose-500/10 h-8 w-8 p-0 cursor-pointer"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                    <div v-if="form.pricing_list.length === 0" class="text-center py-6 text-xs text-muted-foreground italic">
                        No service rates registered. Add some above!
                    </div>
                </div>
            </div>

            <!-- Submit Section with Duolingo Styled Action Button -->
            <div class="flex items-center justify-between pt-4">
                <span v-if="isSuccess" class="text-emerald-600 dark:text-emerald-400 font-bold text-xs flex items-center gap-1.5 animate-pulse">
                    <Sparkles class="h-4 w-4" /> Settings updated successfully!
                </span>
                <span v-else></span>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-emerald-500 hover:bg-emerald-400 text-white font-black tracking-wide uppercase px-8 py-3 rounded-2xl border-2 border-emerald-500 border-b-6 border-emerald-700 hover:border-emerald-600 active:border-b-2 active:translate-y-1 transition-all cursor-pointer shadow-md text-sm"
                >
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</template>
