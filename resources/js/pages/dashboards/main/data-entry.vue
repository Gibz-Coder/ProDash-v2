<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { FileText, Database, Upload, Download, Edit, Save, Trash2, Plus, Search, Filter, Calendar, Clock, User, Settings, FileSpreadsheet, FolderOpen } from 'lucide-vue-next';
import WipTrendUpdateModal from '@/pages/dashboards/subs/wip-trend-update-modal.vue';
import ProcessResultModal from '@/pages/dashboards/subs/process-result-modal.vue';
import ProcessTrackoutModal from '@/pages/dashboards/subs/process-trackout-modal.vue';
import MonthlyPlanModal from '@/pages/dashboards/subs/monthly-plan-modal.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: dashboard().url,
    },
    {
        title: 'Data Entry',
        href: '/data-entry',
    },
];

// Modal state
const isWipTrendModalOpen = ref(false);
const isProcessResultModalOpen = ref(false);
const isProcessTrackoutModalOpen = ref(false);
const isMonthlyPlanModalOpen = ref(false);

// Data entry cards configuration
const dataEntryCards = [
    { title: 'WIP TREND ENTRY', icon: FileText, gradient: 'from-blue-500 to-indigo-600', bg: 'bg-gradient-to-br from-blue-50 to-indigo-50', border: 'border-blue-300', shadow: 'shadow-blue-200', action: () => isWipTrendModalOpen.value = true },
    { title: 'RESULT ENTRY', icon: Edit, gradient: 'from-emerald-500 to-green-600', bg: 'bg-gradient-to-br from-emerald-50 to-green-50', border: 'border-emerald-300', shadow: 'shadow-emerald-200', action: () => isProcessResultModalOpen.value = true },
    { title: 'TRACKOUT ENTRY', icon: Upload, gradient: 'from-purple-500 to-fuchsia-600', bg: 'bg-gradient-to-br from-purple-50 to-fuchsia-50', border: 'border-purple-300', shadow: 'shadow-purple-200', action: () => isProcessTrackoutModalOpen.value = true },
    { title: 'MONTHLY PLAN ENTRY', icon: Calendar, gradient: 'from-indigo-500 to-blue-600', bg: 'bg-gradient-to-br from-indigo-50 to-blue-50', border: 'border-indigo-300', shadow: 'shadow-indigo-200', action: () => isMonthlyPlanModalOpen.value = true },
    { title: 'DERIVE LOT ENTRY', icon: Database, gradient: 'from-cyan-500 to-teal-600', bg: 'bg-gradient-to-br from-cyan-50 to-teal-50', border: 'border-cyan-300', shadow: 'shadow-cyan-200' },
    { title: 'EMPTY 1', icon: FolderOpen, gradient: 'from-slate-400 to-slate-500', bg: 'bg-gradient-to-br from-slate-50 to-slate-100', border: 'border-slate-300', shadow: 'shadow-slate-200' },
    { title: 'EMPTY 2', icon: FolderOpen, gradient: 'from-slate-400 to-slate-500', bg: 'bg-gradient-to-br from-slate-50 to-slate-100', border: 'border-slate-300', shadow: 'shadow-slate-200' },
    { title: 'EMPTY 3', icon: FolderOpen, gradient: 'from-slate-400 to-slate-500', bg: 'bg-gradient-to-br from-slate-50 to-slate-100', border: 'border-slate-300', shadow: 'shadow-slate-200' },
    { title: 'EMPTY 4', icon: FolderOpen, gradient: 'from-slate-400 to-slate-500', bg: 'bg-gradient-to-br from-slate-50 to-slate-100', border: 'border-slate-300', shadow: 'shadow-slate-200' },
    { title: 'EMPTY 5', icon: FolderOpen, gradient: 'from-slate-400 to-slate-500', bg: 'bg-gradient-to-br from-slate-50 to-slate-100', border: 'border-slate-300', shadow: 'shadow-slate-200' },
    { title: 'EMPTY 6', icon: FolderOpen, gradient: 'from-slate-400 to-slate-500', bg: 'bg-gradient-to-br from-slate-50 to-slate-100', border: 'border-slate-300', shadow: 'shadow-slate-200' },
    { title: 'EMPTY 7', icon: FolderOpen, gradient: 'from-slate-400 to-slate-500', bg: 'bg-gradient-to-br from-slate-50 to-slate-100', border: 'border-slate-300', shadow: 'shadow-slate-200' },
    { title: 'EMPTY 8', icon: FolderOpen, gradient: 'from-slate-400 to-slate-500', bg: 'bg-gradient-to-br from-slate-50 to-slate-100', border: 'border-slate-300', shadow: 'shadow-slate-200' },
    { title: 'EMPTY 9', icon: FolderOpen, gradient: 'from-slate-400 to-slate-500', bg: 'bg-gradient-to-br from-slate-50 to-slate-100', border: 'border-slate-300', shadow: 'shadow-slate-200' },
    { title: 'EMPTY 10', icon: FolderOpen, gradient: 'from-slate-400 to-slate-500', bg: 'bg-gradient-to-br from-slate-50 to-slate-100', border: 'border-slate-300', shadow: 'shadow-slate-200' },
    { title: 'EMPTY 11', icon: FolderOpen, gradient: 'from-slate-400 to-slate-500', bg: 'bg-gradient-to-br from-slate-50 to-slate-100', border: 'border-slate-300', shadow: 'shadow-slate-200' },
];
</script>

<template>
    <Head title="Data Entry" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 items-center justify-center p-20">
            <!-- 16 Button-style Cards in 4x4 Grid - Evenly Distributed -->
            <div class="grid w-full max-w-6xl auto-rows-fr gap-16 sm:grid-cols-2 lg:grid-cols-4">
                <button
                    v-for="(card, index) in dataEntryCards"
                    :key="index"
                    @click="card.action"
                    :class="[
                        'group relative flex flex-col items-center justify-center overflow-hidden rounded-xl border-2 p-5 transition-all duration-200',
                        'hover:scale-105 hover:shadow-lg active:scale-95',
                        card.bg,
                        card.border,
                        'dark:bg-gray-800/50 dark:border-gray-700 dark:hover:bg-gray-800/70'
                    ]"
                >
                    <!-- Icon with gradient background -->
                    <div :class="[
                        'mb-2 flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br shadow-md transition-all duration-200',
                        'group-hover:scale-110 group-hover:shadow-lg',
                        card.gradient
                    ]">
                        <component
                            :is="card.icon"
                            class="h-6 w-6 text-white"
                        />
                    </div>
                    
                    <!-- Title -->
                    <span class="text-center text-xs font-semibold leading-tight text-gray-700 transition-colors duration-200 group-hover:text-gray-900 dark:text-gray-300 dark:group-hover:text-white">
                        {{ card.title }}
                    </span>
                </button>
            </div>
        </div>

        <!-- WIP Trend Update Modal -->
        <WipTrendUpdateModal 
            :open="isWipTrendModalOpen" 
            @update:open="isWipTrendModalOpen = $event" 
        />

        <!-- Process Result Modal -->
        <ProcessResultModal 
            :open="isProcessResultModalOpen" 
            @update:open="isProcessResultModalOpen = $event" 
        />

        <!-- Process Trackout Modal -->
        <ProcessTrackoutModal 
            :open="isProcessTrackoutModalOpen" 
            @update:open="isProcessTrackoutModalOpen = $event" 
        />

        <!-- Monthly Plan Modal -->
        <MonthlyPlanModal 
            :open="isMonthlyPlanModalOpen" 
            @update:open="isMonthlyPlanModalOpen = $event" 
        />
    </AppLayout>
</template>
