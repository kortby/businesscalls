<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Activity,
    CheckCircle,
    AlertTriangle,
    XCircle,
    Server,
    Shield,
    Database,
    Zap,
    TrendingUp,
    Play,
    RefreshCw
} from '@lucide/vue';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import DispatcherMascot from '@/components/DispatcherMascot.vue';
import { callStore } from '@/lib/store';

const props = defineProps<{
    tenant: {
        id: number;
        name: string;
        slug: string;
        settings: Record<string, any>;
    };
    resilienceScore: number;
    failoverEventsCount: number;
    timingSettings: {
        startSpeakingPlan: number;
        stopSpeakingPlan: number;
    };
    spendUsage: number;
    spendLimit: number;
}>();

// System status states
const ttsStatus = ref<'optimal' | 'fallback' | 'offline'>('optimal');
const llmStatus = ref<'optimal' | 'fallback' | 'offline'>('optimal');
const sttStatus = ref<'optimal' | 'fallback' | 'offline'>('optimal');

const activeSkin = ref<string>((props.tenant.settings?.active_skin as string) || 'standard');
const simulatedFailoversCount = ref<number>(props.failoverEventsCount);
const simulatedDowntime = ref<number>(0);

// Calculate mathematical index for operational resilience score
const calculatedResilienceScore = computed(() => {
    const F = props.failoverEventsCount + simulatedFailoversCount.value;
    if (F === 0) {
        return 1.0;
    }
    const totalDowntime = simulatedDowntime.value;
    const totalSessionTime = 180 * F; // assume 3 min call sessions on average
    
    const downtimeTerm = 1.0 - (totalDowntime / totalSessionTime);
    const clampedDowntimeTerm = Math.max(0.0, downtimeTerm);
    
    // We assume successful recovery rate is high (e.g. 100% or based on status)
    const successRate = (ttsStatus.value !== 'offline' && llmStatus.value !== 'offline') ? 1.0 : 0.5;
    
    return clampedDowntimeTerm * successRate;
});

// Mascot state binding:
// - If both primary and fallback systems go offline: transition to sad error state (3)
// - If any voice or LLM failovers are actively triggered (scanning for hot backups): scanning radar (1)
// - If all services are fully functional (optimal): celebratory victory gesture (2)
const mascotState = computed(() => {
    if (ttsStatus.value === 'offline' && llmStatus.value === 'offline') {
        return 3; // All offline -> Sad error
    }
    if (ttsStatus.value === 'fallback' || llmStatus.value === 'fallback' || sttStatus.value === 'fallback') {
        return 1; // Scanning/Failover active
    }
    if (ttsStatus.value === 'optimal' && llmStatus.value === 'optimal' && sttStatus.value === 'optimal') {
        return 2; // Fully functional -> Victory
    }
    return 2;
});

// Canvas Waveform setup
const canvasRef = ref<HTMLCanvasElement | null>(null);
let animationFrameId: number | null = null;
let phase = 0;

const drawWave = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    // Set pixel ratio scaling
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * window.devicePixelRatio;
    canvas.height = rect.height * window.devicePixelRatio;
    ctx.scale(window.devicePixelRatio, window.devicePixelRatio);

    const width = rect.width;
    const height = rect.height;
    ctx.clearRect(0, 0, width, height);

    // Determine colors & speeds based on active failover states
    let waveColors = ['#58cc02', '#78d633', '#b1f382']; // green functional waves
    let waveSpeed = 0.05;
    let waveAmplitude = 15;

    if (mascotState.value === 1) {
        waveColors = ['#ffc800', '#ffd933', '#ffe880']; // orange/amber scanning waves
        waveSpeed = 0.12;
        waveAmplitude = 25;
    } else if (mascotState.value === 3) {
        waveColors = ['#ff4b4b', '#ff7373', '#ff9999']; // red flat/distressed waves
        waveSpeed = 0.02;
        waveAmplitude = 4;
    }

    const waveCount = 3;
    const centerY = height / 2;

    for (let w = 0; w < waveCount; w++) {
        ctx.beginPath();
        ctx.lineWidth = 4; // Thick borders (Duolingo style)
        ctx.strokeStyle = waveColors[w % waveColors.length];
        ctx.fillStyle = waveColors[w % waveColors.length] + '15'; // saturated fill

        ctx.moveTo(0, centerY);

        for (let x = 0; x < width; x++) {
            const angle = (x / width) * Math.PI * 4;
            const sineValue = Math.sin(angle + phase + w);
            const y = centerY + sineValue * waveAmplitude * (1.0 - (w * 0.25));
            ctx.lineTo(x, y);
        }

        ctx.lineTo(width, height);
        ctx.lineTo(0, height);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();
    }

    phase += waveSpeed;
    animationFrameId = requestAnimationFrame(drawWave);
};

