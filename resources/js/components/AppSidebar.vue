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
import {
    dashboard,
    endline_delay,
    endtime,
    lot_request,
    mc_allocation,
    mems_dashboard,
    process_wip,
    qc_analysis,
    qc_ok,
    vi_technical,
} from '@/routes';
import admin from '@/routes/admin';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Settings, Shield, Users } from 'lucide-vue-next';
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
        title: 'MEMS-DB',
        href: mems_dashboard(),
        icon: '📊',
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
        href: endline_delay(),
        icon: '🔚',
    },
    {
        title: 'QC ANALYSIS',
        href: qc_analysis(),
        icon: '🔬',
    },
    {
        title: 'VI TECHNICAL',
        href: vi_technical(),
        icon: '🔍',
    },
    {
        title: 'QC OK',
        href: qc_ok(),
        icon: '✅',
    },
];

// Filter navigation items based on user role
const mainNavItems = computed(() => {
    if (!user) return allNavItems;

    const userRole = user.role?.toLowerCase();

    // QC Part role can only see ENDLINE, QC ANALYSIS, and VI TECHNICAL
    if (userRole === 'qc part' || userRole === 'qc-part') {
        return allNavItems.filter((item) =>
            ['ENDLINE', 'QC ANALYSIS', 'VI TECHNICAL', 'QC OK'].includes(
                item.title,
            ),
        );
    }

    // Hide DATA ENTRY for users with 'user' role
    if (userRole === 'user') {
        return allNavItems.filter((item) => item.title !== 'DATA ENTRY');
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

    const userRole = user.role?.toLowerCase();

    // QC Part role should not see any admin items
    if (userRole === 'qc part' || userRole === 'qc-part') {
        return [];
    }

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
