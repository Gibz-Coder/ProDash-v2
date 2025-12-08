<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
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
const isSubmitting = ref(false);
const isLookingUp = ref(false);
const lookupError = ref('');
const endtimeId = ref<number | null>(null);

// Employee lookup state
const employeeLookupState = ref({ isLooking: false, error: '' });

// Employee search state
const employeeSearchState = ref({ 
    isSearching: false, 
    results: [] as any[], 
    showDropdown: false
});



// Form data
const formData = ref({
    lot_id: '',
    lot_qty: '',
    lot_size_display: '',
    lot_type: 'MAIN',
    model_15: '',
    lipas_yn: 'N',
    work_type: 'NORMAL',
    actual_endtime: '',
    machine_list: '',
    est_endtime: '',
    submission_notes: '',
    employee_id: '',
    employee_name: '',
});

// Computed property for estimated endtime display (from database)
const estEndtimeDisplay = computed(() => {
    if (!formData.value.est_endtime) return '';
    
    const date = new Date(formData.value.est_endtime);
    const dateStr = date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: '2-digit',
        year: 'numeric'
    });
    const timeStr = date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: false
    });
    
    return `📅${dateStr}|🕐${timeStr}`;
});

// Computed property for current time display
const currentTimeDisplay = computed(() => {
    if (!formData.value.actual_endtime) return '';
    
    const date = new Date(formData.value.actual_endtime);
    const dateStr = date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: '2-digit',
        year: 'numeric'
    });
    const timeStr = date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: false
    });
    
    return `📅${dateStr}|🕐${timeStr}`;
});

const remarks = computed(() => {
    if (!formData.value.actual_endtime || !formData.value.est_endtime) return 'OK';
    
    const actualTime = new Date(formData.value.actual_endtime).getTime();
    const estTime = new Date(formData.value.est_endtime).getTime();
    const diffMinutes = (actualTime - estTime) / (1000 * 60);
    
    if (Math.abs(diffMinutes) <= 30) {
        return 'OK';
    } else if (diffMinutes < -30) {
        return 'EARLY';
    } else {
        return 'LATE';
    }
});

const remarksClass = computed(() => {
    if (remarks.value === 'OK') {
        return 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
    } else if (remarks.value === 'EARLY') {
        return 'bg-blue-500/20 text-blue-700 dark:text-blue-300 border-blue-500/30';
    } else {
        return 'bg-red-500/20 text-red-700 dark:text-red-300 border-red-500/30';
    }
});

const isReasonRequired = computed(() => {
    return remarks.value !== 'OK';
});

// ============================================================================
// METHODS
// ============================================================================
const closeModal = () => {
    emit('update:open', false);
    resetForm();
};

const resetForm = () => {
    formData.value = {
        lot_id: '',
        lot_qty: '',
        lot_size_display: '',
        lot_type: 'MAIN',
        model_15: '',
        lipas_yn: 'N',
        work_type: 'NORMAL',
        actual_endtime: '',
        machine_list: '',
        est_endtime: '',
        submission_notes: '',
        employee_id: '',
        employee_name: '',
    };
    lookupError.value = '';
    endtimeId.value = null;
    employeeLookupState.value = { isLooking: false, error: '' };
    employeeSearchState.value = { isSearching: false, results: [], showDropdown: false };
};

