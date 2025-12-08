<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import McAllocationEditModal from '@/pages/dashboards/subs/mc-allocation-edit-modal.vue';
import McAllocationViewModal from '@/pages/dashboards/subs/mc-allocation-view-modal.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
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
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

// ============================================================================
// ECHARTS SETUP
// ============================================================================
echarts.use([
    TitleComponent, TooltipComponent, GridComponent,
    LegendComponent, ToolboxComponent, BarChart, PieChart, CanvasRenderer,
]);

// ============================================================================
// CONSTANTS
// ============================================================================
const SIZE_MAPPING: Record<string, string> = {
    '0603': '03', '1005': '05', '1608': '10',
    '2012': '21', '3216': '31', '3225': '32',
};

const CHART_COLORS = ['#985ffd', '#ff49cd', '#fdaf22', '#32d484', '#00c9ff', '#ff6757'];
const STATUS_COLORS = ['#10b981', '#ef4444', '#6b7280', '#8b5cf6', '#f59e0b', '#3b82f6'];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: '/' },
    { title: 'MC ALLOCATION', href: '/mc-allocation' },
];

// ============================================================================
// PROPS & TYPES
// ============================================================================
interface Allocation {
    id: number;
    eqp_no: string;
    eqp_line: string;
    eqp_area: string;
    eqp_maker: string;
    eqp_type?: string;
    size: string;
    alloc_type: string;
    eqp_status: string;
    loading_speed?: number;
    operation_time?: number;
    eqp_oee?: number;
    eqp_passing?: number;
    eqp_yield?: number;
    ideal_capa: number;
    oee_capa: number;
    output_capa: number;
}

interface Props {
    allocations: Allocation[];
    stats: {
        last_update: string;
        cards: {
            total_machines: { count: number; capacity: number };
            allocated: { count: number; capacity: number };
            available: { count: number; capacity: number };
            maintenance: { count: number; capacity: number };
        };
        bar_chart: { categories: string[]; series: any[] };
        donut_chart: { value: number; name: string }[];
        donut_chart_by_size?: Record<string, { value: number; name: string }[]>;
    };
    filterOptions: {
        machine_types: string[];
        statuses: string[];
        locations: string[];
    };
    filters: {
        machine_type?: string;
        status?: string;
        location?: string;
        search?: string;
        capa_type?: string;
    };
    auth: {
        user: {
            role?: string;
        };
    };
}

const props = defineProps<Props>();

// ============================================================================
// STATE
// ============================================================================
const isLoading = ref(false);
const isDarkMode = ref(false);
const selectedSize = ref('ALL');
const sortField = ref<string | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');
const selectedBarFilter = ref<{ machineType?: string; size?: string } | null>(null);

// Table-specific filters
const tableFilters = ref({
    line: 'ALL',
    area: 'ALL',
    size: 'ALL',
    status: 'ALL',
});

// Modal state
const editModalOpen = ref(false);
const viewModalOpen = ref(false);
const selectedAllocation = ref<Allocation | null>(null);

// Role-based permissions
const userRole = computed(() => props.auth?.user?.role?.toLowerCase() || 'user');
const canEdit = computed(() => ['manager', 'admin'].includes(userRole.value));
const canDelete = computed(() => userRole.value === 'admin');

const currentFilters = ref({
    machine_type: props.filters.machine_type || 'ALL',
    status: props.filters.status || 'OPERATIONAL',
    location: props.filters.location || 'ALL',
    search: props.filters.search || '',
    capa_type: props.filters.capa_type || 'oee_capa',
});

// Chart refs
const allocationChartRef = ref<HTMLElement | null>(null);
const statusChartRef = ref<HTMLElement | null>(null);
let allocationChart: echarts.ECharts | null = null;
let statusChart: echarts.ECharts | null = null;
let resizeObserver: ResizeObserver | null = null;


// ============================================================================
// COMPUTED
// ============================================================================
// Unique values for table filters
const uniqueLines = computed(() => [...new Set(props.allocations.map(a => a.eqp_line))].filter(Boolean).sort());
const uniqueAreas = computed(() => [...new Set(props.allocations.map(a => a.eqp_area))].filter(Boolean).sort());
const uniqueSizes = computed(() => [...new Set(props.allocations.map(a => a.size))].filter(Boolean).sort());
const uniqueStatuses = computed(() => [...new Set(props.allocations.map(a => a.eqp_status))].filter(Boolean).sort());

const filteredAllocations = computed(() => {
    return props.allocations.filter(allocation => {
        if (tableFilters.value.line !== 'ALL' && allocation.eqp_line !== tableFilters.value.line) return false;
        if (tableFilters.value.area !== 'ALL' && allocation.eqp_area !== tableFilters.value.area) return false;
        if (tableFilters.value.size !== 'ALL' && allocation.size !== tableFilters.value.size) return false;
        if (tableFilters.value.status !== 'ALL' && allocation.eqp_status !== tableFilters.value.status) return false;
        return true;
    });
});

