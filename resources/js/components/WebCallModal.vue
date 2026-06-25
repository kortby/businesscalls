<script setup lang="ts">
import { Phone, PhoneOff, Mic, MicOff, Volume2 } from '@lucide/vue';
import { ref, watch, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { callStore } from '@/lib/store';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';

const props = defineProps<{
    isOpen: boolean;
    phone: string;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'call_started'): void;
    (e: 'call_ended'): void;
}>();

const callStatus = ref<'idle' | 'connecting' | 'connected' | 'ended' | 'error'>(
    'idle',
);
const isMuted = ref(false);
const errorMessage = ref('');
const isReconnecting = ref(false);
const reconnectAttempts = ref(0);

let vapiInstance: any = null;
let retellInstance: any = null;
const page = usePage();
let telemetryInterval: any = null;
let tokenExpiresAt = 0;
let rotationInterval: any = null;

const startTelemetryCollection = (
    callId: string,
    provider: 'vapi' | 'retell',
) => {
    const latencies: number[] = [];
    const tenantId = (page.props as any).auth?.user?.tenant_id || 1;
    let violationStart: number | null = null;
    let alertSent = false;

    telemetryInterval = setInterval(async () => {
        let stats = { jitter: 0, latency: 0, packetLoss: 0 };

        if (provider === 'vapi' && vapiInstance) {
            const dailyCall =
                typeof vapiInstance.getDailyCallObject === 'function'
                    ? vapiInstance.getDailyCallObject()
                    : vapiInstance.daily || null;
            if (dailyCall && typeof dailyCall.getNetworkStats === 'function') {
                try {
                    const netStats = await dailyCall.getNetworkStats();
                    const latest = netStats?.stats?.latest;
                    if (latest) {
                        stats.latency =
                            (latest.networkRoundTripTime || 0) * 1000;
                        stats.packetLoss = latest.audioRecvPacketLoss || 0;
                    }
                } catch (e) {
                    console.error('Vapi telemetry stats error:', e);
                }
            }
        } else if (
            provider === 'retell' &&
            retellInstance &&
            retellInstance.room
        ) {
            try {
                const pc = retellInstance.room?.engine?.subscriber?.pcTransport;
                if (pc && typeof pc.getStats === 'function') {
                    const rawStats = await pc.getStats();
                    rawStats.forEach((report: any) => {
                        if (
                            report.type === 'inbound-rtp' &&
                            report.kind === 'audio'
                        ) {
                            stats.packetLoss = report.packetsLost || 0;
                        }
                        if (
                            report.type === 'candidate-pair' &&
                            report.state === 'succeeded'
                        ) {
                            stats.latency =
                                (report.currentRoundTripTime || 0) * 1000;
                        }
                    });
                }
            } catch (e) {
                console.error('Retell telemetry stats error:', e);
            }
        }

        // Standard Deviation Jitter Calculation
        if (stats.latency > 0) {
            latencies.push(stats.latency);
            if (latencies.length > 100) {
                latencies.shift();
            }
        }

        const J = latencies.length;
        if (J > 0) {
            const sum = latencies.reduce((acc, val) => acc + val, 0);
            const avg = sum / J;
            const squaredDiffSum = latencies.reduce(
                (acc, val) => acc + Math.pow(val - avg, 2),
                0,
            );
            stats.jitter = Math.sqrt(squaredDiffSum / J);
        }

        try {
            await fetch('/api/telemetry/webrtc', {
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
                    tenant_id: tenantId,
                    call_id: callId,
                    jitter: stats.jitter,
                    latency: stats.latency,
                    packet_loss: stats.packetLoss,
                }),
            });
        } catch (e) {
            // Silently swallow reporting failures
        }

        // Check for threshold violations: jitter > 30 or packetLoss > 5% (0.05 or 5)
        const hasViolation = stats.jitter > 30 || stats.packetLoss > 0.05 || stats.packetLoss > 5;
        if (hasViolation) {
            if (violationStart === null) {
                violationStart = Date.now();
            } else if (Date.now() - violationStart >= 3000) {
                if (!alertSent) {
                    alertSent = true;
                    try {
                        await fetch('/api/telemetry/quality-degraded', {
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
                                tenant_id: tenantId,
                                call_id: callId,
                                packet_loss: stats.packetLoss,
                                rtp_jitter: stats.jitter,
                            }),
                        });
                    } catch (err) {
                        // Silently swallow quality-degraded reporting failures
                    }
                }
            }
        } else {
            violationStart = null;
            alertSent = false;
        }
    }, 2000);
};

