<script setup lang="ts">
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
import { dashboard, lot_request, dashboard_2, dashboard_3, dashboard_5, dashboard_7, data_entry, endline, endtime, escalation, process_wip, mc_allocation } from '@/routes';
import admin from '@/routes/admin';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Home, BarChart3, PieChart, LineChart, TrendingUp, Activity, Wrench, Gauge, Clock, Settings, Users, Shield } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

// Get the current user from Inertia page props
const page = usePage();
const user = page.props.auth.user;
const permissions = page.props.auth.permissions || [];

const allNavItems: NavItem[] = [
    {
        title: 'Home',
        href: dashboard(),
        icon: '🏠',
    },
    {
        title: 'ENDTIME',
        href: endtime(),
        icon: '⌛',
    },
    {
        title: 'LOT REQUEST',
        href: lot_request(),
        icon: '🛒',
    },
    {
        title: 'PROCESS WIP',
        href: process_wip(),
        icon: '📜',
    },
    {
        title: 'MC ALLOCATION',
        href: mc_allocation(),
        icon: '🏪',
    },
    {
        title: 'ENDLINE',
        href: endline(),
        icon: '🔚',
    },
    {
        title: 'ESCALATION',
        href: escalation(),
        icon: '🛠️',
    },
    {
        title: 'PROCESS KPI',
        href: dashboard_5(),
        icon: '📊',
    },
    {
        title: 'LIPAS',
        href: dashboard_7(),
        icon: '💢',
    },
    {
        title: 'DATA ENTRY',
        href: data_entry(),
        icon: '📝',
    },
];

// Filter navigation items based on user role
const mainNavItems = computed(() => {
    if (!user) return allNavItems;
    
    // Hide DATA ENTRY for users with 'user' role
    const userRole = user.role?.toLowerCase();
    if (userRole === 'user') {
        return allNavItems.filter(item => item.title !== 'DATA ENTRY');
    }
    
    return allNavItems;
});

// Helper function to check if user has a permission
const hasPermission = (permission: string): boolean => {
    return Array.isArray(permissions) && permissions.includes(permission);
};

// Filter admin navigation items based on user permissions
const footerNavItems = computed(() => {
    if (!user) return [];
    
    const items: NavItem[] = [];
    
    // User Management - requires "Employees Manage" permission
    if (hasPermission('Employees Manage')) {
        items.push({
            title: 'User Management',
            href: admin.userManagement.url(),
            icon: Users,
        });
    }
    
    // Role Management - requires "Roles Manage" permission
    if (hasPermission('Roles Manage')) {
        items.push({
            title: 'Role Management',
            href: admin.roleManagement.url(),
            icon: Shield,
        });
    }
    
    // System Settings - requires "Settings Manage" permission
    if (hasPermission('Settings Manage')) {
        items.push({
            title: 'System Settings',
            href: admin.systemSettings.url(),
            icon: Settings,
        });
    }
    
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

        <SidebarContent class="px-2">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavMain v-if="footerNavItems.length > 0" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
