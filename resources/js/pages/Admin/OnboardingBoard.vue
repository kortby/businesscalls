<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import {
    CheckCircle,
    XCircle,
    Activity,
    CreditCard,
    Smartphone,
    User,
    Play,
    RefreshCw,
    Sparkles,
    Settings,
} from '@lucide/vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

const props = defineProps<{
    subscriptionActive: boolean;
    mascotSkinActive: boolean;
    phoneProvisioned: boolean;
    allMilestonesPassed: boolean;
    tenant: any;
}>();

// Dynamic States
const subActive = ref(props.subscriptionActive);
const skinActive = ref(props.mascotSkinActive);
const phoneActive = ref(props.phoneProvisioned);
const isProcessing = ref(false);
const showMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null);

// Rive mascot state: 0 = Idle, 1 = Scanning, 2 = Victory, 3 = Error
const mascotState = computed(() => {
    if (isProcessing.value) {
        return 1; // Scanning
    }
    if (subActive.value && skinActive.value && phoneActive.value) {
        return 2; // Victory
    }
    if (!subActive.value || !phoneActive.value) {
        return 3; // Error (if core billing/phone configurations are missing)
    }
    return 0; // Idle
});

// Interactive simulation triggers
const simulateProvisioning = () => {
    isProcessing.value = true;
    showMessage.value = null;

    setTimeout(() => {
        isProcessing.value = false;
        phoneActive.value = true;
        showMessage.value = {
            type: 'success',
            text: 'Carrier Buy Hook Success: Phone Line provisioned and configured!',
        };
    }, 2500);
};

const simulatePaymentGatewayCheck = () => {
    isProcessing.value = true;
    showMessage.value = null;

    setTimeout(() => {
        isProcessing.value = false;
        subActive.value = true;
        showMessage.value = {
            type: 'success',
            text: 'Stripe webhook verified: SaaS subscription active.',
        };
    }, 2000);
};

const toggleMascotSkin = () => {
    skinActive.value = !skinActive.value;
    showMessage.value = {
        type: 'success',
        text: skinActive.value ? 'Theme toggle: Artboard skin customized successfully!' : 'Theme override: Reset to standard skin.',
    };
};

const triggerConfigurationError = () => {
    subActive.value = false;
    showMessage.value = {
        type: 'error',
        text: 'Configuration Error: SaaS subscription billing verification failed / credentials expired.',
    };
};

const resetMilestones = () => {
    subActive.value = props.subscriptionActive;
    skinActive.value = props.mascotSkinActive;
    phoneActive.value = props.phoneProvisioned;
    showMessage.value = null;
};
</script>

