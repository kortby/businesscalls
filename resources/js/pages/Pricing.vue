<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Check,
    X,
    HelpCircle,
    ChevronDown,
    Calculator,
    Sparkles,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import SeoHead from '@/components/SeoHead.vue';
import {
    dashboard,
    login,
    register,
    home,
    about,
    pricing,
    contact,
    privacy,
    terms,
} from '@/routes';

// ROI Calculator State
const missedCalls = ref(15);
const averageTicket = ref(250);

const lostRevenue = computed(() => missedCalls.value * averageTicket.value);
const netSavings = computed(() => Math.max(0, lostRevenue.value - 99)); // Pro plan is $99

// FAQ State
const activeFaq = ref<number | null>(null);
const toggleFaq = (index: number) => {
    activeFaq.value = activeFaq.value === index ? null : index;
};

const faqs = [
    {
        question: 'Are there any hidden setup fees or contracts?',
        answer: 'No. There are no signup or setup fees on any of our standard plans. JustMascot operates on a flexible month-to-month subscription, and you can cancel anytime.',
    },
    {
        question: 'Can I change my subscription tier later?',
        answer: 'Yes. You can upgrade, downgrade, or cancel your subscription at any time directly through your billing portal. Upgrades are applied immediately on a prorated basis.',
    },
    {
        question: 'What happens if we exceed our plan’s call limits?',
        answer: 'We do not hard-block your AI receptionist if you exceed temporary call limits. We apply a soft buffer and will notify you if it is time to transition to a higher plan.',
    },
    {
        question: 'Do you offer a free trial?',
        answer: 'Yes! Every new account includes a 14-day free trial on our Pro plan. No credit card is required to sign up, allowing you to test scheduling with your technicians risk-free.',
    },
];
</script>

