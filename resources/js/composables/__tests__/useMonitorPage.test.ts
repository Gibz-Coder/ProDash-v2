/**
 * Unit tests for useMonitorPage — summaryStats computation
 * Validates: Requirements 1.2, 2.2
 */
import { mount } from '@vue/test-utils';
import axios from 'axios';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';
import {
    type MonitorMode,
    type MonitorRecord,
    useMonitorPage,
} from '../useMonitorPage';

vi.mock('axios');
const mockedAxios = vi.mocked(axios);

function makeRecord(overrides: Partial<MonitorRecord> = {}): MonitorRecord {
    return {
        id: 1,
        lot_id: 'LOT-001',
        model: null,
        lot_qty: null,
        lipas_yn: null,
        qc_result: null,
        qc_defect: null,
        defect_class: null,
        qc_ana_start: null,
        qc_ana_result: null,
        qc_ana_tat: null,
        vi_techl_start: null,
        vi_techl_result: null,
        vi_techl_tat: null,
        work_type: null,
        final_decision: null,
        remarks: null,
        updated_by: null,
        created_at: null,
        updated_at: null,
        ...overrides,
    };
}

/**
 * Mount a minimal component so Vue lifecycle hooks work, then seed records
 * by resolving the axios mock and calling fetchRecords().
 */
async function mountComposable(mode: MonitorMode, rows: MonitorRecord[]) {
    let composable: ReturnType<typeof useMonitorPage>;

    const TestComponent = defineComponent({
        setup() {
            composable = useMonitorPage({ apiUrl: '/api/test', mode });
            return {};
        },
        template: '<div />',
    });

    mockedAxios.get = vi.fn().mockResolvedValue({
        data: { success: true, data: rows },
    });

    const wrapper = mount(TestComponent);

    // Trigger fetch so records are populated
    await composable!.fetchRecords();

    return { wrapper, composable: composable! };
}

describe('useMonitorPage — summaryStats (qc mode)', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it('counts pending rows where qc_ana_start and qc_ana_result are both null', async () => {
        const rows = [
            makeRecord({ id: 1, qc_ana_start: null, qc_ana_result: null }),
            makeRecord({ id: 2, qc_ana_start: null, qc_ana_result: null }),
            makeRecord({
                id: 3,
                qc_ana_start: '2024-01-01',
                qc_ana_result: null,
            }),
        ];

        const { composable } = await mountComposable('qc', rows);

        expect(composable.summaryStats.value.pending).toBe(2);
    });

    it('counts in-progress rows where qc_ana_start is set and qc_ana_result is null', async () => {
        const rows = [
            makeRecord({
                id: 1,
                qc_ana_start: '2024-01-01',
                qc_ana_result: null,
            }),
            makeRecord({
                id: 2,
                qc_ana_start: '2024-01-02',
                qc_ana_result: null,
            }),
            makeRecord({ id: 3, qc_ana_start: null, qc_ana_result: null }),
        ];

        const { composable } = await mountComposable('qc', rows);

        expect(composable.summaryStats.value.inProgress).toBe(2);
    });

    it('counts completed rows where qc_ana_result is set', async () => {
        const rows = [
            makeRecord({
                id: 1,
                qc_ana_start: '2024-01-01',
                qc_ana_result: 'PASS',
            }),
            makeRecord({
                id: 2,
                qc_ana_start: '2024-01-02',
                qc_ana_result: 'FAIL',
            }),
            makeRecord({ id: 3, qc_ana_start: null, qc_ana_result: null }),
        ];

        const { composable } = await mountComposable('qc', rows);

        expect(composable.summaryStats.value.completed).toBe(2);
    });

    it('returns all zeros when records is empty', async () => {
        const { composable } = await mountComposable('qc', []);

        expect(composable.summaryStats.value).toEqual({
            pending: 0,
            inProgress: 0,
            completed: 0,
        });
    });

    it('correctly categorises a mixed set of records', async () => {
        const rows = [
            makeRecord({ id: 1, qc_ana_start: null, qc_ana_result: null }), // pending
            makeRecord({
                id: 2,
                qc_ana_start: '2024-01-01',
                qc_ana_result: null,
            }), // in-progress
            makeRecord({
                id: 3,
                qc_ana_start: '2024-01-01',
                qc_ana_result: 'OK',
            }), // completed
        ];

        const { composable } = await mountComposable('qc', rows);

        expect(composable.summaryStats.value).toEqual({
            pending: 1,
            inProgress: 1,
            completed: 1,
        });
    });

    it('treats a row with only qc_ana_result set (no start) as completed', async () => {
        // result present → completed regardless of start
        const rows = [
            makeRecord({ id: 1, qc_ana_start: null, qc_ana_result: 'PASS' }),
        ];

        const { composable } = await mountComposable('qc', rows);

        expect(composable.summaryStats.value.completed).toBe(1);
        expect(composable.summaryStats.value.pending).toBe(0);
        expect(composable.summaryStats.value.inProgress).toBe(0);
    });
});

describe('useMonitorPage — summaryStats (vi mode)', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it('counts pending rows where vi_techl_start and vi_techl_result are both null', async () => {
        const rows = [
            makeRecord({ id: 1, vi_techl_start: null, vi_techl_result: null }),
            makeRecord({
                id: 2,
                vi_techl_start: '2024-01-01',
                vi_techl_result: null,
            }),
        ];

        const { composable } = await mountComposable('vi', rows);

        expect(composable.summaryStats.value.pending).toBe(1);
    });

    it('counts in-progress rows where vi_techl_start is set and vi_techl_result is null', async () => {
        const rows = [
            makeRecord({
                id: 1,
                vi_techl_start: '2024-01-01',
                vi_techl_result: null,
            }),
            makeRecord({ id: 2, vi_techl_start: null, vi_techl_result: null }),
        ];

        const { composable } = await mountComposable('vi', rows);

        expect(composable.summaryStats.value.inProgress).toBe(1);
    });

    it('counts completed rows where vi_techl_result is set', async () => {
        const rows = [
            makeRecord({
                id: 1,
                vi_techl_start: '2024-01-01',
                vi_techl_result: 'PASS',
            }),
            makeRecord({ id: 2, vi_techl_start: null, vi_techl_result: null }),
        ];

        const { composable } = await mountComposable('vi', rows);

        expect(composable.summaryStats.value.completed).toBe(1);
    });

    it('correctly categorises a mixed set of records in vi mode', async () => {
        const rows = [
            makeRecord({ id: 1, vi_techl_start: null, vi_techl_result: null }), // pending
            makeRecord({
                id: 2,
                vi_techl_start: '2024-01-01',
                vi_techl_result: null,
            }), // in-progress
            makeRecord({
                id: 3,
                vi_techl_start: '2024-01-01',
                vi_techl_result: 'OK',
            }), // completed
        ];

        const { composable } = await mountComposable('vi', rows);

        expect(composable.summaryStats.value).toEqual({
            pending: 1,
            inProgress: 1,
            completed: 1,
        });
    });
});
