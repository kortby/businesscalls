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
import {
    MessageSquare,
    Send,
    Check,
    Archive,
    Clock,
    Phone,
    User as UserIcon,
} from '@lucide/vue';

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
    props.conversations.length > 0 ? props.conversations[0].id : null,
);

const activeConversation = computed(
    () =>
        liveConversations.value.find(
            (c) => c.id === activeConversationId.value,
        ) || null,
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
        },
    });
};

// Auto-scroll logic for the chat window
const messageContainer = ref<HTMLDivElement | null>(null);
const scrollToBottom = () => {
    setTimeout(() => {
        if (messageContainer.value) {
            messageContainer.value.scrollTop =
                messageContainer.value.scrollHeight;
        }
    }, 50);
};

// Sync liveConversations when props update from the server
watch(
    () => props.conversations,
    (newVal) => {
        liveConversations.value = [...newVal];
        if (
            activeConversationId.value &&
            !newVal.some((c) => c.id === activeConversationId.value) &&
            newVal.length > 0
        ) {
            activeConversationId.value = newVal[0].id;
        }
    },
    { deep: true },
);

watch(activeConversationId, () => {
    scrollToBottom();
});

// Setup WebSocket Listener for Real-Time Omni-Channel messages
onMounted(() => {
    scrollToBottom();

    if (props.tenant) {
        useEcho(
            `tenant.${props.tenant.id}`,
            'message.received',
            (payload: any) => {
                const msg = payload.message;

                // Instantly update Rive mascot visual triggers (Victory)
                transitionMascot(2);

                const conv = liveConversations.value.find(
                    (c) => c.id === msg.conversation_id,
                );
                if (conv) {
                    if (!conv.messages.some((m) => m.id === msg.id)) {
                        conv.messages.push(msg);
                    }

                    // Sort conversation to the top
                    liveConversations.value = [
                        conv,
                        ...liveConversations.value.filter(
                            (c) => c.id !== conv.id,
                        ),
                    ];

                    if (activeConversationId.value === conv.id) {
                        scrollToBottom();
                    }
                }
            },
        );
    }
});

