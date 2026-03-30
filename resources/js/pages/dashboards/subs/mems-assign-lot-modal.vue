<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { usePage } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronDown } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

// Get auth from shared Inertia page props
const page = usePage();
const auth = computed(
    () => page.props.auth as { user: any; permissions: string[] },
);

interface Lot {
    lot_no: string;
    lot_model: string;
    lot_qty: number;
    qty_class: string;
    stagnant_tat: number | string;
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
    equipmentLine: string | null;
    equipmentArea: string | null;
    previousModel: string | null;
    previousWorktype: string | null;
    ongoingLot: string | null;
    estEndtime: string | null;
    waitingTime: string | null;
    requestId?: number | null; // ID of the lot request to update
    mcRack?: string | null; // Machine rack location
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'assign', lot: Lot): void;
}>();

const isLoading = ref(false);
const availableLots = ref<Lot[]>([]);
const totalAvailable = ref(0);
const isLimitReached = ref(false);
const modelFilter = ref('');
const employeeId = ref('');
const employeeName = ref('');
const isValidatingEmployee = ref(false);
const employeeValidationError = ref('');
const employeeIdInput = ref<HTMLInputElement | null>(null);
const keepRequestor = ref(false); // Checkbox to keep requestor fixed

// Sorting state - Default sort by TAT descending (highest first)
const sortColumn = ref<string | null>('stagnant_tat');
const sortDirection = ref<'asc' | 'desc'>('desc');

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
        totalAvailable.value = result.total_available || result.count || 0;
        isLimitReached.value = result.data && result.data.length >= 1000;
    } catch (error) {
        console.error('Error fetching available lots:', error);
        availableLots.value = [];
    } finally {
        isLoading.value = false;
    }
};

const handleAssign = async (lot: Lot) => {
    // Validate employee ID first
    if (!employeeId.value.trim()) {
        showToast('⚠️ Please enter Employee ID', 'warning');
        return;
    }

    if (!employeeName.value) {
        showToast('⚠️ Please validate Employee ID first', 'warning');
        return;
    }

    try {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        if (!csrfToken) {
            console.error('CSRF token not found in meta tag');
            showToast('❌ Session error - please refresh the page', 'danger');
            return;
        }

        // If requestId is provided, update existing request (assign lot)
        if (props.requestId) {
            const response = await fetch(
                `/api/lot-request/${props.requestId}/assign`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        lot_no: lot.lot_no,
                        model: lot.lot_model,
                        quantity: lot.lot_qty,
                        lipas: lot.lipas_yn,
                        lot_tat: lot.stagnant_tat?.toString(),
                        lot_location: lot.lot_location,
                        response_by: employeeName.value,
                    }),
                },
            );

            const result = await response.json();

            if (response.ok && result.success) {
                showToast('✅ Lot assigned successfully', 'success');
                emit('assign', lot);
                handleClose();
            } else {
                showToast(
                    '❌ ' + (result.message || 'Failed to assign lot'),
                    'danger',
                );
            }
        } else {
            // Create new lot request (original behavior)
            const response = await fetch('/api/mems/lot-request', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    mc_no: props.equipmentNo,
                    line: props.equipmentLine,
                    area: props.equipmentArea,
                    requestor_id: employeeId.value.trim(),
                    lot_no: lot.lot_no,
                    model: lot.lot_model,
                    quantity: lot.lot_qty,
                    lipas: lot.lipas_yn,
                    lot_tat: lot.stagnant_tat?.toString(),
                    lot_location: lot.lot_location,
                }),
            });

            const result = await response.json();

            if (response.ok && result.success) {
                showToast('✅ Lot request submitted successfully', 'success');
                emit('assign', lot);
                handleClose();
            } else {
                showToast(
                    '❌ ' + (result.message || 'Failed to submit lot request'),
                    'danger',
                );
            }
        }
    } catch (error) {
        console.error('Error processing lot assignment:', error);
        showToast('❌ Failed to process request', 'danger');
    }
};

const showToast = (
    message: string,
    type: 'success' | 'danger' | 'info' | 'warning' = 'info',
) => {
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
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        if (!csrfToken) {
            console.error('CSRF token not found in meta tag');
            employeeValidationError.value =
                'Session error - please refresh the page';
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
                Accept: 'application/json',
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
            employeeValidationError.value =
                result.message || 'Employee ID not found';
            employeeName.value = '';
            showToast(
                '❌ ' + (result.message || 'Employee ID not found'),
                'danger',
            );
        }
    } catch (error) {
        console.error('Error validating employee:', error);
        employeeValidationError.value =
            'Failed to validate employee - check console for details';
        employeeName.value = '';
        showToast('❌ Failed to validate employee', 'danger');
    } finally {
        isValidatingEmployee.value = false;
    }
};

