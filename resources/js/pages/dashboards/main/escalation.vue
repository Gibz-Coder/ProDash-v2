<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: dashboard().url,
    },
    {
        title: 'MLCC Visual Management System (Machine Escalation Reporting)',
        href: '/escalation',
    },
];

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
    { name: 'Equipment', value: 249, color: '#22c55e' },
    { name: 'Technical', value: 180, color: '#3b82f6' },
    { name: 'AI', value: 243, color: '#14b8a6' },
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
    { name: 'GMC-G1', value: 168, color: '#22c55e' },
    { name: 'GMC-G3', value: 281, color: '#f97316' },
    { name: 'GMC-G20', value: 107, color: '#22c55e' },
    { name: 'TWA', value: 85, color: '#eab308' },
    { name: 'Wintec', value: 14, color: '#22c55e' },
]);

const perLine = ref([
    { name: 'A', value: 153, color: '#22c55e' },
    { name: 'B', value: 114, color: '#f97316' },
    { name: 'C', value: 85, color: '#22c55e' },
    { name: 'D', value: 79, color: '#f97316' },
    { name: 'E', value: 71, color: '#3b82f6' },
    { name: 'F', value: 17, color: '#22c55e' },
    { name: 'G', value: 81, color: '#22c55e' },
    { name: 'H', value: 28, color: '#9ca3af' },
    { name: 'I', value: 11, color: '#9ca3af' },
    { name: 'J', value: 3, color: '#9ca3af' },
    { name: 'K', value: 6, color: '#9ca3af' },
]);

const escalationReports = ref([
    {
        no: 1,
        week: 47,
        wallTime: '00:00:00',
        team: 'Equipment',
        line: 'D',
        mcType: 'GMC-G3',
        mcNo: 'VJ489',
        mcStatus: 'Run',
        modelId: '058104',
        lotId: 'EL99PRO',
        category: 'Ejecter Unit',
        problemPart: 'Eject Binning',
        problemDetails: 'eject binning',
        requestor: 'MARQUEZ, FROILAN LEVI',
        dateTime: '2025-11-18 07:53:30',
    },
    {
        no: 2,
        week: 47,
        wallTime: '00:37:52',
        team: 'Equipment',
        line: 'D',
        mcType: 'GMC-G3',
        mcNo: 'VJ489',
        mcStatus: 'Run',
        modelId: '058104',
        lotId: 'EL99PRO',
        category: 'Ejecter Unit',
        problemPart: 'Eject Binning',
        problemDetails: 'eject binning',
        requestor: 'ROLLE, JUSTIN NAVARRO',
        dateTime: '2025-11-18 07:53:30',
    },
    {
        no: 3,
        week: 47,
        wallTime: '00:06:12',
        team: 'AI',
        line: 'D',
        mcType: 'GMC-G3',
        mcNo: 'VJ528',
        mcStatus: 'Run',
        modelId: '058108',
        lotId: 'HLAVPTG',
        category: 'Inspection Result',
        problemPart: 'High NG Rate',
        problemDetails: 'high ng rate',
        requestor: 'CARRASCAL, ALMIRA ALFEO',
        dateTime: '2025-11-18 08:35:30',
    },
]);

// Computed values for charts
const monthlyMax = computed(() =>
    Math.max(...monthlyTrend.value.map((m) => m.value)),
);
const weeklyMax = computed(() =>
    Math.max(...weeklyTrend.value.map((w) => w.value)),
);
const dailyMax = computed(() =>
    Math.max(...dailyTrend.value.map((d) => d.value)),
);
const machineMakerMax = computed(() =>
    Math.max(...perMachineMaker.value.map((m) => m.value)),
);
const perLineMax = computed(() =>
    Math.max(...perLine.value.map((l) => l.value)),
);
const perTeamMax = computed(() =>
    Math.max(...perTeam.value.map((t) => t.value)),
);
const perCategoryMax = computed(() =>
    Math.max(...perCategory.value.map((c) => c.value)),
);
</script>

