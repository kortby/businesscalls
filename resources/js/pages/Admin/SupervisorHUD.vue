<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
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
    StopCircle,
} from '@lucide/vue';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
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
const activeSkin = ref<string>(
    (props.tenant.settings?.active_skin as string) || 'standard',
);
const whisperMessage = ref<string>('');
const whisperStatus = ref<{
    type: 'success' | 'error' | null;
    message: string;
}>({ type: null, message: '' });
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
const telemetryAlerts = ref<
    { id: number; timestamp: string; type: string; message: string }[]
>([]);
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
const isLimitReached = computed(
    () => currentSpendUsage.value >= props.spendLimit,
);

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
        (c) =>
            c.status === 'degraded' ||
            c.status === 'disconnected' ||
            c.packet_loss > 5.0,
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
        (c) =>
            (c.status === 'connected' || c.status === 'ringing') &&
            c.packet_loss <= 2.0,
    );

    if (hasOptimalOngoing) {
        return 1;
    }

    return 1; // Default fallback to scanning if calls exist
});

// Selected call computed helper
const selectedCall = computed(() => {
    return (
        ongoingCalls.value.find((c) => c.call_id === selectedCallId.value) ||
        null
    );
});

// Sync selected call amplitude to global callStore for SpectralVisualizer
watch(
    () => selectedCall.value?.amplitude,
    (newAmp) => {
        callStore.amplitude = (newAmp || 0) / 100;
        callStore.isSpeaking = (newAmp || 0) > 0;
    },
);

// Watch mascotState to update global callStore
watch(mascotState, (newVal) => {
    if (newVal === 3) {
        logAlert(
            'warning',
            'Mascot transitioned to DISAPPOINTED state due to metrics/locks.',
        );
    } else if (newVal === 2) {
        logAlert(
            'success',
            'Mascot transitioned to CELEBRATORY state. Call booked!',
        );
    }
});

// WebSocket updates via Echo
const channelName = props.tenant?.id ? `tenant.${props.tenant.id}` : '';