const sortedAllocations = computed(() => {
    if (!sortField.value) return filteredAllocations.value;
    
    return [...filteredAllocations.value].sort((a: Allocation, b: Allocation) => {
        let aVal: any = a[sortField.value as keyof Allocation];
        let bVal: any = b[sortField.value as keyof Allocation];
        
        // Numeric fields
        if (['ideal_capa', 'oee_capa', 'output_capa'].includes(sortField.value!)) {
            aVal = Number(aVal) || 0;
            bVal = Number(bVal) || 0;
        }
        
        // String fields
        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = (bVal as string).toLowerCase();
        }
        
        if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    const { machine_type, status, location, search, capa_type } = currentFilters.value;
    
    if (machine_type && machine_type !== 'ALL') params.append('machine_type', machine_type);
    // Always send status parameter to ensure ALL works correctly
    if (status) params.append('status', status);
    if (location && location !== 'ALL') params.append('location', location);
    if (search) params.append('search', search);
    if (capa_type) params.append('capa_type', capa_type);
    
    if (selectedBarFilter.value?.machineType) {
        params.append('machine_type_filter', selectedBarFilter.value.machineType);
    }
    if (selectedBarFilter.value?.size) {
        params.append('size_filter', selectedBarFilter.value.size);
    }
    
    const queryString = params.toString();
    return `/mc-allocation/export${queryString ? '?' + queryString : ''}`;
});

// ============================================================================
// METHODS - FILTERS
// ============================================================================
const handleFilterChange = () => {
    isLoading.value = true;
    
    const params: Record<string, any> = {
        machine_type: currentFilters.value.machine_type,
        status: currentFilters.value.status,
        location: currentFilters.value.location,
        search: currentFilters.value.search,
        capa_type: currentFilters.value.capa_type,
    };
    
    if (selectedBarFilter.value?.machineType) {
        params.machine_type_filter = selectedBarFilter.value.machineType;
    }
    if (selectedBarFilter.value?.size) {
        params.size_filter = selectedBarFilter.value.size;
    }
    
    router.get('/mc-allocation', params, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { isLoading.value = false; },
    });
};

const handleReset = () => {
    currentFilters.value = {
        machine_type: 'ALL',
        status: 'OPERATIONAL',
        location: 'ALL',
        search: '',
        capa_type: 'oee_capa',
    };
    selectedBarFilter.value = null;
    handleFilterChange();
};

const handleSort = (field: string) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const clearBarFilter = () => {
    selectedBarFilter.value = null;
    handleFilterChange();
};

const resetTableFilters = () => {
    tableFilters.value = { line: 'ALL', area: 'ALL', size: 'ALL', status: 'ALL' };
};

const openViewModal = (allocation: Allocation) => {
    selectedAllocation.value = allocation;
    viewModalOpen.value = true;
};

const openEditModal = (allocation: Allocation) => {
    selectedAllocation.value = allocation;
    editModalOpen.value = true;
};

const handleEditSaved = () => {
    handleFilterChange();
};

