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
    <div class="relative flex h-full w-full items-center justify-center overflow-hidden rounded-2xl bg-slate-50 p-4 border-4 border-slate-900 shadow-[6px_6px_0px_0px_rgba(0,0,0,1)] dark:bg-slate-800 dark:border-slate-100 dark:shadow-[6px_6px_0px_0px_rgba(255,255,255,1)]">
        <!-- Render canvas targeting WebGL Mascot -->
        <canvas
            ref="canvasRef"
            class="h-full w-full max-w-[280px] max-h-[280px] object-contain aspect-square"
        ></canvas>
        
        <!-- Indicator Badge for visual confirmation if mascot asset is not loaded yet -->
        <div class="absolute bottom-2 right-2 text-xs font-bold px-2 py-0.5 rounded border-2 border-slate-900 bg-emerald-400 text-slate-950 uppercase shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]">
            Mascot State: {{ state }}
        </div>
    </div>
</template>
