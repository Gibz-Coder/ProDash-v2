<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Calendar, RefreshCw } from 'lucide-vue-next';
import ApexCharts from 'apexcharts';
import axios from 'axios';
import MemsAssignLotModal from '@/pages/dashboards/subs/mems-assign-lot-modal.vue';
import LotRequestViewModal from '@/pages/dashboards/subs/lot-request-view-modal.vue';
import EndtimeWipupdateModal from '@/pages/dashboards/subs/endtime-wipupdate-modal.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: dashboard().url,
    },
    {
        title: 'Lot Request',
        href: '/lot-request',
    },
];

interface LotRequest {
    id: number;
    request_no: string;
    mc_no: string;
    line: string;
    area: string;
    requestor: string;
    request_model?: string;
    lot_no: string;
    model: string;
    quantity: number;
    lipas: 'Y' | 'N' | null;
    lot_tat?: string;
    lot_location?: string;
    requested: string;
    completed?: string;
    response_time?: string;
    status: 'PENDING' | 'COMPLETED' | 'REJECTED';
    remarks?: string;
}

interface Stats {
    total: number;
    pending: number;
    completed: number;
    rejected: number;
    completionRate: number;
    avgResponseTime: number;
}

interface SizeData {
    size: string;
    pending: number;
    completed: number;
    rejected: number;
    total: number;
}

interface LineData {
    line: string;
    pending: number;
    completed: number;
    rejected: number;
    total: number;
}

// Data from API
const lotRequests = ref<LotRequest[]>([]);
const stats = ref<Stats>({
    total: 0,
    pending: 0,
    completed: 0,
    rejected: 0,
    completionRate: 0,
    avgResponseTime: 0
});
const sizeData = ref<SizeData[]>([]);
const lineData = ref<LineData[]>([]);
const isLoading = ref(false);

const statusFilter = ref<string>('ALL');
const sortColumn = ref<string>('');
const sortDirection = ref<'asc' | 'desc'>('asc');

// Date filter state with persistence
const dateFrom = ref<string>('');
const dateTo = ref<string>('');

// Auto-refresh state with persistence
const autoRefresh = ref<boolean>(false);
const refreshInterval = ref<number | null>(null);
const sessionKeepAliveInterval = ref<number | null>(null);
const REFRESH_INTERVAL_MS = 5000; // 5 seconds
const SESSION_KEEPALIVE_MS = 240000; // 4 minutes (keep session alive)

// Modal state
const showAssignLotModal = ref(false);
const selectedRequestForAssignment = ref<LotRequest | null>(null);
const showViewModal = ref(false);
const selectedRequestForView = ref<LotRequest | null>(null);
const showWipUpdateModal = ref(false);

// WIP last update state
const wipLastUpdate = ref<string | null>(null);

// Load persisted settings from localStorage
const loadPersistedSettings = () => {
    try {
        const savedDateFrom = localStorage.getItem('lotRequest_dateFrom');
        const savedDateTo = localStorage.getItem('lotRequest_dateTo');
        const savedAutoRefresh = localStorage.getItem('lotRequest_autoRefresh');
        
        if (savedDateFrom) dateFrom.value = savedDateFrom;
        if (savedDateTo) dateTo.value = savedDateTo;
        if (savedAutoRefresh) autoRefresh.value = savedAutoRefresh === 'true';
    } catch (error) {
        console.error('Error loading persisted settings:', error);
    }
};

// Save settings to localStorage
const saveSettings = () => {
    try {
        localStorage.setItem('lotRequest_dateFrom', dateFrom.value);
        localStorage.setItem('lotRequest_dateTo', dateTo.value);
        localStorage.setItem('lotRequest_autoRefresh', String(autoRefresh.value));
    } catch (error) {
        console.error('Error saving settings:', error);
    }
};

// Toggle auto-refresh
const toggleAutoRefresh = () => {
    autoRefresh.value = !autoRefresh.value;
    saveSettings();
    
    if (autoRefresh.value) {
        startAutoRefresh();
        startSessionKeepAlive();
    } else {
        stopAutoRefresh();
        stopSessionKeepAlive();
    }
};

// Start auto-refresh interval
const startAutoRefresh = () => {
    if (refreshInterval.value) return;
    
    refreshInterval.value = window.setInterval(() => {
        fetchData();
    }, REFRESH_INTERVAL_MS);
};

// Stop auto-refresh interval
const stopAutoRefresh = () => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
        refreshInterval.value = null;
    }
};

// Start session keep-alive (ping server to prevent session expiration)
const startSessionKeepAlive = () => {
    if (sessionKeepAliveInterval.value) return;
    
    sessionKeepAliveInterval.value = window.setInterval(async () => {
        try {
            // Ping a lightweight endpoint to keep session alive
            await axios.get('/api/ping');
        } catch (error) {
            console.error('Session keep-alive failed:', error);
        }
    }, SESSION_KEEPALIVE_MS);
};

