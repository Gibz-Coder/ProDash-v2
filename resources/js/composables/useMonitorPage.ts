import axios from 'axios';
import { type Ref, computed, onUnmounted, ref } from 'vue';

export interface MonitorRecord {
    id: number;
    lot_id: string;
    model: string | null;
    lot_qty: number | null;
    lipas_yn: string | null;
    qc_result: string | null;
    qc_defect: string | null;
    defect_class: string | null;
    qc_ana_start: string | null;
    qc_ana_result: string | null;
    qc_ana_tat: number | null;
    vi_techl_start: string | null;
    vi_techl_result: string | null;
    vi_techl_tat: number | null;
    defect_name: string | null;
    work_type: string | null;
    final_decision: string | null;
    remarks: string | null;
    updated_by: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export type MonitorMode = 'qc' | 'vi';
export type UnitType = 'pcs' | 'Kpcs' | 'Mpcs';

interface UseMonitorPageOptions {
    apiUrl: string;
    mode: MonitorMode;
    params?: () => Record<string, string | undefined>;
    unit?: Ref<UnitType>;
}

const REFRESH_INTERVAL_MS = 30_000;

function sumQty(recs: MonitorRecord[]): number {
    return recs.reduce((acc, r) => acc + (r.lot_qty ?? 0), 0);
}

function formatUnit(qty: number, unit: UnitType): string {
    if (unit === 'Kpcs')
        return (qty / 1000).toLocaleString(undefined, {
            maximumFractionDigits: 1,
        });
    if (unit === 'Mpcs')
        return (qty / 1_000_000).toLocaleString(undefined, {
            maximumFractionDigits: 2,
        });
    return qty.toLocaleString();
}

export function useMonitorPage({
    apiUrl,
    mode,
    params,
    unit,
}: UseMonitorPageOptions) {
    const records = ref<MonitorRecord[]>([]);
    const loading = ref(false);

    const error = ref<string | null>(null);

    async function fetchRecords() {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await axios.get<{
                success: boolean;
                data: MonitorRecord[];
            }>(apiUrl, {
                params: params?.(),
            });
            records.value = data.data ?? [];
        } catch (e: unknown) {
            error.value =
                e instanceof Error ? e.message : 'Failed to load records';
        } finally {
            loading.value = false;
        }
    }

    const timer = setInterval(fetchRecords, REFRESH_INTERVAL_MS);
    onUnmounted(() => clearInterval(timer));

    const startKey = mode === 'qc' ? 'qc_ana_start' : 'vi_techl_start';
    const resultKey = mode === 'qc' ? 'qc_ana_result' : 'vi_techl_result';

    const summaryStats = computed(() => {
        const pendingRecs = records.value.filter(
            (r) => !r[startKey] && !r[resultKey],
        );
        const inProgressRecs = records.value.filter(
            (r) => r[startKey] && !r[resultKey],
        );
        const completedRecs = records.value.filter((r) => !!r[resultKey]);

        const todayStr = new Date().toLocaleDateString('en-CA', {
            timeZone: 'Asia/Manila',
        });
        const prevDayRecs = records.value.filter((r) => {
            if (r[startKey] || r[resultKey]) return false;
            if (!r.created_at) return false;
            const recDate = new Date(r.created_at).toLocaleDateString('en-CA', {
                timeZone: 'Asia/Manila',
            });
            return recDate < todayStr;
        });

        const u = unit?.value ?? 'pcs';

        return {
            // counts
            total: records.value.length,
            pending: pendingRecs.length,
            inProgress: inProgressRecs.length,
            completed: completedRecs.length,
            prevDayPending: prevDayRecs.length,
            // qty formatted by unit
            totalQty: formatUnit(sumQty(records.value), u),
            pendingQty: formatUnit(sumQty(pendingRecs), u),
            inProgressQty: formatUnit(sumQty(inProgressRecs), u),
            completedQty: formatUnit(sumQty(completedRecs), u),
            prevDayQty: formatUnit(sumQty(prevDayRecs), u),
        };
    });

    return { records, loading, error, fetchRecords, summaryStats };
}