const stopTelemetryCollection = () => {
    if (telemetryInterval) {
        clearInterval(telemetryInterval);
        telemetryInterval = null;
    }
};

const startTokenRotationLoop = (provider: string) => {
    stopTokenRotationLoop();

    rotationInterval = setInterval(async () => {
        const timeLeft = tokenExpiresAt - Date.now();
        if (timeLeft > 0 && timeLeft <= 5 * 60 * 1000) {
            console.log('Token is within 5 minutes of expiring. Rotating...');
            try {
                const response = await fetch('/api/web-calls/refresh-token', {
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
                });

                if (response.ok) {
                    const data = await response.json();
                    const newAccessToken = data.access_token;
                    const expiresIn = data.expires_in || 3600;
                    tokenExpiresAt = Date.now() + expiresIn * 1000;

                    if (provider === 'vapi' && vapiInstance) {
                        if (typeof vapiInstance.setToken === 'function') {
                            vapiInstance.setToken(newAccessToken);
                        } else {
                            vapiInstance.sdkKey = newAccessToken;
                        }
                        console.log('Successfully rotated Vapi WebRTC token.');
                    } else if (provider === 'retell' && retellInstance) {
                        if (typeof retellInstance.updateAccessToken === 'function') {
                            retellInstance.updateAccessToken(newAccessToken);
                        } else {
                            retellInstance.accessToken = newAccessToken;
                        }
                        console.log('Successfully rotated Retell WebRTC token.');
                    }
                } else {
                    console.error('Failed to refresh WebRTC token:', response.statusText);
                }
            } catch (e) {
                console.error('Error during WebRTC token rotation handshake:', e);
            }
        }
    }, 10000);
};

const stopTokenRotationLoop = () => {
    if (rotationInterval) {
        clearInterval(rotationInterval);
        rotationInterval = null;
    }
};

let audioCtx: AudioContext | null = null;
let analyser: AnalyserNode | null = null;
let dataArray: Uint8Array | null = null;
let animationFrameId: number | null = null;
let simAudioInterval: any = null;

const startAudioPolling = (vapiInst: any, isSimulated = false) => {
    stopAudioPolling();

    if (isSimulated) {
        simAudioInterval = setInterval(() => {
            if (Math.random() < 0.3) {
                callStore.isSpeaking = !callStore.isSpeaking;
            }
            if (callStore.isSpeaking) {
                callStore.amplitude = 0.15 + Math.random() * 0.45;
            } else {
                callStore.amplitude = 0;
            }
        }, 150);
        return;
    }

    let attempts = 0;
    const interval = setInterval(() => {
        attempts++;
        if (attempts > 100 || !vapiInst) {
            clearInterval(interval);
            return;
        }

        const dailyCall =
            typeof vapiInst.getDailyCallObject === 'function'
                ? vapiInst.getDailyCallObject()
                : vapiInst.daily || null;

        if (dailyCall) {
            clearInterval(interval);

            dailyCall.on('track-started', (event: any) => {
                if (
                    event.participant &&
                    !event.participant.local &&
                    event.track &&
                    event.track.kind === 'audio'
                ) {
                    try {
                        if (!audioCtx) {
                            audioCtx = new (
                                window.AudioContext ||
                                (window as any).webkitAudioContext
                            )();
                        }
                        const source = audioCtx.createMediaStreamSource(
                            new MediaStream([event.track]),
                        );
                        analyser = audioCtx.createAnalyser();
                        analyser.fftSize = 256;
                        source.connect(analyser);
                        dataArray = new Uint8Array(analyser.frequencyBinCount);
                        callStore.analyserNode = analyser;

                        const updateAmplitude = () => {
                            if (analyser && dataArray) {
                                analyser.getByteTimeDomainData(dataArray);
                                let sum = 0;
                                for (let i = 0; i < dataArray.length; i++) {
                                    const v = (dataArray[i] - 128) / 128;
                                    sum += v * v;
                                }
                                const rms = Math.sqrt(sum / dataArray.length);
                                callStore.amplitude = rms;
                                callStore.isSpeaking = rms > 0.03;
                            }
                            animationFrameId =
                                requestAnimationFrame(updateAmplitude);
                        };
                        updateAmplitude();
                    } catch (e) {
                        console.error(
                            'Audio extraction context initialization failed:',
                            e,
                        );
                    }
                }
            });
        }
    }, 100);
};