// Stop session keep-alive
const stopSessionKeepAlive = () => {
    if (sessionKeepAliveInterval.value) {
        clearInterval(sessionKeepAliveInterval.value);
        sessionKeepAliveInterval.value = null;
    }
};

// Clear date filters
const clearDateFilters = () => {
    dateFrom.value = '';
    dateTo.value = '';
    saveSettings();
    fetchData();
};

// Get today's date in YYYY-MM-DD format (local timezone)
const getTodayDate = () => {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};


// Fetch WIP last update timestamp
const fetchWipLastUpdate = async () => {
    try {
        const response = await axios.get('/api/updatewip/last-update');
        wipLastUpdate.value = response.data.last_update;
    } catch (error) {
        console.error('Error fetching WIP last update:', error);
        wipLastUpdate.value = null;
    }
};

// Format WIP last update for display
const formatWipLastUpdate = computed(() => {
    if (!wipLastUpdate.value) return 'No data';
    
    try {
        const date = new Date(wipLastUpdate.value);
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        
        return `${month}/${day}/${year}, ${hours}:${minutes}:${seconds}`;
    } catch (error) {
        return wipLastUpdate.value;
    }
});

// Open WIP update modal
const openWipUpdateModal = () => {
    showWipUpdateModal.value = true;
};

// Handle WIP update modal close and refresh
const handleWipUpdateComplete = () => {
    showWipUpdateModal.value = false;
    fetchWipLastUpdate();
};

// Fetch data from API
const fetchData = async () => {
    isLoading.value = true;
    try {
        const params: any = { status: statusFilter.value };
        
        // Add date filters if set
        if (dateFrom.value) params.date_from = dateFrom.value;
        if (dateTo.value) params.date_to = dateTo.value;
        
        const [requestsRes, statsRes, sizeRes, lineRes] = await Promise.all([
            axios.get('/api/lot-request/data', { params }),
            axios.get('/api/lot-request/stats', { params: { date_from: dateFrom.value, date_to: dateTo.value } }),
            axios.get('/api/lot-request/by-size', { params: { date_from: dateFrom.value, date_to: dateTo.value } }),
            axios.get('/api/lot-request/by-line', { params: { date_from: dateFrom.value, date_to: dateTo.value } })
        ]);

        lotRequests.value = requestsRes.data.data;
        stats.value = statsRes.data;
        sizeData.value = sizeRes.data.data;
        lineData.value = lineRes.data.data;
    } catch (error) {
        console.error('Error fetching lot request data:', error);
    } finally {
        isLoading.value = false;
    }
};

const filteredRequests = computed(() => {
    let filtered = statusFilter.value === 'ALL' 
        ? lotRequests.value 
        : lotRequests.value.filter(req => req.status === statusFilter.value);
    
    // Apply sorting
    if (sortColumn.value) {
        filtered = [...filtered].sort((a, b) => {
            const aVal = a[sortColumn.value as keyof LotRequest];
            const bVal = b[sortColumn.value as keyof LotRequest];
            
            if (aVal === undefined || aVal === null) return 1;
            if (bVal === undefined || bVal === null) return -1;
            
            let comparison = 0;
            if (typeof aVal === 'string' && typeof bVal === 'string') {
                comparison = aVal.localeCompare(bVal);
            } else if (typeof aVal === 'number' && typeof bVal === 'number') {
                comparison = aVal - bVal;
            } else {
                comparison = String(aVal).localeCompare(String(bVal));
            }
            
            return sortDirection.value === 'asc' ? comparison : -comparison;
        });
    } else {
        // Default sorting: Pending first (oldest first), then others
        filtered = [...filtered].sort((a, b) => {
            // First, sort by status priority (PENDING first)
            if (a.status === 'PENDING' && b.status !== 'PENDING') return -1;
            if (a.status !== 'PENDING' && b.status === 'PENDING') return 1;
            
            // Within same status, sort by requested date (oldest first)
            const dateA = new Date(a.requested).getTime();
            const dateB = new Date(b.requested).getTime();
            return dateA - dateB;
        });
    }
    
    return filtered;
});


const handleSort = (column: string) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
};

const getLipasColor = (lipas: string | null) => {
    if (lipas === 'Y') {
        return 'bg-destructive text-destructive-foreground';
    } else if (lipas === 'N') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
    } else {
        return 'bg-muted text-muted-foreground';
    }
};

const getLipasLabel = (lipas: string | null) => {
    if (lipas === 'Y') {
        return 'LIPAS';
    } else if (lipas === 'N') {
        return 'VOLPAS';
    } else {
        return '-';
    }
};

