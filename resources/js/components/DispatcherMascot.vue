<script setup lang="ts">
import * as Rive from '@rive-app/webgl';
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        state: number; // 0 = Idle, 1 = Searching, 2 = Victory, 3 = Error
        isSpeaking?: boolean;
        amplitude?: number;
        skin?: string; // 'standard', 'robot', 'gold'
    }>(),
    {
        isSpeaking: false,
        amplitude: 0,
        skin: 'standard',
    },
);

const canvasRef = ref<HTMLCanvasElement | null>(null);
const hasRiveLoaded = ref(false);
let rInstance: Rive.Rive | null = null;
let stateTriggerInput: Rive.StateMachineInput | null = null;
let speakingInput: Rive.StateMachineInput | null = null;
let resizeObserver: ResizeObserver | null = null;

const cleanupRive = () => {
    if (rInstance) {
        rInstance.cleanup();
        rInstance = null;
    }
};

const initRive = async () => {
    cleanupRive();
    if (!canvasRef.value) {
        return;
    }

    const src = '/assets/animations/dispatcher_mascot.riv';

    try {
        const response = await fetch(src, { method: 'HEAD' });
        const contentType = response.headers.get('content-type') || '';

        if (!response.ok || contentType.includes('text/html')) {
            hasRiveLoaded.value = false;
            return;
        }

        // Map skin name to Rive artboard name
        let artboardName = undefined;
        if (props.skin === 'robot') {
            artboardName = 'RobotDispatcher';
        } else if (props.skin === 'gold') {
            artboardName = 'GoldenDispatcher';
        }

        rInstance = new Rive.Rive({
            src: src,
            canvas: canvasRef.value,
            artboard: artboardName,
            stateMachines: 'DispatcherStateMachine',
            autoplay: true,
            onLoad: () => {
                hasRiveLoaded.value = true;
                rInstance?.resizeDrawingSurfaceToCanvas();

                const inputs = rInstance?.stateMachineInputs(
                    'DispatcherStateMachine',
                );

                if (inputs) {
                    const trigger = inputs.find(
                        (i) => i.name === 'state_trigger',
                    );

                    if (trigger) {
                        stateTriggerInput = trigger;
                        stateTriggerInput.value = props.state;
                    }

                    const speaking = inputs.find(
                        (i) =>
                            i.name === 'is_speaking' ||
                            i.name === 'speaking' ||
                            i.name === 'active_speech' ||
                            i.name === 'talk' ||
                            i.name === 'isSpeaking',
                    );

                    if (speaking) {
                        speakingInput = speaking;
                        speakingInput.value = props.isSpeaking;
                    }
                }
            },
            onLoadError: () => {
                hasRiveLoaded.value = false;
            },
        });
    } catch (e) {
        hasRiveLoaded.value = false;
    }
};

onMounted(() => {
    initRive();
    if (canvasRef.value) {
        resizeObserver = new ResizeObserver(() => {
            rInstance?.resizeDrawingSurfaceToCanvas();
        });
        resizeObserver.observe(canvasRef.value);
    }
});

watch(
    () => props.skin,
    () => {
        initRive();
    },
);

watch(
    () => props.state,
    (newVal) => {
        if (stateTriggerInput) {
            stateTriggerInput.value = newVal;
        }
    },
);

watch(
    () => props.isSpeaking,
    (newVal) => {
        if (speakingInput) {
            speakingInput.value = newVal;
        }
    },
);

onBeforeUnmount(() => {
    if (resizeObserver && canvasRef.value) {
        resizeObserver.unobserve(canvasRef.value);
    }
    cleanupRive();
});
</script>

