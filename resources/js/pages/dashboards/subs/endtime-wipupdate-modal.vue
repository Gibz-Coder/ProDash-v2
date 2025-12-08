<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogFooter, 
    DialogHeader, 
    DialogTitle 
} from '@/components/ui/dialog';

// ============================================================================
// PROPS & EMITS
// ============================================================================
defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

// ============================================================================
// STATE
// ============================================================================
const isUpdating = ref(false);
const rawData = ref('');
const dataStats = ref({
    rows: 0,
    columns: 0,
});

// ============================================================================
// COMPUTED
// ============================================================================
const dataStatsDisplay = computed(() => {
    if (dataStats.value.rows === 0) return '';
    return `${dataStats.value.rows} data rows detected (${dataStats.value.columns} columns)`;
});

// ============================================================================
// METHODS
// ============================================================================
const closeModal = () => {
    emit('update:open', false);
    resetForm();
};

const resetForm = () => {
    rawData.value = '';
    dataStats.value = { rows: 0, columns: 0 };
};

// Watch for data input changes to update stats
watch(rawData, (newValue) => {
    if (!newValue || newValue.trim() === '') {
        dataStats.value = { rows: 0, columns: 0 };
        return;
    }
    
    const lines = newValue.split('\n').filter(line => line.trim() !== '');
    const dataRows = lines.length > 0 ? lines.length - 1 : 0; // Subtract header
    
    let columns = 0;
    if (lines.length > 1) {
        const firstDataLine = lines[1].split('\t');
        columns = firstDataLine.length;
    }
    
    dataStats.value = { rows: dataRows, columns };
});

// Download template
const downloadTemplate = () => {
    window.location.href = '/updatewip/download-template';
};

// ============================================================================
// TOAST NOTIFICATION
// ============================================================================
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

// ============================================================================
// FORM SUBMISSION
// ============================================================================
const handleSubmit = async () => {
    if (!rawData.value || rawData.value.trim() === '') {
        showToast('Please paste Excel data', 'danger');
        return;
    }

    if (!confirm('This will delete all existing WIP data and replace it with the new data. Are you sure?')) {
        return;
    }

    isUpdating.value = true;
    
    try {
        router.post('/updatewip', {
            raw_data: rawData.value,
        }, {
            onSuccess: () => {
                showToast('WIP data updated successfully!', 'success');
                closeModal();
            },
            onError: (errors) => {
                const errorMessage = errors.raw_data || 'Failed to update WIP data. Please try again.';
                showToast(errorMessage, 'danger');
            },
            onFinish: () => {
                isUpdating.value = false;
            }
        });
    } catch (error) {
        isUpdating.value = false;
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => emit('update:open', value)">
        <DialogContent class="!max-w-[70vw] max-h-[90vh] flex flex-col overflow-hidden">
            <DialogHeader>
                <div class="flex items-center justify-between gap-4">
                    <DialogTitle class="text-2xl font-bold flex items-center gap-2">
                        <span class="text-2xl">💾</span>
                        WIP Data Import
                    </DialogTitle>
                </div>
                
                <DialogDescription>
                    Copy WIP data from Excel and paste it below to update the database
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="flex flex-col gap-4 flex-1 overflow-hidden">
                <!-- Instructions & Template Section -->
                <div class="grid grid-cols-2 gap-4 flex-shrink-0">
                    <!-- Instructions -->
                    <div class="rounded-lg border-2 border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/20 p-4">
                        <h3 class="text-sm font-semibold mb-3 text-blue-900 dark:text-blue-100">📋 Instructions</h3>
                        <ol class="space-y-2 text-xs text-blue-800 dark:text-blue-200">
                            <li class="flex gap-2">
                                <span class="font-semibold">1.</span>
                                <span>Copy the WIP data from Excel (including headers)</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="font-semibold">2.</span>
                                <span>Paste the data into the text area below</span>
                            </li>
                            <li class="flex gap-2">
                                <span class="font-semibold">3.</span>
                                <span>Click "Update WIP Data" to process</span>
                            </li>
                            <li class="flex gap-2 text-red-600 dark:text-red-400">
                                <span class="font-semibold">⚠️</span>
                                <span><strong>Warning:</strong> This will clear all existing WIP data before importing</span>
                            </li>
                        </ol>
                    </div>

                    <!-- Template Download -->
                    <div 
                        @click="downloadTemplate"
                        class="rounded-lg border-2 border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-950/20 p-4 cursor-pointer hover:bg-emerald-100/50 dark:hover:bg-emerald-900/30 transition-all hover:scale-[1.02]"
                    >
                        <div class="flex flex-col items-center justify-center h-full text-center gap-3">
                            <span class="text-4xl">📥</span>
                            <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">Excel Format Template</h3>
                            <div class="flex items-center gap-2 text-xs text-emerald-700 dark:text-emerald-300">
                                <span>📥</span>
                                <span>Click to download template</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Input Section -->
                <div class="flex-1 flex flex-col overflow-hidden space-y-2">
                    <div class="flex items-center justify-between">
                        <Label for="raw_data" class="text-sm font-semibold">
                            Excel Data <span class="text-destructive">*</span>
                            <span class="text-xs text-muted-foreground font-normal ml-2">(Paste copied Excel data here)</span>
                        </Label>
                        <div v-if="dataStatsDisplay" class="text-xs text-blue-600 dark:text-blue-400 flex items-center gap-1">
                            <span>ℹ️</span>
                            <span>{{ dataStatsDisplay }}</span>
                        </div>
                    </div>
                    
                    <textarea 
                        id="raw_data"
                        v-model="rawData"
                        placeholder="Paste your Excel data here..."
                        required
                        class="flex-1 font-mono text-xs resize-none min-h-[300px] rounded-md border border-border bg-background px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    ></textarea>
                </div>

                <!-- Form Actions -->
                <DialogFooter class="border-t border-border pt-4 mt-0 flex-shrink-0">
                    <Button 
                        type="button" 
                        variant="outline" 
                        @click="closeModal"
                        :disabled="isUpdating"
                    >
                        <span class="text-sm">❌</span>
                        <span class="ml-2">Cancel</span>
                    </Button>
                    <Button 
                        type="submit"
                        :disabled="isUpdating || !rawData.trim()"
                        class="bg-sky-500 text-white hover:bg-sky-600"
                    >
                        <span v-if="isUpdating">⏳ Updating...</span>
                        <span v-else class="flex items-center gap-2">
                            <span>🔄</span>
                            <span>Update WIP Data</span>
                        </span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
