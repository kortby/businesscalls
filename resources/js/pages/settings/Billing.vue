<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { CreditCard, CheckCircle, ExternalLink, ShieldAlert } from '@lucide/vue';

const props = defineProps<{
    tenant: {
        id: number;
        slug: string;
        name: string;
        plan: string;
        settings: Record<string, any>;
    };
    stripeKey: string;
    pmType: string | null;
    pmLastFour: string | null;
    isSubscribed: boolean;
    plan: string;
}>();

const loading = ref(false);
const errorMsg = ref('');

const handlePortalRedirect = async () => {
    loading.value = true;
    errorMsg.value = '';
    try {
        const response = await fetch('/api/billing/portal', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json();
        if (data.url) {
            window.location.href = data.url;
        } else {
            errorMsg.value = 'Failed to generate billing portal link.';
            loading.value = false;
        }
    } catch (e) {
        errorMsg.value = 'An error occurred. Please try again.';
        loading.value = false;
    }
};

const handleCheckout = async (planName: 'pro' | 'enterprise') => {
    loading.value = true;
    errorMsg.value = '';
    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const response = await fetch('/api/billing/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ plan: planName })
        });
        const data = await response.json();
        if (data.url) {
            window.location.href = data.url;
        } else {
            errorMsg.value = 'Failed to initiate checkout session.';
            loading.value = false;
        }
    } catch (e) {
        errorMsg.value = 'An error occurred. Please try again.';
        loading.value = false;
    }
};
</script>

