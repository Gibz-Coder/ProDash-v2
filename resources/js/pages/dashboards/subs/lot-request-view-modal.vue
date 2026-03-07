<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import axios from 'axios';

interface LotRequest {
    id: number;
    request_no: string;
    mc_no: string;
    line: string;
    area: string;
    requestor: string;
    request_model?: string;
    lot_no: string;
    model: string;
    quantity: number;
    lipas: 'Y' | 'N';
    lot_tat?: string;
    lot_location?: string;
    requested: string;
    completed?: string;
    response_time?: string;
    status: 'PENDING' | 'COMPLETED' | 'REJECTED';
    remarks?: string;
    eqp_maker?: string;
    insp_type?: string;
    mc_rack?: string;
}

interface Props {
    open: boolean;
    request: LotRequest | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'refresh'): void;
}>();

const rejectRemarks = ref('');
const rejectEmployeeId = ref('');
const rejectEmployeeName = ref('');
const isRejecting = ref(false);
const isValidatingRejectEmployee = ref(false);
const equipmentData = ref<{ eqp_maker?: string; insp_type?: string; size?: string; mc_rack?: string } | null>(null);
const isLoadingEquipment = ref(false);

const handleClose = () => {
    emit('update:open', false);
    rejectRemarks.value = '';
    rejectEmployeeId.value = '';
    rejectEmployeeName.value = '';
};

const machineType = computed(() => {
    if (equipmentData.value?.eqp_maker && equipmentData.value?.insp_type) {
        return `${equipmentData.value.eqp_maker}-${equipmentData.value.insp_type}`;
    }
    return '-';
});

const machineSize = computed(() => {
    if (!equipmentData.value?.size) return '-';
    
    const sizeMapping: Record<string, string> = {
        '03': '0603',
        '05': '1005',
        '10': '1608',
        '21': '2012',
        '31': '3216',
        '32': '3225',
    };
    
    return sizeMapping[equipmentData.value.size] || equipmentData.value.size;
});

const selectRackText = (event: MouseEvent) => {
    const target = event.currentTarget as HTMLElement;
    const textElement = target.querySelector('.rack-text') as HTMLElement;
    
    if (textElement) {
        const range = document.createRange();
        range.selectNodeContents(textElement);
        const selection = window.getSelection();
        selection?.removeAllRanges();
        selection?.addRange(range);
        
        showToast('📋 Rack selected - Press Ctrl+C to copy', 'info');
    }
};

const fetchEquipmentData = async () => {
    if (!props.request?.mc_no) return;
    
    isLoadingEquipment.value = true;
    try {
        const response = await axios.get(`/api/equipment/details/${props.request.mc_no}`);
        equipmentData.value = response.data;
    } catch (error) {
        console.error('Error fetching equipment data:', error);
        equipmentData.value = null;
    } finally {
        isLoadingEquipment.value = false;
    }
};

const getStatusColor = (status: string) => {
    const colors = {
        PENDING: 'bg-[hsl(253_175_34%)] text-white',
        COMPLETED: 'bg-[hsl(142_76%_36%)] text-white',
        REJECTED: 'bg-destructive text-destructive-foreground'
    };
    return colors[status as keyof typeof colors];
};

const getLipasColor = (lipas: string) => {
    return lipas === 'Y' 
        ? 'bg-destructive text-destructive-foreground' 
        : 'bg-muted text-muted-foreground';
};

const formatDateTime = (datetime: string | undefined) => {
    if (!datetime) return '-';
    
    try {
        const date = new Date(datetime);
        const options: Intl.DateTimeFormatOptions = {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        };
        
        return date.toLocaleString('en-US', options);
    } catch (error) {
        return datetime;
    }
};

const validateRejectEmployeeId = async () => {
    const empId = rejectEmployeeId.value.trim();
    
    if (!empId) {
        rejectEmployeeName.value = '';
        return;
    }

    isValidatingRejectEmployee.value = true;

    try {
        const response = await axios.post('/api/mems/validate-employee', {
            employee_id: empId,
        }, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.data.valid) {
            rejectEmployeeName.value = response.data.name;
            showToast('✅ Employee validated: ' + response.data.name, 'success');
        } else {
            rejectEmployeeName.value = '';
            showToast('❌ ' + (response.data.message || 'Employee ID not found'), 'danger');
        }
    } catch (error) {
        console.error('Error validating employee:', error);
        rejectEmployeeName.value = '';
        showToast('❌ Failed to validate employee', 'danger');
    } finally {
        isValidatingRejectEmployee.value = false;
    }
};

