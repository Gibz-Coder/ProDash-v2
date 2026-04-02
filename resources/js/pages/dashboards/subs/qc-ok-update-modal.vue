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
                class="flex items-center justify-between rounded-t-2xl border-b border-border/50 bg-gradient-to-r from-emerald-500/5 to-emerald-500/10 px-6 py-5"
            >
                <div class="flex items-center gap-3">
                    <div class="rounded-full bg-emerald-500/10 p-2">
                        <ArrowRightLeft
                            class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
                        />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-foreground">
                            Update Status
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Re-route or update QC OK lot
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

            <!-- Lot lookup (empty modal mode) -->
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

            <!-- Lot context -->
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
                <!-- Status select -->
                <div>
                    <label
                        class="mb-2 block text-sm font-semibold text-foreground"
                    >
                        Status <span class="text-destructive">*</span>
                    </label>
                    <select
                        v-model="selectedStatus"
                        class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                        :class="{
                            'border-destructive ring-1 ring-destructive/30':
                                showError,
                        }"
                    >
                        <option value="">Select status...</option>
                        <optgroup label="Route to QC Analysis">
                            <option value="Waiting MOLD">Waiting MOLD</option>
                            <option value="Waiting Reli">Waiting Reli</option>
                            <option value="Waiting Dipping">
                                Waiting Dipping
                            </option>
                            <option value="Waiting Reflow">
                                Waiting Reflow
                            </option>
                            <option value="Waiting OI Size">
                                Waiting OI Size
                            </option>
                            <option value="For Decision (QC)">
                                For Decision (QC)
                            </option>
                        </optgroup>
                        <optgroup label="Route to VI Technical">
                            <option value="Real NG Scan">Real NG Scan</option>
                            <option value="Experiment">Experiment</option>
                            <option value="For Schedule (Yield)">
                                For Schedule (Yield)
                            </option>
                        </optgroup>
                        <optgroup label="No Routing">
                            <option value="Low Yield (Rework)">
                                Low Yield (Rework)
                            </option>
                            <option value="For Verify (Production)">
                                For Verify (Production)
                            </option>
                            <option value="For Decision (Tech'l)">
                                For Decision (Tech'l)
                            </option>
                            <option value="For Decide (QC)">
                                For Decide (QC)
                            </option>
                            <option value="Completed">Completed</option>
                        </optgroup>
                    </select>
                    <p v-if="showError" class="mt-1.5 text-xs text-destructive">
                        Status is required.
                    </p>

                    <!-- Routing hint -->
                    <div
                        v-if="routingHint"
                        class="mt-2 flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium"
                        :class="routingHintClass"
                    >
                        <component
                            :is="routingHintIcon"
                            class="h-3.5 w-3.5 shrink-0"
                        />
                        {{ routingHint }}
                    </div>
                </div>

                <!-- Remarks -->
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
                    class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-emerald-600 to-emerald-500 px-6 py-2.5 text-sm font-semibold text-white shadow-lg transition-all hover:shadow-xl hover:shadow-emerald-500/25 disabled:pointer-events-none disabled:opacity-50"
                    :disabled="submitting"
                    @click="submit"
                >
                    <span
                        v-if="submitting"
                        class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                    />
                    <Check v-else class="h-4 w-4" />
                    Update
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import {
    ArrowRightLeft,
    Check,
    FlaskConical,
    GitBranch,
    Hourglass,
    Search,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

interface LotRecord {
    id: number;
    lot_id: string;
    model: string | null;
    lot_qty: number | null;
    lipas_yn: string | null;
    qc_result: string | null;
    qc_defect: string | null;
    [key: string]: unknown;
}

const props = defineProps<{
    open: boolean;
    lot: LotRecord | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
    (e: 'saved'): void;
}>();

const selectedStatus = ref('');
const remarks = ref('');
const submitting = ref(false);
const showError = ref(false);

// Lot lookup state (for empty modal / F2 mode)
const lotIdInput = ref('');
const lotInputRef = ref<HTMLInputElement | null>(null);
const lookingUp = ref(false);
const lookupError = ref('');
const resolvedLot = ref<LotRecord | null>(null);

const TO_QC = [
    'Waiting MOLD',
    'Waiting Reli',
    'Waiting Dipping',
    'Waiting Reflow',
    'Waiting OI Size',
    'For Decision (QC)',
];
const TO_VI = ['Real NG Scan', 'Experiment', 'For Schedule (Yield)'];

const routingHint = computed(() => {
    if (!selectedStatus.value) return '';
    if (TO_QC.includes(selectedStatus.value))
        return 'Routes to QC Analysis — final decision set to Pending';
    if (TO_VI.includes(selectedStatus.value))
        return 'Routes to VI Technical — final decision set to Pending';
    if (selectedStatus.value === 'Low Yield (Rework)')
        return 'No routing — final decision set to Recovery';
    if (selectedStatus.value === 'For Verify (Production)')
        return 'No routing — final decision set to For Verify';
    if (selectedStatus.value === "For Decision (Tech'l)")
        return "No routing — final decision set to For Decision Tech'l";
    if (selectedStatus.value === 'For Decide (QC)')
        return 'No routing — final decision set to For Decide QC';
    if (selectedStatus.value === 'Completed')
        return 'Marks lot as Completed — output released';
    return '';
});

const routingHintClass = computed(() => {
    if (TO_QC.includes(selectedStatus.value))
        return 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300';
    if (TO_VI.includes(selectedStatus.value))
        return 'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300';
    if (selectedStatus.value === 'Completed')
        return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300';
    return 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300';
});

const routingHintIcon = computed(() => {
    if (TO_QC.includes(selectedStatus.value)) return GitBranch;
    if (TO_VI.includes(selectedStatus.value)) return FlaskConical;
    return Hourglass;
});

watch(
    () => props.open,
    (val) => {
        if (val) {
            selectedStatus.value = '';
            remarks.value = '';
            showError.value = false;
            lookupError.value = '';
            lotIdInput.value = '';
            resolvedLot.value = props.lot ?? null;
            if (!props.lot) {
                nextTick(() => lotInputRef.value?.focus());
            }
        }
    },
);

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
            data: LotRecord[];
        }>('/api/endline-delay/qc-ok-monitor', { params: { search: id } });
        const match = (data.data ?? []).find(
            (r) => r.lot_id?.toLowerCase() === id.toLowerCase(),
        );
        if (!match) {
            lookupError.value = `No QC OK lot found for "${id}".`;
            return;
        }
        resolvedLot.value = match;
    } catch {
        lookupError.value = 'Lookup failed. Please try again.';
    } finally {
        lookingUp.value = false;
    }
}

async function submit() {
    if (!selectedStatus.value) {
        showError.value = true;
        return;
    }
    if (!resolvedLot.value) return;

    showError.value = false;
    submitting.value = true;
    try {
        await axios.post(
            `/api/endline-delay/${resolvedLot.value.id}/update-qc-ok-status`,
            {
                status: selectedStatus.value,
                remarks: remarks.value || undefined,
            },
        );
        emit('saved');
        emit('update:open', false);
    } finally {
        submitting.value = false;
    }
}
</script>
