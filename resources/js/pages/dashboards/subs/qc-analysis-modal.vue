<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import axios from 'axios';
import {
    CheckCircle2,
    ClipboardCheck,
    ImagePlus,
    Loader2,
    Search,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface QcAnalysisRecord {
    id: number;
    lot_id: string;
    model: string | null;
    lot_qty: number | null;
    lipas_yn: string | null;
    work_type: string | null;
    defect_code: string | null;
    analysis_start_at: string | null;
    mold_result: string | null;
    mold_image_before: string[] | null;
    mold_image_after: string[] | null;
    reli_result: string | null;
    reli_image_before: string[] | null;
    reli_image_after: string[] | null;
    dipping_result: string | null;
    dipping_image_before: string[] | null;
    dipping_image_after: string[] | null;
    reflow_result: string | null;
    reflow_image_before: string[] | null;
    reflow_image_after: string[] | null;
    measure_result: string | null;
    measure_image_before: string[] | null;
    measure_image_after: string[] | null;
    analysis_result: string | null;
    analysis_completed_at: string | null;
    remarks: string | null;
    total_tat: number | null;
    inspection_result: string | null;
    mainlot_result: string | null;
    rr_result: string | null;
    ly_result: string | null;
    inspection_times: number | null;
    inspection_spl: number | null;
}

const RESULT_OPTIONS = ['In Progress', 'OK', 'NG'] as const;
const FINAL_RESULT_OPTIONS = [
    'Proceed',
    'Rework',
    'Proceed - w/ OTHER',
    'Derive / Scrap (RL)',
    'DRB',
    'For Decision',
] as const;
const STEPS = [
    { key: 'reli_result', label: 'RELI' },
    { key: 'mold_result', label: 'MOLD' },
    { key: 'dipping_result', label: 'DIPPING' },
    { key: 'reflow_result', label: 'REFLOW' },
    { key: 'measure_result', label: 'MEASURE' },
] as const;

const activeStep = ref<string | null>(null);

// Per-step defect images: before/after arrays (stored as base64 data URIs)
type StepKey =
    | 'reli_result'
    | 'mold_result'
    | 'dipping_result'
    | 'reflow_result'
    | 'measure_result';

type SlotKey = 'before' | 'after';

const stepImages = ref<Record<StepKey, Record<SlotKey, string[]>>>({
    reli_result: { before: [], after: [] },
    mold_result: { before: [], after: [] },
    dipping_result: { before: [], after: [] },
    reflow_result: { before: [], after: [] },
    measure_result: { before: [], after: [] },
});

// Active paste target: which step+slot is focused for clipboard paste
const pasteTarget = ref<{ key: StepKey; slot: 0 | 1 } | null>(null);

// Lightbox preview
const previewSrc = ref<string | null>(null);

const isReadonly = computed(() => props.readonly === true);

function compressToBase64(file: File | Blob): Promise<string> {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                canvas.getContext('2d')!.drawImage(img, 0, 0);
                resolve(canvas.toDataURL('image/jpeg', 0.7));
            };
            img.src = e.target!.result as string;
        };
        reader.readAsDataURL(file);
    });
}

async function onPaste(e: ClipboardEvent, key: StepKey, slot: SlotKey) {
    const items = e.clipboardData?.items;
    if (!items) return;
    for (const item of items) {
        if (item.type.startsWith('image/')) {
            const file = item.getAsFile();
            if (!file) continue;
            const b64 = await compressToBase64(file);
            stepImages.value[key][slot].push(b64);
            break;
        }
    }
}

function removeImage(key: StepKey, slot: SlotKey, index: number) {
    stepImages.value[key][slot].splice(index, 1);
}

function resetImages() {
    (Object.keys(stepImages.value) as StepKey[]).forEach((k) => {
        stepImages.value[k] = { before: [], after: [] };
    });
}

const props = defineProps<{
    open: boolean;
    lot?: QcAnalysisRecord | null;
    readonly?: boolean;
}>();
const emit = defineEmits<{
    (e: 'update:open', v: boolean): void;
    (e: 'submitted'): void;
}>();