<template>
    <Head title="Billing Settings" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Billing & Subscriptions"
            description="Manage your business subscription and payment details"
        />

        <!-- Active Subscription Summary Card -->
        <div class="border-3 border-b-6 border-slate-300 dark:border-slate-800 rounded-2xl bg-card p-6 relative">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1.5">
                    <div class="text-[10px] font-black uppercase tracking-wider text-muted-foreground">Current Plan</div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-black text-slate-900 dark:text-white capitalize">
                            {{ plan }} Plan
                        </span>
                        <span v-if="isSubscribed" class="bg-emerald-500/10 text-emerald-500 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md border border-emerald-500/20">
                            Active
                        </span>
                        <span v-else class="bg-amber-500/10 text-amber-500 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md border border-amber-500/20">
                            Trial / Free
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground font-semibold">
                        Multi-tenant business calling limits apply based on your active plan tier.
                    </p>
                </div>

                <div v-if="isSubscribed" class="flex flex-col items-start sm:items-end gap-1.5">
                    <div class="text-[10px] font-black uppercase tracking-wider text-muted-foreground">Payment Method</div>
                    <div class="flex items-center gap-2 text-xs font-bold text-foreground bg-slate-100 dark:bg-slate-950 p-2 rounded-xl border border-slate-200 dark:border-slate-800">
                        <CreditCard class="h-4 w-4 text-slate-500" />
                        <span class="capitalize">{{ pmType }} •••• {{ pmLastFour }}</span>
                    </div>
                </div>
            </div>

            <!-- Portal Button -->
            <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800/80 flex items-center justify-between flex-wrap gap-4">
                <span v-if="errorMsg" class="text-rose-600 dark:text-rose-400 font-bold text-xs flex items-center gap-1.5">
                    <ShieldAlert class="h-4 w-4" /> {{ errorMsg }}
                </span>
                <span v-else></span>

                <button
                    v-if="isSubscribed"
                    @click="handlePortalRedirect"
                    :disabled="loading"
                    class="bg-indigo-500 hover:bg-indigo-400 text-white font-black tracking-wide uppercase px-6 py-3 rounded-2xl border-2 border-indigo-500 border-b-6 border-indigo-700 hover:border-indigo-600 active:border-b-2 active:translate-y-1 transition-all cursor-pointer shadow-md text-xs flex items-center gap-1.5"
                >
                    <ExternalLink class="h-4 w-4" /> Manage Subscription
                </button>
            </div>
        </div>

        <!-- Available Plans -->
        <div class="space-y-4">
            <h3 class="text-sm font-black uppercase tracking-wider text-slate-400">Available Corporate Plans</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Pro Plan -->
                <div class="border-3 border-b-6 border-slate-300 dark:border-slate-800 rounded-2xl bg-card p-6 flex flex-col justify-between"
                     :class="{ 'border-emerald-500 border-b-6': plan === 'pro' }">
                    <div class="space-y-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-black text-slate-900 dark:text-white text-lg">Pro Plan</h4>
                                <p class="text-xs text-muted-foreground font-semibold">For growing trade teams</p>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-black text-slate-900 dark:text-white">$79</span>
                                <span class="text-xs text-muted-foreground">/mo</span>
                            </div>
                        </div>

                        <ul class="space-y-2 text-xs font-semibold text-slate-600 dark:text-slate-400">
                            <li class="flex items-center gap-2">
                                <CheckCircle class="h-4 w-4 text-emerald-500 shrink-0" />
                                Up to 1,000 AI voice dispatches/mo
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle class="h-4 w-4 text-emerald-500 shrink-0" />
                                1.5-hour automated travel buffer
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle class="h-4 w-4 text-emerald-500 shrink-0" />
                                Basic CRM Sync (HubSpot & Salesforce)
                            </li>
                        </ul>
                    </div>

                    <div class="mt-6">
                        <button
                            v-if="plan !== 'pro' && plan !== 'enterprise'"
                            @click="handleCheckout('pro')"
                            :disabled="loading"
                            class="w-full text-center bg-emerald-500 hover:bg-emerald-400 text-white font-black tracking-wide uppercase py-3 rounded-2xl border-2 border-emerald-500 border-b-6 border-emerald-700 hover:border-emerald-600 active:border-b-2 active:translate-y-1 transition-all cursor-pointer text-xs"
                        >
                            Subscribe to Pro
                        </button>
                        <div v-else-if="plan === 'pro'" class="text-center text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase py-3">
                            Your Active Plan
                        </div>
                        <div v-else class="text-center text-xs text-muted-foreground italic py-3">
                            Included in Enterprise
                        </div>
                    </div>
                </div>

                <!-- Enterprise Plan -->
                <div class="border-3 border-b-6 border-slate-300 dark:border-slate-800 rounded-2xl bg-card p-6 flex flex-col justify-between"
                     :class="{ 'border-emerald-500 border-b-6': plan === 'enterprise' }">
                    <div class="space-y-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-black text-slate-900 dark:text-white text-lg">Enterprise</h4>
                                <p class="text-xs text-muted-foreground font-semibold">For multi-region operations</p>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-black text-slate-900 dark:text-white">$199</span>
                                <span class="text-xs text-muted-foreground">/mo</span>
                            </div>
                        </div>

                        <ul class="space-y-2 text-xs font-semibold text-slate-600 dark:text-slate-400">
                            <li class="flex items-center gap-2">
                                <CheckCircle class="h-4 w-4 text-emerald-500 shrink-0" />
                                Up to 10,000 AI voice dispatches/mo
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle class="h-4 w-4 text-emerald-500 shrink-0" />
                                Real-time Reverb websockets push
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle class="h-4 w-4 text-emerald-500 shrink-0" />
                                Premium CRM Sync & Custom Fields
                            </li>
                        </ul>
                    </div>

                    <div class="mt-6">
                        <button
                            v-if="plan !== 'enterprise'"
                            @click="handleCheckout('enterprise')"
                            :disabled="loading"
                            class="w-full text-center bg-indigo-500 hover:bg-indigo-400 text-white font-black tracking-wide uppercase py-3 rounded-2xl border-2 border-indigo-500 border-b-6 border-indigo-700 hover:border-indigo-600 active:border-b-2 active:translate-y-1 transition-all cursor-pointer text-xs"
                        >
                            Upgrade to Enterprise
                        </button>
                        <div v-else class="text-center text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase py-3">
                            Your Active Plan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
