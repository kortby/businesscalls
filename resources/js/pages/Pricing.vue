<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register, home, about, pricing, contact } from '@/routes';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Check } from '@lucide/vue';
</script>

<template>
    <Head title="Pricing - businesscalls" />

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
                    <Link :href="about()" class="text-sm font-semibold text-muted-foreground hover:text-foreground">About</Link>
                    <Link :href="pricing()" class="text-sm font-semibold text-foreground hover:text-foreground">Pricing</Link>
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

        <!-- Pricing Intro -->
        <section class="py-16 text-center max-w-[800px] mx-auto space-y-4 px-4 sm:px-6">
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl text-foreground">
                Affordable, Bold Plans for Every Team
            </h1>
            <p class="text-lg text-muted-foreground leading-relaxed">
                Save hundreds of hours in call-answering and dispatch overhead. Select the ideal tier for your contracting profile.
            </p>
        </section>

        <!-- Pricing Cards (Duolingo 3D Border Aesthetic) -->
        <section class="py-12 bg-card border-b">
            <div class="container mx-auto px-4 sm:px-6 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-[1000px]">
                
                <!-- Starter Plan -->
                <div class="border-4 border-b-8 border-slate-300 dark:border-slate-800 rounded-3xl p-6 bg-background flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-black uppercase tracking-widest text-slate-500">Starter</span>
                        <h3 class="text-2xl font-black mt-2">$49<span class="text-xs text-muted-foreground font-semibold">/month</span></h3>
                        <p class="text-xs text-muted-foreground mt-2 font-medium">Perfect for small, single-technician setups.</p>
                        <hr class="my-4 border-slate-200 dark:border-slate-800" />
                        <ul class="space-y-2.5 text-xs text-muted-foreground font-semibold">
                            <li class="flex items-center gap-2"><Check class="h-4 w-4 text-emerald-500 shrink-0" /> 1 Active Technician</li>
                            <li class="flex items-center gap-2"><Check class="h-4 w-4 text-emerald-500 shrink-0" /> Webhook Telemetry</li>
                            <li class="flex items-center gap-2"><Check class="h-4 w-4 text-emerald-500 shrink-0" /> Basic AI Prompts</li>
                        </ul>
                    </div>
                    <Link :href="register()" class="mt-8 bg-slate-500 text-white font-black uppercase text-center text-xs py-3 rounded-xl border-2 border-b-6 border-slate-700 active:border-b-0 active:mt-1 shadow-md hover:bg-slate-400">
                        Get Started
                    </Link>
                </div>

                <!-- Pro Plan (Highlighted) -->
                <div class="border-4 border-b-12 border-indigo-400 rounded-3xl p-6 bg-background flex flex-col justify-between relative transform -translate-y-2 md:-translate-y-4 shadow-xl">
                    <div class="absolute -top-3 right-6 bg-indigo-500 text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-full border border-indigo-600 tracking-wider">
                        Popular
                    </div>
                    <div>
                        <span class="text-xs font-black uppercase tracking-widest text-indigo-500">Professional</span>
                        <h3 class="text-2xl font-black mt-2">$99<span class="text-xs text-muted-foreground font-semibold">/month</span></h3>
                        <p class="text-xs text-muted-foreground mt-2 font-medium">Ideal for small teams with scheduling rules.</p>
                        <hr class="my-4 border-indigo-100 dark:border-slate-800" />
                        <ul class="space-y-2.5 text-xs text-muted-foreground font-semibold">
                            <li class="flex items-center gap-2"><Check class="h-4 w-4 text-indigo-500 shrink-0" /> Up to 5 Technicians</li>
                            <li class="flex items-center gap-2"><Check class="h-4 w-4 text-indigo-500 shrink-0" /> 1.5-Hour Buffer Enforced</li>
                            <li class="flex items-center gap-2"><Check class="h-4 w-4 text-indigo-500 shrink-0" /> Custom System Prompt Override</li>
                            <li class="flex items-center gap-2"><Check class="h-4 w-4 text-indigo-500 shrink-0" /> Real-time Mascot Broadcasts</li>
                        </ul>
                    </div>
                    <Link :href="register()" class="mt-8 bg-indigo-500 text-white font-black uppercase text-center text-xs py-3 rounded-xl border-2 border-b-6 border-indigo-700 active:border-b-0 active:mt-1 shadow-md hover:bg-indigo-400">
                        Try 14 Days Free
                    </Link>
                </div>

                <!-- Enterprise Plan -->
                <div class="border-4 border-b-8 border-slate-300 dark:border-slate-800 rounded-3xl p-6 bg-background flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-black uppercase tracking-widest text-slate-500">Enterprise</span>
                        <h3 class="text-2xl font-black mt-2">$199<span class="text-xs text-muted-foreground font-semibold">/month</span></h3>
                        <p class="text-xs text-muted-foreground mt-2 font-medium">Custom multi-tenant setup for busy companies.</p>
                        <hr class="my-4 border-slate-200 dark:border-slate-800" />
                        <ul class="space-y-2.5 text-xs text-muted-foreground font-semibold">
                            <li class="flex items-center gap-2"><Check class="h-4 w-4 text-emerald-500 shrink-0" /> Unlimited Technicians</li>
                            <li class="flex items-center gap-2"><Check class="h-4 w-4 text-emerald-500 shrink-0" /> Priority Telemetry API Routing</li>
                            <li class="flex items-center gap-2"><Check class="h-4 w-4 text-emerald-500 shrink-0" /> Custom HMAC Key Integration</li>
                        </ul>
                    </div>
                    <Link :href="register()" class="mt-8 bg-slate-500 text-white font-black uppercase text-center text-xs py-3 rounded-xl border-2 border-b-6 border-slate-700 active:border-b-0 active:mt-1 shadow-md hover:bg-slate-400">
                        Contact Sales
                    </Link>
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
                    <Link :href="contact()" class="hover:text-foreground">Contact</Link>
                </div>
            </div>
        </footer>

    </div>
</template>
