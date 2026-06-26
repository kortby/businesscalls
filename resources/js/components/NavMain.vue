<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { Collapsible, CollapsibleTrigger, CollapsibleContent } from '@/components/ui/collapsible';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

withDefaults(
    defineProps<{
        items: NavItem[];
        title?: string;
    }>(),
    {
        title: 'Platform',
    }
);

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{ title }}</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <!-- Collapsible Group -->
                <SidebarMenuItem v-if="item.items && item.items.length > 0">
                    <Collapsible
                        as-child
                        class="group/collapsible"
                        :default-open="item.items.some(sub => isCurrentUrl(sub.href || ''))"
                    >
                        <div>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton :tooltip="item.title">
                                    <component :is="item.icon" v-if="item.icon" />
                                    <span>{{ item.title }}</span>
                                    <ChevronRight class="ml-auto size-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <SidebarMenuSub>
                                    <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                                        <SidebarMenuSubButton as-child :is-active="isCurrentUrl(subItem.href || '')">
                                            <Link :href="subItem.href || ''">
                                                <component :is="subItem.icon" v-if="subItem.icon" class="size-4 mr-2" />
                                                <span>{{ subItem.title }}</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </div>
                    </Collapsible>
                </SidebarMenuItem>

                <!-- Regular Link -->
                <SidebarMenuItem v-else>
                    <SidebarMenuButton
                        as-child
                        :is-active="isCurrentUrl(item.href || '')"
                        :tooltip="item.title"
                    >
                        <Link :href="item.href || ''">
                            <component :is="item.icon" v-if="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
