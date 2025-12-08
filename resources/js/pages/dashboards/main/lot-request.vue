<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ApexCharts from 'apexcharts';

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
    id: string;
    requestNo: string;
    requestor: string;
    size: string;
    model: string;
    machineNo: string;
    area: string;
    priority: 'FIFO' | 'LIPAS' | 'URGENT' | 'NORMAL';
    status: 'PENDING' | 'COMPLETED';
    requestedAt: string;
    elapsedTime?: string;
    completedAt?: string;
    notes?: string;
}

// Hardcoded data
const lotRequests = ref<LotRequest[]>([
    {
        id: 'REQ-001',
        requestNo: 'REQ-001',
        requestor: 'John Smith',
        size: '25',
        model: 'Model A',
        machineNo: 'EQP-A101',
        area: 'A1',
        priority: 'URGENT',
        status: 'PENDING',
        requestedAt: '2025-12-06 08:30',
        elapsedTime: '2h 15m',
        notes: 'High priority customer order'
    },
    {
        id: 'REQ-002',
        requestNo: 'REQ-002',
        requestor: 'Maria Garcia',
        size: '50',
        model: 'Model B',
        machineNo: 'EQP-A102',
        area: 'A2',
        priority: 'FIFO',
        status: 'COMPLETED',
        requestedAt: '2025-12-06 07:15',
        elapsedTime: '2h 30m',
        completedAt: '2025-12-06 09:45',
        notes: 'Standard processing'
    },
    {
        id: 'REQ-003',
        requestNo: 'REQ-003',
        requestor: 'David Chen',
        size: '30',
        model: 'Model C',
        machineNo: 'EQP-B205',
        area: 'A3',
        priority: 'LIPAS',
        status: 'COMPLETED',
        requestedAt: '2025-12-05 14:20',
        elapsedTime: '19h 55m',
        completedAt: '2025-12-06 10:15',
        notes: 'Completed ahead of schedule'
    },
    {
        id: 'REQ-004',
        requestNo: 'REQ-004',
        requestor: 'Sarah Johnson',
        size: '40',
        model: 'Model A',
        machineNo: 'EQP-C310',
        area: 'A1',
        priority: 'NORMAL',
        status: 'PENDING',
        requestedAt: '2025-12-06 06:00',
        elapsedTime: '4h 45m',
        notes: 'Regular order'
    },
    {
        id: 'REQ-005',
        requestNo: 'REQ-005',
        requestor: 'Michael Brown',
        size: '35',
        model: 'Model D',
        machineNo: 'EQP-A103',
        area: 'A2',
        priority: 'URGENT',
        status: 'PENDING',
        requestedAt: '2025-12-06 09:00',
        elapsedTime: '1h 45m',
        notes: 'Expedite processing'
    },
    {
        id: 'REQ-006',
        requestNo: 'REQ-006',
        requestor: 'Emily Davis',
        size: '45',
        model: 'Model B',
        machineNo: 'EQP-A104',
        area: 'A3',
        priority: 'FIFO',
        status: 'COMPLETED',
        requestedAt: '2025-12-05 10:30',
        elapsedTime: '21h 15m',
        completedAt: '2025-12-06 07:45',
        notes: 'Batch processing'
    },
]);

const statusFilter = ref<string>('ALL');

const filteredRequests = computed(() => {
    if (statusFilter.value === 'ALL') return lotRequests.value;
    return lotRequests.value.filter(req => req.status === statusFilter.value);
});

const stats = computed(() => {
    const total = lotRequests.value.length;
    const pending = lotRequests.value.filter(r => r.status === 'PENDING').length;
    const completed = lotRequests.value.filter(r => r.status === 'COMPLETED').length;
    const completionRate = total > 0 ? parseFloat(((completed / total) * 100).toFixed(1)) : 0;
    
    // Calculate average completion time (in hours)
    const completedRequests = lotRequests.value.filter(r => r.status === 'COMPLETED' && r.requestedAt && r.completedAt);
    let avgCompletionTime = 0;
    if (completedRequests.length > 0) {
        const totalHours = completedRequests.reduce((sum, req) => {
            const requested = new Date(req.requestedAt);
            const completed = new Date(req.completedAt!);
            const hours = (completed.getTime() - requested.getTime()) / (1000 * 60 * 60);
            return sum + hours;
        }, 0);
        avgCompletionTime = parseFloat((totalHours / completedRequests.length).toFixed(1));
    }
    
    return {
        total,
        pending,
        completed,
        completionRate,
        avgCompletionTime
    };
});

const getPriorityColor = (priority: string) => {
    const colors = {
        URGENT: 'bg-destructive text-destructive-foreground',
        LIPAS: 'bg-secondary text-secondary-foreground',
        FIFO: 'bg-primary text-primary-foreground',
        NORMAL: 'bg-muted text-muted-foreground'
    };
    return colors[priority as keyof typeof colors] || colors.NORMAL;
};

const getStatusColor = (status: string) => {
    const colors = {
        PENDING: 'bg-[hsl(253_175_34%)] text-white',
        COMPLETED: 'bg-[hsl(142_76%_36%)] text-white'
    };
    return colors[status as keyof typeof colors];
};