// Simulation Helpers
const triggerTtsFailover = () => {
    ttsStatus.value = 'fallback';
    simulatedFailoversCount.value += 1;
    simulatedDowntime.value += 4; // 4 seconds downtime
};

const triggerLlmFailover = () => {
    llmStatus.value = 'fallback';
    simulatedFailoversCount.value += 1;
    simulatedDowntime.value += 6; // 6 seconds downtime
};

const triggerSttFailover = () => {
    sttStatus.value = 'fallback';
    simulatedFailoversCount.value += 1;
    simulatedDowntime.value += 2; // 2 seconds downtime
};

const triggerGlobalCollapse = () => {
    ttsStatus.value = 'offline';
    llmStatus.value = 'offline';
    sttStatus.value = 'offline';
    simulatedFailoversCount.value += 3;
    simulatedDowntime.value += 45; // 45 seconds downtime
};

const resetToOptimal = () => {
    ttsStatus.value = 'optimal';
    llmStatus.value = 'optimal';
    sttStatus.value = 'optimal';
    simulatedFailoversCount.value = props.failoverEventsCount;
    simulatedDowntime.value = 0;
};

onMounted(() => {
    drawWave();
    window.addEventListener('resize', drawWave);
});

onUnmounted(() => {
    window.removeEventListener('resize', drawWave);
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
    }
});
</script>

