<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ChevronDown, ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next';

interface Lot {
    lot_no: string;
    lot_model: string;
    lot_qty: number;
    stagnant_tat: number;
    work_type: string;
    wip_status: string;
    auto_yn: string;
    lipas_yn: string;
    lot_location: string;
    lot_size: string;
}

interface Props {
    open: boolean;
    equipmentNo: string | null;
    previousModel: string | null;
    previousWorktype: string | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'assign', lot: Lot): void;
}>();

const isLoading = ref(false);
const availableLots = ref<Lot[]>([]);
const modelFilter = ref('');

// Sorting state
const sortColumn = ref<string | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');

// Filter options
const wipStatusFilter = ref('Newlot Standby');
const worktypeFilter = ref('NORMAL');
const wipStatusOptions = ref<string[]>([]);
const worktypeOptions = ref<string[]>([]);

const handleClose = () => {
    emit('update:open', false);
};

const fetchFilterOptions = async () => {
    try {
        const response = await fetch('/api/mems/filter-options');
        if (!response.ok) throw new Error('Failed to fetch filter options');
        
        const result = await response.json();
        wipStatusOptions.value = result.wip_statuses || [];
        worktypeOptions.value = result.work_types || [];
    } catch (error) {
        console.error('Error fetching filter options:', error);
    }
};

const fetchAvailableLots = async () => {
    if (!props.open) return;
    
    isLoading.value = true;
    try {
        const params = new URLSearchParams();
        
        // Apply filters
        if (wipStatusFilter.value && wipStatusFilter.value !== 'ALL') {
            params.append('wip_status', wipStatusFilter.value);
        }
        
        if (worktypeFilter.value && worktypeFilter.value !== 'ALL') {
            params.append('work_type', worktypeFilter.value);
        }
        
        // Filter by model if user has entered one
        if (modelFilter.value.trim()) {
            params.append('model', modelFilter.value.trim());
        }
        
        const response = await fetch(`/api/mems/available-lots?${params}`);
        
        if (!response.ok) {
            throw new Error('Failed to fetch available lots');
        }
        
        const result = await response.json();
        availableLots.value = result.data || [];
    } catch (error) {
        console.error('Error fetching available lots:', error);
        availableLots.value = [];
    } finally {
        isLoading.value = false;
    }
};

const handleAssign = (lot: Lot) => {
    // Show notification that this feature is under development
    showToast('⚠️ This feature is under development', 'warning');
    
    // Uncomment when ready to implement
    // emit('assign', lot);
    // handleClose();
};

