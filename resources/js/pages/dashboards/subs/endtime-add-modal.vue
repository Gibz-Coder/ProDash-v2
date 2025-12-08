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
const props = defineProps<{
    open: boolean;
    lot?: {
        id: number;
        lot_id: string;
        model_15: string;
        lot_qty: number;
        lot_size: string;
        lipas_yn: string;
        est_endtime: string;
        work_type: string;
        lot_type: string;
        eqp_line: string;
        eqp_area: string;
        eqp_1: string | null;
        eqp_2: string | null;
        eqp_3: string | null;
        eqp_4: string | null;
        eqp_5: string | null;
        eqp_6: string | null;
        eqp_7: string | null;
        eqp_8: string | null;
        eqp_9: string | null;
        eqp_10: string | null;
        status: string;
        created_at: string;
    } | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

// ============================================================================
// STATE
// ============================================================================
const isSubmitting = ref(false);

// Form data
const formData = ref({
    lot_id: '',
    lot_qty: '',
    lot_size: '10',
    lot_size_display: '',
    lot_type: 'MAIN',
    model_15: '',
    lipas_yn: 'N',
    work_type: 'NORMAL',
    no_rl_enabled: false,
    no_rl_minutes: 60,
    employee_id: '',
    employee_name: '',
    equipment: [
        {
            eqp_no: '',
            eqp_no_display: '',
            eqp_size: '',
            oee_capa: 0,
            ng_percent: 0,
            start_time: '',
        }
    ]
});

// Lookup state
const isLookingUp = ref(false);
const lookupError = ref('');
const lookupWarning = ref('');
const isUpdateMode = ref(false);
const endtimeId = ref<number | null>(null);

// Equipment lookup state (per equipment row)
const equipmentLookupState = ref<Record<number, { isLooking: boolean; error: string }>>({});

// Equipment search state (per equipment row)
const equipmentSearchState = ref<Record<number, { 
    isSearching: boolean; 
    results: any[]; 
    showDropdown: boolean;
}>>({});

// Employee lookup state
const employeeLookupState = ref({ isLooking: false, error: '' });

// Employee search state
const employeeSearchState = ref({ 
    isSearching: false, 
    results: [] as any[], 
    showDropdown: false
});

// Validation errors state
const validationErrors = ref<Record<string, string[]>>({});



// Calculation details for display
const calculationDetails = computed(() => {
    const validEquipment = formData.value.equipment.filter(eq => 
        eq.eqp_no && eq.start_time && eq.oee_capa > 0
    );
    
    if (validEquipment.length === 0 || !formData.value.lot_qty) {
        return null;
    }
    
    let totalCapacity = 0;
    validEquipment.forEach(eq => {
        const ngPercent = eq.ng_percent || 0;
        const adjustedCapacity = eq.oee_capa * (1 - (ngPercent / 100));
        totalCapacity += adjustedCapacity;
    });
    
    const qty = parseInt(formData.value.lot_qty.toString().replace(/,/g, ''));
    const capacityPerMinute = totalCapacity / (24 * 60);
    const processingMinutes = qty / capacityPerMinute;
    const processingHours = (processingMinutes / 60).toFixed(2);
    
    return {
        totalCapacity: totalCapacity.toLocaleString(),
        equipmentCount: validEquipment.length,
        processingHours,
        lotQty: qty.toLocaleString()
    };
});

// Estimated endtime calculation
const estimatedEndtime = computed(() => {
    // Check if we have at least one equipment with start time and capacity
    const validEquipment = formData.value.equipment.filter(eq => 
        eq.eqp_no && eq.start_time && eq.oee_capa > 0
    );
    
    if (validEquipment.length === 0 || !formData.value.lot_qty) {
        return null;
    }

    // Get lot quantity (remove commas)
    const qty = parseInt(formData.value.lot_qty.toString().replace(/,/g, ''));
    
    // Find the latest start time (reference point - when last machine starts)
    let latestStartTime: Date | null = null;
    
    // Prepare equipment data with adjusted capacities and start times
    const equipmentData = validEquipment.map(eq => {
        const startTime = new Date(eq.start_time);
        if (!latestStartTime || startTime > latestStartTime) {
            latestStartTime = startTime;
        }
        
        // Calculate adjusted capacity based on NG percentage
        const ngPercent = eq.ng_percent || 0;
        const adjustedCapacity = eq.oee_capa * (1 - (ngPercent / 100));
        const capacityPerMinute = adjustedCapacity / (24 * 60); // Convert to pieces per minute
        
        return {
            startTime,
            capacityPerMinute
        };
    });

    if (!latestStartTime) {
        return null;
    }

    // Calculate total weighted capacity
    // Each machine contributes based on how long it runs relative to the latest start time
    // Formula: For each machine, calculate: capacity_per_minute * (T + time_advantage)
    // where T is the time from latest start to end, and time_advantage is how much earlier it started
    
    // Sum of (capacity_per_minute * time_advantage_in_minutes) for all machines
    let weightedCapacitySum = 0;
    // Sum of capacity_per_minute for all machines
    let totalCapacityPerMinute = 0;
    
    equipmentData.forEach(eq => {
        const timeAdvantageMinutes = (latestStartTime!.getTime() - eq.startTime.getTime()) / 60000;
        weightedCapacitySum += eq.capacityPerMinute * timeAdvantageMinutes;
        totalCapacityPerMinute += eq.capacityPerMinute;
    });

    // Solve for T (time from latest start to end)
    // qty = sum(capacity_i * (T + advantage_i))
    // qty = sum(capacity_i * T) + sum(capacity_i * advantage_i)
    // qty = T * sum(capacity_i) + sum(capacity_i * advantage_i)
    // T = (qty - sum(capacity_i * advantage_i)) / sum(capacity_i)
    
    let processingMinutesFromLatestStart = (qty - weightedCapacitySum) / totalCapacityPerMinute;

    // Apply NO RL adjustment if enabled (subtract time for NO RL)
    if (formData.value.no_rl_enabled && formData.value.no_rl_minutes) {
        processingMinutesFromLatestStart -= formData.value.no_rl_minutes;
        if (processingMinutesFromLatestStart < 0) {
            processingMinutesFromLatestStart = 0;
        }
    }

    // Calculate estimated endtime from the latest start time
    const endtime = new Date((latestStartTime as Date).getTime() + processingMinutesFromLatestStart * 60000);

    // Format date and time
    const dateStr = endtime.toLocaleDateString('en-US', { 
        month: 'short', 
        day: '2-digit',
        year: 'numeric'
    });
    
    const timeStr = endtime.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: false
    });

    // Determine cutoff based on hour
    const hour = endtime.getHours();
    let cutoff = '';
    let cutoffClass = '';
    
    if (hour >= 7 && hour < 12) {
        cutoff = 'DAY 1ST';
        cutoffClass = 'bg-blue-500/20 text-blue-700 dark:text-blue-300 border-blue-500/30';
    } else if (hour >= 12 && hour < 16) {
        cutoff = 'DAY 2ND';
        cutoffClass = 'bg-cyan-500/20 text-cyan-700 dark:text-cyan-300 border-cyan-500/30';
    } else if (hour >= 16 && hour < 19) {
        cutoff = 'DAY 3RD';
        cutoffClass = 'bg-teal-500/20 text-teal-700 dark:text-teal-300 border-teal-500/30';
    } else if (hour >= 19 || hour < 4) {
        cutoff = hour >= 19 ? 'NIGHT 1ST' : 'NIGHT 2ND';
        cutoffClass = 'bg-purple-500/20 text-purple-700 dark:text-purple-300 border-purple-500/30';
    } else {
        cutoff = 'NIGHT 3RD';
        cutoffClass = 'bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border-indigo-500/30';
    }

    return {
        date: dateStr,
        time: timeStr,
        cutoff,
        cutoffClass
    };
});

