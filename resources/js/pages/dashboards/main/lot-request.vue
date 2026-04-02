<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import EndtimeWipupdateModal from '@/pages/dashboards/subs/endtime-wipupdate-modal.vue';
import LotRequestViewModal from '@/pages/dashboards/subs/lot-request-view-modal.vue';
import MemsAssignLotModal from '@/pages/dashboards/subs/mems-assign-lot-modal.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import ApexCharts from 'apexcharts';
import axios from 'axios';
import { Calendar, RefreshCw } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

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
    // Additional equipment data properties
    ongoing_lot?: string | null;
    est_endtime?: string | null;
    waiting_time?: string | null;
    mc_rack?: string | null;
    // MC status columns shown in table
    mc_status?: string | null;
    mc_endtime?: string | null;
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
    avgResponseTime: 0,
});
const sizeData = ref<SizeData[]>([]);
const lineData = ref<LineData[]>([]);
const isLoading = ref(false);

const statusFilter = ref<string>('ALL');
const sortColumn = ref<string>('');
const sortDirection = ref<'asc' | 'desc'>('asc');
const tableSearch = ref<string>('');
const isDarkMode = ref(document.documentElement.classList.contains('dark'));

// Single date filter (Asia/Manila timezone) — empty by default (no date = no entries)
const selectedDate = ref<string>('');

// Auto-refresh state with persistence
const autoRefresh = ref<boolean>(false);
const refreshIntervalSecs = ref<number>(5); // user-configurable interval
const refreshInterval = ref<number | null>(null);
const sessionKeepAliveInterval = ref<number | null>(null);
const SESSION_KEEPALIVE_MS = 240000; // 4 minutes (keep session alive)

// Modal state
const showAssignLotModal = ref(false);
const selectedRequestForAssignment = ref<LotRequest | null>(null);
const showViewModal = ref(false);
const selectedRequestForView = ref<LotRequest | null>(null);
const showWipUpdateModal = ref(false);

// WIP last update state
const wipLastUpdate = ref<string | null>(null);

// Get today's date in YYYY-MM-DD format (Asia/Manila timezone)
const getTodayDate = () => {
    return new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' });
};

// Load persisted settings from localStorage
const loadPersistedSettings = () => {
    try {
        const savedDate = localStorage.getItem('lotRequest_selectedDate');
        const savedAutoRefresh = localStorage.getItem('lotRequest_autoRefresh');
        const savedInterval = localStorage.getItem(
            'lotRequest_refreshInterval',
        );

        if (savedDate) selectedDate.value = savedDate;
        else selectedDate.value = '';
        if (savedAutoRefresh) autoRefresh.value = savedAutoRefresh === 'true';
        if (savedInterval) refreshIntervalSecs.value = Number(savedInterval);
    } catch (error) {
        console.error('Error loading persisted settings:', error);
    }
};

// Save settings to localStorage
const saveSettings = () => {
    try {
        localStorage.setItem('lotRequest_selectedDate', selectedDate.value);
        localStorage.setItem(
            'lotRequest_autoRefresh',
            String(autoRefresh.value),
        );
        localStorage.setItem(
            'lotRequest_refreshInterval',
            String(refreshIntervalSecs.value),
        );
    } catch (error) {
        console.error('Error saving settings:', error);
    }
};

// Toggle auto-refresh
const toggleAutoRefresh = () => {
    autoRefresh.value = !autoRefresh.value;
    saveSettings();

    if (autoRefresh.value) {
        // Always snap to today when enabling auto-refresh
        selectedDate.value = getTodayDate();
        startAutoRefresh();
        startSessionKeepAlive();
    } else {
        stopAutoRefresh();
        stopSessionKeepAlive();
    }
};

// Update refresh interval and restart if running
const updateRefreshInterval = (secs: number) => {
    refreshIntervalSecs.value = secs;
    saveSettings();
    if (autoRefresh.value) {
        stopAutoRefresh();
        startAutoRefresh();
    }
};

