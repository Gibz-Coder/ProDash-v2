import axios from 'axios';
import { type Ref, computed, ref } from 'vue';

export interface MonitorRecord {
    id: number;
    lot_id: string;
    model: string | null;
    lot_qty: number | null;
    lipas_yn: string | null;
    qc_result: string | null;
    qc_defect: string | null;
    defect_class: string | null;
    // endline_delay fields
    qc_ana_start: string | null;
    qc_ana_prog: string | null;
    qc_ana_result: string | null;
    qc_ana_completed_at: string | null;
    vi_techl_start: string | null;
    vi_techl_prog: string | null;
    vi_techl_result: string | null;
    vi_techl_completed_at: string | null;
    // qc_analysis table fields
    defect_code: string | null;
    analysis_start_at: string | null;
    analysis_result: string | null;
    analysis_completed_at: string | null;
    defect_name: string | null;
    work_type: string | null;
    final_decision: string | null;
    remarks: string | null;
    updated_by: string | null;
    created_by: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export type MonitorMode = 'qc' | 'vi' | 'qc_analysis';
export type UnitType = 'pcs' | 'Kpcs' | 'Mpcs';

interface UseMonitorPageOptions {
    apiUrl: string;
    mode: MonitorMode;
    params?: () => Record<string, string | undefined>;
    unit?: Ref<UnitType>;
}

function sumQty(recs: MonitorRecord[]): number {
    return recs.reduce((acc, r) => acc + (r.lot_qty ?? 0), 0);
}

export function formatDuration(minutes: number): string {
    if (minutes < 60) return `${minutes} min`;
    const days = Math.floor(minutes / 1440);
    const hours = Math.floor((minutes % 1440) / 60);
    const mins = minutes % 60;
    if (days > 0) return `${days}d ${hours}h ${mins}m`;
    return `${hours}h ${mins}m`;
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
    const prevDayMeta = ref<{ count: number; qty: number }>({
        count: 0,
        qty: 0,
    });

    async function fetchRecords() {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await axios.get<{
                success: boolean;
                data: MonitorRecord[];
                meta: { prev_day_count: number; prev_day_qty: number } | null;
            }>(apiUrl, {
                params: params?.(),
            });
            records.value = data.data ?? [];
            prevDayMeta.value = {
                count: data.meta?.prev_day_count ?? 0,
                qty: data.meta?.prev_day_qty ?? 0,
            };
        } catch (e: unknown) {
            error.value =
                e instanceof Error ? e.message : 'Failed to load records';
        } finally {
            loading.value = false;
        }
    }

    const startKey =
        mode === 'vi'
            ? 'vi_techl_start'
            : mode === 'qc_analysis'
              ? 'analysis_start_at'
              : 'qc_ana_start';
    const progKey =
        mode === 'vi'
            ? 'vi_techl_prog'
            : mode === 'qc_analysis'
              ? null
              : 'qc_ana_prog';
    const resultKey =
        mode === 'vi'
            ? 'vi_techl_result'
            : mode === 'qc_analysis'
              ? 'analysis_result'
              : 'qc_ana_result';
    const completedAtKey =
        mode === 'vi'
            ? 'vi_techl_completed_at'
            : mode === 'qc_analysis'
              ? 'analysis_completed_at'
              : 'qc_ana_completed_at';

    const summaryStats = computed(() => {
        // COMPLETED: has a real final result (not In Progress)
        const completedRecs = records.value.filter(
            (r) =>
                !!r[resultKey as keyof MonitorRecord] &&
                r[resultKey as keyof MonitorRecord] !== 'In Progress' &&
                r[resultKey as keyof MonitorRecord] !== 'For Decision',
        );

        // IN PROGRESS: analysis_result === 'In Progress' OR progKey-based for other modes
        const inProgressRecs =
            mode === 'qc_analysis'
                ? records.value.filter(
                      (r) => r.analysis_result === 'In Progress',
                  )
                : progKey
                  ? records.value.filter(
                        (r) =>
                            !!r[progKey as keyof MonitorRecord] &&
                            !r[resultKey as keyof MonitorRecord],
                    )
                  : [];

        // PENDING: no result, For Decision, and not in progress
        const pendingRecs =
            mode === 'qc_analysis'
                ? records.value.filter(
                      (r) =>
                          !r.analysis_result ||
                          r.analysis_result === 'For Decision',
                  )
                : progKey
                  ? records.value.filter(
                        (r) =>
                            !r[progKey as keyof MonitorRecord] &&
                            !r[resultKey as keyof MonitorRecord],
                    )
                  : records.value.filter(
                        (r) => !r[resultKey as keyof MonitorRecord],
                    );

        const u = unit?.value ?? 'pcs';

        // AVERAGE TAT: all records with a start time
        // - completed: completed_at minus start
        // - pending/in-progress: now minus start
        const avgTat = (() => {
            const now = Date.now();
            const minutes = records.value
                .filter((r) => !!r[startKey as keyof MonitorRecord])
                .map((r) => {
                    const start = new Date(
                        r[startKey as keyof MonitorRecord] as string,
                    ).getTime();
                    const end = r[completedAtKey as keyof MonitorRecord]
                        ? new Date(
                              r[
                                  completedAtKey as keyof MonitorRecord
                              ] as string,
                          ).getTime()
                        : now;
                    return Math.round((end - start) / 60_000);
                })
                .filter((m) => m > 0);
            if (minutes.length === 0) return '—';
            return formatDuration(
                Math.round(minutes.reduce((a, b) => a + b, 0) / minutes.length),
            );
        })();

        return {
            total: records.value.length,
            pending: pendingRecs.length,
            inProgress: inProgressRecs.length,
            completed: completedRecs.length,
            prevDayPending: prevDayMeta.value.count,
            totalQty: formatUnit(sumQty(records.value), u),
            pendingQty: formatUnit(sumQty(pendingRecs), u),
            inProgressQty: formatUnit(sumQty(inProgressRecs), u),
            completedQty: formatUnit(sumQty(completedRecs), u),
            prevDayQty: formatUnit(prevDayMeta.value.qty, u),
            avgTat,
        };
    });

    return { records, loading, error, fetchRecords, summaryStats };
}
