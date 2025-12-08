<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { BarChart, PieChart, LineChart } from 'echarts/charts';
import {
    GridComponent,
    LegendComponent,
    TitleComponent,
    ToolboxComponent,
    TooltipComponent,
    DataZoomComponent,
} from 'echarts/components';
import * as echarts from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

echarts.use([
    TitleComponent,
    TooltipComponent,
    GridComponent,
    LegendComponent,
    ToolboxComponent,
    DataZoomComponent,
    BarChart,
    PieChart,
    LineChart,
    CanvasRenderer,
]);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: dashboard().url,
    },
    {
        title: 'Endline Analytics',
        href: '/endline',
    },
];

const isDarkMode = ref(false);
const selectedLocation = ref('ALL');

type LocationKey = 'QC INSPECTION' | 'FOR QC ANALYSIS' | 'DEFECT' | 'TO DO';

// Sample data for Endline Analytics
const endlineData = ref({
    lastUpdate: new Date().toLocaleString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }),
    locations: ['ALL', 'QC INSPECTION', 'FOR QC ANALYSIS', 'DEFECT', 'TO DO'],
    stats: {
        totalLotCount: 1245,
        totalLotPercentage: 100,
        wipQty: 45820,
        defectCount: 156,
        toDoCount: 89,
    },
    lotCountByStatus: [
        { name: 'IN PROCESS', value: 523, color: '#3b82f6' },
        { name: 'FOR MACHINE', value: 312, color: '#985ffd' },
        { name: 'QC INSPECTION', value: 189, color: '#10b981' },
        { name: 'TECHNICAL', value: 98, color: '#f59e0b' },
        { name: 'PENDING', value: 67, color: '#ef4444' },
        { name: 'OFFLINE', value: 56, color: '#6b7280' },
    ],
    wipQtyByStatus: [
        { status: 'IN PROCESS', qty: 15820 },
        { status: 'FOR MACHINE', qty: 11240 },
        { status: 'QC INSPECTION', qty: 8950 },
        { status: 'TECHNICAL', qty: 4320 },
        { status: 'PENDING', qty: 3180 },
        { status: 'OFFLINE', qty: 2310 },
    ],
    lotCountByLocation: {
        'QC INSPECTION': [
            { name: 'IN PROCESS', value: 89 },
            { name: 'WAITING FOR QC', value: 56 },
            { name: 'QC CHECKING', value: 44 },
        ],
        'FOR QC ANALYSIS': [
            { name: 'PENDING', value: 67 },
            { name: 'IN REVIEW', value: 45 },
            { name: 'APPROVED', value: 23 },
        ],
        'DEFECT': [
            { name: 'MINOR', value: 89 },
            { name: 'MAJOR', value: 45 },
            { name: 'CRITICAL', value: 22 },
        ],
        'TO DO': [
            { name: 'SCHEDULED', value: 45 },
            { name: 'URGENT', value: 28 },
            { name: 'BACKLOG', value: 16 },
        ],
    } as Record<LocationKey, { name: string; value: number }[]>,
    defectAnalysis: [
        { name: 'SM', count: 89, qty: 3420 },
        { name: 'FOR MACHINE', count: 67, qty: 2890 },
        { name: 'QC INSPECTION', count: 45, qty: 1980 },
        { name: 'TECHNICAL', count: 34, qty: 1560 },
        { name: 'PENDING', count: 23, qty: 980 },
        { name: 'OFFLINE', count: 12, qty: 450 },
    ],
    toDoAnalysis: [
        { name: 'FOR MACHINE', count: 34, qty: 1890 },
        { name: 'FOR REPAIR', count: 28, qty: 1450 },
        { name: 'TOP REPAIR', count: 15, qty: 890 },
        { name: 'FOR REWORK', count: 12, qty: 560 },
    ],
});

// Chart refs
const lotCountChartRef = ref<HTMLElement | null>(null);
const wipQtyChartRef = ref<HTMLElement | null>(null);
const donutChartRef = ref<HTMLElement | null>(null);
const defectChartRef = ref<HTMLElement | null>(null);
const toDoChartRef = ref<HTMLElement | null>(null);

