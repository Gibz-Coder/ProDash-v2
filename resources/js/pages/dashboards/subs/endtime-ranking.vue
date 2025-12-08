<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';

// ============================================================================
// TYPES
// ============================================================================
interface OperatorStat {
    name: string;
    target: number;
    submitted: number;
    achievement: number;
    lotCount: number;
}

interface RankingItem {
    rank: number;
    achievement: number;
    target: number;
    endtime: number;
    submitted: number;
    lotCount: number;
    line?: string;
    area?: string;
    inCharge?: string | string[];
    operatorStats?: OperatorStat[];
    machineCount?: number;
    topPerformer?: string;
}

// ============================================================================
// PROPS
// ============================================================================
/**
 * Per-Shift Achievement Date Range Logic (Backend Implementation):
 * 
 * DAY SHIFT:
 * - Date Range: Selected Date 07:00 AM to Selected Date 06:59 PM
 * - Example: Date 11/27/2025 → 11/27/2025 07:00 AM ~ 11/27/2025 06:59 PM
 * 
 * NIGHT SHIFT:
 * - Date Range: Selected Date 07:00 PM to Next Day 06:59 AM
 * - Example: Date 11/27/2025 → 11/27/2025 07:00 PM ~ 11/28/2025 06:59 AM
 * 
 * ALL SHIFT:
 * - Combines both DAY and NIGHT shift data for the selected date
 * 
 * CUTOFF Logic:
 * - 1ST: First cutoff period within the shift
 * - 2ND: Second cutoff period within the shift
 * - 3RD: Third cutoff period within the shift
 * - ALL: All cutoff periods combined
 */
const props = withDefaults(defineProps<{
    filters?: {
        date: string;
        shift: string;
        cutoff: string;
        rankingType: string;
    };
    topPerformers?: Array<{
        rank: number;
        name: string;
        achievement: number;
        target: number;
        endtime: number;
        submitted: number;
        lotCount: number;
        trend: 'up' | 'down' | 'same';
    }>;
    bottomPerformers?: Array<{
        rank: number;
        name: string;
        achievement: number;
        target: number;
        endtime: number;
        submitted: number;
        lotCount: number;
        trend: 'up' | 'down' | 'same';
    }>;
    lineRankings?: Array<RankingItem>;
    areaRankings?: Array<RankingItem>;
}>(), {
    filters: () => ({
        date: new Date().toISOString().split('T')[0],
        shift: 'ALL',
        cutoff: 'ALL',
        rankingType: 'line',
    }),
    topPerformers: () => [],
    bottomPerformers: () => [],
    lineRankings: () => [],
    areaRankings: () => [],
});

// ============================================================================
// HELPER FUNCTIONS (must be defined before STATE)
// ============================================================================
/**
 * Get the current shift based on the current time
 * DAY: 07:00 AM - 06:59 PM
 * NIGHT: 07:00 PM - 06:59 AM
 */
const getCurrentShift = (): string => {
    const now = new Date();
    const hour = now.getHours();
    
    // DAY shift: 7 AM (07:00) to 6:59 PM (18:59)
    // NIGHT shift: 7 PM (19:00) to 6:59 AM (06:59)
    if (hour >= 7 && hour < 19) {
        return 'DAY';
    } else {
        return 'NIGHT';
    }
};

/**
 * Get the date for the current shift
 * For NIGHT shift (7 PM to 6:59 AM), the shift "date" is the date when it started (at 7 PM)
 * - If current time is 00:00 AM to 06:59 AM → use previous date (shift started yesterday at 7 PM)
 * - If current time is 07:00 AM to 06:59 PM → use current date (DAY shift)
 * - If current time is 07:00 PM to 11:59 PM → use current date (NIGHT shift just started)
 */
const getShiftDate = (): string => {
    const now = new Date();
    const hour = now.getHours();
    
    // Helper function to format date as YYYY-MM-DD in local timezone
    const formatLocalDate = (date: Date): string => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };
    
    // If time is between midnight (00:00) and 06:59 AM, we're in NIGHT shift that started yesterday
    // So use previous date as the shift date
    if (hour >= 0 && hour < 7) {
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        return formatLocalDate(yesterday);
    }
    
    // Otherwise (7 AM onwards), use current date
    return formatLocalDate(now);
};

// ============================================================================
// STATE
// ============================================================================
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: dashboard().url },
    { title: 'ENDTIME', href: '/endtime' },
    { title: 'Achievement Ranking', href: '/endtime-ranking' },
];

// Initialize filters with backend-provided values (which should be correct on fresh load)
const currentFilters = ref({
    date: props.filters?.date || getShiftDate(),
    shift: props.filters?.shift || getCurrentShift(),
    cutoff: props.filters?.cutoff || 'ALL',
    rankingType: props.filters?.rankingType || 'line',
});

// Auto refresh state
const autoRefresh = ref(false);
const refreshInterval = ref<number | null>(null);

// Sorting state
const sortColumn = ref<string>('rank');
const sortDirection = ref<'asc' | 'desc'>('asc');

// Modal state
const showModal = ref(false);
const selectedItem = ref<RankingItem | null>(null);

// ============================================================================
// METHODS
// ============================================================================

/**
 * Reset filters to default values
 */
const resetToDefaultFilters = () => {
    currentFilters.value = {
        date: getShiftDate(),
        shift: getCurrentShift(),
        cutoff: 'ALL',
        rankingType: 'line',
    };
};

