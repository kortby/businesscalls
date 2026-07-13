<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CreditCard,
    DollarSign,
    Lock,
    Shield,
    Activity,
    AlertOctagon,
    CheckCircle,
    HelpCircle,
    RefreshCw,
    MessageSquare,
    Mic,
} from '@lucide/vue';
import { ref, onMounted, computed } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

const props = defineProps<{
    subscriptionStatus: string;
    markupRate: number;
    activePaymentAccount: string;
    transactionSuccessIndex: number;
    totalTransactionsCount: number;
    successfulTransactionsCount: number;
}>();

// Mascot state binding: 0 = Idle, 1 = Scanning (Payments Gateway), 2 = Victory (System Security), 3 = Error (Outbound Dialer)
const mascotState = ref<number>(2);

// Simulated transaction parameters
const isProcessing = ref(false);
const showMessage = ref<{ type: 'success' | 'error'; text: string } | null>(
    null,
);

// Vapi Web Widget Mode: 'voice' or 'chat'
const widgetMode = ref<'voice' | 'chat'>('voice');
const fallbackReason = ref<string>('');

// Load Vapi widget CDN script
onMounted(() => {
    if (!document.querySelector('script[src*="vapi-widget"]')) {
        const script = document.createElement('script');
        script.src =
            'https://cdn.jsdelivr.net/npm/@vapi-ai/web@2/dist/vapi-widget.js';
        script.async = true;
        document.body.appendChild(script);
    }
});

// Trigger Mode-Swapping Voice-to-Chat fallback
const applyFallback = (reason: string) => {
    widgetMode.value = 'chat';
    fallbackReason.value = reason;
    mascotState.value = 3; // Sad error state due to fallback constraint
    showMessage.value = {
        type: 'error',
        text: `Voice link degraded: Mode swapped to Chat due to ${reason}`,
    };
};

const resetMode = () => {
    widgetMode.value = 'voice';
    fallbackReason.value = '';
    mascotState.value = 2; // Victory state
    showMessage.value = null;
};

// Simulate card check transaction
const runVoiceTransaction = (willSucceed = true) => {
    isProcessing.value = true;
    mascotState.value = 1; // Scanning radar animation
    showMessage.value = null;

    setTimeout(() => {
        isProcessing.value = false;

        if (willSucceed) {
            mascotState.value = 2; // Victory
            showMessage.value = {
                type: 'success',
                text: 'Simulated Voice Payment of $150.00 Authorized & Captured!',
            };
        } else {
            mascotState.value = 3; // Error
            showMessage.value = {
                type: 'error',
                text: 'Simulated Voice Payment Failed: Card Declined (Insufficient Funds)',
            };
        }
    }, 2000);
};
</script>

