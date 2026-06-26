<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
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
const showMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null);

// Vapi Web Widget Mode: 'voice' or 'chat'
const widgetMode = ref<'voice' | 'chat'>('voice');
const fallbackReason = ref<string>('');

// Load Vapi widget CDN script
onMounted(() => {
    if (!document.querySelector('script[src*="vapi-widget"]')) {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/@vapi-ai/web@2/dist/vapi-widget.js';
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

    <div class="min-h-screen bg-slate-900 py-8 px-4 text-slate-100 dark:bg-slate-950">
        <div class="max-w-6xl mx-auto space-y-8">
            <!-- Header Panel -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155]">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-emerald-500 border-b-4 border-emerald-700 rounded-2xl">
                        <CreditCard class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tight text-white uppercase">Billing & Payments</h1>
                        <p class="text-xs text-slate-400 font-bold uppercase mt-1 tracking-wider">
                            Voice PCI-DSS Card Tokenization, Dialer Rate-Limit Throttles & WebRTC widget fallback
                        </p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Link
                        href="/settings/billing"
                        class="bg-indigo-600 hover:bg-indigo-500 text-white font-black uppercase tracking-wider py-3 px-6 rounded-2xl border-b-4 border-indigo-800 active:border-b-0 active:mt-1 transition-all duration-75 text-xs shadow-lg cursor-pointer flex items-center justify-center"
                    >
                        Configure Plans
                    </Link>
                    <button
                        @click="runVoiceTransaction(true)"
                        :disabled="isProcessing"
                        class="bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-white font-black uppercase tracking-wider py-3 px-6 rounded-2xl border-b-4 border-emerald-700 active:border-b-0 active:mt-1 transition-all duration-75 text-xs shadow-lg cursor-pointer"
                    >
                        Test Payment Success
                    </button>
                    <button
                        @click="runVoiceTransaction(false)"
                        :disabled="isProcessing"
                        class="bg-rose-500 hover:bg-rose-400 disabled:opacity-50 text-white font-black uppercase tracking-wider py-3 px-6 rounded-2xl border-b-4 border-rose-700 active:border-b-0 active:mt-1 transition-all duration-75 text-xs shadow-lg cursor-pointer"
                    >
                        Test Card Decline
                    </button>
                </div>
            </div>

            <!-- Dashboard Columns -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Rive Mascot Display -->
                <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155] flex flex-col items-center justify-center min-h-[360px]">
                    <h2 class="text-lg font-black uppercase tracking-wider text-slate-300 mb-4">Billing Mascot</h2>
                    <div class="w-full max-w-[240px] aspect-square flex items-center justify-center">
                        <DispatcherMascot :state="mascotState" />
                    </div>
                </div>

                <!-- Metrics Grid -->
                <div class="lg:col-span-2 bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155] flex flex-col justify-between">
                    <div>
                        <h2 class="text-xl font-black uppercase tracking-wider text-white mb-6 border-b-4 border-slate-700 pb-3">
                            Key Billing Indicators
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Subscription Status -->
                            <div class="bg-slate-900 border-2 border-slate-700 p-4 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Subscription Status</span>
                                    <h3 class="text-xl font-black text-white mt-1 uppercase">{{ props.subscriptionStatus }}</h3>
                                </div>
                                <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1 rounded-xl text-xs font-black uppercase">Active</span>
                            </div>

                            <!-- Cost Markups -->
                            <div class="bg-slate-900 border-2 border-slate-700 p-4 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Voice markup rate</span>
                                    <h3 class="text-xl font-black text-white mt-1">${{ props.markupRate }}/min</h3>
                                </div>
                                <span class="bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-3 py-1 rounded-xl text-xs font-black uppercase">Blended</span>
                            </div>

                            <!-- Payment Account -->
                            <div class="bg-slate-900 border-2 border-slate-700 p-4 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Active Account</span>
                                    <h3 class="text-sm font-black text-white mt-2 truncate max-w-[180px]">{{ props.activePaymentAccount }}</h3>
                                </div>
                                <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1 rounded-xl text-xs font-black uppercase">Linked</span>
                            </div>

                            <!-- Transaction success rate -->
                            <div class="bg-slate-900 border-2 border-slate-700 p-4 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Voice Payment Index</span>
                                    <h3 class="text-xl font-black text-white mt-1">&Omega;<sub>transaction</sub> = {{ (props.transactionSuccessIndex * 100).toFixed(1) }}%</h3>
                                </div>
                                <span
                                    class="px-3 py-1 rounded-xl text-xs font-black uppercase"
                                    :class="props.transactionSuccessIndex >= 0.95 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                                >
                                    {{ props.transactionSuccessIndex >= 0.95 ? 'Healthy' : 'Below Par' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Notification alerts -->
                    <div v-if="showMessage" class="mt-6 p-4 border-4 rounded-2xl text-xs font-black uppercase tracking-wide"
                        :class="showMessage.type === 'success' ? 'bg-emerald-950/40 text-emerald-400 border-emerald-700' : 'bg-rose-950/40 text-rose-400 border-rose-700'"
                    >
                        {{ showMessage.text }}
                    </div>
                </div>
            </div>

            <!-- Diagnostics Targets Details -->
            <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155]">
                <h2 class="text-xl font-black uppercase tracking-wider text-white mb-6 border-b-4 border-slate-700 pb-3">
                    Billing Target Diagnostics
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b-2 border-slate-700 text-xs font-black uppercase tracking-wider text-slate-400">
                                <th class="py-3 px-4">Diagnostic Target</th>
                                <th class="py-3 px-4">Evaluation Metric</th>
                                <th class="py-3 px-4">Saturated Theme</th>
                                <th class="py-3 px-4 text-right">Mascot Input</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700 text-sm font-bold">
                            <tr>
                                <td class="py-4 px-4 flex items-center gap-2">
                                    <Lock class="w-4 h-4 text-amber-500" />
                                    <span>Payments Gateway</span>
                                </td>
                                <td class="py-4 px-4 text-slate-300">Voice PCI Tokenization</td>
                                <td class="py-4 px-4 text-amber-500 font-extrabold uppercase">Vivid Gold</td>
                                <td class="py-4 px-4 text-right text-slate-400 uppercase text-xs">State Trigger 1</td>
                            </tr>
                            <tr>
                                <td class="py-4 px-4 flex items-center gap-2">
                                    <Shield class="w-4 h-4 text-emerald-500" />
                                    <span>System Security</span>
                                </td>
                                <td class="py-4 px-4 text-slate-300">Zero Unredacted Card Logs</td>
                                <td class="py-4 px-4 text-emerald-500 font-extrabold uppercase">Saturated Emerald</td>
                                <td class="py-4 px-4 text-right text-slate-400 uppercase text-xs">State Trigger 2</td>
                            </tr>
                            <tr>
                                <td class="py-4 px-4 flex items-center gap-2">
                                    <Activity class="w-4 h-4 text-rose-500" />
                                    <span>Outbound Dialer</span>
                                </td>
                                <td class="py-4 px-4 text-slate-300">Throttle Status / 429 Prevention</td>
                                <td class="py-4 px-4 text-rose-500 font-extrabold uppercase">Saturated Red</td>
                                <td class="py-4 px-4 text-right text-slate-400 uppercase text-xs">State Trigger 3</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Web Widget Mode-Swapping Fallback Panel -->
            <div class="bg-slate-800 border-4 border-slate-700 rounded-3xl p-6 shadow-[0_8px_0_#334155]">
                <div class="flex items-center justify-between mb-4 border-b-4 border-slate-700 pb-3">
                    <div class="flex items-center gap-2">
                        <MessageSquare class="w-6 h-6 text-emerald-400" />
                        <h2 class="text-xl font-black uppercase tracking-wider text-white">Vapi Web Widget Fallback</h2>
                    </div>

                    <span class="text-xs font-black uppercase px-2.5 py-1 border-2 rounded-full"
                        :class="widgetMode === 'voice' ? 'bg-emerald-50 text-emerald-600 border-emerald-500' : 'bg-amber-50 text-amber-600 border-amber-500'"
                    >
                        Active Mode: {{ widgetMode.toUpperCase() }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4 font-bold text-sm text-slate-300">
                        <p>
                            Vapi's Web Widget is loaded globally. If microphone access is blocked or WebRTC connection quality degrades, the widget dynamically transitions to <strong>Chat Mode</strong>.
                        </p>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <button @click="applyFallback('Mic Permission Blocked')"
                                class="bg-slate-700 hover:bg-slate-600 border-b-4 border-slate-900 active:border-b-0 active:mt-1 py-2 px-4 rounded-xl text-xs uppercase tracking-wider cursor-pointer"
                            >
                                Trigger Mic Denied Fallback
                            </button>
                            <button @click="applyFallback('High Jitter / Latency Spike')"
                                class="bg-slate-700 hover:bg-slate-600 border-b-4 border-slate-900 active:border-b-0 active:mt-1 py-2 px-4 rounded-xl text-xs uppercase tracking-wider cursor-pointer"
                            >
                                Trigger Jitter Fallback
                            </button>
                            <button v-if="widgetMode === 'chat'" @click="resetMode"
                                class="bg-emerald-600 hover:bg-emerald-500 border-b-4 border-emerald-800 active:border-b-0 active:mt-1 py-2 px-4 rounded-xl text-xs uppercase tracking-wider cursor-pointer"
                            >
                                Reset to Voice Mode
                            </button>
                        </div>
                    </div>

                    <!-- Visual widget representation mockup -->
                    <div class="bg-slate-900 p-6 border-2 border-slate-700 rounded-2xl flex flex-col justify-between min-h-[160px]">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-400 font-black uppercase">Conversational Widget Mockup</span>
                            <span class="flex h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
                        </div>

                        <div class="flex items-center gap-4 py-4">
                            <div class="p-3 bg-slate-800 rounded-full border border-slate-700 relative">
                                <Mic v-if="widgetMode === 'voice'" class="w-6 h-6 text-emerald-400" />
                                <MessageSquare v-else class="w-6 h-6 text-amber-400" />
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-white">
                                    {{ widgetMode === 'voice' ? 'Listening on Audio channel...' : 'Multi-channel Chat fallback active' }}
                                </h4>
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ widgetMode === 'voice' ? 'Press button to speak with dispatcher' : `Swapped gracefully: ${fallbackReason}` }}
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
