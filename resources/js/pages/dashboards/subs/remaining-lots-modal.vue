<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface RemainingLot {
    eqp_line: string;
    eqp_area: string;
    eqp_1: string;
    lot_id: string;
    model_15: string;
    lot_qty: number;
    work_type: string;
    est_endtime: string;
}

interface Props {
    open: boolean;
    title: string;
    remainingLots: RemainingLot[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'toggle-previous-date', value: boolean): void;
}>();

const handleClose = () => {
    emit('update:open', false);
};

// State for include previous date toggle
const includePreviousDate = ref(false);

// Sorting state per line
type SortField = 'eqp_area' | 'eqp_1' | 'lot_id' | 'model_15' | 'lot_qty' | 'work_type' | 'est_endtime';
type SortOrder = 'asc' | 'desc';
const sortConfig = ref<Record<string, { field: SortField; order: SortOrder }>>({});

// Reset toggle and sorting when modal opens
watch(() => props.open, (newValue) => {
    if (newValue) {
        includePreviousDate.value = false;
        sortConfig.value = {};
    }
});

const handleTogglePreviousDate = () => {
    emit('toggle-previous-date', includePreviousDate.value);
};

// Format quantity with thousand separators
const formatQuantity = (qty: number): string => {
    return qty.toLocaleString();
};

