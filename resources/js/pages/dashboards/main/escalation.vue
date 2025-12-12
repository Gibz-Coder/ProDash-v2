<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, ref, onMounted, onBeforeUnmount, watch } from 'vue';
import ApexCharts from 'apexcharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: dashboard().url },
    { title: 'Machine Escalation Reporting', href: '/escalation' },
];

// Theme colors from COLOR_THEME_UPDATE.md
const colors = {
    primary: '#35B5AA',
    secondary: '#00C9FF',
    success: '#32D484',
    warning: '#FDAF22',
    danger: '#FF6757',
    info: '#FF49CD',
    teal: '#985FFD',
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
    { name: 'Equipment', value: 249, color: colors.success },
    { name: 'Technical', value: 180, color: colors.primary },
    { name: 'AI', value: 243, color: colors.teal },
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
        no: 1, week: 47, wallTime: '00:00:00', team: 'Equipment', line: 'D',
        mcType: 'GMC-G3', mcNo: 'VJ489', mcStatus: 'Run', modelId: '058104',
        lotId: 'EL99PRO', category: 'Ejecter Unit', problemPart: 'Eject Binning',
        problemDetails: 'eject binning', requestor: 'MARQUEZ, FROILAN LEVI',
        dateTime: '2025-11-18 07:53:30',
    },
    {
        no: 2, week: 47, wallTime: '00:37:52', team: 'Equipment', line: 'D',
        mcType: 'GMC-G3', mcNo: 'VJ489', mcStatus: 'Run', modelId: '058104',
        lotId: 'EL99PRO', category: 'Ejecter Unit', problemPart: 'Eject Binning',
        problemDetails: 'eject binning', requestor: 'ROLLE, JUSTIN NAVARRO',
        dateTime: '2025-11-18 07:53:30',
    },
    {
        no: 3, week: 47, wallTime: '00:06:12', team: 'AI', line: 'D',
        mcType: 'GMC-G3', mcNo: 'VJ528', mcStatus: 'Run', modelId: '058108',
        lotId: 'HLAVPTG', category: 'Inspection Result', problemPart: 'High NG Rate',
        problemDetails: 'high ng rate', requestor: 'CARRASCAL, ALMIRA ALFEO',
        dateTime: '2025-11-18 08:35:30',
    },
]);

// Computed values
const perTeamMax = computed(() => Math.max(...perTeam.value.map((t) => t.value)));
const perCategoryMax = computed(() => Math.max(...perCategory.value.map((c) => c.value)));
const machineMakerMax = computed(() => Math.max(...perMachineMaker.value.map((m) => m.value)));
const perLineMax = computed(() => Math.max(...perLine.value.map((l) => l.value)));
const totalTeamValue = computed(() => perTeam.value.reduce((sum, t) => sum + t.value, 0));

// Chart state
const isDarkMode = ref(false);
let completionChart: ApexCharts | null = null;
let monthlyChart: ApexCharts | null = null;
let weeklyChart: ApexCharts | null = null;
let dailyChart: ApexCharts | null = null;
let teamChart: ApexCharts | null = null;

// Completion Chart (Radial Bar)
const getCompletionChartOptions = () => ({
    series: [overallStatus.value.progress],
    chart: {
        type: 'radialBar',
        height: 280,
        background: 'transparent',
        sparkline: { enabled: true }
    },
    colors: [colors.primary],
    plotOptions: {
        radialBar: {
            startAngle: -135,
            endAngle: 135,
            hollow: { size: '55%', background: 'transparent' },
            track: {
                background: isDarkMode.value ? '#374151' : '#e5e7eb',
                strokeWidth: '100%',
                dropShadow: { enabled: true, top: 2, left: 0, blur: 4, opacity: 0.1 }
            },
            dataLabels: {
                name: { show: true, fontSize: '11px', fontWeight: 500, color: isDarkMode.value ? '#9ca3af' : '#6b7280', offsetY: 22 },
                value: { show: true, fontSize: '28px', fontWeight: 700, color: isDarkMode.value ? '#f3f4f6' : '#111827', offsetY: -12, formatter: (val: number) => val + '%' }
            }
        }
    },
    stroke: { lineCap: 'round' },
    labels: ['Completion'],
});

