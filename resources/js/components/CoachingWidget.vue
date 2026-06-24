<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useEcho } from '@laravel/echo-vue';
import { AlertCircle, X, MessageSquare } from '@lucide/vue';

const props = defineProps<{
    tenantId: number | string;
    bookings: any[];
    activeCalls: Array<{ call_id: string; customer_phone: string }>;
}>();

interface Whisper {
    id: string;
    instruction: string;
    supervisorName: string;
    timestamp: string;
}

const whispers = ref<Whisper[]>([]);
const activeCallChannels = ref<Array<{ callId: string; channel: any }>>([]);

const removeWhisper = (id: string) => {
    whispers.value = whispers.value.filter((w) => w.id !== id);
};

// Subscribe to a specific call's coaching channel
const subscribeToCoaching = (callId: string) => {
    if (activeCallChannels.value.some((c) => c.callId === callId)) return;

    console.log(
        `[CoachingWidget] Subscribing to call whisper channel: tenant.${props.tenantId}.coaching.${callId}`,
    );

    try {
        const channel = window.Echo.private(
            `tenant.${props.tenantId}.coaching.${callId}`,
        ).listen('.SupervisorWhisperSent', (data: any) => {
            console.log('[CoachingWidget] Received whisper data:', data);

            const newWhisper: Whisper = {
                id: Math.random().toString(36).substring(2, 9),
                instruction: data.instruction,
                supervisorName: data.supervisorName || 'Supervisor',
                timestamp: new Date().toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                }),
            };

            // Add to list and play anim
            whispers.value.unshift(newWhisper);

            // Auto dismiss after 10 seconds
            setTimeout(() => {
                removeWhisper(newWhisper.id);
            }, 10000);
        });

        activeCallChannels.value.push({ callId, channel });
    } catch (e) {
        console.error(
            '[CoachingWidget] Failed to subscribe to private coaching channel:',
            e,
        );
    }
};

// Leave a specific call's coaching channel
const unsubscribeFromCoaching = (callId: string) => {
    const index = activeCallChannels.value.findIndex(
        (c) => c.callId === callId,
    );
    if (index !== -1) {
        console.log(
            `[CoachingWidget] Leaving whisper channel for call: ${callId}`,
        );
        try {
            window.Echo.leave(`tenant.${props.tenantId}.coaching.${callId}`);
        } catch (e) {}
        activeCallChannels.value.splice(index, 1);
    }
};

onMounted(() => {
    // 1. Subscribe to existing active calls on load
    props.activeCalls?.forEach((ac) => {
        subscribeToCoaching(ac.call_id);
    });

    // 2. Setup Reverb listeners on general tenant channel for new call start/end events
    if (props.tenantId) {
        useEcho(`tenant.${props.tenantId}`, 'CallStarted', (payload: any) => {
            console.log(
                '[CoachingWidget] CallStarted webhook received:',
                payload,
            );

            const customerPhone =
                payload.customer_phone || payload.customerPhone;
            const callId = payload.call_id || payload.id;

            if (!customerPhone || !callId) return;

            // Check if this call is for one of the technician's bookings
            const hasBooking = props.bookings.some((b) => {
                const bPhoneClean = b.customer_phone.replace(/\D/g, '');
                const payloadPhoneClean = customerPhone.replace(/\D/g, '');
                return (
                    bPhoneClean.includes(payloadPhoneClean) ||
                    payloadPhoneClean.includes(bPhoneClean)
                );
            });

            if (hasBooking) {
                subscribeToCoaching(callId);
            }
        });

        useEcho(`tenant.${props.tenantId}`, 'CallEnded', (payload: any) => {
            console.log(
                '[CoachingWidget] CallEnded webhook received:',
                payload,
            );
            const callId = payload.call_id || payload.id;
            if (callId) {
                unsubscribeFromCoaching(callId);
            }
        });
    }
});

onUnmounted(() => {
    activeCallChannels.value.forEach((c) => {
        try {
            window.Echo.leave(`tenant.${props.tenantId}.coaching.${c.callId}`);
        } catch (e) {}
    });
});
</script>

<template>
    <!-- Fixed top overlay for supervisor whispers -->
    <div
        class="pointer-events-none fixed inset-x-4 top-4 z-[9999] flex flex-col items-center gap-3"
    >
        <TransitionGroup name="slide-down">
            <div
                v-for="whisper in whispers"
                :key="whisper.id"
                class="pointer-events-auto flex w-full max-w-md items-start gap-4 rounded-2xl border-4 border-slate-900 bg-amber-400 p-4 shadow-[4px_4px_0px_0px_rgba(15,23,42,1)] dark:border-slate-100 dark:shadow-[4px_4px_0px_0px_rgba(255,255,255,1)]"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border-2 border-slate-900 bg-white dark:border-slate-100"
                >
                    <MessageSquare class="h-5 w-5 stroke-[3] text-amber-500" />
                </div>

                <div class="flex-1 text-slate-950">
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-black tracking-wider text-slate-700 uppercase"
                        >
                            Whisper from {{ whisper.supervisorName }}
                        </span>
                        <span class="text-[10px] font-black text-slate-500">{{
                            whisper.timestamp
                        }}</span>
                    </div>
                    <p class="mt-1 text-sm leading-relaxed font-extrabold">
                        {{ whisper.instruction }}
                    </p>
                </div>

                <button
                    @click="removeWhisper(whisper.id)"
                    class="shrink-0 rounded-lg border-2 border-slate-900 bg-white p-1 text-slate-700 hover:bg-slate-100 active:translate-y-0.5 dark:border-slate-100"
                >
                    <X class="h-4 w-4 stroke-[3]" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
/* Slide-down and bounce transitions */
.slide-down-enter-active {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.slide-down-leave-active {
    transition: all 0.3s cubic-bezier(0.6, -0.28, 0.735, 0.045);
}
.slide-down-enter-from {
    transform: translateY(-50px) scale(0.9);
    opacity: 0;
}
.slide-down-leave-to {
    transform: translateY(-20px) scale(0.95);
    opacity: 0;
}
</style>
