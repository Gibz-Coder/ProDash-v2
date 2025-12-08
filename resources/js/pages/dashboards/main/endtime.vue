<script setup lang="ts">
// ============================================================================
// IMPORTS
// ============================================================================
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApexCharts from 'apexcharts';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import EquipmentModal from '@/pages/dashboards/subs/equipment-modal.vue';
import RemainingLotsModal from '@/pages/dashboards/subs/remaining-lots-modal.vue';
import EndtimeSubmitModal from '@/pages/dashboards/subs/endtime-submit-modal.vue';

// ============================================================================
// PROPS
// ============================================================================

// ============================================================================
// Chart height: 316 & 479
// ============================================================================
const props = defineProps<{
    filters: {
        date: string;
        shift: string;
        cutoff: string;
        worktype: string;
        refresh: boolean;
        line: string;
    };
    metrics: {
        targetCapacity: number;
        targetCapacityFormatted: string;
        targetCapacitySubtitle: string;
        targetCapacityCount: number;
        targetCapacityTotal: number;
        totalEndtime: number;
        totalEndtimeFormatted: string;
        totalEndtimeSubtitle: string;
        totalEndtimeLotCount: number;
        totalSubmitted: number;
        totalSubmittedFormatted: string;
        totalSubmittedSubtitle: string;
        totalSubmittedLotCount: number;
        totalRemaining: number;
        totalRemainingFormatted: string;
        totalRemainingSubtitle: string;
        totalRemainingLotCount: number;
    };
    productionData: Array<{
        month: string;
        target: number;
        endtime: number;
        submitted: number;
        remaining: number;
    }>;
    lineData: Array<{
        metric: string;
        total: number;
        lineA: number;
        lineB: number;
        lineC: number;
        lineD: number;
        lineE: number;
        lineF: number;
        lineG: number;
        lineH: number;
        lineI: number;
        lineJ: number;
        lineK: number;
    }>;
    sizeData: Array<{
        metric: string;
        size0603: number;
        size1005: number;
        size1608: number;
        size2012: number;
        size3216: number;
        size3225: number;
    }>;
    machineStats: {
        total: number;
        withLot: number;
        withoutLot: number;
        utilizationPercentage: number;
    };
}>();

// ============================================================================
// CONSTANTS
// ============================================================================
const CHART_COLORS = {
    target: 'rgba(253, 175, 34, 1)',
    endtime: 'rgba(152, 95, 253, 1)',
    submitted: 'rgba(50, 212, 132, 1)',
    remaining: 'rgba(255, 73, 205, 1)',
};

// Use machine stats from props (real data from database)
const machineStats = ref(props.machineStats);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: dashboard().url },
    { title: 'ENDTIME', href: '/endtime' },
];

// ============================================================================
// STATE
// ============================================================================
const isDarkMode = ref(false);
const isLoading = ref(false);
let refreshInterval: number | null = null;
let keepAliveInterval: number | null = null;
const refreshIntervalSeconds = ref(30); // Default 30 seconds

// Reactive filters
const currentFilters = ref({
    date: props.filters.date,
    shift: props.filters.shift,
    cutoff: props.filters.cutoff,
    worktype: props.filters.worktype,
    refresh: props.filters.refresh,
    line: props.filters.line,
});

// Equipment line filter
const selectedLine = ref(props.filters.line || 'ALL');
const equipmentLines = ['ALL', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];

const navigateLine = (direction: 'prev' | 'next') => {
    const currentIndex = equipmentLines.indexOf(selectedLine.value);
    if (direction === 'prev' && currentIndex > 0) {
        selectedLine.value = equipmentLines[currentIndex - 1];
    } else if (direction === 'next' && currentIndex < equipmentLines.length - 1) {
        selectedLine.value = equipmentLines[currentIndex + 1];
    }
    // Update filter and fetch new data
    currentFilters.value.line = selectedLine.value;
    handleFilterChange();
};

// Equipment modal state
const showEquipmentModal = ref(false);
const equipmentModalType = ref<'with' | 'without'>('with');
const equipmentList = ref<any[]>([]);
const isLoadingEquipment = ref(false);

// Remaining lots modal state
const showRemainingLotsModal = ref(false);
const remainingLotsList = ref<any[]>([]);
const isLoadingRemainingLots = ref(false);
const includePreviousDateLots = ref(false);

// Submit modal state
const showSubmitModal = ref(false);

