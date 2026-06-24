<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    dashboard,
    login,
    register,
    home,
    about,
    pricing,
    contact,
} from '@/routes';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Check } from '@lucide/vue';
</script>

<template>
    <Head title="Pricing - businesscalls" />

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
                        class="text-sm font-semibold text-foreground hover:text-foreground"
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

        <!-- Pricing Intro -->
        <section
            class="mx-auto max-w-[800px] space-y-4 px-4 py-16 text-center sm:px-6"
        >
            <h1
                class="text-4xl font-extrabold tracking-tight text-foreground sm:text-5xl"
            >
                Affordable, Bold Plans for Every Team
            </h1>
            <p class="text-lg leading-relaxed text-muted-foreground">
                Save hundreds of hours in call-answering and dispatch overhead.
                Select the ideal tier for your contracting profile.
            </p>
        </section>

        <!-- Pricing Cards (Duolingo 3D Border Aesthetic) -->
        <section class="border-b bg-card py-12">
            <div
                class="container mx-auto grid max-w-[1000px] grid-cols-1 gap-8 px-4 sm:px-6 md:grid-cols-3"
            >
                <!-- Starter Plan -->
                <div
                    class="flex flex-col justify-between rounded-3xl border-4 border-b-8 border-slate-300 bg-background p-6 dark:border-slate-800"
                >
                    <div>
                        <span
                            class="text-xs font-black tracking-widest text-slate-500 uppercase"
                            >Starter</span
                        >
                        <h3 class="mt-2 text-2xl font-black">
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
                    class="relative flex -translate-y-2 transform flex-col justify-between rounded-3xl border-4 border-b-12 border-indigo-400 bg-background p-6 shadow-xl md:-translate-y-4"
                >
                    <div
                        class="absolute -top-3 right-6 rounded-full border border-indigo-600 bg-indigo-500 px-2 py-0.5 text-[9px] font-black tracking-wider text-white uppercase"
                    >
                        Popular
                    </div>
                    <div>
                        <span
                            class="text-xs font-black tracking-widest text-indigo-500 uppercase"
                            >Professional</span
                        >
                        <h3 class="mt-2 text-2xl font-black">
                            $99<span
                                class="text-xs font-semibold text-muted-foreground"
                                >/month</span
                            >
                        </h3>
                        <p
                            class="mt-2 text-xs font-medium text-muted-foreground"
                        >
                            Ideal for small teams with scheduling rules.
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
                    class="flex flex-col justify-between rounded-3xl border-4 border-b-8 border-slate-300 bg-background p-6 dark:border-slate-800"
                >
                    <div>
                        <span
                            class="text-xs font-black tracking-widest text-slate-500 uppercase"
                            >Enterprise</span
                        >
                        <h3 class="mt-2 text-2xl font-black">
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
                                Priority Telemetry API Routing
                            </li>
                            <li class="flex items-center gap-2">
                                <Check
                                    class="h-4 w-4 shrink-0 text-emerald-500"
                                />
                                Custom HMAC Key Integration
                            </li>
                        </ul>
                    </div>
                    <Link
                        :href="register()"
                        class="mt-8 rounded-xl border-2 border-b-6 border-slate-700 bg-slate-500 py-3 text-center text-xs font-black text-white uppercase shadow-md hover:bg-slate-400 active:mt-1 active:border-b-0"
                    >
                        Contact Sales
                    </Link>
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
                    <Link :href="contact()" class="hover:text-foreground"
                        >Contact</Link
                    >
                </div>
            </div>
        </footer>
    </div>
</template>
