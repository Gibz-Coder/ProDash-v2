<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

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
    open: boolean;
    allocation: Allocation | null;
    filterOptions: {
        machine_types: string[];
        statuses: string[];
        locations: string[];
    };
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'saved'): void;
}>();

const isSubmitting = ref(false);
const isLoadingRef = ref(false);
const errors = ref<Record<string, string>>({});

// Form data
const form = ref({
    eqp_line: '',
    eqp_area: '',
    size: '',
    alloc_type: '',
    eqp_status: '',
    operation_time: 1440,
    loading_speed: 0,
    eqp_oee: 0,
    eqp_passing: 0,
    eqp_yield: 0,
});

// Read-only equipment info
const equipmentInfo = ref({
    eqp_no: '',
    eqp_type: '',
    eqp_maker: '',
});


// Options
const lineOptions = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
const sizeOptions = [
    { value: '03', label: '0603' },
    { value: '05', label: '1005' },
    { value: '10', label: '1608' },
    { value: '21', label: '2012' },
    { value: '31', label: '3216' },
    { value: '32', label: '3225' },
];

// Computed area options based on selected line
const areaOptions = computed(() => {
    const line = form.value.eqp_line;
    if (!line) return [];
    if (line === 'K') return ['K'];
    return ['1', '2', '3', '4'].map(n => `${line}${n}`);
});

// Calculated capacities
const idealCapa = computed(() => form.value.loading_speed * form.value.operation_time);
const oeeCapa = computed(() => Math.floor(form.value.loading_speed * form.value.eqp_oee * form.value.operation_time));
const outputCapa = computed(() => Math.floor(form.value.loading_speed * form.value.eqp_oee * form.value.eqp_passing * form.value.eqp_yield * form.value.operation_time));

// Helper to safely convert to number
const toNumber = (val: any, defaultVal = 0): number => {
    const num = parseFloat(val);
    return isNaN(num) ? defaultVal : num;
};

// Watch for allocation changes to populate form
watch(() => props.allocation, (newVal) => {
    if (newVal) {
        equipmentInfo.value = {
            eqp_no: newVal.eqp_no,
            eqp_type: newVal.eqp_type || '',
            eqp_maker: newVal.eqp_maker,
        };
        form.value = {
            eqp_line: newVal.eqp_line,
            eqp_area: newVal.eqp_area,
            size: newVal.size,
            alloc_type: newVal.alloc_type || '',
            eqp_status: newVal.eqp_status,
            operation_time: toNumber(newVal.operation_time, 1440),
            loading_speed: toNumber(newVal.loading_speed),
            eqp_oee: toNumber(newVal.eqp_oee),
            eqp_passing: toNumber(newVal.eqp_passing),
            eqp_yield: toNumber(newVal.eqp_yield),
        };
        errors.value = {};
    }
}, { immediate: true });

// Reset area when line changes
watch(() => form.value.eqp_line, (newLine, oldLine) => {
    if (oldLine && newLine !== oldLine) {
        form.value.eqp_area = areaOptions.value[0] || '';
    }
});

const handleClose = () => {
    emit('update:open', false);
};

const formatNumber = (value: number): string => {
    return new Intl.NumberFormat().format(value);
};

const formatCapacity = (value: number): string => {
    return (value / 1000000).toFixed(2) + 'M';
};

const formatDecimal = (value: any, decimals = 4): string => {
    const num = toNumber(value);
    return num.toFixed(decimals);
};

