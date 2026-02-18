<script setup lang="ts">
import { ref, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';

interface Props {
    open: boolean;
    equipmentNo: string | null;
    currentRemarks: string | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'save', remarks: string): void;
}>();

const remarks = ref('');
const employeeId = ref('');
const resetWaitingTime = ref(false);
const isSaving = ref(false);

const handleClose = () => {
    emit('update:open', false);
};

const handleSave = async () => {
    if (!props.equipmentNo) return;
    
    if (!employeeId.value.trim()) {
        alert('Please enter your Employee ID');
        return;
    }
    
    isSaving.value = true;
    try {
        const response = await fetch(`/api/equipment/${props.equipmentNo}/remarks`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                remarks: remarks.value,
                modified_by: employeeId.value,
                reset_waiting_time: resetWaitingTime.value
            })
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Failed to save remarks');
        }
        
        if (!result.success) {
            throw new Error(result.message || 'Failed to save remarks');
        }
        
        emit('save', remarks.value);
        handleClose();
    } catch (error: any) {
        console.error('Error saving remarks:', error);
        alert(error.message || 'Failed to save remarks');
    } finally {
        isSaving.value = false;
    }
};

// Watch for modal open to load current remarks
watch(() => props.open, (newValue) => {
    if (newValue) {
        remarks.value = props.currentRemarks || '';
        employeeId.value = '';
        resetWaitingTime.value = false;
    }
});
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="!max-w-[500px] max-h-[90vh] overflow-hidden flex flex-col p-0 gap-0">
            <!-- Header -->
            <DialogHeader class="px-5 py-3 border-b border-border bg-gradient-to-r from-amber-50 to-amber-100/50 dark:from-amber-950/30 dark:to-amber-900/20">
                <DialogTitle class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                        <span class="text-xl">💬</span>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-foreground">Add/Edit Remarks</h2>
                        <p class="text-xs text-muted-foreground mt-0.5">Equipment: {{ equipmentNo }}</p>
                    </div>
                </DialogTitle>
                <DialogDescription class="sr-only">Add or edit remarks for equipment</DialogDescription>
            </DialogHeader>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto px-5 py-4 bg-background">
                <div class="space-y-4">
                    <!-- Remarks -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-foreground">Remarks</label>
                        <Textarea
                            v-model="remarks"
                            placeholder="Enter remarks or notes for this equipment..."
                            class="min-h-[150px] resize-none"
                            :disabled="isSaving"
                        />
                        <p class="text-xs text-muted-foreground">
                            Add any notes, issues, or observations about this equipment.
                        </p>
                    </div>

                    <!-- Employee ID -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-foreground">Employee ID <span class="text-red-500">*</span></label>
                        <Input
                            v-model="employeeId"
                            type="text"
                            placeholder="Enter your employee ID"
                            :disabled="isSaving"
                            class="w-full"
                        />
                        <p class="text-xs text-muted-foreground">
                            Your employee ID will be recorded with this update.
                        </p>
                    </div>

                    <!-- Reset Waiting Time -->
                    <div class="flex items-start space-x-3 rounded-md border border-border p-3 bg-muted/30">
                        <Checkbox
                            :id="`reset-waiting-time-${equipmentNo}`"
                            v-model:checked="resetWaitingTime"
                            :disabled="isSaving"
                        />
                        <div class="flex-1">
                            <label
                                :for="`reset-waiting-time-${equipmentNo}`"
                                class="text-sm font-medium leading-none cursor-pointer"
                            >
                                Reset waiting time
                            </label>
                            <p class="text-xs text-muted-foreground mt-1">
                                Check this to reset the equipment's waiting time counter to zero.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 border-t border-border bg-muted/30 dark:bg-gray-800/30 flex items-center justify-end gap-2">
                <Button variant="outline" @click="handleClose" :disabled="isSaving">
                    Cancel
                </Button>
                <Button @click="handleSave" :disabled="isSaving">
                    <span v-if="isSaving">Saving...</span>
                    <span v-else>Save Remarks</span>
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
