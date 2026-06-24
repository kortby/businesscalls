<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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
    Info,
} from '@lucide/vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'AI Prompt settings',
                href: '/settings/prompt',
            },
        ],
    },
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
    ai_prompt:
        props.settings.ai_prompt ||
        'Act professional, friendly, and efficient. Enforce technician active shifts and the mandatory 1.5-hour travel buffer on all bookings.',
    emergency_fee: props.settings.emergency_fee || '$150',
    emergency_rules:
        props.settings.emergency_rules ||
        'Escalate critical water leaks immediately.',
    pricing_list: props.settings.pricing_list || [
        { service: 'Plumbing Repair', price: '$120/hr' },
        { service: 'HVAC Tune-up', price: '$149 Flat' },
        { service: 'Electrical Diagnostics', price: '$95/hr' },
    ],
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
        },
    });
};
</script>

<template>
    <Head title="AI Prompt & pricing settings" />

    <div class="space-y-8 pb-12">
        <!-- Main Bold Header Section -->
        <div
            class="flex flex-col items-center gap-6 rounded-3xl border-4 border-b-8 border-indigo-400 bg-indigo-50 p-6 shadow-xs md:flex-row dark:border-indigo-800 dark:bg-indigo-950/20"
        >
            <div
                class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border-2 border-b-6 border-indigo-700 bg-indigo-500 text-white shadow-sm"
            >
                <Bot class="h-10 w-10 animate-bounce" />
            </div>
            <div>
                <h2
                    class="text-2xl font-black tracking-tight text-indigo-900 dark:text-indigo-300"
                >
                    AI Dispatcher Configurator
                </h2>
                <p
                    class="mt-1 text-sm font-semibold text-indigo-700 dark:text-indigo-400"
                >
                    Manage your voice agent's intelligence, custom instructions,
                    emergency routing, and service rates.
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- 1. AI Instructions Card (Duolingo Style) -->
            <div
                class="relative rounded-2xl border-3 border-b-6 border-slate-300 bg-card p-6 dark:border-slate-800"
            >
                <div class="mb-4 flex items-center gap-2 border-b pb-3">
                    <Sparkles class="h-5 w-5 text-indigo-500" />
                    <span
                        class="text-base font-black tracking-wider text-card-foreground uppercase"
                        >Voice Agent Prompt Instructions</span
                    >
                </div>

                <div class="space-y-4">
                    <div
                        class="flex items-start gap-3 rounded-xl border-2 border-amber-500/20 bg-amber-500/10 p-4"
                    >
                        <Info
                            class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"
                        />
                        <div
                            class="text-xs leading-normal text-amber-800 dark:text-amber-300"
                        >
                            <span
                                class="mb-1 block font-bold tracking-wider uppercase"
                                >Dynamic Prompt Placeholders:</span
                            >
                            You can customize your prompt with double curly
                            braces values. Our system automatically binds these
                            variables at runtime:
                            <div
                                class="mt-2 grid grid-cols-2 gap-2 font-mono text-[10px]"
                            >
                                <span
                                    v-pre
                                    class="rounded border bg-white/50 px-1.5 py-0.5 dark:bg-black/20"
                                    ><code>{{ business_name }}</code></span
                                >
                                <span
                                    v-pre
                                    class="rounded border bg-white/50 px-1.5 py-0.5 dark:bg-black/20"
                                    ><code>{{
                                        custom_instructions
                                    }}</code></span
                                >
                                <span
                                    v-pre
                                    class="rounded border bg-white/50 px-1.5 py-0.5 dark:bg-black/20"
                                    ><code>{{ emergency_fee }}</code></span
                                >
                                <span
                                    v-pre
                                    class="rounded border bg-white/50 px-1.5 py-0.5 dark:bg-black/20"
                                    ><code>{{ service_list }}</code></span
                                >
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label
                            class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                            for="ai_prompt"
                            >Custom AI Prompt Rules</Label
                        >
                        <textarea
                            id="ai_prompt"
                            v-model="form.ai_prompt"
                            rows="4"
                            class="w-full rounded-xl border-2 border-slate-300 p-3 text-sm transition-all focus:border-indigo-500 focus:ring-0 focus:outline-hidden dark:border-slate-800 dark:bg-slate-900/30"
                            placeholder="Enter instructions for the receptionist agent..."
                        ></textarea>
                        <p
                            v-if="form.errors.ai_prompt"
                            class="mt-1 text-xs font-bold text-rose-500"
                        >
                            {{ form.errors.ai_prompt }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 2. Emergency Rules Card (Duolingo Style) -->
            <div
                class="rounded-2xl border-3 border-b-6 border-slate-300 bg-card p-6 dark:border-slate-800"
            >
                <div class="mb-4 flex items-center gap-2 border-b pb-3">
                    <AlertCircle class="h-5 w-5 text-rose-500" />
                    <span
                        class="text-base font-black tracking-wider text-card-foreground uppercase"
                        >Emergency Escalation Rules</span
                    >
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="space-y-2 md:col-span-1">
                        <Label
                            class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                            for="emergency_fee"
                            >Emergency Booking Fee</Label
                        >
                        <div class="relative">
                            <span
                                class="absolute top-2.5 left-3 font-bold text-slate-400"
                                >$</span
                            >
                            <input
                                id="emergency_fee"
                                type="text"
                                v-model="form.emergency_fee"
                                class="w-full rounded-xl border-2 border-slate-300 p-2.5 pl-7 text-sm font-bold transition-all focus:border-indigo-500 focus:ring-0 focus:outline-hidden dark:border-slate-800 dark:bg-slate-900/30"
                                placeholder="150"
                            />
                        </div>
                        <p
                            v-if="form.errors.emergency_fee"
                            class="mt-1 text-xs font-bold text-rose-500"
                        >
                            {{ form.errors.emergency_fee }}
                        </p>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <Label
                            class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                            for="emergency_rules"
                            >Emergency Handling Rules</Label
                        >
                        <input
                            id="emergency_rules"
                            type="text"
                            v-model="form.emergency_rules"
                            class="w-full rounded-xl border-2 border-slate-300 p-2.5 text-sm transition-all focus:border-indigo-500 focus:ring-0 focus:outline-hidden dark:border-slate-800 dark:bg-slate-900/30"
                            placeholder="What rules qualify as a call escalation?"
                        />
                        <p
                            v-if="form.errors.emergency_rules"
                            class="mt-1 text-xs font-bold text-rose-500"
                        >
                            {{ form.errors.emergency_rules }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 3. Service Pricing Card (Duolingo Style) -->
            <div
                class="rounded-2xl border-3 border-b-6 border-slate-300 bg-card p-6 dark:border-slate-800"
            >
                <div
                    class="mb-4 flex items-center justify-between border-b pb-3"
                >
                    <div class="flex items-center gap-2">
                        <DollarSign class="h-5 w-5 text-emerald-500" />
                        <span
                            class="text-base font-black tracking-wider text-card-foreground uppercase"
                            >Service Pricing List</span
                        >
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addPriceItem"
                        class="flex cursor-pointer items-center gap-1 border-2 border-b-4 text-xs font-bold transition-all hover:border-b-2 active:border-b-0"
                    >
                        <Plus class="h-3.5 w-3.5" /> Add Service
                    </Button>
                </div>

                <div class="max-h-[250px] space-y-3 overflow-y-auto pr-1">
                    <div
                        v-for="(item, index) in form.pricing_list"
                        :key="index"
                        class="flex items-center gap-4 rounded-xl border-2 border-slate-200 bg-muted/20 p-3 dark:border-slate-800/80"
                    >
                        <div class="flex-1">
                            <input
                                type="text"
                                v-model="item.service"
                                class="w-full rounded-lg border-2 border-slate-300 p-1.5 text-xs focus:border-indigo-500 focus:ring-0 focus:outline-hidden dark:border-slate-800 dark:bg-slate-950/40"
                                placeholder="Service Name (e.g. Toilet Repair)"
                                required
                            />
                        </div>
                        <div class="w-1/3">
                            <input
                                type="text"
                                v-model="item.price"
                                class="w-full rounded-lg border-2 border-slate-300 p-1.5 font-mono text-xs font-bold text-emerald-600 focus:border-indigo-500 focus:ring-0 focus:outline-hidden dark:border-slate-800 dark:bg-slate-950/40 dark:text-emerald-400"
                                placeholder="Price (e.g. $89 flat)"
                                required
                            />
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="removePriceItem(index)"
                            class="h-8 w-8 cursor-pointer p-0 text-rose-500 hover:bg-rose-500/10 hover:text-rose-600"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                    <div
                        v-if="form.pricing_list.length === 0"
                        class="py-6 text-center text-xs text-muted-foreground italic"
                    >
                        No service rates registered. Add some above!
                    </div>
                </div>
            </div>

            <!-- Submit Section with Duolingo Styled Action Button -->
            <div class="flex items-center justify-between pt-4">
                <span
                    v-if="isSuccess"
                    class="flex animate-pulse items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400"
                >
                    <Sparkles class="h-4 w-4" /> Settings updated successfully!
                </span>
                <span v-else></span>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="cursor-pointer rounded-2xl border-2 border-b-6 border-emerald-500 border-emerald-700 bg-emerald-500 px-8 py-3 text-sm font-black tracking-wide text-white uppercase shadow-md transition-all hover:border-emerald-600 hover:bg-emerald-400 active:translate-y-1 active:border-b-2"
                >
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</template>
