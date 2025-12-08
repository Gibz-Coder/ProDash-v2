<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import ApexCharts from 'apexcharts';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: dashboard().url,
    },
    {
        title: 'WIP❕OUTPUT',
        href: '/process-wip',
    },
];

const audienceChartRef = ref<HTMLElement | null>(null);
const deviceChartRef = ref<HTMLElement | null>(null);

onMounted(() => {
    // Audience Metrics Chart
    if (audienceChartRef.value) {
        const audienceOptions = {
            series: [
                {
                    name: 'Views',
                    type: 'column',
                    data: [23, 11, 22, 27, 13, 22, 37, 21, 44, 22, 45, 35],
                },
                {
                    name: 'Followers',
                    type: 'line',
                    data: [44, 55, 41, 67, 22, 43, 21, 41, 56, 27, 43, 27],
                },
            ],
            chart: {
                toolbar: {
                    show: false,
                },
                type: 'line',
                height: 330,
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 3,
                padding: {
                    bottom: -10,
                },
            },
            colors: ['rgb(79, 70, 229)', 'rgb(255, 73, 205)'],
            labels: [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec',
            ],
            dataLabels: {
                enabled: false,
            },
            stroke: {
                width: [1, 1.1],
                curve: ['straight', 'smooth'],
                dashArray: [0, 2],
            },
            legend: {
                show: false,
            },
            xaxis: {
                axisBorder: {
                    color: '#e9e9e9',
                },
            },
            plotOptions: {
                bar: {
                    columnWidth: '30%',
                    borderRadius: 2,
                },
            },
        };

        const audienceChart = new ApexCharts(
            audienceChartRef.value,
            audienceOptions,
        );
        audienceChart.render();
    }

    // Sessions by Device Chart
    if (deviceChartRef.value) {
        const deviceOptions = {
            series: [1754, 1234, 878],
            labels: ['Mobile', 'Tablet', 'Desktop'],
            chart: {
                height: 260,
                type: 'donut',
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                show: false,
            },
            stroke: {
                show: true,
                curve: 'smooth',
                lineCap: 'round',
                colors: '#fff',
                width: 0,
                dashArray: 0,
            },
            plotOptions: {
                pie: {
                    expandOnClick: false,
                    donut: {
                        size: '85%',
                        background: 'transparent',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '20px',
                                color: '#495057',
                                fontFamily: 'Montserrat, sans-serif',
                                offsetY: -5,
                            },
                            value: {
                                show: true,
                                fontSize: '22px',
                                color: undefined,
                                offsetY: 5,
                                fontWeight: 600,
                                fontFamily: 'Montserrat, sans-serif',
                                formatter: function (val: string) {
                                    return val + '%';
                                },
                            },
                            total: {
                                show: true,
                                showAlways: true,
                                label: 'Total Audience',
                                fontSize: '14px',
                                fontWeight: 400,
                                color: '#495057',
                            },
                        },
                    },
                },
            },
            colors: ['rgb(79, 70, 229)', 'rgb(255, 73, 205)', 'rgb(253, 175, 34)'],
        };

        const deviceChart = new ApexCharts(
            deviceChartRef.value,
            deviceOptions,
        );
        deviceChart.render();
    }
});
</script>

