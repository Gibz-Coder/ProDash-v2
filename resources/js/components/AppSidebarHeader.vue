<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import ThemeSelector from '@/components/ThemeSelector.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="relative flex min-h-16 shrink-0 items-center justify-between gap-4 border-b border-sidebar-border/70 bg-background px-6 py-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:min-h-14 md:px-4"
    >
        <div class="flex items-center gap-4 flex-shrink-0">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Center slot for page title -->
        <div class="flex-1 flex items-center justify-center px-4 min-w-0">
            <slot name="center" />
        </div>
        
        <div class="flex items-center justify-end gap-3 flex-shrink-0">
            <slot name="filters" />
            <div class="mx-2 h-6 w-px bg-border" />
            <ThemeSelector />
        </div>
    </header>
</template>
