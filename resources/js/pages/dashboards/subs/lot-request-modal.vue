<script setup lang="ts">
import { ref, watch, nextTick, computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { usePage } from '@inertiajs/vue3';

// Get auth from shared Inertia page props
const page = usePage();
const auth = computed(() => page.props.auth as { user: any; permissions: string[] });

interface Props {
    open: boolean;
    machineNo: string | null;
    machineLine: string | null;
    machineArea: string | null;
    ongoingModel: string | null;
    previousModel: string | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'created'): void;
}>();

const isSaving = ref(false);
const employeeId = ref('');
const employeeName = ref('');
const remarks = ref('');
const isValidatingEmployee = ref(false);
const employeeValidationError = ref('');
const employeeIdInput = ref<HTMLInputElement | null>(null);
const isCheckingPending = ref(false);
const hasPendingRequest = ref(false);
const pendingRequestInfo = ref<any>(null);

// Computed model - use ongoing model if available, otherwise previous model
const requestedModel = ref('');

const handleClose = () => {
    emit('update:open', false);
    resetForm();
};

const resetForm = () => {
    employeeId.value = '';
    employeeName.value = '';
    remarks.value = '';
    employeeValidationError.value = '';
    hasPendingRequest.value = false;
    pendingRequestInfo.value = null;
};

const checkPendingRequest = async () => {
    if (!props.machineNo) return;
    
    isCheckingPending.value = true;
    hasPendingRequest.value = false;
    pendingRequestInfo.value = null;
    
    try {
        const response = await fetch(`/api/lot-request/check-pending?mc_no=${encodeURIComponent(props.machineNo)}`);
        const result = await response.json();
        
        if (response.ok && result.has_pending) {
            hasPendingRequest.value = true;
            pendingRequestInfo.value = result.request;
            showToast('⚠️ This machine already has a pending lot request', 'warning');
        }
    } catch (error) {
        console.error('Error checking pending request:', error);
    } finally {
        isCheckingPending.value = false;
    }
};

const validateEmployeeId = async () => {
    const empId = employeeId.value.trim();
    
    if (!empId) {
        employeeValidationError.value = 'Employee ID is required';
        employeeName.value = '';
        return;
    }

    isValidatingEmployee.value = true;
    employeeValidationError.value = '';
    employeeName.value = '';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        if (!csrfToken) {
            console.error('CSRF token not found in meta tag');
            employeeValidationError.value = 'Session error - please refresh the page';
            employeeName.value = '';
            showToast('❌ Session error - please refresh the page', 'danger');
            isValidatingEmployee.value = false;
            return;
        }

        const response = await fetch('/api/mems/validate-employee', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                employee_id: empId,
            }),
        });

        const result = await response.json();

        if (response.ok && result.valid) {
            employeeName.value = result.name;
            employeeValidationError.value = '';
            showToast('✅ Employee validated: ' + result.name, 'success');
        } else {
            employeeValidationError.value = result.message || 'Employee ID not found';
            employeeName.value = '';
            showToast('❌ ' + (result.message || 'Employee ID not found'), 'danger');
        }
    } catch (error) {
        console.error('Error validating employee:', error);
        employeeValidationError.value = 'Failed to validate employee - check console for details';
        employeeName.value = '';
        showToast('❌ Failed to validate employee', 'danger');
    } finally {
        isValidatingEmployee.value = false;
    }
};

const handleSubmit = async () => {
    // Validation
    if (!employeeId.value.trim() || !employeeName.value) {
        showToast('⚠️ Please validate Employee ID', 'warning');
        return;
    }
    
    // Check if there's a pending request
    if (hasPendingRequest.value) {
        showToast('⚠️ Cannot create request - there is already a pending request for this machine', 'warning');
        return;
    }

    isSaving.value = true;
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        if (!csrfToken) {
            console.error('CSRF token not found in meta tag');
            showToast('❌ Session error - please refresh the page', 'danger');
            isSaving.value = false;
            return;
        }

        const response = await fetch('/api/lot-request', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                mc_no: props.machineNo,
                line: props.machineLine,
                area: props.machineArea,
                requestor: employeeName.value,
                request_model: requestedModel.value,
                lot_no: null, // Will be assigned later
                model: null, // Will be determined later
                quantity: null, // Will be determined later
                lipas: null, // Will be determined when lot is assigned
                lot_tat: null,
                lot_location: null,
                remarks: remarks.value.trim() || null,
            }),
        });

        const result = await response.json();

        if (response.ok && result.success) {
            showToast('✅ Lot request created successfully', 'success');
            emit('created');
            handleClose();
        } else {
            showToast('❌ ' + (result.message || 'Failed to create lot request'), 'danger');
        }
    } catch (error) {
        console.error('Error creating lot request:', error);
        showToast('❌ Failed to create lot request', 'danger');
    } finally {
        isSaving.value = false;
    }
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