// Start auto-refresh interval
const startAutoRefresh = () => {
    if (refreshInterval.value) return;

    refreshInterval.value = window.setInterval(() => {
        // Always set date to today (Manila) on each auto-refresh tick
        selectedDate.value = getTodayDate();
        fetchData();
    }, refreshIntervalSecs.value * 1000);
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

// Clear date filter
const clearDateFilter = () => {
    selectedDate.value = '';
    saveSettings();
    fetchData();
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

// Detect if a string looks like a lot number (7 chars, alphanumeric)
const looksLikeLotNo = (q: string) => /^[a-z0-9]{5,8}$/i.test(q.trim());

// Fetch data from API
const fetchData = async (searchMode: 'normal' | 'lot' | 'model' = 'normal') => {
    // No date and no active search — show nothing
    if (!selectedDate.value && searchMode === 'normal') {
        lotRequests.value = [];
        stats.value = {
            total: 0,
            pending: 0,
            completed: 0,
            rejected: 0,
            completionRate: 0,
            avgResponseTime: 0,
        };
        sizeData.value = [];
        lineData.value = [];
        return;
    }

    isLoading.value = true;
    try {
        const params: any = {};
        params.status = statusFilter.value;

        // lot search: no date filter (search all dates)
        // model search: current date + pending only
        // normal: use selected date
        if (searchMode === 'lot') {
            // no date params — fetch all
        } else if (searchMode === 'model') {
            const date = selectedDate.value || getTodayDate();
            params.date_from = date;
            params.date_to = date;
            // keep existing statusFilter (don't override)
        } else if (selectedDate.value) {
            params.date_from = selectedDate.value;
            params.date_to = selectedDate.value;
        }

        const dateParams =
            searchMode === 'lot'
                ? {}
                : { date_from: params.date_from, date_to: params.date_to };

        const [requestsRes, statsRes, sizeRes, lineRes] = await Promise.all([
            axios.get('/api/lot-request/data', { params }),
            axios.get('/api/lot-request/stats', { params: dateParams }),
            axios.get('/api/lot-request/by-size', { params: dateParams }),
            axios.get('/api/lot-request/by-line', { params: dateParams }),
        ]);

        const requests: LotRequest[] = requestsRes.data.data;
        stats.value = statsRes.data;
        sizeData.value = sizeRes.data.data;
        lineData.value = lineRes.data.data;

        // Enrich each row with live equipment status/endtime — single batch call
        const uniqueMachines = [
            ...new Set(requests.map((r) => r.mc_no).filter(Boolean)),
        ];
        let equipmentMap: Record<string, any> = {};

        if (uniqueMachines.length > 0) {
            try {
                const res = await axios.get('/api/equipment/details-batch', {
                    params: { eqp_nos: uniqueMachines.join(',') },
                });
                equipmentMap = res.data;
            } catch {
                // silently ignore — rows will show '-'
            }
        }

        lotRequests.value = requests.map((r) => {
            const eq = equipmentMap[r.mc_no];
            if (!eq) return r;
            const isRunning = eq.ongoing_lot && eq.ongoing_lot.trim() !== '';
            return {
                ...r,
                ongoing_lot: eq.ongoing_lot || null,
                est_endtime: eq.est_endtime || null,
                waiting_time: eq.waiting_time || null,
                mc_rack: eq.mc_rack || null,
                mc_status: isRunning ? 'Running' : 'Waiting',
                mc_endtime: isRunning ? eq.est_endtime || null : null,
            };
        });
    } catch (error) {
        console.error('Error fetching lot request data:', error);
    } finally {
        isLoading.value = false;
    }
};

const filteredRequests = computed(() => {
    const q = tableSearch.value.trim().toLowerCase();

    // When searching, skip status filter so all matching records show
    let filtered = q
        ? lotRequests.value
        : statusFilter.value === 'ALL'
          ? lotRequests.value
          : lotRequests.value.filter(
                (req) => req.status === statusFilter.value,
            );

    // Wildcard search across visible fields
    if (q) {
        filtered = filtered.filter((req) =>
            [
                req.mc_no,
                req.area,
                req.requestor,
                req.request_model,
                req.lot_no,
                req.model,
                req.status,
                req.remarks,
                req.lot_tat,
                req.response_time,
                req.request_no,
            ]
                .filter(Boolean)
                .some((v) => String(v).toLowerCase().includes(q)),
        );
    }

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
        REJECTED: 'bg-red-600 text-white dark:bg-red-700',
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
            shadow: 'hover:shadow-emerald-500/10',
        };
    } else if (rate >= 60) {
        // Medium completion rate - Yellow/Amber
        return {
            bg: 'from-amber-50 to-amber-100/50 dark:from-amber-950/30 dark:to-amber-900/20',
            ring: 'bg-amber-500/10 ring-amber-500/20',
            text: 'text-amber-700 dark:text-amber-300',
            value: 'text-amber-900 dark:text-amber-100',
            shadow: 'hover:shadow-amber-500/10',
        };
    } else if (rate >= 40) {
        // Low completion rate - Orange
        return {
            bg: 'from-orange-50 to-orange-100/50 dark:from-orange-950/30 dark:to-orange-900/20',
            ring: 'bg-orange-500/10 ring-orange-500/20',
            text: 'text-orange-700 dark:text-orange-300',
            value: 'text-orange-900 dark:text-orange-100',
            shadow: 'hover:shadow-orange-500/10',
        };
    } else {
        // Very low completion rate - Red
        return {
            bg: 'from-red-50 to-red-100/50 dark:from-red-950/30 dark:to-red-900/20',
            ring: 'bg-red-500/10 ring-red-500/20',
            text: 'text-red-700 dark:text-red-300',
            value: 'text-red-900 dark:text-red-100',
            shadow: 'hover:shadow-red-500/10',
        };
    }
});

