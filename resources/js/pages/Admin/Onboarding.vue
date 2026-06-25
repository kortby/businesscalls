<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    XCircle,
    Activity,
    CreditCard,
    Users,
    MessageSquare,
    Zap,
    TrendingUp,
    Sparkles,
    AlertTriangle,
    RefreshCw
} from '@lucide/vue';
import { ref, computed } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

const props = defineProps<{
    tenant: {
        id: number;
        name: string;
        slug: string;
        plan: string;
        settings: Record<string, any>;
    };
    isSubscribed: boolean;
    technicianRosterStatus: 'Mapped' | 'Empty';
    voiceAiPromptsStatus: 'Programmed' | 'Default';
}>();

// Simulation and interactive overrides
const isSubscribedSim = ref<boolean>(props.isSubscribed);
const technicianRosterSim = ref<'Mapped' | 'Empty'>(props.technicianRosterStatus);
const voiceAiPromptsSim = ref<'Programmed' | 'Default'>(props.voiceAiPromptsStatus);
const paymentFailedSim = ref<boolean>(false);
const isUpgrading = ref<boolean>(false);
const upgradeMessage = ref<string>('');

// Rive Mascot State:
// - Fully configured (subscribed, mapped, programmed) -> Victory (state 2)
// - Payment failed / invalid params -> Sad error (state 3)
// - Configuration in progress / line provisioning -> Scanning radar (state 1)
const mascotState = computed(() => {
    if (paymentFailedSim.value) {
        return 3; // Sad error state
    }
    const fullyConfigured = isSubscribedSim.value && 
                            technicianRosterSim.value === 'Mapped' && 
                            voiceAiPromptsSim.value === 'Programmed';
                            
    if (fullyConfigured) {
        return 2; // Victory!
    }
    
    return 1; // Scanning radar (Setup in progress)
});

// Trigger Stripe Checkout Simulation
const startCheckout = async (plan: 'pro' | 'enterprise') => {
    isUpgrading.value = true;
    upgradeMessage.value = '';
    
    try {
        const response = await fetch('/api/billing/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ plan }),
        });
        
        const data = await response.json();
        if (response.ok && data.url) {
            upgradeMessage.value = 'Checkout session initiated successfully!';
            // In test mode, this completes instantly. Let's update the simulation state
            isSubscribedSim.value = true;
            paymentFailedSim.value = false;
            if (data.url.includes('checkout=success')) {
                setTimeout(() => {
                    router.reload();
                }, 2000);
            } else {
                window.location.href = data.url;
            }
        } else {
            upgradeMessage.value = data.error || 'Failed to start billing checkout.';
            paymentFailedSim.value = true;
        }
    } catch (e) {
        upgradeMessage.value = 'Connection error initiating Stripe checkout.';
        paymentFailedSim.value = true;
    } finally {
        isUpgrading.value = false;
    }
};

const triggerPaymentFailure = () => {
    paymentFailedSim.value = true;
    upgradeMessage.value = '⚠️ Simulated Payment failure recorded. Mascot state locked to error.';
};

const resetOnboarding = () => {
    isSubscribedSim.value = props.isSubscribed;
    technicianRosterSim.value = props.technicianRosterStatus;
    voiceAiPromptsSim.value = props.voiceAiPromptsStatus;
    paymentFailedSim.value = false;
    upgradeMessage.value = 'Configuration values reset to database defaults.';
};
</script>