const openEquipmentModal = async (type: 'with' | 'without') => {
    equipmentModalType.value = type;
    isLoadingEquipment.value = true;
    showEquipmentModal.value = true;
    
    try {
        const response = await fetch(`/endtime/equipment-list?worktype=${currentFilters.value.worktype}&line=${currentFilters.value.line}&type=${type}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        
        const data = await response.json();
        equipmentList.value = data.equipment || [];
    } catch (error) {
        equipmentList.value = [];
    } finally {
        isLoadingEquipment.value = false;
    }
};

const equipmentModalTitle = computed(() => {
    const lineText = currentFilters.value.line === 'ALL' ? 'All Lines' : `Line ${currentFilters.value.line}`;
    return equipmentModalType.value === 'with' 
        ? `Equipment With Ongoing Lot - ${lineText}`
        : `Equipment Without Ongoing Lot - ${lineText}`;
});

const fetchRemainingLots = async (includePrevious: boolean = false) => {
    isLoadingRemainingLots.value = true;
    
    try {
        // Pass production date directly - backend handles the date range logic
        const response = await fetch(`/endtime/remaining-lots-list?date=${currentFilters.value.date}&shift=${currentFilters.value.shift}&cutoff=${currentFilters.value.cutoff}&worktype=${currentFilters.value.worktype}&includePrevious=${includePrevious}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        
        const data = await response.json();
        remainingLotsList.value = data.remainingLots || [];
    } catch (error) {
        remainingLotsList.value = [];
    } finally {
        isLoadingRemainingLots.value = false;
    }
};

const openRemainingLotsModal = async () => {
    showRemainingLotsModal.value = true;
    includePreviousDateLots.value = false;
    // Fetch remaining lots using current filter values (date, shift, cutoff, worktype)
    // This ensures the modal shows data consistent with the displayed metrics
    await fetchRemainingLots(false);
};

const handleTogglePreviousDate = async (includesPrevious: boolean) => {
    includePreviousDateLots.value = includesPrevious;
    await fetchRemainingLots(includesPrevious);
};

const remainingLotsModalTitle = computed(() => {
    return `Remaining Lots - ${currentFilters.value.date}`;
});

// Computed date range description for tooltip
// Production Date Logic:
// - DAY shift: Production date = same calendar date (07:00-18:59)
// - NIGHT shift: Production date = date when shift STARTS (production date 19:00 - next day 06:59)
const getDateRangeDescription = computed(() => {
    const date = currentFilters.value.date;
    const shift = currentFilters.value.shift;
    const cutoff = currentFilters.value.cutoff;
    
    // Helper to get next day - more robust calculation
    const getNextDay = (d: string) => {
        const parts = d.split('-');
        const year = parseInt(parts[0]);
        const month = parseInt(parts[1]) - 1; // JS months are 0-indexed
        const day = parseInt(parts[2]);
        const dateObj = new Date(year, month, day);
        dateObj.setDate(dateObj.getDate() + 1);
        const nextYear = dateObj.getFullYear();
        const nextMonth = String(dateObj.getMonth() + 1).padStart(2, '0');
        const nextDay = String(dateObj.getDate()).padStart(2, '0');
        return `${nextYear}-${nextMonth}-${nextDay}`;
    };
    
    const nextDay = getNextDay(date);
    
    if (shift === 'ALL' && cutoff === 'ALL') {
        // Full production day: 07:00 same day - 06:59 next day
        return `${date} 07:00 ~ ${nextDay} 06:59`;
    }
    
    if (shift === 'DAY') {
        if (cutoff === 'ALL') return `${date} 07:00 ~ 18:59`;
        if (cutoff === '1ST') return `${date} 07:00 ~ 11:59`;
        if (cutoff === '2ND') return `${date} 12:00 ~ 15:59`;
        if (cutoff === '3RD') return `${date} 16:00 ~ 18:59`;
    }
    
    if (shift === 'NIGHT') {
        // NIGHT shift: production date 19:00 - next day 06:59
        if (cutoff === 'ALL') return `${date} 19:00 ~ ${nextDay} 06:59`;
        if (cutoff === '1ST') return `${date} 19:00 ~ 23:59`;
        if (cutoff === '2ND') return `${nextDay} 00:00 ~ 03:59`;
        if (cutoff === '3RD') return `${nextDay} 04:00 ~ 06:59`;
    }
    
    if (shift === 'ALL' && cutoff !== 'ALL') {
        // Both DAY and NIGHT portions for the production date
        if (cutoff === '1ST') return `${date} 07:00~11:59 & ${date} 19:00~23:59`;
        if (cutoff === '2ND') return `${date} 12:00~15:59 & ${nextDay} 00:00~03:59`;
        if (cutoff === '3RD') return `${date} 16:00~18:59 & ${nextDay} 04:00~06:59`;
    }
    
    return `${date}`;
});

// Computed metrics with real data
const metrics = computed(() => [
    {
        title: 'Target Capacity',
        value: props.metrics.targetCapacityFormatted,
        subtitle: props.metrics.targetCapacitySubtitle,
        icon: '🎯',
        color: 'bg-gradient-to-br from-orange-50 to-orange-100/50 dark:from-orange-950/30 dark:to-orange-900/20',
        textColor: 'text-orange-700 dark:text-orange-300',
        valueColor: 'text-orange-900 dark:text-orange-100',
        subtitleColor: 'text-orange-600/70 dark:text-orange-400/70',
        iconBg: 'bg-orange-500/10 ring-orange-500/20 group-hover:bg-orange-500/20 group-hover:ring-orange-500/30',
        decorBg: 'bg-orange-500/5',
    },
    {
        title: 'Total Endtime',
        value: props.metrics.totalEndtimeFormatted,
        subtitle: props.metrics.totalEndtimeSubtitle,
        icon: '⏱️',
        color: 'bg-gradient-to-br from-purple-50 to-purple-100/50 dark:from-purple-950/30 dark:to-purple-900/20',
        textColor: 'text-purple-700 dark:text-purple-300',
        valueColor: 'text-purple-900 dark:text-purple-100',
        subtitleColor: 'text-purple-600/70 dark:text-purple-400/70',
        iconBg: 'bg-purple-500/10 ring-purple-500/20 group-hover:bg-purple-500/20 group-hover:ring-purple-500/30',
        decorBg: 'bg-purple-500/5',
    },
    {
        title: 'Submitted Lots',
        value: props.metrics.totalSubmittedFormatted,
        subtitle: props.metrics.totalSubmittedSubtitle,
        icon: '✅',
        color: 'bg-gradient-to-br from-emerald-50 to-emerald-100/50 dark:from-emerald-950/30 dark:to-emerald-900/20',
        textColor: 'text-emerald-700 dark:text-emerald-300',
        valueColor: 'text-emerald-900 dark:text-emerald-100',
        subtitleColor: 'text-emerald-600/70 dark:text-emerald-400/70',
        iconBg: 'bg-emerald-500/10 ring-emerald-500/20 group-hover:bg-emerald-500/20 group-hover:ring-emerald-500/30',
        decorBg: 'bg-emerald-500/5',
    },
    {
        title: 'Remaining Lots',
        value: props.metrics.totalRemainingFormatted,
        subtitle: props.metrics.totalRemainingSubtitle,
        icon: '📜',
        color: 'bg-gradient-to-br from-pink-50 to-pink-100/50 dark:from-pink-950/30 dark:to-pink-900/20',
        textColor: 'text-pink-700 dark:text-pink-300',
        valueColor: 'text-pink-900 dark:text-pink-100',
        subtitleColor: 'text-pink-600/70 dark:text-pink-400/70',
        iconBg: 'bg-pink-500/10 ring-pink-500/20 group-hover:bg-pink-500/20 group-hover:ring-pink-500/30',
        decorBg: 'bg-pink-500/5',
    },
]);

// Use production data from props (real data from database)
const productionData = ref(props.productionData);
const lineData = ref(props.lineData);
const sizeData = ref(props.sizeData);

let productionChart: ApexCharts | null = null;
let machineChart: ApexCharts | null = null;

// ============================================================================
// CHART CONFIGURATIONS
// ============================================================================
const getProductionChartOptions = () => ({
    series: [
        {
            type: 'area',
            name: 'Target',
            data: productionData.value.map(d => ({ x: d.month, y: d.target }))
        },
        {
            type: 'bar',
            name: 'Endtime',
            data: productionData.value.map(d => ({ x: d.month, y: d.endtime }))
        },
        {
            type: 'bar',
            name: 'Submitted',
            data: productionData.value.map(d => ({ x: d.month, y: d.submitted }))
        },
        {
            type: 'bar',
            name: 'Remaining',
            data: productionData.value.map(d => ({ x: d.month, y: d.remaining }))
        }
    ],
    chart: {
        type: 'area',
        height: 330,
        background: 'transparent',
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 800,
            animateGradually: { enabled: true, delay: 150 },
            dynamicAnimation: { enabled: true, speed: 350 }
        },
        toolbar: { show: false },
        zoom: { enabled: false },
        dropShadow: {
            enabled: true,
            enabledOnSeries: [0],
            top: 3,
            left: 0,
            blur: 6,
            color: 'rgba(253, 175, 34, 0.5)',
            opacity: 0.3,
        },
    },
    colors: [
        CHART_COLORS.target,
        CHART_COLORS.endtime,
        CHART_COLORS.submitted,
        CHART_COLORS.remaining,
    ],
    dataLabels: { enabled: false },
    markers: {
        size: [6, 0, 0, 0],
        colors: Object.values(CHART_COLORS),
        strokeColors: isDarkMode.value ? '#1f2937' : '#fff',
        strokeWidth: 2,
        hover: { size: 10, sizeOffset: 3 },
    },
    grid: {
        show: true,
        borderColor: isDarkMode.value ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)',
        strokeDashArray: 3,
        position: 'back',
        xaxis: { lines: { show: true } },
        yaxis: { lines: { show: true } },
        padding: { top: 0, right: 10, bottom: 0, left: 10 },
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.1,
            stops: [0, 90, 100],
            colorStops: [
                [
                    { offset: 0, color: 'rgba(253, 175, 34, 0.1)', opacity: 1 },
                    { offset: 75, color: 'rgba(253, 175, 34, 0.1)', opacity: 1 },
                    { offset: 100, color: 'rgba(253, 175, 34, 0.1)', opacity: 1 }
                ],
                [
                    { offset: 0, color: 'rgba(152, 95, 253, 1)', opacity: 1 },
                    { offset: 75, color: 'rgba(152, 95, 253, 1)', opacity: 1 },
                    { offset: 100, color: 'rgba(152, 95, 253, 1)', opacity: 1 }
                ],
                [
                    { offset: 0, color: 'rgba(50, 212, 132, 1)', opacity: 1 },
                    { offset: 75, color: 'rgba(50, 212, 132, 1)', opacity: 1 },
                    { offset: 100, color: 'rgba(50, 212, 132, 1)', opacity: 1 }
                ],
                [
                    { offset: 0, color: 'rgba(255, 73, 205, 1)', opacity: 1 },
                    { offset: 75, color: 'rgba(255, 73, 205, 1)', opacity: 1 },
                    { offset: 100, color: 'rgba(255, 73, 205, 1)', opacity: 1 }
                ]
            ]
        }
    },
    stroke: {
        curve: ['smooth', 'smooth', 'smooth', 'smooth'],
        width: [3, 0, 0, 0],
        dashArray: [5, 0, 0, 0],
        lineCap: 'round',
    },
    xaxis: {
        axisTicks: { show: false },
        axisBorder: {
            show: true,
            color: isDarkMode.value ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)',
        },
        labels: {
            style: {
                colors: isDarkMode.value ? '#9ca3af' : '#6b7280',
                fontSize: '12px',
                fontWeight: 500,
            },
            rotate: 0,
        },
        tooltip: { enabled: false },
    },
    yaxis: {
        labels: {
            style: {
                colors: isDarkMode.value ? '#9ca3af' : '#6b7280',
                fontSize: '12px',
            },
            formatter: (value: number) => value.toFixed(0) + 'M',
        },
        title: {
            text: 'Production (M PCS)',
            style: {
                color: isDarkMode.value ? '#9ca3af' : '#6b7280',
                fontSize: '12px',
                fontWeight: 500,
            },
        },
    },
    plotOptions: {
        bar: {
            columnWidth: '45%',
            borderRadius: 4,
            borderRadiusApplication: 'end',
            dataLabels: { position: 'top' },
        },
    },
    tooltip: {
        enabled: true,
        shared: true,
        intersect: false,
        theme: isDarkMode.value ? 'dark' : 'light',
        style: { fontSize: '13px' },
        x: {
            show: true,
            formatter: (value: any) => '<strong>' + value + '</strong>'
        },
        y: {
            formatter: (value: number) => value === undefined ? '' : value.toFixed(1) + 'M PCS',
            title: { formatter: (seriesName: string) => seriesName + ':' },
        },
        marker: { show: true },
        fixed: { enabled: false },
    },
    legend: { show: false },
    responsive: [
        {
            breakpoint: 1024,
            options: {
                chart: { height: 350 },
                legend: { position: 'bottom' },
            },
        },
        {
            breakpoint: 768,
            options: {
                chart: { height: 300 },
                plotOptions: { bar: { columnWidth: '60%' } },
            },
        },
    ],
});