const handleAccept = async (request: LotRequest) => {
    try {
        // Fetch equipment data to get ongoing_lot, est_endtime, waiting_time, and mc_rack
        const response = await axios.get(
            `/api/equipment/details/${request.mc_no}`,
        );
        const equipmentData = response.data;

        const isRunning =
            equipmentData.ongoing_lot &&
            equipmentData.ongoing_lot.trim() !== '';
        const enriched: LotRequest = {
            ...request,
            ongoing_lot: equipmentData.ongoing_lot || null,
            est_endtime: equipmentData.est_endtime || null,
            waiting_time: equipmentData.waiting_time || null,
            mc_rack: equipmentData.mc_rack || null,
            mc_status: isRunning ? 'Running' : 'Waiting',
            mc_endtime: isRunning ? equipmentData.est_endtime || null : null,
        };

        // Also update the row in the table so the columns reflect live data
        const idx = lotRequests.value.findIndex((r) => r.id === request.id);
        if (idx !== -1) {
            lotRequests.value[idx] = { ...lotRequests.value[idx], ...enriched };
        }

        selectedRequestForAssignment.value = enriched;
        showAssignLotModal.value = true;
    } catch (error) {
        console.error('Error fetching equipment data:', error);
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
        const response = await axios.post(
            `/api/lot-request/${requestId}/reject`,
        );
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
        NORMAL: 'bg-muted text-muted-foreground',
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
const donutChartOptions = computed(() => {
    const labelColor = isDarkMode.value ? '#e5e7eb' : '#495057';
    return {
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
                            color: labelColor,
                            offsetY: -5,
                        },
                        value: {
                            show: true,
                            fontSize: '22px',
                            offsetY: 5,
                            fontWeight: 600,
                            color: labelColor,
                            formatter: function (val: number) {
                                return Math.round(val).toString();
                            },
                        },
                        total: {
                            show: true,
                            showAlways: true,
                            label: 'Completion Rate',
                            fontSize: '14px',
                            fontWeight: 400,
                            color: labelColor,
                            formatter: () => `${stats.value.completionRate}%`,
                        },
                    },
                },
            },
        },
        dataLabels: {
            enabled: false,
        },
    };
});

const donutChartSeries = computed(() => {
    const total = stats.value.total;
    if (total === 0) return [0, 0, 0];

    return [stats.value.pending, stats.value.completed, stats.value.rejected];
});

// Size Chart Configuration (Stacked Bar)
const sizeChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 300,
        stacked: true,
        toolbar: {
            show: false,
        },
        offsetY: -20,
    },
    plotOptions: {
        bar: {
            horizontal: true,
            barHeight: '60%',
        },
    },
    colors: ['#985FFD', '#32D484', '#FDAF22'],
    dataLabels: {
        enabled: true,
        style: {
            fontSize: '10px',
            fontWeight: 600,
            colors: ['#fff'],
        },
    },
    grid: {
        borderColor: '#f1f1f1',
        strokeDashArray: 3,
    },
    xaxis: {
        categories: sizeData.value.map((d) => d.size),
        labels: {
            style: {
                colors: '#8c9097',
                fontSize: '11px',
                fontWeight: 600,
            },
        },
    },
    yaxis: {
        labels: {
            style: {
                colors: '#8c9097',
                fontSize: '11px',
                fontWeight: 600,
            },
        },
    },
    legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
        fontSize: '11px',
    },
    fill: {
        opacity: 1,
    },
    tooltip: {
        y: {
            formatter: function (val: number) {
                return val + ' requests';
            },
        },
    },
}));

const sizeChartSeries = computed(() => [
    {
        name: 'Pending',
        data: sizeData.value.map((d) => d.pending),
    },
    {
        name: 'Completed',
        data: sizeData.value.map((d) => d.completed),
    },
    {
        name: 'Rejected',
        data: sizeData.value.map((d) => d.rejected),
    },
]);

// Stacked Column Chart Configuration
const combinedChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 300,
        stacked: true,
        toolbar: {
            show: false,
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
        },
    },
    colors: ['#985FFD', '#32D484', '#FDAF22'],
    xaxis: {
        categories: lineData.value.map((d) => d.line),
        axisBorder: {
            color: '#e9e9e9',
        },
        labels: {
            style: {
                colors: '#8c9097',
                fontSize: '11px',
                fontWeight: 600,
            },
        },
    },
    yaxis: {
        title: {
            text: 'Request Count',
            style: {
                color: '#8c9097',
            },
        },
        labels: {
            style: {
                colors: '#8c9097',
                fontSize: '11px',
                fontWeight: 600,
            },
        },
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
            colors: ['#fff'],
        },
    },
    fill: {
        opacity: 1,
    },
    tooltip: {
        y: {
            formatter: function (val: number) {
                return val + ' requests';
            },
        },
    },
}));

const combinedChartSeries = computed(() => [
    {
        name: 'Pending',
        data: lineData.value.map((d) => d.pending),
    },
    {
        name: 'Completed',
        data: lineData.value.map((d) => d.completed),
    },
    {
        name: 'Rejected',
        data: lineData.value.map((d) => d.rejected),
    },
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
            series: donutChartSeries.value,
        });
        donutChart.render();
    }

    // Size Chart
    const sizeElement = document.querySelector('#size-chart');
    if (sizeElement) {
        sizeChart = new ApexCharts(sizeElement, {
            ...sizeChartOptions.value,
            series: sizeChartSeries.value,
        });
        sizeChart.render();
    }

    // Combined Chart
    const combinedElement = document.querySelector('#combined-chart');
    if (combinedElement) {
        combinedChart = new ApexCharts(combinedElement, {
            ...combinedChartOptions.value,
            series: combinedChartSeries.value,
        });
        combinedChart.render();
    }

    // Start auto-refresh if enabled
    if (autoRefresh.value) {
        startAutoRefresh();
        startSessionKeepAlive();
    }

    // Watch for dark mode toggle and update donut chart colors
    const themeObserver = new MutationObserver(() => {
        isDarkMode.value = document.documentElement.classList.contains('dark');
        if (donutChart) {
            donutChart.updateOptions(donutChartOptions.value);
        }
    });
    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
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

