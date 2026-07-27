<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Clock,
    ShieldCheck,
    Sparkles,
    UserCheck,
    Star,
    ChevronDown,
    Sun,
    Moon,
    Phone,
    PhoneCall,
    Droplets,
    Wind,
    Zap,
    WashingMachine,
    Home,
    Bug,
    Lock,
    Radio,
    Layers,
    ArrowRight,
} from '@lucide/vue';
import { ref, onMounted } from 'vue';

import AppLogoIcon from '@/components/AppLogoIcon.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import SeoHead from '@/components/SeoHead.vue';
import PublicSandboxLeadMagnet from '@/components/PublicSandboxLeadMagnet.vue';
import VisualDispatchBoardShowcase from '@/components/VisualDispatchBoardShowcase.vue';
import TechnicalReliabilityShowcase from '@/components/TechnicalReliabilityShowcase.vue';
import { useAppearance } from '@/composables/useAppearance';
import { Badge } from '@/components/ui/badge';
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

const serviceTypes = [
    {
        title: 'Plumbing & Drain Services',
        icon: Droplets,
        gradient: 'from-blue-500 to-cyan-500',
        bgGlow: 'bg-blue-500/10 dark:bg-blue-500/20',
        textColor: 'text-blue-600 dark:text-blue-400',
        borderColor: 'hover:border-blue-500/40',
        description: 'Qualifies pipe leaks, main line clogs, water heater outages, and emergency shutoff triage 24/7.',
        keywords: ['Drain Cleaning', 'Water Heaters', 'Pipe Leaks', 'Sewer Camera'],
    },
    {
        title: 'HVAC & Climate Control',
        icon: Wind,
        gradient: 'from-sky-500 to-indigo-600',
        bgGlow: 'bg-sky-500/10 dark:bg-sky-500/20',
        textColor: 'text-sky-600 dark:text-sky-400',
        borderColor: 'hover:border-sky-500/40',
        description: 'Dispatches AC diagnostics, filters EPA 608 technician certs, and triages heat pump and furnace outages.',
        keywords: ['AC Diagnostics', 'Freon Refills', 'Furnace Repair', 'Heat Pumps'],
    },
    {
        title: 'Electrical & Power Systems',
        icon: Zap,
        gradient: 'from-amber-400 to-yellow-500',
        bgGlow: 'bg-amber-500/10 dark:bg-amber-500/20',
        textColor: 'text-amber-600 dark:text-amber-400',
        borderColor: 'hover:border-amber-500/40',
        description: 'Triages partial power outages, circuit breaker trips, panel upgrades, and urgent short-circuit safety risks.',
        keywords: ['Panel Upgrades', 'Short Circuits', 'EV Chargers', 'Sparking Outlets'],
    },
    {
        title: 'Appliance Repair',
        icon: WashingMachine,
        gradient: 'from-purple-500 to-violet-600',
        bgGlow: 'bg-purple-500/10 dark:bg-purple-500/20',
        textColor: 'text-purple-600 dark:text-purple-400',
        borderColor: 'hover:border-purple-500/40',
        description: 'Schedules technicians for refrigerators, washers, dryers, dishwashers, and ovens with deposit authorization.',
        keywords: ['Refrigerators', 'Washers & Dryers', 'Ovens & Ranges', 'Dishwashers'],
    },
    {
        title: 'Roofing & Storm Protection',
        icon: Home,
        gradient: 'from-emerald-400 to-teal-600',
        bgGlow: 'bg-emerald-500/10 dark:bg-emerald-500/20',
        textColor: 'text-emerald-600 dark:text-emerald-400',
        borderColor: 'hover:border-emerald-500/40',
        description: 'Dispatches emergency roof tarping teams, schedules storm damage inspections, and handles shingle repairs.',
        keywords: ['Roof Inspections', 'Emergency Tarping', 'Gutter Cleaning', 'Storm Repair'],
    },
    {
        title: 'Pest Control & Extermination',
        icon: Bug,
        gradient: 'from-rose-500 to-pink-600',
        bgGlow: 'bg-rose-500/10 dark:bg-rose-500/20',
        textColor: 'text-rose-600 dark:text-rose-400',
        borderColor: 'hover:border-rose-500/40',
        description: 'Schedules termite inspections, rodent exclusion triage, and recurring perimeter barrier sprays.',
        keywords: ['Termites', 'Rodent Control', 'Bed Bugs', 'Barrier Sprays'],
    },
    {
        title: 'Garage Doors & Gates',
        icon: ShieldCheck,
        gradient: 'from-orange-500 to-amber-600',
        bgGlow: 'bg-orange-500/10 dark:bg-orange-500/20',
        textColor: 'text-orange-600 dark:text-orange-400',
        borderColor: 'hover:border-orange-500/40',
        description: 'Triages broken torsion springs, stuck openers, and off-track door emergencies for fast dispatch.',
        keywords: ['Torsion Springs', 'Opener Repair', 'Track Alignment', 'Sensor Fixes'],
    },
    {
        title: 'Locksmith & Access Security',
        icon: Lock,
        gradient: 'from-teal-500 to-emerald-600',
        bgGlow: 'bg-teal-500/10 dark:bg-teal-500/20',
        textColor: 'text-teal-600 dark:text-teal-400',
        borderColor: 'hover:border-teal-500/40',
        description: 'Dispatches 24/7 lockout response teams, schedules commercial rekeying, and installs smart access hardware.',
        keywords: ['24/7 Lockout', 'Commercial Rekey', 'Smart Locks', 'Master Keying'],
    },
];