// Lookup lot information from endtime table (only Ongoing status)
const lookupLotInfo = async () => {
    if (!formData.value.lot_id || formData.value.lot_id.trim() === '') {
        return;
    }

    isLookingUp.value = true;
    lookupError.value = '';

    try {
        const response = await fetch(`/api/endtime/lookup-ongoing?lot_id=${encodeURIComponent(formData.value.lot_id)}`);
        
        if (!response.ok) {
            throw new Error('Lot not found');
        }

        const data = await response.json();

        if (data.success && data.endtimeData) {
            // Load existing data for submission
            endtimeId.value = data.endtimeData.id;
            
            formData.value.lot_qty = data.endtimeData.lot_qty ? parseInt(data.endtimeData.lot_qty).toLocaleString() : '';
            formData.value.lot_size_display = getSizeDisplay(data.endtimeData.lot_size || '10');
            formData.value.lot_type = data.endtimeData.lot_type || 'MAIN';
            formData.value.model_15 = data.endtimeData.model_15 || '';
            formData.value.work_type = data.endtimeData.work_type || 'NORMAL';
            formData.value.lipas_yn = data.endtimeData.lipas_yn || 'N';
            formData.value.machine_list = data.endtimeData.machine_list || '-';
            formData.value.est_endtime = data.endtimeData.est_endtime || '';
            
            // Set current time as default actual endtime
            setCurrentTime();
        } else {
            lookupError.value = 'Hindi naka ENDTIME !! 😤';
            resetFormExceptLotId();
        }
    } catch (error) {
        lookupError.value = 'Hindi naka ENDTIME !! 😤';
        resetFormExceptLotId();
    } finally {
        isLookingUp.value = false;
        // Focus on employee_id field after lookup completes
        await nextTick();
        const employeeInput = document.getElementById('employee_id');
        if (employeeInput) {
            employeeInput.focus();
        }
    }
};

const resetFormExceptLotId = () => {
    const lotId = formData.value.lot_id;
    formData.value = {
        lot_id: lotId,
        lot_qty: '',
        lot_size_display: '',
        lot_type: 'MAIN',
        model_15: '',
        lipas_yn: 'N',
        work_type: 'NORMAL',
        actual_endtime: '',
        machine_list: '',
        est_endtime: '',
        submission_notes: '',
        employee_id: '',
        employee_name: '',
    };
    endtimeId.value = null;
};





// Watch remarks and auto-fill submission_notes when OK
watch(remarks, (newRemarks) => {
    if (newRemarks === 'OK') {
        formData.value.submission_notes = 'OK';
    } else if (formData.value.submission_notes === 'OK') {
        formData.value.submission_notes = '';
    }
});

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================
const getSizeDisplay = (size: string): string => {
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

const setCurrentTime = () => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    
    formData.value.actual_endtime = `${year}-${month}-${day}T${hours}:${minutes}`;
};

// ============================================================================
// EMPLOYEE FUNCTIONS
// ============================================================================
const searchEmployee = async () => {
    if (!formData.value.employee_id || formData.value.employee_id.trim().length < 2) {
        employeeSearchState.value.results = [];
        employeeSearchState.value.showDropdown = false;
        return;
    }

    employeeSearchState.value.isSearching = true;

    try {
        const url = `/api/employee/search?search=${encodeURIComponent(formData.value.employee_id)}`;
        const response = await fetch(url);
        const data = await response.json();

        if (data.success && data.employees.length > 0) {
            employeeSearchState.value.results = data.employees;
            employeeSearchState.value.showDropdown = true;
        } else {
            employeeSearchState.value.results = [];
            employeeSearchState.value.showDropdown = false;
        }
    } catch (error) {
        employeeSearchState.value.results = [];
        employeeSearchState.value.showDropdown = false;
    } finally {
        employeeSearchState.value.isSearching = false;
    }
};

const selectEmployee = (employee: any) => {
    // Immediately update both fields to prevent race conditions
    formData.value.employee_id = employee.employee_id;
    formData.value.employee_name = employee.employee_name || '';
    employeeSearchState.value.showDropdown = false;
    employeeSearchState.value.results = [];
    employeeLookupState.value.error = '';
};

const closeEmployeeDropdown = () => {
    // Longer delay to ensure selection is processed before closing
    setTimeout(() => {
        employeeSearchState.value.showDropdown = false;
    }, 300);
};

