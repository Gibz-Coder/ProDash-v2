<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import ApexCharts from 'apexcharts';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: dashboard().url,
    },
    {
        title: 'Endline Monitoring',
        href: '/endline',
    },
];

// Filter states
const selectedDate = ref('');
const selectedCutOff = ref('');
const selectedWorkType = ref('');
const selectedLIPAS = ref('');
const autoRefresh = ref(false);
let refreshInterval: number | null = null;

// Auto refresh function
const toggleAutoRefresh = () => {
    autoRefresh.value = !autoRefresh.value;

    if (autoRefresh.value) {
        // Refresh every 30 seconds
        refreshInterval = window.setInterval(() => {
            console.log('Auto refreshing data...');
            // Add your refresh logic here
        }, 30000);
    } else {
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
    }
};

// Manual refresh function
const manualRefresh = () => {
    console.log('Manual refresh triggered');
    // Add your refresh logic here
};

let pieChart: ApexCharts | null = null;
let barChart: ApexCharts | null = null;
let columnChart: ApexCharts | null = null;

onBeforeUnmount(() => {
    if (pieChart) {
        (pieChart as ApexCharts).destroy();
    }
    if (barChart) {
        (barChart as ApexCharts).destroy();
    }
    if (columnChart) {
        (columnChart as ApexCharts).destroy();
    }
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

onMounted(() => {
    // Basic Pie Chart
    const pieOptions = {
        series: [350, 280, 190],
        chart: {
            height: '100%',
            type: 'pie',
        },
        colors: [
            '#3b82f6', // Blue - Main
            '#10b981', // Green - R-rework
            '#f59e0b', // Amber - L-rework
        ],
        labels: ['Main', 'R-rework', 'L-rework'],
        legend: {
            position: 'bottom',
            fontSize: '11px',
            horizontalAlign: 'center',
            floating: false,
            offsetY: 0,
            markers: {
                width: 10,
                height: 10,
                radius: 2,
            },
            itemMargin: {
                horizontal: 8,
                vertical: 4,
            },
        },
        dataLabels: {
            enabled: true,
            formatter: function (val: number) {
                return val.toFixed(1) + '%';
            },
            dropShadow: {
                enabled: false,
            },
        },
        responsive: [
            {
                breakpoint: 480,
                options: {
                    chart: {
                        width: '100%',
                    },
                    legend: {
                        position: 'bottom',
                        fontSize: '10px',
                    },
                },
            },
        ],
    };

    const pieChart = new ApexCharts(
        document.querySelector('#pie-chart'),
        pieOptions,
    );
    pieChart.render();

    // Horizontal Bar Chart
    const barOptions = {
        series: [
            {
                name: 'Main',
                data: [120, 85, 95, 110, 90, 80],
            },
            {
                name: 'R-rework',
                data: [80, 65, 75, 85, 70, 60],
            },
            {
                name: 'L-rework',
                data: [50, 40, 45, 55, 45, 40],
            },
        ],
        chart: {
            type: 'bar',
            height: '100%',
            stacked: true,
            toolbar: {
                show: false,
            },
        },
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 2,
            },
        },
        stroke: {
            width: 1,
            colors: ['#fff'],
        },
        colors: [
            '#3b82f6', // Blue - Main
            '#10b981', // Green - R-rework
            '#f59e0b', // Amber - L-rework
        ],
        grid: {
            borderColor: '#f2f5f7',
        },
        xaxis: {
            categories: ['03', '05', '10', '21', '31', '32'],
            labels: {
                show: true,
                style: {
                    colors: '#8c9097',
                    fontSize: '11px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-xaxis-label',
                },
            },
        },
        yaxis: {
            labels: {
                show: true,
                style: {
                    colors: '#8c9097',
                    fontSize: '11px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-yaxis-label',
                },
            },
        },
        tooltip: {
            y: {
                formatter: function (val: number) {
                    return val.toString();
                },
            },
        },
        fill: {
            opacity: 1,
        },
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            fontSize: '11px',
            markers: {
                width: 10,
                height: 10,
                radius: 2,
            },
            itemMargin: {
                horizontal: 8,
                vertical: 4,
            },
        },
        dataLabels: {
            enabled: false,
        },
    };

    const barChart = new ApexCharts(
        document.querySelector('#bar-chart'),
        barOptions,
    );
    barChart.render();

    // Stacked Column Chart
    const columnOptions = {
        series: [
            {
                name: '03',
                data: [
                    20, 15, 18, 22, 12, 15, 18, 10, 13, 25, 20, 18, 16, 14, 12,
                    10,
                ],
            },
            {
                name: '05',
                data: [
                    18, 12, 16, 20, 10, 13, 16, 8, 11, 22, 18, 16, 14, 12, 10,
                    8,
                ],
            },
            {
                name: '10',
                data: [
                    22, 16, 19, 24, 14, 17, 20, 12, 15, 28, 22, 20, 18, 16, 14,
                    12,
                ],
            },
            {
                name: '21',
                data: [
                    16, 11, 14, 18, 9, 12, 15, 7, 10, 20, 16, 14, 12, 10, 8, 6,
                ],
            },
            {
                name: '31',
                data: [
                    19, 14, 17, 21, 12, 15, 18, 10, 13, 24, 19, 17, 15, 13, 11,
                    9,
                ],
            },
            {
                name: '32',
                data: [14, 9, 12, 16, 8, 11, 13, 6, 9, 18, 14, 12, 10, 8, 6, 5],
            },
        ],
        chart: {
            type: 'bar',
            height: '100%',
            stacked: true,
            toolbar: {
                show: false,
            },
            zoom: {
                enabled: true,
            },
        },
        grid: {
            borderColor: '#f2f5f7',
        },
        colors: [
            '#3b82f6', // Blue - 03
            '#10b981', // Green - 05
            '#f59e0b', // Amber - 10
            '#ef4444', // Red - 21
            '#8b5cf6', // Purple - 31
            '#06b6d4', // Cyan - 32
        ],
        responsive: [
            {
                breakpoint: 480,
                options: {
                    legend: {
                        position: 'bottom',
                        offsetX: -10,
                        offsetY: 0,
                    },
                },
            },
        ],
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 2,
            },
        },
        xaxis: {
            categories: [
                'Lot weighing',
                'Low Yield',
                'R/L Rework',
                'Vi Barcode',
                'Problem Lot',
                'Decision (QC NG)',
                'Decision (Yield)',
                'DRB Approval',
                'Experiment',
                'QC Inspection',
                'QC Analysis',
                'QC OK (MOLD)',
                'QC OK (RELI)',
                'QC OK (DIPPING)',
                'QC Barcode',
                'QC Decision',
            ],
            labels: {
                show: true,
                rotate: -45,
                rotateAlways: true,
                style: {
                    colors: '#8c9097',
                    fontSize: '10px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-xaxis-label',
                },
            },
        },
        legend: {
            position: 'top',
            horizontalAlign: 'center',
            fontSize: '11px',
            markers: {
                width: 10,
                height: 10,
                radius: 2,
            },
            itemMargin: {
                horizontal: 8,
                vertical: 4,
            },
        },
        fill: {
            opacity: 1,
        },
        yaxis: {
            labels: {
                show: true,
                style: {
                    colors: '#8c9097',
                    fontSize: '11px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-yaxis-label',
                },
            },
        },
    };

    const columnChart = new ApexCharts(
        document.querySelector('#column-chart'),
        columnOptions,
    );
    columnChart.render();
});
</script>