const handleDelete = (allocation: Allocation) => {
    if (!confirm(`Are you sure you want to delete machine ${allocation.eqp_no}?`)) {
        return;
    }

    router.delete(`/mc-allocation/${allocation.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            handleFilterChange();
        },
    });
};

// ============================================================================
// METHODS - FORMATTING
// ============================================================================
const formatNumber = (value: number): string => value.toLocaleString('en-US');

const formatCapacity = (value: number): string => {
    const millions = value / 1000000;
    return millions.toLocaleString('en-US', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    }) + 'M';
};

const getSizeDisplayLabel = (dbValue: string): string => {
    return Object.entries(SIZE_MAPPING).find(([, v]) => v === dbValue)?.[0] || dbValue;
};


// ============================================================================
// METHODS - CHARTS
// ============================================================================
const initCharts = () => {
    initAllocationChart();
    initStatusChart();
};

const initAllocationChart = () => {
    if (!allocationChartRef.value) return;
    
    if (allocationChart) allocationChart.dispose();
    allocationChart = echarts.init(allocationChartRef.value, isDarkMode.value ? 'dark' : undefined);

    const labelOption = {
        show: true,
        position: 'insideBottom',
        distance: 5,
        align: 'left',
        verticalAlign: 'middle',
        rotate: 90,
        formatter: (params: any) => {
            const count = typeof params.data === 'object' ? (params.data.value || 0) : params.data;
            const capacity = typeof params.data === 'object' ? (params.data.capacity || 0) : 0;
            const capacityM = (capacity / 1000000).toFixed(1);
            return `${params.seriesName}: ${count} : ${capacityM}M`;
        },
        fontSize: 10,
        color: isDarkMode.value ? '#e5e7eb' : '#374151',
    };

    const option = {
        backgroundColor: 'transparent',
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
            formatter: (params: any) => {
                let result = `<b>Size: ${params[0].axisValueLabel}</b> (Click to filter)<br/>`;
                params.forEach((item: any) => {
                    const count = typeof item.data === 'object' ? (item.data.value || 0) : item.data;
                    const capacity = typeof item.data === 'object' ? (item.data.capacity || 0) : 0;
                    const capacityM = (capacity / 1000000).toFixed(1);
                    result += `${item.marker} <b>${item.seriesName}</b>: ${count} : ${capacityM}M<br/>`;
                });
                return result;
            },
        },
        legend: {
            data: props.stats.bar_chart.series.map((s: any) => s.name),
            top: '0%',
            left: 'center',
        },
        grid: { left: '3%', right: '3%', bottom: '3%', top: '15%', containLabel: true },
        xAxis: [{
            type: 'category',
            axisTick: { show: false },
            data: props.stats.bar_chart.categories,
            splitLine: { lineStyle: { type: 'dashed', color: 'rgba(142, 156, 173,0.1)' } },
            axisLabel: {
                fontSize: 14,
                fontWeight: 'bold',
                color: isDarkMode.value ? '#e5e7eb' : '#1f2937',
                interval: 0,
                padding: [8, 12, 8, 12],
                backgroundColor: isDarkMode.value ? '#374151' : '#f3f4f6',
                borderRadius: 6,
                borderColor: isDarkMode.value ? '#4b5563' : '#d1d5db',
                borderWidth: 1,
            },
            triggerEvent: true,
        }],
        yAxis: [{ type: 'value', splitLine: { lineStyle: { color: 'rgba(142, 156, 173,0.1)' } } }],
        series: props.stats.bar_chart.series.map((s: any) => ({
            ...s,
            label: labelOption,
            barMaxWidth: 35,
            barCategoryGap: '25%',
        })),
        color: CHART_COLORS,
    };

    allocationChart.setOption(option);
    setupAllocationChartEvents();
};

const setupAllocationChartEvents = () => {
    if (!allocationChart || !allocationChartRef.value) return;

    allocationChart.on('click', (params: any) => {
        if (params.componentType === 'series') {
            const machineType = params.seriesName;
            const size = SIZE_MAPPING[params.name] || params.name;
            
            if (selectedBarFilter.value?.machineType === machineType && selectedBarFilter.value?.size === size) {
                selectedBarFilter.value = null;
            } else {
                selectedBarFilter.value = { machineType, size };
            }
            handleFilterChange();
        } else if (params.componentType === 'xAxis') {
            const size = SIZE_MAPPING[params.value] || params.value;
            
            if (selectedBarFilter.value?.size === size && !selectedBarFilter.value?.machineType) {
                selectedBarFilter.value = null;
            } else {
                selectedBarFilter.value = { size };
            }
            handleFilterChange();
        }
    });

    allocationChart.on('mouseover', (params: any) => {
        if (params.componentType === 'xAxis') {
            allocationChartRef.value!.style.cursor = 'pointer';
        }
    });

    allocationChart.on('mouseout', (params: any) => {
        if (params.componentType === 'xAxis') {
            allocationChartRef.value!.style.cursor = 'default';
        }
    });
};

const initStatusChart = () => {
    if (!statusChartRef.value) return;
    
    if (statusChart) statusChart.dispose();
    statusChart = echarts.init(statusChartRef.value, isDarkMode.value ? 'dark' : undefined);

    let chartData = props.stats.donut_chart;
    if (selectedSize.value !== 'ALL' && props.stats.donut_chart_by_size) {
        chartData = props.stats.donut_chart_by_size[selectedSize.value] || props.stats.donut_chart;
    }

    const total = chartData.reduce((sum: number, item: any) => sum + item.value, 0);

    const option = {
        backgroundColor: 'transparent',
        tooltip: { show: false },
        legend: { show: false },
        title: {
            text: `Total\n${total.toLocaleString()} Units`,
            left: 'center',
            top: 'center',
            textStyle: {
                fontSize: 16,
                fontWeight: 'bold',
                color: isDarkMode.value ? '#e5e7eb' : '#374151',
            },
        },
        series: [{
            name: 'Status',
            type: 'pie',
            radius: ['60%', '90%'],
            avoidLabelOverlap: false,
            selectedMode: false,
            label: { show: false },
            emphasis: { label: { show: false } },
            labelLine: { show: false },
            data: chartData,
        }],
        color: STATUS_COLORS,
    };

    statusChart.setOption(option);
    setupStatusChartEvents(total);
};

const setupStatusChartEvents = (total: number) => {
    if (!statusChart) return;

    statusChart.on('mouseover', (params: any) => {
        if (params.componentType === 'series') {
            statusChart!.setOption({
                title: {
                    text: `${params.name}\n${params.value.toLocaleString()} Units\n(${params.percent.toFixed(2)}%)`,
                    textStyle: { fontSize: 18 },
                },
            });
        }
    });

    statusChart.on('mouseout', () => {
        statusChart!.setOption({
            title: {
                text: `Total\n${total.toLocaleString()} Units`,
                textStyle: { fontSize: 16 },
            },
        });
    });

    statusChart.on('click', (params: any) => {
        if (params.componentType === 'series') {
            currentFilters.value.status = params.name;
            handleFilterChange();
        }
    });
};


// ============================================================================
// WATCHERS
// ============================================================================
watch(() => props.filters, (newFilters) => {
    currentFilters.value = {
        machine_type: newFilters.machine_type || 'ALL',
        status: newFilters.status || 'OPERATIONAL',
        location: newFilters.location || 'ALL',
        search: newFilters.search || '',
        capa_type: newFilters.capa_type || 'oee_capa',
    };
}, { deep: true });

watch(() => props.stats, () => initCharts(), { deep: true });
watch(isDarkMode, () => initCharts());
watch(selectedSize, () => initCharts());

// ============================================================================
// LIFECYCLE
// ============================================================================
onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark');

    nextTick(() => {
        initCharts();

        // Resize observer
        resizeObserver = new ResizeObserver(() => {
            allocationChart?.resize();
            statusChart?.resize();
        });

        if (allocationChartRef.value) resizeObserver.observe(allocationChartRef.value);
        if (statusChartRef.value) resizeObserver.observe(statusChartRef.value);

        window.addEventListener('resize', () => {
            allocationChart?.resize();
            statusChart?.resize();
        });

        // Theme observer
        const themeObserver = new MutationObserver(() => {
            const newDarkMode = document.documentElement.classList.contains('dark');
            if (newDarkMode !== isDarkMode.value) isDarkMode.value = newDarkMode;
        });

        themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });

        (window as any).__themeObserver = themeObserver;
    });
});

onUnmounted(() => {
    resizeObserver?.disconnect();
    allocationChart?.dispose();
    statusChart?.dispose();
    
    if ((window as any).__themeObserver) {
        (window as any).__themeObserver.disconnect();
        delete (window as any).__themeObserver;
    }
});
</script>

<template>
    <Head title="MC ALLOCATION" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- FILTERS SECTION -->
        <template #filters>
            <div class="flex items-center gap-3">
                <!-- Last Update -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm">
                    <span class="text-xs font-medium text-muted-foreground">LAST UPDATE:</span>
                    <span class="text-xs font-semibold text-foreground">{{ stats.last_update }}</span>
                </div>

                <!-- Status Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm">
                    <span class="text-xs font-medium text-muted-foreground">STATUS:</span>
                    <select
                        v-model="currentFilters.status"
                        @change="handleFilterChange"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground"
                    >
                        <option value="ALL">ALL</option>
                        <option v-for="status in filterOptions.statuses" :key="status" :value="status">
                            {{ status }}
                        </option>
                    </select>
                </div>

                <!-- MC Type Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm">
                    <span class="text-xs font-medium text-muted-foreground">MC TYPE:</span>
                    <select
                        v-model="currentFilters.machine_type"
                        @change="handleFilterChange"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground"
                    >
                        <option value="ALL">ALL</option>
                        <option v-for="type in filterOptions.machine_types" :key="type" :value="type">
                            {{ type }}
                        </option>
                    </select>
                </div>

                <!-- Capa Type Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm">
                    <span class="text-xs font-medium text-muted-foreground">CAPA TYPE:</span>
                    <select
                        v-model="currentFilters.capa_type"
                        @change="handleFilterChange"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground"
                    >
                        <option value="ideal_capa">IDEAL</option>
                        <option value="oee_capa">OEE</option>
                        <option value="output_capa">OUTPUT</option>
                    </select>
                </div>

                <!-- Allocation Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm">
                    <span class="text-xs font-medium text-muted-foreground">ALLOCATION:</span>
                    <select
                        v-model="currentFilters.location"
                        @change="handleFilterChange"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground"
                    >
                        <option value="ALL">ALL</option>
                        <option v-for="location in filterOptions.locations" :key="location" :value="location">
                            {{ location }}
                        </option>
                    </select>
                </div>

                <!-- Refresh Button -->
                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 px-3 text-xs font-medium shadow-sm"
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


        <!-- MAIN CONTENT -->
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
            <div class="grid grid-cols-1 gap-4">
                <div class="grid grid-cols-1 gap-4 2xl:grid-cols-12">
                    <!-- LEFT SECTION -->
                    <div class="2xl:col-span-9">
                        <div class="grid grid-cols-1 gap-4">
                            <!-- STATS CARDS -->
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-4">
                                <!-- Total Machines -->
                                <div class="group cursor-pointer rounded-lg bg-gradient-to-br from-purple-50 to-purple-100/50 dark:from-purple-950/30 dark:to-purple-900/20 p-4 shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-500/10 ring-1 ring-purple-500/20 text-purple-600 dark:text-purple-400 transition-all duration-200 group-hover:scale-110">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M20 6H4V4H20V6ZM20 10H4V8H20V10ZM20 14H4V12H20V14ZM20 18H4V16H20V18Z"/>
                                            </svg>
                                        </span>
                                        <div>
                                            <span class="block text-xs font-medium text-purple-700 dark:text-purple-300">TOTAL MACHINES</span>
                                            <h5 class="text-2xl font-bold text-gray-900 dark:text-white">
                                                {{ formatCapacity(stats.cards.total_machines.capacity) }}
                                            </h5>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatNumber(stats.cards.total_machines.count) }} Units
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Normal -->
                                <div class="group cursor-pointer rounded-lg bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-950/30 dark:to-blue-900/20 p-4 shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-500/10 ring-1 ring-blue-500/20 text-blue-600 dark:text-blue-400 transition-all duration-200 group-hover:scale-110">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z"/>
                                            </svg>
                                        </span>
                                        <div>
                                            <span class="block text-xs font-medium text-blue-700 dark:text-blue-300">NORMAL</span>
                                            <h5 class="text-2xl font-bold text-gray-900 dark:text-white">
                                                {{ formatCapacity(stats.cards.allocated.capacity) }}
                                            </h5>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatNumber(stats.cards.allocated.count) }} Units
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Warehouse -->
                                <div class="group cursor-pointer rounded-lg bg-gradient-to-br from-emerald-50 to-emerald-100/50 dark:from-emerald-950/30 dark:to-emerald-900/20 p-4 shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-500/10 ring-1 ring-emerald-500/20 text-emerald-600 dark:text-emerald-400 transition-all duration-200 group-hover:scale-110">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V11H13V17ZM13 9H11V7H13V9Z"/>
                                            </svg>
                                        </span>
                                        <div>
                                            <span class="block text-xs font-medium text-emerald-700 dark:text-emerald-300">WAREHOUSE</span>
                                            <h5 class="text-2xl font-bold text-gray-900 dark:text-white">
                                                {{ formatCapacity(stats.cards.available.capacity) }}
                                            </h5>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatNumber(stats.cards.available.count) }} Units
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PR / RL / LY -->
                                <div class="group cursor-pointer rounded-lg bg-gradient-to-br from-orange-50 to-orange-100/50 dark:from-orange-950/30 dark:to-orange-900/20 p-4 shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-500/10 ring-1 ring-orange-500/20 text-orange-600 dark:text-orange-400 transition-all duration-200 group-hover:scale-110">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M22.7 19L13.6 9.9C14.5 7.6 14 4.9 12.1 3C10.1 1 7.1 0.6 4.7 1.7L9 6L6 9L1.6 4.7C0.4 7.1 0.9 10.1 2.9 12.1C4.8 14 7.5 14.5 9.8 13.6L18.9 22.7C19.3 23.1 19.9 23.1 20.3 22.7L22.6 20.4C23.1 20 23.1 19.3 22.7 19Z"/>
                                            </svg>
                                        </span>
                                        <div>
                                            <span class="block text-xs font-medium text-orange-700 dark:text-orange-300">PR / RL / LY</span>
                                            <h5 class="text-2xl font-bold text-gray-900 dark:text-white">
                                                {{ formatCapacity(stats.cards.maintenance.capacity) }}
                                            </h5>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatNumber(stats.cards.maintenance.count) }} Units
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BAR CHART -->
                            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                                <div class="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white p-4 dark:border-gray-700 dark:from-gray-800 dark:to-gray-900">
                                    <div class="text-sm font-bold uppercase tracking-wide text-gray-700 dark:text-gray-200">MACHINE ALLOCATION</div>
                                </div>
                                <div class="p-4">
                                    <div ref="allocationChartRef" style="width: 100%; height: 318px"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT SECTION - DONUT CHART -->
                    <div class="2xl:col-span-3">
                        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                            <div class="flex items-center justify-between border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white p-4 dark:border-gray-700 dark:from-gray-800 dark:to-gray-900">
                                <div class="text-sm font-bold uppercase tracking-wide text-gray-700 dark:text-gray-200">MACHINE STATUS</div>
                                <div class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 shadow-sm dark:border-gray-600 dark:bg-gray-800">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">SIZE:</span>
                                    <select
                                        v-model="selectedSize"
                                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-gray-900 focus:ring-0 focus:outline-none dark:text-gray-100 [&>option]:bg-white [&>option]:text-gray-900 dark:[&>option]:bg-gray-800 dark:[&>option]:text-gray-100"
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
                            </div>
                            <div class="p-4">
                                <div ref="statusChartRef" style="width: 100%; height: 300px"></div>
                            </div>
                            <div class="border-t border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50">
                                <div class="grid grid-cols-3 gap-px bg-gray-200 dark:bg-gray-700">
                                    <div
                                        v-for="(item, index) in stats.donut_chart"
                                        :key="index"
                                        class="flex flex-col items-center justify-center bg-white py-1 px-4 transition-all hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-750"
                                    >
                                        <div class="mb-1 text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(item.value) }}</div>
                                        <div class="text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">{{ item.name }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- DATA TABLE -->
                <div class="grid grid-cols-1">
                    <div class="flex flex-col rounded-lg bg-white shadow dark:bg-gray-800" style="height: 500px">
                        <!-- Table Header -->
                        <div class="flex flex-shrink-0 items-center justify-between border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white p-4 dark:border-gray-700 dark:from-gray-800 dark:to-gray-900">
                            <div class="flex items-center gap-3">
                                <div class="text-sm font-bold uppercase tracking-wide text-gray-700 dark:text-gray-200">MACHINE LIST - RAW DATA</div>
                                <div v-if="selectedBarFilter" class="flex items-center gap-2 rounded-lg bg-blue-100 dark:bg-blue-900 px-3 py-1.5 text-xs">
                                    <span class="font-medium text-blue-900 dark:text-blue-100">
                                        Filtered: 
                                        <span v-if="selectedBarFilter.machineType" class="font-semibold">{{ selectedBarFilter.machineType }}</span>
                                        <span v-if="selectedBarFilter.machineType && selectedBarFilter.size"> - </span>
                                        <span v-if="selectedBarFilter.size" class="font-semibold">Size: {{ getSizeDisplayLabel(selectedBarFilter.size) }}</span>
                                    </span>
                                    <button @click="clearBarFilter" class="ml-1 text-blue-700 dark:text-blue-300 hover:text-blue-900" title="Clear filter">✕</button>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Table Filters -->
                                <div class="flex items-center gap-1 rounded border border-gray-300 bg-gray-50 px-2 py-1 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Line:</span>
                                    <select v-model="tableFilters.line" class="cursor-pointer border-0 bg-transparent py-0 pr-6 text-xs font-semibold text-gray-900 focus:ring-0 focus:outline-none dark:text-gray-100">
                                        <option value="ALL">ALL</option>
                                        <option v-for="line in uniqueLines" :key="line" :value="line">{{ line }}</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-1 rounded border border-gray-300 bg-gray-50 px-2 py-1 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Area:</span>
                                    <select v-model="tableFilters.area" class="cursor-pointer border-0 bg-transparent py-0 pr-6 text-xs font-semibold text-gray-900 focus:ring-0 focus:outline-none dark:text-gray-100">
                                        <option value="ALL">ALL</option>
                                        <option v-for="area in uniqueAreas" :key="area" :value="area">{{ area }}</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-1 rounded border border-gray-300 bg-gray-50 px-2 py-1 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Size:</span>
                                    <select v-model="tableFilters.size" class="cursor-pointer border-0 bg-transparent py-0 pr-6 text-xs font-semibold text-gray-900 focus:ring-0 focus:outline-none dark:text-gray-100">
                                        <option value="ALL">ALL</option>
                                        <option v-for="size in uniqueSizes" :key="size" :value="size">{{ size }}</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-1 rounded border border-gray-300 bg-gray-50 px-2 py-1 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Status:</span>
                                    <select v-model="tableFilters.status" class="cursor-pointer border-0 bg-transparent py-0 pr-6 text-xs font-semibold text-gray-900 focus:ring-0 focus:outline-none dark:text-gray-100">
                                        <option value="ALL">ALL</option>
                                        <option v-for="status in uniqueStatuses" :key="status" :value="status">{{ status }}</option>
                                    </select>
                                </div>
                                <button 
                                    v-if="tableFilters.line !== 'ALL' || tableFilters.area !== 'ALL' || tableFilters.size !== 'ALL' || tableFilters.status !== 'ALL'"
                                    @click="resetTableFilters" 
                                    class="rounded bg-gray-200 px-2 py-1 text-xs text-gray-700 hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500"
                                    title="Reset table filters"
                                >
                                    ✕ Clear
                                </button>

                                <div class="mx-1 h-6 w-px bg-gray-300 dark:bg-gray-600"></div>

                                <input
                                    v-model="currentFilters.search"
                                    @keyup.enter="handleFilterChange"
                                    class="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    type="text"
                                    placeholder="Search Here"
                                />
                                <button class="rounded bg-blue-500 px-3 py-1 text-sm text-white hover:bg-blue-600" @click="handleFilterChange">Search</button>
                                <a :href="exportUrl" class="inline-flex items-center gap-1 rounded bg-green-600 px-3 py-1 text-sm text-white hover:bg-green-700" title="Export to CSV">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    Export
                                </a>
                            </div>
                        </div>

                        <!-- Table Content -->
                        <div class="flex-1 overflow-hidden p-0">
                            <div class="h-full overflow-auto">
                                <table class="w-full text-sm">
                                    <thead class="sticky top-0 z-10">
                                        <tr class="border-b border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950">
                                            <th class="px-2 py-3 text-center whitespace-nowrap font-semibold text-blue-900 dark:text-blue-100">No.</th>
                                            <th @click="handleSort('eqp_no')" class="cursor-pointer px-3 py-3 text-left whitespace-nowrap font-semibold text-blue-900 dark:text-blue-100 hover:bg-blue-100 dark:hover:bg-blue-900">
                                                <div class="flex items-center gap-1">
                                                    MC No.
                                                    <span class="text-xs">
                                                        <span v-if="sortField === 'eqp_no'">{{ sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                                        <span v-else class="opacity-30">⇅</span>
                                                    </span>
                                                </div>
                                            </th>
                                            <th @click="handleSort('eqp_line')" class="cursor-pointer px-3 py-3 text-left whitespace-nowrap font-semibold text-blue-900 dark:text-blue-100 hover:bg-blue-100 dark:hover:bg-blue-900">
                                                <div class="flex items-center gap-1">
                                                    Line
                                                    <span class="text-xs">
                                                        <span v-if="sortField === 'eqp_line'">{{ sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                                        <span v-else class="opacity-30">⇅</span>
                                                    </span>
                                                </div>
                                            </th>
                                            <th @click="handleSort('eqp_area')" class="cursor-pointer px-3 py-3 text-left whitespace-nowrap font-semibold text-blue-900 dark:text-blue-100 hover:bg-blue-100 dark:hover:bg-blue-900">
                                                <div class="flex items-center gap-1">
                                                    Area
                                                    <span class="text-xs">
                                                        <span v-if="sortField === 'eqp_area'">{{ sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                                        <span v-else class="opacity-30">⇅</span>
                                                    </span>
                                                </div>
                                            </th>
                                            <th @click="handleSort('eqp_maker')" class="cursor-pointer px-3 py-3 text-left whitespace-nowrap font-semibold text-blue-900 dark:text-blue-100 hover:bg-blue-100 dark:hover:bg-blue-900">
                                                <div class="flex items-center gap-1">
                                                    MC Type
                                                    <span class="text-xs">
                                                        <span v-if="sortField === 'eqp_maker'">{{ sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                                        <span v-else class="opacity-30">⇅</span>
                                                    </span>
                                                </div>
                                            </th>
                                            <th @click="handleSort('size')" class="cursor-pointer px-3 py-3 text-left whitespace-nowrap font-semibold text-blue-900 dark:text-blue-100 hover:bg-blue-100 dark:hover:bg-blue-900">
                                                <div class="flex items-center gap-1">
                                                    Size
                                                    <span class="text-xs">
                                                        <span v-if="sortField === 'size'">{{ sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                                        <span v-else class="opacity-30">⇅</span>
                                                    </span>
                                                </div>
                                            </th>
                                            <th @click="handleSort('alloc_type')" class="cursor-pointer px-3 py-3 text-left whitespace-nowrap font-semibold text-blue-900 dark:text-blue-100 hover:bg-blue-100 dark:hover:bg-blue-900">
                                                <div class="flex items-center gap-1">
                                                    Allocation
                                                    <span class="text-xs">
                                                        <span v-if="sortField === 'alloc_type'">{{ sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                                        <span v-else class="opacity-30">⇅</span>
                                                    </span>
                                                </div>
                                            </th>
                                            <th @click="handleSort('eqp_status')" class="cursor-pointer px-3 py-3 text-left whitespace-nowrap font-semibold text-blue-900 dark:text-blue-100 hover:bg-blue-100 dark:hover:bg-blue-900">
                                                <div class="flex items-center gap-1">
                                                    MC Status
                                                    <span class="text-xs">
                                                        <span v-if="sortField === 'eqp_status'">{{ sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                                        <span v-else class="opacity-30">⇅</span>
                                                    </span>
                                                </div>
                                            </th>
                                            <th @click="handleSort('ideal_capa')" class="cursor-pointer px-2 py-3 text-right whitespace-nowrap font-semibold text-blue-900 dark:text-blue-100 hover:bg-blue-100 dark:hover:bg-blue-900" style="min-width: 90px">
                                                <div class="flex items-center justify-end gap-1">
                                                    Ideal Capa
                                                    <span class="text-xs">
                                                        <span v-if="sortField === 'ideal_capa'">{{ sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                                        <span v-else class="opacity-30">⇅</span>
                                                    </span>
                                                </div>
                                            </th>
                                            <th @click="handleSort('oee_capa')" class="cursor-pointer px-2 py-3 text-right whitespace-nowrap font-semibold text-blue-900 dark:text-blue-100 hover:bg-blue-100 dark:hover:bg-blue-900" style="min-width: 90px">
                                                <div class="flex items-center justify-end gap-1">
                                                    OEE Capa
                                                    <span class="text-xs">
                                                        <span v-if="sortField === 'oee_capa'">{{ sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                                        <span v-else class="opacity-30">⇅</span>
                                                    </span>
                                                </div>
                                            </th>
                                            <th @click="handleSort('output_capa')" class="cursor-pointer px-2 py-3 text-right whitespace-nowrap font-semibold text-blue-900 dark:text-blue-100 hover:bg-blue-100 dark:hover:bg-blue-900" style="min-width: 90px">
                                                <div class="flex items-center justify-end gap-1">
                                                    Output Capa
                                                    <span class="text-xs">
                                                        <span v-if="sortField === 'output_capa'">{{ sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                                        <span v-else class="opacity-30">⇅</span>
                                                    </span>
                                                </div>
                                            </th>
                                            <th class="px-3 py-3 text-center whitespace-nowrap font-semibold text-blue-900 dark:text-blue-100" style="min-width: 150px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(allocation, index) in sortedAllocations"
                                            :key="allocation.id"
                                            class="border-b border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                                        >
                                            <td class="px-2 py-3 text-center">{{ index + 1 }}</td>
                                            <td class="px-3 py-3">{{ allocation.eqp_no }}</td>
                                            <td class="px-3 py-3">{{ allocation.eqp_line }}</td>
                                            <td class="px-3 py-3">{{ allocation.eqp_area }}</td>
                                            <td class="px-3 py-3">{{ allocation.eqp_maker }}</td>
                                            <td class="px-3 py-3">{{ allocation.size }}</td>
                                            <td class="px-3 py-3">{{ allocation.alloc_type }}</td>
                                            <td class="px-3 py-3">{{ allocation.eqp_status }}</td>
                                            <td class="px-2 py-3 text-right text-xs">{{ formatCapacity(allocation.ideal_capa) }}</td>
                                            <td class="px-2 py-3 text-right text-xs">{{ formatCapacity(allocation.oee_capa) }}</td>
                                            <td class="px-2 py-3 text-right text-xs">{{ formatCapacity(allocation.output_capa) }}</td>
                                            <td class="px-2 py-3">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <!-- View Button - All users -->
                                                    <button @click="openViewModal(allocation)" class="rounded bg-blue-500 px-2.5 py-1.5 text-xs text-white hover:bg-blue-600 transition-colors shadow-sm" title="View Details">
                                                        👁️
                                                    </button>
                                                    <!-- Edit Button - Manager and Admin only -->
                                                    <button v-if="canEdit" @click="openEditModal(allocation)" class="rounded bg-green-500 px-2.5 py-1.5 text-xs text-white hover:bg-green-600 transition-colors shadow-sm" title="Edit">
                                                        ✏️
                                                    </button>
                                                    <!-- Delete Button - Admin only -->
                                                    <button v-if="canDelete" @click="handleDelete(allocation)" class="rounded bg-red-500 px-2.5 py-1.5 text-xs text-white hover:bg-red-600 transition-colors shadow-sm" title="Delete">
                                                        🗑️
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="sortedAllocations.length === 0">
                                            <td colspan="12" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">No data found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex-shrink-0 border-t border-gray-200 px-4 py-2 dark:border-gray-700">
                            <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                                <div>
                                    Showing {{ sortedAllocations.length }} of {{ allocations.length }} Entries
                                    <span v-if="tableFilters.line !== 'ALL' || tableFilters.area !== 'ALL' || tableFilters.size !== 'ALL' || tableFilters.status !== 'ALL'" class="text-blue-600 dark:text-blue-400">(filtered)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <McAllocationViewModal
            v-model:open="viewModalOpen"
            :allocation="selectedAllocation"
        />

        <!-- Edit Modal -->
        <McAllocationEditModal
            v-model:open="editModalOpen"
            :allocation="selectedAllocation"
            :filter-options="filterOptions"
            @saved="handleEditSaved"
        />
    </AppLayout>
</template>
