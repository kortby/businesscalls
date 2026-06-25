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

interface CrmCredential {
    id: number;
    platform_name: string;
    access_token: string;
    refresh_token: string | null;
    token_expires_at: string | null;
    settings_json: any;
}

const props = defineProps<{
    tenant: {
        id: number;
        name: string;
        mascot_skin?: string;
    };
    integrations: Integration[];
    crmCredentials: CrmCredential[];
    timingSettings: {
        startSpeakingPlan: number;
        stopSpeakingPlan: number;
        backchanneling_enabled: boolean;
    };
    stripe_active: boolean;
    telephony_active: boolean;
}>();

// Timing Configuration Form
const timingForm = useForm({
    startSpeakingPlan: props.timingSettings.startSpeakingPlan,
    stopSpeakingPlan: props.timingSettings.stopSpeakingPlan,
    backchanneling_enabled: props.timingSettings.backchanneling_enabled,
});

// Setup specific integration forms
const getIntegration = (platform: string) => {
    return props.integrations.find(i => i.platform_name === platform) || {
        webhook_url: '',
        is_active: false,
        settings_json: {},
    };
};

const getCrmCredential = (platform: string) => {
    return props.crmCredentials.find(c => c.platform_name === platform) || {
        access_token: '',
        refresh_token: '',
        token_expires_at: null,
        settings_json: {
            client_id: '',
            client_secret: '',
            instance_url: '',
            is_active: false,
        },
    };
};

const makeInt = getIntegration('make');
const makeForm = useForm({
    platform_name: 'make',
    webhook_url: makeInt.webhook_url || '',
    is_active: makeInt.is_active,
    settings_json: makeInt.settings_json || {},
});

const hubspotCred = getCrmCredential('hubspot');
const hubspotForm = useForm({
    platform_name: 'hubspot',
    access_token: hubspotCred.access_token || '',
    refresh_token: hubspotCred.refresh_token || '',
    is_active: hubspotCred.settings_json?.is_active || false,
    settings_json: {
        client_id: hubspotCred.settings_json?.client_id || '',
        client_secret: hubspotCred.settings_json?.client_secret || '',
    },
});

const salesforceCred = getCrmCredential('salesforce');
const salesforceForm = useForm({
    platform_name: 'salesforce',
    access_token: salesforceCred.access_token || '',
    refresh_token: salesforceCred.refresh_token || '',
    is_active: salesforceCred.settings_json?.is_active || false,
    settings_json: {
        client_id: salesforceCred.settings_json?.client_id || '',
        client_secret: salesforceCred.settings_json?.client_secret || '',
        instance_url: salesforceCred.settings_json?.instance_url || 'https://login.salesforce.com',
    },
});

// Test connection states
const testingMake = ref(false);
const testingHubspot = ref(false);
const testingSalesforce = ref(false);
const testSuccessMake = ref<boolean | null>(null);
const testSuccessHubspot = ref<boolean | null>(null);
const testSuccessSalesforce = ref<boolean | null>(null);

