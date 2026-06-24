<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useEcho } from '@laravel/echo-vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { MessageSquare, Send, Check, Archive, Clock, Phone, User as UserIcon } from '@lucide/vue';

// Define layout explicitly
defineOptions({ layout: AppLayout });

const props = defineProps<{
    tenant: {
        id: number;
        slug: string;
        name: string;
    } | null;
    conversations: Array<{
        id: number;
        customer_phone: string;
        status: string;
        subject: string | null;
        updated_at: string;
        user?: {
            name: string;
        } | null;
        messages: Array<{
            id: number;
            sender: string;
            body: string;
            created_at: string;
        }>;
    }>;
}>();

const liveConversations = ref([...props.conversations]);
const activeConversationId = ref<number | null>(
    props.conversations.length > 0 ? props.conversations[0].id : null
);

const activeConversation = computed(() => 
    liveConversations.value.find(c => c.id === activeConversationId.value) || null
);

// Mascot State: 0=Idle, 1=Searching, 2=Victory, 3=Error
const mascotState = ref<number>(0);

const transitionMascot = (newState: number) => {
    mascotState.value = newState;
    if (newState === 2 || newState === 3) {
        setTimeout(() => {
            if (mascotState.value === newState) {
                mascotState.value = 0;
            }
        }, 6000);
    }
};

// Form for sending messages
const form = useForm({
    body: '',
});

const selectConversation = (id: number) => {
    activeConversationId.value = id;
};

const submitMessage = () => {
    if (!activeConversationId.value || !form.body.trim()) return;

    transitionMascot(1); // Searching/sending

    form.post(`/conversations/${activeConversationId.value}/messages`, {
        onSuccess: () => {
            form.reset('body');
            scrollToBottom();
            transitionMascot(2); // Victory
        },
        onError: () => {
            transitionMascot(3); // Error
        }
    });
};

// Auto-scroll logic for the chat window
const messageContainer = ref<HTMLDivElement | null>(null);
const scrollToBottom = () => {
    setTimeout(() => {
        if (messageContainer.value) {
            messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
        }
    }, 50);
};

// Sync liveConversations when props update from the server
watch(() => props.conversations, (newVal) => {
    liveConversations.value = [...newVal];
    if (activeConversationId.value && !newVal.some(c => c.id === activeConversationId.value) && newVal.length > 0) {
        activeConversationId.value = newVal[0].id;
    }
}, { deep: true });

watch(activeConversationId, () => {
    scrollToBottom();
});

// Setup WebSocket Listener for Real-Time Omni-Channel messages
onMounted(() => {
    scrollToBottom();

    if (props.tenant) {
        useEcho(`tenant.${props.tenant.id}`, 'message.received', (payload: any) => {
            const msg = payload.message;
            
            // Instantly update Rive mascot visual triggers (Victory)
            transitionMascot(2);

            const conv = liveConversations.value.find(c => c.id === msg.conversation_id);
            if (conv) {
                if (!conv.messages.some(m => m.id === msg.id)) {
                    conv.messages.push(msg);
                }
                
                // Sort conversation to the top
                liveConversations.value = [
                    conv,
                    ...liveConversations.value.filter(c => c.id !== conv.id)
                ];

                if (activeConversationId.value === conv.id) {
                    scrollToBottom();
                }
            }
        });
    }
});

