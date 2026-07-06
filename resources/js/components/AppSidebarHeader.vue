<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';
import { usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const auth = computed(() => page.props.auth);

const toggleSandboxMode = () => {
    router.post(
        '/settings/toggle-sandbox',
        {},
        {
            preserveState: false,
            onSuccess: () => {
                window.location.reload();
            },
        },
    );
};
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Sandbox Toggle -->
        <div v-if="auth?.user?.tenant" class="flex items-center space-x-2">
            <button
                @click="toggleSandboxMode"
                class="relative inline-flex cursor-pointer items-center rounded-full border-3 px-4 py-1.5 text-xs font-black tracking-wider uppercase shadow-md transition-all duration-300"
                :class="
                    auth.user.tenant.is_test_mode
                        ? 'border-amber-600 bg-amber-500 text-black shadow-[0_0_12px_rgba(245,158,11,0.4)] hover:bg-amber-400'
                        : 'border-emerald-700 bg-emerald-600 text-white shadow-[0_0_12px_rgba(16,185,129,0.3)] hover:bg-emerald-500'
                "
            >
                <span
                    class="mr-1.5 inline-block h-2.5 w-2.5 rounded-full"
                    :class="
                        auth.user.tenant.is_test_mode
                            ? 'bg-black animate-pulse'
                            : 'bg-white animate-pulse'
                    "
                ></span>
                {{
                    auth.user.tenant.is_test_mode
                        ? 'Test Mode'
                        : 'Live Mode'
                }}
            </button>
        </div>
    </header>
</template>