const stopAudioPolling = () => {
    if (simAudioInterval) {
        clearInterval(simAudioInterval);
        simAudioInterval = null;
    }
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
    }
    if (audioCtx) {
        audioCtx.close().catch(() => {});
        audioCtx = null;
    }
    analyser = null;
    dataArray = null;
    callStore.analyserNode = null;
    callStore.amplitude = 0;
    callStore.isSpeaking = false;
};

const bindRetellListeners = (assistantId: string, callId: string) => {
    if (!retellInstance) return;

    retellInstance.on('call_started', () => {
        callStatus.value = 'connected';
        emit('call_started');
        startTelemetryCollection(callId, 'retell');
        startAudioPolling(null, true);
        reconnectAttempts.value = 0;
    });

    retellInstance.on('call_ended', () => {
        if (!isReconnecting.value) {
            callStatus.value = 'ended';
            emit('call_ended');
            cleanupCall();
        }
    });

    retellInstance.on('error', (err: any) => {
        console.error('Retell error:', err);
        if (callStatus.value === 'connected' || isReconnecting.value) {
            handleReconnection('retell', assistantId, callId);
        } else {
            errorMessage.value = err?.message || 'A network error occurred during the call.';
            callStatus.value = 'error';
            emit('call_ended');
            cleanupCall();
        }
    });

    retellInstance.on('connection_lost', () => {
        console.warn('Retell connection lost');
        handleReconnection('retell', assistantId, callId);
    });

    retellInstance.on('close', () => {
        console.warn('Retell connection closed');
        if (callStatus.value === 'connected') {
            handleReconnection('retell', assistantId, callId);
        }
    });
};

const bindVapiListeners = (assistantId: string, callId: string) => {
    if (!vapiInstance) return;

    vapiInstance.on('call-start', (call: any) => {
        callStatus.value = 'connected';
        emit('call_started');
        const cid = call?.id || callId || 'vapi-call';
        startTelemetryCollection(cid, 'vapi');
        startAudioPolling(vapiInstance, false);
        reconnectAttempts.value = 0;
    });

    vapiInstance.on('call-end', () => {
        if (!isReconnecting.value) {
            callStatus.value = 'ended';
            emit('call_ended');
            cleanupCall();
        }
    });

    vapiInstance.on('error', (err: any) => {
        console.error('Vapi error:', err);
        if (callStatus.value === 'connected' || isReconnecting.value) {
            handleReconnection('vapi', assistantId, callId);
        } else {
            errorMessage.value = err?.message || 'Failed to establish WebRTC connection.';
            callStatus.value = 'error';
            emit('call_ended');
            cleanupCall();
        }
    });

    vapiInstance.on('connection_lost', () => {
        console.warn('Vapi connection lost');
        handleReconnection('vapi', assistantId, callId);
    });

    vapiInstance.on('close', () => {
        console.warn('Vapi connection closed');
        if (callStatus.value === 'connected') {
            handleReconnection('vapi', assistantId, callId);
        }
    });
};

const handleReconnection = async (provider: 'vapi' | 'retell', assistantId: string, currentCallId: string) => {
    if (isReconnecting.value) {
        return;
    }
    isReconnecting.value = true;
    reconnectAttempts.value++;

    if (reconnectAttempts.value > 3) {
        isReconnecting.value = false;
        errorMessage.value = 'Reconnection failed after maximum attempts.';
        callStatus.value = 'error';
        emit('call_ended');
        cleanupCall();
        return;
    }

    console.warn(`WebRTC connection dropped. Attempting auto-reconnection (${reconnectAttempts.value}/3)...`);
    callStatus.value = 'connecting';

    try {
        const response = await fetch('/api/web-calls/refresh-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Token refresh failed');
        }

        const data = await response.json();
        const newAccessToken = data.access_token;
        const expiresIn = data.expires_in || 3600;
        tokenExpiresAt = Date.now() + expiresIn * 1000;

        if (provider === 'retell') {
            if (retellInstance) {
                try { retellInstance.stopCall(); } catch (e) {}
            }
            const { RetellWebClient } = await import('retell-client-js-sdk');
            retellInstance = new RetellWebClient();
            callStore.retellClient = retellInstance;

            bindRetellListeners(assistantId, currentCallId);

            await retellInstance.startCall({
                accessToken: newAccessToken,
            });
        } else {
            if (vapiInstance) {
                try { vapiInstance.stop(); } catch (e) {}
            }
            const Vapi = (await import('@vapi-ai/web')).default;
            vapiInstance = new Vapi(newAccessToken);
            callStore.vapiClient = vapiInstance;

            bindVapiListeners(assistantId, currentCallId);

            vapiInstance.start(assistantId);
        }

        isReconnecting.value = false;
        callStatus.value = 'connected';
        console.log('WebRTC session auto-reconnection handshake successful!');
    } catch (err: any) {
        console.error('Reconnection attempt failed:', err);
        setTimeout(() => {
            isReconnecting.value = false;
            handleReconnection(provider, assistantId, currentCallId);
        }, 3000);
    }
};

