<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { BarChart, PieChart } from 'echarts/charts';
import {
    GridComponent,
    LegendComponent,
    TitleComponent,
    ToolboxComponent,
    TooltipComponent,
} from 'echarts/components';
import * as echarts from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import {
    computed,
    defineProps,
    nextTick,
    onMounted,
    onUnmounted,
    ref,
    watch,
} from 'vue';
import EndtimeWipupdateModal from '@/pages/dashboards/subs/endtime-wipupdate-modal.vue';

echarts.use([
    TitleComponent,
    TooltipComponent,
    GridComponent,
    LegendComponent,
    ToolboxComponent,
    BarChart,
    PieChart,
    CanvasRenderer,
]);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: '/',
    },
    {
        title: 'PROCESS WIP',
        href: '/process-wip',
    },
];

// ============================================================================
// PROPS & STATE
// ============================================================================
const props = defineProps<{
    wips: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
    };
    stats: {
        last_update: string;
        cards: {
            normal: { mpcs: number; lots: number };
            rework: { mpcs: number; lots: number };
            warehouse: { mpcs: number; lots: number };
            outgoing_ng: { mpcs: number; lots: number };
        };
        bar_chart: { categories: string[]; series: any[] };
        donut_chart: { value: number; name: string }[];
        donut_chart_by_size?: Record<string, { value: number; name: string }[]>;
    };
    filterOptions: {
        wip_statuses: string[];
        lot_statuses: string[];
        eqp_types: string[];
        work_types: string[];
    };
    filters: {
        wip_status?: string;
        lot_status?: string;
        eqp_type?: string;
        hold?: string;
        work_type?: string;
        search?: string;
        automotive?: string;
        lipas?: string;
        size?: string;
        sort_field?: string;
        sort_direction?: string;
    };
}>();

const isLoading = ref(false);
const isDarkMode = ref(false);
const sortField = ref<string | null>(props.filters.sort_field || null);
const sortDirection = ref<'asc' | 'desc'>((props.filters.sort_direction as 'asc' | 'desc') || 'asc');
const selectedBarFilter = ref<{ eqpType?: string; wipStatus?: string } | null>(
    null,
);

// Modal state for WIP Update
const isWipUpdateModalOpen = ref(false);

// Reactive filters
const currentFilters = ref({
    last_update: props.stats.last_update,
    lipas: props.filters.lipas || 'ALL',
    automotive: props.filters.automotive || 'ALL',
    hold: props.filters.hold || 'ALL',
    worktype: props.filters.work_type || 'ALL',
    size: props.filters.size || 'ALL',
    search: props.filters.search || '',
    sort_field: props.filters.sort_field || null,
    sort_direction: props.filters.sort_direction || 'asc',
});

// ============================================================================
// METHODS
// ============================================================================
const handleFilterChange = () => {
    isLoading.value = true;

    const params: any = {
        lipas:
            currentFilters.value.lipas !== 'ALL'
                ? currentFilters.value.lipas
                : null,
        automotive:
            currentFilters.value.automotive !== 'ALL'
                ? currentFilters.value.automotive
                : null,
        hold:
            currentFilters.value.hold !== 'ALL'
                ? currentFilters.value.hold
                : null,
        work_type:
            currentFilters.value.worktype !== 'ALL'
                ? currentFilters.value.worktype
                : null,
        size:
            currentFilters.value.size !== 'ALL'
                ? currentFilters.value.size
                : null,
        search: currentFilters.value.search,
        sort_field: sortField.value,
        sort_direction: sortDirection.value,
    };

    // Add chart filter parameters
    if (selectedBarFilter.value?.eqpType) {
        const lotSize = sizeToLotSizeMap[selectedBarFilter.value.eqpType];
        if (lotSize) {
            params.lot_size = lotSize;
        }
    }

    if (selectedBarFilter.value?.wipStatus) {
        const actualStatuses =
            displayToActualStatusMap[selectedBarFilter.value.wipStatus] ||
            categoryToStatusMap[selectedBarFilter.value.wipStatus];
        if (actualStatuses && actualStatuses.length > 0) {
            params.wip_status_filter = actualStatuses.join(',');
        }
    }

    router.get('/process-wip', params, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const handleReset = () => {
    sortField.value = null;
    sortDirection.value = 'asc';
    currentFilters.value = {
        last_update: props.stats.last_update,
        lipas: 'ALL',
        automotive: 'ALL',
        hold: 'ALL',
        worktype: 'ALL',
        size: 'ALL',
        search: '',
        sort_field: null,
        sort_direction: 'asc',
    };
    handleFilterChange();
};

const handleSort = (field: string) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    handleFilterChange();
};

// Mapping from display names back to database values
const sizeToLotSizeMap: Record<string, string> = {
    '0603': '03',
    '1005': '05',
    '1608': '10',
    '2012': '21',
    '3216': '31',
    '3225': '32',
};

const displayToActualStatusMap: Record<string, string[]> = {
    'Waiting RCV': ['Waiting Receive'],
    'Newlot STBY': ['Newlot Standby'],
    'Rework STBY': ['Rework Lot Standby'],
    'Ongoing MC': ['Ongoing MC'],
    'Manual (QC)': ['Manual Inspection (QC)', 'RL Rework'],
    'OI VI (QC)': ['OI Visual Inspection (QC)'],
    'VI Finish': ['Visual Finish', 'Yeild Recovery'],
};

const categoryToStatusMap: Record<string, string[]> = {
    STANDBY: ['Waiting Receive', 'Newlot Standby', 'Rework Lot Standby'],
    ONGOING: ['Ongoing MC'],
    ENDLINE: [
        'Manual Inspection (QC)',
        'RL Rework',
        'OI Visual Inspection (QC)',
        'Visual Finish',
        'Yeild Recovery',
    ],
};

// Remove client-side sorting - now handled by backend
const sortedWips = computed(() => props.wips.data);

const clearBarFilter = () => {
    selectedBarFilter.value = null;
    handleFilterChange();
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();

    if (currentFilters.value.lipas !== 'ALL') {
        params.append('lipas', currentFilters.value.lipas);
    }
    if (currentFilters.value.automotive !== 'ALL') {
        params.append('automotive', currentFilters.value.automotive);
    }
    if (currentFilters.value.hold !== 'ALL') {
        params.append('hold', currentFilters.value.hold);
    }
    if (currentFilters.value.worktype !== 'ALL') {
        params.append('work_type', currentFilters.value.worktype);
    }
    if (currentFilters.value.size !== 'ALL') {
        params.append('size', currentFilters.value.size);
    }
    if (currentFilters.value.search) {
        params.append('search', currentFilters.value.search);
    }

    // Add chart filter parameters
    if (selectedBarFilter.value?.eqpType) {
        const lotSize = sizeToLotSizeMap[selectedBarFilter.value.eqpType];
        if (lotSize) {
            params.append('lot_size', lotSize);
        }
    }

    if (selectedBarFilter.value?.wipStatus) {
        const actualStatuses =
            displayToActualStatusMap[selectedBarFilter.value.wipStatus] ||
            categoryToStatusMap[selectedBarFilter.value.wipStatus];
        if (actualStatuses && actualStatuses.length > 0) {
            params.append('wip_status_filter', actualStatuses.join(','));
        }
    }

    const queryString = params.toString();
    return `/process-wip/export${queryString ? '?' + queryString : ''}`;
});

