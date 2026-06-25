<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Check,
    X,
    Link2,
    Settings,
    AlertCircle,
    Sparkles,
    RefreshCw,
    Play,
    CheckCircle,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Integrations',
                href: '/admin/integrations',
            },
        ],
    },
});

interface Integration {
    id: number;
    platform_name: string;
    webhook_url: string | null;
    is_active: boolean;
    settings_json: any;
}

const props = defineProps<{
    tenant: {
        id: number;
        name: string;
        mascot_skin?: string;
    };
    integrations: Integration[];
    timingSettings: {
        startSpeakingPlan: number;
        stopSpeakingPlan: number;
    };
    stripe_active: boolean;
}>();

// Timing Configuration Form
const timingForm = useForm({
    startSpeakingPlan: props.timingSettings.startSpeakingPlan,
    stopSpeakingPlan: props.timingSettings.stopSpeakingPlan,
});

// Setup specific integration forms
const getIntegration = (platform: string) => {
    return props.integrations.find(i => i.platform_name === platform) || {
        webhook_url: '',
        is_active: false,
        settings_json: {},
    };
};

const makeInt = getIntegration('make');
const ghlInt = getIntegration('gohighlevel');

const makeForm = useForm({
    platform_name: 'make',
    webhook_url: makeInt.webhook_url || '',
    is_active: makeInt.is_active,
    settings_json: makeInt.settings_json || {},
});

const ghlForm = useForm({
    platform_name: 'gohighlevel',
    webhook_url: ghlInt.webhook_url || '',
    is_active: ghlInt.is_active,
    settings_json: ghlInt.settings_json || {},
});

const stripeForm = useForm({
    platform_name: 'stripe',
    webhook_url: null,
    is_active: props.stripe_active,
    settings_json: {},
});

// Test connection states
const testingMake = ref(false);
const testingGhl = ref(false);
const testSuccessMake = ref<boolean | null>(null);
const testSuccessGhl = ref<boolean | null>(null);

// Mascot state computation
// If Make, GoHighLevel, and Stripe are all active, celebratory victory (state 2)
// If any is inactive or has validation error/connection issue, sad error state (state 3)
// Otherwise idle state (state 0)
const mascotState = computed(() => {
    const makeActive = makeForm.is_active && makeForm.webhook_url.length > 0;
    const ghlActive = ghlForm.is_active && ghlForm.webhook_url.length > 0;
    const stripeActive = props.stripe_active;

    if (makeActive && ghlActive && stripeActive) {
        return 2; // Victory
    }
    if (!makeForm.is_active || !ghlForm.is_active || !props.stripe_active) {
        return 3; // Error / Incomplete
    }
    return 0; // Idle
});

const activeSkin = computed(() => {
    return props.tenant.mascot_skin || 'standard';
});

const submitTiming = () => {
    timingForm.post('/admin/integrations/timing', {
        preserveScroll: true,
    });
};

const savePlatformIntegration = (form: any) => {
    form.post('/admin/integrations', {
        preserveScroll: true,
    });
};

const testMakeConnection = async () => {
    testingMake.value = true;
    testSuccessMake.value = null;
    try {
        const response = await fetch('/api/webhooks/dispatch', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                function_name: 'trigger_workflow',
                event_name: 'make_connection_test',
                tenant_id: props.tenant.id,
                payload: { test: true },
            }),
        });
        testSuccessMake.value = response.ok;
    } catch {
        testSuccessMake.value = false;
    } finally {
        testingMake.value = false;
    }
};

const testGhlConnection = async () => {
    testingGhl.value = true;
    testSuccessGhl.value = null;
    try {
        const response = await fetch('/api/webhooks/dispatch', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                function_name: 'trigger_workflow',
                event_name: 'ghl_connection_test',
                tenant_id: props.tenant.id,
                payload: { test: true },
            }),
        });
        testSuccessGhl.value = response.ok;
    } catch {
        testSuccessGhl.value = false;
    } finally {
        testingGhl.value = false;
    }
};
</script>