// Mascot state computation
// Victory (state 2) if all target integrations (Stripe, Telephony, Make, and at least one CRM) are active with no expired tokens
// Sad error (state 3) if any tests fail, or active CRM token is expired
const mascotState = computed(() => {
    if (
        testSuccessMake.value === false ||
        testSuccessHubspot.value === false ||
        testSuccessSalesforce.value === false
    ) {
        return 3; // Sad Error State
    }

    const hsExpired = hubspotForm.is_active && hubspotCred.token_expires_at && new Date(hubspotCred.token_expires_at) < new Date();
    const sfExpired = salesforceForm.is_active && salesforceCred.token_expires_at && new Date(salesforceCred.token_expires_at) < new Date();

    if (hsExpired || sfExpired) {
        return 3; // Sad Error State (Expired token)
    }

    const stripeActive = props.stripe_active;
    const telephonyActive = props.telephony_active;
    const makeActive = makeForm.is_active && makeForm.webhook_url.length > 0;
    const crmActive = (hubspotForm.is_active && hubspotForm.access_token.length > 0 && !hsExpired) ||
                      (salesforceForm.is_active && salesforceForm.access_token.length > 0 && !sfExpired);

    if (stripeActive && telephonyActive && makeActive && crmActive) {
        return 2; // Victory Celebration
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

const testHubspotConnection = async () => {
    testingHubspot.value = true;
    testSuccessHubspot.value = null;
    try {
        // Simple request to check if server endpoint validates credentials
        const response = await fetch('/api/mcp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${hubspotForm.access_token}`,
            },
            body: JSON.stringify({
                jsonrpc: '2.0',
                method: 'tools/list',
                id: 99,
            }),
        });
        testSuccessHubspot.value = response.ok;
    } catch {
        testSuccessHubspot.value = false;
    } finally {
        testingHubspot.value = false;
    }
};

const testSalesforceConnection = async () => {
    testingSalesforce.value = true;
    testSuccessSalesforce.value = null;
    try {
        const response = await fetch('/api/mcp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${salesforceForm.access_token}`,
            },
            body: JSON.stringify({
                jsonrpc: '2.0',
                method: 'tools/list',
                id: 99,
            }),
        });
        testSuccessSalesforce.value = response.ok;
    } catch {
        testSuccessSalesforce.value = false;
    } finally {
        testingSalesforce.value = false;
    }
};
</script>

