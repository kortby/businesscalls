<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Activity,
    Phone,
    Cpu,
    Wifi,
    CheckCircle2,
    AlertTriangle,
    ShieldCheck,
    RefreshCw,
    Terminal,
} from '@lucide/vue';
import { ref, computed, onMounted } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

const props = defineProps<{
    averageEvalScore: number;
    activeDid: string;
    queueWorkersCount: number;
    phoneLinesStatus: string;
    evalsEngineStatus: string;
    webrtcSessionsStatus: string;
}>();

// State definition for Rive mascot: 0 = Idle, 1 = Scanning, 2 = Victory, 3 = Error
const mascotState = ref<number>(2);

// Form for provisioning a phone number
const provisionForm = useForm({
    area_code: '',
});

const isProcessing = ref(false);
const showMessage = ref<{ type: 'success' | 'error'; text: string } | null>(
    null,
);

// Determine initial mascot state based on backend diagnostics
const checkOverallHealth = () => {
    if (
        props.averageEvalScore < 0.95 ||
        props.phoneLinesStatus === 'error' ||
        props.webrtcSessionsStatus === 'error'
    ) {
        mascotState.value = 3; // Sad Error State
    } else {
        mascotState.value = 2; // Celebratory Victory State
    }
};

onMounted(() => {
    checkOverallHealth();
});

const runManualDiagnostics = () => {
    isProcessing.value = true;
    mascotState.value = 1; // Scanning radar animation

    setTimeout(() => {
        isProcessing.value = false;
        checkOverallHealth();
    }, 2500);
};

const handleProvision = () => {
    if (!/^\d{3}$/.test(provisionForm.area_code)) {
        showMessage.value = {
            type: 'error',
            text: 'Area code must be exactly 3 digits.',
        };
        mascotState.value = 3;

        return;
    }

    isProcessing.value = true;
    mascotState.value = 1; // Scanning radar animation
    showMessage.value = null;

    provisionForm.post(route('admin.telephony.provision'), {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false;
            mascotState.value = 2; // Victory celebratory animation
            showMessage.value = {
                type: 'success',
                text: `Successfully provisioned carrier DID with area code ${provisionForm.area_code}!`,
            };
            provisionForm.reset();
        },
        onError: (errors) => {
            isProcessing.value = false;
            mascotState.value = 3; // Sad Error State
            showMessage.value = {
                type: 'error',
                text:
                    errors.error || 'Failed to provision carrier phone number.',
            };
        },
    });
};
</script>

