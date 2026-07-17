<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import SeoHead from '@/components/SeoHead.vue';
import {
    home,
    about,
    pricing,
    contact,
    privacy,
    terms,
    login,
    register,
    dashboard,
} from '@/routes';
</script>

<template>
    <SeoHead
        title="Terms of Service | JustMascot"
        description="Read the Terms of Service for JustMascot, a product of ShieldSuite Inc. Understand licensing, subscription fees, and scheduling SLA rules."
        keywords="terms of service, billing terms, SaaS license, contractor dispatcher SLA"
    />

    <div
        class="min-h-screen bg-slate-50 font-sans text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    >
        <!-- Header -->
        <header
            class="sticky top-0 z-40 w-full border-b bg-background/95 backdrop-blur-md supports-[backdrop-filter]:bg-background/60"
        >
            <div
                class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6"
            >
                <Link :href="home()" class="flex items-center gap-2">
                    <AppLogoIcon
                        class="h-10 w-10 shrink-0"
                    />
                    <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white"
                        >Just<span class="text-emerald-500 dark:text-emerald-400">Mascot</span></span
                    >
                </Link>

                <nav class="hidden items-center gap-6 md:flex">
                    <Link
                        :href="home()"
                        class="text-sm font-semibold text-muted-foreground hover:text-foreground"
                        >Home</Link
                    >
                    <Link
                        :href="about()"
                        class="text-sm font-semibold text-muted-foreground hover:text-foreground"
                        >About</Link
                    >
                    <Link
                        :href="pricing()"
                        class="text-sm font-semibold text-muted-foreground hover:text-foreground"
                        >Pricing</Link
                    >
                    <Link
                        :href="contact()"
                        class="text-sm font-semibold text-muted-foreground hover:text-foreground"
                        >Contact</Link
                    >
                </nav>

                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary/90"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="text-sm font-medium text-muted-foreground hover:text-foreground"
                            >Log in</Link
                        >
                        <Link
                            :href="register()"
                            class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary/90"
                            >Get Started</Link
                        >
                    </template>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="container mx-auto max-w-[800px] px-4 py-16 sm:px-6">
            <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">
                Terms of Service
            </h1>
            <p class="mt-2 text-xs text-muted-foreground">
                Last Updated: July 13, 2026
            </p>

            <div
                class="prose prose-slate dark:prose-invert mt-8 max-w-none space-y-6 text-sm leading-relaxed text-muted-foreground"
            >
                <p>
                    Welcome to <strong>JustMascot</strong>. These Terms of
                    Service ("Terms") govern your access to and use of our
                    website, services, and software apps. The service is owned,
                    developed, and operated by <strong>ShieldSuite Inc.</strong>
                    By registering for an account or using the platform, you
                    agree to be bound by these Terms.
                </p>

                <hr class="my-6 border-t" />

                <h2 class="text-xl font-bold text-foreground">
                    1. Account Registration & Use
                </h2>
                <p>
                    You must provide accurate company and billing details to
                    initialize a tenant environment. You are solely responsible
                    for all dispatch, scheduling, and call activities linked to
                    your account, and you must verify that call recordings
                    comply with local wiretapping and consent regulations in
                    your service jurisdiction.
                </p>

                <h2 class="text-xl font-bold text-foreground">
                    2. Service Level Agreement (SLA) & Buffer Calculations
                </h2>
                <p>
                    Our system uses a 1.5-hour geographic travel buffer and
                    skill tags to match appointments. While we strive for
                    absolute accuracy in scheduling routing, ShieldSuite Inc.
                    does not guarantee that schedules will always prevent
                    overlaps due to external navigation changes, internet
                    latency, or telephony webhook dropouts.
                </p>

                <h2 class="text-xl font-bold text-foreground">
                    3. Payments & Billing
                </h2>
                <p>
                    Subscriptions are billed in advance on a recurring monthly
                    or annual basis depending on your selected plan. Failure to
                    settle payments through Stripe billing will lead to account
                    suspension and pausing of AI receptionist services. All
                    payments are non-refundable except as specified by
                    ShieldSuite Inc. compliance policies.
                </p>

                <h2 class="text-xl font-bold text-foreground">
                    4. Limitation of Liability
                </h2>
                <p>
                    To the maximum extent permitted by law, ShieldSuite Inc.
                    shall not be liable for any indirect, incidental, or
                    consequential damages (including lost profits, lost
                    contractor jobs, or data leaks) arising out of or relating
                    to the use of JustMascot software.
                </p>

                <h2 class="text-xl font-bold text-foreground">
                    5. Governing Law
                </h2>
                <p>
                    These Terms are governed by and construed in accordance with
                    the laws of the State of California, without regard to
                    conflict of law principles.
                </p>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-12 border-t bg-background py-8">
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
                    <Link :href="pricing()" class="hover:text-foreground"
                        >Pricing</Link
                    >
                    <Link :href="contact()" class="hover:text-foreground"
                        >Contact</Link
                    >
                    <Link :href="privacy()" class="hover:text-foreground"
                        >Privacy</Link
                    >
                    <Link
                        :href="terms()"
                        class="text-foreground hover:text-foreground"
                        >Terms</Link
                    >
                </div>
            </div>
        </footer>
    </div>
</template>
