<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import * as Rive from '@rive-app/webgl';

const props = defineProps<{
    state: number; // 0 = Idle, 1 = Searching, 2 = Victory, 3 = Error
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let rInstance: Rive.Rive | null = null;
let stateTriggerInput: Rive.StateMachineInput | null = null;
let resizeObserver: ResizeObserver | null = null;

onMounted(() => {
    if (!canvasRef.value) return;

    // Load Rive WebGL instance
    rInstance = new Rive.Rive({
        src: '/assets/animations/dispatcher_mascot.riv',
        canvas: canvasRef.value,
        stateMachines: 'DispatcherStateMachine',
        autoplay: true,
        onLoad: () => {
            // Adjust vector drawing surface initially
            rInstance?.resizeDrawingSurfaceToCanvas();

            // Locate and fetch the State Machine Inputs
            const inputs = rInstance?.stateMachineInputs('DispatcherStateMachine');
            if (inputs) {
                const trigger = inputs.find(i => i.name === 'state_trigger');
                if (trigger) {
                    stateTriggerInput = trigger;
                    stateTriggerInput.value = props.state;
                }
            }
        }
    });

    // ResizeObserver ensures vector line fidelity is recalculated on retina screens
    resizeObserver = new ResizeObserver(() => {
        rInstance?.resizeDrawingSurfaceToCanvas();
    });
    resizeObserver.observe(canvasRef.value);
});

// Watch the state prop to trigger appropriate Rive transitions automatically
watch(() => props.state, (newVal) => {
    if (stateTriggerInput) {
        stateTriggerInput.value = newVal;
    }
});

onBeforeUnmount(() => {
    if (resizeObserver && canvasRef.value) {
        resizeObserver.unobserve(canvasRef.value);
    }
    if (rInstance) {
        rInstance.cleanup();
    }
});
</script>

<template>
    <div class="relative flex h-full w-full items-center justify-center overflow-hidden rounded-xl bg-accent/25 p-4 border shadow-xs">
        <!-- Render canvas targeting WebGL Mascot -->
        <canvas
            ref="canvasRef"
            class="h-full w-full max-w-[280px] max-h-[280px] object-contain aspect-square"
        ></canvas>
        
        <!-- Indicator Badge for visual confirmation if mascot asset is not loaded yet -->
        <div class="absolute bottom-2 right-2">
            <span class="inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20 uppercase tracking-wider">
                Mascot State: {{ state }}
            </span>
        </div>
    </div>
</template>