const getStatusColor = (status: string) => {
    const colors = {
        PENDING: 'bg-purple-600 text-white dark:bg-purple-700',
        COMPLETED: 'bg-[#32D484] text-white dark:bg-[#32D484]',
        REJECTED: 'bg-red-600 text-white dark:bg-red-700'
    };
    return colors[status as keyof typeof colors];
};

const getCompletionRateColor = computed(() => {
    const rate = stats.value.completionRate;
    
    if (rate >= 80) {
        // High completion rate - Green
        return {
            bg: 'from-emerald-50 to-emerald-100/50 dark:from-emerald-950/30 dark:to-emerald-900/20',
            ring: 'bg-emerald-500/10 ring-emerald-500/20',
            text: 'text-emerald-700 dark:text-emerald-300',
            value: 'text-emerald-900 dark:text-emerald-100',
            shadow: 'hover:shadow-emerald-500/10'
        };
    } else if (rate >= 60) {
        // Medium completion rate - Yellow/Amber
        return {
            bg: 'from-amber-50 to-amber-100/50 dark:from-amber-950/30 dark:to-amber-900/20',
            ring: 'bg-amber-500/10 ring-amber-500/20',
            text: 'text-amber-700 dark:text-amber-300',
            value: 'text-amber-900 dark:text-amber-100',
            shadow: 'hover:shadow-amber-500/10'
        };
    } else if (rate >= 40) {
        // Low completion rate - Orange
        return {
            bg: 'from-orange-50 to-orange-100/50 dark:from-orange-950/30 dark:to-orange-900/20',
            ring: 'bg-orange-500/10 ring-orange-500/20',
            text: 'text-orange-700 dark:text-orange-300',
            value: 'text-orange-900 dark:text-orange-100',
            shadow: 'hover:shadow-orange-500/10'
        };
    } else {
        // Very low completion rate - Red
        return {
            bg: 'from-red-50 to-red-100/50 dark:from-red-950/30 dark:to-red-900/20',
            ring: 'bg-red-500/10 ring-red-500/20',
            text: 'text-red-700 dark:text-red-300',
            value: 'text-red-900 dark:text-red-100',
            shadow: 'hover:shadow-red-500/10'
        };
    }
});

const handleAccept = async (request: LotRequest) => {
    try {
        // Fetch equipment data to get ongoing_lot, est_endtime, waiting_time, and mc_rack
        const response = await axios.get(`/api/equipment/details/${request.mc_no}`);
        const equipmentData = response.data;
        
        // Store the selected request with equipment data and open the modal
        selectedRequestForAssignment.value = {
            ...request,
            ongoing_lot: equipmentData.ongoing_lot || null,
            est_endtime: equipmentData.est_endtime || null,
            waiting_time: equipmentData.waiting_time || null,
            mc_rack: equipmentData.mc_rack || null,
        };
        showAssignLotModal.value = true;
    } catch (error) {
        console.error('Error fetching equipment data:', error);
        // Still open the modal even if equipment data fetch fails
        selectedRequestForAssignment.value = request;
        showAssignLotModal.value = true;
    }
};

const handleAssignLotConfirm = async (lot: any) => {
    // After lot is assigned, refresh the data
    await fetchData();
};

const handleView = (request: LotRequest) => {
    selectedRequestForView.value = request;
    showViewModal.value = true;
};

const handleDelete = async (requestId: number) => {
    if (!confirm('Are you sure you want to reject this request?')) return;
    
    try {
        const response = await axios.post(`/api/lot-request/${requestId}/reject`);
        if (response.data.success) {
            await fetchData();
        }
    } catch (error) {
        console.error('Error rejecting request:', error);
    }
};

const getPriorityColor = (lipasStatus: string) => {
    const colors = {
        URGENT: 'bg-destructive text-destructive-foreground',
        LIPAS: 'bg-secondary text-secondary-foreground',
        FIFO: 'bg-primary text-primary-foreground',
        NORMAL: 'bg-muted text-muted-foreground'
    };
    return colors[lipasStatus as keyof typeof colors] || colors.NORMAL;
};

// Format datetime to short format (MM-DD HH:mm)
const formatDateTime = (datetime: string | undefined) => {
    if (!datetime) return '-';
    
    try {
        const date = new Date(datetime);
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        return `${month}-${day} ${hours}:${minutes}`;
    } catch (error) {
        return datetime;
    }
};

// Donut Chart Configuration
const donutChartOptions = computed(() => ({
    chart: {
        type: 'donut',
        height: 250,
        offsetY: 0,
        sparkline: {
            enabled: false,
        },
    },
    labels: ['Pending', 'Completed', 'Rejected'],
    colors: ['#985FFD', '#32D484', '#FDAF22'],
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
            offsetY: 0,
            donut: {
                size: '70%',
                background: 'transparent',
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: '20px',
                        color: '#495057',
                        offsetY: -5
                    },
                    value: {
                        show: true,
                        fontSize: '22px',
                        offsetY: 5,
                        fontWeight: 600,
                        formatter: function (val: number) {
                            return Math.round(val).toString()
                        }
                    },
                    total: {
                        show: true,
                        showAlways: true,
                        label: 'Completion Rate',
                        fontSize: '14px',
                        fontWeight: 400,
                        color: '#495057',
                        formatter: () => `${stats.value.completionRate}%`
                    }
                }
            }
        }
    },
    dataLabels: {
        enabled: false,
    },
}));

