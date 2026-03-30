import { computed, ref } from 'vue';

export type SortDir = 'asc' | 'desc' | null;

export function useTableSort<T extends object>(
    defaultKey: keyof T | null = null,
) {
    const sortKey = ref<keyof T | null>(defaultKey);
    const sortDir = ref<SortDir>(null);

    function toggleSort(key: keyof T) {
        if (sortKey.value === key) {
            sortDir.value =
                sortDir.value === 'asc'
                    ? 'desc'
                    : sortDir.value === 'desc'
                      ? null
                      : 'asc';
            if (sortDir.value === null) sortKey.value = null;
        } else {
            sortKey.value = key;
            sortDir.value = 'asc';
        }
    }

    function sortIcon(key: keyof T): string {
        if (sortKey.value !== key) return '↕';
        return sortDir.value === 'asc' ? '↑' : '↓';
    }

    function applySort(rows: T[], elapsedFn: (r: T) => number): T[] {
        const key = sortKey.value;
        const dir = sortDir.value;

        return [...rows].sort((a, b) => {
            // Primary: user-selected column
            if (key && dir) {
                const av = a[key] ?? '';
                const bv = b[key] ?? '';
                const cmp = String(av).localeCompare(String(bv), undefined, {
                    numeric: true,
                    sensitivity: 'base',
                });
                if (cmp !== 0) return dir === 'asc' ? cmp : -cmp;
            }

            // Tiebreaker: completed last, then longest elapsed first
            const aCompleted =
                !!(a as any).qc_ana_result || !!(a as any).vi_techl_result;
            const bCompleted =
                !!(b as any).qc_ana_result || !!(b as any).vi_techl_result;
            if (aCompleted !== bCompleted) return aCompleted ? 1 : -1;
            return elapsedFn(b) - elapsedFn(a);
        });
    }

    return {
        sortKey: computed(() => sortKey.value),
        sortDir: computed(() => sortDir.value),
        toggleSort,
        sortIcon,
        applySort,
    };
}
