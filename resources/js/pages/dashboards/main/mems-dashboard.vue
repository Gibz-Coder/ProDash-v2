<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard, mems_dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, watch, nextTick, computed } from 'vue';
import { useMemsCharts } from '@/composables/useMemsCharts';
import { useMemsData } from '@/composables/useMemsData';
import MemsEquipmentDetailsModal from '@/pages/dashboards/subs/mems-equipment-details-modal.vue';
import MemsAssignLotModal from '@/pages/dashboards/subs/mems-assign-lot-modal.vue';
import MemsRemarksModal from '@/pages/dashboards/subs/mems-remarks-modal.vue';
import EndtimeSubmitModal from '@/pages/dashboards/subs/endtime-submit-modal.vue';
import '@/../../resources/css/mems-dashboard.css';

// Get auth from shared Inertia page props
const page = usePage();
const auth = computed(() => page.props.auth as { user: any; permissions: string[] });

// Permission-based access control
const userPermissions = computed(() => auth.value?.permissions || []);
const hasPermission = (permission: string) => userPermissions.value.includes(permission);
const canDeleteEndtime = computed(() => hasPermission('Endtime Delete'));

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: dashboard().url,
    },
    {
        title: 'MEMS Dashboard',
        href: mems_dashboard().url,
    },
];

const selectedTime = ref('12h');
const { updateTrendChart, updateGaugeChart, handleResize } = useMemsCharts('utilizationGauge', 'ongoingLotsChart');
const { 
    fetchLineUtilization, 
    fetchTrendData,
    fetchMachineTypeStatus,
    fetchEndtimeRemaining,
    fetchEndtimePerCutoff,
    trendData,
    isAutoCapturing,
    formatCountdown,
    toggleAutoCapture,
    restoreAutoCapture,
    setSnapshotCallback
} = useMemsData();

// Line carousel state
const carouselEnabled = ref(false);
const currentLineIndex = ref(-1); // -1 means show total
const lines = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
let carouselTimer: number | null = null;

// Line utilization data
const lineUtilizationData = ref<any[]>([]);
const currentGaugeData = ref<any>(null);

// Machine type status data
const machineTypeStatusData = ref<any>(null);
const machineTypeStatusDataBySize = ref<any>(null);

// Fixed machine types in specific order
const machineTypes = ['G1', 'G1-AI', 'G20', 'G3', 'TWA', 'WINTEC'];

// Machine type status view selection (Type or Size) - default to 'size'
const machineTypeStatusView = ref<'type' | 'size'>('size');

// Size columns mapping
const sizeColumns = [
    { display: '0603', dbValue: '03' },
    { display: '1005', dbValue: '05' },
    { display: '1608', dbValue: '10' },
    { display: '2012', dbValue: '21' },
    { display: '3216', dbValue: '31' },
    { display: '3225', dbValue: '32' }
];

// Endtime data
const endtimeRemainingData = ref<any[]>([]);
const endtimePerCutoffData = ref<any[]>([]);

// Computed property for endtime remaining total
const endtimeRemainingTotal = computed(() => {
    const total = {
        class: 'TOTAL',
        count: 0,
        qty: 0,
        '0603': 0,
        '1005': 0,
        '1608': 0,
        '2012': 0,
        '3216': 0,
        '3225': 0
    };
    
    endtimeRemainingData.value.forEach(row => {
        total.count += row.count;
        total.qty += row.qty;
        total['0603'] += row['0603'];
        total['1005'] += row['1005'];
        total['1608'] += row['1608'];
        total['2012'] += row['2012'];
        total['3216'] += row['3216'];
        total['3225'] += row['3225'];
    });
    
    return total;
});

// Helper function to get endtime remaining row by class name
const getEndtimeRemainingRow = (className: string) => {
    if (className === 'TOTAL') {
        return endtimeRemainingTotal.value;
    }
    const row = endtimeRemainingData.value.find(r => r.class === className);
    if (row) return row;
    // Return empty structure if no data
    return {
        class: className,
        count: 0,
        qty: 0,
        '0603': 0,
        '1005': 0,
        '1608': 0,
        '2012': 0,
        '3216': 0,
        '3225': 0
    };
};

// Helper function to get endtime per cutoff row by cutoff name
const getEndtimePerCutoffRow = (cutoffName: string) => {
    const row = endtimePerCutoffData.value.find(r => r.cutoff === cutoffName);
    if (row) return row;
    // Return empty structure if no data
    return {
        cutoff: cutoffName,
        count: 0,
        qty: 0,
        '0603': 0,
        '1005': 0,
        '1608': 0,
        '2012': 0,
        '3216': 0,
        '3225': 0
    };
};

// Helper function to format numbers with thousand separators
const formatNumber = (num: number) => {
    return num.toLocaleString();
};