if (channelName) {
    useEcho(channelName, 'WebRtcTelemetryUpdated', (e: any) => {
        logAlert(
            'telemetry',
            `Telemetry update for call ${e.callId?.substring(0, 8)}: Loss=${e.packetLoss?.toFixed(1)}%, Jitter=${e.jitter?.toFixed(1)}ms`,
        );
        updateCallTelemetry(e.callId, e.jitter, e.latency, e.packetLoss);
    });

    useEcho(channelName, 'CallQualityDegraded', (e: any) => {
        logAlert(
            'degraded',
            `⚠️ Quality DEGRADED on call ${e.callId?.substring(0, 8)}!`,
        );
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
        logAlert(
            'status',
            `New dynamic call started: ${callId?.substring(0, 8)}`,
        );

        // Add to active calls list if not already there
        if (!ongoingCalls.value.some((c) => c.call_id === callId)) {
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
                transcripts: [
                    {
                        sender: 'System',
                        text: 'Live WebRTC stream initialized.',
                    },
                ],
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
        logAlert(
            'status',
            `Call ended dynamically: ${callId?.substring(0, 8)}`,
        );
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
const updateCallTelemetry = (
    callId: string,
    jitter: number,
    latency: number,
    packetLoss: number,
) => {
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
    {
        role: 'Customer',
        text: 'Hey there, I need a plumber to fix our water heater.',
    },
    {
        role: 'Assistant',
        text: 'No problem! I can schedule an appointment. Is tomorrow morning good?',
    },
    { role: 'Customer', text: 'Yes, around 10 AM works best.' },
    {
        role: 'Assistant',
        text: 'Perfect. I have scheduled Bob Jones for tomorrow at 10:00 AM.',
    },
    { role: 'Customer', text: 'Thank you so much! That was super fast.' },
    {
        role: 'Assistant',
        text: 'You are welcome! Have a fantastic day. Goodbye!',
    },
];

// Simulator controls
const startMockCall = () => {
    const randomId = 'call_' + Math.random().toString(36).substring(2, 10);
    const names = [
        'Jerry Seinfeld',
        'Elaine Benes',
        'Cosmo Kramer',
        'George Costanza',
    ];
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
        transcripts: [
            {
                sender: 'System',
                text: '📞 Call incoming, routing to automated assistant...',
            },
        ],
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
            newCall.transcripts.push({
                sender: 'Assistant',
                text: 'Hello, thank you for calling. How can I help you book your service today?',
            });
            logAlert('status', `Call ${randomId.substring(0, 8)} connected.`);
        }
    }, 1200);

    // Call duration timer
    newCall.timerInterval = setInterval(() => {
        if (
            newCall.status !== 'completed' &&
            newCall.status !== 'disconnected'
        ) {
            newCall.duration++;
            currentSpendUsage.value += 0.0025; // simulate spend ticking
        }
    }, 1000);

    // Speech & Amplitude simulation
    let phraseIndex = 0;
    newCall.speechInterval = setInterval(() => {
        if (
            newCall.status === 'completed' ||
            newCall.status === 'disconnected'
        ) {
            clearInterval(newCall.speechInterval);

            return;
        }

        const isSpeakingNow = Math.random() > 0.35;
        newCall.amplitude = isSpeakingNow
            ? Math.floor(40 + Math.random() * 55)
            : 0;

        // Periodically push a transcript phrase
        if (Math.random() > 0.65 && phraseIndex < mockPhrases.length) {
            const phrase = mockPhrases[phraseIndex++];
            newCall.transcripts.push({
                sender: phrase.role,
                text: phrase.text,
            });

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
        call.transcripts.push({
            sender: 'System',
            text: '✅ Call completed. Dispatch schedule synchronized.',
        });
        logAlert(
            'success',
            `Call ${callId.substring(0, 8)} completed successfully!`,
        );

        // Cleanup intervals
        if (call.timerInterval) {
            clearInterval(call.timerInterval);
        }

        if (call.speechInterval) {
            clearInterval(call.speechInterval);
        }

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
        call.transcripts.push({
            sender: 'System',
            text: '❌ Call terminated abruptly.',
        });
        logAlert('error', `Call ${callId.substring(0, 8)} disconnected.`);

        if (call.timerInterval) {
            clearInterval(call.timerInterval);
        }

        if (call.speechInterval) {
            clearInterval(call.speechInterval);
        }

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
            selectedCallId.value =
                ongoingCalls.value.length > 0
                    ? ongoingCalls.value[0].call_id
                    : null;
        }
    }
};

// Force packet loss simulation
const triggerMockPacketLoss = (callId: string, lossAmount: number) => {
    const call = ongoingCalls.value.find((c) => c.call_id === callId);

    if (!call) {
        return;
    }

    call.status = 'degraded';
    call.packet_loss = lossAmount;
    call.jitter = 15.4;
    logAlert(
        'telemetry',
        `⚠️ Simulated high packet loss (${lossAmount}%) on call ${callId.substring(0, 8)}`,
    );
};

// Send supervisor whisper coaching tip
const sendWhisper = async () => {
    if (!selectedCallId.value || !whisperMessage.value.trim()) {
        return;
    }

    isSendingWhisper.value = true;
    whisperStatus.value = { type: null, message: '' };

    try {
        const response = await fetch('/api/web-calls/whisper', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                call_id: selectedCallId.value,
                instruction: whisperMessage.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            whisperStatus.value = {
                type: 'success',
                message: 'Whisper coaching tip sent to technician!',
            };
            logAlert(
                'whisper',
                `Coaching whisper sent for call: ${selectedCallId.value.substring(0, 8)}`,
            );
            whisperMessage.value = '';
        } else {
            whisperStatus.value = {
                type: 'error',
                message: data.error || 'Failed to send whisper.',
            };
        }
    } catch (e: any) {
        whisperStatus.value = {
            type: 'error',
            message: 'Network error sending whisper coaching tip.',
        };
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
    logAlert(
        'warning',
        '⚠️ Spend limit lock triggered! Mascot transitioned to error state.',
    );
};

onMounted(() => {
    startMockCall();
});

onUnmounted(() => {
    ongoingCalls.value.forEach((call) => {
        if (call.timerInterval) {
            clearInterval(call.timerInterval);
        }

        if (call.speechInterval) {
            clearInterval(call.speechInterval);
        }
    });
});

const formatTime = (seconds: number) => {
    const m = Math.floor(seconds / 60)
        .toString()
        .padStart(2, '0');
    const s = (seconds % 60).toString().padStart(2, '0');

    return `${m}:${s}`;
};

const spendPercentage = computed(() => {
    if (props.spendLimit <= 0) {
        return 0;
    }

    return Math.min(100, (currentSpendUsage.value / props.spendLimit) * 100);
});
</script>

<template>
    <Head title="Supervisor HUD" />

    <div
        class="mx-auto flex min-h-screen max-w-[1400px] flex-col gap-8 p-4 text-foreground sm:p-6 md:p-8"
    >
        <!-- Duolingo style Geometric Title Banner -->
        <div
            class="relative overflow-hidden rounded-3xl border-4 border-border bg-card p-6 text-card-foreground shadow-[6px_6px_0px_0px_rgba(16,185,129,0.3)] sm:p-8"
        >
            <div
                class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h1
                        class="flex items-center gap-3 text-3xl font-black tracking-tight text-foreground sm:text-4xl"
                    >
                        <span
                            class="rounded-2xl border-4 border-emerald-500 bg-emerald-600 px-3 py-1 text-white shadow-[2px_2px_0px_0px_rgba(15,23,42,1)]"
                            >HUD</span
                        >
                        Supervisor Control Dashboard
                    </h1>
                    <p
                        class="mt-2 text-sm font-bold text-muted-foreground sm:text-base"
                    >
                        Real-time call console, packet telemetry metrics, Rive
                        mascot bindings, and private whispers.
                    </p>
                </div>

                <!-- Spend limit gauge with thick borders -->
                <div
                    class="flex min-w-[280px] flex-col gap-2 rounded-2xl border-4 border-border bg-muted/50 p-4 dark:border-slate-800 dark:bg-slate-950"
                >
                    <div
                        class="flex justify-between text-xs font-black tracking-widest text-muted-foreground uppercase"
                    >
                        <span>Tenant Billing Limit</span>
                        <span
                            :class="[
                                isLimitReached
                                    ? 'text-rose-500'
                                    : 'text-emerald-500',
                            ]"
                        >
                            ${{ currentSpendUsage.toFixed(2) }} / ${{
                                spendLimit.toFixed(2)
                            }}
                        </span>
                    </div>

                    <div
                        class="h-4 w-full overflow-hidden rounded-full border-2 border-border bg-muted dark:border-slate-700 dark:bg-slate-900"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-300"
                            :class="[
                                isLimitReached
                                    ? 'bg-rose-500'
                                    : spendPercentage > 85
                                      ? 'bg-amber-500'
                                      : 'bg-emerald-500',
                            ]"
                            :style="{ width: `${spendPercentage}%` }"
                        ></div>
                    </div>

                    <div class="flex items-center justify-between text-[11px]">
                        <span
                            v-if="isLimitReached"
                            class="inline-flex items-center gap-1 rounded-lg border-2 border-rose-800 bg-rose-900/40 px-2 py-0.5 font-black text-rose-400 uppercase"
                        >
                            <WifiOff class="h-3 w-3" /> Suspended
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 rounded-lg border-2 border-emerald-800 bg-emerald-900/40 px-2 py-0.5 font-black text-emerald-400 uppercase"
                        >
                            Active
                        </span>
                        <div class="flex gap-2">
                            <button
                                v-if="!isLimitReached"
                                @click="triggerSpendLock"
                                class="text-[9px] font-black tracking-wider text-rose-400 uppercase hover:text-rose-300"
                            >
                                Simulate Lock
                            </button>
                            <button
                                v-else
                                @click="resetSpendUsage"
                                class="text-[9px] font-black tracking-wider text-emerald-400 uppercase hover:text-emerald-300"
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
                <div
                    class="flex flex-col gap-6 rounded-3xl border-4 border-border bg-card p-6 text-card-foreground shadow-[4px_4px_0px_0px_rgba(30,41,59,0.15)]"
                >
                    <div
                        class="flex flex-col gap-4 border-b-4 border-border pb-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <h2
                            class="flex items-center gap-2 text-xl font-black text-foreground"
                        >
                            <Activity
                                class="h-6 w-6 animate-pulse text-emerald-500"
                            />
                            Live Call Console
                            <span
                                class="rounded-full border-2 border-emerald-500/20 bg-emerald-50 px-3 py-0.5 text-xs font-black text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400"
                            >
                                {{ ongoingCalls.length }} Ongoing
                            </span>
                        </h2>

                        <div class="flex gap-2">
                            <button
                                @click="startMockCall"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-2xl border-4 border-emerald-700 bg-emerald-500 px-4 py-2 text-xs font-black tracking-wider text-white uppercase transition-all hover:bg-emerald-400 active:translate-y-[2px] active:border-b-2"
                            >
                                <Play class="h-4 w-4" /> Simulate Call
                            </button>
                        </div>
                    </div>

                    <!-- Call list empty state -->
                    <div
                        v-if="ongoingCalls.length === 0"
                        class="flex flex-col items-center justify-center rounded-2xl border-4 border-dashed border-border bg-muted/30 px-4 py-16 text-center"
                    >
                        <Phone
                            class="mb-4 h-16 w-16 animate-bounce text-muted-foreground/60"
                        />
                        <h3 class="text-lg font-black text-muted-foreground">
                            All call channels are currently quiet
                        </h3>
                        <p
                            class="mt-2 max-w-sm text-xs font-bold text-muted-foreground/80"
                        >
                            Simulate inbound technician streams to view active
                            packet metrics, speech wave amplitude, and dispatch
                            triggers.
                        </p>
                    </div>

                    <!-- Ongoing Calls Grid -->
                    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div
                            v-for="call in ongoingCalls"
                            :key="call.call_id"
                            @click="selectedCallId = call.call_id"
                            class="relative flex cursor-pointer flex-col gap-4 rounded-2xl border-4 p-4 shadow-sm transition-all"
                            :class="[
                                selectedCallId === call.call_id
                                    ? 'border-emerald-500 bg-emerald-500/5 shadow-[4px_4px_0_0_rgba(16,185,129,0.2)] dark:bg-emerald-950/20'
                                    : 'border-border bg-muted/20 hover:border-slate-400 dark:bg-slate-950 dark:hover:border-slate-700',
                            ]"
                        >
                            <!-- Card Header -->
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4
                                        class="text-base font-black tracking-tight text-foreground"
                                    >
                                        {{ call.customer_name }}
                                    </h4>
                                    <p
                                        class="mt-0.5 font-mono text-xs text-muted-foreground"
                                    >
                                        {{ call.customer_phone }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-lg border-2 px-2.5 py-0.5 text-[9px] font-black uppercase"
                                        :class="{
                                            'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-400':
                                                call.status === 'ringing',
                                            'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400':
                                                call.status === 'connected',
                                            'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-800 dark:bg-rose-950/60 dark:text-rose-400':
                                                call.status === 'degraded',
                                            'border-border bg-secondary text-secondary-foreground':
                                                call.status === 'completed' ||
                                                call.status === 'disconnected',
                                        }"
                                    >
                                        {{ call.status }}
                                    </span>

                                    <span
                                        class="flex items-center gap-0.5 font-mono text-xs font-black text-muted-foreground"
                                    >
                                        <Clock class="h-3 w-3" />
                                        {{ formatTime(call.duration) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Real-time Amplitude Waveform (Dynamic height bars) -->
                            <div
                                class="flex items-center gap-1.5 rounded-xl border-y border-border bg-muted/40 px-2 py-3 dark:bg-slate-900/50"
                            >
                                <div
                                    class="flex h-8 flex-1 items-end justify-between"
                                >
                                    <div
                                        v-for="i in 18"
                                        :key="i"
                                        class="w-1.5 rounded-full transition-all duration-75"
                                        :class="[
                                            call.status === 'degraded'
                                                ? 'bg-rose-500'
                                                : 'bg-emerald-500',
                                        ]"
                                        :style="{
                                            height: `${Math.max(4, call.amplitude > 0 ? Math.sin(i * 0.7 + call.duration) * (call.amplitude / 100) * 28 + 14 : 4)}px`,
                                            opacity:
                                                call.amplitude > 0 ? 1.0 : 0.2,
                                        }"
                                    ></div>
                                </div>
                                <span
                                    class="w-8 text-right font-mono text-[10px] font-black text-muted-foreground"
                                    >{{ call.amplitude }}dB</span
                                >
                            </div>

                            <!-- Packet metric grid -->
                            <div
                                class="grid grid-cols-3 gap-2 rounded-xl border border-border bg-muted/60 p-2.5 font-mono text-[10px] text-muted-foreground dark:border-slate-800/80 dark:bg-slate-900"
                            >
                                <div>
                                    Jitter:
                                    <span class="font-black text-foreground"
                                        >{{ call.jitter.toFixed(1) }}ms</span
                                    >
                                </div>
                                <div>
                                    Latency:
                                    <span class="font-black text-foreground"
                                        >{{ call.latency.toFixed(0) }}ms</span
                                    >
                                </div>
                                <div
                                    :class="[
                                        call.packet_loss > 5.0
                                            ? 'font-black text-rose-600 dark:text-rose-500'
                                            : '',
                                    ]"
                                >
                                    Loss: {{ call.packet_loss.toFixed(1) }}%
                                </div>
                            </div>

                            <!-- Card Simulation Controls -->
                            <div class="flex justify-end gap-2 pt-1">
                                <button
                                    @click.stop="
                                        triggerMockPacketLoss(call.call_id, 8.2)
                                    "
                                    class="cursor-pointer rounded-xl border border-rose-500/20 bg-rose-50 px-3 py-1 text-[9px] font-black text-rose-600 uppercase transition-colors hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-400 dark:hover:bg-rose-900/60"
                                >
                                    Force Loss
                                </button>
                                <button
                                    v-if="
                                        call.status !== 'completed' &&
                                        call.status !== 'disconnected'
                                    "
                                    @click.stop="completeCall(call.call_id)"
                                    class="cursor-pointer rounded-xl border border-emerald-500/20 bg-emerald-50 px-3 py-1 text-[9px] font-black text-emerald-600 uppercase transition-colors hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-400 dark:hover:bg-emerald-900/60"
                                >
                                    Book Job
                                </button>
                                <button
                                    v-if="
                                        call.status !== 'completed' &&
                                        call.status !== 'disconnected'
                                    "
                                    @click.stop="disconnectCall(call.call_id)"
                                    class="cursor-pointer rounded-xl border border-border bg-secondary px-3 py-1 text-[9px] font-black text-secondary-foreground uppercase transition-colors hover:bg-secondary/80"
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
                <div
                    class="flex flex-col gap-4 rounded-3xl border-4 border-border bg-card p-6 text-card-foreground shadow-[4px_4px_0px_0px_rgba(30,41,59,0.15)]"
                >
                    <h2
                        class="flex items-center gap-2 border-b-4 border-border pb-4 text-xl font-black text-foreground"
                    >
                        <Volume2 class="h-6 w-6 text-emerald-500" />
                        Scrolling Speech Transcript
                        <span
                            v-if="selectedCall"
                            class="text-xs font-bold text-muted-foreground"
                        >
                            - Tracking call session
                            <span class="font-mono text-emerald-400">{{
                                selectedCall.call_id.substring(0, 8)
                            }}</span>
                        </span>
                    </h2>

                    <!-- Empty state -->
                    <div
                        v-if="!selectedCall"
                        class="flex flex-col items-center justify-center py-12 text-center text-muted-foreground"
                    >
                        <VolumeX
                            class="mb-3 h-12 w-12 text-muted-foreground/40"
                        />
                        <span class="text-xs font-bold"
                            >Select an ongoing call to inspect active transcript
                            streams.</span
                        >
                    </div>

                    <!-- Transcript balloons -->
                    <div
                        v-else
                        class="dark:border-slate-850 flex max-h-[350px] flex-col gap-4 overflow-y-auto rounded-2xl border-4 border-border bg-muted/20 p-4 dark:bg-slate-950"
                    >
                        <div
                            v-for="(t, idx) in selectedCall.transcripts"
                            :key="idx"
                            class="flex max-w-[80%] flex-col gap-1 rounded-2xl border-4 px-4 py-3 text-xs shadow-sm transition-all"
                            :class="[
                                t.sender === 'Customer'
                                    ? 'self-start border-border bg-card text-foreground shadow-xs'
                                    : t.sender === 'Assistant'
                                      ? 'self-end border-emerald-700 bg-emerald-600 text-white shadow-[2px_2px_0px_0px_rgba(15,23,42,1)]'
                                      : 'text-amber-850 dark:text-amber-450 self-center border-amber-200 bg-amber-50 text-center dark:border-amber-900/60 dark:bg-amber-950/40',
                            ]"
                        >
                            <span
                                class="text-[9px] font-black tracking-widest uppercase"
                                :class="[
                                    t.sender === 'Assistant'
                                        ? 'text-emerald-200'
                                        : 'text-muted-foreground',
                                ]"
                            >
                                {{ t.sender }}
                            </span>
                            <p class="mt-1 leading-relaxed font-extrabold">
                                {{ t.text }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar Dashboard (1/3 columns) -->
            <div class="flex flex-col gap-8">
                <!-- Playful Mascot widget -->
                <div
                    class="flex flex-col gap-4 rounded-3xl border-4 border-border bg-card p-6 text-card-foreground shadow-[4px_4px_0px_0px_rgba(30,41,59,0.15)]"
                >
                    <h3
                        class="flex items-center gap-2 border-b-4 border-border pb-4 text-lg font-black text-foreground"
                    >
                        <Sparkles class="h-5 w-5 text-amber-500" />
                        Rive Mascot HUD Bindings
                    </h3>

                    <!-- Character container -->
                    <div class="h-[280px]">
                        <DispatcherMascot
                            :state="mascotState"
                            :is-speaking="callStore.isSpeaking"
                            :amplitude="
                                selectedCall ? selectedCall.amplitude : 0
                            "
                            :skin="activeSkin"
                        />
                    </div>

                    <!-- Mascot trigger mapping list -->
                    <div
                        class="flex flex-col gap-2.5 rounded-2xl border-4 border-border bg-muted/40 p-4 text-xs font-bold text-muted-foreground dark:border-slate-800 dark:bg-slate-950"
                    >
                        <div
                            class="border-b border-border pb-1 text-[10px] font-black tracking-widest text-muted-foreground uppercase"
                        >
                            Active Machine Triggers
                        </div>
                        <div class="flex items-center justify-between py-0.5">
                            <span>WebRTC Scanning Radar</span>
                            <span
                                class="rounded border border-emerald-300 bg-emerald-50 px-2 py-0.5 text-[10px] font-black text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-400"
                                :class="[
                                    mascotState === 1
                                        ? 'ring-2 ring-emerald-500'
                                        : '',
                                ]"
                            >
                                Trigger 1
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-0.5">
                            <span>Victory Celebration</span>
                            <span
                                class="rounded border border-amber-300 bg-amber-50 px-2 py-0.5 text-[10px] font-black text-amber-700 dark:border-amber-800 dark:bg-amber-950/80 dark:text-amber-400"
                                :class="[
                                    mascotState === 2
                                        ? 'ring-2 ring-amber-500'
                                        : '',
                                ]"
                            >
                                Trigger 2
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-0.5">
                            <span>Disappointed Error / Lock</span>
                            <span
                                class="rounded border border-rose-300 bg-rose-50 px-2 py-0.5 text-[10px] font-black text-rose-700 dark:border-rose-800 dark:bg-rose-950/80 dark:text-rose-400"
                                :class="[
                                    mascotState === 3
                                        ? 'ring-2 ring-rose-500'
                                        : '',
                                ]"
                            >
                                Trigger 3
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Real-Time Supervisor Whisper Panel -->
                <div
                    class="flex flex-col gap-4 rounded-3xl border-4 border-border bg-card p-6 text-card-foreground shadow-[4px_4px_0px_0px_rgba(30,41,59,0.15)]"
                >
                    <h3
                        class="flex items-center gap-2 border-b-4 border-border pb-4 text-lg font-black text-foreground"
                    >
                        <Zap class="h-5 w-5 text-amber-400" />
                        Whisper coaching
                    </h3>

                    <div
                        v-if="!selectedCall"
                        class="py-8 text-center text-xs font-bold text-muted-foreground"
                    >
                        Select an active line to start whisper coaching.
                    </div>

                    <form
                        v-else
                        @submit.prevent="sendWhisper"
                        class="flex flex-col gap-4"
                    >
                        <div
                            class="rounded-2xl border-4 border-border bg-muted/40 p-3.5 dark:border-slate-800 dark:bg-slate-950"
                        >
                            <span
                                class="block text-[10px] font-black tracking-widest text-muted-foreground uppercase"
                                >Recipient Technician</span
                            >
                            <span
                                class="mt-1 block text-sm font-extrabold text-foreground"
                            >
                                Dynamic call stream:
                                {{ selectedCall.customer_name }}
                            </span>
                            <span
                                class="mt-0.5 block font-mono text-[11px] text-emerald-600 dark:text-emerald-400"
                            >
                                Channel: tenant.{{ tenant.id }}.coaching.{{
                                    selectedCall.call_id.substring(0, 8)
                                }}
                            </span>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label
                                class="text-xs font-black tracking-wider text-muted-foreground uppercase"
                                >Coaching tip message</label
                            >
                            <textarea
                                v-model="whisperMessage"
                                placeholder="Type secret instructions here... (e.g. 'Offer standard diagnostic waive')"
                                rows="3"
                                class="rounded-2xl border-4 border-border bg-muted/20 p-3 text-xs font-extrabold text-foreground placeholder-muted-foreground/60 shadow-inner focus:border-emerald-500 focus:outline-none dark:bg-slate-950"
                                required
                            ></textarea>
                        </div>

                        <!-- Whisper sending alert banner -->
                        <div
                            v-if="whisperStatus.type"
                            class="rounded-xl border-2 px-3 py-2 text-xs font-bold"
                            :class="[
                                whisperStatus.type === 'success'
                                    ? 'border-emerald-250 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400'
                                    : 'border-rose-250 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-400',
                            ]"
                        >
                            {{ whisperStatus.message }}
                        </div>

                        <button
                            type="submit"
                            :disabled="
                                isSendingWhisper || !whisperMessage.trim()
                            "
                            class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border-4 border-emerald-700 bg-emerald-500 py-3 text-xs font-black tracking-wider text-white uppercase transition-all hover:bg-emerald-400 active:translate-y-[2px] active:border-b-2 disabled:pointer-events-none disabled:opacity-40"
                        >
                            <Send class="h-4.5 w-4.5" />
                            {{
                                isSendingWhisper
                                    ? 'Broadcasting...'
                                    : 'Send Whisper tip'
                            }}
                        </button>
                    </form>
                </div>

                <!-- WebRTC websocket telemetry log -->
                <div
                    class="flex flex-1 flex-col gap-4 rounded-3xl border-4 border-border bg-card p-6 text-card-foreground shadow-[4px_4px_0px_0px_rgba(30,41,59,0.15)]"
                >
                    <h3
                        class="flex items-center gap-2 border-b-4 border-border pb-4 text-lg font-black text-foreground"
                    >
                        <Database class="h-5 w-5 text-emerald-500" />
                        WebSocket events log
                    </h3>

                    <div
                        class="dark:border-slate-850 flex max-h-[220px] flex-1 flex-col gap-2.5 overflow-y-auto rounded-2xl border-4 border-border bg-muted/20 p-4 font-mono text-[10px] text-muted-foreground dark:bg-slate-950"
                    >
                        <div
                            v-if="telemetryAlerts.length === 0"
                            class="py-8 text-center font-bold text-muted-foreground/60"
                        >
                            Waiting for socket frames...
                        </div>
                        <div
                            v-for="alert in telemetryAlerts"
                            :key="alert.id"
                            class="border-b border-border/60 pb-2 last:border-0"
                        >
                            <span class="text-emerald-600 dark:text-emerald-500"
                                >[{{ alert.timestamp }}]</span
                            >
                            <span
                                class="ml-1 rounded px-1 text-[9px] font-black uppercase"
                                :class="{
                                    'border border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-900 dark:text-rose-300':
                                        alert.type === 'degraded' ||
                                        alert.type === 'error' ||
                                        alert.type === 'warning',
                                    'border border-cyan-200 bg-cyan-50 text-cyan-800 dark:border-cyan-800 dark:bg-cyan-900 dark:text-cyan-300':
                                        alert.type === 'telemetry',
                                    'border border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-400':
                                        alert.type === 'success',
                                    'border border-border bg-muted text-muted-foreground':
                                        alert.type === 'status',
                                    'border border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-900 dark:text-amber-300':
                                        alert.type === 'whisper',
                                }"
                            >
                                {{ alert.type }}
                            </span>
                            <p class="mt-1 leading-relaxed text-foreground/90">
                                {{ alert.message }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-card {
    transition:
        background-color 0.2s,
        border-color 0.2s;
}
</style>
