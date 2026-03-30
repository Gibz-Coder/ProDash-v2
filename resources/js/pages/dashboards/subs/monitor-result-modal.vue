<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        @click="$emit('update:open', false)"
    >
        <div
            class="w-full max-w-lg animate-in rounded-2xl bg-card shadow-2xl ring-1 ring-border/50 duration-300 fade-in-0 zoom-in-95"
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
                            {{ mode === 'qc' ? 'QC Analysis' : 'VI Technical' }}
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

            <!-- Lot ID lookup (empty modal mode) -->
            <div v-if="!resolvedLot" class="px-6 pt-5 pb-2">
                <label class="mb-2 block text-sm font-semibold text-foreground">
                    Lot No <span class="text-destructive">*</span>
                </label>
                <div class="flex gap-2">
                    <input
                        ref="lotInputRef"
                        v-model="lotIdInput"
                        type="text"
                        placeholder="Enter lot ID..."
                        class="h-10 flex-1 rounded-lg border border-input bg-background px-3 text-sm text-foreground shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                        @keydown.enter="lookupLot"
                    />
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 disabled:opacity-50"
                        :disabled="lookingUp || !lotIdInput.trim()"
                        @click="lookupLot"
                    >
                        <span
                            v-if="lookingUp"
                            class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                        />
                        <Search v-else class="h-3.5 w-3.5" />
                        Lookup
                    </button>
                </div>
                <p v-if="lookupError" class="mt-1.5 text-xs text-destructive">
                    {{ lookupError }}
                </p>
            </div>

            <!-- Lot context (read-only) -->
            <div v-if="resolvedLot" class="px-6 pt-5">
                <div
                    class="grid grid-cols-4 gap-3 rounded-xl border border-border/50 bg-muted/20 px-5 py-4"
                >
                    <div>
                        <p
                            class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            Lot No
                        </p>
                        <p
                            class="mt-1 font-mono text-sm font-bold text-primary"
                        >
                            {{ resolvedLot.lot_id }}
                        </p>
                    </div>
                    <div class="col-span-2">
                        <p
                            class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            Model
                        </p>
                        <p
                            class="mt-1 truncate text-sm font-medium text-foreground"
                            :title="resolvedLot.model ?? ''"
                        >
                            {{ resolvedLot.model ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            Qty
                        </p>
                        <p class="mt-1 text-sm font-semibold text-foreground">
                            {{ resolvedLot.lot_qty?.toLocaleString() ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            QC Result
                        </p>
                        <p class="mt-1 text-sm font-medium text-foreground">
                            {{ resolvedLot.qc_result ?? '—' }}
                        </p>
                    </div>
                    <div class="col-span-2">
                        <p
                            class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            Defect Code
                        </p>
                        <p class="mt-1 text-sm font-medium text-foreground">
                            {{ resolvedLot.qc_defect ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            LIPAS
                        </p>
                        <p class="mt-1 text-sm font-medium text-foreground">
                            {{ resolvedLot.lipas_yn ?? '—' }}
                        </p>
                    </div>
                </div>
                <!-- Allow re-lookup in empty modal mode -->
                <button
                    v-if="!lot"
                    type="button"
                    class="mt-2 text-xs text-muted-foreground underline hover:text-foreground"
                    @click="
                        resolvedLot = null;
                        lotIdInput = '';
                    "
                >
                    Change lot
                </button>
            </div>

            <!-- Form -->
            <div v-if="resolvedLot" class="space-y-4 px-6 py-5">
                <div>
                    <label
                        class="mb-2 block text-sm font-semibold text-foreground"
                    >
                        {{
                            mode === 'qc'
                                ? 'QC Analysis Result'
                                : 'Visual Technical Result'
                        }}
                        <span class="text-destructive">*</span>
                    </label>
                    <select
                        v-model="decision"
                        class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                        :class="{
                            'border-destructive ring-1 ring-destructive/30':
                                showError,
                        }"
                    >
                        <option value="">Select result...</option>
                        <template v-if="mode === 'qc'">
                            <option value="Proceed">Proceed</option>
                            <option value="Rework">Rework</option>
                            <option value="MOLD">MOLD</option>
                            <option value="RELI">RELI</option>
                            <option value="Dipping">Dipping</option>
                            <option value="Reflow">Reflow</option>
                            <option value="Measure">Measure</option>
                            <option value="CPDF">CPDF</option>
                            <option value="DRB Approval">DRB Approval</option>
                            <option value="For Decision (other process)">
                                For Decision (other process)
                            </option>
                        </template>
                        <template v-else>
                            <option value="Proceed">Proceed</option>
                            <option value="Rework">Rework</option>
                            <option value="DRB Approval">DRB Approval</option>
                            <option value="For Decision (other process)">
                                For Decision (other process)
                            </option>
                        </template>
                    </select>
                    <p v-if="showError" class="mt-1.5 text-xs text-destructive">
                        Result is required.
                    </p>
                </div>

                <div>
                    <label
                        class="mb-2 block text-sm font-semibold text-foreground"
                    >
                        Remarks
                        <span
                            class="ml-1 text-xs font-normal text-muted-foreground"
                            >(optional)</span
                        >
                    </label>
                    <textarea
                        v-model="remarks"
                        rows="3"
                        class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm text-foreground shadow-sm placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                        placeholder="Add any remarks or notes..."
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
                    v-if="resolvedLot"
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
import { ClipboardCheck, Search, X } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';

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

// Lot lookup state (for empty modal mode)
const lotIdInput = ref('');
const lotInputRef = ref<HTMLInputElement | null>(null);
const lookingUp = ref(false);
const lookupError = ref('');
const resolvedLot = ref<MonitorRecord | null>(null);

watch(
    () => props.open,
    (val) => {
        if (val) {
            decision.value = '';
            remarks.value = '';
            showError.value = false;
            lookupError.value = '';
            lotIdInput.value = '';
            resolvedLot.value = props.lot ?? null;
            // Auto-focus lot input when opening in empty mode
            if (!props.lot) {
                nextTick(() => lotInputRef.value?.focus());
            }
        }
    },
);

// Also sync if lot prop changes while open
watch(
    () => props.lot,
    (val) => {
        if (val) resolvedLot.value = val;
    },
);

async function lookupLot() {
    const id = lotIdInput.value.trim();
    if (!id) return;
    lookingUp.value = true;
    lookupError.value = '';
    try {
        const { data } = await axios.get<{
            success: boolean;
            data: MonitorRecord[];
        }>(
            props.mode === 'qc'
                ? '/api/endline-delay/qc-monitor'
                : '/api/endline-delay/vi-monitor',
            { params: { search: id } },
        );

        const matches = (data.data ?? []).filter(
            (r) => r.lot_id?.toLowerCase() === id.toLowerCase(),
        );

        if (matches.length === 0) {
            lookupError.value = `No entry found for lot "${id}".`;
            return;
        }

        const resultKey =
            props.mode === 'qc' ? 'qc_ana_result' : 'vi_techl_result';
        const pending = matches.filter((r) => !r[resultKey]);
        const pool = pending.length > 0 ? pending : matches;

        // Pick the most recent entry by created_at
        resolvedLot.value = pool.sort(
            (a, b) =>
                new Date(b.created_at ?? 0).getTime() -
                new Date(a.created_at ?? 0).getTime(),
        )[0];
    } catch {
        lookupError.value = 'Lookup failed. Please try again.';
    } finally {
        lookingUp.value = false;
    }
}

async function submit() {
    if (!decision.value) {
        showError.value = true;
        return;
    }
    if (!resolvedLot.value) return;

    showError.value = false;
    submitting.value = true;

    const submitUrl =
        props.mode === 'qc'
            ? `/api/endline-delay/${resolvedLot.value.id}/submit-qc`
            : `/api/endline-delay/${resolvedLot.value.id}/submit-vi`;

    try {
        await axios.post(submitUrl, {
            result: decision.value,
            remarks: remarks.value || resolvedLot.value.remarks,
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