// Helper function to format QTY based on selected unit
const formatQty = (qty: number) => {
    let value = qty;
    if (selectedQtyUnit.value === 'KPCS') {
        value = qty / 1000;
    } else if (selectedQtyUnit.value === 'MPCS') {
        value = qty / 1000000;
    }
    return value.toLocaleString(undefined, { 
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
};

// Helper function to get color class for elapsed time
const getElapsedTimeClass = (minutes: number) => {
    if (minutes <= 0) {
        return ''; // No color for not yet due
    } else if (minutes <= 60) {
        return 'elapsed-time-a'; // 0-1 hrs: Green
    } else if (minutes <= 180) {
        return 'elapsed-time-b'; // 1-3 hrs: Orange
    } else if (minutes <= 360) {
        return 'elapsed-time-c'; // 3-6 hrs: Light Red
    } else {
        return 'elapsed-time-d'; // 6+ hrs: Red
    }
};

// Restore carousel state from localStorage
const restoreCarouselState = () => {
    const savedState = localStorage.getItem('memsCarouselEnabled');
    if (savedState === 'true') {
        carouselEnabled.value = true;
        startCarousel();
    }
};

// Fetch line utilization data from API (REAL-TIME from equipment table)
const fetchLineUtilizationData = async () => {
    try {
        const line = currentLineIndex.value === -1 ? null : lines[currentLineIndex.value];
        const response = await fetch(`/api/equipment/status/line?` + new URLSearchParams({
            line: line || 'ALL',
            mcStatus: selectedMcStatus.value,
            mcWorktype: selectedMcWorktype.value,
        }));
        
        if (!response.ok) {
            throw new Error('Failed to fetch line utilization');
        }
        
        const result = await response.json();
        currentGaugeData.value = result.data;
        
        // Update the gauge chart with line name
        const lineName = currentLineIndex.value === -1 ? 'ALL' : lines[currentLineIndex.value];
        updateGaugeChart(result.data, lineName);
        
        return result.data;
    } catch (error) {
        return null;
    }
};

// Fetch all lines data for the table (REAL-TIME from equipment table)
const fetchAllLinesData = async () => {
    try {
        const response = await fetch(`/api/equipment/status/utilization?` + new URLSearchParams({
            mcStatus: selectedMcStatus.value,
            mcWorktype: selectedMcWorktype.value,
        }));
        
        if (!response.ok) {
            throw new Error('Failed to fetch all lines data');
        }
        
        const result = await response.json();
        lineUtilizationData.value = result.data || [];
        
        return result.data;
    } catch (error) {
        return [];
    }
};

// Fetch machine type status data (REAL-TIME from equipment table)
const fetchMachineTypeStatusData = async () => {
    try {
        const response = await fetch(`/api/equipment/status/machine-type?` + new URLSearchParams({
            mcStatus: selectedMcStatus.value,
            mcWorktype: selectedMcWorktype.value,
        }));
        
        if (!response.ok) {
            throw new Error('Failed to fetch machine type status');
        }
        
        const result = await response.json();
        machineTypeStatusData.value = result.data;
        return result.data;
    } catch (error) {
        return null;
    }
};

// Fetch machine type status data by size (REAL-TIME from equipment table)
const fetchMachineTypeStatusDataBySize = async () => {
    try {
        const response = await fetch(`/api/equipment/status/machine-size?` + new URLSearchParams({
            mcStatus: selectedMcStatus.value,
            mcWorktype: selectedMcWorktype.value,
        }));
        
        if (!response.ok) {
            throw new Error('Failed to fetch machine size status');
        }
        
        const result = await response.json();
        machineTypeStatusDataBySize.value = result.data;
        return result.data;
    } catch (error) {
        return null;
    }
};

// Fetch endtime remaining data
const fetchEndtimeRemainingData = async () => {
    try {
        const data = await fetchEndtimeRemaining(
            selectedDate.value
        );
        endtimeRemainingData.value = data;
        return data;
    } catch (error) {
        console.error('❌ Error fetching endtime remaining:', error);
        return [];
    }
};

// Fetch endtime per cutoff data
const fetchEndtimePerCutoffData = async () => {
    try {
        const data = await fetchEndtimePerCutoff(
            selectedDate.value,
            selectedLotWorktype.value,
            'all' // Can be 'all', 'ongoing', or 'submitted'
        );
        endtimePerCutoffData.value = data;
        return data;
    } catch (error) {
        return [];
    }
};

// Fetch raw machine data
const fetchRawMachineData = async () => {
    try {
        const response = await fetch(`/api/equipment/raw-machine?` + new URLSearchParams({
            mcStatus: selectedMcStatus.value,
            mcWorktype: selectedMcWorktype.value,
        }));
        
        if (!response.ok) {
            throw new Error('Failed to fetch raw machine data');
        }
        
        const result = await response.json();
        rawMachineData.value = result.data || [];
        return result.data;
    } catch (error) {
        console.error('Error fetching raw machine data:', error);
        return [];
    }
};

// Fetch raw lots data
const fetchRawLotsData = async () => {
    try {
        const response = await fetch(`/api/mems/endtime/raw-lots?` + new URLSearchParams({
            date: selectedDate.value,
        }));
        
        if (!response.ok) {
            throw new Error('Failed to fetch raw lots data');
        }
        
        const result = await response.json();
        rawLotsData.value = result.data || [];
        return result.data;
    } catch (error) {
        console.error('Error fetching raw lots data:', error);
        rawLotsData.value = [];
        return [];
    }
};

// Map UI time range to API time range
const mapTimeRange = (uiRange: string): string => {
    const mapping: Record<string, string> = {
        '1h': '1hour',
        '2h': '2hour',
        '4h': '4hour',
        '12h': '12hour',
        '1d': '1day',
    };
    return mapping[uiRange] || '1hour';
};

// Filter states
// Get current date in local timezone
const now = new Date();
const year = now.getFullYear();
const month = String(now.getMonth() + 1).padStart(2, '0');
const day = String(now.getDate()).padStart(2, '0');
const selectedDate = ref<string>(`${year}-${month}-${day}`);
const selectedMcStatus = ref<string>('OPERATIONAL');
const selectedMcWorktype = ref<string>('NORMAL');
const selectedLotWorktype = ref<string>('NORMAL');
// Restore QTY unit from localStorage or default to PCS
const savedQtyUnit = localStorage.getItem('memsQtyUnit');
const selectedQtyUnit = ref<string>(savedQtyUnit && ['PCS', 'KPCS', 'MPCS'].includes(savedQtyUnit) ? savedQtyUnit : 'PCS');
const selectedRawDataView = ref<string>('machine'); // 'machine' or 'lots'
const rawMachineData = ref<any[]>([]);
const rawLotsData = ref<any[]>([]);
const rawDataSearchQuery = ref<string>('');
const autoRefreshEnabled = ref(false);
const refreshInterval = ref(30); // seconds
const showDatePicker = ref(false);
const showMcStatusDropdown = ref(false);
const showMcWorktypeDropdown = ref(false);
const showLotWorktypeDropdown = ref(false);
const showRefreshModal = ref(false);

// Sorting state for raw data tables
const rawDataSortColumn = ref<string | null>(null);
const rawDataSortDirection = ref<'asc' | 'desc'>('asc');

// Filter state for raw data from Machine Operation Status
const activeStatusFilter = ref<{ line: string | null; status: string | null; machineTypeOrSize: string | null; filterType: 'type' | 'size' | null }>({ 
    line: null, 
    status: null,
    machineTypeOrSize: null,
    filterType: null
});
const showQtyUnitDropdown = ref(false);

// Filter options - will be loaded from database
const mcStatusOptions = ref<string[]>(['ALL']);
const mcWorktypeOptions = ref<string[]>(['ALL']);
const lotWorktypeOptions = ref<string[]>(['ALL']);
const qtyUnitOptions = ['PCS', 'KPCS', 'MPCS'];

// Next snapshot time display
const nextSnapshotTime = computed(() => {
    if (!isAutoCapturing.value) return '';
    const now = new Date();
    const minutes = now.getMinutes();
    const nextMinute = Math.ceil(minutes / 5) * 5;
    const nextTime = new Date(now);
    nextTime.setMinutes(nextMinute, 0, 0);
    if (nextMinute >= 60) {
        nextTime.setHours(nextTime.getHours() + 1);
        nextTime.setMinutes(0, 0, 0);
    }
    return nextTime.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
});

// Auto-refresh timer
let refreshTimer: number | null = null;
let countdownTimer: number | null = null;
let sessionKeepAliveTimer: number | null = null;
const nextRefreshIn = ref(0);

const handleTimeChange = async (event: Event) => {
    const target = event.target as HTMLSelectElement;
    selectedTime.value = target.value;
    
    // Fetch new trend data for the selected time range
    const data = await fetchTrendData(
        mapTimeRange(selectedTime.value),
        'ALL',
        selectedLotWorktype.value,
        selectedDate.value,
        selectedMcStatus.value,
        selectedMcWorktype.value
    );
    
    // Update chart with the new data
    updateTrendChart(selectedTime.value, data);
};

// Toggle dropdowns
const dateInputRef = ref<HTMLInputElement | null>(null);

const toggleDatePicker = () => {
    showDatePicker.value = !showDatePicker.value;
    showMcStatusDropdown.value = false;
    showMcWorktypeDropdown.value = false;
    showLotWorktypeDropdown.value = false;
    showRefreshModal.value = false;
    showQtyUnitDropdown.value = false;
    
    // Automatically focus and open the date picker
    if (showDatePicker.value) {
        nextTick(() => {
            if (dateInputRef.value) {
                dateInputRef.value.focus();
                dateInputRef.value.showPicker?.();
            }
        });
    }
};

const toggleMcStatus = () => {
    showMcStatusDropdown.value = !showMcStatusDropdown.value;
    showDatePicker.value = false;
    showMcWorktypeDropdown.value = false;
    showLotWorktypeDropdown.value = false;
    showRefreshModal.value = false;
    showQtyUnitDropdown.value = false;
};

const toggleMcWorktype = () => {
    showMcWorktypeDropdown.value = !showMcWorktypeDropdown.value;
    showDatePicker.value = false;
    showMcStatusDropdown.value = false;
    showLotWorktypeDropdown.value = false;
    showRefreshModal.value = false;
    showQtyUnitDropdown.value = false;
};

const toggleLotWorktype = () => {
    showLotWorktypeDropdown.value = !showLotWorktypeDropdown.value;
    showDatePicker.value = false;
    showMcStatusDropdown.value = false;
    showMcWorktypeDropdown.value = false;
    showRefreshModal.value = false;
    showQtyUnitDropdown.value = false;
};

const toggleRefreshModal = () => {
    showRefreshModal.value = !showRefreshModal.value;
    showDatePicker.value = false;
    showMcStatusDropdown.value = false;
    showMcWorktypeDropdown.value = false;
    showLotWorktypeDropdown.value = false;
    showQtyUnitDropdown.value = false;
};

const toggleQtyUnit = () => {
    showQtyUnitDropdown.value = !showQtyUnitDropdown.value;
};

// Watch for QTY unit changes and save to localStorage
watch(selectedQtyUnit, (newUnit) => {
    localStorage.setItem('memsQtyUnit', newUnit);
});

// Computed property for filtered raw machine data
const filteredRawMachineData = computed(() => {
    let filtered = rawMachineData.value;
    
    // Apply status filter from Machine Operation Status table
    if (activeStatusFilter.value.line || activeStatusFilter.value.status) {
        filtered = filtered.filter(item => {
            let matchesLine = true;
            let matchesStatus = true;
            
            if (activeStatusFilter.value.line) {
                matchesLine = item.eqp_line === activeStatusFilter.value.line;
            }
            
            if (activeStatusFilter.value.status) {
                // Calculate the actual status based on eqp_status and ongoing_lot
                // WAIT = Operational but no ongoing_lot
                // RUN = Operational and has ongoing_lot
                // IDLE = Not Operational
                let actualStatus = '';
                
                if (item.eqp_status && item.eqp_status.toUpperCase() === 'OPERATIONAL') {
                    if (item.ongoing_lot && item.ongoing_lot.trim() !== '') {
                        actualStatus = 'RUN';
                    } else {
                        actualStatus = 'WAIT';
                    }
                } else {
                    actualStatus = 'IDLE';
                }
                
                matchesStatus = actualStatus === activeStatusFilter.value.status;
            }
            
            return matchesLine && matchesStatus;
        });
    }
    
    // Apply machine type/size filter from Operation Status per Machine table
    if (activeStatusFilter.value.machineTypeOrSize && activeStatusFilter.value.filterType) {
        filtered = filtered.filter(item => {
            if (activeStatusFilter.value.filterType === 'type') {
                // Filter by machine type (eqp_maker)
                return item.eqp_maker === activeStatusFilter.value.machineTypeOrSize;
            } else if (activeStatusFilter.value.filterType === 'size') {
                // Filter by size
                return item.size === activeStatusFilter.value.machineTypeOrSize;
            }
            return true;
        });
    }
    
    // Apply search filter
    if (rawDataSearchQuery.value) {
        const query = rawDataSearchQuery.value.toLowerCase();
        filtered = filtered.filter(item => 
            item.eqp_no?.toLowerCase().includes(query) ||
            item.eqp_maker?.toLowerCase().includes(query) ||
            item.eqp_line?.toLowerCase().includes(query) ||
            item.prev_lot?.toLowerCase().includes(query) ||
            item.prev_model?.toLowerCase().includes(query) ||
            item.est_endtime_formatted?.toLowerCase().includes(query)
        );
    }
    
    // Apply sorting
    if (rawDataSortColumn.value) {
        filtered = [...filtered].sort((a, b) => {
            let aVal: any = a[rawDataSortColumn.value as string];
            let bVal: any = b[rawDataSortColumn.value as string];
            
            // Handle numeric columns (waiting_minutes)
            if (rawDataSortColumn.value === 'waiting_minutes') {
                aVal = Number(aVal) || 0;
                bVal = Number(bVal) || 0;
            } else if (rawDataSortColumn.value === 'est_endtime') {
                // Handle date sorting for est_endtime
                aVal = aVal ? new Date(aVal).getTime() : 0;
                bVal = bVal ? new Date(bVal).getTime() : 0;
            } else {
                // Handle string columns
                aVal = String(aVal || '').toLowerCase();
                bVal = String(bVal || '').toLowerCase();
            }
            
            if (aVal < bVal) return rawDataSortDirection.value === 'asc' ? -1 : 1;
            if (aVal > bVal) return rawDataSortDirection.value === 'asc' ? 1 : -1;
            return 0;
        });
    }
    
    return filtered;
});

// Computed property for filtered raw lots data
const filteredRawLotsData = computed(() => {
    let filtered = rawLotsData.value;
    
    // Apply search filter
    if (rawDataSearchQuery.value) {
        const query = rawDataSearchQuery.value.toLowerCase();
        filtered = filtered.filter(item => 
            item.lot_no?.toLowerCase().includes(query) ||
            item.lot_model?.toLowerCase().includes(query) ||
            item.mc_no?.toLowerCase().includes(query) ||
            item.mc_line?.toLowerCase().includes(query) ||
            item.lipas?.toLowerCase().includes(query)
        );
    }
    
    // Apply sorting
    if (rawDataSortColumn.value) {
        filtered = [...filtered].sort((a, b) => {
            let aVal: any = a[rawDataSortColumn.value as string];
            let bVal: any = b[rawDataSortColumn.value as string];
            
            // Handle numeric columns (elapsed_minutes, lot_qty)
            if (rawDataSortColumn.value === 'elapsed_minutes' || rawDataSortColumn.value === 'lot_qty') {
                aVal = Number(aVal) || 0;
                bVal = Number(bVal) || 0;
            } else {
                // Handle string columns
                aVal = String(aVal || '').toLowerCase();
                bVal = String(bVal || '').toLowerCase();
            }
            
            if (aVal < bVal) return rawDataSortDirection.value === 'asc' ? -1 : 1;
            if (aVal > bVal) return rawDataSortDirection.value === 'asc' ? 1 : -1;
            return 0;
        });
    }
    
    return filtered;
});

// Handle sorting for raw data tables
const handleRawDataSort = (column: string) => {
    if (rawDataSortColumn.value === column) {
        // Toggle direction if same column
        rawDataSortDirection.value = rawDataSortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        // New column, default to ascending
        rawDataSortColumn.value = column;
        rawDataSortDirection.value = 'asc';
    }
};

// Handle clicking on status badges in Machine Operation Status table
const handleStatusBadgeClick = (line: string, status: string) => {
    // Set the filter - status should be RUN, WAIT, or IDLE
    activeStatusFilter.value = { 
        line, 
        status: status.toUpperCase(),
        machineTypeOrSize: null,
        filterType: null
    };
    
    // Switch to MACHINE view in raw data
    selectedRawDataView.value = 'machine';
    
    // Scroll to raw data section
    nextTick(() => {
        const rawDataSection = document.querySelector('.mems-raw-data');
        if (rawDataSection) {
            rawDataSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
};

// Handle clicking on machine type/size cells in Operation Status per Machine table
const handleMachineTypeClick = (typeOrSize: string, status: string) => {
    // Set the filter for machine type or size
    activeStatusFilter.value = { 
        line: null,
        status: status.toUpperCase(),
        machineTypeOrSize: typeOrSize,
        filterType: machineTypeStatusView.value
    };
    
    // Switch to MACHINE view in raw data
    selectedRawDataView.value = 'machine';
    
    // Scroll to raw data section
    nextTick(() => {
        const rawDataSection = document.querySelector('.mems-raw-data');
        if (rawDataSection) {
            rawDataSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
};

// Reset the status filter
const resetStatusFilter = () => {
    activeStatusFilter.value = { 
        line: null, 
        status: null,
        machineTypeOrSize: null,
        filterType: null
    };
    rawDataSearchQuery.value = '';
};

// Close all dropdowns when clicking outside
const closeAllDropdowns = () => {
    showDatePicker.value = false;
    showMcStatusDropdown.value = false;
    showMcWorktypeDropdown.value = false;
    showLotWorktypeDropdown.value = false;
    showRefreshModal.value = false;
    showQtyUnitDropdown.value = false;
};

// Apply filters
const applyFilters = async () => {
    // Fetch gauge data for current line
    await fetchLineUtilizationData();
    
    // Fetch table data for all lines
    await fetchAllLinesData();
    
    // Fetch machine type status data
    await fetchMachineTypeStatusData();
    await fetchMachineTypeStatusDataBySize();
    
    // Fetch endtime data
    await fetchEndtimeRemainingData();
    await fetchEndtimePerCutoffData();
    
    // Don't fetch raw data automatically - only when user switches to that view
    // This improves initial load performance
    
    // Fetch trend data
    const data = await fetchTrendData(
        mapTimeRange(selectedTime.value),
        'ALL',
        selectedLotWorktype.value,
        selectedDate.value,
        selectedMcStatus.value,
        selectedMcWorktype.value
    );
    updateTrendChart(selectedTime.value, data);
};

// Watch for filter changes (only mcStatus and mcWorktype for real-time equipment data)
// But also watch selectedDate for endtime data
watch([selectedMcStatus, selectedMcWorktype], async () => {
    // Fetch equipment status data
    fetchLineUtilizationData();
    fetchAllLinesData();
    fetchMachineTypeStatusData();
    fetchMachineTypeStatusDataBySize();
    
    // Refresh trend data when mcStatus or mcWorktype changes
    const data = await fetchTrendData(
        mapTimeRange(selectedTime.value),
        'ALL',
        selectedLotWorktype.value,
        selectedDate.value,
        selectedMcStatus.value,
        selectedMcWorktype.value
    );
    updateTrendChart(selectedTime.value, data);
    
    // Refresh raw machine data when filters change
    if (selectedRawDataView.value === 'machine') {
        fetchRawMachineData();
    }
});

watch([selectedDate], () => {
    // Fetch endtime data when date changes
    fetchEndtimeRemainingData();
    fetchEndtimePerCutoffData();
    
    // Fetch raw lots data if lots view is selected
    if (selectedRawDataView.value === 'lots') {
        fetchRawLotsData();
    }
});

// Watch for raw data view changes
watch(selectedRawDataView, (newView) => {
    if (newView === 'machine') {
        fetchRawMachineData();
    } else {
        fetchRawLotsData();
    }
});

// Auto-refresh functionality
const startAutoRefresh = () => {
    if (refreshTimer) {
        clearInterval(refreshTimer);
        clearInterval(countdownTimer!);
    }
    
    autoRefreshEnabled.value = true;
    nextRefreshIn.value = refreshInterval.value;
    
    // Refresh immediately
    applyFilters();
    
    // Set up refresh interval
    refreshTimer = setInterval(() => {
        applyFilters();
        nextRefreshIn.value = refreshInterval.value;
    }, refreshInterval.value * 1000);
    
    // Set up countdown
    countdownTimer = setInterval(() => {
        if (nextRefreshIn.value > 0) {
            nextRefreshIn.value--;
        }
    }, 1000);
    
    // Set up session keep-alive (ping every 5 minutes to prevent session expiration)
    if (sessionKeepAliveTimer) {
        clearInterval(sessionKeepAliveTimer);
    }
    sessionKeepAliveTimer = setInterval(() => {
        // Ping the server to keep session alive
        fetch('/api/session/keep-alive', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        }).catch(() => {
            // Silently fail - session might have expired, user will be redirected on next real request
        });
    }, 5 * 60 * 1000); // Every 5 minutes
    
    localStorage.setItem('memsAutoRefresh', 'true');
    localStorage.setItem('memsRefreshInterval', refreshInterval.value.toString());
};

const stopAutoRefresh = () => {
    autoRefreshEnabled.value = false;
    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }
    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
    if (sessionKeepAliveTimer) {
        clearInterval(sessionKeepAliveTimer);
        sessionKeepAliveTimer = null;
    }
    nextRefreshIn.value = 0;
    
    // Save OFF state to localStorage
    localStorage.setItem('memsAutoRefresh', 'false');
};

const toggleAutoRefresh = () => {
    if (autoRefreshEnabled.value) {
        stopAutoRefresh();
    } else {
        startAutoRefresh();
    }
    showRefreshModal.value = false;
};

const updateRefreshInterval = (seconds: number) => {
    refreshInterval.value = seconds;
    if (autoRefreshEnabled.value) {
        startAutoRefresh();
    }
};

// Format countdown display
const formatRefreshCountdown = () => {
    const minutes = Math.floor(nextRefreshIn.value / 60);
    const seconds = nextRefreshIn.value % 60;
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
};

// Line carousel functions
const startCarousel = () => {
    if (carouselTimer) {
        clearInterval(carouselTimer);
    }
    
    currentLineIndex.value = 0;
    fetchLineUtilizationData(); // Fetch data for first line
    
    carouselTimer = setInterval(() => {
        currentLineIndex.value = (currentLineIndex.value + 1) % lines.length;
        fetchLineUtilizationData(); // Fetch data for new line
    }, 10000); // Change every 10 seconds
};

const stopCarousel = () => {
    if (carouselTimer) {
        clearInterval(carouselTimer);
        carouselTimer = null;
    }
    currentLineIndex.value = -1; // Show total
    fetchLineUtilizationData(); // Fetch total data
};

const toggleCarousel = () => {
    if (carouselEnabled.value) {
        startCarousel();
        localStorage.setItem('memsCarouselEnabled', 'true');
    } else {
        stopCarousel();
        localStorage.setItem('memsCarouselEnabled', 'false');
    }
};

// Modal states
const showEquipmentDetailsModal = ref(false);
const showAssignLotModal = ref(false);
const showRemarksModal = ref(false);
const showSubmitLotModal = ref(false);
const selectedEquipment = ref<any>(null);
const selectedEquipmentForAssignment = ref<any>(null);
const selectedEquipmentForRemarks = ref<any>(null);
const selectedLotForSubmit = ref<any>(null);

// Action handlers for raw data table
const handleViewDetails = async (item: any) => {
    try {
        // Get equipment number from either eqp_no (machine data) or mc_no (lots data)
        const equipmentNo = item.eqp_no || item.mc_no;
        if (!equipmentNo) {
            alert('Equipment number not found');
            return;
        }
        
        // Fetch equipment details from API
        const response = await fetch(`/api/equipment/details/${equipmentNo}`);
        if (!response.ok) {
            throw new Error('Failed to fetch equipment details');
        }
        const data = await response.json();
        
        // Add waiting time from the raw data if available
        if (item.waiting_time) {
            data.waiting_time = item.waiting_time;
        }
        
        selectedEquipment.value = data;
        showEquipmentDetailsModal.value = true;
    } catch (error) {
        console.error('Error fetching equipment details:', error);
        alert('Failed to load equipment details');
    }
};

const handleAssignLot = (item: any) => {
    // Get equipment number from either eqp_no (machine data) or mc_no (lots data)
    const equipmentNo = item.eqp_no || item.mc_no;
    if (!equipmentNo) {
        alert('Equipment number not found');
        return;
    }
    
    selectedEquipmentForAssignment.value = {
        equipmentNo: equipmentNo,
        // For machine data: prev_model, for lots data: lot_model
        previousModel: item.prev_model || item.lot_model || null,
        // For machine data: alloc_type, for lots data: lot_worktype
        previousWorktype: item.alloc_type || item.lot_worktype || null
    };
    showAssignLotModal.value = true;
};

const handleAssignLotConfirm = async (lot: any) => {
    try {
        // TODO: Implement the actual lot assignment API call
        const response = await fetch('/api/equipment/assign-lot', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                equipment_no: selectedEquipmentForAssignment.value.equipmentNo,
                lot_no: lot.lot_no,
                lot_model: lot.lot_model,
                lot_qty: lot.lot_qty,
                work_type: lot.work_type
            })
        });
        
        if (!response.ok) {
            throw new Error('Failed to assign lot');
        }
        
        alert(`Lot ${lot.lot_no} assigned successfully to ${selectedEquipmentForAssignment.value.equipmentNo}`);
        
        // Refresh the data
        fetchRawMachineData();
    } catch (error) {
        console.error('Error assigning lot:', error);
        alert('Failed to assign lot');
    }
};

const handleRemarks = (item: any) => {
    // Get equipment number from either eqp_no (machine data) or mc_no (lots data)
    const equipmentNo = item.eqp_no || item.mc_no;
    if (!equipmentNo) {
        alert('Equipment number not found');
        return;
    }
    
    selectedEquipmentForRemarks.value = {
        equipmentNo: equipmentNo,
        currentRemarks: item.remarks || null
    };
    showRemarksModal.value = true;
};

const handleSubmitLot = (item: any) => {
    // Store the lot data for the submit modal
    selectedLotForSubmit.value = {
        lot_no: item.lot_no,
        lot_model: item.lot_model,
        lot_worktype: item.lot_worktype,
        mc_no: item.mc_no,
        ...item
    };
    showSubmitLotModal.value = true;
};

const handleDeleteLot = async (item: any) => {
    if (!canDeleteEndtime.value) {
        alert('You do not have permission to delete endtime records');
        return;
    }
    
    const confirmed = confirm(`Are you sure you want to delete lot ${item.lot_no}?\n\nThis action cannot be undone.`);
    if (!confirmed) return;
    
    try {
        const response = await fetch(`/endtime/${item.id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            redirect: 'manual' // Don't follow redirects automatically
        });
        
        // The backend returns a redirect (302) on success, which is OK
        if (response.ok || response.type === 'opaqueredirect' || response.status === 302) {
            alert(`Lot ${item.lot_no} deleted successfully`);
            
            // Refresh the data
            await fetchRawLotsData();
            await fetchEndtimeRemainingData();
            await fetchEndtimePerCutoffData();
        } else {
            throw new Error('Failed to delete lot');
        }
    } catch (error) {
        console.error('Error deleting lot:', error);
        alert(`Failed to delete lot: ${error.message}`);
    }
};

const handleRemarksSave = async (remarks: string) => {
    // Refresh the raw data to show updated remarks
    if (selectedRawDataView.value === 'machine') {
        await fetchRawMachineData();
    } else {
        await fetchRawLotsData();
    }
    
    // Show success message
    alert('Remarks saved successfully');
};

const scrollToLine = (direction: 'left' | 'right') => {
    if (carouselEnabled.value) return;
    
    if (direction === 'left') {
        if (currentLineIndex.value <= 0) {
            currentLineIndex.value = -1; // Go to total
        } else {
            currentLineIndex.value--;
        }
    } else {
        if (currentLineIndex.value < lines.length - 1) {
            currentLineIndex.value++;
        }
    }
    
    // Fetch data for the new line
    fetchLineUtilizationData();
};

const currentLineDisplay = computed(() => {
    if (currentLineIndex.value === -1) {
        return 'ALL';
    }
    return lines[currentLineIndex.value];
});

// Fetch filter options from database
const fetchFilterOptions = async () => {
    try {
        const response = await fetch('/api/mems/filters/options');
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error('Failed to fetch filter options');
        }
        
        const result = await response.json();
        
        // Update filter options with database values (only if we got results)
        if (result.mcStatuses && result.mcStatuses.length > 0) {
            mcStatusOptions.value = ['ALL', ...result.mcStatuses];
        }
        if (result.mcWorktypes && result.mcWorktypes.length > 0) {
            mcWorktypeOptions.value = ['ALL', ...result.mcWorktypes];
        }
        if (result.lotWorktypes && result.lotWorktypes.length > 0) {
            lotWorktypeOptions.value = ['ALL', ...result.lotWorktypes];
        }
    } catch (e) {
        // Keep the default ['ALL'] if API fails - don't add fake options
    }
};

// Add a global event listener for sidebar toggle
let sidebarToggleListener: (() => void) | null = null;

onMounted(() => {
    // Fetch filter options first
    fetchFilterOptions();
    
    // Set up snapshot callback to refresh data after capture
    setSnapshotCallback(async () => {
        await applyFilters();
    });
    
    // Restore auto-capture state (defaults to ON)
    restoreAutoCapture();
    
    // Restore carousel state
    restoreCarouselState();
    
    // Initial data load
    applyFilters();
    
    // Fetch initial raw data for the default view (machine)
    fetchRawMachineData();
    
    // Restore auto-refresh state (defaults to ON with 30 second interval)
    const savedAutoRefresh = localStorage.getItem('memsAutoRefresh');
    const savedInterval = localStorage.getItem('memsRefreshInterval');
    
    // Default to ON if no saved state exists, or if explicitly set to 'true'
    if (savedAutoRefresh !== 'false') {
        if (savedInterval) {
            refreshInterval.value = parseInt(savedInterval);
        }
        startAutoRefresh();
    }
    
    // Close dropdowns when clicking outside
    const handleClickOutside = (event: MouseEvent) => {
        const target = event.target as HTMLElement;
        if (!target.closest('.mems-filter-wrapper')) {
            closeAllDropdowns();
        }
    };
    
    document.addEventListener('click', handleClickOutside);
    
    // Listen for custom sidebar toggle events
    const handleSidebarToggle = () => {
        // Force layout recalculation on multiple elements
        const dashboard = document.querySelector('.mems-dashboard') as HTMLElement;
        const chartPanel = document.querySelector('.mems-ongoing-lots') as HTMLElement;
        const chartContent = document.querySelector('.mems-ongoing-lots .mems-panel-content') as HTMLElement;
        const canvas = document.getElementById('ongoingLotsChart') as HTMLCanvasElement;
        
        if (dashboard) {
            // Trigger reflow by reading offsetHeight
            void dashboard.offsetHeight;
        }
        
        if (chartPanel) {
            void chartPanel.offsetWidth;
        }
        
        if (chartContent) {
            void chartContent.offsetWidth;
        }
        
        if (canvas) {
            // Force canvas to recalculate
            const currentWidth = canvas.style.width;
            const currentHeight = canvas.style.height;
            canvas.style.width = '0px';
            canvas.style.height = '0px';
            void canvas.offsetHeight;
            canvas.style.width = currentWidth;
            canvas.style.height = currentHeight;
        }
        
        // Multiple resize calls at different intervals
        setTimeout(() => handleResize(), 50);
        setTimeout(() => handleResize(), 250);
        setTimeout(() => handleResize(), 500);
    };
    
    window.addEventListener('sidebar-toggle', handleSidebarToggle);
    sidebarToggleListener = () => {
        window.removeEventListener('sidebar-toggle', handleSidebarToggle);
        document.removeEventListener('click', handleClickOutside);
    };
});

onBeforeUnmount(() => {
    sidebarToggleListener?.();
    stopAutoRefresh();
    stopCarousel();
});
</script>

<template>
    <Head title="MEMS Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #center>
            <h1 class="mems-title-header">MACHINE AND ENDTIME MONITORING SYSTEM</h1>
        </template>
        
        <template #filters>
            <div class="mems-header-buttons" @click.stop>
                <!-- Date Picker -->
                <div class="mems-filter-wrapper">
                    <button class="mems-btn" @click="toggleDatePicker">
                        📅 {{ selectedDate }}
                    </button>
                    <div v-if="showDatePicker" class="mems-dropdown">
                        <input 
                            ref="dateInputRef"
                            type="date" 
                            v-model="selectedDate" 
                            class="mems-date-input"
                            @change="closeAllDropdowns"
                        />
                    </div>
                </div>

                <!-- MC Status -->
                <div class="mems-filter-wrapper">
                    <button class="mems-btn" @click="toggleMcStatus">
                        MC STATUS: {{ selectedMcStatus }}
                    </button>
                    <div v-if="showMcStatusDropdown" class="mems-dropdown">
                        <button 
                            v-for="status in mcStatusOptions" 
                            :key="status"
                            class="mems-dropdown-item"
                            :class="{ active: selectedMcStatus === status }"
                            @click="selectedMcStatus = status; closeAllDropdowns()"
                        >
                            {{ status }}
                        </button>
                    </div>
                </div>

                <!-- MC Worktype -->
                <div class="mems-filter-wrapper">
                    <button class="mems-btn" @click="toggleMcWorktype">
                        MC WORKTYPE: {{ selectedMcWorktype }}
                    </button>
                    <div v-if="showMcWorktypeDropdown" class="mems-dropdown">
                        <button 
                            v-for="worktype in mcWorktypeOptions" 
                            :key="worktype"
                            class="mems-dropdown-item"
                            :class="{ active: selectedMcWorktype === worktype }"
                            @click="selectedMcWorktype = worktype; closeAllDropdowns()"
                        >
                            {{ worktype }}
                        </button>
                    </div>
                </div>

                <!-- Lot Worktype -->
                <div class="mems-filter-wrapper">
                    <button class="mems-btn" @click="toggleLotWorktype">
                        LOT WORKTYPE: {{ selectedLotWorktype }}
                    </button>
                    <div v-if="showLotWorktypeDropdown" class="mems-dropdown">
                        <button 
                            v-for="worktype in lotWorktypeOptions" 
                            :key="worktype"
                            class="mems-dropdown-item"
                            :class="{ active: selectedLotWorktype === worktype }"
                            @click="selectedLotWorktype = worktype; closeAllDropdowns()"
                        >
                            {{ worktype }}
                        </button>
                    </div>
                </div>

                <!-- Auto Refresh Toggle -->
                <div class="mems-filter-wrapper">
                    <button 
                        class="mems-btn" 
                        :class="{ active: autoRefreshEnabled }"
                        @click="toggleRefreshModal"
                    >
                        🔄 AUTO REFRESH {{ autoRefreshEnabled ? 'ON' : 'OFF' }}
                        <span v-if="autoRefreshEnabled" class="mems-countdown">
                            ({{ formatRefreshCountdown() }})
                        </span>
                    </button>
                    <div v-if="showRefreshModal" class="mems-dropdown mems-refresh-modal">
                        <div class="mems-refresh-header">Auto Refresh Settings</div>
                        <div class="mems-refresh-status">
                            Status: <strong>{{ autoRefreshEnabled ? 'ENABLED' : 'DISABLED' }}</strong>
                        </div>
                        <div class="mems-refresh-interval">
                            <label>Interval (seconds):</label>
                            <div class="mems-interval-buttons">
                                <button 
                                    v-for="interval in [5, 10, 15, 30, 45, 60]" 
                                    :key="interval"
                                    class="mems-interval-btn"
                                    :class="{ active: refreshInterval === interval }"
                                    @click="updateRefreshInterval(interval)"
                                >
                                    {{ interval }}s
                                </button>
                            </div>
                        </div>
                        <button 
                            class="mems-toggle-btn"
                            :class="{ active: autoRefreshEnabled }"
                            @click="toggleAutoRefresh"
                        >
                            {{ autoRefreshEnabled ? 'STOP' : 'START' }} AUTO REFRESH
                        </button>
                    </div>
                </div>
            </div>
        </template>
        
        <div class="mems-dashboard">
            <div class="mems-main-grid">
                <!-- Left Column -->
                <div class="mems-left-col">
                    <!-- Machine Utilization -->
                    <div class="mems-panel mems-machine-util">
                        <div class="mems-panel-header">Machine Operation Status</div>
                        <div class="mems-panel-content mems-machine-util-content" style="background: transparent; color: white; overflow: hidden;">
                            <div class="mems-chart-area" style="width: 50%; display: flex; flex-direction: column; height: 100%; min-height: 0;">
                                <div class="mems-gauge-controls">
                                    <div class="mems-line-display">
                                        <span class="mems-line-prefix">Line</span>
                                        <button 
                                            @click="scrollToLine('left')"
                                            class="mems-nav-btn"
                                            :disabled="carouselEnabled || currentLineIndex <= -1"
                                            title="Previous"
                                        >
                                            ◀
                                        </button>
                                        <span class="mems-line-label">{{ currentLineDisplay }}</span>
                                        <button 
                                            @click="scrollToLine('right')"
                                            class="mems-nav-btn"
                                            :disabled="carouselEnabled || currentLineIndex >= lines.length - 1"
                                            title="Next"
                                        >
                                            ▶
                                        </button>
                                    </div>
                                    <div class="mems-carousel-toggle">
                                        <span class="mems-toggle-label">SCROLL</span>
                                        <label class="mems-toggle-switch">
                                            <input 
                                                type="checkbox" 
                                                v-model="carouselEnabled"
                                                @change="toggleCarousel"
                                            />
                                            <span class="mems-toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <div style="flex: 1; position: relative; min-height: 0; padding: 4px;">
                                    <div id="utilizationGauge" style="width: 100%; height: 100%;"></div>
                                </div>
                                <div class="mems-chart-legend">
                                    <div class="mems-legend-item">
                                        <div class="mems-legend-badge run"></div>
                                        <span>Run ({{ currentGaugeData?.run || 0 }})</span>
                                    </div>
                                    <div class="mems-legend-item">
                                        <div class="mems-legend-badge wait"></div>
                                        <span>Wait ({{ currentGaugeData?.wait || 0 }})</span>
                                    </div>
                                    <div class="mems-legend-item">
                                        <div class="mems-legend-badge idle"></div>
                                        <span>Idle ({{ currentGaugeData?.idle || 0 }})</span>
                                    </div>
                                </div>
                            </div>
                            <div style="width: 48%; display: flex; flex-direction: column; min-height: 0; overflow: auto;">
                                <table class="mems-table mems-util-table" style="height: auto;">
                                    <thead>
                                        <tr>
                                            <th>LINE</th>
                                            <th>COUNT</th>
                                            <th>RUN</th>
                                            <th>WAIT</th>
                                            <th>IDLE</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="lineData in lineUtilizationData" :key="lineData.line">
                                            <td>{{ lineData.line }}</td>
                                            <td>{{ lineData.count }}</td>
                                            <td>
                                                <span 
                                                    class="mems-status-badge mems-status-run clickable-badge" 
                                                    @click="handleStatusBadgeClick(lineData.line, 'Run')"
                                                    :title="`Click to filter ${lineData.run.value} running machines in line ${lineData.line}`"
                                                >
                                                    {{ lineData.run.value }} ({{ lineData.run.percent.toFixed(1) }}%)
                                                </span>
                                            </td>
                                            <td>
                                                <span 
                                                    class="mems-status-badge mems-status-wait clickable-badge" 
                                                    @click="handleStatusBadgeClick(lineData.line, 'Wait')"
                                                    :title="`Click to filter ${lineData.wait.value} waiting machines in line ${lineData.line}`"
                                                >
                                                    {{ lineData.wait.value }} ({{ lineData.wait.percent.toFixed(1) }}%)
                                                </span>
                                            </td>
                                            <td>
                                                <span 
                                                    class="mems-status-badge mems-status-idle clickable-badge" 
                                                    @click="handleStatusBadgeClick(lineData.line, 'Idle')"
                                                    :title="`Click to filter ${lineData.idle.value} idle machines in line ${lineData.line}`"
                                                >
                                                    {{ lineData.idle.value }} ({{ lineData.idle.percent.toFixed(1) }}%)
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="lineUtilizationData.length === 0">
                                            <td colspan="5" style="text-align: center; padding: 20px;">No data available</td>
                                        </tr>
                                        <tr v-if="lineUtilizationData.length > 0">
                                            <td>TOTAL</td>
                                            <td>{{ lineUtilizationData.reduce((sum, line) => sum + line.count, 0) }}</td>
                                            <td>
                                                <span class="mems-status-badge mems-status-run">
                                                    {{ lineUtilizationData.reduce((sum, line) => sum + line.run.value, 0) }}
                                                    ({{ (() => {
                                                        const total = lineUtilizationData.reduce((sum, line) => sum + line.count, 0);
                                                        const run = lineUtilizationData.reduce((sum, line) => sum + line.run.value, 0);
                                                        return total > 0 ? ((run / total) * 100).toFixed(1) : 0;
                                                    })() }}%)
                                                </span>
                                            </td>
                                            <td>
                                                <span class="mems-status-badge mems-status-wait">
                                                    {{ lineUtilizationData.reduce((sum, line) => sum + line.wait.value, 0) }}
                                                    ({{ (() => {
                                                        const total = lineUtilizationData.reduce((sum, line) => sum + line.count, 0);
                                                        const wait = lineUtilizationData.reduce((sum, line) => sum + line.wait.value, 0);
                                                        return total > 0 ? ((wait / total) * 100).toFixed(1) : 0;
                                                    })() }}%)
                                                </span>
                                            </td>
                                            <td>
                                                <span class="mems-status-badge mems-status-idle">
                                                    {{ lineUtilizationData.reduce((sum, line) => sum + line.idle.value, 0) }}
                                                    ({{ (() => {
                                                        const total = lineUtilizationData.reduce((sum, line) => sum + line.count, 0);
                                                        const idle = lineUtilizationData.reduce((sum, line) => sum + line.idle.value, 0);
                                                        return total > 0 ? ((idle / total) * 100).toFixed(1) : 0;
                                                    })() }}%)
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Status per Machine Type -->
                    <div class="mems-panel mems-machine-type-status">
                        <div class="mems-panel-header" style="display: flex; align-items: center; justify-content: flex-start !important; gap: 15px;">
                            <span>Operation Status per Machine</span>
                            <!-- Radio button selection -->
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer; font-size: 0.75rem; font-weight: 600;">
                                    <input 
                                        type="radio" 
                                        value="type" 
                                        v-model="machineTypeStatusView"
                                        style="cursor: pointer;"
                                    />
                                    <span>Type</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer; font-size: 0.75rem; font-weight: 600;">
                                    <input 
                                        type="radio" 
                                        value="size" 
                                        v-model="machineTypeStatusView"
                                        style="cursor: pointer;"
                                    />
                                    <span>Size</span>
                                </label>
                            </div>
                        </div>
                        <div class="mems-panel-content" style="padding: 0">
                            <!-- Type View -->
                            <table v-if="machineTypeStatusView === 'type'" class="mems-table">
                                <thead>
                                    <tr>
                                        <th>ITEM</th>
                                        <th v-for="type in machineTypes" :key="type">{{ type }}</th>
                                        <th>TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!machineTypeStatusData">
                                        <td :colspan="machineTypes.length + 2" style="text-align: center; padding: 20px;">Loading...</td>
                                    </tr>
                                    <template v-else>
                                        <tr class="mems-machine-type-run">
                                            <td>RUN</td>
                                            <td 
                                                v-for="type in machineTypes" 
                                                :key="type"
                                                @click="handleMachineTypeClick(type, 'RUN')"
                                                class="clickable-cell"
                                                :title="`Click to filter ${machineTypeStatusData[type]?.run.value || 0} running ${type} machines`"
                                            >
                                                {{ machineTypeStatusData[type]?.run.value || 0 }} ( {{ machineTypeStatusData[type]?.run.percent || 0 }}% )
                                            </td>
                                            <td>{{ machineTypeStatusData.TOTAL?.run.value || 0 }} ( {{ machineTypeStatusData.TOTAL?.run.percent || 0 }}% )</td>
                                        </tr>
                                        <tr class="mems-machine-type-wait">
                                            <td>WAIT</td>
                                            <td 
                                                v-for="type in machineTypes" 
                                                :key="type"
                                                @click="handleMachineTypeClick(type, 'WAIT')"
                                                class="clickable-cell"
                                                :title="`Click to filter ${machineTypeStatusData[type]?.wait.value || 0} waiting ${type} machines`"
                                            >
                                                {{ machineTypeStatusData[type]?.wait.value || 0 }} ( {{ machineTypeStatusData[type]?.wait.percent || 0 }}% )
                                            </td>
                                            <td>{{ machineTypeStatusData.TOTAL?.wait.value || 0 }} ( {{ machineTypeStatusData.TOTAL?.wait.percent || 0 }}% )</td>
                                        </tr>
                                        <tr class="mems-machine-type-idle">
                                            <td>IDLE</td>
                                            <td 
                                                v-for="type in machineTypes" 
                                                :key="type"
                                                @click="handleMachineTypeClick(type, 'IDLE')"
                                                class="clickable-cell"
                                                :title="`Click to filter ${machineTypeStatusData[type]?.idle.value || 0} idle ${type} machines`"
                                            >
                                                {{ machineTypeStatusData[type]?.idle.value || 0 }} ( {{ machineTypeStatusData[type]?.idle.percent || 0 }}% )
                                            </td>
                                            <td>{{ machineTypeStatusData.TOTAL?.idle.value || 0 }} ( {{ machineTypeStatusData.TOTAL?.idle.percent || 0 }}% )</td>
                                        </tr>
                                        <tr>
                                            <td>TOTAL</td>
                                            <td v-for="type in machineTypes" :key="type">
                                                {{ machineTypeStatusData[type]?.value || 0 }}
                                                <span v-if="machineTypeStatusData.TOTAL?.value > 0">
                                                    ( {{ ((machineTypeStatusData[type]?.value || 0) / machineTypeStatusData.TOTAL.value * 100).toFixed(1) }}% )
                                                </span>
                                            </td>
                                            <td>{{ machineTypeStatusData.TOTAL?.value || 0 }} ( 100% )</td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            
                            <!-- Size View -->
                            <table v-else class="mems-table">
                                <thead>
                                    <tr>
                                        <th>ITEM</th>
                                        <th v-for="size in sizeColumns" :key="size.display">{{ size.display }}</th>
                                        <th>TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!machineTypeStatusDataBySize">
                                        <td :colspan="sizeColumns.length + 2" style="text-align: center; padding: 20px;">Loading...</td>
                                    </tr>
                                    <template v-else>
                                        <tr class="mems-machine-type-run">
                                            <td>RUN</td>
                                            <td 
                                                v-for="size in sizeColumns" 
                                                :key="size.display"
                                                @click="handleMachineTypeClick(size.dbValue, 'RUN')"
                                                class="clickable-cell"
                                                :title="`Click to filter ${machineTypeStatusDataBySize[size.dbValue]?.run.value || 0} running ${size.display} machines`"
                                            >
                                                {{ machineTypeStatusDataBySize[size.dbValue]?.run.value || 0 }} ( {{ machineTypeStatusDataBySize[size.dbValue]?.run.percent || 0 }}% )
                                            </td>
                                            <td>{{ machineTypeStatusDataBySize.TOTAL?.run.value || 0 }} ( {{ machineTypeStatusDataBySize.TOTAL?.run.percent || 0 }}% )</td>
                                        </tr>
                                        <tr class="mems-machine-type-wait">
                                            <td>WAIT</td>
                                            <td 
                                                v-for="size in sizeColumns" 
                                                :key="size.display"
                                                @click="handleMachineTypeClick(size.dbValue, 'WAIT')"
                                                class="clickable-cell"
                                                :title="`Click to filter ${machineTypeStatusDataBySize[size.dbValue]?.wait.value || 0} waiting ${size.display} machines`"
                                            >
                                                {{ machineTypeStatusDataBySize[size.dbValue]?.wait.value || 0 }} ( {{ machineTypeStatusDataBySize[size.dbValue]?.wait.percent || 0 }}% )
                                            </td>
                                            <td>{{ machineTypeStatusDataBySize.TOTAL?.wait.value || 0 }} ( {{ machineTypeStatusDataBySize.TOTAL?.wait.percent || 0 }}% )</td>
                                        </tr>
                                        <tr class="mems-machine-type-idle">
                                            <td>IDLE</td>
                                            <td 
                                                v-for="size in sizeColumns" 
                                                :key="size.display"
                                                @click="handleMachineTypeClick(size.dbValue, 'IDLE')"
                                                class="clickable-cell"
                                                :title="`Click to filter ${machineTypeStatusDataBySize[size.dbValue]?.idle.value || 0} idle ${size.display} machines`"
                                            >
                                                {{ machineTypeStatusDataBySize[size.dbValue]?.idle.value || 0 }} ( {{ machineTypeStatusDataBySize[size.dbValue]?.idle.percent || 0 }}% )
                                            </td>
                                            <td>{{ machineTypeStatusDataBySize.TOTAL?.idle.value || 0 }} ( {{ machineTypeStatusDataBySize.TOTAL?.idle.percent || 0 }}% )</td>
                                        </tr>
                                        <tr>
                                            <td>TOTAL</td>
                                            <td v-for="size in sizeColumns" :key="size.display">
                                                {{ machineTypeStatusDataBySize[size.dbValue]?.value || 0 }}
                                                <span v-if="machineTypeStatusDataBySize.TOTAL?.value > 0">
                                                    ( {{ ((machineTypeStatusDataBySize[size.dbValue]?.value || 0) / machineTypeStatusDataBySize.TOTAL.value * 100).toFixed(1) }}% )
                                                </span>
                                            </td>
                                            <td>{{ machineTypeStatusDataBySize.TOTAL?.value || 0 }} ( 100% )</td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="mems-right-col">
                    <!-- Machine Operation Trend -->
                    <div class="mems-panel mems-ongoing-lots">
                        <div class="mems-panel-header" style="display: flex; justify-content: space-between; align-items: center; padding: 6px 15px; gap: 10px; overflow: visible;">
                            <span>Machine Operation Trend</span>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div class="mems-time-dropdown">
                                    <select class="mems-time-select" @change="handleTimeChange" v-model="selectedTime">
                                        <option value="1h">1 Hour</option>
                                        <option value="2h">2 Hours</option>
                                        <option value="4h">4 Hours</option>
                                        <option value="12h">12 Hours</option>
                                        <option value="1d">1 Day</option>
                                    </select>
                                </div>
                                <div class="mems-snapshot-wrapper">
                                    <button 
                                        class="mems-snapshot-toggle-btn"
                                        :class="{ active: isAutoCapturing }"
                                        @click="toggleAutoCapture"
                                        :title="isAutoCapturing ? `Auto Snapshot ON - Next at ${nextSnapshotTime}` : 'Auto Snapshot OFF - Click to enable 5-min captures'"
                                    >
                                        <span class="snapshot-icon">📸</span>
                                        <span class="snapshot-status">{{ isAutoCapturing ? 'ON' : 'OFF' }}</span>
                                        <span v-if="isAutoCapturing" class="snapshot-countdown">{{ formatCountdown }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mems-panel-content" style="position: relative; padding: 8px; flex: 1; overflow: visible;">
                            <canvas id="ongoingLotsChart" style="position: relative; width: 100%; height: 100%;"></canvas>
                        </div>
                    </div>

                    <!-- Endtime remaining -->
                    <div class="mems-panel">
                        <div class="mems-panel-header">
                            <span>Endtime remaining | Delay</span>
                            <div class="mems-panel-header-dropdown" @click.stop>
                                <button class="mems-panel-header-dropdown-btn" @click="toggleQtyUnit">
                                    {{ selectedQtyUnit }}
                                </button>
                                <div v-if="showQtyUnitDropdown" class="mems-panel-header-dropdown-menu">
                                    <button 
                                        v-for="unit in qtyUnitOptions" 
                                        :key="unit"
                                        class="mems-panel-header-dropdown-item"
                                        :class="{ active: selectedQtyUnit === unit }"
                                        @click="selectedQtyUnit = unit; showQtyUnitDropdown = false"
                                    >
                                        {{ unit }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mems-panel-content" style="padding: 0">
                            <table class="mems-table mems-endtime-table">
                                <thead>
                                    <tr>
                                        <th>CLASS</th>
                                        <th>COUNT</th>
                                        <th>QTY</th>
                                        <th>0603</th>
                                        <th>1005</th>
                                        <th>1608</th>
                                        <th>2012</th>
                                        <th>3216</th>
                                        <th>3225</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr data-class="A">
                                        <td>A | 0~1 HRS</td>
                                        <td>{{ formatNumber(getEndtimeRemainingRow('A').count) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('A').qty) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('A')['0603']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('A')['1005']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('A')['1608']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('A')['2012']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('A')['3216']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('A')['3225']) }}</td>
                                    </tr>
                                    <tr data-class="B">
                                        <td>B | 1~3 HRS</td>
                                        <td>{{ formatNumber(getEndtimeRemainingRow('B').count) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('B').qty) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('B')['0603']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('B')['1005']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('B')['1608']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('B')['2012']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('B')['3216']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('B')['3225']) }}</td>
                                    </tr>
                                    <tr data-class="C">
                                        <td>C | 3~6 HRS</td>
                                        <td>{{ formatNumber(getEndtimeRemainingRow('C').count) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('C').qty) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('C')['0603']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('C')['1005']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('C')['1608']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('C')['2012']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('C')['3216']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('C')['3225']) }}</td>
                                    </tr>
                                    <tr data-class="D">
                                        <td>D | 6+ HRS</td>
                                        <td>{{ formatNumber(getEndtimeRemainingRow('D').count) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('D').qty) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('D')['0603']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('D')['1005']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('D')['1608']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('D')['2012']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('D')['3216']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('D')['3225']) }}</td>
                                    </tr>
                                    <tr class="mems-total-row">
                                        <td>TOTAL</td>
                                        <td>{{ formatNumber(getEndtimeRemainingRow('TOTAL').count) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('TOTAL').qty) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('TOTAL')['0603']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('TOTAL')['1005']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('TOTAL')['1608']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('TOTAL')['2012']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('TOTAL')['3216']) }}</td>
                                        <td>{{ formatQty(getEndtimeRemainingRow('TOTAL')['3225']) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Endtime per cutoff -->
                    <div class="mems-panel">
                        <div class="mems-panel-header">
                            <span>Endtime per cutoff</span>
                            <div class="mems-panel-header-dropdown" @click.stop>
                                <button class="mems-panel-header-dropdown-btn" @click="toggleQtyUnit">
                                    {{ selectedQtyUnit }}
                                </button>
                                <div v-if="showQtyUnitDropdown" class="mems-panel-header-dropdown-menu">
                                    <button 
                                        v-for="unit in qtyUnitOptions" 
                                        :key="unit"
                                        class="mems-panel-header-dropdown-item"
                                        :class="{ active: selectedQtyUnit === unit }"
                                        @click="selectedQtyUnit = unit; showQtyUnitDropdown = false"
                                    >
                                        {{ unit }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mems-panel-content" style="padding: 0">
                            <table class="mems-table mems-endtime-table">
                                <thead>
                                    <tr>
                                        <th>CUTOFF</th>
                                        <th>COUNT</th>
                                        <th>QTY</th>
                                        <th>0603</th>
                                        <th>1005</th>
                                        <th>1608</th>
                                        <th>2012</th>
                                        <th>3216</th>
                                        <th>3225</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr data-cutoff="00:00~03:59">
                                        <td>00:00~03:59</td>
                                        <td>{{ formatNumber(getEndtimePerCutoffRow('00:00~03:59').count) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('00:00~03:59').qty) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('00:00~03:59')['0603']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('00:00~03:59')['1005']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('00:00~03:59')['1608']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('00:00~03:59')['2012']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('00:00~03:59')['3216']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('00:00~03:59')['3225']) }}</td>
                                    </tr>
                                    <tr data-cutoff="04:00~06:59">
                                        <td>04:00~06:59</td>
                                        <td>{{ formatNumber(getEndtimePerCutoffRow('04:00~06:59').count) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('04:00~06:59').qty) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('04:00~06:59')['0603']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('04:00~06:59')['1005']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('04:00~06:59')['1608']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('04:00~06:59')['2012']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('04:00~06:59')['3216']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('04:00~06:59')['3225']) }}</td>
                                    </tr>
                                    <tr data-cutoff="07:00~11:59">
                                        <td>07:00~11:59</td>
                                        <td>{{ formatNumber(getEndtimePerCutoffRow('07:00~11:59').count) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('07:00~11:59').qty) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('07:00~11:59')['0603']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('07:00~11:59')['1005']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('07:00~11:59')['1608']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('07:00~11:59')['2012']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('07:00~11:59')['3216']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('07:00~11:59')['3225']) }}</td>
                                    </tr>
                                    <tr data-cutoff="12:00~15:59">
                                        <td>12:00~15:59</td>
                                        <td>{{ formatNumber(getEndtimePerCutoffRow('12:00~15:59').count) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('12:00~15:59').qty) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('12:00~15:59')['0603']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('12:00~15:59')['1005']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('12:00~15:59')['1608']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('12:00~15:59')['2012']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('12:00~15:59')['3216']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('12:00~15:59')['3225']) }}</td>
                                    </tr>
                                    <tr data-cutoff="16:00~18:59">
                                        <td>16:00~18:59</td>
                                        <td>{{ formatNumber(getEndtimePerCutoffRow('16:00~18:59').count) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('16:00~18:59').qty) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('16:00~18:59')['0603']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('16:00~18:59')['1005']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('16:00~18:59')['1608']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('16:00~18:59')['2012']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('16:00~18:59')['3216']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('16:00~18:59')['3225']) }}</td>
                                    </tr>
                                    <tr data-cutoff="19:00~23:59">
                                        <td>19:00~23:59</td>
                                        <td>{{ formatNumber(getEndtimePerCutoffRow('19:00~23:59').count) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('19:00~23:59').qty) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('19:00~23:59')['0603']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('19:00~23:59')['1005']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('19:00~23:59')['1608']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('19:00~23:59')['2012']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('19:00~23:59')['3216']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('19:00~23:59')['3225']) }}</td>
                                    </tr>
                                    <tr class="mems-total-row">
                                        <td>TOTAL</td>
                                        <td>{{ formatNumber(getEndtimePerCutoffRow('TOTAL').count) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('TOTAL').qty) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('TOTAL')['0603']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('TOTAL')['1005']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('TOTAL')['1608']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('TOTAL')['2012']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('TOTAL')['3216']) }}</td>
                                        <td>{{ formatQty(getEndtimePerCutoffRow('TOTAL')['3225']) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Raw Data -->
            <div class="mems-panel mems-raw-data">
                <div class="mems-panel-header" style="display: flex; justify-content: space-between; align-items: center; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 15px; flex: 1;">
                        <span>Raw Data</span>
                        <!-- Radio button selection -->
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer; font-size: 0.75rem; font-weight: 600;">
                                <input 
                                    type="radio" 
                                    value="machine" 
                                    v-model="selectedRawDataView"
                                    style="cursor: pointer;"
                                />
                                <span>Machine</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer; font-size: 0.75rem; font-weight: 600;">
                                <input 
                                    type="radio" 
                                    value="lots" 
                                    v-model="selectedRawDataView"
                                    style="cursor: pointer;"
                                />
                                <span>Lots</span>
                            </label>
                        </div>
                        <!-- Active Filter Indicator -->
                        <div v-if="activeStatusFilter.line || activeStatusFilter.status || activeStatusFilter.machineTypeOrSize" style="display: flex; align-items: center; gap: 8px; padding: 4px 12px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 6px;">
                            <span style="font-size: 0.7rem; color: var(--mems-accent-primary); font-weight: 600;">
                                🔍 Filtered: 
                                <template v-if="activeStatusFilter.line && activeStatusFilter.status">
                                    Line {{ activeStatusFilter.line }} - {{ activeStatusFilter.status }}
                                </template>
                                <template v-else-if="activeStatusFilter.machineTypeOrSize && activeStatusFilter.status">
                                    {{ activeStatusFilter.filterType === 'type' ? 'Type' : 'Size' }} {{ activeStatusFilter.machineTypeOrSize }} - {{ activeStatusFilter.status }}
                                </template>
                            </span>
                            <button 
                                @click="resetStatusFilter"
                                style="background: transparent; border: none; color: var(--mems-accent-primary); cursor: pointer; font-size: 0.7rem; font-weight: 600; padding: 2px 6px; border-radius: 4px; transition: all 0.2s;"
                                title="Reset filter"
                                onmouseover="this.style.background='rgba(59, 130, 246, 0.2)'"
                                onmouseout="this.style.background='transparent'"
                            >
                                ✕ Reset
                            </button>
                        </div>
                    </div>
                    <input 
                        type="text" 
                        placeholder="Search ...." 
                        class="mems-search-input"
                        v-model="rawDataSearchQuery"
                    />
                </div>
                <div class="mems-panel-content mems-raw-data-content" style="padding: 0">
                    <!-- Machine View -->
                    <table v-if="selectedRawDataView === 'machine'" class="mems-table" style="min-height: 100px">
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th class="sortable-header" @click="handleRawDataSort('eqp_no')">
                                    MC NO.
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'eqp_no'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('eqp_type')">
                                    MC TYPE
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'eqp_type'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('eqp_line')">
                                    LINE
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'eqp_line'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('eqp_area')">
                                    AREA
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'eqp_area'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('eqp_size')">
                                    SIZE
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'eqp_size'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('prev_lot')">
                                    PREV. LOT NO.
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'prev_lot'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('prev_model')">
                                    PREV. MODEL
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'prev_model'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('prev_worktype')">
                                    WORKTYPE
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'prev_worktype'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('est_endtime')">
                                    EST. ENDTIME
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'est_endtime'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('waiting_minutes')">
                                    WAITING TIME
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'waiting_minutes'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="filteredRawMachineData.length === 0">
                                <td colspan="12" style="text-align: center; padding: 20px; color: var(--mems-text-secondary);">
                                    No data available
                                </td>
                            </tr>
                            <tr v-for="(item, index) in filteredRawMachineData" :key="index">
                                <td>{{ index + 1 }}</td>
                                <td>{{ item.eqp_no }}</td>
                                <td>{{ item.eqp_maker }}</td>
                                <td>{{ item.eqp_line }}</td>
                                <td>{{ item.eqp_area }}</td>
                                <td>{{ item.size }}</td>
                                <td>{{ item.prev_lot || '-' }}</td>
                                <td>{{ item.prev_model || '-' }}</td>
                                <td>{{ item.alloc_type }}</td>
                                <td>{{ item.est_endtime_formatted || '-' }}</td>
                                <td :class="getElapsedTimeClass(item.waiting_minutes)">{{ item.waiting_time }}</td>
                                <td>
                                    <div class="mems-action-group">
                                        <button class="mems-action-btn mems-action-view" title="View Details" @click="handleViewDetails(item)">
                                            <span class="action-icon">👁️</span>
                                        </button>
                                        <button class="mems-action-btn mems-action-assign" title="Assign Lot" @click="handleAssignLot(item)">
                                            <span class="action-icon">📋</span>
                                            <span class="action-text">Assign</span>
                                        </button>
                                        <button class="mems-action-btn mems-action-remarks" title="Remarks" @click="handleRemarks(item)">
                                            <span class="action-icon">💬</span>
                                            <span class="action-text">Remarks</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Lots View -->
                    <table v-else class="mems-table" style="min-height: 100px">
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th class="sortable-header" @click="handleRawDataSort('lot_no')">
                                    LOT NO.
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'lot_no'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('lot_model')">
                                    MODEL
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'lot_model'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('mc_line')">
                                    LINE
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'mc_line'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('mc_area')">
                                    AREA
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'mc_area'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('lot_size')">
                                    SIZE
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'lot_size'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('lot_qty')">
                                    LOT QTY
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'lot_qty'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('mc_no')">
                                    MC NO.
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'mc_no'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('lot_worktype')">
                                    WORKTYPE
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'lot_worktype'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('lipas')">
                                    LIPAS
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'lipas'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('est_endtime')">
                                    EST. ENDTIME
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'est_endtime'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th class="sortable-header" @click="handleRawDataSort('elapsed_minutes')">
                                    ELAPSED TIME
                                    <span class="sort-indicator">
                                        <span v-if="rawDataSortColumn !== 'elapsed_minutes'">↕</span>
                                        <span v-else-if="rawDataSortDirection === 'asc'">↑</span>
                                        <span v-else>↓</span>
                                    </span>
                                </th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="filteredRawLotsData.length === 0">
                                <td colspan="13" style="text-align: center; padding: 20px; color: var(--mems-text-secondary);">
                                    No data available
                                </td>
                            </tr>
                            <tr v-for="(item, index) in filteredRawLotsData" :key="index">
                                <td>{{ index + 1 }}</td>
                                <td>{{ item.lot_no }}</td>
                                <td>{{ item.lot_model }}</td>
                                <td>{{ item.mc_line }}</td>
                                <td>{{ item.mc_area }}</td>
                                <td>{{ item.lot_size }}</td>
                                <td>{{ formatNumber(item.lot_qty) }}</td>
                                <td>{{ item.mc_no }}</td>
                                <td>{{ item.lot_worktype }}</td>
                                <td>{{ item.lipas }}</td>
                                <td>{{ item.est_endtime_formatted }}</td>
                                <td :class="getElapsedTimeClass(item.elapsed_minutes)">{{ item.elapsed_time }}</td>
                                <td>
                                    <div class="mems-action-group">
                                        <button class="mems-action-btn mems-action-submit" title="Submit Lot" @click="handleSubmitLot(item)">
                                            <span class="action-icon">✅</span>
                                            <span class="action-text">Submit</span>
                                        </button>
                                        <button 
                                            v-if="canDeleteEndtime"
                                            class="mems-action-btn mems-action-delete" 
                                            title="Delete Lot" 
                                            @click="handleDeleteLot(item)"
                                        >
                                            <span class="action-icon">🗑️</span>
                                            <span class="action-text">Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Modals -->
        <MemsEquipmentDetailsModal
            :open="showEquipmentDetailsModal"
            :equipment="selectedEquipment"
            @update:open="showEquipmentDetailsModal = $event"
        />
        
        <MemsAssignLotModal
            :open="showAssignLotModal"
            :equipment-no="selectedEquipmentForAssignment?.equipmentNo ?? null"
            :previous-model="selectedEquipmentForAssignment?.previousModel ?? null"
            :previous-worktype="selectedEquipmentForAssignment?.previousWorktype ?? null"
            @update:open="showAssignLotModal = $event"
            @assign="handleAssignLotConfirm"
        />
        
        <MemsRemarksModal
            :open="showRemarksModal"
            :equipment-no="selectedEquipmentForRemarks?.equipmentNo ?? null"
            :current-remarks="selectedEquipmentForRemarks?.currentRemarks ?? null"
            @update:open="showRemarksModal = $event"
            @save="handleRemarksSave"
        />
        
        <EndtimeSubmitModal
            :open="showSubmitLotModal"
            :initial-lot-no="selectedLotForSubmit?.lot_no ?? null"
            @update:open="showSubmitLotModal = $event"
        />
    </AppLayout>
</template>
