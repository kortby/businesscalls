import { reactive } from 'vue';

export interface WebhookEvent {
    event_id: string;
    event: string;
    is_duplicate: boolean;
    timestamp: string;
    url: string;
}

export interface CallStore {
    isSpeaking: boolean;
    transcript: string;
    vapiClient: any | null;
    retellClient: any | null;
    recentWebhookEvents: WebhookEvent[];
    amplitude: number;
}

export const callStore = reactive<CallStore>({
    isSpeaking: false,
    transcript: '',
    vapiClient: null,
    retellClient: null,
    recentWebhookEvents: [],
    amplitude: 0,
});