<template>
    <Head title="System Status HUD" />

    <div class="mx-auto flex max-w-[1400px] flex-col gap-8 p-4 sm:p-6 md:p-8 bg-slate-950 text-slate-100 min-h-screen">
        
        <!-- Duolingo style Geometric Title Banner -->
        <div class="relative overflow-hidden rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-[6px_6px_0px_0px_rgba(88,204,2,0.3)]">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-white sm:text-4xl flex items-center gap-3">
                        <span class="rounded-2xl border-4 border-emerald-500 bg-emerald-600 px-3 py-1 text-white shadow-[2px_2px_0px_0px_rgba(15,23,42,1)]">STATUS</span>
                        Infrastructure Outage HUD
                    </h1>
                    <p class="text-slate-400 font-bold mt-2 text-sm sm:text-base">
                        Programmatic TTS fallbacks, backup local LLM model gateways, and real-time resilience scoreboards.
                    </p>
                </div>

                <!-- Resilience Rating Dashboard -->
                <div class="flex flex-col gap-2 min-w-[280px] bg-slate-950 p-4 rounded-2xl border-4 border-slate-800">
                    <div class="flex justify-between text-xs font-black tracking-widest text-slate-400 uppercase">
                        <span>Resilience Rating</span>
                        <span :class="[calculatedResilienceScore > 0.9 ? 'text-emerald-500' : 'text-amber-500']">
                            {{ (calculatedResilienceScore * 100).toFixed(1) }}%
                        </span>
                    </div>

                    <div class="h-4 w-full bg-slate-850 rounded-full overflow-hidden border-2 border-slate-700">
                        <div 
                            class="h-full rounded-full transition-all duration-300"
                            :class="[
                                calculatedResilienceScore > 0.9 ? 'bg-emerald-500' : (calculatedResilienceScore > 0.6 ? 'bg-amber-500' : 'bg-rose-500')
                            ]"
                            :style="{ width: `${calculatedResilienceScore * 100}%` }"
                        ></div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-400 font-bold">
                        <span>Failovers Today: {{ simulatedFailoversCount }}</span>
                        <span>Downtime: {{ simulatedDowntime }}s</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Matrix -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            
            <!-- Health Monitor List (2/3 columns) -->
            <div class="flex flex-col gap-8 lg:col-span-2">
                
                <!-- Infrastructure table -->
                <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-6 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                    <h2 class="text-xl font-black text-white flex items-center gap-2 border-b-4 border-slate-800 pb-4">
                        <Server class="h-6 w-6 text-emerald-500" />
                        Infrastructure Health Monitors
                    </h2>

                    <!-- Clean Single-Spaced Health Monitors Table -->
                    <div class="overflow-x-auto rounded-2xl border-4 border-slate-850 bg-slate-950">
                        <table class="w-full text-left border-collapse text-xs font-bold">
                            <thead>
                                <tr class="bg-slate-900 text-slate-400 uppercase tracking-widest text-[10px] border-b-4 border-slate-800">
                                    <th class="p-3">Service Target</th>
                                    <th class="p-3">Primary Provider</th>
                                    <th class="p-3">Fallback Provider</th>
                                    <th class="p-3">Timeout Threshold</th>
                                    <th class="p-3 text-right">Operational Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-850">
                                <!-- TTS Row -->
                                <tr class="hover:bg-slate-900/30 transition-colors">
                                    <td class="p-3 text-white font-extrabold flex items-center gap-2">
                                        <Activity class="h-4.5 w-4.5 text-slate-400" />
                                        TTS Synthesis
                                    </td>
                                    <td class="p-3 font-mono">elevenlabs</td>
                                    <td class="p-3 font-mono">cartesia</td>
                                    <td class="p-3 font-mono">1500ms</td>
                                    <td class="p-3 text-right">
                                        <span 
                                            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-0.5 border-2 text-[9px] uppercase font-black"
                                            :class="{
                                                'bg-emerald-950/60 text-emerald-400 border-emerald-800': ttsStatus === 'optimal',
                                                'bg-amber-950/60 text-amber-400 border-amber-800': ttsStatus === 'fallback',
                                                'bg-rose-950/60 text-rose-400 border-rose-800': ttsStatus === 'offline',
                                            }"
                                        >
                                            {{ ttsStatus }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- LLM Row -->
                                <tr class="hover:bg-slate-900/30 transition-colors">
                                    <td class="p-3 text-white font-extrabold flex items-center gap-2">
                                        <Zap class="h-4.5 w-4.5 text-slate-400" />
                                        LLM Reasoning
                                    </td>
                                    <td class="p-3 font-mono">gpt-4.1</td>
                                    <td class="p-3 font-mono">claude-4.5-sonnet</td>
                                    <td class="p-3 font-mono">2500ms</td>
                                    <td class="p-3 text-right">
                                        <span 
                                            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-0.5 border-2 text-[9px] uppercase font-black"
                                            :class="{
                                                'bg-emerald-950/60 text-emerald-400 border-emerald-800': llmStatus === 'optimal',
                                                'bg-amber-950/60 text-amber-400 border-amber-800': llmStatus === 'fallback',
                                                'bg-rose-950/60 text-rose-400 border-rose-800': llmStatus === 'offline',
                                            }"
                                        >
                                            {{ llmStatus }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- STT Row -->
                                <tr class="hover:bg-slate-900/30 transition-colors">
                                    <td class="p-3 text-white font-extrabold flex items-center gap-2">
                                        <Database class="h-4.5 w-4.5 text-slate-400" />
                                        STT Transcription
                                    </td>
                                    <td class="p-3 font-mono">deepgram</td>
                                    <td class="p-3 font-mono">whisper</td>
                                    <td class="p-3 font-mono">1000ms</td>
                                    <td class="p-3 text-right">
                                        <span 
                                            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-0.5 border-2 text-[9px] uppercase font-black"
                                            :class="{
                                                'bg-emerald-950/60 text-emerald-400 border-emerald-800': sttStatus === 'optimal',
                                                'bg-amber-950/60 text-amber-400 border-amber-800': sttStatus === 'fallback',
                                                'bg-rose-950/60 text-rose-400 border-rose-800': sttStatus === 'offline',
                                            }"
                                        >
                                            {{ sttStatus }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- WebGL Spectral Status Waveform Canvas -->
                <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-4 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                    <div class="flex items-center justify-between border-b-4 border-slate-800 pb-4">
                        <h2 class="text-xl font-black text-white flex items-center gap-2">
                            <Activity class="h-6 w-6 text-emerald-500 animate-pulse" />
                            Spectral Load Waveform
                        </h2>
                        <span class="rounded-lg bg-slate-950 px-2.5 py-0.5 text-[10px] font-black text-slate-400 border-2 border-slate-800 uppercase">
                            Canvas Wave sync
                        </span>
                    </div>
                    
                    <div class="relative w-full h-36 overflow-hidden rounded-2xl border-4 border-slate-700 bg-slate-900 shadow-inner">
                        <canvas ref="canvasRef" class="w-full h-full block"></canvas>
                        <div class="absolute inset-0 pointer-events-none flex items-center justify-between px-6">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-400">TTS/LLM Outage Spectrogram</span>
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-emerald-500 animate-ping"></span>
                                <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Hydrated</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Sidebar Dashboard (1/3 columns) -->
            <div class="flex flex-col gap-8">
                
                <!-- Playful Mascot Widget with Rive machine triggers -->
                <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-4 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                    <h3 class="text-lg font-black text-white flex items-center gap-2 border-b-4 border-slate-800 pb-4">
                        <Sparkles class="h-5 w-5 text-amber-500" />
                        AI Dispatch Mascot
                    </h3>

                    <!-- Character container -->
                    <div class="h-[280px]">
                        <DispatcherMascot 
                            :state="mascotState" 
                            :is-speaking="mascotState === 1"
                            :amplitude="mascotState === 1 ? 50 : 0"
                            :skin="activeSkin"
                        />
                    </div>

                    <!-- Mascot trigger mapping list -->
                    <div class="flex flex-col gap-2.5 text-xs bg-slate-950 p-4 rounded-2xl border-4 border-slate-800 font-bold text-slate-350">
                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest pb-1 border-b border-slate-800">
                            Status machine trigger
                        </div>
                        <div class="flex justify-between items-center py-0.5">
                            <span>Services Optimal (Victory)</span>
                            <span class="rounded bg-emerald-950/80 px-2 py-0.5 text-[10px] font-black text-emerald-400 border border-emerald-800" :class="[mascotState === 2 ? 'ring-2 ring-emerald-500' : '']">
                                Trigger 2
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-0.5">
                            <span>Backup Scanning (Failover)</span>
                            <span class="rounded bg-amber-950/80 px-2 py-0.5 text-[10px] font-black text-amber-400 border border-amber-800" :class="[mascotState === 1 ? 'ring-2 ring-amber-500' : '']">
                                Trigger 1
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-0.5">
                            <span>Complete System Offline (Error)</span>
                            <span class="rounded bg-rose-950/80 px-2 py-0.5 text-[10px] font-black text-rose-400 border border-rose-800" :class="[mascotState === 3 ? 'ring-2 ring-rose-500' : '']">
                                Trigger 3
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Simulation Outage control Panel -->
                <div class="rounded-3xl border-4 border-slate-800 bg-slate-900 p-6 flex flex-col gap-4 shadow-[4px_4px_0px_0px_rgba(30,41,59,0.5)]">
                    <h3 class="text-lg font-black text-white flex items-center gap-2 border-b-4 border-slate-800 pb-4">
                        <Shield class="h-5 w-5 text-emerald-500" />
                        Failover Outage Simulator
                    </h3>

                    <div class="flex flex-col gap-3.5">
                        <button 
                            @click="triggerTtsFailover"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl border-4 border-amber-700 bg-amber-500 hover:bg-amber-400 text-amber-955 py-2.5 text-xs font-black tracking-wider uppercase transition-all active:translate-y-[2px]"
                        >
                            Trigger TTS Outage
                        </button>

                        <button 
                            @click="triggerLlmFailover"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl border-4 border-amber-700 bg-amber-500 hover:bg-amber-400 text-amber-955 py-2.5 text-xs font-black tracking-wider uppercase transition-all active:translate-y-[2px]"
                        >
                            Trigger LLM Outage
                        </button>

                        <button 
                            @click="triggerSttFailover"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl border-4 border-amber-700 bg-amber-500 hover:bg-amber-400 text-amber-955 py-2.5 text-xs font-black tracking-wider uppercase transition-all active:translate-y-[2px]"
                        >
                            Trigger STT Outage
                        </button>

                        <button 
                            @click="triggerGlobalCollapse"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl border-4 border-rose-700 bg-rose-600 hover:bg-rose-500 text-white py-2.5 text-xs font-black tracking-wider uppercase transition-all active:translate-y-[2px]"
                        >
                            Collapse Network
                        </button>

                        <button 
                            @click="resetToOptimal"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl border-4 border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-300 py-2.5 text-xs font-black tracking-wider uppercase transition-all active:translate-y-[2px]"
                        >
                            <RefreshCw class="h-4 w-4" /> Reset System to Optimal
                        </button>
                    </div>
                </div>

            </div>

        </div>

    </div>
</template>

<style scoped>
.bg-card {
    transition: background-color 0.2s, border-color 0.2s;
}
</style>
