<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    CreditCard,
    CheckCircle,
    ExternalLink,
    ShieldAlert,
} from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';

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
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();

        if (data.url) {
            window.location.href = data.url;
        } else {
            errorMsg.value = data.error || 'Failed to generate billing portal link.';
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
        const csrfToken =
            (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement
            )?.content || '';
        const response = await fetch('/api/billing/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ plan: planName }),
        });
        const data = await response.json();

        if (data.url) {
            window.location.href = data.url;
        } else {
            errorMsg.value = data.error || 'Failed to initiate checkout session.';
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
        <div
            class="relative rounded-2xl border-3 border-b-6 border-slate-300 bg-card p-6 dark:border-slate-800"
        >
            <div
                class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
            >
                <div class="space-y-1.5">
                    <div
                        class="text-[10px] font-black tracking-wider text-muted-foreground uppercase"
                    >
                        Current Plan
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="text-2xl font-black text-slate-900 capitalize dark:text-white"
                        >
                            {{ plan }} Plan
                        </span>
                        <span
                            v-if="isSubscribed"
                            class="rounded-md border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-black tracking-wider text-emerald-500 uppercase"
                        >
                            Active
                        </span>
                        <span
                            v-else
                            class="rounded-md border border-amber-500/20 bg-amber-500/10 px-2 py-0.5 text-[10px] font-black tracking-wider text-amber-500 uppercase"
                        >
                            Trial / Free
                        </span>
                    </div>
                    <p class="text-xs font-semibold text-muted-foreground">
                        Multi-tenant business calling limits apply based on your
                        active plan tier.
                    </p>
                </div>

                <div
                    v-if="isSubscribed"
                    class="flex flex-col items-start gap-1.5 sm:items-end"
                >
                    <div
                        class="text-[10px] font-black tracking-wider text-muted-foreground uppercase"
                    >
                        Payment Method
                    </div>
                    <div
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-100 p-2 text-xs font-bold text-foreground dark:border-slate-800 dark:bg-slate-950"
                    >
                        <CreditCard class="h-4 w-4 text-slate-500" />
                        <span class="capitalize"
                            >{{ pmType }} •••• {{ pmLastFour }}</span
                        >
                    </div>
                </div>
            </div>

            <!-- Portal Button -->
            <div
                class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 pt-6 dark:border-slate-800/80"
            >
                <span
                    v-if="errorMsg"
                    class="flex items-center gap-1.5 text-xs font-bold text-rose-600 dark:text-rose-400"
                >
                    <ShieldAlert class="h-4 w-4" /> {{ errorMsg }}
                </span>
                <span v-else></span>

                <button
                    v-if="isSubscribed"
                    @click="handlePortalRedirect"
                    :disabled="loading"
                    class="flex cursor-pointer items-center gap-1.5 rounded-2xl border-2 border-b-6 border-indigo-500 border-indigo-700 bg-indigo-500 px-6 py-3 text-xs font-black tracking-wide text-white uppercase shadow-md transition-all hover:border-indigo-600 hover:bg-indigo-400 active:translate-y-1 active:border-b-2"
                >
                    <ExternalLink class="h-4 w-4" /> Manage Subscription
                </button>
            </div>
        </div>

        <!-- Available Plans -->
        <div class="space-y-4">
            <h3
                class="text-sm font-black tracking-wider text-slate-400 uppercase"
            >
                Available Corporate Plans
            </h3>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Pro Plan -->
                <div
                    class="flex flex-col justify-between rounded-2xl border-3 border-b-6 border-slate-300 bg-card p-6 dark:border-slate-800"
                    :class="{ 'border-b-6 border-emerald-500': plan === 'pro' }"
                >
                    <div class="space-y-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h4
                                    class="text-lg font-black text-slate-900 dark:text-white"
                                >
                                    Pro Plan
                                </h4>
                                <p
                                    class="text-xs font-semibold text-muted-foreground"
                                >
                                    For growing trade teams
                                </p>
                            </div>
                            <div class="text-right">
                                <span
                                    class="text-2xl font-black text-slate-900 dark:text-white"
                                    >$79</span
                                >
                                <span class="text-xs text-muted-foreground"
                                    >/mo</span
                                >
                            </div>
                        </div>

                        <ul
                            class="space-y-2 text-xs font-semibold text-slate-600 dark:text-slate-400"
                        >
                            <li class="flex items-center gap-2">
                                <CheckCircle
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                Up to 1,000 AI voice dispatches/mo
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                1.5-hour automated travel buffer
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                Basic CRM Sync (HubSpot & Salesforce)
                            </li>
                        </ul>
                    </div>

                    <div class="mt-6">
                        <button
                            v-if="plan !== 'pro' && plan !== 'enterprise'"
                            @click="handleCheckout('pro')"
                            :disabled="loading"
                            class="w-full cursor-pointer rounded-2xl border-2 border-b-6 border-emerald-500 border-emerald-700 bg-emerald-500 py-3 text-center text-xs font-black tracking-wide text-white uppercase transition-all hover:border-emerald-600 hover:bg-emerald-400 active:translate-y-1 active:border-b-2"
                        >
                            Subscribe to Pro
                        </button>
                        <div
                            v-else-if="plan === 'pro'"
                            class="py-3 text-center text-xs font-black text-emerald-600 uppercase dark:text-emerald-400"
                        >
                            Your Active Plan
                        </div>
                        <div
                            v-else
                            class="py-3 text-center text-xs text-muted-foreground italic"
                        >
                            Included in Enterprise
                        </div>
                    </div>
                </div>

                <!-- Enterprise Plan -->
                <div
                    class="flex flex-col justify-between rounded-2xl border-3 border-b-6 border-slate-300 bg-card p-6 dark:border-slate-800"
                    :class="{
                        'border-b-6 border-emerald-500': plan === 'enterprise',
                    }"
                >
                    <div class="space-y-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h4
                                    class="text-lg font-black text-slate-900 dark:text-white"
                                >
                                    Enterprise
                                </h4>
                                <p
                                    class="text-xs font-semibold text-muted-foreground"
                                >
                                    For multi-region operations
                                </p>
                            </div>
                            <div class="text-right">
                                <span
                                    class="text-2xl font-black text-slate-900 dark:text-white"
                                    >$199</span
                                >
                                <span class="text-xs text-muted-foreground"
                                    >/mo</span
                                >
                            </div>
                        </div>

                        <ul
                            class="space-y-2 text-xs font-semibold text-slate-600 dark:text-slate-400"
                        >
                            <li class="flex items-center gap-2">
                                <CheckCircle
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                Up to 10,000 AI voice dispatches/mo
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                Real-time Reverb websockets push
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckCircle
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                Premium CRM Sync & Custom Fields
                            </li>
                        </ul>
                    </div>

                    <div class="mt-6">
                        <button
                            v-if="plan !== 'enterprise'"
                            @click="handleCheckout('enterprise')"
                            :disabled="loading"
                            class="w-full cursor-pointer rounded-2xl border-2 border-b-6 border-indigo-500 border-indigo-700 bg-indigo-500 py-3 text-center text-xs font-black tracking-wide text-white uppercase transition-all hover:border-indigo-600 hover:bg-indigo-400 active:translate-y-1 active:border-b-2"
                        >
                            Upgrade to Enterprise
                        </button>
                        <div
                            v-else
                            class="py-3 text-center text-xs font-black text-emerald-600 uppercase dark:text-emerald-400"
                        >
                            Your Active Plan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
