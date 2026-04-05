<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import axios from 'axios';
import { ClipboardList, Loader2, Search } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref, watch, watchEffect } from 'vue';

interface QcInspectionRecord {
    id: number;
    lot_id: string;
    model: string | null;
    lot_qty: number | null;
    lipas_yn: string | null;
    work_type: string | null;
    inspection_times: number | null;
    inspection_spl: number | null;
    inspected_part: string | null;
    inspection_result: string | null;
    defect_code: string | null;
    defect_flow: string | null;
    final_decision: string | null;
    remarks: string | null;
    output_status: string | null;
    created_by: string | null;
    updated_by: string | null;
}

interface DefectOption {
    defect_code: string;
    defect_name: string;
    defect_class: string;
    defect_flow: string;
}

interface FormState {
    lot_id: string;
    model: string;
    lot_qty: number | null;
    lipas_yn: string;
    work_type: string;
    inspection_times: number | null;
    inspection_spl: number | null;
    inspected_bin: string;
    qc_result: string; // 'OK' | 'NG' | ''
    inspected_bin_result: string[]; // Main / RR / LY
    qc_ng_part: string[]; // Main / RR / LY
    inspection_result: string[];
    defect_codes: string[];
    defect_input: string;
    defect_flow: string;
    final_decision: string;
    remarks: string;
    _loading: boolean;
}

const props = defineProps<{
    open: boolean;
    editRecord?: QcInspectionRecord | null;
}>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'saved'): void;
}>();

const submitting = ref(false);
const errors = ref<Record<string, string>>({});
const isEditMode = ref(false);
const editId = ref<number | null>(null);
const scanOnly = ref(false);
const defectSuggestions = ref<DefectOption[]>([]);
const defectOpen = ref(false);
const defectCache = ref<
    Record<string, { defect_flow: string; defect_class: string }>
>({});
let defectTimer: ReturnType<typeof setTimeout> | null = null;

function emptyForm(): FormState {
    return {
        lot_id: '',
        model: '',
        lot_qty: null,
        lipas_yn: '',
        work_type: '',
        inspection_times: null,
        inspection_spl: null,
        inspected_bin: '',
        qc_result: '',
        inspected_bin_result: [],
        qc_ng_part: [],
        inspection_result: [],
        defect_codes: [],
        defect_input: '',
        defect_flow: '',
        final_decision: 'Pending',
        remarks: '',
        _loading: false,
    };
}

const form = ref<FormState>(emptyForm());

watch(
    () => props.open,
    (val) => {
        if (!val) return;
        errors.value = {};
        defectSuggestions.value = [];
        defectOpen.value = false;
        remarksManuallyEdited.value = false;
        scanOnly.value = false;
        if (props.editRecord) {
            isEditMode.value = true;
            editId.value = props.editRecord.id;
            form.value = {
                lot_id: props.editRecord.lot_id,
                model: props.editRecord.model ?? '',
                lot_qty: props.editRecord.lot_qty,
                lipas_yn: props.editRecord.lipas_yn ?? '',
                work_type: props.editRecord.work_type ?? '',
                inspection_times: props.editRecord.inspection_times,
                inspection_spl: props.editRecord.inspection_spl,
                inspected_bin: props.editRecord.inspected_part ?? '',
                qc_result: props.editRecord.inspection_result?.includes('OK')
                    ? 'OK'
                    : props.editRecord.inspection_result
                      ? 'NG'
                      : '',
                inspected_bin_result: [],
                qc_ng_part: props.editRecord.inspection_result
                    ? props.editRecord.inspection_result
                          .split(',')
                          .map((s) => s.trim())
                          .filter((s) => ['Main', 'RR', 'LY'].includes(s))
                    : [],
                inspection_result: props.editRecord.inspection_result
                    ? props.editRecord.inspection_result
                          .split(',')
                          .map((s) => s.trim())
                          .filter(Boolean)
                    : [],
                defect_codes: props.editRecord.defect_code
                    ? props.editRecord.defect_code
                          .split(',')
                          .map((s) => s.trim())
                          .filter(Boolean)
                    : [],
                defect_input: '',
                defect_flow: props.editRecord.defect_flow ?? '',
                final_decision: props.editRecord.final_decision ?? 'Pending',
                remarks: props.editRecord.remarks ?? '',
                _loading: false,
            };
        } else {
            isEditMode.value = false;
            editId.value = null;
            form.value = emptyForm();
        }
    },
);

