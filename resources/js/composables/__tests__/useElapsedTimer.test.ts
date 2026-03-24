/**
 * Unit tests for useElapsedTimer
 * Validates: Requirements 3.1, 3.2
 */
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, ref } from 'vue';
import { useElapsedTimer } from '../useElapsedTimer';

// Helper: mount a minimal component so Vue lifecycle hooks (onUnmounted) work
function mountTimer(startValue: string | null) {
    const startRef = ref<string | null>(startValue);

    let elapsedMinutes: ReturnType<typeof useElapsedTimer>['elapsedMinutes'];

    const TestComponent = defineComponent({
        setup() {
            const timer = useElapsedTimer(startRef);
            elapsedMinutes = timer.elapsedMinutes;
            return {};
        },
        template: '<div />',
    });

    const wrapper = mount(TestComponent);

    return {
        wrapper,
        startRef,
        getElapsed: () => elapsedMinutes.value,
    };
}

describe('useElapsedTimer', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    describe('Requirement 3.2 — returns 0 when start is null', () => {
        it('returns 0 when startTimestamp is null', () => {
            const { getElapsed } = mountTimer(null);
            expect(getElapsed()).toBe(0);
        });

        it('returns 0 even after time advances when startTimestamp is null', () => {
            const { getElapsed } = mountTimer(null);
            vi.advanceTimersByTime(60_000 * 10);
            expect(getElapsed()).toBe(0);
        });
    });

    describe('Requirement 3.1 — elapsed minutes increase as time advances', () => {
        it('returns 0 elapsed minutes immediately after start', () => {
            const start = new Date(Date.now()).toISOString();
            const { getElapsed } = mountTimer(start);
            expect(getElapsed()).toBe(0);
        });

        it('returns 1 after one 60-second tick', () => {
            const start = new Date(Date.now()).toISOString();
            const { getElapsed } = mountTimer(start);

            vi.advanceTimersByTime(60_000);

            expect(getElapsed()).toBe(1);
        });

        it('returns 5 after five 60-second ticks', () => {
            const start = new Date(Date.now()).toISOString();
            const { getElapsed } = mountTimer(start);

            vi.advanceTimersByTime(60_000 * 5);

            expect(getElapsed()).toBe(5);
        });

        it('floors partial minutes — 90 seconds elapsed = 1 minute', () => {
            // Set start 90 seconds in the past
            const start = new Date(Date.now() - 90_000).toISOString();
            const { getElapsed } = mountTimer(start);

            // Trigger one tick so `now` updates
            vi.advanceTimersByTime(60_000);

            expect(getElapsed()).toBe(2); // 90s + 60s tick = 150s = 2 min floored
        });

        it('accounts for a start timestamp already in the past', () => {
            // Start was 3 minutes ago
            const start = new Date(Date.now() - 3 * 60_000).toISOString();
            const { getElapsed } = mountTimer(start);

            expect(getElapsed()).toBe(3);
        });

        it('never returns a negative value for a future start timestamp', () => {
            // Start is 1 minute in the future (edge case)
            const start = new Date(Date.now() + 60_000).toISOString();
            const { getElapsed } = mountTimer(start);

            expect(getElapsed()).toBeGreaterThanOrEqual(0);
        });
    });

    describe('cleanup', () => {
        it('clears the interval on unmount', () => {
            const clearSpy = vi.spyOn(globalThis, 'clearInterval');
            const start = new Date(Date.now()).toISOString();
            const { wrapper } = mountTimer(start);

            wrapper.unmount();

            expect(clearSpy).toHaveBeenCalled();
        });
    });
});