// Format endtime to display date, hour, minutes
const formatEndtime = (endtime: string): string => {
    if (!endtime) return '-';
    const date = new Date(endtime);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day} ${hours}:${minutes}`;
};

// Sort lots within a line
const sortLots = (line: string, field: SortField) => {
    const currentSort = sortConfig.value[line];
    if (currentSort?.field === field) {
        sortConfig.value[line] = { field, order: currentSort.order === 'asc' ? 'desc' : 'asc' };
    } else {
        sortConfig.value[line] = { field, order: 'asc' };
    }
};

// Group remaining lots by line
const groupedLots = computed(() => {
    const groups: Record<string, RemainingLot[]> = {};
    props.remainingLots.forEach(lot => {
        if (!groups[lot.eqp_line]) {
            groups[lot.eqp_line] = [];
        }
        groups[lot.eqp_line].push(lot);
    });
    
    // Apply sorting to each group
    Object.keys(groups).forEach(line => {
        const sort = sortConfig.value[line];
        if (sort) {
            groups[line].sort((a, b) => {
                let aVal: any = a[sort.field];
                let bVal: any = b[sort.field];
                
                // Handle date comparison for est_endtime
                if (sort.field === 'est_endtime') {
                    aVal = new Date(aVal).getTime();
                    bVal = new Date(bVal).getTime();
                }
                
                // Handle numeric comparison for lot_qty
                if (sort.field === 'lot_qty') {
                    aVal = Number(aVal);
                    bVal = Number(bVal);
                }
                
                // String comparison for others
                if (typeof aVal === 'string') {
                    aVal = aVal.toLowerCase();
                    bVal = bVal.toLowerCase();
                }
                
                if (aVal < bVal) return sort.order === 'asc' ? -1 : 1;
                if (aVal > bVal) return sort.order === 'asc' ? 1 : -1;
                return 0;
            });
        }
    });
    
    return groups;
});

const sortedLines = computed(() => {
    return Object.keys(groupedLots.value).sort();
});

// Calculate subtotal quantity per line
const getLineSubtotal = (line: string): number => {
    return groupedLots.value[line].reduce((sum, lot) => sum + lot.lot_qty, 0);
};

// Check if endtime has lapsed (past current time)
const isEndtimeLapsed = (endtime: string): boolean => {
    if (!endtime) return false;
    const now = new Date();
    const endDate = new Date(endtime);
    return endDate < now;
};

// Get endtime badge color based on lapsed status
const getEndtimeBadgeColor = (endtime: string): string => {
    if (isEndtimeLapsed(endtime)) {
        // Lapsed - Red/Danger color
        return 'bg-[#FF6757]/10 text-[#FF6757] dark:text-[#FF6757] border-[#FF6757]/30';
    } else {
        // Not lapsed - Green/Success color
        return 'bg-[#32D484]/10 text-[#32D484] dark:text-[#32D484] border-[#32D484]/30';
    }
};

// Get sort icon for column
const getSortIcon = (line: string, field: SortField): string => {
    const sort = sortConfig.value[line];
    if (sort?.field === field) {
        return sort.order === 'asc' ? '↑' : '↓';
    }
    return '↕';
};

// Export to CSV functionality
const handleExportCSV = () => {
    // Prepare CSV headers
    const headers = ['Line', 'Area', 'MC No.', 'Lot No', 'Model', 'Quantity', 'WorkType', 'Endtime'];
    
    // Prepare CSV rows
    const rows: string[][] = [];
    sortedLines.value.forEach(line => {
        groupedLots.value[line].forEach(lot => {
            rows.push([
                lot.eqp_line,
                lot.eqp_area,
                lot.eqp_1,
                lot.lot_id,
                lot.model_15 || '-',
                lot.lot_qty.toString(),
                lot.work_type,
                formatEndtime(lot.est_endtime)
            ]);
        });
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
    
    link.setAttribute('href', url);
    link.setAttribute('download', `remaining-lots-${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent 
            class="!max-w-[95vw] !w-[1000px] max-h-[85vh] overflow-hidden flex flex-col p-0 gap-0"
        >
            <!-- Header with gradient background -->
            <DialogHeader class="px-5 py-3 border-b border-border bg-gradient-to-r from-muted/50 to-muted/30 dark:from-gray-800/50 dark:to-gray-900/30">
                <DialogTitle class="flex items-center justify-between pr-8">
                    <div class="flex items-center gap-2.5">
                        <div 
                            class="w-8 h-8 rounded-lg flex items-center justify-center shadow-sm bg-pink-500/20"
                        >
                            <span class="text-lg">📜</span>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-foreground">{{ title }}</h2>
                            <p class="text-[10px] text-muted-foreground mt-0.5">
                                Ongoing lots pending submission
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <!-- Include Previous Date Toggle -->
                        <div class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm">
                            <span class="text-xs font-medium text-muted-foreground whitespace-nowrap">Include Previous Date</span>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input 
                                    type="checkbox" 
                                    v-model="includePreviousDate"
                                    @change="handleTogglePreviousDate"
                                    class="peer sr-only" 
                                />
                                <div class="peer h-5 w-9 rounded-full bg-gray-300 dark:bg-gray-600 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 dark:after:border-gray-600 after:bg-white dark:after:bg-gray-400 after:transition-all after:shadow-md peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-checked:after:bg-white"></div>
                            </label>
                        </div>
                        <!-- Export CSV Button -->
                        <Button 
                            @click="handleExportCSV"
                            size="sm"
                            variant="outline"
                            class="gap-1.5"
                        >
                            <span class="text-sm">📥</span>
                            <span class="text-xs font-medium">Export CSV</span>
                        </Button>
                        <!-- Total Badge -->
                        <Badge 
                            class="bg-pink-500 hover:bg-pink-500/90 text-white text-xs font-semibold px-2.5 py-1 shadow-md"
                        >
                            Total: {{ remainingLots.length }}
                        </Badge>
                    </div>
                </DialogTitle>
                <DialogDescription class="sr-only">
                    List of ongoing production lots that are pending submission for the selected production date
                </DialogDescription>
            </DialogHeader>
            
            <!-- Content area with padding -->
            <div class="flex-1 overflow-y-auto px-4 py-3 bg-background">
                <div v-if="remainingLots.length === 0" class="flex flex-col items-center justify-center py-16 text-muted-foreground">
                    <div class="w-16 h-16 rounded-full bg-muted/50 flex items-center justify-center mb-4">
                        <span class="text-3xl">📭</span>
                    </div>
                    <p class="text-lg font-medium">No remaining lots found</p>
                    <p class="text-sm mt-1">All lots have been submitted</p>
                </div>
                
                <div v-else class="space-y-4">
                    <div 
                        v-for="line in sortedLines" 
                        :key="line" 
                        class="border-2 border-border rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 bg-card"
                    >
                        <!-- Line header with enhanced styling -->
                        <div class="bg-gradient-to-r from-primary/10 to-primary/5 dark:from-primary/20 dark:to-primary/10 px-4 py-2.5 flex items-center justify-between border-b-2 border-border">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-primary/20 dark:bg-primary/30 flex items-center justify-center">
                                    <span class="text-xs font-bold text-primary">{{ line }}</span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-foreground">Line {{ line }}</h3>
                                    <p class="text-[10px] text-muted-foreground">Production Line</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <!-- Sort Controls -->
                                <div class="flex items-center gap-1 px-2 py-1 rounded-lg bg-background/50 border border-border/50 print:hidden">
                                    <span class="text-[9px] text-muted-foreground font-medium uppercase tracking-wide">Sort:</span>
                                    <button 
                                        @click="sortLots(line, 'lot_qty')"
                                        class="px-1.5 py-0.5 text-[9px] font-medium rounded hover:bg-primary/10 transition-colors"
                                        :class="sortConfig[line]?.field === 'lot_qty' ? 'bg-primary/20 text-primary' : 'text-muted-foreground'"
                                    >
                                        Qty {{ getSortIcon(line, 'lot_qty') }}
                                    </button>
                                    <button 
                                        @click="sortLots(line, 'est_endtime')"
                                        class="px-1.5 py-0.5 text-[9px] font-medium rounded hover:bg-primary/10 transition-colors"
                                        :class="sortConfig[line]?.field === 'est_endtime' ? 'bg-primary/20 text-primary' : 'text-muted-foreground'"
                                    >
                                        Time {{ getSortIcon(line, 'est_endtime') }}
                                    </button>
                                    <button 
                                        @click="sortLots(line, 'work_type')"
                                        class="px-1.5 py-0.5 text-[9px] font-medium rounded hover:bg-primary/10 transition-colors"
                                        :class="sortConfig[line]?.field === 'work_type' ? 'bg-primary/20 text-primary' : 'text-muted-foreground'"
                                    >
                                        Type {{ getSortIcon(line, 'work_type') }}
                                    </button>
                                </div>
                                <Badge variant="outline" class="text-xs font-semibold px-2.5 py-1 bg-background">
                                    {{ groupedLots[line].length }} {{ groupedLots[line].length === 1 ? 'lot' : 'lots' }}
                                </Badge>
                                <Badge variant="secondary" class="text-xs font-mono font-semibold px-2.5 py-1 bg-[#00C9FF]/10 text-[#00C9FF] dark:text-[#00C9FF] border border-[#00C9FF]/30">
                                    {{ formatQuantity(getLineSubtotal(line)) }} PCS
                                </Badge>
                            </div>
                        </div>
                        
                        <!-- Table with enhanced styling -->
                        <div>
                            <table class="w-full text-sm table-fixed">
                                <thead class="bg-muted/50 dark:bg-gray-800/50">
                                    <tr class="border-b-2 border-border">
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 5%;">#</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 8%;">Line</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 8%;">Area</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 10%;">MC No.</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 12%;">Lot No</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 12%;">Model</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 11%;">Quantity</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 13%;">WorkType</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 21%;">Endtime</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr 
                                        v-for="(lot, index) in groupedLots[line]" 
                                        :key="lot.lot_id"
                                        class="hover:bg-muted/30 dark:hover:bg-gray-800/30 transition-colors duration-150"
                                    >
                                        <td class="px-2 py-2 text-center text-muted-foreground text-[11px] font-medium">{{ index + 1 }}</td>
                                        <td class="px-2 py-2 text-center font-bold text-foreground text-sm">{{ lot.eqp_line }}</td>
                                        <td class="px-2 py-2 text-center font-medium text-foreground text-sm">{{ lot.eqp_area }}</td>
                                        <td class="px-2 py-2 text-center font-mono font-bold text-foreground text-sm">{{ lot.eqp_1 }}</td>
                                        <td class="px-2 py-2 text-center">
                                            <Badge variant="secondary" class="font-mono text-[10px] font-semibold bg-primary/10 text-primary border border-primary/20 px-1.5 py-0.5">
                                                {{ lot.lot_id }}
                                            </Badge>
                                        </td>
                                        <td class="px-2 py-2 text-center font-medium text-foreground text-[11px]">
                                            {{ lot.model_15 || '-' }}
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <Badge variant="outline" class="text-[10px] font-semibold border px-1.5 py-0.5 font-mono">
                                                {{ formatQuantity(lot.lot_qty) }}
                                            </Badge>
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <Badge 
                                                variant="secondary" 
                                                class="text-[10px] font-semibold px-1.5 py-0.5"
                                                :class="{
                                                    'bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30': lot.work_type === 'NORMAL',
                                                    'bg-orange-500/20 text-orange-700 dark:text-orange-300 border border-orange-500/30': lot.work_type === 'PROCESS RW',
                                                    'bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30': lot.work_type === 'WH REWORK',
                                                    'bg-teal-500/20 text-teal-700 dark:text-teal-300 border border-teal-500/30': lot.work_type === 'OI REWORK'
                                                }"
                                            >
                                                {{ lot.work_type }}
                                            </Badge>
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <Badge 
                                                variant="secondary"
                                                class="text-[10px] font-mono font-semibold border px-1.5 py-0.5"
                                                :class="getEndtimeBadgeColor(lot.est_endtime)"
                                            >
                                                ⏱️ {{ formatEndtime(lot.est_endtime) }}
                                            </Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer with summary -->
            <div class="px-5 py-2.5 border-t border-border bg-muted/30 dark:bg-gray-800/30">
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Showing {{ remainingLots.length }} remaining lots across {{ sortedLines.length }} {{ sortedLines.length === 1 ? 'line' : 'lines' }}</span>
                    <span class="font-medium">🔄 Ongoing</span>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

