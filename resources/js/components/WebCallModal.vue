<script setup lang="ts">
import { Phone, PhoneOff, Mic, MicOff, Volume2 } from '@lucide/vue';
import { ref, watch, onBeforeUnmount } from 'vue';
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

let vapiInstance: any = null;
let retellInstance: any = null;

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
        const { provider, access_token, assistant_id } = data;

        if (provider === 'retell') {
            const { RetellWebClient } = await import('retell-client-js-sdk');
            retellInstance = new RetellWebClient();
            callStore.retellClient = retellInstance;

            retellInstance.on('call_started', () => {
                callStatus.value = 'connected';
                emit('call_started');
            });

            retellInstance.on('call_ended', () => {
                callStatus.value = 'ended';
                emit('call_ended');
                cleanupCall();
            });

            retellInstance.on('error', (err: any) => {
                console.error('Retell error:', err);
                errorMessage.value =
                    err?.message || 'A network error occurred during the call.';
                callStatus.value = 'error';
                emit('call_ended');
                cleanupCall();
            });

            await retellInstance.startCall({
                accessToken: access_token,
            });
        } else {
            // Default to Vapi
            const Vapi = (await import('@vapi-ai/web')).default;
            vapiInstance = new Vapi(access_token);
            callStore.vapiClient = vapiInstance;

            vapiInstance.on('call-start', () => {
                callStatus.value = 'connected';
                emit('call_started');
            });

            vapiInstance.on('call-end', () => {
                callStatus.value = 'ended';
                emit('call_ended');
                cleanupCall();
            });

            vapiInstance.on('error', (err: any) => {
                console.error('Vapi error:', err);
                errorMessage.value =
                    err?.message || 'Failed to establish WebRTC connection.';
                callStatus.value = 'error';
                emit('call_ended');
                cleanupCall();
            });

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
    vapiInstance = null;
    retellInstance = null;
    callStore.vapiClient = null;
    callStore.retellClient = null;
    callStore.isSpeaking = false;
    callStore.transcript = '';
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