<template>
    <div class="p-6 max-w-6xl mx-auto space-y-8 bg-slate-50 dark:bg-slate-900 min-h-screen text-slate-800 dark:text-slate-100">
        <Head title="Platform Integrations" />

        <!-- Header Hero Box (Duolingo Style) -->
        <div class="bg-indigo-500 border-4 border-indigo-600 rounded-3xl p-6 text-white shadow-[0_4px_0_#4f46e5] flex flex-col md:flex-row items-center justify-between gap-6 transition-all duration-300">
            <div class="space-y-2">
                <h1 class="text-3xl font-black tracking-tight">Sync & Connect CRM Tools!</h1>
                <p class="font-bold text-indigo-100">
                    Manage HubSpot, Salesforce, Stripe, and Make.com integrations with real-time mascot feedback!
                </p>
            </div>
            <div class="flex items-center gap-2 bg-indigo-600/50 px-4 py-2 border-2 border-indigo-600 rounded-full font-black text-sm uppercase tracking-wider">
                <Sparkles class="w-5 h-5 animate-pulse" />
                Active Integrations Control
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
                            <CheckCircle class="w-4 h-4" /> All Active & Synced 🎉
                        </div>
                        <div v-else-if="mascotState === 3" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border-2 border-rose-500 rounded-full font-black text-xs uppercase">
                            <AlertCircle class="w-4 h-4" /> Connection Error ⚠️
                        </div>
                        <div v-else class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border-2 border-amber-500 rounded-full font-black text-xs uppercase">
                            <AlertCircle class="w-4 h-4" /> Config Incomplete
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-bold">
                            {{ mascotState === 2 ? 'Excellent work! Your AI assistant is fully synchronized.' : mascotState === 3 ? 'Sync failure or credentials expired. Please recheck integration keys.' : 'Some integrations are incomplete or toggled off.' }}
                        </p>
                    </div>
                </div>

                <!-- Speech Turn-Taking Configuration Card -->
                <div class="bg-white dark:bg-slate-800 border-4 border-slate-200 dark:border-slate-700 rounded-3xl p-6 shadow-[0_4px_0_#CBD5E1] dark:shadow-[0_4px_0_#334155]">
                    <div class="flex items-center gap-2 mb-4 border-b-2 border-slate-100 dark:border-slate-700 pb-2">
                        <Settings class="w-5 h-5 text-indigo-500" />
                        <h2 class="text-lg font-black uppercase tracking-wide">Speech Overrides</h2>
                    </div>

                    <form @submit.prevent="submitTiming" class="space-y-6">
                        <!-- Start Speaking Delay Slider -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs font-black uppercase">
                                <span>Start Response Delay</span>
                                <span class="text-indigo-500">{{ timingForm.startSpeakingPlan }}ms</span>
                            </div>
                            <input
                                type="range"
                                min="400"
                                max="800"
                                step="50"
                                v-model.number="timingForm.startSpeakingPlan"
                                class="w-full accent-indigo-500 h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer"
                            />
                            <p class="text-[10px] text-slate-400 font-medium">
                                Controls how long the assistant waits after the caller stops speaking before responding.
                            </p>
                        </div>

                        <!-- Stop Speaking Barge-In Slider -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs font-black uppercase">
                                <span>Barge-In Sensitivity</span>
                                <span class="text-indigo-500">{{ timingForm.stopSpeakingPlan }}s</span>
                            </div>
                            <input
                                type="range"
                                min="0.1"
                                max="2.0"
                                step="0.1"
                                v-model.number="timingForm.stopSpeakingPlan"
                                class="w-full accent-indigo-500 h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer"
                            />
                            <p class="text-[10px] text-slate-400 font-medium">
                                Barge-in interrupt threshold. Lower values stop speaking faster upon interruption.
                            </p>
                        </div>

                        <!-- Backchannel Cues Toggle -->
                        <div class="flex items-center justify-between border-t border-slate-100 dark:border-slate-700 pt-4">
                            <div class="space-y-0.5">
                                <label class="text-xs font-black uppercase">Backchannel Cues</label>
                                <p class="text-[9px] text-slate-400 font-medium">Enable conversational acknowledgements ("mm-hmm", "okay").</p>
                            </div>
                            <input
                                type="checkbox"
                                v-model="timingForm.backchanneling_enabled"
                                class="w-5 h-5 rounded-md border-2 border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 accent-indigo-500"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="timingForm.processing"
                            class="w-full bg-slate-800 hover:bg-slate-700 text-white font-black py-2.5 px-4 border-b-4 border-slate-900 rounded-xl transition-all duration-100 flex items-center justify-center gap-2 text-sm uppercase tracking-wider disabled:opacity-50 disabled:border-b-0 disabled:translate-y-[4px]"
                        >
                            <RefreshCw v-if="timingForm.processing" class="w-4 h-4 animate-spin" />
                            Save Configs
                        </button>
                    </form>
                </div>
            </div>

            <!-- Integrations List Panel -->
            <div class="lg:col-span-2 space-y-6">
                <!-- HubSpot Integration Card -->
                <div class="bg-white dark:bg-slate-800 border-4 border-slate-200 dark:border-slate-700 rounded-3xl p-6 shadow-[0_4px_0_#CBD5E1] dark:shadow-[0_4px_0_#334155] relative overflow-hidden">
                    <div class="flex items-start justify-between mb-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black">HubSpot CRM</h3>
                                <span class="text-xs bg-orange-100 dark:bg-orange-950 text-orange-700 dark:text-orange-300 font-extrabold px-2 py-0.5 rounded-md border border-orange-200">
                                    Private App Token
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold">
                                Sync call transcripts, duration details, and client contact cards directly with HubSpot.
                            </p>
                        </div>

                        <span
                            class="text-xs font-black uppercase px-2.5 py-1 border-2 rounded-full"
                            :class="hubspotForm.is_active && hubspotForm.access_token.length > 0 ? 'bg-emerald-50 text-emerald-600 border-emerald-500 dark:bg-emerald-950' : 'bg-slate-50 text-slate-400 border-slate-300 dark:bg-slate-700'"
                        >
                            {{ hubspotForm.is_active && hubspotForm.access_token.length > 0 ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <form @submit.prevent="savePlatformIntegration(hubspotForm)" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Access Token</label>
                                <input
                                    type="password"
                                    v-model="hubspotForm.access_token"
                                    placeholder="pat-na1-..."
                                    class="w-full border-4 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:outline-hidden rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 font-medium"
                                />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Refresh Token</label>
                                <input
                                    type="password"
                                    v-model="hubspotForm.refresh_token"
                                    placeholder="Optional refresh token"
                                    class="w-full border-4 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:outline-hidden rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 font-medium"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Client ID</label>
                                <input
                                    type="text"
                                    v-model="hubspotForm.settings_json.client_id"
                                    placeholder="HubSpot App Client ID"
                                    class="w-full border-4 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:outline-hidden rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 font-medium"
                                />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Client Secret</label>
                                <input
                                    type="password"
                                    v-model="hubspotForm.settings_json.client_secret"
                                    placeholder="HubSpot App Client Secret"
                                    class="w-full border-4 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:outline-hidden rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 font-medium"
                                />
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 border-t-2 border-slate-100 dark:border-slate-700 pt-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="hubspotForm.is_active"
                                    class="w-5 h-5 rounded-md border-2 border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 accent-indigo-500"
                                />
                                <span class="text-xs font-black uppercase text-slate-600 dark:text-slate-300">Enable HubSpot Sync</span>
                            </label>

                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="testHubspotConnection"
                                    :disabled="testingHubspot || hubspotForm.access_token.length === 0"
                                    class="inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 border-b-4 border-slate-300 dark:border-slate-800 font-bold px-3 py-1.5 rounded-xl text-xs uppercase disabled:opacity-50 disabled:border-b-0"
                                >
                                    <Play class="w-3 h-3" /> Test Sync
                                </button>

                                <button
                                    type="submit"
                                    :disabled="hubspotForm.processing"
                                    class="bg-indigo-500 hover:bg-indigo-400 text-white font-black px-4 py-2 border-b-4 border-indigo-700 rounded-xl text-xs uppercase tracking-wider disabled:opacity-50 disabled:border-b-0"
                                >
                                    Save HubSpot
                                </button>
                            </div>
                        </div>

                        <!-- Test Results -->
                        <div v-if="testSuccessHubspot !== null" class="mt-2 text-xs font-bold transition-all duration-300">
                            <span v-if="testSuccessHubspot" class="text-emerald-500 flex items-center gap-1">
                                <Check class="w-4 h-4" /> HubSpot OAuth connection active!
                            </span>
                            <span v-else class="text-rose-500 flex items-center gap-1">
                                <X class="w-4 h-4" /> Connection test failed. Check tokens.
                            </span>
                        </div>
                    </form>
                </div>

                <!-- Salesforce Integration Card -->
                <div class="bg-white dark:bg-slate-800 border-4 border-slate-200 dark:border-slate-700 rounded-3xl p-6 shadow-[0_4px_0_#CBD5E1] dark:shadow-[0_4px_0_#334155] relative overflow-hidden">
                    <div class="flex items-start justify-between mb-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black">Salesforce CRM</h3>
                                <span class="text-xs bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 font-extrabold px-2 py-0.5 rounded-md border border-sky-200">
                                    OAuth 2.0 Credentials
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold">
                                Create Salesforce Contacts, Leads, and Task engagements automatically after call analysis.
                            </p>
                        </div>

                        <span
                            class="text-xs font-black uppercase px-2.5 py-1 border-2 rounded-full"
                            :class="salesforceForm.is_active && salesforceForm.access_token.length > 0 ? 'bg-emerald-50 text-emerald-600 border-emerald-500 dark:bg-emerald-950' : 'bg-slate-50 text-slate-400 border-slate-300 dark:bg-slate-700'"
                        >
                            {{ salesforceForm.is_active && salesforceForm.access_token.length > 0 ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <form @submit.prevent="savePlatformIntegration(salesforceForm)" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Access Token</label>
                                <input
                                    type="password"
                                    v-model="salesforceForm.access_token"
                                    placeholder="Salesforce Access Token"
                                    class="w-full border-4 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:outline-hidden rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 font-medium"
                                />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Refresh Token</label>
                                <input
                                    type="password"
                                    v-model="salesforceForm.refresh_token"
                                    placeholder="Salesforce Refresh Token"
                                    class="w-full border-4 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:outline-hidden rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 font-medium"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2 md:col-span-1">
                                <label class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Client ID</label>
                                <input
                                    type="text"
                                    v-model="salesforceForm.settings_json.client_id"
                                    placeholder="Connected App Client ID"
                                    class="w-full border-4 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:outline-hidden rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 font-medium"
                                />
                            </div>
                            <div class="space-y-2 md:col-span-1">
                                <label class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Client Secret</label>
                                <input
                                    type="password"
                                    v-model="salesforceForm.settings_json.client_secret"
                                    placeholder="Connected App Client Secret"
                                    class="w-full border-4 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:outline-hidden rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 font-medium"
                                />
                            </div>
                            <div class="space-y-2 md:col-span-1">
                                <label class="text-xs font-black uppercase text-slate-500 dark:text-slate-400">Instance URL</label>
                                <input
                                    type="url"
                                    v-model="salesforceForm.settings_json.instance_url"
                                    placeholder="https://login.salesforce.com"
                                    class="w-full border-4 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:outline-hidden rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 font-medium"
                                />
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 border-t-2 border-slate-100 dark:border-slate-700 pt-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="salesforceForm.is_active"
                                    class="w-5 h-5 rounded-md border-2 border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 accent-indigo-500"
                                />
                                <span class="text-xs font-black uppercase text-slate-600 dark:text-slate-300">Enable Salesforce Sync</span>
                            </label>

                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="testSalesforceConnection"
                                    :disabled="testingSalesforce || salesforceForm.access_token.length === 0"
                                    class="inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 border-b-4 border-slate-300 dark:border-slate-800 font-bold px-3 py-1.5 rounded-xl text-xs uppercase disabled:opacity-50 disabled:border-b-0"
                                >
                                    <Play class="w-3 h-3" /> Test Sync
                                </button>

                                <button
                                    type="submit"
                                    :disabled="salesforceForm.processing"
                                    class="bg-indigo-500 hover:bg-indigo-400 text-white font-black px-4 py-2 border-b-4 border-indigo-700 rounded-xl text-xs uppercase tracking-wider disabled:opacity-50 disabled:border-b-0"
                                >
                                    Save Salesforce
                                </button>
                            </div>
                        </div>

                        <!-- Test Results -->
                        <div v-if="testSuccessSalesforce !== null" class="mt-2 text-xs font-bold transition-all duration-300">
                            <span v-if="testSuccessSalesforce" class="text-emerald-500 flex items-center gap-1">
                                <Check class="w-4 h-4" /> Salesforce JWT connection active!
                            </span>
                            <span v-else class="text-rose-500 flex items-center gap-1">
                                <X class="w-4 h-4" /> Connection test failed. Check credentials.
                            </span>
                        </div>
                    </form>
                </div>

                <!-- Make.com Platform Sync Card -->
                <div class="bg-white dark:bg-slate-800 border-4 border-slate-200 dark:border-slate-700 rounded-3xl p-6 shadow-[0_4px_0_#CBD5E1] dark:shadow-[0_4px_0_#334155] relative overflow-hidden">
                    <div class="flex items-start justify-between mb-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black">Make.com</h3>
                                <span class="text-xs bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-extrabold px-2 py-0.5 rounded-md border border-indigo-200">
                                    Incoming Mailhook
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold">
                                Trigger custom workflows, spreadsheets, notifications, or calendar updates dynamically.
                            </p>
                        </div>

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
                                class="w-full border-4 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:outline-hidden rounded-xl p-2.5 text-sm bg-slate-50 dark:bg-slate-900 font-medium"
                            />
                        </div>

                        <div class="flex items-center justify-between gap-4 border-t-2 border-slate-100 dark:border-slate-700 pt-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="makeForm.is_active"
                                    class="w-5 h-5 rounded-md border-2 border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 accent-indigo-500"
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
                                    class="bg-indigo-500 hover:bg-indigo-400 text-white font-black px-4 py-2 border-b-4 border-indigo-700 rounded-xl text-xs uppercase tracking-wider disabled:opacity-50 disabled:border-b-0"
                                >
                                    Save Sync
                                </button>
                            </div>
                        </div>

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

                <!-- Stripe Billing Card -->
                <div class="bg-white dark:bg-slate-800 border-4 border-slate-200 dark:border-slate-700 rounded-3xl p-6 shadow-[0_4px_0_#CBD5E1] dark:shadow-[0_4px_0_#334155] relative overflow-hidden">
                    <div class="flex items-start justify-between mb-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black">Stripe Billing</h3>
                                <span class="text-xs bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 font-extrabold px-2 py-0.5 rounded-md border border-amber-200">
                                    Stripe Connect
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold">
                                Handles credit card payments, monthly subscriptions, and billing validation loops.
                            </p>
                        </div>

                        <span
                            class="text-xs font-black uppercase px-2.5 py-1 border-2 rounded-full"
                            :class="props.stripe_active ? 'bg-emerald-50 text-emerald-600 border-emerald-500 dark:bg-emerald-950' : 'bg-rose-50 text-rose-500 border-rose-500 dark:bg-rose-950'"
                        >
                            {{ props.stripe_active ? 'Billing Active' : 'Offline' }}
                        </span>
                    </div>

                    <div class="border-t-2 border-slate-100 dark:border-slate-700 pt-4 flex items-center justify-between">
                        <div class="text-xs font-bold text-slate-500 dark:text-slate-400">
                            Stripe Profile ID: <span :class="props.stripe_active ? 'text-emerald-500 font-black' : 'text-rose-500 font-black'">{{ props.stripe_active ? 'Connected' : 'Missing settings' }}</span>
                        </div>
                        <a
                            href="/api/billing/portal"
                            class="inline-flex items-center gap-1 bg-slate-800 hover:bg-slate-700 text-white font-black px-4 py-2 border-b-4 border-slate-900 rounded-xl text-xs uppercase tracking-wider"
                        >
                            Billing Portal
                        </a>
                    </div>
                </div>

                <!-- Telephony Vapi/Retell Voice Lines -->
                <div class="bg-white dark:bg-slate-800 border-4 border-slate-200 dark:border-slate-700 rounded-3xl p-6 shadow-[0_4px_0_#CBD5E1] dark:shadow-[0_4px_0_#334155] relative overflow-hidden">
                    <div class="flex items-start justify-between mb-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black">Vapi / Retell Voice Lines</h3>
                                <span class="text-xs bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-extrabold px-2 py-0.5 rounded-md border border-emerald-200">
                                    Telephony Engine
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold">
                                Real-time voice synthesis and inbound dispatch phone lines configuration.
                            </p>
                        </div>

                        <span
                            class="text-xs font-black uppercase px-2.5 py-1 border-2 rounded-full"
                            :class="props.telephony_active ? 'bg-emerald-50 text-emerald-600 border-emerald-500 dark:bg-emerald-950' : 'bg-rose-50 text-rose-500 border-rose-500 dark:bg-rose-950'"
                        >
                            {{ props.telephony_active ? 'Engine Online' : 'Offline' }}
                        </span>
                    </div>
                    <div class="border-t-2 border-slate-100 dark:border-slate-700 pt-4 flex items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400">
                        Status: <span :class="props.telephony_active ? 'text-emerald-500 font-black' : 'text-rose-500 font-black'">{{ props.telephony_active ? 'Vapi / Retell credentials verified' : 'No credentials set' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