const submitting = ref(false);
const lookupLoading = ref(false);
const errors = ref<Record<string, string>>({});
const lotInput = ref('');
const activeLot = ref<QcAnalysisRecord | null>(null);

interface StepForm {
    mold_result: string;
    reli_result: string;
    dipping_result: string;
    reflow_result: string;
    measure_result: string;
    analysis_result: string;
    remarks: string;
}

const form = ref<StepForm>({
    mold_result: '',
    reli_result: '',
    dipping_result: '',
    reflow_result: '',
    measure_result: '',
    analysis_result: '',
    remarks: '',
});

watch(
    () => props.open,
    (val) => {
        if (!val) return;
        errors.value = {};
        activeStep.value = null;
        resetImages();
        if (props.lot) {
            lotInput.value = props.lot.lot_id;
            activeLot.value = props.lot;
            populate(props.lot);
        } else {
            lotInput.value = '';
            activeLot.value = null;
            form.value = {
                mold_result: '',
                reli_result: '',
                dipping_result: '',
                reflow_result: '',
                measure_result: '',
                analysis_result: '',
                remarks: '',
            };
        }
    },
);

// Re-populate when lot prop is updated after modal is already open (enriched data arrives)
watch(
    () => props.lot,
    (lot) => {
        if (!props.open || !lot) return;
        lotInput.value = lot.lot_id;
        activeLot.value = lot;
        populate(lot);
    },
);

function populate(r: QcAnalysisRecord) {
    form.value = {
        mold_result: r.mold_result ?? '',
        reli_result: r.reli_result ?? '',
        dipping_result: r.dipping_result ?? '',
        reflow_result: r.reflow_result ?? '',
        measure_result: r.measure_result ?? '',
        analysis_result: r.analysis_result ?? '',
        remarks: r.remarks ?? '',
    };
    // Restore saved images
    stepImages.value = {
        mold_result: {
            before: r.mold_image_before ?? [],
            after: r.mold_image_after ?? [],
        },
        reli_result: {
            before: r.reli_image_before ?? [],
            after: r.reli_image_after ?? [],
        },
        dipping_result: {
            before: r.dipping_image_before ?? [],
            after: r.dipping_image_after ?? [],
        },
        reflow_result: {
            before: r.reflow_image_before ?? [],
            after: r.reflow_image_after ?? [],
        },
        measure_result: {
            before: r.measure_image_before ?? [],
            after: r.measure_image_after ?? [],
        },
    };
}

function onLotInput(e: Event) {
    lotInput.value = (e.target as HTMLInputElement).value.toUpperCase();
    delete errors.value.lot_id;
    // Clear lot details when user types a new lot
    activeLot.value = null;
}

async function lookupLot() {
    const id = lotInput.value.trim().toUpperCase();
    if (!id) return;
    lookupLoading.value = true;
    delete errors.value.lot_id;
    try {
        const { data } = await axios.get<{ data: QcAnalysisRecord }>(
            '/api/qc-analysis/find-by-lot',
            { params: { lot_id: id } },
        );
        activeLot.value = data.data;
        populate(data.data);
    } catch (err: any) {
        activeLot.value = null;
        form.value = {
            mold_result: '',
            reli_result: '',
            dipping_result: '',
            reflow_result: '',
            measure_result: '',
            analysis_result: '',
            remarks: '',
        };
        errors.value.lot_id =
            err.response?.data?.error ?? 'Lot not found in QC Analysis queue';
    } finally {
        lookupLoading.value = false;
    }
}

function btnClass(current: string, opt: string) {
    if (current !== opt)
        return 'border-border bg-background text-muted-foreground hover:border-muted-foreground/30 hover:text-foreground';
    if (opt === 'Proceed' || opt === 'Proceed - w/ OTHER' || opt === 'OK')
        return 'border-emerald-500 bg-emerald-500 text-white shadow-sm shadow-emerald-500/30';
    if (opt === 'Rework' || opt === 'NG')
        return 'border-red-500 bg-red-500 text-white shadow-sm shadow-red-500/30';
    if (opt === 'Derive / Scrap (RL)')
        return 'border-violet-500 bg-violet-500 text-white shadow-sm shadow-violet-500/30';
    if (opt === 'DRB')
        return 'border-orange-500 bg-orange-500 text-white shadow-sm shadow-orange-500/30';
    if (opt === 'For Decision')
        return 'border-sky-500 bg-sky-500 text-white shadow-sm shadow-sky-500/30';
    if (opt === 'In Progress')
        return 'border-amber-500 bg-amber-500 text-white shadow-sm shadow-amber-500/30';
}