// Monthly Chart (Bar)
const getMonthlyChartOptions = () => ({
    series: [{ name: 'Reports', data: monthlyTrend.value.map(d => d.value) }],
    chart: {
        type: 'bar',
        height: 240,
        background: 'transparent',
        toolbar: { show: false },
        parentHeightOffset: 0
    },
    colors: [colors.primary],
    plotOptions: {
        bar: { columnWidth: '55%', borderRadius: 6, borderRadiusApplication: 'end', distributed: false }
    },
    dataLabels: {
        enabled: true,
        formatter: (val: number) => val,
        style: { fontSize: '11px', fontWeight: 600, colors: [isDarkMode.value ? '#e5e7eb' : '#374151'] },
        offsetY: -20
    },
    xaxis: {
        categories: monthlyTrend.value.map(d => d.month),
        labels: { style: { colors: isDarkMode.value ? '#9ca3af' : '#6b7280', fontSize: '11px', fontWeight: 500 } },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: { show: false },
    grid: { show: false, padding: { top: -10, bottom: -5, left: 0, right: 0 } },
    tooltip: { enabled: true, theme: isDarkMode.value ? 'dark' : 'light' }
});

// Weekly Chart (Bar)
const getWeeklyChartOptions = () => ({
    series: [{ name: 'Reports', data: weeklyTrend.value.map(d => d.value) }],
    chart: {
        type: 'bar',
        height: 240,
        background: 'transparent',
        toolbar: { show: false },
        parentHeightOffset: 0
    },
    colors: [colors.secondary],
    plotOptions: {
        bar: { columnWidth: '55%', borderRadius: 6, borderRadiusApplication: 'end' }
    },
    dataLabels: {
        enabled: true,
        formatter: (val: number) => val,
        style: { fontSize: '11px', fontWeight: 600, colors: [isDarkMode.value ? '#e5e7eb' : '#374151'] },
        offsetY: -20
    },
    xaxis: {
        categories: weeklyTrend.value.map(d => d.week),
        labels: { style: { colors: isDarkMode.value ? '#9ca3af' : '#6b7280', fontSize: '11px', fontWeight: 500 } },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: { show: false },
    grid: { show: false, padding: { top: -10, bottom: -5, left: 0, right: 0 } },
    tooltip: { enabled: true, theme: isDarkMode.value ? 'dark' : 'light' }
});

// Daily Chart (Area)
const getDailyChartOptions = () => ({
    series: [{ name: 'Reports', data: dailyTrend.value.map(d => d.value) }],
    chart: {
        type: 'area',
        height: 240,
        background: 'transparent',
        toolbar: { show: false },
        parentHeightOffset: 0,
        sparkline: { enabled: false }
    },
    colors: [colors.teal],
    stroke: { width: 3, curve: 'smooth' },
    fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 95, 100] }
    },
    dataLabels: { enabled: false },
    markers: { size: 4, colors: [colors.teal], strokeColors: '#fff', strokeWidth: 2, hover: { size: 6 } },
    xaxis: {
        categories: dailyTrend.value.map(d => d.date),
        labels: { style: { colors: isDarkMode.value ? '#9ca3af' : '#6b7280', fontSize: '10px', fontWeight: 500 } },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: { show: false },
    grid: { show: false, padding: { top: 0, bottom: 0, left: 5, right: 5 } },
    tooltip: { enabled: true, theme: isDarkMode.value ? 'dark' : 'light' }
});

// Team Donut Chart
const getTeamChartOptions = () => ({
    series: perTeam.value.map(t => t.value),
    chart: {
        type: 'donut',
        height: 180,
        background: 'transparent'
    },
    colors: perTeam.value.map(t => t.color),
    labels: perTeam.value.map(t => t.name),
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    name: { show: true, fontSize: '12px', fontWeight: 500, color: isDarkMode.value ? '#9ca3af' : '#6b7280' },
                    value: { show: true, fontSize: '18px', fontWeight: 700, color: isDarkMode.value ? '#f3f4f6' : '#111827' },
                    total: {
                        show: true,
                        label: 'Total',
                        fontSize: '11px',
                        fontWeight: 500,
                        color: isDarkMode.value ? '#9ca3af' : '#6b7280',
                        formatter: () => totalTeamValue.value.toString()
                    }
                }
            }
        }
    },
    dataLabels: { enabled: false },
    legend: { show: false },
    stroke: { width: 2, colors: [isDarkMode.value ? '#1f2937' : '#ffffff'] },
    tooltip: { enabled: true, theme: isDarkMode.value ? 'dark' : 'light' }
});

// Chart initialization
const initCharts = () => {
    const completionEl = document.querySelector('#completion-chart');
    const monthlyEl = document.querySelector('#monthly-chart');
    const weeklyEl = document.querySelector('#weekly-chart');
    const dailyEl = document.querySelector('#daily-chart');
    const teamEl = document.querySelector('#team-chart');

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
    if (teamEl) {
        teamChart = new ApexCharts(teamEl, getTeamChartOptions());
        teamChart.render();
    }
};

