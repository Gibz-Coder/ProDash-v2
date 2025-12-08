<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface Equipment {
    eqp_line: string;
    eqp_area: string;
    eqp_no: string;
    size: string;
    alloc_type: string;
    ongoing_lot?: string;
    updated_at: string;
}

interface Props {
    open: boolean;
    title: string;
    equipmentList: Equipment[];
    type: 'with' | 'without';
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const handleClose = () => {
    emit('update:open', false);
};

// Sorting state per line
type SortField = 'eqp_area' | 'eqp_no' | 'size' | 'alloc_type' | 'ongoing_lot' | 'updated_at';
type SortOrder = 'asc' | 'desc';
const sortConfig = ref<Record<string, { field: SortField; order: SortOrder }>>({});

// Reset sorting when modal opens
watch(() => props.open, (newValue) => {
    if (newValue) {
        sortConfig.value = {};
    }
});

// Format size display
const formatSize = (size: string): string => {
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

// Calculate elapsed time
const calculateElapsedTime = (updatedAt: string): { text: string; totalHours: number } => {
    const now = new Date();
    const updated = new Date(updatedAt);
    const diffMs = now.getTime() - updated.getTime();
    
    const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
    
    const totalHours = days * 24 + hours;
    
    let text = '';
    if (days > 0) {
        text = `${days}d ${hours}h ${minutes}m`;
    } else if (hours > 0) {
        text = `${hours}h ${minutes}m`;
    } else {
        text = `${minutes}m`;
    }
    
    return { text, totalHours };
};

// Get elapsed time badge color
const getElapsedTimeColor = (totalHours: number): string => {
    if (totalHours >= 1) {
        return 'bg-red-500/20 text-red-700 dark:text-red-300 border-red-500/40';
    } else {
        return 'bg-orange-500/20 text-orange-700 dark:text-orange-300 border-orange-500/40';
    }
};

// Sort equipment within a line
const sortEquipment = (line: string, field: SortField) => {
    const currentSort = sortConfig.value[line];
    if (currentSort?.field === field) {
        sortConfig.value[line] = { field, order: currentSort.order === 'asc' ? 'desc' : 'asc' };
    } else {
        sortConfig.value[line] = { field, order: 'asc' };
    }
};

// Group equipment by line
const groupedEquipment = computed(() => {
    const groups: Record<string, Equipment[]> = {};
    props.equipmentList.forEach(eq => {
        if (!groups[eq.eqp_line]) {
            groups[eq.eqp_line] = [];
        }
        groups[eq.eqp_line].push(eq);
    });
    
    // Apply sorting to each group
    Object.keys(groups).forEach(line => {
        const sort = sortConfig.value[line];
        if (sort) {
            groups[line].sort((a, b) => {
                let aVal: any = a[sort.field];
                let bVal: any = b[sort.field];
                
                // Handle date comparison for updated_at
                if (sort.field === 'updated_at') {
                    aVal = new Date(aVal).getTime();
                    bVal = new Date(bVal).getTime();
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
    return Object.keys(groupedEquipment.value).sort();
});

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
    const headers = props.type === 'with' 
        ? ['Line', 'Area', 'MC No.', 'Size', 'WorkType', 'Ongoing Lot', 'Elapsed Time']
        : ['Line', 'Area', 'MC No.', 'Size', 'WorkType', 'Elapsed Time'];
    
    // Prepare CSV rows
    const rows: string[][] = [];
    sortedLines.value.forEach(line => {
        groupedEquipment.value[line].forEach(equipment => {
            const elapsedTime = calculateElapsedTime(equipment.updated_at);
            const row = [
                equipment.eqp_line,
                equipment.eqp_area,
                equipment.eqp_no,
                formatSize(equipment.size),
                equipment.alloc_type,
            ];
            
            if (props.type === 'with' && equipment.ongoing_lot) {
                row.push(equipment.ongoing_lot);
            }
            
            row.push(elapsedTime.text);
            rows.push(row);
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
    
    const filename = props.type === 'with' 
        ? `equipment-with-lot-${new Date().toISOString().split('T')[0]}.csv`
        : `equipment-without-lot-${new Date().toISOString().split('T')[0]}.csv`;
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent 
            class="!max-w-[90vw] !w-[800px] max-h-[85vh] overflow-hidden flex flex-col p-0 gap-0"
        >
            <!-- Header with gradient background -->
            <DialogHeader class="px-5 py-3 border-b border-border bg-gradient-to-r from-muted/50 to-muted/30 dark:from-gray-800/50 dark:to-gray-900/30">
                <DialogTitle class="flex items-center justify-between pr-8">
                    <div class="flex items-center gap-2.5">
                        <div 
                            class="w-8 h-8 rounded-lg flex items-center justify-center shadow-sm"
                            :class="type === 'with' ? 'bg-[#32D484]/20' : 'bg-[#FF6757]/20'"
                        >
                            <span class="text-lg">{{ type === 'with' ? '✓' : '○' }}</span>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-foreground">{{ title }}</h2>
                            <p class="text-[10px] text-muted-foreground mt-0.5">
                                {{ type === 'with' ? 'Running production' : 'Available for production' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5">
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
                        <Badge 
                            :class="type === 'with' ? 'bg-[#32D484] hover:bg-[#32D484]/90 text-white' : 'bg-[#FF6757] hover:bg-[#FF6757]/90 text-white'"
                            class="text-xs font-semibold px-2.5 py-1 shadow-md"
                        >
                            Total: {{ equipmentList.length }}
                        </Badge>
                    </div>
                </DialogTitle>
                <DialogDescription class="sr-only">
                    {{ type === 'with' ? 'List of equipment currently running production with ongoing lots' : 'List of equipment available for production without ongoing lots' }}
                </DialogDescription>
            </DialogHeader>
            
            <!-- Content area with padding -->
            <div class="flex-1 overflow-y-auto px-4 py-3 bg-background">
                <div v-if="equipmentList.length === 0" class="flex flex-col items-center justify-center py-16 text-muted-foreground">
                    <div class="w-16 h-16 rounded-full bg-muted/50 flex items-center justify-center mb-4">
                        <span class="text-3xl">📭</span>
                    </div>
                    <p class="text-lg font-medium">No equipment found</p>
                    <p class="text-sm mt-1">Try adjusting your filters</p>
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
                                        @click="sortEquipment(line, 'alloc_type')"
                                        class="px-1.5 py-0.5 text-[9px] font-medium rounded hover:bg-primary/10 transition-colors"
                                        :class="sortConfig[line]?.field === 'alloc_type' ? 'bg-primary/20 text-primary' : 'text-muted-foreground'"
                                    >
                                        Type {{ getSortIcon(line, 'alloc_type') }}
                                    </button>
                                    <button 
                                        @click="sortEquipment(line, 'size')"
                                        class="px-1.5 py-0.5 text-[9px] font-medium rounded hover:bg-primary/10 transition-colors"
                                        :class="sortConfig[line]?.field === 'size' ? 'bg-primary/20 text-primary' : 'text-muted-foreground'"
                                    >
                                        Size {{ getSortIcon(line, 'size') }}
                                    </button>
                                    <button 
                                        @click="sortEquipment(line, 'updated_at')"
                                        class="px-1.5 py-0.5 text-[9px] font-medium rounded hover:bg-primary/10 transition-colors"
                                        :class="sortConfig[line]?.field === 'updated_at' ? 'bg-primary/20 text-primary' : 'text-muted-foreground'"
                                    >
                                        Time {{ getSortIcon(line, 'updated_at') }}
                                    </button>
                                </div>
                                <Badge variant="outline" class="text-[10px] font-semibold px-2 py-0.5 bg-background">
                                    {{ groupedEquipment[line].length }} {{ groupedEquipment[line].length === 1 ? 'machine' : 'machines' }}
                                </Badge>
                            </div>
                        </div>
                        
                        <!-- Table with enhanced styling -->
                        <div>
                            <table class="w-full text-sm table-fixed">
                                <thead class="bg-muted/50 dark:bg-gray-800/50">
                                    <tr class="border-b-2 border-border">
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 8%;">#</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 12%;">Line</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 10%;">Area</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 12%;">MC No.</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 10%;">Size</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 14%;">Allocation</th>
                                        <th v-if="type === 'with'" class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" style="width: 14%;">Ongoing Lot</th>
                                        <th class="px-2 py-2.5 text-center font-bold text-[10px] uppercase tracking-wide text-muted-foreground" :style="type === 'with' ? 'width: 26%;' : 'width: 44%;'">Elapsed Time</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <tr 
                                        v-for="(equipment, index) in groupedEquipment[line]" 
                                        :key="equipment.eqp_no"
                                        class="hover:bg-muted/30 dark:hover:bg-gray-800/30 transition-colors duration-150"
                                    >
                                        <td class="px-2 py-2 text-center text-muted-foreground text-[11px] font-medium">{{ index + 1 }}</td>
                                        <td class="px-2 py-2 text-center font-bold text-foreground text-sm">{{ equipment.eqp_line }}</td>
                                        <td class="px-2 py-2 text-center font-medium text-foreground text-sm">{{ equipment.eqp_area }}</td>
                                        <td class="px-2 py-2 text-center font-mono font-bold text-foreground text-sm">{{ equipment.eqp_no }}</td>
                                        <td class="px-2 py-2 text-center">
                                            <Badge variant="outline" class="text-[10px] font-semibold border px-1.5 py-0.5">
                                                {{ formatSize(equipment.size) }}
                                            </Badge>
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <Badge 
                                                variant="secondary" 
                                                class="text-[10px] font-semibold px-1.5 py-0.5"
                                                :class="{
                                                    'bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30': equipment.alloc_type === 'NORMAL',
                                                    'bg-orange-500/20 text-orange-700 dark:text-orange-300 border border-orange-500/30': equipment.alloc_type === 'PROCESS RW',
                                                    'bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/30': equipment.alloc_type === 'WH REWORK',
                                                    'bg-teal-500/20 text-teal-700 dark:text-teal-300 border border-teal-500/30': equipment.alloc_type === 'OI REWORK'
                                                }"
                                            >
                                                {{ equipment.alloc_type }}
                                            </Badge>
                                        </td>
                                        <td v-if="type === 'with'" class="px-2 py-2 text-center">
                                            <Badge variant="secondary" class="font-mono text-[10px] font-semibold bg-primary/10 text-primary border border-primary/20 px-1.5 py-0.5">
                                                {{ equipment.ongoing_lot }}
                                            </Badge>
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <Badge 
                                                variant="secondary"
                                                class="text-[10px] font-mono font-semibold border px-1.5 py-0.5"
                                                :class="getElapsedTimeColor(calculateElapsedTime(equipment.updated_at).totalHours)"
                                            >
                                                ⏱️ {{ calculateElapsedTime(equipment.updated_at).text }}
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
                    <span>Showing {{ equipmentList.length }} equipment across {{ sortedLines.length }} {{ sortedLines.length === 1 ? 'line' : 'lines' }}</span>
                    <span class="font-medium">{{ type === 'with' ? '🟢 Active' : '⛔ Idle' }}</span>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

