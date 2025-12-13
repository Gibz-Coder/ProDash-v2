<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, ref, onMounted, onBeforeUnmount, watch } from 'vue';
import ApexCharts from 'apexcharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: dashboard().url },
    { title: 'Escalation', href: '/escalation' },
];

// Theme colors aligned with reference image
const colors = {
    primary: '#35B5AA',     // Teal for main elements
    secondary: '#4ECDC4',   // Light teal for secondary
    success: '#32D484',     // Green for success states
    warning: '#FDAF22',     // Orange for warnings
    danger: '#FF6757',      // Red for danger states
    info: '#FF49CD',        // Pink for info
    teal: '#35B5AA',        // Teal for accent
};

// Sample data - replace with actual API data
const overallStatus = ref({
    progress: 99,
    totalReport: 655,
    completed: 652,
    pending: 3,
});

const monthlyTrend = ref([
    { month: 'Sep', value: 693 },
    { month: 'Oct', value: 1048 },
    { month: 'Nov', value: 652 },
]);

const weeklyTrend = ref([
    { week: 'Wk 45', value: 265 },
    { week: 'Wk 46', value: 288 },
    { week: 'Wk 47', value: 178 },
]);

const dailyTrend = ref([
    { date: 'Jul 1', value: 5 },
    { date: 'Jul 2', value: 29 },
    { date: 'Jul 3', value: 47 },
    { date: 'Jul 4', value: 22 },
    { date: 'Jul 5', value: 25 },
    { date: 'Jul 6', value: 40 },
    { date: 'Jul 7', value: 8 },
]);

const perTeam = ref([
    { name: 'Equipment', value: 249, color: '#32D484' },  // Green
    { name: 'Technical', value: 180, color: '#35B5AA' },  // Teal
    { name: 'AI', value: 243, color: '#4ECDC4' },         // Light teal
]);

const perCategory = ref([
    { name: 'Loading Unit', value: 20 },
    { name: 'Inspection Unit', value: 52 },
    { name: 'Ejecter Unit', value: 75 },
    { name: 'Control Unit', value: 94 },
    { name: 'Accessories', value: 6 },
    { name: 'Activity', value: 60 },
    { name: 'Inspection Result', value: 401 },
    { name: 'Lot Related', value: 2 },
    { name: 'SEAIRPO', value: 1 },
    { name: 'SAFETY', value: 1 },
]);

const perMachineMaker = ref([
    { name: 'GMC-G1', value: 168, color: colors.success },
    { name: 'GMC-G3', value: 281, color: colors.warning },
    { name: 'GMC-G20', value: 107, color: colors.primary },
    { name: 'TWA', value: 85, color: colors.secondary },
    { name: 'Wintec', value: 14, color: colors.teal },
]);

const perLine = ref([
    { name: 'A', value: 153, color: colors.success },
    { name: 'B', value: 114, color: colors.warning },
    { name: 'C', value: 85, color: colors.primary },
    { name: 'D', value: 79, color: colors.secondary },
    { name: 'E', value: 71, color: colors.info },
    { name: 'F', value: 17, color: colors.teal },
    { name: 'G', value: 81, color: colors.success },
    { name: 'H', value: 28, color: '#9ca3af' },
    { name: 'I', value: 11, color: '#9ca3af' },
    { name: 'J', value: 3, color: '#9ca3af' },
    { name: 'K', value: 6, color: '#9ca3af' },
]);

const escalationReports = ref([
    {
        no: 1, week: 47, waitingTime: '00:00:00', team: 'Equipment', line: 'D',
        mcType: 'GMC-G3', mcNo: 'VJ489', mcStatus: 'Run', modelId: '058104',
        lotId: 'EL99PRO', category: 'Ejecter Unit', problemPart: 'Eject Binning',
        problemDetails: 'eject binning', requestor: 'MARQUEZ, FROILAN LEVI',
        dateTime: '2025-11-18 07:53:30',
    },
    {
        no: 2, week: 47, waitingTime: '00:37:52', team: 'Equipment', line: 'D',
        mcType: 'GMC-G3', mcNo: 'VJ489', mcStatus: 'Run', modelId: '058104',
        lotId: 'EL99PRO', category: 'Ejecter Unit', problemPart: 'Eject Binning',
        problemDetails: 'eject binning', requestor: 'ROLLE, JUSTIN NAVARRO',
        dateTime: '2025-11-18 07:53:30',
    },
    {
        no: 3, week: 47, waitingTime: '00:06:12', team: 'AI', line: 'D',
        mcType: 'GMC-G3', mcNo: 'VJ528', mcStatus: 'Run', modelId: '058108',
        lotId: 'HLAVPTG', category: 'Inspection Result', problemPart: 'High NG Rate',
        problemDetails: 'high ng rate', requestor: 'CARRASCAL, ALMIRA ALFEO',
        dateTime: '2025-11-18 08:35:30',
    },
]);