async function lookupLot() {
    const lotId = form.value.lot_id.trim();
    if (!lotId || scanOnly.value) return;
    form.value._loading = true;
    try {
        // First check if this lot has a pending entry
        if (!isEditMode.value) {
            const { data: check } = await axios.get(
                '/api/qc-inspection/check-lot',
                {
                    params: { lot_id: lotId },
                },
            );
            if (check.pending) {
                errors.value.lot_id = check.message;
                form.value._loading = false;
                return;
            }
        }

        const { data } = await axios.get('/api/endline-delay/lot-lookup', {
            params: { lot_id: lotId },
        });
        if (data && (data.model_15 || data.lot_qty || data.work_type)) {
            form.value.model = data.model_15 ?? form.value.model;
            form.value.lot_qty = data.lot_qty ?? form.value.lot_qty;
            form.value.work_type = data.work_type ?? form.value.work_type;
            form.value.lipas_yn = data.lipas_yn ?? form.value.lipas_yn;
        } else {
            errors.value.lot_id = 'Lot not found in system';
        }
    } catch {
        errors.value.lot_id = 'Lot not found in system';
    } finally {
        form.value._loading = false;
    }
}

function onModelBlur() {
    const val = form.value.model.trim();
    if (val.length === 12) {
        form.value.model = `CL${val}B`;
        delete errors.value.model;
    }
}

function onLotInput(e: Event) {
    form.value.lot_id = (e.target as HTMLInputElement).value.toUpperCase();
    delete errors.value.lot_id;
    delete errors.value.model;
    delete errors.value.lot_qty;
    delete errors.value.work_type;
    delete errors.value.lipas_yn;
}

async function onDefectInput(val: string) {
    if (defectTimer) clearTimeout(defectTimer);
    if (!val.trim()) {
        defectSuggestions.value = [];
        defectOpen.value = false;
        return;
    }
    defectTimer = setTimeout(async () => {
        const { data } = await axios.get<DefectOption[]>(
            '/api/endline-delay/defect-codes',
            { params: { q: val } },
        );
        defectSuggestions.value = data;
        defectOpen.value = data.length > 0;
    }, 200);
}

function onDefectRawInput(e: Event) {
    form.value.defect_input = (
        e.target as HTMLInputElement
    ).value.toUpperCase();
    onDefectInput(form.value.defect_input);
}

function resolveDefectFlow() {
    if (!form.value.defect_codes.length) {
        form.value.defect_flow = '';
        return;
    }
    const priority = form.value.defect_codes.find(
        (c) => defectCache.value[c]?.defect_class === 'QC Analysis',
    );
    const key =
        priority ?? form.value.defect_codes.find((c) => defectCache.value[c]);
    form.value.defect_flow = key
        ? (defectCache.value[key]?.defect_flow ?? '')
        : '';
}

function selectDefect(opt: DefectOption) {
    if (!form.value.defect_codes.includes(opt.defect_code))
        form.value.defect_codes.push(opt.defect_code);
    defectCache.value[opt.defect_code] = {
        defect_flow: opt.defect_flow,
        defect_class: opt.defect_class,
    };
    form.value.defect_input = '';
    resolveDefectFlow();
    defectOpen.value = false;
    defectSuggestions.value = [];
    delete errors.value.defect_codes;
}

function removeDefectTag(code: string) {
    form.value.defect_codes = form.value.defect_codes.filter((c) => c !== code);
    resolveDefectFlow();
}

function onDefectKeydown(e: KeyboardEvent) {
    if (e.key === ',' || e.key === 'Enter') {
        e.preventDefault();
        const val = form.value.defect_input.trim().toUpperCase();
        const exact = defectSuggestions.value.find(
            (d) => d.defect_code === val,
        );
        if (exact) selectDefect(exact);
        else {
            form.value.defect_input = '';
            defectOpen.value = false;
        }
    } else if (
        e.key === 'Backspace' &&
        form.value.defect_input === '' &&
        form.value.defect_codes.length
    ) {
        form.value.defect_codes.pop();
        resolveDefectFlow();
    } else if (e.key === 'Escape') {
        defectOpen.value = false;
    }
}

async function closeDefectDropdown() {
    await new Promise((r) => setTimeout(r, 150));
    form.value.defect_input = '';
    defectOpen.value = false;
}

function toggleInspectionResult(opt: string) {
    delete errors.value.inspection_result;
    if (opt === 'OK') {
        form.value.inspection_result = form.value.inspection_result.includes(
            'OK',
        )
            ? []
            : ['OK'];
        if (form.value.inspection_result.includes('OK')) {
            form.value.defect_codes = [];
            form.value.defect_flow = '';
        }
        return;
    }
    const without = form.value.inspection_result.filter((v) => v !== 'OK');
    form.value.inspection_result = without.includes(opt)
        ? without.filter((v) => v !== opt)
        : [...without, opt];
}

function setQcResult(val: string) {
    delete errors.value.qc_result;
    form.value.qc_result = form.value.qc_result === val ? '' : val;
    // Clear NG-specific fields when switching to OK
    if (form.value.qc_result === 'OK') {
        form.value.qc_ng_part = [];
        form.value.defect_codes = [];
        form.value.defect_flow = '';
    }
}

