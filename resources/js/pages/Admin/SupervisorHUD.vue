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
    Sparkles,
    Clock,
    WifiOff,
    Send,
    MessageSquare,
    AlertTriangle,
    Zap,
    Play,
    StopCircle
} from '@lucide/vue';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useEcho } from '@laravel/echo-vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import SpectralVisualizer from '@/components/SpectralVisualizer.vue';
import { callStore } from '@/lib/store';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Supervisor HUD',
                href: '/admin/supervisor-hud',
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
const activeSkin = ref<string>((props.tenant.settings?.active_skin as string) || 'standard');
const whisperMessage = ref<string>('');
const whisperStatus = ref<{ type: 'success' | 'error' | null; message: string }>({ type: null, message: '' });
const isSendingWhisper = ref<boolean>(false);

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
    if (telemetryAlerts.value.length > 20) {
        telemetryAlerts.value.pop();
    }
};

// Spend limit and usage
const currentSpendUsage = ref<number>(props.spendUsage);
const isLimitReached = computed(() => currentSpendUsage.value >= props.spendLimit);

// Calculate mascot state based on active call metrics or spend limits
const mascotState = computed(() => {
    if (isLimitReached.value) {
        return 3; // Error state on spend lock
    }
    if (ongoingCalls.value.length === 0) {
        return 0; // Idle
    }

    // Packet loss spikes or disconnections transition mascot immediately to sad error state (3)
    const hasCriticalIssues = ongoingCalls.value.some(
        (c) => c.status === 'degraded' || c.status === 'disconnected' || c.packet_loss > 5.0
    );
    if (hasCriticalIssues) {
        return 3;
    }

    // Call successfully completed / booked triggers celebratory victory (2)
    const hasVictory = ongoingCalls.value.some((c) => c.status === 'completed');
    if (hasVictory) {
        return 2;
    }

    // Call ongoing with optimal WebRTC packet metrics triggers scanning radar (1)
    const hasOptimalOngoing = ongoingCalls.value.some(
        (c) => (c.status === 'connected' || c.status === 'ringing') && c.packet_loss <= 2.0
    );
    if (hasOptimalOngoing) {
        return 1;
    }

    return 1; // Default fallback to scanning if calls exist
});

// Selected call computed helper
const selectedCall = computed(() => {
    return ongoingCalls.value.find((c) => c.call_id === selectedCallId.value) || null;
});

// Sync selected call amplitude to global callStore for SpectralVisualizer
watch(
    () => selectedCall.value?.amplitude,
    (newAmp) => {
        callStore.amplitude = (newAmp || 0) / 100;
        callStore.isSpeaking = (newAmp || 0) > 0;
    }
);

// Watch mascotState to update global callStore
watch(mascotState, (newVal) => {
    if (newVal === 3) {
        logAlert('warning', 'Mascot transitioned to DISAPPOINTED state due to metrics/locks.');
    } else if (newVal === 2) {
        logAlert('success', 'Mascot transitioned to CELEBRATORY state. Call booked!');
    }
});