<template>
    <Head title="Machine Escalation Reporting" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-auto bg-gray-50 p-4 dark:bg-gray-950"
        >
            <!-- Row 1: Overall Status, Monthly Trend, Weekly Trend, Daily Trend -->
            <div class="grid grid-cols-12 gap-4">
                <!-- Overall Completion Status -->
                <div
                    class="col-span-12 rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-3 dark:border-gray-700 dark:bg-gray-900"
                >
                    <h3
                        class="text-base font-semibold text-gray-800 dark:text-gray-200"
                    >
                        Overall Completion Status
                    </h3>
                    <div class="mt-4 flex items-center justify-center">
                        <div class="relative h-28 w-28">
                            <svg
                                class="h-full w-full -rotate-90 transform"
                                viewBox="0 0 100 100"
                            >
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="42"
                                    stroke="currentColor"
                                    stroke-width="8"
                                    fill="none"
                                    class="text-gray-200 dark:text-gray-700"
                                />
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="42"
                                    stroke="currentColor"
                                    stroke-width="8"
                                    fill="none"
                                    stroke-linecap="round"
                                    :stroke-dasharray="`${overallStatus.progress * 2.64} 264`"
                                    class="text-teal-500"
                                />
                            </svg>
                            <div
                                class="absolute inset-0 flex flex-col items-center justify-center"
                            >
                                <span
                                    class="text-3xl font-bold text-teal-500"
                                    >{{ overallStatus.progress }}</span
                                >
                                <span class="text-xs text-gray-500"
                                    >Progress</span
                                >
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Total Report:</span>
                            <span
                                class="font-bold text-gray-800 dark:text-gray-200"
                                >{{ overallStatus.totalReport }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Completed:</span>
                            <span class="font-bold text-teal-600">{{
                                overallStatus.completed
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Pending:</span>
                            <span class="font-bold text-orange-500">{{
                                overallStatus.pending
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trend -->
                <div
                    class="col-span-12 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:col-span-6 lg:col-span-2 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="mb-3 flex items-center gap-1.5">
                        <span class="text-lg text-teal-500">◆</span>
                        <span class="text-sm font-semibold text-teal-600"
                            >Monthly Trend</span
                        >
                    </div>
                    <div class="flex h-28 items-end justify-around">
                        <div
                            v-for="item in monthlyTrend"
                            :key="item.month"
                            class="flex flex-col items-center"
                        >
                            <span
                                class="mb-1 text-xs font-bold text-gray-700 dark:text-gray-300"
                                >{{ item.value }}</span
                            >
                            <div
                                class="w-12 rounded-t-sm bg-gradient-to-t from-blue-600 to-blue-400"
                                :style="{
                                    height: `${Math.max((item.value / monthlyMax) * 70, 8)}px`,
                                }"
                            ></div>
                            <span class="mt-2 text-xs text-gray-500">{{
                                item.month
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Weekly Trend -->
                <div
                    class="col-span-12 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:col-span-6 lg:col-span-2 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="mb-3 flex items-center gap-1.5">
                        <span class="text-lg text-teal-500">◆</span>
                        <span class="text-sm font-semibold text-teal-600"
                            >Weekly Trend</span
                        >
                    </div>
                    <div class="flex h-28 items-end justify-around">
                        <div
                            v-for="item in weeklyTrend"
                            :key="item.week"
                            class="flex flex-col items-center"
                        >
                            <span
                                class="mb-1 text-xs font-bold text-gray-700 dark:text-gray-300"
                                >{{ item.value }}</span
                            >
                            <div
                                class="w-12 rounded-t-sm bg-gradient-to-t from-blue-600 to-blue-400"
                                :style="{
                                    height: `${Math.max((item.value / weeklyMax) * 70, 8)}px`,
                                }"
                            ></div>
                            <span class="mt-2 text-xs text-gray-500">{{
                                item.week
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Daily Trend -->
                <div
                    class="col-span-12 rounded-xl border border-gray-200 bg-white p-4 shadow-sm lg:col-span-5 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="mb-2 flex items-center gap-1.5">
                        <span class="text-lg text-teal-500">◆</span>
                        <span class="text-sm font-semibold text-teal-600"
                            >Daily Trend</span
                        >
                    </div>
                    <div class="relative h-28">
                        <svg
                            class="h-full w-full"
                            viewBox="0 0 320 90"
                            preserveAspectRatio="none"
                        >
                            <!-- Y-axis labels -->
                            <text
                                x="8"
                                y="12"
                                font-size="8"
                                class="fill-gray-400"
                            >
                                50
                            </text>
                            <text
                                x="8"
                                y="45"
                                font-size="8"
                                class="fill-gray-400"
                            >
                                25
                            </text>
                            <text
                                x="8"
                                y="78"
                                font-size="8"
                                class="fill-gray-400"
                            >
                                0
                            </text>

                            <!-- Grid lines -->
                            <line
                                x1="25"
                                y1="10"
                                x2="310"
                                y2="10"
                                stroke="#e5e7eb"
                                stroke-width="0.5"
                                stroke-dasharray="3"
                            />
                            <line
                                x1="25"
                                y1="42"
                                x2="310"
                                y2="42"
                                stroke="#e5e7eb"
                                stroke-width="0.5"
                                stroke-dasharray="3"
                            />
                            <line
                                x1="25"
                                y1="74"
                                x2="310"
                                y2="74"
                                stroke="#e5e7eb"
                                stroke-width="0.5"
                                stroke-dasharray="3"
                            />

                            <!-- Area fill gradient -->
                            <defs>
                                <linearGradient
                                    id="dailyAreaGradient"
                                    x1="0%"
                                    y1="0%"
                                    x2="0%"
                                    y2="100%"
                                >
                                    <stop
                                        offset="0%"
                                        stop-color="#f97316"
                                        stop-opacity="0.25"
                                    />
                                    <stop
                                        offset="100%"
                                        stop-color="#f97316"
                                        stop-opacity="0.02"
                                    />
                                </linearGradient>
                            </defs>

                            <!-- Area path -->
                            <path
                                :d="`M 40 ${74 - (dailyTrend[0].value / 50) * 60} ${dailyTrend.map((item, i) => `L ${40 + i * 45} ${74 - (item.value / 50) * 60}`).join(' ')} L ${40 + (dailyTrend.length - 1) * 45} 74 L 40 74 Z`"
                                fill="url(#dailyAreaGradient)"
                            />

                            <!-- Line -->
                            <polyline
                                :points="
                                    dailyTrend
                                        .map(
                                            (item, i) =>
                                                `${40 + i * 45},${74 - (item.value / 50) * 60}`,
                                        )
                                        .join(' ')
                                "
                                fill="none"
                                stroke="#14b8a6"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />

                            <!-- Points and labels -->
                            <g v-for="(item, i) in dailyTrend" :key="i">
                                <circle
                                    :cx="40 + i * 45"
                                    :cy="74 - (item.value / 50) * 60"
                                    r="4"
                                    fill="#fff"
                                    stroke="#14b8a6"
                                    stroke-width="2"
                                />
                                <text
                                    :x="40 + i * 45"
                                    :y="74 - (item.value / 50) * 60 - 8"
                                    text-anchor="middle"
                                    font-size="9"
                                    font-weight="600"
                                    class="fill-gray-700 dark:fill-gray-300"
                                >
                                    {{ item.value }}
                                </text>
                            </g>
                        </svg>
                    </div>
                    <div
                        class="mt-1 flex justify-between px-6 text-[10px] text-gray-500"
                    >
                        <span v-for="item in dailyTrend" :key="item.date">{{
                            item.date
                        }}</span>
                    </div>
                </div>
            </div>

            <!-- Row 2: Per Team, Per Category, Per Machine Maker, Per Line -->
            <div class="grid grid-cols-12 gap-4">
                <!-- Per Team -->
                <div
                    class="col-span-12 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:col-span-6 lg:col-span-2 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="mb-3 flex items-center gap-1.5">
                        <span class="text-lg text-teal-500">◆</span>
                        <span class="text-sm font-semibold text-teal-600"
                            >Per Team</span
                        >
                    </div>
                    <div class="space-y-3">
                        <div
                            v-for="team in perTeam"
                            :key="team.name"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="w-16 text-xs text-gray-600 dark:text-gray-400"
                                >{{ team.name }}</span
                            >
                            <div
                                class="relative h-6 flex-1 overflow-hidden rounded bg-gray-100 dark:bg-gray-700"
                            >
                                <div
                                    class="h-full rounded transition-all"
                                    :style="{
                                        width: `${(team.value / perTeamMax) * 100}%`,
                                        backgroundColor: team.color,
                                    }"
                                ></div>
                                <span
                                    class="absolute inset-0 flex items-center justify-center text-xs font-bold text-white drop-shadow-sm"
                                    >{{ team.value }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Per Category -->
                <div
                    class="col-span-12 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:col-span-6 lg:col-span-3 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="mb-2 flex items-center gap-1.5">
                        <span class="text-lg text-teal-500">◆</span>
                        <span class="text-sm font-semibold text-teal-600"
                            >Per Category</span
                        >
                    </div>
                    <div class="space-y-1 text-[11px]">
                        <div
                            v-for="cat in perCategory"
                            :key="cat.name"
                            class="flex items-center justify-between gap-2"
                        >
                            <span
                                class="w-24 truncate text-gray-600 dark:text-gray-400"
                                >{{ cat.name }}</span
                            >
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-2.5 w-20 overflow-hidden rounded-sm bg-gray-100 dark:bg-gray-700"
                                >
                                    <div
                                        class="h-full rounded-sm bg-teal-400 transition-all"
                                        :style="{
                                            width: `${(cat.value / perCategoryMax) * 100}%`,
                                        }"
                                    ></div>
                                </div>
                                <span
                                    class="w-7 text-right font-semibold text-gray-700 dark:text-gray-300"
                                    >{{ cat.value }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Per Machine Maker -->
                <div
                    class="col-span-12 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:col-span-6 lg:col-span-3 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="mb-2 flex items-center gap-1.5">
                        <span class="text-lg text-teal-500">◆</span>
                        <span class="text-sm font-semibold text-teal-600"
                            >Per Machine Maker</span
                        >
                    </div>
                    <div class="flex h-28 items-end justify-around pt-2">
                        <div
                            v-for="maker in perMachineMaker"
                            :key="maker.name"
                            class="flex flex-col items-center"
                        >
                            <span
                                class="mb-1 text-xs font-bold text-gray-700 dark:text-gray-300"
                                >{{ maker.value }}</span
                            >
                            <div
                                class="w-10 rounded-t-sm transition-all"
                                :style="{
                                    height: `${Math.max((maker.value / machineMakerMax) * 65, 6)}px`,
                                    backgroundColor: maker.color,
                                }"
                            ></div>
                            <span class="mt-1.5 text-[9px] text-gray-500">{{
                                maker.name
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Per Line -->
                <div
                    class="col-span-12 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:col-span-6 lg:col-span-4 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="mb-2 flex items-center gap-1.5">
                        <span class="text-lg text-teal-500">◆</span>
                        <span class="text-sm font-semibold text-teal-600"
                            >Per Line</span
                        >
                    </div>
                    <div class="flex h-28 items-end justify-around pt-2">
                        <div
                            v-for="line in perLine"
                            :key="line.name"
                            class="flex flex-col items-center"
                        >
                            <span
                                class="mb-0.5 text-[10px] font-bold text-gray-700 dark:text-gray-300"
                                >{{ line.value }}</span
                            >
                            <div
                                class="w-5 rounded-t-sm transition-all"
                                :style="{
                                    height: `${Math.max((line.value / perLineMax) * 60, 3)}px`,
                                    backgroundColor: line.color,
                                }"
                            ></div>
                            <span class="mt-1 text-[9px] text-gray-500">{{
                                line.name
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Escalation Report Table -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <h3
                    class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-200"
                >
                    Escalation Report
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-teal-500 text-white">
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    No
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Week
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Wall Time
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Team
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Line
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Mc Type
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Mc No
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Mc Status
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Model ID
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Lot ID
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Category
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Problem Part
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Problem Details
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Requestor
                                </th>
                                <th
                                    class="border border-teal-600 px-3 py-2 text-left font-semibold whitespace-nowrap"
                                >
                                    Date/Time
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="report in escalationReports"
                                :key="report.no"
                                class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                <td
                                    class="border border-gray-200 px-3 py-2 text-center dark:border-gray-700"
                                >
                                    {{ report.no }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 text-center dark:border-gray-700"
                                >
                                    {{ report.week }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 dark:border-gray-700"
                                >
                                    {{ report.wallTime }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 dark:border-gray-700"
                                >
                                    {{ report.team }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 text-center dark:border-gray-700"
                                >
                                    {{ report.line }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 dark:border-gray-700"
                                >
                                    {{ report.mcType }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 dark:border-gray-700"
                                >
                                    {{ report.mcNo }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 dark:border-gray-700"
                                >
                                    {{ report.mcStatus }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 dark:border-gray-700"
                                >
                                    {{ report.modelId }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 dark:border-gray-700"
                                >
                                    {{ report.lotId }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 dark:border-gray-700"
                                >
                                    {{ report.category }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 dark:border-gray-700"
                                >
                                    {{ report.problemPart }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 dark:border-gray-700"
                                >
                                    {{ report.problemDetails }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 dark:border-gray-700"
                                >
                                    {{ report.requestor }}
                                </td>
                                <td
                                    class="border border-gray-200 px-3 py-2 dark:border-gray-700"
                                >
                                    {{ report.dateTime }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