function toggleInspectedBin(opt: string) {
    delete errors.value.inspected_bin_result;
    form.value.inspected_bin_result = form.value.inspected_bin_result.includes(
        opt,
    )
        ? form.value.inspected_bin_result.filter((v) => v !== opt)
        : [...form.value.inspected_bin_result, opt];
}

function toggleQcNgPart(opt: string) {
    delete errors.value.qc_ng_part;
    form.value.qc_ng_part = form.value.qc_ng_part.includes(opt)
        ? form.value.qc_ng_part.filter((v) => v !== opt)
        : [...form.value.qc_ng_part, opt];
}

const splQtyDisplay = ref('');

function onSplQtyInput(e: Event) {
    const raw = (e.target as HTMLInputElement).value.replace(/,/g, '');
    const num = parseInt(raw, 10);
    form.value.inspection_spl = isNaN(num) ? null : num;
    splQtyDisplay.value = isNaN(num) ? raw : num.toLocaleString();
    delete errors.value.inspection_spl;
}

watch(
    () => form.value.inspection_spl,
    (val) => {
        splQtyDisplay.value = val != null ? val.toLocaleString() : '';
    },
    { immediate: true },
);

const lotQtyDisplay = ref('');

function onLotQtyInput(e: Event) {
    const raw = (e.target as HTMLInputElement).value.replace(/,/g, '');
    const num = parseInt(raw, 10);
    form.value.lot_qty = isNaN(num) ? null : num;
    lotQtyDisplay.value = isNaN(num) ? raw : num.toLocaleString();
}

watch(
    () => form.value.lot_qty,
    (val) => {
        lotQtyDisplay.value = val != null ? val.toLocaleString() : '';
    },
    { immediate: true },
);

function onInspTimesInput() {
    delete errors.value.inspection_times;
}
const remarksManuallyEdited = ref(false);

function onRemarksInput() {
    remarksManuallyEdited.value = true;
}

function buildAutoRemarks(): string {
    if (!form.value.qc_result) return scanOnly.value ? 'Scan Only' : '';

    const prefix = scanOnly.value ? 'Scan Only | ' : '';

    if (form.value.qc_result === 'OK') {
        const bins = form.value.inspected_bin_result;
        const t =
            form.value.inspection_times != null
                ? `-${form.value.inspection_times}`
                : '';
        if (!bins.length) return prefix + 'QC OK';
        return prefix + bins.map((b) => `${b}${t}-OK`).join(', ');
    }

    // NG path — describe each inspected bin's status
    const inspected = form.value.inspected_bin_result;
    const ngBins = form.value.qc_ng_part;
    const defects = form.value.defect_codes;

    if (!inspected.length && !ngBins.length) return prefix + 'QC NG';

    const allBins = ['Main', 'RR', 'LY'];
    const relevant = inspected.length ? inspected : ngBins;

    const parts = allBins
        .filter((b) => relevant.includes(b) || ngBins.includes(b))
        .map((b) => ({ bin: b, isNg: ngBins.includes(b) }))
        .sort((a, b) => Number(a.isNg) - Number(b.isNg)) // OK bins first
        .map(({ bin, isNg }) => {
            const times = form.value.inspection_times;
            const t = times != null ? `-${times}` : '';
            return `${bin}${t}-${isNg ? 'NG' : 'OK'}`;
        });

    let summary = prefix + parts.join(', ');
    if (defects.length) summary += ` [${defects.join(', ')}]`;
    return summary;
}

// Auto-fill remarks whenever relevant fields change, unless user edited manually
watchEffect(() => {
    if (remarksManuallyEdited.value) return;
    const auto = buildAutoRemarks();
    if (auto) form.value.remarks = auto;
});

function validate(): boolean {
    errors.value = {};
    if (!form.value.lot_id.trim()) errors.value.lot_id = 'Lot No is required';
    const model = form.value.model.trim();
    if (!model) {
        errors.value.model = 'Model is required';
    } else if (
        model.length !== 15 ||
        !model.startsWith('CL') ||
        !model.endsWith('B')
    ) {
        errors.value.model =
            'Model must be 15 characters, start with "CL" and end with "B"';
    }
    if (form.value.lot_qty == null) errors.value.lot_qty = 'Required';
    if (!form.value.lipas_yn) errors.value.lipas_yn = 'Required';
    if (!form.value.work_type) errors.value.work_type = 'Required';
    if (!form.value.qc_result) errors.value.qc_result = 'Select OK or NG';
    if (form.value.inspected_bin_result.length === 0)
        errors.value.inspected_bin_result = 'Select at least one inspected bin';
    if (form.value.qc_result === 'NG' && form.value.qc_ng_part.length === 0)
        errors.value.qc_ng_part = 'Select at least one NG bin';
    if (form.value.inspection_times == null || form.value.inspection_times < 1)
        errors.value.inspection_times = 'Required';
    if (form.value.inspection_spl == null || form.value.inspection_spl < 0)
        errors.value.inspection_spl = 'Required';
    if (form.value.qc_result === 'NG' && form.value.defect_codes.length === 0)
        errors.value.defect_codes = 'Defect code required for NG';
    return Object.keys(errors.value).length === 0;
}

