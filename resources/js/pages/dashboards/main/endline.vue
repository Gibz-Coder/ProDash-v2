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

// Sample data for Endline Analytics
const endlineData = ref({
    lastUpdate: 'February 12, 2026 (08:00 AM)',
    selectedPeriod: 'MONTHLY',
    selectedSize: 'LIPAS',
    
    // Trend data for line charts
    trendData: {
        monthly: [
            { period: 'NOV', value: 105291.752 },
            { period: 'DEC', value: 102291.752 },
            { period: 'JAN', value: 106291.752 },
            { period: 'FEB', value: 102291.752 },
        ],
        weekly: [
            { period: 'WK3', value: 104291.752 },
            { period: 'WK4', value: 106291.752 },
            { period: 'WK5', value: 102291.752 },
            { period: 'WK6', value: 106291.752 },
        ],
        daily: [
            { period: '9-FEB', value: 104291.752 },
            { period: '10-FEB', value: 102291.752 },
            { period: '11-FEB', value: 106291.752 },
            { period: '12-FEB', value: 102291.752 },
        ],
    },
    
    // Daily trend per size
    dailyTrendPerSize: {
        dates: ['8-FEB', '9-FEB', '10-FEB', '11-FEB', '12-FEB'],
        series: [
            { name: '0603', data: [95000, 98000, 92000, 96000, 94000], color: '#60a5fa' },
            { name: '1005', data: [102000, 105000, 98000, 103000, 100000], color: '#818cf8' },
            { name: '1608', data: [88000, 91000, 85000, 89000, 87000], color: '#a78bfa' },
            { name: '2012', data: [110000, 115000, 108000, 112000, 110000], color: '#c084fc' },
            { name: '3216', data: [95000, 98000, 92000, 96000, 94000], color: '#e879f9' },
            { name: '3225', data: [78000, 82000, 76000, 80000, 78000], color: '#f472b6' },
        ],
    },
    
    // Per cut off data
    cutOffData: [
        {
            time: '12:00 AM',
            total: 201497.734,
            breakdown: [
                { name: 'Endline', value: 4, color: '#60a5fa' },
                { name: 'For MC', value: 116, color: '#818cf8' },
                { name: 'QC', value: 34, color: '#a78bfa' },
                { name: 'SIMOT', value: 78, color: '#c084fc' },
                { name: 'TechT', value: 14, color: '#e879f9' },
            ],
            detailedData: [
                { name: 'Endline', value: 105291.752, label: '105,291.752' },
                { name: 'For MC', value: 33289.126, label: '33,289.126' },
                { name: 'QC', value: 0, label: '0' },
                { name: 'SIMOT', value: 0, label: '0' },
                { name: 'TechT', value: 16906.344, label: '16,906.344' },
            ],
        },
        {
            time: '04:00 AM',
            total: 330843.408,
            breakdown: [
                { name: 'Endline', value: 0, color: '#60a5fa' },
                { name: 'For MC', value: 37, color: '#818cf8' },
                { name: 'QC', value: 0, color: '#a78bfa' },
                { name: 'SIMOT', value: 138, color: '#c084fc' },
                { name: 'TechT', value: 27, color: '#e879f9' },
            ],
            detailedData: [
                { name: 'Endline', value: 0, label: '0' },
                { name: 'For MC', value: 34110.105, label: '34,110.105' },
                { name: 'QC', value: 173642.327, label: '173,642.327' },
                { name: 'SIMOT', value: 94413.602, label: '94,413.602' },
                { name: 'TechT', value: 28779.801, label: '28,779.801' },
            ],
        },
        {
            time: '08:00 AM',
            total: 217442.692,
            breakdown: [
                { name: 'Endline', value: 0, color: '#60a5fa' },
                { name: 'For MC', value: 125, color: '#818cf8' },
                { name: 'QC', value: 68, color: '#a78bfa' },
                { name: 'SIMOT', value: 72, color: '#c084fc' },
                { name: 'TechT', value: 0, color: '#e879f9' },
            ],
            detailedData: [
                { name: 'Endline', value: 0, label: '0' },
                { name: 'For MC', value: 66102.646, label: '66,102.646' },
                { name: 'QC', value: 23149.205, label: '23,149.205' },
                { name: 'SIMOT', value: 27545.625, label: '27,545.625' },
                { name: 'TechT', value: 13456.126, label: '13,456.126' },
            ],
        },
    ],
    
    // LIPAS vs VOLIPAS comparison
    lipasVsVolipas: {
        sizes: ['0603', '1005', '1608', '2012', '3216', '3225'],
        lipas: [
            { size: '0603', value: 105000, label: '105,000 M' },
            { size: '1005', value: 98000, label: '98,000 M' },
            { size: '1608', value: 92000, label: '92,000 M' },
            { size: '2012', value: 115000, label: '115,000 M' },
            { size: '3216', value: 88000, label: '88,000 M' },
            { size: '3225', value: 76000, label: '76,000 M' },
        ],
        volipas: [
            { size: '0603', value: 95000, label: '95,000 M' },
            { size: '1005', value: 89000, label: '89,000 M' },
            { size: '1608', value: 85000, label: '85,000 M' },
            { size: '2012', value: 105000, label: '105,000 M' },
            { size: '3216', value: 82000, label: '82,000 M' },
            { size: '3225', value: 70000, label: '70,000 M' },
        ],
    },
});

