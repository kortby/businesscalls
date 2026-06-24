<script setup lang="ts">
import { ref, onBeforeUnmount } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import {
    Shield,
    Radio,
    VolumeX,
    Mic,
    PhoneOff,
    CheckCircle,
} from '@lucide/vue';

const props = defineProps<{
    activeCall: {
        call_id: string;
        customer_phone: string;
    };
    isTestMode: boolean;
}>();

const emit = defineEmits<{
    (e: 'barge_initiated', payload: { mode: 'monitor' | 'barge' }): void;
    (e: 'barge_ended'): void;
}>();

const modeState = ref<'idle' | 'monitoring' | 'barging'>('idle');
const connectionStatus = ref<
    'disconnected' | 'connecting' | 'connected' | 'error'
>('disconnected');
const errorMsg = ref('');

let vapiInstance: any = null;
let retellInstance: any = null;

const initiateOverride = async (mode: 'monitor' | 'barge') => {
    connectionStatus.value = 'connecting';
    modeState.value = mode === 'barge' ? 'barging' : 'monitoring';
    errorMsg.value = '';

    try {
        const response = await fetch('/api/web-calls/barge', {
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
                call_id: props.activeCall.call_id,
                mode: mode,
            }),
        });

        if (!response.ok) {
            const errData = await response.json().catch(() => ({}));
            throw new Error(errData.error || 'Barge authorization failed.');
        }

        const data = await response.json();
        const { provider, access_token, mode: activeMode } = data;

        if (access_token.includes('sandbox-') || props.isTestMode) {
            // Simulate WebRTC connection in sandbox/test mode
            setTimeout(() => {
                connectionStatus.value = 'connected';
                emit('barge_initiated', { mode: activeMode });
            }, 1000);
            return;
        }

        // Live Mode: Connect WebRTC
        if (provider === 'retell') {
            const { RetellWebClient } = await import('retell-client-js-sdk');
            retellInstance = new RetellWebClient();

            retellInstance.on('call_started', () => {
                connectionStatus.value = 'connected';
                emit('barge_initiated', { mode: activeMode });
            });

            retellInstance.on('call_ended', () => {
                cleanupOverride();
            });

            retellInstance.on('error', (err: any) => {
                errorMsg.value =
                    err?.message || 'Retell WebRTC connection error.';
                connectionStatus.value = 'error';
            });

            await retellInstance.startCall({
                accessToken: access_token,
            });
        } else {
            // Vapi
            const Vapi = (await import('@vapi-ai/web')).default;
            vapiInstance = new Vapi(access_token);

            vapiInstance.on('call-start', () => {
                connectionStatus.value = 'connected';
                emit('barge_initiated', { mode: activeMode });
            });

            vapiInstance.on('call-end', () => {
                cleanupOverride();
            });

            vapiInstance.on('error', (err: any) => {
                errorMsg.value =
                    err?.message || 'Vapi WebRTC connection error.';
                connectionStatus.value = 'error';
            });

            vapiInstance.start(data.assistant_id);
        }
    } catch (err: any) {
        console.error('Error initiating barge:', err);
        errorMsg.value = err.message || 'Unable to connect to WebRTC stream.';
        connectionStatus.value = 'error';
    }
};

const stopOverride = () => {
    if (vapiInstance) {
        try {
            vapiInstance.stop();
        } catch (e) {}
    }
    if (retellInstance) {
        try {
            retellInstance.stopCall();
        } catch (e) {}
    }
    cleanupOverride();
};

const cleanupOverride = () => {
    vapiInstance = null;
    retellInstance = null;
    modeState.value = 'idle';
    connectionStatus.value = 'disconnected';
    emit('barge_ended');
};

onBeforeUnmount(() => {
    stopOverride();
});
</script>

