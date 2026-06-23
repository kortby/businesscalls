<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register, home, about, pricing, contact } from '@/routes';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { 
    Users, 
    Target, 
    Rocket,
    CheckCircle,
    Bot
} from '@lucide/vue';
</script>

<template>
    <Head title="About Us - businesscalls" />

    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-sans">
        
        <!-- Header -->
        <header class="sticky top-0 z-40 w-full border-b bg-background/95 backdrop-blur-md supports-[backdrop-filter]:bg-background/60">
            <div class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6">
                <Link :href="home()" class="flex items-center gap-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm">
                        <AppLogoIcon class="h-5 w-5 text-primary-foreground fill-current" />
                    </div>
                    <span class="text-xl font-bold tracking-tight">businesscalls</span>
                </Link>

                <nav class="hidden md:flex items-center gap-6">
                    <Link :href="home()" class="text-sm font-semibold text-muted-foreground hover:text-foreground">Home</Link>
                    <Link :href="about()" class="text-sm font-semibold text-foreground hover:text-foreground">About</Link>
                    <Link :href="pricing()" class="text-sm font-semibold text-muted-foreground hover:text-foreground">Pricing</Link>
                    <Link :href="contact()" class="text-sm font-semibold text-muted-foreground hover:text-foreground">Contact</Link>
                </nav>

                <div class="flex items-center gap-4">
                    <Link v-if="$page.props.auth.user" :href="dashboard()" class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary/90">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="login()" class="text-sm font-medium text-muted-foreground hover:text-foreground">Log in</Link>
                        <Link :href="register()" class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary/90">Get Started</Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="py-16 md:py-24 border-b relative overflow-hidden bg-indigo-500/10 dark:bg-indigo-950/20">
            <div class="container mx-auto px-4 sm:px-6 text-center max-w-[800px] space-y-4">
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl text-foreground">
                    Our Mission: Zero Missed Service Calls
                </h1>
                <p class="text-lg text-muted-foreground leading-relaxed">
                    We empower trade contractors (Plumbers, HVAC technicians, Electricians) with voice AI dispatches that listen, evaluate availability shifts, manage buffers, and schedule bookings automatically.
                </p>
            </div>
        </section>

        <!-- Mission & Values (Duolingo Aesthetic Card Grid) -->
        <section class="py-16 bg-card border-b">
            <div class="container mx-auto px-4 sm:px-6 grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Card 1 -->
                <div class="border-4 border-b-8 border-emerald-400 bg-emerald-500/5 dark:bg-emerald-950/10 dark:border-emerald-800 rounded-3xl p-6 flex flex-col items-center text-center">
                    <div class="h-12 w-12 rounded-xl bg-emerald-500 text-white flex items-center justify-center border-2 border-b-4 border-emerald-700 shadow-sm mb-4">
                        <Users class="h-6 w-6" />
                    </div>
                    <h3 class="text-xl font-black text-emerald-900 dark:text-emerald-300">Contractor Focused</h3>
                    <p class="text-sm text-emerald-800 dark:text-emerald-400 mt-2 leading-relaxed">
                        Built specifically for hands-on businesses. We understand dispatching logic, travel times, and emergency priorities.
                    </p>
                </div>

                <!-- Card 2 -->
                <div class="border-4 border-b-8 border-indigo-400 bg-indigo-500/5 dark:bg-indigo-950/10 dark:border-indigo-800 rounded-3xl p-6 flex flex-col items-center text-center">
                    <div class="h-12 w-12 rounded-xl bg-indigo-500 text-white flex items-center justify-center border-2 border-b-4 border-indigo-700 shadow-sm mb-4">
                        <Bot class="h-6 w-6" />
                    </div>
                    <h3 class="text-xl font-black text-indigo-900 dark:text-indigo-300">Voice AI Intelligence</h3>
                    <p class="text-sm text-indigo-800 dark:text-indigo-400 mt-2 leading-relaxed">
                        Leveraging speech recognition and JSON telemetry to validate technician skills and schedules in real-time.
                    </p>
                </div>

                <!-- Card 3 -->
                <div class="border-4 border-b-8 border-rose-400 bg-rose-500/5 dark:bg-rose-950/10 dark:border-rose-800 rounded-3xl p-6 flex flex-col items-center text-center">
                    <div class="h-12 w-12 rounded-xl bg-rose-500 text-white flex items-center justify-center border-2 border-b-4 border-rose-700 shadow-sm mb-4">
                        <Target class="h-6 w-6" />
                    </div>
                    <h3 class="text-xl font-black text-rose-900 dark:text-rose-300">100% Reliable</h3>
                    <p class="text-sm text-rose-800 dark:text-rose-400 mt-2 leading-relaxed">
                        Strict multi-tenant security structures ensure tenant settings, bookings, and schedules remain fully isolated.
                    </p>
                </div>

            </div>
        </section>

        <!-- Footer -->
        <footer class="py-8 bg-background border-t">
            <div class="container mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-semibold text-muted-foreground">
                <p>© 2026 businesscalls Inc. All rights reserved.</p>
                <div class="flex items-center gap-6">
                    <Link :href="home()" class="hover:text-foreground">Home</Link>
                    <Link :href="pricing()" class="hover:text-foreground">Pricing</Link>
                    <Link :href="contact()" class="hover:text-foreground">Contact</Link>
                </div>
            </div>
        </footer>

    </div>
</template>
