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
    return (
        props.integrations.find((i) => i.platform_name === platform) || {
            webhook_url: '',
            is_active: false,
            settings_json: {},
        }
    );
};

const getCrmCredential = (platform: string) => {
    return (
        props.crmCredentials.find((c) => c.platform_name === platform) || {
            access_token: '',
            refresh_token: '',
            token_expires_at: null,
            settings_json: {
                client_id: '',
                client_secret: '',
                instance_url: '',
                is_active: false,
            },
        }
    );
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
        instance_url:
            salesforceCred.settings_json?.instance_url ||
            'https://login.salesforce.com',
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

    const hsExpired =
        hubspotForm.is_active &&
        hubspotCred.token_expires_at &&
        new Date(hubspotCred.token_expires_at) < new Date();
    const sfExpired =
        salesforceForm.is_active &&
        salesforceCred.token_expires_at &&
        new Date(salesforceCred.token_expires_at) < new Date();

    if (hsExpired || sfExpired) {
        return 3; // Sad Error State (Expired token)
    }

    const stripeActive = props.stripe_active;
    const telephonyActive = props.telephony_active;
    const makeActive = makeForm.is_active && makeForm.webhook_url.length > 0;
    const crmActive =
        (hubspotForm.is_active &&
            hubspotForm.access_token.length > 0 &&
            !hsExpired) ||
        (salesforceForm.is_active &&
            salesforceForm.access_token.length > 0 &&
            !sfExpired);

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
                Authorization: `Bearer ${hubspotForm.access_token}`,
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
                Authorization: `Bearer ${salesforceForm.access_token}`,
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
    <div
        class="mx-auto min-h-screen max-w-6xl space-y-8 bg-slate-50 p-6 text-slate-800 dark:bg-slate-900 dark:text-slate-100"
    >
        <Head title="Platform Integrations" />

        <!-- Header Hero Box (Duolingo Style) -->
        <div
            class="flex flex-col items-center justify-between gap-6 rounded-3xl border-4 border-indigo-600 bg-indigo-500 p-6 text-white shadow-[0_4px_0_#4f46e5] transition-all duration-300 md:flex-row"
        >
            <div class="space-y-2">
                <h1 class="text-3xl font-black tracking-tight">
                    Sync & Connect CRM Tools!
                </h1>
                <p class="font-bold text-indigo-100">
                    Manage HubSpot, Salesforce, Stripe, and Make.com
                    integrations with real-time mascot feedback!
                </p>
            </div>
            <div
                class="flex items-center gap-2 rounded-full border-2 border-indigo-600 bg-indigo-600/50 px-4 py-2 text-sm font-black tracking-wider uppercase"
            >
                <Sparkles class="h-5 w-5 animate-pulse" />
                Active Integrations Control
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Sidebar Mascot Bind & General Status -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Mascot Card -->
                <div
                    class="flex flex-col items-center rounded-3xl border-4 border-slate-200 bg-white p-6 shadow-[0_4px_0_#CBD5E1] dark:border-slate-700 dark:bg-slate-800 dark:shadow-[0_4px_0_#334155]"
                >
                    <h2
                        class="mb-4 text-center text-xl font-black tracking-wide uppercase"
                    >
                        Mascot Status
                    </h2>
                    <div
                        class="mb-4 flex h-64 w-full items-center justify-center"
                    >
                        <DispatcherMascot
                            :state="mascotState"
                            :skin="activeSkin"
                        />
                    </div>
                    <div class="space-y-2 text-center">
                        <div
                            v-if="mascotState === 2"
                            class="inline-flex items-center gap-1.5 rounded-full border-2 border-emerald-500 bg-emerald-100 px-3 py-1.5 text-xs font-black text-emerald-700 uppercase dark:bg-emerald-950 dark:text-emerald-300"
                        >
                            <CheckCircle class="h-4 w-4" /> All Active & Synced
                            🎉
                        </div>
                        <div
                            v-else-if="mascotState === 3"
                            class="inline-flex items-center gap-1.5 rounded-full border-2 border-rose-500 bg-rose-100 px-3 py-1.5 text-xs font-black text-rose-700 uppercase dark:bg-rose-950 dark:text-rose-300"
                        >
                            <AlertCircle class="h-4 w-4" /> Connection Error ⚠️
                        </div>
                        <div
                            v-else
                            class="inline-flex items-center gap-1.5 rounded-full border-2 border-amber-500 bg-amber-100 px-3 py-1.5 text-xs font-black text-amber-700 uppercase dark:bg-amber-950 dark:text-amber-300"
                        >
                            <AlertCircle class="h-4 w-4" /> Config Incomplete
                        </div>
                        <p
                            class="text-xs font-bold text-slate-500 dark:text-slate-400"
                        >
                            {{
                                mascotState === 2
                                    ? 'Excellent work! Your AI assistant is fully synchronized.'
                                    : mascotState === 3
                                      ? 'Sync failure or credentials expired. Please recheck integration keys.'
                                      : 'Some integrations are incomplete or toggled off.'
                            }}
                        </p>
                    </div>
                </div>

                <!-- Speech Turn-Taking Configuration Card -->
                <div
                    class="rounded-3xl border-4 border-slate-200 bg-white p-6 shadow-[0_4px_0_#CBD5E1] dark:border-slate-700 dark:bg-slate-800 dark:shadow-[0_4px_0_#334155]"
                >
                    <div
                        class="mb-4 flex items-center gap-2 border-b-2 border-slate-100 pb-2 dark:border-slate-700"
                    >
                        <Settings class="h-5 w-5 text-indigo-500" />
                        <h2 class="text-lg font-black tracking-wide uppercase">
                            Speech Overrides
                        </h2>
                    </div>

                    <form @submit.prevent="submitTiming" class="space-y-6">
                        <!-- Start Speaking Delay Slider -->
                        <div class="space-y-2">
                            <div
                                class="flex justify-between text-xs font-black uppercase"
                            >
                                <span>Start Response Delay</span>
                                <span class="text-indigo-500"
                                    >{{ timingForm.startSpeakingPlan }}ms</span
                                >
                            </div>
                            <input
                                type="range"
                                min="400"
                                max="800"
                                step="50"
                                v-model.number="timingForm.startSpeakingPlan"
                                class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-slate-200 accent-indigo-500 dark:bg-slate-700"
                            />
                            <p class="text-[10px] font-medium text-slate-400">
                                Controls how long the assistant waits after the
                                caller stops speaking before responding.
                            </p>
                        </div>

                        <!-- Stop Speaking Barge-In Slider -->
                        <div class="space-y-2">
                            <div
                                class="flex justify-between text-xs font-black uppercase"
                            >
                                <span>Barge-In Sensitivity</span>
                                <span class="text-indigo-500"
                                    >{{ timingForm.stopSpeakingPlan }}s</span
                                >
                            </div>
                            <input
                                type="range"
                                min="0.1"
                                max="2.0"
                                step="0.1"
                                v-model.number="timingForm.stopSpeakingPlan"
                                class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-slate-200 accent-indigo-500 dark:bg-slate-700"
                            />
                            <p class="text-[10px] font-medium text-slate-400">
                                Barge-in interrupt threshold. Lower values stop
                                speaking faster upon interruption.
                            </p>
                        </div>

                        <!-- Backchannel Cues Toggle -->
                        <div
                            class="flex items-center justify-between border-t border-slate-100 pt-4 dark:border-slate-700"
                        >
                            <div class="space-y-0.5">
                                <label class="text-xs font-black uppercase"
                                    >Backchannel Cues</label
                                >
                                <p
                                    class="text-[9px] font-medium text-slate-400"
                                >
                                    Enable conversational acknowledgements
                                    ("mm-hmm", "okay").
                                </p>
                            </div>
                            <input
                                type="checkbox"
                                v-model="timingForm.backchanneling_enabled"
                                class="h-5 w-5 rounded-md border-2 border-slate-300 text-indigo-600 accent-indigo-500 focus:ring-indigo-500 dark:border-slate-600"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="timingForm.processing"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border-b-4 border-slate-900 bg-slate-800 px-4 py-2.5 text-sm font-black tracking-wider text-white uppercase transition-all duration-100 hover:bg-slate-700 disabled:translate-y-[4px] disabled:border-b-0 disabled:opacity-50"
                        >
                            <RefreshCw
                                v-if="timingForm.processing"
                                class="h-4 w-4 animate-spin"
                            />
                            Save Configs
                        </button>
                    </form>
                </div>
            </div>

            <!-- Integrations List Panel -->
            <div class="space-y-6 lg:col-span-2">
                <!-- HubSpot Integration Card -->
                <div
                    class="relative overflow-hidden rounded-3xl border-4 border-slate-200 bg-white p-6 shadow-[0_4px_0_#CBD5E1] dark:border-slate-700 dark:bg-slate-800 dark:shadow-[0_4px_0_#334155]"
                >
                    <div class="mb-4 flex items-start justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black">HubSpot CRM</h3>
                                <span
                                    class="rounded-md border border-orange-200 bg-orange-100 px-2 py-0.5 text-xs font-extrabold text-orange-700 dark:bg-orange-950 dark:text-orange-300"
                                >
                                    Private App Token
                                </span>
                            </div>
                            <p
                                class="text-xs font-bold text-slate-500 dark:text-slate-400"
                            >
                                Sync call transcripts, duration details, and
                                client contact cards directly with HubSpot.
                            </p>
                        </div>

                        <span
                            class="rounded-full border-2 px-2.5 py-1 text-xs font-black uppercase"
                            :class="
                                hubspotForm.is_active &&
                                hubspotForm.access_token.length > 0
                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-600 dark:bg-emerald-950'
                                    : 'border-slate-300 bg-slate-50 text-slate-400 dark:bg-slate-700'
                            "
                        >
                            {{
                                hubspotForm.is_active &&
                                hubspotForm.access_token.length > 0
                                    ? 'Active'
                                    : 'Inactive'
                            }}
                        </span>
                    </div>

                    <form
                        @submit.prevent="savePlatformIntegration(hubspotForm)"
                        class="space-y-4"
                    >
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label
                                    class="text-xs font-black text-slate-500 uppercase dark:text-slate-400"
                                    >Access Token</label
                                >
                                <input
                                    type="password"
                                    v-model="hubspotForm.access_token"
                                    placeholder="pat-na1-..."
                                    class="w-full rounded-xl border-4 border-slate-200 bg-slate-50 p-2.5 text-sm font-medium focus:border-indigo-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-900"
                                />
                            </div>
                            <div class="space-y-2">
                                <label
                                    class="text-xs font-black text-slate-500 uppercase dark:text-slate-400"
                                    >Refresh Token</label
                                >
                                <input
                                    type="password"
                                    v-model="hubspotForm.refresh_token"
                                    placeholder="Optional refresh token"
                                    class="w-full rounded-xl border-4 border-slate-200 bg-slate-50 p-2.5 text-sm font-medium focus:border-indigo-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-900"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label
                                    class="text-xs font-black text-slate-500 uppercase dark:text-slate-400"
                                    >Client ID</label
                                >
                                <input
                                    type="text"
                                    v-model="
                                        hubspotForm.settings_json.client_id
                                    "
                                    placeholder="HubSpot App Client ID"
                                    class="w-full rounded-xl border-4 border-slate-200 bg-slate-50 p-2.5 text-sm font-medium focus:border-indigo-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-900"
                                />
                            </div>
                            <div class="space-y-2">
                                <label
                                    class="text-xs font-black text-slate-500 uppercase dark:text-slate-400"
                                    >Client Secret</label
                                >
                                <input
                                    type="password"
                                    v-model="
                                        hubspotForm.settings_json.client_secret
                                    "
                                    placeholder="HubSpot App Client Secret"
                                    class="w-full rounded-xl border-4 border-slate-200 bg-slate-50 p-2.5 text-sm font-medium focus:border-indigo-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-900"
                                />
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between gap-4 border-t-2 border-slate-100 pt-4 dark:border-slate-700"
                        >
                            <label
                                class="flex cursor-pointer items-center gap-2"
                            >
                                <input
                                    type="checkbox"
                                    v-model="hubspotForm.is_active"
                                    class="h-5 w-5 rounded-md border-2 border-slate-300 text-indigo-600 accent-indigo-500 focus:ring-indigo-500 dark:border-slate-600"
                                />
                                <span
                                    class="text-xs font-black text-slate-600 uppercase dark:text-slate-300"
                                    >Enable HubSpot Sync</span
                                >
                            </label>

                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="testHubspotConnection"
                                    :disabled="
                                        testingHubspot ||
                                        hubspotForm.access_token.length === 0
                                    "
                                    class="inline-flex items-center gap-1 rounded-xl border-b-4 border-slate-300 bg-slate-100 px-3 py-1.5 text-xs font-bold uppercase hover:bg-slate-200 disabled:border-b-0 disabled:opacity-50 dark:border-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600"
                                >
                                    <Play class="h-3 w-3" /> Test Sync
                                </button>

                                <button
                                    type="submit"
                                    :disabled="hubspotForm.processing"
                                    class="rounded-xl border-b-4 border-indigo-700 bg-indigo-500 px-4 py-2 text-xs font-black tracking-wider text-white uppercase hover:bg-indigo-400 disabled:border-b-0 disabled:opacity-50"
                                >
                                    Save HubSpot
                                </button>
                            </div>
                        </div>

                        <!-- Test Results -->
                        <div
                            v-if="testSuccessHubspot !== null"
                            class="mt-2 text-xs font-bold transition-all duration-300"
                        >
                            <span
                                v-if="testSuccessHubspot"
                                class="flex items-center gap-1 text-emerald-500"
                            >
                                <Check class="h-4 w-4" /> HubSpot OAuth
                                connection active!
                            </span>
                            <span
                                v-else
                                class="flex items-center gap-1 text-rose-500"
                            >
                                <X class="h-4 w-4" /> Connection test failed.
                                Check tokens.
                            </span>
                        </div>
                    </form>
                </div>

                <!-- Salesforce Integration Card -->
                <div
                    class="relative overflow-hidden rounded-3xl border-4 border-slate-200 bg-white p-6 shadow-[0_4px_0_#CBD5E1] dark:border-slate-700 dark:bg-slate-800 dark:shadow-[0_4px_0_#334155]"
                >
                    <div class="mb-4 flex items-start justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black">
                                    Salesforce CRM
                                </h3>
                                <span
                                    class="rounded-md border border-sky-200 bg-sky-100 px-2 py-0.5 text-xs font-extrabold text-sky-700 dark:bg-sky-950 dark:text-sky-300"
                                >
                                    OAuth 2.0 Credentials
                                </span>
                            </div>
                            <p
                                class="text-xs font-bold text-slate-500 dark:text-slate-400"
                            >
                                Create Salesforce Contacts, Leads, and Task
                                engagements automatically after call analysis.
                            </p>
                        </div>

                        <span
                            class="rounded-full border-2 px-2.5 py-1 text-xs font-black uppercase"
                            :class="
                                salesforceForm.is_active &&
                                salesforceForm.access_token.length > 0
                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-600 dark:bg-emerald-950'
                                    : 'border-slate-300 bg-slate-50 text-slate-400 dark:bg-slate-700'
                            "
                        >
                            {{
                                salesforceForm.is_active &&
                                salesforceForm.access_token.length > 0
                                    ? 'Active'
                                    : 'Inactive'
                            }}
                        </span>
                    </div>

                    <form
                        @submit.prevent="
                            savePlatformIntegration(salesforceForm)
                        "
                        class="space-y-4"
                    >
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label
                                    class="text-xs font-black text-slate-500 uppercase dark:text-slate-400"
                                    >Access Token</label
                                >
                                <input
                                    type="password"
                                    v-model="salesforceForm.access_token"
                                    placeholder="Salesforce Access Token"
                                    class="w-full rounded-xl border-4 border-slate-200 bg-slate-50 p-2.5 text-sm font-medium focus:border-indigo-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-900"
                                />
                            </div>
                            <div class="space-y-2">
                                <label
                                    class="text-xs font-black text-slate-500 uppercase dark:text-slate-400"
                                    >Refresh Token</label
                                >
                                <input
                                    type="password"
                                    v-model="salesforceForm.refresh_token"
                                    placeholder="Salesforce Refresh Token"
                                    class="w-full rounded-xl border-4 border-slate-200 bg-slate-50 p-2.5 text-sm font-medium focus:border-indigo-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-900"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="space-y-2 md:col-span-1">
                                <label
                                    class="text-xs font-black text-slate-500 uppercase dark:text-slate-400"
                                    >Client ID</label
                                >
                                <input
                                    type="text"
                                    v-model="
                                        salesforceForm.settings_json.client_id
                                    "
                                    placeholder="Connected App Client ID"
                                    class="w-full rounded-xl border-4 border-slate-200 bg-slate-50 p-2.5 text-sm font-medium focus:border-indigo-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-900"
                                />
                            </div>
                            <div class="space-y-2 md:col-span-1">
                                <label
                                    class="text-xs font-black text-slate-500 uppercase dark:text-slate-400"
                                    >Client Secret</label
                                >
                                <input
                                    type="password"
                                    v-model="
                                        salesforceForm.settings_json
                                            .client_secret
                                    "
                                    placeholder="Connected App Client Secret"
                                    class="w-full rounded-xl border-4 border-slate-200 bg-slate-50 p-2.5 text-sm font-medium focus:border-indigo-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-900"
                                />
                            </div>
                            <div class="space-y-2 md:col-span-1">
                                <label
                                    class="text-xs font-black text-slate-500 uppercase dark:text-slate-400"
                                    >Instance URL</label
                                >
                                <input
                                    type="url"
                                    v-model="
                                        salesforceForm.settings_json
                                            .instance_url
                                    "
                                    placeholder="https://login.salesforce.com"
                                    class="w-full rounded-xl border-4 border-slate-200 bg-slate-50 p-2.5 text-sm font-medium focus:border-indigo-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-900"
                                />
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between gap-4 border-t-2 border-slate-100 pt-4 dark:border-slate-700"
                        >
                            <label
                                class="flex cursor-pointer items-center gap-2"
                            >
                                <input
                                    type="checkbox"
                                    v-model="salesforceForm.is_active"
                                    class="h-5 w-5 rounded-md border-2 border-slate-300 text-indigo-600 accent-indigo-500 focus:ring-indigo-500 dark:border-slate-600"
                                />
                                <span
                                    class="text-xs font-black text-slate-600 uppercase dark:text-slate-300"
                                    >Enable Salesforce Sync</span
                                >
                            </label>

                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="testSalesforceConnection"
                                    :disabled="
                                        testingSalesforce ||
                                        salesforceForm.access_token.length === 0
                                    "
                                    class="inline-flex items-center gap-1 rounded-xl border-b-4 border-slate-300 bg-slate-100 px-3 py-1.5 text-xs font-bold uppercase hover:bg-slate-200 disabled:border-b-0 disabled:opacity-50 dark:border-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600"
                                >
                                    <Play class="h-3 w-3" /> Test Sync
                                </button>

                                <button
                                    type="submit"
                                    :disabled="salesforceForm.processing"
                                    class="rounded-xl border-b-4 border-indigo-700 bg-indigo-500 px-4 py-2 text-xs font-black tracking-wider text-white uppercase hover:bg-indigo-400 disabled:border-b-0 disabled:opacity-50"
                                >
                                    Save Salesforce
                                </button>
                            </div>
                        </div>

                        <!-- Test Results -->
                        <div
                            v-if="testSuccessSalesforce !== null"
                            class="mt-2 text-xs font-bold transition-all duration-300"
                        >
                            <span
                                v-if="testSuccessSalesforce"
                                class="flex items-center gap-1 text-emerald-500"
                            >
                                <Check class="h-4 w-4" /> Salesforce JWT
                                connection active!
                            </span>
                            <span
                                v-else
                                class="flex items-center gap-1 text-rose-500"
                            >
                                <X class="h-4 w-4" /> Connection test failed.
                                Check credentials.
                            </span>
                        </div>
                    </form>
                </div>

                <!-- Make.com Platform Sync Card -->
                <div
                    class="relative overflow-hidden rounded-3xl border-4 border-slate-200 bg-white p-6 shadow-[0_4px_0_#CBD5E1] dark:border-slate-700 dark:bg-slate-800 dark:shadow-[0_4px_0_#334155]"
                >
                    <div class="mb-4 flex items-start justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black">Make.com</h3>
                                <span
                                    class="rounded-md border border-indigo-200 bg-indigo-100 px-2 py-0.5 text-xs font-extrabold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                                >
                                    Incoming Mailhook
                                </span>
                            </div>
                            <p
                                class="text-xs font-bold text-slate-500 dark:text-slate-400"
                            >
                                Trigger custom workflows, spreadsheets,
                                notifications, or calendar updates dynamically.
                            </p>
                        </div>

                        <span
                            class="rounded-full border-2 px-2.5 py-1 text-xs font-black uppercase"
                            :class="
                                makeForm.is_active &&
                                makeForm.webhook_url.length > 0
                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-600 dark:bg-emerald-950'
                                    : 'border-slate-300 bg-slate-50 text-slate-400 dark:bg-slate-700'
                            "
                        >
                            {{
                                makeForm.is_active &&
                                makeForm.webhook_url.length > 0
                                    ? 'Active'
                                    : 'Inactive'
                            }}
                        </span>
                    </div>

                    <form
                        @submit.prevent="savePlatformIntegration(makeForm)"
                        class="space-y-4"
                    >
                        <div class="space-y-2">
                            <label
                                class="text-xs font-black text-slate-500 uppercase dark:text-slate-400"
                                >Webhook Mailhook URL</label
                            >
                            <input
                                type="url"
                                v-model="makeForm.webhook_url"
                                placeholder="https://hook.us1.make.com/..."
                                class="w-full rounded-xl border-4 border-slate-200 bg-slate-50 p-2.5 text-sm font-medium focus:border-indigo-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-900"
                            />
                        </div>

                        <div
                            class="flex items-center justify-between gap-4 border-t-2 border-slate-100 pt-4 dark:border-slate-700"
                        >
                            <label
                                class="flex cursor-pointer items-center gap-2"
                            >
                                <input
                                    type="checkbox"
                                    v-model="makeForm.is_active"
                                    class="h-5 w-5 rounded-md border-2 border-slate-300 text-indigo-600 accent-indigo-500 focus:ring-indigo-500 dark:border-slate-600"
                                />
                                <span
                                    class="text-xs font-black text-slate-600 uppercase dark:text-slate-300"
                                    >Enable Workflows</span
                                >
                            </label>

                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="testMakeConnection"
                                    :disabled="
                                        testingMake ||
                                        makeForm.webhook_url.length === 0
                                    "
                                    class="inline-flex items-center gap-1 rounded-xl border-b-4 border-slate-300 bg-slate-100 px-3 py-1.5 text-xs font-bold uppercase hover:bg-slate-200 disabled:border-b-0 disabled:opacity-50 dark:border-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600"
                                >
                                    <Play class="h-3 w-3" /> Test Webhook
                                </button>

                                <button
                                    type="submit"
                                    :disabled="makeForm.processing"
                                    class="rounded-xl border-b-4 border-indigo-700 bg-indigo-500 px-4 py-2 text-xs font-black tracking-wider text-white uppercase hover:bg-indigo-400 disabled:border-b-0 disabled:opacity-50"
                                >
                                    Save Sync
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="testSuccessMake !== null"
                            class="mt-2 text-xs font-bold transition-all duration-300"
                        >
                            <span
                                v-if="testSuccessMake"
                                class="flex items-center gap-1 text-emerald-500"
                            >
                                <Check class="h-4 w-4" /> Outbound payload
                                delivered successfully!
                            </span>
                            <span
                                v-else
                                class="flex items-center gap-1 text-rose-500"
                            >
                                <X class="h-4 w-4" /> Connection test failed.
                                Check URL.
                            </span>
                        </div>
                    </form>
                </div>

                <!-- Stripe Billing Card -->
                <div
                    class="relative overflow-hidden rounded-3xl border-4 border-slate-200 bg-white p-6 shadow-[0_4px_0_#CBD5E1] dark:border-slate-700 dark:bg-slate-800 dark:shadow-[0_4px_0_#334155]"
                >
                    <div class="mb-4 flex items-start justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black">
                                    Stripe Billing
                                </h3>
                                <span
                                    class="rounded-md border border-amber-200 bg-amber-100 px-2 py-0.5 text-xs font-extrabold text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                                >
                                    Stripe Connect
                                </span>
                            </div>
                            <p
                                class="text-xs font-bold text-slate-500 dark:text-slate-400"
                            >
                                Handles credit card payments, monthly
                                subscriptions, and billing validation loops.
                            </p>
                        </div>

                        <span
                            class="rounded-full border-2 px-2.5 py-1 text-xs font-black uppercase"
                            :class="
                                props.stripe_active
                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-600 dark:bg-emerald-950'
                                    : 'border-rose-500 bg-rose-50 text-rose-500 dark:bg-rose-950'
                            "
                        >
                            {{
                                props.stripe_active
                                    ? 'Billing Active'
                                    : 'Offline'
                            }}
                        </span>
                    </div>

                    <div
                        class="flex items-center justify-between border-t-2 border-slate-100 pt-4 dark:border-slate-700"
                    >
                        <div
                            class="text-xs font-bold text-slate-500 dark:text-slate-400"
                        >
                            Stripe Profile ID:
                            <span
                                :class="
                                    props.stripe_active
                                        ? 'font-black text-emerald-500'
                                        : 'font-black text-rose-500'
                                "
                                >{{
                                    props.stripe_active
                                        ? 'Connected'
                                        : 'Missing settings'
                                }}</span
                            >
                        </div>
                        <a
                            href="/api/billing/portal"
                            class="inline-flex items-center gap-1 rounded-xl border-b-4 border-slate-900 bg-slate-800 px-4 py-2 text-xs font-black tracking-wider text-white uppercase hover:bg-slate-700"
                        >
                            Billing Portal
                        </a>
                    </div>
                </div>

                <!-- Telephony Vapi/Retell Voice Lines -->
                <div
                    class="relative overflow-hidden rounded-3xl border-4 border-slate-200 bg-white p-6 shadow-[0_4px_0_#CBD5E1] dark:border-slate-700 dark:bg-slate-800 dark:shadow-[0_4px_0_#334155]"
                >
                    <div class="mb-4 flex items-start justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-black">
                                    Vapi / Retell Voice Lines
                                </h3>
                                <span
                                    class="rounded-md border border-emerald-200 bg-emerald-100 px-2 py-0.5 text-xs font-extrabold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                                >
                                    Telephony Engine
                                </span>
                            </div>
                            <p
                                class="text-xs font-bold text-slate-500 dark:text-slate-400"
                            >
                                Real-time voice synthesis and inbound dispatch
                                phone lines configuration.
                            </p>
                        </div>

                        <span
                            class="rounded-full border-2 px-2.5 py-1 text-xs font-black uppercase"
                            :class="
                                props.telephony_active
                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-600 dark:bg-emerald-950'
                                    : 'border-rose-500 bg-rose-50 text-rose-500 dark:bg-rose-950'
                            "
                        >
                            {{
                                props.telephony_active
                                    ? 'Engine Online'
                                    : 'Offline'
                            }}
                        </span>
                    </div>
                    <div
                        class="flex items-center justify-between border-t-2 border-slate-100 pt-4 text-xs font-bold text-slate-500 dark:border-slate-700 dark:text-slate-400"
                    >
                        Status:
                        <span
                            :class="
                                props.telephony_active
                                    ? 'font-black text-emerald-500'
                                    : 'font-black text-rose-500'
                            "
                            >{{
                                props.telephony_active
                                    ? 'Vapi / Retell credentials verified'
                                    : 'No credentials set'
                            }}</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