function toggleStep(key: keyof StepForm, val: string) {
    form.value[key] = form.value[key] === val ? '' : val;
    buildRemarks();
}

function autoDerive() {
    const vals = STEPS.map((s) => form.value[s.key]).filter(Boolean);
    if (!vals.length) {
        form.value.analysis_result = '';
        return;
    }
    if (vals.some((v) => v === 'Rework' || v === 'NG')) {
        form.value.analysis_result = 'Rework';
        return;
    }
    if (vals.some((v) => v === 'In Progress')) {
        form.value.analysis_result = 'In Progress';
        return;
    }
    if (vals.every((v) => v === 'OK')) {
        form.value.analysis_result = 'Proceed';
    }
}

function buildRemarks() {
    const parts = STEPS.filter((s) => form.value[s.key]).map(
        (s) => `${s.label} - ${form.value[s.key]}`,
    );
    const final = form.value.analysis_result || 'Pending';
    parts.push(`Final: ${final}`);
    form.value.remarks = parts.join(', ');
}

const hasAnyStepInProgress = computed(() =>
    STEPS.map((s) => form.value[s.key])
        .filter(Boolean)
        .some((v) => v === 'In Progress'),
);

function validate() {
    errors.value = {};
    if (!activeLot.value) {
        errors.value.lot_id = 'Look up a lot first';
        return false;
    }
    // Final result is only required if NO step has "In Progress"
    if (!hasAnyStepInProgress.value && !form.value.analysis_result) {
        errors.value.analysis_result = 'Required';
    }
    // Block any final result when any step is still In Progress
    if (hasAnyStepInProgress.value && form.value.analysis_result) {
        errors.value.analysis_result =
            'Cannot set a final result while a step is still In Progress';
    }
    return !Object.keys(errors.value).length;
}