const startCall = async () => {
    callStatus.value = 'connecting';
    errorMessage.value = '';

    try {
        const response = await fetch('/api/web-calls/token', {
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
        });

        if (!response.ok) {
            const errData = await response.json().catch(() => ({}));

            throw new Error(
                errData.error || 'Failed to authorize call session.',
            );
        }

        const data = await response.json();
        const { provider, access_token, assistant_id, call_id } = data;

        tokenExpiresAt = Date.now() + (data.expires_in || 3600) * 1000;
        startTokenRotationLoop(provider);

        if (provider === 'retell') {
            const { RetellWebClient } = await import('retell-client-js-sdk');
            retellInstance = new RetellWebClient();
            callStore.retellClient = retellInstance;

            bindRetellListeners(assistant_id, call_id || 'retell-call');

            await retellInstance.startCall({
                accessToken: access_token,
            });
        } else {
            // Default to Vapi
            const Vapi = (await import('@vapi-ai/web')).default;
            vapiInstance = new Vapi(access_token);
            callStore.vapiClient = vapiInstance;

            bindVapiListeners(assistant_id, call_id || 'vapi-call');

            vapiInstance.start(assistant_id);
        }
    } catch (err: any) {
        console.error('Error starting web call:', err);
        errorMessage.value =
            err.message || 'Unable to connect to telephony provider.';
        callStatus.value = 'error';
    }
};

const endCall = () => {
    if (vapiInstance) {
        try {
            vapiInstance.stop();
        } catch (e) {
            console.error(e);
        }
    }

    if (retellInstance) {
        try {
            retellInstance.stopCall();
        } catch (e) {
            console.error(e);
        }
    }

    cleanupCall();
    callStatus.value = 'ended';
    emit('call_ended');
};

const cleanupCall = () => {
    stopTelemetryCollection();
    stopAudioPolling();
    stopTokenRotationLoop();
    vapiInstance = null;
    retellInstance = null;
    callStore.vapiClient = null;
    callStore.retellClient = null;
    callStore.isSpeaking = false;
    callStore.transcript = '';
    callStore.amplitude = 0;
    isReconnecting.value = false;
    reconnectAttempts.value = 0;
};

const toggleMute = () => {
    isMuted.value = !isMuted.value;

    if (vapiInstance) {
        vapiInstance.setMuted(isMuted.value);
    }

    if (retellInstance) {
        // Retell client SDK supports muting standard audio tracks
        retellInstance.isMuted = isMuted.value;
    }
};

const handleClose = () => {
    if (callStatus.value === 'connecting' || callStatus.value === 'connected') {
        endCall();
    }

    emit('close');
};

watch(
    () => props.isOpen,
    (newVal) => {
        if (newVal) {
            startCall();
        } else {
            endCall();
        }
    },
);

onBeforeUnmount(() => {
    endCall();
});
</script>

