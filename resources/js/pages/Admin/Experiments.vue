<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Activity,
    FlaskConical,
    Sparkles,
    ShieldCheck,
    Volume2,
    VolumeX,
    Database,
    TrendingUp,
    TrendingDown,
    Award,
    Play,
    CheckCircle2,
    XCircle,
    Info,
    Phone,
    Sliders,
    Wifi,
    Check,
} from '@lucide/vue';
import { ref, computed, watch } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Experiments',
                href: '/admin/experiments',
            },
        ],
    },
});

const props = defineProps<{
    tenant: {
        id: number;
        name: string;
        slug: string;
        settings: Record<string, any>;
    };
    experiments: Array<{
        id: number;
        name: string;
        status: string;
        traffic_split: number;
        created_at: string;
        variants: Array<{
            id: number;
            version: string;
            prompt_instructions: string;
            model_provider: string;
            call_count: number;
            booking_count: number;
        }>;
    }>;
    activeExperiment: {
        id: number;
        name: string;
        status: string;
        traffic_split: number;
        variants: Array<{
            id: number;
            version: string;
            prompt_instructions: string;
            model_provider: string;
            call_count: number;
            booking_count: number;
        }>;
    } | null;
    chiSquare: number;
    baselineConversion: number;
    denoisingEnabled: boolean;
}>();

// Denoising Toggle Form
const denoisingForm = useForm({});
const toggleDenoising = () => {
    denoisingForm.post(route('admin::admin.experiments.denoising'), {
        preserveScroll: true,
    });
};

// Branded Caller ID Form
const brandedIdForm = useForm({
    legal_business_name:
        props.tenant.settings?.branded_business_name || props.tenant.name,
    brand_logo_url:
        props.tenant.settings?.branded_logo_url ||
        'https://assets.businesscalls.io/logo.png',
    physical_address:
        props.tenant.settings?.branded_physical_address ||
        '123 Main St, Seattle, WA 98101',
    phone_numbers: [
        props.tenant.settings?.telephony_phone_number || '+15550001111',
    ],
});

const registerBrandedCallerId = () => {
    brandedIdForm.post('/api/settings/branded-caller-id', {
        preserveScroll: true,
        onSuccess: () => {
            // reload page state
            router.reload();
        },
    });
};

// Create A/B Experiment Form
const experimentForm = useForm({
    name: '',
    traffic_split: 50,
    prompt_a:
        'Act as a friendly, professional voice agent. Be extremely polite.',
    model_a: 'openai/gpt-4o',
    prompt_b:
        'Act as a fast, direct dispatcher. Get customer info as quickly as possible.',
    model_b: 'anthropic/claude-3-5-sonnet',
});

const createExperiment = () => {
    experimentForm.post(route('admin::admin.experiments.save'), {
        preserveScroll: true,
        onSuccess: () => {
            experimentForm.reset();
        },
    });
};

// Dynamic Mascot Bindings
const mascotStateOverride = ref<number | null>(null);

const mascotState = computed(() => {
    if (mascotStateOverride.value !== null) {
        return mascotStateOverride.value;
    }

    if (!props.activeExperiment) {
        return 0; // Idle
    }

    // Check if error state should trigger (e.g. active experiment experiencing low conversions / call drops)
    const variantA = props.activeExperiment.variants.find(
        (v) => v.version === 'A',
    );
    const variantB = props.activeExperiment.variants.find(
        (v) => v.version === 'B',
    );

    if (variantA && variantB) {
        const totalCalls = variantA.call_count + variantB.call_count;
        const totalBookings = variantA.booking_count + variantB.booking_count;

        // If calls started but zero bookings after 15 calls, alert admin
        if (totalCalls >= 15 && totalBookings === 0) {
            return 3; // Error
        }
    }

    // Crowned winner state if chi-square significance is reached
    if (props.chiSquare >= 3.84) {
        return 2; // Victory celebratory dance
    }

    return 1; // Scanning radar loop
});

// Mock telemetry logger
const telemetryLogs = ref<
    { id: number; timestamp: string; message: string; type: string }[]
>([]);
let logCounter = 0;

const addLog = (type: string, message: string) => {
    telemetryLogs.value.unshift({
        id: ++logCounter,
        timestamp: new Date().toLocaleTimeString(),
        message,
        type,
    });

    if (telemetryLogs.value.length > 25) {
        telemetryLogs.value.pop();
    }
};