let lotCountChart: echarts.ECharts | null = null;
let wipQtyChart: echarts.ECharts | null = null;
let donutChart: echarts.ECharts | null = null;
let defectChart: echarts.ECharts | null = null;
let toDoChart: echarts.ECharts | null = null;
let resizeObserver: ResizeObserver | null = null;

const formatNumber = (value: number): string => {
    return value.toLocaleString('en-US');
};

const initCharts = () => {
    const chartColor = isDarkMode.value ? '#e5e7eb' : '#374151';
    const gridColor = 'rgba(142, 156, 173, 0.1)';
    
    // Lot Count by Status (Horizontal Bar Chart)
    if (lotCountChartRef.value) {
        if (lotCountChart) lotCountChart.dispose();
        lotCountChart = echarts.init(
            lotCountChartRef.value,
            isDarkMode.value ? 'dark' : undefined,
        );

        const option = {
            backgroundColor: 'transparent',
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
            },
            grid: {
                left: '15%',
                right: '10%',
                top: '5%',
                bottom: '5%',
            },
            xAxis: {
                type: 'value',
                splitLine: { lineStyle: { color: gridColor } },
            },
            yAxis: {
                type: 'category',
                data: endlineData.value.lotCountByStatus.map((item) => item.name),
                axisLabel: { color: chartColor, fontSize: 11 },
            },
            series: [
                {
                    type: 'bar',
                    data: endlineData.value.lotCountByStatus.map((item) => ({
                        value: item.value,
                        itemStyle: { color: item.color },
                    })),
                    label: {
                        show: true,
                        position: 'right',
                        formatter: '{c}',
                        color: chartColor,
                    },
                    barWidth: '60%',
                },
            ],
        };

        lotCountChart.setOption(option);
    }

    // WIP Qty by Status (Horizontal Bar Chart with Orange color)
    if (wipQtyChartRef.value) {
        if (wipQtyChart) wipQtyChart.dispose();
        wipQtyChart = echarts.init(
            wipQtyChartRef.value,
            isDarkMode.value ? 'dark' : undefined,
        );

        const option = {
            backgroundColor: 'transparent',
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                formatter: (params: any) => {
                    const item = params[0];
                    return `${item.name}<br/>${item.marker} ${formatNumber(item.value)} Units`;
                },
            },
            grid: {
                left: '15%',
                right: '10%',
                top: '5%',
                bottom: '5%',
            },
            xAxis: {
                type: 'value',
                splitLine: { lineStyle: { color: gridColor } },
                axisLabel: {
                    formatter: (value: number) => formatNumber(value),
                },
            },
            yAxis: {
                type: 'category',
                data: endlineData.value.wipQtyByStatus.map((item) => item.status),
                axisLabel: { color: chartColor, fontSize: 11 },
            },
            series: [
                {
                    type: 'bar',
                    data: endlineData.value.wipQtyByStatus.map((item) => item.qty),
                    itemStyle: { color: '#fa8128' },
                    label: {
                        show: true,
                        position: 'right',
                        formatter: (params: any) => formatNumber(params.value),
                        color: chartColor,
                    },
                    barWidth: '60%',
                },
            ],
        };

        wipQtyChart.setOption(option);
    }

    // Donut Chart (Status Distribution)
    if (donutChartRef.value) {
        if (donutChart) donutChart.dispose();
        donutChart = echarts.init(
            donutChartRef.value,
            isDarkMode.value ? 'dark' : undefined,
        );

        let chartData: { name: string; value: number }[] = endlineData.value.lotCountByStatus.slice(0, 3);
        if (selectedLocation.value !== 'ALL' && selectedLocation.value in endlineData.value.lotCountByLocation) {
            chartData = endlineData.value.lotCountByLocation[selectedLocation.value as LocationKey];
        }

        const total = chartData.reduce((sum, item) => sum + item.value, 0);
        const colors = ['#3b82f6', '#985ffd', '#10b981', '#f59e0b', '#ef4444'];

        const option = {
            backgroundColor: 'transparent',
            tooltip: {
                trigger: 'item',
                formatter: '{b}: {c} ({d}%)',
            },
            legend: {
                show: false,
            },
            title: {
                text: `${total}%`,
                left: 'center',
                top: 'center',
                textStyle: {
                    fontSize: 28,
                    fontWeight: 'bold',
                    color: chartColor,
                },
            },
            series: [
                {
                    type: 'pie',
                    radius: ['60%', '85%'],
                    avoidLabelOverlap: false,
                    label: { show: false },
                    emphasis: {
                        label: {
                            show: true,
                            fontSize: 14,
                            fontWeight: 'bold',
                        },
                    },
                    labelLine: { show: false },
                    data: chartData.map((item, index) => ({
                        value: item.value,
                        name: item.name,
                        itemStyle: { color: colors[index % colors.length] },
                    })),
                },
            ],
        };

        donutChart.setOption(option);
    }

    // Defect Analysis Chart
    if (defectChartRef.value) {
        if (defectChart) defectChart.dispose();
        defectChart = echarts.init(
            defectChartRef.value,
            isDarkMode.value ? 'dark' : undefined,
        );

        const option = {
            backgroundColor: 'transparent',
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
            },
            legend: {
                data: ['Lot Count', 'WIP Qty'],
                top: '0%',
            },
            grid: {
                left: '3%',
                right: '5%',
                bottom: '3%',
                top: '15%',
                containLabel: true,
            },
            xAxis: {
                type: 'category',
                data: endlineData.value.defectAnalysis.map((item) => item.name),
                axisLabel: { color: chartColor, fontSize: 10, rotate: 0 },
            },
            yAxis: [
                {
                    type: 'value',
                    name: 'Lot Count',
                    position: 'left',
                    splitLine: { lineStyle: { color: gridColor } },
                },
                {
                    type: 'value',
                    name: 'WIP Qty',
                    position: 'right',
                    splitLine: { show: false },
                },
            ],
            series: [
                {
                    name: 'Lot Count',
                    type: 'bar',
                    data: endlineData.value.defectAnalysis.map((item) => item.count),
                    itemStyle: { color: '#3b82f6' },
                    label: {
                        show: true,
                        position: 'top',
                        color: chartColor,
                    },
                },
                {
                    name: 'WIP Qty',
                    type: 'bar',
                    yAxisIndex: 1,
                    data: endlineData.value.defectAnalysis.map((item) => item.qty),
                    itemStyle: { color: '#fa8128' },
                    label: {
                        show: true,
                        position: 'top',
                        color: chartColor,
                        formatter: (params: any) => formatNumber(params.value),
                    },
                },
            ],
            color: ['#3b82f6', '#fa8128'],
        };

        defectChart.setOption(option);
    }

    // To Do Analysis Chart
    if (toDoChartRef.value) {
        if (toDoChart) toDoChart.dispose();
        toDoChart = echarts.init(
            toDoChartRef.value,
            isDarkMode.value ? 'dark' : undefined,
        );

        const option = {
            backgroundColor: 'transparent',
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
            },
            legend: {
                data: ['Lot Count', 'WIP Qty'],
                top: '0%',
            },
            grid: {
                left: '3%',
                right: '5%',
                bottom: '3%',
                top: '15%',
                containLabel: true,
            },
            xAxis: {
                type: 'category',
                data: endlineData.value.toDoAnalysis.map((item) => item.name),
                axisLabel: { color: chartColor, fontSize: 10, rotate: 0 },
            },
            yAxis: [
                {
                    type: 'value',
                    name: 'Lot Count',
                    position: 'left',
                    splitLine: { lineStyle: { color: gridColor } },
                },
                {
                    type: 'value',
                    name: 'WIP Qty',
                    position: 'right',
                    splitLine: { show: false },
                },
            ],
            series: [
                {
                    name: 'Lot Count',
                    type: 'bar',
                    data: endlineData.value.toDoAnalysis.map((item) => item.count),
                    itemStyle: { color: '#10b981' },
                    label: {
                        show: true,
                        position: 'top',
                        color: chartColor,
                    },
                },
                {
                    name: 'WIP Qty',
                    type: 'bar',
                    yAxisIndex: 1,
                    data: endlineData.value.toDoAnalysis.map((item) => item.qty),
                    itemStyle: { color: '#fdaf22' },
                    label: {
                        show: true,
                        position: 'top',
                        color: chartColor,
                        formatter: (params: any) => formatNumber(params.value),
                    },
                },
            ],
            color: ['#10b981', '#fdaf22'],
        };

        toDoChart.setOption(option);
    }
};