const audienceChartRef = ref<HTMLElement | null>(null);
const deviceChartRef = ref<HTMLElement | null>(null);
let audienceChart: echarts.ECharts | null = null;
let deviceChart: echarts.ECharts | null = null;
let resizeObserver: ResizeObserver | null = null;

const initCharts = () => {
    // WORK in PROGRESS Metrics Chart (Bar with Labels from ECharts)
    if (audienceChartRef.value) {
        if (audienceChart) audienceChart.dispose();
        audienceChart = echarts.init(
            audienceChartRef.value,
            isDarkMode.value ? 'dark' : undefined,
        );

        const labelOption = {
            show: true,
            position: 'insideBottom',
            distance: 5,
            align: 'left',
            verticalAlign: 'middle',
            rotate: 90,
            formatter: function (params: any) {
                // Extract size code from series name
                // '0603' -> '03', '1005' -> '05', '1608' -> '10', '2012' -> '21', '3216' -> '31', '3225' -> '32'
                const name = params.seriesName;
                let shortName = '';
                if (name === '0603') shortName = '03';
                else if (name === '1005') shortName = '05';
                else if (name === '1608') shortName = '10';
                else if (name === '2012') shortName = '21';
                else if (name === '3216') shortName = '31';
                else if (name === '3225') shortName = '32';
                else shortName = name.slice(-2); // fallback
                return `${shortName} ${params.value}M`;
            },
            fontSize: 11,
            color: isDarkMode.value ? '#e5e7eb' : '#374151',
        };

        const option = {
            backgroundColor: 'transparent',
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow',
                },
                formatter: function (params: any) {
                    let result = `<b>${params[0].axisValueLabel}</b> (Click to filter all sizes)<br/>`;
                    params.forEach((item: any) => {
                        result += `${item.marker} ${item.seriesName}: <b>${item.value}M</b><br/>`;
                    });
                    return result;
                },
            },
            legend: {
                data: props.stats.bar_chart.series.map((s: any) => s.name),
                top: '0%',
                left: 'center',
            },
            grid: {
                left: '3%',
                right: '5%',
                bottom: '3%',
                top: '15%',
                containLabel: true,
            },
            toolbox: {
                show: true,
                orient: 'vertical',
                left: 'right',
                top: 'center',
                feature: {
                    mark: { show: true },
                    dataView: { show: true, readOnly: false },
                    magicType: {
                        show: true,
                        type: ['line', 'bar', 'stack'],
                    },
                    restore: { show: true },
                    saveAsImage: { show: true },
                },
            },
            xAxis: [
                {
                    type: 'category',
                    axisTick: { show: false },
                    data: props.stats.bar_chart.categories,
                    splitLine: {
                        lineStyle: {
                            type: 'dashed',
                            color: 'rgba(142, 156, 173,0.1)',
                        },
                    },
                    axisLabel: {
                        fontSize: 10,
                        color: isDarkMode.value ? '#9ca3af' : '#6b7280',
                        interval: 0,
                        rotate: 0,
                    },
                    triggerEvent: true,
                },
            ],
            yAxis: [
                {
                    type: 'value',
                    splitLine: {
                        lineStyle: {
                            color: 'rgba(142, 156, 173,0.1)',
                        },
                    },
                },
            ],
            series: props.stats.bar_chart.series.map((s: any) => ({
                ...s,
                label: labelOption,
            })),
            color: [
                '#985ffd',
                '#ff49cd',
                '#fdaf22',
                '#32d484',
                '#00c9ff',
                '#ff6757',
            ],
        };

        audienceChart.setOption(option);

        // Add click event to filter table by bar
        audienceChart.on('click', (params: any) => {
            if (params.componentType === 'series') {
                // Click on a specific bar (size + category)
                const eqpType = params.seriesName; // Size code (0603, 1005, etc.)
                const wipStatus = params.name; // Category name from x-axis

                // Toggle filter - if same bar clicked, clear filter
                if (
                    selectedBarFilter.value?.eqpType === eqpType &&
                    selectedBarFilter.value?.wipStatus === wipStatus
                ) {
                    selectedBarFilter.value = null;
                } else {
                    selectedBarFilter.value = { eqpType, wipStatus };
                }

                // Trigger backend filter
                handleFilterChange();
            } else if (params.componentType === 'xAxis') {
                // Click on x-axis label (category only, all sizes)
                const wipStatus = params.value; // Category name

                // Toggle filter - if same category clicked, clear filter
                if (
                    selectedBarFilter.value?.wipStatus === wipStatus &&
                    !selectedBarFilter.value?.eqpType
                ) {
                    selectedBarFilter.value = null;
                } else {
                    selectedBarFilter.value = { wipStatus };
                }

                // Trigger backend filter
                handleFilterChange();
            }
        });

        // Add hover effect for x-axis labels
        audienceChart.on('mouseover', (params: any) => {
            if (params.componentType === 'xAxis') {
                audienceChartRef.value!.style.cursor = 'pointer';
            }
        });

        audienceChart.on('mouseout', (params: any) => {
            if (params.componentType === 'xAxis') {
                audienceChartRef.value!.style.cursor = 'default';
            }
        });
    }

    // WIP Category Chart (Donut Chart)
    if (deviceChartRef.value) {
        if (deviceChart) deviceChart.dispose();
        deviceChart = echarts.init(
            deviceChartRef.value,
            isDarkMode.value ? 'dark' : undefined,
        );

        // Filter data by selected size
        let chartData = props.stats.donut_chart;
        if (currentFilters.value.size !== 'ALL' && props.stats.donut_chart_by_size) {
            chartData =
                props.stats.donut_chart_by_size[currentFilters.value.size] ||
                props.stats.donut_chart;
        }

        // Calculate total
        const total = chartData.reduce(
            (sum: number, item: any) => sum + item.value,
            0,
        );

        const option = {
            backgroundColor: 'transparent',
            tooltip: {
                show: false,
            },
            legend: {
                show: false,
            },
            title: {
                text: `Total\n${total.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} Mpcs`,
                left: 'center',
                top: 'center',
                textStyle: {
                    fontSize: 16,
                    fontWeight: 'bold',
                    color: isDarkMode.value ? '#e5e7eb' : '#374151',
                },
            },
            series: [
                {
                    name: 'WIP Category',
                    type: 'pie',
                    radius: ['60%', '90%'],
                    avoidLabelOverlap: false,
                    selectedMode: false,
                    label: {
                        show: false,
                    },
                    emphasis: {
                        label: {
                            show: false,
                        },
                    },
                    labelLine: {
                        show: false,
                    },
                    data: chartData,
                },
            ],
            color: [
                '#3b82f6', // STANDBY - Blue (matches professional dashboard)
                '#10b981', // ONGOING - Emerald Green (matches warehouse card)
                '#f59e0b', // ENDLINE - Amber/Orange (warm accent)
            ],
        };

        deviceChart.setOption(option);

        // Add mouse events to update center text
        deviceChart.on('mouseover', (params: any) => {
            if (params.componentType === 'series' && deviceChart) {
                deviceChart.setOption({
                    title: {
                        text: `${params.name}\n${params.value.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} Mpcs\n(${params.percent.toFixed(2)}%)`,
                        textStyle: {
                            fontSize: 18,
                        },
                    },
                });
            }
        });

        deviceChart.on('mouseout', () => {
            if (deviceChart) {
                deviceChart.setOption({
                    title: {
                        text: `Total\n${total.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} Mpcs`,
                        textStyle: {
                            fontSize: 16,
                        },
                    },
                });
            }
        });

        // Add click event to filter table by category
        deviceChart.on('click', (params: any) => {
            if (params.componentType === 'series') {
                const wipStatus = params.name; // STANDBY, ONGOING, or ENDLINE

                // Toggle filter - if same segment clicked, clear filter
                if (
                    selectedBarFilter.value?.wipStatus === wipStatus &&
                    !selectedBarFilter.value?.eqpType
                ) {
                    selectedBarFilter.value = null;
                } else {
                    selectedBarFilter.value = { wipStatus };
                }

                // Trigger backend filter
                handleFilterChange();
            }
        });
    }
};