// ============================================================================
// WATCHERS
// ============================================================================
// Watch for lot_type changes to clear Lot Qty when RL/LY is selected
watch(() => formData.value.lot_type, (newType, oldType) => {
    if (newType === 'RL/LY' && oldType !== 'RL/LY') {
        // Clear the lot_qty field when switching to RL/LY
        formData.value.lot_qty = '';
    }
});

// Watch for lot prop changes to load edit data
watch(() => props.lot, async (newLot) => {
    if (newLot && props.open) {
        // Set the lot_id and trigger lookup to get full data
        formData.value.lot_id = newLot.lot_id;
        await lookupLotInfo();
    }
}, { immediate: true });

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
        lot_size: '10',
        lot_size_display: '',
        lot_type: 'MAIN',
        model_15: '',
        lipas_yn: 'N',
        work_type: 'NORMAL',
        no_rl_enabled: false,
        no_rl_minutes: 60,
        employee_id: '',
        employee_name: '',
        equipment: [
            {
                eqp_no: '',
                eqp_no_display: '',
                eqp_size: '',
                oee_capa: 0,
                ng_percent: 0,
                start_time: '',
            }
        ]
    };
    lookupError.value = '';
    lookupWarning.value = '';
    validationErrors.value = {};
    equipmentLookupState.value = {};
    equipmentSearchState.value = {};
    employeeLookupState.value = { isLooking: false, error: '' };
    employeeSearchState.value = { isSearching: false, results: [], showDropdown: false };
    isUpdateMode.value = false;
    endtimeId.value = null;
};