const destroyCharts = () => {
    completionChart?.destroy();
    monthlyChart?.destroy();
    weeklyChart?.destroy();
    dailyChart?.destroy();
    teamChart?.destroy();
    completionChart = monthlyChart = weeklyChart = dailyChart = teamChart = null;
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
        <div class="flex h-full flex-1 flex-col gap-5 overflow-auto bg-gray-50/50 p-5 dark:bg-gray-950">
            <!-- Header Section -->
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-1 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Live Data
                    </span>
                </div>
            </div>

            <!-- Row 1: KPI Cards -->
            <div class="grid grid-cols-12 gap-5">
                <!-- Overall Completion Status -->
                <div class="col-span-12 lg:col-span-3">
                    <div class="h-full rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Completion Status</h3>
                            <span class="rounded-md bg-purple-100 px-2 py-0.5 text-[10px] font-semibold text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">MTD</span>
                        </div>
                        <div id="completion-chart" class="flex justify-center"></div>
                        <div class="mt-3 grid grid-cols-3 gap-3">
                            <div class="rounded-xl bg-gray-50 p-3 text-center dark:bg-gray-800/50">
                                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ overallStatus.totalReport }}</div>
                                <div class="text-[10px] font-medium text-gray-500 dark:text-gray-400">Total</div>
                            </div>
                            <div class="rounded-xl bg-emerald-50 p-3 text-center dark:bg-emerald-900/20">
                                <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ overallStatus.completed }}</div>
                                <div class="text-[10px] font-medium text-emerald-600/80 dark:text-emerald-400/80">Done</div>
                            </div>
                            <div class="rounded-xl bg-amber-50 p-3 text-center dark:bg-amber-900/20">
                                <div class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ overallStatus.pending }}</div>
                                <div class="text-[10px] font-medium text-amber-600/80 dark:text-amber-400/80">Pending</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trend -->
                <div class="col-span-12 md:col-span-6 lg:col-span-3">
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
                <div class="col-span-12 md:col-span-6 lg:col-span-3">
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
                <div class="col-span-12 lg:col-span-3">
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
            <div class="grid grid-cols-12 gap-5">
                <!-- Per Team (Donut Chart) -->
                <div class="col-span-12 md:col-span-6 lg:col-span-3">
                    <div class="h-full rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Reports by Team</h3>
                        <div id="team-chart"></div>
                        <div class="mt-3 flex justify-center gap-4">
                            <div v-for="team in perTeam" :key="team.name" class="flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: team.color }"></span>
                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ team.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Per Category -->
                <div class="col-span-12 md:col-span-6 lg:col-span-3">
                    <div class="h-full rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Reports by Category</h3>
                        <div class="space-y-2 max-h-[220px] overflow-y-auto pr-1">
                            <div v-for="cat in perCategory" :key="cat.name" class="group flex items-center gap-3">
                                <span class="w-24 truncate text-xs text-gray-600 dark:text-gray-400">{{ cat.name }}</span>
                                <div class="relative h-5 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="h-full rounded-full bg-gradient-to-r from-purple-500 to-pink-500 transition-all duration-300" :style="{ width: `${(cat.value / perCategoryMax) * 100}%` }"></div>
                                </div>
                                <span class="w-8 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">{{ cat.value }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Per Machine Maker -->
                <div class="col-span-12 md:col-span-6 lg:col-span-3">
                    <div class="h-full rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">By Machine Maker</h3>
                        <div class="flex h-[180px] items-end justify-around gap-2 pt-4">
                            <div v-for="maker in perMachineMaker" :key="maker.name" class="group flex flex-col items-center">
                                <span class="mb-2 text-xs font-bold text-gray-700 opacity-0 transition-opacity group-hover:opacity-100 dark:text-gray-300">{{ maker.value }}</span>
                                <div class="relative w-10 overflow-hidden rounded-t-lg transition-all duration-300 group-hover:scale-105" :style="{ height: `${Math.max((maker.value / machineMakerMax) * 120, 8)}px`, backgroundColor: maker.color }">
                                    <div class="absolute inset-x-0 top-0 h-full bg-gradient-to-b from-white/20 to-transparent"></div>
                                </div>
                                <span class="mt-2 text-[10px] font-medium text-gray-500 dark:text-gray-400">{{ maker.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Per Line -->
                <div class="col-span-12 md:col-span-6 lg:col-span-3">
                    <div class="h-full rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">By Production Line</h3>
                        <div class="flex h-[180px] items-end justify-around gap-1 pt-4">
                            <div v-for="line in perLine" :key="line.name" class="group flex flex-col items-center">
                                <span class="mb-1 text-[9px] font-bold text-gray-700 opacity-0 transition-opacity group-hover:opacity-100 dark:text-gray-300">{{ line.value }}</span>
                                <div class="relative w-5 overflow-hidden rounded-t-md transition-all duration-300 group-hover:scale-110" :style="{ height: `${Math.max((line.value / perLineMax) * 110, 4)}px`, backgroundColor: line.color }">
                                    <div class="absolute inset-x-0 top-0 h-full bg-gradient-to-b from-white/20 to-transparent"></div>
                                </div>
                                <span class="mt-1.5 text-[10px] font-semibold text-gray-500 dark:text-gray-400">{{ line.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Escalation Report Table -->
            <div class="rounded-2xl border border-gray-200/80 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-pink-500">
                            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Escalation Reports</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Recent machine escalation entries</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                        {{ escalationReports.length }} records
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-gradient-to-r from-purple-600 to-pink-500 text-white">
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">#</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Week</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Wall Time</th>
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
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Date/Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">

                            <tr v-for="report in escalationReports" :key="report.no" class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 text-center font-medium text-gray-900 dark:text-white">{{ report.no }}</td>
                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ report.week }}</td>
                                <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">{{ report.wallTime }}</td>
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
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500 dark:text-gray-500">{{ report.dateTime }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
