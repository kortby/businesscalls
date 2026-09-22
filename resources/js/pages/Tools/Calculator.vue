<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    Calculator,
    DollarSign,
    TrendingDown,
    Sparkles,
    ShieldCheck,
    CheckCircle2,
    ArrowRight,
    PhoneCall,
    Zap,
    Send,
    HelpCircle,
    Calendar,
    Users,
} from '@lucide/vue';
import PublicHeader from '@/components/PublicHeader.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Badge } from '@/components/ui/badge';
import { register, home } from '@/routes';

// Calculator Input States
const averageTicket = ref<number>(450);
const missedCallsPerWeek = ref<number>(8);
const closeRate = ref<number>(45);

// Computed Financial Metrics
const weeklyLostRevenue = computed(() => {
    return Math.round(
        missedCallsPerWeek.value * (closeRate.value / 100) * averageTicket.value,
    );
});

const monthlyLostRevenue = computed(() => {
    return Math.round(weeklyLostRevenue.value * 4.333);
});

const annualLostRevenue = computed(() => {
    return Math.round(weeklyLostRevenue.value * 52);
});

const annualCostAi = 99 * 12; // $1,188/yr on Starter
const estimatedNetRecovery = computed(() => {
    return Math.max(0, annualLostRevenue.value - annualCostAi);
});

const roiPercentage = computed(() => {
    if (annualCostAi === 0) return 0;
    return Math.round((estimatedNetRecovery.value / annualCostAi) * 100);
});

// Lead capture state
const emailInput = ref('');
const leadSubmitted = ref(false);
const leadLoading = ref(false);

const handleReportSubmit = () => {
    if (!emailInput.value) return;
    leadLoading.value = true;
    setTimeout(() => {
        leadLoading.value = false;
        leadSubmitted.value = true;
    }, 700);
};

