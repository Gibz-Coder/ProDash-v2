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

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isUpdating = ref(false);
const rawData = ref('');
const dataStats = ref({
    rows: 0,
    columns: 0,
});

const dataStatsDisplay = computed(() => {
    if (dataStats.value.rows === 0) return '';
    return `${dataStats.value.rows} data rows detected (${dataStats.value.columns} columns)`;
});

const closeModal = () => {
    emit('update:open', false);
    resetForm();
};

const resetForm = () => {
    rawData.value = '';
    dataStats.value = { rows: 0, columns: 0 };
};

watch(rawData, (newValue) => {
    if (!newValue || newValue.trim() === '') {
        dataStats.value = { rows: 0, columns: 0 };
        return;
    }
    
    const lines = newValue.split('\n');
    let validRows = 0;
    let columns = 0;
    let isFirstDataRow = true;
    
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        if (line === '') continue;
        
        const parts = line.split('\t');
        
        if (i === 0 || (validRows === 0 && line.includes('model_15'))) {
            if (isFirstDataRow && i > 0) validRows = 0;
            continue;
        }
        
        if (parts.length >= 8) {
            validRows++;
            if (isFirstDataRow) {
                columns = parts.length;
                isFirstDataRow = false;
            }
        }
    }
    
    dataStats.value = { rows: validRows, columns };
});

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

const handleSubmit = async () => {
    if (!rawData.value || rawData.value.trim() === '') {
        showToast('Please paste Excel data', 'danger');
        return;
    }

    if (!confirm('This will add the new Monthly Plan data to the database. Continue?')) {
        return;
    }

    isUpdating.value = true;
    
    try {
        router.post('/monthly-plan/update', {
            raw_data: rawData.value,
        }, {
            onSuccess: () => {
                showToast('Monthly Plan data added successfully!', 'success');
                closeModal();
            },
            onError: (errors) => {
                console.error('Update error:', errors);
                const errorMessage = errors.raw_data || 'Failed to update Monthly Plan data. Please try again.';
                showToast(errorMessage, 'danger');
            },
            onFinish: () => {
                isUpdating.value = false;
            }
        });
    } catch (error) {
        console.error('Error updating Monthly Plan:', error);
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
                        <span class="text-2xl">📅</span>
                        Monthly Plan Data Import
                    </DialogTitle>
                </div>
                
                <DialogDescription>
                    Copy Monthly Plan data from Excel and paste it below to update the database
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="flex flex-col gap-4 flex-1 overflow-hidden">
                <div class="flex-1 flex flex-col overflow-hidden space-y-2">
                    <div class="flex items-center justify-between">
                        <Label for="raw_data" class="text-sm font-semibold">
                            Excel Data <span class="text-destructive">*</span>
                            <span class="text-xs text-muted-foreground font-normal ml-2">MOS40496 - Monthly Plan</span>
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
                            <span>Update Monthly Plan Data</span>
                        </span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