// Watch for modal open
watch(() => props.open, async (newValue) => {
    if (newValue) {
        // Set requested model - use previous model since ongoing_lot contains lot number, not model
        requestedModel.value = props.previousModel || 'N/A';
        
        // Check for pending requests
        await checkPendingRequest();
        
        // Focus on employee ID field after modal opens
        await nextTick();
        setTimeout(() => {
            const inputElement = employeeIdInput.value?.$el?.querySelector('input') || employeeIdInput.value?.$el;
            if (inputElement && typeof inputElement.focus === 'function') {
                inputElement.focus();
            }
        }, 150);
    } else {
        resetForm();
    }
});
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="!max-w-[650px] max-h-[90vh] overflow-hidden flex flex-col p-0 gap-0 shadow-2xl">
            <!-- Header -->
            <DialogHeader class="px-6 py-5 border-b border-border/50 bg-gradient-to-br from-purple-500 via-purple-600 to-indigo-600 dark:from-purple-700 dark:via-purple-800 dark:to-indigo-800">
                <DialogTitle class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg ring-2 ring-white/30">
                        <span class="text-3xl">📋</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h2 class="text-xl font-bold text-white drop-shadow-sm">Create Lot Request for Machine</h2>
                            <span class="inline-flex items-center px-3.5 py-1.5 rounded-lg text-sm font-bold bg-white/95 text-blue-700 shadow-md ring-1 ring-white/50">
                                {{ machineNo }}
                            </span>
                            <span class="inline-flex items-center px-3.5 py-1.5 rounded-lg text-sm font-bold bg-white/95 text-green-700 shadow-md ring-1 ring-white/50">
                                {{ machineArea }}
                            </span>
                        </div>
                    </div>
                </DialogTitle>
                <DialogDescription class="sr-only">Create a new lot request for machine {{ machineNo }}</DialogDescription>
            </DialogHeader>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto px-6 py-6 bg-gradient-to-br from-slate-50 via-white to-slate-50 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900">
                <!-- Pending Request Warning -->
                <div v-if="isCheckingPending" class="mb-6 flex items-center gap-3 px-4 py-3 bg-blue-50 dark:bg-blue-950/30 border-2 border-blue-300 dark:border-blue-700 rounded-xl">
                    <span class="animate-spin text-blue-600 dark:text-blue-400">⟳</span>
                    <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">Checking for pending requests...</span>
                </div>
                
                <div v-if="hasPendingRequest && pendingRequestInfo" class="mb-6 space-y-3 px-5 py-4 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/30 border-2 border-amber-400 dark:border-amber-600 rounded-xl shadow-md">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">⚠️</span>
                        <div class="flex-1">
                            <h3 class="text-base font-bold text-amber-800 dark:text-amber-300 mb-2">Pending Request Already Exists</h3>
                            <div class="space-y-1.5 text-sm text-amber-700 dark:text-amber-400">
                                <p><span class="font-semibold">Requestor:</span> {{ pendingRequestInfo.requestor || 'N/A' }}</p>
                                <p><span class="font-semibold">Model:</span> {{ pendingRequestInfo.request_model || 'N/A' }}</p>
                                <p><span class="font-semibold">Status:</span> {{ pendingRequestInfo.status || 'Pending' }}</p>
                                <p v-if="pendingRequestInfo.created_at" class="text-xs"><span class="font-semibold">Created:</span> {{ new Date(pendingRequestInfo.created_at).toLocaleString() }}</p>
                            </div>
                            <p class="mt-3 text-sm font-semibold text-amber-900 dark:text-amber-200">
                                You cannot create a new request until the existing one is completed or cancelled.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-6" :class="{ 'opacity-50 pointer-events-none': hasPendingRequest }">
                    <!-- Requested Model (Editable) -->
                    <div class="space-y-2.5">
                        <Label class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                            Requested Model
                        </Label>
                        <Input
                            v-model="requestedModel"
                            type="text"
                            placeholder="Enter model number"
                            class="h-20 border-2 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 shadow-lg hover:shadow-xl transition-all duration-200 bg-gradient-to-r from-blue-50 via-purple-50 to-pink-50 dark:from-blue-950/40 dark:via-purple-950/40 dark:to-pink-950/40 placeholder:text-slate-400 dark:placeholder:text-slate-500 placeholder:text-lg"
                            style="font-size: 1.875rem; font-weight: 900; line-height: 2.25rem; background-image: linear-gradient(to right, rgb(37, 99, 235), rgb(147, 51, 234), rgb(219, 39, 119)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; caret-color: rgb(147, 51, 234);"
                        />
                    </div>

                    <!-- Employee ID (Active Focus) -->
                    <div class="space-y-2.5">
                        <Label class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                            Employee ID <span class="text-red-500 ml-1">*</span>
                        </Label>
                        <div class="flex gap-2.5">
                            <Input
                                ref="employeeIdInput"
                                v-model="employeeId"
                                type="text"
                                placeholder="Enter your employee ID"
                                class="flex-1 h-12 text-base border-2 shadow-sm hover:shadow-md transition-all duration-200"
                                :class="{ 
                                    'border-red-400 focus:border-red-500 focus:ring-red-500/20 bg-red-50/50 dark:bg-red-950/20': employeeValidationError, 
                                    'border-green-400 focus:border-green-500 focus:ring-green-500/20 bg-green-50/50 dark:bg-green-950/20': employeeName,
                                    'focus:border-purple-500 focus:ring-purple-500/20': !employeeValidationError && !employeeName
                                }"
                                @keyup.enter="validateEmployeeId"
                            />
                            <Button 
                                size="sm" 
                                variant="outline"
                                @click="validateEmployeeId" 
                                :disabled="isValidatingEmployee || !employeeId.trim()"
                                class="h-12 px-5 font-bold shrink-0 shadow-sm hover:shadow-md transition-all duration-200"
                                :class="{
                                    'bg-green-50 border-2 border-green-500 text-green-700 hover:bg-green-100 dark:bg-green-950/30 dark:border-green-600 dark:text-green-400': employeeName,
                                    'border-2 hover:border-purple-500': !employeeName
                                }"
                            >
                                <span v-if="isValidatingEmployee" class="animate-spin text-base">⟳</span>
                                <span v-else-if="employeeName" class="text-base">✓</span>
                                <span v-else class="text-sm">Verify</span>
                            </Button>
                        </div>
                        
                        <!-- Validation Feedback -->
                        <div v-if="employeeName" class="flex items-center gap-2.5 px-4 py-3 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-950/30 dark:to-emerald-950/30 border-2 border-green-300 dark:border-green-700 rounded-xl shadow-sm">
                            <span class="text-green-600 dark:text-green-400 text-base">✓</span>
                            <span class="text-sm font-semibold text-green-700 dark:text-green-300">{{ employeeName }}</span>
                        </div>
                        <div v-else-if="employeeValidationError" class="flex items-center gap-2.5 px-4 py-3 bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-950/30 dark:to-rose-950/30 border-2 border-red-300 dark:border-red-700 rounded-xl shadow-sm">
                            <span class="text-red-600 dark:text-red-400 text-base">✗</span>
                            <span class="text-sm font-semibold text-red-700 dark:text-red-300">{{ employeeValidationError }}</span>
                        </div>
                    </div>

                    <!-- Remarks / Notes -->
                    <div class="space-y-2.5">
                        <Label class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                            Remarks / Notes
                        </Label>
                        <Textarea
                            v-model="remarks"
                            placeholder="Enter any additional notes or special instructions..."
                            class="min-h-[110px] resize-none text-base border-2 focus:border-purple-500 focus:ring-purple-500/20 shadow-sm hover:shadow-md transition-all duration-200"
                        />
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-border/50 bg-gradient-to-r from-slate-50 via-white to-slate-50 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 flex items-center justify-between shadow-inner">
                <div class="text-xs font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <span class="text-red-500 text-sm">*</span> 
                    <span>Required field</span>
                </div>
                <div class="flex items-center gap-3">
                    <Button 
                        variant="outline" 
                        @click="handleClose" 
                        :disabled="isSaving"
                        class="h-11 px-6 font-semibold border-2 hover:border-slate-400 shadow-sm hover:shadow-md transition-all duration-200"
                    >
                        Cancel
                    </Button>
                    <Button 
                        @click="handleSubmit" 
                        :disabled="!employeeName || isSaving || hasPendingRequest"
                        class="h-11 px-6 font-bold bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 shadow-md hover:shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="isSaving" class="flex items-center gap-2">
                            <span class="animate-spin text-base">⟳</span>
                            Creating...
                        </span>
                        <span v-else class="flex items-center gap-2">
                            <span>✨</span>
                            Create Request
                        </span>
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