// Simulator controls
const simulateCallOutcome = (variantVersion: string, converted: boolean) => {
    if (!props.activeExperiment) {
        return;
    }

    const variant = props.activeExperiment.variants.find(
        (v) => v.version === variantVersion,
    );

    if (!variant) {
        return;
    }

    addLog(
        'simulation',
        `Simulated Call on Variant ${variantVersion}: ${converted ? '✅ Booked Conversion' : '📞 No Booking'}`,
    );

    // Call webhook event simulation locally to trigger increments
    const mockCallId =
        'mock_call_' + Math.random().toString(36).substring(2, 9);

    // We cache mapping
    router.post(
        route('webhook.call-events', { tenant_id: props.tenant.id }),
        {
            event: 'call_ended',
            call: {
                call_id: mockCallId,
                customer_phone_number:
                    '+1555' + Math.floor(1000000 + Math.random() * 9000000),
                duration_seconds: 45 + Math.floor(Math.random() * 120),
                metadata: {
                    experiment_variant_id: variant.id,
                },
            },
            survey_scores: converted ? [5, 5, 5] : [],
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                if (converted) {
                    // To simulate booking created fallback logic
                    router.post(
                        route('webhook.call-events', {
                            tenant_id: props.tenant.id,
                        }),
                        {
                            event: 'call_analyzed',
                            call: {
                                call_id: mockCallId,
                                customer_phone_number: '+15551234567',
                                metadata: {
                                    experiment_variant_id: variant.id,
                                },
                            },
                        },
                        {
                            preserveScroll: true,
                            onSuccess: () => {
                                router.reload();
                            },
                        },
                    );
                } else {
                    router.reload();
                }
            },
        },
    );
};