watch(
    () => props.stats,
    () => {
        initCharts();
        currentFilters.value.last_update = props.stats.last_update;
    },
    { deep: true },
);

// Watch for dark mode changes
watch(isDarkMode, () => {
    initCharts();
});

// Watch for size filter changes to update donut chart
watch(() => currentFilters.value.size, () => {
    initCharts();
});

onMounted(() => {
    // Initialize dark mode state
    isDarkMode.value = document.documentElement.classList.contains('dark');

    nextTick(() => {
        initCharts();

        // Create ResizeObserver to watch for container size changes
        resizeObserver = new ResizeObserver(() => {
            if (audienceChart) audienceChart.resize();
            if (deviceChart) deviceChart.resize();
        });

        // Observe both chart containers
        if (audienceChartRef.value) {
            resizeObserver.observe(audienceChartRef.value);
        }
        if (deviceChartRef.value) {
            resizeObserver.observe(deviceChartRef.value);
        }

        // Also listen to window resize as fallback
        window.addEventListener('resize', () => {
            if (audienceChart) audienceChart.resize();
            if (deviceChart) deviceChart.resize();
        });

        // Watch for theme changes
        const themeObserver = new MutationObserver(() => {
            const newDarkMode =
                document.documentElement.classList.contains('dark');
            if (newDarkMode !== isDarkMode.value) {
                isDarkMode.value = newDarkMode;
            }
        });

        themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });

        // Store observer for cleanup
        (window as any).__themeObserver = themeObserver;
    });
});