const formatTime = (isoString: string) => {
    return new Date(isoString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const formatDate = (isoString: string) => {
    return new Date(isoString).toLocaleDateString([], { month: 'short', day: 'numeric' });
};
</script>

<template>
    <Head title="Omni-Channel Customer Conversations" />

    <div class="bg-background text-foreground p-6 min-h-screen">
        <!-- Top Page Header -->
        <div class="mb-8 flex items-center justify-between border-b pb-6">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500 text-white shadow-xs border-b-4 border-indigo-700">
                    <MessageSquare class="h-6 w-6" />
                </div>
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-foreground uppercase">Omni-Channel Customer Chat</h1>
                    <p class="text-xs text-muted-foreground font-semibold uppercase tracking-widest mt-1">
                        Live SMS & Web messaging for {{ tenant?.name ?? 'businesscalls' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Layout Grid: Left Sidebar Threads List & Right Chat Panel -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- LEFT THREADS PANEL -->
            <div class="flex flex-col gap-6">
                <!-- Rive Mascot Integration -->
                <div class="h-64 shrink-0">
                    <DispatcherMascot :state="mascotState" />
                </div>

                <Card class="border-4 border-b-8 border-slate-300 dark:border-slate-800 rounded-3xl overflow-hidden">
                    <CardHeader class="border-b pb-4">
                        <CardTitle class="text-lg font-black uppercase tracking-wider">Active Conversations</CardTitle>
                        <CardDescription class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">
                            Sorted dynamically by recency
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-0 max-h-[550px] overflow-y-auto pr-1">
                        <div v-if="liveConversations.length === 0" class="p-8 text-center text-xs text-muted-foreground italic font-semibold">
                            No active conversations found.
                        </div>
                        <div v-else class="divide-y divide-border">
                            <button
                                v-for="conv in liveConversations"
                                :key="conv.id"
                                @click="selectConversation(conv.id)"
                                :class="[
                                    activeConversationId === conv.id 
                                        ? 'bg-indigo-500/10 dark:bg-indigo-500/5 border-l-8 border-l-indigo-500 border-y border-y-indigo-500/20' 
                                        : 'hover:bg-accent/40'
                                ]"
                                class="w-full text-left p-4 transition-all flex items-start gap-3 border-l-4 border-l-transparent"
                            >
                                <div class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-900 border-2 flex items-center justify-center text-slate-600 dark:text-slate-400 font-bold shrink-0">
                                    {{ conv.customer_phone.slice(-4) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-xs font-black text-foreground truncate block">
                                            {{ conv.customer_phone }}
                                        </span>
                                        <span class="text-[9px] font-bold text-muted-foreground font-mono">
                                            {{ conv.messages.length > 0 ? formatTime(conv.messages[conv.messages.length - 1].created_at) : '' }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-muted-foreground font-semibold uppercase tracking-wider truncate mb-1">
                                        {{ conv.subject ?? 'General Inquiry' }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate italic">
                                        {{ conv.messages.length > 0 ? conv.messages[conv.messages.length - 1].body : '(No messages)' }}
                                    </p>
                                    <div class="flex gap-1.5 mt-2">
                                        <Badge 
                                            :variant="conv.status === 'open' ? 'default' : 'secondary'"
                                            class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded"
                                            :class="[
                                                conv.status === 'open' 
                                                    ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20 hover:bg-emerald-500/10' 
                                                    : 'bg-slate-500/10 text-slate-600 border-slate-500/20 hover:bg-slate-500/10'
                                            ]"
                                        >
                                            {{ conv.status }}
                                        </Badge>
                                        <Badge 
                                            v-if="conv.user" 
                                            variant="outline" 
                                            class="text-[8px] font-bold border-indigo-500/20 text-indigo-500 px-1.5 py-0.5"
                                        >
                                            Assignee: {{ conv.user.name }}
                                        </Badge>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- RIGHT CHAT PANEL -->
            <div class="lg:col-span-2 flex flex-col gap-6">
                <Card v-if="activeConversation" class="border-4 border-b-8 border-indigo-400 dark:border-indigo-950 rounded-3xl overflow-hidden flex flex-col h-[600px]">
                    <!-- Active Conversation Header -->
                    <CardHeader class="border-b pb-4 bg-indigo-500/5 flex flex-row items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-indigo-500 text-white flex items-center justify-center font-bold">
                                <Phone class="h-5 w-5" />
                            </div>
                            <div>
                                <CardTitle class="text-sm font-black text-foreground">{{ activeConversation.customer_phone }}</CardTitle>
                                <CardDescription class="text-[10px] font-semibold text-muted-foreground uppercase tracking-widest flex items-center gap-1.5 mt-0.5">
                                    <Clock class="h-3 w-3" /> Last Active: {{ formatDate(activeConversation.updated_at) }} at {{ formatTime(activeConversation.updated_at) }}
                                </CardDescription>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-black bg-emerald-500/10 text-emerald-600 border-emerald-500/20 uppercase tracking-wider">
                                Live Connection
                            </span>
                        </div>
                    </CardHeader>

                    <!-- Message History Area -->
                    <div 
                        ref="messageContainer" 
                        class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50/50 dark:bg-slate-950/20"
                    >
                        <div v-if="activeConversation.messages.length === 0" class="text-center py-12 text-xs text-muted-foreground font-semibold italic">
                            No chat history with this customer. Write a message below to initiate the thread.
                        </div>
                        <div 
                            v-for="msg in activeConversation.messages" 
                            :key="msg.id"
                            :class="[
                                msg.sender === 'agent' ? 'justify-end' : 'justify-start'
                            ]"
                            class="flex"
                        >
                            <div 
                                :class="[
                                    msg.sender === 'agent' 
                                        ? 'bg-indigo-500 text-white rounded-t-2xl rounded-l-2xl border-indigo-600' 
                                        : 'bg-white dark:bg-slate-900 text-foreground rounded-t-2xl rounded-r-2xl border-slate-200 dark:border-slate-800'
                                ]"
                                class="max-w-[70%] p-3.5 border-2 border-b-4 shadow-sm rounded-lg relative"
                            >
                                <span class="block text-[8px] font-bold uppercase tracking-wider mb-1 opacity-70">
                                    {{ msg.sender === 'agent' ? 'You' : 'Customer' }}
                                </span>
                                <p class="text-xs font-semibold leading-relaxed">{{ msg.body }}</p>
                                <span class="block text-[7.5px] font-mono text-right mt-1 opacity-60">
                                    {{ formatTime(msg.created_at) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Input Panel (Duolingo Style Exaggerated) -->
                    <div class="p-4 border-t bg-background">
                        <form @submit.prevent="submitMessage" class="flex gap-3">
                            <Input 
                                v-model="form.body"
                                placeholder="Type an SMS response message..." 
                                class="flex-1 border-2 border-slate-300 dark:border-slate-800 focus:border-indigo-500 rounded-xl h-11 text-xs font-semibold shadow-xs"
                                :disabled="form.processing"
                            />
                            <Button 
                                type="submit" 
                                size="icon"
                                :disabled="form.processing || !form.body.trim()"
                                class="h-11 w-11 rounded-xl bg-indigo-500 hover:bg-indigo-600 text-white border-b-4 border-indigo-700 active:border-b-0 active:translate-y-[4px] transition-all flex items-center justify-center"
                            >
                                <Send class="h-4 w-4" />
                            </Button>
                        </form>
                    </div>
                </Card>

                <Card v-else class="border-4 border-b-8 border-slate-300 dark:border-slate-800 rounded-3xl flex items-center justify-center h-[600px] text-center p-8">
                    <div>
                        <div class="h-16 w-16 bg-slate-100 dark:bg-slate-900 border-2 rounded-2xl flex items-center justify-center mx-auto text-slate-400 mb-4">
                            <MessageSquare class="h-8 w-8" />
                        </div>
                        <h3 class="text-sm font-black uppercase text-foreground">No Thread Selected</h3>
                        <p class="text-xs text-muted-foreground mt-2 max-w-[280px]">
                            Select a customer conversation from the list to view chat logs and send real-time SMS updates.
                        </p>
                    </div>
                </Card>
            </div>

        </div>
    </div>
</template>