<template>
    <div class="p-6 max-w-6xl mx-auto space-y-8 bg-slate-50 dark:bg-slate-900 min-h-screen text-slate-800 dark:text-slate-100">
        <Head title="Platform Integrations" />

        <!-- Header Hero Box (Duolingo Style) -->
        <div class="bg-emerald-500 border-4 border-emerald-600 rounded-3xl p-6 text-white shadow-[0_4px_0_#047857] flex flex-col md:flex-row items-center justify-between gap-6 transition-all duration-300">
            <div class="space-y-2">
                <h1 class="text-3xl font-black tracking-tight">Connect Your Apps!</h1>
                <p class="font-bold text-emerald-100">
                    Sync Make.com, GoHighLevel, and Stripe. Watch your AI receptionist execute automated workflows instantly.
                </p>
            </div>
            <div class="flex items-center gap-2 bg-emerald-600/50 px-4 py-2 border-2 border-emerald-600 rounded-full font-black text-sm uppercase tracking-wider">
                <Sparkles class="w-5 h-5 animate-pulse" />
                Webhook Auth Active
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Sidebar Mascot Bind & General Status -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Mascot Card -->
                <div class="bg-white dark:bg-slate-800 border-4 border-slate-200 dark:border-slate-700 rounded-3xl p-6 shadow-[0_4px_0_#CBD5E1] dark:shadow-[0_4px_0_#334155] flex flex-col items-center">
                    <h2 class="text-xl font-black text-center mb-4 uppercase tracking-wide">Mascot Status</h2>
                    <div class="w-full h-64 flex items-center justify-center mb-4">
                        <DispatcherMascot :state="mascotState" :skin="activeSkin" />
                    </div>
                    <div class="text-center space-y-2">
                        <div v-if="mascotState === 2" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border-2 border-emerald-500 rounded-full font-black text-xs uppercase">
                            <CheckCircle class="w-4 h-4" /> All Active & Synced
                        </div>
                        <div v-else class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border-2 border-rose-500 rounded-full font-black text-xs uppercase">
                            <AlertCircle class="w-4 h-4" /> Setup Required
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-bold">
                            {{ mascotState === 2 ? 'Excellent work! Your AI assistant is fully synchronized.' : 'Some integrations are incomplete or toggled off.' }}
                        </p>
                    </div>
                </div>

                <!-- Speech Turn-Taking Configuration Card -->
                <div class="bg-white dark:bg-slate-800 border-4 border-slate-200 dark:border-slate-700 rounded-3xl p-6 shadow-[0_4px_0_#CBD5E1] dark:shadow-[0_4px_0_#334155]">
                    <div class="flex items-center gap-2 mb-4 border-b-2 border-slate-100 dark:border-slate-700 pb-2">
                        <Settings class="w-5 h-5 text-emerald-500" />
                        <h2 class="text-lg font-black uppercase tracking-wide">Speech Overrides</h2>
                    </div>

                    <form @submit.prevent="submitTiming" class="space-y-6">
                        <!-- Start Speaking Delay Slider -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs font-black uppercase">
                                <span>Start Response Delay</span>
                                <span class="text-emerald-500">{{ timingForm.startSpeakingPlan }}ms</span>
                            </div>
                            <input
                                type="range"
                                min="400"
                                max="800"
                                step="50"
                                v-model.number="timingForm.startSpeakingPlan"
                                class="w-full accent-emerald-500 h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer"
                            />
                            <p class="text-[10px] text-slate-400 font-medium">
                                Controls how long the assistant waits after the caller stops speaking.
                            </p>
                        </div>

                        <!-- Stop Speaking Barge-In Slider -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs font-black uppercase">
                                <span>Barge-In Sensitivity</span>
                                <span class="text-emerald-500">{{ timingForm.stopSpeakingPlan }}s</span>
                            </div>
                            <input
                                type="range"
                                min="0.1"
                                max="2.0"
                                step="0.1"
                                v-model.number="timingForm.stopSpeakingPlan"
                                class="w-full accent-emerald-500 h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer"
                            />
                            <p class="text-[10px] text-slate-400 font-medium">
                                Barge-in interrupt threshold. Lower values stop speaking faster upon interruption.
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="timingForm.processing"
                            class="w-full bg-slate-800 hover:bg-slate-700 text-white font-black py-2.5 px-4 border-b-4 border-slate-900 rounded-xl transition-all duration-100 flex items-center justify-center gap-2 text-sm uppercase tracking-wider disabled:opacity-50 disabled:border-b-0 disabled:translate-y-[4px]"
                        >
                            <RefreshCw v-if="timingForm.processing" class="w-4 h-4 animate-spin" />
                            Save Timing Config
                        </button>
                    </form>
                </div>
            </div>

            <!-- Integrations List Panel -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Make.com Platform Sync Card -->
                <div class="bg-white dark:bg-slate-800 border-4 border-slate-200 dark:border-slate-700 rounded-3xl p-6 shadow-[0_4px_0_#CBD5E1] dark:shadow-[0_4px_0_#334155] relative overflow-hidden">
                    <div class="flex items-start justify-between mb-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black">Make.com</h3>
                                <span class="text-xs bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-extrabold px-2 py-0.5 rounded-md border border-indigo-200">
                                    Webhook Link
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold">
                                Trigger custom workflows, spreadsheets, notifications, or calendar updates dynamically.
                            </p>
                        </div>

                        <!-- Active Badge -->
                        <span
                            class="text-xs font-black uppercase px-2.5 py-1 border-2 rounded-full"
                            :class="makeForm.is_active && makeForm.webhook_url.length > 0 ? 'bg-emerald-50 text-emerald-600 border-emerald-500 dark:bg-emerald-950' : 'bg-slate-50 text-slate-400 border-slate-300 dark:bg-slate-700'"
                        >
                            {{ makeForm.is_active && makeForm.webhook_url.length > 0 ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <form @submit.prevent="savePlatformIntegration(makeForm)" class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Webhook Mailhook URL</label>
                            <input
                                type="url"
                                v-model="makeForm.webhook_url"
                                placeholder="https://hook.us1.make.com/..."
                                class="w-full border-4 border-slate-200 dark:border-slate-700 focus:border-emerald-500 focus:outline-hidden rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 font-medium"
                            />
                        </div>

                        <div class="flex items-center justify-between gap-4 border-t-2 border-slate-100 dark:border-slate-700 pt-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="makeForm.is_active"
                                    class="w-5 h-5 rounded-md border-2 border-slate-300 dark:border-slate-600 text-emerald-600 focus:ring-emerald-500 accent-emerald-500"
                                />
                                <span class="text-xs font-black uppercase text-slate-600 dark:text-slate-300">Enable Workflows</span>
                            </label>

                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="testMakeConnection"
                                    :disabled="testingMake || makeForm.webhook_url.length === 0"
                                    class="inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 border-b-4 border-slate-300 dark:border-slate-800 font-bold px-3 py-1.5 rounded-xl text-xs uppercase disabled:opacity-50 disabled:border-b-0"
                                >
                                    <Play class="w-3 h-3" /> Test Webhook
                                </button>

                                <button
                                    type="submit"
                                    :disabled="makeForm.processing"
                                    class="bg-emerald-500 hover:bg-emerald-400 text-white font-black px-4 py-2 border-b-4 border-emerald-700 rounded-xl text-xs uppercase tracking-wider disabled:opacity-50 disabled:border-b-0"
                                >
                                    Save Sync
                                </button>
                            </div>
                        </div>

                        <!-- Test Results -->
                        <div v-if="testSuccessMake !== null" class="mt-2 text-xs font-bold transition-all duration-300">
                            <span v-if="testSuccessMake" class="text-emerald-500 flex items-center gap-1">
                                <Check class="w-4 h-4" /> Outbound payload delivered successfully!
                            </span>
                            <span v-else class="text-rose-500 flex items-center gap-1">
                                <X class="w-4 h-4" /> Connection test failed. Check URL.
                            </span>
                        </div>
                    </form>
                </div>

                <!-- GoHighLevel Platform Sync Card -->
                <div class="bg-white dark:bg-slate-800 border-4 border-slate-200 dark:border-slate-700 rounded-3xl p-6 shadow-[0_4px_0_#CBD5E1] dark:shadow-[0_4px_0_#334155] relative overflow-hidden">
                    <div class="flex items-start justify-between mb-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black">GoHighLevel</h3>
                                <span class="text-xs bg-teal-100 dark:bg-teal-950 text-teal-700 dark:text-teal-300 font-extrabold px-2 py-0.5 rounded-md border border-teal-200">
                                    OAuth 2.0 API
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold">
                                Create calendar appointments, tags, logging notes, and CRM updates instantly.
                            </p>
                        </div>

                        <!-- Active Badge -->
                        <span
                            class="text-xs font-black uppercase px-2.5 py-1 border-2 rounded-full"
                            :class="ghlForm.is_active && ghlForm.webhook_url.length > 0 ? 'bg-emerald-50 text-emerald-600 border-emerald-500 dark:bg-emerald-950' : 'bg-slate-50 text-slate-400 border-slate-300 dark:bg-slate-700'"
                        >
                            {{ ghlForm.is_active && ghlForm.webhook_url.length > 0 ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <form @submit.prevent="savePlatformIntegration(ghlForm)" class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Connection Endpoint URL</label>
                            <input
                                type="url"
                                v-model="ghlForm.webhook_url"
                                placeholder="https://services.leadconnectorhq.com/hooks/..."
                                class="w-full border-4 border-slate-200 dark:border-slate-700 focus:border-emerald-500 focus:outline-hidden rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 font-medium"
                            />
                        </div>

                        <div class="flex items-center justify-between gap-4 border-t-2 border-slate-100 dark:border-slate-700 pt-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="ghlForm.is_active"
                                    class="w-5 h-5 rounded-md border-2 border-slate-300 dark:border-slate-600 text-emerald-600 focus:ring-emerald-500 accent-emerald-500"
                                />
                                <span class="text-xs font-black uppercase text-slate-600 dark:text-slate-300">Enable Integrations</span>
                            </label>

                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="testGhlConnection"
                                    :disabled="testingGhl || ghlForm.webhook_url.length === 0"
                                    class="inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 border-b-4 border-slate-300 dark:border-slate-800 font-bold px-3 py-1.5 rounded-xl text-xs uppercase disabled:opacity-50 disabled:border-b-0"
                                >
                                    <Play class="w-3 h-3" /> Test Webhook
                                </button>

                                <button
                                    type="submit"
                                    :disabled="ghlForm.processing"
                                    class="bg-emerald-500 hover:bg-emerald-400 text-white font-black px-4 py-2 border-b-4 border-emerald-700 rounded-xl text-xs uppercase tracking-wider disabled:opacity-50 disabled:border-b-0"
                                >
                                    Save Sync
                                </button>
                            </div>
                        </div>

                        <!-- Test Results -->
                        <div v-if="testSuccessGhl !== null" class="mt-2 text-xs font-bold transition-all duration-300">
                            <span v-if="testSuccessGhl" class="text-emerald-500 flex items-center gap-1">
                                <Check class="w-4 h-4" /> Outbound GHL webhook delivered successfully!
                            </span>
                            <span v-else class="text-rose-500 flex items-center gap-1">
                                <X class="w-4 h-4" /> GHL Connection test failed. Check URL.
                            </span>
                        </div>
                    </form>
                </div>

                <!-- Stripe Platform Sync Card -->
                <div class="bg-white dark:bg-slate-800 border-4 border-slate-200 dark:border-slate-700 rounded-3xl p-6 shadow-[0_4px_0_#CBD5E1] dark:shadow-[0_4px_0_#334155] relative overflow-hidden">
                    <div class="flex items-start justify-between mb-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black">Stripe Billing</h3>
                                <span class="text-xs bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 font-extrabold px-2 py-0.5 rounded-md border border-amber-200">
                                    Stripe Key
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold">
                                Handles automated credit card subscriptions, invoices, and billing validation.
                            </p>
                        </div>

                        <!-- Active Badge -->
                        <span
                            class="text-xs font-black uppercase px-2.5 py-1 border-2 rounded-full"
                            :class="props.stripe_active ? 'bg-emerald-50 text-emerald-600 border-emerald-500 dark:bg-emerald-950' : 'bg-rose-50 text-rose-500 border-rose-500 dark:bg-rose-950'"
                        >
                            {{ props.stripe_active ? 'Billing Validated' : 'Unregistered' }}
                        </span>
                    </div>

                    <div class="border-t-2 border-slate-100 dark:border-slate-700 pt-4 flex items-center justify-between">
                        <div class="text-xs font-bold text-slate-500 dark:text-slate-400">
                            Status: <span :class="props.stripe_active ? 'text-emerald-500 font-black' : 'text-rose-500 font-black'">{{ props.stripe_active ? 'Linked under tenant billing profile' : 'Stripe payment methods not configured yet.' }}</span>
                        </div>
                        <a
                            href="/api/billing/portal"
                            class="inline-flex items-center gap-1 bg-slate-800 hover:bg-slate-700 text-white font-black px-4 py-2 border-b-4 border-slate-900 rounded-xl text-xs uppercase tracking-wider"
                        >
                            Billing Portal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