// Chart refs
const trendChartRef = ref<HTMLElement | null>(null);
const dailyTrendChartRef = ref<HTMLElement | null>(null);
const cutOff1ChartRef = ref<HTMLElement | null>(null);
const cutOff2ChartRef = ref<HTMLElement | null>(null);
const cutOff3ChartRef = ref<HTMLElement | null>(null);
const lipasChartRef = ref<HTMLElement | null>(null);
const volipasChartRef = ref<HTMLElement | null>(null);

let trendChart: echarts.ECharts | null = null;
let dailyTrendChart: echarts.ECharts | null = null;
let cutOff1Chart: echarts.ECharts | null = null;
let cutOff2Chart: echarts.ECharts | null = null;
let cutOff3Chart: echarts.ECharts | null = null;
let lipasChart: echarts.ECharts | null = null;
let volipasChart: echarts.ECharts | null = null;
let resizeObserver: ResizeObserver | null = null;

const formatNumber = (value: number): string => {
    return value.toLocaleString('en-US');
};

const initCharts = () => {
    const chartColor = isDarkMode.value ? '#e5e7eb' : '#374151';
    const gridColor = 'rgba(142, 156, 173, 0.1)';
    const bgColor = isDarkMode.value ? '#1f2937' : '#f9fafb';
    
    // Get current trend data based on selected period
    const getTrendData = () => {
        switch (endlineData.value.selectedPeriod) {
            case 'WEEKLY':
                return endlineData.value.trendData.weekly;
            case 'DAILY':
                return endlineData.value.trendData.daily;
            default:
                return endlineData.value.trendData.monthly;
        }
    };
    
    // Overall Endline Delay Trend Chart
    if (trendChartRef.value) {
        if (trendChart) trendChart.dispose();
        trendChart = echarts.init(trendChartRef.value, isDarkMode.value ? 'dark' : undefined);

        const trendData = getTrendData();
        const option = {
            backgroundColor: 'transparent',
            tooltip: {
                trigger: 'axis',
                formatter: (params: any) => {
                    const item = params[0];
                    return `${item.name}<br/>${item.marker} ${formatNumber(item.value)}`;
                },
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '10%',
                top: '10%',
                containLabel: true,
            },
            xAxis: {
                type: 'category',
                data: trendData.map((item) => item.period),
                axisLabel: { color: chartColor, fontSize: 11 },
                axisLine: { lineStyle: { color: gridColor } },
            },
            yAxis: {
                type: 'value',
                splitLine: { lineStyle: { color: gridColor } },
                axisLabel: {
                    color: chartColor,
                    formatter: (value: number) => formatNumber(value),
                },
            },
            series: [
                {
                    type: 'line',
                    data: trendData.map((item) => item.value),
                    smooth: true,
                    symbol: 'circle',
                    symbolSize: 8,
                    itemStyle: { color: '#60a5fa' },
                    lineStyle: { width: 3, color: '#60a5fa' },
                    label: {
                        show: true,
                        position: 'top',
                        formatter: (params: any) => formatNumber(params.value),
                        color: chartColor,
                        fontSize: 10,
                    },
                },
            ],
        };

        trendChart.setOption(option);
    }

    // Daily Trend Per Size Chart
    if (dailyTrendChartRef.value) {
        if (dailyTrendChart) dailyTrendChart.dispose();
        dailyTrendChart = echarts.init(dailyTrendChartRef.value, isDarkMode.value ? 'dark' : undefined);

        const option = {
            backgroundColor: 'transparent',
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
            },
            legend: {
                data: endlineData.value.dailyTrendPerSize.series.map((s) => s.name),
                top: '0%',
                textStyle: { color: chartColor, fontSize: 10 },
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '8%',
                top: '15%',
                containLabel: true,
            },
            xAxis: {
                type: 'category',
                data: endlineData.value.dailyTrendPerSize.dates,
                axisLabel: { color: chartColor, fontSize: 10 },
            },
            yAxis: {
                type: 'value',
                splitLine: { lineStyle: { color: gridColor } },
                axisLabel: {
                    color: chartColor,
                    formatter: (value: number) => formatNumber(value),
                },
            },
            series: endlineData.value.dailyTrendPerSize.series.map((s) => ({
                name: s.name,
                type: 'bar',
                data: s.data,
                itemStyle: { color: s.color },
            })),
        };

        dailyTrendChart.setOption(option);
    }

    // Cut Off Charts (3 charts)
    const cutOffCharts = [
        { ref: cutOff1ChartRef, chart: cutOff1Chart, data: endlineData.value.cutOffData[0] },
        { ref: cutOff2ChartRef, chart: cutOff2Chart, data: endlineData.value.cutOffData[1] },
        { ref: cutOff3ChartRef, chart: cutOff3Chart, data: endlineData.value.cutOffData[2] },
    ];

    cutOffCharts.forEach((item, index) => {
        if (item.ref.value) {
            if (item.chart) item.chart.dispose();
            const chart = echarts.init(item.ref.value, isDarkMode.value ? 'dark' : undefined);

            const option = {
                backgroundColor: 'transparent',
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    top: '3%',
                    containLabel: true,
                },
                xAxis: {
                    type: 'category',
                    data: item.data.detailedData.map((d) => d.name),
                    axisLabel: { color: chartColor, fontSize: 9, rotate: 0 },
                },
                yAxis: {
                    type: 'value',
                    splitLine: { lineStyle: { color: gridColor } },
                    axisLabel: {
                        color: chartColor,
                        formatter: (value: number) => formatNumber(value),
                        fontSize: 9,
                    },
                },
                series: [
                    {
                        type: 'bar',
                        data: item.data.detailedData.map((d, idx) => ({
                            value: d.value,
                            itemStyle: { color: item.data.breakdown[idx]?.color || '#60a5fa' },
                        })),
                        label: {
                            show: true,
                            position: 'top',
                            formatter: (params: any) => {
                                const dataItem = item.data.detailedData[params.dataIndex];
                                return dataItem.label;
                            },
                            color: chartColor,
                            fontSize: 8,
                        },
                        barWidth: '50%',
                    },
                ],
            };

            chart.setOption(option);

            // Update the chart reference
            if (index === 0) cutOff1Chart = chart;
            if (index === 1) cutOff2Chart = chart;
            if (index === 2) cutOff3Chart = chart;
        }
    });

    // LIPAS Chart
    if (lipasChartRef.value) {
        if (lipasChart) lipasChart.dispose();
        lipasChart = echarts.init(lipasChartRef.value, isDarkMode.value ? 'dark' : undefined);

        const option = {
            backgroundColor: 'transparent',
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '8%',
                top: '5%',
                containLabel: true,
            },
            xAxis: {
                type: 'category',
                data: endlineData.value.lipasVsVolipas.sizes,
                axisLabel: { color: chartColor, fontSize: 10 },
            },
            yAxis: {
                type: 'value',
                splitLine: { lineStyle: { color: gridColor } },
                axisLabel: {
                    color: chartColor,
                    formatter: (value: number) => formatNumber(value),
                },
            },
            series: [
                {
                    name: 'LIPAS',
                    type: 'bar',
                    data: endlineData.value.lipasVsVolipas.lipas.map((item) => item.value),
                    itemStyle: { color: '#60a5fa' },
                    label: {
                        show: true,
                        position: 'top',
                        formatter: (params: any) => {
                            return endlineData.value.lipasVsVolipas.lipas[params.dataIndex].label;
                        },
                        color: chartColor,
                        fontSize: 9,
                    },
                },
            ],
        };

        lipasChart.setOption(option);
    }

    // VOLIPAS Chart
    if (volipasChartRef.value) {
        if (volipasChart) volipasChart.dispose();
        volipasChart = echarts.init(volipasChartRef.value, isDarkMode.value ? 'dark' : undefined);

        const option = {
            backgroundColor: 'transparent',
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '8%',
                top: '5%',
                containLabel: true,
            },
            xAxis: {
                type: 'category',
                data: endlineData.value.lipasVsVolipas.sizes,
                axisLabel: { color: chartColor, fontSize: 10 },
            },
            yAxis: {
                type: 'value',
                splitLine: { lineStyle: { color: gridColor } },
                axisLabel: {
                    color: chartColor,
                    formatter: (value: number) => formatNumber(value),
                },
            },
            series: [
                {
                    name: 'VOLIPAS',
                    type: 'bar',
                    data: endlineData.value.lipasVsVolipas.volipas.map((item) => item.value),
                    itemStyle: { color: '#818cf8' },
                    label: {
                        show: true,
                        position: 'top',
                        formatter: (params: any) => {
                            return endlineData.value.lipasVsVolipas.volipas[params.dataIndex].label;
                        },
                        color: chartColor,
                        fontSize: 9,
                    },
                },
            ],
        };

        volipasChart.setOption(option);
    }
};