// WebSocket updates via Echo
const channelName = props.tenant?.id ? `tenant.${props.tenant.id}` : '';
if (channelName) {
    useEcho(channelName, 'WebRtcTelemetryUpdated', (e: any) => {
        logAlert('telemetry', `Telemetry update for call ${e.callId?.substring(0, 8)}: Loss=${e.packetLoss?.toFixed(1)}%, Jitter=${e.jitter?.toFixed(1)}ms`);
        updateCallTelemetry(e.callId, e.jitter, e.latency, e.packetLoss);
    });

    useEcho(channelName, 'CallQualityDegraded', (e: any) => {
        logAlert('degraded', `⚠️ Quality DEGRADED on call ${e.callId?.substring(0, 8)}!`);
        const call = ongoingCalls.value.find((c) => c.call_id === e.callId);
        if (call) {
            call.status = 'degraded';
            call.packet_loss = e.packetLoss || 8.0;
            call.jitter = e.rtpJitter || 12.0;
        }
    });

    useEcho(channelName, 'CallStarted', (payload: any) => {
        const callId = payload.call_id || payload.id;
        const customerPhone = payload.customer_phone || 'Unknown';
        logAlert('status', `New dynamic call started: ${callId?.substring(0, 8)}`);
        
        // Add to active calls list if not already there
        if (!ongoingCalls.value.some(c => c.call_id === callId)) {
            const newCall: Call = {
                call_id: callId,
                customer_name: 'Incoming External Call',
                customer_phone: customerPhone,
                status: 'connected',
                duration: 0,
                amplitude: 15,
                jitter: 1.5,
                latency: 60,
                packet_loss: 0.1,
                transcripts: [{ sender: 'System', text: 'Live WebRTC stream initialized.' }],
                voice_assistant: 'Branded Voice Model',
            };
            ongoingCalls.value.push(newCall);
            if (!selectedCallId.value) {
                selectedCallId.value = callId;
            }
        }
    });

    useEcho(channelName, 'CallEnded', (payload: any) => {
        const callId = payload.call_id || payload.id;
        logAlert('status', `Call ended dynamically: ${callId?.substring(0, 8)}`);
        const call = ongoingCalls.value.find((c) => c.call_id === callId);
        if (call) {
            call.status = 'completed';
            setTimeout(() => {
                removeCall(callId);
            }, 5000);
        }
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
        } else if (call.status === 'degraded' && packetLoss <= 2.0) {
            call.status = 'connected';
        }
    }
};

// Setup mock dialog options
const mockPhrases = [
    { role: 'Customer', text: 'Hey there, I need a plumber to fix our water heater.' },
    { role: 'Assistant', text: 'No problem! I can schedule an appointment. Is tomorrow morning good?' },
    { role: 'Customer', text: 'Yes, around 10 AM works best.' },
    { role: 'Assistant', text: 'Perfect. I have scheduled Bob Jones for tomorrow at 10:00 AM.' },
    { role: 'Customer', text: 'Thank you so much! That was super fast.' },
    { role: 'Assistant', text: 'You are welcome! Have a fantastic day. Goodbye!' },
];

// Simulator controls
const startMockCall = () => {
    const randomId = 'call_' + Math.random().toString(36).substring(2, 10);
    const names = ['Jerry Seinfeld', 'Elaine Benes', 'Cosmo Kramer', 'George Costanza'];
    const selectedName = names[Math.floor(Math.random() * names.length)];
    const phone = '555-02' + Math.floor(10 + Math.random() * 90);

    const newCall: Call = {
        call_id: randomId,
        customer_name: selectedName,
        customer_phone: phone,
        status: 'ringing',
        duration: 0,
        amplitude: 0,
        jitter: 1.1 + Math.random() * 1.5,
        latency: 40 + Math.random() * 15,
        packet_loss: 0.1 + Math.random() * 0.4,
        transcripts: [{ sender: 'System', text: '📞 Call incoming, routing to automated assistant...' }],
        voice_assistant: 'Optimal Assistant v3',
    };

    ongoingCalls.value.push(newCall);
    if (ongoingCalls.value.length === 1 || !selectedCallId.value) {
        selectedCallId.value = randomId;
    }

    logAlert('status', `Simulated Call Started: ${selectedName}`);

    // Transition ringing to connected
    setTimeout(() => {
        if (newCall.status === 'ringing') {
            newCall.status = 'connected';
            newCall.transcripts.push({ sender: 'Assistant', text: 'Hello, thank you for calling. How can I help you book your service today?' });
            logAlert('status', `Call ${randomId.substring(0, 8)} connected.`);
        }
    }, 1200);

    // Call duration timer
    newCall.timerInterval = setInterval(() => {
        if (newCall.status !== 'completed' && newCall.status !== 'disconnected') {
            newCall.duration++;
            currentSpendUsage.value += 0.0025; // simulate spend ticking
        }
    }, 1000);

    // Speech & Amplitude simulation
    let phraseIndex = 0;
    newCall.speechInterval = setInterval(() => {
        if (newCall.status === 'completed' || newCall.status === 'disconnected') {
            clearInterval(newCall.speechInterval);
            return;
        }

        const isSpeakingNow = Math.random() > 0.35;
        newCall.amplitude = isSpeakingNow ? Math.floor(40 + Math.random() * 55) : 0;

        // Periodically push a transcript phrase
        if (Math.random() > 0.65 && phraseIndex < mockPhrases.length) {
            const phrase = mockPhrases[phraseIndex++];
            newCall.transcripts.push({ sender: phrase.role, text: phrase.text });

            // If it reaches the final phrase, complete the call
            if (phraseIndex === mockPhrases.length) {
                setTimeout(() => {
                    completeCall(randomId);
                }, 4000);
            }
        }
    }, 2500);
};

