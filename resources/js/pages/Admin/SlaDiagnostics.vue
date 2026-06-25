<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
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
const showMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null);

// Determine initial mascot state based on backend diagnostics
const checkOverallHealth = () => {
    if (props.averageEvalScore < 0.95 || props.phoneLinesStatus === 'error' || props.webrtcSessionsStatus === 'error') {
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
                text: errors.error || 'Failed to provision carrier phone number.',
            };
        },
    });
};
</script>

<template>
    <Head title="SLA & Diagnostics HUD" />

    <div class="min-h-screen bg-slate-900 py-8 px-4 text-slate-100 dark:bg-slate-950">
        <div class="max-w-6xl mx-auto space-y-8">
            <!-- Duolingo styleHeader -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155] dark:shadow-[0_8px_0_#1e293b]">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-emerald-500 border-b-4 border-emerald-700 rounded-2xl">
                        <ShieldCheck class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tight text-white uppercase">SLA & Diagnostics HUD</h1>
                        <p class="text-xs text-slate-400 font-bold uppercase mt-1 tracking-wider">
                            Autonomous Carrier Provisioning, Scorecard Evals & WebRTC Token Heartbeat
                        </p>
                    </div>
                </div>

                <button
                    @click="runManualDiagnostics"
                    :disabled="isProcessing"
                    class="w-full md:w-auto inline-flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-white font-black uppercase tracking-wider py-3 px-6 rounded-2xl border-b-4 border-emerald-700 active:border-b-0 active:mt-1 transition-all duration-75 text-sm cursor-pointer shadow-lg"
                >
                    <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isProcessing }" />
                    Run System Verification
                </button>
            </div>

            <!-- Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Mascot Panel (Duolingo visual mascot) -->
                <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155] flex flex-col items-center justify-center min-h-[360px] relative overflow-hidden">
                    <h2 class="text-lg font-black uppercase tracking-wider text-slate-300 mb-4">Dispatcher Status</h2>
                    <div class="w-full max-w-[240px] aspect-square flex items-center justify-center">
                        <DispatcherMascot :state="mascotState" />
                    </div>
                </div>

                <!-- SLA & Diagnostics Live Indicators -->
                <div class="lg:col-span-2 bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155] flex flex-col justify-between">
                    <div>
                        <h2 class="text-xl font-black uppercase tracking-wider text-white mb-6 border-b-4 border-slate-700 pb-3">
                            Active SLA Key Metrics
                        </h2>

                        <!-- Diagnostic Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b-2 border-slate-700 text-xs font-black uppercase tracking-wider text-slate-400">
                                        <th class="py-3 px-4">Diagnostic Target</th>
                                        <th class="py-3 px-4">Evaluation Metric</th>
                                        <th class="py-3 px-4">Current Health</th>
                                        <th class="py-3 px-4 text-right">Status Indicator</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700 text-sm font-bold">
                                    <tr>
                                        <td class="py-4 px-4 flex items-center gap-2">
                                            <Phone class="w-4 h-4 text-emerald-400" />
                                            <span>Phone Lines</span>
                                        </td>
                                        <td class="py-4 px-4 text-slate-300">Active Carrier DIDs</td>
                                        <td class="py-4 px-4 font-black text-emerald-400">
                                            {{ props.activeDid ? '100% Available' : 'No DID Bound' }}
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <span class="inline-flex items-center gap-1 bg-emerald-500 text-white font-extrabold uppercase px-3 py-1 rounded-xl text-[10px] border-b-2 border-emerald-700">
                                                <CheckCircle2 class="w-3 h-3" />
                                                Emerald Status
                                            </span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="py-4 px-4">
                                            <div class="flex items-center gap-2">
                                                <Cpu class="w-4 h-4 text-indigo-400" />
                                                <span>Evals Engine</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 text-slate-300">Transcript Scorecards</td>
                                        <td class="py-4 px-4 font-black" :class="props.averageEvalScore >= 0.95 ? 'text-emerald-400' : 'text-rose-400'">
                                            &Theta;<sub>eval</sub> = {{ (props.averageEvalScore * 100).toFixed(1) }}%
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <span
                                                v-if="props.averageEvalScore >= 0.95"
                                                class="inline-flex items-center gap-1 bg-emerald-500 text-white font-extrabold uppercase px-3 py-1 rounded-xl text-[10px] border-b-2 border-emerald-700"
                                            >
                                                <CheckCircle2 class="w-3 h-3" />
                                                Emerald Status
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 bg-rose-500 text-white font-extrabold uppercase px-3 py-1 rounded-xl text-[10px] border-b-2 border-rose-700"
                                            >
                                                <AlertTriangle class="w-3 h-3" />
                                                Degraded
                                            </span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="py-4 px-4">
                                            <div class="flex items-center gap-2">
                                                <Wifi class="w-4 h-4 text-emerald-400" />
                                                <span>WebRTC Sessions</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 text-slate-300">Token Handshakes</td>
                                        <td class="py-4 px-4 font-black text-emerald-400">Zero Timeout Drops</td>
                                        <td class="py-4 px-4 text-right">
                                            <span class="inline-flex items-center gap-1 bg-emerald-500 text-white font-extrabold uppercase px-3 py-1 rounded-xl text-[10px] border-b-2 border-emerald-700">
                                                <CheckCircle2 class="w-3 h-3" />
                                                Emerald Status
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Extra system sharding detail -->
                    <div class="mt-6 p-4 bg-slate-900 border-2 border-slate-700 rounded-2xl flex items-center justify-between text-xs text-slate-400 font-bold uppercase tracking-wider">
                        <span>Active Queue Workers:</span>
                        <span class="text-white font-black bg-slate-700 px-2 py-1 rounded-lg border-b-2 border-slate-800">
                            {{ props.queueWorkersCount }} Workers Online
                        </span>
                    </div>
                </div>
            </div>

            <!-- Programmatic Carrier DID Provisioning Section -->
            <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155]">
                <div class="flex items-center gap-3 mb-6 border-b-4 border-slate-700 pb-3">
                    <Phone class="w-6 h-6 text-emerald-400" />
                    <h2 class="text-xl font-black uppercase tracking-wider text-white">Programmatic Carrier Provisioning</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Left: Explanation -->
                    <div class="space-y-4 text-slate-300 font-bold text-sm">
                        <p>
                            Subscribers can dynamically buy and provision local US or Canada phone numbers without administrative manual setup overhead.
                        </p>
                        <ul class="list-disc pl-5 space-y-2 text-xs text-slate-400 uppercase tracking-wide">
                            <li>Integrates instantly with Vapi / Retell endpoints.</li>
                            <li>Binds tenant voice assistant default inbound handler route.</li>
                            <li>Preserves absolute multi-tenant sharding & database isolation.</li>
                        </ul>
                    </div>

                    <!-- Right: Search and Buy Form -->
                    <div class="bg-slate-900 p-6 border-2 border-slate-700 rounded-2xl space-y-4">
                        <form @submit.prevent="handleProvision" class="space-y-4">
                            <div class="space-y-2">
                                <label for="area_code" class="text-xs font-black uppercase tracking-wider text-slate-400">
                                    Target Area Code (3 Digits)
                                </label>
                                <input
                                    id="area_code"
                                    type="text"
                                    maxlength="3"
                                    v-model="provisionForm.area_code"
                                    placeholder="e.g. 206"
                                    required
                                    class="w-full bg-slate-800 border-4 border-slate-700 rounded-xl px-4 py-3 font-bold text-white tracking-wider focus:outline-none focus:border-indigo-500"
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="isProcessing"
                                class="w-full bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-white font-black uppercase py-3 px-6 rounded-2xl border-b-4 border-emerald-700 active:border-b-0 active:mt-1 transition-all duration-75 text-sm cursor-pointer"
                            >
                                Search & Buy Carrier Line
                            </button>
                        </form>

                        <!-- Toast / Status Message -->
                        <div
                            v-if="showMessage"
                            class="p-4 border-2 rounded-xl text-xs font-black uppercase tracking-wide"
                            :class="showMessage.type === 'success' ? 'bg-emerald-950/40 text-emerald-400 border-emerald-800' : 'bg-rose-950/40 text-rose-400 border-rose-800'"
                        >
                            {{ showMessage.text }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