<template>
    <div
        class="relative flex h-full w-full items-center justify-center overflow-hidden rounded-xl border bg-accent/25 p-4 shadow-xs"
    >
        <!-- Render canvas targeting WebGL Mascot -->
        <canvas
            v-show="hasRiveLoaded"
            ref="canvasRef"
            class="aspect-square h-full max-h-[280px] w-full max-w-[280px] object-contain"
        ></canvas>

        <!-- Beautiful Fallback Animated SVG Mascot (Duolingo style) -->
        <div
            v-if="!hasRiveLoaded"
            class="relative flex h-full max-h-[220px] w-full max-w-[220px] flex-col items-center justify-center"
        >
            <!-- Visual pulse rings for Scanning/Searching state -->
            <div
                v-if="state === 1"
                class="absolute inset-0 flex items-center justify-center"
            >
                <div
                    class="absolute h-44 w-44 animate-ping rounded-full border-2 border-amber-500/20"
                    style="animation-duration: 2.5s"
                ></div>
                <div
                    class="absolute h-36 w-36 animate-ping rounded-full border-2 border-amber-500/30"
                    style="animation-duration: 1.8s"
                ></div>
            </div>

            <!-- Mascot Character SVG -->
            <svg
                viewBox="0 0 100 100"
                class="h-36 w-36 drop-shadow-[0_4px_0_rgba(0,0,0,0.15)] transition-all duration-300"
                :class="[state === 1 ? 'animate-bounce' : '']"
                style="animation-duration: 2s"
            >
                <!-- Body background (Round body, Duolingo bird or cute owl shape) -->
                <!-- Base body color, stroke and tummy based on active skin -->
                <path
                    v-if="skin === 'robot'"
                    d="M 50 15 C 28 15, 20 30, 20 60 C 20 80, 32 85, 50 85 C 68 85, 80 80, 80 60 C 80 30, 72 15, 50 15 Z"
                    fill="#64748B"
                    stroke="#334155"
                    stroke-width="3"
                />
                <path
                    v-else-if="skin === 'gold'"
                    d="M 50 15 C 28 15, 20 30, 20 60 C 20 80, 32 85, 50 85 C 68 85, 80 80, 80 60 C 80 30, 72 15, 50 15 Z"
                    fill="#F59E0B"
                    stroke="#B45309"
                    stroke-width="3"
                />
                <path
                    v-else
                    d="M 50 15 C 28 15, 20 30, 20 60 C 20 80, 32 85, 50 85 C 68 85, 80 80, 80 60 C 80 30, 72 15, 50 15 Z"
                    fill="#10B981"
                    stroke="#047857"
                    stroke-width="3"
                />

                <!-- Tummy panel -->
                <path
                    v-if="skin === 'robot'"
                    d="M 50 45 C 36 45, 32 55, 32 68 C 32 78, 40 82, 50 82 C 60 82, 68 78, 68 68 C 68 55, 64 45, 50 45 Z"
                    fill="#F1F5F9"
                />
                <path
                    v-else-if="skin === 'gold'"
                    d="M 50 45 C 36 45, 32 55, 32 68 C 32 78, 40 82, 50 82 C 60 82, 68 78, 68 68 C 68 55, 64 45, 50 45 Z"
                    fill="#FEF3C7"
                />
                <path
                    v-else
                    d="M 50 45 C 36 45, 32 55, 32 68 C 32 78, 40 82, 50 82 C 60 82, 68 78, 68 68 C 68 55, 64 45, 50 45 Z"
                    fill="#D1FAE5"
                />

                <!-- Golden Crown (only for gold skin) -->
                <path
                    v-if="skin === 'gold'"
                    d="M 38 12 L 44 20 L 50 12 L 56 20 L 62 12 L 59 24 L 41 24 Z"
                    fill="#FBBF24"
                    stroke="#D97706"
                    stroke-width="1.5"
                />

                <!-- Eyes & Face Features based on state -->
                <!-- State 0: Idle (Friendly wide open eyes) -->
                <g v-if="state === 0">
                    <!-- Left Eye -->
                    <g
                        :style="{
                            transform: `scale(${1 - amplitude * 0.15})`,
                            transformOrigin: '38px 42px',
                        }"
                    >
                        <circle
                            cx="38"
                            cy="42"
                            r="9"
                            fill="white"
                            :stroke="
                                skin === 'robot'
                                    ? '#334155'
                                    : skin === 'gold'
                                      ? '#B45309'
                                      : '#047857'
                            "
                            stroke-width="2.5"
                        />
                        <circle
                            cx="39"
                            cy="42"
                            r="4.5"
                            :fill="skin === 'robot' ? '#06B6D4' : '#111827'"
                        />
                        <circle cx="41" cy="40" r="1.8" fill="white" />
                    </g>

                    <!-- Right Eye -->
                    <g
                        :style="{
                            transform: `scale(${1 - amplitude * 0.15})`,
                            transformOrigin: '62px 42px',
                        }"
                    >
                        <circle
                            cx="62"
                            cy="42"
                            r="9"
                            fill="white"
                            :stroke="
                                skin === 'robot'
                                    ? '#334155'
                                    : skin === 'gold'
                                      ? '#B45309'
                                      : '#047857'
                            "
                            stroke-width="2.5"
                        />
                        <circle
                            cx="61"
                            cy="42"
                            r="4.5"
                            :fill="skin === 'robot' ? '#06B6D4' : '#111827'"
                        />
                        <circle cx="63" cy="40" r="1.8" fill="white" />
                    </g>

                    <!-- Speaking Beak/Mouth -->
                    <ellipse
                        v-if="isSpeaking"
                        cx="50"
                        cy="51"
                        rx="4.5"
                        ry="6.5"
                        fill="#EF4444"
                        stroke="#D97706"
                        stroke-width="1.5"
                        :style="{
                            transform: `scaleY(${1 + amplitude * 1.5})`,
                            transformOrigin: '50px 51px',
                        }"
                        class="transition-transform duration-75 ease-out"
                    />
                    <!-- Cute Beak/Smile -->
                    <polygon
                        v-else
                        points="50,48 46,54 54,54"
                        fill="#F59E0B"
                        stroke="#D97706"
                        stroke-width="1.5"
                        stroke-linejoin="round"
                    />
                </g>

                <!-- State 1: Scanning -->
                <g v-if="state === 1">
                    <!-- Tech Glasses/Visor background -->
                    <rect
                        x="25"
                        y="34"
                        width="50"
                        height="16"
                        rx="8"
                        :fill="skin === 'robot' ? '#06B6D4' : '#F59E0B'"
                        :stroke="skin === 'robot' ? '#0891B2' : '#D97706'"
                        stroke-width="2.5"
                    />

                    <!-- Scanning Eye Indicators -->
                    <g class="animate-pulse">
                        <circle cx="38" cy="42" r="5" fill="#FFFBEB" />
                        <circle
                            cx="36"
                            cy="42"
                            r="2.5"
                            :fill="skin === 'robot' ? '#0891B2' : '#D97706'"
                            class="animate-bounce"
                        />

                        <circle cx="62" cy="42" r="5" fill="#FFFBEB" />
                        <circle
                            cx="60"
                            cy="42"
                            r="2.5"
                            :fill="skin === 'robot' ? '#0891B2' : '#D97706'"
                            class="animate-bounce"
                        />
                    </g>

                    <!-- Smiling Beak -->
                    <polygon points="50,54 47,59 53,59" fill="#111827" />
                </g>

                <!-- State 2: Victory -->
                <g v-if="state === 2">
                    <!-- Curved happy eyes -->
                    <path
                        d="M 30 44 Q 38 34 46 44"
                        fill="none"
                        stroke="#111827"
                        stroke-width="4.5"
                        stroke-linecap="round"
                    />
                    <path
                        d="M 54 44 Q 62 34 70 44"
                        fill="none"
                        stroke="#111827"
                        stroke-width="4.5"
                        stroke-linecap="round"
                    />

                    <!-- Rosy Blushing Cheeks -->
                    <circle
                        cx="28"
                        cy="52"
                        r="4.5"
                        fill="#F87171"
                        opacity="0.6"
                    />
                    <circle
                        cx="72"
                        cy="52"
                        r="4.5"
                        fill="#F87171"
                        opacity="0.6"
                    />

                    <!-- Wide open happy mouth -->
                    <path
                        d="M 45 54 Q 50 64 55 54 Z"
                        fill="#EF4444"
                        stroke="#111827"
                        stroke-width="2"
                    />
                    <polygon
                        points="50,52 46,55 54,55"
                        fill="#F59E0B"
                        stroke-width="0.5"
                    />
                </g>

                <!-- State 3: Error/Conflict -->
                <g v-if="state === 3">
                    <!-- Left Eye Cross -->
                    <line
                        x1="33"
                        y1="37"
                        x2="43"
                        y2="47"
                        stroke="#111827"
                        stroke-width="4"
                        stroke-linecap="round"
                    />
                    <line
                        x1="43"
                        y1="37"
                        x2="33"
                        y2="47"
                        stroke="#111827"
                        stroke-width="4"
                        stroke-linecap="round"
                    />

                    <!-- Right Eye Cross -->
                    <line
                        x1="57"
                        y1="37"
                        x2="67"
                        y2="47"
                        stroke="#111827"
                        stroke-width="4"
                        stroke-linecap="round"
                    />
                    <line
                        x1="67"
                        y1="37"
                        x2="57"
                        y2="47"
                        stroke="#111827"
                        stroke-width="4"
                        stroke-linecap="round"
                    />

                    <!-- Sweat Drop on forehead -->
                    <path
                        d="M 50 25 C 47 29, 45 32, 47 35 C 49 37, 51 37, 53 35 C 55 32, 53 29, 50 25 Z"
                        fill="#3B82F6"
                        class="animate-bounce"
                    />

                    <!-- Sad Beak -->
                    <polygon
                        points="50,56 46,50 54,50"
                        fill="#F59E0B"
                        stroke="#D97706"
                        stroke-width="1.5"
                        stroke-linejoin="round"
                    />
                </g>

                <!-- Headset overlay -->
                <g>
                    <!-- Headset Arch -->
                    <path
                        d="M 23 48 C 23 25, 77 25, 77 48"
                        fill="none"
                        :stroke="skin === 'robot' ? '#475569' : '#374151'"
                        stroke-width="5"
                        stroke-linecap="round"
                    />

                    <!-- Left Earcup -->
                    <rect
                        x="15"
                        y="44"
                        width="8"
                        height="16"
                        rx="4"
                        :fill="skin === 'robot' ? '#0F172A' : '#1F2937'"
                        stroke="#111827"
                        stroke-width="1.5"
                    />
                    <circle
                        cx="19"
                        cy="52"
                        r="2"
                        :fill="skin === 'robot' ? '#22D3EE' : '#D1D5DB'"
                    />

                    <!-- Right Earcup -->
                    <rect
                        x="77"
                        y="44"
                        width="8"
                        height="16"
                        rx="4"
                        :fill="skin === 'robot' ? '#0F172A' : '#1F2937'"
                        stroke="#111827"
                        stroke-width="1.5"
                    />
                    <circle
                        cx="81"
                        cy="52"
                        r="2"
                        :fill="skin === 'robot' ? '#22D3EE' : '#D1D5DB'"
                    />

                    <!-- Headset Microphone Arm -->
                    <path
                        d="M 21 54 Q 30 66 43 62"
                        fill="none"
                        :stroke="skin === 'robot' ? '#0F172A' : '#1F2937'"
                        stroke-width="2.5"
                        stroke-linecap="round"
                    />
                    <circle
                        cx="43"
                        cy="62"
                        :r="isSpeaking ? '4.5' : '2.5'"
                        fill="#EF4444"
                        :class="[isSpeaking ? 'animate-ping' : 'animate-pulse']"
                    />
                </g>
            </svg>

            <!-- Confetti for State 2 (Victory) -->
            <div
                v-if="state === 2"
                class="pointer-events-none absolute inset-0"
            >
                <div
                    class="absolute top-8 left-6 h-2 w-2 animate-bounce rounded-full bg-yellow-400"
                ></div>
                <div
                    class="absolute top-6 right-10 h-1.5 w-1.5 rotate-45 animate-ping bg-rose-400"
                ></div>
                <div
                    class="absolute bottom-8 left-10 h-1 w-2 rotate-12 animate-bounce bg-blue-400"
                ></div>
                <div
                    class="absolute right-6 bottom-10 h-1.5 w-1.5 animate-ping rounded-full bg-emerald-400"
                ></div>
            </div>

            <!-- Mascot status/action text -->
            <p
                class="mt-2 text-xs font-black tracking-wider text-muted-foreground uppercase"
            >
                <span v-if="state === 0" class="text-emerald-500"
                    >Mascot: Idle & Ready</span
                >
                <span
                    v-else-if="state === 1"
                    class="animate-pulse text-amber-500"
                    >Mascot: Scanning Lines...</span
                >
                <span
                    v-else-if="state === 2"
                    class="text-emerald-600 dark:text-emerald-400"
                    >Mascot: Booking Confirmed! 🎉</span
                >
                <span v-else-if="state === 3" class="text-rose-500"
                    >Mascot: Conflict Alert! ⚠️</span
                >
            </p>
        </div>

        <!-- Indicator Badge -->
        <div class="absolute right-2 bottom-2">
            <span
                class="inline-flex items-center rounded-md border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold tracking-wider text-emerald-600 uppercase dark:text-emerald-400"
            >
                Mascot: {{ skin }} ({{ state }})
            </span>
        </div>
    </div>
</template>

<style scoped>
@keyframes float-slow {
    0%,
    100% {
        transform: translateY(0px) scale(1);
    }
    50% {
        transform: translateY(-15px) scale(1.05);
    }
}
@keyframes float-delayed {
    0%,
    100% {
        transform: translateY(0px) scale(1.05);
    }
    50% {
        transform: translateY(15px) scale(1);
    }
}
.animate-float-slow {
    animation: float-slow 8s ease-in-out infinite;
}
.animate-float-delayed {
    animation: float-delayed 10s ease-in-out infinite;
}
.animate-spin-slow {
    animation: spin 8s linear infinite;
}
.animate-pulse-slow {
    animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
