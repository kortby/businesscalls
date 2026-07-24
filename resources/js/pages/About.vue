<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Users,
    Target,
    Bot,
    Database,
    Clock,
    ShieldCheck,
    Cpu,
    Activity,
    PhoneCall,
    MapPin,
    ArrowRight,
} from '@lucide/vue';
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
</script>

<template>
    <SeoHead
        title="About Us | Dedicated AI Voice Receptionists for Trades"
        description="Learn how JustMascot is transforming scheduling for trade industries. Discover our mission to eliminate missed service calls for HVAC, plumbing, and electrical contractors."
        keywords="about JustMascot, contractor answering services, trade scheduling platform, AI dispatcher mission, automation for contractors"
        :jsonLd="{
            '@context': 'https://schema.org',
            '@type': 'Organization',
            name: 'JustMascot',
            url: 'https://justmascot.io',
            logo: 'https://justmascot.io/apple-touch-icon.png',
            description:
                'AI voice receptionist and scheduling automation provider for home service and trade contractors.',
            sameAs: [
                'https://x.com/justmascot',
                'https://www.linkedin.com/company/justmascot',
            ],
        }"
    />

    <div
        class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-primary selection:text-primary-foreground dark:bg-slate-950 dark:text-slate-100"
    >
        <!-- Header -->
        <PublicHeader activePage="about" />

        <!-- Hero Section with Glowing Gradients -->
        <section
            class="relative overflow-hidden border-b bg-gradient-to-b from-indigo-500/10 via-transparent to-transparent py-20 md:py-32 dark:from-indigo-950/20"
        >
            <div
                class="absolute inset-0 -z-20 bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)] bg-[size:14px_24px]"
            ></div>
            <div
                class="animate-float-slow absolute top-12 left-10 -z-10 h-64 w-64 rounded-full bg-indigo-500/15 blur-3xl"
            ></div>

            <div
                class="container mx-auto max-w-[900px] space-y-6 px-4 text-center sm:px-6"
            >
                <div
                    class="mx-auto inline-flex items-center gap-1.5 rounded-full border bg-background/80 px-3 py-1 text-xs font-semibold text-muted-foreground shadow-xs backdrop-blur-xs"
                >
                    <Activity class="h-3.5 w-3.5 text-indigo-500" />
                    <span>Engineered by ShieldSuite Inc.</span>
                </div>
                <h1
                    class="text-4xl leading-tight font-extrabold tracking-tight text-foreground sm:text-5xl md:text-6xl"
                >
                    Our Mission: Zero Missed Service Calls for Contractors
                </h1>
                <p class="text-lg leading-relaxed text-muted-foreground">
                    At <strong>JustMascot</strong>, we build cutting-edge
                    artificial intelligence dispatch systems specifically
                    engineered for plumbing, HVAC, and electrical service
                    businesses. Our mission is to ensure that no home service
                    business ever loses a client because they were busy on a job
                    or unable to answer the phone. By combining advanced voice
                    technology with robust scheduling logic, we help you keep
                    your trucks rolling and your business growing.
                </p>
            </div>
        </section>

        <!-- Dynamic Metrics Row -->
        <section class="border-b bg-card py-10 dark:bg-slate-900/10">
            <div
                class="container mx-auto grid grid-cols-1 gap-8 px-4 text-center sm:px-6 md:grid-cols-3"
            >
                <div class="space-y-1">
                    <p class="text-4xl font-extrabold text-indigo-500">98.4%</p>
                    <p class="text-sm font-semibold text-muted-foreground">
                        Answering & Booking Success Rate
                    </p>
                </div>
                <div class="space-y-1">
                    <p class="text-4xl font-extrabold text-emerald-500">
                        &lt; 1.2s
                    </p>
                    <p class="text-sm font-semibold text-muted-foreground">
                        Voice Assistant Intent Latency
                    </p>
                </div>
                <div class="space-y-1">
                    <p class="text-4xl font-extrabold text-rose-500">
                        24/7/365
                    </p>
                    <p class="text-sm font-semibold text-muted-foreground">
                        Operational Coverage & Monitoring
                    </p>
                </div>
            </div>
        </section>

        <!-- Our Story Timeline (Interactive Aesthetic) -->
        <section class="border-b py-20 md:py-28">
            <div class="container mx-auto max-w-[900px] px-4 sm:px-6">
                <div class="mb-16 text-center">
                    <h2
                        class="text-xs font-black tracking-widest text-primary uppercase"
                    >
                        Our Journey
                    </h2>
                    <h3
                        class="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                    >
                        From Answering Service to Intelligent Dispatch
                    </h3>
                </div>

                <!-- Timeline Layout -->
                <div
                    class="relative space-y-12 border-l border-slate-200 pl-6 dark:border-slate-800"
                >
                    <!-- Point 1 -->
                    <div class="relative">
                        <div
                            class="absolute top-1.5 -left-[35px] flex h-6 w-6 items-center justify-center rounded-full border-2 border-indigo-500 bg-background"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-indigo-500"
                            ></span>
                        </div>
                        <h4 class="text-lg font-black text-foreground">
                            2023: The Leak in Trade Services
                        </h4>
                        <p
                            class="mt-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            Our founders at ShieldSuite Inc. analyzed service
                            operations and discovered that
                            <strong>up to 62% of incoming phone calls</strong>
                            to HVAC, plumbing, and electrical businesses went
                            unanswered. Traditional call centers were slow,
                            expensive, and didn't integrate with active
                            technician calendars.
                        </p>
                    </div>

                    <!-- Point 2 -->
                    <div class="relative">
                        <div
                            class="absolute top-1.5 -left-[35px] flex h-6 w-6 items-center justify-center rounded-full border-2 border-emerald-500 bg-background"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-emerald-500"
                            ></span>
                        </div>
                        <h4 class="text-lg font-black text-foreground">
                            2024: Acoustic Telemetry & Voice AI Breakthroughs
                        </h4>
                        <p
                            class="mt-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            ShieldSuite developed a low-latency speech analysis
                            engine capable of understanding complex trade
                            scheduling requests (e.g., distinguishing an
                            emergency pipe burst from a routine tune-up) and
                            matching them directly against real-time operational
                            database states.
                        </p>
                    </div>

                    <!-- Point 3 -->
                    <div class="relative">
                        <div
                            class="absolute top-1.5 -left-[35px] flex h-6 w-6 items-center justify-center rounded-full border-2 border-rose-500 bg-background"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-rose-500"
                            ></span>
                        </div>
                        <h4 class="text-lg font-black text-foreground">
                            2025: The 1.5-Hour Buffer & Tenant Security Shield
                        </h4>
                        <p
                            class="mt-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            To make automated dispatching truly
                            production-ready, we introduced the 1.5-hour
                            geographic travel buffer and built logical tenant
                            scoping into every database call. This guaranteed
                            that technicians were never double-booked and client
                            databases remained completely isolated.
                        </p>
                    </div>

                    <!-- Point 4 -->
                    <div class="relative">
                        <div
                            class="absolute top-1.5 -left-[35px] flex h-6 w-6 items-center justify-center rounded-full border-2 border-primary bg-background"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-primary"
                            ></span>
                        </div>
                        <h4 class="text-lg font-black text-foreground">
                            2026: Scaled Operations & Franchise Integration
                        </h4>
                        <p
                            class="mt-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            Today, <strong>JustMascot</strong> processes
                            thousands of calls daily, powering trade franchise
                            groups and local business owners alike, preventing
                            missed leads, and allowing technicians to focus
                            strictly on their craft.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mission & Values (Duolingo Aesthetic Card Grid) -->
        <section class="border-b bg-card py-20 md:py-28">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="mx-auto mb-16 max-w-[800px] text-center">
                    <h2
                        class="text-xs font-black tracking-widest text-primary uppercase"
                    >
                        Core Pillars
                    </h2>
                    <h3
                        class="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                    >
                        Built Upon Security, Reliability, & Trades Logic
                    </h3>
                </div>

                <div
                    class="mx-auto grid max-w-[1100px] grid-cols-1 gap-8 md:grid-cols-3"
                >
                    <!-- Card 1 -->
                    <div
                        class="flex flex-col items-center rounded-3xl border-4 border-b-8 border-emerald-400 bg-emerald-500/5 p-6 text-center transition-all duration-300 hover:-translate-y-1.5 hover:shadow-lg dark:border-emerald-800 dark:bg-emerald-950/10"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl border-2 border-b-4 border-emerald-700 bg-emerald-500 text-white shadow-sm"
                        >
                            <Users class="h-6 w-6" />
                        </div>
                        <h3
                            class="text-xl font-black text-emerald-900 dark:text-emerald-300"
                        >
                            Contractor Focused
                        </h3>
                        <p
                            class="mt-2 text-sm leading-relaxed text-emerald-800 dark:text-emerald-400"
                        >
                            Built specifically for hands-on businesses. We
                            understand dispatching logic, travel times, and
                            emergency priorities.
                        </p>
                    </div>

                    <!-- Card 2 -->
                    <div
                        class="flex flex-col items-center rounded-3xl border-4 border-b-8 border-indigo-400 bg-indigo-500/5 p-6 text-center transition-all duration-300 hover:-translate-y-1.5 hover:shadow-lg dark:border-indigo-800 dark:bg-indigo-950/10"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl border-2 border-b-4 border-indigo-700 bg-indigo-500 text-white shadow-sm"
                        >
                            <Bot class="h-6 w-6" />
                        </div>
                        <h3
                            class="text-xl font-black text-indigo-900 dark:text-indigo-300"
                        >
                            Voice AI Intelligence
                        </h3>
                        <p
                            class="mt-2 text-sm leading-relaxed text-indigo-800 dark:text-indigo-400"
                        >
                            Leveraging speech recognition and JSON telemetry to
                            validate technician skills and schedules in
                            real-time.
                        </p>
                    </div>

                    <!-- Card 3 -->
                    <div
                        class="flex flex-col items-center rounded-3xl border-4 border-b-8 border-rose-400 bg-rose-500/5 p-6 text-center transition-all duration-300 hover:-translate-y-1.5 hover:shadow-lg dark:border-rose-800 dark:bg-rose-950/10"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl border-2 border-b-4 border-rose-700 bg-rose-500 text-white shadow-sm"
                        >
                            <Target class="h-6 w-6" />
                        </div>
                        <h3
                            class="text-xl font-black text-rose-900 dark:text-rose-300"
                        >
                            100% Reliable
                        </h3>
                        <p
                            class="mt-2 text-sm leading-relaxed text-rose-800 dark:text-rose-400"
                        >
                            Strict multi-tenant security structures ensure
                            tenant settings, bookings, and schedules remain
                            fully isolated.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Technical Excellence Architecture Section -->
        <section
            class="border-b bg-slate-50/50 py-20 md:py-28 dark:bg-slate-900/5"
        >
            <div
                class="container mx-auto grid max-w-[1000px] grid-cols-1 items-center gap-12 px-4 sm:px-6 md:grid-cols-12"
            >
                <div class="space-y-6 md:col-span-7">
                    <h2
                        class="text-xs font-black tracking-widest text-primary uppercase"
                    >
                        The ShieldSuite Standard
                    </h2>
                    <h3
                        class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl"
                    >
                        Enterprise compliance and scheduling safety
                    </h3>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        Our platform is engineered by
                        <strong>ShieldSuite Inc.</strong>, a recognized leader
                        in operational compliance and safety SaaS platforms.
                        When you run your dispatching through us, you aren't
                        just getting an answering bot—you are deploying an
                        enterprise-grade backend infrastructure.
                    </p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex items-start gap-3">
                            <Database class="mt-1 h-5 w-5 text-indigo-500" />
                            <div>
                                <h4 class="text-sm font-bold text-foreground">
                                    Logical Shielding
                                </h4>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Global query isolation guarantees zero
                                    database cross-leakage.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Clock class="mt-1 h-5 w-5 text-emerald-500" />
                            <div>
                                <h4 class="text-sm font-bold text-foreground">
                                    1.5h Intelligent Buffers
                                </h4>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Travel time safety zones automatically
                                    injected between technician shifts.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <ShieldCheck class="mt-1 h-5 w-5 text-rose-500" />
                            <div>
                                <h4 class="text-sm font-bold text-foreground">
                                    HMAC Webhook Security
                                </h4>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Telephony integrations are sealed with
                                    SHA256 webhook request signing.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Cpu class="mt-1 h-5 w-5 text-primary" />
                            <div>
                                <h4 class="text-sm font-bold text-foreground">
                                    Low Latency Voice AI
                                </h4>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Ultra-fast transcription pipelines built for
                                    high conversion.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visual Accent Column -->
                <div class="md:col-span-5">
                    <div
                        class="relative mx-auto max-w-[320px] rounded-3xl border-4 border-b-8 border-indigo-500 bg-indigo-500/5 p-6 text-center dark:border-indigo-800 dark:bg-indigo-950/10"
                    >
                        <div
                            class="absolute -top-10 -right-10 -z-10 h-32 w-32 rounded-full bg-indigo-500/10 blur-2xl"
                        ></div>
                        <div
                            class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500 text-white shadow-md"
                        >
                            <PhoneCall class="h-7 w-7" />
                        </div>
                        <h4
                            class="text-lg font-black text-indigo-900 dark:text-indigo-300"
                        >
                            Never Miss a Customer
                        </h4>
                        <p
                            class="mt-2 text-xs leading-relaxed text-indigo-800 dark:text-indigo-400"
                        >
                            Let JustMascot secure your phone lines,
                            auto-route bookings, and grow your revenue today.
                        </p>
                        <Link
                            :href="register()"
                            class="mt-6 inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-black text-white uppercase shadow-md transition-all hover:bg-indigo-500 active:scale-95"
                        >
                            <span>Start Answering</span>
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
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
                    <Link :href="pricing()" class="hover:text-foreground"
                        >Pricing</Link
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
