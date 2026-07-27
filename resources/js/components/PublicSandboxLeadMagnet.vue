<script setup lang="ts">
import { ref, computed } from 'vue';
import {
    PhoneCall,
    Mic,
    ShieldCheck,
    Clock,
    Zap,
    Send,
    Sparkles,
    CheckCircle2,
    Play,
    Pause,
    RotateCcw,
    Radio,
    UserCheck,
    AlertCircle,
} from '@lucide/vue';
import SpectralVisualizer from '@/components/SpectralVisualizer.vue';
import { Badge } from '@/components/ui/badge';

// IVR Scenario Options
const scenarios = [
    {
        id: 'emergency',
        title: 'Option 1: Emergency Pipe Burst Triage',
        key: '1',
        trade: 'Plumbing',
        icon: Zap,
        color: 'rose',
        customerPrompt: 'Emergency! Water is pouring through my ceiling from the upstairs bathroom!',
        aiResponse: 'I understand this is an urgent water leak emergency. I am immediately isolating your main shutoff guidance and dispatching our top-rated emergency plumber within your area.',
        techAssigned: 'Marcus Vance (Master Plumber)',
        bufferApplied: '1.5h Travel Buffer Reserved',
        skillRequired: 'Emergency Shutoff & Pipe Triage',
        status: 'Priority Emergency Dispatch Created',
    },
    {
        id: 'hvac',
        title: 'Option 2: AC Repair & Diagnostics',
        key: '2',
        trade: 'HVAC',
        icon: Clock,
        color: 'sky',
        customerPrompt: 'My central AC unit stopped cooling and is blowing hot air on a 95° day.',
        aiResponse: 'Got it. I am checking technician shifts EPA 608 certified for AC compressor diagnostics and booking your appointment for 2:00 PM today.',
        techAssigned: 'Amanda Ross (EPA 608 Tech)',
        bufferApplied: '1.5h Shift Gap Verified',
        skillRequired: 'AC Compressor & Freon Diagnostic',
        status: 'Appointment Booked & Confirmed',
    },
    {
        id: 'electrical',
        title: 'Option 3: Panel Upgrade & Short Circuit',
        key: '3',
        trade: 'Electrical',
        icon: ShieldCheck,
        color: 'amber',
        customerPrompt: 'Our circuit breaker keeps tripping when the commercial oven turns on.',
        aiResponse: 'I am routing this to an electrician qualified in 200A commercial panels and scheduling an inspection slot with zero schedule overlap.',
        techAssigned: 'Devon Lane (Master Electrician)',
        bufferApplied: '1.5h Travel Buffer Clear',
        skillRequired: 'Commercial Panel Triage',
        status: 'Dispatch En Route Reserved',
    },
];

const selectedScenarioId = ref<string>('emergency');
const selectedScenario = computed(() => scenarios.find((s) => s.id === selectedScenarioId.value) || scenarios[0]);

// Simulation State Engine
const isSimulating = ref(false);
const currentStep = ref<number>(0); // 0: Idle, 1: IVR Ringing, 2: AI Speech Analysis, 3: Skill & Buffer Check, 4: Booked
const simulatedTranscript = ref<string[]>([]);
const leadEmail = ref('');
const leadPhone = ref('');
const leadSubmitted = ref(false);
const leadLoading = ref(false);

const runSandboxSimulation = (scenarioId?: string) => {
    if (scenarioId) {
        selectedScenarioId.value = scenarioId;
    }
    isSimulating.value = true;
    currentStep.value = 1;
    simulatedTranscript.value = ['[IVR Telephony Node]: Call connected. Press key or select prompt...'];

    setTimeout(() => {
        currentStep.value = 2;
        simulatedTranscript.value.push(`[Customer Speech]: "${selectedScenario.value.customerPrompt}"`);
    }, 1200);

    setTimeout(() => {
        currentStep.value = 3;
        simulatedTranscript.value.push(`[AI Receptionist Voice]: "${selectedScenario.value.aiResponse}"`);
        simulatedTranscript.value.push(`[Engine Check]: Skill Tag "${selectedScenario.value.skillRequired}" matched.`);
        simulatedTranscript.value.push(`[Buffer Protection]: ${selectedScenario.value.bufferApplied}.`);
    }, 2800);

    setTimeout(() => {
        currentStep.value = 4;
        simulatedTranscript.value.push(`[Dispatch Success]: Assigned to ${selectedScenario.value.techAssigned}. Status: ${selectedScenario.value.status}.`);
        isSimulating.value = false;
    }, 4500);
};

const resetSandbox = () => {
    isSimulating.value = false;
    currentStep.value = 0;
    simulatedTranscript.value = [];
};

const handleLeadSubmit = () => {
    if (!leadEmail.value && !leadPhone.value) return;
    leadLoading.value = true;
    setTimeout(() => {
        leadLoading.value = false;
        leadSubmitted.value = true;
    }, 800);
};
</script>