onUnmounted(() => {
    // Cleanup
    if (resizeObserver) {
        resizeObserver.disconnect();
    }
    if (audienceChart) {
        audienceChart.dispose();
    }
    if (deviceChart) {
        deviceChart.dispose();
    }
    if ((window as any).__themeObserver) {
        (window as any).__themeObserver.disconnect();
        delete (window as any).__themeObserver;
    }
});

// Helper function to format values with thousand separators and 1 decimal place
const formatMpcs = (value: number): string => {
    return value.toLocaleString('en-US', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    });
};
</script>

<template>
    <Head title="PROCESS WIP" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- ================================================================ -->
        <!-- FILTERS SECTION -->
        <!-- ================================================================ -->
        <template #filters>
            <div class="flex items-center gap-3">
                <!-- Last Update Display -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >LAST UPDATE:</span
                    >
                    <span class="text-xs font-semibold text-foreground">{{
                        stats.last_update
                    }}</span>
                </div>

                <!-- Lipas Filter -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >LIPAS:</span
                    >
                    <select
                        v-model="currentFilters.lipas"
                        @change="handleFilterChange"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100"
                    >
                        <option value="ALL">ALL</option>
                        <option value="Y">Y</option>
                        <option value="N">N</option>
                    </select>
                </div>

                <!-- Automotive Filter -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >AUTOMOTIVE:</span
                    >
                    <select
                        v-model="currentFilters.automotive"
                        @change="handleFilterChange"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100"
                    >
                        <option value="ALL">ALL</option>
                        <option value="Y">Y</option>
                        <option value="N">N</option>
                    </select>
                </div>

                <!-- Hold Filter -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >HOLD:</span
                    >
                    <select
                        v-model="currentFilters.hold"
                        @change="handleFilterChange"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100"
                    >
                        <option value="ALL">ALL</option>
                        <option value="Y">Y</option>
                        <option value="N">N</option>
                    </select>
                </div>

                <!-- WorkType Filter -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >WORKTYPE:</span
                    >
                    <select
                        v-model="currentFilters.worktype"
                        @change="handleFilterChange"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100"
                    >
                        <option value="ALL">ALL</option>
                        <option
                            v-for="type in filterOptions.work_types"
                            :key="type"
                            :value="type"
                        >
                            {{ type }}
                        </option>
                    </select>
                </div>

                <!-- Size Filter -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >SIZE:</span
                    >
                    <select
                        v-model="currentFilters.size"
                        @change="handleFilterChange"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100"
                    >
                        <option value="ALL">ALL</option>
                        <option value="0603">0603</option>
                        <option value="1005">1005</option>
                        <option value="1608">1608</option>
                        <option value="2012">2012</option>
                        <option value="3216">3216</option>
                        <option value="3225">3225</option>
                    </select>
                </div>

                <!-- Manual Refresh -->
                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 border-gray-200 px-3 text-xs font-medium shadow-sm hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800"
                    @click="handleFilterChange"
                    :disabled="isLoading"
                >
                    <span class="mr-1">🔄</span> Refresh
                </Button>

                <!-- Reset Button -->
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 rounded-lg hover:bg-muted"
                    @click="handleReset"
                    :disabled="isLoading"
                    title="Reset to default"
                >
                    <span class="text-lg">🔀</span>
                </Button>
            </div>
        </template>
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
            <!-- Start:: row-1 -->
            <div class="grid grid-cols-1 gap-4">
                <div class="grid grid-cols-1 gap-4 2xl:grid-cols-12">
                    <div class="2xl:col-span-9">
                        <div class="grid grid-cols-1 gap-4">
                            <!-- Stats Cards -->
                            <div
                                class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-4"
                            >
                                <div
                                    class="group cursor-pointer rounded-lg bg-gradient-to-br from-purple-50 to-purple-100/50 p-4 shadow transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg dark:from-purple-950/30 dark:to-purple-900/20"
                                >
                                    <div class="flex items-start gap-3">
                                        <div>
                                            <span
                                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-500/10 text-purple-600 ring-1 ring-purple-500/20 transition-all duration-200 group-hover:scale-110 group-hover:bg-purple-500/20 group-hover:ring-purple-500/30 dark:text-purple-400"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-6 w-6"
                                                    viewBox="0 0 24 24"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        d="M9 2C8.44772 2 8 2.44772 8 3V4H6C4.89543 4 4 4.89543 4 6V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V6C20 4.89543 19.1046 4 18 4H16V3C16 2.44772 15.5523 2 15 2C14.4477 2 14 2.44772 14 3V4H10V3C10 2.44772 9.55228 2 9 2ZM6 9V20H18V9H6ZM8 11H10V13H8V11ZM12 11H14V13H12V11ZM16 11H18V13H16V11ZM8 15H10V17H8V15ZM12 15H14V17H12V15ZM16 15H18V17H16V15Z"
                                                    />
                                                </svg>
                                            </span>
                                        </div>
                                        <div>
                                            <span
                                                class="block text-sm font-semibold text-purple-700 dark:text-purple-300"
                                                >NORMAL LOTS</span
                                            >
                                            <h5
                                                class="mb-1 text-xl font-semibold text-gray-900 dark:text-white"
                                            >
                                                {{
                                                    formatMpcs(
                                                        stats.cards.normal.mpcs,
                                                    )
                                                }}
                                                Mpcs
                                            </h5>
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-300"
                                            >
                                                <span
                                                    class="text-green-500 dark:text-green-400"
                                                >
                                                    {{
                                                        stats.cards.normal.lots.toLocaleString()
                                                    }}
                                                </span>
                                                lot counts
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="group cursor-pointer rounded-lg bg-gradient-to-br from-pink-50 to-pink-100/50 p-4 shadow transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg dark:from-pink-950/30 dark:to-pink-900/20"
                                >
                                    <div class="flex items-start gap-3">
                                        <div>
                                            <span
                                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-pink-500/10 text-pink-600 ring-1 ring-pink-500/20 transition-all duration-200 group-hover:scale-110 group-hover:bg-pink-500/20 group-hover:ring-pink-500/30 dark:text-pink-400"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-6 w-6"
                                                    viewBox="0 0 24 24"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z"
                                                    />
                                                    <path
                                                        d="M17.65 6.35C16.2 4.9 14.21 4 12 4C9.79 4 7.8 4.9 6.35 6.35L7.76 7.76C8.87 6.65 10.35 6 12 6C13.65 6 15.13 6.65 16.24 7.76L17.65 6.35Z"
                                                    />
                                                </svg>
                                            </span>
                                        </div>
                                        <div>
                                            <span
                                                class="block text-sm font-semibold text-pink-700 dark:text-pink-300"
                                                >PROCESS REWORK</span
                                            >
                                            <h5
                                                class="mb-1 text-xl font-semibold text-gray-900 dark:text-white"
                                            >
                                                {{
                                                    formatMpcs(
                                                        stats.cards.rework.mpcs,
                                                    )
                                                }}
                                                Mpcs
                                            </h5>
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-300"
                                            >
                                                <span
                                                    class="text-green-500 dark:text-green-400"
                                                >
                                                    {{
                                                        stats.cards.rework.lots.toLocaleString()
                                                    }}
                                                </span>
                                                lot counts
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="group cursor-pointer rounded-lg bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-4 shadow transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg dark:from-emerald-950/30 dark:to-emerald-900/20"
                                >
                                    <div class="flex items-start gap-3">
                                        <div>
                                            <span
                                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 ring-1 ring-emerald-500/20 transition-all duration-200 group-hover:scale-110 group-hover:bg-emerald-500/20 group-hover:ring-emerald-500/30 dark:text-emerald-400"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-6 w-6"
                                                    viewBox="0 0 24 24"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        d="M20 8H4V6H20V8ZM20 18H4V16H20V18ZM3 22L5 20H19L21 22H3ZM3 2L5 4H19L21 2H3ZM12 9C10.9 9 10 9.9 10 11V13C10 14.1 10.9 15 12 15C13.1 15 14 14.1 14 13V11C14 9.9 13.1 9 12 9Z"
                                                    />
                                                </svg>
                                            </span>
                                        </div>
                                        <div>
                                            <span
                                                class="block text-sm font-semibold text-emerald-700 dark:text-emerald-300"
                                                >WAREHOUSE</span
                                            >
                                            <h5
                                                class="mb-1 text-xl font-semibold text-gray-900 dark:text-white"
                                            >
                                                {{
                                                    formatMpcs(
                                                        stats.cards.warehouse
                                                            .mpcs,
                                                    )
                                                }}
                                                Mpcs
                                            </h5>
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-300"
                                            >
                                                <span
                                                    class="text-green-500 dark:text-green-400"
                                                >
                                                    {{
                                                        stats.cards.warehouse.lots.toLocaleString()
                                                    }}
                                                </span>
                                                Lots
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="group cursor-pointer rounded-lg bg-gradient-to-br from-orange-50 to-orange-100/50 p-4 shadow transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg dark:from-orange-950/30 dark:to-orange-900/20"
                                >
                                    <div class="flex items-start gap-3">
                                        <div>
                                            <span
                                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-500/10 text-orange-600 ring-1 ring-orange-500/20 transition-all duration-200 group-hover:scale-110 group-hover:bg-orange-500/20 group-hover:ring-orange-500/30 dark:text-orange-400"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-6 w-6"
                                                    viewBox="0 0 24 24"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        d="M12 2L1 21H23L12 2ZM13 18H11V16H13V18ZM13 14H11V10H13V14Z"
                                                    />
                                                </svg>
                                            </span>
                                        </div>
                                        <div>
                                            <span
                                                class="block text-sm font-semibold text-orange-700 dark:text-orange-300"
                                                >OUTGOING NG</span
                                            >
                                            <h5
                                                class="mb-1 text-xl font-semibold text-gray-900 dark:text-white"
                                            >
                                                {{
                                                    formatMpcs(
                                                        stats.cards.outgoing_ng
                                                            .mpcs,
                                                    )
                                                }}
                                                Mpcs
                                            </h5>
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-300"
                                            >
                                                <span
                                                    class="text-green-500 dark:text-green-400"
                                                >
                                                    {{
                                                        stats.cards.outgoing_ng.lots.toLocaleString()
                                                    }}
                                                </span>
                                                Lots
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Work in Progress Breakdown -->
                            <div
                                class="grid grid-cols-1 gap-4 2xl:grid-cols-12"
                            >
                                <div class="2xl:col-span-12">
                                    <div
                                        class="rounded-lg bg-white shadow dark:bg-gray-800"
                                    >
                                        <div
                                            class="border-b border-gray-200 p-4 dark:border-gray-700"
                                        >
                                            <div class="font-semibold">
                                                WORK IN PROGRESS
                                            </div>
                                        </div>
                                        <div class="p-4">
                                            <div
                                                ref="audienceChartRef"
                                                style="
                                                    width: 100%;
                                                    height: 260px;
                                                "
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- WIP Category Chart (Donut Chart) -->
                    <div class="2xl:col-span-3">
                        <div class="grid grid-cols-1 gap-4">
                            <div
                                class="rounded-lg bg-white shadow dark:bg-gray-800"
                            >
                                <div
                                    class="border-b border-gray-200 p-4 dark:border-gray-700"
                                >
                                    <div class="font-semibold">
                                        WIP CATEGORY
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div
                                        ref="deviceChartRef"
                                        style="width: 100%; height: 300px"
                                    ></div>
                                </div>
                                <div class="p-0">
                                    <div class="grid grid-cols-3">
                                        <div
                                            v-for="(
                                                item, index
                                            ) in stats.donut_chart"
                                            :key="index"
                                            class="border-r border-gray-200 p-3 text-center last:border-r-0 dark:border-gray-700"
                                        >
                                            <h5
                                                class="mb-1 text-lg font-semibold text-gray-900 dark:text-white"
                                            >
                                                {{ formatMpcs(item.value) }} M
                                            </h5>
                                            <span
                                                class="block text-xs text-gray-500 dark:text-gray-300"
                                                >{{ item.name }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lot List Table - Full Width -->
                <div class="grid grid-cols-1">
                    <div
                        class="flex flex-col rounded-lg bg-white shadow dark:bg-gray-800"
                        style="min-height: 600px"
                    >
                        <div
                            class="flex flex-shrink-0 items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700"
                        >
                            <div class="flex items-center gap-3">
                                <div class="font-semibold">
                                    LOT LIST - RAW DATA
                                </div>
                                <div
                                    v-if="selectedBarFilter"
                                    class="flex items-center gap-2 rounded-lg bg-blue-100 px-3 py-1.5 text-xs dark:bg-blue-900"
                                >
                                    <span
                                        class="font-medium text-blue-900 dark:text-blue-100"
                                    >
                                        Filtered:
                                        <span
                                            v-if="selectedBarFilter.eqpType"
                                            class="font-semibold"
                                            >{{
                                                selectedBarFilter.eqpType
                                            }}</span
                                        >
                                        <span
                                            v-if="
                                                selectedBarFilter.eqpType &&
                                                selectedBarFilter.wipStatus
                                            "
                                        >
                                            -
                                        </span>
                                        <span
                                            v-if="selectedBarFilter.wipStatus"
                                            class="font-semibold"
                                            >{{
                                                selectedBarFilter.wipStatus
                                            }}</span
                                        >
                                    </span>
                                    <button
                                        @click="clearBarFilter"
                                        class="ml-1 text-blue-700 hover:text-blue-900 dark:text-blue-300 dark:hover:text-blue-100"
                                        title="Clear filter"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <div>
                                    <input
                                        v-model="currentFilters.search"
                                        @keyup.enter="handleFilterChange"
                                        class="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        type="text"
                                        placeholder="Search Here"
                                    />
                                </div>
                                <div class="relative">
                                    <button
                                        class="rounded bg-blue-500 px-3 py-1 text-sm text-white hover:bg-blue-600"
                                        @click="handleFilterChange"
                                    >
                                        Search
                                    </button>
                                </div>
                                <div class="relative">
                                    <button
                                        class="inline-flex items-center gap-1 rounded bg-sky-500 px-3 py-1 text-sm text-white hover:bg-sky-600"
                                        @click="isWipUpdateModalOpen = true"
                                        title="Update WIP Data"
                                    >
                                        <span>🔄</span>
                                        <span>Update WIP</span>
                                    </button>
                                </div>
                                <div class="relative">
                                    <a
                                        :href="exportUrl"
                                        class="inline-flex items-center gap-1 rounded bg-green-600 px-3 py-1 text-sm text-white hover:bg-green-700"
                                        title="Export to CSV"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                                            ></path>
                                            <polyline
                                                points="7 10 12 15 17 10"
                                            ></polyline>
                                            <line
                                                x1="12"
                                                y1="15"
                                                x2="12"
                                                y2="3"
                                            ></line>
                                        </svg>
                                        Export
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 overflow-hidden p-0">
                            <div class="h-full overflow-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr
                                            class="border-b border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950"
                                        >
                                            <th
                                                class="px-2 py-3 text-center font-semibold whitespace-nowrap text-blue-900 dark:text-blue-100"
                                            >
                                                No.
                                            </th>
                                            <th
                                                @click="handleSort('lot_id')"
                                                class="cursor-pointer px-3 py-3 text-left font-semibold whitespace-nowrap text-blue-900 hover:bg-blue-100 dark:text-blue-100 dark:hover:bg-blue-900"
                                            >
                                                <div
                                                    class="flex items-center gap-1"
                                                >
                                                    Lot no.
                                                    <span class="text-xs">
                                                        <span
                                                            v-if="
                                                                sortField ===
                                                                'lot_id'
                                                            "
                                                        >
                                                            {{
                                                                sortDirection ===
                                                                'asc'
                                                                    ? '▲'
                                                                    : '▼'
                                                            }}
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="opacity-30"
                                                            >⇅</span
                                                        >
                                                    </span>
                                                </div>
                                            </th>
                                            <th
                                                @click="handleSort('model_15')"
                                                class="cursor-pointer px-3 py-3 text-left font-semibold whitespace-nowrap text-blue-900 hover:bg-blue-100 dark:text-blue-100 dark:hover:bg-blue-900"
                                            >
                                                <div
                                                    class="flex items-center gap-1"
                                                >
                                                    Model
                                                    <span class="text-xs">
                                                        <span
                                                            v-if="
                                                                sortField ===
                                                                'model_15'
                                                            "
                                                        >
                                                            {{
                                                                sortDirection ===
                                                                'asc'
                                                                    ? '▲'
                                                                    : '▼'
                                                            }}
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="opacity-30"
                                                            >⇅</span
                                                        >
                                                    </span>
                                                </div>
                                            </th>
                                            <th
                                                @click="handleSort('lot_qty')"
                                                class="cursor-pointer px-3 py-3 text-right font-semibold whitespace-nowrap text-blue-900 hover:bg-blue-100 dark:text-blue-100 dark:hover:bg-blue-900"
                                            >
                                                <div
                                                    class="flex items-center justify-end gap-1"
                                                >
                                                    Quantity
                                                    <span class="text-xs">
                                                        <span
                                                            v-if="
                                                                sortField ===
                                                                'lot_qty'
                                                            "
                                                        >
                                                            {{
                                                                sortDirection ===
                                                                'asc'
                                                                    ? '▲'
                                                                    : '▼'
                                                            }}
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="opacity-30"
                                                            >⇅</span
                                                        >
                                                    </span>
                                                </div>
                                            </th>
                                            <th
                                                @click="
                                                    handleSort('stagnant_tat')
                                                "
                                                class="cursor-pointer px-3 py-3 text-right font-semibold whitespace-nowrap text-blue-900 hover:bg-blue-100 dark:text-blue-100 dark:hover:bg-blue-900"
                                            >
                                                <div
                                                    class="flex items-center justify-end gap-1"
                                                >
                                                    TAT
                                                    <span class="text-xs">
                                                        <span
                                                            v-if="
                                                                sortField ===
                                                                'stagnant_tat'
                                                            "
                                                        >
                                                            {{
                                                                sortDirection ===
                                                                'asc'
                                                                    ? '▲'
                                                                    : '▼'
                                                            }}
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="opacity-30"
                                                            >⇅</span
                                                        >
                                                    </span>
                                                </div>
                                            </th>
                                            <th
                                                @click="handleSort('work_type')"
                                                class="cursor-pointer px-3 py-3 text-left font-semibold whitespace-nowrap text-blue-900 hover:bg-blue-100 dark:text-blue-100 dark:hover:bg-blue-900"
                                            >
                                                <div
                                                    class="flex items-center gap-1"
                                                >
                                                    Work Type
                                                    <span class="text-xs">
                                                        <span
                                                            v-if="
                                                                sortField ===
                                                                'work_type'
                                                            "
                                                        >
                                                            {{
                                                                sortDirection ===
                                                                'asc'
                                                                    ? '▲'
                                                                    : '▼'
                                                            }}
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="opacity-30"
                                                            >⇅</span
                                                        >
                                                    </span>
                                                </div>
                                            </th>
                                            <th
                                                @click="
                                                    handleSort('wip_status')
                                                "
                                                class="cursor-pointer px-3 py-3 text-left font-semibold whitespace-nowrap text-blue-900 hover:bg-blue-100 dark:text-blue-100 dark:hover:bg-blue-900"
                                            >
                                                <div
                                                    class="flex items-center gap-1"
                                                >
                                                    WIP Status
                                                    <span class="text-xs">
                                                        <span
                                                            v-if="
                                                                sortField ===
                                                                'wip_status'
                                                            "
                                                        >
                                                            {{
                                                                sortDirection ===
                                                                'asc'
                                                                    ? '▲'
                                                                    : '▼'
                                                            }}
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="opacity-30"
                                                            >⇅</span
                                                        >
                                                    </span>
                                                </div>
                                            </th>
                                            <th
                                                @click="handleSort('auto_yn')"
                                                class="cursor-pointer px-3 py-3 text-center font-semibold whitespace-nowrap text-blue-900 hover:bg-blue-100 dark:text-blue-100 dark:hover:bg-blue-900"
                                            >
                                                <div
                                                    class="flex items-center justify-center gap-1"
                                                >
                                                    Automotive
                                                    <span class="text-xs">
                                                        <span
                                                            v-if="
                                                                sortField ===
                                                                'auto_yn'
                                                            "
                                                        >
                                                            {{
                                                                sortDirection ===
                                                                'asc'
                                                                    ? '▲'
                                                                    : '▼'
                                                            }}
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="opacity-30"
                                                            >⇅</span
                                                        >
                                                    </span>
                                                </div>
                                            </th>
                                            <th
                                                @click="handleSort('lipas_yn')"
                                                class="cursor-pointer px-3 py-3 text-center font-semibold whitespace-nowrap text-blue-900 hover:bg-blue-100 dark:text-blue-100 dark:hover:bg-blue-900"
                                            >
                                                <div
                                                    class="flex items-center justify-center gap-1"
                                                >
                                                    Lipas
                                                    <span class="text-xs">
                                                        <span
                                                            v-if="
                                                                sortField ===
                                                                'lipas_yn'
                                                            "
                                                        >
                                                            {{
                                                                sortDirection ===
                                                                'asc'
                                                                    ? '▲'
                                                                    : '▼'
                                                            }}
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="opacity-30"
                                                            >⇅</span
                                                        >
                                                    </span>
                                                </div>
                                            </th>
                                            <th
                                                @click="handleSort('eqp_type')"
                                                class="cursor-pointer px-3 py-3 text-left font-semibold whitespace-nowrap text-blue-900 hover:bg-blue-100 dark:text-blue-100 dark:hover:bg-blue-900"
                                            >
                                                <div
                                                    class="flex items-center gap-1"
                                                >
                                                    MC Type
                                                    <span class="text-xs">
                                                        <span
                                                            v-if="
                                                                sortField ===
                                                                'eqp_type'
                                                            "
                                                        >
                                                            {{
                                                                sortDirection ===
                                                                'asc'
                                                                    ? '▲'
                                                                    : '▼'
                                                            }}
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="opacity-30"
                                                            >⇅</span
                                                        >
                                                    </span>
                                                </div>
                                            </th>
                                            <th
                                                @click="handleSort('qty_class')"
                                                class="cursor-pointer px-3 py-3 text-left font-semibold whitespace-nowrap text-blue-900 hover:bg-blue-100 dark:text-blue-100 dark:hover:bg-blue-900"
                                            >
                                                <div
                                                    class="flex items-center gap-1"
                                                >
                                                    MC Class
                                                    <span class="text-xs">
                                                        <span
                                                            v-if="
                                                                sortField ===
                                                                'qty_class'
                                                            "
                                                        >
                                                            {{
                                                                sortDirection ===
                                                                'asc'
                                                                    ? '▲'
                                                                    : '▼'
                                                            }}
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="opacity-30"
                                                            >⇅</span
                                                        >
                                                    </span>
                                                </div>
                                            </th>
                                            <th
                                                @click="
                                                    handleSort('lot_location')
                                                "
                                                class="cursor-pointer px-3 py-3 text-left font-semibold whitespace-nowrap text-blue-900 hover:bg-blue-100 dark:text-blue-100 dark:hover:bg-blue-900"
                                            >
                                                <div
                                                    class="flex items-center gap-1"
                                                >
                                                    Location
                                                    <span class="text-xs">
                                                        <span
                                                            v-if="
                                                                sortField ===
                                                                'lot_location'
                                                            "
                                                        >
                                                            {{
                                                                sortDirection ===
                                                                'asc'
                                                                    ? '▲'
                                                                    : '▼'
                                                            }}
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="opacity-30"
                                                            >⇅</span
                                                        >
                                                    </span>
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(wip, index) in sortedWips"
                                            :key="wip.id"
                                            class="border-b border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                                        >
                                            <td class="px-2 py-3 text-center">
                                                {{ wips.from + index }}
                                            </td>
                                            <td class="px-3 py-3">
                                                {{ wip.lot_id }}
                                            </td>
                                            <td class="px-3 py-3">
                                                {{ wip.model_15 }}
                                            </td>
                                            <td class="px-3 py-3 text-right">
                                                {{
                                                    wip.lot_qty.toLocaleString()
                                                }}
                                            </td>
                                            <td class="px-3 py-3 text-right">
                                                {{ wip.stagnant_tat }}
                                            </td>
                                            <td class="px-3 py-3">
                                                {{ wip.work_type }}
                                            </td>
                                            <td class="px-3 py-3">
                                                {{ wip.wip_status }}
                                            </td>
                                            <td class="px-3 py-3 text-center">
                                                {{ wip.auto_yn }}
                                            </td>
                                            <td class="px-3 py-3 text-center">
                                                {{ wip.lipas_yn }}
                                            </td>
                                            <td class="px-3 py-3">
                                                {{ wip.eqp_type }}
                                            </td>
                                            <td class="px-3 py-3">
                                                {{ wip.qty_class }}
                                            </td>
                                            <td class="px-3 py-3">
                                                {{ wip.lot_location }}
                                            </td>
                                        </tr>
                                        <tr v-if="sortedWips.length === 0">
                                            <td
                                                colspan="12"
                                                class="px-3 py-3 text-center text-gray-500 dark:text-gray-400"
                                            >
                                                No data found.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div
                            class="flex-shrink-0 border-t border-gray-200 p-4 dark:border-gray-700"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between"
                            >
                                <div>
                                    Showing {{ wips.from }} to {{ wips.to }} of
                                    {{ wips.total }} Entries
                                </div>
                                <div class="ml-auto">
                                    <nav>
                                        <ul class="flex gap-1">
                                            <li
                                                v-for="(
                                                    link, index
                                                ) in wips.links"
                                                :key="index"
                                            >
                                                <Link
                                                    v-if="link.url"
                                                    :href="link.url"
                                                    class="flex items-center justify-center rounded border px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                    :class="{
                                                        'bg-blue-500 text-white hover:bg-blue-600':
                                                            link.active,
                                                        'text-gray-500 dark:text-gray-400':
                                                            !link.active,
                                                    }"
                                                >
                                                    <span
                                                        v-html="link.label"
                                                    ></span>
                                                </Link>
                                                <span
                                                    v-else
                                                    class="flex cursor-not-allowed items-center justify-center rounded border px-3 py-1 text-gray-400"
                                                    v-html="link.label"
                                                ></span>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- WIP Update Modal Component -->
        <EndtimeWipupdateModal v-model:open="isWipUpdateModalOpen" />
    </AppLayout>
</template>
