import { computed, onUnmounted, ref, type Ref } from 'vue';

/**
 * Returns a reactive elapsed-minutes counter that ticks every 60 seconds.
 * Returns 0 when the start timestamp is null (pending lots).
 */
export function useElapsedTimer(startTimestamp: Ref<string | null>) {
    const now = ref(Date.now());

    const timer = setInterval(() => {
        now.value = Date.now();
    }, 60_000);

    onUnmounted(() => clearInterval(timer));

    const elapsedMinutes = computed(() => {
        if (!startTimestamp.value) return 0;
        const diff = now.value - new Date(startTimestamp.value).getTime();
        return Math.max(0, Math.floor(diff / 60_000));
    });

    return { elapsedMinutes };
}