const donutChartSeries = computed(() => {
    const total = stats.value.total;
    if (total === 0) return [0, 0, 0];
    
    return [
        stats.value.pending,
        stats.value.completed,
        stats.value.rejected
    ];
});

// Size Chart Configuration (Stacked Bar)
const sizeChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 300,
        stacked: true,
        toolbar: {
            show: false
        },
        offsetY: -20,
    },
    plotOptions: {
        bar: {
            horizontal: true,
            barHeight: '60%',
        }
    },
    colors: ['#985FFD', '#32D484', '#FDAF22'],
    dataLabels: {
        enabled: true,
        style: {
            fontSize: '10px',
            fontWeight: 600,
            colors: ['#fff']
        }
    },
    grid: {
        borderColor: '#f1f1f1',
        strokeDashArray: 3,
    },
    xaxis: {
        categories: sizeData.value.map(d => d.size),
        labels: {
            style: {
                colors: "#8c9097",
                fontSize: '11px',
                fontWeight: 600,
            },
        }
    },
    yaxis: {
        labels: {
            style: {
                colors: "#8c9097",
                fontSize: '11px',
                fontWeight: 600,
            },
        }
    },
    legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
        fontSize: '11px',
    },
    fill: {
        opacity: 1
    },
    tooltip: {
        y: {
            formatter: function (val: number) {
                return val + " requests"
            }
        }
    }
}));

const sizeChartSeries = computed(() => [
    {
        name: 'Pending',
        data: sizeData.value.map(d => d.pending)
    },
    {
        name: 'Completed',
        data: sizeData.value.map(d => d.completed)
    },
    {
        name: 'Rejected',
        data: sizeData.value.map(d => d.rejected)
    }
]);

// Stacked Column Chart Configuration
const combinedChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 300,
        stacked: true,
        toolbar: {
            show: false
        },
        offsetY: -10,
    },
    grid: {
        borderColor: '#f1f1f1',
        strokeDashArray: 3,
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
        }
    },
    colors: ['#985FFD', '#32D484', '#FDAF22'],
    xaxis: {
        categories: lineData.value.map(d => d.line),
        axisBorder: {
            color: '#e9e9e9',
        },
        labels: {
            style: {
                colors: "#8c9097",
                fontSize: '11px',
                fontWeight: 600,
            },
        }
    },
    yaxis: {
        title: {
            text: 'Request Count',
            style: {
                color: "#8c9097",
            }
        },
        labels: {
            style: {
                colors: "#8c9097",
                fontSize: '11px',
                fontWeight: 600,
            },
        }
    },
    legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
    },
    dataLabels: {
        enabled: true,
        style: {
            fontSize: '10px',
            colors: ['#fff']
        }
    },
    fill: {
        opacity: 1
    },
    tooltip: {
        y: {
            formatter: function (val: number) {
                return val + " requests"
            }
        }
    }
}));

const combinedChartSeries = computed(() => [
    {
        name: 'Pending',
        data: lineData.value.map(d => d.pending)
    },
    {
        name: 'Completed',
        data: lineData.value.map(d => d.completed)
    },
    {
        name: 'Rejected',
        data: lineData.value.map(d => d.rejected)
    }
]);

// Chart instances
let donutChart: ApexCharts | null = null;
let combinedChart: ApexCharts | null = null;
let sizeChart: ApexCharts | null = null;

// Initialize charts
onMounted(async () => {
    // Load persisted settings
    loadPersistedSettings();
    
    // Fetch data first
    await fetchData();
    
    // Fetch WIP last update
    await fetchWipLastUpdate();
    
    // Then initialize charts
    // Donut Chart
    const donutElement = document.querySelector('#donut-chart');
    if (donutElement) {
        donutChart = new ApexCharts(donutElement, {
            ...donutChartOptions.value,
            series: donutChartSeries.value
        });
        donutChart.render();
    }

    // Size Chart
    const sizeElement = document.querySelector('#size-chart');
    if (sizeElement) {
        sizeChart = new ApexCharts(sizeElement, {
            ...sizeChartOptions.value,
            series: sizeChartSeries.value
        });
        sizeChart.render();
    }

    // Combined Chart
    const combinedElement = document.querySelector('#combined-chart');
    if (combinedElement) {
        combinedChart = new ApexCharts(combinedElement, {
            ...combinedChartOptions.value,
            series: combinedChartSeries.value
        });
        combinedChart.render();
    }
    
    // Start auto-refresh if enabled
    if (autoRefresh.value) {
        startAutoRefresh();
        startSessionKeepAlive();
    }
});