watch(isDarkMode, () => {
    initCharts();
});

watch(selectedLocation, () => {
    initCharts();
});

onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark');

    nextTick(() => {
        initCharts();

        resizeObserver = new ResizeObserver(() => {
            if (lotCountChart) lotCountChart.resize();
            if (wipQtyChart) wipQtyChart.resize();
            if (donutChart) donutChart.resize();
            if (defectChart) defectChart.resize();
            if (toDoChart) toDoChart.resize();
        });

        if (lotCountChartRef.value) resizeObserver.observe(lotCountChartRef.value);
        if (wipQtyChartRef.value) resizeObserver.observe(wipQtyChartRef.value);
        if (donutChartRef.value) resizeObserver.observe(donutChartRef.value);
        if (defectChartRef.value) resizeObserver.observe(defectChartRef.value);
        if (toDoChartRef.value) resizeObserver.observe(toDoChartRef.value);

        window.addEventListener('resize', () => {
            if (lotCountChart) lotCountChart.resize();
            if (wipQtyChart) wipQtyChart.resize();
            if (donutChart) donutChart.resize();
            if (defectChart) defectChart.resize();
            if (toDoChart) toDoChart.resize();
        });

        const themeObserver = new MutationObserver(() => {
            const newDarkMode = document.documentElement.classList.contains('dark');
            if (newDarkMode !== isDarkMode.value) {
                isDarkMode.value = newDarkMode;
            }
        });

        themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });

        (window as any).__themeObserver = themeObserver;
    });
});

