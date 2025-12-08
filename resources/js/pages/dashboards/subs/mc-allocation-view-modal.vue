<script setup lang="ts">
import { computed } from 'vue';
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
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const handleClose = () => {
    emit('update:open', false);
};

const formatNumber = (value: any): string => {
    const num = parseFloat(value);
    return isNaN(num) ? '0' : new Intl.NumberFormat().format(num);
};

const formatCapacity = (value: any): string => {
    const num = parseFloat(value);
    if (isNaN(num)) return '0.0M';
    return (num / 1000000).toFixed(2) + 'M';
};

const formatDecimal = (value: any): string => {
    const num = parseFloat(value);
    return isNaN(num) ? '0.0000' : num.toFixed(4);
};

const getSizeLabel = (size: string): string => {
    const mapping: Record<string, string> = {
        '03': '0603', '05': '1005', '10': '1608',
        '21': '2012', '31': '3216', '32': '3225',
    };
    return mapping[size] || size;
};
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="!max-w-[700px] max-h-[90vh] overflow-hidden flex flex-col p-0 gap-0">
            <!-- Header -->
            <DialogHeader class="px-5 py-3 border-b border-border bg-gradient-to-r from-cyan-50 to-cyan-100/50 dark:from-cyan-950/30 dark:to-cyan-900/20">
                <DialogTitle class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center">
                        <span class="text-xl">👁️</span>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-foreground">Equipment Details</h2>
                        <p class="text-xs text-muted-foreground mt-0.5">{{ allocation?.eqp_no }}</p>
                    </div>
                </DialogTitle>
                <DialogDescription class="sr-only">View equipment details</DialogDescription>
            </DialogHeader>

            <!-- Content -->
            <div v-if="allocation" class="flex-1 overflow-y-auto px-5 py-4 bg-background space-y-4">
                <!-- Basic Information -->
                <div class="rounded-lg border-2 border-blue-400 dark:border-blue-600 overflow-hidden">
                    <div class="px-3 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                        <span class="text-xs font-bold">📋 Basic Information</span>
                    </div>
                    <div class="p-3 bg-blue-50/50 dark:bg-blue-900/20 grid grid-cols-3 gap-3">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Equipment Number:</span>
                            <p class="font-bold text-blue-700 dark:text-blue-300">{{ allocation.eqp_no }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Line:</span>
                            <p class="font-bold text-gray-700 dark:text-gray-200">{{ allocation.eqp_line }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Area:</span>
                            <p class="font-bold text-gray-700 dark:text-gray-200">{{ allocation.eqp_area }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Equipment Type:</span>
                            <p class="font-bold text-gray-700 dark:text-gray-200">{{ allocation.eqp_type || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Maker:</span>
                            <p class="font-bold text-gray-700 dark:text-gray-200">{{ allocation.eqp_maker }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Size:</span>
                            <p class="font-bold text-gray-700 dark:text-gray-200">{{ getSizeLabel(allocation.size) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Status & Allocation -->
                <div class="rounded-lg border-2 border-purple-400 dark:border-purple-600 overflow-hidden">
                    <div class="px-3 py-2 bg-gradient-to-r from-purple-500 to-purple-600 text-white">
                        <span class="text-xs font-bold">📊 Status & Allocation</span>
                    </div>
                    <div class="p-3 bg-purple-50/50 dark:bg-purple-900/20 grid grid-cols-2 gap-3">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Allocation Type:</span>
                            <p class="font-bold text-purple-700 dark:text-purple-300">{{ allocation.alloc_type || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Equipment Status:</span>
                            <span class="inline-block px-2 py-1 rounded text-xs font-bold" :class="{
                                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300': allocation.eqp_status === 'OPERATIONAL',
                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300': allocation.eqp_status === 'BREAKDOWN',
                                'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-300': allocation.eqp_status === 'IDDLE',
                            }">
                                {{ allocation.eqp_status }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Performance Parameters -->
                <div class="rounded-lg border-2 border-green-400 dark:border-green-600 overflow-hidden">
                    <div class="px-3 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white">
                        <span class="text-xs font-bold">⚙️ Performance Parameters</span>
                    </div>
                    <div class="p-3 bg-green-50/50 dark:bg-green-900/20 grid grid-cols-5 gap-3">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Loading Speed:</span>
                            <p class="font-bold text-green-700 dark:text-green-300">{{ formatNumber(allocation.loading_speed) }} <span class="text-xs font-normal">units/min</span></p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Operation Time:</span>
                            <p class="font-bold text-green-700 dark:text-green-300">{{ formatNumber(allocation.operation_time) }} <span class="text-xs font-normal">min</span></p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">OEE:</span>
                            <p class="font-bold text-green-700 dark:text-green-300">{{ formatDecimal(allocation.eqp_oee) }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Passing:</span>
                            <p class="font-bold text-green-700 dark:text-green-300">{{ formatDecimal(allocation.eqp_passing) }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Yield:</span>
                            <p class="font-bold text-green-700 dark:text-green-300">{{ formatDecimal(allocation.eqp_yield) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Calculated Capacities -->
                <div class="rounded-lg border-2 border-cyan-400 dark:border-cyan-600 overflow-hidden">
                    <div class="px-3 py-2 bg-gradient-to-r from-cyan-500 to-cyan-600 text-white">
                        <span class="text-xs font-bold">📈 Calculated Capacities</span>
                    </div>
                    <div class="p-3 bg-cyan-50/50 dark:bg-cyan-900/20 grid grid-cols-3 gap-3">
                        <div class="text-center p-3 rounded bg-cyan-100 dark:bg-cyan-800/30">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Ideal Capacity</span>
                            <p class="text-2xl font-bold text-cyan-700 dark:text-cyan-300">{{ formatNumber(allocation.ideal_capa) }}</p>
                            <span class="text-xs text-gray-500">{{ formatCapacity(allocation.ideal_capa) }}</span>
                            <p class="text-[10px] text-gray-400 mt-1">speed × time</p>
                        </div>
                        <div class="text-center p-3 rounded bg-blue-100 dark:bg-blue-800/30">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">OEE Capacity</span>
                            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ formatNumber(allocation.oee_capa) }}</p>
                            <span class="text-xs text-gray-500">{{ formatCapacity(allocation.oee_capa) }}</span>
                            <p class="text-[10px] text-gray-400 mt-1">speed × oee × time</p>
                        </div>
                        <div class="text-center p-3 rounded bg-emerald-100 dark:bg-emerald-800/30">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Output Capacity</span>
                            <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ formatNumber(allocation.output_capa) }}</p>
                            <span class="text-xs text-gray-500">{{ formatCapacity(allocation.output_capa) }}</span>
                            <p class="text-[10px] text-gray-400 mt-1">speed × oee × pass × yield × time</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 border-t border-border bg-muted/30 dark:bg-gray-800/30 flex items-center justify-end">
                <Button variant="outline" @click="handleClose">
                    Close
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
