<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Phone,
    ShieldAlert,
    CheckCircle2,
    Volume2,
    VolumeX,
    Database,
    TrendingUp,
    AlertTriangle,
    Play,
    Square,
    Radio,
    Sparkles,
    Clock,
    WifiOff,
} from '@lucide/vue';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useEcho } from '@laravel/echo-vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Live Monitor',
                href: '/admin/call-monitor',
            },
        ],
    },
});

const props = defineProps<{
    tenant: {
        id: number;
        name: string;
        slug: string;
        settings: Record<string, any>;
    };
    timingSettings: {
        startSpeakingPlan: number;
        stopSpeakingPlan: number;
    };
    spendUsage: number;
    spendLimit: number;
}>();

// Page state variables
const mascotState = ref<number>(1); // 0 = Idle, 1 = Scanning, 2 = Victory, 3 = Error
const mascotSpeaking = ref<boolean>(false);
const mascotAmplitude = ref<number>(0);
const activeSkin = ref<string>((props.tenant.settings?.active_skin as string) || 'standard');

// Local tracking of ongoing calls
interface Call {
    call_id: string;
    customer_name: string;
    customer_phone: string;
    status: 'ringing' | 'connected' | 'degraded' | 'disconnected' | 'completed';
    duration: number;
    amplitude: number;
    jitter: number;
    latency: number;
    packet_loss: number;
    transcripts: { sender: string; text: string }[];
    voice_assistant: string;
    timerInterval?: any;
    speechInterval?: any;
}

const ongoingCalls = ref<Call[]>([]);
const selectedCallId = ref<string | null>(null);

// Alert logs for visualization
const telemetryAlerts = ref<{ id: number; timestamp: string; type: string; message: string }[]>([]);
let alertIdCounter = 0;

const logAlert = (type: string, message: string) => {
    telemetryAlerts.value.unshift({
        id: ++alertIdCounter,
        timestamp: new Date().toLocaleTimeString(),
        type,
        message,
    });
    if (telemetryAlerts.value.length > 30) {
        telemetryAlerts.value.pop();
    }
};

// WebSocket updates
const channelName = props.tenant?.id ? `tenant.${props.tenant.id}` : '';
if (channelName) {
    useEcho(channelName, 'WebRtcTelemetryUpdated', (e: any) => {
        logAlert('telemetry', `WebRTC Telemetry for call ${e.callId.substring(0, 8)}: Loss=${e.packetLoss.toFixed(1)}%, Jitter=${e.jitter.toFixed(1)}ms`);
        updateCallTelemetry(e.callId, e.jitter, e.latency, e.packetLoss);
    });

    useEcho(channelName, 'CallQualityDegraded', (e: any) => {
        logAlert('degraded', `⚠️ QUALITY DEGRADED on call ${e.callId.substring(0, 8)}! Packet loss hit ${e.packetLoss.toFixed(1)}%`);
        handleQualityDegraded(e.callId, e.packetLoss, e.rtpJitter);
    });
}

// Find call and update stats
const updateCallTelemetry = (callId: string, jitter: number, latency: number, packetLoss: number) => {
    const call = ongoingCalls.value.find((c) => c.call_id === callId);
    if (call) {
        call.jitter = jitter;
        call.latency = latency;
        call.packet_loss = packetLoss;
        if (packetLoss > 5.0) {
            call.status = 'degraded';
            mascotState.value = 3; // Error state on mascot
        } else if (call.status === 'degraded' && packetLoss <= 2.0) {
            call.status = 'connected';
            mascotState.value = 1; // back to scanning
        }
    }
};

// Handle degradation trigger
const handleQualityDegraded = (callId: string, packetLoss: number, rtpJitter: number) => {
    const call = ongoingCalls.value.find((c) => c.call_id === callId);
    if (call) {
        call.status = 'degraded';
        call.packet_loss = packetLoss;
        call.jitter = rtpJitter;
        mascotState.value = 3; // Switch mascot to error state
    }
};

// Selected call computed helper
const selectedCall = computed(() => {
    return ongoingCalls.value.find((c) => c.call_id === selectedCallId.value) || null;
});

