<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Phone,
    Calendar,
    Clock,
    CheckCircle2,
    Zap,
    ShieldCheck,
    Sparkles,
    UserCheck,
    MessageSquare,
    Play,
    Star,
    ChevronDown,
} from '@lucide/vue';
import { ref, onMounted } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import { Badge } from '@/components/ui/badge';
import {
    dashboard,
    login,
    register,
    home,
    about,
    pricing,
    contact,
} from '@/routes';

const mascotState = ref<number>(0);
const simulatedMessage = ref<string>('Mascot is idle, monitoring channels...');

const simulateState = (stateNum: number) => {
    mascotState.value = stateNum;

    if (stateNum === 0) {
        simulatedMessage.value = 'Mascot is idle, monitoring channels...';
    } else if (stateNum === 1) {
        simulatedMessage.value =
            'AI Receptionist is on a live call analyzing technician skills & availabilities...';
    } else if (stateNum === 2) {
        simulatedMessage.value =
            'Success! Booking confirmed for plumber shift with 1.5h travel buffer applied.';
    } else if (stateNum === 3) {
        simulatedMessage.value =
            'Conflict blocked: Request overlaps with another booking or technician is out-of-shift.';
    }
};

const activeFaq = ref<number | null>(null);
const toggleFaq = (index: number) => {
    activeFaq.value = activeFaq.value === index ? null : index;
};

const faqs = [
    {
        question: 'How does the 1.5-hour travel buffer work?',
        answer: "When an incoming call is received, our smart scheduling engine checks the requested time slot against the assigned technician's existing appointments. If the slot falls within 1.5 hours of another booking, the system flags a conflict and suggests alternate times, protecting your team's travel windows.",
    },
    {
        question: 'How does database isolation ensure multi-tenant security?',
        answer: "Each company subscription (tenant) operates inside a completely isolated logical database context. We use Laravel Eloquent global scopes to automatically restrict queries to the logged-in tenant's scope, preventing data leaks or cross-tenant scheduling overlaps.",
    },
    {
        question: 'How does HMAC signature verification protect the webhooks?',
        answer: "To protect your telephony integration from unauthorized webhook trigger requests, all incoming payloads are verified using HMAC-SHA256 signatures. The incoming signature header is computed against your company's tenant secret key, instantly rejecting unverified requests.",
    },
    {
        question: 'Can we define custom skills for our technicians?',
        answer: 'Absolutely. You can assign specific skill tags (e.g., HVAC, electrical, plumbing, gas) to your technicians. The AI dispatcher uses these tags alongside active shifts to match incoming booking requests with the right qualified specialist.',
    },
];

// Entrance animation trigger
const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});
</script>