onUnmounted(() => {
    if (resizeObserver) resizeObserver.disconnect();
    if (lotCountChart) lotCountChart.dispose();
    if (wipQtyChart) wipQtyChart.dispose();
    if (donutChart) donutChart.dispose();
    if (defectChart) defectChart.dispose();
    if (toDoChart) toDoChart.dispose();
    if ((window as any).__themeObserver) {
        (window as any).__themeObserver.disconnect();
        delete (window as any).__themeObserver;
    }
});
</script>

<template>
    <Head title="Endline Analytics" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #filters>
            <div class="flex items-center gap-3">
                <!-- Last Update Display -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground">LAST UPDATE:</span>
                    <span class="text-xs font-semibold text-foreground">{{
                        endlineData.lastUpdate
                    }}</span>
                </div>

                <!-- Location Filter -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground">LOCATION:</span>
                    <select
                        v-model="selectedLocation"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100"
                    >
                        <option
                            v-for="location in endlineData.locations"
                            :key="location"
                            :value="location"
                        >
                            {{ location }}
                        </option>
                    </select>
                </div>
            </div>
        </template>

        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
            <!-- Under Construction Banner -->
            <div
                class="rounded-lg border-2 border-dashed border-orange-400 bg-orange-50 p-4 dark:bg-orange-950/20"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/30"
                    >
                        <span class="text-2xl">🚧</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-orange-900 dark:text-orange-100">
                            Under Construction
                        </h3>
                        <p class="text-sm text-orange-700 dark:text-orange-300">
                            This page is currently under development. The data shown below is
                            sample data for demonstration purposes only.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Top Stats Row -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                <!-- Total Lot Count -->
                <div
                    class="group cursor-pointer rounded-lg bg-gradient-to-br from-purple-50 to-purple-100/50 dark:from-purple-950/30 dark:to-purple-900/20 p-4 shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="block text-xs font-semibold text-purple-700 dark:text-purple-300"
                                >TOTAL LOT COUNT</span
                            >
                            <h5 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatNumber(endlineData.stats.totalLotCount) }}
                            </h5>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                                <span class="text-green-500 dark:text-green-400">
                                    {{ endlineData.stats.totalLotPercentage }}%
                                </span>
                                of capacity
                            </div>
                        </div>
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-500/10 ring-1 ring-purple-500/20 text-purple-600 dark:text-purple-400 transition-all duration-200 group-hover:bg-purple-500/20 group-hover:ring-purple-500/30 group-hover:scale-110"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- WIP Qty -->
                <div
                    class="group cursor-pointer rounded-lg bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-950/30 dark:to-blue-900/20 p-4 shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="block text-xs font-semibold text-blue-700 dark:text-blue-300"
                                >WIP QTY</span
                            >
                            <h5 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatNumber(endlineData.stats.wipQty) }}
                            </h5>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                                Units in process
                            </div>
                        </div>
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-500/10 ring-1 ring-blue-500/20 text-blue-600 dark:text-blue-400 transition-all duration-200 group-hover:bg-blue-500/20 group-hover:ring-blue-500/30 group-hover:scale-110"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Defect Count -->
                <div
                    class="group cursor-pointer rounded-lg bg-gradient-to-br from-red-50 to-red-100/50 dark:from-red-950/30 dark:to-red-900/20 p-4 shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="block text-xs font-semibold text-red-700 dark:text-red-300"
                                >DEFECT</span
                            >
                            <h5 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatNumber(endlineData.stats.defectCount) }}
                            </h5>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                                Items with issues
                            </div>
                        </div>
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-500/10 ring-1 ring-red-500/20 text-red-600 dark:text-red-400 transition-all duration-200 group-hover:bg-red-500/20 group-hover:ring-red-500/30 group-hover:scale-110"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- To Do Count -->
                <div
                    class="group cursor-pointer rounded-lg bg-gradient-to-br from-green-50 to-green-100/50 dark:from-green-950/30 dark:to-green-900/20 p-4 shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="block text-xs font-semibold text-green-700 dark:text-green-300"
                                >TO DO</span
                            >
                            <h5 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatNumber(endlineData.stats.toDoCount) }}
                            </h5>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                                Pending tasks
                            </div>
                        </div>
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-500/10 ring-1 ring-green-500/20 text-green-600 dark:text-green-400 transition-all duration-200 group-hover:bg-green-500/20 group-hover:ring-green-500/30 group-hover:scale-110"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"
                                />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Charts Row -->
            <div class="grid grid-cols-1 gap-4 2xl:grid-cols-12">
                <!-- Left Column: Lot Count & WIP Qty Charts -->
                <div class="2xl:col-span-4">
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Lot Count Chart -->
                        <div
                            class="rounded-lg border border-sidebar-border/70 bg-card p-4 shadow dark:border-sidebar-border"
                        >
                            <h3 class="mb-3 text-sm font-semibold text-foreground">
                                Lot Count by Status
                            </h3>
                            <div ref="lotCountChartRef" class="h-[280px] w-full"></div>
                        </div>

                        <!-- WIP Qty Chart -->
                        <div
                            class="rounded-lg border border-sidebar-border/70 bg-card p-4 shadow dark:border-sidebar-border"
                        >
                            <h3 class="mb-3 text-sm font-semibold text-foreground">
                                WIP Qty by Status
                            </h3>
                            <div ref="wipQtyChartRef" class="h-[280px] w-full"></div>
                        </div>
                    </div>
                </div>

                <!-- Middle Column: Donut Chart -->
                <div class="2xl:col-span-3">
                    <div
                        class="rounded-lg border border-sidebar-border/70 bg-card p-4 shadow dark:border-sidebar-border h-full"
                    >
                        <h3 class="mb-3 text-sm font-semibold text-foreground">
                            {{ selectedLocation === 'ALL' ? 'Overall' : selectedLocation }} Status Distribution
                        </h3>
                        <div ref="donutChartRef" class="h-[300px] w-full"></div>
                        
                        <!-- Legend -->
                        <div class="mt-4 space-y-2">
                            <div
                                v-for="(item, index) in (selectedLocation === 'ALL' 
                                    ? endlineData.lotCountByStatus.slice(0, 3) 
                                    : (selectedLocation in endlineData.lotCountByLocation ? endlineData.lotCountByLocation[selectedLocation as LocationKey] : []))"
                                :key="index"
                                class="flex items-center justify-between text-xs"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-3 w-3 rounded-sm"
                                        :style="{
                                            backgroundColor: ['#3b82f6', '#985ffd', '#10b981', '#f59e0b', '#ef4444'][index],
                                        }"
                                    ></div>
                                    <span class="text-muted-foreground">{{ item.name }}</span>
                                </div>
                                <span class="font-semibold text-foreground">{{ item.value }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Defect & To Do Analysis -->
                <div class="2xl:col-span-5">
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Defect Analysis -->
                        <div
                            class="rounded-lg border border-sidebar-border/70 bg-card p-4 shadow dark:border-sidebar-border"
                        >
                            <h3 class="mb-3 text-sm font-semibold text-foreground">
                                Defect Analysis
                            </h3>
                            <div ref="defectChartRef" class="h-[280px] w-full"></div>
                        </div>

                        <!-- To Do Analysis -->
                        <div
                            class="rounded-lg border border-sidebar-border/70 bg-card p-4 shadow dark:border-sidebar-border"
                        >
                            <h3 class="mb-3 text-sm font-semibold text-foreground">
                                To Do Analysis
                            </h3>
                            <div ref="toDoChartRef" class="h-[280px] w-full"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Table Section -->
            <div
                class="rounded-lg border border-sidebar-border/70 bg-card shadow dark:border-sidebar-border"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-border bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Inc</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Month</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Date</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Lot %</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Model</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Size</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Current Qty</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Table No</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">QC Result</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Analysis Result</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Lot Location</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Lot Sub Location</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Defect</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">To Do</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="i in 10"
                                :key="i"
                                class="hover:bg-muted/30 transition-colors"
                            >
                                <td class="px-4 py-3 text-muted-foreground">{{ i }}</td>
                                <td class="px-4 py-3 text-foreground">Nov</td>
                                <td class="px-4 py-3 text-foreground">2024-11-{{ String(i).padStart(2, '0') }}</td>
                                <td class="px-4 py-3 text-foreground">{{ (Math.random() * 100).toFixed(1) }}%</td>
                                <td class="px-4 py-3 text-foreground">Model-{{ i }}</td>
                                <td class="px-4 py-3 text-foreground">{{ ['S', 'M', 'L', 'XL'][i % 4] }}</td>
                                <td class="px-4 py-3 text-foreground">{{ Math.floor(Math.random() * 1000) + 100 }}</td>
                                <td class="px-4 py-3 text-foreground">T-{{ i }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2 py-1 text-xs font-medium',
                                            i % 3 === 0
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                : i % 3 === 1
                                                ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        ]"
                                    >
                                        {{ i % 3 === 0 ? 'PASS' : i % 3 === 1 ? 'PENDING' : 'FAIL' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-foreground">{{ ['OK', 'Review', 'Reject'][i % 3] }}</td>
                                <td class="px-4 py-3 text-foreground">{{ ['QC INSPECTION', 'FOR QC ANALYSIS', 'PRODUCTION'][i % 3] }}</td>
                                <td class="px-4 py-3 text-foreground">{{ ['Zone A', 'Zone B', 'Zone C'][i % 3] }}</td>
                                <td class="px-4 py-3 text-foreground">{{ i % 4 === 0 ? Math.floor(Math.random() * 10) : '-' }}</td>
                                <td class="px-4 py-3 text-foreground">{{ i % 5 === 0 ? Math.floor(Math.random() * 5) : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