// Setup mock dialog options
const mockPhrases = [
    { role: 'Customer', text: 'I would like to schedule an AC tune-up for tomorrow afternoon.' },
    { role: 'Assistant', text: 'Sure! I see we have Alice Smith available at 2:00 PM tomorrow. Would that work?' },
    { role: 'Customer', text: 'Yes, 2:00 PM works perfectly. How long will it take?' },
    { role: 'Assistant', text: 'Great! It typically takes about 1 to 2 hours. I have locked in that appointment for you.' },
    { role: 'Customer', text: 'Awesome, thank you very much!' },
    { role: 'Assistant', text: 'You are welcome! Have a wonderful day. Goodbye!' },
];

// Simulator controls
const startMockCall = () => {
    const randomId = 'call_' + Math.random().toString(36).substring(2, 10);
    const names = ['Alice Cooper', 'Bob Dylan', 'Charlie Brown', 'Diana Ross', 'Evan Wright'];
    const selectedName = names[Math.floor(Math.random() * names.length)];
    const phone = '555-01' + Math.floor(10 + Math.random() * 90);

    const newCall: Call = {
        call_id: randomId,
        customer_name: selectedName,
        customer_phone: phone,
        status: 'ringing',
        duration: 0,
        amplitude: 0,
        jitter: 1.2 + Math.random() * 2,
        latency: 45 + Math.random() * 20,
        packet_loss: 0.1 + Math.random() * 0.5,
        transcripts: [{ sender: 'System', text: '📞 Call incoming, routing to automated assistant...' }],
        voice_assistant: 'Standard Dispatcher Model v2',
    };

    ongoingCalls.value.push(newCall);
    if (ongoingCalls.value.length === 1) {
        selectedCallId.value = randomId;
    }

    logAlert('status', `New inbound call: ${selectedName} (${phone})`);
    mascotState.value = 1; // scanning

    // Transition ringing to connected
    setTimeout(() => {
        if (newCall.status === 'ringing') {
            newCall.status = 'connected';
            newCall.transcripts.push({ sender: 'Assistant', text: 'Hello, thank you for calling. How can I help you book your service today?' });
            logAlert('status', `Call ${randomId.substring(0, 8)} connected.`);
        }
    }, 1500);

    // Call duration timer
    newCall.timerInterval = setInterval(() => {
        if (newCall.status !== 'completed' && newCall.status !== 'disconnected') {
            newCall.duration++;
        }
    }, 1000);

    // Speech & Amplitude simulation
    let phraseIndex = 0;
    newCall.speechInterval = setInterval(() => {
        if (newCall.status === 'completed' || newCall.status === 'disconnected') {
            clearInterval(newCall.speechInterval);
            return;
        }

        // Random speaking indicator
        const isSpeakingNow = Math.random() > 0.4;
        newCall.amplitude = isSpeakingNow ? Math.floor(30 + Math.random() * 60) : 0;

        if (selectedCallId.value === randomId) {
            mascotSpeaking.value = isSpeakingNow;
            mascotAmplitude.value = newCall.amplitude;
        }

        // Periodically push a transcript phrase
        if (Math.random() > 0.7 && phraseIndex < mockPhrases.length) {
            const phrase = mockPhrases[phraseIndex++];
            newCall.transcripts.push({ sender: phrase.role, text: phrase.text });

            if (phrase.role === 'Assistant') {
                mascotState.value = 1; // talking/scanning
            }

            // If it reaches the final phrase, complete the call in a few seconds
            if (phraseIndex === mockPhrases.length) {
                setTimeout(() => {
                    completeCall(randomId);
                }, 5000);
            }
        }
    }, 3000);
};

const completeCall = (callId: string) => {
    const call = ongoingCalls.value.find((c) => c.call_id === callId);
    if (call && call.status !== 'completed') {
        call.status = 'completed';
        call.amplitude = 0;
        call.transcripts.push({ sender: 'System', text: '✅ Call completed. Dispatch schedule synchronized.' });
        logAlert('success', `Call ${callId.substring(0, 8)} completed successfully!`);

        // Mascot Victory
        mascotState.value = 2; // Victory!
        mascotSpeaking.value = false;
        mascotAmplitude.value = 0;

        // Cleanup intervals
        if (call.timerInterval) clearInterval(call.timerInterval);
        if (call.speechInterval) clearInterval(call.speechInterval);

        // Keep in list for 8 seconds, then remove
        setTimeout(() => {
            removeCall(callId);
        }, 8000);
    }
};