<template>
    <Dialog :open="props.isOpen" @update:open="(val) => !val && handleClose()">
        <DialogContent
            class="overflow-hidden rounded-3xl border-slate-800 bg-slate-900 p-6 text-slate-100 shadow-2xl sm:max-w-[420px]"
        >
            <!-- Background gradients -->
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div
                    class="absolute -top-32 -left-32 h-64 w-64 rounded-full bg-indigo-500/10 blur-3xl transition-all duration-1000"
                    :class="{
                        'scale-125 bg-emerald-500/15':
                            callStatus === 'connected',
                    }"
                ></div>
                <div
                    class="absolute -right-32 -bottom-32 h-64 w-64 rounded-full bg-rose-500/5 blur-3xl"
                    :class="{ 'bg-rose-500/15': callStatus === 'error' }"
                ></div>
            </div>

            <DialogHeader class="relative z-10">
                <DialogTitle
                    class="bg-gradient-to-r from-indigo-200 to-slate-200 bg-clip-text text-xl font-bold tracking-tight text-transparent"
                >
                    WebRTC Voice Calling
                </DialogTitle>
                <DialogDescription class="text-xs text-slate-400">
                    Connecting live administrator voice channel to customer
                    endpoint.
                </DialogDescription>
            </DialogHeader>

            <div
                class="relative z-10 flex flex-col items-center justify-center gap-6 py-8"
            >
                <!-- Caller Phone Display -->
                <div class="text-center">
                    <span
                        class="text-xs font-semibold tracking-widest text-slate-500 uppercase"
                        >Active Destination</span
                    >
                    <h3
                        class="mt-1 text-2xl font-black tracking-tight text-indigo-300"
                    >
                        {{ props.phone }}
                    </h3>
                </div>

                <!-- Call Status Circle Visualizer -->
                <div
                    class="relative flex h-32 w-32 items-center justify-center"
                >
                    <!-- Outer pulsating ring -->
                    <div
                        v-if="
                            callStatus === 'connecting' ||
                            callStatus === 'connected'
                        "
                        class="absolute inset-0 animate-ping rounded-full border border-indigo-500/30 opacity-75"
                        :class="{
                            'border-emerald-500/30': callStatus === 'connected',
                            'animate-pulse': callStatus === 'connected',
                        }"
                    ></div>

                    <!-- Middle glow ring -->
                    <div
                        class="absolute flex h-28 w-28 items-center justify-center rounded-full border border-slate-700/50 bg-slate-800/80 shadow-inner transition-colors duration-500"
                        :class="{
                            'border-emerald-500/30 bg-emerald-950/20':
                                callStatus === 'connected',
                            'border-rose-500/30 bg-rose-950/20':
                                callStatus === 'error',
                        }"
                    >
                        <Phone
                            v-if="
                                callStatus === 'idle' ||
                                callStatus === 'connecting'
                            "
                            class="h-10 w-10 animate-pulse text-indigo-400"
                        />
                        <Volume2
                            v-else-if="callStatus === 'connected'"
                            class="h-10 w-10 text-emerald-400"
                        />
                        <PhoneOff
                            v-else-if="callStatus === 'ended'"
                            class="h-10 w-10 text-slate-500"
                        />
                        <PhoneOff v-else class="h-10 w-10 text-rose-400" />
                    </div>
                </div>

                <!-- Call Waveform Simulator (when connected) -->
                <div class="flex h-6 items-center gap-1">
                    <template v-if="callStatus === 'connected'">
                        <div
                            v-for="i in 8"
                            :key="i"
                            class="w-1 animate-bounce rounded-full bg-emerald-500/80"
                            :style="{
                                height: `${10 + Math.random() * 14}px`,
                                animationDuration: `${0.6 + Math.random() * 0.8}s`,
                                animationDelay: `${i * 0.1}s`,
                            }"
                        ></div>
                    </template>
                    <span
                        v-else-if="callStatus === 'connecting'"
                        class="animate-pulse text-xs font-medium text-indigo-400/80"
                    >
                        Securing WebRTC channel...
                    </span>
                    <span
                        v-else-if="callStatus === 'error'"
                        class="max-w-[280px] text-center text-xs font-bold text-rose-400"
                    >
                        {{ errorMessage || 'Connection failed' }}
                    </span>
                    <span v-else class="text-xs font-medium text-slate-500">
                        Call {{ callStatus }}
                    </span>
                </div>
            </div>

            <!-- Controls Panel -->
            <div
                class="relative z-10 mt-2 flex items-center justify-center gap-4"
            >
                <!-- Mute Toggle -->
                <Button
                    size="icon"
                    variant="outline"
                    class="h-12 w-12 rounded-full border-slate-800 bg-slate-900 text-slate-300 hover:bg-slate-800"
                    :class="{
                        'border-rose-500/20 bg-rose-500/10 text-rose-400 hover:bg-rose-500/20':
                            isMuted,
                    }"
                    :disabled="callStatus !== 'connected'"
                    @click="toggleMute"
                >
                    <MicOff v-if="isMuted" class="h-5 w-5" />
                    <Mic v-else class="h-5 w-5" />
                </Button>

                <!-- Action Button (Call or Hang Up) -->
                <Button
                    v-if="
                        callStatus === 'connecting' ||
                        callStatus === 'connected'
                    "
                    variant="destructive"
                    class="flex h-12 items-center gap-2 rounded-full border-0 bg-rose-600 px-6 font-bold tracking-tight text-white shadow-lg shadow-rose-950/40 hover:bg-rose-500"
                    @click="endCall"
                >
                    <PhoneOff class="h-4 w-4" />
                    End Call
                </Button>

                <Button
                    v-else
                    class="flex h-12 items-center gap-2 rounded-full border-0 bg-indigo-600 px-6 font-bold tracking-tight text-white shadow-lg shadow-indigo-950/40 hover:bg-indigo-500"
                    @click="startCall"
                >
                    <Phone class="h-4 w-4" />
                    Redial
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
