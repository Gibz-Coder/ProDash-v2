import { onUnmounted, ref, watch } from 'vue';

export const REFRESH_INTERVALS = [
    { label: '15s', value: 15_000 },
    { label: '30s', value: 30_000 },
    { label: '1m', value: 60_000 },
    { label: '2m', value: 120_000 },
    { label: '5m', value: 300_000 },
] as const;

export type RefreshInterval = (typeof REFRESH_INTERVALS)[number]['value'];

const STORAGE_KEY_ENABLED = 'autoRefresh_enabled';
const STORAGE_KEY_INTERVAL = 'autoRefresh_interval';

export function useAutoRefresh(onRefresh: () => void) {
    const enabled = ref(localStorage.getItem(STORAGE_KEY_ENABLED) !== 'false');
    const interval = ref<RefreshInterval>(
        (Number(
            localStorage.getItem(STORAGE_KEY_INTERVAL),
        ) as RefreshInterval) || 30_000,
    );

    let timer: ReturnType<typeof setInterval> | null = null;

    function start() {
        stop();
        if (enabled.value) {
            timer = setInterval(onRefresh, interval.value);
        }
    }

    function stop() {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }
    }

    function toggle() {
        enabled.value = !enabled.value;
        localStorage.setItem(STORAGE_KEY_ENABLED, String(enabled.value));
        start();
    }

    function setInterval_(ms: RefreshInterval) {
        interval.value = ms;
        localStorage.setItem(STORAGE_KEY_INTERVAL, String(ms));
        start();
    }

    watch([enabled, interval], start, { immediate: true });

    onUnmounted(stop);

    return { enabled, interval, toggle, setInterval: setInterval_ };
}
