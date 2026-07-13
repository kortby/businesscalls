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
        title="Privacy Policy | businesscalls"
        description="Read the privacy policy for businesscalls (built by ShieldSuite Inc.). Learn how we isolate customer data, manage telephony webhooks, and secure technician schedules."
        keywords="privacy policy, data isolation, multi-tenant security, call recording privacy"
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
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm"
                    >
                        <AppLogoIcon
                            class="h-5 w-5 fill-current text-primary-foreground"
                        />
                    </div>
                    <span class="text-xl font-bold tracking-tight"
                        >businesscalls</span
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
                Privacy Policy
            </h1>
            <p class="mt-2 text-xs text-muted-foreground">
                Last Updated: July 13, 2026
            </p>

            <div
                class="prose prose-slate dark:prose-invert mt-8 max-w-none space-y-6 text-sm leading-relaxed text-muted-foreground"
            >
                <p>
                    At <strong>businesscalls</strong> (a software service
                    developed and operated by
                    <strong>ShieldSuite Inc.</strong>), we take the security and
                    privacy of your data seriously. This Privacy Policy outlines
                    how we collect, process, isolate, and protect your
                    information when you use our AI voice receptionist and
                    automated scheduling platform.
                </p>

                <hr class="my-6 border-t" />

                <h2 class="text-xl font-bold text-foreground">
                    1. Information We Collect
                </h2>
                <p>
                    To provide dynamic scheduling, telephony integrations, and
                    dispatch management for contractors, we collect:
                </p>
                <ul class="list-disc space-y-2 pl-5">
                    <li>
                        <strong>Account Information:</strong> Names, emails,
                        passwords, and tenant profiles (e.g. company name,
                        business details).
                    </li>
                    <li>
                        <strong>Technician & Availability Profiles:</strong>
                        Calendars, skill tags (e.g., HVAC, plumbing,
                        electrical), scheduled shifts, and dispatch zones.
                    </li>
                    <li>
                        <strong>Telephony & Call Data:</strong> Incoming call
                        logs, metadata (timestamps, caller ID), webhook
                        signatures, call duration, and transcripts generated by
                        voice assistants.
                    </li>
                </ul>

                <h2 class="text-xl font-bold text-foreground">
                    2. Data Isolation & Security
                </h2>
                <p>
                    We employ strict logical multi-tenant database isolation.
                    Each company operates in an isolated scope, and all Eloquent
                    queries are restricted by global tenant scopes. This ensures
                    that scheduling, shift, technician, and call log data remain
                    fully protected and invisible to other tenants.
                </p>
                <p>
                    Additionally, all incoming telephony webhooks are protected
                    with HMAC-SHA256 signature verification computed using
                    tenant secret keys, preventing spoofing or unauthorized
                    write operations.
                </p>

                <h2 class="text-xl font-bold text-foreground">
                    3. How We Use Your Information
                </h2>
                <p>We use the collected information solely to:</p>
                <ul class="list-disc space-y-2 pl-5">
                    <li>
                        Execute voice dispatch workflows (match incoming calls
                        to technician skills and shifts).
                    </li>
                    <li>
                        Compute travel time buffers (including the 1.5-hour
                        calendar buffer) to prevent double bookings.
                    </li>
                    <li>
                        Generate diagnostic executive reports and maintain
                        supervisor HUD logs.
                    </li>
                    <li>
                        Process subscription billings secure via payment
                        platforms (e.g., Stripe).
                    </li>
                </ul>

                <h2 class="text-xl font-bold text-foreground">4. Contact Us</h2>
                <p>
                    If you have questions or concerns regarding this policy,
                    security protocols, or ShieldSuite's platform architecture,
                    contact our compliance team:
                </p>
                <p>
                    <strong>Email:</strong> compliance@shieldsuite.com<br />
                    <strong>Address:</strong> ShieldSuite Inc., 100 Pine Street,
                    San Francisco, CA 94111
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
                    <Link
                        :href="privacy()"
                        class="text-foreground hover:text-foreground"
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