const getMachineChartOptions = () => {
    return {
        series: [machineStats.value.utilizationPercentage],
        chart: {
            type: 'radialBar',
            height: 330,
            background: 'transparent',
            offsetY: -10
        },
        colors: ['#985FFD'],
        plotOptions: {
            radialBar: {
                startAngle: -135,
                endAngle: 135,
                hollow: {
                    margin: 0,
                    size: '60%',
                    background: 'transparent',
                },
                track: {
                    background: isDarkMode.value ? '#1f2937' : '#e5e7eb',
                    strokeWidth: '100%',
                    margin: 5,
                    dropShadow: { enabled: false }
                },
                dataLabels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: '14px',
                        fontWeight: 600,
                        color: '#985FFD',
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
        labels: ['Machine Utilization'],
        legend: { show: false },
    };
};

// ============================================================================
// METHODS
// ============================================================================
const initProductionChart = () => {
    const chartElement = document.querySelector('#production-overview-chart');
    if (!chartElement) return;

    productionChart = new ApexCharts(chartElement, getProductionChartOptions());
    productionChart.render();
};

const initMachineChart = () => {
    const chartElement = document.querySelector('#machine-chart');
    if (!chartElement) return;

    machineChart = new ApexCharts(chartElement, getMachineChartOptions());
    machineChart.render();
};

const destroyCharts = () => {
    if (productionChart) {
        productionChart.destroy();
        productionChart = null;
    }
    if (machineChart) {
        machineChart.destroy();
        machineChart = null;
    }
};

const reinitializeCharts = () => {
    destroyCharts();
    initProductionChart();
    initMachineChart();
};

const getPercentageColor = (value: number): string => {
    if (value >= 100) return 'bg-[#32D484]/20 text-[#32D484]';
    if (value >= 90) return 'bg-[#FDAF22]/20 text-[#FDAF22]';
    return 'bg-[#FF6757]/20 text-[#FF6757]';
};

const handleFilterChange = () => {
    isLoading.value = true;
    router.get('/endtime', currentFilters.value, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

/**
 * Get current shift, cutoff, and production date based on current time
 * Production Date Logic:
 * - DAY shift: Production date = same calendar date (07:00-18:59)
 * - NIGHT shift: Production date = date when shift STARTS
 *   - NIGHT 1ST (19:00-23:59): Production date = current day
 *   - NIGHT 2ND (00:00-03:59): Production date = previous day (shift started yesterday)
 *   - NIGHT 3RD (04:00-06:59): Production date = previous day (shift started yesterday)
 */
const getCurrentShiftAndCutoff = () => {
    const currentTime = new Date();
    const hour = currentTime.getHours();
    let shift = 'DAY';
    let cutoff = '1ST';
    let dateObj = new Date();
    
    // Determine shift and cutoff based on time
    if (hour >= 7 && hour < 12) {
        shift = 'DAY';
        cutoff = '1ST';
    } else if (hour >= 12 && hour < 16) {
        shift = 'DAY';
        cutoff = '2ND';
    } else if (hour >= 16 && hour < 19) {
        shift = 'DAY';
        cutoff = '3RD';
    } else if (hour >= 19 && hour < 24) {
        // NIGHT 1ST: 19:00-23:59, production date = current day (shift starts today)
        shift = 'NIGHT';
        cutoff = '1ST';
    } else if (hour >= 0 && hour < 4) {
        // NIGHT 2ND: 00:00-03:59, production date = previous day (shift started yesterday)
        shift = 'NIGHT';
        cutoff = '2ND';
        dateObj.setDate(dateObj.getDate() - 1);
    } else { // 4-6:59
        // NIGHT 3RD: 04:00-06:59, production date = previous day (shift started yesterday)
        shift = 'NIGHT';
        cutoff = '3RD';
        dateObj.setDate(dateObj.getDate() - 1);
    }
    
    // Format date as YYYY-MM-DD using local date components
    const year = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day = String(dateObj.getDate()).padStart(2, '0');
    const date = `${year}-${month}-${day}`;
    
    return { shift, cutoff, date };
};

const handleRefreshToggle = () => {
    if (currentFilters.value.refresh) {
        // Turning ON - set to current shift/cutoff, reset line to ALL, and start interval
        const current = getCurrentShiftAndCutoff();
        currentFilters.value.shift = current.shift;
        currentFilters.value.cutoff = current.cutoff;
        currentFilters.value.date = current.date;
        currentFilters.value.line = 'ALL';
        selectedLine.value = 'ALL';
        startAutoRefresh();
        startKeepAlive();
    } else {
        // Turning OFF - stop intervals
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
        stopKeepAlive();
    }
    handleFilterChange();
};

const handleReset = () => {
    currentFilters.value.refresh = true;
    currentFilters.value.worktype = 'ALL';
    currentFilters.value.line = 'ALL';
    selectedLine.value = 'ALL';
    const current = getCurrentShiftAndCutoff();
    currentFilters.value.shift = current.shift;
    currentFilters.value.cutoff = current.cutoff;
    currentFilters.value.date = current.date;
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
    startAutoRefresh();
    startKeepAlive();
    handleFilterChange();
};

const startAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    refreshInterval = setInterval(() => {
        if (currentFilters.value.refresh) {
            // Update to current shift/cutoff before refreshing
            const current = getCurrentShiftAndCutoff();
            currentFilters.value.shift = current.shift;
            currentFilters.value.cutoff = current.cutoff;
            currentFilters.value.date = current.date;
            handleFilterChange();
        }
    }, refreshIntervalSeconds.value * 1000); // Convert seconds to milliseconds
};

const handleIntervalChange = () => {
    // Validate interval (minimum 5 seconds, maximum 300 seconds)
    if (refreshIntervalSeconds.value < 5) {
        refreshIntervalSeconds.value = 5;
    } else if (refreshIntervalSeconds.value > 300) {
        refreshIntervalSeconds.value = 300;
    }
    
    // Restart interval if auto-refresh is enabled
    if (currentFilters.value.refresh) {
        startAutoRefresh();
    }
};

const startKeepAlive = () => {
    // Clear existing interval if any
    if (keepAliveInterval) {
        clearInterval(keepAliveInterval);
    }
    
    // Ping server every 5 minutes to keep session alive
    keepAliveInterval = setInterval(() => {
        // Use fetch instead of Inertia router to avoid Inertia response requirement
        fetch('/keep-alive', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        }).catch(() => {
            // Silently fail if keep-alive request fails
        });
    }, 300000); // 5 minutes (300,000 ms)
};

const stopKeepAlive = () => {
    if (keepAliveInterval) {
        clearInterval(keepAliveInterval);
        keepAliveInterval = null;
    }
};

// ============================================================================
// LIFECYCLE HOOKS
// ============================================================================
onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
    initProductionChart();
    initMachineChart();
    
    // Start auto-refresh and keep-alive if enabled
    if (currentFilters.value.refresh) {
        startAutoRefresh();
        startKeepAlive();
    }
    
    const observer = new MutationObserver(() => {
        const newDarkMode = document.documentElement.classList.contains('dark');
        if (newDarkMode !== isDarkMode.value) {
            isDarkMode.value = newDarkMode;
        }
    });
    
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    (window as any).__themeObserver = observer;
});