function toPayload() {
    const bins = form.value.inspected_bin_result;
    const ngBins = form.value.qc_ng_part;

    // For each possible bin: OK if inspected & not NG, NG if inspected & NG, null if not inspected
    const binResult = (bin: string) => {
        if (!bins.includes(bin)) return null;
        return ngBins.includes(bin) ? 'NG' : 'OK';
    };

    return {
        lot_id: form.value.lot_id || null,
        model: form.value.model || null,
        lot_qty: form.value.lot_qty,
        lipas_yn: form.value.lipas_yn || null,
        work_type: form.value.work_type || null,
        inspection_times: form.value.inspection_times ?? null,
        inspection_spl: form.value.inspection_spl ?? null,
        inspected_bin: bins.length ? bins.join(', ') : null,
        inspection_result: form.value.qc_result || null,
        mainlot_result: binResult('Main'),
        rr_result: binResult('RR'),
        ly_result: binResult('LY'),
        defect_code: form.value.defect_codes.length
            ? form.value.defect_codes.join(', ')
            : null,
        defect_flow: form.value.defect_flow || null,
        final_decision: form.value.final_decision || null,
        remarks: form.value.remarks || null,
    };
}

async function save() {
    if (!validate()) return;
    submitting.value = true;
    try {
        if (isEditMode.value && editId.value) {
            await axios.put(`/api/qc-inspection/${editId.value}`, toPayload());
        } else {
            await axios.post('/api/qc-inspection', toPayload());
        }
        emit('saved');
        emit('update:open', false);
        showToast('QC inspection record saved successfully', 'success');
    } catch (err: any) {
        if (err.response?.status === 422) {
            const raw = err.response.data?.errors as
                | Record<string, string[]>
                | undefined;
            if (raw)
                Object.keys(raw).forEach((k) => (errors.value[k] = raw[k][0]));
            else if (err.response.data?.message)
                errors.value.lot_id = err.response.data.message;
        }
    } finally {
        submitting.value = false;
    }
}

function close() {
    emit('update:open', false);
}