const handleAccept = (requestId: string) => {
    console.log('Accept request:', requestId);
    // Add your accept logic here
};

const handleDelete = (requestId: string) => {
    console.log('Delete request:', requestId);
    // Add your delete logic here
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
    labels: ['Total', 'Pending', 'Completed'],
    colors: ['#985FFD', '#FDAF22', '#32D484'],
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
                            return val + '%'
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
        parseFloat(((stats.value.total / total) * 100).toFixed(1)),
        parseFloat(((stats.value.pending / total) * 100).toFixed(1)),
        parseFloat(((stats.value.completed / total) * 100).toFixed(1))
    ];
});

// Combined Bar and Line Chart Configuration
const combinedChartOptions = computed(() => ({
    chart: {
        type: 'line',
        height: 260,
        toolbar: {
            show: false
        }
    },
    grid: {
        borderColor: '#f1f1f1',
        strokeDashArray: 3,
        padding: {
            bottom: -10,
        },
    },
    stroke: {
        width: [1, 1.1],
        curve: ['straight', 'smooth'],
        dashArray: [0, 2]
    },
    plotOptions: {
        bar: {
            columnWidth: '30%',
            borderRadius: 2
        }
    },
    colors: ['#985FFD', '#FF49CD'],
    labels: ['Line A', 'Line B', 'Line C', 'Line D', 'Line E', 'Line F', 'Line G', 'Line H', 'Line I', 'Line J', 'Line K'],
    xaxis: {
        axisBorder: {
            color: '#e9e9e9',
        },
    },
    legend: {
        show: false,
    },
    dataLabels: {
        enabled: false
    },
}));

// Sample data for categories A-K
const categoryData = computed(() => {
    const categories = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
    return categories.map(cat => {
        // Generate sample data based on category
        const pending = Math.floor(Math.random() * 15) + 5;
        const completed = Math.floor(Math.random() * 20) + 10;
        return {
            category: cat,
            pending,
            completed,
            total: pending + completed
        };
    });
});

const combinedChartSeries = computed(() => [
    {
        name: 'Request Count (Pending)',
        type: 'column',
        data: categoryData.value.map(d => d.pending)
    },
    {
        name: 'Total Request Lots',
        type: 'line',
        data: categoryData.value.map(d => d.total)
    }
]);

// Chart instances
let donutChart: ApexCharts | null = null;
let combinedChart: ApexCharts | null = null;

