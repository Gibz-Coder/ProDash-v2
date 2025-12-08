<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="flex flex-col overflow-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" class="flex-shrink-0">
                <template #filters>
                    <slot name="filters" />
                </template>
            </AppSidebarHeader>
            <div class="flex-1 overflow-y-auto overflow-x-hidden">
                <slot />
            </div>
        </AppContent>
    </AppShell>
</template>
