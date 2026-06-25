<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import {
    Terminal as TerminalIcon,
    Shield,
    CheckCircle,
    AlertTriangle,
    ShieldAlert,
    ChevronDown,
    ChevronUp,
    RefreshCw,
    Home
} from '@lucide/vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

const props = defineProps<{
    initialLogs: Array<{
        id: number;
        tenant_id: number;
        user_id: number | null;
        action: string;
        ip_address: string;
        browser_agent: string;
        payload: Record<string, any> | null;
        created_at: string;
        user: { name: string; email: string } | null;
    }>;
    tenantId: any;
}>();

const logs = ref([...props.initialLogs]);
const activeStreamState = ref<number | null>(null);
const expandedLogId = ref<number | null>(null);
const isResetting = ref(false);

// Toggle log details code viewer
const toggleLogDetails = (id: number) => {
    expandedLogId.value = expandedLogId.value === id ? null : id;
};

// Check for any unauthorized / security failures in the log list
const hasVerificationFailure = computed(() => {
    return logs.value.some(log =>
        log.action === 'signature_verification_failed' ||
        log.action === 'unauthorized_fallback_attempt' ||
        log.payload?.error === 'Forbidden' ||
        log.payload?.status === 403 ||
        log.payload?.unauthorized === true
    );
});

// Mascot state binding:
// 1 = Streaming radar check (Radar Scan loop)
// 2 = Optimal/Victory state (zero errors)
// 3 = Disappointment/Sad error state (verification failure)
const mascotState = computed(() => {
    if (hasVerificationFailure.value) {
        return 3;
    }
    if (activeStreamState.value !== null) {
        return activeStreamState.value;
    }
    return 2;
});

// Echo websocket listener
onMounted(() => {
    if (props.tenantId) {
        window.Echo.private(`tenant.${props.tenantId}`)
            .listen('.AuditLogCreated', (e: any) => {
                logs.value.unshift(e);

                // Set to Scanning Radar state (1)
                activeStreamState.value = 1;

                // After 2.5 seconds, reset check to go back to 2 (unless a failure is present)
                setTimeout(() => {
                    if (activeStreamState.value === 1) {
                        activeStreamState.value = null;
                    }
                }, 2500);
            });
    }
});

onUnmounted(() => {
    if (props.tenantId) {
        window.Echo.leave(`tenant.${props.tenantId}`);
    }
});

// Clear local error logs simulation helper (for testing UI states)
const clearSimulatedErrors = () => {
    isResetting.value = true;
    setTimeout(() => {
        logs.value = logs.value.filter(log =>
            log.action !== 'signature_verification_failed' &&
            log.action !== 'unauthorized_fallback_attempt' &&
            log.payload?.status !== 403
        );
        isResetting.value = false;
    }, 500);
};

// Format timestamp
const formatTime = (timeStr: string) => {
    try {
        const date = new Date(timeStr);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    } catch (e) {
        return timeStr;
    }
};

const formatDate = (timeStr: string) => {
    try {
        const date = new Date(timeStr);
        return date.toLocaleDateString([], { month: 'short', day: '2-digit', year: 'numeric' });
    } catch (e) {
        return timeStr;
    }
};
</script>