// Initialize charts
onMounted(() => {
    // Donut Chart
    const donutElement = document.querySelector('#donut-chart');
    if (donutElement) {
        donutChart = new ApexCharts(donutElement, {
            ...donutChartOptions.value,
            series: donutChartSeries.value
        });
        donutChart.render();
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
});

// Update charts when data changes
watch([donutChartSeries, combinedChartSeries], () => {
    if (donutChart) {
        donutChart.updateSeries(donutChartSeries.value);
    }
    if (combinedChart) {
        combinedChart.updateSeries(combinedChartSeries.value);
    }
});
</script>

<template>
    <Head title="Lot Request Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #filters>
            <div class="flex items-center gap-2 flex-wrap">
                <Button 
                    @click="statusFilter = 'ALL'"
                    :variant="statusFilter === 'ALL' ? 'default' : 'outline'"
                    size="sm"
                    class="transition-all"
                >
                    All
                </Button>
                <Button 
                    @click="statusFilter = 'PENDING'"
                    :variant="statusFilter === 'PENDING' ? 'default' : 'outline'"
                    size="sm"
                    class="transition-all"
                >
                    Pending
                </Button>
                <Button 
                    @click="statusFilter = 'COMPLETED'"
                    :variant="statusFilter === 'COMPLETED' ? 'default' : 'outline'"
                    size="sm"
                    class="transition-all"
                >
                    Completed
                </Button>
            </div>
        </template>

        <div class="flex h-full flex-1 flex-col gap-3 overflow-auto p-4 bg-gradient-to-br from-background via-background to-muted/10">
            <!-- Top Section: Donut Chart Left, Stats + Bar Chart Right -->
            <div class="grid gap-3 md:grid-cols-3">
                <!-- Donut Chart (Left) -->
                <Card class="overflow-hidden flex flex-col">
                    <CardHeader class="pb-0 pt-2 px-3">
                        <CardTitle class="text-sm">Lot Request Overview</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0 flex-1 flex items-center justify-center">
                        <div id="donut-chart" class="w-full"></div>
                    </CardContent>
                    <div class="border-t">
                        <div class="grid grid-cols-3">
                            <div class="py-2 px-2 text-center border-r">
                                <h5 class="text-lg font-semibold mb-0">{{ stats.total }}</h5>
                                <span class="text-xs block text-muted-foreground">Total</span>
                            </div>
                            <div class="py-2 px-2 text-center border-r">
                                <h5 class="text-lg font-semibold mb-0">{{ stats.pending }}</h5>
                                <span class="text-xs block text-muted-foreground">Pending</span>
                            </div>
                            <div class="py-2 px-2 text-center">
                                <h5 class="text-lg font-semibold mb-0">{{ stats.completed }}</h5>
                                <span class="text-xs block text-muted-foreground">Completed</span>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Right Side: Stats Cards + Bar Chart -->
                <div class="md:col-span-2 flex flex-col gap-3">
                    <!-- Stats Cards Row -->
                    <div class="grid gap-2 grid-cols-5">
                        <div class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-purple-50 to-purple-100/50 p-2 shadow-sm transition-all duration-300 hover:shadow-lg hover:shadow-purple-500/10 dark:from-purple-950/30 dark:to-purple-900/20">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-purple-500/10 p-1.5 ring-1 ring-purple-500/20">
                                    <span class="text-sm">📊</span>
                                </div>
                                <div>
                                    <p class="text-[10px] font-medium text-purple-700 dark:text-purple-300">Total Request</p>
                                    <p class="text-lg font-bold text-purple-900 dark:text-purple-100">{{ stats.total }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-amber-50 to-amber-100/50 p-2 shadow-sm transition-all duration-300 hover:shadow-lg hover:shadow-amber-500/10 dark:from-amber-950/30 dark:to-amber-900/20">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-amber-500/10 p-1.5 ring-1 ring-amber-500/20">
                                    <span class="text-sm">⏳</span>
                                </div>
                                <div>
                                    <p class="text-[10px] font-medium text-amber-700 dark:text-amber-300">Pending</p>
                                    <p class="text-lg font-bold text-amber-900 dark:text-amber-100">{{ stats.pending }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-2 shadow-sm transition-all duration-300 hover:shadow-lg hover:shadow-emerald-500/10 dark:from-emerald-950/30 dark:to-emerald-900/20">
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

                        <div class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-pink-50 to-pink-100/50 p-2 shadow-sm transition-all duration-300 hover:shadow-lg hover:shadow-pink-500/10 dark:from-pink-950/30 dark:to-pink-900/20">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-pink-500/10 p-1.5 ring-1 ring-pink-500/20">
                                    <span class="text-sm">📈</span>
                                </div>
                                <div>
                                    <p class="text-[10px] font-medium text-pink-700 dark:text-pink-300">Completion %</p>
                                    <p class="text-lg font-bold text-pink-900 dark:text-pink-100">{{ stats.completionRate }}%</p>
                                </div>
                            </div>
                        </div>

                        <div class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-blue-50 to-blue-100/50 p-2 shadow-sm transition-all duration-300 hover:shadow-lg hover:shadow-blue-500/10 dark:from-blue-950/30 dark:to-blue-900/20">
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-blue-500/10 p-1.5 ring-1 ring-blue-500/20">
                                    <span class="text-sm">⏱️</span>
                                </div>
                                <div>
                                    <p class="text-[10px] font-medium text-blue-700 dark:text-blue-300">Avg. Time</p>
                                    <p class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ stats.avgCompletionTime }}h</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bar Chart -->
                    <Card class="overflow-hidden flex-1">
                        <CardHeader class="pb-0 pt-2 px-3">
                            <CardTitle class="text-sm">Requests by Production Line</CardTitle>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div id="combined-chart"></div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Table with Fixed Header -->
            <Card class="overflow-hidden flex flex-col">
                <div class="overflow-auto max-h-[650px]">
                    <table class="w-full">
                        <thead class="bg-muted/80 border-b sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted/50">Request No.</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted/50">Requestor</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted/50">Size</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted/50">Model</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted/50">Machine No.</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted/50">Area</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted/50">Requested</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted/50">Priority</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted/50">Notes</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted/50">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted/50">Completed</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-muted-foreground uppercase tracking-wider bg-muted/50">Action</th>
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
                                <td class="px-4 py-3 text-sm font-semibold text-foreground">{{ request.requestNo }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ request.requestor }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-foreground">{{ request.size }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">{{ request.model }}</td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    <span class="font-medium text-secondary">{{ request.machineNo }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground">
                                    <Badge variant="outline" class="text-xs">{{ request.area }}</Badge>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    <div class="whitespace-nowrap">{{ request.requestedAt }}</div>
                                    <div v-if="request.elapsedTime" class="text-[10px] text-amber-600 dark:text-amber-400 font-medium">{{ request.elapsedTime }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :class="getPriorityColor(request.priority)" class="text-xs">
                                        {{ request.priority }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground max-w-[200px]">
                                    <span v-if="request.notes" :title="request.notes" class="line-clamp-2">{{ request.notes }}</span>
                                    <span v-else>-</span>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :class="getStatusColor(request.status)" class="text-xs">
                                        {{ request.status }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">
                                    <span v-if="request.completedAt">{{ request.completedAt }}</span>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button 
                                            @click="handleAccept(request.id)"
                                            size="sm"
                                            variant="default"
                                            :disabled="request.status === 'COMPLETED'"
                                            class="h-7 px-3 text-xs"
                                        >
                                            Accept
                                        </Button>
                                        <Button 
                                            @click="handleDelete(request.id)"
                                            size="sm"
                                            variant="destructive"
                                            class="h-7 px-3 text-xs"
                                        >
                                            Delete
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
    </AppLayout>
</template>