const handleFilterChange = () => {
    router.get('/endtime-ranking', currentFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetToCurrentShift = () => {
    router.get('/endtime-ranking', {
        date: getShiftDate(),
        shift: getCurrentShift(),
        cutoff: 'ALL',
        rankingType: 'line',
    }, {
        preserveState: false,
        preserveScroll: false,
    });
};

const toggleAutoRefresh = () => {
    autoRefresh.value = !autoRefresh.value;
    
    if (autoRefresh.value) {
        // Reset to current shift when turning ON
        const shiftDate = getShiftDate();
        const currentShift = getCurrentShift();
        
        // Update local filters
        currentFilters.value = {
            date: shiftDate,
            shift: currentShift,
            cutoff: 'ALL',
            rankingType: 'line',
        };
        
        // Navigate with fresh data
        router.get('/endtime-ranking', {
            date: shiftDate,
            shift: currentShift,
            cutoff: 'ALL',
            rankingType: 'line',
        }, {
            preserveState: true,
            preserveScroll: true,
        });
        
        // Refresh every 30 seconds
        refreshInterval.value = window.setInterval(() => {
            // Update to current shift and date on each refresh
            const refreshShiftDate = getShiftDate();
            const refreshCurrentShift = getCurrentShift();
            
            currentFilters.value = {
                date: refreshShiftDate,
                shift: refreshCurrentShift,
                cutoff: 'ALL',
                rankingType: 'line',
            };
            
            router.get('/endtime-ranking', {
                date: refreshShiftDate,
                shift: refreshCurrentShift,
                cutoff: 'ALL',
                rankingType: 'line',
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        }, 30000);
    } else {
        if (refreshInterval.value) {
            clearInterval(refreshInterval.value);
            refreshInterval.value = null;
        }
    }
};

// Cleanup on unmount
import { onUnmounted } from 'vue';
onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
});

const formatNumber = (value: number): string => {
    // If value is already in millions (< 1000), just format it
    // If value is in raw units (> 1000), convert to millions
    if (value > 1000) {
        return (value / 1000000).toFixed(2);
    }
    return value.toFixed(2);
};

const getItemName = (item: RankingItem): string => {
    if (currentFilters.value.rankingType === 'area') {
        return item.area ? `Area ${item.area}` : 'Unknown';
    } else {
        return item.line ? `Line ${item.line}` : 'Unknown';
    }
};

const getDynamicColumnName = (): string => {
    return currentFilters.value.rankingType === 'area' ? 'AREA' : 'LINE';
};

const getDynamicColumnValue = (item: RankingItem): string => {
    return currentFilters.value.rankingType === 'area' ? (item.area || '-') : (item.line || '-');
};

const handleSort = (column: string) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
};

const getSortIcon = (column: string): string => {
    if (sortColumn.value !== column) return '⇅';
    return sortDirection.value === 'asc' ? '↑' : '↓';
};

const openModal = (item: RankingItem) => {
    selectedItem.value = item;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedItem.value = null;
};

// Get rank badge color based on position
const getRankBadgeColor = (rank: number): string => {
    if (rank === 1) return 'bg-gradient-to-r from-[#FFD700] to-[#FFA500] text-white shadow-lg';
    if (rank === 2) return 'bg-gradient-to-r from-gray-300 to-gray-400 text-gray-800 shadow-md';
    if (rank === 3) return 'bg-gradient-to-r from-[#CD7F32] to-[#8B4513] text-white shadow-md';
    if (rank <= 5) return 'bg-gradient-to-r from-[#10B981] to-[#059669] text-white';
    if (rank <= 10) return 'bg-gradient-to-r from-[#3B82F6] to-[#2563EB] text-white';
    return 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
};

// Get achievement badge color based on percentage
const getAchievementBadgeColor = (achievement: number): string => {
    if (achievement >= 100) return 'bg-gradient-to-r from-[#10B981] to-[#059669] text-white shadow-md';
    if (achievement >= 90) return 'bg-gradient-to-r from-[#3B82F6] to-[#2563EB] text-white shadow-md';
    if (achievement >= 75) return 'bg-gradient-to-r from-[#F59E0B] to-[#D97706] text-white shadow-md';
    if (achievement >= 50) return 'bg-gradient-to-r from-[#EF4444] to-[#DC2626] text-white shadow-md';
    return 'bg-gradient-to-r from-[#991B1B] to-[#7F1D1D] text-white shadow-md';
};

// Get row background color based on rank
const getRowColor = (rank: number): string => {
    if (rank === 1) return 'bg-gradient-to-r from-[#FFD700]/10 to-transparent dark:from-[#FFD700]/20';
    if (rank === 2) return 'bg-gradient-to-r from-gray-300/10 to-transparent dark:from-gray-400/20';
    if (rank === 3) return 'bg-gradient-to-r from-[#CD7F32]/10 to-transparent dark:from-[#CD7F32]/20';
    return '';
};

// ============================================================================
// COMPUTED
// ============================================================================
const currentRankingData = computed((): RankingItem[] => {
    switch (currentFilters.value.rankingType) {
        case 'area':
            return props.areaRankings || [];
        case 'line':
        default:
            return props.lineRankings || [];
    }
});

// Top 3 based on current ranking type
const topThree = computed(() => {
    const data = [...currentRankingData.value];
    return data.slice(0, 3).map((item, index) => ({
        rank: index + 1,
        name: getItemName(item),
        achievement: item.achievement,
        target: item.target,
        endtime: item.endtime,
        submitted: item.submitted,
        lotCount: item.lotCount,
        trend: 'same' as const,
    }));
});

// Sorted ranking data
const sortedRankingData = computed(() => {
    const data = [...currentRankingData.value];
    
    if (sortColumn.value === 'rank') {
        return data.sort((a, b) => {
            const comparison = a.rank - b.rank;
            return sortDirection.value === 'asc' ? comparison : -comparison;
        });
    }
    
    return data.sort((a, b) => {
        let aVal: any = a[sortColumn.value as keyof RankingItem];
        let bVal: any = b[sortColumn.value as keyof RankingItem];
        
        if (typeof aVal === 'string') {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }
        
        const comparison = aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        return sortDirection.value === 'asc' ? comparison : -comparison;
    });
});

// Summary statistics
const summaryStats = computed(() => {
    const data = currentRankingData.value;
    if (data.length === 0) return null;

    const totalTarget = data.reduce((sum, item) => sum + item.target, 0);
    const totalEndtime = data.reduce((sum, item) => sum + item.endtime, 0);
    const totalSubmitted = data.reduce((sum, item) => sum + item.submitted, 0);
    const totalLots = data.reduce((sum, item) => sum + item.lotCount, 0);
    const avgAchievement = data.reduce((sum, item) => sum + item.achievement, 0) / data.length;

    return {
        totalTarget: formatNumber(totalTarget),
        totalEndtime: formatNumber(totalEndtime),
        totalSubmitted: formatNumber(totalSubmitted),
        totalLots,
        avgAchievement: formatNumber(avgAchievement),
        overallAchievement: totalTarget > 0 ? formatNumber((totalSubmitted / totalTarget) * 100) : '0.0',
    };
});
</script>

<template>
    <Head title="Endtime Achievement Ranking" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- ================================================================ -->
        <!-- FILTERS SECTION -->
        <!-- ================================================================ -->
        <template #filters>
            <div class="flex items-center gap-3">
                <!-- Date Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm transition-all duration-150 hover:shadow hover:border-primary/30">
                    <span class="text-xs font-medium text-muted-foreground">DATE:</span>
                    <input 
                        type="date" 
                        v-model="currentFilters.date"
                        @change="handleFilterChange"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 cursor-pointer"
                    />
                </div>

                <!-- Shift Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm transition-all duration-150 hover:shadow hover:border-primary/30">
                    <span class="text-xs font-medium text-muted-foreground">SHIFT:</span>
                    <select 
                        v-model="currentFilters.shift"
                        @change="handleFilterChange"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 pr-6 cursor-pointer [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100"
                    >
                        <option>ALL</option>
                        <option>DAY</option>
                        <option>NIGHT</option>
                    </select>
                </div>

                <!-- Cutoff Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm transition-all duration-150 hover:shadow hover:border-primary/30">
                    <span class="text-xs font-medium text-muted-foreground">CUTOFF:</span>
                    <select 
                        v-model="currentFilters.cutoff"
                        @change="handleFilterChange"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 pr-6 cursor-pointer [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100"
                    >
                        <option>ALL</option>
                        <option>1ST</option>
                        <option>2ND</option>
                        <option>3RD</option>
                    </select>
                </div>

                <!-- Ranking Type Filter -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm transition-all duration-150 hover:shadow hover:border-primary/30">
                    <span class="text-xs font-medium text-muted-foreground">VIEW:</span>
                    <select 
                        v-model="currentFilters.rankingType"
                        @change="handleFilterChange"
                        class="border-0 bg-transparent text-xs font-semibold text-foreground focus:outline-none focus:ring-0 pr-6 cursor-pointer [&>option]:bg-background [&>option]:text-foreground [&>option]:dark:bg-gray-800 [&>option]:dark:text-gray-100"
                    >
                        <option value="line">Per Line</option>
                        <option value="area">Per Area</option>
                    </select>
                </div>

                <!-- Auto Refresh Toggle -->
                <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm transition-all duration-150 hover:shadow hover:border-primary/30">
                    <span class="text-xs font-medium text-muted-foreground">AUTO REFRESH:</span>
                    <button 
                        @click="toggleAutoRefresh"
                        :class="[
                            'relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2',
                            autoRefresh ? 'bg-[#10B981]' : 'bg-gray-300 dark:bg-gray-600'
                        ]"
                    >
                        <span 
                            :class="[
                                'inline-block h-4 w-4 transform rounded-full bg-white shadow-lg transition-transform duration-200',
                                autoRefresh ? 'translate-x-5' : 'translate-x-0.5'
                            ]"
                        />
                    </button>
                    <span class="text-xs font-semibold" :class="autoRefresh ? 'text-[#10B981]' : 'text-gray-500'">
                        {{ autoRefresh ? 'ON' : 'OFF' }}
                    </span>
                </div>

                <!-- Reset to Current Shift Button -->
                <button 
                    @click="resetToCurrentShift"
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm transition-all duration-150 hover:shadow hover:border-primary/30 hover:bg-primary/5"
                    title="Reset to current shift"
                >
                    <span class="text-xs font-medium text-muted-foreground">🔄 RESET</span>
                </button>
            </div>
        </template>

        <!-- ================================================================ -->
        <!-- MAIN CONTENT -->
        <!-- ================================================================ -->
        <div class="flex h-[calc(100vh-120px)] flex-col gap-3 p-4 overflow-hidden">
            <!-- Top 4 Summary Cards (Single row) -->
            <div v-if="summaryStats" class="grid gap-3 grid-cols-4 flex-shrink-0">
                <!-- Card 1: Target Capacity (Peach/Orange) -->
                <Card class="bg-gradient-to-br from-[#FFE5D9] to-[#FFD4C4] dark:from-[#8B4513]/30 dark:to-[#A0522D]/30 shadow-sm border-0 transition-all duration-200 hover:shadow-md cursor-pointer">
                    <CardContent class="p-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-[#D2691E] dark:text-[#FFB380] mb-2">Target Capacity</p>
                                <h3 class="text-3xl font-bold text-[#8B4513] dark:text-white mb-1">{{ summaryStats.totalTarget }} M PCS</h3>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/60 dark:bg-white/20">
                                <span class="text-3xl">🎯</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 2: Total Endtime (Purple) -->
                <Card class="bg-gradient-to-br from-[#E9D5FF] to-[#DDD6FE] dark:from-[#6B21A8]/30 dark:to-[#7C3AED]/30 shadow-sm border-0 transition-all duration-200 hover:shadow-md cursor-pointer">
                    <CardContent class="p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-[#7C3AED] dark:text-[#C4B5FD] mb-2">Total Endtime</p>
                                <h3 class="text-3xl font-bold text-[#6B21A8] dark:text-white mb-1">{{ summaryStats.totalEndtime }} M PCS</h3>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/60 dark:bg-white/20">
                                <span class="text-3xl">⏱️</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 3: Submitted Lots (Green) -->
                <Card class="bg-gradient-to-br from-[#D1FAE5] to-[#A7F3D0] dark:from-[#065F46]/30 dark:to-[#059669]/30 shadow-sm border-0 transition-all duration-200 hover:shadow-md cursor-pointer">
                    <CardContent class="p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-[#059669] dark:text-[#6EE7B7] mb-2">Submitted Lots</p>
                                <h3 class="text-3xl font-bold text-[#065F46] dark:text-white mb-1">{{ summaryStats.totalSubmitted }} M PCS</h3>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/60 dark:bg-white/20">
                                <span class="text-3xl">✅</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Card 4: Achievement Rate (Info/Light Blue) -->
                <Card class="bg-gradient-to-br from-[#DBEAFE] to-[#BFDBFE] dark:from-[#1E3A8A]/30 dark:to-[#1E40AF]/30 shadow-sm border-0 transition-all duration-200 hover:shadow-md cursor-pointer">
                    <CardContent class="p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-[#2563EB] dark:text-[#93C5FD] mb-2">Achievement Rate</p>
                                <h3 class="text-3xl font-bold text-[#1E40AF] dark:text-white mb-1">{{ summaryStats.overallAchievement }}% Overall</h3>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/60 dark:bg-white/20">
                                <span class="text-3xl">📊</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Main Content Grid: Podium + Table -->
            <div class="grid gap-3 lg:grid-cols-2 flex-1 min-h-0 h-full">
                <!-- LEFT: Podium Section -->
                <div class="flex flex-col h-full">
                    <!-- Podium - Award-Winning Design (Taller & Thinner) -->
                    <div class="flex-1 flex items-end justify-center pb-2 min-h-[320px] relative">
                        <!-- Decorative Background Elements -->
                        <div class="absolute inset-0 overflow-hidden pointer-events-none">
                            <div class="absolute top-0 left-1/4 w-24 h-24 bg-gradient-to-br from-yellow-300/20 to-orange-300/20 rounded-full blur-3xl animate-pulse"></div>
                            <div class="absolute top-5 right-1/4 w-28 h-28 bg-gradient-to-br from-purple-300/20 to-pink-300/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
                        </div>

                        <div class="grid grid-cols-3 gap-8 w-full max-w-5xl px-3 relative z-10">
                            <!-- 2nd Place - Silver -->
                            <div v-if="topThree[1]" class="flex flex-col items-center justify-end group cursor-pointer animate-fade-in" style="animation-delay: 0.2s;">
                                <!-- Avatar with Glow -->
                                <div class="relative mb-3 transition-all duration-300 group-hover:scale-110 group-hover:-translate-y-1">
                                    <div class="absolute inset-0 bg-gradient-to-br from-gray-300 to-gray-500 rounded-full blur-xl opacity-60 group-hover:opacity-90 transition-opacity"></div>
                                    <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-gray-200 via-gray-300 to-gray-400 shadow-2xl ring-4 ring-white dark:ring-gray-800 transition-all duration-300 group-hover:ring-5 group-hover:shadow-gray-400/60">
                                        <span class="text-3xl filter drop-shadow-lg">🥈</span>
                                    </div>
                                </div>

                                <!-- Podium Card -->
                                <div class="w-4/5 mx-auto rounded-t-2xl bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300 dark:from-gray-700 dark:via-gray-600 dark:to-gray-500 px-2 py-16 text-center shadow-xl transition-all duration-300 group-hover:shadow-gray-400/50 group-hover:scale-[1.02] border-t-4 border-gray-400 relative overflow-hidden">
                                    <!-- Shine Effect -->
                                    <div class="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    
                                    <div class="relative z-10">
                                        <div class="mb-2 text-gray-700 dark:text-gray-200 text-sm font-black truncate px-1">{{ topThree[1].name }}</div>
                                        <div class="flex items-center justify-center mb-2">
                                            <div class="relative">
                                                <div class="absolute inset-0 bg-gray-400 rounded-full blur-sm opacity-50"></div>
                                                <span class="relative text-4xl filter drop-shadow-xl transition-transform duration-300 group-hover:scale-110">🏆</span>
                                            </div>
                                        </div>
                                        <div class="text-2xl font-black bg-gradient-to-r from-gray-700 to-gray-900 dark:from-white dark:to-gray-200 bg-clip-text text-transparent mb-0.5">{{ formatNumber(topThree[1].achievement) }}%</div>
                                        <div class="text-[10px] font-black text-gray-600 dark:text-gray-300 tracking-wider">2ND PLACE</div>
                                    </div>
                                </div>
                            </div>

                            <!-- 1st Place - Gold (Taller & More Prominent) -->
                            <div v-if="topThree[0]" class="flex flex-col items-center justify-end -mt-12 group cursor-pointer animate-fade-in z-20">
                                <!-- Avatar with Crown & Glow -->
                                <div class="relative mb-3 transition-all duration-300 group-hover:scale-110 group-hover:-translate-y-2">
                                    <div class="absolute inset-0 bg-gradient-to-br from-yellow-300 to-orange-500 rounded-full blur-2xl opacity-70 group-hover:opacity-100 transition-opacity animate-pulse"></div>
                                    <div class="relative flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-yellow-300 via-yellow-400 to-orange-500 shadow-2xl ring-4 ring-white dark:ring-gray-800 transition-all duration-300 group-hover:ring-6 group-hover:shadow-yellow-500/70">
                                        <span class="text-4xl filter drop-shadow-2xl">👑</span>
                                    </div>
                                    <div class="absolute -top-2 -right-1 animate-bounce">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 shadow-xl ring-2 ring-white">
                                            <span class="text-lg">✨</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Podium Card -->
                                <div class="w-4/5 mx-auto rounded-t-2xl bg-gradient-to-br from-yellow-200 via-yellow-300 to-orange-400 dark:from-yellow-500 dark:via-yellow-600 dark:to-yellow-700 px-2 py-28 text-center shadow-2xl transition-all duration-300 group-hover:shadow-yellow-500/60 group-hover:scale-[1.03] border-t-4 border-yellow-500 relative overflow-hidden">
                                    <!-- Animated Shine Effect -->
                                    <div class="absolute inset-0 bg-gradient-to-br from-white/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    <div class="absolute top-0 left-0 w-full h-full">
                                        <div class="absolute top-2 left-2 w-2 h-2 bg-white/60 rounded-full animate-ping"></div>
                                        <div class="absolute top-4 right-4 w-1.5 h-1.5 bg-white/60 rounded-full animate-ping" style="animation-delay: 0.5s;"></div>
                                        <div class="absolute bottom-6 left-6 w-1.5 h-1.5 bg-white/60 rounded-full animate-ping" style="animation-delay: 1s;"></div>
                                    </div>
                                    
                                    <div class="relative z-10">
                                        <div class="mb-2 text-orange-900 dark:text-yellow-50 text-base font-black truncate px-1 drop-shadow-lg">{{ topThree[0].name }}</div>
                                        <div class="flex items-center justify-center mb-2">
                                            <div class="relative">
                                                <div class="absolute inset-0 bg-yellow-500 rounded-full blur-md opacity-60 animate-pulse"></div>
                                                <span class="relative text-5xl filter drop-shadow-2xl transition-transform duration-300 group-hover:scale-125 group-hover:rotate-12">🏆</span>
                                            </div>
                                        </div>
                                        <div class="text-3xl font-black bg-gradient-to-r from-orange-800 to-red-900 dark:from-yellow-50 dark:to-yellow-200 bg-clip-text text-transparent mb-1 drop-shadow-lg">{{ formatNumber(topThree[0].achievement) }}%</div>
                                        <div class="text-xs font-black text-orange-900 dark:text-yellow-100 tracking-wider drop-shadow-md">1ST PLACE</div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3rd Place - Bronze -->
                            <div v-if="topThree[2]" class="flex flex-col items-center justify-end group cursor-pointer animate-fade-in" style="animation-delay: 0.4s;">
                                <!-- Avatar with Glow -->
                                <div class="relative mb-3 transition-all duration-300 group-hover:scale-110 group-hover:-translate-y-1">
                                    <div class="absolute inset-0 bg-gradient-to-br from-orange-400 to-orange-700 rounded-full blur-xl opacity-60 group-hover:opacity-90 transition-opacity"></div>
                                    <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-orange-300 via-orange-400 to-orange-600 shadow-2xl ring-4 ring-white dark:ring-gray-800 transition-all duration-300 group-hover:ring-5 group-hover:shadow-orange-500/60">
                                        <span class="text-3xl filter drop-shadow-lg">🥉</span>
                                    </div>
                                </div>

                                <!-- Podium Card -->
                                <div class="w-4/5 mx-auto rounded-t-2xl bg-gradient-to-br from-orange-100 via-orange-200 to-orange-300 dark:from-orange-800 dark:via-orange-700 dark:to-red-800 px-2 py-14 text-center shadow-xl transition-all duration-300 group-hover:shadow-orange-400/50 group-hover:scale-[1.02] border-t-4 border-orange-500 relative overflow-hidden">
                                    <!-- Shine Effect -->
                                    <div class="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    
                                    <div class="relative z-10">
                                        <div class="mb-2 text-orange-900 dark:text-orange-50 text-sm font-black truncate px-1">{{ topThree[2].name }}</div>
                                        <div class="flex items-center justify-center mb-2">
                                            <div class="relative">
                                                <div class="absolute inset-0 bg-orange-500 rounded-full blur-sm opacity-50"></div>
                                                <span class="relative text-4xl filter drop-shadow-xl transition-transform duration-300 group-hover:scale-110">🏆</span>
                                            </div>
                                        </div>
                                        <div class="text-2xl font-black bg-gradient-to-r from-orange-800 to-orange-950 dark:from-orange-50 dark:to-orange-200 bg-clip-text text-transparent mb-0.5">{{ formatNumber(topThree[2].achievement) }}%</div>
                                        <div class="text-[10px] font-black text-orange-800 dark:text-orange-100 tracking-wider">3RD PLACE</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom 3 Cards - Top Performers (Compact) -->
                    <div class="grid gap-2 grid-cols-3 flex-shrink-0 mt-2">
                        <!-- 2nd Place Achiever -->
                        <Card v-if="topThree[1]" class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 shadow-md border-2 border-gray-300 dark:border-gray-600 transition-all duration-300 hover:shadow-xl hover:border-gray-400 dark:hover:border-gray-500 hover:-translate-y-0.5 cursor-pointer group overflow-hidden relative">
                            <div class="absolute inset-0 bg-gradient-to-br from-gray-200/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <CardContent class="p-2.5 flex flex-col items-center justify-center relative z-10">
                                <div class="mb-1.5 px-2 py-0.5 bg-gray-200 dark:bg-gray-700 rounded-full">
                                    <p class="text-[10px] font-black text-gray-600 dark:text-gray-300 tracking-wide">TOP PERFORMER</p>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-gray-300 to-gray-400 shadow-md group-hover:scale-110 transition-transform duration-300">
                                        <span class="text-base">👤</span>
                                    </div>
                                    <p class="text-xs font-black text-gray-900 dark:text-white text-center leading-tight">
                                        {{ currentRankingData[1]?.topPerformer || topThree[1].name }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- 1st Place Achiever -->
                        <Card v-if="topThree[0]" class="bg-gradient-to-br from-yellow-50 via-orange-50 to-yellow-100 dark:from-yellow-900/30 dark:via-orange-900/30 dark:to-yellow-800/30 shadow-lg border-2 border-yellow-400 dark:border-yellow-500 transition-all duration-300 hover:shadow-xl hover:shadow-yellow-500/50 hover:scale-105 hover:-translate-y-1 cursor-pointer group overflow-hidden relative">
                            <div class="absolute inset-0 bg-gradient-to-br from-yellow-200/50 to-orange-200/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="absolute top-1 right-1 animate-bounce">
                                <span class="text-lg">⭐</span>
                            </div>
                            <CardContent class="p-2.5 flex flex-col items-center justify-center relative z-10">
                                <div class="mb-1.5 px-2 py-0.5 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-full shadow-md">
                                    <p class="text-[10px] font-black text-white tracking-wide">🏆 CHAMPION</p>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 shadow-lg group-hover:scale-125 transition-transform duration-300 ring-2 ring-yellow-300">
                                        <span class="text-lg">👤</span>
                                    </div>
                                    <p class="text-xs font-black bg-gradient-to-r from-orange-700 to-red-700 dark:from-yellow-400 dark:to-orange-400 bg-clip-text text-transparent text-center leading-tight">
                                        {{ currentRankingData[0]?.topPerformer || topThree[0].name }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- 3rd Place Achiever -->
                        <Card v-if="topThree[2]" class="bg-gradient-to-br from-white to-orange-50 dark:from-gray-800 dark:to-orange-900/20 shadow-md border-2 border-orange-400 dark:border-orange-600 transition-all duration-300 hover:shadow-xl hover:border-orange-500 dark:hover:border-orange-500 hover:-translate-y-0.5 cursor-pointer group overflow-hidden relative">
                            <div class="absolute inset-0 bg-gradient-to-br from-orange-200/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <CardContent class="p-2.5 flex flex-col items-center justify-center relative z-10">
                                <div class="mb-1.5 px-2 py-0.5 bg-orange-200 dark:bg-orange-800 rounded-full">
                                    <p class="text-[10px] font-black text-orange-700 dark:text-orange-200 tracking-wide">TOP PERFORMER</p>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-orange-400 to-orange-600 shadow-md group-hover:scale-110 transition-transform duration-300">
                                        <span class="text-base">👤</span>
                                    </div>
                                    <p class="text-xs font-black text-orange-900 dark:text-orange-200 text-center leading-tight">
                                        {{ currentRankingData[2]?.topPerformer || topThree[2].name }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- RIGHT: Rankings Table -->
                <Card class="bg-white dark:bg-gray-800 shadow-md border border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden">
                    <CardContent class="p-0 flex-1 overflow-auto">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-[#3B82F6] to-[#2563EB] sticky top-0 z-10 shadow-md">
                                <tr class="border-b-2 border-[#1D4ED8]">
                                    <th class="px-4 py-3 text-center text-sm font-bold text-white cursor-pointer hover:bg-white/10 transition-colors" @click="handleSort('rank')">
                                        <div class="flex items-center justify-center gap-1">
                                            <span>RANK</span>
                                            <span class="text-xs">{{ getSortIcon('rank') }}</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-white cursor-pointer hover:bg-white/10 transition-colors" @click="handleSort(currentFilters.rankingType === 'area' ? 'area' : 'line')">
                                        <div class="flex items-center gap-1">
                                            <span>{{ getDynamicColumnName() }}</span>
                                            <span class="text-xs">{{ getSortIcon(currentFilters.rankingType === 'area' ? 'area' : 'line') }}</span>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-sm font-bold text-white cursor-pointer hover:bg-white/10 transition-colors" @click="handleSort('inCharge')">
                                        <div class="flex items-center gap-1">
                                            <span>IN-CHARGE</span>
                                            <span class="text-xs">{{ getSortIcon('inCharge') }}</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-bold text-white cursor-pointer hover:bg-white/10 transition-colors" @click="handleSort('target')">
                                        <div class="flex items-center justify-end gap-1">
                                            <span>TARGET</span>
                                            <span class="text-xs">{{ getSortIcon('target') }}</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-right text-sm font-bold text-white cursor-pointer hover:bg-white/10 transition-colors" @click="handleSort('submitted')">
                                        <div class="flex items-center justify-end gap-1">
                                            <span>SUBMITTED</span>
                                            <span class="text-xs">{{ getSortIcon('submitted') }}</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-center text-sm font-bold text-white cursor-pointer hover:bg-white/10 transition-colors" @click="handleSort('achievement')">
                                        <div class="flex items-center justify-center gap-1">
                                            <span>ACHIEVEMENT</span>
                                            <span class="text-xs">{{ getSortIcon('achievement') }}</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="sortedRankingData.length === 0">
                                    <td colspan="6" class="p-12 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <span class="text-5xl">📭</span>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">No ranking data available</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr 
                                    v-for="item in sortedRankingData" 
                                    :key="item.rank"
                                    :class="[
                                        'hover:bg-[#7C3AED]/5 dark:hover:bg-[#7C3AED]/10 transition-all duration-200 cursor-pointer group border-l-4',
                                        getRowColor(item.rank),
                                        item.rank === 1 ? 'border-l-[#FFD700]' : item.rank === 2 ? 'border-l-gray-400' : item.rank === 3 ? 'border-l-[#CD7F32]' : 'border-l-transparent'
                                    ]"
                                >
                                    <td class="px-4 py-4 text-center">
                                        <div class="flex items-center justify-center">
                                            <span :class="[
                                                'inline-flex items-center justify-center w-10 h-10 rounded-full text-base font-bold transition-transform duration-200 group-hover:scale-110',
                                                getRankBadgeColor(item.rank)
                                            ]">
                                                {{ item.rank }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-2xl">{{ currentFilters.rankingType === 'area' ? '🏢' : '📍' }}</span>
                                            <span class="text-base font-bold text-gray-900 dark:text-white">
                                                {{ getDynamicColumnValue(item) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="Array.isArray(item.inCharge)" class="flex flex-wrap gap-1 max-w-md">
                                            <span v-for="(name, idx) in item.inCharge" :key="idx" class="inline-flex items-center gap-1 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded transition-all duration-200 hover:bg-gray-200 dark:hover:bg-gray-600">
                                                <span class="text-xs">👤</span>
                                                <span>{{ name }}</span>
                                            </span>
                                        </div>
                                        <div v-else class="flex items-center gap-2">
                                            <span class="text-base">👤</span>
                                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                {{ item.inCharge || '-' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="text-base font-bold text-gray-900 dark:text-white">
                                            {{ formatNumber(item.target) }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">M PCS</div>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="text-base font-bold text-[#059669]">
                                            {{ formatNumber(item.submitted) }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">M PCS</div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <button 
                                            @click="openModal(item)"
                                            :class="[
                                                'inline-flex items-center justify-center px-4 py-2 rounded-full text-base font-bold transition-all duration-200 hover:scale-110 cursor-pointer',
                                                getAchievementBadgeColor(item.achievement)
                                            ]"
                                        >
                                            {{ formatNumber(item.achievement) }}%
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Details Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click="closeModal">
                    <div 
                        class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl transform transition-all"
                        @click.stop
                    >
                        <!-- Modal Header -->
                        <div class="relative bg-gradient-to-r from-[#3B82F6] to-[#2563EB] rounded-t-2xl px-6 py-5 text-white">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                        <span class="text-2xl">{{ currentFilters.rankingType === 'area' ? '🏢' : '📍' }}</span>
                                    </div>
                                    <div>
                                        <h3 class="text-2xl font-black">{{ getItemName(selectedItem!) }}</h3>
                                        <p class="text-sm text-blue-100">Performance Details</p>
                                    </div>
                                </div>
                                <button 
                                    @click="closeModal"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 hover:bg-white/20 transition-colors duration-200"
                                >
                                    <span class="text-2xl">✕</span>
                                </button>
                            </div>
                        </div>

                        <!-- Modal Body -->
                        <div v-if="selectedItem" class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                            <!-- Achievement Overview -->
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Rank Badge -->
                                <div class="col-span-2 flex justify-center">
                                    <div :class="[
                                        'inline-flex items-center justify-center px-8 py-4 rounded-2xl text-4xl font-black shadow-lg',
                                        getRankBadgeColor(selectedItem.rank)
                                    ]">
                                        <span class="mr-3 text-3xl">🏆</span>
                                        Rank #{{ selectedItem.rank }}
                                    </div>
                                </div>

                                <!-- Achievement Percentage -->
                                <div class="col-span-2">
                                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 text-center border-2 border-blue-200 dark:border-blue-700">
                                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Achievement Rate</p>
                                        <p :class="[
                                            'text-5xl font-black mb-2',
                                            selectedItem.achievement >= 100 ? 'text-green-600 dark:text-green-400' : 
                                            selectedItem.achievement >= 90 ? 'text-blue-600 dark:text-blue-400' : 
                                            selectedItem.achievement >= 75 ? 'text-orange-600 dark:text-orange-400' : 
                                            'text-red-600 dark:text-red-400'
                                        ]">
                                            {{ formatNumber(selectedItem.achievement) }}%
                                        </p>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                            <div 
                                                :class="[
                                                    'h-full rounded-full transition-all duration-500',
                                                    selectedItem.achievement >= 100 ? 'bg-gradient-to-r from-green-500 to-green-600' : 
                                                    selectedItem.achievement >= 90 ? 'bg-gradient-to-r from-blue-500 to-blue-600' : 
                                                    selectedItem.achievement >= 75 ? 'bg-gradient-to-r from-orange-500 to-orange-600' : 
                                                    'bg-gradient-to-r from-red-500 to-red-600'
                                                ]"
                                                :style="{ width: Math.min(selectedItem.achievement, 100) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Key Metrics Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Target Capacity -->
                                <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-xl p-4 border border-orange-200 dark:border-orange-700">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-500 text-white">
                                            <span class="text-xl">🎯</span>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Target Capacity</p>
                                    </div>
                                    <p class="text-3xl font-black text-orange-700 dark:text-orange-300">{{ formatNumber(selectedItem.target) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Million PCS</p>
                                </div>

                                <!-- Endtime -->
                                <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-4 border border-purple-200 dark:border-purple-700">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-500 text-white">
                                            <span class="text-xl">⏱️</span>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Total Endtime</p>
                                    </div>
                                    <p class="text-3xl font-black text-purple-700 dark:text-purple-300">{{ formatNumber(selectedItem.endtime) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Million PCS</p>
                                </div>

                                <!-- Submitted -->
                                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-4 border border-green-200 dark:border-green-700">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-500 text-white">
                                            <span class="text-xl">✅</span>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Submitted Lots</p>
                                    </div>
                                    <p class="text-3xl font-black text-green-700 dark:text-green-300">{{ formatNumber(selectedItem.submitted) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Million PCS</p>
                                </div>

                                <!-- Lot Count -->
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500 text-white">
                                            <span class="text-xl">📦</span>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Total Lots</p>
                                    </div>
                                    <p class="text-3xl font-black text-blue-700 dark:text-blue-300">{{ selectedItem.lotCount }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Lots Processed</p>
                                </div>
                            </div>

                            <!-- In-Charge Section with Table -->
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white">
                                        <span class="text-xl">👥</span>
                                    </div>
                                    <h4 class="text-lg font-black text-gray-800 dark:text-gray-200">Personnel In-Charge</h4>
                                </div>
                                
                                <!-- Operator Stats Table -->
                                <div v-if="selectedItem.operatorStats && selectedItem.operatorStats.length > 0" class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-600">
                                    <table class="w-full">
                                        <thead class="bg-gradient-to-r from-blue-500 to-indigo-600">
                                            <tr>
                                                <th class="px-4 py-2.5 text-left text-xs font-bold text-white uppercase tracking-wider">Operator</th>
                                                <th class="px-4 py-2.5 text-right text-xs font-bold text-white uppercase tracking-wider">Target</th>
                                                <th class="px-4 py-2.5 text-right text-xs font-bold text-white uppercase tracking-wider">Submitted</th>
                                                <th class="px-4 py-2.5 text-center text-xs font-bold text-white uppercase tracking-wider">Achievement</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600 bg-white dark:bg-gray-700">
                                            <tr 
                                                v-for="(operator, idx) in selectedItem.operatorStats" 
                                                :key="idx"
                                                :class="[
                                                    'transition-colors hover:bg-gray-50 dark:hover:bg-gray-600',
                                                    idx === 0 ? 'bg-yellow-50/50 dark:bg-yellow-900/20' : ''
                                                ]"
                                            >
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <div :class="[
                                                            'flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                                                            idx === 0 ? 'bg-gradient-to-br from-yellow-400 to-orange-500 text-white' : 
                                                            idx === 1 ? 'bg-gradient-to-br from-gray-300 to-gray-400 text-gray-800' : 
                                                            idx === 2 ? 'bg-gradient-to-br from-orange-300 to-orange-500 text-white' : 
                                                            'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300'
                                                        ]">
                                                            {{ idx + 1 }}
                                                        </div>
                                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ operator.name }}</span>
                                                        <span v-if="idx === 0" class="text-sm">👑</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ formatNumber(operator.target) }}</span>
                                                    <span class="text-xs text-gray-400 ml-1">M</span>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ formatNumber(operator.submitted) }}</span>
                                                    <span class="text-xs text-gray-400 ml-1">M</span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span :class="[
                                                        'inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-bold',
                                                        operator.achievement >= 100 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 
                                                        operator.achievement >= 90 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 
                                                        operator.achievement >= 75 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' : 
                                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                                    ]">
                                                        {{ operator.achievement.toFixed(1) }}%
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Fallback for no operator stats -->
                                <div v-else-if="Array.isArray(selectedItem.inCharge)" class="flex flex-wrap gap-2">
                                    <div 
                                        v-for="(name, idx) in selectedItem.inCharge" 
                                        :key="idx"
                                        class="inline-flex items-center gap-2 bg-white dark:bg-gray-700 px-4 py-2 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600"
                                    >
                                        <span class="text-lg">👤</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ name }}</span>
                                    </div>
                                </div>
                                <div v-else class="inline-flex items-center gap-2 bg-white dark:bg-gray-700 px-4 py-2 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600">
                                    <span class="text-lg">👤</span>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ selectedItem.inCharge || 'Not Assigned' }}</span>
                                </div>
                            </div>

                            <!-- Machine Count (if available) -->
                            <div v-if="selectedItem.machineCount" class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 rounded-xl p-5 border border-indigo-200 dark:border-indigo-700">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-500 text-white">
                                            <span class="text-xl">⚙️</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Machine Count</p>
                                            <p class="text-2xl font-black text-indigo-700 dark:text-indigo-300">{{ selectedItem.machineCount }} Machines</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-900 rounded-b-2xl">
                            <button 
                                @click="closeModal"
                                class="w-full bg-gradient-to-r from-[#3B82F6] to-[#2563EB] hover:from-[#2563EB] hover:to-[#1D4ED8] text-white font-bold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.6s ease-out forwards;
    opacity: 0;
}

/* Modal Transitions */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-active > div,
.modal-leave-active > div {
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: scale(0.9);
    opacity: 0;
}
</style>