<template>
    <Card
        class="overflow-hidden rounded-2xl border-2 border-dashed border-red-500/40 bg-slate-950 text-slate-100 shadow-xl transition-all duration-300"
    >
        <CardHeader
            class="flex flex-row items-center gap-3 border-b border-slate-900 bg-red-950/10 pb-3"
        >
            <div class="rounded-lg border border-red-500/20 bg-red-500/10 p-2">
                <Shield class="h-5 w-5 animate-pulse text-red-500" />
            </div>
            <div>
                <CardTitle
                    class="text-sm font-black tracking-wider text-red-400 uppercase"
                >
                    Supervisor Call Override
                </CardTitle>
                <p class="mt-0.5 text-xs text-slate-400">
                    Live Session ID:
                    <span class="font-mono text-red-300/80">{{
                        props.activeCall.call_id
                    }}</span>
                </p>
            </div>
        </CardHeader>

        <CardContent class="flex flex-col gap-4 pt-5">
            <!-- Mode State Details -->
            <div
                class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900 p-3"
            >
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75"
                            :class="{
                                'bg-yellow-400': modeState === 'monitoring',
                                'bg-red-500': modeState === 'barging',
                                'bg-slate-500': modeState === 'idle',
                            }"
                        ></span>
                        <span
                            class="relative inline-flex h-2.5 w-2.5 rounded-full"
                            :class="{
                                'bg-yellow-400': modeState === 'monitoring',
                                'bg-red-500': modeState === 'barging',
                                'bg-slate-500': modeState === 'idle',
                            }"
                        ></span>
                    </span>
                    <span
                        class="text-xs font-semibold tracking-wider text-slate-300 uppercase"
                    >
                        Status:
                        <span v-if="modeState === 'idle'">Monitoring Idle</span>
                        <span v-else-if="modeState === 'monitoring'"
                            >Silent Monitoring</span
                        >
                        <span v-else>Active Live Barge</span>
                    </span>
                </div>
                <div class="text-xs text-slate-500">
                    <span
                        v-if="connectionStatus === 'connecting'"
                        class="animate-pulse"
                        >Connecting...</span
                    >
                    <span
                        v-else-if="connectionStatus === 'connected'"
                        class="font-bold text-emerald-400"
                        >WebRTC Connected</span
                    >
                    <span
                        v-else-if="connectionStatus === 'error'"
                        class="font-bold text-red-400"
                        >Error</span
                    >
                    <span v-else>Offline</span>
                </div>
            </div>

            <!-- Waveform or Connection Simulator -->
            <div
                v-if="connectionStatus === 'connected'"
                class="flex h-12 items-center justify-center gap-1.5 rounded-xl border border-red-500/10 bg-red-950/15 p-2"
            >
                <div
                    v-for="i in 12"
                    :key="i"
                    class="w-1 rounded-full transition-all duration-300"
                    :class="
                        modeState === 'barging'
                            ? 'animate-bounce bg-red-500/80'
                            : 'animate-pulse bg-yellow-500/80'
                    "
                    :style="{
                        height: `${8 + Math.random() * 28}px`,
                        animationDuration: `${0.4 + Math.random() * 0.8}s`,
                        animationDelay: `${i * 0.05}s`,
                    }"
                ></div>
            </div>

            <div
                v-if="errorMsg"
                class="rounded-lg border border-red-500/20 bg-red-950/20 p-3 text-xs font-bold text-red-400"
            >
                {{ errorMsg }}
            </div>

            <!-- Controls Buttons -->
            <div class="flex items-center gap-3">
                <template v-if="modeState === 'idle'">
                    <Button
                        variant="outline"
                        class="flex h-11 flex-1 items-center justify-center gap-2 rounded-xl border-slate-800 bg-slate-900 text-xs font-bold tracking-wider text-slate-300 uppercase hover:bg-slate-800"
                        @click="initiateOverride('monitor')"
                    >
                        <Radio class="h-4 w-4 text-yellow-400" />
                        Silent Listen
                    </Button>
                    <Button
                        class="flex h-11 flex-1 items-center justify-center gap-2 rounded-xl border-0 bg-red-600 text-xs font-bold tracking-wider text-white uppercase shadow-lg shadow-red-950/30 hover:bg-red-500"
                        @click="initiateOverride('barge')"
                    >
                        <Mic class="h-4 w-4 text-white" />
                        Barge & Speak
                    </Button>
                </template>
                <template v-else>
                    <div class="flex w-full flex-col gap-2 text-center">
                        <p class="text-xs text-slate-400 italic">
                            <span v-if="modeState === 'monitoring'"
                                >You are listening silently. AI Agent remains
                                active.</span
                            >
                            <span v-else class="font-bold text-red-400"
                                >You are speaking live to the customer. AI Agent
                                is muted.</span
                            >
                        </p>
                        <Button
                            variant="destructive"
                            class="flex h-11 w-full items-center justify-center gap-2 rounded-xl border-0 bg-rose-600 text-xs font-bold tracking-wider text-white uppercase hover:bg-rose-500"
                            @click="stopOverride"
                        >
                            <PhoneOff class="h-4 w-4" />
                            Disconnect Override
                        </Button>
                    </div>
                </template>
            </div>
        </CardContent>
    </Card>
</template>