onBeforeUnmount(() => {
    destroyCharts();
    
    // Clear auto-refresh interval
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
    
    // Clear keep-alive interval
    stopKeepAlive();
    
    if ((window as any).__themeObserver) {
        (window as any).__themeObserver.disconnect();
        delete (window as any).__themeObserver;
    }
});

watch(isDarkMode, reinitializeCharts);

// Watch for production data changes from server
watch(() => props.productionData, (newData) => {
    productionData.value = newData;
    reinitializeCharts();
}, { deep: true });

// Watch for line data changes
watch(() => props.lineData, (newData) => {
    lineData.value = newData;
}, { deep: true });

// Watch for size data changes
watch(() => props.sizeData, (newData) => {
    sizeData.value = newData;
}, { deep: true });

// Watch for machine stats changes
watch(() => props.machineStats, (newData) => {
    machineStats.value = newData;
    reinitializeCharts();
}, { deep: true });

// Watch for filter changes from props to keep currentFilters in sync
watch(() => props.filters.date, (newDate) => {
    currentFilters.value.date = newDate;
});

watch(() => props.filters.shift, (newShift) => {
    currentFilters.value.shift = newShift;
});

watch(() => props.filters.cutoff, (newCutoff) => {
    currentFilters.value.cutoff = newCutoff;
});