// Fetch reference data from backend
const loadReferenceData = async () => {
    const size = form.value.size;
    const allocType = form.value.alloc_type;
    const eqpType = equipmentInfo.value.eqp_type;

    if (!size || !allocType) {
        return;
    }

    isLoadingRef.value = true;

    try {
        const params = new URLSearchParams({
            eqp_type: eqpType,
            size: size,
            alloc_type: allocType,
        });

        const response = await fetch(`/mc-allocation/reference-data?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const data = await response.json();

        if (data.success && data.data) {
            if (data.data.loading_speed !== undefined) {
                form.value.loading_speed = data.data.loading_speed;
            }
            if (data.data.eqp_oee !== undefined) {
                form.value.eqp_oee = parseFloat(data.data.eqp_oee);
            }
            if (data.data.eqp_passing !== undefined) {
                form.value.eqp_passing = parseFloat(data.data.eqp_passing);
            }
            if (data.data.eqp_yield !== undefined) {
                form.value.eqp_yield = parseFloat(data.data.eqp_yield);
            }
        }
    } catch (error) {
        console.error('Error loading reference data:', error);
    } finally {
        isLoadingRef.value = false;
    }
};

const handleSubmit = () => {
    if (!props.allocation) return;
    
    errors.value = {};
    isSubmitting.value = true;

    router.put(`/mc-allocation/${props.allocation.id}`, form.value, {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            handleClose();
        },
        onError: (errs) => {
            errors.value = errs;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>


<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="!max-w-[750px] max-h-[90vh] overflow-hidden flex flex-col p-0 gap-0">
            <!-- Header -->
            <DialogHeader class="px-5 py-3 border-b border-border bg-gradient-to-r from-amber-50 to-amber-100/50 dark:from-amber-950/30 dark:to-amber-900/20">
                <DialogTitle class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                        <span class="text-xl">✏️</span>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-foreground">Edit Equipment</h2>
                        <p class="text-xs text-muted-foreground mt-0.5">{{ equipmentInfo.eqp_no }}</p>
                    </div>
                </DialogTitle>
                <DialogDescription class="sr-only">Edit machine allocation details</DialogDescription>
            </DialogHeader>

            <!-- Form Content -->
            <div class="flex-1 overflow-y-auto px-5 py-4 bg-background space-y-4">
                <!-- Equipment Info (Read-only) -->
                <div class="rounded-lg border-2 border-gray-400 dark:border-gray-600 overflow-hidden">
                    <div class="px-3 py-2 bg-gradient-to-r from-gray-500 to-gray-600 text-white">
                        <span class="text-xs font-bold">📋 Equipment Information (Read-only)</span>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-800/50 grid grid-cols-3 gap-3">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Number:</span>
                            <p class="font-bold text-gray-700 dark:text-gray-200">{{ equipmentInfo.eqp_no }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Type:</span>
                            <p class="font-bold text-gray-700 dark:text-gray-200">{{ equipmentInfo.eqp_type || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Maker:</span>
                            <p class="font-bold text-gray-700 dark:text-gray-200">{{ equipmentInfo.eqp_maker }}</p>
                        </div>
                    </div>
                </div>

                <!-- Editable Fields -->
                <div class="rounded-lg border-2 border-blue-400 dark:border-blue-600 overflow-hidden">
                    <div class="px-3 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white flex items-center justify-between">
                        <span class="text-xs font-bold">✏️ Editable Fields</span>
                    </div>
                    <div class="p-3 bg-blue-50/50 dark:bg-blue-900/20 grid grid-cols-3 gap-3">
                        <!-- Line -->
                        <div>
                            <label class="block text-xs font-semibold text-blue-700 dark:text-blue-300 mb-1">Line <span class="text-red-500">*</span></label>
                            <select v-model="form.eqp_line" class="w-full rounded border-2 border-blue-300 dark:border-blue-600 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                                <option v-for="line in lineOptions" :key="line" :value="line">{{ line }}</option>
                            </select>
                        </div>
                        <!-- Area -->
                        <div>
                            <label class="block text-xs font-semibold text-blue-700 dark:text-blue-300 mb-1">Area</label>
                            <select v-model="form.eqp_area" class="w-full rounded border-2 border-blue-300 dark:border-blue-600 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                                <option v-for="area in areaOptions" :key="area" :value="area">{{ area }}</option>
                            </select>
                        </div>
                        <!-- Size -->
                        <div>
                            <label class="block text-xs font-semibold text-blue-700 dark:text-blue-300 mb-1">Size <span class="text-red-500">*</span></label>
                            <select v-model="form.size" @change="loadReferenceData" class="w-full rounded border-2 border-blue-300 dark:border-blue-600 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                                <option v-for="size in sizeOptions" :key="size.value" :value="size.value">{{ size.label }}</option>
                            </select>
                        </div>
                        <!-- Allocation Type -->
                        <div>
                            <label class="block text-xs font-semibold text-blue-700 dark:text-blue-300 mb-1">Alloc Type <span class="text-red-500">*</span></label>
                            <select v-model="form.alloc_type" @change="loadReferenceData" class="w-full rounded border-2 border-blue-300 dark:border-blue-600 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="">-- None --</option>
                                <option v-for="loc in filterOptions.locations" :key="loc" :value="loc">{{ loc }}</option>
                            </select>
                        </div>
                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-semibold text-blue-700 dark:text-blue-300 mb-1">Status <span class="text-red-500">*</span></label>
                            <select v-model="form.eqp_status" class="w-full rounded border-2 border-blue-300 dark:border-blue-600 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                                <option v-for="status in filterOptions.statuses" :key="status" :value="status">{{ status }}</option>
                            </select>
                        </div>
                        <!-- Operation Time -->
                        <div>
                            <label class="block text-xs font-semibold text-blue-700 dark:text-blue-300 mb-1">Op Time <span class="text-red-500">*</span></label>
                            <input type="number" v-model.number="form.operation_time" min="0" class="w-full rounded border-2 border-blue-300 dark:border-blue-600 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500" />
                        </div>
                    </div>
                </div>

                <!-- Reference Data (Auto-populated) -->
                <div class="rounded-lg border-2 border-green-400 dark:border-green-600 overflow-hidden">
                    <div class="px-3 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white flex items-center justify-between">
                        <span class="text-xs font-bold">📊 Reference Data (Auto-populated)</span>
                        <button @click="loadReferenceData" :disabled="isLoadingRef" class="text-xs bg-white/20 hover:bg-white/30 px-2 py-0.5 rounded transition-colors">
                            {{ isLoadingRef ? '⏳' : '🔄' }} Refresh
                        </button>
                    </div>
                    <div class="p-3 bg-green-50/50 dark:bg-green-900/20 grid grid-cols-4 gap-3">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Loading Speed:</span>
                            <p class="font-bold text-green-700 dark:text-green-300">{{ formatNumber(form.loading_speed) }} <span class="text-xs font-normal">units/min</span></p>
                            <input type="hidden" v-model.number="form.loading_speed" />
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">OEE:</span>
                            <p class="font-bold text-green-700 dark:text-green-300">{{ formatDecimal(form.eqp_oee) }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Passing:</span>
                            <p class="font-bold text-green-700 dark:text-green-300">{{ formatDecimal(form.eqp_passing) }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Yield:</span>
                            <p class="font-bold text-green-700 dark:text-green-300">{{ formatDecimal(form.eqp_yield) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Calculated Capacities -->
                <div class="rounded-lg border-2 border-cyan-400 dark:border-cyan-600 overflow-hidden">
                    <div class="px-3 py-2 bg-gradient-to-r from-cyan-500 to-cyan-600 text-white">
                        <span class="text-xs font-bold">🧮 Calculated Capacities (Auto-updated)</span>
                    </div>
                    <div class="p-3 bg-cyan-50/50 dark:bg-cyan-900/20 grid grid-cols-3 gap-3">
                        <div class="text-center p-2 rounded bg-cyan-100 dark:bg-cyan-800/30">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block">Ideal Capacity</span>
                            <p class="text-lg font-bold text-cyan-700 dark:text-cyan-300">{{ formatNumber(idealCapa) }}</p>
                            <span class="text-xs text-gray-500">{{ formatCapacity(idealCapa) }}</span>
                            <p class="text-[10px] text-gray-400 mt-1">speed × time</p>
                        </div>
                        <div class="text-center p-2 rounded bg-blue-100 dark:bg-blue-800/30">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block">OEE Capacity</span>
                            <p class="text-lg font-bold text-blue-700 dark:text-blue-300">{{ formatNumber(oeeCapa) }}</p>
                            <span class="text-xs text-gray-500">{{ formatCapacity(oeeCapa) }}</span>
                            <p class="text-[10px] text-gray-400 mt-1">speed × oee × time</p>
                        </div>
                        <div class="text-center p-2 rounded bg-emerald-100 dark:bg-emerald-800/30">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block">Output Capacity</span>
                            <p class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ formatNumber(outputCapa) }}</p>
                            <span class="text-xs text-gray-500">{{ formatCapacity(outputCapa) }}</span>
                            <p class="text-[10px] text-gray-400 mt-1">speed × oee × pass × yield × time</p>
                        </div>
                    </div>
                </div>

                <!-- Validation Errors -->
                <div v-if="Object.keys(errors).length > 0" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 p-3">
                    <p class="text-xs font-semibold text-red-700 dark:text-red-300 mb-1">Please fix the following errors:</p>
                    <ul class="text-xs text-red-600 dark:text-red-400 list-disc list-inside">
                        <li v-for="(error, field) in errors" :key="field">{{ error }}</li>
                    </ul>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 border-t border-border bg-muted/30 dark:bg-gray-800/30 flex items-center justify-between">
                <div class="text-xs text-gray-500">
                    <span class="text-red-500">*</span> Required fields
                </div>
                <div class="flex items-center gap-3">
                    <Button variant="outline" @click="handleClose" :disabled="isSubmitting">
                        Cancel
                    </Button>
                    <Button @click="handleSubmit" :disabled="isSubmitting" class="bg-amber-600 hover:bg-amber-700 text-white">
                        <span v-if="isSubmitting" class="mr-2">⏳</span>
                        {{ isSubmitting ? 'Saving...' : '💾 Update Equipment' }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