function showToast(
    message: string,
    type: 'success' | 'danger' | 'info' | 'warning' = 'info',
) {
    const bgColors = {
        success: 'bg-green-600',
        danger: 'bg-red-600',
        info: 'bg-blue-600',
        warning: 'bg-amber-600',
    };
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 ${bgColors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-[9999] transition-opacity duration-300 text-sm font-medium`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function onAltS(e: KeyboardEvent) {
    if (e.altKey && (e.key === 's' || e.key === 'S') && props.open) {
        e.preventDefault();
        save();
    }
}

onMounted(() => document.addEventListener('keydown', onAltS));
onBeforeUnmount(() => document.removeEventListener('keydown', onAltS));
</script>

<template>
    <Dialog :open="open" @update:open="(v: boolean) => emit('update:open', v)">
        <DialogContent
            class="flex !max-h-[95vh] !w-[820px] !max-w-[820px] flex-col gap-0 overflow-hidden p-0"
        >
            <!-- Header -->
            <DialogHeader
                class="relative overflow-hidden rounded-t-lg border-b border-border/50 px-6 py-4"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-gradient-to-br from-teal-500/10 via-transparent to-primary/5"
                ></div>
                <div class="relative flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-500/15 ring-1 ring-teal-500/30"
                    >
                        <ClipboardList
                            class="h-4 w-4 text-teal-600 dark:text-teal-400"
                        />
                    </div>
                    <div>
                        <DialogTitle
                            class="text-base leading-tight font-bold text-foreground"
                        >
                            {{
                                isEditMode
                                    ? 'Edit QC Inspection'
                                    : 'QC Inspected Lot Entry'
                            }}
                        </DialogTitle>
                        <DialogDescription
                            class="mt-0.5 text-xs text-muted-foreground"
                        >
                            {{
                                isEditMode
                                    ? 'Update inspection record'
                                    : 'Record a single QC inspected lot'
                            }}
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <!-- Body: no scroll, fixed layout -->
            <div class="space-y-4 px-6 py-5">
                <!-- ROW 1: Lot No | Scan Only | LIPAS | Model | Qty | Work Type -->
                <div class="grid grid-cols-12 items-end gap-3">
                    <!-- Lot No — 2/12 -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-xs font-semibold text-foreground"
                        >
                            Lot No <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <input
                                :value="form.lot_id"
                                type="text"
                                maxlength="7"
                                placeholder=""
                                class="h-9 w-full rounded-lg border bg-background pr-9 pl-3 font-mono text-sm font-semibold tracking-wider uppercase placeholder:font-normal placeholder:tracking-normal placeholder:text-muted-foreground/50 placeholder:normal-case focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
                                :class="
                                    errors.lot_id
                                        ? 'border-destructive'
                                        : 'border-input focus:border-teal-500'
                                "
                                @input="onLotInput"
                                @blur="lookupLot"
                                @keydown.enter.prevent="lookupLot"
                            />
                            <span
                                class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center"
                            >
                                <Loader2
                                    v-if="form._loading"
                                    class="h-4 w-4 animate-spin text-teal-500"
                                />
                                <Search
                                    v-else
                                    class="h-3.5 w-3.5 text-muted-foreground/40"
                                />
                            </span>
                        </div>
                        <p
                            v-if="errors.lot_id"
                            class="mt-1 text-[10px] text-destructive"
                        >
                            {{ errors.lot_id }}
                        </p>
                    </div>

                    <!-- Scan Only toggle — 1/12 -->
                    <div
                        class="col-span-1 flex flex-col items-center justify-end gap-1 pb-1"
                    >
                        <span
                            class="text-center text-[10px] leading-tight font-semibold text-muted-foreground"
                            >Scan<br />Only</span
                        >
                        <button
                            type="button"
                            class="relative h-5 w-9 rounded-full transition-colors duration-200 focus:outline-none"
                            :class="
                                scanOnly
                                    ? 'bg-amber-500'
                                    : 'bg-slate-300 dark:bg-slate-600'
                            "
                            @click="scanOnly = !scanOnly"
                        >
                            <span
                                class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform duration-200"
                                :class="
                                    scanOnly ? 'translate-x-4' : 'translate-x-0'
                                "
                            ></span>
                        </button>
                    </div>

                    <!-- LIPAS — 1/12 -->
                    <div class="col-span-1">
                        <label
                            class="mb-1 block text-xs font-semibold text-foreground"
                        >
                            LIPAS <span class="text-destructive">*</span>
                        </label>
                        <select
                            v-if="scanOnly"
                            v-model="form.lipas_yn"
                            class="h-9 w-full rounded-lg border bg-background px-2 text-sm font-semibold focus:ring-2 focus:outline-none"
                            :class="
                                errors.lipas_yn
                                    ? 'border-destructive focus:ring-destructive/20'
                                    : 'border-amber-400 focus:border-amber-500 focus:ring-amber-500/20'
                            "
                            @change="delete errors.value.lipas_yn"
                        >
                            <option value="">—</option>
                            <option value="Y">Y</option>
                            <option value="N">N</option>
                        </select>
                        <div
                            v-else
                            class="flex h-9 items-center justify-center gap-1.5 rounded-lg border bg-muted/40 px-2 text-sm"
                            :class="
                                errors.lipas_yn
                                    ? 'border-destructive'
                                    : 'border-input'
                            "
                        >
                            <template v-if="form.lipas_yn">
                                <span
                                    class="h-2 w-2 shrink-0 rounded-full"
                                    :class="
                                        form.lipas_yn === 'Y'
                                            ? 'bg-emerald-500'
                                            : 'bg-slate-400'
                                    "
                                ></span>
                                <span
                                    class="text-xs font-bold"
                                    :class="
                                        form.lipas_yn === 'Y'
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-slate-500 dark:text-slate-400'
                                    "
                                    >{{ form.lipas_yn }}</span
                                >
                            </template>
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                        <p
                            v-if="errors.lipas_yn"
                            class="mt-1 text-[10px] text-destructive"
                        >
                            {{ errors.lipas_yn }}
                        </p>
                    </div>

                    <!-- Model — 4/12 -->
                    <div class="col-span-4">
                        <label
                            class="mb-1 block text-xs font-semibold text-foreground"
                            >Model</label
                        >
                        <input
                            v-if="scanOnly"
                            :value="form.model"
                            type="text"
                            maxlength="15"
                            placeholder="CL_____________B"
                            class="h-9 w-full rounded-lg border bg-background px-3 font-mono text-sm placeholder:text-muted-foreground/40 focus:ring-2 focus:outline-none"
                            :class="
                                errors.model
                                    ? 'border-destructive focus:ring-destructive/20'
                                    : 'border-amber-400 focus:border-amber-500 focus:ring-amber-500/20'
                            "
                            @input="
                                (e) => {
                                    form.model = (
                                        e.target as HTMLInputElement
                                    ).value.toUpperCase();
                                    delete errors.value.model;
                                }
                            "
                            @blur="onModelBlur"
                        />
                        <div
                            v-else
                            class="flex h-9 items-center rounded-lg border bg-muted/40 px-3 text-sm"
                            :class="
                                errors.model
                                    ? 'border-destructive'
                                    : 'border-input'
                            "
                        >
                            <span
                                v-if="form.model"
                                class="truncate text-foreground"
                                >{{ form.model }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                        <p
                            v-if="errors.model"
                            class="mt-1 text-[10px] text-destructive"
                        >
                            {{ errors.model }}
                        </p>
                    </div>

                    <!-- Qty — 2/12 -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-xs font-semibold text-foreground"
                            >Qty</label
                        >
                        <input
                            v-if="scanOnly"
                            :value="lotQtyDisplay"
                            type="text"
                            inputmode="numeric"
                            placeholder="0"
                            class="h-9 w-full rounded-lg border border-amber-400 bg-background px-3 text-sm font-medium tabular-nums focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                            @input="onLotQtyInput"
                        />
                        <div
                            v-else
                            class="flex h-9 items-center rounded-lg border bg-muted/40 px-3 text-sm font-medium"
                            :class="
                                errors.lot_qty
                                    ? 'border-destructive'
                                    : 'border-input'
                            "
                        >
                            <span
                                v-if="form.lot_qty != null"
                                class="text-foreground"
                                >{{ form.lot_qty.toLocaleString() }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                        <p
                            v-if="errors.lot_qty"
                            class="mt-1 text-[10px] text-destructive"
                        >
                            {{ errors.lot_qty }}
                        </p>
                    </div>

                    <!-- Work Type — 2/12 -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-xs font-semibold text-foreground"
                            >Work Type</label
                        >
                        <select
                            v-if="scanOnly"
                            v-model="form.work_type"
                            class="h-9 w-full rounded-lg border border-amber-400 bg-background px-3 text-sm text-foreground focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                        >
                            <option value="">—</option>
                            <option value="NORMAL">NORMAL</option>
                            <option value="PROCESS RW">PROCESS RW</option>
                            <option value="WH REWORK">WH REWORK</option>
                            <option value="OI REWORK">OI REWORK</option>
                        </select>
                        <div
                            v-else
                            class="flex h-9 items-center rounded-lg border bg-muted/40 px-3 text-sm"
                            :class="
                                errors.work_type
                                    ? 'border-destructive'
                                    : 'border-input'
                            "
                        >
                            <span
                                v-if="form.work_type"
                                class="truncate text-foreground"
                                >{{ form.work_type }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                        <p
                            v-if="errors.work_type"
                            class="mt-1 text-[10px] text-destructive"
                        >
                            {{ errors.work_type }}
                        </p>
                    </div>
                </div>

                <!-- Thin divider -->
                <div class="h-px bg-border/40"></div>

                <!-- ROW 2: QC Result | Inspected Bin | QC NG Bin -->
                <div class="grid grid-cols-3 gap-4">
                    <!-- QC Result: OK / NG -->
                    <div>
                        <label
                            class="mb-2 block text-xs font-semibold text-foreground"
                        >
                            QC Result <span class="text-destructive">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="rounded-xl border py-2.5 text-sm font-bold tracking-wide transition-all duration-150"
                                :class="
                                    form.qc_result === 'OK'
                                        ? 'border-emerald-500 bg-emerald-500 text-white shadow-lg shadow-emerald-500/30'
                                        : 'border-border bg-background text-muted-foreground hover:border-emerald-400 hover:text-emerald-600'
                                "
                                @click="setQcResult('OK')"
                            >
                                OK
                            </button>
                            <button
                                type="button"
                                class="rounded-xl border py-2.5 text-sm font-bold tracking-wide transition-all duration-150"
                                :class="
                                    form.qc_result === 'NG'
                                        ? 'border-red-500 bg-red-500 text-white shadow-lg shadow-red-500/30'
                                        : 'border-border bg-background text-muted-foreground hover:border-red-400 hover:text-red-600'
                                "
                                @click="setQcResult('NG')"
                            >
                                NG
                            </button>
                        </div>
                        <p
                            v-if="errors.qc_result"
                            class="mt-1 text-[10px] text-destructive"
                        >
                            {{ errors.qc_result }}
                        </p>
                    </div>

                    <!-- Inspected Bin: Main / RR / LY multi -->
                    <div>
                        <label
                            class="mb-2 block text-xs font-semibold text-foreground"
                            >Inspected Bin</label
                        >
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="opt in ['Main', 'RR', 'LY']"
                                :key="opt"
                                type="button"
                                class="rounded-xl border py-2.5 text-xs font-bold tracking-wide transition-all duration-150"
                                :class="
                                    form.inspected_bin_result.includes(opt)
                                        ? opt === 'Main'
                                            ? 'border-blue-500 bg-blue-500 text-white shadow-md shadow-blue-500/30'
                                            : opt === 'RR'
                                              ? 'border-orange-500 bg-orange-500 text-white shadow-md shadow-orange-500/30'
                                              : 'border-purple-500 bg-purple-500 text-white shadow-md shadow-purple-500/30'
                                        : 'border-border bg-background text-muted-foreground hover:border-muted-foreground/40 hover:text-foreground'
                                "
                                @click="toggleInspectedBin(opt)"
                            >
                                {{ opt }}
                            </button>
                        </div>
                        <p
                            v-if="errors.inspected_bin_result"
                            class="mt-1 text-[10px] text-destructive"
                        >
                            {{ errors.inspected_bin_result }}
                        </p>
                    </div>

                    <!-- QC NG Bin: Main / RR / LY multi -->
                    <div>
                        <label
                            class="mb-2 block text-xs font-semibold text-foreground"
                        >
                            QC NG Bin
                            <span
                                v-if="form.qc_result === 'NG'"
                                class="text-destructive"
                                >*</span
                            >
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="opt in ['Main', 'RR', 'LY']"
                                :key="opt"
                                type="button"
                                class="rounded-xl border py-2.5 text-xs font-bold tracking-wide transition-all duration-150"
                                :disabled="form.qc_result !== 'NG'"
                                :class="
                                    form.qc_ng_part.includes(opt)
                                        ? opt === 'Main'
                                            ? 'border-red-500 bg-red-500 text-white shadow-md shadow-red-500/30'
                                            : opt === 'RR'
                                              ? 'border-orange-500 bg-orange-500 text-white shadow-md shadow-orange-500/30'
                                              : 'border-yellow-500 bg-yellow-500 text-white shadow-md shadow-yellow-500/30'
                                        : form.qc_result !== 'NG'
                                          ? 'cursor-not-allowed border-border bg-muted/30 text-muted-foreground/30'
                                          : 'border-border bg-background text-muted-foreground hover:border-muted-foreground/40 hover:text-foreground'
                                "
                                @click="toggleQcNgPart(opt)"
                            >
                                {{ opt }}
                            </button>
                        </div>
                        <p
                            v-if="errors.qc_ng_part"
                            class="mt-1 text-[10px] text-destructive"
                        >
                            {{ errors.qc_ng_part }}
                        </p>
                    </div>
                </div>

                <!-- Thin divider -->
                <div class="h-px bg-border/40"></div>

                <!-- ROW 3: Insp. Times | Scan SPL Qty | Defect Code(s) | Defect Flow -->
                <div class="grid grid-cols-12 items-start gap-3">
                    <!-- Insp. Times — narrow (2 digits) -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-xs font-semibold text-foreground"
                        >
                            Insp. Times <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model.number="form.inspection_times"
                            type="number"
                            min="1"
                            max="99"
                            maxlength="2"
                            placeholder="—"
                            class="h-9 w-full rounded-lg border bg-background px-3 text-center text-sm font-semibold focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
                            :class="
                                errors.inspection_times
                                    ? 'border-destructive'
                                    : 'border-input focus:border-teal-500'
                            "
                            @input="onInspTimesInput"
                        />
                        <p
                            v-if="errors.inspection_times"
                            class="mt-1 text-[10px] text-destructive"
                        >
                            {{ errors.inspection_times }}
                        </p>
                    </div>

                    <!-- Scan SPL Qty — narrow (5 digits) -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-xs font-semibold text-foreground"
                            >SPL Qty
                            <span class="text-destructive">*</span></label
                        >
                        <input
                            :value="splQtyDisplay"
                            type="text"
                            inputmode="numeric"
                            placeholder="—"
                            class="h-9 w-full rounded-lg border bg-background px-3 text-center text-sm font-semibold tabular-nums focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
                            :class="
                                errors.inspection_spl
                                    ? 'border-destructive'
                                    : 'border-input focus:border-teal-500'
                            "
                            @input="onSplQtyInput"
                        />
                        <p
                            v-if="errors.inspection_spl"
                            class="mt-1 text-[10px] text-destructive"
                        >
                            {{ errors.inspection_spl }}
                        </p>
                    </div>

                    <!-- Defect Code(s) — multi tag input, wide -->
                    <div class="col-span-6">
                        <label
                            class="mb-1 block text-xs font-semibold text-foreground"
                            >Defect Code(s)</label
                        >
                        <div class="relative">
                            <div
                                class="flex min-h-9 w-full flex-wrap items-center gap-1.5 rounded-lg border bg-background px-2 py-1 focus-within:ring-2 focus-within:ring-teal-500/20"
                                :class="
                                    errors.defect_codes
                                        ? 'border-destructive'
                                        : 'border-input focus-within:border-teal-500'
                                "
                            >
                                <span
                                    v-for="code in form.defect_codes"
                                    :key="code"
                                    class="inline-flex items-center gap-1 rounded-md bg-red-500/10 px-2 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-red-500/20 dark:text-red-400"
                                >
                                    {{ code }}
                                    <button
                                        type="button"
                                        class="leading-none text-red-500/60 hover:text-red-600"
                                        @mousedown.prevent="
                                            removeDefectTag(code)
                                        "
                                    >
                                        ×
                                    </button>
                                </span>
                                <input
                                    :value="form.defect_input"
                                    type="text"
                                    class="min-w-[80px] flex-1 bg-transparent text-sm uppercase outline-none placeholder:font-normal placeholder:text-muted-foreground/50 placeholder:normal-case"
                                    placeholder="Type code + Enter"
                                    autocomplete="off"
                                    @input="onDefectRawInput"
                                    @keydown="onDefectKeydown"
                                    @blur="closeDefectDropdown"
                                />
                            </div>
                            <ul
                                v-if="defectOpen && defectSuggestions.length"
                                class="absolute top-full left-0 z-50 mt-1 max-h-44 w-full overflow-auto rounded-lg border border-border bg-popover shadow-xl"
                            >
                                <li
                                    v-for="opt in defectSuggestions"
                                    :key="opt.defect_code"
                                    class="flex cursor-pointer items-center gap-3 px-3 py-2 text-xs hover:bg-muted"
                                    @mousedown.prevent="selectDefect(opt)"
                                >
                                    <span
                                        class="w-16 shrink-0 font-bold text-foreground"
                                        >{{ opt.defect_code }}</span
                                    >
                                    <span
                                        class="truncate text-muted-foreground"
                                        >{{ opt.defect_name }}</span
                                    >
                                    <span
                                        class="ml-auto shrink-0 rounded bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                                        >{{ opt.defect_class }}</span
                                    >
                                </li>
                            </ul>
                            <div
                                v-else-if="
                                    defectOpen &&
                                    !defectSuggestions.length &&
                                    form.defect_input.trim()
                                "
                                class="absolute top-full left-0 z-50 mt-1 w-full rounded-lg border border-border bg-popover px-3 py-2.5 text-xs text-muted-foreground shadow-xl"
                            >
                                No matching defect codes found
                            </div>
                        </div>
                        <p
                            v-if="errors.defect_codes"
                            class="mt-1 text-[10px] text-destructive"
                        >
                            {{ errors.defect_codes }}
                        </p>
                    </div>

                    <!-- Defect Flow — auto badge -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-xs font-semibold text-foreground"
                            >Defect Flow</label
                        >
                        <div
                            class="flex h-9 items-center rounded-lg border border-input bg-muted/40 px-3 text-sm"
                        >
                            <span
                                v-if="form.defect_flow"
                                class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset"
                                :class="
                                    form.defect_flow === 'QC Analysis'
                                        ? 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-950/30 dark:text-indigo-400'
                                        : 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-950/30 dark:text-purple-400'
                                "
                                >{{ form.defect_flow }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground/50"
                                >Auto</span
                            >
                        </div>
                    </div>
                </div>
                <!-- end ROW 3 grid -->

                <!-- Thin divider -->
                <div class="h-px bg-border/40"></div>

                <!-- ROW 4: Remarks (full width, 3-row textarea) -->
                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <label class="text-xs font-semibold text-foreground"
                            >Remarks</label
                        >
                    </div>
                    <textarea
                        v-model="form.remarks"
                        rows="3"
                        placeholder="Auto-generated from selections above..."
                        class="w-full resize-none rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground/50 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
                        @input="onRemarksInput"
                    ></textarea>
                </div>
                <!-- end ROW 4 grid -->
            </div>

            <!-- Footer -->
            <div
                class="flex shrink-0 items-center justify-between border-t border-border/50 bg-muted/10 px-6 py-4"
            >
                <p class="text-[10px] text-muted-foreground/50">
                    <kbd
                        class="rounded border border-border bg-muted px-1 py-0.5 font-mono text-[10px]"
                        >Alt+S</kbd
                    >
                    to save
                </p>
                <div class="flex gap-2.5">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center rounded-lg border border-border bg-background px-5 text-sm font-semibold text-foreground transition-all hover:bg-muted"
                        @click="close"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center rounded-lg bg-gradient-to-r from-teal-600 to-teal-500 px-6 text-sm font-semibold text-white shadow-lg shadow-teal-500/25 transition-all hover:from-teal-700 hover:to-teal-600 disabled:pointer-events-none disabled:opacity-50"
                        :disabled="submitting"
                        @click="save"
                    >
                        <Loader2
                            v-if="submitting"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        {{ isEditMode ? 'Update' : 'Save Record' }}
                    </button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
