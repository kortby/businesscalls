<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { callStore } from '@/lib/store';

const canvasRef = ref<HTMLCanvasElement | null>(null);
let animationFrameId: number | null = null;

// Saturated Duolingo aesthetic colors
const colors = ['#58cc02', '#1cb0f6', '#ffc800', '#ff4b4b'];

onMounted(() => {
    const canvas = canvasRef.value;
    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return;
    }

    const resizeCanvas = () => {
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * window.devicePixelRatio;
        canvas.height = rect.height * window.devicePixelRatio;
        ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
    };

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    let phase = 0;

    const draw = () => {
        if (!canvas || !ctx) {
            return;
        }

        const width = canvas.width / window.devicePixelRatio;
        const height = canvas.height / window.devicePixelRatio;

        ctx.clearRect(0, 0, width, height);

        const frequencyData = new Uint8Array(128);
        const amplitude = callStore.amplitude;

        if (callStore.analyserNode) {
            const bufferLength = callStore.analyserNode.frequencyBinCount;
            const dataArray = new Uint8Array(bufferLength);
            callStore.analyserNode.getByteFrequencyData(dataArray);

            const step = Math.max(1, Math.floor(bufferLength / 128));
            for (let i = 0; i < 128; i++) {
                frequencyData[i] = dataArray[i * step] || 0;
            }
        } else if (callStore.isSpeaking) {
            // Simulate speaking data if speaking but no WebRTC track is plugged in yet (e.g. simulated call)
            for (let i = 0; i < 128; i++) {
                const centerDist = 1 - Math.abs(i - 64) / 64;
                frequencyData[i] = Math.max(
                    0,
                    (Math.sin(i * 0.1 + phase) * 70 + 80) *
                        centerDist *
                        (0.5 + Math.random() * 0.5),
                );
            }
        } else {
            // Idle floating wave
            for (let i = 0; i < 128; i++) {
                frequencyData[i] = Math.sin(i * 0.05 + phase) * 15 + 15;
            }
        }

        phase += 0.08;

        // Draw multiple overlapping fluid waves
        const waveCount = 3;
        for (let w = 0; w < waveCount; w++) {
            ctx.beginPath();
            ctx.lineWidth = 4; // Thick borders (Duolingo style)
            ctx.strokeStyle = colors[w % colors.length];
            ctx.fillStyle = colors[w % colors.length] + '22'; // Saturated fill with low opacity

            const centerY = height / 2;
            const offsetMultiplier = (w + 1) * 0.5;

            ctx.moveTo(0, centerY);

            for (let i = 0; i < width; i++) {
                const binIndex = Math.floor((i / width) * 128);
                const freq = (frequencyData[binIndex] || 0) / 255.0;

                const sineValue = Math.sin(i * 0.02 + phase * offsetMultiplier + w);
                const ampFactor = callStore.isSpeaking
                    ? Math.max(amplitude, 0.15)
                    : 0.05;
                const y = centerY + sineValue * freq * (height * 0.4) * ampFactor * 2.2;

                ctx.lineTo(i, y);
            }

            ctx.lineTo(width, height);
            ctx.lineTo(0, height);
            ctx.closePath();
            ctx.fill();
            ctx.stroke();
        }

        animationFrameId = requestAnimationFrame(draw);
    };

    draw();

    onBeforeUnmount(() => {
        window.removeEventListener('resize', resizeCanvas);
        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
        }
    });
});
</script>

<template>
    <div
        class="relative w-full h-32 overflow-hidden rounded-2xl border-4 border-slate-700 bg-slate-900 shadow-inner"
    >
        <canvas ref="canvasRef" class="w-full h-full block"></canvas>
        <div
            class="absolute inset-0 pointer-events-none flex items-center justify-between px-4"
        >
            <span class="text-xs font-black uppercase tracking-wider text-slate-400"
                >WebGL Audio Spectrogram</span
            >
            <div class="flex items-center gap-1.5">
                <span
                    class="h-2.5 w-2.5 rounded-full"
                    :class="[
                        callStore.isSpeaking
                            ? 'bg-emerald-500 animate-ping'
                            : 'bg-slate-500 animate-pulse',
                    ]"
                ></span>
                <span
                    class="text-[10px] font-black text-slate-300 uppercase tracking-widest"
                >
                    {{ callStore.isSpeaking ? 'Speaking' : 'Muted' }}
                </span>
            </div>
        </div>
    </div>
</template>