<template>
    <SeoHead
        title="Contractor Pricing Plans | AI Answering & Dispatch"
        description="Explore affordable pricing tiers for plumbing, HVAC, and electrical contractors. Boost your bookings, eliminate dispatch overhead, and pay only for what you need."
        keywords="contractor answering service cost, AI receptionist pricing, HVAC dispatch software price, plumber booking app subscription"
    />

    <div
        class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-primary selection:text-primary-foreground dark:bg-slate-950 dark:text-slate-100"
    >
        <!-- Header -->
        <PublicHeader activePage="pricing" />

        <!-- Pricing Intro Hero -->
        <section
            class="relative overflow-hidden border-b bg-gradient-to-b from-indigo-500/10 via-transparent to-transparent py-16 md:py-24"
        >
            <div
                class="absolute inset-0 -z-20 bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)] bg-[size:14px_24px]"
            ></div>
            <div
                class="container mx-auto max-w-[800px] space-y-4 px-4 text-center sm:px-6"
            >
                <h1
                    class="text-4xl font-extrabold tracking-tight text-foreground sm:text-5xl"
                >
                    Affordable, Flexible Answering & Dispatch Plans
                </h1>
                <p class="text-lg leading-relaxed text-muted-foreground">
                    Maximize your home service business's ROI. Save hundreds of
                    dollars monthly compared to traditional call centers and
                    dedicated dispatcher hires. Select the plan that fits your
                    technician team size and start capturing every single
                    service lead.
                </p>
            </div>
        </section>

        <!-- Pricing Cards Section -->
        <section class="border-b bg-card py-16 dark:bg-slate-900/10">
            <div
                class="container mx-auto grid max-w-[1000px] grid-cols-1 gap-8 px-4 sm:px-6 md:grid-cols-3"
            >
                <!-- Starter Plan -->
                <div
                    class="flex flex-col justify-between rounded-3xl border-4 border-b-8 border-slate-300 bg-background p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:border-slate-800"
                >
                    <div>
                        <span
                            class="text-xs font-black tracking-widest text-slate-500 uppercase"
                            >Starter</span
                        >
                        <h3 class="mt-2 text-3xl font-black">
                            $49<span
                                class="text-xs font-semibold text-muted-foreground"
                                >/month</span
                            >
                        </h3>
                        <p
                            class="mt-2 text-xs font-medium text-muted-foreground"
                        >
                            Perfect for small, single-technician setups.
                        </p>
                        <hr
                            class="my-4 border-slate-200 dark:border-slate-800"
                        />
                        <ul
                            class="space-y-2.5 text-xs font-semibold text-muted-foreground"
                        >
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                1 Active Technician
                            </li>
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                Webhook Telemetry
                            </li>
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                Basic AI Prompts
                            </li>
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                Logical Tenant Shielding
                            </li>
                        </ul>
                    </div>
                    <Link
                        :href="register()"
                        class="mt-8 rounded-xl border-2 border-b-6 border-slate-700 bg-slate-500 py-3 text-center text-xs font-black text-white uppercase shadow-md hover:bg-slate-400 active:mt-1 active:border-b-0"
                    >
                        Get Started
                    </Link>
                </div>

                <!-- Pro Plan (Highlighted) -->
                <div
                    class="relative flex -translate-y-2 transform flex-col justify-between rounded-3xl border-4 border-b-12 border-indigo-400 bg-background p-6 shadow-xl transition-all duration-300 hover:-translate-y-5 hover:shadow-2xl md:-translate-y-4"
                >
                    <div
                        class="animate-pulse-slow absolute -top-3 right-6 rounded-full border border-indigo-600 bg-indigo-500 px-2 py-0.5 text-[9px] font-black tracking-wider text-white uppercase shadow-xs"
                    >
                        Popular
                    </div>
                    <div>
                        <span
                            class="text-xs font-black tracking-widest text-indigo-500 uppercase"
                            >Professional</span
                        >
                        <h3 class="mt-2 text-3xl font-black">
                            $99<span
                                class="text-xs font-semibold text-muted-foreground"
                                >/month</span
                            >
                        </h3>
                        <p
                            class="mt-2 text-xs font-medium text-muted-foreground"
                        >
                            Ideal for growing teams with custom scheduling
                            rules.
                        </p>
                        <hr
                            class="my-4 border-indigo-100 dark:border-slate-800"
                        />
                        <ul
                            class="space-y-2.5 text-xs font-semibold text-muted-foreground"
                        >
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-indigo-500"
                                />
                                Up to 5 Technicians
                            </li>
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-indigo-500"
                                />
                                1.5-Hour Buffer Enforced
                            </li>
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-indigo-500"
                                />
                                Custom System Prompt Override
                            </li>
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-indigo-500"
                                />
                                Real-time Mascot Broadcasts
                            </li>
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-indigo-500"
                                />
                                Priority Telemetry Routing
                            </li>
                        </ul>
                    </div>
                    <Link
                        :href="register()"
                        class="mt-8 rounded-xl border-2 border-b-6 border-indigo-700 bg-indigo-500 py-3 text-center text-xs font-black text-white uppercase shadow-md hover:bg-indigo-400 active:mt-1 active:border-b-0"
                    >
                        Try 14 Days Free
                    </Link>
                </div>

                <!-- Enterprise Plan -->
                <div
                    class="flex flex-col justify-between rounded-3xl border-4 border-b-8 border-slate-300 bg-background p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:border-slate-800"
                >
                    <div>
                        <span
                            class="text-xs font-black tracking-widest text-slate-500 uppercase"
                            >Enterprise</span
                        >
                        <h3 class="mt-2 text-3xl font-black">
                            $199<span
                                class="text-xs font-semibold text-muted-foreground"
                                >/month</span
                            >
                        </h3>
                        <p
                            class="mt-2 text-xs font-medium text-muted-foreground"
                        >
                            Custom multi-tenant setup for busy companies.
                        </p>
                        <hr
                            class="my-4 border-slate-200 dark:border-slate-800"
                        />
                        <ul
                            class="space-y-2.5 text-xs font-semibold text-muted-foreground"
                        >
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                Unlimited Technicians
                            </li>
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                Custom HMAC Key Integration
                            </li>
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                API Access & Webhook Diagnostics
                            </li>
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                Dedicated Account Manager
                            </li>
                        </ul>
                    </div>
                    <Link
                        :href="contact()"
                        class="mt-8 rounded-xl border-2 border-b-6 border-slate-700 bg-slate-500 py-3 text-center text-xs font-black text-white uppercase shadow-md hover:bg-slate-400 active:mt-1 active:border-b-0"
                    >
                        Contact Sales
                    </Link>
                </div>
            </div>
        </section>

        <!-- ROI Interactive Calculator (Premium Feature) -->
        <section class="border-b bg-slate-100/50 py-20 dark:bg-slate-900/5">
            <div class="container mx-auto max-w-[800px] px-4 sm:px-6">
                <div class="mb-12 text-center">
                    <div
                        class="mx-auto inline-flex items-center gap-1.5 rounded-full border bg-background px-3 py-1 text-xs font-semibold text-muted-foreground shadow-xs"
                    >
                        <Calculator class="h-3.5 w-3.5 text-indigo-500" />
                        <span>Interactive Value Estimator</span>
                    </div>
                    <h2
                        class="mt-4 text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl"
                    >
                        Calculate the Cost of Missed Calls
                    </h2>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        Home service leads are highly time-sensitive. If you
                        miss a call, customers dial the next contractor. Drag
                        the sliders to see what you could save with
                        JustMascot.
                    </p>
                </div>

                <!-- Calculator Container -->
                <div
                    class="rounded-3xl border-4 border-b-8 border-slate-200 bg-background p-8 dark:border-slate-800"
                >
                    <div class="space-y-6">
                        <!-- Slider 1 -->
                        <div class="space-y-2">
                            <div
                                class="flex items-center justify-between font-bold"
                            >
                                <label class="text-sm"
                                    >Estimated Missed Calls (per month)</label
                                >
                                <span class="text-indigo-500"
                                    >{{ missedCalls }} calls</span
                                >
                            </div>
                            <input
                                type="range"
                                min="5"
                                max="100"
                                step="5"
                                v-model.number="missedCalls"
                                class="h-2 w-full cursor-pointer rounded-lg bg-slate-200 accent-indigo-500 dark:bg-slate-700"
                            />
                        </div>

                        <!-- Slider 2 -->
                        <div class="space-y-2">
                            <div
                                class="flex items-center justify-between font-bold"
                            >
                                <label class="text-sm"
                                    >Average Job Ticket Value ($)</label
                                >
                                <span class="text-indigo-500"
                                    >${{ averageTicket }}</span
                                >
                            </div>
                            <input
                                type="range"
                                min="50"
                                max="1000"
                                step="50"
                                v-model.number="averageTicket"
                                class="h-2 w-full cursor-pointer rounded-lg bg-slate-200 accent-indigo-500 dark:bg-slate-700"
                            />
                        </div>

                        <hr class="my-6 border-t" />

                        <!-- Outputs -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div
                                class="rounded-2xl border-2 bg-slate-50/50 p-4 text-center dark:bg-slate-900/10"
                            >
                                <p
                                    class="text-xs font-bold text-muted-foreground uppercase"
                                >
                                    Lost Monthly Revenue
                                </p>
                                <p
                                    class="mt-2 text-3xl font-extrabold text-rose-500"
                                >
                                    ${{ lostRevenue.toLocaleString() }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border-4 border-emerald-500/20 bg-emerald-500/[0.03] p-4 text-center"
                            >
                                <p
                                    class="text-xs font-bold text-emerald-600 uppercase dark:text-emerald-400"
                                >
                                    Net Monthly Savings
                                </p>
                                <p
                                    class="mt-2 text-3xl font-extrabold text-emerald-500"
                                >
                                    ${{ netSavings.toLocaleString() }}
                                </p>
                                <p
                                    class="mt-1 text-[9px] text-muted-foreground"
                                >
                                    Based on Pro Plan ($99/mo)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Comparison Table -->
        <section class="border-b py-20 md:py-28">
            <div class="container mx-auto max-w-[900px] px-4 sm:px-6">
                <div class="mb-16 text-center">
                    <h2
                        class="text-xs font-black tracking-widest text-primary uppercase"
                    >
                        Features Grid
                    </h2>
                    <h3
                        class="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                    >
                        Compare Answering & Dispatch Capabilities
                    </h3>
                </div>

                <!-- Table Container -->
                <div
                    class="overflow-x-auto rounded-2xl border border-slate-200 bg-background shadow-xs dark:border-slate-800"
                >
                    <table
                        class="w-full border-collapse text-left text-xs font-semibold text-slate-700 dark:text-slate-300"
                    >
                        <thead>
                            <tr
                                class="border-b bg-slate-100/50 font-extrabold text-slate-900 dark:bg-slate-900/20 dark:text-slate-100"
                            >
                                <th class="w-1/3 p-4">Feature Capabilities</th>
                                <th class="p-4 text-center">Starter</th>
                                <th class="p-4 text-center">Pro</th>
                                <th class="p-4 text-center">Enterprise</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Row 1 -->
                            <tr class="border-b">
                                <td class="p-4 font-bold text-foreground">
                                    AI Voice Answering
                                </td>
                                <td class="p-4 text-center text-emerald-500">
                                    <Check class="mx-auto h-4 w-4" />
                                </td>
                                <td class="p-4 text-center text-emerald-500">
                                    <Check class="mx-auto h-4 w-4" />
                                </td>
                                <td class="p-4 text-center text-emerald-500">
                                    <Check class="mx-auto h-4 w-4" />
                                </td>
                            </tr>
                            <!-- Row 2 -->
                            <tr
                                class="border-b bg-slate-50/20 dark:bg-slate-900/5"
                            >
                                <td class="p-4 font-bold text-foreground">
                                    Active Shift Matching
                                </td>
                                <td class="p-4 text-center text-emerald-500">
                                    <Check class="mx-auto h-4 w-4" />
                                </td>
                                <td class="p-4 text-center text-emerald-500">
                                    <Check class="mx-auto h-4 w-4" />
                                </td>
                                <td class="p-4 text-center text-emerald-500">
                                    <Check class="mx-auto h-4 w-4" />
                                </td>
                            </tr>
                            <!-- Row 3 -->
                            <tr class="border-b">
                                <td class="p-4 font-bold text-foreground">
                                    Active Technicians
                                </td>
                                <td class="p-4 text-center text-foreground">
                                    1
                                </td>
                                <td class="p-4 text-center text-foreground">
                                    Up to 5
                                </td>
                                <td class="p-4 text-center text-foreground">
                                    Unlimited
                                </td>
                            </tr>
                            <!-- Row 4 -->
                            <tr
                                class="border-b bg-slate-50/20 dark:bg-slate-900/5"
                            >
                                <td class="p-4 font-bold text-foreground">
                                    1.5h Travel Time Buffer
                                </td>
                                <td class="p-4 text-center text-rose-500">
                                    <X class="mx-auto h-4 w-4" />
                                </td>
                                <td class="p-4 text-center text-emerald-500">
                                    <Check class="mx-auto h-4 w-4" />
                                </td>
                                <td class="p-4 text-center text-emerald-500">
                                    <Check class="mx-auto h-4 w-4" />
                                </td>
                            </tr>
                            <!-- Row 5 -->
                            <tr class="border-b">
                                <td class="p-4 font-bold text-foreground">
                                    Logical Db Isolation
                                </td>
                                <td class="p-4 text-center text-emerald-500">
                                    <Check class="mx-auto h-4 w-4" />
                                </td>
                                <td class="p-4 text-center text-emerald-500">
                                    <Check class="mx-auto h-4 w-4" />
                                </td>
                                <td class="p-4 text-center text-emerald-500">
                                    <Check class="mx-auto h-4 w-4" />
                                </td>
                            </tr>
                            <!-- Row 6 -->
                            <tr
                                class="border-b bg-slate-50/20 dark:bg-slate-900/5"
                            >
                                <td class="p-4 font-bold text-foreground">
                                    HMAC Webhook Keys
                                </td>
                                <td class="p-4 text-center text-rose-500">
                                    <X class="mx-auto h-4 w-4" />
                                </td>
                                <td class="p-4 text-center text-rose-500">
                                    <X class="mx-auto h-4 w-4" />
                                </td>
                                <td class="p-4 text-center text-emerald-500">
                                    <Check class="mx-auto h-4 w-4" />
                                </td>
                            </tr>
                            <!-- Row 7 -->
                            <tr class="border-b">
                                <td class="p-4 font-bold text-foreground">
                                    CRM Sync Integrations
                                </td>
                                <td class="p-4 text-center text-rose-500">
                                    <X class="mx-auto h-4 w-4" />
                                </td>
                                <td class="p-4 text-center text-rose-500">
                                    <X class="mx-auto h-4 w-4" />
                                </td>
                                <td class="p-4 text-center text-emerald-500">
                                    <Check class="mx-auto h-4 w-4" />
                                </td>
                            </tr>
                            <!-- Row 8 -->
                            <tr class="bg-slate-50/20 dark:bg-slate-900/5">
                                <td class="p-4 font-bold text-foreground">
                                    Support Level
                                </td>
                                <td class="p-4 text-center">Standard Email</td>
                                <td class="p-4 text-center">Priority Email</td>
                                <td
                                    class="p-4 text-center font-extrabold text-indigo-500"
                                >
                                    24/7 Dedicated Manager
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- FAQs Section -->
        <section class="border-b bg-card py-20 md:py-28 dark:bg-slate-900/10">
            <div class="container mx-auto max-w-[800px] px-4 sm:px-6">
                <div class="mb-16 text-center">
                    <h2
                        class="text-xs font-black tracking-widest text-primary uppercase"
                    >
                        Questions & Answers
                    </h2>
                    <h3
                        class="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                    >
                        Frequently Asked Billing Questions
                    </h3>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(faq, index) in faqs"
                        :key="index"
                        class="group overflow-hidden rounded-2xl border bg-background transition-all duration-300 hover:border-indigo-500/20"
                    >
                        <button
                            @click="toggleFaq(index)"
                            class="flex w-full cursor-pointer items-center justify-between p-6 text-left font-black transition-colors"
                        >
                            <span class="pr-4 text-sm text-foreground">{{
                                faq.question
                            }}</span>
                            <ChevronDown
                                class="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-300"
                                :class="[
                                    activeFaq === index ? 'rotate-180' : '',
                                ]"
                            />
                        </button>
                        <Transition
                            enter-active-class="transition-[max-height,opacity] duration-300 ease-out"
                            leave-active-class="transition-[max-height,opacity] duration-300 ease-in"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-40 opacity-100"
                            leave-from-class="max-h-40 opacity-100"
                            leave-to-class="max-h-0 opacity-0"
                        >
                            <div
                                v-show="activeFaq === index"
                                class="overflow-hidden"
                            >
                                <div
                                    class="border-t border-border/60 px-6 pt-4 pb-5 text-sm leading-relaxed text-muted-foreground"
                                >
                                    {{ faq.answer }}
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t bg-background py-8">
            <div
                class="container mx-auto flex flex-col items-center justify-between gap-4 px-4 text-xs font-semibold text-muted-foreground sm:flex-row sm:px-6"
            >
                <p>© 2026 ShieldSuite Inc. All rights reserved.</p>
                <div class="flex items-center gap-6">
                    <Link :href="home()" class="hover:text-foreground"
                        >Home</Link
                    >
                    <Link :href="about()" class="hover:text-foreground"
                        >About</Link
                    >
                    <Link :href="contact()" class="hover:text-foreground"
                        >Contact</Link
                    >
                    <Link :href="privacy()" class="hover:text-foreground"
                        >Privacy</Link
                    >
                    <Link :href="terms()" class="hover:text-foreground"
                        >Terms</Link
                    >
                </div>
            </div>
        </footer>
    </div>
</template>