<template>
    <Head title="Subscriber Onboarding Workspace" />

    <div class="mx-auto flex max-w-[1400px] flex-col gap-8 p-4 sm:p-6 md:p-8 bg-slate-950 text-slate-100 min-h-screen">
        
        <!-- Duolingo style Geometric Title Banner -->
        <div class="relative overflow-hidden rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-[6px_6px_0px_0px_rgba(16,185,129,0.3)]">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-white sm:text-4xl flex items-center gap-3">
                        <span class="rounded-2xl border-4 border-emerald-500 bg-emerald-600 px-3 py-1 text-white shadow-[2px_2px_0px_0px_rgba(15,23,42,1)]">SETUP</span>
                        Onboarding Workspace
                    </h1>
                    <p class="text-slate-400 font-bold mt-2 text-sm sm:text-base">
                        Configure SaaS billing plans, technician skills profiles, and voice agent system prompts.
                    </p>
                </div>

                <!-- Active plan tier widget -->
                <div class="flex flex-col gap-1.5 min-w-[280px] bg-slate-950 p-4 rounded-2xl border-4 border-slate-800">
                    <div class="flex justify-between text-xs font-black tracking-widest text-slate-400 uppercase">
                        <span>Workspace Tier</span>
                        <span class="text-emerald-500 font-black tracking-wider capitalize">{{ tenant.plan || 'Free' }}</span>
                    </div>

                    <div class="h-4 w-full bg-slate-850 rounded-full overflow-hidden border-2 border-slate-700 mt-1">
                        <div 
                            class="h-full rounded-full transition-all duration-300"
                            :class="[
                                isSubscribedSim ? 'bg-emerald-500 w-full' : 'bg-slate-700 w-1/3'
                            ]"
                        ></div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 font-bold mt-1">
                        <span>Checkout Mode: Stripe</span>
                        <span v-if="isSubscribedSim" class="text-emerald-400 font-black">Active Subscriber</span>
                        <span v-else class="text-slate-400">Trial/Basic limits</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Workspace Setup Area -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            
            <!-- Matrix Table and upgrade steps (2/3 columns) -->
            <div class="flex flex-col gap-8 lg:col-span-2">
                
                <!-- Onboarding steps matrix -->
                <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-6 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                    <h2 class="text-xl font-black text-white flex items-center gap-2 border-b-4 border-slate-800 pb-4">
                        <Activity class="h-6 w-6 text-emerald-500 animate-pulse" />
                        Dynamic Onboarding Matrix
                    </h2>

                    <!-- Clean Single-Spaced Onboarding Table -->
                    <div class="overflow-x-auto rounded-2xl border-4 border-slate-850 bg-slate-950">
                        <table class="w-full text-left border-collapse text-xs font-bold">
                            <thead>
                                <tr class="bg-slate-900 text-slate-400 uppercase tracking-widest text-[10px] border-b-4 border-slate-800">
                                    <th class="p-3">Setup Step</th>
                                    <th class="p-3">Configuration Target</th>
                                    <th class="p-3">Validation Source</th>
                                    <th class="p-3 text-right">Status Indicator</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-850">
                                <!-- Step 1: SaaS Subscription -->
                                <tr class="hover:bg-slate-900/30 transition-colors">
                                    <td class="p-3 text-white font-extrabold flex items-center gap-2">
                                        <CreditCard class="h-4.5 w-4.5 text-slate-400" />
                                        SaaS Subscription
                                    </td>
                                    <td class="p-3 font-mono text-slate-300">Stripe Checkout Session</td>
                                    <td class="p-3 font-mono text-slate-400">subscriptions Table</td>
                                    <td class="p-3 text-right">
                                        <span 
                                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-0.5 border-2 text-[9px] uppercase font-black"
                                            :class="[
                                                isSubscribedSim 
                                                    ? 'bg-emerald-950/60 text-emerald-400 border-emerald-800' 
                                                    : 'bg-slate-900 text-slate-400 border-slate-750'
                                            ]"
                                        >
                                            <CheckCircle2 v-if="isSubscribedSim" class="h-3 w-3 text-emerald-400" />
                                            <XCircle v-else class="h-3 w-3 text-slate-400" />
                                            {{ isSubscribedSim ? 'Active' : 'Unsubscribed' }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Step 2: Technician Roster -->
                                <tr class="hover:bg-slate-900/30 transition-colors">
                                    <td class="p-3 text-white font-extrabold flex items-center gap-2">
                                        <Users class="h-4.5 w-4.5 text-slate-400" />
                                        Technician Roster
                                    </td>
                                    <td class="p-3 font-mono text-slate-300">Staff Skills Profile</td>
                                    <td class="p-3 font-mono text-slate-400">employees Table</td>
                                    <td class="p-3 text-right">
                                        <span 
                                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-0.5 border-2 text-[9px] uppercase font-black"
                                            :class="[
                                                technicianRosterSim === 'Mapped' 
                                                    ? 'bg-emerald-950/60 text-emerald-400 border-emerald-800' 
                                                    : 'bg-slate-900 text-slate-400 border-slate-750'
                                            ]"
                                        >
                                            <CheckCircle2 v-if="technicianRosterSim === 'Mapped'" class="h-3 w-3 text-emerald-400" />
                                            <XCircle v-else class="h-3 w-3 text-slate-400" />
                                            {{ technicianRosterSim }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Step 3: Voice AI Prompts -->
                                <tr class="hover:bg-slate-900/30 transition-colors">
                                    <td class="p-3 text-white font-extrabold flex items-center gap-2">
                                        <MessageSquare class="h-4.5 w-4.5 text-slate-400" />
                                        Voice AI Prompts
                                    </td>
                                    <td class="p-3 font-mono text-slate-300">Agent System Prompts</td>
                                    <td class="p-3 font-mono text-slate-400">tenants settings JSON</td>
                                    <td class="p-3 text-right">
                                        <span 
                                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-0.5 border-2 text-[9px] uppercase font-black"
                                            :class="[
                                                voiceAiPromptsSim === 'Programmed' 
                                                    ? 'bg-emerald-950/60 text-emerald-400 border-emerald-800' 
                                                    : 'bg-slate-900 text-slate-400 border-slate-750'
                                            ]"
                                        >
                                            <CheckCircle2 v-if="voiceAiPromptsSim === 'Programmed'" class="h-3 w-3 text-emerald-400" />
                                            <XCircle v-else class="h-3 w-3 text-slate-400" />
                                            {{ voiceAiPromptsSim }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Simulation Tools for developers -->
                <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-4 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                    <h2 class="text-xl font-black text-white flex items-center gap-2 border-b-4 border-slate-800 pb-4">
                        <Zap class="h-5 w-5 text-emerald-500" />
                        Onboarding Simulator
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button 
                            @click="technicianRosterSim = technicianRosterSim === 'Mapped' ? 'Empty' : 'Mapped'"
                            class="rounded-2xl border-4 border-slate-700 bg-slate-800 hover:bg-slate-700 p-3 text-xs font-black uppercase text-slate-300 transition-all"
                        >
                            Toggle Technician Profile
                        </button>
                        <button 
                            @click="voiceAiPromptsSim = voiceAiPromptsSim === 'Programmed' ? 'Default' : 'Programmed'"
                            class="rounded-2xl border-4 border-slate-700 bg-slate-800 hover:bg-slate-700 p-3 text-xs font-black uppercase text-slate-300 transition-all"
                        >
                            Toggle Custom System Prompts
                        </button>
                        <button 
                            @click="resetOnboarding"
                            class="rounded-2xl border-4 border-slate-700 bg-slate-800 hover:bg-slate-700 p-3 text-xs font-black uppercase text-slate-300 transition-all flex items-center justify-center gap-1.5"
                        >
                            <RefreshCw class="h-4.5 w-4.5" /> Reset Matrix
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar Panel (1/3 columns) -->
            <div class="flex flex-col gap-8">
                
                <!-- AI Mascot display widget -->
                <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-4 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                    <h3 class="text-lg font-black text-white flex items-center gap-2 border-b-4 border-slate-800 pb-4">
                        <Sparkles class="h-5 w-5 text-amber-500" />
                        Dispatcher Mascot
                    </h3>

                    <!-- Character container -->
                    <div class="h-[280px]">
                        <DispatcherMascot 
                            :state="mascotState" 
                            :is-speaking="mascotState === 1"
                            :amplitude="mascotState === 1 ? 40 : 0"
                            :skin="activeSkin"
                        />
                    </div>

                    <!-- Trigger lists -->
                    <div class="flex flex-col gap-2.5 text-xs bg-slate-950 p-4 rounded-2xl border-4 border-slate-800 font-bold text-slate-350">
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest pb-1 border-b border-slate-800">
                            Workspace configuration status
                        </div>
                        <div class="flex justify-between items-center py-0.5">
                            <span>Setup Complete (Victory)</span>
                            <span class="rounded bg-emerald-950/80 px-2 py-0.5 text-[10px] font-black text-emerald-400 border border-emerald-800" :class="[mascotState === 2 ? 'ring-2 ring-emerald-500' : '']">
                                Trigger 2
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-0.5">
                            <span>Setup In-Progress (Scanning)</span>
                            <span class="rounded bg-amber-950/80 px-2 py-0.5 text-[10px] font-black text-amber-400 border border-amber-800" :class="[mascotState === 1 ? 'ring-2 ring-amber-500' : '']">
                                Trigger 1
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-0.5">
                            <span>Billing Failure (Error)</span>
                            <span class="rounded bg-rose-950/80 px-2 py-0.5 text-[10px] font-black text-rose-400 border border-rose-800" :class="[mascotState === 3 ? 'ring-2 ring-rose-500' : '']">
                                Trigger 3
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Stripe upgrades billing widget -->
                <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-4 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                    <h3 class="text-lg font-black text-white flex items-center gap-2 border-b-4 border-slate-800 pb-4">
                        <CreditCard class="h-5 w-5 text-emerald-500" />
                        Upgrade Billing Plan
                    </h3>

                    <p class="text-xs text-slate-400 font-bold leading-relaxed">
                        Instantly deploy premium, dedicated dispatch routes by subscribing to a professional platform plan.
                    </p>

                    <div v-if="upgradeMessage" class="rounded-xl border-2 p-3 text-xs font-bold bg-slate-950 border-slate-800 text-amber-400">
                        {{ upgradeMessage }}
                    </div>

                    <div class="flex flex-col gap-3">
                        <button 
                            @click="startCheckout('pro')"
                            :disabled="isUpgrading"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl border-4 border-emerald-700 bg-emerald-500 hover:bg-emerald-400 text-white py-3 text-xs font-black tracking-wider uppercase transition-all active:translate-y-[2px] disabled:opacity-50"
                        >
                            Subscribe to Pro Plan
                        </button>
                        <button 
                            @click="startCheckout('enterprise')"
                            :disabled="isUpgrading"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl border-4 border-emerald-700 bg-emerald-500 hover:bg-emerald-400 text-white py-3 text-xs font-black tracking-wider uppercase transition-all active:translate-y-[2px] disabled:opacity-50"
                        >
                            Subscribe to Enterprise Plan
                        </button>
                        <button 
                            @click="triggerPaymentFailure"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl border-4 border-rose-900 bg-rose-950 hover:bg-rose-900/60 text-rose-400 py-3 text-xs font-black tracking-wider uppercase transition-all active:translate-y-[2px]"
                        >
                            Force Billing Failure
                        </button>
                    </div>
                </div>

            </div>

        </div>

    </div>
</template>

<style scoped>
.bg-card {
    transition: background-color 0.2s, border-color 0.2s;
}
</style>
