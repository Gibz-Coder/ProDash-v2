import { ref, computed } from 'vue';

export interface LineUtilization {
    line: string;
    count: number;
    run: { value: number; percent: number };
    wait: { value: number; percent: number };
    idle: { value: number; percent: number };
}

export interface MachineTypeStatus {
    item: string;
    g20: { value: number; percent: number };
    g3: { value: number; percent: number };
    g1: { value: number; percent: number };
    twa: { value: number; percent: number };
    wintec: { value: number; percent: number };
    total: { value: number; percent: number };
}

export interface TrendDataPoint {
    label: string;
    time: string;
    utilization: number;
    run: number;
    wait: number;
    total: number;
}

export type TimeRange = '1hour' | '2hour' | '4hour' | '1day';
export type SnapshotInterval = '5min' | '10min' | '15min' | '30min' | '1hour';
export type ClearPeriod = 'previous_day' | 'previous_week' | 'previous_month' | 'all';

export interface SnapshotStats {
    total: number;
    today: number;
    yesterday: number;
    this_week: number;
    this_month: number;
    oldest: string | null;
    latest: string | null;
}

export function useMemsData() {
    const lineUtilizationData = ref<LineUtilization[]>([]);
    const trendData = ref<TrendDataPoint[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    
    // Auto-capture state
    const isAutoCapturing = ref(false);
    const snapshotInterval = ref<SnapshotInterval>('30min');
    const lastCaptureTime = ref<string | null>(null);
    const nextCaptureIn = ref<number>(0);
    const snapshotStats = ref<SnapshotStats | null>(null);
    let captureIntervalId: number | null = null;
    let countdownIntervalId: number | null = null;
    
    // Callback to refresh data after snapshot
    let onSnapshotCaptured: (() => Promise<void>) | null = null;
    
    const setSnapshotCallback = (callback: () => Promise<void>) => {
        onSnapshotCaptured = callback;
    };

    // Interval durations in milliseconds
    const intervalDurations: Record<SnapshotInterval, number> = {
        '5min': 5 * 60 * 1000,
        '10min': 10 * 60 * 1000,
        '15min': 15 * 60 * 1000,
        '30min': 30 * 60 * 1000,
        '1hour': 60 * 60 * 1000,
    };

    // Interval durations in seconds (for countdown)
    const intervalSeconds: Record<SnapshotInterval, number> = {
        '5min': 5 * 60,
        '10min': 10 * 60,
        '15min': 15 * 60,
        '30min': 30 * 60,
        '1hour': 60 * 60,
    };

    const lineUtilizationTotal = computed(() => {
        return lineUtilizationData.value.reduce(
            (acc, line) => ({
                count: acc.count + line.count,
                run: acc.run + line.run.value,
                wait: acc.wait + line.wait.value,
                idle: acc.idle + line.idle.value,
            }),
            { count: 0, run: 0, wait: 0, idle: 0 }
        );
    });

    /**
     * Fetch latest line utilization data
     */
    const fetchLineUtilization = async (
        workType: string = 'ALL',
        date?: string,
        mcStatus?: string,
        mcWorktype?: string
    ) => {
        isLoading.value = true;
        error.value = null;
        
        try {
            const params = new URLSearchParams({
                workType,
                ...(date && { date }),
                ...(mcStatus && mcStatus !== 'ALL' && { mcStatus }),
                ...(mcWorktype && mcWorktype !== 'ALL' && { mcWorktype }),
            });
            
            const response = await fetch(`/api/mems/utilization/latest?${params}`);
            if (!response.ok) throw new Error('Failed to fetch utilization data');
            
            const result = await response.json();
            lineUtilizationData.value = result.data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Fetch trend data for chart
     */
    const fetchTrendData = async (
        timeRange: TimeRange = '1hour',
        line: string = 'ALL',
        workType: string = 'ALL',
        date?: string,
        mcStatus?: string,
        mcWorktype?: string
    ) => {
        isLoading.value = true;
        error.value = null;
        
        try {
            const params = new URLSearchParams({
                timeRange,
                line,
                workType,
                ...(date && { date }),
                ...(mcStatus && mcStatus !== 'ALL' && { mcStatus }),
                ...(mcWorktype && mcWorktype !== 'ALL' && { mcWorktype }),
            });
            
            const response = await fetch(`/api/mems/utilization/trend?${params}`);
            if (!response.ok) throw new Error('Failed to fetch trend data');
            
            const result = await response.json();
            trendData.value = result.data;
            
            // Return the data so it can be used to update the chart
            return result.data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            return [];
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Fetch machine type status data
     */
    const fetchMachineTypeStatus = async (
        workType: string = 'ALL',
        date?: string,
        mcStatus?: string,
        mcWorktype?: string
    ) => {
        isLoading.value = true;
        error.value = null;
        
        try {
            const params = new URLSearchParams({
                workType,
                ...(date && { date }),
                ...(mcStatus && mcStatus !== 'ALL' && { mcStatus }),
                ...(mcWorktype && mcWorktype !== 'ALL' && { mcWorktype }),
            });
            
            const response = await fetch(`/api/mems/utilization/machine-type?${params}`);
            if (!response.ok) throw new Error('Failed to fetch machine type status');
            
            const result = await response.json();
            return result.data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            return {};
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Fetch endtime remaining data
     */
    const fetchEndtimeRemaining = async (
        date?: string
    ) => {
        isLoading.value = true;
        error.value = null;
        
        try {
            const params = new URLSearchParams({
                ...(date && { date }),
            });
            
            const response = await fetch(`/api/mems/endtime/remaining?${params}`);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ API error response:', errorText);
                throw new Error('Failed to fetch endtime remaining');
            }
            
            const result = await response.json();
            return result.data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            console.error('❌ Fetch error:', e);
            return [];
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Fetch endtime per cutoff data
     */
    const fetchEndtimePerCutoff = async (
        date?: string,
        workType?: string,
        status: string = 'all'
    ) => {
        isLoading.value = true;
        error.value = null;
        
        try {
            const params = new URLSearchParams({
                ...(date && { date }),
                ...(workType && workType !== 'ALL' && { workType }),
                status,
            });
            
            const response = await fetch(`/api/mems/endtime/per-cutoff?${params}`);
            if (!response.ok) throw new Error('Failed to fetch endtime per cutoff');
            
            const result = await response.json();
            return result.data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            return [];
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Manually trigger snapshot capture
     */
    const captureSnapshot = async () => {
        try {
            const response = await fetch('/api/mems/snapshot/capture', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            
            if (!response.ok) throw new Error('Failed to capture snapshot');
            
            const result = await response.json();
            lastCaptureTime.value = result.timestamp;
            
            // Trigger callback to refresh data
            if (onSnapshotCaptured) {
                await onSnapshotCaptured();
            }
            
            return result;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            throw e;
        }
    };

    /**
     * Start auto-capture with 5-minute clock-aligned intervals
     * Captures at :00, :05, :10, :15, :20, :25, :30, :35, :40, :45, :50, :55
     */
    const startAutoCapture = async () => {
        if (isAutoCapturing.value) {
            stopAutoCapture();
        }
        
        isAutoCapturing.value = true;
        
        // Calculate time until next 5-minute mark
        const now = new Date();
        const currentMinutes = now.getMinutes();
        const currentSeconds = now.getSeconds();
        const currentMilliseconds = now.getMilliseconds();
        
        // Find next 5-minute mark
        const nextMinuteMark = Math.ceil(currentMinutes / 5) * 5;
        const minutesUntilNext = nextMinuteMark - currentMinutes;
        const secondsUntilNext = (minutesUntilNext * 60) - currentSeconds;
        const millisecondsUntilNext = (secondsUntilNext * 1000) - currentMilliseconds;
        
        // Set countdown
        nextCaptureIn.value = secondsUntilNext;
        
        // Flag to track if we captured immediately
        let capturedImmediately = false;
        
        // Capture immediately if we're very close to a 5-minute mark (within 5 seconds)
        if (secondsUntilNext <= 5) {
            await captureSnapshot();
            capturedImmediately = true;
            // Skip to next interval since we just captured
            nextCaptureIn.value = 300; // Reset to 5 minutes
        }
        
        // Set up countdown timer (updates every second)
        countdownIntervalId = setInterval(() => {
            if (nextCaptureIn.value > 0) {
                nextCaptureIn.value--;
            }
        }, 1000);
        
        // If we captured immediately, skip the initial timeout and go straight to recurring interval
        if (capturedImmediately) {
            // Set up recurring 5-minute captures
            captureIntervalId = setInterval(async () => {
                await captureSnapshot();
                nextCaptureIn.value = 300; // Reset countdown
            }, 300000); // 5 minutes = 300,000 milliseconds
        } else {
            // Schedule first capture at next 5-minute mark
            const initialTimeout = setTimeout(async () => {
                await captureSnapshot();
                nextCaptureIn.value = 300; // Reset to 5 minutes
                
                // Set up recurring 5-minute captures
                captureIntervalId = setInterval(async () => {
                    await captureSnapshot();
                    nextCaptureIn.value = 300; // Reset countdown
                }, 300000); // 5 minutes = 300,000 milliseconds
            }, millisecondsUntilNext);
            
            // Store timeout ID for cleanup
            (window as any).__snapshotInitialTimeout = initialTimeout;
        }
        
        // Save state to localStorage
        localStorage.setItem('memsAutoCapture', 'true');
    };

    /**
     * Stop auto-capture
     */
    const stopAutoCapture = () => {
        isAutoCapturing.value = false;
        
        if (captureIntervalId) {
            clearInterval(captureIntervalId);
            captureIntervalId = null;
        }
        
        if (countdownIntervalId) {
            clearInterval(countdownIntervalId);
            countdownIntervalId = null;
        }
        
        // Clear initial timeout if it exists
        if ((window as any).__snapshotInitialTimeout) {
            clearTimeout((window as any).__snapshotInitialTimeout);
            (window as any).__snapshotInitialTimeout = null;
        }
        
        nextCaptureIn.value = 0;
        
        // Save OFF state to localStorage
        localStorage.setItem('memsAutoCapture', 'false');
    };

    /**
     * Toggle auto-capture on/off
     */
    const toggleAutoCapture = async () => {
        if (isAutoCapturing.value) {
            stopAutoCapture();
        } else {
            await startAutoCapture();
        }
    };

    /**
     * Clear snapshots by period
     */
    const clearSnapshots = async (period: ClearPeriod) => {
        try {
            const response = await fetch('/api/mems/snapshot/clear', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ period }),
            });
            
            if (!response.ok) throw new Error('Failed to clear snapshots');
            
            const result = await response.json();
            
            // Refresh stats after clearing
            await fetchSnapshotStats();
            
            return result;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            throw e;
        }
    };

    /**
     * Fetch snapshot statistics
     */
    const fetchSnapshotStats = async () => {
        try {
            const response = await fetch('/api/mems/snapshot/stats');
            if (!response.ok) throw new Error('Failed to fetch stats');
            
            const result = await response.json();
            snapshotStats.value = result;
            return result;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Unknown error';
            throw e;
        }
    };

    /**
     * Format countdown time
     */
    const formatCountdown = computed(() => {
        const hours = Math.floor(nextCaptureIn.value / 3600);
        const minutes = Math.floor((nextCaptureIn.value % 3600) / 60);
        const seconds = nextCaptureIn.value % 60;
        
        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    });

    /**
     * Restore auto-capture state from localStorage
     * Defaults to ON if no saved state exists
     */
    const restoreAutoCapture = () => {
        const saved = localStorage.getItem('memsAutoCapture');
        
        // Default to ON if no saved state exists, or if explicitly set to 'true'
        // Only stay OFF if explicitly set to 'false'
        if (saved !== 'false') {
            startAutoCapture();
        }
    };

    /**
     * Start polling for real-time updates
     */
    const startPolling = (
        intervalMs: number = 30000,
        workType: string = 'ALL',
        date?: string,
        mcStatus?: string,
        mcWorktype?: string
    ) => {
        fetchLineUtilization(workType, date, mcStatus, mcWorktype);
        return setInterval(() => fetchLineUtilization(workType, date, mcStatus, mcWorktype), intervalMs);
    };

    /**
     * Stop polling
     */
    const stopPolling = (intervalId: number) => {
        clearInterval(intervalId);
    };

    return {
        // Data
        lineUtilizationData,
        lineUtilizationTotal,
        trendData,
        isLoading,
        error,
        
        // Auto-capture state
        isAutoCapturing,
        snapshotInterval,
        lastCaptureTime,
        nextCaptureIn,
        formatCountdown,
        snapshotStats,
        
        // Methods
        fetchLineUtilization,
        fetchTrendData,
        fetchMachineTypeStatus,
        fetchEndtimeRemaining,
        fetchEndtimePerCutoff,
        captureSnapshot,
        startAutoCapture,
        stopAutoCapture,
        toggleAutoCapture,
        clearSnapshots,
        fetchSnapshotStats,
        restoreAutoCapture,
        startPolling,
        stopPolling,
        setSnapshotCallback,
    };
}