const useMyId = async () => {
    // Get the current user's employee ID
    const currentUser = auth.value?.user;

    if (!currentUser) {
        console.error('Auth context not available:', auth.value);
        showToast(
            '⚠️ User session not loaded - please refresh the page',
            'warning',
        );
        return;
    }

    // Use emp_no field from User model
    const empId = currentUser.emp_no;

    if (!empId) {
        console.error('Employee ID not found in user object:', currentUser);
        showToast('⚠️ Your employee ID is not available', 'warning');
        return;
    }

    // Set the employee ID and validate it
    employeeId.value = empId;
    await validateEmployeeId();
};

const clearRequestor = () => {
    employeeId.value = '';
    employeeName.value = '';
    employeeValidationError.value = '';
    keepRequestor.value = false;
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
    // Always sort, even if sortColumn is null (use default TAT sorting)
    const columnToSort = sortColumn.value || 'stagnant_tat';
    const directionToUse = sortColumn.value ? sortDirection.value : 'desc';

    const sorted = [...availableLots.value].sort((a, b) => {
        let aVal: any = a[columnToSort as keyof Lot];
        let bVal: any = b[columnToSort as keyof Lot];

        // Handle numeric columns
        if (columnToSort === 'lot_qty' || columnToSort === 'stagnant_tat') {
            aVal = Number(aVal) || 0;
            bVal = Number(bVal) || 0;
        } else {
            // Handle string columns
            aVal = String(aVal || '').toLowerCase();
            bVal = String(bVal || '').toLowerCase();
        }

        if (aVal < bVal) return directionToUse === 'asc' ? -1 : 1;
        if (aVal > bVal) return directionToUse === 'asc' ? 1 : -1;
        return 0;
    });

    return sorted;
});

const machineStatus = computed(() => {
    if (props.ongoingLot && props.ongoingLot.trim() !== '') {
        return {
            status: 'Running',
            label: 'Running',
            bgClass: 'bg-green-100 dark:bg-green-900/30',
            textClass: 'text-green-700 dark:text-green-300',
            borderClass: 'border-green-300 dark:border-green-700',
            icon: '▶️',
        };
    } else {
        return {
            status: 'Waiting',
            label: 'Waiting',
            bgClass: 'bg-amber-100 dark:bg-amber-900/30',
            textClass: 'text-amber-700 dark:text-amber-300',
            borderClass: 'border-amber-300 dark:border-amber-700',
            icon: '⏸️',
        };
    }
});

const timeInfo = computed(() => {
    if (props.ongoingLot && props.ongoingLot.trim() !== '') {
        // Running - show estimated endtime
        const endtime = props.estEndtime || 'N/A';
        return {
            label: 'Endtime',
            value: endtime !== 'N/A' ? formatEndtime(endtime) : 'N/A',
            bgClass: 'bg-blue-100 dark:bg-blue-900/30',
            textClass: 'text-blue-700 dark:text-blue-300',
            icon: '⏰',
        };
    } else {
        // Waiting - show waiting time
        const waitTime = props.waitingTime || 'N/A';
        return {
            label: 'Waiting Time',
            value: waitTime,
            bgClass: 'bg-orange-100 dark:bg-orange-900/30',
            textClass: 'text-orange-700 dark:text-orange-300',
            icon: '⏳',
        };
    }
});

const formatEndtime = (endtime: string): string => {
    if (!endtime || endtime === 'N/A') return 'N/A';

    try {
        const date = new Date(endtime);
        if (isNaN(date.getTime())) return endtime;

        // Format: Feb 21, 2026 15:16
        const options: Intl.DateTimeFormatOptions = {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        };

        return date.toLocaleString('en-US', options);
    } catch (error) {
        return endtime;
    }
};

const formatNumber = (value: number): string => {
    return new Intl.NumberFormat().format(value);
};

const getSizeLabel = (size: string): string => {
    const mapping: Record<string, string> = {
        '03': '0603',
        '05': '1005',
        '10': '1608',
        '21': '2012',
        '31': '3216',
        '32': '3225',
    };
    return mapping[size] || size;
};

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