watch(() => props.filters.worktype, (newWorktype) => {
    currentFilters.value.worktype = newWorktype;
});

watch(() => props.filters.refresh, (newRefresh) => {
    currentFilters.value.refresh = newRefresh;
});

watch(() => props.filters.line, (newLine) => {
    selectedLine.value = newLine;
    currentFilters.value.line = newLine;
});
</script>

<template>
    <Head title="ENDTIME Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- ================================================================ -->
        <!-- FILTERS SECTION -->
        <!-- ================================================================ -->
        <template #filters>
            <div class="flex items-center gap-3">
                <!-- Production Date Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm group relative">
                    <span class="text-xs font-medium text-muted-foreground">DATE:</span>
                    <input 
                        type="date" 
                        v-model="currentFilters.date"
                        @change="handleFilterChange"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 cursor-pointer"
                    />
                    <!-- Tooltip showing actual datetime range -->
                    <div class="absolute left-0 top-full mt-1 hidden group-hover:block z-50 w-64 p-2 bg-popover border border-border rounded-lg shadow-lg text-xs">
                        <div class="font-semibold text-foreground mb-1">📅 Query Range:</div>
                        <div class="text-muted-foreground">{{ getDateRangeDescription }}</div>
                    </div>
                </div>

                <!-- Shift Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm" :class="currentFilters.refresh ? 'opacity-60' : ''">
                    <span class="text-xs font-medium text-muted-foreground">SHIFT:</span>
                    <select 
                        v-model="currentFilters.shift"
                        @change="handleFilterChange"
                        :disabled="currentFilters.refresh"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 pr-6 [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100 disabled:cursor-not-allowed"
                        :class="currentFilters.refresh ? '' : 'cursor-pointer'"
                    >
                        <option>ALL</option>
                        <option>DAY</option>
                        <option>NIGHT</option>
                    </select>
                </div>

                <!-- Cutoff Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm" :class="currentFilters.refresh ? 'opacity-60' : ''">
                    <span class="text-xs font-medium text-muted-foreground">CUTOFF:</span>
                    <select 
                        v-model="currentFilters.cutoff"
                        @change="handleFilterChange"
                        :disabled="currentFilters.refresh"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 pr-6 [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100 disabled:cursor-not-allowed"
                        :class="currentFilters.refresh ? '' : 'cursor-pointer'"
                    >
                        <option>ALL</option>
                        <option>1ST</option>
                        <option>2ND</option>
                        <option>3RD</option>
                    </select>
                </div>

                <!-- WorkType Filter -->
                <div class="flex items-center gap-1.5 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm">
                    <span class="text-xs font-medium text-muted-foreground">WORKTYPE:</span>
                    <select 
                        v-model="currentFilters.worktype"
                        @change="handleFilterChange"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 cursor-pointer pr-6 [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100"
                    >
                        <option>ALL</option>
                        <option>NORMAL</option>
                        <option>PROCESS RW</option>
                        <option>WH REWORK</option>
                        <option>OI REWORK</option>
                    </select>
                </div>

                <!-- Auto Refresh -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm">
                    <span class="text-xs font-medium text-muted-foreground">REFRESH:</span>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input 
                            type="checkbox" 
                            v-model="currentFilters.refresh"
                            @change="handleRefreshToggle"
                            class="peer sr-only" 
                        />
                        <div class="peer h-5 w-9 rounded-full bg-gray-300 dark:bg-gray-600 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 dark:after:border-gray-600 after:bg-white dark:after:bg-gray-400 after:transition-all after:shadow-md peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-checked:after:bg-white"></div>
                    </label>
                    <input 
                        type="number" 
                        v-model.number="refreshIntervalSeconds"
                        @change="handleIntervalChange"
                        min="5"
                        max="300"
                        class="w-12 border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-1 focus:ring-primary rounded px-1 text-center"
                    />
                    <span class="text-xs text-muted-foreground">sec</span>
                    <div v-if="isLoading && currentFilters.refresh" class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden"></span>
                    </div>
                </div>

                <!-- Reset Button -->
                <Button 
                    variant="ghost" 
                    size="icon" 
                    class="h-8 w-8 rounded-lg hover:bg-muted"
                    @click="handleReset"
                    :disabled="isLoading"
                    title="Reset to current time and ALL worktype"
                >
                    <span class="text-lg">🔀</span>
                </Button>

                <!-- Separator -->
                <div class="h-8 w-px bg-border"></div>

                <!-- Action Buttons -->
                <Link href="/endtime-add">
                    <Button variant="outline" size="sm" class="h-8 px-4 text-xs font-medium shadow-sm border-primary text-primary hover:bg-primary/10 dark:hover:bg-primary/20">
                        ➕ ENDTIME
                    </Button>
                </Link>
                <Button 
                    variant="outline" 
                    size="sm" 
                    class="h-8 px-4 text-xs font-medium shadow-sm border-secondary text-secondary hover:bg-secondary/10 dark:hover:bg-secondary/20"
                    @click="showSubmitModal = true"
                >
                    ✅ SUBMIT
                </Button>
                <Link href="/endtime-ranking">
                    <Button variant="outline" size="sm" class="h-8 px-4 text-xs font-medium shadow-sm border-[#FDAF22] text-[#FDAF22] hover:bg-[#FDAF22]/10 dark:hover:bg-[#FDAF22]/20">
                        🥇 RANK
                    </Button>
                </Link>
            </div>
        </template>

        <!-- ================================================================ -->
        <!-- MAIN CONTENT -->
        <!-- ================================================================ -->
        <div class="flex h-full flex-1 flex-col gap-3 p-4">
            <!-- Metrics Cards -->
            <div class="grid gap-3 md:grid-cols-4">
                <div 
                    v-for="(metric, index) in metrics" 
                    :key="index"
                    @click="index === 3 ? openRemainingLotsModal() : null"
                >
                    <div 
                        class="group relative overflow-hidden rounded-xl border-2 border-border dark:border-gray-700 shadow-lg transition-all duration-300 hover:shadow-xl hover:-translate-y-0.5 hover:scale-[1.02]" 
                        :class="[metric.color, index === 3 ? 'cursor-pointer' : '']"
                    >
                        <div class="relative p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex-1">
                                    <p class="text-m font-medium" :class="metric.textColor">
                                        {{ metric.title }}
                                    </p>
                                    <h3 class="mt-1 text-2xl font-bold" :class="metric.valueColor">
                                        {{ metric.value }}
                                    </h3>
                                    <p class="mt-0.5 text-sm" :class="metric.subtitleColor">
                                        {{ metric.subtitle }}
                                    </p>
                                </div>
                                <div class="rounded-full p-3 ring-1 transition-all duration-300" :class="metric.iconBg">
                                    <span class="text-3xl">{{ metric.icon }}</span>
                                </div>
                            </div>
                            <div class="absolute -right-3 -top-3 h-12 w-12 rounded-full" :class="metric.decorBg"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid gap-3 lg:grid-cols-3">
                <!-- Production Overview Chart -->
                <Card class="lg:col-span-2 overflow-hidden shadow-lg border-2 border-border dark:border-gray-700 dark:bg-gray-900/50 transition-all duration-300 hover:shadow-xl hover:-translate-y-0.5 hover:scale-[1.01]">
                    <CardHeader class="px-4 py-2">
                        <CardTitle class="flex items-center justify-between text-xl">
                            <span>Production Overview</span>
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-[#FDAF22]"></span>
                                    <span class="text-xs font-medium text-muted-foreground">Target</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-[#985FFD]"></span>
                                    <span class="text-xs font-medium text-muted-foreground">Endtime</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-[#00C9FF]"></span>
                                    <span class="text-xs font-medium text-muted-foreground">Submitted</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-[#FF49CD]"></span>
                                    <span class="text-xs font-medium text-muted-foreground">Remaining</span>
                                </div>
                            </div>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="pb-3 -mt-10">
                        <div id="production-overview-chart"></div>
                    </CardContent>
                </Card>

                <!-- Machine Utilization -->
                <Card class="shadow-lg border-1 border-border dark:border-gray-700 dark:bg-gray-900/50 transition-all duration-300 hover:shadow-xl hover:-translate-y-0.5 hover:scale-[1.01]">
                    <CardHeader class="px-4 py-2">
                        <CardTitle class="flex items-center justify-between text-xl">
                            <div class="flex items-center gap-2">
                                <span>Machine Utilization</span>
                            </div>
                            <div class="flex items-center gap-1 rounded-lg border border-border bg-background px-2 py-1 shadow-sm" :class="currentFilters.refresh ? 'opacity-60' : ''">
                                <Button 
                                    variant="ghost" 
                                    size="icon" 
                                    class="h-6 w-6 rounded hover:bg-muted"
                                    @click="navigateLine('prev')"
                                    :disabled="selectedLine === 'ALL' || currentFilters.refresh"
                                >
                                    <span class="text-sm">◀</span>
                                </Button>
                                <div class="flex items-center gap-1.5 px-2">
                                    <span class="text-xs font-medium text-muted-foreground">LINE:</span>
                                    <span class="text-xs font-bold text-foreground min-w-[32px] text-center">{{ selectedLine }}</span>
                                </div>
                                <Button 
                                    variant="ghost" 
                                    size="icon" 
                                    class="h-6 w-6 rounded hover:bg-muted"
                                    @click="navigateLine('next')"
                                    :disabled="selectedLine === 'K' || currentFilters.refresh"
                                >
                                    <span class="text-sm">▶</span>
                                </Button>
                            </div>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="pb-3 pt-2 -mt-10">
                        <div id="machine-chart"></div>
                        
                        <!-- Machine Stats -->
                        <div class="grid grid-cols-2 gap-3 mt-1">
                            <div 
                                @click="openEquipmentModal('with')"
                                class="text-center p-1 rounded-lg bg-[#32D484]/10 dark:bg-[#32D484]/20 border border-[#32D484]/30 dark:border-[#32D484]/40 transition-all duration-300 hover:bg-[#32D484]/20 dark:hover:bg-[#32D484]/30 hover:scale-105 hover:shadow-md cursor-pointer"
                            >
                                <div class="text-xl font-bold text-[#32D484] dark:text-[#32D484]">{{ machineStats.withLot }}</div>
                                <div class="text-xs text-[#32D484]/80 dark:text-[#32D484]/90 mt-1">With Ongoing Lot</div>
                            </div>
                            <div 
                                @click="openEquipmentModal('without')"
                                class="text-center p-1 rounded-lg bg-[#FF6757]/10 dark:bg-[#FF6757]/20 border border-[#FF6757]/30 dark:border-[#FF6757]/40 transition-all duration-300 hover:bg-[#FF6757]/20 dark:hover:bg-[#FF6757]/30 hover:scale-105 hover:shadow-md cursor-pointer"
                            >
                                <div class="text-xl font-bold text-[#FF6757] dark:text-[#FF6757]">{{ machineStats.withoutLot }}</div>
                                <div class="text-xs text-[#FF6757]/80 dark:text-[#FF6757]/90 mt-1">Without Ongoing Lot</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tables Section -->
            <div class="grid gap-3 lg:grid-cols-3">
                <!-- Per Line Summary -->
                <Card class="overflow-hidden lg:col-span-2 shadow-lg border-2 border-border dark:border-gray-700 dark:bg-gray-900/50 transition-all duration-300 hover:shadow-xl hover:-translate-y-0.5 hover:scale-[1.01]">
                    <CardHeader class="px-4 py-1 pb-1">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <span>Per Line Summary</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0 -mt-5">
                        <div class="overflow-x-auto max-h-[220px] overflow-y-auto">
                            <table class="w-full text-xs">
                                <thead class="bg-muted/80 dark:bg-gray-800 sticky top-0 z-10">
                                    <tr class="border-b-2 border-border dark:border-gray-600">
                                        <th class="sticky left-0 bg-muted/80 dark:bg-gray-800 p-2 text-left font-semibold text-xs">ITEMS</th>
                                        <th class="p-2 text-center font-semibold text-xs">Line A</th>
                                        <th class="p-2 text-center font-semibold text-xs">Line B</th>
                                        <th class="p-2 text-center font-semibold text-xs">Line C</th>
                                        <th class="p-2 text-center font-semibold text-xs">Line D</th>
                                        <th class="p-2 text-center font-semibold text-xs">Line E</th>
                                        <th class="p-2 text-center font-semibold text-xs">Line F</th>
                                        <th class="p-2 text-center font-semibold text-xs">Line G</th>
                                        <th class="p-2 text-center font-semibold text-xs">Line H</th>
                                        <th class="p-2 text-center font-semibold text-xs">Line I</th>
                                        <th class="p-2 text-center font-semibold text-xs">Line J</th>
                                        <th class="p-2 text-center font-semibold text-xs">Line K</th>
                                        <th class="p-2 text-center font-semibold text-xs">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="(row, index) in lineData" 
                                        :key="index" 
                                        class="border-b border-border dark:border-gray-700 hover:bg-muted/50 dark:hover:bg-gray-800/50"
                                    >
                                        <td class="sticky left-0 bg-background dark:bg-gray-900 p-2 font-semibold text-xs">
                                            <span v-if="row.metric === 'Target'" class="flex items-center gap-1">
                                                <span class="text-[#FDAF22]">🎯</span> {{ row.metric }}
                                            </span>
                                            <span v-else-if="row.metric === 'ENDTIME'" class="flex items-center gap-1">
                                                <span class="text-[#985FFD]">⏱️</span> {{ row.metric }}
                                            </span>
                                            <span v-else-if="row.metric === 'SUBMITTED'" class="flex items-center gap-1">
                                                <span class="text-[#00C9FF]">✅</span> {{ row.metric }}
                                            </span>
                                            <span v-else>{{ row.metric }}</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.lineA)" variant="secondary">{{ row.lineA }}%</Badge>
                                            <span v-else>{{ row.lineA }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.lineB)" variant="secondary">{{ row.lineB }}%</Badge>
                                            <span v-else>{{ row.lineB }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.lineC)" variant="secondary">{{ row.lineC }}%</Badge>
                                            <span v-else>{{ row.lineC }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.lineD)" variant="secondary">{{ row.lineD }}%</Badge>
                                            <span v-else>{{ row.lineD }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.lineE)" variant="secondary">{{ row.lineE }}%</Badge>
                                            <span v-else>{{ row.lineE }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.lineF)" variant="secondary">{{ row.lineF }}%</Badge>
                                            <span v-else>{{ row.lineF }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.lineG)" variant="secondary">{{ row.lineG }}%</Badge>
                                            <span v-else>{{ row.lineG }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.lineH)" variant="secondary">{{ row.lineH }}%</Badge>
                                            <span v-else>{{ row.lineH }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.lineI)" variant="secondary">{{ row.lineI }}%</Badge>
                                            <span v-else>{{ row.lineI }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.lineJ)" variant="secondary">{{ row.lineJ }}%</Badge>
                                            <span v-else>{{ row.lineJ }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.lineK)" variant="secondary">{{ row.lineK }}%</Badge>
                                            <span v-else>{{ row.lineK }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.total)" variant="secondary">{{ row.total }}%</Badge>
                                            <span v-else>{{ row.total }} M</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Per Size Summary -->
                <Card class="shadow-lg border-2 border-border dark:border-gray-700 dark:bg-gray-900/50 transition-all duration-300 hover:shadow-xl hover:-translate-y-0.5 hover:scale-[1.01]">
                    <CardHeader class="px-4 py-1 pb-1">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <span>Per Size Summary</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0 -mt-5">
                        <div class="overflow-x-auto max-h-[220px] overflow-y-auto">
                            <table class="w-full text-xs">
                                <thead class="bg-muted/80 dark:bg-gray-800 sticky top-0 z-10">
                                    <tr class="border-b-2 border-border dark:border-gray-600">
                                        <th class="p-2 text-center font-semibold text-xs">0603</th>
                                        <th class="p-2 text-center font-semibold text-xs">1005</th>
                                        <th class="p-2 text-center font-semibold text-xs">1608</th>
                                        <th class="p-2 text-center font-semibold text-xs">2012</th>
                                        <th class="p-2 text-center font-semibold text-xs">3216</th>
                                        <th class="p-2 text-center font-semibold text-xs">3225</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="(row, index) in sizeData" 
                                        :key="index" 
                                        class="border-b border-border dark:border-gray-700 hover:bg-muted/50 dark:hover:bg-gray-800/50"
                                    >
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.size0603)" variant="secondary">{{ row.size0603 }}%</Badge>
                                            <span v-else>{{ row.size0603 }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.size1005)" variant="secondary">{{ row.size1005 }}%</Badge>
                                            <span v-else>{{ row.size1005 }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.size1608)" variant="secondary">{{ row.size1608 }}%</Badge>
                                            <span v-else>{{ row.size1608 }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.size2012)" variant="secondary">{{ row.size2012 }}%</Badge>
                                            <span v-else>{{ row.size2012 }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.size3216)" variant="secondary">{{ row.size3216 }}%</Badge>
                                            <span v-else>{{ row.size3216 }} M</span>
                                        </td>
                                        <td class="p-2 text-center font-semibold text-xs">
                                            <Badge v-if="row.metric.includes('%')" :class="getPercentageColor(row.size3225)" variant="secondary">{{ row.size3225 }}%</Badge>
                                            <span v-else>{{ row.size3225 }} M</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Equipment Modal -->
        <EquipmentModal 
            :open="showEquipmentModal"
            @update:open="showEquipmentModal = $event"
            :title="equipmentModalTitle"
            :equipment-list="equipmentList"
            :type="equipmentModalType"
        />

        <!-- Remaining Lots Modal -->
        <RemainingLotsModal 
            :open="showRemainingLotsModal"
            @update:open="showRemainingLotsModal = $event"
            @toggle-previous-date="handleTogglePreviousDate"
            :title="remainingLotsModalTitle"
            :remaining-lots="remainingLotsList"
        />

        <!-- Submit Modal -->
        <EndtimeSubmitModal 
            :open="showSubmitModal"
            @update:open="showSubmitModal = $event"
        />
    </AppLayout>
</template>

