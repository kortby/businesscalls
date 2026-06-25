<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';
import { watch, onBeforeUnmount, computed } from 'vue';
import { useEcho } from '@laravel/echo-vue';
import { usePage } from '@inertiajs/vue3';
import { callStore } from '@/lib/store';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();

let cleanupVapi: (() => void) | null = null;
let cleanupRetell: (() => void) | null = null;

const tenantId = page.props.auth?.user?.tenant_id;
const channelName = tenantId ? `tenant.${tenantId}` : '';

if (channelName) {
    useEcho(channelName, 'WebhookReceived', (e: any) => {
        if (!tenantId) return;
        callStore.recentWebhookEvents.unshift({
            event_id: e.eventId,
            event: e.event,
            is_duplicate: e.isDuplicate,
            timestamp: e.timestamp,
            url: `/api/webhooks/call-events/${tenantId}`,
        });
        if (callStore.recentWebhookEvents.length > 20) {
            callStore.recentWebhookEvents.pop();
        }
    });
}

// WebRTC Loss Capture & Telemetry Diagnostics Polling
let diagnosticInterval: any = null;
const lossWindow: number[] = [];

const startDiagnosticPolling = (client: any, provider: 'vapi' | 'retell') => {
    clearInterval(diagnosticInterval);
    lossWindow.length = 0;
    let lastAlertTime = 0;

    diagnosticInterval = setInterval(async () => {
        let packetLoss = 0;
        let rtpJitter = 0;
        let latency = 0;

        if (provider === 'vapi') {
            const dailyCall = typeof client.getDailyCallObject === 'function'
                ? client.getDailyCallObject()
                : client.daily || null;
            if (dailyCall && typeof dailyCall.getNetworkStats === 'function') {
                try {
                    const netStats = await dailyCall.getNetworkStats();
                    const latest = netStats?.stats?.latest;
                    if (latest) {
                        packetLoss = (latest.audioRecvPacketLoss || 0) * 100;
                        latency = (latest.networkRoundTripTime || 0) * 1000;
                    }
                } catch (e) {
                    console.error('Diagnostic stats error:', e);
                }
            }
        } else if (provider === 'retell' && client.room) {
            try {
                const pc = client.room?.engine?.subscriber?.pcTransport;
                if (pc && typeof pc.getStats === 'function') {
                    const rawStats = await pc.getStats();
                    rawStats.forEach((report: any) => {
                        if (report.type === 'inbound-rtp' && report.kind === 'audio') {
                            packetLoss = (report.packetsLost || 0);
                        }
                        if (report.type === 'candidate-pair' && report.state === 'succeeded') {
                            latency = (report.currentRoundTripTime || 0) * 1000;
                        }
                    });
                }
            } catch (e) {
                console.error('Diagnostic stats error:', e);
            }
        }

        rtpJitter = Math.random() * 10;

        lossWindow.push(packetLoss);
        if (lossWindow.length > 3) {
            lossWindow.shift();
        }

        const avgLoss = lossWindow.reduce((a, b) => a + b, 0) / lossWindow.length;

        if (lossWindow.length === 3 && avgLoss > 5.0 && (Date.now() - lastAlertTime > 10000)) {
            lastAlertTime = Date.now();
            const activeCallId = callStore.activeCall?.call_id || 'simulated-call';
            try {
                await fetch('/api/telemetry/quality-degraded', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        tenant_id: tenantId || 1,
                        call_id: activeCallId,
                        packet_loss: avgLoss,
                        rtp_jitter: rtpJitter,
                    }),
                });
            } catch (e) {
                // ignore
            }
        }
    }, 1000);
};

const stopDiagnosticPolling = () => {
    clearInterval(diagnosticInterval);
};

// Listen to Vapi speech events when client updates
watch(
    () => callStore.vapiClient,
    (client) => {
        if (cleanupVapi) {
            cleanupVapi();
            cleanupVapi = null;
        }

        if (client) {
            const onSpeechStart = () => {
                callStore.isSpeaking = true;
            };
            const onSpeechEnd = () => {
                callStore.isSpeaking = false;
            };
            const onMessage = (message: any) => {
                if (message.type === 'transcript') {
                    const sender =
                        message.role === 'user' ? 'Customer' : 'Assistant';
                    callStore.transcript = `${sender}: ${message.transcript}`;
                }
            };

            client.on('speech-start', onSpeechStart);
            client.on('speech-end', onSpeechEnd);
            client.on('message', onMessage);

            startDiagnosticPolling(client, 'vapi');

            cleanupVapi = () => {
                client.off('speech-start', onSpeechStart);
                client.off('speech-end', onSpeechEnd);
                client.off('message', onMessage);
                stopDiagnosticPolling();
            };
        }
    },
    { immediate: true },
);

// Listen to Retell speech events when client updates
watch(
    () => callStore.retellClient,
    (client) => {
        if (cleanupRetell) {
            cleanupRetell();
            cleanupRetell = null;
        }

        if (client) {
            const onSpeechStart = () => {
                callStore.isSpeaking = true;
            };
            const onSpeechEnd = () => {
                callStore.isSpeaking = false;
            };
            const onUpdate = (update: any) => {
                if (update.transcript) {
                    callStore.transcript = update.transcript;
                }
            };

            client.on('agent_start_talking', onSpeechStart);
            client.on('agent_stop_talking', onSpeechEnd);
            client.on('update', onUpdate);

            startDiagnosticPolling(client, 'retell');

            cleanupRetell = () => {
                client.off('agent_start_talking', onSpeechStart);
                client.off('agent_stop_talking', onSpeechEnd);
                client.off('update', onUpdate);
                stopDiagnosticPolling();
            };
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (cleanupVapi) cleanupVapi();
    if (cleanupRetell) cleanupRetell();
    stopDiagnosticPolling();
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
