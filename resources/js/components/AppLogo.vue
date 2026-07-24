<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div class="flex items-center gap-2.5">
        <AppLogoIcon class="size-10 shrink-0 transition-transform duration-300 group-hover:scale-105" />
        <div class="grid flex-1 text-left">
            <span class="truncate text-base font-bold leading-tight tracking-tight"
                >Just<span class="text-emerald-500 dark:text-emerald-400">Mascot</span></span
            >
        </div>
    </div>
</template>