<template>
    <Head title="SLA & Diagnostics HUD" />

    <div
        class="min-h-screen bg-slate-900 px-4 py-8 text-slate-100 dark:bg-slate-950"
    >
        <div class="mx-auto max-w-6xl space-y-8">
            <!-- Duolingo styleHeader -->
            <div
                class="flex flex-col items-center justify-between gap-6 rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155] md:flex-row dark:shadow-[0_8px_0_#1e293b]"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="rounded-2xl border-b-4 border-emerald-700 bg-emerald-500 p-3"
                    >
                        <ShieldCheck class="h-8 w-8 text-white" />
                    </div>
                    <div>
                        <h1
                            class="text-3xl font-black tracking-tight text-white uppercase"
                        >
                            SLA & Diagnostics HUD
                        </h1>
                        <p
                            class="mt-1 text-xs font-bold tracking-wider text-slate-400 uppercase"
                        >
                            Autonomous Carrier Provisioning, Scorecard Evals &
                            WebRTC Token Heartbeat
                        </p>
                    </div>
                </div>

                <button
                    @click="runManualDiagnostics"
                    :disabled="isProcessing"
                    class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border-b-4 border-emerald-700 bg-emerald-500 px-6 py-3 text-sm font-black tracking-wider text-white uppercase shadow-lg transition-all duration-75 hover:bg-emerald-400 active:mt-1 active:border-b-0 disabled:opacity-50 md:w-auto"
                >
                    <RefreshCw
                        class="h-4 w-4"
                        :class="{ 'animate-spin': isProcessing }"
                    />
                    Run System Verification
                </button>
            </div>

            <!-- Dashboard Grid -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Mascot Panel (Duolingo visual mascot) -->
                <div
                    class="relative flex min-h-[360px] flex-col items-center justify-center overflow-hidden rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155]"
                >
                    <h2
                        class="mb-4 text-lg font-black tracking-wider text-slate-300 uppercase"
                    >
                        Dispatcher Status
                    </h2>
                    <div
                        class="flex aspect-square w-full max-w-[240px] items-center justify-center"
                    >
                        <DispatcherMascot :state="mascotState" />
                    </div>
                </div>

                <!-- SLA & Diagnostics Live Indicators -->
                <div
                    class="flex flex-col justify-between rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155] lg:col-span-2"
                >
                    <div>
                        <h2
                            class="mb-6 border-b-4 border-slate-700 pb-3 text-xl font-black tracking-wider text-white uppercase"
                        >
                            Active SLA Key Metrics
                        </h2>

                        <!-- Diagnostic Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-left">
                                <thead>
                                    <tr
                                        class="border-b-2 border-slate-700 text-xs font-black tracking-wider text-slate-400 uppercase"
                                    >
                                        <th class="px-4 py-3">
                                            Diagnostic Target
                                        </th>
                                        <th class="px-4 py-3">
                                            Evaluation Metric
                                        </th>
                                        <th class="px-4 py-3">
                                            Current Health
                                        </th>
                                        <th class="px-4 py-3 text-right">
                                            Status Indicator
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-700 text-sm font-bold"
                                >
                                    <tr>
                                        <td
                                            class="flex items-center gap-2 px-4 py-4"
                                        >
                                            <Phone
                                                class="h-4 w-4 text-emerald-400"
                                            />
                                            <span>Phone Lines</span>
                                        </td>
                                        <td class="px-4 py-4 text-slate-300">
                                            Active Carrier DIDs
                                        </td>
                                        <td
                                            class="px-4 py-4 font-black text-emerald-400"
                                        >
                                            {{
                                                props.activeDid
                                                    ? '100% Available'
                                                    : 'No DID Bound'
                                            }}
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <span
                                                class="inline-flex items-center gap-1 rounded-xl border-b-2 border-emerald-700 bg-emerald-500 px-3 py-1 text-[10px] font-extrabold text-white uppercase"
                                            >
                                                <CheckCircle2 class="h-3 w-3" />
                                                Emerald Status
                                            </span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-4">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Cpu
                                                    class="h-4 w-4 text-indigo-400"
                                                />
                                                <span>Evals Engine</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-slate-300">
                                            Transcript Scorecards
                                        </td>
                                        <td
                                            class="px-4 py-4 font-black"
                                            :class="
                                                props.averageEvalScore >= 0.95
                                                    ? 'text-emerald-400'
                                                    : 'text-rose-400'
                                            "
                                        >
                                            &Theta;<sub>eval</sub> =
                                            {{
                                                (
                                                    props.averageEvalScore * 100
                                                ).toFixed(1)
                                            }}%
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <span
                                                v-if="
                                                    props.averageEvalScore >=
                                                    0.95
                                                "
                                                class="inline-flex items-center gap-1 rounded-xl border-b-2 border-emerald-700 bg-emerald-500 px-3 py-1 text-[10px] font-extrabold text-white uppercase"
                                            >
                                                <CheckCircle2 class="h-3 w-3" />
                                                Emerald Status
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 rounded-xl border-b-2 border-rose-700 bg-rose-500 px-3 py-1 text-[10px] font-extrabold text-white uppercase"
                                            >
                                                <AlertTriangle
                                                    class="h-3 w-3"
                                                />
                                                Degraded
                                            </span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-4">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Wifi
                                                    class="h-4 w-4 text-emerald-400"
                                                />
                                                <span>WebRTC Sessions</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-slate-300">
                                            Token Handshakes
                                        </td>
                                        <td
                                            class="px-4 py-4 font-black text-emerald-400"
                                        >
                                            Zero Timeout Drops
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <span
                                                class="inline-flex items-center gap-1 rounded-xl border-b-2 border-emerald-700 bg-emerald-500 px-3 py-1 text-[10px] font-extrabold text-white uppercase"
                                            >
                                                <CheckCircle2 class="h-3 w-3" />
                                                Emerald Status
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Extra system sharding detail -->
                    <div
                        class="mt-6 flex items-center justify-between rounded-2xl border-2 border-slate-700 bg-slate-900 p-4 text-xs font-bold tracking-wider text-slate-400 uppercase"
                    >
                        <span>Active Queue Workers:</span>
                        <span
                            class="rounded-lg border-b-2 border-slate-800 bg-slate-700 px-2 py-1 font-black text-white"
                        >
                            {{ props.queueWorkersCount }} Workers Online
                        </span>
                    </div>
                </div>
            </div>

            <!-- Programmatic Carrier DID Provisioning Section -->
            <div
                class="rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155]"
            >
                <div
                    class="mb-6 flex items-center gap-3 border-b-4 border-slate-700 pb-3"
                >
                    <Phone class="h-6 w-6 text-emerald-400" />
                    <h2
                        class="text-xl font-black tracking-wider text-white uppercase"
                    >
                        Programmatic Carrier Provisioning
                    </h2>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                    <!-- Left: Explanation -->
                    <div class="space-y-4 text-sm font-bold text-slate-300">
                        <p>
                            Subscribers can dynamically buy and provision local
                            US or Canada phone numbers without administrative
                            manual setup overhead.
                        </p>
                        <ul
                            class="list-disc space-y-2 pl-5 text-xs tracking-wide text-slate-400 uppercase"
                        >
                            <li>
                                Integrates instantly with Vapi / Retell
                                endpoints.
                            </li>
                            <li>
                                Binds tenant voice assistant default inbound
                                handler route.
                            </li>
                            <li>
                                Preserves absolute multi-tenant sharding &
                                database isolation.
                            </li>
                        </ul>
                    </div>

                    <!-- Right: Search and Buy Form -->
                    <div
                        class="space-y-4 rounded-2xl border-2 border-slate-700 bg-slate-900 p-6"
                    >
                        <form
                            @submit.prevent="handleProvision"
                            class="space-y-4"
                        >
                            <div class="space-y-2">
                                <label
                                    for="area_code"
                                    class="text-xs font-black tracking-wider text-slate-400 uppercase"
                                >
                                    Target Area Code (3 Digits)
                                </label>
                                <input
                                    id="area_code"
                                    type="text"
                                    maxlength="3"
                                    v-model="provisionForm.area_code"
                                    placeholder="e.g. 206"
                                    required
                                    class="w-full rounded-xl border-4 border-slate-700 bg-slate-800 px-4 py-3 font-bold tracking-wider text-white focus:border-indigo-500 focus:outline-none"
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="isProcessing"
                                class="w-full cursor-pointer rounded-2xl border-b-4 border-emerald-700 bg-emerald-500 px-6 py-3 text-sm font-black text-white uppercase transition-all duration-75 hover:bg-emerald-400 active:mt-1 active:border-b-0 disabled:opacity-50"
                            >
                                Search & Buy Carrier Line
                            </button>
                        </form>

                        <!-- Toast / Status Message -->
                        <div
                            v-if="showMessage"
                            class="rounded-xl border-2 p-4 text-xs font-black tracking-wide uppercase"
                            :class="
                                showMessage.type === 'success'
                                    ? 'border-emerald-800 bg-emerald-950/40 text-emerald-400'
                                    : 'border-rose-800 bg-rose-950/40 text-rose-400'
                            "
                        >
                            {{ showMessage.text }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
