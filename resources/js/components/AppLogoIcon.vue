<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 100 100"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Body background -->
        <path
            d="M 50 15 C 28 15, 20 30, 20 60 C 20 80, 32 85, 50 85 C 68 85, 80 80, 80 60 C 80 30, 72 15, 50 15 Z"
            fill="#10B981"
            stroke="#047857"
            stroke-width="3"
        />
        <!-- Tummy panel -->
        <path
            d="M 50 45 C 36 45, 32 55, 32 68 C 32 78, 40 82, 50 82 C 60 82, 68 78, 68 68 C 68 55, 64 45, 50 45 Z"
            fill="#D1FAE5"
        />
        <!-- Left Eye -->
        <circle
            cx="38"
            cy="42"
            r="9"
            fill="white"
            stroke="#047857"
            stroke-width="2.5"
        />
        <circle cx="39" cy="42" r="4.5" fill="#111827" />
        <circle cx="41" cy="40" r="1.8" fill="white" />
        <!-- Right Eye -->
        <circle
            cx="62"
            cy="42"
            r="9"
            fill="white"
            stroke="#047857"
            stroke-width="2.5"
        />
        <circle cx="61" cy="42" r="4.5" fill="#111827" />
        <circle cx="63" cy="40" r="1.8" fill="white" />
        <!-- Cute Beak -->
        <polygon
            points="50,48 46,54 54,54"
            fill="#F59E0B"
            stroke="#D97706"
            stroke-width="1.5"
            stroke-linejoin="round"
        />
        <!-- Headset Arch -->
        <path
            d="M 23 48 C 23 25, 77 25, 77 48"
            fill="none"
            stroke="#374151"
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
            fill="#1F2937"
            stroke="#111827"
            stroke-width="1.5"
        />
        <circle cx="19" cy="52" r="2" fill="#D1D5DB" />
        <!-- Right Earcup -->
        <rect
            x="77"
            y="44"
            width="8"
            height="16"
            rx="4"
            fill="#1F2937"
            stroke="#111827"
            stroke-width="1.5"
        />
        <circle cx="81" cy="52" r="2" fill="#D1D5DB" />
        <!-- Headset Microphone Arm -->
        <path
            d="M 21 54 Q 30 66 43 62"
            fill="none"
            stroke="#1F2937"
            stroke-width="2.5"
            stroke-linecap="round"
        />
        <circle cx="43" cy="62" r="2.5" fill="#EF4444" />
    </svg>
</template>