const completeCall = (callId: string) => {
    const call = ongoingCalls.value.find((c) => c.call_id === callId);
    if (call && call.status !== 'completed') {
        call.status = 'completed';
        call.amplitude = 0;
        call.transcripts.push({ sender: 'System', text: '✅ Call completed. Dispatch schedule synchronized.' });
        logAlert('success', `Call ${callId.substring(0, 8)} completed successfully!`);

        // Cleanup intervals
        if (call.timerInterval) clearInterval(call.timerInterval);
        if (call.speechInterval) clearInterval(call.speechInterval);

        // Keep in list for 6 seconds, then remove
        setTimeout(() => {
            removeCall(callId);
        }, 6000);
    }
};

const disconnectCall = (callId: string) => {
    const call = ongoingCalls.value.find((c) => c.call_id === callId);
    if (call && call.status !== 'completed' && call.status !== 'disconnected') {
        call.status = 'disconnected';
        call.amplitude = 0;
        call.transcripts.push({ sender: 'System', text: '❌ Call terminated abruptly.' });
        logAlert('error', `Call ${callId.substring(0, 8)} disconnected.`);

        if (call.timerInterval) clearInterval(call.timerInterval);
        if (call.speechInterval) clearInterval(call.speechInterval);

        setTimeout(() => {
            removeCall(callId);
        }, 6000);
    }
};

const removeCall = (callId: string) => {
    const index = ongoingCalls.value.findIndex((c) => c.call_id === callId);
    if (index !== -1) {
        ongoingCalls.value.splice(index, 1);
        if (selectedCallId.value === callId) {
            selectedCallId.value = ongoingCalls.value.length > 0 ? ongoingCalls.value[0].call_id : null;
        }
    }
};

// Force packet loss simulation
const triggerMockPacketLoss = (callId: string, lossAmount: number) => {
    const call = ongoingCalls.value.find((c) => c.call_id === callId);
    if (!call) return;
    call.status = 'degraded';
    call.packet_loss = lossAmount;
    call.jitter = 15.4;
    logAlert('telemetry', `⚠️ Simulated high packet loss (${lossAmount}%) on call ${callId.substring(0, 8)}`);
};

// Send supervisor whisper coaching tip
const sendWhisper = async () => {
    if (!selectedCallId.value || !whisperMessage.value.trim()) return;

    isSendingWhisper.value = true;
    whisperStatus.value = { type: null, message: '' };

    try {
        const response = await fetch('/api/web-calls/whisper', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                call_id: selectedCallId.value,
                instruction: whisperMessage.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            whisperStatus.value = { type: 'success', message: 'Whisper coaching tip sent to technician!' };
            logAlert('whisper', `Coaching whisper sent for call: ${selectedCallId.value.substring(0, 8)}`);
            whisperMessage.value = '';
        } else {
            whisperStatus.value = { type: 'error', message: data.error || 'Failed to send whisper.' };
        }
    } catch (e: any) {
        whisperStatus.value = { type: 'error', message: 'Network error sending whisper coaching tip.' };
    } finally {
        isSendingWhisper.value = false;
        setTimeout(() => {
            whisperStatus.value = { type: null, message: '' };
        }, 5000);
    }
};