// Computed values
const perTeamMax = computed(() => Math.max(...perTeam.value.map((t) => t.value)));
const perCategoryMax = computed(() => Math.max(...perCategory.value.map((c) => c.value)));
const sortedPerCategory = computed(() => [...perCategory.value].sort((a, b) => b.value - a.value));
const machineMakerMax = computed(() => Math.max(...perMachineMaker.value.map((m) => m.value)));
const perLineMax = computed(() => Math.max(...perLine.value.map((l) => l.value)));
const totalTeamValue = computed(() => perTeam.value.reduce((sum, t) => sum + t.value, 0));

// Chart state
const isDarkMode = ref(false);
let completionChart: ApexCharts | null = null;
let monthlyChart: ApexCharts | null = null;
let weeklyChart: ApexCharts | null = null;
let dailyChart: ApexCharts | null = null;


// Completion Chart (Radial Bar)
const getCompletionChartOptions = () => ({
    series: [overallStatus.value.progress],
    chart: {
        type: 'radialBar',
        height: 270,
        background: 'transparent',
        offsetY: 0
    },
    colors: [colors.primary],
    plotOptions: {
        radialBar: {
            startAngle: 0,
            endAngle: 360,
            hollow: {
                margin: 0,
                size: '50%',
                background: 'transparent',
            },
            track: {
                background: isDarkMode.value ? '#1f2937' : '#e5e7eb',
                strokeWidth: '120%',
                margin: 5,
                dropShadow: { enabled: false }
            },
            dataLabels: {
                show: true,
                name: {
                    show: true,
                    fontSize: '14px',
                    fontWeight: 600,
                    color: colors.primary,
                    offsetY: 25,
                },
                value: {
                    show: true,
                    fontSize: '32px',
                    fontWeight: 700,
                    color: isDarkMode.value ? '#f3f4f6' : '#111827',
                    offsetY: -10,
                    formatter: (val: number) => val + '%'
                }
            }
        }
    },
    fill: {
        type: 'gradient',
        gradient: {
            shade: 'dark',
            shadeIntensity: 0.15,
            inverseColors: false,
            opacityFrom: 1,
            opacityTo: 1,
            stops: [0, 50, 65, 91]
        },
    },
    stroke: { dashArray: 4 },
    labels: ['Completion'],
});