<template>
    <Head title="Billing & Payments Hub" />

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
                        <CreditCard class="h-8 w-8 text-white" />
                    </div>
                    <div>
                        <h1
                            class="text-3xl font-black tracking-tight text-white uppercase"
                        >
                            Billing & Payments
                        </h1>
                        <p
                            class="mt-1 text-xs font-bold tracking-wider text-slate-400 uppercase"
                        >
                            Voice PCI-DSS Card Tokenization, Dialer Rate-Limit
                            Throttles & WebRTC widget fallback
                        </p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Link
                        href="/settings/billing"
                        class="flex cursor-pointer items-center justify-center rounded-2xl border-b-4 border-indigo-800 bg-indigo-600 px-6 py-3 text-xs font-black tracking-wider text-white uppercase shadow-lg transition-all duration-75 hover:bg-indigo-500 active:mt-1 active:border-b-0"
                    >
                        Configure Plans
                    </Link>
                    <button
                        @click="runVoiceTransaction(true)"
                        :disabled="isProcessing"
                        class="cursor-pointer rounded-2xl border-b-4 border-emerald-700 bg-emerald-500 px-6 py-3 text-xs font-black tracking-wider text-white uppercase shadow-lg transition-all duration-75 hover:bg-emerald-400 active:mt-1 active:border-b-0 disabled:opacity-50"
                    >
                        Test Payment Success
                    </button>
                    <button
                        @click="runVoiceTransaction(false)"
                        :disabled="isProcessing"
                        class="cursor-pointer rounded-2xl border-b-4 border-rose-700 bg-rose-500 px-6 py-3 text-xs font-black tracking-wider text-white uppercase shadow-lg transition-all duration-75 hover:bg-rose-400 active:mt-1 active:border-b-0 disabled:opacity-50"
                    >
                        Test Card Decline
                    </button>
                </div>
            </div>

            <!-- Dashboard Columns -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Rive Mascot Display -->
                <div
                    class="flex min-h-[360px] flex-col items-center justify-center rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155]"
                >
                    <h2
                        class="mb-4 text-lg font-black tracking-wider text-slate-300 uppercase"
                    >
                        Billing Mascot
                    </h2>
                    <div
                        class="flex aspect-square w-full max-w-[240px] items-center justify-center"
                    >
                        <DispatcherMascot :state="mascotState" />
                    </div>
                </div>

                <!-- Metrics Grid -->
                <div
                    class="flex flex-col justify-between rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155] lg:col-span-2"
                >
                    <div>
                        <h2
                            class="mb-6 border-b-4 border-slate-700 pb-3 text-xl font-black tracking-wider text-white uppercase"
                        >
                            Key Billing Indicators
                        </h2>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <!-- Subscription Status -->
                            <div
                                class="flex items-center justify-between rounded-2xl border-2 border-slate-700 bg-slate-900 p-4"
                            >
                                <div>
                                    <span
                                        class="text-[10px] font-black tracking-wider text-slate-400 uppercase"
                                        >Subscription Status</span
                                    >
                                    <h3
                                        class="mt-1 text-xl font-black text-white uppercase"
                                    >
                                        {{ props.subscriptionStatus }}
                                    </h3>
                                </div>
                                <span
                                    class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-black text-emerald-400 uppercase"
                                    >Active</span
                                >
                            </div>

                            <!-- Cost Markups -->
                            <div
                                class="flex items-center justify-between rounded-2xl border-2 border-slate-700 bg-slate-900 p-4"
                            >
                                <div>
                                    <span
                                        class="text-[10px] font-black tracking-wider text-slate-400 uppercase"
                                        >Voice markup rate</span
                                    >
                                    <h3
                                        class="mt-1 text-xl font-black text-white"
                                    >
                                        ${{ props.markupRate }}/min
                                    </h3>
                                </div>
                                <span
                                    class="rounded-xl border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-xs font-black text-indigo-400 uppercase"
                                    >Blended</span
                                >
                            </div>

                            <!-- Payment Account -->
                            <div
                                class="flex items-center justify-between rounded-2xl border-2 border-slate-700 bg-slate-900 p-4"
                            >
                                <div>
                                    <span
                                        class="text-[10px] font-black tracking-wider text-slate-400 uppercase"
                                        >Active Account</span
                                    >
                                    <h3
                                        class="mt-2 max-w-[180px] truncate text-sm font-black text-white"
                                    >
                                        {{ props.activePaymentAccount }}
                                    </h3>
                                </div>
                                <span
                                    class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-black text-emerald-400 uppercase"
                                    >Linked</span
                                >
                            </div>

                            <!-- Transaction success rate -->
                            <div
                                class="flex items-center justify-between rounded-2xl border-2 border-slate-700 bg-slate-900 p-4"
                            >
                                <div>
                                    <span
                                        class="text-[10px] font-black tracking-wider text-slate-400 uppercase"
                                        >Voice Payment Index</span
                                    >
                                    <h3
                                        class="mt-1 text-xl font-black text-white"
                                    >
                                        &Omega;<sub>transaction</sub> =
                                        {{
                                            (
                                                props.transactionSuccessIndex *
                                                100
                                            ).toFixed(1)
                                        }}%
                                    </h3>
                                </div>
                                <span
                                    class="rounded-xl px-3 py-1 text-xs font-black uppercase"
                                    :class="
                                        props.transactionSuccessIndex >= 0.95
                                            ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
                                            : 'border border-rose-500/20 bg-rose-500/10 text-rose-400'
                                    "
                                >
                                    {{
                                        props.transactionSuccessIndex >= 0.95
                                            ? 'Healthy'
                                            : 'Below Par'
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Notification alerts -->
                    <div
                        v-if="showMessage"
                        class="mt-6 rounded-2xl border-4 p-4 text-xs font-black tracking-wide uppercase"
                        :class="
                            showMessage.type === 'success'
                                ? 'border-emerald-700 bg-emerald-950/40 text-emerald-400'
                                : 'border-rose-700 bg-rose-950/40 text-rose-400'
                        "
                    >
                        {{ showMessage.text }}
                    </div>
                </div>
            </div>

            <!-- Diagnostics Targets Details -->
            <div
                class="rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155]"
            >
                <h2
                    class="mb-6 border-b-4 border-slate-700 pb-3 text-xl font-black tracking-wider text-white uppercase"
                >
                    Billing Target Diagnostics
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr
                                class="border-b-2 border-slate-700 text-xs font-black tracking-wider text-slate-400 uppercase"
                            >
                                <th class="px-4 py-3">Diagnostic Target</th>
                                <th class="px-4 py-3">Evaluation Metric</th>
                                <th class="px-4 py-3">Saturated Theme</th>
                                <th class="px-4 py-3 text-right">
                                    Mascot Input
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-700 text-sm font-bold"
                        >
                            <tr>
                                <td class="flex items-center gap-2 px-4 py-4">
                                    <Lock class="h-4 w-4 text-amber-500" />
                                    <span>Payments Gateway</span>
                                </td>
                                <td class="px-4 py-4 text-slate-300">
                                    Voice PCI Tokenization
                                </td>
                                <td
                                    class="px-4 py-4 font-extrabold text-amber-500 uppercase"
                                >
                                    Vivid Gold
                                </td>
                                <td
                                    class="px-4 py-4 text-right text-xs text-slate-400 uppercase"
                                >
                                    State Trigger 1
                                </td>
                            </tr>
                            <tr>
                                <td class="flex items-center gap-2 px-4 py-4">
                                    <Shield class="h-4 w-4 text-emerald-500" />
                                    <span>System Security</span>
                                </td>
                                <td class="px-4 py-4 text-slate-300">
                                    Zero Unredacted Card Logs
                                </td>
                                <td
                                    class="px-4 py-4 font-extrabold text-emerald-500 uppercase"
                                >
                                    Saturated Emerald
                                </td>
                                <td
                                    class="px-4 py-4 text-right text-xs text-slate-400 uppercase"
                                >
                                    State Trigger 2
                                </td>
                            </tr>
                            <tr>
                                <td class="flex items-center gap-2 px-4 py-4">
                                    <Activity class="h-4 w-4 text-rose-500" />
                                    <span>Outbound Dialer</span>
                                </td>
                                <td class="px-4 py-4 text-slate-300">
                                    Throttle Status / 429 Prevention
                                </td>
                                <td
                                    class="px-4 py-4 font-extrabold text-rose-500 uppercase"
                                >
                                    Saturated Red
                                </td>
                                <td
                                    class="px-4 py-4 text-right text-xs text-slate-400 uppercase"
                                >
                                    State Trigger 3
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Web Widget Mode-Swapping Fallback Panel -->
            <div
                class="rounded-3xl border-4 border-slate-700 bg-slate-800 p-6 shadow-[0_8px_0_#334155]"
            >
                <div
                    class="mb-4 flex items-center justify-between border-b-4 border-slate-700 pb-3"
                >
                    <div class="flex items-center gap-2">
                        <MessageSquare class="h-6 w-6 text-emerald-400" />
                        <h2
                            class="text-xl font-black tracking-wider text-white uppercase"
                        >
                            Vapi Web Widget Fallback
                        </h2>
                    </div>

                    <span
                        class="rounded-full border-2 px-2.5 py-1 text-xs font-black uppercase"
                        :class="
                            widgetMode === 'voice'
                                ? 'border-emerald-500 bg-emerald-50 text-emerald-600'
                                : 'border-amber-500 bg-amber-50 text-amber-600'
                        "
                    >
                        Active Mode: {{ widgetMode.toUpperCase() }}
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                    <div class="space-y-4 text-sm font-bold text-slate-300">
                        <p>
                            Vapi's Web Widget is loaded globally. If microphone
                            access is blocked or WebRTC connection quality
                            degrades, the widget dynamically transitions to
                            <strong>Chat Mode</strong>.
                        </p>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <button
                                @click="applyFallback('Mic Permission Blocked')"
                                class="cursor-pointer rounded-xl border-b-4 border-slate-900 bg-slate-700 px-4 py-2 text-xs tracking-wider uppercase hover:bg-slate-600 active:mt-1 active:border-b-0"
                            >
                                Trigger Mic Denied Fallback
                            </button>
                            <button
                                @click="
                                    applyFallback('High Jitter / Latency Spike')
                                "
                                class="cursor-pointer rounded-xl border-b-4 border-slate-900 bg-slate-700 px-4 py-2 text-xs tracking-wider uppercase hover:bg-slate-600 active:mt-1 active:border-b-0"
                            >
                                Trigger Jitter Fallback
                            </button>
                            <button
                                v-if="widgetMode === 'chat'"
                                @click="resetMode"
                                class="cursor-pointer rounded-xl border-b-4 border-emerald-800 bg-emerald-600 px-4 py-2 text-xs tracking-wider uppercase hover:bg-emerald-500 active:mt-1 active:border-b-0"
                            >
                                Reset to Voice Mode
                            </button>
                        </div>
                    </div>

                    <!-- Visual widget representation mockup -->
                    <div
                        class="flex min-h-[160px] flex-col justify-between rounded-2xl border-2 border-slate-700 bg-slate-900 p-6"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-black text-slate-400 uppercase"
                                >Conversational Widget Mockup</span
                            >
                            <span
                                class="flex h-2 w-2 animate-ping rounded-full bg-emerald-400"
                            ></span>
                        </div>

                        <div class="flex items-center gap-4 py-4">
                            <div
                                class="relative rounded-full border border-slate-700 bg-slate-800 p-3"
                            >
                                <Mic
                                    v-if="widgetMode === 'voice'"
                                    class="h-6 w-6 text-emerald-400"
                                />
                                <MessageSquare
                                    v-else
                                    class="h-6 w-6 text-amber-400"
                                />
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-white">
                                    {{
                                        widgetMode === 'voice'
                                            ? 'Listening on Audio channel...'
                                            : 'Multi-channel Chat fallback active'
                                    }}
                                </h4>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{
                                        widgetMode === 'voice'
                                            ? 'Press button to speak with dispatcher'
                                            : `Swapped gracefully: ${fallbackReason}`
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- The actual Vapi Web Widget customized mode binding -->
    <vapi-widget
        v-if="widgetMode"
        voice-id="rachel"
        :mode="widgetMode"
        token="dummy-vapi-public-key"
    ></vapi-widget>
</template>