<template>
    <section id="sandbox-demo" class="relative overflow-hidden border-b bg-slate-900 py-16 text-white md:py-24">
        <!-- Ambient Glowing Orbs -->
        <div class="animate-float-slow absolute top-10 left-10 -z-10 h-96 w-96 rounded-full bg-emerald-500/10 blur-3xl"></div>
        <div class="animate-float-delayed absolute right-10 bottom-10 -z-10 h-96 w-96 rounded-full bg-indigo-500/15 blur-3xl"></div>
        <div class="absolute inset-0 -z-20 bg-[linear-gradient(to_right,#ffffff0a_1px,transparent_1px),linear-gradient(to_bottom,#ffffff0a_1px,transparent_1px)] bg-[size:24px_24px]"></div>

        <div class="container mx-auto px-4 sm:px-6">
            <!-- Header section -->
            <div class="mx-auto mb-12 max-w-[850px] space-y-4 text-center">
                <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-emerald-400 backdrop-blur-md">
                    <Radio class="h-4 w-4 animate-pulse text-emerald-400" />
                    <span>Risk-Free Lead Magnet • Interactive Sandbox Mode</span>
                </div>

                <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl md:text-5xl">
                    Test Interactive <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-indigo-400 bg-clip-text text-transparent">IVR & AI Voice Routing</span> Direct On Site
                </h2>

                <p class="text-base leading-relaxed text-slate-300 sm:text-lg">
                    Experience how our AI voice receptionist handles live trade emergency calls, queries technician skills, and enforces 1.5-hour travel buffers in real-time — with <strong>zero sign-up required</strong>.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Left Column: Interactive Scenario & IVR Controller -->
                <div class="flex flex-col space-y-6 lg:col-span-6">
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-6 shadow-2xl backdrop-blur-xl">
                        <div class="mb-4 flex items-center justify-between border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-2">
                                <Sparkles class="h-5 w-5 text-emerald-400" />
                                <h3 class="text-lg font-bold text-white">Select Interactive Scenario</h3>
                            </div>
                            <Badge variant="outline" class="border-emerald-500/30 bg-emerald-500/10 text-xs text-emerald-400">
                                Sandbox Mode Active
                            </Badge>
                        </div>

                        <!-- Scenarios Buttons -->
                        <div class="space-y-3">
                            <button
                                v-for="sc in scenarios"
                                :key="sc.id"
                                @click="runSandboxSimulation(sc.id)"
                                :disabled="isSimulating"
                                class="flex w-full items-center justify-between rounded-xl border p-4 text-left transition-all duration-300"
                                :class="[
                                    selectedScenarioId === sc.id
                                        ? 'border-emerald-500 bg-emerald-950/40 text-white shadow-lg ring-1 ring-emerald-500/50'
                                        : 'border-slate-800 bg-slate-900/60 text-slate-300 hover:border-slate-700 hover:bg-slate-900',
                                    isSimulating ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer'
                                ]"
                            >
                                <div class="flex items-center gap-3.5">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/20 text-emerald-400">
                                        <component :is="sc.icon" class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-mono font-bold text-emerald-400">KEY {{ sc.key }}</span>
                                            <span class="text-sm font-bold text-white">{{ sc.title }}</span>
                                        </div>
                                        <p class="text-xs text-slate-400">{{ sc.skillRequired }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Play class="h-4 w-4 text-emerald-400" v-if="selectedScenarioId === sc.id && !isSimulating" />
                                    <Radio class="h-4 w-4 animate-spin text-emerald-400" v-else-if="selectedScenarioId === sc.id && isSimulating" />
                                </div>
                            </button>
                        </div>

                        <!-- Action Control Buttons -->
                        <div class="mt-6 flex items-center justify-between gap-3">
                            <button
                                @click="runSandboxSimulation()"
                                :disabled="isSimulating"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-sm font-bold text-slate-950 shadow-lg transition-all hover:bg-emerald-400 hover:shadow-emerald-500/25 active:scale-95 disabled:opacity-50"
                            >
                                <Play class="h-4 w-4 fill-current" />
                                <span>{{ isSimulating ? 'Simulating Call...' : 'Run IVR Test Prompt' }}</span>
                            </button>

                            <button
                                @click="resetSandbox"
                                :disabled="isSimulating"
                                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-xs font-semibold text-slate-300 transition-colors hover:bg-slate-800 active:scale-95"
                            >
                                <RotateCcw class="h-4 w-4" />
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <!-- Direct Live Telephone Call Card -->
                    <div class="flex items-center justify-between rounded-2xl border border-emerald-500/30 bg-gradient-to-r from-emerald-950/60 via-slate-900/80 to-slate-950/90 p-5 backdrop-blur-md">
                        <div class="flex items-center gap-3.5">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-slate-950 shadow-md">
                                <PhoneCall class="h-6 w-6 animate-pulse" />
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-white">Prefer to test via real phone call?</h4>
                                <p class="text-xs text-slate-300">Dial our active test line to experience live voice speech: <strong class="text-emerald-400">+1 (619) 639-0411</strong></p>
                            </div>
                        </div>
                        <a
                            href="tel:+16196390411"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md transition-all hover:bg-emerald-400 active:scale-95"
                        >
                            <PhoneCall class="h-3.5 w-3.5" />
                            <span>Call Line</span>
                        </a>
                    </div>
                </div>

                <!-- Right Column: Live Telephony & Dispatch Output Console -->
                <div class="flex flex-col space-y-6 lg:col-span-6">
                    <div class="relative flex flex-col justify-between rounded-2xl border border-slate-800 bg-slate-950 p-6 shadow-2xl">
                        <!-- Top status bar -->
                        <div class="mb-4 flex items-center justify-between border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full" :class="currentStep > 0 ? 'bg-emerald-400 animate-ping' : 'bg-slate-600'"></span>
                                <span class="text-xs font-mono font-bold uppercase tracking-wider text-slate-400">
                                    {{ currentStep === 0 ? 'STATUS: IDLE STANDBY' : currentStep === 4 ? 'STATUS: JOB DISPATCH CONFIRMED' : 'STATUS: LIVE CALL PROCESSING' }}
                                </span>
                            </div>
                            <span class="text-[11px] font-mono text-slate-500">Sub-Second AI Engine</span>
                        </div>

                        <!-- Audio Spectral Visualizer -->
                        <div class="mb-4 rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                            <div class="mb-2 flex items-center justify-between text-xs text-slate-400">
                                <span class="flex items-center gap-1.5"><Mic class="h-3.5 w-3.5 text-emerald-400" /> Audio Stream Visualizer</span>
                                <span class="font-mono text-emerald-400" v-if="currentStep > 0 && currentStep < 4">Synthesizing...</span>
                            </div>
                            <SpectralVisualizer :is-active="currentStep > 0 && currentStep < 4" />
                        </div>

                        <!-- Live Terminal / Speech Console -->
                        <div class="mb-4 min-h-[160px] rounded-xl border border-slate-800 bg-slate-900/90 p-4 font-mono text-xs text-slate-300 shadow-inner overflow-y-auto">
                            <div v-if="simulatedTranscript.length === 0" class="flex h-full min-h-[120px] flex-col items-center justify-center text-center text-slate-500">
                                <Radio class="mb-2 h-8 w-8 text-slate-700" />
                                <p>Click any IVR scenario option on the left to initiate the live sandbox test.</p>
                            </div>
                            <div v-else class="space-y-2">
                                <div v-for="(line, idx) in simulatedTranscript" :key="idx" class="leading-relaxed">
                                    <span v-if="line.startsWith('[Customer')" class="text-sky-300 font-bold">{{ line }}</span>
                                    <span v-else-if="line.startsWith('[AI Receptionist')" class="text-emerald-400 font-bold">{{ line }}</span>
                                    <span v-else-if="line.startsWith('[Dispatch')" class="text-emerald-300 font-extrabold bg-emerald-950/60 p-1 rounded border border-emerald-500/30 block">{{ line }}</span>
                                    <span v-else class="text-slate-400">{{ line }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Live Lead Magnet Capture Container -->
                        <div class="rounded-xl border border-emerald-500/20 bg-emerald-950/20 p-4">
                            <div v-if="!leadSubmitted" class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-400">
                                        Send Full Sandbox Audio & Report To Your Phone/Email
                                    </h4>
                                    <Badge variant="outline" class="border-emerald-500/30 bg-emerald-500/10 text-[10px] text-emerald-300">
                                        Free Instant Lead Magnet
                                    </Badge>
                                </div>

                                <form @submit.prevent="handleLeadSubmit" class="flex flex-col gap-2 sm:flex-row">
                                    <input
                                        v-model="leadEmail"
                                        type="email"
                                        placeholder="Enter your email or phone..."
                                        class="h-10 flex-1 rounded-lg border border-slate-700 bg-slate-900 px-3 text-xs text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-hidden"
                                        required
                                    />
                                    <button
                                        type="submit"
                                        :disabled="leadLoading"
                                        class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg bg-emerald-500 px-4 text-xs font-bold text-slate-950 transition-all hover:bg-emerald-400 active:scale-95 disabled:opacity-50 cursor-pointer"
                                    >
                                        <Send class="h-3.5 w-3.5" />
                                        <span>{{ leadLoading ? 'Sending...' : 'Get Instant Demo' }}</span>
                                    </button>
                                </form>
                            </div>

                            <div v-else class="flex items-center gap-3 text-emerald-400">
                                <CheckCircle2 class="h-5 w-5 shrink-0" />
                                <p class="text-xs font-semibold">
                                    Demo results sent! Check your inbox or phone for your direct sandbox recording and workflow blueprint.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
@keyframes float-slow {
    0%, 100% { transform: translateY(0px) scale(1); }
    50% { transform: translateY(-15px) scale(1.05); }
}
@keyframes float-delayed {
    0%, 100% { transform: translateY(0px) scale(1.05); }
    50% { transform: translateY(15px) scale(1); }
}
.animate-float-slow {
    animation: float-slow 8s ease-in-out infinite;
}
.animate-float-delayed {
    animation: float-delayed 10s ease-in-out infinite;
}
</style>
