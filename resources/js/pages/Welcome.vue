<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register, home, about, pricing, contact } from '@/routes';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Badge } from '@/components/ui/badge';
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
    Play
} from '@lucide/vue';

const mascotState = ref<number>(0);
const simulatedMessage = ref<string>('Mascot is idle, monitoring channels...');

const simulateState = (stateNum: number) => {
    mascotState.value = stateNum;
    if (stateNum === 0) {
        simulatedMessage.value = 'Mascot is idle, monitoring channels...';
    } else if (stateNum === 1) {
        simulatedMessage.value = 'AI Receptionist is on a live call analyzing technician skills & availabilities...';
    } else if (stateNum === 2) {
        simulatedMessage.value = 'Success! Booking confirmed for plumber shift with 1.5h travel buffer applied.';
    } else if (stateNum === 3) {
        simulatedMessage.value = 'Conflict blocked: Request overlaps with another booking or technician is out-of-shift.';
    }
};
</script>

<template>
    <Head title="AI Voice Receptionist & Dispatch for Contractors" />

    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-sans selection:bg-primary selection:text-primary-foreground">
        
        <!-- Header -->
        <header class="sticky top-0 z-40 w-full border-b bg-background/95 backdrop-blur-md supports-[backdrop-filter]:bg-background/60">
            <div class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6">
                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm">
                        <AppLogoIcon class="h-5 w-5 text-primary-foreground fill-current" />
                    </div>
                    <span class="text-xl font-bold tracking-tight">businesscalls</span>
                </div>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center gap-6">
                    <Link :href="home()" class="text-sm font-semibold text-foreground hover:text-foreground">Home</Link>
                    <Link :href="about()" class="text-sm font-semibold text-muted-foreground hover:text-foreground">About</Link>
                    <Link :href="pricing()" class="text-sm font-semibold text-muted-foreground hover:text-foreground">Pricing</Link>
                    <Link :href="contact()" class="text-sm font-semibold text-muted-foreground hover:text-foreground">Contact</Link>
                </nav>

                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        Go to Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-flex h-9 items-center justify-center rounded-md px-4 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            Get Started
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero Section with Live Simulator -->
        <section class="relative py-12 md:py-24 lg:py-32 overflow-hidden border-b">
            <!-- Background Gradients -->
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(45rem_50rem_at_top,var(--color-slate-100),theme(colors.slate.50))] dark:bg-[radial-gradient(45rem_50rem_at_top,var(--color-slate-900),theme(colors.slate.950))]"></div>
            
            <div class="container mx-auto px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <!-- Hero Left Column -->
                <div class="lg:col-span-7 flex flex-col justify-center space-y-6">
                    <div class="inline-flex w-fit items-center gap-1.5 rounded-full border bg-background px-3 py-1 text-xs font-semibold text-muted-foreground shadow-xs">
                        <Sparkles class="h-3.5 w-3.5 text-primary" />
                        <span>Interactive Rive Mascot Live Preview</span>
                    </div>

                    <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl text-foreground">
                        AI-driven receptionist & smart scheduling for contractors
                    </h1>
                    
                    <p class="text-lg text-muted-foreground max-w-[600px] leading-relaxed">
                        Say goodbye to missed calls and double bookings. **businesscalls** automatically answers incoming calls, matches technician skills, validates active shifts, enforces travel buffers, and schedules bookings dynamically.
                    </p>

                    <div class="flex flex-wrap gap-4 pt-2">
                        <Link
                            :href="register()"
                            class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-6 py-2.5 text-base font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                        >
                            Start 14-Day Free Trial
                        </Link>
                        <a
                            href="#features"
                            class="inline-flex h-11 items-center justify-center rounded-md border bg-background px-6 py-2.5 text-base font-medium text-muted-foreground shadow-xs transition-all hover:bg-accent hover:text-accent-foreground"
                        >
                            Explore Platform Features
                        </a>
                    </div>
                </div>

                <!-- Hero Right Column (Mascot Simulator Card) -->
                <div class="lg:col-span-5 flex flex-col items-center">
                    <div class="w-full max-w-[420px] rounded-2xl border bg-card p-6 shadow-xl relative dark:border-slate-800">
                        <div class="flex items-center justify-between mb-4 border-b pb-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Interactive AI Simulator</span>
                            <Badge variant="outline" class="text-[9px] font-bold text-primary uppercase bg-primary/10 border-primary/20 px-1.5 py-0.5 rounded">
                                Live Interactive Demo
                            </Badge>
                        </div>

                        <!-- Render Canvas Targeting Mascot -->
                        <div class="aspect-square w-full max-w-[260px] mx-auto bg-accent/25 rounded-xl border p-2 mb-4 relative overflow-hidden dark:bg-slate-900/40">
                            <DispatcherMascot :state="mascotState" />
                        </div>

                        <!-- Simulated Event Details -->
                        <div class="bg-accent/40 border p-3 rounded-lg text-xs font-medium text-muted-foreground min-h-[50px] mb-4 flex items-center justify-center text-center leading-normal">
                            {{ simulatedMessage }}
                        </div>

                        <!-- Simulation Action Triggers -->
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                @click="simulateState(1)"
                                class="inline-flex h-8 items-center justify-center rounded-md border text-xs font-semibold hover:bg-accent text-foreground transition-all cursor-pointer"
                            >
                                Simulate Call
                            </button>
                            <button
                                @click="simulateState(2)"
                                class="inline-flex h-8 items-center justify-center rounded-md border text-xs font-semibold bg-emerald-500/10 border-emerald-500/20 text-emerald-600 hover:bg-emerald-500/20 dark:text-emerald-400 cursor-pointer"
                            >
                                Simulate Booking
                            </button>
                            <button
                                @click="simulateState(3)"
                                class="inline-flex h-8 items-center justify-center rounded-md border text-xs font-semibold bg-rose-500/10 border-rose-500/20 text-rose-600 hover:bg-rose-500/20 dark:text-rose-400 cursor-pointer"
                            >
                                Simulate Overlap
                            </button>
                            <button
                                @click="simulateState(0)"
                                class="inline-flex h-8 items-center justify-center rounded-md border text-xs font-semibold text-muted-foreground hover:bg-accent cursor-pointer"
                            >
                                Reset State
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Grid Section -->
        <section id="features" class="py-16 md:py-24 bg-card border-b dark:bg-slate-900/20">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="text-center max-w-[800px] mx-auto mb-16 space-y-3">
                    <h2 class="text-xs font-black uppercase tracking-widest text-primary">Core Architecture</h2>
                    <h3 class="text-3xl font-bold tracking-tight sm:text-4xl text-foreground">
                        Production-grade scheduling engine
                    </h3>
                    <p class="text-muted-foreground leading-relaxed">
                        Our platform is engineered for trade contractors (HVAC, Plumbing, Electrical) to ensure absolute data isolation and schedule verification.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Feature 1: Tenancy Scope -->
                    <div class="flex flex-col p-6 border rounded-xl bg-background shadow-xs hover:shadow-md transition-shadow">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary mb-4">
                            <UserCheck class="h-5 w-5" />
                        </div>
                        <h4 class="text-lg font-bold text-foreground mb-2">Tenant Database Isolation</h4>
                        <p class="text-sm text-muted-foreground leading-relaxed">
                            Global scopes isolate queries to active session sub-tenants. Multi-tenant database rules are validated at every layer of the Eloquent model lifecycle.
                        </p>
                    </div>

                    <!-- Feature 2: Overlap Buffer -->
                    <div class="flex flex-col p-6 border rounded-xl bg-background shadow-xs hover:shadow-md transition-shadow">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary mb-4">
                            <Clock class="h-5 w-5" />
                        </div>
                        <h4 class="text-lg font-bold text-foreground mb-2">1.5h Overlap Buffer</h4>
                        <p class="text-sm text-muted-foreground leading-relaxed">
                            Automatically validates scheduled appointments and blocks technician dispatches that fall within 1.5 hours of another booking to account for travel.
                        </p>
                    </div>

                    <!-- Feature 3: HMAC Signatures -->
                    <div class="flex flex-col p-6 border rounded-xl bg-background shadow-xs hover:shadow-md transition-shadow">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary mb-4">
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <h4 class="text-lg font-bold text-foreground mb-2">HMAC Webhook Security</h4>
                        <p class="text-sm text-muted-foreground leading-relaxed">
                            Secure your API routes from fake telephony requests. All incoming payload headers are checked against SHA256 hashes generated from the tenant secret key.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-8 bg-background border-t">
            <div class="container mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-semibold text-muted-foreground">
                <p>© 2026 businesscalls Inc. All rights reserved.</p>
                <div class="flex items-center gap-6">
                    <Link :href="home()" class="hover:text-foreground">Home</Link>
                    <Link :href="about()" class="hover:text-foreground">About</Link>
                    <Link :href="pricing()" class="hover:text-foreground">Pricing</Link>
                    <Link :href="contact()" class="hover:text-foreground">Contact</Link>
                </div>
            </div>
        </footer>

    </div>
</template>