const selectLotNumber = (event: MouseEvent) => {
    const target = event.currentTarget as HTMLElement;

    const range = document.createRange();
    range.selectNodeContents(target);
    const selection = window.getSelection();
    selection?.removeAllRanges();
    selection?.addRange(range);

    showToast('📋 Lot number selected - Press Ctrl+C to copy', 'info');
};

// Watch for modal open to fetch data
watch(
    () => props.open,
    async (newValue) => {
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

            // Focus on employee ID field after modal opens
            await nextTick();
            setTimeout(() => {
                const inputElement =
                    employeeIdInput.value?.$el?.querySelector('input') ||
                    employeeIdInput.value?.$el;
                if (inputElement && typeof inputElement.focus === 'function') {
                    inputElement.focus();
                }
            }, 150);
        } else {
            modelFilter.value = '';

            // Only clear requestor if keepRequestor is false
            if (!keepRequestor.value) {
                employeeId.value = '';
                employeeName.value = '';
                employeeValidationError.value = '';
            }

            wipStatusFilter.value = 'Newlot Standby';
            worktypeFilter.value = 'NORMAL';
        }
    },
);

// Fetch filter options on mount
onMounted(() => {
    fetchFilterOptions();
});
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent
            class="flex max-h-[90vh] !max-w-[1200px] flex-col gap-0 overflow-hidden p-0"
        >
            <!-- Header -->
            <DialogHeader
                class="border-b border-border/50 bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 px-6 py-5 dark:from-blue-700 dark:via-blue-800 dark:to-indigo-800"
            >
                <DialogTitle class="flex items-center gap-4">
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 shadow-lg ring-2 ring-white/30 backdrop-blur-sm"
                    >
                        <span class="text-3xl">📦</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h2
                                class="text-xl font-bold text-white drop-shadow-sm"
                            >
                                Lot Assignment for Machine
                            </h2>
                            <span
                                class="inline-flex items-center rounded-lg bg-white/95 px-3.5 py-1.5 text-sm font-bold text-blue-700 shadow-md ring-1 ring-white/50"
                            >
                                {{ equipmentNo }}
                            </span>
                            <span
                                v-if="equipmentArea"
                                class="inline-flex items-center rounded-lg bg-white/95 px-3.5 py-1.5 text-sm font-bold text-green-700 shadow-md ring-1 ring-white/50"
                            >
                                {{ equipmentArea }}
                            </span>
                            <!-- Rack Badge with Select Function -->
                            <div
                                v-if="mcRack"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-white/95 px-3.5 py-1.5 text-sm font-bold text-orange-700 shadow-md ring-1 ring-white/50"
                            >
                                <span class="text-xs">📍</span>
                                <span
                                    >RACK:
                                    <span class="rack-text">{{
                                        mcRack
                                    }}</span></span
                                >
                                <button
                                    @click="selectRackText"
                                    class="cursor-pointer p-1 text-xs opacity-70 transition-all duration-200 hover:scale-125 hover:opacity-100"
                                    title="Click to select rack location, then Ctrl+C to copy"
                                >
                                    📋
                                </button>
                            </div>
                            <!-- Machine Status Badge -->
                            <span
                                class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-sm font-bold shadow-md ring-1 ring-white/50"
                                :class="[
                                    machineStatus.bgClass,
                                    machineStatus.textClass,
                                ]"
                            >
                                <span>{{ machineStatus.icon }}</span>
                                <span>Status: {{ machineStatus.label }}</span>
                            </span>
                            <!-- Time Info Badge -->
                            <span
                                class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-bold shadow-md ring-1 ring-white/50"
                                :class="[timeInfo.bgClass, timeInfo.textClass]"
                            >
                                <span>{{ timeInfo.icon }}</span>
                                <span
                                    >{{ timeInfo.label }}:
                                    {{ timeInfo.value }}</span
                                >
                            </span>
                        </div>
                    </div>
                </DialogTitle>
                <DialogDescription class="sr-only"
                    >Request a lot assignment for equipment
                    {{ equipmentNo }}</DialogDescription
                >
            </DialogHeader>

            <!-- Filter Section -->
            <div
                class="border-b border-border/60 bg-gradient-to-br from-slate-50 via-blue-50/30 to-slate-50 px-6 py-4 dark:from-slate-900 dark:via-blue-950/20 dark:to-slate-900"
            >
                <div class="grid grid-cols-12 items-end gap-3">
                    <!-- Employee ID Filter -->
                    <div class="col-span-3 space-y-1.5">
                        <label
                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-slate-700 uppercase dark:text-slate-300"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-purple-500"
                            ></span>
                            response by:
                        </label>
                        <div class="flex gap-1.5">
                            <Input
                                ref="employeeIdInput"
                                v-model="employeeId"
                                type="text"
                                placeholder="Enter ID"
                                class="h-10 flex-1 text-sm shadow-sm transition-all duration-200"
                                :class="{
                                    'border-red-400 bg-red-50/50 focus:border-red-500 focus:ring-red-500/20 dark:bg-red-950/20':
                                        employeeValidationError,
                                    'border-green-400 bg-green-50/50 focus:border-green-500 focus:ring-green-500/20 dark:bg-green-950/20':
                                        employeeName,
                                    'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-slate-600':
                                        !employeeValidationError &&
                                        !employeeName,
                                }"
                                @keyup.enter="validateEmployeeId"
                            />
                            <Button
                                size="sm"
                                variant="outline"
                                @click="validateEmployeeId"
                                :disabled="
                                    isValidatingEmployee || !employeeId.trim()
                                "
                                class="h-10 shrink-0 px-3 font-medium shadow-sm transition-all duration-200 hover:shadow"
                                :class="{
                                    'border-green-400 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-600 dark:bg-green-950/30 dark:text-green-400':
                                        employeeName,
                                    'bg-white dark:bg-slate-800': !employeeName,
                                }"
                            >
                                <span
                                    v-if="isValidatingEmployee"
                                    class="animate-spin text-sm"
                                    >⟳</span
                                >
                                <span v-else-if="employeeName" class="text-sm"
                                    >✓</span
                                >
                                <span v-else class="text-xs">Verify</span>
                            </Button>
                        </div>
                    </div>

                    <!-- Model Filter -->
                    <div class="col-span-3 space-y-1.5">
                        <label
                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-slate-700 uppercase dark:text-slate-300"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-blue-500"
                            ></span>
                            Model
                        </label>
                        <Input
                            v-model="modelFilter"
                            type="text"
                            placeholder="e.g., CL21A2106..."
                            class="h-10 border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500/20 dark:border-slate-600"
                            @keyup.enter="handleSearch"
                        />
                    </div>

                    <!-- WIP Status Filter -->
                    <div class="col-span-2 space-y-1.5">
                        <label
                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-slate-700 uppercase dark:text-slate-300"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-green-500"
                            ></span>
                            Status
                        </label>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-10 w-full justify-between border-slate-300 text-xs font-medium shadow-sm transition-all duration-200 hover:shadow dark:border-slate-600"
                                >
                                    <span class="truncate">{{
                                        wipStatusFilter === 'ALL'
                                            ? 'All'
                                            : wipStatusFilter
                                    }}</span>
                                    <ChevronDown
                                        class="ml-1.5 h-3.5 w-3.5 shrink-0 opacity-50"
                                    />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="start"
                                class="max-h-64 w-56 overflow-y-auto"
                            >
                                <DropdownMenuItem
                                    @click="
                                        wipStatusFilter = 'ALL';
                                        handleSearch();
                                    "
                                    class="cursor-pointer"
                                >
                                    <span class="font-medium"
                                        >All Statuses</span
                                    >
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-for="status in wipStatusOptions"
                                    :key="status"
                                    @click="
                                        wipStatusFilter = status;
                                        handleSearch();
                                    "
                                    class="cursor-pointer"
                                >
                                    {{ status }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <!-- Worktype Filter -->
                    <div class="col-span-2 space-y-1.5">
                        <label
                            class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-slate-700 uppercase dark:text-slate-300"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-amber-500"
                            ></span>
                            Type
                        </label>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-10 w-full justify-between border-slate-300 text-xs font-medium shadow-sm transition-all duration-200 hover:shadow dark:border-slate-600"
                                >
                                    <span class="truncate">{{
                                        worktypeFilter === 'ALL'
                                            ? 'All'
                                            : worktypeFilter
                                    }}</span>
                                    <ChevronDown
                                        class="ml-1.5 h-3.5 w-3.5 shrink-0 opacity-50"
                                    />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="start"
                                class="max-h-64 w-48 overflow-y-auto"
                            >
                                <DropdownMenuItem
                                    @click="
                                        worktypeFilter = 'ALL';
                                        handleSearch();
                                    "
                                    class="cursor-pointer"
                                >
                                    <span class="font-medium">All Types</span>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-for="worktype in worktypeOptions"
                                    :key="worktype"
                                    @click="
                                        worktypeFilter = worktype;
                                        handleSearch();
                                    "
                                    class="cursor-pointer"
                                >
                                    {{ worktype }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <!-- Search Button -->
                    <div class="col-span-2">
                        <Button
                            size="sm"
                            @click="handleSearch"
                            :disabled="isLoading"
                            class="mt-[26px] h-10 w-full bg-gradient-to-r from-blue-600 to-blue-700 font-semibold shadow-md transition-all duration-200 hover:from-blue-700 hover:to-blue-800 hover:shadow-lg"
                        >
                            <span
                                v-if="isLoading"
                                class="flex items-center gap-2 text-xs"
                            >
                                <span class="animate-spin">⟳</span>
                                <span class="hidden sm:inline"
                                    >Searching...</span
                                >
                            </span>
                            <span
                                v-else
                                class="flex items-center gap-2 text-xs"
                            >
                                <span>🔍</span>
                                Search
                            </span>
                        </Button>
                    </div>
                </div>

                <!-- Employee Validation Feedback (Below the row) -->
                <div
                    v-if="employeeName || employeeValidationError"
                    class="mt-3"
                >
                    <div v-if="employeeName" class="flex items-center gap-3">
                        <div
                            class="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-3 py-2 dark:border-green-800 dark:bg-green-950/30"
                        >
                            <span
                                class="text-xs text-green-600 dark:text-green-400"
                                >✓</span
                            >
                            <span
                                class="text-xs font-medium text-green-700 dark:text-green-300"
                                >Requestor: {{ employeeName }}</span
                            >
                        </div>
                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-purple-200 bg-purple-50 px-3 py-2 transition-colors hover:bg-purple-100 dark:border-purple-800 dark:bg-purple-950/30 dark:hover:bg-purple-900/40"
                        >
                            <input
                                type="checkbox"
                                v-model="keepRequestor"
                                class="h-4 w-4 cursor-pointer rounded border-purple-300 bg-white text-purple-600 focus:ring-2 focus:ring-purple-500"
                            />
                            <span
                                class="text-xs font-medium text-purple-700 dark:text-purple-300"
                                >Keep for next request</span
                            >
                        </label>
                        <button
                            @click="clearRequestor"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 transition-colors hover:bg-red-100 dark:border-red-800 dark:bg-red-950/30 dark:hover:bg-red-900/40"
                            title="Clear requestor"
                        >
                            <span
                                class="text-sm font-bold text-red-600 dark:text-red-400"
                                >✕</span
                            >
                        </button>
                    </div>
                    <div
                        v-else-if="employeeValidationError"
                        class="flex w-fit items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 dark:border-red-800 dark:bg-red-950/30"
                    >
                        <span class="text-xs text-red-600 dark:text-red-400"
                            >✗</span
                        >
                        <span
                            class="text-xs font-medium text-red-700 dark:text-red-300"
                            >{{ employeeValidationError }}</span
                        >
                    </div>
                </div>
            </div>

            <!-- Content with sticky header -->
            <div class="flex flex-1 flex-col overflow-hidden bg-background">
                <div
                    v-if="isLoading"
                    class="flex items-center justify-center py-12"
                >
                    <div class="text-center">
                        <div
                            class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-b-2 border-blue-500"
                        ></div>
                        <p class="text-sm text-muted-foreground">
                            Loading available lots...
                        </p>
                    </div>
                </div>

                <div
                    v-else-if="availableLots.length === 0"
                    class="flex items-center justify-center py-12"
                >
                    <div class="text-center">
                        <span class="mb-4 block text-4xl">📭</span>
                        <p class="text-sm text-muted-foreground">
                            No available lots found
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Try adjusting the filters or search criteria
                        </p>
                    </div>
                </div>

                <div v-else class="flex flex-1 flex-col overflow-hidden">
                    <!-- Table Header - Sticky -->
                    <div
                        class="shrink-0 border-b border-border bg-background px-5 pt-4 pb-2"
                    >
                        <div
                            class="grid grid-cols-[0.9fr_1.6fr_0.9fr_0.7fr_0.5fr_0.9fr_1.3fr_0.5fr_0.5fr_1.1fr_1.1fr] gap-2 rounded-lg border border-blue-600 bg-gradient-to-r from-blue-500 to-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm"
                        >
                            <div
                                class="flex cursor-pointer items-center gap-1 transition-colors hover:text-blue-100"
                                @click="handleSort('lot_no')"
                            >
                                <span>Lot No.</span>
                                <ArrowUpDown
                                    v-if="sortColumn !== 'lot_no'"
                                    class="h-3 w-3 opacity-50"
                                />
                                <ArrowUp
                                    v-else-if="sortDirection === 'asc'"
                                    class="h-3 w-3"
                                />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div
                                class="flex cursor-pointer items-center gap-1 transition-colors hover:text-blue-100"
                                @click="handleSort('lot_model')"
                            >
                                <span>Model</span>
                                <ArrowUpDown
                                    v-if="sortColumn !== 'lot_model'"
                                    class="h-3 w-3 opacity-50"
                                />
                                <ArrowUp
                                    v-else-if="sortDirection === 'asc'"
                                    class="h-3 w-3"
                                />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div
                                class="flex cursor-pointer items-center justify-end gap-1 transition-colors hover:text-blue-100"
                                @click="handleSort('lot_qty')"
                            >
                                <span>Quantity</span>
                                <ArrowUpDown
                                    v-if="sortColumn !== 'lot_qty'"
                                    class="h-3 w-3 opacity-50"
                                />
                                <ArrowUp
                                    v-else-if="sortDirection === 'asc'"
                                    class="h-3 w-3"
                                />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div
                                class="flex cursor-pointer items-center justify-center gap-1 transition-colors hover:text-blue-100"
                                @click="handleSort('qty_class')"
                            >
                                <span>Qty Class</span>
                                <ArrowUpDown
                                    v-if="sortColumn !== 'qty_class'"
                                    class="h-3 w-3 opacity-50"
                                />
                                <ArrowUp
                                    v-else-if="sortDirection === 'asc'"
                                    class="h-3 w-3"
                                />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div
                                class="flex cursor-pointer items-center justify-center gap-1 transition-colors hover:text-blue-100"
                                @click="handleSort('stagnant_tat')"
                            >
                                <span>TAT</span>
                                <ArrowUpDown
                                    v-if="sortColumn !== 'stagnant_tat'"
                                    class="h-3 w-3 opacity-50"
                                />
                                <ArrowUp
                                    v-else-if="sortDirection === 'asc'"
                                    class="h-3 w-3"
                                />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div
                                class="flex cursor-pointer items-center justify-center gap-1 transition-colors hover:text-blue-100"
                                @click="handleSort('work_type')"
                            >
                                <span>Work Type</span>
                                <ArrowUpDown
                                    v-if="sortColumn !== 'work_type'"
                                    class="h-3 w-3 opacity-50"
                                />
                                <ArrowUp
                                    v-else-if="sortDirection === 'asc'"
                                    class="h-3 w-3"
                                />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div
                                class="flex cursor-pointer items-center justify-center gap-1 transition-colors hover:text-blue-100"
                                @click="handleSort('wip_status')"
                            >
                                <span>WIP Status</span>
                                <ArrowUpDown
                                    v-if="sortColumn !== 'wip_status'"
                                    class="h-3 w-3 opacity-50"
                                />
                                <ArrowUp
                                    v-else-if="sortDirection === 'asc'"
                                    class="h-3 w-3"
                                />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div
                                class="flex cursor-pointer items-center justify-center gap-1 transition-colors hover:text-blue-100"
                                @click="handleSort('auto_yn')"
                            >
                                <span>Auto</span>
                                <ArrowUpDown
                                    v-if="sortColumn !== 'auto_yn'"
                                    class="h-3 w-3 opacity-50"
                                />
                                <ArrowUp
                                    v-else-if="sortDirection === 'asc'"
                                    class="h-3 w-3"
                                />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div
                                class="flex cursor-pointer items-center justify-center gap-1 transition-colors hover:text-blue-100"
                                @click="handleSort('lipas_yn')"
                            >
                                <span>Lipas</span>
                                <ArrowUpDown
                                    v-if="sortColumn !== 'lipas_yn'"
                                    class="h-3 w-3 opacity-50"
                                />
                                <ArrowUp
                                    v-else-if="sortDirection === 'asc'"
                                    class="h-3 w-3"
                                />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div
                                class="flex cursor-pointer items-center justify-center gap-1 transition-colors hover:text-blue-100"
                                @click="handleSort('lot_location')"
                            >
                                <span>Location</span>
                                <ArrowUpDown
                                    v-if="sortColumn !== 'lot_location'"
                                    class="h-3 w-3 opacity-50"
                                />
                                <ArrowUp
                                    v-else-if="sortDirection === 'asc'"
                                    class="h-3 w-3"
                                />
                                <ArrowDown v-else class="h-3 w-3" />
                            </div>
                            <div class="text-center">Action</div>
                        </div>
                    </div>

                    <!-- Table Body - Scrollable -->
                    <div class="flex-1 overflow-y-auto px-5 pb-4">
                        <div class="space-y-1 pt-2">
                            <div
                                v-for="lot in sortedLots"
                                :key="lot.lot_no"
                                class="grid grid-cols-[0.9fr_1.6fr_0.9fr_0.7fr_0.5fr_0.9fr_1.3fr_0.5fr_0.5fr_1.1fr_1.1fr] items-center gap-2 rounded-lg border border-border p-2 transition-colors hover:bg-accent/50"
                            >
                                <div
                                    class="truncate text-xs font-semibold text-foreground"
                                    :title="lot.lot_no"
                                >
                                    {{ lot.lot_no }}
                                </div>
                                <div
                                    class="truncate text-xs text-muted-foreground"
                                    :title="lot.lot_model"
                                >
                                    {{ lot.lot_model }}
                                </div>
                                <div class="text-right text-xs font-medium">
                                    {{ formatNumber(lot.lot_qty) }}
                                </div>
                                <div class="text-center text-xs font-medium">
                                    {{ lot.qty_class || '-' }}
                                </div>
                                <div class="text-center text-xs">
                                    {{
                                        lot.stagnant_tat
                                            ? Number(lot.stagnant_tat).toFixed(
                                                  1,
                                              )
                                            : '-'
                                    }}
                                </div>
                                <div
                                    class="truncate text-center text-xs"
                                    :title="lot.work_type"
                                >
                                    {{ lot.work_type }}
                                </div>
                                <div class="text-center text-xs">
                                    <span
                                        class="inline-block max-w-full truncate rounded bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-300"
                                        :title="lot.wip_status"
                                    >
                                        {{ lot.wip_status }}
                                    </span>
                                </div>
                                <div class="text-center text-xs font-medium">
                                    {{ lot.auto_yn || '-' }}
                                </div>
                                <div class="text-center text-xs font-medium">
                                    {{ lot.lipas_yn || '-' }}
                                </div>
                                <div
                                    class="truncate text-center text-xs"
                                    :title="lot.lot_location"
                                >
                                    {{ lot.lot_location || '-' }}
                                </div>
                                <div class="text-center">
                                    <Button
                                        size="sm"
                                        variant="default"
                                        @click="handleAssign(lot)"
                                        class="h-8 px-3 text-xs"
                                    >
                                        {{ requestId ? 'Assign' : 'Request' }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex items-center justify-between border-t border-border bg-gradient-to-br from-slate-50 via-slate-100/50 to-slate-50 px-6 py-4 dark:from-slate-900 dark:via-slate-800/50 dark:to-slate-900"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-100 px-3 py-1.5 dark:border-blue-800 dark:bg-blue-900/30"
                    >
                        <span class="text-sm text-blue-600 dark:text-blue-400"
                            >📊</span
                        >
                        <span
                            class="text-sm font-semibold text-blue-700 dark:text-blue-300"
                        >
                            {{ availableLots.length }} lot(s) displayed
                            <span
                                v-if="totalAvailable > availableLots.length"
                                class="text-xs opacity-75"
                            >
                                ({{ totalAvailable }} total)
                            </span>
                        </span>
                    </div>
                    <div
                        v-if="isLimitReached"
                        class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-100 px-3 py-1.5 dark:border-amber-800 dark:bg-amber-900/30"
                    >
                        <span class="text-sm text-amber-600 dark:text-amber-400"
                            >⚠️</span
                        >
                        <span
                            class="text-xs font-medium text-amber-700 dark:text-amber-300"
                        >
                            Showing first 1000 lots. Use filters to narrow
                            results.
                        </span>
                    </div>
                </div>
                <Button
                    variant="outline"
                    @click="handleClose"
                    class="px-6 font-medium shadow-sm transition-all duration-200 hover:shadow"
                >
                    Cancel
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