const { appearance, updateAppearance } = useAppearance();

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
    {
        question: 'How does the Sandbox Mode lead magnet work?',
        answer: 'You can test interactive IVR options, speech-to-text triage, and simulated AI call booking directly on our site risk-free before signing up. You can also dial +1 (619) 639-0411 to experience the voice AI live!',
    },
];

// Entrance animation trigger
const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});
</script>

<template>
    <SeoHead
        title="JustMascot • AI Voice Receptionist & Smart Dispatch for Trade Contractors"
        description="Streamline your plumbing, HVAC, or electrical business with an AI voice receptionist that automatically answers calls, checks technician shifts, respects 1.5h travel buffers, and schedules bookings."
        keywords="AI receptionist, contractor answering service, HVAC dispatch software, plumber booking system, electrical scheduling app, 24/7 call management, sandbox mode IVR, visual dispatch board"
        :jsonLd="{
            '@context': 'https://schema.org',
            '@graph': [
                {
                    '@type': 'SoftwareApplication',
                    name: 'JustMascot',
                    operatingSystem: 'All',
                    applicationCategory: 'BusinessApplication',
                    description: 'AI voice receptionist and dynamic dispatch scheduler for trade contractors (Plumbing, HVAC, Electrical, Roofing).',
                    url: 'https://justmascot.io',
                    offers: {
                        '@type': 'Offer',
                        price: '49.00',
                        priceCurrency: 'USD',
                        availability: 'https://schema.org/InStock'
                    },
                    aggregateRating: {
                        '@type': 'AggregateRating',
                        ratingValue: '4.9',
                        reviewCount: '128'
                    }
                },
                {
                    '@type': 'Organization',
                    name: 'JustMascot',
                    url: 'https://justmascot.io',
                    logo: 'https://justmascot.io/apple-touch-icon.png',
                    contactPoint: {
                        '@type': 'ContactPoint',
                        telephone: '+1-619-639-0411',
                        contactType: 'customer service'
                    }
                },
                {
                    '@type': 'FAQPage',
                    mainEntity: faqs.map(f => ({
                        '@type': 'Question',
                        name: f.question,
                        acceptedAnswer: {
                            '@type': 'Answer',
                            text: f.answer
                        }
                    }))
                }
            ]
        }"
    />

    <div
        class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-primary selection:text-primary-foreground dark:bg-slate-950 dark:text-slate-100"
    >
        <!-- Responsive Header -->
        <PublicHeader activePage="home" />

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
                        class="animate-pulse-slow inline-flex w-fit items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-1 text-xs font-semibold text-emerald-600 shadow-xs backdrop-blur-xs dark:bg-emerald-500/20 dark:text-emerald-400"
                    >
                        <PhoneCall class="h-3.5 w-3.5 animate-pulse text-emerald-500" />
                        <span>Test Live AI Receptionist: <a href="tel:+16196390411" class="font-bold underline hover:text-emerald-700 dark:hover:text-emerald-300">+1 (619) 639-0411</a></span>
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
                        Never miss another service call or lose a customer to the competition.
                        <strong>JustMascot</strong> is the ultimate AI voice receptionist and automated dispatch scheduling platform built specifically for plumbing, HVAC, electrical, and home service businesses. Automatically answer calls 24/7, verify technician skills, enforce 1.5-hour travel buffers, and book jobs into your dispatch board.
                    </p>

                    <div class="flex flex-wrap items-center gap-4 pt-2">
                        <a
                            href="#sandbox-demo"
                            class="inline-flex h-11 items-center justify-center gap-2.5 rounded-md bg-emerald-600 px-6 py-2.5 text-base font-bold text-white shadow-md transition-all hover:scale-103 hover:bg-emerald-700 active:scale-97 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                        >
                            <Radio class="h-5 w-5 animate-pulse text-white" />
                            <span>Try Interactive Sandbox Lead Magnet</span>
                        </a>

                        <Link
                            :href="register()"
                            class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-6 py-2.5 text-base font-medium text-primary-foreground shadow-sm transition-all hover:scale-103 hover:bg-primary/90 active:scale-97"
                        >
                            Start 14-Day Free Trial
                        </Link>
                    </div>

                    <div class="flex items-center gap-2 pt-1 text-xs font-medium text-muted-foreground">
                        <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                        <span>Live AI Receptionist line open 24/7 — Call +1 (619) 639-0411 or test below risk-free</span>
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
                        <div
                            class="mb-4 flex items-center justify-between border-b pb-3"
                        >
                            <span
                                class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                                >Interactive Mascot Dispatcher</span
                            >
                            <Badge
                                variant="outline"
                                class="rounded border-primary/20 bg-primary/10 px-1.5 py-0.5 text-[9px] font-bold text-primary uppercase"
                            >
                                Live Demo
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

        <!-- Risk-Free Sandbox Mode Lead Magnet Section -->
        <PublicSandboxLeadMagnet />

        <!-- Visual Dispatch Board Showcase Section -->
        <VisualDispatchBoardShowcase />

        <!-- Technical Reliability Showcase Section -->
        <TechnicalReliabilityShowcase />

        <!-- Service Types Trade Showcase Section -->
        <section class="relative border-b bg-background py-16 md:py-24">
            <div class="container mx-auto px-4 sm:px-6">
                <!-- Section Header -->
                <div class="mx-auto mb-16 max-w-[800px] space-y-4 text-center">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-3.5 py-1 text-xs font-bold text-primary uppercase tracking-wider"
                    >
                        <Sparkles class="h-3.5 w-3.5 text-primary" />
                        <span>Built For Every Trade Contractor</span>
                    </div>

                    <h2 class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl md:text-5xl">
                        AI Receptionist Trained for <span class="bg-gradient-to-r from-primary to-emerald-500 bg-clip-text text-transparent">All Home Services</span>
                    </h2>

                    <p class="text-lg leading-relaxed text-muted-foreground">
                        Our voice assistant automatically understands trade-specific terminology, diagnostic questions, emergency safety criteria, and skill requirements for your specialized workforce.
                    </p>
                </div>

                <!-- Services Grid -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="service in serviceTypes"
                        :key="service.title"
                        class="group relative flex flex-col justify-between rounded-2xl border bg-card/70 p-6 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl dark:border-slate-800/80 dark:bg-slate-900/40"
                        :class="service.borderColor"
                    >
                        <div>
                            <div class="mb-4 flex items-center justify-between">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl p-2.5 transition-transform duration-300 group-hover:scale-110"
                                    :class="service.bgGlow"
                                >
                                    <component
                                        :is="service.icon"
                                        class="h-6 w-6"
                                        :class="service.textColor"
                                    />
                                </div>
                                <span class="flex h-2 w-2 rounded-full bg-emerald-500 opacity-75 group-hover:animate-ping"></span>
                            </div>

                            <h3 class="mb-2 text-xl font-bold tracking-tight text-foreground">
                                {{ service.title }}
                            </h3>

                            <p class="mb-4 text-xs leading-relaxed text-muted-foreground">
                                {{ service.description }}
                            </p>
                        </div>

                        <div>
                            <div class="mb-3 flex flex-wrap gap-1.5">
                                <span
                                    v-for="kw in service.keywords"
                                    :key="kw"
                                    class="rounded-md border bg-accent/40 px-2 py-0.5 text-[11px] font-medium text-muted-foreground transition-colors group-hover:border-primary/20 group-hover:text-foreground"
                                >
                                    {{ kw }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Customer Testimonials Section -->
        <section
            class="relative overflow-hidden border-b bg-slate-50/50 py-16 md:py-24 dark:bg-slate-950/20"
        >
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
                        Discover how JustMascot helps HVAC, plumbing, and electrical teams capture more value from every call.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div
                        class="group relative flex flex-col justify-between rounded-2xl border bg-background p-6 shadow-xs transition-all duration-300 hover:-translate-y-1.5 hover:border-indigo-500/30 hover:shadow-lg dark:hover:shadow-indigo-500/5"
                    >
                        <div>
                            <div class="mb-4 flex items-center gap-0.5 text-amber-500">
                                <Star class="h-4.5 w-4.5 fill-current" v-for="i in 5" :key="i" />
                            </div>
                            <p class="mb-6 text-sm leading-relaxed text-muted-foreground italic">
                                "The 1.5h overlap buffer is a lifesaver. Before JustMascot, our plumbers were constantly double-booked during rush hour traffic. Now, our travel windows are protected automatically."
                            </p>
                        </div>
                        <div class="flex items-center gap-3.5 border-t border-border/60 pt-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-sm font-bold text-white shadow-sm">
                                MV
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-foreground">Marcus Vance</h4>
                                <p class="text-xs text-muted-foreground">Owner, Vance Plumbing &amp; Gas</p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group relative flex flex-col justify-between rounded-2xl border bg-background p-6 shadow-xs transition-all duration-300 hover:-translate-y-1.5 hover:border-emerald-500/30 hover:shadow-lg dark:hover:shadow-emerald-500/5"
                    >
                        <div>
                            <div class="mb-4 flex items-center gap-0.5 text-amber-500">
                                <Star class="h-4.5 w-4.5 fill-current" v-for="i in 5" :key="i" />
                            </div>
                            <p class="mb-6 text-sm leading-relaxed text-muted-foreground italic">
                                "HMAC webhook security gives us peace of mind. We integrate directly with our telephony provider, and we know our technician schedules cannot be manipulated."
                            </p>
                        </div>
                        <div class="flex items-center gap-3.5 border-t border-border/60 pt-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-sm font-bold text-white shadow-sm">
                                AR
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-foreground">Amanda Ross</h4>
                                <p class="text-xs text-muted-foreground">Operations Director, Apex Air Systems</p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="group relative flex flex-col justify-between rounded-2xl border bg-background p-6 shadow-xs transition-all duration-300 hover:-translate-y-1.5 hover:border-amber-500/30 hover:shadow-lg dark:hover:shadow-amber-500/5"
                    >
                        <div>
                            <div class="mb-4 flex items-center gap-0.5 text-amber-500">
                                <Star class="h-4.5 w-4.5 fill-current" v-for="i in 5" :key="i" />
                            </div>
                            <p class="mb-6 text-sm leading-relaxed text-muted-foreground italic">
                                "The multi-tenant isolation is top notch. We run a franchise model and need complete data separation. The Eloquent global scope implementation is flawless."
                            </p>
                        </div>
                        <div class="flex items-center gap-3.5 border-t border-border/60 pt-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-sm font-bold text-white shadow-sm">
                                DL
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-foreground">Devon Lane</h4>
                                <p class="text-xs text-muted-foreground">Founder, Lane Electrical Group</p>
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
                        Got questions about scheduling, security, or sandbox testing? We've got answers.
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
                            <span class="text-base sm:text-lg">{{ faq.question }}</span>
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
                            <div v-show="activeFaq === index" class="overflow-hidden">
                                <div class="border-t border-border/60 px-6 pt-4 pb-5 text-sm leading-relaxed text-muted-foreground">
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
                <p>© 2026 JustMascot Inc. All rights reserved.</p>
                <div class="flex items-center gap-6">
                    <Link :href="home()" class="hover:text-foreground">Home</Link>
                    <Link :href="about()" class="hover:text-foreground">About</Link>
                    <Link :href="pricing()" class="hover:text-foreground">Pricing</Link>
                    <Link :href="contact()" class="hover:text-foreground">Contact</Link>
                    <Link :href="privacy()" class="hover:text-foreground">Privacy</Link>
                    <Link :href="terms()" class="hover:text-foreground">Terms</Link>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes float-slow {
    0%, 100% { transform: translateY(0px) scale(1); }
    50% { transform: translateY(-15px) scale(1.05); }
}
@keyframes float-delayed {
    0%, 100% { transform: translateY(0px) scale(1.05); }
    50% { transform: translateY(15px) scale(1); }
}
.animate-float-slow {
    animation: float-slow 8s ease-in-out infinite;
}
.animate-float-delayed {
    animation: float-delayed 10s ease-in-out infinite;
}
.animate-pulse-slow {
    animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