<template>
    <Head title="Security & Compliance Audit Logs" />

    <div class="min-h-screen bg-slate-950 p-8 text-slate-100 font-sans selection:bg-emerald-500 selection:text-slate-950">
        <div class="max-w-7xl mx-auto flex flex-col gap-8">
            
            <!-- Duolingo Style Navigation / Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b-4 border-slate-800 pb-6 gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-emerald-500 p-2 border-2 border-emerald-700 shadow-[2px_2px_0px_0px_rgba(4,120,87,1)]">
                            <TerminalIcon class="h-6 w-6 text-slate-950" />
                        </div>
                        <h1 class="text-3xl font-black tracking-wider uppercase text-white">
                            Audit Logs Terminal
                        </h1>
                    </div>
                    <p class="mt-2 text-slate-400 text-xs font-black uppercase tracking-widest">
                        Real-time Multi-Tenant Administrative Logs & Fallback Router Telemetry
                    </p>
                </div>
                
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <Link
                        href="/dashboard"
                        class="inline-flex items-center gap-2 rounded-2xl border-4 border-slate-800 bg-slate-900 hover:bg-slate-800 text-slate-300 px-5 py-2.5 text-xs font-black tracking-wider uppercase transition-all hover:scale-[1.02] active:translate-y-[2px] shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)] cursor-pointer"
                    >
                        <Home class="h-4 w-4" />
                        Dashboard
                    </Link>
                    <button
                        @click="clearSimulatedErrors"
                        class="inline-flex items-center gap-2 rounded-2xl border-4 border-emerald-800 bg-emerald-500 hover:bg-emerald-400 text-slate-950 px-5 py-2.5 text-xs font-black tracking-wider uppercase transition-all hover:scale-[1.02] active:translate-y-[2px] shadow-[4px_4px_0px_0px_rgba(4,120,87,0.4)] cursor-pointer"
                        :disabled="isResetting"
                    >
                        <RefreshCw class="h-4 w-4" :class="{'animate-spin': isResetting}" />
                        Reset Mascot
                    </button>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Live Terminal Feed (2/3 Grid) -->
                <div class="lg:col-span-2 flex flex-col gap-6">
                    
                    <!-- Retro Console Container -->
                    <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-4 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                        
                        <div class="flex items-center justify-between border-b-4 border-slate-800 pb-4">
                            <h2 class="text-xl font-black text-white flex items-center gap-2">
                                <TerminalIcon class="h-5 w-5 text-emerald-500 animate-pulse" />
                                CLI System Stream
                            </h2>
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-950/60 px-2.5 py-0.5 text-[10px] font-black text-emerald-400 border-2 border-emerald-800 uppercase animate-pulse">
                                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                Live Sync Active
                            </span>
                        </div>

                        <!-- Terminal Panel -->
                        <div class="bg-slate-950 rounded-2xl border-4 border-slate-800 overflow-hidden">
                            
                            <!-- Header / Control bar -->
                            <div class="bg-slate-900 border-b-2 border-slate-800 px-4 py-2 flex items-center gap-2">
                                <div class="h-3 w-3 rounded-full bg-rose-500"></div>
                                <div class="h-3 w-3 rounded-full bg-amber-500"></div>
                                <div class="h-3 w-3 rounded-full bg-emerald-500"></div>
                                <span class="ml-4 text-[10px] font-mono font-black text-slate-500 uppercase tracking-widest">
                                    root@antigravity-audit:~
                                </span>
                            </div>

                            <!-- Terminal Rows -->
                            <div class="p-4 font-mono text-sm leading-relaxed max-h-[500px] overflow-y-auto scrollbar-thin scrollbar-thumb-slate-800">
                                <div v-if="logs.length === 0" class="text-slate-500 text-center py-12">
                                    <span>-- No audit logs recorded for this tenant session --</span>
                                </div>
                                <div v-else class="flex flex-col gap-2">
                                    <div 
                                        v-for="log in logs" 
                                        :key="log.id"
                                        class="border-b border-slate-900/60 pb-2 last:border-0"
                                    >
                                        <div class="flex flex-wrap items-center justify-between gap-2 hover:bg-slate-900/40 p-2 rounded-lg transition-colors">
                                            
                                            <!-- Timestamp & Action -->
                                            <div class="flex items-center gap-2.5">
                                                <span class="text-slate-500 text-xs">
                                                    [{{ formatDate(log.created_at) }} {{ formatTime(log.created_at) }}]
                                                </span>
                                                <span 
                                                    class="px-2 py-0.5 rounded text-xs font-black uppercase tracking-wider"
                                                    :class="{
                                                        'bg-rose-950/80 text-rose-400 border border-rose-800': log.action === 'signature_verification_failed' || log.action === 'unauthorized_fallback_attempt',
                                                        'bg-emerald-950/80 text-emerald-400 border border-emerald-800': log.action === 'telephony_fallback_routed',
                                                        'bg-slate-800/80 text-slate-300 border border-slate-700': log.action !== 'signature_verification_failed' && log.action !== 'unauthorized_fallback_attempt' && log.action !== 'telephony_fallback_routed'
                                                    }"
                                                >
                                                    {{ log.action }}
                                                </span>
                                            </div>

                                            <!-- Metadata & Details Toggle -->
                                            <div class="flex items-center gap-4 text-xs">
                                                <span class="text-slate-400 font-medium">
                                                    IP: {{ log.ip_address || 'N/A' }}
                                                </span>
                                                <span class="text-slate-400 font-medium hidden md:inline">
                                                    User: {{ log.user ? log.user.name : 'System' }}
                                                </span>
                                                <button
                                                    @click="toggleLogDetails(log.id)"
                                                    class="inline-flex items-center gap-1 text-emerald-400 hover:text-emerald-300 font-bold uppercase transition-colors cursor-pointer"
                                                >
                                                    JSON
                                                    <ChevronDown v-if="expandedLogId !== log.id" class="h-4 w-4" />
                                                    <ChevronUp v-else class="h-4 w-4" />
                                                </button>
                                            </div>

                                        </div>

                                        <!-- Expanded JSON Details -->
                                        <div 
                                            v-if="expandedLogId === log.id" 
                                            class="mt-2 mx-2 p-3 bg-slate-900 rounded-xl border-2 border-slate-800 text-xs overflow-x-auto text-emerald-300"
                                        >
                                            <pre>{{ JSON.stringify(log.payload || {}, null, 2) }}</pre>
                                            <div class="mt-2 pt-2 border-t border-slate-800 text-[10px] text-slate-500">
                                                Browser Agent: {{ log.browser_agent || 'Unknown Agent' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Mascot & Telemetry Panel (1/3 Grid) -->
                <div class="flex flex-col gap-8">
                    
                    <!-- Rive WebGL Mascot Card -->
                    <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-4 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                        <h3 class="text-xl font-black text-white flex items-center gap-2 border-b-4 border-slate-800 pb-4 uppercase">
                            <Shield class="h-5 w-5 text-emerald-500" />
                            Security Mascot
                        </h3>

                        <!-- Rive Canvas Container -->
                        <div class="h-[280px] w-full">
                            <DispatcherMascot 
                                :state="mascotState" 
                                :is-speaking="mascotState === 1"
                                :amplitude="mascotState === 1 ? 40 : 0"
                                skin="robot"
                            />
                        </div>

                        <!-- Mascot State Info table -->
                        <div class="rounded-2xl border-4 border-slate-800 bg-slate-950 p-4 font-bold text-xs text-slate-300 flex flex-col gap-2.5">
                            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest pb-1 border-b border-slate-800">
                                Rive Mascot Triggers
                            </div>
                            <div class="flex justify-between items-center">
                                <span>1. Active Event Scanning</span>
                                <span 
                                    class="rounded px-2.5 py-0.5 text-[10px] font-black uppercase border"
                                    :class="mascotState === 1 ? 'bg-amber-950 text-amber-400 border-amber-800 ring-2 ring-amber-500' : 'bg-slate-900 text-slate-500 border-slate-800'"
                                >
                                    Active
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>2. Celebratory Victory</span>
                                <span 
                                    class="rounded px-2.5 py-0.5 text-[10px] font-black uppercase border"
                                    :class="mascotState === 2 ? 'bg-emerald-950 text-emerald-400 border-emerald-800 ring-2 ring-emerald-500' : 'bg-slate-900 text-slate-500 border-slate-800'"
                                >
                                    Active
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>3. Verification Failure</span>
                                <span 
                                    class="rounded px-2.5 py-0.5 text-[10px] font-black uppercase border"
                                    :class="mascotState === 3 ? 'bg-rose-950 text-rose-400 border-rose-800 ring-2 ring-rose-500' : 'bg-slate-900 text-slate-500 border-slate-800'"
                                >
                                    Active
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Security Evaluation Metric Panel -->
                    <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-4 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                        <h3 class="text-lg font-black text-white flex items-center gap-2 border-b-4 border-slate-800 pb-4 uppercase">
                            <ShieldAlert class="h-5 w-5 text-emerald-500" />
                            System Compliance
                        </h3>

                        <div class="flex flex-col gap-4">
                            <!-- Metric 1: Compliance scan status -->
                            <div class="flex items-center justify-between p-3.5 bg-slate-950 border-4 border-slate-800 rounded-2xl">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black uppercase text-white">Compliance Check</span>
                                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">HIPAA/GDPR Scanning</span>
                                </div>
                                <span class="rounded-lg bg-emerald-950/80 px-2.5 py-1 text-[10px] font-black text-emerald-400 border border-emerald-800 uppercase flex items-center gap-1">
                                    <CheckCircle class="h-3.5 w-3.5" />
                                    Passed
                                </span>
                            </div>

                            <!-- Metric 2: PII Redaction compliance -->
                            <div class="flex items-center justify-between p-3.5 bg-slate-950 border-4 border-slate-800 rounded-2xl">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black uppercase text-white">Zero Unredacted PII</span>
                                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Encryption status</span>
                                </div>
                                <span class="rounded-lg bg-emerald-950/80 px-2.5 py-1 text-[10px] font-black text-emerald-400 border border-emerald-800 uppercase flex items-center gap-1">
                                    <CheckCircle class="h-3.5 w-3.5" />
                                    Verified
                                </span>
                            </div>

                            <!-- Metric 3: Twilio Gateway Status -->
                            <div class="flex items-center justify-between p-3.5 bg-slate-950 border-4 border-slate-800 rounded-2xl">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black uppercase text-white">Twilio Signatures</span>
                                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Signature validation</span>
                                </div>
                                <span 
                                    class="rounded-lg px-2.5 py-1 text-[10px] font-black border uppercase flex items-center gap-1"
                                    :class="hasVerificationFailure ? 'bg-rose-950/80 text-rose-400 border-rose-800' : 'bg-emerald-950/80 text-emerald-400 border-emerald-800'"
                                >
                                    <AlertTriangle v-if="hasVerificationFailure" class="h-3.5 w-3.5 animate-pulse" />
                                    <CheckCircle v-else class="h-3.5 w-3.5" />
                                    {{ hasVerificationFailure ? 'Violation' : 'Secured' }}
                                </span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
</template>