const handleReject = async () => {
    if (!props.request) return;
    
    // Validate employee ID
    if (!rejectEmployeeId.value.trim()) {
        showToast('⚠️ Please enter Employee ID', 'warning');
        return;
    }
    
    if (!rejectEmployeeName.value) {
        showToast('⚠️ Please validate Employee ID first', 'warning');
        return;
    }
    
    // Validate reject remarks
    const trimmedRemarks = rejectRemarks.value.trim();
    
    if (!trimmedRemarks) {
        showToast('⚠️ Please enter a reason for rejection', 'warning');
        return;
    }
    
    if (trimmedRemarks.length < 5) {
        showToast('⚠️ Rejection reason must be at least 5 characters', 'warning');
        return;
    }
    
    // Check if remarks contain only repeated characters or dots
    const uniqueChars = new Set(trimmedRemarks.replace(/\s/g, '')).size;
    if (uniqueChars < 3) {
        showToast('⚠️ Please provide a meaningful rejection reason', 'warning');
        return;
    }
    
    if (!confirm('Are you sure you want to reject this request?')) return;
    
    isRejecting.value = true;
    
    try {
        const response = await axios.post(`/api/lot-request/${props.request.id}/reject`, {
            remarks: trimmedRemarks,
            rejected_by: rejectEmployeeName.value
        });
        
        if (response.data.success) {
            showToast('✅ Request rejected successfully', 'success');
            emit('refresh');
            handleClose();
        } else {
            showToast('❌ Failed to reject request', 'danger');
        }
    } catch (error) {
        console.error('Error rejecting request:', error);
        showToast('❌ Failed to reject request', 'danger');
    } finally {
        isRejecting.value = false;
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

// Watch for modal open and fetch equipment data
watch(() => props.open, (newValue) => {
    if (newValue && props.request) {
        fetchEquipmentData();
    }
});
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="!max-w-[750px] max-h-[85vh] overflow-hidden flex flex-col p-0 gap-0 shadow-2xl">
            <!-- Header -->
            <DialogHeader class="px-5 py-3 border-b border-border/50 bg-gradient-to-br from-indigo-500 via-purple-600 to-pink-600 dark:from-indigo-700 dark:via-purple-800 dark:to-pink-800">
                <DialogTitle class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg ring-2 ring-white/30">
                        <span class="text-2xl">📋</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg font-bold text-white drop-shadow-sm">Lot Request Details</h2>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-white/95 text-indigo-700 shadow-md ring-1 ring-white/50">
                                {{ request?.request_no }}
                            </span>
                            <Badge v-if="request" :class="getStatusColor(request.status)" class="text-xs px-2.5 py-0.5">
                                {{ request.status }}
                            </Badge>
                            <!-- Rack Badge with Select Function -->
                            <div
                                v-if="equipmentData?.mc_rack"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-white/95 text-orange-700 shadow-md ring-1 ring-white/50 cursor-pointer hover:bg-white transition-colors"
                                @click="selectRackText"
                                title="Click to select rack location, then Ctrl+C to copy"
                            >
                                <span class="text-[10px]">📍</span>
                                <span>RACK: <span class="rack-text">{{ equipmentData.mc_rack }}</span></span>
                                <span class="text-[10px] opacity-70">📋</span>
                            </div>
                        </div>
                    </div>
                </DialogTitle>
                <DialogDescription class="sr-only">View lot request details for {{ request?.request_no }}</DialogDescription>
            </DialogHeader>

            <!-- Content -->
            <div v-if="request" class="flex-1 overflow-y-auto px-5 py-4 bg-gradient-to-br from-slate-50 via-white to-slate-50 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900">
                <div class="grid grid-cols-2 gap-4">
                    <!-- Left Column -->
                    <div class="space-y-3">
                        <!-- Request Information -->
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-3 border border-pink-200 dark:border-pink-800 shadow-sm">
                            <h3 class="text-xs font-bold text-pink-700 dark:text-pink-300 uppercase tracking-wide mb-2 flex items-center gap-1.5">
                                <span class="text-sm">👤</span>
                                Request Information
                            </h3>
                            <div class="space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">Requestor:</span>
                                    <span class="text-xs font-semibold text-foreground">{{ request.requestor }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">Requested:</span>
                                    <span class="text-xs font-semibold text-foreground">{{ formatDateTime(request.requested) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">Completed:</span>
                                    <span class="text-xs font-semibold text-foreground">{{ formatDateTime(request.completed) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">
                                        {{ request.status === 'PENDING' ? 'Waiting Time:' : 'Response Time:' }}
                                    </span>
                                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">{{ request.response_time || '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Lot Information -->
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-3 border border-purple-200 dark:border-purple-800 shadow-sm">
                            <h3 class="text-xs font-bold text-purple-700 dark:text-purple-300 uppercase tracking-wide mb-2 flex items-center gap-1.5">
                                <span class="text-sm">📦</span>
                                Lot Information
                            </h3>
                            <div class="space-y-1.5">
                                <!-- Requested Model -->
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">Request Model:</span>
                                    <span class="text-xs font-semibold text-foreground">{{ request.request_model || '-' }}</span>
                                </div>
                                
                                <!-- Separator -->
                                <div class="border-t border-purple-200 dark:border-purple-700 my-1.5"></div>
                                
                                <!-- Assigned Lot Section -->
                                <div class="pt-0.5">
                                    <span class="text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase">Assigned Lot</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">Lot No:</span>
                                    <span class="text-xs font-semibold text-foreground">{{ request.lot_no || '-' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">Model:</span>
                                    <span class="text-xs font-semibold text-foreground">{{ request.lot_no ? request.model : '-' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">Quantity:</span>
                                    <span class="text-xs font-semibold text-foreground">{{ request.lot_no && request.quantity ? request.quantity.toLocaleString() : '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-3">
                        <!-- Machine Information -->
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-3 border border-indigo-200 dark:border-indigo-800 shadow-sm">
                            <h3 class="text-xs font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wide mb-2 flex items-center gap-1.5">
                                <span class="text-sm">🏭</span>
                                Machine Information
                            </h3>
                            <div class="space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">Machine No:</span>
                                    <span class="text-xs font-semibold text-foreground">{{ request.mc_no }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">Machine Type:</span>
                                    <span v-if="isLoadingEquipment" class="text-xs text-muted-foreground">Loading...</span>
                                    <span v-else class="text-xs font-semibold text-foreground">{{ machineType }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">Size:</span>
                                    <span v-if="isLoadingEquipment" class="text-xs text-muted-foreground">Loading...</span>
                                    <Badge v-else variant="outline" class="text-xs font-semibold h-5">{{ machineSize }}</Badge>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-muted-foreground">Area:</span>
                                    <Badge variant="outline" class="text-xs h-5">{{ request.area }}</Badge>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-3 border border-slate-200 dark:border-slate-700 shadow-sm">
                            <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide mb-2 flex items-center gap-1.5">
                                <span class="text-sm">📝</span>
                                Remarks
                            </h3>
                            <div class="text-xs text-muted-foreground bg-slate-50 dark:bg-slate-900 rounded-lg p-2 h-[100px] overflow-auto">
                                {{ request.remarks || 'No remarks' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reject Section (Only for PENDING status) -->
                <div v-if="request.status === 'PENDING'" class="mt-4 bg-red-50 dark:bg-red-950/30 rounded-lg p-3 border border-red-200 dark:border-red-800">
                    <h3 class="text-xs font-bold text-red-700 dark:text-red-300 uppercase tracking-wide mb-2 flex items-center gap-1.5">
                        <span class="text-sm">⚠️</span>
                        Reject Request
                    </h3>
                    <div class="space-y-2">
                        <div>
                            <Textarea
                                v-model="rejectRemarks"
                                placeholder="Enter reason for rejection.."
                                class="mt-1 min-h-[60px] resize-none text-xs border-red-300 dark:border-red-700 focus:border-red-500 focus:ring-red-500/20"
                                required
                            />
                        </div>
                        
                        <!-- Employee ID and Reject Button Row -->
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <Label class="text-xs font-semibold text-red-700 dark:text-red-300">
                                    Rejected By <span class="text-red-500">*</span>
                                </Label>
                                <div class="flex gap-1 mt-1">
                                    <Input
                                        v-model="rejectEmployeeId"
                                        type="text"
                                        placeholder="Employee ID"
                                        class="flex-1 h-9 text-xs border-red-300 dark:border-red-700 focus:border-red-500 focus:ring-red-500/20"
                                        :class="{ 
                                            'border-green-400 bg-green-50/50 dark:bg-green-950/20': rejectEmployeeName
                                        }"
                                        @keyup.enter="validateRejectEmployeeId"
                                    />
                                    <Button 
                                        size="sm" 
                                        variant="outline"
                                        @click="validateRejectEmployeeId" 
                                        :disabled="isValidatingRejectEmployee || !rejectEmployeeId.trim()"
                                        class="h-9 px-2 text-xs"
                                        :class="{
                                            'bg-green-50 border-green-400 text-green-700': rejectEmployeeName
                                        }"
                                    >
                                        <span v-if="isValidatingRejectEmployee" class="animate-spin text-xs">⟳</span>
                                        <span v-else-if="rejectEmployeeName" class="text-xs">✓</span>
                                        <span v-else class="text-[10px]">Verify</span>
                                    </Button>
                                </div>
                                <p v-if="rejectEmployeeName" class="text-[10px] text-green-600 dark:text-green-400 mt-0.5">
                                    ✓ {{ rejectEmployeeName }}
                                </p>
                            </div>
                            
                            <Button 
                                @click="handleReject"
                                :disabled="isRejecting || rejectRemarks.trim().length < 5 || !rejectEmployeeName"
                                variant="destructive"
                                class="h-9 px-4 font-bold text-xs whitespace-nowrap"
                            >
                                <span v-if="isRejecting" class="flex items-center gap-1.5">
                                    <span class="animate-spin text-sm">⟳</span>
                                    Rejecting...
                                </span>
                                <span v-else class="flex items-center gap-1.5">
                                    <span>🗑️</span>
                                    Reject Request
                                </span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 border-t border-border/50 bg-gradient-to-r from-slate-50 via-white to-slate-50 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 flex items-center justify-end shadow-inner">
                <Button 
                    variant="outline" 
                    @click="handleClose"
                    class="h-9 px-5 font-semibold border-2 hover:border-slate-400 shadow-sm hover:shadow-md transition-all duration-200 text-sm"
                >
                    Close
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