// Cleanup on unmount
onUnmounted(() => {
    stopAutoRefresh();
    stopSessionKeepAlive();
});

// Update charts when data changes
watch([donutChartSeries, combinedChartSeries, sizeChartSeries], () => {
    if (donutChart) {
        donutChart.updateSeries(donutChartSeries.value);
    }
    if (combinedChart) {
        combinedChart.updateSeries(combinedChartSeries.value);
    }
    if (sizeChart) {
        sizeChart.updateSeries(sizeChartSeries.value);
    }
});

// Watch status filter and refetch data
watch(statusFilter, () => {
    fetchData();
});

// Watch date filters
watch([dateFrom, dateTo], () => {
    saveSettings();
    fetchData();
});
</script>

<template>
    <Head title="Lot Request Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #filters>
            <div class="flex items-center gap-3 flex-wrap">
                <!-- WIP Last Update Info -->
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800">
                    <span class="text-xs font-medium text-blue-700 dark:text-blue-300">WIP Last Update:</span>
                    <span class="text-xs font-semibold text-blue-900 dark:text-blue-100">{{ formatWipLastUpdate }}</span>
                </div>
                
                <!-- Update WIP Button -->
                <Button
                    @click="openWipUpdateModal"
                    variant="default"
                    size="sm"
                    class="h-8 gap-2 bg-sky-500 hover:bg-sky-600 text-white"
                    title="Update WIP data"
                >
                    <span class="text-sm">💾</span>
                    <span class="text-xs">Update WIP</span>
                </Button>
                
                <!-- Divider -->
                <div class="h-6 w-px bg-border"></div>
                
                <!-- Date Filters -->
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5">
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="dateFrom"
                            type="date"
                            class="h-8 w-36 text-xs"
                            placeholder="From"
                            :max="dateTo || undefined"
                        />
                    </div>
                    <span class="text-xs text-muted-foreground">to</span>
                    <Input
                        v-model="dateTo"
                        type="date"
                        class="h-8 w-36 text-xs"
                        placeholder="To"
                        :min="dateFrom || undefined"
                        :max="getTodayDate()"
                    />
                    <Button
                        v-if="dateFrom || dateTo"
                        @click="clearDateFilters"
                        variant="ghost"
                        size="sm"
                        class="h-8 px-2 text-xs"
                        title="Clear date filters"
                    >
                        ✕
                    </Button>
                </div>
                
                <!-- Divider -->
                <div class="h-6 w-px bg-border"></div>
                
                <!-- Auto Refresh Toggle -->
                <Button
                    @click="toggleAutoRefresh"
                    :variant="autoRefresh ? 'default' : 'outline'"
                    size="sm"
                    class="transition-all gap-2"
                    title="Auto-refresh every 5 seconds"
                >
                    <RefreshCw :class="['h-3.5 w-3.5', autoRefresh ? 'animate-spin' : '']" />
                    <span class="text-xs">Auto Refresh</span>
                </Button>
                
                <!-- Manual Refresh -->
                <Button
                    @click="fetchData"
                    variant="outline"
                    size="sm"
                    class="transition-all"
                    :disabled="isLoading"
                    title="Refresh now"
                >
                    <RefreshCw :class="['h-3.5 w-3.5', isLoading ? 'animate-spin' : '']" />
                </Button>
            </div>
        </template>

        <div class="flex h-full flex-1 flex-col gap-3 overflow-auto p-4 bg-gradient-to-br from-background via-background to-muted/10">
            <!-- Top Section: Donut Chart Left, Stats + Bar Chart Right -->
            <div class="grid gap-3 md:grid-cols-4">
                <!-- Donut Chart (Left) -->
                <Card class="overflow-hidden flex flex-col md:col-span-1">
                    <CardHeader class="pb-0 pt-2 px-3">
                        <CardTitle class="text-sm">LOT REQUEST SUMMARY</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0 flex-1 flex items-center justify-center">
                        <div id="donut-chart" class="w-full"></div>
                    </CardContent>
                    <div class="border-t">
                        <div class="grid grid-cols-4">
                            <div class="py-2 px-2 text-center border-r">
                                <div class="flex items-center justify-center gap-1 mb-1">
                                    <div class="w-2.5 h-2.5 rounded-full" style="background-color: rgb(0, 201, 255);"></div>
                                    <span class="text-xs font-medium text-muted-foreground">Total</span>
                                </div>
                                <h5 class="text-lg font-semibold mb-0">{{ stats.total }}</h5>
                            </div>
                            <div class="py-2 px-2 text-center border-r">
                                <div class="flex items-center justify-center gap-1 mb-1">
                                    <div class="w-2.5 h-2.5 rounded-full bg-[#985FFD]"></div>
                                    <span class="text-xs font-medium text-muted-foreground">Pending</span>
                                </div>
                                <h5 class="text-lg font-semibold mb-0">{{ stats.pending }}</h5>
                            </div>
                            <div class="py-2 px-2 text-center border-r">
                                <div class="flex items-center justify-center gap-1 mb-1">
                                    <div class="w-2.5 h-2.5 rounded-full bg-[#32D484]"></div>
                                    <span class="text-xs font-medium text-muted-foreground">Completed</span>
                                </div>
                                <h5 class="text-lg font-semibold mb-0">{{ stats.completed }}</h5>
                            </div>
                            <div class="py-2 px-2 text-center">
                                <div class="flex items-center justify-center gap-1 mb-1">
                                    <div class="w-2.5 h-2.5 rounded-full bg-[#FDAF22]"></div>
                                    <span class="text-xs font-medium text-muted-foreground">Rejected</span>
                                </div>
                                <h5 class="text-lg font-semibold mb-0">{{ stats.rejected }}</h5>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Right Side: Stats Cards + Bar Chart -->
                <div class="md:col-span-3 flex flex-col gap-3">
                    <!-- Stats Cards Row -->
                    <div class="grid gap-2 grid-cols-6">
                        <!-- Total Request - Blue -->
                        <button
                            @click="statusFilter = 'ALL'"
                            class="group relative overflow-hidden rounded-xl border transition-all duration-300 hover:shadow-lg text-left"
                            :class="statusFilter === 'ALL' 
                                ? 'border-blue-500 bg-gradient-to-br from-blue-100 to-blue-200/70 shadow-lg shadow-blue-500/20 dark:from-blue-900/50 dark:to-blue-800/50 ring-2 ring-blue-500' 
                                : 'border-border/50 bg-gradient-to-br from-blue-50 to-blue-100/50 hover:shadow-blue-500/10 dark:from-blue-950/30 dark:to-blue-900/20'"
                        >
                            <div class="p-2">
                                <div class="flex items-center gap-2">
                                    <div class="rounded-full bg-blue-500/10 p-1.5 ring-1 ring-blue-500/20">
                                        <span class="text-sm">📊</span>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-medium text-blue-700 dark:text-blue-300">Total Request</p>
                                        <p class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ stats.total }}</p>
                                    </div>
                                </div>
                            </div>
                        </button>

                        <!-- Pending - Purple -->
                        <button
                            @click="statusFilter = 'PENDING'"
                            class="group relative overflow-hidden rounded-xl border transition-all duration-300 hover:shadow-lg text-left"
                            :class="statusFilter === 'PENDING' 
                                ? 'border-purple-500 bg-gradient-to-br from-purple-100 to-purple-200/70 shadow-lg shadow-purple-500/20 dark:from-purple-900/50 dark:to-purple-800/50 ring-2 ring-purple-500' 
                                : 'border-border/50 bg-gradient-to-br from-purple-50 to-purple-100/50 hover:shadow-purple-500/10 dark:from-purple-950/30 dark:to-purple-900/20'"
                        >
                            <div class="p-2">
                                <div class="flex items-center gap-2">
                                    <div class="rounded-full bg-purple-500/10 p-1.5 ring-1 ring-purple-500/20">
                                        <span class="text-sm">⏳</span>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-medium text-purple-700 dark:text-purple-300">Pending</p>
                                        <p class="text-lg font-bold text-purple-900 dark:text-purple-100">{{ stats.pending }}</p>
                                    </div>
                                </div>
                            </div>
                        </button>

                        <!-- Completed - Green -->
                        <button
                            @click="statusFilter = 'COMPLETED'"
                            class="group relative overflow-hidden rounded-xl border transition-all duration-300 hover:shadow-lg text-left"
                            :class="statusFilter === 'COMPLETED' 
                                ? 'border-emerald-500 bg-gradient-to-br from-emerald-100 to-emerald-200/70 shadow-lg shadow-emerald-500/20 dark:from-emerald-900/50 dark:to-emerald-800/50 ring-2 ring-emerald-500' 
                                : 'border-border/50 bg-gradient-to-br from-emerald-50 to-emerald-100/50 hover:shadow-emerald-500/10 dark:from-emerald-950/30 dark:to-emerald-900/20'"
                        >
                            <div class="p-2">
                                <div class="flex items-center gap-2">
                                    <div class="rounded-full bg-emerald-500/10 p-1.5 ring-1 ring-emerald-500/20">
                                        <span class="text-sm">✅</span>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-medium text-emerald-700 dark:text-emerald-300">Completed</p>
                                        <p class="text-lg font-bold text-emerald-900 dark:text-emerald-100">{{ stats.completed }}</p>
                                    </div>
                                </div>
                            </div>
                        </button>

                        <!-- Rejected - Red -->
                        <button
                            @click="statusFilter = 'REJECTED'"
                            class="group relative overflow-hidden rounded-xl border transition-all duration-300 hover:shadow-lg text-left"
                            :class="statusFilter === 'REJECTED' 
                                ? 'border-red-500 bg-gradient-to-br from-red-100 to-red-200/70 shadow-lg shadow-red-500/20 dark:from-red-900/50 dark:to-red-800/50 ring-2 ring-red-500' 
                                : 'border-border/50 bg-gradient-to-br from-red-50 to-red-100/50 hover:shadow-red-500/10 dark:from-red-950/30 dark:to-red-900/20'"
                        >
                            <div class="p-2">
                                <div class="flex items-center gap-2">
                                    <div class="rounded-full bg-red-500/10 p-1.5 ring-1 ring-red-500/20">
                                        <span class="text-sm">❌</span>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-medium text-red-700 dark:text-red-300">Rejected</p>
                                        <p class="text-lg font-bold text-red-900 dark:text-red-100">{{ stats.rejected }}</p>
                                    </div>
                                </div>
                            </div>
                        </button>

                        <!-- Completion % - Dynamic Color (Not clickable) -->
                        <div class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br p-2 shadow-sm transition-all duration-300 hover:shadow-lg" :class="[getCompletionRateColor.bg, getCompletionRateColor.shadow]">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full p-1.5 ring-1" :class="getCompletionRateColor.ring">
                                    <span class="text-sm">📈</span>
                                </div>
                                <div>
                                    <p class="text-[10px] font-medium" :class="getCompletionRateColor.text">Completion %</p>
                                    <p class="text-lg font-bold" :class="getCompletionRateColor.value">{{ stats.completionRate }}%</p>
                                </div>
                            </div>
                        </div>

                        <!-- Avg. Time - Dark Blue (Not clickable) -->
                        <div class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-indigo-50 to-indigo-100/50 p-2 shadow-sm transition-all duration-300 hover:shadow-lg hover:shadow-indigo-500/10 dark:from-indigo-950/30 dark:to-indigo-900/20">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-indigo-500/10 p-1.5 ring-1 ring-indigo-500/20">
                                    <span class="text-sm">⏱️</span>
                                </div>
                                <div>
                                    <p class="text-[10px] font-medium text-indigo-700 dark:text-indigo-300">Avg. Time</p>
                                    <p class="text-lg font-bold text-indigo-900 dark:text-indigo-100">{{ stats.avgResponseTime }}h</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row: Size Chart (30%) + Production Line Chart (70%) -->
                    <div class="grid gap-3 grid-cols-10 flex-1">
                        <!-- Per Size Request Chart (30%) -->
                        <Card class="overflow-hidden flex flex-col col-span-3">
                            <CardHeader class="pb-0 pt-1 px-3">
                                <CardTitle class="text-sm">LOT REQUEST PER SIZE</CardTitle>
                            </CardHeader>
                            <CardContent class="p-0 flex-1 flex items-center justify-center -mt-4">
                                <div id="size-chart" class="w-full"></div>
                            </CardContent>
                        </Card>

                        <!-- Production Line Chart (70%) -->
                        <Card class="overflow-hidden flex flex-col col-span-7">
                            <CardHeader class="pb-0 pt-1 px-3">
                                <CardTitle class="text-sm">LOT REQUEST PER LINE</CardTitle>
                            </CardHeader>
                            <CardContent class="p-0 -mt-4">
                                <div id="combined-chart"></div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Table with Fixed Header -->
            <Card class="overflow-hidden flex flex-col">
                <div class="overflow-auto max-h-[650px]">
                    <table class="w-full">
                        <thead class="bg-muted border-b sticky top-0 z-10">
                            <tr>
                                <th @click="handleSort('mc_no')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        MC No.
                                        <span v-if="sortColumn === 'mc_no'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th @click="handleSort('area')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Area
                                        <span v-if="sortColumn === 'area'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th @click="handleSort('requestor')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Requestor
                                        <span v-if="sortColumn === 'requestor'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th @click="handleSort('request_model')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Req. Model
                                        <span v-if="sortColumn === 'request_model'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th @click="handleSort('lot_no')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Assigned Lot
                                        <span v-if="sortColumn === 'lot_no'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th @click="handleSort('model')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Assigned Model
                                        <span v-if="sortColumn === 'model'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th @click="handleSort('quantity')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Quantity
                                        <span v-if="sortColumn === 'quantity'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th @click="handleSort('lipas')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        LIPAS
                                        <span v-if="sortColumn === 'lipas'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th @click="handleSort('lot_tat')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        TAT
                                        <span v-if="sortColumn === 'lot_tat'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th @click="handleSort('requested')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Requested
                                        <span v-if="sortColumn === 'requested'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th @click="handleSort('completed')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Completed
                                        <span v-if="sortColumn === 'completed'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th @click="handleSort('response_time')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Response Time
                                        <span v-if="sortColumn === 'response_time'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th @click="handleSort('status')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Status
                                        <span v-if="sortColumn === 'status'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th @click="handleSort('remarks')" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted cursor-pointer hover:bg-muted/80 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Remarks
                                        <span v-if="sortColumn === 'remarks'" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr 
                                v-for="request in filteredRequests" 
                                :key="request.id"
                                class="hover:bg-muted/30 transition-colors"
                                :class="{
                                    'border-l-4 border-l-[hsl(253_175_34%)]': request.status === 'PENDING',
                                    'border-l-4 border-l-[hsl(142_76%_36%)]': request.status === 'COMPLETED'
                                }"
                            >
                                <td class="px-4 py-3 text-sm text-foreground">
                                    <span class="font-medium text-secondary">{{ request.mc_no }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    <Badge variant="outline" class="text-xs">{{ request.area }}</Badge>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ request.requestor }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    <span v-if="request.request_model" class="font-medium text-blue-600 dark:text-blue-400">{{ request.request_model }}</span>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    <span v-if="request.lot_no" class="font-medium">{{ request.lot_no }}</span>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    <span v-if="request.model" class="font-medium text-green-600 dark:text-green-400">{{ request.model }}</span>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-foreground">
                                    <span v-if="request.quantity">{{ new Intl.NumberFormat().format(request.quantity) }}</span>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :class="getLipasColor(request.lipas)" class="text-xs">
                                        {{ getLipasLabel(request.lipas) }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    <span v-if="request.lot_tat">{{ request.lot_tat }}</span>
                                    <span v-else>-</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">{{ formatDateTime(request.requested) }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">
                                    <span v-if="request.completed">{{ formatDateTime(request.completed) }}</span>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-amber-600 dark:text-amber-400 font-medium whitespace-nowrap">
                                    <span v-if="request.response_time">{{ request.response_time }}</span>
                                    <span v-else>-</span>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :class="getStatusColor(request.status)" class="text-xs">
                                        {{ request.status }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground max-w-[200px]">
                                    <span v-if="request.remarks" :title="request.remarks" class="line-clamp-2">{{ request.remarks }}</span>
                                    <span v-else>-</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button 
                                            @click="handleAccept(request)"
                                            size="sm"
                                            :disabled="request.status === 'COMPLETED'"
                                            class="h-7 px-3 text-xs bg-emerald-600 hover:bg-emerald-700 text-white dark:bg-emerald-700 dark:hover:bg-emerald-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Accept
                                        </Button>
                                        <Button 
                                            @click="handleView(request)"
                                            size="sm"
                                            variant="outline"
                                            class="h-7 px-3 text-xs"
                                        >
                                            View
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Enhanced Empty State -->
            <div v-if="filteredRequests.length === 0" class="flex flex-col items-center justify-center py-16 bg-card/30 rounded-xl border-2 border-dashed">
                <div class="text-8xl mb-4 animate-pulse">📦</div>
                <h3 class="text-2xl font-bold text-muted-foreground">No requests found</h3>
                <p class="text-sm text-muted-foreground mt-2">Try adjusting your filters to see more results</p>
            </div>
        </div>
        
        <!-- Assign Lot Modal -->
        <MemsAssignLotModal
            :open="showAssignLotModal"
            :equipment-no="selectedRequestForAssignment?.mc_no ?? null"
            :equipment-line="selectedRequestForAssignment?.line ?? null"
            :equipment-area="selectedRequestForAssignment?.area ?? null"
            :previous-model="selectedRequestForAssignment?.request_model ?? null"
            :previous-worktype="null"
            :ongoing-lot="selectedRequestForAssignment?.ongoing_lot ?? null"
            :est-endtime="selectedRequestForAssignment?.est_endtime ?? null"
            :waiting-time="selectedRequestForAssignment?.waiting_time ?? null"
            :request-id="selectedRequestForAssignment?.id ?? null"
            :mc-rack="selectedRequestForAssignment?.mc_rack ?? null"
            @update:open="showAssignLotModal = $event"
            @assign="handleAssignLotConfirm"
        />
        
        <!-- View Lot Request Modal -->
        <LotRequestViewModal
            :open="showViewModal"
            :request="selectedRequestForView"
            @update:open="showViewModal = $event"
            @refresh="fetchData"
        />
        
        <!-- WIP Update Modal -->
        <EndtimeWipupdateModal
            :open="showWipUpdateModal"
            @update:open="handleWipUpdateComplete"
        />
    </AppLayout>
</template>