// Lookup lot information from endtime and updatewip tables
const lookupLotInfo = async () => {
    if (!formData.value.lot_id || formData.value.lot_id.trim() === '') {
        return;
    }

    isLookingUp.value = true;
    lookupError.value = '';
    lookupWarning.value = '';

    try {
        const response = await fetch(`/api/updatewip/lookup?lot_id=${encodeURIComponent(formData.value.lot_id)}`);
        
        if (!response.ok) {
            throw new Error('Lot not found');
        }

        const data = await response.json();

        if (data.success) {
            if (data.existingEndtime) {
                // Lot exists in endtime table
                if (data.status === 'Ongoing') {
                    // Load existing data for update
                    isUpdateMode.value = true;
                    endtimeId.value = data.endtimeData.id;
                    lookupWarning.value = data.message;
                    
                    // Load all data from endtime
                    formData.value.lot_qty = data.endtimeData.lot_qty ? parseInt(data.endtimeData.lot_qty).toLocaleString() : '';
                    formData.value.lot_size = data.endtimeData.lot_size || '10';
                    formData.value.lot_size_display = getSizeDisplay(data.endtimeData.lot_size || '10');
                    formData.value.lot_type = data.endtimeData.lot_type || 'MAIN';
                    formData.value.model_15 = data.endtimeData.model_15 || '';
                    formData.value.work_type = data.endtimeData.work_type || 'NORMAL';
                    formData.value.lipas_yn = data.endtimeData.lipas_yn || 'N';
                    formData.value.no_rl_enabled = data.endtimeData.no_rl_enabled || false;
                    formData.value.no_rl_minutes = data.endtimeData.no_rl_minutes || 60;
                    
                    // Load equipment data
                    if (data.endtimeData.equipment && data.endtimeData.equipment.length > 0) {
                        formData.value.equipment = data.endtimeData.equipment.map((eq: any) => ({
                            eqp_no: eq.eqp_no,
                            eqp_no_display: eq.eqp_no_display,
                            eqp_size: eq.eqp_size || '',
                            oee_capa: eq.oee_capa,
                            ng_percent: eq.ng_percent,
                            start_time: eq.start_time,
                        }));
                        
                        // Lookup capacity for each equipment
                        formData.value.equipment.forEach((eq, index) => {
                            if (eq.eqp_no) {
                                lookupEquipmentInfo(index);
                            }
                        });
                    }
                } else if (data.status === 'Submitted') {
                    // Lot exists but submitted - warn user
                    lookupWarning.value = data.message;
                    isUpdateMode.value = false;
                    endtimeId.value = null;
                    
                    // Auto-fill from existing data but allow new entry with WL/RW or RL/LY
                    formData.value.lot_qty = data.lot.lot_qty ? parseInt(data.lot.lot_qty).toLocaleString() : '';
                    formData.value.lot_size = data.lot.lot_size || '10';
                    formData.value.lot_size_display = getSizeDisplay(data.lot.lot_size || '10');
                    formData.value.model_15 = data.lot.model_15 || '';
                    formData.value.work_type = data.lot.work_type || 'NORMAL';
                    formData.value.lipas_yn = data.lot.lipas_yn || 'N';
                    
                    // Keep lot_type as MAIN - user must manually change it to WL/RW or RL/LY
                }
            } else {
                // New lot - auto-fill from updatewip data
                isUpdateMode.value = false;
                endtimeId.value = null;
                
                formData.value.lot_qty = data.lot.lot_qty ? parseInt(data.lot.lot_qty).toLocaleString() : '';
                formData.value.lot_size = data.lot.lot_size || '10';
                formData.value.lot_size_display = getSizeDisplay(data.lot.lot_size || '10');
                formData.value.model_15 = data.lot.model_15 || '';
                formData.value.work_type = data.lot.work_type || 'NORMAL';
                formData.value.lipas_yn = data.lot.lipas_yn || 'N';
            }
        } else {
            lookupError.value = 'Lot not found in database';
        }
    } catch (error) {
        lookupError.value = 'Failed to lookup lot information';
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

const formatFieldName = (field: string): string => {
    return field
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
        .replace(/\./g, ' ');
};

// Helper function to focus on the first error field
const focusErrorField = async (errors: Record<string, string[]>) => {
    await nextTick();
    
    // Get the first error field name
    const firstErrorField = Object.keys(errors)[0];
    if (!firstErrorField) return;
    
    // Map field names to their input IDs
    const fieldIdMap: Record<string, string> = {
        'lot_id': 'lot_id',
        'lot_qty': 'lot_qty',
        'lot_size': 'lot_size',
        'model_15': 'model_15',
        'work_type': 'work_type',
        'lipas_yn': 'lipas_yn',
    };
    
    // Check if it's an equipment field (e.g., equipment.1.eqp_no)
    const equipmentMatch = firstErrorField.match(/equipment\.(\d+)\.(eqp_no|ng_percent|start_time)/);
    
    let elementId: string | null = null;
    
    if (equipmentMatch) {
        const index = equipmentMatch[1];
        const field = equipmentMatch[2];
        elementId = `${field}_${index}`;
    } else {
        elementId = fieldIdMap[firstErrorField] || firstErrorField;
    }
    
    // Try to find and focus the element
    if (elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.focus();
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
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
// EQUIPMENT FUNCTIONS
// ============================================================================
const initializeEquipmentState = (index: number) => {
    if (!equipmentSearchState.value[index]) {
        equipmentSearchState.value[index] = { 
            isSearching: false, 
            results: [], 
            showDropdown: false 
        };
    }
    if (!equipmentLookupState.value[index]) {
        equipmentLookupState.value[index] = { isLooking: false, error: '' };
    }
};

const searchEquipment = async (index: number) => {
    const equipment = formData.value.equipment[index];
    initializeEquipmentState(index);

    if (!equipment.eqp_no || equipment.eqp_no.trim().length < 2) {
        equipmentSearchState.value[index].results = [];
        equipmentSearchState.value[index].showDropdown = false;
        return;
    }

    equipmentSearchState.value[index].isSearching = true;

    try {
        const response = await fetch(`/api/equipment/search?search=${encodeURIComponent(equipment.eqp_no)}`);
        const data = await response.json();

        if (data.success && data.equipment.length > 0) {
            equipmentSearchState.value[index].results = data.equipment;
            equipmentSearchState.value[index].showDropdown = true;
        } else {
            equipmentSearchState.value[index].results = [];
            equipmentSearchState.value[index].showDropdown = false;
        }
    } catch (error) {
        equipmentSearchState.value[index].results = [];
        equipmentSearchState.value[index].showDropdown = false;
    } finally {
        equipmentSearchState.value[index].isSearching = false;
    }
};

const selectEquipment = (index: number, equipment: any) => {
    formData.value.equipment[index].eqp_no = equipment.eqp_no;
    formData.value.equipment[index].eqp_no_display = equipment.eqp_no;
    formData.value.equipment[index].eqp_size = equipment.size || '';
    formData.value.equipment[index].oee_capa = equipment.oee_capa || 0;
    
    if (equipmentSearchState.value[index]) {
        equipmentSearchState.value[index].showDropdown = false;
    }
    if (equipmentLookupState.value[index]) {
        equipmentLookupState.value[index].error = '';
    }
};

const closeEquipmentDropdown = (index: number) => {
    setTimeout(() => {
        if (equipmentSearchState.value[index]) {
            equipmentSearchState.value[index].showDropdown = false;
        }
    }, 200);
};

const lookupEquipmentInfo = async (index: number) => {
    const equipment = formData.value.equipment[index];
    
    if (!equipment.eqp_no || equipment.eqp_no.trim() === '') return;

    initializeEquipmentState(index);
    equipmentLookupState.value[index].isLooking = true;
    equipmentLookupState.value[index].error = '';

    try {
        // Try exact match first, then try with VI prefix if input is 3 digits
        let eqpNoToLookup = equipment.eqp_no.trim().toUpperCase();
        let response = await fetch(`/api/equipment/lookup?eqp_no=${encodeURIComponent(eqpNoToLookup)}`);
        let data = await response.json();

        // If not found and input is 3 characters (likely just the number), try with VI prefix
        if (!data.success && eqpNoToLookup.length === 3 && /^\d{3}$/.test(eqpNoToLookup)) {
            eqpNoToLookup = 'VI' + eqpNoToLookup;
            response = await fetch(`/api/equipment/lookup?eqp_no=${encodeURIComponent(eqpNoToLookup)}`);
            data = await response.json();
        }

        if (data.success) {
            // Update both eqp_no and eqp_no_display with the correct full equipment number
            formData.value.equipment[index].eqp_no = data.equipment.eqp_no;
            formData.value.equipment[index].eqp_no_display = data.equipment.eqp_no;
            formData.value.equipment[index].eqp_size = data.equipment.size || '';
            formData.value.equipment[index].oee_capa = data.equipment.oee_capa || 0;
        } else {
            equipmentLookupState.value[index].error = 'Equipment not found';
            formData.value.equipment[index].eqp_no_display = '';
            formData.value.equipment[index].eqp_size = '';
            formData.value.equipment[index].oee_capa = 0;
        }
    } catch (error) {
        equipmentLookupState.value[index].error = 'Equipment not found';
        formData.value.equipment[index].eqp_no_display = '';
        formData.value.equipment[index].eqp_size = '';
        formData.value.equipment[index].oee_capa = 0;
    } finally {
        equipmentLookupState.value[index].isLooking = false;
    }
};

const addEquipmentRow = () => {
    if (formData.value.equipment.length < 10) {
        formData.value.equipment.push({
            eqp_no: '',
            eqp_no_display: '',
            eqp_size: '',
            oee_capa: 0,
            ng_percent: 0,
            start_time: '',
        });
    }
};

const removeEquipmentRow = (index: number) => {
    if (formData.value.equipment.length > 1) {
        formData.value.equipment.splice(index, 1);
    }
};

const setCurrentTime = (index: number) => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    
    formData.value.equipment[index].start_time = `${year}-${month}-${day}T${hours}:${minutes}`;
};

const openCalendarPicker = (index: number) => {
    const input = document.getElementById(`start_time_${index + 1}`) as HTMLInputElement;
    if (input && input.showPicker) {
        input.showPicker();
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

const formatLotQty = (event: Event) => {
    if (formData.value.lot_type !== 'RL/LY') return;
    
    const input = event.target as HTMLInputElement;
    const cursorPosition = input.selectionStart || 0;
    const value = input.value.replace(/\D/g, '');
    
    if (!value) {
        formData.value.lot_qty = '';
        return;
    }
    
    const formatted = parseInt(value).toLocaleString();
    const adjustment = formatted.length - input.value.length;
    
    formData.value.lot_qty = formatted;
    
    setTimeout(() => {
        const newPosition = Math.max(0, cursorPosition + adjustment);
        input.setSelectionRange(newPosition, newPosition);
    }, 0);
};

// ============================================================================
// FORM SUBMISSION
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
    isSubmitting.value = true;
    
    try {
        // Validate: Employee must be validated (employee_name must be set)
        if (!formData.value.employee_id || formData.value.employee_id.trim() === '') {
            showToast('Please enter Employee ID', 'danger');
            isSubmitting.value = false;
            return;
        }
        
        if (!formData.value.employee_name || formData.value.employee_name.trim() === '') {
            showToast('Please validate Employee ID first (click 🔍 or select from dropdown)', 'danger');
            isSubmitting.value = false;
            // Focus on employee_id field
            const employeeInput = document.getElementById('employee_id');
            if (employeeInput) {
                employeeInput.focus();
            }
            return;
        }
        
        // Validate: If there's a warning about submitted lot and lot_type is still MAIN, prevent submission
        if (lookupWarning.value && lookupWarning.value.includes('already submitted') && formData.value.lot_type === 'MAIN') {
            validationErrors.value = {
                'lot_type': ['Please select WL/RW or RL/LY lot type for this already submitted lot.']
            };
            isSubmitting.value = false;
            return;
        }
        
        // Prepare equipment data as a proper array for Laravel
        const equipmentArray: Array<{eqp_no: string; ng_percent: number; start_time: string}> = [];
        formData.value.equipment.forEach((eq) => {
            if (eq.eqp_no) {
                // Use the display name (full equipment number) if available, otherwise use the input
                const eqpNoToSubmit = eq.eqp_no_display || (eq.eqp_no.length === 3 ? 'VI' + eq.eqp_no : eq.eqp_no);
                equipmentArray.push({
                    eqp_no: eqpNoToSubmit,
                    ng_percent: eq.ng_percent || 0,
                    start_time: eq.start_time,
                });
            }
        });
        
        // Convert formatted lot_qty back to number (remove commas)
        const lotQtyNumber = formData.value.lot_qty ? 
            parseInt(formData.value.lot_qty.toString().replace(/,/g, '')) : 0;
        
        // Clear previous validation errors
        validationErrors.value = {};
        
        // Determine the route and method based on update mode
        const url = isUpdateMode.value ? `/endtime/${endtimeId.value}` : '/endtime/store';
        const method = isUpdateMode.value ? 'put' : 'post';
        
        // Prepare submission data - exclude the raw equipment array from formData
        const { equipment: _, employee_name: __, lot_size_display: ___, ...formDataWithoutEquipment } = formData.value;
        
        // Submit form
        router[method](url, {
            ...formDataWithoutEquipment,
            lot_qty: lotQtyNumber,
            equipment: equipmentArray,
        }, {
            onSuccess: () => {
                const message = isUpdateMode.value 
                    ? 'Lot entry updated successfully!' 
                    : 'Lot entry created successfully!';
                showToast(message, 'success');
                closeModal();
            },
            onError: (errors) => {
                const formattedErrors: Record<string, string[]> = {};
                Object.keys(errors).forEach(key => {
                    const error = errors[key];
                    formattedErrors[key] = Array.isArray(error) ? error : [error];
                });
                validationErrors.value = formattedErrors;
                focusErrorField(formattedErrors);
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
        <DialogContent class="!max-w-[80vw] min-h-[90vh] max-h-[90vh] flex flex-col overflow-hidden">
            <DialogHeader>
                <div class="flex items-center justify-between gap-4">
                    <DialogTitle class="text-2xl font-bold flex items-center gap-2">
                        <span class="text-2xl">{{ isUpdateMode ? '✏️' : '🕐' }}</span>
                        {{ isUpdateMode ? 'Update Lot Entry & Endtime' : 'New Lot Entry & Endtime Forecast' }}
                    </DialogTitle>
                    
                    <!-- Inline Warning Notice with Title -->
                    <transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 transform translate-x-2"
                        enter-to-class="opacity-100 transform translate-x-0"
                        leave-active-class="transition-all duration-300 ease-in"
                        leave-from-class="opacity-100 transform translate-x-0"
                        leave-to-class="opacity-0 transform translate-x-2"
                    >
                        <div v-if="lookupWarning" class="flex items-center gap-2 px-3 py-1.5 rounded-md bg-amber-500/10 border border-amber-500/30 whitespace-nowrap">
                            <span class="text-lg">⚠️</span>
                            <span class="text-xs text-amber-700 dark:text-amber-300 font-medium">{{ lookupWarning }}</span>
                            <button 
                                type="button"
                                @click="lookupWarning = ''"
                                class="text-amber-700 dark:text-amber-300 hover:text-amber-800 dark:hover:text-amber-200 transition-colors ml-1"
                                title="Dismiss"
                            >
                                ✕
                            </button>
                        </div>
                    </transition>
                </div>
                
                <DialogDescription>
                    {{ isUpdateMode ? 'Update lot information and equipment assignments' : 'Enter lot information and assign equipment to calculate estimated endtime' }}
                </DialogDescription>
                
                <!-- Validation Errors Display -->
                <div v-if="Object.keys(validationErrors).length > 0" class="mt-4 p-4 rounded-lg bg-destructive/10 border-2 border-destructive/50">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">⚠️</span>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-destructive mb-2">Validation Errors</h4>
                            <ul class="space-y-1">
                                <li 
                                    v-for="(messages, field) in validationErrors" 
                                    :key="field"
                                    class="text-sm text-destructive"
                                >
                                    <span class="font-medium">{{ formatFieldName(field) }}:</span>
                                    <span v-for="(message, index) in messages" :key="index">
                                        {{ message }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                        <button 
                            type="button"
                            @click="validationErrors = {}"
                            class="text-destructive hover:text-destructive/80 transition-colors"
                            title="Dismiss"
                        >
                            ✕
                        </button>
                    </div>
                </div>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="flex flex-col gap-6 flex-1 overflow-hidden">
                <!-- Lot Information Section (Fixed) -->
                <div class="rounded-lg border-2 border-border p-4 space-y-4 flex-shrink-0">
                    <h3 class="text-lg font-semibold flex items-center gap-2">
                        <span>ℹ️</span>
                        Lot Information
                    </h3>
                    
                    <div class="grid grid-cols-12 gap-4">
                        <!-- Lot No -->
                        <div class="col-span-2 space-y-2">
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
                            <p v-if="lookupError" class="text-xs text-destructive">{{ lookupError }}</p>
                        </div>

                        <!-- Lot Type -->
                        <div class="col-span-3 space-y-2">
                            <Label class="text-sm font-medium">Lot Type *</Label>
                            <div class="flex gap-1.5 items-center h-10">
                                <label class="flex items-center gap-1 cursor-pointer h-full px-2.5 rounded-md border border-input hover:bg-accent transition-colors">
                                    <input 
                                        type="radio" 
                                        v-model="formData.lot_type" 
                                        value="MAIN"
                                        class="w-4 h-4"
                                    />
                                    <Badge class="bg-blue-500/20 text-blue-700 dark:text-blue-300 border-blue-500/30 text-xs py-1">MAIN</Badge>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer h-full px-2.5 rounded-md border border-input hover:bg-accent transition-colors">
                                    <input 
                                        type="radio" 
                                        v-model="formData.lot_type" 
                                        value="WL/RW"
                                        class="w-4 h-4"
                                    />
                                    <Badge class="bg-amber-500/20 text-amber-700 dark:text-amber-300 border-amber-500/30 text-xs py-1">WL/RW</Badge>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer h-full px-2.5 rounded-md border border-input hover:bg-accent transition-colors">
                                    <input 
                                        type="radio" 
                                        v-model="formData.lot_type" 
                                        value="RL/LY"
                                        class="w-4 h-4"
                                    />
                                    <Badge class="bg-cyan-500/20 text-cyan-700 dark:text-cyan-300 border-cyan-500/30 text-xs py-1">RL/LY</Badge>
                                </label>
                            </div>
                        </div>

                        <!-- Lot Qty (Auto-filled from updatewip, manual for RL/LY) -->
                        <div class="col-span-1 space-y-2">
                            <Label for="lot_qty" class="text-sm font-medium">Lot Qty *</Label>
                            <Input 
                                id="lot_qty"
                                v-model="formData.lot_qty"
                                type="text"
                                @input="formatLotQty"
                                :placeholder="formData.lot_type === 'RL/LY' ? 'input qty' : 'Auto'"
                                :readonly="formData.lot_type !== 'RL/LY'"
                                :class="formData.lot_type === 'RL/LY' ? 'w-full text-right font-mono' : 'w-full bg-muted/50 text-right font-mono'"
                            />
                        </div>

                        <!-- Lot Size (Auto-filled from updatewip) -->
                        <div class="col-span-1 space-y-2">
                            <Label for="lot_size" class="text-sm font-medium">Size *</Label>
                            <Input 
                                id="lot_size"
                                v-model="formData.lot_size_display"
                                type="text"
                                placeholder="Auto"
                                readonly
                                class="w-full bg-muted/50 text-center"
                            />
                        </div>

                        <!-- Model (Auto-filled from updatewip) -->
                        <div class="col-span-2 space-y-2">
                            <Label for="model_15" class="text-sm font-medium">Model</Label>
                            <Input 
                                id="model_15"
                                v-model="formData.model_15"
                                placeholder="Auto"
                                readonly
                                maxlength="15"
                                class="w-full bg-muted/50"
                            />
                        </div>

                        <!-- Work Type (Auto-filled from updatewip) -->
                        <div class="col-span-2 space-y-2">
                            <Label for="work_type" class="text-sm font-medium">Work Type *</Label>
                            <Input 
                                id="work_type"
                                v-model="formData.work_type"
                                placeholder="Auto"
                                readonly
                                class="w-full bg-muted/50"
                            />
                        </div>

                        <!-- LIPAS (Auto-filled from updatewip) -->
                        <div class="col-span-1 space-y-2">
                            <Label for="lipas_yn" class="text-sm font-medium">LIPAS</Label>
                            <Input 
                                id="lipas_yn"
                                v-model="formData.lipas_yn"
                                placeholder="Auto"
                                readonly
                                class="w-full bg-muted/50 text-center"
                            />
                        </div>
                    </div>

                    <!-- Employee ID & NO RL Toggle & Estimated Endtime -->
                    <div class="flex items-center justify-between gap-4 p-3 rounded-lg bg-muted/50">
                        <div class="flex items-center gap-4">
                            <!-- Employee ID -->
                            <div class="flex items-center gap-2">
                                <Label class="text-sm font-medium whitespace-nowrap">Employee ID *</Label>
                                <div class="relative">
                                    <Input 
                                        id="employee_id"
                                        v-model="formData.employee_id"
                                        @input="searchEmployee"
                                        @blur="closeEmployeeDropdown"
                                        @keyup.enter="lookupEmployeeInfo"
                                        placeholder="Type to search..."
                                        required
                                        maxlength="10"
                                        class="w-32"
                                        :disabled="employeeLookupState.isLooking"
                                        autocomplete="off"
                                    />
                                    
                                    <!-- Dropdown Results -->
                                    <div 
                                        v-if="employeeSearchState.showDropdown && employeeSearchState.results.length > 0"
                                        class="absolute z-[9999] w-64 mt-1 left-0 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-xl max-h-60 overflow-y-auto"
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
                                    <span v-if="employeeLookupState.isLooking">⏳</span>
                                    <span v-else>🔍</span>
                                </Button>
                                <span v-if="formData.employee_name" class="text-sm text-muted-foreground">{{ formData.employee_name }}</span>
                                <span v-if="employeeLookupState.error" class="text-xs text-destructive">{{ employeeLookupState.error }}</span>
                            </div>

                            <!-- NO RL Toggle -->
                            <div class="flex items-center gap-2 border-l border-border pl-4">
                                <label class="flex items-center gap-2 cursor-pointer whitespace-nowrap">
                                    <input 
                                        type="checkbox" 
                                        v-model="formData.no_rl_enabled"
                                        class="w-4 h-4"
                                    />
                                    <span class="text-sm font-medium">NO RL?</span>
                                </label>
                                <div v-if="formData.no_rl_enabled" class="flex items-center gap-2">
                                    <Input 
                                        v-model.number="formData.no_rl_minutes"
                                        type="number"
                                        min="1"
                                        max="999"
                                        class="w-16 h-9"
                                    />
                                    <span class="text-xs text-muted-foreground whitespace-nowrap">mins</span>
                                </div>
                            </div>

                            <!-- Estimated Endtime Badge -->
                            <div class="flex items-center gap-2 border-l border-border pl-4">
                                <span class="text-xs font-medium text-muted-foreground whitespace-nowrap">Estimated Endtime:</span>
                                <Badge 
                                    v-if="estimatedEndtime"
                                    class="bg-gradient-to-r from-emerald-500/20 to-blue-500/20 text-foreground border-emerald-500/30 px-3 py-1.5 text-sm font-semibold"
                                >
                                    <span class="flex items-center gap-2">
                                        <span>📅</span>
                                        <span>{{ estimatedEndtime.date }}</span>
                                        <span class="text-muted-foreground">|</span>
                                        <span>🕐</span>
                                        <span>{{ estimatedEndtime.time }}</span>
                                        <span class="text-muted-foreground">|</span>
                                        <Badge 
                                            variant="outline" 
                                            class="text-xs"
                                            :class="estimatedEndtime.cutoffClass"
                                        >
                                            {{ estimatedEndtime.cutoff }}
                                        </Badge>
                                    </span>
                                </Badge>
                                <Badge 
                                    v-else
                                    variant="outline"
                                    class="text-muted-foreground text-xs px-3 py-1.5"
                                >
                                    <span class="flex items-center gap-2">
                                        <span>⏳</span>
                                        <span>Enter equipment & start time</span>
                                    </span>
                                </Badge>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Calculation Details (Optional Info) -->
                    <div v-if="calculationDetails" class="text-xs text-muted-foreground mt-2 px-3">
                        <span class="font-medium">Calculation:</span>
                        {{ calculationDetails.lotQty }} pcs ÷ {{ calculationDetails.totalCapacity }} pcs/day ({{ calculationDetails.equipmentCount }} machine{{ calculationDetails.equipmentCount > 1 ? 's' : '' }}) 
                        = {{ calculationDetails.processingHours }} hours
                    </div>
                </div>

                <!-- Equipment Assignment Section -->
                <div class="rounded-lg border-2 border-border flex flex-col flex-1 overflow-hidden">
                    <div class="flex items-center justify-between p-4 border-b border-border bg-muted/30">
                        <h3 class="text-lg font-semibold flex items-center gap-2">
                            <span>⚙️</span>
                            Machine Assignment & Loading Times
                        </h3>
                        <Button 
                            type="button"
                            size="sm"
                            @click="addEquipmentRow"
                            :disabled="formData.equipment.length >= 10"
                            class="bg-blue-600 hover:bg-blue-700 text-white"
                        >
                            ➕ Add Machine
                        </Button>
                    </div>

                    <div class="space-y-3 p-4 overflow-y-auto flex-1">
                        <div 
                            v-for="(eq, index) in formData.equipment" 
                            :key="index"
                            class="grid grid-cols-1 md:grid-cols-12 gap-3 p-3 rounded-lg bg-muted/30 border border-border"
                        >
                            <div class="md:col-span-1 flex items-center justify-center">
                                <span class="text-sm font-semibold text-muted-foreground">{{ index + 1 }}</span>
                            </div>

                            <!-- Equipment No -->
                            <div class="md:col-span-2 space-y-1 relative">
                                <Label class="text-xs">Machine No. *</Label>
                                <div class="flex gap-2">
                                    <div class="flex-1 relative">
                                        <Input 
                                            :id="`eqp_no_${index + 1}`"
                                            v-model="eq.eqp_no"
                                            @input="searchEquipment(index)"
                                            @blur="closeEquipmentDropdown(index)"
                                            @keyup.enter="lookupEquipmentInfo(index)"
                                            placeholder="Type to search..."
                                            required
                                            maxlength="10"
                                            class="w-full"
                                            :disabled="equipmentLookupState[index]?.isLooking"
                                            autocomplete="off"
                                        />
                                        
                                        <!-- Dropdown Results -->
                                        <div 
                                            v-if="equipmentSearchState[index]?.showDropdown && equipmentSearchState[index]?.results.length > 0"
                                            class="absolute z-50 w-full mt-1 bg-popover border border-border rounded-md shadow-lg max-h-60 overflow-y-auto"
                                        >
                                            <div 
                                                v-for="(result, resultIndex) in equipmentSearchState[index].results" 
                                                :key="resultIndex"
                                                @mousedown="selectEquipment(index, result)"
                                                class="px-3 py-2 hover:bg-accent cursor-pointer border-b border-border last:border-b-0"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <div class="flex flex-col">
                                                        <span class="font-semibold text-sm">{{ result.eqp_no }}</span>
                                                        <span class="text-xs text-muted-foreground">
                                                            Line {{ result.eqp_line }} | Area {{ result.eqp_area }} | {{ result.alloc_type }}
                                                        </span>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="text-xs font-mono text-muted-foreground">
                                                            {{ result.oee_capa ? result.oee_capa.toLocaleString() : '0' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <Button 
                                        type="button"
                                        size="sm"
                                        @click="lookupEquipmentInfo(index)"
                                        :disabled="equipmentLookupState[index]?.isLooking || !eq.eqp_no"
                                        title="Lookup equipment"
                                        class="shrink-0 px-3 whitespace-nowrap bg-slate-600 hover:bg-slate-700 text-white"
                                    >
                                        <span v-if="equipmentLookupState[index]?.isLooking">⏳ LOADING</span>
                                        <span v-else>🔍</span>
                                    </Button>
                                </div>
                                <p v-if="equipmentLookupState[index]?.error" class="text-xs text-destructive">
                                    {{ equipmentLookupState[index].error }}
                                </p>
                            </div>

                            <!-- Size -->
                            <div class="md:col-span-1 space-y-1">
                                <Label class="text-xs">Size</Label>
                                <Input 
                                    :value="eq.eqp_size || '-'"
                                    readonly
                                    class="w-full bg-muted/50 text-center"
                                />
                            </div>

                            <!-- Capacity (OEE Capa) -->
                            <div class="md:col-span-2 space-y-1">
                                <Label class="text-xs">Capa</Label>
                                <Input 
                                    :value="eq.oee_capa ? eq.oee_capa.toLocaleString() : '0'"
                                    readonly
                                    class="w-full bg-muted/50 text-right font-mono"
                                />
                            </div>

                            <!-- NG % -->
                            <div class="md:col-span-1 space-y-1">
                                <Label class="text-xs">NG %</Label>
                                <Input 
                                    :id="`ng_percent_${index + 1}`"
                                    v-model.number="eq.ng_percent"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                    class="w-full"
                                />
                            </div>

                            <!-- Start Time -->
                            <div class="md:col-span-4 space-y-1">
                                <Label class="text-xs">Start Time *</Label>
                                <div class="flex gap-2">
                                    <Input 
                                        :id="`start_time_${index + 1}`"
                                        v-model="eq.start_time"
                                        type="datetime-local"
                                        required
                                        step="60"
                                        class="flex-1"
                                    />
                                    <Button 
                                        type="button"
                                        size="sm"
                                        @click="openCalendarPicker(index)"
                                        title="Open calendar picker"
                                        class="shrink-0 px-3 whitespace-nowrap bg-orange-400 hover:bg-orange-600 text-white"
                                    >
                                        📅 PICKER
                                    </Button>
                                    <Button 
                                        type="button"
                                        size="sm"
                                        @click="setCurrentTime(index)"
                                        title="Set to current time"
                                        class="shrink-0 px-3 whitespace-nowrap bg-green-600 hover:bg-green-700 text-white"
                                    >
                                        🕐 AUTO SET
                                    </Button>
                                </div>
                            </div>

                            <!-- Remove Button -->
                            <div class="md:col-span-1 flex items-end justify-center">
                                <Button 
                                    v-if="formData.equipment.length > 1"
                                    type="button"
                                    variant="ghost" 
                                    size="icon"
                                    @click="removeEquipmentRow(index)"
                                    class="text-destructive hover:text-destructive hover:bg-destructive/10"
                                >
                                    ❌
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions (Fixed at bottom) -->
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
                        :disabled="isSubmitting"
                    >
                        <span v-if="isSubmitting">{{ isUpdateMode ? 'Updating...' : 'Saving...' }}</span>
                        <span v-else>{{ isUpdateMode ? '✏️ Update Lot Entry' : '💾 Save Lot Entry' }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