const disconnectCall = (callId: string) => {
    const call = ongoingCalls.value.find((c) => c.call_id === callId);
    if (call && call.status !== 'completed' && call.status !== 'disconnected') {
        call.status = 'disconnected';
        call.amplitude = 0;
        call.transcripts.push({ sender: 'System', text: '❌ Call terminated abruptly.' });
        logAlert('error', `Call ${callId.substring(0, 8)} disconnected.`);

        mascotState.value = 3; // Error!
        mascotSpeaking.value = false;
        mascotAmplitude.value = 0;

        if (call.timerInterval) clearInterval(call.timerInterval);
        if (call.speechInterval) clearInterval(call.speechInterval);

        setTimeout(() => {
            removeCall(callId);
        }, 8000);
    }
};

const removeCall = (callId: string) => {
    const index = ongoingCalls.value.findIndex((c) => c.call_id === callId);
    if (index !== -1) {
        ongoingCalls.value.splice(index, 1);
        if (selectedCallId.value === callId) {
            selectedCallId.value = ongoingCalls.value.length > 0 ? ongoingCalls.value[0].call_id : null;
        }
        if (ongoingCalls.value.length === 0) {
            mascotState.value = 0; // Back to Idle
        }
    }
};

// Simulated Degraded telemetries
const triggerMockPacketLoss = async (callId: string, lossAmount: number) => {
    const call = ongoingCalls.value.find((c) => c.call_id === callId);
    if (!call) return;

    logAlert('telemetry', `Simulating packet degradation: ${lossAmount}% on ${callId.substring(0, 8)}`);

    try {
        await fetch('/api/telemetry/quality-degraded', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                tenant_id: props.tenant.id,
                call_id: callId,
                packet_loss: lossAmount,
                rtp_jitter: 8.5 + Math.random() * 5,
            }),
        });
    } catch (e) {
        // Fallback local update if API fails
        handleQualityDegraded(callId, lossAmount, 10.2);
    }
};

// Start a default mock call on mount so the layout is lively
onMounted(() => {
    startMockCall();
});

onUnmounted(() => {
    ongoingCalls.value.forEach((call) => {
        if (call.timerInterval) clearInterval(call.timerInterval);
        if (call.speechInterval) clearInterval(call.speechInterval);
    });
});