// Reset simulated spend lock
const resetSpendUsage = () => {
    currentSpendUsage.value = props.spendUsage;
    logAlert('status', 'Supervisor reset API spend usage simulation.');
};

// Toggle spend limit lock simulation
const triggerSpendLock = () => {
    currentSpendUsage.value = props.spendLimit + 1.0;
    logAlert('warning', '⚠️ Spend limit lock triggered! Mascot transitioned to error state.');
};

onMounted(() => {
    startMockCall();
});

onUnmounted(() => {
    ongoingCalls.value.forEach((call) => {
        if (call.timerInterval) clearInterval(call.timerInterval);
        if (call.speechInterval) clearInterval(call.speechInterval);
    });
});

const formatTime = (seconds: number) => {
    const m = Math.floor(seconds / 60).toString().padStart(2, '0');
    const s = (seconds % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
};

const spendPercentage = computed(() => {
    if (props.spendLimit <= 0) return 0;
    return Math.min(100, (currentSpendUsage.value / props.spendLimit) * 100);
});
</script>

<template>
    <Head title="Supervisor HUD" />

    <div class="mx-auto flex max-w-[1400px] flex-col gap-8 p-4 sm:p-6 md:p-8 bg-slate-950 text-slate-100 min-h-screen">
        
        <!-- Duolingo style Geometric Title Banner -->
        <div class="relative overflow-hidden rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-[6px_6px_0px_0px_rgba(16,185,129,0.3)]">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-white sm:text-4xl flex items-center gap-3">
                        <span class="rounded-2xl border-4 border-emerald-500 bg-emerald-600 px-3 py-1 text-white shadow-[2px_2px_0px_0px_rgba(15,23,42,1)]">HUD</span>
                        Supervisor Control Dashboard
                    </h1>
                    <p class="text-slate-400 font-bold mt-2 text-sm sm:text-base">
                        Real-time call console, packet telemetry metrics, Rive mascot bindings, and private whispers.
                    </p>
                </div>
                
                <!-- Spend limit gauge with thick borders -->
                <div class="flex flex-col gap-2 min-w-[280px] bg-slate-950 p-4 rounded-2xl border-4 border-slate-800">
                    <div class="flex justify-between text-xs font-black tracking-widest text-slate-400 uppercase">
                        <span>Tenant Billing Limit</span>
                        <span :class="[isLimitReached ? 'text-rose-500' : 'text-emerald-500']">
                            ${{ currentSpendUsage.toFixed(2) }} / ${{ spendLimit.toFixed(2) }}
                        </span>
                    </div>
                    
                    <div class="h-4 w-full bg-slate-850 rounded-full overflow-hidden border-2 border-slate-700">
                        <div 
                            class="h-full rounded-full transition-all duration-300"
                            :class="[
                                isLimitReached ? 'bg-rose-500' : (spendPercentage > 85 ? 'bg-amber-500' : 'bg-emerald-500')
                            ]"
                            :style="{ width: `${spendPercentage}%` }"
                        ></div>
                    </div>
                    
                    <div class="flex items-center justify-between text-[11px]">
                        <span 
                            v-if="isLimitReached"
                            class="inline-flex items-center gap-1 rounded-lg bg-rose-900/40 px-2 py-0.5 font-black uppercase text-rose-400 border-2 border-rose-800"
                        >
                            <WifiOff class="h-3 w-3" /> Suspended
                        </span>
                        <span 
                            v-else 
                            class="inline-flex items-center gap-1 rounded-lg bg-emerald-900/40 px-2 py-0.5 font-black uppercase text-emerald-400 border-2 border-emerald-800"
                        >
                            Active
                        </span>
                        <div class="flex gap-2">
                            <button 
                                v-if="!isLimitReached"
                                @click="triggerSpendLock"
                                class="text-rose-400 hover:text-rose-300 font-black uppercase tracking-wider text-[9px]"
                            >
                                Simulate Lock
                            </button>
                            <button 
                                v-else
                                @click="resetSpendUsage"
                                class="text-emerald-400 hover:text-emerald-300 font-black uppercase tracking-wider text-[9px]"
                            >
                                Unlock Limit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Console Layout -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            
            <!-- Left & Center Section (2/3 columns) -->
            <div class="flex flex-col gap-8 lg:col-span-2">
                
                <!-- Live Call Console (Grid of ongoing calls) -->
                <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-6 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b-4 border-slate-800 pb-4">
                        <h2 class="text-xl font-black text-white flex items-center gap-2">
                            <Activity class="h-6 w-6 text-emerald-500 animate-pulse" />
                            Live Call Console
                            <span class="rounded-full bg-slate-950 px-3 py-0.5 text-xs font-black text-emerald-400 border-2 border-slate-800">
                                {{ ongoingCalls.length }} Ongoing
                            </span>
                        </h2>
                        
                        <div class="flex gap-2">
                            <button 
                                @click="startMockCall"
                                class="inline-flex items-center gap-1.5 rounded-2xl border-4 border-emerald-700 bg-emerald-500 hover:bg-emerald-400 text-white px-4 py-2 text-xs font-black tracking-wider uppercase transition-all active:translate-y-[2px] active:border-b-2"
                            >
                                <Play class="h-4 w-4" /> Simulate Call
                            </button>
                        </div>
                    </div>

                    <!-- Call list empty state -->
                    <div 
                        v-if="ongoingCalls.length === 0" 
                        class="flex flex-col items-center justify-center py-16 px-4 text-center border-4 border-dashed border-slate-800 rounded-2xl bg-slate-950/50"
                    >
                        <Phone class="h-16 w-16 text-slate-700 animate-bounce mb-4" />
                        <h3 class="text-lg font-black text-slate-400">All call channels are currently quiet</h3>
                        <p class="text-xs text-slate-500 mt-2 max-w-sm font-bold">
                            Simulate inbound technician streams to view active packet metrics, speech wave amplitude, and dispatch triggers.
                        </p>
                    </div>

                    <!-- Ongoing Calls Grid -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div 
                            v-for="call in ongoingCalls" 
                            :key="call.call_id"
                            @click="selectedCallId = call.call_id"
                            class="relative rounded-2xl border-4 p-4 cursor-pointer transition-all flex flex-col gap-4 shadow-sm"
                            :class="[
                                selectedCallId === call.call_id 
                                    ? 'border-emerald-500 bg-emerald-950/20 shadow-[4px_4px_0_0_rgba(16,185,129,0.2)]' 
                                    : 'border-slate-800 bg-slate-950 hover:border-slate-700'
                            ]"
                        >
                            <!-- Card Header -->
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-black text-base text-white tracking-tight">
                                        {{ call.customer_name }}
                                    </h4>
                                    <p class="text-xs text-slate-400 font-mono mt-0.5">{{ call.customer_phone }}</p>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <span 
                                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-0.5 text-[9px] font-black uppercase border-2"
                                        :class="{
                                            'bg-amber-950/60 text-amber-400 border-amber-800': call.status === 'ringing',
                                            'bg-emerald-950/60 text-emerald-400 border-emerald-800': call.status === 'connected',
                                            'bg-rose-950/60 text-rose-400 border-rose-800': call.status === 'degraded',
                                            'bg-slate-900 text-slate-400 border-slate-750': call.status === 'completed' || call.status === 'disconnected',
                                        }"
                                    >
                                        {{ call.status }}
                                    </span>
                                    
                                    <span class="text-xs font-black font-mono text-slate-400 flex items-center gap-0.5">
                                        <Clock class="h-3 w-3" /> {{ formatTime(call.duration) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Real-time Amplitude Waveform (Dynamic height bars) -->
                            <div class="flex items-center gap-1.5 border-y-2 border-slate-800 py-3 bg-slate-900/50 rounded-xl px-2">
                                <div class="flex-1 flex items-end justify-between h-8">
                                    <div 
                                        v-for="i in 18" 
                                        :key="i"
                                        class="w-1.5 rounded-full transition-all duration-75"
                                        :class="[call.status === 'degraded' ? 'bg-rose-500' : 'bg-emerald-500']"
                                        :style="{ 
                                            height: `${Math.max(4, call.amplitude > 0 ? (Math.sin(i * 0.7 + call.duration) * (call.amplitude / 100) * 28 + 14) : 4)}px`,
                                            opacity: call.amplitude > 0 ? 1.0 : 0.2
                                        }"
                                    ></div>
                                </div>
                                <span class="text-[10px] font-black font-mono text-slate-400 w-8 text-right">{{ call.amplitude }}dB</span>
                            </div>

                            <!-- Packet metric grid -->
                            <div class="grid grid-cols-3 gap-2 text-[10px] font-mono text-slate-400 bg-slate-900 p-2.5 rounded-xl border-2 border-slate-850">
                                <div>Jitter: <span class="font-black text-white">{{ call.jitter.toFixed(1) }}ms</span></div>
                                <div>Latency: <span class="font-black text-white">{{ call.latency.toFixed(0) }}ms</span></div>
                                <div :class="[call.packet_loss > 5.0 ? 'text-rose-500 font-black' : '']">Loss: {{ call.packet_loss.toFixed(1) }}%</div>
                            </div>

                            <!-- Card Simulation Controls -->
                            <div class="flex gap-2 justify-end pt-1">
                                <button 
                                    @click.stop="triggerMockPacketLoss(call.call_id, 8.2)"
                                    class="rounded-xl border-2 border-rose-900 bg-rose-950 hover:bg-rose-900/60 text-rose-400 px-3 py-1 text-[9px] font-black uppercase transition-colors"
                                >
                                    Force Loss
                                </button>
                                <button 
                                    v-if="call.status !== 'completed' && call.status !== 'disconnected'"
                                    @click.stop="completeCall(call.call_id)"
                                    class="rounded-xl border-2 border-emerald-900 bg-emerald-950 hover:bg-emerald-900/60 text-emerald-400 px-3 py-1 text-[9px] font-black uppercase transition-colors"
                                >
                                    Book Job
                                </button>
                                <button 
                                    v-if="call.status !== 'completed' && call.status !== 'disconnected'"
                                    @click.stop="disconnectCall(call.call_id)"
                                    class="rounded-xl border-2 border-slate-800 bg-slate-900 hover:bg-slate-800 text-slate-400 px-3 py-1 text-[9px] font-black uppercase transition-colors"
                                >
                                    Drop Line
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Waveform & Spectral spectrogram (Visible for selected active call) -->
                <div v-if="selectedCall" class="w-full">
                    <SpectralVisualizer />
                </div>

                <!-- Speech Transcript Balloons -->
                <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-4 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                    <h2 class="text-xl font-black text-white flex items-center gap-2 border-b-4 border-slate-800 pb-4">
                        <Volume2 class="h-6 w-6 text-emerald-500" />
                        Scrolling Speech Transcript
                        <span v-if="selectedCall" class="text-xs font-bold text-slate-400">
                            - Tracking call session <span class="font-mono text-emerald-400">{{ selectedCall.call_id.substring(0, 8) }}</span>
                        </span>
                    </h2>

                    <!-- Empty state -->
                    <div 
                        v-if="!selectedCall" 
                        class="flex flex-col items-center justify-center text-center text-slate-500 py-12"
                    >
                        <VolumeX class="h-12 w-12 text-slate-800 mb-3" />
                        <span class="text-xs font-bold">Select an ongoing call to inspect active transcript streams.</span>
                    </div>

                    <!-- Transcript balloons -->
                    <div 
                        v-else 
                        class="flex flex-col gap-4 overflow-y-auto max-h-[350px] p-4 bg-slate-950 rounded-2xl border-4 border-slate-850"
                    >
                        <div 
                            v-for="(t, idx) in selectedCall.transcripts" 
                            :key="idx"
                            class="flex flex-col gap-1 max-w-[80%] rounded-2xl px-4 py-3 text-xs transition-all border-4 shadow-sm"
                            :class="[
                                t.sender === 'Customer' 
                                    ? 'bg-slate-900 text-slate-100 self-start border-slate-800' 
                                    : t.sender === 'Assistant'
                                      ? 'bg-emerald-600 text-white self-end border-emerald-700 shadow-[2px_2px_0px_0px_rgba(15,23,42,1)]'
                                      : 'bg-amber-950/40 text-amber-400 self-center text-center border-amber-900/60'
                            ]"
                        >
                            <span 
                                class="text-[9px] font-black tracking-widest uppercase"
                                :class="[t.sender === 'Assistant' ? 'text-emerald-200' : 'text-slate-400']"
                            >
                                {{ t.sender }}
                            </span>
                            <p class="font-extrabold mt-1 leading-relaxed">{{ t.text }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar Dashboard (1/3 columns) -->
            <div class="flex flex-col gap-8">
                
                <!-- Playful Mascot widget -->
                <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-4 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                    <h3 class="text-lg font-black text-white flex items-center gap-2 border-b-4 border-slate-800 pb-4">
                        <Sparkles class="h-5 w-5 text-amber-500" />
                        Rive Mascot HUD Bindings
                    </h3>

                    <!-- Character container -->
                    <div class="h-[280px]">
                        <DispatcherMascot 
                            :state="mascotState" 
                            :is-speaking="callStore.isSpeaking"
                            :amplitude="selectedCall ? selectedCall.amplitude : 0"
                            :skin="activeSkin"
                        />
                    </div>

                    <!-- Mascot trigger mapping list -->
                    <div class="flex flex-col gap-2.5 text-xs bg-slate-950 p-4 rounded-2xl border-4 border-slate-800 font-bold text-slate-300">
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest pb-1 border-b border-slate-800">
                            Active Machine Triggers
                        </div>
                        <div class="flex justify-between items-center py-0.5">
                            <span>WebRTC Scanning Radar</span>
                            <span class="rounded bg-emerald-950/80 px-2 py-0.5 text-[10px] font-black text-emerald-400 border border-emerald-800" :class="[mascotState === 1 ? 'ring-2 ring-emerald-500' : '']">
                                Trigger 1
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-0.5">
                            <span>Victory Celebration</span>
                            <span class="rounded bg-amber-950/80 px-2 py-0.5 text-[10px] font-black text-amber-400 border border-amber-800" :class="[mascotState === 2 ? 'ring-2 ring-amber-500' : '']">
                                Trigger 2
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-0.5">
                            <span>Disappointed Error / Lock</span>
                            <span class="rounded bg-rose-950/80 px-2 py-0.5 text-[10px] font-black text-rose-400 border border-rose-800" :class="[mascotState === 3 ? 'ring-2 ring-rose-500' : '']">
                                Trigger 3
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Real-Time Supervisor Whisper Panel -->
                <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-4 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                    <h3 class="text-lg font-black text-white flex items-center gap-2 border-b-4 border-slate-800 pb-4">
                        <Zap class="h-5 w-5 text-amber-400" />
                        Whisper coaching
                    </h3>

                    <div v-if="!selectedCall" class="text-xs font-bold text-slate-500 text-center py-8">
                        Select an active line to start whisper coaching.
                    </div>

                    <form v-else @submit.prevent="sendWhisper" class="flex flex-col gap-4">
                        <div class="bg-slate-950 p-3.5 rounded-2xl border-4 border-slate-800">
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest block">Recipient Technician</span>
                            <span class="font-extrabold text-white text-sm mt-1 block">
                                Dynamic call stream: {{ selectedCall.customer_name }}
                            </span>
                            <span class="text-[11px] font-mono text-emerald-400 mt-0.5 block">
                                Channel: tenant.{{ tenant.id }}.coaching.{{ selectedCall.call_id.substring(0, 8) }}
                            </span>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-black text-slate-400 uppercase tracking-wider">Coaching tip message</label>
                            <textarea 
                                v-model="whisperMessage"
                                placeholder="Type secret instructions here... (e.g. 'Offer standard diagnostic waive')"
                                rows="3"
                                class="rounded-2xl border-4 border-slate-800 bg-slate-950 text-white p-3 text-xs font-extrabold focus:border-emerald-500 focus:outline-none placeholder-slate-600 shadow-inner"
                                required
                            ></textarea>
                        </div>

                        <!-- Whisper sending alert banner -->
                        <div 
                            v-if="whisperStatus.type" 
                            class="rounded-xl border-2 px-3 py-2 text-xs font-bold"
                            :class="[
                                whisperStatus.type === 'success' 
                                    ? 'bg-emerald-950/40 border-emerald-800 text-emerald-400' 
                                    : 'bg-rose-950/40 border-rose-800 text-rose-400'
                            ]"
                        >
                            {{ whisperStatus.message }}
                        </div>

                        <button 
                            type="submit"
                            :disabled="isSendingWhisper || !whisperMessage.trim()"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl border-4 border-emerald-700 bg-emerald-500 hover:bg-emerald-400 text-white py-3 text-xs font-black tracking-wider uppercase transition-all active:translate-y-[2px] active:border-b-2 disabled:opacity-40 disabled:pointer-events-none"
                        >
                            <Send class="h-4.5 w-4.5" />
                            {{ isSendingWhisper ? 'Broadcasting...' : 'Send Whisper tip' }}
                        </button>
                    </form>
                </div>

                <!-- WebRTC websocket telemetry log -->
                <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-4 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)] flex-1">
                    <h3 class="text-lg font-black text-white flex items-center gap-2 border-b-4 border-slate-800 pb-4">
                        <Database class="h-5 w-5 text-emerald-500" />
                        WebSocket events log
                    </h3>
                    
                    <div class="flex-1 overflow-y-auto max-h-[220px] rounded-2xl bg-slate-950 p-4 font-mono text-[10px] text-slate-400 flex flex-col gap-2.5 border-4 border-slate-850">
                        <div v-if="telemetryAlerts.length === 0" class="text-slate-700 text-center py-8 font-bold">
                            Waiting for socket frames...
                        </div>
                        <div 
                            v-for="alert in telemetryAlerts" 
                            :key="alert.id"
                            class="border-b border-slate-900 pb-2 last:border-0"
                        >
                            <span class="text-emerald-500">[{{ alert.timestamp }}]</span>
                            <span 
                                class="ml-1 px-1 rounded text-[9px] uppercase font-black"
                                :class="{
                                    'bg-rose-900 text-rose-300': alert.type === 'degraded' || alert.type === 'error' || alert.type === 'warning',
                                    'bg-cyan-900 text-cyan-300': alert.type === 'telemetry',
                                    'bg-emerald-950/80 text-emerald-400 border border-emerald-800': alert.type === 'success',
                                    'bg-slate-800 text-slate-350': alert.type === 'status',
                                    'bg-amber-900 text-amber-300': alert.type === 'whisper',
                                }"
                            >
                                {{ alert.type }}
                            </span>
                            <p class="mt-1 text-slate-350 leading-relaxed">{{ alert.message }}</p>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</template>

<style scoped>
.bg-card {
    transition: background-color 0.2s, border-color 0.2s;
}
</style>
