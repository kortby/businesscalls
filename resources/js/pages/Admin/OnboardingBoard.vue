<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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
import { ref, onMounted, computed } from 'vue';
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
const showMessage = ref<{ type: 'success' | 'error'; text: string } | null>(
    null,
);

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
        text: skinActive.value
            ? 'Theme toggle: Artboard skin customized successfully!'
            : 'Theme override: Reset to standard skin.',
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

    <div
        class="min-h-screen bg-slate-900 px-4 py-8 text-slate-100 dark:bg-slate-950"
    >
        <div class="mx-auto max-w-6xl space-y-8">
            <!-- Header Panel -->
            <div
                class="flex flex-col items-center justify-between gap-6 rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155] md:flex-row"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="rounded-2xl border-b-4 border-emerald-700 bg-emerald-500 p-3"
                    >
                        <Settings class="h-8 w-8 text-white" />
                    </div>
                    <div>
                        <h1
                            class="text-3xl font-black tracking-tight text-white uppercase"
                        >
                            Onboarding Customizer
                        </h1>
                        <p
                            class="mt-1 text-xs font-bold tracking-wider text-slate-400 uppercase"
                        >
                            Interactive Workspace Setup, Subagent Mappings &
                            Rive Observers
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        @click="simulateProvisioning"
                        :disabled="isProcessing"
                        class="border-indigo-850 cursor-pointer rounded-2xl border-b-4 bg-indigo-600 px-6 py-3 text-xs font-black tracking-wider text-white uppercase shadow-lg transition-all duration-75 hover:bg-indigo-500 active:mt-1 active:border-b-0 disabled:opacity-50"
                    >
                        Buy Line
                    </button>
                    <button
                        @click="triggerConfigurationError"
                        class="border-rose-850 cursor-pointer rounded-2xl border-b-4 bg-rose-600 px-6 py-3 text-xs font-black tracking-wider text-white uppercase shadow-lg transition-all duration-75 hover:bg-rose-500 active:mt-1 active:border-b-0"
                    >
                        Simulate Failure
                    </button>
                    <button
                        @click="resetMilestones"
                        class="hover:bg-slate-650 cursor-pointer rounded-2xl border-b-4 border-slate-900 bg-slate-700 px-6 py-3 text-xs font-black tracking-wider text-white uppercase shadow-lg transition-all duration-75 active:mt-1 active:border-b-0"
                    >
                        Reset Board
                    </button>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Rive Mascot Card -->
                <div
                    class="relative flex min-h-[360px] flex-col items-center justify-center overflow-hidden rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155]"
                >
                    <h2
                        class="mb-4 text-lg font-black tracking-wider text-slate-300 uppercase"
                    >
                        Mascot Status
                    </h2>
                    <div
                        class="flex aspect-square w-full max-w-[220px] items-center justify-center"
                    >
                        <DispatcherMascot :state="mascotState" />
                    </div>
                </div>

                <!-- Onboarding Checklist Table Card -->
                <div
                    class="flex flex-col justify-between rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155] lg:col-span-2"
                >
                    <div>
                        <h2
                            class="mb-6 border-b-4 border-slate-700 pb-3 text-xl font-black tracking-wider text-white uppercase"
                        >
                            Workspace Setup Checklist
                        </h2>

                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-left">
                                <thead>
                                    <tr
                                        class="border-b-2 border-slate-700 text-xs font-black tracking-wider text-slate-400 uppercase"
                                    >
                                        <th class="px-4 py-2">
                                            Milestone Target
                                        </th>
                                        <th class="px-4 py-2">
                                            Configuration Parameter
                                        </th>
                                        <th class="px-4 py-2">
                                            Database Check
                                        </th>
                                        <th class="px-4 py-2">Mascot Action</th>
                                        <th class="px-4 py-2 text-right">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-700 text-sm font-bold text-slate-300"
                                >
                                    <!-- SaaS Subscription -->
                                    <tr
                                        class="transition-colors hover:bg-slate-900/30"
                                    >
                                        <td
                                            class="flex items-center gap-2 px-4 py-3"
                                        >
                                            <CreditCard
                                                class="h-4 w-4 text-emerald-400"
                                            />
                                            <span>SaaS Subscription</span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">
                                            Stripe Checkout Status
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">
                                            <code>subscriptions</code> Table
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">
                                            Billing Verification
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span
                                                v-if="subActive"
                                                class="flex items-center justify-end gap-1 text-emerald-400"
                                            >
                                                <CheckCircle class="h-4 w-4" />
                                                Active
                                            </span>
                                            <button
                                                v-else
                                                @click="
                                                    simulatePaymentGatewayCheck
                                                "
                                                class="cursor-pointer rounded-lg border-b-2 border-amber-700 bg-amber-500 px-2.5 py-1 text-[10px] font-black text-slate-950 uppercase hover:bg-amber-400"
                                            >
                                                Verify
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Mascot Skin -->
                                    <tr
                                        class="transition-colors hover:bg-slate-900/30"
                                    >
                                        <td
                                            class="flex items-center gap-2 px-4 py-3"
                                        >
                                            <User
                                                class="h-4 w-4 text-indigo-400"
                                            />
                                            <span>Mascot Skin</span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">
                                            Artboard Skin Selection
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">
                                            <code>tenants.settings</code> JSON
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">
                                            UI Theme Toggle
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span
                                                v-if="skinActive"
                                                class="flex items-center justify-end gap-1 text-emerald-400"
                                            >
                                                <CheckCircle class="h-4 w-4" />
                                                Customized
                                            </span>
                                            <button
                                                v-else
                                                @click="toggleMascotSkin"
                                                class="cursor-pointer rounded-lg border-b-2 border-indigo-700 bg-indigo-500 px-2.5 py-1 text-[10px] font-black text-white uppercase hover:bg-indigo-400"
                                            >
                                                Customize
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Phone Provisioning -->
                                    <tr
                                        class="transition-colors hover:bg-slate-900/30"
                                    >
                                        <td
                                            class="flex items-center gap-2 px-4 py-3"
                                        >
                                            <Smartphone
                                                class="h-4 w-4 text-amber-400"
                                            />
                                            <span>Phone Provisioning</span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">
                                            Active Phone Line ID
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">
                                            <code>tenants.settings</code> JSON
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">
                                            Line Verification
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span
                                                v-if="phoneActive"
                                                class="flex items-center justify-end gap-1 text-emerald-400"
                                            >
                                                <CheckCircle class="h-4 w-4" />
                                                Bound
                                            </span>
                                            <button
                                                v-else
                                                @click="simulateProvisioning"
                                                class="cursor-pointer rounded-lg border-b-2 border-amber-700 bg-amber-500 px-2.5 py-1 text-[10px] font-black text-slate-950 uppercase hover:bg-amber-400"
                                            >
                                                Buy Line
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Alerts Notice -->
                    <div
                        v-if="showMessage"
                        class="mt-6 rounded-2xl border-4 p-4 text-xs font-black tracking-wide uppercase"
                        :class="
                            showMessage.type === 'success'
                                ? 'border-emerald-700 bg-emerald-950/40 text-emerald-400 shadow-[0_4px_0_#047857]'
                                : 'border-rose-700 bg-rose-950/40 text-rose-400 shadow-[0_4px_0_#be123c]'
                        "
                    >
                        {{ showMessage.text }}
                    </div>
                </div>
            </div>

            <!-- Subagents Workflows Handovers Details Panel -->
            <div
                class="rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155]"
            >
                <h2
                    class="mb-6 border-b-4 border-slate-700 pb-3 text-xl font-black tracking-wider text-white uppercase"
                >
                    Modular Subagent Handovers Configuration
                </h2>

                <div
                    class="grid grid-cols-1 gap-8 text-sm font-bold text-slate-300 md:grid-cols-2"
                >
                    <div class="space-y-4">
                        <p>
                            Avoid bulky single-prompt scripts by splitting
                            conversational tasks. The parent voice assistant
                            (e.g. <strong>Receptionist Agent</strong>) hands off
                            the customer cleanly to specialized child assistants
                            (e.g. <strong>Payment Agent</strong> or
                            <strong>CSAT Survey Agent</strong>).
                        </p>
                        <p>
                            Under Retell & Vapi settings, targets inherit
                            transcription context to prevent silos, tracking
                            index scoring via $\Phi_{\text{handoff}}$ formulas.
                        </p>
                    </div>

                    <!-- Visual subagents configuration spec mockup -->
                    <div
                        class="space-y-3 rounded-2xl border-2 border-slate-700 bg-slate-900 p-4 font-mono text-xs"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-700 pb-2 font-bold tracking-wider text-indigo-400 uppercase"
                        >
                            <span>Subagent mapping payload</span>
                            <span
                                class="rounded-md bg-slate-800 px-2 py-0.5 text-[9px] text-slate-400"
                                >JSON</span
                            >
                        </div>
                        <pre
                            class="overflow-x-auto text-[10px] leading-relaxed text-slate-300"
                        >
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