const simulateDenoisingMetrics = () => {
    const rawSnr = (8 + Math.random() * 6).toFixed(1);
    const processedSnr = (24 + Math.random() * 8).toFixed(1);
    const improvement = (parseFloat(processedSnr) - parseFloat(rawSnr)).toFixed(
        1,
    );

    addLog(
        'audio',
        `Denoised call frame metrics: Raw SNR = ${rawSnr}dB, Processed SNR = ${processedSnr}dB, Delta quality = +${improvement}dB`,
    );

    if (props.activeExperiment) {
        const mockCallId =
            'mock_snr_' + Math.random().toString(36).substring(2, 9);
        const variant =
            props.activeExperiment.variants[Math.floor(Math.random() * 2)];

        router.post(
            route('webhook.call-events', { tenant_id: props.tenant.id }),
            {
                event: 'call_ended',
                call: {
                    call_id: mockCallId,
                    customer_phone_number: '+15550001111',
                    duration_seconds: 60,
                    snr_raw: parseFloat(rawSnr),
                    snr_processed: parseFloat(processedSnr),
                    metadata: {
                        experiment_variant_id: variant.id,
                    },
                },
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
    }
};

const variantA = computed(
    () =>
        props.activeExperiment?.variants.find((v) => v.version === 'A') || null,
);
const variantB = computed(
    () =>
        props.activeExperiment?.variants.find((v) => v.version === 'B') || null,
);

const variantAConversion = computed(() => {
    if (!variantA.value || variantA.value.call_count === 0) {
        return 0;
    }

    return (variantA.value.booking_count / variantA.value.call_count) * 100;
});

const variantBConversion = computed(() => {
    if (!variantB.value || variantB.value.call_count === 0) {
        return 0;
    }

    return (variantB.value.booking_count / variantB.value.call_count) * 100;
});

const winningVariant = computed(() => {
    if (props.chiSquare < 3.84 || !variantA.value || !variantB.value) {
        return null;
    }

    return variantBConversion.value > variantAConversion.value ? 'B' : 'A';
});
</script>

<template>
    <Head title="A/B Prompt Testing & Experiments" />

    <div class="mx-auto flex max-w-[1400px] flex-col gap-6 p-4 sm:p-6 md:p-8">
        <!-- Top Branded Identity and Audio Suppression Controls -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Outbound Voice Identity Registration -->
            <div
                class="flex flex-col gap-4 rounded-2xl border-2 border-slate-200 bg-card p-6 shadow-sm dark:border-slate-800"
            >
                <h2
                    class="flex items-center gap-2 text-lg font-black tracking-tight text-foreground"
                >
                    <Phone class="h-5 w-5 text-emerald-500" />
                    Branded Caller ID Verified Identity
                    <span
                        v-if="
                            tenant.settings?.branded_caller_id_status ===
                            'verified'
                        "
                        class="ml-auto inline-flex items-center gap-1 rounded border border-emerald-200 bg-emerald-100 px-2 py-0.5 text-xs font-black text-emerald-600 uppercase dark:border-emerald-900/50 dark:bg-emerald-900/30 dark:text-emerald-400"
                    >
                        <ShieldCheck class="h-3.5 w-3.5" /> Verified
                    </span>
                    <span
                        v-else
                        class="ml-auto inline-flex items-center gap-1 rounded border border-yellow-200 bg-yellow-100 px-2 py-0.5 text-xs font-black text-yellow-600 uppercase dark:border-yellow-900/50 dark:bg-yellow-900/30 dark:text-yellow-400"
                    >
                        Unverified
                    </span>
                </h2>

                <form
                    @submit.prevent="registerBrandedCallerId"
                    class="flex flex-col gap-3"
                >
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1">
                            <label
                                class="text-[10px] font-black text-slate-400 uppercase"
                                >Business Name</label
                            >
                            <input
                                v-model="brandedIdForm.legal_business_name"
                                type="text"
                                class="rounded-xl border-2 border-slate-200 bg-slate-50 p-2.5 text-xs font-bold focus:border-emerald-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900"
                            />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label
                                class="text-[10px] font-black text-slate-400 uppercase"
                                >Brand Logo URL</label
                            >
                            <input
                                v-model="brandedIdForm.brand_logo_url"
                                type="text"
                                class="rounded-xl border-2 border-slate-200 bg-slate-50 p-2.5 text-xs font-bold focus:border-emerald-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label
                            class="text-[10px] font-black text-slate-400 uppercase"
                            >Physical Address</label
                        >
                        <input
                            v-model="brandedIdForm.physical_address"
                            type="text"
                            class="rounded-xl border-2 border-slate-200 bg-slate-50 p-2.5 text-xs font-bold focus:border-emerald-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900"
                        />
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <div
                            class="font-mono text-[10px] text-muted-foreground"
                        >
                            Trunk ID:
                            {{
                                tenant.settings?.branded_caller_id_trunk_id ||
                                'unregistered'
                            }}
                        </div>
                        <button
                            type="submit"
                            :disabled="brandedIdForm.processing"
                            class="rounded-xl border-2 border-b-4 border-emerald-600 bg-emerald-500 px-4 py-2 text-xs font-black tracking-wider text-white uppercase transition-all hover:translate-y-[-1px] hover:bg-emerald-400 active:translate-y-[1px] active:border-b-2"
                        >
                            Register caller ID
                        </button>
                    </div>
                </form>
            </div>

            <!-- Audio Denoising Filters Toggle -->
            <div
                class="flex flex-col justify-between rounded-2xl border-2 border-slate-200 bg-card p-6 shadow-sm dark:border-slate-800"
            >
                <div>
                    <h2
                        class="flex items-center gap-2 border-b pb-4 text-lg font-black tracking-tight text-foreground"
                    >
                        <Volume2 class="h-5 w-5 text-emerald-500" />
                        Advanced Audio Denoising Filters
                    </h2>
                    <p
                        class="mt-3 text-xs leading-relaxed text-muted-foreground"
                    >
                        Suppresses drilling, running HVAC compressors, and
                        active pipe leak background noise when technicians
                        communicate from on-site jobs. Automatically logs the
                        Denoising Quality Improvement Index
                        ($\Delta_{\text{quality}}$) to completed call logs.
                    </p>
                </div>

                <div
                    class="mt-6 flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-900/50"
                >
                    <div class="flex flex-col gap-0.5">
                        <span
                            class="text-xs font-black tracking-tight text-foreground uppercase"
                            >Suppress Background Noise</span
                        >
                        <span class="text-[10px] text-muted-foreground"
                            >Retell Denoising / Vapi Suppression Profile</span
                        >
                    </div>

                    <button
                        @click="toggleDenoising"
                        class="relative h-7 w-14 rounded-full border-2 transition-all duration-300"
                        :class="[
                            denoisingEnabled
                                ? 'border-emerald-600 bg-emerald-500'
                                : 'border-slate-350 bg-slate-200 dark:border-slate-700 dark:bg-slate-800',
                        ]"
                    >
                        <span
                            class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow-xs transition-all duration-300"
                            :class="[
                                denoisingEnabled ? 'left-[30px]' : 'left-0.5',
                            ]"
                        ></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main A/B Experiments Console -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left Panel: Split tests metrics & Create tests (2/3 Grid) -->
            <div class="flex flex-col gap-6 lg:col-span-2">
                <!-- Active Experiment Details -->
                <div
                    class="flex flex-col gap-4 rounded-2xl border-2 border-slate-200 bg-card p-6 shadow-sm dark:border-slate-800"
                >
                    <h2
                        class="flex items-center gap-2 border-b pb-4 text-lg font-black tracking-tight text-foreground"
                    >
                        <FlaskConical class="h-5 w-5 text-emerald-500" />
                        Active Split Experiment
                        <span
                            v-if="activeExperiment"
                            class="ml-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-black text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400"
                        >
                            {{ activeExperiment.name }}
                        </span>
                        <span
                            v-else
                            class="ml-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-black text-muted-foreground dark:bg-slate-800"
                        >
                            No Active Experiment
                        </span>
                    </h2>

                    <!-- Empty State -->
                    <div
                        v-if="!activeExperiment"
                        class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 py-12 text-center dark:border-slate-800"
                    >
                        <VolumeX
                            class="mb-2 h-10 w-10 text-slate-300 dark:text-slate-700"
                        />
                        <h3
                            class="dark:text-slate-350 font-bold text-slate-700"
                        >
                            No ongoing split-tests
                        </h3>
                        <p class="mt-1 max-w-xs text-xs text-muted-foreground">
                            Define two system prompt variations or AI models
                            below to start split routing call traffic.
                        </p>
                    </div>

                    <!-- Experiment Metrics & Variants Comparative Table -->
                    <div v-else class="flex flex-col gap-4">
                        <!-- Header Chi-Square Stat -->
                        <div
                            class="grid grid-cols-3 gap-4 border-b border-dashed pb-4"
                        >
                            <div
                                class="rounded-xl border bg-slate-50 p-3 dark:bg-slate-900/50"
                            >
                                <span
                                    class="mb-1 block text-[9px] font-black text-slate-400 uppercase"
                                    >Statistical Score (Chi2)</span
                                >
                                <span
                                    class="font-mono text-lg font-black text-foreground"
                                    >{{ chiSquare.toFixed(2) }}</span
                                >
                            </div>
                            <div
                                class="rounded-xl border bg-slate-50 p-3 dark:bg-slate-900/50"
                            >
                                <span
                                    class="mb-1 block text-[9px] font-black text-slate-400 uppercase"
                                    >Target Threshold</span
                                >
                                <span
                                    class="font-mono text-lg font-black text-foreground"
                                    >3.84 (95%)</span
                                >
                            </div>
                            <div
                                class="flex flex-col justify-center rounded-xl border bg-slate-50 p-3 dark:bg-slate-900/50"
                            >
                                <span
                                    class="mb-0.5 block text-[9px] font-black text-slate-400 uppercase"
                                    >Confidence Status</span
                                >
                                <span
                                    class="inline-flex w-fit rounded px-1.5 py-0.5 text-center text-[9px] font-black uppercase"
                                    :class="[
                                        chiSquare >= 3.84
                                            ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400'
                                            : 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
                                    ]"
                                >
                                    {{
                                        chiSquare >= 3.84
                                            ? 'Significant ✓'
                                            : 'Inconclusive'
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Statistical Crowned Banner -->
                        <div
                            v-if="winningVariant"
                            class="flex animate-pulse items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20"
                        >
                            <Award class="h-8 w-8 text-amber-500" />
                            <div>
                                <h4
                                    class="text-sm font-black text-amber-800 dark:text-amber-300"
                                >
                                    Variant {{ winningVariant }} is the winner!
                                </h4>
                                <p
                                    class="text-[10px] font-medium text-amber-700 dark:text-amber-400"
                                >
                                    This variant achieves a Chi-Square score of
                                    {{ chiSquare.toFixed(2) }} (confidence level
                                    &ge; 95%). We recommend archiving the
                                    experiment and routing all traffic here.
                                </p>
                            </div>
                        </div>

                        <!-- Comparative Single Spaced Table -->
                        <div
                            class="dark:border-slate-850 overflow-x-auto rounded-xl border border-slate-200"
                        >
                            <table
                                class="w-full border-collapse text-left text-xs"
                            >
                                <thead>
                                    <tr
                                        class="border-b bg-slate-50 font-black text-slate-500 uppercase dark:bg-slate-900"
                                    >
                                        <th class="p-3">Variant</th>
                                        <th class="p-3">Target Model</th>
                                        <th class="p-3">Calls</th>
                                        <th class="p-3">Bookings</th>
                                        <th class="p-3">Conversion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="variant in activeExperiment.variants"
                                        :key="variant.id"
                                        class="border-b font-bold last:border-0 hover:bg-slate-50/50 dark:hover:bg-slate-900/20"
                                    >
                                        <td class="p-3">
                                            <span
                                                class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-black uppercase"
                                                :class="[
                                                    variant.version === 'A'
                                                        ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400'
                                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                                ]"
                                            >
                                                {{ variant.version }}
                                            </span>
                                        </td>
                                        <td
                                            class="p-3 font-mono text-muted-foreground"
                                        >
                                            {{ variant.model_provider }}
                                        </td>
                                        <td class="p-3">
                                            {{ variant.call_count }}
                                        </td>
                                        <td class="p-3 text-emerald-500">
                                            {{ variant.booking_count }}
                                        </td>
                                        <td class="p-3">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span class="w-8"
                                                    >{{
                                                        variant.call_count > 0
                                                            ? (
                                                                  (variant.booking_count /
                                                                      variant.call_count) *
                                                                  100
                                                              ).toFixed(0)
                                                            : 0
                                                    }}%</span
                                                >
                                                <div
                                                    class="h-2 w-16 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                                                >
                                                    <div
                                                        class="h-full rounded-full"
                                                        :class="[
                                                            variant.version ===
                                                            'A'
                                                                ? 'bg-indigo-500'
                                                                : 'bg-amber-500',
                                                        ]"
                                                        :style="{
                                                            width: `${variant.call_count > 0 ? (variant.booking_count / variant.call_count) * 100 : 0}%`,
                                                        }"
                                                    ></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Split Ratio Slider Visual -->
                        <div class="mt-2">
                            <span
                                class="mb-1 block text-[10px] font-black text-slate-400 uppercase"
                                >Traffic Routing Split</span
                            >
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-black text-indigo-500"
                                    >Variant A ({{
                                        100 - activeExperiment.traffic_split
                                    }}%)</span
                                >
                                <div
                                    class="flex h-3 flex-1 overflow-hidden rounded-full bg-indigo-500"
                                >
                                    <div
                                        class="h-full bg-amber-400"
                                        :style="{
                                            width: `${activeExperiment.traffic_split}%`,
                                            marginLeft: 'auto',
                                        }"
                                    ></div>
                                </div>
                                <span class="text-xs font-black text-amber-500"
                                    >Variant B ({{
                                        activeExperiment.traffic_split
                                    }}%)</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Create Experiment Panel Form (Chunky 3D style) -->
                <div
                    class="flex flex-col gap-4 rounded-2xl border-2 border-slate-200 bg-card p-6 shadow-sm dark:border-slate-800"
                >
                    <h3
                        class="flex items-center gap-2 border-b pb-4 text-lg font-black tracking-tight text-foreground"
                    >
                        <Sliders class="h-5 w-5 text-emerald-500" />
                        Configure New A/B Experiment
                    </h3>

                    <form
                        @submit.prevent="createExperiment"
                        class="flex flex-col gap-4"
                    >
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2 flex flex-col gap-1">
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase"
                                    >Experiment Name</label
                                >
                                <input
                                    v-model="experimentForm.name"
                                    type="text"
                                    placeholder="e.g., Booking Pacing Optimization Test"
                                    class="rounded-xl border-2 border-slate-200 bg-slate-50 p-2.5 text-xs font-bold focus:border-emerald-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900"
                                />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label
                                    class="text-[10px] font-black text-slate-400 uppercase"
                                    >Traffic Split % to B</label
                                >
                                <input
                                    v-model="experimentForm.traffic_split"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="rounded-xl border-2 border-slate-200 bg-slate-50 p-2.5 text-xs font-bold focus:border-emerald-500 focus:outline-none dark:border-slate-800 dark:bg-slate-900"
                                />
                            </div>
                        </div>

                        <!-- Variant A and B Settings Columns -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div
                                class="border-indigo-250 flex flex-col gap-3 rounded-xl border bg-indigo-50/10 p-4 dark:border-indigo-900"
                            >
                                <h4
                                    class="text-xs font-black tracking-wider text-indigo-500 uppercase"
                                >
                                    Variant A Configuration
                                </h4>
                                <div class="flex flex-col gap-1">
                                    <label
                                        class="text-[9px] font-black text-slate-400 uppercase"
                                        >LLM Model Provider</label
                                    >
                                    <input
                                        v-model="experimentForm.model_a"
                                        type="text"
                                        class="border-slate-250 rounded-lg border bg-slate-50 p-2 text-xs font-bold focus:border-indigo-500 focus:outline-none dark:bg-slate-900"
                                    />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label
                                        class="text-[9px] font-black text-slate-400 uppercase"
                                        >System Prompts Instructions</label
                                    >
                                    <textarea
                                        v-model="experimentForm.prompt_a"
                                        rows="3"
                                        class="border-slate-250 rounded-lg border bg-slate-50 p-2 text-xs font-medium focus:border-indigo-500 focus:outline-none dark:bg-slate-900"
                                    ></textarea>
                                </div>
                            </div>

                            <div
                                class="border-amber-250 flex flex-col gap-3 rounded-xl border bg-amber-50/10 p-4 dark:border-amber-900"
                            >
                                <h4
                                    class="text-xs font-black tracking-wider text-amber-500 uppercase"
                                >
                                    Variant B Configuration
                                </h4>
                                <div class="flex flex-col gap-1">
                                    <label
                                        class="text-[9px] font-black text-slate-400 uppercase"
                                        >LLM Model Provider</label
                                    >
                                    <input
                                        v-model="experimentForm.model_b"
                                        type="text"
                                        class="border-slate-250 rounded-lg border bg-slate-50 p-2 text-xs font-bold focus:border-amber-500 focus:outline-none dark:bg-slate-900"
                                    />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label
                                        class="text-[9px] font-black text-slate-400 uppercase"
                                        >System Prompts Instructions</label
                                    >
                                    <textarea
                                        v-model="experimentForm.prompt_b"
                                        rows="3"
                                        class="border-slate-250 rounded-lg border bg-slate-50 p-2 text-xs font-medium focus:border-amber-500 focus:outline-none dark:bg-slate-900"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="hover:bg-slate-750 rounded-xl border-2 border-b-4 border-slate-700 bg-slate-800 py-2.5 text-xs font-black text-white uppercase hover:translate-y-[-1px] active:translate-y-[1px] active:border-b-2"
                        >
                            Save and Activate Experiment
                        </button>
                    </form>
                </div>

                <!-- Simulation controls -->
                <div
                    class="flex flex-col gap-4 rounded-2xl border-2 border-slate-200 bg-card p-6 shadow-sm dark:border-slate-800"
                >
                    <h3
                        class="flex items-center gap-2 border-b pb-4 text-lg font-black tracking-tight text-foreground"
                    >
                        <Activity class="h-5 w-5 text-emerald-500" />
                        Simulation Play Ground
                    </h3>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <button
                            @click="simulateCallOutcome('A', false)"
                            class="rounded-xl border-2 border-b-4 border-slate-300 bg-white p-2 text-xs font-black tracking-tight text-slate-700 transition-all hover:translate-y-[-1px] hover:bg-slate-50 active:translate-y-[1px] active:border-b-2 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            Call Variant A
                        </button>
                        <button
                            @click="simulateCallOutcome('A', true)"
                            class="rounded-xl border-2 border-b-4 border-indigo-600 bg-indigo-500 p-2 text-xs font-black tracking-tight text-white transition-all hover:translate-y-[-1px] hover:bg-indigo-400 active:translate-y-[1px] active:border-b-2"
                        >
                            Book Variant A
                        </button>
                        <button
                            @click="simulateCallOutcome('B', false)"
                            class="rounded-xl border-2 border-b-4 border-slate-300 bg-white p-2 text-xs font-black tracking-tight text-slate-700 transition-all hover:translate-y-[-1px] hover:bg-slate-50 active:translate-y-[1px] active:border-b-2 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            Call Variant B
                        </button>
                        <button
                            @click="simulateCallOutcome('B', true)"
                            class="text-amber-955 rounded-xl border-2 border-b-4 border-amber-600 bg-amber-500 p-2 text-xs font-black tracking-tight transition-all hover:translate-y-[-1px] hover:bg-amber-400 active:translate-y-[1px] active:border-b-2"
                        >
                            Book Variant B
                        </button>
                    </div>

                    <div
                        class="mt-2 flex items-center justify-between gap-4 border-t border-dashed pt-4"
                    >
                        <span class="text-xs text-muted-foreground"
                            >Test noise suppression audio frame telemetry:</span
                        >
                        <button
                            @click="simulateDenoisingMetrics"
                            class="hover:bg-slate-650 rounded-xl border-2 border-b-4 border-slate-600 bg-slate-700 px-4 py-2 text-xs font-black text-white uppercase transition-all hover:translate-y-[-1px] active:translate-y-[1px] active:border-b-2"
                        >
                            Simulate Denoise Metric
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Dispatch Mascot & Telemetry Logs (1/3 Grid) -->
            <div class="flex flex-col gap-6">
                <!-- Mascot Box -->
                <div
                    class="flex flex-col gap-4 rounded-2xl border-2 border-slate-200 bg-card p-6 shadow-sm dark:border-slate-800"
                >
                    <h3
                        class="flex items-center gap-2 border-b pb-4 text-lg font-black tracking-tight text-foreground"
                    >
                        <Sparkles class="h-5 w-5 text-amber-500" />
                        A/B Monitor Mascot
                    </h3>

                    <!-- Dispatch Mascot Wrapper -->
                    <div class="h-[280px]">
                        <DispatcherMascot
                            :state="mascotState"
                            :is-speaking="false"
                            :amplitude="0"
                            :skin="tenant.settings?.active_skin || 'standard'"
                        />
                    </div>

                    <!-- Mascot Controls Override -->
                    <div class="mt-2 flex flex-col gap-2">
                        <span
                            class="text-[10px] font-black text-slate-400 uppercase"
                            >Override Mascot State</span
                        >
                        <div class="grid grid-cols-4 gap-1.5">
                            <button
                                v-for="st in [0, 1, 2, 3]"
                                :key="st"
                                @click="
                                    mascotStateOverride =
                                        mascotStateOverride === st ? null : st
                                "
                                class="rounded border-2 p-1 font-mono text-xs font-bold transition-all"
                                :class="[
                                    mascotStateOverride === st
                                        ? 'border-slate-900 bg-slate-900 text-slate-100 dark:bg-slate-100 dark:text-slate-900'
                                        : 'border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400',
                                ]"
                            >
                                S: {{ st }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Simulation Logs -->
                <div
                    class="flex flex-1 flex-col gap-4 rounded-2xl border-2 border-slate-200 bg-card p-6 shadow-sm dark:border-slate-800"
                >
                    <h3
                        class="flex items-center gap-2 border-b pb-4 text-lg font-black tracking-tight text-foreground"
                    >
                        <Database class="h-5 w-5 text-emerald-500" />
                        Simulation Event Log
                    </h3>

                    <div
                        class="flex max-h-[300px] flex-1 flex-col gap-2 overflow-y-auto rounded-xl bg-slate-950 p-4 font-mono text-[10px] text-slate-400"
                    >
                        <div
                            v-if="telemetryLogs.length === 0"
                            class="py-6 text-center text-slate-600"
                        >
                            Waiting for simulated calls and audio packets...
                        </div>
                        <div
                            v-for="log in telemetryLogs"
                            :key="log.id"
                            class="border-b border-slate-900 pb-1.5 last:border-0"
                        >
                            <span class="text-emerald-500"
                                >[{{ log.timestamp }}]</span
                            >
                            <span
                                class="ml-1 rounded px-1 text-[9px] font-black uppercase"
                                :class="{
                                    'bg-indigo-900 text-indigo-300':
                                        log.type === 'simulation',
                                    'bg-cyan-900 text-cyan-300':
                                        log.type === 'audio',
                                    'bg-emerald-900 text-emerald-300':
                                        log.type === 'success',
                                }"
                            >
                                {{ log.type }}
                            </span>
                            <p class="text-slate-350 mt-1 leading-relaxed">
                                {{ log.message }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-card {
    transition:
        background-color 0.2s,
        border-color 0.2s;
}
</style>