<template>
    <Head title="AI Voice Receptionist & Dispatch for Contractors" />

    <div
        class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-primary selection:text-primary-foreground dark:bg-slate-950 dark:text-slate-100"
    >
        <!-- Header -->
        <header
            class="sticky top-0 z-40 w-full border-b bg-background/95 shadow-xs backdrop-blur-md transition-all duration-300 supports-[backdrop-filter]:bg-background/60"
        >
            <div
                class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6"
            >
                <!-- Logo -->
                <div class="group flex cursor-pointer items-center gap-2">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm transition-transform duration-300 group-hover:scale-105"
                    >
                        <AppLogoIcon
                            class="h-5 w-5 fill-current text-primary-foreground"
                        />
                    </div>
                    <span
                        class="bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-xl font-bold tracking-tight text-transparent dark:from-white dark:to-slate-300"
                        >businesscalls</span
                    >
                </div>

                <!-- Navigation Links -->
                <nav class="hidden items-center gap-6 md:flex">
                    <Link
                        :href="home()"
                        class="relative text-sm font-semibold text-foreground transition-colors after:absolute after:bottom-[-20px] after:left-0 after:h-[2px] after:w-full after:origin-bottom-right after:scale-x-0 after:bg-primary after:transition-transform after:duration-300 hover:text-foreground hover:after:origin-bottom-left hover:after:scale-x-100"
                        >Home</Link
                    >
                    <Link
                        :href="about()"
                        class="relative text-sm font-semibold text-muted-foreground transition-colors after:absolute after:bottom-[-20px] after:left-0 after:h-[2px] after:w-full after:origin-bottom-right after:scale-x-0 after:bg-primary after:transition-transform after:duration-300 hover:text-foreground hover:after:origin-bottom-left hover:after:scale-x-100"
                        >About</Link
                    >
                    <Link
                        :href="pricing()"
                        class="relative text-sm font-semibold text-muted-foreground transition-colors after:absolute after:bottom-[-20px] after:left-0 after:h-[2px] after:w-full after:origin-bottom-right after:scale-x-0 after:bg-primary after:transition-transform after:duration-300 hover:text-foreground hover:after:origin-bottom-left hover:after:scale-x-100"
                        >Pricing</Link
                    >
                    <Link
                        :href="contact()"
                        class="relative text-sm font-semibold text-muted-foreground transition-colors after:absolute after:bottom-[-20px] after:left-0 after:h-[2px] after:w-full after:origin-bottom-right after:scale-x-0 after:bg-primary after:transition-transform after:duration-300 hover:text-foreground hover:after:origin-bottom-left hover:after:scale-x-100"
                        >Contact</Link
                    >
                </nav>

                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-all hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-hidden active:scale-95"
                    >
                        Go to Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-flex h-9 items-center justify-center rounded-md px-4 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground active:scale-95"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-all hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-hidden active:scale-95"
                        >
                            Get Started
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero Section with Live Simulator -->
        <section
            class="relative overflow-hidden border-b py-12 md:py-24 lg:py-32"
        >
            <!-- Background Gradients & Grid Pattern -->
            <div
                class="absolute inset-0 -z-15 bg-[radial-gradient(45rem_50rem_at_top,var(--color-slate-100),theme(colors.slate.50))] dark:bg-[radial-gradient(45rem_50rem_at_top,var(--color-slate-900),theme(colors.slate.950))]"
            ></div>
            <div
                class="absolute inset-0 -z-20 bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)] bg-[size:14px_24px]"
            ></div>

            <!-- Decorative Glow Blobs -->
            <div
                class="animate-float-slow absolute top-1/4 left-1/4 -z-10 h-72 w-72 rounded-full bg-indigo-400/20 blur-3xl"
            ></div>
            <div
                class="animate-float-delayed absolute right-1/4 bottom-1/4 -z-10 h-80 w-80 rounded-full bg-emerald-400/15 blur-3xl"
            ></div>

            <div
                class="container mx-auto grid grid-cols-1 items-center gap-12 px-4 sm:px-6 lg:grid-cols-12"
            >
                <!-- Hero Left Column -->
                <div
                    class="flex transform flex-col justify-center space-y-6 transition-all duration-1000 ease-out lg:col-span-7"
                    :class="[
                        isMounted
                            ? 'translate-y-0 opacity-100'
                            : 'translate-y-6 opacity-0',
                    ]"
                >
                    <div
                        class="animate-pulse-slow inline-flex w-fit items-center gap-1.5 rounded-full border bg-background/80 px-3 py-1 text-xs font-semibold text-muted-foreground shadow-xs backdrop-blur-xs"
                    >
                        <Sparkles
                            class="animate-spin-slow h-3.5 w-3.5 text-indigo-500"
                        />
                        <span>Interactive Rive Mascot Live Preview</span>
                    </div>

                    <h1
                        class="text-4xl leading-tight font-extrabold tracking-tight text-foreground sm:text-5xl md:text-6xl lg:text-7xl"
                    >
                        AI-driven receptionist &
                        <span
                            class="bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 bg-clip-text text-transparent dark:from-indigo-400 dark:via-indigo-300 dark:to-emerald-400"
                            >smart scheduling</span
                        >
                        for contractors
                    </h1>

                    <p
                        class="max-w-[600px] text-lg leading-relaxed text-muted-foreground"
                    >
                        Say goodbye to missed calls and double bookings.
                        **businesscalls** automatically answers incoming calls,
                        matches technician skills, validates active shifts,
                        enforces travel buffers, and schedules bookings
                        dynamically.
                    </p>

                    <div class="flex flex-wrap gap-4 pt-2">
                        <Link
                            :href="register()"
                            class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-6 py-2.5 text-base font-medium text-primary-foreground shadow-sm transition-all hover:scale-103 hover:bg-primary/90 active:scale-97"
                        >
                            Start 14-Day Free Trial
                        </Link>
                        <a
                            href="#features"
                            class="inline-flex h-11 items-center justify-center rounded-md border bg-background/80 px-6 py-2.5 text-base font-medium text-muted-foreground shadow-xs backdrop-blur-xs transition-all hover:scale-103 hover:bg-accent hover:text-accent-foreground active:scale-97"
                        >
                            Explore Platform Features
                        </a>
                    </div>
                </div>

                <!-- Hero Right Column (Mascot Simulator Card) -->
                <div
                    class="flex transform flex-col items-center transition-all delay-200 duration-1000 ease-out lg:col-span-5"
                    :class="[
                        isMounted
                            ? 'translate-y-0 opacity-100'
                            : 'translate-y-6 opacity-0',
                    ]"
                >
                    <div
                        class="relative w-full max-w-[420px] rounded-2xl border bg-card/85 p-6 shadow-xl backdrop-blur-md transition-all duration-500 hover:border-indigo-500/20 hover:shadow-2xl dark:border-slate-800/80 dark:bg-slate-900/50"
                    >
                        <!-- Top right ambient light glow -->
                        <div
                            class="absolute -top-10 -right-10 -z-10 h-32 w-32 rounded-full bg-indigo-500/10 blur-2xl"
                        ></div>

                        <div
                            class="mb-4 flex items-center justify-between border-b pb-3"
                        >
                            <span
                                class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                                >Interactive AI Simulator</span
                            >
                            <Badge
                                variant="outline"
                                class="rounded border-primary/20 bg-primary/10 px-1.5 py-0.5 text-[9px] font-bold text-primary uppercase"
                            >
                                Live Interactive Demo
                            </Badge>
                        </div>

                        <!-- Render Canvas Targeting Mascot -->
                        <div
                            class="relative mx-auto mb-4 aspect-square w-full max-w-[260px] overflow-hidden rounded-xl border bg-accent/25 p-2 dark:bg-slate-900/40"
                        >
                            <DispatcherMascot :state="mascotState" />
                        </div>

                        <!-- Simulated Event Details -->
                        <div
                            class="mb-4 flex min-h-[60px] items-center justify-center rounded-lg border bg-accent/40 p-3 text-center text-xs leading-normal font-medium text-muted-foreground shadow-inner"
                        >
                            {{ simulatedMessage }}
                        </div>

                        <!-- Simulation Action Triggers -->
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                @click="simulateState(1)"
                                class="inline-flex h-8 cursor-pointer items-center justify-center rounded-md border text-xs font-semibold text-foreground transition-all duration-150 hover:bg-accent active:scale-95"
                            >
                                Simulate Call
                            </button>
                            <button
                                @click="simulateState(2)"
                                class="inline-flex h-8 cursor-pointer items-center justify-center rounded-md border border-emerald-500/20 bg-emerald-500/10 text-xs font-semibold text-emerald-600 duration-150 hover:bg-emerald-500/20 active:scale-95 dark:text-emerald-400"
                            >
                                Simulate Booking
                            </button>
                            <button
                                @click="simulateState(3)"
                                class="inline-flex h-8 cursor-pointer items-center justify-center rounded-md border border-rose-500/20 bg-rose-500/10 text-xs font-semibold text-rose-600 duration-150 hover:bg-rose-500/20 active:scale-95 dark:text-rose-400"
                            >
                                Simulate Overlap
                            </button>
                            <button
                                @click="simulateState(0)"
                                class="inline-flex h-8 cursor-pointer items-center justify-center rounded-md border text-xs font-semibold text-muted-foreground duration-150 hover:bg-accent active:scale-95"
                            >
                                Reset State
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Grid Section -->
        <section
            id="features"
            class="border-b bg-card py-16 md:py-24 dark:bg-slate-900/20"
        >
            <div class="container mx-auto px-4 sm:px-6">
                <div class="mx-auto mb-16 max-w-[800px] space-y-3 text-center">
                    <h2
                        class="text-xs font-black tracking-widest text-primary uppercase"
                    >
                        Core Architecture
                    </h2>
                    <h3
                        class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                    >
                        Production-grade scheduling engine
                    </h3>
                    <p class="leading-relaxed text-muted-foreground">
                        Our platform is engineered for trade contractors (HVAC,
                        Plumbing, Electrical) to ensure absolute data isolation
                        and schedule verification.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <!-- Feature 1: Tenancy Scope -->
                    <div
                        class="group relative flex flex-col rounded-xl border bg-background p-6 shadow-xs transition-all duration-300 hover:-translate-y-1.5 hover:border-indigo-500/20 hover:shadow-md"
                    >
                        <div
                            class="absolute inset-0 -z-10 rounded-xl bg-gradient-to-br from-indigo-500/0 via-indigo-500/0 to-indigo-500/0 opacity-0 transition-opacity duration-300 group-hover:from-indigo-500/[0.01] group-hover:to-indigo-500/[0.03] group-hover:opacity-100"
                        ></div>
                        <div
                            class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary transition-transform duration-300 group-hover:scale-105"
                        >
                            <UserCheck class="h-5 w-5" />
                        </div>
                        <h4
                            class="mb-2 text-lg font-bold text-foreground transition-colors group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                        >
                            Tenant Database Isolation
                        </h4>
                        <p
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            Global scopes isolate queries to active session
                            sub-tenants. Multi-tenant database rules are
                            validated at every layer of the Eloquent model
                            lifecycle.
                        </p>
                    </div>

                    <!-- Feature 2: Overlap Buffer -->
                    <div
                        class="group relative flex flex-col rounded-xl border bg-background p-6 shadow-xs transition-all duration-300 hover:-translate-y-1.5 hover:border-emerald-500/20 hover:shadow-md"
                    >
                        <div
                            class="absolute inset-0 -z-10 rounded-xl bg-gradient-to-br from-emerald-500/0 via-emerald-500/0 to-emerald-500/0 opacity-0 transition-opacity duration-300 group-hover:from-emerald-500/[0.01] group-hover:to-emerald-500/[0.03] group-hover:opacity-100"
                        ></div>
                        <div
                            class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary transition-transform duration-300 group-hover:scale-105"
                        >
                            <Clock class="h-5 w-5" />
                        </div>
                        <h4
                            class="mb-2 text-lg font-bold text-foreground transition-colors group-hover:text-emerald-600 dark:group-hover:text-emerald-400"
                        >
                            1.5h Overlap Buffer
                        </h4>
                        <p
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            Automatically validates scheduled appointments and
                            blocks technician dispatches that fall within 1.5
                            hours of another booking to account for travel.
                        </p>
                    </div>

                    <!-- Feature 3: HMAC Signatures -->
                    <div
                        class="group relative flex flex-col rounded-xl border bg-background p-6 shadow-xs transition-all duration-300 hover:-translate-y-1.5 hover:border-amber-500/20 hover:shadow-md"
                    >
                        <div
                            class="absolute inset-0 -z-10 rounded-xl bg-gradient-to-br from-amber-500/0 via-amber-500/0 to-amber-500/0 opacity-0 transition-opacity duration-300 group-hover:from-amber-500/[0.01] group-hover:to-amber-500/[0.03] group-hover:opacity-100"
                        ></div>
                        <div
                            class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary transition-transform duration-300 group-hover:scale-105"
                        >
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <h4
                            class="mb-2 text-lg font-bold text-foreground transition-colors group-hover:text-amber-600 dark:group-hover:text-amber-400"
                        >
                            HMAC Webhook Security
                        </h4>
                        <p
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            Secure your API routes from fake telephony requests.
                            All incoming payload headers are checked against
                            SHA256 hashes generated from the tenant secret key.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Reviews Section -->
        <section
            class="relative overflow-hidden border-b bg-slate-50/50 py-16 md:py-24 dark:bg-slate-950/20"
        >
            <!-- Background Decorative Glow Blobs -->
            <div
                class="absolute top-20 -right-40 -z-10 h-80 w-80 rounded-full bg-indigo-500/10 blur-3xl"
            ></div>
            <div
                class="absolute bottom-20 -left-40 -z-10 h-80 w-80 rounded-full bg-emerald-500/10 blur-3xl"
            ></div>

            <div class="container mx-auto px-4 sm:px-6">
                <div class="mx-auto mb-16 max-w-[800px] space-y-3 text-center">
                    <h2
                        class="text-xs font-black tracking-widest text-primary uppercase"
                    >
                        Customer Testimonials
                    </h2>
                    <h3
                        class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                    >
                        Trusted by top-tier trade contractors
                    </h3>
                    <p class="leading-relaxed text-muted-foreground">
                        Discover how businesscalls helps HVAC, plumbing, and
                        electrical teams capture more value from every call.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <!-- Review 1: Plumbing -->
                    <div
                        class="group relative flex flex-col justify-between rounded-2xl border bg-background p-6 shadow-xs transition-all duration-300 hover:-translate-y-1.5 hover:border-indigo-500/30 hover:shadow-lg dark:hover:shadow-indigo-500/5"
                    >
                        <div
                            class="absolute inset-0 -z-10 rounded-2xl bg-gradient-to-br from-indigo-500/0 via-indigo-500/0 to-indigo-500/0 opacity-0 transition-opacity duration-300 group-hover:from-indigo-500/[0.02] group-hover:to-indigo-500/[0.05] group-hover:opacity-100"
                        ></div>
                        <div>
                            <!-- Star rating -->
                            <div
                                class="mb-4 flex items-center gap-0.5 text-amber-500"
                            >
                                <Star
                                    class="h-4.5 w-4.5 fill-current"
                                    v-for="i in 5"
                                    :key="i"
                                />
                            </div>
                            <p
                                class="mb-6 text-sm leading-relaxed text-muted-foreground italic"
                            >
                                "The 1.5h overlap buffer is a lifesaver. Before
                                businesscalls, our plumbers were constantly
                                double-booked during rush hour traffic. Now, our
                                travel windows are protected automatically."
                            </p>
                        </div>
                        <div
                            class="flex items-center gap-3.5 border-t border-border/60 pt-4"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-sm font-bold text-white shadow-sm"
                            >
                                MV
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-foreground">
                                    Marcus Vance
                                </h4>
                                <p class="text-xs text-muted-foreground">
                                    Owner, Vance Plumbing &amp; Gas
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Review 2: HVAC -->
                    <div
                        class="group relative flex flex-col justify-between rounded-2xl border bg-background p-6 shadow-xs transition-all duration-300 hover:-translate-y-1.5 hover:border-emerald-500/30 hover:shadow-lg dark:hover:shadow-emerald-500/5"
                    >
                        <div
                            class="absolute inset-0 -z-10 rounded-2xl bg-gradient-to-br from-emerald-500/0 via-emerald-500/0 to-emerald-500/0 opacity-0 transition-opacity duration-300 group-hover:from-emerald-500/[0.02] group-hover:to-emerald-500/[0.05] group-hover:opacity-100"
                        ></div>
                        <div>
                            <!-- Star rating -->
                            <div
                                class="mb-4 flex items-center gap-0.5 text-amber-500"
                            >
                                <Star
                                    class="h-4.5 w-4.5 fill-current"
                                    v-for="i in 5"
                                    :key="i"
                                />
                            </div>
                            <p
                                class="mb-6 text-sm leading-relaxed text-muted-foreground italic"
                            >
                                "HMAC webhook security gives us peace of mind.
                                We integrate directly with our telephony
                                provider, and we know our technician schedules
                                cannot be manipulated by unauthorized requests."
                            </p>
                        </div>
                        <div
                            class="flex items-center gap-3.5 border-t border-border/60 pt-4"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-sm font-bold text-white shadow-sm"
                            >
                                AR
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-foreground">
                                    Amanda Ross
                                </h4>
                                <p class="text-xs text-muted-foreground">
                                    Operations Director, Apex Air Systems
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Review 3: Electrical -->
                    <div
                        class="group relative flex flex-col justify-between rounded-2xl border bg-background p-6 shadow-xs transition-all duration-300 hover:-translate-y-1.5 hover:border-amber-500/30 hover:shadow-lg dark:hover:shadow-amber-500/5"
                    >
                        <div
                            class="absolute inset-0 -z-10 rounded-2xl bg-gradient-to-br from-amber-500/0 via-amber-500/0 to-amber-500/0 opacity-0 transition-opacity duration-300 group-hover:from-amber-500/[0.02] group-hover:to-amber-500/[0.05] group-hover:opacity-100"
                        ></div>
                        <div>
                            <!-- Star rating -->
                            <div
                                class="mb-4 flex items-center gap-0.5 text-amber-500"
                            >
                                <Star
                                    class="h-4.5 w-4.5 fill-current"
                                    v-for="i in 5"
                                    :key="i"
                                />
                            </div>
                            <p
                                class="mb-6 text-sm leading-relaxed text-muted-foreground italic"
                            >
                                "The multi-tenant isolation is top notch. We run
                                a franchise model and need complete data
                                separation between different regions. The
                                Eloquent global scope implementation is
                                flawless."
                            </p>
                        </div>
                        <div
                            class="flex items-center gap-3.5 border-t border-border/60 pt-4"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-sm font-bold text-white shadow-sm"
                            >
                                DL
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-foreground">
                                    Devon Lane
                                </h4>
                                <p class="text-xs text-muted-foreground">
                                    Founder, Lane Electrical Group
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="border-b bg-card py-16 md:py-24 dark:bg-slate-900/20">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="mx-auto mb-16 max-w-[800px] space-y-3 text-center">
                    <h2
                        class="text-xs font-black tracking-widest text-primary uppercase"
                    >
                        FAQ
                    </h2>
                    <h3
                        class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                    >
                        Frequently Asked Questions
                    </h3>
                    <p class="leading-relaxed text-muted-foreground">
                        Got questions about scheduling, security, or setup?
                        We've got answers.
                    </p>
                </div>

                <div class="mx-auto max-w-[800px] space-y-4">
                    <div
                        v-for="(faq, index) in faqs"
                        :key="index"
                        class="overflow-hidden rounded-xl border bg-background transition-all duration-300"
                        :class="[
                            activeFaq === index
                                ? 'border-primary/50 shadow-xs'
                                : 'hover:border-slate-300 dark:hover:border-slate-800',
                        ]"
                    >
                        <button
                            @click="toggleFaq(index)"
                            class="flex w-full cursor-pointer items-center justify-between px-6 py-5 text-left font-bold text-foreground focus:outline-hidden"
                        >
                            <span class="text-base sm:text-lg">{{
                                faq.question
                            }}</span>
                            <span
                                class="ml-4 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground transition-all duration-300"
                                :class="{
                                    'rotate-180 bg-primary text-primary-foreground':
                                        activeFaq === index,
                                }"
                            >
                                <ChevronDown class="h-4 w-4" />
                            </span>
                        </button>

                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0 transform -translate-y-2"
                            enter-to-class="max-h-96 opacity-100 transform translate-y-0"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-96 opacity-100 transform translate-y-0"
                            leave-to-class="max-h-0 opacity-0 transform -translate-y-2"
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
                <p>© 2026 businesscalls Inc. All rights reserved.</p>
                <div class="flex items-center gap-6">
                    <Link :href="home()" class="hover:text-foreground"
                        >Home</Link
                    >
                    <Link :href="about()" class="hover:text-foreground"
                        >About</Link
                    >
                    <Link :href="pricing()" class="hover:text-foreground"
                        >Pricing</Link
                    >
                    <Link :href="contact()" class="hover:text-foreground"
                        >Contact</Link
                    >
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes float-slow {
    0%,
    100% {
        transform: translateY(0px) scale(1);
    }
    50% {
        transform: translateY(-15px) scale(1.05);
    }
}
@keyframes float-delayed {
    0%,
    100% {
        transform: translateY(0px) scale(1.05);
    }
    50% {
        transform: translateY(15px) scale(1);
    }
}
.animate-float-slow {
    animation: float-slow 8s ease-in-out infinite;
}
.animate-float-delayed {
    animation: float-delayed 10s ease-in-out infinite;
}
.animate-spin-slow {
    animation: spin 8s linear infinite;
}
.animate-pulse-slow {
    animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