watch(isDarkMode, () => {
    initCharts();
});

watch(() => endlineData.value.selectedPeriod, () => {
    initCharts();
});

watch(() => endlineData.value.selectedSize, () => {
    initCharts();
});

onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark');

    nextTick(() => {
        initCharts();

        resizeObserver = new ResizeObserver(() => {
            if (trendChart) trendChart.resize();
            if (dailyTrendChart) dailyTrendChart.resize();
            if (cutOff1Chart) cutOff1Chart.resize();
            if (cutOff2Chart) cutOff2Chart.resize();
            if (cutOff3Chart) cutOff3Chart.resize();
            if (lipasChart) lipasChart.resize();
            if (volipasChart) volipasChart.resize();
        });

        if (trendChartRef.value) resizeObserver.observe(trendChartRef.value);
        if (dailyTrendChartRef.value) resizeObserver.observe(dailyTrendChartRef.value);
        if (cutOff1ChartRef.value) resizeObserver.observe(cutOff1ChartRef.value);
        if (cutOff2ChartRef.value) resizeObserver.observe(cutOff2ChartRef.value);
        if (cutOff3ChartRef.value) resizeObserver.observe(cutOff3ChartRef.value);
        if (lipasChartRef.value) resizeObserver.observe(lipasChartRef.value);
        if (volipasChartRef.value) resizeObserver.observe(volipasChartRef.value);

        window.addEventListener('resize', () => {
            if (trendChart) trendChart.resize();
            if (dailyTrendChart) dailyTrendChart.resize();
            if (cutOff1Chart) cutOff1Chart.resize();
            if (cutOff2Chart) cutOff2Chart.resize();
            if (cutOff3Chart) cutOff3Chart.resize();
            if (lipasChart) lipasChart.resize();
            if (volipasChart) volipasChart.resize();
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
    if (trendChart) trendChart.dispose();
    if (dailyTrendChart) dailyTrendChart.dispose();
    if (cutOff1Chart) cutOff1Chart.dispose();
    if (cutOff2Chart) cutOff2Chart.dispose();
    if (cutOff3Chart) cutOff3Chart.dispose();
    if (lipasChart) lipasChart.dispose();
    if (volipasChart) volipasChart.dispose();
    if ((window as any).__themeObserver) {
        (window as any).__themeObserver.disconnect();
        delete (window as any).__themeObserver;
    }
});
</script>

<template>
    <Head title="Endline Delay Status" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #filters>
            <div class="flex items-center gap-2 text-xs">
                <span class="font-medium text-muted-foreground">Endline Delay Status as of</span>
                <span class="font-semibold text-foreground">{{ endlineData.lastUpdate }}</span>
            </div>
        </template>

        <div class="flex h-full flex-1 flex-col gap-3 overflow-auto p-3">
            <!-- Top Row: Trend Charts -->
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <!-- Overall Endline Delay Trend -->
                <div class="rounded-lg border border-sidebar-border/70 bg-card shadow dark:border-sidebar-border">
                    <div class="border-b border-border p-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-foreground uppercase">Overall Endline Delay Trend</h3>
                            <div class="flex gap-1">
                                <button
                                    v-for="period in ['MONTHLY', 'WEEKLY', 'DAILY']"
                                    :key="period"
                                    @click="endlineData.selectedPeriod = period"
                                    :class="[
                                        'rounded px-3 py-1 text-xs font-medium transition-colors',
                                        endlineData.selectedPeriod === period
                                            ? 'bg-blue-500 text-white'
                                            : 'bg-muted text-muted-foreground hover:bg-muted/80'
                                    ]"
                                >
                                    {{ period }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <div ref="trendChartRef" class="h-[200px] w-full"></div>
                    </div>
                </div>

                <!-- Daily Trend Per Size -->
                <div class="rounded-lg border border-sidebar-border/70 bg-card shadow dark:border-sidebar-border">
                    <div class="border-b border-border p-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-foreground uppercase">Daily Trend Per Size</h3>
                            <div class="flex gap-1">
                                <button
                                    v-for="size in ['LIPAS', 'HM03']"
                                    :key="size"
                                    @click="endlineData.selectedSize = size"
                                    :class="[
                                        'rounded px-3 py-1 text-xs font-medium transition-colors',
                                        endlineData.selectedSize === size
                                            ? 'bg-blue-500 text-white'
                                            : 'bg-muted text-muted-foreground hover:bg-muted/80'
                                    ]"
                                >
                                    {{ size }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <div ref="dailyTrendChartRef" class="h-[200px] w-full"></div>
                    </div>
                </div>
            </div>

            <!-- Middle Row: Per Cut Off -->
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                <div
                    v-for="(cutOff, index) in endlineData.cutOffData"
                    :key="index"
                    class="rounded-lg border border-sidebar-border/70 bg-card shadow dark:border-sidebar-border"
                >
                    <div class="border-b border-border p-3">
                        <h3 class="text-sm font-semibold text-foreground uppercase">Per Cut Off</h3>
                    </div>
                    <div class="p-3">
                        <!-- Time and Total -->
                        <div class="mb-3 text-center">
                            <div class="text-lg font-bold text-foreground">{{ cutOff.time }}</div>
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ formatNumber(cutOff.total) }}
                            </div>
                        </div>

                        <!-- Small Bar Chart with Counts -->
                        <div class="mb-3 flex items-end justify-between gap-1" style="height: 80px;">
                            <div
                                v-for="item in cutOff.breakdown"
                                :key="item.name"
                                class="flex flex-1 flex-col items-center justify-end gap-1"
                            >
                                <div class="text-xs font-semibold text-foreground">{{ item.value }}</div>
                                <div
                                    class="w-full rounded-t"
                                    :style="{
                                        height: `${(item.value / Math.max(...cutOff.breakdown.map(b => b.value))) * 60}px`,
                                        backgroundColor: item.color,
                                        minHeight: '10px'
                                    }"
                                ></div>
                                <div class="text-[10px] font-medium text-muted-foreground">{{ item.name }}</div>
                            </div>
                        </div>

                        <!-- Detailed Chart -->
                        <div :ref="el => { if (index === 0) cutOff1ChartRef = el; else if (index === 1) cutOff2ChartRef = el; else cutOff3ChartRef = el; }" class="h-[180px] w-full"></div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: LIPAS vs VOLIPAS -->
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <!-- LIPAS -->
                <div class="rounded-lg border border-sidebar-border/70 bg-card shadow dark:border-sidebar-border">
                    <div class="border-b border-border p-3">
                        <h3 class="text-sm font-semibold text-foreground uppercase">LIPAS vs. VOLIPAS Per Size</h3>
                    </div>
                    <div class="p-3">
                        <div class="mb-2 text-center">
                            <span class="inline-block rounded bg-blue-500 px-3 py-1 text-xs font-semibold text-white">LIPAS</span>
                        </div>
                        <div ref="lipasChartRef" class="h-[200px] w-full"></div>
                    </div>
                </div>

                <!-- VOLIPAS -->
                <div class="rounded-lg border border-sidebar-border/70 bg-card shadow dark:border-sidebar-border">
                    <div class="border-b border-border p-3">
                        <h3 class="text-sm font-semibold text-foreground uppercase">LIPAS vs. VOLIPAS Per Size</h3>
                    </div>
                    <div class="p-3">
                        <div class="mb-2 text-center">
                            <span class="inline-block rounded bg-indigo-500 px-3 py-1 text-xs font-semibold text-white">VOLIPAS</span>
                        </div>
                        <div ref="volipasChartRef" class="h-[200px] w-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
