<script setup lang="ts">
import {
    ShieldCheck,
    Database,
    Clock,
    Lock,
    Cpu,
    Server,
    Activity,
    CheckCircle2,
    Zap,
    Key,
    Layers,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';

const pillars = [
    {
        icon: Database,
        title: 'Multi-Tenant Database Isolation',
        subtitle: 'Laravel Eloquent Global Scopes',
        description: 'Every SQL query is automatically scoped to the active tenant ID. Enterprise-grade logical data separation prevents cross-tenant data leaks or unauthorized dispatch access.',
        badge: 'Zero Data Leaks Guarantee',
        gradient: 'from-blue-500 to-indigo-600',
        borderColor: 'hover:border-blue-500/40',
        textColor: 'text-blue-600 dark:text-blue-400',
        bgGlow: 'bg-blue-500/10 dark:bg-blue-500/20',
        techDetails: ['Eloquent Global Scopes', 'Tenant ID Foreign Key Bounds', 'Automated Session Isolation'],
    },
    {
        icon: Clock,
        title: '1.5h Overlap & Travel Buffer Engine',
        subtitle: 'Algorithmic Conflict Protection',
        description: 'Protects trade technicians from double bookings during peak traffic hours. The dispatch engine automatically calculates a 1.5-hour buffer between consecutive bookings.',
        badge: 'Automated Travel Triage',
        gradient: 'from-emerald-400 to-teal-600',
        borderColor: 'hover:border-emerald-500/40',
        textColor: 'text-emerald-600 dark:text-emerald-400',
        bgGlow: 'bg-emerald-500/10 dark:bg-emerald-500/20',
        techDetails: ['Real-time Shift Overlap Checks', 'Travel Time Buffer Margin', 'Dynamic Skill-Shift Matching'],
    },
    {
        icon: Lock,
        title: 'HMAC-SHA256 Signature Verification',
        subtitle: 'Cryptographic Telephony Security',
        description: 'All incoming telephony webhooks (Twilio / WebRTC) require valid HMAC SHA-256 request signatures computed against your company’s unique secret key.',
        badge: 'Anti-Spoofing Webhooks',
        gradient: 'from-amber-400 to-orange-500',
        borderColor: 'hover:border-amber-500/40',
        textColor: 'text-amber-600 dark:text-amber-400',
        bgGlow: 'bg-amber-500/10 dark:bg-amber-500/20',
        techDetails: ['SHA-256 Signature Headers', 'Timestamp Nonce Validation', 'Webhook Spoofing Defense'],
    },
    {
        icon: Cpu,
        title: 'Sub-Second Voice AI & 99.99% SLA',
        subtitle: 'Ultra-Low Latency Streaming',
        description: 'High-availability voice streaming pipeline engineered with sub-second TTS synthesis, immediate call quality scoring, and enterprise failover redundancy.',
        badge: '99.99% Uptime Guarantee',
        gradient: 'from-purple-500 to-pink-600',
        borderColor: 'hover:border-purple-500/40',
        textColor: 'text-purple-600 dark:text-purple-400',
        bgGlow: 'bg-purple-500/10 dark:bg-purple-500/20',
        techDetails: ['Sub-300ms Speech Synthesis', 'WebRTC Telephony Nodes', 'Call Quality Score Telemetry'],
    },
];
</script>

<template>
    <section id="technical-reliability" class="border-b bg-card/60 py-16 dark:bg-slate-900/30 md:py-24">
        <div class="container mx-auto px-4 sm:px-6">
            <!-- Section Header -->
            <div class="mx-auto mb-16 max-w-[850px] space-y-4 text-center">
                <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3.5 py-1 text-xs font-bold text-emerald-600 uppercase tracking-wider dark:text-emerald-400">
                    <ShieldCheck class="h-4 w-4 text-emerald-500" />
                    <span>Technical Reliability & Infrastructure Security</span>
                </div>

                <h2 class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl md:text-5xl">
                    Engineered for <span class="bg-gradient-to-r from-emerald-500 via-teal-400 to-indigo-500 bg-clip-text text-transparent">Zero Down-Time & Total Isolation</span>
                </h2>

                <p class="text-base leading-relaxed text-muted-foreground sm:text-lg">
                    Trade contractors rely on 24/7 call management. Our platform is built on enterprise Laravel architecture to guarantee multi-tenant security, scheduling precision, and instant telephony response.
                </p>
            </div>

            <!-- Pillars Grid -->
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                <div
                    v-for="pillar in pillars"
                    :key="pillar.title"
                    class="group relative flex flex-col justify-between rounded-2xl border bg-background/90 p-8 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900/50"
                    :class="pillar.borderColor"
                >
                    <div>
                        <!-- Header & Icon -->
                        <div class="mb-6 flex items-center justify-between">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl p-3 shadow-sm transition-transform duration-300 group-hover:scale-110" :class="pillar.bgGlow">
                                <component :is="pillar.icon" class="h-7 w-7" :class="pillar.textColor" />
                            </div>
                            <Badge variant="outline" class="rounded-lg border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                {{ pillar.badge }}
                            </Badge>
                        </div>

                        <h3 class="mb-1 text-2xl font-bold tracking-tight text-foreground">
                            {{ pillar.title }}
                        </h3>
                        <p class="mb-4 text-xs font-bold text-muted-foreground uppercase tracking-wider">
                            {{ pillar.subtitle }}
                        </p>
                        <p class="mb-6 text-sm leading-relaxed text-muted-foreground">
                            {{ pillar.description }}
                        </p>
                    </div>

                    <!-- Tech Bullet Details -->
                    <div class="border-t border-border/60 pt-4">
                        <div class="space-y-2">
                            <div v-for="detail in pillar.techDetails" :key="detail" class="flex items-center gap-2 text-xs font-semibold text-foreground/90">
                                <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-500" />
                                <span>{{ detail }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reliability SLA Stats Banner Bar -->
            <div class="mt-12 grid grid-cols-2 gap-4 rounded-2xl border bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 text-white shadow-xl sm:grid-cols-4 sm:p-8">
                <div class="space-y-1 text-center sm:text-left">
                    <p class="text-3xl font-extrabold text-emerald-400 sm:text-4xl">99.99%</p>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-300">Telephony Uptime</p>
                </div>
                <div class="space-y-1 text-center sm:text-left">
                    <p class="text-3xl font-extrabold text-indigo-300 sm:text-4xl">&lt; 300ms</p>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-300">Voice Synthesis Latency</p>
                </div>
                <div class="space-y-1 text-center sm:text-left">
                    <p class="text-3xl font-extrabold text-amber-400 sm:text-4xl">1.5 Hours</p>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-300">Travel Buffer Window</p>
                </div>
                <div class="space-y-1 text-center sm:text-left">
                    <p class="text-3xl font-extrabold text-sky-400 sm:text-4xl">100% Isolated</p>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-300">Tenant Data Security</p>
                </div>
            </div>
        </div>
    </section>
</template>