const lookupEmployeeInfo = async () => {
    if (!formData.value.employee_id || formData.value.employee_id.trim() === '') return;

    // Close dropdown first to prevent interference
    employeeSearchState.value.showDropdown = false;
    employeeSearchState.value.results = [];
    
    employeeLookupState.value.isLooking = true;
    employeeLookupState.value.error = '';

    try {
        const employeeIdToLookup = formData.value.employee_id.trim();
        const response = await fetch(`/api/employee/lookup?employee_id=${encodeURIComponent(employeeIdToLookup)}`);
        const data = await response.json();

        if (data.success) {
            // Update both employee_id (in case of case differences) and employee_name
            formData.value.employee_id = data.employee.employee_id;
            formData.value.employee_name = data.employee.employee_name || '';
            employeeLookupState.value.error = '';
        } else {
            employeeLookupState.value.error = 'Employee not found';
            formData.value.employee_name = '';
        }
    } catch (error) {
        employeeLookupState.value.error = 'Employee not found';
        formData.value.employee_name = '';
    } finally {
        employeeLookupState.value.isLooking = false;
    }
};

// ============================================================================
// INPUT FORMATTING FUNCTIONS
// ============================================================================
const capitalizeLotId = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const cursorPosition = input.selectionStart;
    const upperValue = input.value.toUpperCase();
    
    // Only update if value changed to avoid unnecessary re-renders
    if (formData.value.lot_id !== upperValue) {
        formData.value.lot_id = upperValue;
    }
    
    nextTick(() => {
        if (cursorPosition !== null) {
            input.setSelectionRange(cursorPosition, cursorPosition);
        }
    });
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
    if (!endtimeId.value) {
        showToast('Please lookup a valid lot first', 'danger');
        return;
    }

    if (!formData.value.employee_id || formData.value.employee_id.trim() === '') {
        showToast('Please enter employee ID', 'danger');
        return;
    }

    // Validate: Employee must be validated (employee_name must be set)
    if (!formData.value.employee_name || formData.value.employee_name.trim() === '') {
        showToast('Please validate Employee ID first (click 🔍 or select from dropdown)', 'danger');
        // Focus on employee_id field
        const employeeInput = document.getElementById('employee_id');
        if (employeeInput) {
            employeeInput.focus();
        }
        return;
    }

    if (!formData.value.actual_endtime) {
        showToast('Please enter actual endtime', 'danger');
        return;
    }

    if (isReasonRequired.value && (!formData.value.submission_notes || formData.value.submission_notes.trim() === '')) {
        showToast('Please provide a reason for early or late submission', 'danger');
        return;
    }

    isSubmitting.value = true;
    
    try {
        router.post(`/endtime/${endtimeId.value}/submit`, {
            actual_endtime: formData.value.actual_endtime,
            remarks: remarks.value,
            submission_notes: formData.value.submission_notes,
            employee_id: formData.value.employee_id,
        }, {
            onSuccess: () => {
                showToast('Lot submitted successfully!', 'success');
                closeModal();
            },
            onError: () => {
                showToast('Failed to submit lot. Please try again.', 'danger');
            },
            onFinish: () => {
                isSubmitting.value = false;
            }
        });
    } catch (error) {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => emit('update:open', value)">
        <DialogContent class="!max-w-[60vw] max-h-[90vh] flex flex-col overflow-hidden">
            <DialogHeader>
                <div class="flex items-center justify-between gap-4">
                    <DialogTitle class="text-2xl font-bold flex items-center gap-2">
                        <span class="text-2xl">✅</span>
                        SUBMITTED LOT ENTRY
                    </DialogTitle>
                </div>
                
                <DialogDescription>
                    💢 -- Only lot with Endtime can accept here!!
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="flex flex-col gap-6 flex-1 overflow-hidden">
                <!-- Lot Information Section -->
                <div class="rounded-lg border-2 border-border p-4 space-y-4 flex-shrink-0">
                    <h3 class="text-lg font-semibold flex items-center gap-2">
                        <span>ℹ️</span>
                        Lot Information
                    </h3>
                    
                    <div class="grid grid-cols-12 gap-4">
                        <!-- Lot No -->
                        <div class="col-span-3 space-y-2">
                            <Label for="lot_id" class="text-sm font-medium">Lot No. *</Label>
                            <div class="flex gap-2">
                                <Input 
                                    id="lot_id"
                                    v-model="formData.lot_id"
                                    @input="capitalizeLotId"
                                    @blur="lookupLotInfo"
                                    @keyup.enter="lookupLotInfo"
                                    placeholder="Enter lot number"
                                    required
                                    maxlength="15"
                                    class="flex-1 uppercase"
                                    :disabled="isLookingUp"
                                />
                                <Button 
                                    type="button"
                                    variant="outline" 
                                    size="icon"
                                    @click="lookupLotInfo"
                                    :disabled="isLookingUp || !formData.lot_id"
                                    title="Lookup lot information"
                                    class="shrink-0"
                                >
                                    <span v-if="isLookingUp">⏳</span>
                                    <span v-else>🔍</span>
                                </Button>
                            </div>
                            <p v-if="lookupError" class="text-xs text-destructive font-semibold">{{ lookupError }}</p>
                        </div>

                        <!-- Lot Type -->
                        <div class="col-span-2 space-y-2">
                            <Label class="text-sm font-medium">Lot Type</Label>
                            <div class="flex items-center h-10">
                                <Badge 
                                    v-if="formData.lot_type === 'MAIN'"
                                    class="bg-blue-500/20 text-blue-700 dark:text-blue-300 border-blue-500/30"
                                >
                                    MAIN
                                </Badge>
                                <Badge 
                                    v-else-if="formData.lot_type === 'WL/RW'"
                                    class="bg-amber-500/20 text-amber-700 dark:text-amber-300 border-amber-500/30"
                                >
                                    WL/RW
                                </Badge>
                                <Badge 
                                    v-else-if="formData.lot_type === 'RL/LY'"
                                    class="bg-cyan-500/20 text-cyan-700 dark:text-cyan-300 border-cyan-500/30"
                                >
                                    RL/LY
                                </Badge>
                                <span v-else class="text-sm text-muted-foreground">-</span>
                            </div>
                        </div>

                        <!-- Lot Qty -->
                        <div class="col-span-2 space-y-2">
                            <Label for="lot_qty" class="text-sm font-medium">Lot Qty</Label>
                            <Input 
                                id="lot_qty"
                                v-model="formData.lot_qty"
                                type="text"
                                readonly
                                class="w-full bg-muted/50 text-right font-mono"
                            />
                        </div>

                        <!-- Lot Size -->
                        <div class="col-span-1 space-y-2">
                            <Label for="lot_size" class="text-sm font-medium">Size</Label>
                            <Input 
                                id="lot_size"
                                v-model="formData.lot_size_display"
                                type="text"
                                readonly
                                class="w-full bg-muted/50 text-center"
                            />
                        </div>

                        <!-- Model -->
                        <div class="col-span-2 space-y-2">
                            <Label for="model_15" class="text-sm font-medium">Model</Label>
                            <Input 
                                id="model_15"
                                v-model="formData.model_15"
                                readonly
                                class="w-full bg-muted/50"
                            />
                        </div>

                        <!-- Work Type -->
                        <div class="col-span-2 space-y-2">
                            <Label for="work_type" class="text-sm font-medium">Work Type</Label>
                            <Input 
                                id="work_type"
                                v-model="formData.work_type"
                                readonly
                                class="w-full bg-muted/50"
                            />
                        </div>
                    </div>

                    <!-- Employee ID & Machine List -->
                    <div class="grid grid-cols-12 gap-4 pt-2">
                        <!-- Employee ID -->
                        <div class="col-span-4 space-y-2">
                            <Label for="employee_id" class="text-sm font-medium">Employee ID *</Label>
                            <div class="flex gap-2">
                                <div class="flex-1 relative">
                                    <Input 
                                        id="employee_id"
                                        v-model="formData.employee_id"
                                        @input="searchEmployee"
                                        @blur="closeEmployeeDropdown"
                                        @keyup.enter="lookupEmployeeInfo"
                                        placeholder="Type to search..."
                                        required
                                        maxlength="10"
                                        class="w-full"
                                        :disabled="employeeLookupState.isLooking"
                                        autocomplete="off"
                                    />
                                    
                                    <!-- Dropdown Results -->
                                    <div 
                                        v-if="employeeSearchState.showDropdown && employeeSearchState.results.length > 0"
                                        class="absolute z-[9999] w-full mt-1 left-0 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-xl max-h-60 overflow-y-auto"
                                        style="top: 100%;"
                                    >
                                        <div 
                                            v-for="(result, resultIndex) in employeeSearchState.results" 
                                            :key="resultIndex"
                                            @mousedown="selectEmployee(result)"
                                            class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-700 last:border-b-0"
                                        >
                                            <div class="flex flex-col">
                                                <span class="font-semibold text-sm">{{ result.employee_id }}</span>
                                                <span class="text-xs text-muted-foreground">{{ result.employee_name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <Button 
                                    type="button"
                                    size="sm"
                                    @click="lookupEmployeeInfo"
                                    :disabled="employeeLookupState.isLooking || !formData.employee_id"
                                    title="Lookup employee"
                                    class="shrink-0 px-3 whitespace-nowrap bg-slate-600 hover:bg-slate-700 text-white"
                                >
                                    <span v-if="employeeLookupState.isLooking">⏳ LOADING</span>
                                    <span v-else>🔍</span>
                                </Button>
                            </div>
                            <p v-if="formData.employee_name" class="text-xs text-muted-foreground">{{ formData.employee_name }}</p>
                            <p v-if="employeeLookupState.error" class="text-xs text-destructive">{{ employeeLookupState.error }}</p>
                        </div>

                        <!-- Machine List -->
                        <div class="col-span-8 space-y-2">
                            <Label for="machine_list" class="text-sm font-medium">Machine List</Label>
                            <Input 
                                id="machine_list"
                                v-model="formData.machine_list"
                                readonly
                                class="w-full bg-muted/50 font-mono text-sm"
                            />
                        </div>
                    </div>

                    <!-- Estimated Endtime, Actual Endtime, Remarks, and Reason -->
                    <div class="grid grid-cols-12 gap-4 pt-4 border-t border-border">
                        <!-- Estimated Endtime (from est_endtime DB column) -->
                        <div class="col-span-3 space-y-2">
                            <Label class="text-sm font-medium">Estimated Endtime</Label>
                            <div class="flex items-center h-10 px-3 rounded-md border border-border bg-muted/50">
                                <span class="text-base font-semibold font-mono">
                                    {{ estEndtimeDisplay || '-' }}
                                </span>
                            </div>
                        </div>

                        <!-- Submitted Time (Current Time) -->
                        <div class="col-span-3 space-y-2">
                            <Label class="text-sm font-medium">Submitted Time *</Label>
                            <div class="flex items-center h-10 px-3 rounded-md border border-border bg-muted/50">
                                <span class="text-base font-semibold font-mono">
                                    {{ currentTimeDisplay || '-' }}
                                </span>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="col-span-2 space-y-2">
                            <Label class="text-sm font-medium">Remarks</Label>
                            <div class="flex items-center h-10">
                                <Badge 
                                    :class="remarksClass"
                                    class="text-sm font-semibold px-3 py-1"
                                >
                                    {{ remarks }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="col-span-4 space-y-2">
                            <Label for="submission_notes" class="text-sm font-medium">
                                Reason {{ isReasonRequired ? '*' : '' }}
                            </Label>
                            <Input 
                                id="submission_notes"
                                v-model="formData.submission_notes"
                                type="text"
                                placeholder="Enter reason"
                                :required="isReasonRequired"
                                :readonly="!isReasonRequired"
                                :class="!isReasonRequired ? 'bg-muted/50' : ''"
                                maxlength="100"
                            />
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <DialogFooter class="border-t border-border pt-4 mt-0">
                    <Button 
                        type="button" 
                        variant="outline" 
                        @click="closeModal"
                        :disabled="isSubmitting"
                    >
                        Cancel
                    </Button>
                    <Button 
                        type="submit"
                        :disabled="isSubmitting || !endtimeId"
                        class="bg-emerald-600 text-white hover:bg-emerald-700"
                    >
                        <span v-if="isSubmitting">Submitting...</span>
                        <span v-else>✅ Submit Lot</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
