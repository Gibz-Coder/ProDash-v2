<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import EndtimeAddModal from './endtime-add-modal.vue';
import EndtimeSubmitModal from './endtime-submit-modal.vue';
import EndtimeWipupdateModal from './endtime-wipupdate-modal.vue';

// ============================================================================
// PROPS
// ============================================================================
const props = defineProps<{
    lots: Array<{
        id: number;
        lot_id: string;
        model_15: string;
        lot_qty: number;
        lot_size: string;
        lipas_yn: string;
        est_endtime: string;
        actual_submitted_at: string | null;
        work_type: string;
        lot_type: string;
        eqp_line: string;
        eqp_area: string;
        eqp_1: string | null;
        eqp_2: string | null;
        eqp_3: string | null;
        eqp_4: string | null;
        eqp_5: string | null;
        eqp_6: string | null;
        eqp_7: string | null;
        eqp_8: string | null;
        eqp_9: string | null;
        eqp_10: string | null;
        status: string;
        created_at: string;
    }>;
    filters: {
        search: string;
        line: string;
        area: string;
        worktype: string;
        status: string;
        date: string;
        shift: string;
        cutoff: string;
    };
    stats: {
        total: number;
        ongoing: number;
        submitted: number;
        mainLots: number;
        wlRework: number;
        rlRework: number;
    };
    badgeStats: {
        targetQty: number;
        endtimeQty: number;
        submittedQty: number;
        achievement: number;
    };
    areaOptions: string[];
}>();

// Get auth from shared Inertia page props
const page = usePage();
const auth = computed(() => page.props.auth as { user: any; permissions: string[] });

// ============================================================================
// BREADCRUMBS
// ============================================================================
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: dashboard.url() },
    { title: 'ENDTIME', href: '/endtime' },
    { title: 'LIST', href: '/endtime-add' },
];

// ============================================================================
// CONSTANTS
// ============================================================================
const EQUIPMENT_LINES = ['ALL', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'] as const;
const MILLISECONDS_PER_DAY = 24 * 60 * 60 * 1000;

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================
/**
 * Format date in local timezone (YYYY-MM-DD)
 */
const formatLocalDate = (date: Date): string => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

/**
 * Get current shift, cutoff, and date based on current time
 * Production Date Logic:
 * - DAY shift: Production date = same calendar date (07:00-18:59)
 * - NIGHT shift: Production date = date when shift STARTS
 *   - NIGHT 1ST (19:00-23:59): Production date = current day
 *   - NIGHT 2ND (00:00-03:59): Production date = previous day (shift started yesterday)
 *   - NIGHT 3RD (04:00-06:59): Production date = previous day (shift started yesterday)
 */
const getCurrentShiftCutoffAndDate = () => {
    const now = new Date();
    const hour = now.getHours();
    
    type Shift = 'DAY' | 'NIGHT';
    type Cutoff = '1ST' | '2ND' | '3RD';
    
    let shift: Shift = 'DAY';
    let cutoff: Cutoff = '1ST';
    let date = now;
    
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
        date = new Date(now.getTime() - MILLISECONDS_PER_DAY);
    } else { // 4-6:59
        // NIGHT 3RD: 04:00-06:59, production date = previous day (shift started yesterday)
        shift = 'NIGHT';
        cutoff = '3RD';
        date = new Date(now.getTime() - MILLISECONDS_PER_DAY);
    }
    
    return { shift, cutoff, date: formatLocalDate(date) };
};

// ============================================================================
// STATE
// ============================================================================
const defaults = getCurrentShiftCutoffAndDate();

const currentFilters = ref({
    search: props.filters.search || '',
    line: props.filters.line || 'ALL',
    area: props.filters.area || 'ALL',
    worktype: props.filters.worktype || 'ALL',
    status: props.filters.status || 'Ongoing',
    date: props.filters.date || defaults.date,
    shift: props.filters.shift || defaults.shift,
    cutoff: props.filters.cutoff || defaults.cutoff,
});

// Sort state
type SortField = 'lot_id' | 'model_15' | 'lot_qty' | 'lot_size' | 'eqp_line' | 'eqp_area' | 'work_type' | 'est_endtime' | 'actual_submitted_at' | 'status' | 'created_at';
type SortOrder = 'asc' | 'desc';
const sortField = ref<SortField>('created_at');
const sortOrder = ref<SortOrder>('desc');

