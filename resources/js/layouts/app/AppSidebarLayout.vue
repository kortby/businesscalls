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
    useEcho(
        channelName,
        'WebhookReceived',
        (e: any) => {
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
        }
    );
}

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
                    const sender = message.role === 'user' ? 'Customer' : 'Assistant';
                    callStore.transcript = `${sender}: ${message.transcript}`;
                }
            };

            client.on('speech-start', onSpeechStart);
            client.on('speech-end', onSpeechEnd);
            client.on('message', onMessage);

            cleanupVapi = () => {
                client.off('speech-start', onSpeechStart);
                client.off('speech-end', onSpeechEnd);
                client.off('message', onMessage);
            };
        }
    },
    { immediate: true }
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

            cleanupRetell = () => {
                client.off('agent_start_talking', onSpeechStart);
                client.off('agent_stop_talking', onSpeechEnd);
                client.off('update', onUpdate);
            };
        }
    },
    { immediate: true }
);

onBeforeUnmount(() => {
    if (cleanupVapi) cleanupVapi();
    if (cleanupRetell) cleanupRetell();
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
