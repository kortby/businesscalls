<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    FolderGit2,
    LayoutGrid,
    Calendar,
    Clock,
    MessageSquare,
    Wrench,
    Users,
    Briefcase,
    HelpCircle,
    Sparkles,
    Link2,
    Activity,
    FlaskConical,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as availabilitiesIndex } from '@/routes/availabilities';
import { index as bookingsIndex } from '@/routes/bookings';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Bookings',
        href: bookingsIndex(),
        icon: Calendar,
    },
    {
        title: 'Availabilities',
        href: availabilitiesIndex(),
        icon: Clock,
    },
    {
        title: 'Employees',
        href: '/employees',
        icon: Wrench,
    },
    {
        title: 'Customers',
        href: '/customers',
        icon: Users,
    },
    {
        title: 'Customer Jobs',
        href: '/jobs',
        icon: Briefcase,
    },
    {
        title: 'Conversations',
        href: '/conversations',
        icon: MessageSquare,
    },
    {
        title: 'Live Monitor',
        href: '/admin/call-monitor',
        icon: Activity,
    },
    {
        title: 'Onboarding Quest',
        href: '/admin/onboarding',
        icon: Sparkles,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Mascot Shop',
        href: '/admin/mascot-shop',
        icon: Sparkles,
    },
    {
        title: 'Integrations',
        href: '/admin/integrations',
        icon: Link2,
    },
    {
        title: 'Experiments',
        href: '/admin/experiments',
        icon: FlaskConical,
    },
    {
        title: 'Documentation',
        href: '/docs',
        icon: HelpCircle,
    },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