<template>
    <Head title="Endline" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #filters>
            <div class="flex items-center gap-2">
                <!-- Date Picker -->
                <input
                    v-model="selectedDate"
                    type="date"
                    class="rounded-md border border-input bg-background px-2 py-1 text-xs text-foreground focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                />

                <!-- Cut-Off Selection -->
                <select
                    v-model="selectedCutOff"
                    class="rounded-md border border-input bg-background px-2 py-1 text-xs text-foreground focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                >
                    <option value="">Cut-Off: All</option>
                    <option value="DAY_1ST">DAY 1ST</option>
                    <option value="DAY_2ND">DAY 2ND</option>
                    <option value="DAY_3RD">DAY 3RD</option>
                    <option value="NIGHT_1ST">NIGHT 1ST</option>
                    <option value="NIGHT_2ND">NIGHT 2ND</option>
                    <option value="NIGHT_3RD">NIGHT 3RD</option>
                </select>

                <!-- Work Type -->
                <select
                    v-model="selectedWorkType"
                    class="rounded-md border border-input bg-background px-2 py-1 text-xs text-foreground focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                >
                    <option value="">Work Type: All</option>
                    <option value="NORMAL">NORMAL</option>
                    <option value="REWORK">REWORK</option>
                    <option value="URGENT">URGENT</option>
                </select>

                <!-- LIPAS -->
                <select
                    v-model="selectedLIPAS"
                    class="rounded-md border border-input bg-background px-2 py-1 text-xs text-foreground focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                >
                    <option value="">LIPAS: All</option>
                    <option value="Y">Yes</option>
                    <option value="N">No</option>
                </select>

                <!-- Refresh Button -->
                <button
                    @click="manualRefresh"
                    class="flex items-center gap-1 rounded-md border border-blue-600 bg-transparent px-2 py-1 text-xs font-medium text-blue-600 transition-colors hover:bg-blue-600 hover:text-white"
                    title="Refresh"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-3.5 w-3.5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path
                            d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"
                        />
                    </svg>
                    Refresh
                </button>

                <!-- Auto Refresh Toggle -->
                <button
                    @click="toggleAutoRefresh"
                    :class="[
                        'flex items-center gap-1 rounded-md border px-2 py-1 text-xs font-medium transition-colors',
                        autoRefresh
                            ? 'border-green-600 bg-green-600 text-white hover:bg-green-700'
                            : 'border-gray-600 bg-transparent text-gray-600 hover:bg-gray-600 hover:text-white dark:text-gray-400',
                    ]"
                    :title="
                        autoRefresh ? 'Auto Refresh ON' : 'Auto Refresh OFF'
                    "
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-3.5 w-3.5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path
                            d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"
                        />
                    </svg>
                    Auto {{ autoRefresh ? 'ON' : 'OFF' }}
                </button>
            </div>
        </template>

        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="grid gap-4 md:grid-cols-4 md:grid-rows-1">
                <div
                    class="relative min-h-[400px] overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <div
                        class="mb-3 flex justify-center border-b border-sidebar-border/50 pb-2"
                    >
                        <h3 class="text-sm font-bold text-foreground">
                            Summary of Endline Delay
                        </h3>
                    </div>
                    <div id="pie-chart" class="h-full w-full pb-2"></div>
                </div>
                <div
                    class="relative min-h-[400px] overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <div class="mb-2 flex justify-center">
                        <h3 class="text-sm font-bold text-foreground">
                            Per Size Endline Delay
                        </h3>
                    </div>
                    <div id="bar-chart" class="h-full w-full"></div>
                </div>
                <div
                    class="relative min-h-[400px] overflow-hidden rounded-xl border border-sidebar-border/70 p-4 md:col-span-2 dark:border-sidebar-border"
                >
                    <div class="mb-2 flex justify-between">
                        <!-- Left side - 3 button group -->
                        <div
                            class="inline-flex rounded-md shadow-sm"
                            role="group"
                        >
                            <button
                                type="button"
                                class="rounded-l-md border border-green-600 bg-transparent px-2 py-1 text-xs font-medium text-green-600 hover:bg-green-600 hover:text-white focus:z-10 focus:bg-green-600 focus:text-white focus:ring-1 focus:ring-green-600 dark:border-green-500 dark:text-green-500 dark:hover:bg-green-500 dark:hover:text-white dark:focus:bg-green-500"
                            >
                                Mainlot
                            </button>
                            <button
                                type="button"
                                class="border border-x-0 border-green-600 bg-transparent px-2 py-1 text-xs font-medium text-green-600 hover:bg-green-600 hover:text-white focus:z-10 focus:bg-green-600 focus:text-white focus:ring-1 focus:ring-green-600 dark:border-green-500 dark:text-green-500 dark:hover:bg-green-500 dark:hover:text-white dark:focus:bg-green-500"
                            >
                                R-rework
                            </button>
                            <button
                                type="button"
                                class="rounded-r-md border border-green-600 bg-transparent px-2 py-1 text-xs font-medium text-green-600 hover:bg-green-600 hover:text-white focus:z-10 focus:bg-green-600 focus:text-white focus:ring-1 focus:ring-green-600 dark:border-green-500 dark:text-green-500 dark:hover:bg-green-500 dark:hover:text-white dark:focus:bg-green-500"
                            >
                                L-rework
                            </button>
                        </div>

                        <h3 class="text-sm font-bold text-foreground">
                            Detailed Endline Delay
                        </h3>

                        <!-- Right side - Technical/QC Analysis -->
                        <div
                            class="inline-flex rounded-md shadow-sm"
                            role="group"
                        >
                            <button
                                type="button"
                                class="rounded-l-md border border-blue-600 bg-transparent px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-600 hover:text-white focus:z-10 focus:bg-blue-600 focus:text-white focus:ring-1 focus:ring-blue-600 dark:border-blue-500 dark:text-blue-500 dark:hover:bg-blue-500 dark:hover:text-white dark:focus:bg-blue-500"
                            >
                                Technical
                            </button>
                            <button
                                type="button"
                                class="rounded-r-md border border-blue-600 bg-transparent px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-600 hover:text-white focus:z-10 focus:bg-blue-600 focus:text-white focus:ring-1 focus:ring-blue-600 dark:border-blue-500 dark:text-blue-500 dark:hover:bg-blue-500 dark:hover:text-white dark:focus:bg-blue-500"
                            >
                                QC Analysis
                            </button>
                        </div>
                    </div>
                    <div id="column-chart" class="h-full w-full"></div>
                </div>
            </div>
            <div
                class="relative flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b border-sidebar-border/70 bg-muted/50"
                        >
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">
                                    No.
                                </th>
                                <th class="px-4 py-3 text-left font-semibold">
                                    Lot No.
                                </th>
                                <th class="px-4 py-3 text-left font-semibold">
                                    Model
                                </th>
                                <th class="px-4 py-3 text-right font-semibold">
                                    Quantity
                                </th>
                                <th class="px-4 py-3 text-center font-semibold">
                                    LIPAS
                                </th>
                                <th class="px-4 py-3 text-center font-semibold">
                                    QC NG?
                                </th>
                                <th class="px-4 py-3 text-left font-semibold">
                                    Defect
                                </th>
                                <th class="px-4 py-3 text-left font-semibold">
                                    Defect Class
                                </th>
                                <th class="px-4 py-3 text-left font-semibold">
                                    Date
                                </th>
                                <th class="px-4 py-3 text-center font-semibold">
                                    TAT
                                </th>
                                <th class="px-4 py-3 text-left font-semibold">
                                    Last Decision
                                </th>
                                <th class="px-4 py-3 text-left font-semibold">
                                    Updated By
                                </th>
                                <th class="px-4 py-3 text-center font-semibold">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/50">
                            <tr class="transition-colors hover:bg-muted/30">
                                <td class="px-4 py-3">1</td>
                                <td class="px-4 py-3 font-mono text-xs">
                                    LOT001
                                </td>
                                <td class="px-4 py-3">10A106MQ8NNN</td>
                                <td class="px-4 py-3 text-right font-mono">
                                    1,500
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-500/20 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-300"
                                        >Y</span
                                    >
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full bg-red-500/20 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-300"
                                        >Main</span
                                    >
                                </td>
                                <td class="px-4 py-3">Scratch</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center rounded-full bg-amber-500/20 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300"
                                        >Analysis</span
                                    >
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    2024-03-11 14:30
                                </td>
                                <td
                                    class="px-4 py-3 text-center font-mono text-xs"
                                >
                                    2.5h
                                </td>
                                <td class="px-4 py-3">Dipping</td>
                                <td class="px-4 py-3">John Doe</td>
                                <td class="px-4 py-3 text-center">
                                    <div
                                        class="flex items-center justify-center gap-2"
                                    >
                                        <button
                                            class="rounded-md border border-blue-600 bg-transparent px-2 py-1 text-xs font-medium text-blue-600 transition-colors hover:bg-blue-600 hover:text-white"
                                        >
                                            View
                                        </button>
                                        <button
                                            class="rounded-md border border-green-600 bg-transparent px-2 py-1 text-xs font-medium text-green-600 transition-colors hover:bg-green-600 hover:text-white"
                                        >
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="transition-colors hover:bg-muted/30">
                                <td class="px-4 py-3">2</td>
                                <td class="px-4 py-3 font-mono text-xs">
                                    LOT002
                                </td>
                                <td class="px-4 py-3">10B104KA8NNP</td>
                                <td class="px-4 py-3 text-right font-mono">
                                    2,300
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full bg-gray-500/20 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300"
                                        >N</span
                                    >
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-500/20 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-300"
                                        >OK</span
                                    >
                                </td>
                                <td class="px-4 py-3">-</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-500/20 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-300"
                                        >OK</span
                                    >
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    2024-03-11 15:45
                                </td>
                                <td
                                    class="px-4 py-3 text-center font-mono text-xs"
                                >
                                    1.2h
                                </td>
                                <td class="px-4 py-3">Low Yield</td>
                                <td class="px-4 py-3">Jane Smith</td>
                                <td class="px-4 py-3 text-center">
                                    <div
                                        class="flex items-center justify-center gap-2"
                                    >
                                        <button
                                            class="rounded-md border border-blue-600 bg-transparent px-2 py-1 text-xs font-medium text-blue-600 transition-colors hover:bg-blue-600 hover:text-white"
                                        >
                                            View
                                        </button>
                                        <button
                                            class="rounded-md border border-green-600 bg-transparent px-2 py-1 text-xs font-medium text-green-600 transition-colors hover:bg-green-600 hover:text-white"
                                        >
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="transition-colors hover:bg-muted/30">
                                <td class="px-4 py-3">3</td>
                                <td class="px-4 py-3 font-mono text-xs">
                                    LOT003
                                </td>
                                <td class="px-4 py-3">10C223JB8NNN</td>
                                <td class="px-4 py-3 text-right font-mono">
                                    1,800
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-500/20 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-300"
                                        >Y</span
                                    >
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full bg-red-500/20 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-300"
                                        >Yes</span
                                    >
                                </td>
                                <td class="px-4 py-3">EER</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center rounded-full bg-red-500/20 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-300"
                                        >Major</span
                                    >
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    2024-03-11 16:20
                                </td>
                                <td
                                    class="px-4 py-3 text-center font-mono text-xs"
                                >
                                    3.8h
                                </td>
                                <td class="px-4 py-3">DRB Approval</td>
                                <td class="px-4 py-3">Mike Johnson</td>
                                <td class="px-4 py-3 text-center">
                                    <div
                                        class="flex items-center justify-center gap-2"
                                    >
                                        <button
                                            class="rounded-md border border-blue-600 bg-transparent px-2 py-1 text-xs font-medium text-blue-600 transition-colors hover:bg-blue-600 hover:text-white"
                                        >
                                            View
                                        </button>
                                        <button
                                            class="rounded-md border border-green-600 bg-transparent px-2 py-1 text-xs font-medium text-green-600 transition-colors hover:bg-green-600 hover:text-white"
                                        >
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
