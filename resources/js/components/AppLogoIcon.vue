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
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Phone receiver path -->
        <path fill="currentColor" d="M21 15.46a1 1 0 0 1-.55-.17 17.06 17.06 0 0 0-4.73-2.18 1 1 0 0 0-1 .26l-2.2 2.2a15 15 0 0 1-6.59-6.59l2.2-2.2a1 1 0 0 0 .26-1 17.06 17.06 0 0 0-2.18-4.73 1 1 0 0 0-1.17-.38l-3 1A1 1 0 0 0 2 2.7c0 10.64 8.66 19.3 19.3 19.3a1 1 0 0 0 .73-.3l1-3a1 1 0 0 0-.37-1.17l-.06-.07z" />
        <!-- Broadcast wave arcs -->
        <path fill="currentColor" d="M12 2a8 8 0 0 1 8 8 1 1 0 0 1-2 0 6 6 0 0 0-6-6 1 1 0 0 1 0-2z" />
        <path fill="currentColor" d="M12 5a5 5 0 0 1 5 5 1 1 0 0 1-2 0 3 3 0 0 0-3-3 1 1 0 0 1 0-2z" />
        <circle cx="12" cy="10" r="1.5" fill="currentColor" />
    </svg>
</template>