// Monthly Chart (Bar + Line)
const getMonthlyChartOptions = () => ({
    series: [
        {
            name: 'Reports',
            type: 'column',
            data: monthlyTrend.value.map(d => d.value)
        },
        {
            name: 'Trend',
            type: 'line',
            data: monthlyTrend.value.map(d => d.value)
        }
    ],
    chart: {
        type: 'line',
        height: 230,
        background: 'transparent',
        toolbar: { show: false },
        parentHeightOffset: 0
    },
    colors: ['#35B5AA', '#4ECDC4'],
    plotOptions: {
        bar: { columnWidth: '55%', borderRadius: 6, borderRadiusApplication: 'end' }
    },
    dataLabels: {
        enabled: true,
        enabledOnSeries: [0],
        formatter: (val: number) => val,
        style: { fontSize: '11px', fontWeight: 600, colors: [isDarkMode.value ? '#e5e7eb' : '#374151'] },
        offsetY: -20
    },
    stroke: {
        width: [0, 3],
        curve: 'straight'
    },
    markers: {
        size: [0, 5],
        colors: [colors.warning],
        strokeColors: '#fff',
        strokeWidth: 2
    },
    xaxis: {
        categories: monthlyTrend.value.map(d => d.month),
        labels: { style: { colors: isDarkMode.value ? '#9ca3af' : '#6b7280', fontSize: '11px', fontWeight: 500 } },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: { show: false },
    grid: { show: false, padding: { top: -10, bottom: -5, left: 0, right: 0 } },
    tooltip: { enabled: true, theme: isDarkMode.value ? 'dark' : 'light' },
    legend: { show: false }
});

// Weekly Chart (Bar + Line)
const getWeeklyChartOptions = () => ({
    series: [
        {
            name: 'Reports',
            type: 'column',
            data: weeklyTrend.value.map(d => d.value)
        },
        {
            name: 'Trend',
            type: 'line',
            data: weeklyTrend.value.map(d => d.value)
        }
    ],
    chart: {
        type: 'line',
        height: 230,
        background: 'transparent',
        toolbar: { show: false },
        parentHeightOffset: 0
    },
    colors: ['#35B5AA', '#4ECDC4'],
    plotOptions: {
        bar: { columnWidth: '55%', borderRadius: 6, borderRadiusApplication: 'end' }
    },
    dataLabels: {
        enabled: true,
        enabledOnSeries: [0],
        formatter: (val: number) => val,
        style: { fontSize: '11px', fontWeight: 600, colors: [isDarkMode.value ? '#e5e7eb' : '#374151'] },
        offsetY: -20
    },
    stroke: {
        width: [0, 3],
        curve: 'straight'
    },
    markers: {
        size: [0, 5],
        colors: [colors.danger],
        strokeColors: '#fff',
        strokeWidth: 2
    },
    xaxis: {
        categories: weeklyTrend.value.map(d => d.week),
        labels: { style: { colors: isDarkMode.value ? '#9ca3af' : '#6b7280', fontSize: '11px', fontWeight: 500 } },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: { show: false },
    grid: { show: false, padding: { top: -10, bottom: -5, left: 0, right: 0 } },
    tooltip: { enabled: true, theme: isDarkMode.value ? 'dark' : 'light' },
    legend: { show: false }
});

// Daily Chart (Bar + Line)
const getDailyChartOptions = () => ({
    series: [
        {
            name: 'Reports',
            type: 'column',
            data: dailyTrend.value.map(d => d.value)
        },
        {
            name: 'Trend',
            type: 'line',
            data: dailyTrend.value.map(d => d.value)
        }
    ],
    chart: {
        type: 'line',
        height: 230,
        background: 'transparent',
        toolbar: { show: false },
        parentHeightOffset: 0
    },
    colors: ['#35B5AA', '#4ECDC4'],
    plotOptions: {
        bar: { columnWidth: '45%', borderRadius: 4, borderRadiusApplication: 'end' }
    },
    dataLabels: {
        enabled: true,
        enabledOnSeries: [0],
        formatter: (val: number) => val,
        style: { fontSize: '10px', fontWeight: 600, colors: [isDarkMode.value ? '#e5e7eb' : '#374151'] },
        offsetY: -15
    },
    stroke: {
        width: [0, 3],
        curve: 'straight'
    },
    markers: {
        size: [0, 4],
        colors: [colors.info],
        strokeColors: '#fff',
        strokeWidth: 2,
        hover: { size: 6 }
    },
    xaxis: {
        categories: dailyTrend.value.map(d => d.date),
        labels: { style: { colors: isDarkMode.value ? '#9ca3af' : '#6b7280', fontSize: '10px', fontWeight: 500 } },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: { show: false },
    grid: { show: false, padding: { top: -10, bottom: -5, left: 0, right: 0 } },
    tooltip: { enabled: true, theme: isDarkMode.value ? 'dark' : 'light' },
    legend: { show: false }
});



// Chart initialization
const initCharts = () => {
    const completionEl = document.querySelector('#completion-chart');
    const monthlyEl = document.querySelector('#monthly-chart');
    const weeklyEl = document.querySelector('#weekly-chart');
    const dailyEl = document.querySelector('#daily-chart');

    if (completionEl) {
        completionChart = new ApexCharts(completionEl, getCompletionChartOptions());
        completionChart.render();
    }
    if (monthlyEl) {
        monthlyChart = new ApexCharts(monthlyEl, getMonthlyChartOptions());
        monthlyChart.render();
    }
    if (weeklyEl) {
        weeklyChart = new ApexCharts(weeklyEl, getWeeklyChartOptions());
        weeklyChart.render();
    }
    if (dailyEl) {
        dailyChart = new ApexCharts(dailyEl, getDailyChartOptions());
        dailyChart.render();
    }
};

const destroyCharts = () => {
    completionChart?.destroy();
    monthlyChart?.destroy();
    weeklyChart?.destroy();
    dailyChart?.destroy();
    completionChart = monthlyChart = weeklyChart = dailyChart = null;
};

const reinitializeCharts = () => {
    destroyCharts();
    initCharts();
};

// Status badge helper
const getStatusClass = (status: string) => {
    const statusMap: Record<string, string> = {
        'Run': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        'Stop': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'Idle': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    };
    return statusMap[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
};

const getTeamClass = (team: string) => {
    const teamMap: Record<string, string> = {
        'Equipment': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        'Technical': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        'AI': 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
    };
    return teamMap[team] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
};

// Lifecycle hooks
onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
    initCharts();

    const observer = new MutationObserver(() => {
        const newDarkMode = document.documentElement.classList.contains('dark');
        if (newDarkMode !== isDarkMode.value) {
            isDarkMode.value = newDarkMode;
        }
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    (window as any).__themeObserver = observer;
});

onBeforeUnmount(() => {
    destroyCharts();
    if ((window as any).__themeObserver) {
        (window as any).__themeObserver.disconnect();
        delete (window as any).__themeObserver;
    }
});

watch(isDarkMode, reinitializeCharts);
</script>


<template>
    <Head title="Machine Escalation Reporting" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Center: Badge Page Title -->
        <template #center>
            <span class="inline-flex items-center gap-2.5 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 px-6 py-2 text-sm font-bold text-white shadow-lg">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Machine Escalation Dashboard Reporting
            </span>
        </template>

        <!-- Right: Actions -->
        <template #filters>
            <div class="flex items-center gap-3">
                <!-- Live Data Badge -->
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live Data
                </span>

                <!-- Notification Bell -->
                <button class="relative rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">3</span>
                </button>

                <!-- Export Button -->
                <button class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </template>

        <div class="flex h-full flex-1 flex-col gap-5 overflow-auto bg-gray-50/50 p-5 dark:bg-gray-950">
            <!-- Row 1: KPI Cards -->
            <div class="grid grid-cols-11 gap-5">
                <!-- Overall Completion Status -->
                <div class="col-span-11 lg:col-span-3">
                    <div class="h-full rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Completion Status</h3>
                            <span class="rounded-md bg-purple-100 px-1.5 py-0.5 text-[9px] font-semibold text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">MTD</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div id="completion-chart" class="flex-shrink-0"></div>
                            <div class="flex flex-col gap-2 flex-1">
                                <div class="rounded-lg bg-gray-50 p-2 text-center dark:bg-gray-800/50">
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ overallStatus.totalReport }}</div>
                                    <div class="text-[9px] font-medium text-gray-500 dark:text-gray-400">Total</div>
                                </div>
                                <div class="rounded-lg bg-emerald-50 p-2 text-center dark:bg-emerald-900/20">
                                    <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ overallStatus.completed }}</div>
                                    <div class="text-[9px] font-medium text-emerald-600/80 dark:text-emerald-400/80">Done</div>
                                </div>
                                <div class="rounded-lg bg-amber-50 p-2 text-center dark:bg-amber-900/20">
                                    <div class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ overallStatus.pending }}</div>
                                    <div class="text-[9px] font-medium text-amber-600/80 dark:text-amber-400/80">Pending</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trend -->
                <div class="col-span-11 md:col-span-6 lg:col-span-2">
                    <div class="h-full rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                                    <svg class="h-4 w-4 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Monthly</span>
                            </div>
                        </div>
                        <div id="monthly-chart"></div>
                    </div>
                </div>

                <!-- Weekly Trend -->
                <div class="col-span-11 md:col-span-6 lg:col-span-2">
                    <div class="h-full rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-pink-100 dark:bg-pink-900/30">
                                    <svg class="h-4 w-4 text-pink-600 dark:text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Weekly</span>
                            </div>
                        </div>
                        <div id="weekly-chart"></div>
                    </div>
                </div>

                <!-- Daily Trend -->
                <div class="col-span-11 lg:col-span-4">
                    <div class="h-full rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-900/30">
                                    <svg class="h-4 w-4 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Daily Trend</span>
                            </div>
                        </div>
                        <div id="daily-chart"></div>
                    </div>
                </div>
            </div>


            <!-- Row 2: Analytics Cards -->
            <div class="grid grid-cols-9 gap-5">
                <!-- Per Team (Horizontal Bars) -->
                <div class="col-span-9 md:col-span-4 lg:col-span-2">
                    <div class="h-full rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Reports by Team</h3>
                        <div class="space-y-3">
                            <div v-for="team in perTeam" :key="team.name" class="group">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ team.name }}</span>
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ team.value }}</span>
                                </div>
                                <div class="relative h-6 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div 
                                        class="h-full rounded-full transition-all duration-300 group-hover:scale-105" 
                                        :style="{ 
                                            width: `${(team.value / perTeamMax) * 100}%`, 
                                            backgroundColor: team.color 
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Per Category -->
                <div class="col-span-9 md:col-span-4 lg:col-span-2">
                    <div class="h-full rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Reports by Category</h3>
                        <div class="space-y-1.5 max-h-[160px] overflow-y-auto pr-1">
                            <div v-for="cat in sortedPerCategory" :key="cat.name" class="group flex items-center gap-2.5">
                                <span class="w-20 truncate text-xs text-gray-600 dark:text-gray-400">{{ cat.name }}</span>
                                <div class="relative h-4 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="h-full rounded-full bg-gradient-to-r from-purple-500 to-pink-500 transition-all duration-300" :style="{ width: `${(cat.value / perCategoryMax) * 100}%` }"></div>
                                </div>
                                <span class="w-7 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">{{ cat.value }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Per Machine Maker -->
                <div class="col-span-9 md:col-span-4 lg:col-span-2">
                    <div class="h-full rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">By Machine Maker</h3>
                        <div class="flex h-[180px] items-end justify-around gap-2 pt-2">
                            <div v-for="maker in perMachineMaker" :key="maker.name" class="group flex flex-col items-center">
                                <span class="mb-1 text-xs font-bold text-gray-700 opacity-0 transition-opacity group-hover:opacity-100 dark:text-gray-300">{{ maker.value }}</span>
                                <div class="relative w-10 overflow-hidden rounded-t-lg transition-all duration-300 group-hover:scale-105" :style="{ height: `${Math.max((maker.value / machineMakerMax) * 140, 8)}px`, backgroundColor: maker.color }">
                                    <div class="absolute inset-x-0 top-0 h-full bg-gradient-to-b from-white/20 to-transparent"></div>
                                </div>
                                <span class="mt-1 text-[10px] font-medium text-gray-500 dark:text-gray-400">{{ maker.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Per Line -->
                <div class="col-span-9 md:col-span-5 lg:col-span-3">
                    <div class="h-full rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">By Production Line</h3>
                        <div class="flex h-[180px] items-end justify-around gap-1 pt-2">
                            <div v-for="line in perLine" :key="line.name" class="group flex flex-col items-center">
                                <span class="mb-1 text-[9px] font-bold text-gray-700 opacity-0 transition-opacity group-hover:opacity-100 dark:text-gray-300">{{ line.value }}</span>
                                <div class="relative w-5 overflow-hidden rounded-t-md transition-all duration-300 group-hover:scale-110" :style="{ height: `${Math.max((line.value / perLineMax) * 140, 4)}px`, backgroundColor: line.color }">
                                    <div class="absolute inset-x-0 top-0 h-full bg-gradient-to-b from-white/20 to-transparent"></div>
                                </div>
                                <span class="mt-1 text-[10px] font-semibold text-gray-500 dark:text-gray-400">{{ line.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Escalation Report Table -->
            <div class="rounded-2xl border border-gray-200/80 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 max-h-[300px] flex flex-col">
                <div class="overflow-x-auto overflow-y-auto flex-1">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-gradient-to-r from-teal-600 to-cyan-500 text-white">
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">#</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Week</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Date/Time</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Team</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Line</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Mc Type</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Mc No</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Model ID</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Lot ID</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Category</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Problem Part</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Details</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Requestor</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Waiting Time</th>
                                <th class="px-4 py-3 text-center font-semibold whitespace-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">

                            <tr v-for="report in escalationReports" :key="report.no" class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 text-center font-medium text-gray-900 dark:text-white">{{ report.no }}</td>
                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ report.week }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500 dark:text-gray-500">{{ report.dateTime }}</td>
                                <td class="px-4 py-3">
                                    <span :class="getTeamClass(report.team)" class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium">
                                        {{ report.team }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-gray-100 text-xs font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ report.line }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ report.mcType }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ report.mcNo }}</td>
                                <td class="px-4 py-3">
                                    <span :class="getStatusClass(report.mcStatus)" class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium">
                                        {{ report.mcStatus }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">{{ report.modelId }}</td>
                                <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">{{ report.lotId }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ report.category }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ report.problemPart }}</td>
                                <td class="px-4 py-3 max-w-[150px] truncate text-gray-500 dark:text-gray-500" :title="report.problemDetails">{{ report.problemDetails }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ report.requestor }}</td>
                                <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">{{ report.waitingTime }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button class="inline-flex items-center gap-1 rounded-lg bg-teal-500 px-3 py-1.5 text-[10px] font-semibold text-white transition-colors hover:bg-teal-600">
                                        Response
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