// ============================================================================
// COMPUTED
// ============================================================================
// Computed date range description for tooltip
// Production Date Logic:
// - DAY shift: Production date = same calendar date (07:00-18:59)
// - NIGHT shift: Production date = date when shift STARTS
//   Example: Production Date 12/01 + NIGHT → 12/01 19:00 - 12/02 06:59
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

const sortedLots = computed(() => {
    const lotsCopy = [...props.lots];
    
    return lotsCopy.sort((a, b) => {
        let aVal: any = a[sortField.value];
        let bVal: any = b[sortField.value];
        
        // Handle null values
        if (aVal === null || aVal === undefined) return sortOrder.value === 'asc' ? 1 : -1;
        if (bVal === null || bVal === undefined) return sortOrder.value === 'asc' ? -1 : 1;
        
        // Handle date comparisons
        if (sortField.value === 'est_endtime' || sortField.value === 'actual_submitted_at' || sortField.value === 'created_at') {
            aVal = new Date(aVal).getTime();
            bVal = new Date(bVal).getTime();
        }
        
        // Handle numeric comparisons
        if (sortField.value === 'lot_qty') {
            aVal = Number(aVal);
            bVal = Number(bVal);
        }
        
        // Handle string comparisons (case-insensitive)
        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }
        
        if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const stats = computed(() => [
    {
        title: 'Total Lots',
        value: props.stats.total.toLocaleString(),
        icon: '📦',
        color: 'bg-gradient-to-br from-purple-50 to-purple-100/50 dark:from-purple-950/30 dark:to-purple-900/20',
        textColor: 'text-purple-700 dark:text-purple-300',
        valueColor: 'text-purple-900 dark:text-purple-100',
        iconBg: 'bg-purple-500/10 ring-purple-500/20',
    },
    {
        title: 'Ongoing',
        value: props.stats.ongoing.toLocaleString(),
        icon: '⏳',
        color: 'bg-gradient-to-br from-orange-50 to-orange-100/50 dark:from-orange-950/30 dark:to-orange-900/20',
        textColor: 'text-orange-700 dark:text-orange-300',
        valueColor: 'text-orange-900 dark:text-orange-100',
        iconBg: 'bg-orange-500/10 ring-orange-500/20',
    },
    {
        title: 'Submitted',
        value: props.stats.submitted.toLocaleString(),
        icon: '✅',
        color: 'bg-gradient-to-br from-emerald-50 to-emerald-100/50 dark:from-emerald-950/30 dark:to-emerald-900/20',
        textColor: 'text-emerald-700 dark:text-emerald-300',
        valueColor: 'text-emerald-900 dark:text-emerald-100',
        iconBg: 'bg-emerald-500/10 ring-emerald-500/20',
    },
    {
        title: 'Main Lot',
        value: props.stats.mainLots.toLocaleString(),
        icon: '⏺️',
        color: 'bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-950/30 dark:to-blue-900/20',
        textColor: 'text-blue-700 dark:text-blue-300',
        valueColor: 'text-blue-900 dark:text-blue-100',
        iconBg: 'bg-blue-500/10 ring-blue-500/20',
    },
    {
        title: 'WL Rework',
        value: props.stats.wlRework.toLocaleString(),
        icon: '♻️',
        color: 'bg-gradient-to-br from-amber-50 to-amber-100/50 dark:from-amber-950/30 dark:to-amber-900/20',
        textColor: 'text-amber-700 dark:text-amber-300',
        valueColor: 'text-amber-900 dark:text-amber-100',
        iconBg: 'bg-amber-500/10 ring-amber-500/20',
    },
    {
        title: 'RL Rework',
        value: props.stats.rlRework.toLocaleString(),
        icon: '🧩',
        color: 'bg-gradient-to-br from-cyan-50 to-cyan-100/50 dark:from-cyan-950/30 dark:to-cyan-900/20',
        textColor: 'text-cyan-700 dark:text-cyan-300',
        valueColor: 'text-cyan-900 dark:text-cyan-100',
        iconBg: 'bg-cyan-500/10 ring-cyan-500/20',
    },
]);

// ============================================================================
// METHODS
// ============================================================================
const handleFilterChange = () => {
    router.get('/endtime-add', currentFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

/**
 * Reset all filters to current shift/cutoff defaults
 */
const handleReset = () => {
    const { shift, cutoff, date } = getCurrentShiftCutoffAndDate();
    
    currentFilters.value = {
        search: '',
        line: 'ALL',
        area: 'ALL',
        worktype: 'ALL',
        status: 'Ongoing',
        date,
        shift,
        cutoff,
    };
    
    handleFilterChange();
};

const getStatusBadgeClass = (status: string) => {
    if (status === 'Submitted') {
        return 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
    }
    return 'bg-orange-500/20 text-orange-700 dark:text-orange-300 border-orange-500/30';
};

const getSizeDisplay = (size: string) => {
    const sizeMap: Record<string, string> = {
        '03': '0603',
        '05': '1005',
        '10': '1608',
        '21': '2012',
        '31': '3216',
        '32': '3225',
    };
    return sizeMap[size] || size;
};

const getLipasBadgeClass = (lipas: string) => {
    if (lipas === 'Y') {
        return 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
    }
    return 'bg-red-500/20 text-red-700 dark:text-red-300 border-red-500/30';
};

/**
 * Format number to millions with M PCS suffix
 */
const formatMillions = (value: number): string => {
    const millions = value / 1000000;
    return millions.toFixed(1) + ' M';
};

const formatDateTime = (datetime: string) => {
    const date = new Date(datetime);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const isEndtimeLapsed = (datetime: string) => {
    const endtime = new Date(datetime);
    const now = new Date();
    return endtime < now;
};

const getEndtimeColorClass = (datetime: string) => {
    return isEndtimeLapsed(datetime) 
        ? 'text-red-600 dark:text-red-400 font-semibold' 
        : 'text-emerald-600 dark:text-emerald-400';
};

const formatQuantity = (qty: number) => {
    return qty.toLocaleString();
};

/**
 * Get comma-separated list of machines assigned to a lot
 */
const getMachineList = (lot: typeof props.lots[0]): string => {
    const machines = Array.from({ length: 10 }, (_, i) => {
        const key = `eqp_${i + 1}` as keyof typeof lot;
        return lot[key];
    }).filter((eqp): eqp is string => Boolean(eqp));
    
    return machines.length > 0 ? machines.join(', ') : '-';
};

// Permission-based access control
const userPermissions = computed(() => auth.value?.permissions || []);
const hasPermission = (permission: string) => userPermissions.value.includes(permission);
const canManage = computed(() => hasPermission('Endtime Manage'));
const canDelete = computed(() => hasPermission('Endtime Delete'));

const isSearchActive = computed(() => {
    return currentFilters.value.search && currentFilters.value.search.trim() !== '';
});

/**
 * Handle delete lot with confirmation
 */
const handleDelete = (lotId: number, lotName: string) => {
    if (confirm(`Are you sure you want to delete lot "${lotName}"? This action cannot be undone.`)) {
        router.delete(`/endtime/${lotId}`, {
            preserveScroll: true,
            onSuccess: () => {
                // Success message will be shown by the backend
            },
            onError: () => {
                alert('Failed to delete lot. Please try again.');
            }
        });
    }
};

/**
 * Handle edit lot - open modal with lot data
 */
const handleEdit = (lot: typeof props.lots[0]) => {
    editingLot.value = lot;
    isEditModalOpen.value = true;
};

// ============================================================================
// MODAL STATE
// ============================================================================
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isSubmitModalOpen = ref(false);
const isExportModalOpen = ref(false);
const isWipUpdateModalOpen = ref(false);
const editingLot = ref<typeof props.lots[0] | null>(null);

/**
 * Handle submit lot action
 */
const handleSubmitLot = () => {
    isSubmitModalOpen.value = true;
};

/**
 * Handle WIP update action
 */
const handleWipUpdate = () => {
    isWipUpdateModalOpen.value = true;
};

/**
 * Handle column sort
 */
const handleSort = (field: SortField) => {
    if (sortField.value === field) {
        // Toggle sort order if clicking the same field
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        // Set new field and default to ascending
        sortField.value = field;
        sortOrder.value = 'asc';
    }
};

/**
 * Get sort icon for column header
 */
const getSortIcon = (field: SortField): string => {
    if (sortField.value !== field) return '↕️';
    return sortOrder.value === 'asc' ? '↑' : '↓';
};

// ============================================================================
// EXPORT FUNCTIONALITY
// ============================================================================
const exportFilters = ref({
    dateFrom: defaults.date,
    dateTo: defaults.date,
    shift: 'ALL',
    cutoff: 'ALL',
});

const isExporting = ref(false);

/**
 * Handle export data to CSV
 */
const handleExport = async () => {
    // Validate date range
    if (exportFilters.value.dateFrom > exportFilters.value.dateTo) {
        alert('Start date cannot be after end date.');
        return;
    }
    
    isExporting.value = true;
    
    try {
        const params = new URLSearchParams({
            dateFrom: exportFilters.value.dateFrom,
            dateTo: exportFilters.value.dateTo,
            shift: exportFilters.value.shift,
            cutoff: exportFilters.value.cutoff,
        });
        
        const response = await fetch(`/endtime/export?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        
        if (!response.ok) {
            throw new Error('Export failed');
        }
        
        const data = await response.json();
        
        // Helper function to extract date, shift, and cutoff from datetime
        const extractDateShiftCutoff = (datetime: string) => {
            const date = new Date(datetime);
            const hour = date.getHours(); // This gets local hour
            
            // Format date as YYYY-MM-DD in local timezone
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const dateStr = `${year}-${month}-${day}`;
            
            // Determine shift and cutoff based on hour
            let shift = 'DAY';
            let cutoff = '1ST';
            
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
                shift = 'NIGHT';
                cutoff = '1ST';
            } else if (hour >= 0 && hour < 4) {
                shift = 'NIGHT';
                cutoff = '2ND';
            } else { // 4-6:59
                shift = 'NIGHT';
                cutoff = '3RD';
            }
            
            return { date: dateStr, shift, cutoff };
        };
        
        // Prepare CSV headers
        const headers = [
            'Lot ID',
            'Model',
            'Quantity',
            'Size',
            'Lipas',
            'Line',
            'Area',
            'Machine',
            'Work Type',
            'Lot Type',
            'Date',
            'Shift',
            'Cutoff',
            'Est. Endtime',
            'Submitted Time',
            'Status',
            'Modified By',
            'Created At'
        ];
        
        // Prepare CSV rows
        const rows: string[][] = data.lots.map((lot: any) => {
            const machines = Array.from({ length: 10 }, (_, i) => lot[`eqp_${i + 1}`])
                .filter(Boolean)
                .join(', ') || '-';
            
            // Use backend computed values if available, otherwise extract from datetime
            let date = lot.computed_date || '-';
            let shift = lot.computed_shift || '-';
            let cutoff = lot.computed_cutoff || '-';
            
            // Fallback: Extract date, shift, cutoff based on status if backend didn't compute
            if (date === '-' || !lot.computed_date) {
                if (lot.status === 'Ongoing' && lot.est_endtime) {
                    // For Ongoing lots, use est_endtime
                    const extracted = extractDateShiftCutoff(lot.est_endtime);
                    date = extracted.date;
                    shift = extracted.shift;
                    cutoff = extracted.cutoff;
                } else if (lot.status === 'Submitted' && lot.actual_submitted_at) {
                    // For Submitted lots, use actual_submitted_at
                    const extracted = extractDateShiftCutoff(lot.actual_submitted_at);
                    date = extracted.date;
                    shift = extracted.shift;
                    cutoff = extracted.cutoff;
                }
            }
            
            return [
                lot.lot_id,
                lot.model_15 || '-',
                lot.lot_qty.toString(),
                getSizeDisplay(lot.lot_size),
                lot.lipas_yn,
                lot.eqp_line,
                lot.eqp_area,
                machines,
                lot.work_type,
                lot.lot_type,
                date,
                shift,
                cutoff,
                formatDateTime(lot.est_endtime),
                lot.actual_submitted_at ? formatDateTime(lot.actual_submitted_at) : '-',
                lot.status,
                lot.modified_by || '-',
                formatDateTime(lot.created_at)
            ];
        });
        
        // Create CSV content
        const csvContent = [
            headers.join(','),
            ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
        ].join('\n');
        
        // Create blob and download
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        const filename = `endtime-export-${exportFilters.value.dateFrom}-to-${exportFilters.value.dateTo}-${exportFilters.value.shift}-${exportFilters.value.cutoff}.csv`;
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Close modal
        isExportModalOpen.value = false;
    } catch (error) {
        alert('Failed to export data. Please try again.');
    } finally {
        isExporting.value = false;
    }
};
</script>

<template>
    <Head title="Endtime Raw" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- ================================================================ -->
        <!-- FILTERS SECTION -->
        <!-- ================================================================ -->
        <template #filters>
            <div class="flex items-center gap-3">
                <!-- Production Date Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm group relative" :class="{ 'opacity-50': isSearchActive }">
                    <span class="text-xs font-medium text-muted-foreground">DATE:</span>
                    <input 
                        type="date" 
                        v-model="currentFilters.date"
                        @change="handleFilterChange"
                        :disabled="isSearchActive ? true : undefined"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 cursor-pointer disabled:cursor-not-allowed"
                    />
                    <!-- Tooltip showing actual datetime range -->
                    <div class="absolute left-0 top-full mt-1 hidden group-hover:block z-50 w-64 p-2 bg-popover border border-border rounded-lg shadow-lg text-xs">
                        <div class="font-semibold text-foreground mb-1">📅 Query Range:</div>
                        <div class="text-muted-foreground">{{ getDateRangeDescription }}</div>
                    </div>
                </div>

                <!-- Shift Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm" :class="{ 'opacity-50': isSearchActive }">
                    <span class="text-xs font-medium text-muted-foreground">SHIFT:</span>
                    <select 
                        v-model="currentFilters.shift"
                        @change="handleFilterChange"
                        :disabled="isSearchActive ? true : undefined"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 cursor-pointer pr-6 [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100 disabled:cursor-not-allowed"
                    >
                        <option>ALL</option>
                        <option>DAY</option>
                        <option>NIGHT</option>
                    </select>
                </div>

                <!-- Cutoff Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm" :class="{ 'opacity-50': isSearchActive }">
                    <span class="text-xs font-medium text-muted-foreground">CUTOFF:</span>
                    <select 
                        v-model="currentFilters.cutoff"
                        @change="handleFilterChange"
                        :disabled="isSearchActive ? true : undefined"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 cursor-pointer pr-6 [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100 disabled:cursor-not-allowed"
                    >
                        <option>ALL</option>
                        <option>1ST</option>
                        <option>2ND</option>
                        <option>3RD</option>
                    </select>
                </div>

                <!-- Line Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm" :class="{ 'opacity-50': isSearchActive }">
                    <span class="text-xs font-medium text-muted-foreground">LINE:</span>
                    <select 
                        v-model="currentFilters.line"
                        @change="handleFilterChange"
                        :disabled="isSearchActive ? true : undefined"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 cursor-pointer pr-6 [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100 disabled:cursor-not-allowed"
                    >
                        <option v-for="line in EQUIPMENT_LINES" :key="line" :value="line">{{ line }}</option>
                    </select>
                </div>

                <!-- Area Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm" :class="{ 'opacity-50': isSearchActive }">
                    <span class="text-xs font-medium text-muted-foreground">AREA:</span>
                    <select 
                        v-model="currentFilters.area"
                        @change="handleFilterChange"
                        :disabled="isSearchActive ? true : undefined"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 cursor-pointer pr-6 [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100 disabled:cursor-not-allowed"
                    >
                        <option>ALL</option>
                        <option v-for="area in areaOptions" :key="area" :value="area">{{ area }}</option>
                    </select>
                </div>

                <!-- WorkType Filter -->
                <div class="flex items-center gap-1.5 rounded-lg border border-border bg-background px-2 py-1.5 shadow-sm" :class="{ 'opacity-50': isSearchActive }">
                    <span class="text-xs font-medium text-muted-foreground whitespace-nowrap">TYPE:</span>
                    <select 
                        v-model="currentFilters.worktype"
                        @change="handleFilterChange"
                        :disabled="isSearchActive ? true : undefined"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 cursor-pointer pr-4 max-w-[90px] [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100 disabled:cursor-not-allowed"
                    >
                        <option>ALL</option>
                        <option>NORMAL</option>
                        <option>PROCESS RW</option>
                        <option>WH REWORK</option>
                        <option>OI REWORK</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm" :class="{ 'opacity-50': isSearchActive }">
                    <span class="text-xs font-medium text-muted-foreground">STATUS:</span>
                    <select 
                        v-model="currentFilters.status"
                        @change="handleFilterChange"
                        :disabled="isSearchActive ? true : undefined"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 cursor-pointer pr-6 [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100 disabled:cursor-not-allowed"
                    >
                        <option>ALL</option>
                        <option>Ongoing</option>
                        <option>Submitted</option>
                    </select>
                </div>

                <!-- Reset Button -->
                <Button 
                    variant="ghost" 
                    size="icon" 
                    class="h-8 w-8 rounded-lg hover:bg-muted"
                    @click="handleReset"
                    title="Reset filters"
                >
                    <span class="text-xl">🔀</span>
                </Button>

                <!-- Export Button -->
                <Button 
                    variant="outline" 
                    size="sm" 
                    class="h-8 px-3 rounded-lg hover:bg-muted gap-1.5"
                    @click="isExportModalOpen = true"
                    title="Export data to CSV"
                >
                    <span class="text-sm">📥</span>
                    <span class="text-xs font-medium">Export</span>
                </Button>
            </div>
        </template>

        <!-- ================================================================ -->
        <!-- MAIN CONTENT -->
        <!-- ================================================================ -->
        <div class="flex h-full flex-1 flex-col gap-3 p-4">
            <!-- Stats Cards -->
            <div class="grid gap-3 md:grid-cols-3 lg:grid-cols-6">
                <div 
                    v-for="(stat, index) in stats" 
                    :key="index"
                    class="group relative overflow-hidden rounded-xl border-2 border-border dark:border-gray-700 shadow-lg transition-all duration-300 hover:shadow-xl hover:-translate-y-0.5 hover:scale-[1.02]" 
                    :class="stat.color"
                >
                    <div class="relative p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1">
                                <p class="text-sm font-medium" :class="stat.textColor">
                                    {{ stat.title }}
                                </p>
                                <h3 class="mt-1 text-3xl font-bold" :class="stat.valueColor">
                                    {{ stat.value }}
                                </h3>
                            </div>
                            <div class="rounded-full p-3 ring-1 transition-all duration-300" :class="stat.iconBg">
                                <span class="text-3xl">{{ stat.icon }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lots Table -->
            <Card class="flex-1 overflow-hidden shadow-lg border-2 border-border dark:border-gray-700 dark:bg-gray-900/50">
                <CardHeader class="px-4 py-2">
                    <CardTitle class="flex items-center justify-between text-xl">
                        <span>Lot Entries</span>
                        <div class="flex items-center gap-3">
                            <!-- Performance Badges -->
                            <div class="flex items-center gap-2">
                                <Badge class="bg-blue-500/20 text-blue-700 dark:text-blue-300 border-blue-500/30 border px-3 py-1">
                                    <span class="text-xs font-semibold">🎯 Target: {{ formatMillions(props.badgeStats.targetQty) }}</span>
                                </Badge>
                                <Badge class="bg-orange-500/20 text-orange-700 dark:text-orange-300 border-orange-500/30 border px-3 py-1">
                                    <span class="text-xs font-semibold">⏳ Endtime: {{ formatMillions(props.badgeStats.endtimeQty) }}</span>
                                </Badge>
                                <Badge class="bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30 border px-3 py-1">
                                    <span class="text-xs font-semibold">✅ Submitted: {{ formatMillions(props.badgeStats.submittedQty) }}</span>
                                </Badge>
                                <Badge class="bg-purple-500/20 text-purple-700 dark:text-purple-300 border-purple-500/30 border px-3 py-1">
                                    <span class="text-xs font-semibold">📊 Achievement: {{ props.badgeStats.achievement }}%</span>
                                </Badge>
                            </div>
                            
                            <!-- Search Input -->
                            <div class="flex items-center gap-2 rounded-lg border-2 bg-background px-3 py-1.5 shadow-sm" :class="isSearchActive ? 'border-primary' : 'border-border'">
                                <span class="text-xs font-medium" :class="isSearchActive ? 'text-primary' : 'text-muted-foreground'">🔍</span>
                                <Input 
                                    v-model="currentFilters.search"
                                    @keyup.enter="handleFilterChange"
                                    placeholder="Search Lot ID, Model, Size, Line, Area, Type..."
                                    class="h-6 w-80 border-0 bg-transparent text-xs font-semibold text-foreground focus-visible:ring-0 focus-visible:ring-offset-0 px-0"
                                />
                                <Button 
                                    v-if="isSearchActive"
                                    variant="ghost" 
                                    size="icon" 
                                    class="h-5 w-5 hover:bg-muted"
                                    @click="currentFilters.search = ''; handleFilterChange();"
                                    title="Clear search"
                                >
                                    <span class="text-xs">❌</span>
                                </Button>
                            </div>
                            
                            <Button 
                                v-if="canManage"
                                variant="default" 
                                size="sm" 
                                class="h-9 px-4 text-sm font-medium shadow-sm bg-blue-600 text-white hover:bg-blue-700"
                                @click="handleWipUpdate"
                            >
                                🔄 UPDATE WIP
                            </Button>
                            
                            <Button 
                                v-if="canManage"
                                variant="default" 
                                size="sm" 
                                class="h-9 px-4 text-sm font-medium shadow-sm bg-primary text-primary-foreground hover:bg-primary/90"
                                @click="isAddModalOpen = true"
                            >
                                ➕ ADD NEW ENDTIME
                            </Button>
                            
                            <Button 
                                v-if="canManage"
                                variant="default" 
                                size="sm" 
                                class="h-9 px-4 text-sm font-medium shadow-sm bg-emerald-600 text-white hover:bg-emerald-700"
                                @click="handleSubmitLot"
                            >
                                ✅ SUBMIT LOT
                            </Button>
                        </div>
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-auto max-h-[calc(100vh-320px)]">
                        <table class="w-full text-xs">
                            <thead class="bg-muted/80 dark:bg-gray-800 sticky top-0 z-10">
                                <tr class="border-b-2 border-border dark:border-gray-600">
                                    <th class="p-3 text-left font-semibold w-15">#</th>
                                    <th 
                                        class="p-3 text-left font-semibold w-25 cursor-pointer hover:bg-muted/50 transition-colors select-none"
                                        @click="handleSort('lot_id')"
                                        title="Click to sort"
                                    >
                                        Lot ID {{ getSortIcon('lot_id') }}
                                    </th>
                                    <th 
                                        class="p-3 text-left font-semibold w-45 cursor-pointer hover:bg-muted/50 transition-colors select-none"
                                        @click="handleSort('model_15')"
                                        title="Click to sort"
                                    >
                                        Model {{ getSortIcon('model_15') }}
                                    </th>
                                    <th 
                                        class="p-3 text-right font-semibold w-30 cursor-pointer hover:bg-muted/50 transition-colors select-none"
                                        @click="handleSort('lot_qty')"
                                        title="Click to sort"
                                    >
                                        Quantity {{ getSortIcon('lot_qty') }}
                                    </th>
                                    <th 
                                        class="p-3 text-center font-semibold cursor-pointer hover:bg-muted/50 transition-colors select-none"
                                        @click="handleSort('lot_size')"
                                        title="Click to sort"
                                    >
                                        Size {{ getSortIcon('lot_size') }}
                                    </th>
                                    <th class="p-3 text-center font-semibold">Lipas</th>
                                    <th 
                                        class="p-3 text-center font-semibold cursor-pointer hover:bg-muted/50 transition-colors select-none"
                                        @click="handleSort('eqp_line')"
                                        title="Click to sort"
                                    >
                                        Line {{ getSortIcon('eqp_line') }}
                                    </th>
                                    <th 
                                        class="p-3 text-center font-semibold cursor-pointer hover:bg-muted/50 transition-colors select-none"
                                        @click="handleSort('eqp_area')"
                                        title="Click to sort"
                                    >
                                        Area {{ getSortIcon('eqp_area') }}
                                    </th>
                                    <th class="p-3 text-left font-semibold">Machine</th>
                                    <th 
                                        class="p-3 text-center font-semibold w-30 cursor-pointer hover:bg-muted/50 transition-colors select-none"
                                        @click="handleSort('work_type')"
                                        title="Click to sort"
                                    >
                                        Work Type {{ getSortIcon('work_type') }}
                                    </th>
                                    <th 
                                        class="p-3 text-left font-semibold w-45 cursor-pointer hover:bg-muted/50 transition-colors select-none"
                                        @click="handleSort('est_endtime')"
                                        title="Click to sort"
                                    >
                                        Est. Endtime {{ getSortIcon('est_endtime') }}
                                    </th>
                                    <th 
                                        class="p-3 text-left font-semibold w-45 cursor-pointer hover:bg-muted/50 transition-colors select-none"
                                        @click="handleSort('actual_submitted_at')"
                                        title="Click to sort"
                                    >
                                        Submitted Time {{ getSortIcon('actual_submitted_at') }}
                                    </th>
                                    <th 
                                        class="p-3 text-center font-semibold cursor-pointer hover:bg-muted/50 transition-colors select-none"
                                        @click="handleSort('status')"
                                        title="Click to sort"
                                    >
                                        Status {{ getSortIcon('status') }}
                                    </th>
                                    <th class="p-3 text-center font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="(lot, index) in sortedLots" 
                                    :key="lot.id"
                                    class="border-b border-border dark:border-gray-700 hover:bg-muted/50 dark:hover:bg-gray-800/50 transition-colors"
                                >
                                    <td class="p-3 text-muted-foreground">{{ index + 1 }}</td>
                                    <td class="p-3">
                                        <Badge class="font-mono font-semibold bg-blue-500/20 text-blue-700 dark:text-blue-300 border-blue-500/30">
                                            {{ lot.lot_id }}
                                        </Badge>
                                    </td>
                                    <td class="p-3 text-left font-medium text-foreground truncate max-w-32">{{ lot.model_15 }}</td>
                                    <td class="p-3 text-right font-semibold">{{ formatQuantity(lot.lot_qty) }}</td>
                                    <td class="p-3 text-center font-semibold">{{ getSizeDisplay(lot.lot_size) }}</td>
                                    <td class="p-3 text-center">
                                        <Badge :class="getLipasBadgeClass(lot.lipas_yn)" class="border font-semibold">
                                            {{ lot.lipas_yn }}
                                        </Badge>
                                    </td>
                                    <td class="p-3 text-center">
                                        <Badge variant="outline" class="font-semibold">{{ lot.eqp_line }}</Badge>
                                    </td>
                                    <td class="p-3 text-center font-semibold">{{ lot.eqp_area }}</td>
                                    <td class="p-3 text-left text-xs font-semibold">{{ getMachineList(lot) }}</td>
                                    <td class="p-3 text-center">
                                        <Badge variant="outline" class="text-xs">{{ lot.work_type }}</Badge>
                                    </td>
                                    <td class="p-3" :class="getEndtimeColorClass(lot.est_endtime)">{{ formatDateTime(lot.est_endtime) }}</td>
                                    <td class="p-3 text-emerald-600 dark:text-emerald-400">
                                        {{ lot.actual_submitted_at ? formatDateTime(lot.actual_submitted_at) : '-' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <Badge :class="getStatusBadgeClass(lot.status)" class="border">
                                            {{ lot.status }}
                                        </Badge>
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <Button 
                                                v-if="lot.status === 'Ongoing' && canManage"
                                                variant="ghost" 
                                                size="icon" 
                                                class="h-7 w-7 hover:bg-primary/10 hover:text-primary" 
                                                title="Edit"
                                                @click="handleEdit(lot)"
                                            >
                                                <span class="text-sm">✏️</span>
                                            </Button>
                                            <Button 
                                                v-if="canDelete"
                                                variant="ghost" 
                                                size="icon" 
                                                class="h-7 w-7 hover:bg-destructive/10 hover:text-destructive" 
                                                title="Delete"
                                                @click="handleDelete(lot.id, lot.lot_id)"
                                            >
                                                <span class="text-sm">🟥</span>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="sortedLots.length === 0">
                                    <td colspan="14" class="p-8 text-center text-muted-foreground">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="text-4xl">📭</span>
                                            <p class="text-sm">No lots found</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Add Lot Modal Component -->
        <EndtimeAddModal v-model:open="isAddModalOpen" />
        
        <!-- Edit Lot Modal Component -->
        <EndtimeAddModal v-model:open="isEditModalOpen" :lot="editingLot" />
        
        <!-- Submit Lot Modal Component -->
        <EndtimeSubmitModal v-model:open="isSubmitModalOpen" />
        
        <!-- WIP Update Modal Component -->
        <EndtimeWipupdateModal v-model:open="isWipUpdateModalOpen" />
        
        <!-- Export Modal -->
        <Dialog :open="isExportModalOpen" @update:open="isExportModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <span class="text-xl">📥</span>
                        <span>Export Data to CSV</span>
                    </DialogTitle>
                </DialogHeader>
                
                <div class="space-y-4 py-4">
                    <p class="text-sm text-muted-foreground">
                        Select the date range and filters for the data you want to export.
                    </p>
                    
                    <!-- Date Range Filters -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Start Date</label>
                            <input 
                                type="date" 
                                v-model="exportFilters.dateFrom"
                                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-sm font-medium">End Date</label>
                            <input 
                                type="date" 
                                v-model="exportFilters.dateTo"
                                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                    </div>
                    
                    <!-- Shift Filter -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Shift</label>
                        <select 
                            v-model="exportFilters.shift"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option>ALL</option>
                            <option>DAY</option>
                            <option>NIGHT</option>
                        </select>
                    </div>
                    
                    <!-- Cutoff Filter -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Cutoff</label>
                        <select 
                            v-model="exportFilters.cutoff"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option>ALL</option>
                            <option>1ST</option>
                            <option>2ND</option>
                            <option>3RD</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end gap-2">
                    <Button 
                        variant="outline" 
                        @click="isExportModalOpen = false"
                        :disabled="isExporting"
                    >
                        Cancel
                    </Button>
                    <Button 
                        @click="handleExport"
                        :disabled="isExporting"
                        class="gap-2"
                    >
                        <span v-if="isExporting">Exporting...</span>
                        <span v-else>
                            <span>📥</span>
                            <span>Export CSV</span>
                        </span>
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