const showToast = (message: string, type: 'success' | 'danger' | 'info' | 'warning' = 'info') => {
    const toast = document.createElement('div');
    
    const bgColors = {
        success: 'bg-green-600',
        danger: 'bg-red-600',
        info: 'bg-blue-600',
        warning: 'bg-amber-600',
    };
    toast.className = `fixed top-4 right-4 ${bgColors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-[9999] transition-opacity duration-300`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

const handleSearch = () => {
    fetchAvailableLots();
};

const handleSort = (column: string) => {
    if (sortColumn.value === column) {
        // Toggle direction if same column
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        // New column, default to ascending
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
};

const sortedLots = computed(() => {
    if (!sortColumn.value) return availableLots.value;
    
    const sorted = [...availableLots.value].sort((a, b) => {
        let aVal: any = a[sortColumn.value as keyof Lot];
        let bVal: any = b[sortColumn.value as keyof Lot];
        
        // Handle numeric columns
        if (sortColumn.value === 'lot_qty' || sortColumn.value === 'stagnant_tat') {
            aVal = Number(aVal) || 0;
            bVal = Number(bVal) || 0;
        } else {
            // Handle string columns
            aVal = String(aVal || '').toLowerCase();
            bVal = String(bVal || '').toLowerCase();
        }
        
        if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
    
    return sorted;
});

const formatNumber = (value: number): string => {
    return new Intl.NumberFormat().format(value);
};

const getSizeLabel = (size: string): string => {
    const mapping: Record<string, string> = {
        '03': '0603', '05': '1005', '10': '1608',
        '21': '2012', '31': '3216', '32': '3225',
    };
    return mapping[size] || size;
};

// Watch for modal open to fetch data
watch(() => props.open, (newValue) => {
    if (newValue) {
        // Set default model filter to previous model if available
        modelFilter.value = props.previousModel || '';
        
        // Set default worktype if available
        if (props.previousWorktype) {
            worktypeFilter.value = props.previousWorktype;
        } else {
            worktypeFilter.value = 'NORMAL';
        }
        
        fetchAvailableLots();
    } else {
        modelFilter.value = '';
        wipStatusFilter.value = 'Newlot Standby';
        worktypeFilter.value = 'NORMAL';
    }
});

// Fetch filter options on mount
onMounted(() => {
    fetchFilterOptions();
});
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="!max-w-[1200px] max-h-[90vh] overflow-hidden flex flex-col p-0 gap-0">
            <!-- Header -->
            <DialogHeader class="px-5 py-3 border-b border-border bg-gradient-to-r from-blue-50 to-blue-100/50 dark:from-blue-950/30 dark:to-blue-900/20">
                <DialogTitle class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                        <span class="text-xl">📦</span>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-foreground">Assign Lot</h2>
                        <p class="text-xs text-muted-foreground mt-0.5">Equipment: {{ equipmentNo }}</p>
                    </div>
                </DialogTitle>
                <DialogDescription class="sr-only">Assign a lot to equipment</DialogDescription>
            </DialogHeader>

            <!-- Filter Section -->
            <div class="px-5 py-3 bg-blue-50/50 dark:bg-blue-900/20 border-b border-border">
                <div class="grid grid-cols-12 gap-3">
                    <!-- Model Filter -->
                    <div class="col-span-5 flex items-center gap-2">
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400 w-16 shrink-0">Model:</label>
                        <Input
                            v-model="modelFilter"
                            type="text"
                            placeholder="Enter model (e.g., CL21A2106KPQNFNB)"
                            class="flex-1 h-9"
                            @keyup.enter="handleSearch"
                        />
                    </div>
                    
                    <!-- WIP Status Filter -->
                    <div class="col-span-3 flex items-center gap-2">
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400 w-20 shrink-0">WIP Status:</label>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" size="sm" class="flex-1 justify-between h-9 text-xs">
                                    <span class="truncate">{{ wipStatusFilter }}</span>
                                    <ChevronDown class="ml-2 h-3 w-3 shrink-0 opacity-50" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start" class="w-56 max-h-64 overflow-y-auto">
                                <DropdownMenuItem @click="wipStatusFilter = 'ALL'; handleSearch()">
                                    All
                                </DropdownMenuItem>
                                <DropdownMenuItem 
                                    v-for="status in wipStatusOptions" 
                                    :key="status" 
                                    @click="wipStatusFilter = status; handleSearch()"
                                >
                                    {{ status }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                    
                    <!-- Worktype Filter -->
                    <div class="col-span-3 flex items-center gap-2">
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400 w-20 shrink-0">Worktype:</label>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" size="sm" class="flex-1 justify-between h-9 text-xs">
                                    <span class="truncate">{{ worktypeFilter }}</span>
                                    <ChevronDown class="ml-2 h-3 w-3 shrink-0 opacity-50" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start" class="w-56 max-h-64 overflow-y-auto">
                                <DropdownMenuItem @click="worktypeFilter = 'ALL'; handleSearch()">
                                    All
                                </DropdownMenuItem>
                                <DropdownMenuItem 
                                    v-for="worktype in worktypeOptions" 
                                    :key="worktype" 
                                    @click="worktypeFilter = worktype; handleSearch()"
                                >
                                    {{ worktype }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                    
                    <!-- Search Button -->
                    <div class="col-span-1 flex items-center">
                        <Button size="sm" @click="handleSearch" :disabled="isLoading" class="w-full h-9">
                            Search
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Content with sticky header -->
            <div class="flex-1 flex flex-col overflow-hidden bg-background">
                <div v-if="isLoading" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
                        <p class="text-sm text-muted-foreground">Loading available lots...</p>
                    </div>
                </div>

                <div v-else-if="availableLots.length === 0" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <span class="text-4xl mb-4 block">📭</span>
                        <p class="text-sm text-muted-foreground">No available lots found</p>
                        <p class="text-xs text-muted-foreground mt-1">Try adjusting the filters or search criteria</p>
                    </div>
                </div>

                <div v-else class="flex-1 flex flex-col overflow-hidden">
                    <!-- Table Header - Sticky -->
                    <div class="px-5 pt-4 pb-2 bg-background border-b border-border shrink-0">
                        <div class="grid grid-cols-12 gap-2 px-3 py-2 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg border border-blue-600 text-xs font-semibold text-white shadow-sm">
                            <div class="col-span-1 flex items-center gap-1 cursor-pointer hover:text-blue-100 transition-colors" @click="handleSort('lot_no')">
                                <span>Lot No.</span>
                                <ArrowUpDown v-if="sortColumn !== 'lot_no'" class="h-3 w-3 opacity-50" />
                                <ArrowUp v-else-if="sortDirection === 'asc'" class="h-3 w-3" />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div class="col-span-2 flex items-center gap-1 cursor-pointer hover:text-blue-100 transition-colors" @click="handleSort('lot_model')">
                                <span>Model</span>
                                <ArrowUpDown v-if="sortColumn !== 'lot_model'" class="h-3 w-3 opacity-50" />
                                <ArrowUp v-else-if="sortDirection === 'asc'" class="h-3 w-3" />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div class="col-span-1 flex items-center justify-end gap-1 cursor-pointer hover:text-blue-100 transition-colors" @click="handleSort('lot_qty')">
                                <span>Quantity</span>
                                <ArrowUpDown v-if="sortColumn !== 'lot_qty'" class="h-3 w-3 opacity-50" />
                                <ArrowUp v-else-if="sortDirection === 'asc'" class="h-3 w-3" />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div class="col-span-1 flex items-center justify-center gap-1 cursor-pointer hover:text-blue-100 transition-colors" @click="handleSort('stagnant_tat')">
                                <span>TAT</span>
                                <ArrowUpDown v-if="sortColumn !== 'stagnant_tat'" class="h-3 w-3 opacity-50" />
                                <ArrowUp v-else-if="sortDirection === 'asc'" class="h-3 w-3" />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div class="col-span-1 flex items-center justify-center gap-1 cursor-pointer hover:text-blue-100 transition-colors" @click="handleSort('work_type')">
                                <span>Work Type</span>
                                <ArrowUpDown v-if="sortColumn !== 'work_type'" class="h-3 w-3 opacity-50" />
                                <ArrowUp v-else-if="sortDirection === 'asc'" class="h-3 w-3" />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div class="col-span-2 flex items-center justify-center gap-1 cursor-pointer hover:text-blue-100 transition-colors" @click="handleSort('wip_status')">
                                <span>WIP Status</span>
                                <ArrowUpDown v-if="sortColumn !== 'wip_status'" class="h-3 w-3 opacity-50" />
                                <ArrowUp v-else-if="sortDirection === 'asc'" class="h-3 w-3" />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div class="col-span-1 flex items-center justify-center gap-1 cursor-pointer hover:text-blue-100 transition-colors" @click="handleSort('auto_yn')">
                                <span>Auto</span>
                                <ArrowUpDown v-if="sortColumn !== 'auto_yn'" class="h-3 w-3 opacity-50" />
                                <ArrowUp v-else-if="sortDirection === 'asc'" class="h-3 w-3" />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div class="col-span-1 flex items-center justify-center gap-1 cursor-pointer hover:text-blue-100 transition-colors" @click="handleSort('lipas_yn')">
                                <span>Lipas</span>
                                <ArrowUpDown v-if="sortColumn !== 'lipas_yn'" class="h-3 w-3 opacity-50" />
                                <ArrowUp v-else-if="sortDirection === 'asc'" class="h-3 w-3" />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div class="col-span-1 flex items-center justify-center gap-1 cursor-pointer hover:text-blue-100 transition-colors" @click="handleSort('lot_location')">
                                <span>Location</span>
                                <ArrowUpDown v-if="sortColumn !== 'lot_location'" class="h-3 w-3 opacity-50" />
                                <ArrowUp v-else-if="sortDirection === 'asc'" class="h-3 w-3" />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div class="col-span-1 text-center">Action</div>
                        </div>
                    </div>
                    
                    <!-- Table Body - Scrollable -->
                    <div class="flex-1 overflow-y-auto px-5 pb-4">
                        <div class="space-y-1 pt-2">
                            <div
                                v-for="lot in sortedLots"
                                :key="lot.lot_no"
                                class="grid grid-cols-12 gap-2 p-3 border border-border rounded-lg hover:bg-accent/50 transition-colors items-center"
                            >
                                <div class="col-span-1 font-semibold text-xs text-foreground truncate" :title="lot.lot_no">
                                    {{ lot.lot_no }}
                                </div>
                                <div class="col-span-2 text-xs truncate text-muted-foreground" :title="lot.lot_model">
                                    {{ lot.lot_model }}
                                </div>
                                <div class="col-span-1 text-xs text-right font-medium">
                                    {{ formatNumber(lot.lot_qty) }}
                                </div>
                                <div class="col-span-1 text-xs text-center">
                                    {{ lot.stagnant_tat ? lot.stagnant_tat.toFixed(1) : '-' }}
                                </div>
                                <div class="col-span-1 text-xs text-center truncate" :title="lot.work_type">
                                    {{ lot.work_type }}
                                </div>
                                <div class="col-span-2 text-xs text-center">
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 truncate max-w-full" :title="lot.wip_status">
                                        {{ lot.wip_status }}
                                    </span>
                                </div>
                                <div class="col-span-1 text-xs text-center font-medium">
                                    {{ lot.auto_yn || '-' }}
                                </div>
                                <div class="col-span-1 text-xs text-center font-medium">
                                    {{ lot.lipas_yn || '-' }}
                                </div>
                                <div class="col-span-1 text-xs text-center truncate" :title="lot.lot_location">
                                    {{ lot.lot_location || '-' }}
                                </div>
                                <div class="col-span-1 text-center">
                                    <Button size="sm" variant="default" @click="handleAssign(lot)" class="h-8 px-3 text-xs">
                                        Assign
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 border-t border-border bg-muted/30 dark:bg-gray-800/30 flex items-center justify-between">
                <p class="text-xs text-muted-foreground">
                    {{ availableLots.length }} lot(s) available
                </p>
                <Button variant="outline" @click="handleClose">
                    Cancel
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
