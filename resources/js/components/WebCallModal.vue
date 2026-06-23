<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue';
import { 
    Dialog, 
    DialogContent, 
    DialogHeader, 
    DialogTitle, 
    DialogDescription 
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Phone, PhoneOff, Mic, MicOff, Volume2 } from '@lucide/vue';

const props = defineProps<{
    isOpen: boolean;
    phone: string;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'call_started'): void;
    (e: 'call_ended'): void;
}>();

const callStatus = ref<'idle' | 'connecting' | 'connected' | 'ended' | 'error'>('idle');
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
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Accept': 'application/json',
            }
        });

        if (!response.ok) {
            const errData = await response.json().catch(() => ({}));
            throw new Error(errData.error || 'Failed to authorize call session.');
        }

        const data = await response.json();
        const { provider, access_token, assistant_id } = data;

        if (provider === 'retell') {
            const { RetellWebClient } = await import('retell-client-js-sdk');
            retellInstance = new RetellWebClient();
            
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
                errorMessage.value = err?.message || 'A network error occurred during the call.';
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
                errorMessage.value = err?.message || 'Failed to establish WebRTC connection.';
                callStatus.value = 'error';
                emit('call_ended');
                cleanupCall();
            });

            vapiInstance.start(assistant_id);
        }
    } catch (err: any) {
        console.error('Error starting web call:', err);
        errorMessage.value = err.message || 'Unable to connect to telephony provider.';
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

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        startCall();
    } else {
        endCall();
    }
});

onBeforeUnmount(() => {
    endCall();
});
</script>

<template>
    <Dialog :open="props.isOpen" @update:open="(val) => !val && handleClose()">
        <DialogContent class="sm:max-w-[420px] bg-slate-900 border-slate-800 text-slate-100 overflow-hidden rounded-3xl p-6 shadow-2xl">
            <!-- Background gradients -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden">
                <div class="absolute -top-32 -left-32 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl transition-all duration-1000"
                     :class="{ 'bg-emerald-500/15 scale-125': callStatus === 'connected' }"></div>
                <div class="absolute -bottom-32 -right-32 w-64 h-64 bg-rose-500/5 rounded-full blur-3xl"
                     :class="{ 'bg-rose-500/15': callStatus === 'error' }"></div>
            </div>

            <DialogHeader class="relative z-10">
                <DialogTitle class="text-xl font-bold tracking-tight bg-gradient-to-r from-indigo-200 to-slate-200 bg-clip-text text-transparent">
                    WebRTC Voice Calling
                </DialogTitle>
                <DialogDescription class="text-xs text-slate-400">
                    Connecting live administrator voice channel to customer endpoint.
                </DialogDescription>
            </DialogHeader>

            <div class="relative z-10 flex flex-col items-center justify-center py-8 gap-6">
                <!-- Caller Phone Display -->
                <div class="text-center">
                    <span class="text-xs text-slate-500 uppercase tracking-widest font-semibold">Active Destination</span>
                    <h3 class="text-2xl font-black tracking-tight text-indigo-300 mt-1">
                        {{ props.phone }}
                    </h3>
                </div>

                <!-- Call Status Circle Visualizer -->
                <div class="relative flex items-center justify-center w-32 h-32">
                    <!-- Outer pulsating ring -->
                    <div 
                        v-if="callStatus === 'connecting' || callStatus === 'connected'" 
                        class="absolute inset-0 rounded-full border border-indigo-500/30 animate-ping opacity-75"
                        :class="{ 'border-emerald-500/30': callStatus === 'connected', 'animate-pulse': callStatus === 'connected' }"
                    ></div>
                    
                    <!-- Middle glow ring -->
                    <div 
                        class="absolute w-28 h-28 rounded-full bg-slate-800/80 border border-slate-700/50 flex items-center justify-center shadow-inner transition-colors duration-500"
                        :class="{ 
                            'border-emerald-500/30 bg-emerald-950/20': callStatus === 'connected',
                            'border-rose-500/30 bg-rose-950/20': callStatus === 'error',
                        }"
                    >
                        <Phone 
                            v-if="callStatus === 'idle' || callStatus === 'connecting'" 
                            class="h-10 w-10 text-indigo-400 animate-pulse" 
                        />
                        <Volume2 
                            v-else-if="callStatus === 'connected'" 
                            class="h-10 w-10 text-emerald-400" 
                        />
                        <PhoneOff 
                            v-else-if="callStatus === 'ended'" 
                            class="h-10 w-10 text-slate-500" 
                        />
                        <PhoneOff 
                            v-else 
                            class="h-10 w-10 text-rose-400" 
                        />
                    </div>
                </div>

                <!-- Call Waveform Simulator (when connected) -->
                <div class="h-6 flex items-center gap-1">
                    <template v-if="callStatus === 'connected'">
                        <div v-for="i in 8" :key="i" 
                             class="w-1 bg-emerald-500/80 rounded-full animate-bounce"
                             :style="{ 
                                 height: `${10 + Math.random() * 14}px`, 
                                 animationDuration: `${0.6 + Math.random() * 0.8}s`,
                                 animationDelay: `${i * 0.1}s` 
                             }"
                        ></div>
                    </template>
                    <span v-else-if="callStatus === 'connecting'" class="text-xs text-indigo-400/80 animate-pulse font-medium">
                        Securing WebRTC channel...
                    </span>
                    <span v-else-if="callStatus === 'error'" class="text-xs text-rose-400 font-bold text-center max-w-[280px]">
                        {{ errorMessage || 'Connection failed' }}
                    </span>
                    <span v-else class="text-xs text-slate-500 font-medium">
                        Call {{ callStatus }}
                    </span>
                </div>
            </div>

            <!-- Controls Panel -->
            <div class="relative z-10 flex items-center justify-center gap-4 mt-2">
                <!-- Mute Toggle -->
                <Button
                    size="icon"
                    variant="outline"
                    class="h-12 w-12 rounded-full border-slate-800 bg-slate-900 hover:bg-slate-800 text-slate-300"
                    :class="{ 'bg-rose-500/10 border-rose-500/20 text-rose-400 hover:bg-rose-500/20': isMuted }"
                    :disabled="callStatus !== 'connected'"
                    @click="toggleMute"
                >
                    <MicOff v-if="isMuted" class="h-5 w-5" />
                    <Mic v-else class="h-5 w-5" />
                </Button>

                <!-- Action Button (Call or Hang Up) -->
                <Button
                    v-if="callStatus === 'connecting' || callStatus === 'connected'"
                    variant="destructive"
                    class="h-12 px-6 rounded-full bg-rose-600 hover:bg-rose-500 font-bold tracking-tight text-white shadow-lg shadow-rose-950/40 border-0 flex items-center gap-2"
                    @click="endCall"
                >
                    <PhoneOff class="h-4 w-4" />
                    End Call
                </Button>
                
                <Button
                    v-else
                    class="h-12 px-6 rounded-full bg-indigo-600 hover:bg-indigo-500 font-bold tracking-tight text-white shadow-lg shadow-indigo-950/40 border-0 flex items-center gap-2"
                    @click="startCall"
                >
                    <Phone class="h-4 w-4" />
                    Redial
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