// JSON-LD structured data for the calculator tool
const jsonLdData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'WebApplication',
    'name': 'Contractor Missed Call Revenue Loss Calculator',
    'url': 'https://justmascot.com/tools/missed-call-calculator',
    'description':
        'Free interactive calculator for trade contractors (Plumbing, HVAC, Electrical, Roofing) to calculate annual revenue lost to unanswered phone calls.',
    'applicationCategory': 'BusinessApplication',
    'operatingSystem': 'All',
    'offers': {
        '@type': 'Offer',
        'price': '0',
        'priceCurrency': 'USD',
    },
}));
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 selection:bg-emerald-500 selection:text-slate-950 font-sans antialiased">
        <SeoHead
            title="Contractor Missed Call Revenue Loss Calculator | JustMascot"
            description="Calculate how much annual revenue your plumbing, HVAC, or electrical business loses from missed customer calls and unanswered after-hours phone leads."
            keywords="contractor missed call calculator, HVAC lost revenue calculator, plumber phone answering ROI, answering service cost calculator, field service revenue loss"
            :json-ld="jsonLdData"
        />

        <PublicHeader />

        <main>
            <!-- Hero Header -->
            <section class="relative overflow-hidden pt-12 pb-16 md:pt-16 md:pb-24 border-b border-slate-800">
                <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 -z-10 h-[450px] w-[650px] rounded-full bg-emerald-500/10 blur-[130px]"></div>

                <div class="container mx-auto px-4 sm:px-6">
                    <!-- Breadcrumbs -->
                    <nav class="mb-6 flex items-center gap-2 text-xs text-slate-400 font-mono">
                        <Link :href="home()" class="hover:text-emerald-400 transition-colors">Home</Link>
                        <span>/</span>
                        <span class="text-slate-500">Free Tools</span>
                        <span>/</span>
                        <span class="text-emerald-400 font-semibold">Missed Call Revenue Calculator</span>
                    </nav>

                    <div class="mx-auto max-w-3xl text-center space-y-4">
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-emerald-400 backdrop-blur-md">
                            <Calculator class="h-4 w-4 text-emerald-400" />
                            <span>Interactive Trade Financial Tool</span>
                        </div>

                        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl md:text-5xl text-white leading-tight">
                            Contractor Missed Call <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-indigo-400 bg-clip-text text-transparent">Revenue Loss Calculator</span>
                        </h1>

                        <p class="text-base sm:text-lg text-slate-300 leading-relaxed">
                            Find out exactly how much revenue your contracting business leaves on the table from missed calls, evening voicemails, and unhandled weekend emergencies.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Interactive Calculator Body -->
            <section class="py-12 md:py-20 bg-slate-950 border-b border-slate-800">
                <div class="container mx-auto px-4 sm:px-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                        <!-- Left Side: Interactive Sliders -->
                        <div class="lg:col-span-6 rounded-3xl border border-slate-800 bg-slate-900/80 p-6 sm:p-8 shadow-2xl backdrop-blur-xl space-y-8">
                            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                                    <Zap class="h-5 w-5 text-emerald-400" />
                                    <span>Adjust Your Business Numbers</span>
                                </h2>
                                <Badge variant="outline" class="border-slate-700 text-xs text-slate-400">
                                    Live Calculation
                                </Badge>
                            </div>

                            <!-- Slider 1: Average Ticket Size -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="text-sm font-semibold text-slate-200">
                                        Average Job / Ticket Revenue
                                    </label>
                                    <span class="text-lg font-mono font-extrabold text-emerald-400">
                                        ${{ averageTicket.toLocaleString() }}
                                    </span>
                                </div>
                                <input
                                    v-model.number="averageTicket"
                                    type="range"
                                    min="100"
                                    max="5000"
                                    step="50"
                                    class="w-full h-2 rounded-lg bg-slate-700 accent-emerald-400 cursor-pointer"
                                />
                                <div class="flex justify-between text-[11px] text-slate-500 font-mono">
                                    <span>$100 (Drain Snaking)</span>
                                    <span>$1,500 (Water Heater)</span>
                                    <span>$5,000+ (AC/Roof)</span>
                                </div>
                            </div>

                            <!-- Slider 2: Missed Calls per Week -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="text-sm font-semibold text-slate-200">
                                        Estimated Missed Calls Per Week
                                    </label>
                                    <span class="text-lg font-mono font-extrabold text-amber-400">
                                        {{ missedCallsPerWeek }} calls/wk
                                    </span>
                                </div>
                                <input
                                    v-model.number="missedCallsPerWeek"
                                    type="range"
                                    min="1"
                                    max="50"
                                    step="1"
                                    class="w-full h-2 rounded-lg bg-slate-700 accent-amber-400 cursor-pointer"
                                />
                                <div class="flex justify-between text-[11px] text-slate-500 font-mono">
                                    <span>1 call/wk</span>
                                    <span>8 calls/wk (Avg)</span>
                                    <span>50 calls/wk (High)</span>
                                </div>
                            </div>

                            <!-- Slider 3: Lead Close Rate -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="text-sm font-semibold text-slate-200">
                                        Average Lead Booking / Close Rate
                                    </label>
                                    <span class="text-lg font-mono font-extrabold text-sky-400">
                                        {{ closeRate }}%
                                    </span>
                                </div>
                                <input
                                    v-model.number="closeRate"
                                    type="range"
                                    min="10"
                                    max="90"
                                    step="5"
                                    class="w-full h-2 rounded-lg bg-slate-700 accent-sky-400 cursor-pointer"
                                />
                                <div class="flex justify-between text-[11px] text-slate-500 font-mono">
                                    <span>10%</span>
                                    <span>45% (Standard)</span>
                                    <span>90%</span>
                                </div>
                            </div>

                            <!-- Free Lead Magnet Export -->
                            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-950/20 p-5 mt-6 space-y-3">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-400">
                                    Email Full Financial Audit & ROI Breakdown
                                </h3>
                                <p class="text-xs text-slate-400">
                                    Get an executive PDF showing how your revenue compares to industry benchmarks.
                                </p>

                                <div v-if="!leadSubmitted" class="flex flex-col sm:flex-row gap-2">
                                    <input
                                        v-model="emailInput"
                                        type="email"
                                        placeholder="Enter your work email..."
                                        class="h-10 flex-1 rounded-lg border border-slate-700 bg-slate-900 px-3 text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-hidden"
                                    />
                                    <button
                                        @click="handleReportSubmit"
                                        :disabled="leadLoading"
                                        class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg bg-emerald-500 px-4 text-xs font-bold text-slate-950 hover:bg-emerald-400 cursor-pointer disabled:opacity-50"
                                    >
                                        <Send class="h-3.5 w-3.5" />
                                        <span>{{ leadLoading ? 'Sending...' : 'Get Audit Report' }}</span>
                                    </button>
                                </div>
                                <div v-else class="flex items-center gap-2 text-emerald-400 text-xs font-semibold">
                                    <CheckCircle2 class="h-4 w-4" />
                                    <span>Audit report sent! Check your inbox shortly.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side: Revenue Leak Results HUD -->
                        <div class="lg:col-span-6 space-y-6">
                            <!-- High-Impact Lost Revenue Highlight Card -->
                            <div class="rounded-3xl border border-rose-500/30 bg-gradient-to-br from-rose-950/40 via-slate-900/90 to-slate-950 p-6 sm:p-8 shadow-2xl space-y-6">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/10 px-3 py-1 text-xs font-bold text-rose-400 border border-rose-500/20">
                                        <TrendingDown class="h-4 w-4" />
                                        <span>Estimated Revenue Leak</span>
                                    </span>
                                    <span class="text-xs font-mono text-slate-400">Annual Projection</span>
                                </div>

                                <div>
                                    <p class="text-xs uppercase tracking-wider text-slate-400 font-bold">You are losing approximately</p>
                                    <p class="text-4xl sm:text-5xl md:text-6xl font-black text-rose-400 font-mono tracking-tight mt-1">
                                        ${{ annualLostRevenue.toLocaleString() }}
                                        <span class="text-lg sm:text-xl font-normal text-slate-400">/year</span>
                                    </p>
                                </div>

                                <!-- Breakdown grid -->
                                <div class="grid grid-cols-2 gap-4 border-t border-slate-800 pt-6">
                                    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
                                        <p class="text-xs text-slate-400">Weekly Revenue Lost</p>
                                        <p class="text-xl font-bold font-mono text-white mt-1">
                                            ${{ weeklyLostRevenue.toLocaleString() }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
                                        <p class="text-xs text-slate-400">Monthly Revenue Lost</p>
                                        <p class="text-xl font-bold font-mono text-white mt-1">
                                            ${{ monthlyLostRevenue.toLocaleString() }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- ROI with JustMascot AI Recovery Card -->
                            <div class="rounded-3xl border border-emerald-500/30 bg-gradient-to-br from-emerald-950/40 via-slate-900/90 to-slate-950 p-6 sm:p-8 shadow-2xl space-y-6">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-400 border border-emerald-500/20">
                                        <Sparkles class="h-4 w-4" />
                                        <span>Net Value With JustMascot AI</span>
                                    </span>
                                    <span class="text-xs font-mono text-emerald-400 font-bold">
                                        {{ roiPercentage }}% ROI
                                    </span>
                                </div>

                                <div>
                                    <p class="text-xs uppercase tracking-wider text-slate-400 font-bold">Estimated Net Annual Recovery</p>
                                    <p class="text-4xl sm:text-5xl font-black text-emerald-400 font-mono tracking-tight mt-1">
                                        +${{ estimatedNetRecovery.toLocaleString() }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-2">
                                        Recovers all missed calls for just $99/month ($1,188/yr) with 24/7 sub-second dispatching.
                                    </p>
                                </div>

                                <div class="pt-2">
                                    <Link
                                        :href="register()"
                                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 px-6 py-4 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all hover:bg-emerald-400 active:scale-95"
                                    >
                                        <span>Start Recovering Lost Calls Today</span>
                                        <ArrowRight class="h-4 w-4" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Three-Way Cost Comparison Breakdown Table -->
            <section class="py-20 bg-slate-900/30 border-b border-slate-800">
                <div class="container mx-auto px-4 sm:px-6 max-w-5xl">
                    <div class="text-center mb-12 space-y-3">
                        <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                            How AI Voice Dispatch Compares To Other Options
                        </h2>
                        <p class="text-slate-400 text-sm sm:text-base">
                            Why trade businesses are replacing legacy call centers with autonomous voice AI.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs sm:text-sm border-collapse rounded-2xl overflow-hidden">
                            <thead>
                                <tr class="border-b border-slate-800 bg-slate-900">
                                    <th class="p-4 sm:p-5 font-bold text-slate-400">Feature / Cost</th>
                                    <th class="p-4 sm:p-5 font-bold text-slate-400">In-House Receptionist</th>
                                    <th class="p-4 sm:p-5 font-bold text-slate-400">Call Answering Service</th>
                                    <th class="p-4 sm:p-5 font-bold text-emerald-400 bg-emerald-950/40">JustMascot Voice AI</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 bg-slate-950/60">
                                <tr>
                                    <td class="p-4 sm:p-5 font-bold text-white">Monthly Cost</td>
                                    <td class="p-4 sm:p-5 text-slate-300">$3,500 - $4,500/mo + taxes</td>
                                    <td class="p-4 sm:p-5 text-slate-300">$400 - $900/mo ($2.50/min)</td>
                                    <td class="p-4 sm:p-5 font-bold text-emerald-400 bg-emerald-950/20">From $99/mo flat</td>
                                </tr>
                                <tr>
                                    <td class="p-4 sm:p-5 font-bold text-white">Coverage Hours</td>
                                    <td class="p-4 sm:p-5 text-slate-400">8 AM - 5 PM (M-F only)</td>
                                    <td class="p-4 sm:p-5 text-slate-300">24/7 (High night surcharges)</td>
                                    <td class="p-4 sm:p-5 font-bold text-emerald-400 bg-emerald-950/20">24/7/365 Included</td>
                                </tr>
                                <tr>
                                    <td class="p-4 sm:p-5 font-bold text-white">Caller Hold Time</td>
                                    <td class="p-4 sm:p-5 text-slate-400">Busy on second line</td>
                                    <td class="p-4 sm:p-5 text-slate-400">30 - 90 seconds hold time</td>
                                    <td class="p-4 sm:p-5 font-bold text-emerald-400 bg-emerald-950/20">&lt; 1 sec (Zero wait)</td>
                                </tr>
                                <tr>
                                    <td class="p-4 sm:p-5 font-bold text-white">Skill & License Routing</td>
                                    <td class="p-4 sm:p-5 text-slate-400">Manual lookup errors</td>
                                    <td class="p-4 sm:p-5 text-rose-400">None (Takes message only)</td>
                                    <td class="p-4 sm:p-5 font-bold text-emerald-400 bg-emerald-950/20">Automated (EPA, Master)</td>
                                </tr>
                                <tr>
                                    <td class="p-4 sm:p-5 font-bold text-white">Drive Time Buffer Protection</td>
                                    <td class="p-4 sm:p-5 text-slate-400">Frequent scheduling conflicts</td>
                                    <td class="p-4 sm:p-5 text-rose-400">None</td>
                                    <td class="p-4 sm:p-5 font-bold text-emerald-400 bg-emerald-950/20">Enforces 1.5h Drive Buffers</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
