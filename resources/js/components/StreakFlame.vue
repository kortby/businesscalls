<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import * as Rive from '@rive-app/webgl';

const props = defineProps<{
    streak: number;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
const hasRiveLoaded = ref(false);
let rInstance: Rive.Rive | null = null;
let activeInput: Rive.StateMachineInput | null = null;
let resizeObserver: ResizeObserver | null = null;

onMounted(async () => {
    if (!canvasRef.value) return;

    const src = '/assets/animations/streak_flame.riv';
    try {
        // Perform a quick HEAD check to see if the file exists and is not an HTML error fallback page
        const response = await fetch(src, { method: 'HEAD' });
        const contentType = response.headers.get('content-type') || '';

        if (!response.ok || contentType.includes('text/html')) {
            hasRiveLoaded.value = false;
            LogWarning(
                'Streak flame Rive asset not found. Using custom SVG fallback.',
            );
            return;
        }

        // Load Rive WebGL instance
        rInstance = new Rive.Rive({
            src: src,
            canvas: canvasRef.value,
            stateMachines: 'StreakStateMachine',
            autoplay: true,
            onLoad: () => {
                hasRiveLoaded.value = true;
                rInstance?.resizeDrawingSurfaceToCanvas();

                const inputs =
                    rInstance?.stateMachineInputs('StreakStateMachine');
                if (inputs) {
                    const active = inputs.find(
                        (i) =>
                            i.name === 'active' ||
                            i.name === 'state_trigger' ||
                            i.name === 'streak_count',
                    );
                    if (active) {
                        activeInput = active;
                        activeInput.value = props.streak;
                    }
                }
            },
            onLoadError: () => {
                hasRiveLoaded.value = false;
                LogWarning(
                    'Streak flame Rive asset not found. Using custom SVG fallback.',
                );
            },
        });

        resizeObserver = new ResizeObserver(() => {
            rInstance?.resizeDrawingSurfaceToCanvas();
        });
        resizeObserver.observe(canvasRef.value);
    } catch (e) {
        hasRiveLoaded.value = false;
        LogWarning(
            'Streak flame Rive asset check failed. Using custom SVG fallback.',
        );
    }
});

watch(
    () => props.streak,
    (newVal) => {
        if (activeInput) {
            activeInput.value = newVal;
        }
    },
);

onBeforeUnmount(() => {
    if (resizeObserver && canvasRef.value) {
        resizeObserver.unobserve(canvasRef.value);
    }
    if (rInstance) {
        rInstance.cleanup();
    }
});

// Avoid console error on missing asset logs in fallback mode
const LogWarning = (msg: string) => {
    console.warn(msg);
};
</script>

<template>
    <div class="relative flex h-16 w-16 items-center justify-center">
        <!-- Rive Canvas -->
        <canvas
            v-show="hasRiveLoaded"
            ref="canvasRef"
            class="aspect-square h-full w-full object-contain"
        ></canvas>

        <!-- Playful Fallback Glowing SVG Flame (Duolingo Style) -->
        <div
            v-if="!hasRiveLoaded"
            class="relative flex items-center justify-center"
        >
            <!-- Pulsing outer glow for active streak -->
            <div
                v-if="streak > 0"
                class="absolute h-14 w-14 animate-pulse rounded-full bg-amber-500/20 blur-md"
            ></div>

            <!-- Animated Flame Vector -->
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                class="h-12 w-12 drop-shadow-[0_3px_0_rgba(0,0,0,0.15)] transition-all duration-300"
                :class="[
                    streak > 0
                        ? 'scale-110 animate-bounce fill-amber-500 text-amber-500'
                        : 'fill-slate-300 text-slate-300 dark:fill-slate-700 dark:text-slate-700',
                ]"
                style="animation-duration: 2s"
            >
                <path
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linejoin="round"
                    d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 3z"
                />
                <!-- Inner Flame Spark -->
                <path
                    v-if="streak > 0"
                    fill="#FBBF24"
                    d="M12 18.5a3.5 3.5 0 0 0 3.5-3.5c0-1.925-1.05-3.5-2.5-4.5-.5.5-.5 1-.5 1.5 0 2.21-1.79 4-4 4a3.5 3.5 0 0 0 3.5 2.5z"
                />
            </svg>
        </div>
    </div>
</template>
