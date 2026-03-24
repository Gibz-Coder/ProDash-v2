<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        @click="$emit('update:open', false)"
    >
        <div
            class="w-full max-w-md animate-in rounded-2xl bg-card shadow-2xl ring-1 ring-border/50 duration-300 fade-in-0 zoom-in-95"
            @click.stop
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between rounded-t-2xl border-b border-border/50 bg-gradient-to-r from-primary/5 to-primary/10 px-6 py-5"
            >
                <div class="flex items-center gap-3">
                    <div class="rounded-full bg-primary/10 p-2">
                        <ClipboardCheck class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-foreground">
                            Submit Result
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            {{
                                mode === 'qc' ? 'QC Analysis' : 'VI Technical'
                            }}
                            decision
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    @click="$emit('update:open', false)"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Lot context (read-only) -->
            <div class="px-6 pt-5">
                <div
                    class="grid grid-cols-3 gap-3 rounded-lg border border-border/50 bg-muted/30 p-3"
                >
                    <div>
                        <p
                            class="text-[10px] font-medium text-muted-foreground uppercase"
                        >
                            Lot No
                        </p>
                        <p
                            class="mt-0.5 font-mono text-sm font-semibold text-primary"
                        >
                            {{ lot?.lot_id ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-[10px] font-medium text-muted-foreground uppercase"
                        >
                            Model
                        </p>
                        <p
                            class="mt-0.5 truncate text-sm text-foreground"
                            :title="lot?.model ?? ''"
                        >
                            {{ lot?.model ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-[10px] font-medium text-muted-foreground uppercase"
                        >
                            Qty
                        </p>
                        <p class="mt-0.5 text-sm font-medium text-foreground">
                            {{ lot?.lot_qty?.toLocaleString() ?? '—' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="space-y-4 px-6 py-5">
                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold text-foreground"
                    >
                        Decision <span class="text-destructive">*</span>
                    </label>
                    <select
                        v-model="decision"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                        :class="{ 'border-destructive': showError }"
                    >
                        <option value="">Select decision...</option>
                        <option value="Proceed">Proceed</option>
                        <option value="Rework">Rework</option>
                        <option value="Low Yield">Low Yield</option>
                    </select>
                    <p v-if="showError" class="mt-1 text-xs text-destructive">
                        Decision is required.
                    </p>
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold text-foreground"
                        >Remarks</label
                    >
                    <textarea
                        v-model="remarks"
                        rows="3"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                        placeholder="Optional remarks..."
                    />
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex justify-end gap-3 rounded-b-2xl border-t border-border/50 bg-muted/10 px-6 py-4"
            >
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg border border-border bg-background px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm transition-all hover:bg-muted"
                    @click="$emit('update:open', false)"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg bg-gradient-to-r from-primary to-primary/90 px-6 py-2.5 text-sm font-semibold text-primary-foreground shadow-lg transition-all hover:shadow-xl hover:shadow-primary/25 disabled:pointer-events-none disabled:opacity-50"
                    :disabled="submitting"
                    @click="submit"
                >
                    <span
                        v-if="submitting"
                        class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                    />
                    Submit
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { MonitorMode, MonitorRecord } from '@/composables/useMonitorPage';
import axios from 'axios';
import { ClipboardCheck, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    lot: MonitorRecord | null;
    mode: MonitorMode;
}>();

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
    (e: 'submitted'): void;
}>();

const decision = ref('');
const remarks = ref('');
const submitting = ref(false);
const showError = ref(false);

// Reset form when modal opens
watch(
    () => props.open,
    (val) => {
        if (val) {
            decision.value = '';
            remarks.value = '';
            showError.value = false;
        }
    },
);

async function submit() {
    if (!decision.value) {
        showError.value = true;
        return;
    }
    if (!props.lot) return;

    showError.value = false;
    submitting.value = true;

    const resultField =
        props.mode === 'qc' ? 'qc_ana_result' : 'vi_techl_result';

    try {
        await axios.put(`/api/endline-delay/${props.lot.id}`, {
            ...props.lot,
            [resultField]: decision.value,
            final_decision: decision.value,
            remarks: remarks.value || props.lot.remarks,
        });
        emit('submitted');
        emit('update:open', false);
    } catch {
        // keep modal open on error so user can retry
    } finally {
        submitting.value = false;
    }
}
</script>