// Watch date filter
watch(selectedDate, () => {
    saveSettings();
    fetchData();
});

// Search watch — lot number searches all dates, model searches current date
let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(tableSearch, (val) => {
    if (searchTimer) clearTimeout(searchTimer);
    const q = val.trim();
    if (!q) {
        // Cleared — restore normal date-based fetch
        fetchData();
        return;
    }
    searchTimer = setTimeout(() => {
        if (looksLikeLotNo(q)) {
            fetchData('lot');
        } else {
            fetchData('model');
        }
    }, 400);
});
</script>

<template>
    <Head title="Lot Request Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #filters>
            <div class="flex flex-wrap items-center gap-3">
                <!-- WIP Last Update Info -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 dark:border-blue-800 dark:bg-blue-950/30"
                >
                    <span
                        class="text-xs font-medium text-blue-700 dark:text-blue-300"
                        >WIP Last Update:</span
                    >
                    <span
                        class="text-xs font-semibold text-blue-900 dark:text-blue-100"
                        >{{ formatWipLastUpdate }}</span
                    >
                </div>

                <!-- Divider -->
                <div class="h-6 w-px bg-border"></div>

                <!-- Date Filter -->
                <div class="flex items-center gap-2">
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="selectedDate"
                        type="date"
                        class="h-8 w-36 text-xs"
                        :max="getTodayDate()"
                        :disabled="autoRefresh"
                        title="Filter by date (Asia/Manila)"
                    />
                    <Button
                        v-if="selectedDate"
                        @click="clearDateFilter"
                        variant="ghost"
                        size="sm"
                        class="h-8 px-2 text-xs"
                        title="Clear date filter"
                        :disabled="autoRefresh"
                    >
                        ✕
                    </Button>
                </div>

                <!-- Divider -->
                <div class="h-6 w-px bg-border"></div>

                <!-- Auto Refresh Toggle + Interval -->
                <div class="flex items-center gap-1.5">
                    <Button
                        @click="toggleAutoRefresh"
                        :variant="autoRefresh ? 'default' : 'outline'"
                        size="sm"
                        class="gap-2 transition-all"
                        title="Auto-refresh (sets date to today)"
                    >
                        <RefreshCw
                            :class="[
                                'h-3.5 w-3.5',
                                autoRefresh ? 'animate-spin' : '',
                            ]"
                        />
                        <span class="text-xs">Auto Refresh</span>
                    </Button>
                    <select
                        :value="refreshIntervalSecs"
                        @change="
                            updateRefreshInterval(
                                Number(
                                    ($event.target as HTMLSelectElement).value,
                                ),
                            )
                        "
                        class="h-8 rounded-md border border-input bg-background px-2 text-xs text-foreground focus:ring-1 focus:ring-ring focus:outline-none"
                        title="Refresh interval"
                    >
                        <option
                            v-for="s in [5, 10, 15, 20, 30, 45, 60]"
                            :key="s"
                            :value="s"
                        >
                            {{ s }}s
                        </option>
                    </select>
                </div>

                <!-- Manual Refresh -->
                <Button
                    @click="fetchData"
                    variant="outline"
                    size="sm"
                    class="transition-all"
                    :disabled="isLoading"
                    title="Refresh now"
                >
                    <RefreshCw
                        :class="[
                            'h-3.5 w-3.5',
                            isLoading ? 'animate-spin' : '',
                        ]"
                    />
                </Button>
            </div>
        </template>

        <div
            class="flex h-full flex-1 flex-col gap-3 overflow-hidden bg-gradient-to-br from-background via-background to-muted/10 p-4"
        >
            <!-- Top Section: Donut Chart Left, Stats + Bar Chart Right -->
            <div class="grid gap-3 md:grid-cols-4">
                <!-- Donut Chart (Left) -->
                <Card
                    class="flex flex-col overflow-hidden md:col-span-1"
                    style="min-height: 380px"
                >
                    <CardHeader class="px-3 pt-2 pb-0">
                        <CardTitle class="text-sm"
                            >LOT REQUEST SUMMARY</CardTitle
                        >
                    </CardHeader>
                    <CardContent
                        class="flex flex-1 items-center justify-center p-0"
                    >
                        <div
                            id="donut-chart"
                            class="w-full"
                            style="min-height: 250px"
                        ></div>
                    </CardContent>
                    <div class="border-t">
                        <div class="grid grid-cols-4">
                            <div class="border-r px-2 py-2 text-center">
                                <div
                                    class="mb-1 flex items-center justify-center gap-1"
                                >
                                    <div
                                        class="h-2.5 w-2.5 rounded-full"
                                        style="
                                            background-color: rgb(0, 201, 255);
                                        "
                                    ></div>
                                    <span
                                        class="text-xs font-medium text-muted-foreground"
                                        >Total</span
                                    >
                                </div>
                                <h5 class="mb-0 text-lg font-semibold">
                                    {{ stats.total }}
                                </h5>
                            </div>
                            <div class="border-r px-2 py-2 text-center">
                                <div
                                    class="mb-1 flex items-center justify-center gap-1"
                                >
                                    <div
                                        class="h-2.5 w-2.5 rounded-full bg-[#985FFD]"
                                    ></div>
                                    <span
                                        class="text-xs font-medium text-muted-foreground"
                                        >Pending</span
                                    >
                                </div>
                                <h5 class="mb-0 text-lg font-semibold">
                                    {{ stats.pending }}
                                </h5>
                            </div>
                            <div class="border-r px-2 py-2 text-center">
                                <div
                                    class="mb-1 flex items-center justify-center gap-1"
                                >
                                    <div
                                        class="h-2.5 w-2.5 rounded-full bg-[#32D484]"
                                    ></div>
                                    <span
                                        class="text-xs font-medium text-muted-foreground"
                                        >Completed</span
                                    >
                                </div>
                                <h5 class="mb-0 text-lg font-semibold">
                                    {{ stats.completed }}
                                </h5>
                            </div>
                            <div class="px-2 py-2 text-center">
                                <div
                                    class="mb-1 flex items-center justify-center gap-1"
                                >
                                    <div
                                        class="h-2.5 w-2.5 rounded-full bg-[#FDAF22]"
                                    ></div>
                                    <span
                                        class="text-xs font-medium text-muted-foreground"
                                        >Rejected</span
                                    >
                                </div>
                                <h5 class="mb-0 text-lg font-semibold">
                                    {{ stats.rejected }}
                                </h5>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Right Side: Stats Cards + Bar Chart -->
                <div class="flex flex-col gap-3 md:col-span-3">
                    <!-- Stats Cards Row -->
                    <div class="grid grid-cols-6 gap-2">
                        <!-- Total Request - Blue -->
                        <button
                            @click="statusFilter = 'ALL'"
                            class="group relative overflow-hidden rounded-xl border text-left transition-all duration-300 hover:shadow-lg"
                            :class="
                                statusFilter === 'ALL'
                                    ? 'border-blue-500 bg-gradient-to-br from-blue-100 to-blue-200/70 shadow-lg ring-2 shadow-blue-500/20 ring-blue-500 dark:from-blue-900/50 dark:to-blue-800/50'
                                    : 'border-border/50 bg-gradient-to-br from-blue-50 to-blue-100/50 hover:shadow-blue-500/10 dark:from-blue-950/30 dark:to-blue-900/20'
                            "
                        >
                            <div class="p-2">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="rounded-full bg-blue-500/10 p-1.5 ring-1 ring-blue-500/20"
                                    >
                                        <span class="text-sm">📊</span>
                                    </div>
                                    <div>
                                        <p
                                            class="text-[10px] font-medium text-blue-700 dark:text-blue-300"
                                        >
                                            Total Request
                                        </p>
                                        <p
                                            class="text-lg font-bold text-blue-900 dark:text-blue-100"
                                        >
                                            {{ stats.total }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </button>

                        <!-- Pending - Purple -->
                        <button
                            @click="statusFilter = 'PENDING'"
                            class="group relative overflow-hidden rounded-xl border text-left transition-all duration-300 hover:shadow-lg"
                            :class="
                                statusFilter === 'PENDING'
                                    ? 'border-purple-500 bg-gradient-to-br from-purple-100 to-purple-200/70 shadow-lg ring-2 shadow-purple-500/20 ring-purple-500 dark:from-purple-900/50 dark:to-purple-800/50'
                                    : 'border-border/50 bg-gradient-to-br from-purple-50 to-purple-100/50 hover:shadow-purple-500/10 dark:from-purple-950/30 dark:to-purple-900/20'
                            "
                        >
                            <div class="p-2">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="rounded-full bg-purple-500/10 p-1.5 ring-1 ring-purple-500/20"
                                    >
                                        <span class="text-sm">⏳</span>
                                    </div>
                                    <div>
                                        <p
                                            class="text-[10px] font-medium text-purple-700 dark:text-purple-300"
                                        >
                                            Pending
                                        </p>
                                        <p
                                            class="text-lg font-bold text-purple-900 dark:text-purple-100"
                                        >
                                            {{ stats.pending }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </button>

                        <!-- Completed - Green -->
                        <button
                            @click="statusFilter = 'COMPLETED'"
                            class="group relative overflow-hidden rounded-xl border text-left transition-all duration-300 hover:shadow-lg"
                            :class="
                                statusFilter === 'COMPLETED'
                                    ? 'border-emerald-500 bg-gradient-to-br from-emerald-100 to-emerald-200/70 shadow-lg ring-2 shadow-emerald-500/20 ring-emerald-500 dark:from-emerald-900/50 dark:to-emerald-800/50'
                                    : 'border-border/50 bg-gradient-to-br from-emerald-50 to-emerald-100/50 hover:shadow-emerald-500/10 dark:from-emerald-950/30 dark:to-emerald-900/20'
                            "
                        >
                            <div class="p-2">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="rounded-full bg-emerald-500/10 p-1.5 ring-1 ring-emerald-500/20"
                                    >
                                        <span class="text-sm">✅</span>
                                    </div>
                                    <div>
                                        <p
                                            class="text-[10px] font-medium text-emerald-700 dark:text-emerald-300"
                                        >
                                            Completed
                                        </p>
                                        <p
                                            class="text-lg font-bold text-emerald-900 dark:text-emerald-100"
                                        >
                                            {{ stats.completed }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </button>

                        <!-- Rejected - Red -->
                        <button
                            @click="statusFilter = 'REJECTED'"
                            class="group relative overflow-hidden rounded-xl border text-left transition-all duration-300 hover:shadow-lg"
                            :class="
                                statusFilter === 'REJECTED'
                                    ? 'border-red-500 bg-gradient-to-br from-red-100 to-red-200/70 shadow-lg ring-2 shadow-red-500/20 ring-red-500 dark:from-red-900/50 dark:to-red-800/50'
                                    : 'border-border/50 bg-gradient-to-br from-red-50 to-red-100/50 hover:shadow-red-500/10 dark:from-red-950/30 dark:to-red-900/20'
                            "
                        >
                            <div class="p-2">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="rounded-full bg-red-500/10 p-1.5 ring-1 ring-red-500/20"
                                    >
                                        <span class="text-sm">❌</span>
                                    </div>
                                    <div>
                                        <p
                                            class="text-[10px] font-medium text-red-700 dark:text-red-300"
                                        >
                                            Rejected
                                        </p>
                                        <p
                                            class="text-lg font-bold text-red-900 dark:text-red-100"
                                        >
                                            {{ stats.rejected }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </button>

                        <!-- Completion % - Dynamic Color (Not clickable) -->
                        <div
                            class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br p-2 shadow-sm transition-all duration-300 hover:shadow-lg"
                            :class="[
                                getCompletionRateColor.bg,
                                getCompletionRateColor.shadow,
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="rounded-full p-1.5 ring-1"
                                    :class="getCompletionRateColor.ring"
                                >
                                    <span class="text-sm">📈</span>
                                </div>
                                <div>
                                    <p
                                        class="text-[10px] font-medium"
                                        :class="getCompletionRateColor.text"
                                    >
                                        Completion %
                                    </p>
                                    <p
                                        class="text-lg font-bold"
                                        :class="getCompletionRateColor.value"
                                    >
                                        {{ stats.completionRate }}%
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Avg. Time - Dark Blue (Not clickable) -->
                        <div
                            class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-indigo-50 to-indigo-100/50 p-2 shadow-sm transition-all duration-300 hover:shadow-lg hover:shadow-indigo-500/10 dark:from-indigo-950/30 dark:to-indigo-900/20"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="rounded-full bg-indigo-500/10 p-1.5 ring-1 ring-indigo-500/20"
                                >
                                    <span class="text-sm">⏱️</span>
                                </div>
                                <div>
                                    <p
                                        class="text-[10px] font-medium text-indigo-700 dark:text-indigo-300"
                                    >
                                        Avg. Time
                                    </p>
                                    <p
                                        class="text-lg font-bold text-indigo-900 dark:text-indigo-100"
                                    >
                                        {{ stats.avgResponseTime }}h
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row: Size Chart (30%) + Production Line Chart (70%) -->
                    <div class="grid flex-1 grid-cols-10 gap-3">
                        <!-- Per Size Request Chart (30%) -->
                        <Card
                            class="col-span-3 flex flex-col overflow-hidden"
                            style="min-height: 320px"
                        >
                            <CardHeader class="px-3 pt-1 pb-0">
                                <CardTitle class="text-sm"
                                    >LOT REQUEST PER SIZE</CardTitle
                                >
                            </CardHeader>
                            <CardContent
                                class="-mt-4 flex flex-1 items-center justify-center p-0"
                            >
                                <div
                                    id="size-chart"
                                    class="w-full"
                                    style="min-height: 300px"
                                ></div>
                            </CardContent>
                        </Card>

                        <!-- Production Line Chart (70%) -->
                        <Card
                            class="col-span-7 flex flex-col overflow-hidden"
                            style="min-height: 320px"
                        >
                            <CardHeader class="px-3 pt-1 pb-0">
                                <CardTitle class="text-sm"
                                    >LOT REQUEST PER LINE</CardTitle
                                >
                            </CardHeader>
                            <CardContent class="-mt-4 p-0">
                                <div
                                    id="combined-chart"
                                    style="min-height: 300px"
                                ></div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Table with Fixed Header -->
            <Card class="flex min-h-0 flex-1 flex-col">
                <div
                    class="flex h-8 items-center justify-between border-b px-3"
                >
                    <span
                        class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >Raw Data</span
                    >
                    <div class="relative">
                        <input
                            v-model="tableSearch"
                            type="text"
                            placeholder="Search lot, MC, model..."
                            class="h-6 w-56 rounded-md border border-input bg-background pr-6 pl-7 text-xs placeholder:text-muted-foreground/50 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                        />
                        <svg
                            class="absolute top-1/2 left-2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground/50"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <button
                            v-if="tableSearch"
                            @click="tableSearch = ''"
                            class="absolute top-1/2 right-2 -translate-y-1/2 text-sm leading-none text-muted-foreground/50 hover:text-muted-foreground"
                        >
                            ×
                        </button>
                    </div>
                </div>
                <div class="min-h-0 flex-1 overflow-auto">
                    <table class="w-full min-w-[1400px] table-fixed text-xs">
                        <colgroup>
                            <col class="w-[80px]" />
                            <!-- MC No -->
                            <col class="w-[46px]" />
                            <!-- Area -->
                            <col class="w-[130px]" />
                            <!-- Requestor -->
                            <col class="w-[120px]" />
                            <!-- Req. Model -->
                            <col class="w-[100px]" />
                            <!-- Assigned Lot -->
                            <col class="w-[120px]" />
                            <!-- Assigned Model -->
                            <col class="w-[68px]" />
                            <!-- Qty -->
                            <col class="w-[62px]" />
                            <!-- LIPAS -->
                            <col class="w-[40px]" />
                            <!-- TAT -->
                            <col class="w-[82px]" />
                            <!-- Requested -->
                            <col class="w-[82px]" />
                            <!-- Completed -->
                            <col class="w-[72px]" />
                            <!-- Resp. Time -->
                            <col class="w-[72px]" />
                            <!-- MC Status -->
                            <col class="w-[100px]" />
                            <!-- MC Endtime -->
                            <col class="w-[72px]" />
                            <!-- Status -->
                            <col class="w-[72px]" />
                            <!-- Remarks -->
                            <col class="w-[140px]" />
                            <!-- Action -->
                        </colgroup>
                        <thead class="sticky top-0 z-10 border-b bg-muted">
                            <tr>
                                <th
                                    @click="handleSort('mc_no')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        MC No.<span
                                            v-if="sortColumn === 'mc_no'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    @click="handleSort('area')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        Area<span
                                            v-if="sortColumn === 'area'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    @click="handleSort('requestor')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        Requestor<span
                                            v-if="sortColumn === 'requestor'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    @click="handleSort('request_model')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        Req. Model<span
                                            v-if="
                                                sortColumn === 'request_model'
                                            "
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    @click="handleSort('lot_no')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        Assigned Lot<span
                                            v-if="sortColumn === 'lot_no'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    @click="handleSort('model')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        Assigned Model<span
                                            v-if="sortColumn === 'model'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    @click="handleSort('quantity')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        Qty<span
                                            v-if="sortColumn === 'quantity'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    @click="handleSort('lipas')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        LIPAS<span
                                            v-if="sortColumn === 'lipas'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    @click="handleSort('lot_tat')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        TAT<span
                                            v-if="sortColumn === 'lot_tat'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    @click="handleSort('requested')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        Requested<span
                                            v-if="sortColumn === 'requested'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    @click="handleSort('completed')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        Completed<span
                                            v-if="sortColumn === 'completed'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    @click="handleSort('response_time')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        Resp. Time<span
                                            v-if="
                                                sortColumn === 'response_time'
                                            "
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <!-- NEW: MC Status -->
                                <th
                                    @click="handleSort('mc_status')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        MC Status<span
                                            v-if="sortColumn === 'mc_status'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <!-- NEW: MC Endtime -->
                                <th
                                    @click="handleSort('mc_endtime')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        MC Endtime<span
                                            v-if="sortColumn === 'mc_endtime'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    @click="handleSort('status')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        Status<span
                                            v-if="sortColumn === 'status'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    @click="handleSort('remarks')"
                                    class="cursor-pointer bg-muted px-2 py-2 text-left text-[10px] font-semibold tracking-wider text-muted-foreground uppercase hover:bg-muted/80"
                                >
                                    <div class="flex items-center gap-0.5">
                                        Remarks<span
                                            v-if="sortColumn === 'remarks'"
                                            >{{
                                                sortDirection === 'asc'
                                                    ? '↑'
                                                    : '↓'
                                            }}</span
                                        >
                                    </div>
                                </th>
                                <th
                                    class="bg-muted px-2 py-2 text-center text-[10px] font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="request in filteredRequests"
                                :key="request.id"
                                class="transition-colors hover:bg-muted/30"
                                :class="{
                                    'border-l-4 border-l-[hsl(253_175_34%)]':
                                        request.status === 'PENDING',
                                    'border-l-4 border-l-[hsl(142_76%_36%)]':
                                        request.status === 'COMPLETED',
                                }"
                            >
                                <td class="px-2 py-1.5 text-xs">
                                    <span class="font-medium text-secondary">{{
                                        request.mc_no
                                    }}</span>
                                </td>
                                <td class="px-2 py-1.5 text-xs">
                                    <Badge
                                        variant="outline"
                                        class="px-1 py-0 text-[10px]"
                                        >{{ request.area }}</Badge
                                    >
                                </td>
                                <td
                                    class="truncate px-2 py-1.5 text-xs text-foreground"
                                    :title="request.requestor"
                                >
                                    {{ request.requestor }}
                                </td>
                                <td
                                    class="truncate px-2 py-1.5 text-xs"
                                    :title="request.request_model ?? ''"
                                >
                                    <span
                                        v-if="request.request_model"
                                        class="font-medium text-blue-600 dark:text-blue-400"
                                        >{{ request.request_model }}</span
                                    >
                                    <span v-else class="text-muted-foreground"
                                        >-</span
                                    >
                                </td>
                                <td
                                    class="truncate px-2 py-1.5 text-xs"
                                    :title="request.lot_no"
                                >
                                    <span
                                        v-if="request.lot_no"
                                        class="font-medium"
                                        >{{ request.lot_no }}</span
                                    >
                                    <span v-else class="text-muted-foreground"
                                        >-</span
                                    >
                                </td>
                                <td
                                    class="truncate px-2 py-1.5 text-xs"
                                    :title="request.model"
                                >
                                    <span
                                        v-if="request.model"
                                        class="font-medium text-green-600 dark:text-green-400"
                                        >{{ request.model }}</span
                                    >
                                    <span v-else class="text-muted-foreground"
                                        >-</span
                                    >
                                </td>
                                <td
                                    class="px-2 py-1.5 text-xs font-medium text-foreground"
                                >
                                    <span v-if="request.quantity">{{
                                        new Intl.NumberFormat().format(
                                            request.quantity,
                                        )
                                    }}</span>
                                    <span v-else class="text-muted-foreground"
                                        >-</span
                                    >
                                </td>
                                <td class="px-2 py-1.5">
                                    <Badge
                                        :class="getLipasColor(request.lipas)"
                                        class="px-1 py-0 text-[10px]"
                                        >{{
                                            getLipasLabel(request.lipas)
                                        }}</Badge
                                    >
                                </td>
                                <td
                                    class="px-2 py-1.5 text-xs text-muted-foreground"
                                >
                                    {{ request.lot_tat ?? '-' }}
                                </td>
                                <td
                                    class="px-2 py-1.5 text-xs whitespace-nowrap text-muted-foreground"
                                >
                                    {{ formatDateTime(request.requested) }}
                                </td>
                                <td
                                    class="px-2 py-1.5 text-xs whitespace-nowrap text-muted-foreground"
                                >
                                    <span v-if="request.completed">{{
                                        formatDateTime(request.completed)
                                    }}</span>
                                    <span v-else class="text-muted-foreground"
                                        >-</span
                                    >
                                </td>
                                <td
                                    class="px-2 py-1.5 text-xs font-medium whitespace-nowrap text-amber-600 dark:text-amber-400"
                                >
                                    {{ request.response_time ?? '-' }}
                                </td>
                                <!-- MC Status -->
                                <td class="px-2 py-1.5 text-xs">
                                    <span
                                        v-if="request.mc_status"
                                        class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-semibold"
                                        :class="
                                            request.mc_status === 'Running'
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                                        "
                                    >
                                        <span>{{
                                            request.mc_status === 'Running'
                                                ? '▶'
                                                : '⏸'
                                        }}</span>
                                        {{ request.mc_status }}
                                    </span>
                                    <span v-else class="text-muted-foreground"
                                        >-</span
                                    >
                                </td>
                                <!-- MC Endtime -->
                                <td
                                    class="px-2 py-1.5 text-xs whitespace-nowrap text-muted-foreground"
                                    :title="request.mc_endtime ?? ''"
                                >
                                    <span v-if="request.mc_endtime">{{
                                        formatDateTime(request.mc_endtime)
                                    }}</span>
                                    <span v-else class="text-muted-foreground"
                                        >-</span
                                    >
                                </td>
                                <td class="px-2 py-1.5">
                                    <Badge
                                        :class="getStatusColor(request.status)"
                                        class="px-1.5 py-0 text-[10px]"
                                        >{{ request.status }}</Badge
                                    >
                                </td>
                                <!-- Remarks -->
                                <td
                                    class="max-w-[140px] px-2 py-1.5 text-xs text-muted-foreground"
                                >
                                    <span
                                        v-if="request.remarks"
                                        :title="request.remarks"
                                        class="line-clamp-2"
                                        >{{ request.remarks }}</span
                                    >
                                    <span v-else>-</span>
                                </td>
                                <td class="px-2 py-1.5">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <Button
                                            @click="handleAccept(request)"
                                            size="sm"
                                            :disabled="
                                                request.status === 'COMPLETED'
                                            "
                                            class="h-6 bg-emerald-600 px-2 text-[10px] text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-emerald-700 dark:hover:bg-emerald-800"
                                        >
                                            Accept
                                        </Button>
                                        <Button
                                            @click="handleView(request)"
                                            size="sm"
                                            variant="outline"
                                            class="h-6 px-2 text-[10px]"
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
            <div
                v-if="filteredRequests.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed bg-card/30 py-16"
            >
                <div class="mb-4 animate-pulse text-8xl">📦</div>
                <h3 class="text-2xl font-bold text-muted-foreground">
                    No requests found
                </h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    Try adjusting your filters to see more results
                </p>
            </div>
        </div>

        <!-- Assign Lot Modal -->
        <MemsAssignLotModal
            :open="showAssignLotModal"
            :equipment-no="selectedRequestForAssignment?.mc_no ?? null"
            :equipment-line="selectedRequestForAssignment?.line ?? null"
            :equipment-area="selectedRequestForAssignment?.area ?? null"
            :previous-model="
                selectedRequestForAssignment?.request_model ?? null
            "
            :previous-worktype="null"
            :ongoing-lot="
                (selectedRequestForAssignment as any)?.ongoing_lot ?? null
            "
            :est-endtime="
                (selectedRequestForAssignment as any)?.est_endtime ?? null
            "
            :waiting-time="
                (selectedRequestForAssignment as any)?.waiting_time ?? null
            "
            :request-id="selectedRequestForAssignment?.id ?? null"
            :mc-rack="(selectedRequestForAssignment as any)?.mc_rack ?? null"
            @update:open="showAssignLotModal = $event"
            @assign="handleAssignLotConfirm"
        />

        <!-- View Lot Request Modal -->
        <LotRequestViewModal
            :open="showViewModal"
            :request="selectedRequestForView as any"
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
