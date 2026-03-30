<template>
    <div
        class="flex items-center gap-1 rounded-lg border border-border bg-background px-2 py-1.5 shadow-sm"
    >
        <!-- Countdown ring + toggle -->
        <button
            :title="enabled ? 'Pause auto-refresh' : 'Enable auto-refresh'"
            class="relative flex h-6 w-6 items-center justify-center rounded-full transition-colors"
            :class="
                enabled
                    ? 'text-emerald-600 hover:text-emerald-700'
                    : 'text-muted-foreground hover:text-foreground'
            "
            @click="$emit('toggle')"
        >
            <!-- SVG countdown ring -->
            <svg
                class="absolute inset-0 h-6 w-6 -rotate-90"
                viewBox="0 0 24 24"
            >
                <circle
                    cx="12"
                    cy="12"
                    r="9"
                    fill="none"
                    stroke="currentColor"
                    stroke-opacity="0.15"
                    stroke-width="2"
                />
                <circle
                    v-if="enabled"
                    cx="12"
                    cy="12"
                    r="9"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    :stroke-dasharray="circumference"
                    :stroke-dashoffset="dashOffset"
                    class="transition-[stroke-dashoffset] duration-1000 ease-linear"
                />
            </svg>
            <component
                :is="enabled ? RefreshCw : PauseCircle"
                class="relative h-3 w-3"
                :class="enabled && spinning ? 'animate-spin' : ''"
            />
        </button>

        <!-- Interval selector (only when enabled) -->
        <select
            v-if="enabled"
            :value="interval"
            class="cursor-pointer border-0 bg-transparent text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background"
            @change="
                $emit(
                    'set-interval',
                    Number(
                        ($event.target as HTMLSelectElement).value,
                    ) as RefreshInterval,
                )
            "
        >
            <option
                v-for="opt in REFRESH_INTERVALS"
                :key="opt.value"
                :value="opt.value"
            >
                {{ opt.label }}
            </option>
        </select>
        <span v-else class="text-xs font-semibold text-muted-foreground"
            >Off</span
        >
    </div>
</template>

<script setup lang="ts">
import {
    REFRESH_INTERVALS,
    type RefreshInterval,
} from '@/composables/useAutoRefresh';
import { PauseCircle, RefreshCw } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    enabled: boolean;
    interval: RefreshInterval;
    spinning?: boolean;
}>();

defineEmits<{
    toggle: [];
    'set-interval': [value: RefreshInterval];
}>();

const circumference = 2 * Math.PI * 9; // r=9

// Countdown progress: ticks every second from interval down to 0
const elapsed = ref(0);

let ticker: ReturnType<typeof setInterval> | null = null;

function resetTicker() {
    if (ticker) clearInterval(ticker);
    elapsed.value = 0;
    if (!props.enabled) return;
    ticker = setInterval(() => {
        elapsed.value = Math.min(elapsed.value + 1000, props.interval);
    }, 1000);
}

watch(() => [props.enabled, props.interval], resetTicker, { immediate: true });

// When a refresh fires externally, reset the countdown
watch(
    () => props.spinning,
    (v) => {
        if (v) resetTicker();
    },
);

const dashOffset = computed(() => {
    const progress = elapsed.value / props.interval;
    return circumference * (1 - progress);
});
</script>