// Format duration to mm:ss
const formatTime = (seconds: number) => {
    const m = Math.floor(seconds / 60).toString().padStart(2, '0');
    const s = (seconds % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
};

// Spend limit percentage helper
const spendPercentage = computed(() => {
    if (props.spendLimit <= 0) return 0;
    return Math.min(100, (props.spendUsage / props.spendLimit) * 100);
});

const isLimitReached = computed(() => props.spendUsage >= props.spendLimit);
</script>

<template>
    <Head title="Live Call Monitor Hub" />

    <div class="mx-auto flex max-w-[1400px] flex-col gap-6 p-4 sm:p-6 md:p-8">
        
        <!-- Duolingo-styled Top Spend Limit Banner -->
        <div class="relative overflow-hidden rounded-2xl border-2 border-slate-200 dark:border-slate-800 bg-card p-6 shadow-sm">
            <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                <TrendingUp class="h-24 w-24" />
            </div>
            
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-foreground sm:text-3xl flex items-center gap-2">
                        <Activity class="h-8 w-8 text-emerald-500 animate-pulse" />
                        Live Voice Assistant Monitor
                    </h1>
                    <p class="text-sm font-medium text-muted-foreground mt-1">
                        SaaS tenant call event streams, diagnostic status grids, and safety locks.
                    </p>
                </div>
                
                <!-- Spend Widget -->
                <div class="flex flex-col gap-1.5 min-w-[280px]">
                    <div class="flex justify-between text-xs font-black tracking-wider text-slate-500 uppercase">
                        <span>API Spend Usage</span>
                        <span :class="[isLimitReached ? 'text-rose-500' : 'text-emerald-500']">
                            ${{ spendUsage.toFixed(2) }} / ${{ spendLimit.toFixed(2) }}
                        </span>
                    </div>
                    
                    <div class="h-3 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700">
                        <div 
                            class="h-full rounded-full transition-all duration-500"
                            :class="[
                                isLimitReached ? 'bg-rose-500' : (spendPercentage > 80 ? 'bg-amber-500' : 'bg-emerald-500')
                            ]"
                            :style="{ width: `${spendPercentage}%` }"
                        ></div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-muted-foreground">Blended Rate: $0.15/min</span>
                        <span 
                            v-if="isLimitReached || !tenant.settings?.voice_assistant_active" 
                            class="inline-flex items-center gap-1 rounded bg-rose-100 dark:bg-rose-900/30 px-1.5 py-0.5 text-[10px] font-black uppercase text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50"
                        >
                            <WifiOff class="h-3 w-3" /> Suspended
                        </span>
                        <span 
                            v-else 
                            class="inline-flex items-center gap-1 rounded bg-emerald-100 dark:bg-emerald-900/30 px-1.5 py-0.5 text-[10px] font-black uppercase text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50"
                        >
                            Active
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Major Split Console -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            
            <!-- Left Console: Active Calls List and Feed (2/3 Grid) -->
            <div class="flex flex-col gap-6 lg:col-span-2">
                
                <!-- Ongoing Calls Grid -->
                <div class="rounded-2xl border-2 border-slate-200 dark:border-slate-800 bg-card p-6 shadow-sm flex flex-col gap-4">
                    <div class="flex items-center justify-between border-b pb-4">
                        <h2 class="text-lg font-black tracking-tight text-foreground flex items-center gap-2">
                            <Radio class="h-5 w-5 text-emerald-500 animate-pulse" />
                            Ongoing Active Lines
                            <span class="ml-1 rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-xs font-black text-slate-600 dark:text-slate-400">
                                {{ ongoingCalls.length }} Call{{ ongoingCalls.length === 1 ? '' : 's' }}
                            </span>
                        </h2>
                        
                        <button 
                            @click="startMockCall"
                            class="inline-flex items-center gap-1.5 rounded-xl border-2 border-b-4 border-emerald-600 bg-emerald-500 hover:bg-emerald-400 text-white px-4 py-2 text-xs font-black tracking-wider uppercase transition-all hover:translate-y-[-1px] active:translate-y-[1px] active:border-b-2"
                        >
                            <Play class="h-4.5 w-4.5" /> Simulate Inbound Call
                        </button>
                    </div>

                    <!-- Calls List Empty State -->
                    <div 
                        v-if="ongoingCalls.length === 0" 
                        class="flex flex-col items-center justify-center py-12 px-4 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-900/20"
                    >
                        <Phone class="h-12 w-12 text-slate-300 dark:text-slate-700 animate-bounce mb-3" />
                        <h3 class="font-bold text-slate-700 dark:text-slate-300">No active calls right now</h3>
                        <p class="text-xs text-muted-foreground mt-1 max-w-xs">
                            Trigger a simulated call or dispatch events from telephony lines to watch live audio streams.
                        </p>
                    </div>

                    <!-- Call Cards Grid -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div 
                            v-for="call in ongoingCalls" 
                            :key="call.call_id"
                            @click="selectedCallId = call.call_id"
                            class="group relative rounded-xl border-2 p-4 cursor-pointer transition-all flex flex-col gap-3 shadow-xs"
                            :class="[
                                selectedCallId === call.call_id 
                                    ? 'border-emerald-500 bg-emerald-50/30 dark:bg-emerald-950/20 shadow-md ring-2 ring-emerald-500/20' 
                                    : 'border-slate-200 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/10 hover:border-slate-400 dark:hover:border-slate-600'
                            ]"
                        >
                            <!-- Card Header Info -->
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-black text-sm text-foreground tracking-tight group-hover:text-emerald-500 transition-colors">
                                        {{ call.customer_name }}
                                    </h4>
                                    <p class="text-[11px] text-muted-foreground">{{ call.customer_phone }}</p>
                                </div>
                                
                                <div class="flex items-center gap-1.5">
                                    <span 
                                        class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[9px] font-black uppercase border"
                                        :class="{
                                            'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 border-yellow-200 dark:border-yellow-900/50': call.status === 'ringing',
                                            'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/50': call.status === 'connected',
                                            'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-900/50': call.status === 'degraded',
                                            'bg-slate-100 dark:bg-slate-900/30 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800': call.status === 'completed',
                                        }"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="{
                                            'bg-yellow-500 animate-pulse': call.status === 'ringing',
                                            'bg-emerald-500 animate-ping': call.status === 'connected',
                                            'bg-rose-500 animate-pulse': call.status === 'degraded',
                                            'bg-slate-400': call.status === 'completed',
                                        }"></span>
                                        {{ call.status }}
                                    </span>
                                    
                                    <span class="text-xs font-mono text-muted-foreground flex items-center gap-0.5">
                                        <Clock class="h-3 w-3" /> {{ formatTime(call.duration) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Live Amplitude Visual Bar -->
                            <div class="flex items-center gap-2 border-y border-dashed border-slate-100 dark:border-slate-800/80 py-2">
                                <div class="flex-1 flex items-center justify-around h-6">
                                    <div 
                                        v-for="i in 16" 
                                        :key="i"
                                        class="w-1 bg-emerald-500 dark:bg-emerald-400 rounded-full transition-all duration-75"
                                        :style="{ 
                                            height: `${Math.max(4, call.amplitude > 0 ? (Math.sin(i + call.duration) * (call.amplitude / 100) * 20 + 12) : 4)}px`,
                                            opacity: call.amplitude > 0 ? 0.85 : 0.25
                                        }"
                                    ></div>
                                </div>
                                <span class="text-[10px] font-mono text-muted-foreground w-8 text-right">{{ call.amplitude }}dB</span>
                            </div>

                            <!-- Telemetry stats footer -->
                            <div class="grid grid-cols-3 gap-1 text-[10px] font-mono text-muted-foreground bg-slate-50 dark:bg-slate-900/50 p-2 rounded">
                                <div>Jit: <span class="font-bold text-foreground">{{ call.jitter.toFixed(1) }}ms</span></div>
                                <div>Lat: <span class="font-bold text-foreground">{{ call.latency.toFixed(0) }}ms</span></div>
                                <div :class="[call.packet_loss > 5.0 ? 'text-rose-500 font-black' : '']">Loss: {{ call.packet_loss.toFixed(1) }}%</div>
                            </div>

                            <!-- Quick simulation tools inside card -->
                            <div class="flex gap-2 justify-end mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                <button 
                                    @click.stop="triggerMockPacketLoss(call.call_id, 8.5)"
                                    class="rounded-md border border-rose-300 dark:border-rose-900 hover:bg-rose-50 dark:hover:bg-rose-955/20 text-rose-500 px-2 py-0.5 text-[9px] font-black uppercase transition-colors"
                                >
                                    Force Loss (8%)
                                </button>
                                <button 
                                    v-if="call.status !== 'completed'"
                                    @click.stop="completeCall(call.call_id)"
                                    class="rounded-md border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 px-2 py-0.5 text-[9px] font-black uppercase transition-colors"
                                >
                                    End
                                </button>
                                <button 
                                    v-if="call.status !== 'completed' && call.status !== 'disconnected'"
                                    @click.stop="disconnectCall(call.call_id)"
                                    class="rounded-md border border-amber-300 dark:border-amber-900 hover:bg-amber-50 dark:hover:bg-amber-955/20 text-amber-600 px-2 py-0.5 text-[9px] font-black uppercase transition-colors"
                                >
                                    Drop
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scrolling Active Transcript Bubble -->
                <div class="rounded-2xl border-2 border-slate-200 dark:border-slate-800 bg-card p-6 shadow-sm flex flex-col gap-4 flex-1">
                    <h2 class="text-lg font-black tracking-tight text-foreground flex items-center gap-2 border-b pb-4">
                        <Volume2 class="h-5 w-5 text-emerald-500" />
                        Scrolling Call Transcript
                        <span v-if="selectedCall" class="text-xs font-normal text-muted-foreground">
                            - Viewing conversation context for <span class="font-bold text-foreground">{{ selectedCall.customer_name }}</span>
                        </span>
                    </h2>

                    <!-- Transcript area empty state -->
                    <div 
                        v-if="!selectedCall" 
                        class="flex-1 flex flex-col items-center justify-center text-center text-muted-foreground py-12"
                    >
                        <VolumeX class="h-10 w-10 text-slate-300 dark:text-slate-700 mb-2" />
                        <span class="text-xs">Select an active line to inspect dialogue transcripts in real-time.</span>
                    </div>

                    <!-- Transcript lists -->
                    <div 
                        v-else 
                        class="flex-1 flex flex-col gap-3 overflow-y-auto max-h-[300px] p-2 bg-slate-50 dark:bg-slate-900/30 border border-slate-100 dark:border-slate-850 rounded-xl"
                    >
                        <div 
                            v-for="(t, idx) in selectedCall.transcripts" 
                            :key="idx"
                            class="flex flex-col gap-1 max-w-[80%] rounded-xl px-4 py-2.5 text-xs transition-all shadow-xs border"
                            :class="[
                                t.sender === 'Customer' 
                                    ? 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 self-start border-slate-200 dark:border-slate-800 border-b-4' 
                                    : t.sender === 'Assistant'
                                      ? 'bg-emerald-500 text-white self-end border-emerald-600 border-b-4'
                                      : 'bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 self-center text-center border-amber-200 dark:border-amber-900/50'
                            ]"
                        >
                            <span 
                                class="text-[9px] font-black tracking-wider uppercase"
                                :class="[t.sender === 'Assistant' ? 'text-emerald-100' : 'text-muted-foreground']"
                            >
                                {{ t.sender }}
                            </span>
                            <p class="font-medium mt-0.5 leading-relaxed">{{ t.text }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Console: Mascot Shop integration and diagnostics (1/3 Grid) -->
            <div class="flex flex-col gap-6">
                
                <!-- Playful Interactive Mascot Box -->
                <div class="rounded-2xl border-2 border-slate-200 dark:border-slate-800 bg-card p-6 shadow-sm flex flex-col gap-4">
                    <h3 class="text-lg font-black tracking-tight text-foreground flex items-center gap-2 border-b pb-4">
                        <Sparkles class="h-5 w-5 text-amber-500" />
                        AI Dispatch Mascot
                    </h3>

                    <!-- Dispatch Mascot Wrapper -->
                    <div class="h-[300px]">
                        <DispatcherMascot 
                            :state="mascotState" 
                            :is-speaking="mascotSpeaking"
                            :amplitude="mascotAmplitude"
                            :skin="activeSkin"
                        />
                    </div>

                    <!-- Mascot Controls -->
                    <div class="flex flex-col gap-2">
                        <div class="text-xs font-black tracking-wider text-slate-500 uppercase mb-1">
                            Simulate Mascot State Outputs
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button 
                                @click="mascotState = 0"
                                class="rounded-xl border-2 border-b-4 border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 hover:bg-slate-50 px-2.5 py-1.5 text-xs font-black tracking-wider uppercase transition-all hover:translate-y-[-1px] active:translate-y-[1px] active:border-b-2"
                                :class="{ 'border-slate-500 bg-slate-200 dark:bg-slate-700': mascotState === 0 }"
                            >
                                0: Idle
                            </button>
                            <button 
                                @click="mascotState = 1"
                                class="rounded-xl border-2 border-b-4 border-emerald-600 bg-emerald-500 hover:bg-emerald-400 text-white px-2.5 py-1.5 text-xs font-black tracking-wider uppercase transition-all hover:translate-y-[-1px] active:translate-y-[1px] active:border-b-2"
                                :class="{ 'ring-2 ring-emerald-500 ring-offset-2': mascotState === 1 }"
                            >
                                1: Scanning
                            </button>
                            <button 
                                @click="mascotState = 2"
                                class="rounded-xl border-2 border-b-4 border-amber-500 bg-amber-400 hover:bg-amber-300 text-amber-950 px-2.5 py-1.5 text-xs font-black tracking-wider uppercase transition-all hover:translate-y-[-1px] active:translate-y-[1px] active:border-b-2"
                                :class="{ 'ring-2 ring-amber-400 ring-offset-2': mascotState === 2 }"
                            >
                                2: Victory
                            </button>
                            <button 
                                @click="mascotState = 3"
                                class="rounded-xl border-2 border-b-4 border-rose-600 bg-rose-500 hover:bg-rose-400 text-white px-2.5 py-1.5 text-xs font-black tracking-wider uppercase transition-all hover:translate-y-[-1px] active:translate-y-[1px] active:border-b-2"
                                :class="{ 'ring-2 ring-rose-500 ring-offset-2': mascotState === 3 }"
                            >
                                3: Error
                            </button>
                        </div>
                    </div>

                    <!-- Skin toggle details -->
                    <div class="flex items-center justify-between mt-2 p-2.5 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-100 dark:border-slate-800">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black uppercase text-slate-400">Current Equipped Skin</span>
                            <span class="text-xs font-bold text-foreground capitalize">{{ activeSkin }} Skin</span>
                        </div>
                        <div class="flex gap-1.5">
                            <button 
                                v-for="sk in ['standard', 'robot', 'gold']"
                                :key="sk"
                                @click="activeSkin = sk"
                                class="rounded px-2 py-0.5 text-[9px] font-black uppercase border border-slate-350 dark:border-slate-700 capitalize transition-all"
                                :class="[activeSkin === sk ? 'bg-slate-900 dark:bg-slate-100 text-slate-100 dark:text-slate-900' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400']"
                            >
                                {{ sk }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- WebRTC Telemetry Feed Logs -->
                <div class="rounded-2xl border-2 border-slate-200 dark:border-slate-800 bg-card p-6 shadow-sm flex flex-col gap-4 flex-1">
                    <h3 class="text-lg font-black tracking-tight text-foreground flex items-center gap-2 border-b pb-4">
                        <Database class="h-5 w-5 text-emerald-500" />
                        Websocket Telemetry Log
                    </h3>
                    
                    <div class="flex-1 overflow-y-auto max-h-[220px] rounded-xl bg-slate-950 p-4 font-mono text-[10px] text-slate-400 flex flex-col gap-2">
                        <div v-if="telemetryAlerts.length === 0" class="text-slate-600 text-center py-6">
                            Waiting for inbound WebRTC events...
                        </div>
                        <div 
                            v-for="alert in telemetryAlerts" 
                            :key="alert.id"
                            class="border-b border-slate-900 pb-1.5 last:border-0"
                        >
                            <span class="text-emerald-500">[{{ alert.timestamp }}]</span>
                            <span 
                                class="ml-1 px-1 rounded text-[9px] uppercase font-black"
                                :class="{
                                    'bg-rose-900 text-rose-300': alert.type === 'degraded' || alert.type === 'error',
                                    'bg-cyan-900 text-cyan-300': alert.type === 'telemetry',
                                    'bg-emerald-900 text-emerald-300': alert.type === 'success',
                                    'bg-slate-800 text-slate-300': alert.type === 'status',
                                }"
                            >
                                {{ alert.type }}
                            </span>
                            <p class="mt-1 text-slate-300">{{ alert.message }}</p>
                        </div>
                    </div>
                </div>

                <!-- Call Flow settings overview -->
                <div class="rounded-2xl border-2 border-slate-200 dark:border-slate-800 bg-card p-6 shadow-sm flex flex-col gap-3">
                    <h4 class="font-black text-sm text-foreground uppercase tracking-wider flex items-center gap-1.5">
                        <Clock class="h-4.5 w-4.5 text-slate-400" /> Pacing & Speech Settings
                    </h4>
                    <div class="grid grid-cols-2 gap-3 text-xs pt-1">
                        <div class="rounded border p-2.5 bg-slate-50/50 dark:bg-slate-900/30">
                            <span class="text-[10px] font-black uppercase text-slate-400 block mb-1">Silence Timeout</span>
                            <span class="font-bold font-mono text-foreground">{{ timingSettings.startSpeakingPlan }}ms</span>
                        </div>
                        <div class="rounded border p-2.5 bg-slate-50/50 dark:bg-slate-900/30">
                            <span class="text-[10px] font-black uppercase text-slate-400 block mb-1">Barge-in Threshold</span>
                            <span class="font-bold font-mono text-foreground">{{ timingSettings.stopSpeakingPlan }}s</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</template>

<style scoped>
/* Duolingo UI animations and transitions helper */
.bg-card {
    transition: background-color 0.2s, border-color 0.2s;
}
</style>