<template>
    <Head title="WIP❕OUTPUT" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
            <!-- Start:: row-1 -->
            <div class="grid grid-cols-1 gap-4">
            <div class="grid grid-cols-1 2xl:grid-cols-12 gap-4">
                <div class="2xl:col-span-9">
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Stats Cards -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 2xl:grid-cols-4 gap-4">
                            <!-- Total Users Card -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                <div class="flex items-start gap-3">
                                    <div>
                                        <span class="flex items-center justify-center w-12 h-12 rounded-lg bg-blue-500 text-white">
                                            📊
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-500 dark:text-gray-400">Normal Lots</span>
                                        <h5 class="text-xl font-semibold mb-1">2,643 Mpcs</h5>
                                        <div class="text-gray-500 text-xs">
                                            <span class="text-green-500">
                                                1,130
                                            </span>
                                            lot counts
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Sessions Card -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                <div class="flex items-start gap-3">
                                    <div>
                                        <span class="flex items-center justify-center w-12 h-12 rounded-lg bg-gray-500 text-white">
                                            ♻️
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-500 dark:text-gray-400">Process Rework</span>
                                        <h5 class="text-xl font-semibold mb-1">140 Mpcs</h5>
                                        <div class="text-gray-500 text-xs">
                                            <span class="text-green-500">
                                                230
                                            </span>
                                            Lots
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bounce Rate Card -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                <div class="flex items-start gap-3">
                                    <div>
                                        <span class="flex items-center justify-center w-12 h-12 rounded-lg bg-green-500 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M7.5,11C9.43,11,11,9.43,11,7.5S9.43,4,7.5,4S4,5.57,4,7.5S5.57,11,7.5,11z M7.5,6C8.33,6,9,6.67,9,7.5S8.33,9,7.5,9 S6,8.33,6,7.5S6.67,6,7.5,6z"/>
                                                <rect height="2" transform="matrix(0.7071 -0.7071 0.7071 0.7071 -4.9706 12)" width="20.63" x="1.69" y="11"/>
                                                <path d="M16.5,13c-1.93,0-3.5,1.57-3.5,3.5s1.57,3.5,3.5,3.5s3.5-1.57,3.5-3.5S18.43,13,16.5,13z M16.5,18 c-0.83,0-1.5-0.67-1.5-1.5s0.67-1.5,1.5-1.5s1.5,0.67,1.5,1.5S17.33,18,16.5,18z"/>
                                            </svg>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-500 dark:text-gray-400">Warehouse</span>
                                        <h5 class="text-xl font-semibold mb-1">240 Mpcs</h5>
                                        <div class="text-gray-500 text-xs">
                                            <span class="text-green-500">
                                                180
                                            </span>
                                            Lots
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Avg Session Duration Card -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                <div class="flex items-start gap-3">
                                    <div>
                                        <span class="flex items-center justify-center w-12 h-12 rounded-lg bg-yellow-500 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                                                <rect height="2" width="6" x="9" y="1"/>
                                                <path d="M19.03,7.39l1.42-1.42c-0.43-0.51-0.9-0.99-1.41-1.41l-1.42,1.42C16.07,4.74,14.12,4,12,4c-4.97,0-9,4.03-9,9 c0,4.97,4.02,9,9,9s9-4.03,9-9C21,10.88,20.26,8.93,19.03,7.39z M13,14h-2V8h2V14z"/>
                                            </svg>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-500 dark:text-gray-400">Outgoing NG</span>
                                        <h5 class="text-xl font-semibold mb-1">40 Mpcs</h5>
                                        <div class="text-gray-500 text-xs">
                                            <span class="text-green-500">
                                                30
                                            </span>
                                            Lots
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sessions By Device & Audience Metrics -->
                        <div class="grid grid-cols-1 2xl:grid-cols-12 gap-4">
                            <!-- Sessions By Device -->
                            <div class="2xl:col-span-4">
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                                        <div class="font-semibold">Sessions By Device</div>
                                        <div class="relative">
                                            <button class="px-2 py-1 text-xs text-gray-500 hover:text-gray-700">
                                                View All
                                                <svg class="inline w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <div ref="deviceChartRef"></div>
                                    </div>
                                    <div class="p-0">
                                        <div class="grid grid-cols-3">
                                            <div class="p-3 text-center border-r border-gray-200 dark:border-gray-700">
                                                <h5 class="text-lg font-semibold mb-1">1754</h5>
                                                <span class="text-xs block">Mobile</span>
                                            </div>
                                            <div class="p-3 text-center border-r border-gray-200 dark:border-gray-700">
                                                <h5 class="text-lg font-semibold mb-1">1234</h5>
                                                <span class="text-xs block">Tablet</span>
                                            </div>
                                            <div class="p-3 text-center">
                                                <h5 class="text-lg font-semibold mb-1">878</h5>
                                                <span class="text-xs block">Desktop</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Audience Metrics -->
                            <div class="2xl:col-span-8">
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                        <div class="font-semibold">Audience Metrics</div>
                                    </div>
                                    <div class="p-4">
                                        <div ref="audienceChartRef"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Visitors By Countries & Top Campaigns -->
                        <div class="grid grid-cols-1 2xl:grid-cols-12 gap-4">
                            <!-- Visitors By Countries -->
                            <div class="2xl:col-span-4">
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                        <div class="font-semibold">Visitors By Countries</div>
                                    </div>
                                    <div class="p-0">
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                        <th class="px-4 py-3 text-left">S.No</th>
                                                        <th class="px-4 py-3 text-left">Country</th>
                                                        <th class="px-4 py-3 text-left">Visitors</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                        <td class="px-4 py-3">1</td>
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center gap-2">
                                                                <div>
                                                                    <span class="inline-block w-6 h-6 rounded overflow-hidden">
                                                                        <img src="/assets/images/flags/us_flag.jpg" alt="" class="w-full h-full object-cover">
                                                                    </span>
                                                                </div>
                                                                <div class="font-semibold">USA</div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <span class="text-xs text-gray-500 mr-2">
                                                                (<svg class="inline w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                                                </svg>2.15%)
                                                            </span>45,234
                                                        </td>
                                                    </tr>
                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                        <td class="px-4 py-3">2</td>
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center gap-2">
                                                                <div>
                                                                    <span class="inline-block w-6 h-6 rounded overflow-hidden">
                                                                        <img src="/assets/images/flags/argentina_flag.jpg" alt="" class="w-full h-full object-cover">
                                                                    </span>
                                                                </div>
                                                                <div class="font-semibold">Argentina</div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <span class="text-xs text-gray-500 mr-2">
                                                                (<svg class="inline w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                                                </svg>1.62%)
                                                            </span>12,234
                                                        </td>
                                                    </tr>
                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                        <td class="px-4 py-3">3</td>
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center gap-2">
                                                                <div>
                                                                    <span class="inline-block w-6 h-6 rounded overflow-hidden">
                                                                        <img src="/assets/images/flags/italy_flag.jpg" alt="" class="w-full h-full object-cover">
                                                                    </span>
                                                                </div>
                                                                <div class="font-semibold">Italy</div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <span class="text-xs text-gray-500 mr-2">
                                                                (<svg class="inline w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                                                </svg>0.85%)
                                                            </span>7,234
                                                        </td>
                                                    </tr>
                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                        <td class="px-4 py-3">4</td>
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center gap-2">
                                                                <div>
                                                                    <span class="inline-block w-6 h-6 rounded overflow-hidden">
                                                                        <img src="/assets/images/flags/russia_flag.jpg" alt="" class="w-full h-full object-cover">
                                                                    </span>
                                                                </div>
                                                                <div class="font-semibold">Russia</div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <span class="text-xs text-gray-500 mr-2">
                                                                (<svg class="inline w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                                                </svg>3.51%)
                                                            </span>3,543
                                                        </td>
                                                    </tr>
                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                        <td class="px-4 py-3">5</td>
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center gap-2">
                                                                <div>
                                                                    <span class="inline-block w-6 h-6 rounded overflow-hidden">
                                                                        <img src="/assets/images/flags/spain_flag.jpg" alt="" class="w-full h-full object-cover">
                                                                    </span>
                                                                </div>
                                                                <div class="font-semibold">Spain</div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <span class="text-xs text-gray-500 mr-2">
                                                                (<svg class="inline w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                                                </svg>0.56%)
                                                            </span>2,463
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="px-4 py-3">6</td>
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center gap-2">
                                                                <div>
                                                                    <span class="inline-block w-6 h-6 rounded overflow-hidden">
                                                                        <img src="/assets/images/flags/uae_flag.jpg" alt="" class="w-full h-full object-cover">
                                                                    </span>
                                                                </div>
                                                                <div class="font-semibold">UAE</div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <span class="text-xs text-gray-500 mr-2">
                                                                (<svg class="inline w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                                                </svg>1.92%)
                                                            </span>1,832
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Top Campaigns -->
                            <div class="2xl:col-span-8">
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                                        <div class="font-semibold">Top Campaigns</div>
                                        <div class="relative">
                                            <button class="px-2 py-1 text-xs text-gray-500 hover:text-gray-700">
                                                View All
                                                <svg class="inline w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="p-0">
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                        <th class="px-4 py-3 text-left">Provider</th>
                                                        <th class="px-4 py-3 text-left">Sales</th>
                                                        <th class="px-4 py-3 text-left">Goal</th>
                                                        <th class="px-4 py-3 text-left">Status</th>
                                                        <th class="px-4 py-3 text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden bg-gray-100 p-1">
                                                                    <img src="/assets/images/faces/1.jpg" alt="" class="w-full h-full object-cover rounded-full">
                                                                </span>
                                                                <div>
                                                                    <p class="font-medium mb-0">Leo Phillips</p>
                                                                    <span class="text-xs text-gray-500">Influencer</span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">$12,465</td>
                                                        <td class="px-4 py-3"><span class="text-blue-500">23.3%</span></td>
                                                        <td class="px-4 py-3">
                                                            <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-600">On process</span>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <div class="flex gap-1 justify-center">
                                                                <button class="p-1 hover:bg-gray-100 rounded">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                                    </svg>
                                                                </button>
                                                                <button class="p-1 hover:bg-gray-100 rounded">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden bg-gray-100 p-1">
                                                                    <img src="/assets/images/faces/2.jpg" alt="" class="w-full h-full object-cover rounded-full">
                                                                </span>
                                                                <div>
                                                                    <p class="font-medium mb-0">Noah Russell</p>
                                                                    <span class="text-xs text-gray-500">Influencer</span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">$3,576</td>
                                                        <td class="px-4 py-3"><span class="text-gray-500">19.4%</span></td>
                                                        <td class="px-4 py-3">
                                                            <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-600">Achieved</span>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <div class="flex gap-1 justify-center">
                                                                <button class="p-1 hover:bg-gray-100 rounded">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                                    </svg>
                                                                </button>
                                                                <button class="p-1 hover:bg-gray-100 rounded">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden bg-gray-100 p-1">
                                                                    <img src="/assets/images/faces/3.jpg" alt="" class="w-full h-full object-cover rounded-full">
                                                                </span>
                                                                <div>
                                                                    <p class="font-medium mb-0">Henry Morgan</p>
                                                                    <span class="text-xs text-gray-500">Youtuber</span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">$12,764</td>
                                                        <td class="px-4 py-3"><span class="text-green-500">12.76%</span></td>
                                                        <td class="px-4 py-3">
                                                            <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-600">On Process</span>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <div class="flex gap-1 justify-center">
                                                                <button class="p-1 hover:bg-gray-100 rounded">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                                    </svg>
                                                                </button>
                                                                <button class="p-1 hover:bg-gray-100 rounded">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden bg-gray-100 p-1">
                                                                    <img src="/assets/images/faces/4.jpg" alt="" class="w-full h-full object-cover rounded-full">
                                                                </span>
                                                                <div>
                                                                    <p class="font-medium mb-0">Ava Taylor</p>
                                                                    <span class="text-xs text-gray-500">Content Creator</span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">$13,864</td>
                                                        <td class="px-4 py-3"><span class="text-yellow-500">16.78%</span></td>
                                                        <td class="px-4 py-3">
                                                            <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-600">Achieved</span>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <div class="flex gap-1 justify-center">
                                                                <button class="p-1 hover:bg-gray-100 rounded">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                                    </svg>
                                                                </button>
                                                                <button class="p-1 hover:bg-gray-100 rounded">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center gap-2">
                                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden bg-gray-100 p-1">
                                                                    <img src="/assets/images/faces/5.jpg" alt="" class="w-full h-full object-cover rounded-full">
                                                                </span>
                                                                <div>
                                                                    <p class="font-medium mb-0">Liam Parker</p>
                                                                    <span class="text-xs text-gray-500">Youtuber</span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">$9,756</td>
                                                        <td class="px-4 py-3"><span class="text-blue-500">6.13%</span></td>
                                                        <td class="px-4 py-3">
                                                            <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-600">Achieved</span>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <div class="flex gap-1 justify-center">
                                                                <button class="p-1 hover:bg-gray-100 rounded">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                                    </svg>
                                                                </button>
                                                                <button class="p-1 hover:bg-gray-100 rounded">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Engagement Metrics Table -->
                        <div class="grid grid-cols-1">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                                    <div class="font-semibold">Engagement Metrics</div>
                                    <div class="flex flex-wrap gap-2">
                                        <div>
                                            <input class="px-3 py-1 text-sm border border-gray-300 rounded" type="text" placeholder="Search Here">
                                        </div>
                                        <div class="relative">
                                            <button class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600">
                                                Sort By
                                                <svg class="inline w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-0">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                                    <th class="px-4 py-3 text-center">S.No</th>
                                                    <th class="px-4 py-3 text-left">User</th>
                                                    <th class="px-4 py-3 text-left">Sessions</th>
                                                    <th class="px-4 py-3 text-left">Country</th>
                                                    <th class="px-4 py-3 text-left">Page Views</th>
                                                    <th class="px-4 py-3 text-left">Bounce Rate</th>
                                                    <th class="px-4 py-3 text-center">Conversion Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                                    <td class="px-4 py-3 text-center">1</td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            <div>
                                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden">
                                                                    <img src="/assets/images/faces/12.jpg" alt="" class="w-full h-full object-cover">
                                                                </span>
                                                            </div>
                                                            <div>John Doe</div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">120</td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            <div>
                                                                <span class="inline-block w-6 h-6 rounded overflow-hidden">
                                                                    <img src="/assets/images/flags/us_flag.jpg" alt="" class="w-full h-full object-cover">
                                                                </span>
                                                            </div>
                                                            <div>United States</div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">350</td>
                                                    <td class="px-4 py-3">45%</td>
                                                    <td class="px-4 py-3 text-center">5.2%</td>
                                                </tr>
                                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                                    <td class="px-4 py-3 text-center">2</td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            <div>
                                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden">
                                                                    <img src="/assets/images/faces/1.jpg" alt="" class="w-full h-full object-cover">
                                                                </span>
                                                            </div>
                                                            <div>Jane Smith</div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">95</td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            <div>
                                                                <span class="inline-block w-6 h-6 rounded overflow-hidden">
                                                                    <img src="/assets/images/flags/germany_flag.jpg" alt="" class="w-full h-full object-cover">
                                                                </span>
                                                            </div>
                                                            <div>Germany</div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">240</td>
                                                    <td class="px-4 py-3">38%</td>
                                                    <td class="px-4 py-3 text-center">6.8%</td>
                                                </tr>
                                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                                    <td class="px-4 py-3 text-center">3</td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            <div>
                                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden">
                                                                    <img src="/assets/images/faces/14.jpg" alt="" class="w-full h-full object-cover">
                                                                </span>
                                                            </div>
                                                            <div>Chris Johnson</div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">110</td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            <div>
                                                                <span class="inline-block w-6 h-6 rounded overflow-hidden">
                                                                    <img src="/assets/images/flags/canada_flag.jpg" alt="" class="w-full h-full object-cover">
                                                                </span>
                                                            </div>
                                                            <div>Canada</div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">290</td>
                                                    <td class="px-4 py-3">40%</td>
                                                    <td class="px-4 py-3 text-center">4.5%</td>
                                                </tr>
                                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                                    <td class="px-4 py-3 text-center">4</td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            <div>
                                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden">
                                                                    <img src="/assets/images/faces/4.jpg" alt="" class="w-full h-full object-cover">
                                                                </span>
                                                            </div>
                                                            <div>Emily Davis</div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">75</td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            <div>
                                                                <span class="inline-block w-6 h-6 rounded overflow-hidden">
                                                                    <img src="/assets/images/flags/argentina_flag.jpg" alt="" class="w-full h-full object-cover">
                                                                </span>
                                                            </div>
                                                            <div>Argentina</div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">200</td>
                                                    <td class="px-4 py-3">50%</td>
                                                    <td class="px-4 py-3 text-center">3.8%</td>
                                                </tr>
                                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                                    <td class="px-4 py-3 text-center">5</td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            <div>
                                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden">
                                                                    <img src="/assets/images/faces/15.jpg" alt="" class="w-full h-full object-cover">
                                                                </span>
                                                            </div>
                                                            <div>Michael Brown</div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">135</td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            <div>
                                                                <span class="inline-block w-6 h-6 rounded overflow-hidden">
                                                                    <img src="/assets/images/flags/india_flag.jpg" alt="" class="w-full h-full object-cover">
                                                                </span>
                                                            </div>
                                                            <div>India</div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">400</td>
                                                    <td class="px-4 py-3">30%</td>
                                                    <td class="px-4 py-3 text-center">7.1%</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="border-t-0 p-4">
                                    <div class="flex items-center flex-wrap justify-between">
                                        <div>Showing 5 Entries <svg class="inline w-4 h-4 ml-2 font-semibold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></div>
                                        <div class="ml-auto">
                                            <nav>
                                                <ul class="flex gap-1">
                                                    <li><a class="px-3 py-1 border rounded text-gray-400 cursor-not-allowed" href="javascript:void(0);">Prev</a></li>
                                                    <li><a class="px-3 py-1 border rounded bg-blue-500 text-white" href="javascript:void(0);">1</a></li>
                                                    <li><a class="px-3 py-1 border rounded hover:bg-gray-100" href="javascript:void(0);">2</a></li>
                                                    <li><a class="px-3 py-1 border rounded text-blue-500 hover:bg-gray-100" href="javascript:void(0);">next</a></li>
                                                </ul>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Browser Insights Sidebar -->
                <div class="2xl:col-span-3">
                    <div class="grid grid-cols-1 gap-4">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <div class="font-semibold">WIP per Size</div>
                            </div>
                            <div class="p-4">
                                <ul class="space-y-4">
                                    <li>
                                        <div class="flex items-start gap-3">
                                            <div>
                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden">
                                                    💢
                                                </span>
                                            </div>
                                            <div class="flex-1">
                                                <span class="font-medium">0603</span>
                                                <span class="block text-gray-500 text-xs">Ongoing MC</span>
                                            </div>
                                            <div class="text-right w-1/4">
                                                <span class="block mb-1 font-semibold">1,215</span>
                                                <div class="w-3/4 ml-auto bg-gray-200 rounded-full h-1.5">
                                                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: 78%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-start gap-3">
                                            <div>
                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden">
                                                    ❌
                                                </span>
                                            </div>
                                            <div class="flex-1">
                                                <span class="font-medium">1005</span>
                                                <span class="block text-gray-500 text-xs">Ongoing MC</span>
                                            </div>
                                            <div class="text-right w-1/4">
                                                <span class="block mb-1 font-semibold">978</span>
                                                <div class="w-3/4 ml-auto bg-gray-200 rounded-full h-1.5">
                                                    <div class="bg-gray-500 h-1.5 rounded-full" style="width: 72%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-start gap-3">
                                            <div>
                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden">
                                                    🧩
                                                </span>
                                            </div>
                                            <div class="flex-1">
                                                <span class="font-medium">1608</span>
                                                <span class="block text-gray-500 text-xs">Ongoing MC</span>
                                            </div>
                                            <div class="text-right w-1/4">
                                                <span class="block mb-1 font-semibold">815</span>
                                                <div class="w-3/4 ml-auto bg-gray-200 rounded-full h-1.5">
                                                    <div class="bg-yellow-500 h-1.5 rounded-full" style="width: 64%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-start gap-3">
                                            <div>
                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden">
                                                    🧬
                                                </span>
                                            </div>
                                            <div class="flex-1">
                                                <span class="font-medium">2012</span>
                                                <span class="block text-gray-500 text-xs">Ongoing MC</span>
                                            </div>
                                            <div class="text-right w-1/4">
                                                <span class="block mb-1 font-semibold">1,347</span>
                                                <div class="w-3/4 ml-auto bg-gray-200 rounded-full h-1.5">
                                                    <div class="bg-red-500 h-1.5 rounded-full" style="width: 85%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-start gap-3">
                                            <div>
                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden">
                                                    ⛓️‍💥
                                                </span>
                                            </div>
                                            <div class="flex-1">
                                                <span class="font-medium">3216</span>
                                                <span class="block text-gray-500 text-xs">Ongoing MC</span>
                                            </div>
                                            <div class="text-right w-1/4">
                                                <span class="block mb-1 font-semibold">1123</span>
                                                <div class="w-3/4 ml-auto bg-gray-200 rounded-full h-1.5">
                                                    <div class="bg-blue-400 h-1.5 rounded-full" style="width: 70%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-start gap-3">
                                            <div>
                                                <span class="inline-block w-8 h-8 rounded-full overflow-hidden">
                                                    🚀
                                                </span>
                                            </div>
                                            <div class="flex-1">
                                                <span class="font-medium">3225</span>
                                                <span class="block text-gray-500 text-xs">Ongoing MC</span>
                                            </div>
                                            <div class="text-right w-1/4">
                                                <span class="block mb-1 font-semibold">1189</span>
                                                <div class="w-3/4 ml-auto bg-gray-200 rounded-full h-1.5">
                                                    <div class="bg-orange-500 h-1.5 rounded-full" style="width: 74%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Users By Time Of Week -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <div class="font-semibold">Users By Time Of Week</div>
                            </div>
                            <div class="p-4">
                                <div class="h-64 flex items-center justify-center text-gray-400">
                                    [Heatmap: Users By Time]
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            <!-- End:: row-1 -->
        </div>
    </AppLayout>
</template>