<template>
    <Head title="Onboarding Customizer Board" />

    <div class="min-h-screen bg-slate-900 py-8 px-4 text-slate-100 dark:bg-slate-950">
        <div class="max-w-6xl mx-auto space-y-8">
            <!-- Header Panel -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155]">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-emerald-500 border-b-4 border-emerald-700 rounded-2xl">
                        <Settings class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tight text-white uppercase">Onboarding Customizer</h1>
                        <p class="text-xs text-slate-400 font-bold uppercase mt-1 tracking-wider">
                            Interactive Workspace Setup, Subagent Mappings & Rive Observers
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        @click="simulateProvisioning"
                        :disabled="isProcessing"
                        class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-black uppercase tracking-wider py-3 px-6 rounded-2xl border-b-4 border-indigo-850 active:border-b-0 active:mt-1 transition-all duration-75 text-xs shadow-lg cursor-pointer"
                    >
                        Buy Line
                    </button>
                    <button
                        @click="triggerConfigurationError"
                        class="bg-rose-600 hover:bg-rose-500 text-white font-black uppercase tracking-wider py-3 px-6 rounded-2xl border-b-4 border-rose-850 active:border-b-0 active:mt-1 transition-all duration-75 text-xs shadow-lg cursor-pointer"
                    >
                        Simulate Failure
                    </button>
                    <button
                        @click="resetMilestones"
                        class="bg-slate-700 hover:bg-slate-650 text-white font-black uppercase tracking-wider py-3 px-6 rounded-2xl border-b-4 border-slate-900 active:border-b-0 active:mt-1 transition-all duration-75 text-xs shadow-lg cursor-pointer"
                    >
                        Reset Board
                    </button>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Rive Mascot Card -->
                <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155] flex flex-col items-center justify-center min-h-[360px] relative overflow-hidden">
                    <h2 class="text-lg font-black uppercase tracking-wider text-slate-300 mb-4">Mascot Status</h2>
                    <div class="w-full max-w-[220px] aspect-square flex items-center justify-center">
                        <DispatcherMascot :state="mascotState" />
                    </div>
                </div>

                <!-- Onboarding Checklist Table Card -->
                <div class="lg:col-span-2 bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155] flex flex-col justify-between">
                    <div>
                        <h2 class="text-xl font-black uppercase tracking-wider text-white mb-6 border-b-4 border-slate-700 pb-3">
                            Workspace Setup Checklist
                        </h2>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b-2 border-slate-700 text-xs font-black uppercase tracking-wider text-slate-400">
                                        <th class="py-2 px-4">Milestone Target</th>
                                        <th class="py-2 px-4">Configuration Parameter</th>
                                        <th class="py-2 px-4">Database Check</th>
                                        <th class="py-2 px-4">Mascot Action</th>
                                        <th class="py-2 px-4 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700 text-sm font-bold text-slate-300">
                                    <!-- SaaS Subscription -->
                                    <tr class="hover:bg-slate-900/30 transition-colors">
                                        <td class="py-3 px-4 flex items-center gap-2">
                                            <CreditCard class="w-4 h-4 text-emerald-400" />
                                            <span>SaaS Subscription</span>
                                        </td>
                                        <td class="py-3 px-4 text-slate-400">Stripe Checkout Status</td>
                                        <td class="py-3 px-4 text-slate-400"><code>subscriptions</code> Table</td>
                                        <td class="py-3 px-4 text-slate-400">Billing Verification</td>
                                        <td class="py-3 px-4 text-right">
                                            <span v-if="subActive" class="text-emerald-400 flex items-center justify-end gap-1">
                                                <CheckCircle class="w-4 h-4" /> Active
                                            </span>
                                            <button v-else @click="simulatePaymentGatewayCheck" class="bg-amber-500 hover:bg-amber-400 text-slate-950 text-[10px] font-black uppercase px-2.5 py-1 rounded-lg border-b-2 border-amber-700 cursor-pointer">
                                                Verify
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Mascot Skin -->
                                    <tr class="hover:bg-slate-900/30 transition-colors">
                                        <td class="py-3 px-4 flex items-center gap-2">
                                            <User class="w-4 h-4 text-indigo-400" />
                                            <span>Mascot Skin</span>
                                        </td>
                                        <td class="py-3 px-4 text-slate-400">Artboard Skin Selection</td>
                                        <td class="py-3 px-4 text-slate-400"><code>tenants.settings</code> JSON</td>
                                        <td class="py-3 px-4 text-slate-400">UI Theme Toggle</td>
                                        <td class="py-3 px-4 text-right">
                                            <span v-if="skinActive" class="text-emerald-400 flex items-center justify-end gap-1">
                                                <CheckCircle class="w-4 h-4" /> Customized
                                            </span>
                                            <button v-else @click="toggleMascotSkin" class="bg-indigo-500 hover:bg-indigo-400 text-white text-[10px] font-black uppercase px-2.5 py-1 rounded-lg border-b-2 border-indigo-700 cursor-pointer">
                                                Customize
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Phone Provisioning -->
                                    <tr class="hover:bg-slate-900/30 transition-colors">
                                        <td class="py-3 px-4 flex items-center gap-2">
                                            <Smartphone class="w-4 h-4 text-amber-400" />
                                            <span>Phone Provisioning</span>
                                        </td>
                                        <td class="py-3 px-4 text-slate-400">Active Phone Line ID</td>
                                        <td class="py-3 px-4 text-slate-400"><code>tenants.settings</code> JSON</td>
                                        <td class="py-3 px-4 text-slate-400">Line Verification</td>
                                        <td class="py-3 px-4 text-right">
                                            <span v-if="phoneActive" class="text-emerald-400 flex items-center justify-end gap-1">
                                                <CheckCircle class="w-4 h-4" /> Bound
                                            </span>
                                            <button v-else @click="simulateProvisioning" class="bg-amber-500 hover:bg-amber-400 text-slate-950 text-[10px] font-black uppercase px-2.5 py-1 rounded-lg border-b-2 border-amber-700 cursor-pointer">
                                                Buy Line
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Alerts Notice -->
                    <div v-if="showMessage" class="mt-6 p-4 border-4 rounded-2xl text-xs font-black uppercase tracking-wide"
                        :class="showMessage.type === 'success' ? 'bg-emerald-950/40 text-emerald-400 border-emerald-700 shadow-[0_4px_0_#047857]' : 'bg-rose-950/40 text-rose-400 border-rose-700 shadow-[0_4px_0_#be123c]'"
                    >
                        {{ showMessage.text }}
                    </div>
                </div>
            </div>

            <!-- Subagents Workflows Handovers Details Panel -->
            <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155]">
                <h2 class="text-xl font-black uppercase tracking-wider text-white mb-6 border-b-4 border-slate-700 pb-3">
                    Modular Subagent Handovers Configuration
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm font-bold text-slate-300">
                    <div class="space-y-4">
                        <p>
                            Avoid bulky single-prompt scripts by splitting conversational tasks. The parent voice assistant (e.g. <strong>Receptionist Agent</strong>) hands off the customer cleanly to specialized child assistants (e.g. <strong>Payment Agent</strong> or <strong>CSAT Survey Agent</strong>).
                        </p>
                        <p>
                            Under Retell & Vapi settings, targets inherit transcription context to prevent silos, tracking index scoring via $\Phi_{\text{handoff}}$ formulas.
                        </p>
                    </div>

                    <!-- Visual subagents configuration spec mockup -->
                    <div class="bg-slate-900 border-2 border-slate-700 rounded-2xl p-4 space-y-3 font-mono text-xs">
                        <div class="flex items-center justify-between text-indigo-400 font-bold uppercase tracking-wider border-b border-slate-700 pb-2">
                            <span>Subagent mapping payload</span>
                            <span class="text-[9px] bg-slate-800 text-slate-400 px-2 py-0.5 rounded-md">JSON</span>
                        </div>
                        <pre class="text-slate-300 overflow-x-auto text-[10px] leading-relaxed">
{
  "parent_agent": "receptionist_voice_default",
  "child_agents": {
    "payment_checkout": "stripe_voice_secured",
    "feedback_survey": "csat_survey_default"
  },
  "context_inheritance": {
    "inherit_transcript": true,
    "variables": ["customer_name", "amount_due", "booking_id"]
  }
}
                        </pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
