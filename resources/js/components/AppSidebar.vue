<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    LayoutGrid,
    Calendar,
    Clock,
    Wrench,
    Users,
    Briefcase,
    MessageSquare,
    Activity,
    Sparkles,
    Link2,
    FlaskConical,
    HelpCircle,
    Map,
    Shield,
    HeartPulse,
    ClipboardCheck,
    Settings2,
    Zap,
    Server,
    GitBranch,
    TrendingUp,
    Flame,
    CalendarCheck,
    ThumbsUp,
    Award,
    Trophy,
    CreditCard,
    FileText,
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
import { dashboard, docs } from '@/routes';
import { index as availabilitiesIndex } from '@/routes/availabilities';
import { index as bookingsIndex } from '@/routes/bookings';
import { index as employeesIndex } from '@/routes/employees';
import { index as customersIndex } from '@/routes/customers';
import { index as jobsIndex } from '@/routes/jobs';
import { index as conversationsIndex } from '@/routes/conversations';
import {
    dispatchMap as adminDispatchMap,
    callMonitor as adminCallMonitor,
    supervisorHud as adminSupervisorHud,
    statusHud as adminStatusHud,
    preflight as adminPreFlightAudit,
    diagnostics as adminDiagnostics,
    slaDiagnostics as adminSlaDiagnostics,
    health as adminHealth,
    callflow as adminCallFlow,
    saasProfit as adminSaasProfit,
    onboarding as adminOnboardingQuest,
    onboardingBoard as adminOnboardingBoard,
    streakHub as adminStreakHub,
    csatFeedback as adminCsatFeedback,
    achievements as adminAchievements,
    leaderboard as adminLeaderboard,
    billingHub as adminBillingHub,
    loyalty as adminLoyalty,
    auditLogs as adminAuditLogs,
    mascotShop as adminMascotShop,
    integrations as adminIntegrations,
    experiments as adminExperiments,
} from '@/routes/admin';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);
const isSupervisor = computed(() => !!user.value?.is_supervisor);

const sidebarNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Operations',
            icon: Briefcase,
            items: [
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
                    href: employeesIndex(),
                    icon: Wrench,
                },
                {
                    title: 'Customers',
                    href: customersIndex(),
                    icon: Users,
                },
                {
                    title: 'Customer Jobs',
                    href: jobsIndex(),
                    icon: Briefcase,
                },
                {
                    title: 'Conversations',
                    href: conversationsIndex(),
                    icon: MessageSquare,
                },
            ],
        },
    ];

    if (isSupervisor.value) {
        items.push(
            {
                title: 'Live HUDs',
                icon: Activity,
                items: [
                    {
                        title: 'Live Dispatch Map',
                        href: adminDispatchMap(),
                        icon: Map,
                    },
                    {
                        title: 'Live Call Monitor',
                        href: adminCallMonitor(),
                        icon: Activity,
                    },
                    {
                        title: 'Supervisor HUD',
                        href: adminSupervisorHud(),
                        icon: Shield,
                    },
                    {
                        title: 'Telephony Status HUD',
                        href: adminStatusHud(),
                        icon: HeartPulse,
                    },
                    {
                        title: 'IVR Call Flow',
                        href: adminCallFlow(),
                        icon: GitBranch,
                    },
                ],
            },
            {
                title: 'Insights',
                icon: TrendingUp,
                items: [
                    {
                        title: 'SaaS Profit HUD',
                        href: adminSaasProfit(),
                        icon: TrendingUp,
                    },
                    {
                        title: 'CSAT Feedback',
                        href: adminCsatFeedback(),
                        icon: ThumbsUp,
                    },
                    {
                        title: 'Leaderboard',
                        href: adminLeaderboard(),
                        icon: Trophy,
                    },
                    {
                        title: 'Achievements',
                        href: adminAchievements(),
                        icon: Award,
                    },
                    {
                        title: 'Streak Hub',
                        href: adminStreakHub(),
                        icon: CalendarCheck,
                    },
                ],
            },
            {
                title: 'Administration',
                icon: Settings2,
                items: [
                    {
                        title: 'Billing Hub',
                        href: adminBillingHub(),
                        icon: CreditCard,
                    },
                    {
                        title: 'Loyalty Panel',
                        href: adminLoyalty(),
                        icon: Users,
                    },
                    {
                        title: 'Audit Logs',
                        href: adminAuditLogs(),
                        icon: FileText,
                    },
                    {
                        title: 'Pre-Flight Audit',
                        href: adminPreFlightAudit(),
                        icon: ClipboardCheck,
                    },
                    {
                        title: 'Diagnostics Panel',
                        href: adminDiagnostics(),
                        icon: Settings2,
                    },
                    {
                        title: 'SLA Diagnostics',
                        href: adminSlaDiagnostics(),
                        icon: Zap,
                    },
                    {
                        title: 'System Health',
                        href: adminHealth(),
                        icon: Server,
                    },
                    {
                        title: 'Onboarding Quest',
                        href: adminOnboardingQuest(),
                        icon: Sparkles,
                    },
                    {
                        title: 'Onboarding Customize',
                        href: adminOnboardingBoard(),
                        icon: Flame,
                    },
                    {
                        title: 'Integrations',
                        href: adminIntegrations(),
                        icon: Link2,
                    },
                    {
                        title: 'Experiments',
                        href: adminExperiments(),
                        icon: FlaskConical,
                    },
                ],
            }
        );
    }

    return items;
});

const footerNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    if (isSupervisor.value) {
        items.push({
            title: 'Mascot Shop',
            href: adminMascotShop(),
            icon: Sparkles,
        });
    }
    items.push({
        title: 'Documentation',
        href: docs(),
        icon: HelpCircle,
    });
    return items;
});
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

        <SidebarContent class="space-y-4">
            <NavMain :items="sidebarNavItems" title="Navigation" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