const formatTime = (isoString: string) => {
    return new Date(isoString).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDate = (isoString: string) => {
    return new Date(isoString).toLocaleDateString([], {
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Omni-Channel Customer Conversations" />

    <div class="min-h-screen bg-background p-6 text-foreground">
        <!-- Top Page Header -->
        <div class="mb-8 flex items-center justify-between border-b pb-6">
            <div class="flex items-center gap-4">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl border-b-4 border-indigo-700 bg-indigo-500 text-white shadow-xs"
                >
                    <MessageSquare class="h-6 w-6" />
                </div>
                <div>
                    <h1
                        class="text-3xl font-black tracking-tight text-foreground uppercase"
                    >
                        Omni-Channel Customer Chat
                    </h1>
                    <p
                        class="mt-1 text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                    >
                        Live SMS & Web messaging for
                        {{ tenant?.name ?? 'businesscalls' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Layout Grid: Left Sidebar Threads List & Right Chat Panel -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- LEFT THREADS PANEL -->
            <div class="flex flex-col gap-6">
                <!-- Rive Mascot Integration -->
                <div class="h-64 shrink-0">
                    <DispatcherMascot :state="mascotState" />
                </div>

                <Card
                    class="overflow-hidden rounded-3xl border-4 border-b-8 border-slate-300 dark:border-slate-800"
                >
                    <CardHeader class="border-b pb-4">
                        <CardTitle
                            class="text-lg font-black tracking-wider uppercase"
                            >Active Conversations</CardTitle
                        >
                        <CardDescription
                            class="text-[10px] font-bold tracking-widest text-muted-foreground uppercase"
                        >
                            Sorted dynamically by recency
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="max-h-[550px] overflow-y-auto p-0 pr-1">
                        <div
                            v-if="liveConversations.length === 0"
                            class="p-8 text-center text-xs font-semibold text-muted-foreground italic"
                        >
                            No active conversations found.
                        </div>
                        <div v-else class="divide-y divide-border">
                            <button
                                v-for="conv in liveConversations"
                                :key="conv.id"
                                @click="selectConversation(conv.id)"
                                :class="[
                                    activeConversationId === conv.id
                                        ? 'border-y border-l-8 border-y-indigo-500/20 border-l-indigo-500 bg-indigo-500/10 dark:bg-indigo-500/5'
                                        : 'hover:bg-accent/40',
                                ]"
                                class="flex w-full items-start gap-3 border-l-4 border-l-transparent p-4 text-left transition-all"
                            >
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border-2 bg-slate-100 font-bold text-slate-600 dark:bg-slate-900 dark:text-slate-400"
                                >
                                    {{ conv.customer_phone.slice(-4) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="mb-1 flex items-center justify-between"
                                    >
                                        <span
                                            class="block truncate text-xs font-black text-foreground"
                                        >
                                            {{ conv.customer_phone }}
                                        </span>
                                        <span
                                            class="font-mono text-[9px] font-bold text-muted-foreground"
                                        >
                                            {{
                                                conv.messages.length > 0
                                                    ? formatTime(
                                                          conv.messages[
                                                              conv.messages
                                                                  .length - 1
                                                          ].created_at,
                                                      )
                                                    : ''
                                            }}
                                        </span>
                                    </div>
                                    <p
                                        class="mb-1 truncate text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                                    >
                                        {{ conv.subject ?? 'General Inquiry' }}
                                    </p>
                                    <p
                                        class="truncate text-xs text-slate-500 italic dark:text-slate-400"
                                    >
                                        {{
                                            conv.messages.length > 0
                                                ? conv.messages[
                                                      conv.messages.length - 1
                                                  ].body
                                                : '(No messages)'
                                        }}
                                    </p>
                                    <div class="mt-2 flex gap-1.5">
                                        <Badge
                                            :variant="
                                                conv.status === 'open'
                                                    ? 'default'
                                                    : 'secondary'
                                            "
                                            class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase"
                                            :class="[
                                                conv.status === 'open'
                                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/10'
                                                    : 'border-slate-500/20 bg-slate-500/10 text-slate-600 hover:bg-slate-500/10',
                                            ]"
                                        >
                                            {{ conv.status }}
                                        </Badge>
                                        <Badge
                                            v-if="conv.user"
                                            variant="outline"
                                            class="border-indigo-500/20 px-1.5 py-0.5 text-[8px] font-bold text-indigo-500"
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
            <div class="flex flex-col gap-6 lg:col-span-2">
                <Card
                    v-if="activeConversation"
                    class="flex h-[600px] flex-col overflow-hidden rounded-3xl border-4 border-b-8 border-indigo-400 dark:border-indigo-950"
                >
                    <!-- Active Conversation Header -->
                    <CardHeader
                        class="flex flex-row items-center justify-between border-b bg-indigo-500/5 pb-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500 font-bold text-white"
                            >
                                <Phone class="h-5 w-5" />
                            </div>
                            <div>
                                <CardTitle
                                    class="text-sm font-black text-foreground"
                                    >{{
                                        activeConversation.customer_phone
                                    }}</CardTitle
                                >
                                <CardDescription
                                    class="mt-0.5 flex items-center gap-1.5 text-[10px] font-semibold tracking-widest text-muted-foreground uppercase"
                                >
                                    <Clock class="h-3 w-3" /> Last Active:
                                    {{
                                        formatDate(
                                            activeConversation.updated_at,
                                        )
                                    }}
                                    at
                                    {{
                                        formatTime(
                                            activeConversation.updated_at,
                                        )
                                    }}
                                </CardDescription>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-md border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 text-xs font-black tracking-wider text-emerald-600 uppercase"
                            >
                                Live Connection
                            </span>
                        </div>
                    </CardHeader>

                    <!-- Message History Area -->
                    <div
                        ref="messageContainer"
                        class="flex-1 space-y-4 overflow-y-auto bg-slate-50/50 p-6 dark:bg-slate-950/20"
                    >
                        <div
                            v-if="activeConversation.messages.length === 0"
                            class="py-12 text-center text-xs font-semibold text-muted-foreground italic"
                        >
                            No chat history with this customer. Write a message
                            below to initiate the thread.
                        </div>
                        <div
                            v-for="msg in activeConversation.messages"
                            :key="msg.id"
                            :class="[
                                msg.sender === 'agent'
                                    ? 'justify-end'
                                    : 'justify-start',
                            ]"
                            class="flex"
                        >
                            <div
                                :class="[
                                    msg.sender === 'agent'
                                        ? 'rounded-t-2xl rounded-l-2xl border-indigo-600 bg-indigo-500 text-white'
                                        : 'rounded-t-2xl rounded-r-2xl border-slate-200 bg-white text-foreground dark:border-slate-800 dark:bg-slate-900',
                                ]"
                                class="relative max-w-[70%] rounded-lg border-2 border-b-4 p-3.5 shadow-sm"
                            >
                                <span
                                    class="mb-1 block text-[8px] font-bold tracking-wider uppercase opacity-70"
                                >
                                    {{
                                        msg.sender === 'agent'
                                            ? 'You'
                                            : 'Customer'
                                    }}
                                </span>
                                <p
                                    class="text-xs leading-relaxed font-semibold"
                                >
                                    {{ msg.body }}
                                </p>
                                <span
                                    class="mt-1 block text-right font-mono text-[7.5px] opacity-60"
                                >
                                    {{ formatTime(msg.created_at) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Input Panel (Duolingo Style Exaggerated) -->
                    <div class="border-t bg-background p-4">
                        <form
                            @submit.prevent="submitMessage"
                            class="flex gap-3"
                        >
                            <Input
                                v-model="form.body"
                                placeholder="Type an SMS response message..."
                                class="h-11 flex-1 rounded-xl border-2 border-slate-300 text-xs font-semibold shadow-xs focus:border-indigo-500 dark:border-slate-800"
                                :disabled="form.processing"
                            />
                            <Button
                                type="submit"
                                size="icon"
                                :disabled="form.processing || !form.body.trim()"
                                class="flex h-11 w-11 items-center justify-center rounded-xl border-b-4 border-indigo-700 bg-indigo-500 text-white transition-all hover:bg-indigo-600 active:translate-y-[4px] active:border-b-0"
                            >
                                <Send class="h-4 w-4" />
                            </Button>
                        </form>
                    </div>
                </Card>

                <Card
                    v-else
                    class="flex h-[600px] items-center justify-center rounded-3xl border-4 border-b-8 border-slate-300 p-8 text-center dark:border-slate-800"
                >
                    <div>
                        <div
                            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl border-2 bg-slate-100 text-slate-400 dark:bg-slate-900"
                        >
                            <MessageSquare class="h-8 w-8" />
                        </div>
                        <h3
                            class="text-sm font-black text-foreground uppercase"
                        >
                            No Thread Selected
                        </h3>
                        <p
                            class="mt-2 max-w-[280px] text-xs text-muted-foreground"
                        >
                            Select a customer conversation from the list to view
                            chat logs and send real-time SMS updates.
                        </p>
                    </div>
                </Card>
            </div>
        </div>
    </div>
</template>