function showToast(message: string, type: 'success' | 'danger' = 'success') {
    const colors = { success: 'bg-emerald-600', danger: 'bg-red-600' };
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 ${colors[type]} text-white px-5 py-3 rounded-lg shadow-xl z-[9999] text-sm font-semibold transition-opacity duration-300`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

async function save() {
    if (!validate() || !activeLot.value) return;
    submitting.value = true;
    try {
        await axios.put(`/api/qc-analysis/${activeLot.value.id}`, {
            mold_result: form.value.mold_result || null,
            mold_image_before: stepImages.value.mold_result.before,
            mold_image_after: stepImages.value.mold_result.after,
            reli_result: form.value.reli_result || null,
            reli_image_before: stepImages.value.reli_result.before,
            reli_image_after: stepImages.value.reli_result.after,
            dipping_result: form.value.dipping_result || null,
            dipping_image_before: stepImages.value.dipping_result.before,
            dipping_image_after: stepImages.value.dipping_result.after,
            reflow_result: form.value.reflow_result || null,
            reflow_image_before: stepImages.value.reflow_result.before,
            reflow_image_after: stepImages.value.reflow_result.after,
            measure_result: form.value.measure_result || null,
            measure_image_before: stepImages.value.measure_result.before,
            measure_image_after: stepImages.value.measure_result.after,
            analysis_result: form.value.analysis_result || null,
            remarks: form.value.remarks || null,
        });
        showToast('QC Analysis saved successfully');
        emit('submitted');
        emit('update:open', false);
    } catch (err: any) {
        if (err.response?.status === 422) {
            const raw = err.response.data?.errors as
                | Record<string, string[]>
                | undefined;
            if (raw)
                Object.keys(raw).forEach((k) => (errors.value[k] = raw[k][0]));
        } else {
            showToast('Failed to save. Please try again.', 'danger');
        }
    } finally {
        submitting.value = false;
    }
}

function fmt(dt: string | null) {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('en-US', {
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function binClass(v: string | null) {
    if (!v)
        return 'bg-slate-50 text-slate-500 ring-slate-400/20 dark:bg-slate-800/30 dark:text-slate-400';
    return v === 'NG'
        ? 'bg-red-50 text-red-700 ring-red-500/20 dark:bg-red-950/30 dark:text-red-400'
        : 'bg-emerald-50 text-emerald-700 ring-emerald-500/20 dark:bg-emerald-950/30 dark:text-emerald-400';
}

function elapsed(start: string | null): string {
    if (!start) return '—';
    const mins = Math.floor((Date.now() - new Date(start).getTime()) / 60_000);
    if (mins < 60) return `${mins} min`;
    const h = Math.floor(mins / 60),
        m = mins % 60;
    return `${h}h ${m}m`;
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent
            class="flex !max-h-[88vh] !w-[860px] !max-w-[860px] flex-col gap-0 overflow-hidden p-0"
            @interact-outside.prevent
            @escape-key-down.prevent
        >
            <!-- Header -->
            <DialogHeader
                class="relative overflow-hidden rounded-t-lg border-b border-border/50 px-6 py-3.5"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-gradient-to-br from-indigo-500/10 via-transparent to-primary/5"
                ></div>
                <div class="relative flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-500/15 ring-1 ring-indigo-500/30"
                    >
                        <ClipboardCheck
                            class="h-4 w-4 text-indigo-600 dark:text-indigo-400"
                        />
                    </div>
                    <div>
                        <DialogTitle
                            class="text-sm leading-tight font-bold text-foreground"
                            >QC Analysis Update</DialogTitle
                        >
                        <DialogDescription
                            class="text-[11px] text-muted-foreground"
                            >Record analysis step results and final
                            decision</DialogDescription
                        >
                    </div>
                </div>
            </DialogHeader>

            <div class="flex-1 space-y-3 overflow-y-auto px-6 py-4">
                <!-- ROW 1: Lot No (editable) + auto-filled details -->
                <div class="grid grid-cols-12 items-end gap-3">
                    <!-- Lot No — input with search -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            Lot No <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <input
                                :value="lotInput"
                                type="text"
                                maxlength="7"
                                placeholder="—"
                                :disabled="isReadonly"
                                class="h-9 w-full rounded-lg border bg-background pr-8 pl-3 font-mono text-sm font-bold tracking-wider uppercase placeholder:font-normal placeholder:tracking-normal placeholder:text-muted-foreground/40 placeholder:normal-case focus:ring-2 focus:outline-none disabled:cursor-default disabled:opacity-60"
                                :class="
                                    errors.lot_id
                                        ? 'border-destructive focus:ring-destructive/20'
                                        : 'border-input focus:border-indigo-500 focus:ring-indigo-500/20'
                                "
                                @input="onLotInput"
                                @blur="lookupLot"
                                @keydown.enter.prevent="lookupLot"
                            />
                            <span
                                class="pointer-events-none absolute inset-y-0 right-2 flex items-center"
                            >
                                <Loader2
                                    v-if="lookupLoading"
                                    class="h-3.5 w-3.5 animate-spin text-indigo-500"
                                />
                                <Search
                                    v-else
                                    class="h-3 w-3 text-muted-foreground/40"
                                />
                            </span>
                        </div>
                        <p
                            v-if="errors.lot_id"
                            class="mt-0.5 text-[10px] leading-tight text-destructive"
                        >
                            {{ errors.lot_id }}
                        </p>
                    </div>

                    <!-- LIPAS -->
                    <div class="col-span-1">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >LIPAS</label
                        >
                        <div
                            class="flex h-9 items-center justify-center rounded-lg border border-input bg-muted/40 px-2 text-sm"
                        >
                            <span
                                v-if="activeLot?.lipas_yn"
                                class="font-bold"
                                :class="
                                    activeLot.lipas_yn === 'Y'
                                        ? 'text-emerald-600'
                                        : 'text-slate-500'
                                "
                            >
                                {{ activeLot.lipas_yn }}
                            </span>
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                    </div>

                    <!-- Model -->
                    <div class="col-span-3">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Model</label
                        >
                        <div
                            class="flex h-9 items-center rounded-lg border border-input bg-muted/40 px-3 text-sm"
                        >
                            <span
                                v-if="activeLot?.model"
                                class="truncate font-medium text-foreground"
                                :title="activeLot.model"
                                >{{ activeLot.model }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                    </div>

                    <!-- Qty -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Qty</label
                        >
                        <div
                            class="flex h-9 items-center rounded-lg border border-input bg-muted/40 px-3 text-sm font-semibold tabular-nums"
                        >
                            <span
                                v-if="activeLot?.lot_qty != null"
                                class="text-foreground"
                                >{{ activeLot.lot_qty.toLocaleString() }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                    </div>

                    <!-- Work Type -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Work Type</label
                        >
                        <div
                            class="flex h-9 items-center rounded-lg border border-input bg-muted/40 px-3 text-sm"
                        >
                            <span
                                v-if="activeLot?.work_type"
                                class="truncate text-foreground"
                                >{{ activeLot.work_type }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                    </div>

                    <!-- Analysis Start -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Analysis Start</label
                        >
                        <div
                            class="flex h-9 items-center rounded-lg border border-input bg-muted/40 px-3 text-xs whitespace-nowrap text-muted-foreground"
                        >
                            {{ fmt(activeLot?.analysis_start_at ?? null) }}
                        </div>
                    </div>
                </div>

                <!-- ROW 2: Bin results + Defect Code -->
                <div class="grid grid-cols-12 items-stretch gap-3">
                    <!-- Main Lot bin card -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Main Lot</label
                        >
                        <div
                            class="flex h-10 items-center gap-2.5 rounded-lg border px-3 transition-colors"
                            :class="
                                activeLot?.mainlot_result === 'NG'
                                    ? 'border-red-200 bg-red-50 dark:border-red-800/60 dark:bg-red-950/30'
                                    : activeLot?.mainlot_result === 'OK'
                                      ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800/60 dark:bg-emerald-950/30'
                                      : 'border-border bg-muted/40'
                            "
                        >
                            <span
                                v-if="activeLot?.mainlot_result"
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] leading-none font-black"
                                :class="
                                    activeLot.mainlot_result === 'NG'
                                        ? 'bg-red-500 text-white'
                                        : 'bg-emerald-500 text-white'
                                "
                                >{{
                                    activeLot.mainlot_result === 'NG'
                                        ? '✕'
                                        : '✓'
                                }}</span
                            >
                            <span
                                class="text-sm font-bold"
                                :class="
                                    activeLot?.mainlot_result === 'NG'
                                        ? 'text-red-700 dark:text-red-400'
                                        : activeLot?.mainlot_result === 'OK'
                                          ? 'text-emerald-700 dark:text-emerald-400'
                                          : 'text-muted-foreground/40'
                                "
                                >{{ activeLot?.mainlot_result ?? '—' }}</span
                            >
                        </div>
                    </div>

                    <!-- RR Bin card -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >RR Bin</label
                        >
                        <div
                            class="flex h-10 items-center gap-2.5 rounded-lg border px-3 transition-colors"
                            :class="
                                activeLot?.rr_result === 'NG'
                                    ? 'border-orange-200 bg-orange-50 dark:border-orange-800/60 dark:bg-orange-950/30'
                                    : activeLot?.rr_result === 'OK'
                                      ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800/60 dark:bg-emerald-950/30'
                                      : 'border-border bg-muted/40'
                            "
                        >
                            <span
                                v-if="activeLot?.rr_result"
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] leading-none font-black"
                                :class="
                                    activeLot.rr_result === 'NG'
                                        ? 'bg-orange-500 text-white'
                                        : 'bg-emerald-500 text-white'
                                "
                                >{{
                                    activeLot.rr_result === 'NG' ? '✕' : '✓'
                                }}</span
                            >
                            <span
                                class="text-sm font-bold"
                                :class="
                                    activeLot?.rr_result === 'NG'
                                        ? 'text-orange-700 dark:text-orange-400'
                                        : activeLot?.rr_result === 'OK'
                                          ? 'text-emerald-700 dark:text-emerald-400'
                                          : 'text-muted-foreground/40'
                                "
                                >{{ activeLot?.rr_result ?? '—' }}</span
                            >
                        </div>
                    </div>

                    <!-- LY Bin card -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >LY Bin</label
                        >
                        <div
                            class="flex h-10 items-center gap-2.5 rounded-lg border px-3 transition-colors"
                            :class="
                                activeLot?.ly_result === 'NG'
                                    ? 'border-violet-200 bg-violet-50 dark:border-violet-800/60 dark:bg-violet-950/30'
                                    : activeLot?.ly_result === 'OK'
                                      ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800/60 dark:bg-emerald-950/30'
                                      : 'border-border bg-muted/40'
                            "
                        >
                            <span
                                v-if="activeLot?.ly_result"
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] leading-none font-black"
                                :class="
                                    activeLot.ly_result === 'NG'
                                        ? 'bg-violet-500 text-white'
                                        : 'bg-emerald-500 text-white'
                                "
                                >{{
                                    activeLot.ly_result === 'NG' ? '✕' : '✓'
                                }}</span
                            >
                            <span
                                class="text-sm font-bold"
                                :class="
                                    activeLot?.ly_result === 'NG'
                                        ? 'text-violet-700 dark:text-violet-400'
                                        : activeLot?.ly_result === 'OK'
                                          ? 'text-emerald-700 dark:text-emerald-400'
                                          : 'text-muted-foreground/40'
                                "
                                >{{ activeLot?.ly_result ?? '—' }}</span
                            >
                        </div>
                    </div>

                    <!-- Defect Code -->
                    <div class="col-span-4">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Defect Code</label
                        >
                        <div
                            class="flex h-10 items-center rounded-lg border border-input bg-muted/40 px-3 text-sm"
                        >
                            <span
                                v-if="activeLot?.defect_code"
                                class="truncate font-semibold text-foreground"
                                :title="activeLot.defect_code"
                                >{{ activeLot.defect_code }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                    </div>

                    <!-- Elapsed -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Elapsed</label
                        >
                        <div
                            class="flex h-10 items-center rounded-lg border border-input bg-muted/40 px-3"
                        >
                            <span
                                class="text-sm font-semibold tabular-nums"
                                :class="
                                    activeLot?.analysis_start_at
                                        ? 'text-amber-600 dark:text-amber-400'
                                        : 'text-muted-foreground/40'
                                "
                                >{{
                                    elapsed(
                                        activeLot?.analysis_start_at ?? null,
                                    )
                                }}</span
                            >
                        </div>
                    </div>
                </div>

                <div class="h-px bg-border/40"></div>

                <!-- Analysis Steps + Final Result side by side -->
                <div class="grid grid-cols-2 gap-5">
                    <!-- Steps -->
                    <div>
                        <p class="mb-2 text-xs font-semibold text-foreground">
                            Analysis Steps
                        </p>
                        <!-- Step tab row -->
                        <div class="flex gap-1.5">
                            <button
                                v-for="step in STEPS"
                                :key="step.key"
                                type="button"
                                class="flex-1 rounded-lg border px-2 py-2 text-[11px] font-black tracking-wider uppercase transition-all duration-100"
                                :class="
                                    activeStep === step.key
                                        ? 'border-indigo-500 bg-indigo-500 text-white shadow-sm shadow-indigo-500/30'
                                        : form[step.key as keyof StepForm]
                                          ? form[step.key as keyof StepForm] ===
                                            'OK'
                                              ? 'border-emerald-400 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400'
                                              : form[
                                                      step.key as keyof StepForm
                                                  ] === 'NG'
                                                ? 'border-red-400 bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400'
                                                : 'border-amber-400 bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400'
                                          : 'border-border bg-background text-muted-foreground hover:border-muted-foreground/30 hover:text-foreground'
                                "
                                :disabled="!activeLot"
                                @click="
                                    activeStep =
                                        activeStep === step.key
                                            ? null
                                            : step.key;
                                    if (
                                        !isReadonly &&
                                        activeStep &&
                                        !form[activeStep as keyof StepForm]
                                    ) {
                                        (form[
                                            activeStep as keyof StepForm
                                        ] as string) = 'In Progress';
                                        buildRemarks();
                                    }
                                "
                            >
                                {{ step.label }}
                            </button>
                        </div>
                        <!-- Active step result panel -->
                        <div
                            v-for="step in STEPS"
                            :key="step.key + '_panel'"
                            v-show="activeStep === step.key"
                            class="mt-2 rounded-lg border border-border/60 bg-muted/20 p-3"
                        >
                            <!-- Result label + buttons in one row -->
                            <div class="flex items-center gap-2">
                                <p
                                    class="shrink-0 text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                                >
                                    {{ step.label }} Result
                                </p>
                                <div class="flex flex-1 gap-2">
                                    <button
                                        v-for="opt in RESULT_OPTIONS"
                                        :key="opt"
                                        type="button"
                                        class="flex-1 rounded-lg border py-1.5 text-xs font-bold transition-all duration-100"
                                        :class="
                                            btnClass(
                                                form[
                                                    step.key as keyof StepForm
                                                ],
                                                opt,
                                            )
                                        "
                                        :disabled="!activeLot || isReadonly"
                                        @click="
                                            toggleStep(
                                                step.key as keyof StepForm,
                                                opt,
                                            )
                                        "
                                    >
                                        {{ opt }}
                                    </button>
                                </div>
                                <div
                                    v-if="form[step.key as keyof StepForm]"
                                    class="flex shrink-0 items-center gap-1 text-[11px] font-semibold"
                                    :class="
                                        form[step.key as keyof StepForm] ===
                                        'OK'
                                            ? 'text-emerald-600'
                                            : form[
                                                    step.key as keyof StepForm
                                                ] === 'NG'
                                              ? 'text-red-600'
                                              : 'text-amber-500'
                                    "
                                >
                                    <CheckCircle2
                                        v-if="
                                            form[step.key as keyof StepForm] ===
                                            'OK'
                                        "
                                        class="h-3.5 w-3.5"
                                    />
                                    <XCircle v-else class="h-3.5 w-3.5" />
                                </div>
                            </div>

                            <!-- Defect image upload slots -->
                            <div class="mt-3 flex gap-2">
                                <div
                                    v-for="slot in ['before', 'after'] as const"
                                    :key="slot"
                                    class="flex-1"
                                >
                                    <p
                                        class="mb-1 text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                                    >
                                        Defect Image
                                        {{
                                            slot === 'before'
                                                ? 'Before'
                                                : 'After'
                                        }}
                                    </p>
                                    <!-- Thumbnails -->
                                    <div
                                        v-if="
                                            stepImages[step.key as StepKey][
                                                slot
                                            ].length
                                        "
                                        class="mb-1.5 flex flex-wrap gap-1"
                                    >
                                        <div
                                            v-for="(img, idx) in stepImages[
                                                step.key as StepKey
                                            ][slot]"
                                            :key="idx"
                                            class="group relative h-16 w-16 overflow-hidden rounded border-2 border-indigo-400 bg-black"
                                        >
                                            <img
                                                :src="img"
                                                class="h-full w-full cursor-zoom-in object-cover"
                                                alt="Defect"
                                                @click="previewSrc = img"
                                            />
                                            <button
                                                v-if="!isReadonly"
                                                type="button"
                                                class="absolute top-0.5 right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[13px] leading-none font-black text-white opacity-0 transition-opacity group-hover:opacity-100"
                                                @click.stop="
                                                    removeImage(
                                                        step.key as StepKey,
                                                        slot,
                                                        idx,
                                                    )
                                                "
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Paste zone — always visible, paste appends -->
                                    <div
                                        tabindex="0"
                                        class="flex h-14 w-full cursor-text flex-col items-center justify-center gap-0.5 rounded-lg border-2 border-dashed border-indigo-400/60 bg-indigo-50/30 transition-colors focus:border-indigo-500 focus:bg-indigo-50/60 focus:outline-none dark:bg-indigo-950/10"
                                        :class="
                                            !activeLot || isReadonly
                                                ? 'pointer-events-none opacity-40'
                                                : ''
                                        "
                                        @paste="
                                            onPaste(
                                                $event,
                                                step.key as StepKey,
                                                slot,
                                            )
                                        "
                                    >
                                        <ImagePlus
                                            class="h-4 w-4 text-indigo-300"
                                        />
                                        <span
                                            class="text-[9px] font-semibold text-indigo-400"
                                            >Click, then Ctrl+V</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Final Result + Remarks -->
                    <div class="flex flex-col gap-3">
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-semibold text-foreground"
                            >
                                Final Result and Decision
                                <span
                                    v-if="
                                        !STEPS.map((s) => form[s.key])
                                            .filter(Boolean)
                                            .some((v) => v === 'In Progress')
                                    "
                                    class="text-destructive"
                                    >*</span
                                >
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="opt in FINAL_RESULT_OPTIONS"
                                    :key="opt"
                                    type="button"
                                    class="rounded-xl border px-4 py-2.5 text-sm font-bold tracking-wide transition-all duration-150"
                                    :class="btnClass(form.analysis_result, opt)"
                                    :disabled="
                                        !activeLot ||
                                        isReadonly ||
                                        hasAnyStepInProgress
                                    "
                                    :title="
                                        hasAnyStepInProgress
                                            ? 'Not allowed while a step is In Progress'
                                            : undefined
                                    "
                                    @click="
                                        form.analysis_result =
                                            form.analysis_result === opt
                                                ? ''
                                                : opt;
                                        delete errors.analysis_result;
                                        buildRemarks();
                                    "
                                >
                                    {{ opt }}
                                </button>
                            </div>
                            <p
                                v-if="errors.analysis_result"
                                class="mt-0.5 text-[10px] text-destructive"
                            >
                                {{ errors.analysis_result }}
                            </p>
                        </div>
                        <div class="flex-1">
                            <label
                                class="mb-1 block text-xs font-semibold text-foreground"
                                >Remarks</label
                            >
                            <textarea
                                v-model="form.remarks"
                                rows="6"
                                :disabled="!activeLot || isReadonly"
                                placeholder="Add analysis notes..."
                                class="w-full resize-none rounded-lg border border-input bg-background px-3 py-2 text-xs placeholder:text-muted-foreground/50 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none disabled:opacity-40"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex shrink-0 items-center justify-end gap-2 border-t border-border/50 bg-muted/10 px-6 py-3.5"
            >
                <button
                    type="button"
                    class="inline-flex h-9 items-center rounded-lg border border-border bg-background px-5 text-sm font-semibold text-foreground transition-all hover:bg-muted"
                    @click="emit('update:open', false)"
                >
                    {{ props.readonly ? 'Close' : 'Cancel' }}
                </button>
                <button
                    v-if="!props.readonly"
                    type="button"
                    class="inline-flex h-9 items-center rounded-lg bg-gradient-to-r from-indigo-600 to-indigo-500 px-6 text-sm font-semibold text-white shadow-md shadow-indigo-500/25 transition-all hover:from-indigo-700 hover:to-indigo-600 disabled:pointer-events-none disabled:opacity-50"
                    :disabled="submitting || !activeLot"
                    @click="save"
                >
                    <Loader2
                        v-if="submitting"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    Save Analysis
                </button>
            </div>

            <!-- Lightbox preview — inside DialogContent so it covers the modal -->
            <div
                v-if="previewSrc"
                class="absolute inset-0 z-50 flex items-center justify-center rounded-lg"
            >
                <!-- backdrop -->
                <div
                    class="absolute inset-0 rounded-lg bg-black/85 backdrop-blur-sm"
                    @click="previewSrc = null"
                ></div>
                <!-- image -->
                <img
                    :src="previewSrc"
                    class="relative z-10 max-h-[85vh] max-w-[95%] rounded-lg shadow-2xl"
                    alt="Preview"
                />
                <!-- close button -->
                <button
                    type="button"
                    class="absolute top-3 right-3 z-20 flex h-9 items-center gap-1.5 rounded-lg bg-white/20 px-3 text-sm font-semibold text-white transition-colors hover:bg-white/40"
                    @click="previewSrc = null"
                >
                    <span class="text-base leading-none font-black">✕</span>
                    Close Preview
                </button>
            </div>
        </DialogContent>
    </Dialog>
</template>
