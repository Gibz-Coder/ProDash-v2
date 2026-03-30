<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import axios from 'axios';
import { Minus, Plus } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface Props {
    open: boolean;
    editRecord?: EndlineRecord | null;
}

interface EndlineRecord {
    id: number;
    lot_id: string;
    model: string | null;
    lot_qty: number | null;
    lipas_yn: string | null;
    qc_result: string | null;
    qc_defect: string | null;
    defect_class: string | null;
    qc_ana_start: string | null;
    qc_ana_result: string | null;
    qc_ana_completed_at: string | null;
    vi_techl_start: string | null;
    vi_techl_result: string | null;
    vi_techl_completed_at: string | null;
    work_type: string | null;
    final_decision: string | null;
    remarks: string | null;
    inspection_times: number | null;
}

interface RowForm {
    lot_id: string;
    qc_ng: string[]; // multi-select → joined as comma-separated in qc_result
    defect_codes: string[]; // array of codes → joined as comma-separated in qc_defect
    defect_input: string; // current typing buffer
    defect_flow: string; // auto-resolved from defect codes → stored in defect_class col
    final_decision: string;
    model: string;
    lot_qty: number | null;
    work_type: string;
    lipas_yn: string;
    qc_ana_start: string | null; // auto-stamped when defect_flow = 'QC Analysis'
    vi_techl_start: string | null; // auto-stamped when defect_flow = "Tech'l Verification"
    inspection_times: number | null;
    _loading: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'saved'): void;
}>();

const DEFAULT_ROWS = 10;

const submitting = ref(false);
const rowErrors = ref<Record<number, Record<string, string>>>({});
const isEditMode = ref(false);
const editId = ref<number | null>(null);

const emptyRow = (): RowForm => ({
    lot_id: '',
    qc_ng: [],
    defect_codes: [],
    defect_input: '',
    defect_flow: '',
    final_decision: 'Pending',
    model: '',
    lot_qty: null,
    work_type: '',
    lipas_yn: '',
    qc_ana_start: null,
    vi_techl_start: null,
    inspection_times: null,
    _loading: false,
});

const rows = ref<RowForm[]>(Array.from({ length: DEFAULT_ROWS }, emptyRow));

watch(
    () => props.open,
    (val) => {
        if (!val) return;
        rowErrors.value = {};
        if (props.editRecord) {
            isEditMode.value = true;
            editId.value = props.editRecord.id;
            rows.value = [
                {
                    lot_id: props.editRecord.lot_id,
                    qc_ng: props.editRecord.qc_result
                        ? props.editRecord.qc_result
                              .split(',')
                              .map((s) => s.trim())
                              .filter(Boolean)
                        : [],
                    defect_codes: props.editRecord.qc_defect
                        ? props.editRecord.qc_defect
                              .split(',')
                              .map((s) => s.trim())
                              .filter(Boolean)
                        : [],
                    defect_input: '',
                    defect_flow: props.editRecord.defect_class ?? '',
                    final_decision: props.editRecord.final_decision ?? '',
                    model: props.editRecord.model ?? '',
                    lot_qty: props.editRecord.lot_qty,
                    work_type: props.editRecord.work_type ?? '',
                    lipas_yn: props.editRecord.lipas_yn ?? '',
                    qc_ana_start: props.editRecord.qc_ana_start ?? null,
                    vi_techl_start: props.editRecord.vi_techl_start ?? null,
                    inspection_times: props.editRecord.inspection_times ?? null,
                    _loading: false,
                },
            ];
        } else {
            isEditMode.value = false;
            editId.value = null;
            rows.value = Array.from({ length: DEFAULT_ROWS }, emptyRow);
        }
    },
);

function addRow() {
    rows.value.push(emptyRow());
}

function removeRow(i: number) {
    if (rows.value.length > 1) rows.value.splice(i, 1);
}

// Refs for lot_id inputs so we can focus next row
const lotInputRefs = ref<HTMLInputElement[]>([]);

// Refs for defect inputs so we can navigate rows with arrow keys
const defectInputRefs = ref<HTMLInputElement[]>([]);

// Defect code autocomplete state per row
interface DefectOption {
    defect_code: string;
    defect_name: string;
    defect_class: string;
    defect_flow: string;
}
const defectSuggestions = ref<Record<number, DefectOption[]>>({});
const defectOpen = ref<Record<number, boolean>>({});
let defectTimer: ReturnType<typeof setTimeout> | null = null;

async function onDefectInput(i: number, val: string) {
    if (defectTimer) clearTimeout(defectTimer);
    const token = val.trim();
    if (!token) {
        defectSuggestions.value[i] = [];
        defectOpen.value[i] = false;
        return;
    }
    defectTimer = setTimeout(async () => {
        const { data } = await axios.get<DefectOption[]>(
            '/api/endline-delay/defect-codes',
            { params: { q: token } },
        );
        defectSuggestions.value[i] = data;
        defectOpen.value[i] = data.length > 0;
    }, 200);
}

// Cache of defect code → { defect_flow, defect_class } for flow resolution
const defectCache = ref<
    Record<string, { defect_flow: string; defect_class: string }>
>({});

async function commitDefectInput(i: number) {
    const val = rows.value[i].defect_input
        .trim()
        .replace(/,$/, '')
        .toUpperCase();
    if (val && !rows.value[i].defect_codes.includes(val)) {
        rows.value[i].defect_codes.push(val);
        // Fetch and cache if not already cached
        if (!defectCache.value[val]) {
            try {
                const { data } = await axios.get<DefectOption[]>(
                    '/api/endline-delay/defect-codes',
                    { params: { q: val } },
                );
                // exact match only
                const match = data.find((d) => d.defect_code === val);
                if (match) {
                    defectCache.value[val] = {
                        defect_flow: match.defect_flow,
                        defect_class: match.defect_class,
                    };
                }
            } catch {
                /* ignore */
            }
        }
        resolveDefectFlow(rows.value[i]);
    }
    rows.value[i].defect_input = '';
    defectOpen.value[i] = false;
    defectSuggestions.value[i] = [];
}

function onDefectKeydown(e: KeyboardEvent, i: number) {
    const row = rows.value[i];
    if (e.key === ',' || e.key === 'Enter') {
        e.preventDefault();
        commitDefectInput(i);
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        const next = defectInputRefs.value[i + 1];
        if (next) next.focus();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        const prev = defectInputRefs.value[i - 1];
        if (prev) prev.focus();
    } else if (
        e.key === 'Backspace' &&
        row.defect_input === '' &&
        row.defect_codes.length
    ) {
        row.defect_codes.pop();
    }
}

function resolveDefectFlow(row: RowForm) {
    if (!row.defect_codes.length) {
        row.defect_flow = '';
        row.qc_ana_start = null;
        row.vi_techl_start = null;
        return;
    }
    const priority = row.defect_codes.find(
        (c) => defectCache.value[c]?.defect_class === 'QC Analysis',
    );
    const key = priority ?? row.defect_codes.find((c) => defectCache.value[c]);
    const flow = key ? (defectCache.value[key]?.defect_flow ?? '') : '';
    row.defect_flow = flow;
    // start timestamps are set server-side when user clicks Start — never auto-stamped here
}

function selectDefect(i: number, opt: DefectOption) {
    const row = rows.value[i];
    if (!row.defect_codes.includes(opt.defect_code)) {
        row.defect_codes.push(opt.defect_code);
    }
    // Cache the lookup result
    defectCache.value[opt.defect_code] = {
        defect_flow: opt.defect_flow,
        defect_class: opt.defect_class,
    };
    row.defect_input = '';
    resolveDefectFlow(row);
    defectOpen.value[i] = false;
    defectSuggestions.value[i] = [];
}

function removeDefectTag(i: number, code: string) {
    rows.value[i].defect_codes = rows.value[i].defect_codes.filter(
        (c) => c !== code,
    );
    resolveDefectFlow(rows.value[i]);
}

async function closeDefectDropdown(i: number) {
    // small delay so mousedown on suggestion fires first
    await new Promise((r) => setTimeout(r, 150));
    await commitDefectInput(i);
    defectOpen.value[i] = false;
}

async function lookupLot(row: RowForm) {
    const lotId = row.lot_id.trim();
    if (!lotId) return;
    row._loading = true;
    try {
        const { data } = await axios.get('/api/endline-delay/lot-lookup', {
            params: { lot_id: lotId },
        });
        if (data) {
            row.model = data.model_15 ?? row.model;
            row.lot_qty = data.lot_qty ?? row.lot_qty;
            row.work_type = data.work_type ?? row.work_type;
            row.lipas_yn = data.lipas_yn ?? row.lipas_yn;
        }
    } catch {
        // silently ignore — lot may not exist in wip
    } finally {
        row._loading = false;
    }
}

function onLotIdEnter(e: KeyboardEvent, i: number) {
    e.preventDefault();
    lookupLot(rows.value[i]);
    // Focus next row's lot_id input
    const next = lotInputRefs.value[i + 1];
    if (next) {
        next.focus();
    } else {
        // Add a new row and focus it next tick
        addRow();
        setTimeout(() => {
            const newInput = lotInputRefs.value[rows.value.length - 1];
            if (newInput) newInput.focus();
        }, 50);
    }
}

function toggleQcNg(row: RowForm, opt: string) {
    // Clear any validation error for this row on interaction
    const idx = rows.value.indexOf(row);
    if (rowErrors.value[idx]?.qc_ng) {
        delete rowErrors.value[idx].qc_ng;
    }
    if (opt === 'OK') {
        // OK is exclusive — select only OK or deselect if already selected
        row.qc_ng = row.qc_ng.includes('OK') ? [] : ['OK'];
        return;
    }
    // For Main/RR/LY: toggle and clear OK if present
    const without = row.qc_ng.filter((v) => v !== 'OK');
    if (without.includes(opt)) {
        row.qc_ng = without.filter((v) => v !== opt);
    } else {
        row.qc_ng = [...without, opt];
    }
}

function rowToPayload(row: RowForm) {
    return {
        lot_id: row.lot_id || null,
        qc_result: row.qc_ng.length ? row.qc_ng.join(', ') : null,
        qc_defect: row.defect_codes.length ? row.defect_codes.join(', ') : null,
        defect_class: row.defect_flow || null,
        final_decision: row.final_decision || null,
        model: row.model || null,
        lot_qty: row.lot_qty,
        work_type: row.work_type || null,
        lipas_yn: row.lipas_yn || null,
        inspection_times: row.inspection_times ?? null,
    };
}

async function save() {
    submitting.value = true;
    rowErrors.value = {};
    try {
        if (isEditMode.value && editId.value) {
            await axios.put(
                `/api/endline-delay/${editId.value}`,
                rowToPayload(rows.value[0]),
            );
        } else {
            const filled = rows.value.filter((r) => r.lot_id.trim() !== '');
            if (filled.length === 0) {
                submitting.value = false;
                return;
            }
            // Validate: every filled row must have at least one QC NG selected
            const invalidIndices = rows.value.reduce<number[]>(
                (acc, r, idx) => {
                    if (r.lot_id.trim() !== '' && r.qc_ng.length === 0)
                        acc.push(idx);
                    return acc;
                },
                [],
            );
            if (invalidIndices.length > 0) {
                invalidIndices.forEach((idx) => {
                    rowErrors.value[idx] = {
                        qc_ng: 'Select QC Result before saving!',
                    };
                });
                submitting.value = false;
                return;
            }
            await Promise.all(
                filled.map((row) =>
                    axios.post('/api/endline-delay', rowToPayload(row)),
                ),
            );
        }
        emit('saved');
        emit('update:open', false);
    } catch (err: any) {
        if (err.response?.status === 422) {
            rowErrors.value[0] = {};
            const raw = err.response.data.errors as Record<string, string[]>;
            Object.keys(raw).forEach(
                (k) => (rowErrors.value[0][k] = raw[k][0]),
            );
        }
    } finally {
        submitting.value = false;
    }
}

function close() {
    emit('update:open', false);
}

function onAltS(e: KeyboardEvent) {
    if (e.altKey && (e.key === 's' || e.key === 'S') && props.open) {
        e.preventDefault();
        save();
    }
}

onMounted(() => document.addEventListener('keydown', onAltS));
onBeforeUnmount(() => document.removeEventListener('keydown', onAltS));

const filledCount = () =>
    isEditMode.value
        ? 1
        : rows.value.filter((r) => r.lot_id.trim() !== '').length;
</script>

<template>
    <Dialog
        :open="open"
        @update:open="(value: boolean) => emit('update:open', value)"
    >
        <DialogContent
            class="flex !max-h-[90vh] !w-[calc(100vw-24rem)] !max-w-[calc(100vw-24rem)] flex-col gap-0 overflow-hidden p-0"
        >
            <DialogHeader
                class="border-b border-border/50 bg-gradient-to-r from-muted/30 to-muted/10 px-6 py-4"
            >
                <DialogTitle>{{
                    isEditMode ? 'Edit Record' : 'Add Records'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        isEditMode
                            ? 'Update endline delay details'
                            : 'Enter lot entries — only rows with a Lot No will be saved'
                    }}
                </DialogDescription>
            </DialogHeader>

            <!-- Table -->
            <div class="flex-1 overflow-auto">
                <table class="w-full border-collapse text-sm">
                    <thead
                        class="sticky top-0 z-10 bg-muted/80 backdrop-blur-sm"
                    >
                        <tr class="border-b border-border">
                            <th
                                class="w-10 px-3 py-2.5 text-center text-xs font-bold tracking-wide text-muted-foreground uppercase"
                            >
                                No.
                            </th>
                            <th
                                class="min-w-[70px] px-3 py-2.5 text-left text-xs font-bold tracking-wide text-muted-foreground uppercase"
                            >
                                Lot No <span class="text-destructive">*</span>
                            </th>
                            <th
                                class="min-w-[130px] px-3 py-2.5 text-left text-xs font-bold tracking-wide text-muted-foreground uppercase"
                            >
                                QC Result
                            </th>
                            <th
                                class="min-w-[60px] px-3 py-2.5 text-left text-xs font-bold tracking-wide text-muted-foreground uppercase"
                            >
                                Insp. Times
                            </th>
                            <th
                                class="min-w-[140px] px-3 py-2.5 text-left text-xs font-bold tracking-wide text-muted-foreground uppercase"
                            >
                                Defect (Code)
                            </th>
                            <th
                                class="min-w-[120px] px-3 py-2.5 text-left text-xs font-bold tracking-wide text-muted-foreground uppercase"
                            >
                                Defect Flow
                            </th>
                            <th
                                class="min-w-[130px] px-3 py-2.5 text-left text-xs font-bold tracking-wide text-muted-foreground uppercase"
                            >
                                Model
                            </th>
                            <th
                                class="min-w-[70px] px-3 py-2.5 text-left text-xs font-bold tracking-wide text-muted-foreground uppercase"
                            >
                                Qty
                            </th>
                            <th
                                class="min-w-[110px] px-3 py-2.5 text-left text-xs font-bold tracking-wide text-muted-foreground uppercase"
                            >
                                Worktype
                            </th>
                            <th
                                class="min-w-[70px] px-3 py-2.5 text-left text-xs font-bold tracking-wide text-muted-foreground uppercase"
                            >
                                LIPAS
                            </th>
                            <th v-if="!isEditMode" class="w-8 px-2 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/40">
                        <tr
                            v-for="(row, i) in rows"
                            :key="i"
                            class="transition-colors hover:bg-muted/20"
                            :class="
                                i % 2 === 0 ? 'bg-background' : 'bg-muted/10'
                            "
                        >
                            <!-- No. -->
                            <td
                                class="px-3 py-1.5 text-center text-xs font-medium text-muted-foreground"
                            >
                                {{ i + 1 }}
                            </td>

                            <!-- Lot No -->
                            <td class="px-2 py-1.5">
                                <div class="relative">
                                    <input
                                        :ref="
                                            (el) => {
                                                if (el)
                                                    lotInputRefs[i] =
                                                        el as HTMLInputElement;
                                            }
                                        "
                                        v-model="row.lot_id"
                                        type="text"
                                        class="h-8 w-full rounded border border-input bg-background px-2 text-xs uppercase placeholder:text-muted-foreground/50 placeholder:normal-case focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                        :class="{
                                            'border-destructive':
                                                rowErrors[i]?.lot_id,
                                        }"
                                        placeholder="LOT-001"
                                        maxlength="7"
                                        @input="
                                            row.lot_id = (
                                                $event.target as HTMLInputElement
                                            ).value.toUpperCase()
                                        "
                                        @keydown.enter="onLotIdEnter($event, i)"
                                        @blur="lookupLot(row)"
                                    />
                                    <span
                                        v-if="row._loading"
                                        class="absolute top-1/2 right-1.5 -translate-y-1/2"
                                    >
                                        <span
                                            class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-primary border-r-transparent"
                                        ></span>
                                    </span>
                                </div>
                            </td>

                            <!-- QC NG — multi-select toggle buttons (OK is exclusive) -->
                            <td class="px-2 py-1.5">
                                <div class="flex items-center gap-1.5">
                                    <button
                                        v-for="opt in [
                                            'Main',
                                            'RR',
                                            'LY',
                                            'OK',
                                        ]"
                                        :key="opt"
                                        type="button"
                                        class="rounded border px-2 py-0.5 text-xs font-semibold transition-all"
                                        :class="
                                            row.qc_ng.includes(opt)
                                                ? {
                                                      'border-red-500 bg-red-500 text-white shadow-sm':
                                                          opt === 'Main',
                                                      'border-orange-500 bg-orange-500 text-white shadow-sm':
                                                          opt === 'RR',
                                                      'border-yellow-500 bg-yellow-500 text-white shadow-sm':
                                                          opt === 'LY',
                                                      'border-emerald-500 bg-emerald-500 text-white shadow-sm':
                                                          opt === 'OK',
                                                  }
                                                : 'border-border bg-transparent text-muted-foreground/50 hover:border-muted-foreground/40 hover:text-muted-foreground'
                                        "
                                        @click="toggleQcNg(row, opt)"
                                    >
                                        {{ opt }}
                                    </button>
                                </div>
                                <p
                                    v-if="rowErrors[i]?.qc_ng"
                                    class="mt-0.5 text-[10px] text-destructive"
                                >
                                    {{ rowErrors[i].qc_ng }}
                                </p>
                            </td>

                            <!-- Inspection Times -->
                            <td class="px-2 py-1.5">
                                <input
                                    v-model.number="row.inspection_times"
                                    type="number"
                                    min="1"
                                    max="255"
                                    class="h-8 w-full rounded border border-input bg-background px-2 text-center text-xs placeholder:text-muted-foreground/50 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                    placeholder="—"
                                />
                            </td>

                            <!-- Defect Code -->
                            <td class="px-2 py-1.5">
                                <div class="relative">
                                    <!-- Tag container -->
                                    <div
                                        class="flex min-h-8 w-full flex-wrap items-center gap-1 rounded border border-input bg-background px-1.5 py-1 focus-within:border-primary focus-within:ring-1 focus-within:ring-primary"
                                    >
                                        <span
                                            v-for="code in row.defect_codes"
                                            :key="code"
                                            class="inline-flex items-center gap-0.5 rounded bg-primary/10 px-1.5 py-0.5 text-xs font-medium text-primary"
                                        >
                                            {{ code }}
                                            <button
                                                type="button"
                                                class="ml-0.5 leading-none text-primary/60 hover:text-primary"
                                                @mousedown.prevent="
                                                    removeDefectTag(i, code)
                                                "
                                            >
                                                ×
                                            </button>
                                        </span>
                                        <input
                                            v-model="row.defect_input"
                                            :ref="
                                                (el) => {
                                                    if (el)
                                                        defectInputRefs[i] =
                                                            el as HTMLInputElement;
                                                }
                                            "
                                            type="text"
                                            class="min-w-[60px] flex-1 bg-transparent text-sm uppercase outline-none placeholder:text-muted-foreground/50 placeholder:normal-case"
                                            placeholder="Type + Enter"
                                            autocomplete="off"
                                            @input="
                                                row.defect_input = (
                                                    $event.target as HTMLInputElement
                                                ).value.toUpperCase();
                                                onDefectInput(
                                                    i,
                                                    row.defect_input,
                                                );
                                            "
                                            @keydown="
                                                onDefectKeydown($event, i)
                                            "
                                            @blur="closeDefectDropdown(i)"
                                        />
                                    </div>
                                    <!-- Suggestions dropdown -->
                                    <ul
                                        v-if="
                                            defectOpen[i] &&
                                            defectSuggestions[i]?.length
                                        "
                                        class="absolute top-full left-0 z-50 mt-0.5 max-h-48 w-64 overflow-auto rounded-md border border-border bg-popover shadow-lg"
                                    >
                                        <li
                                            v-for="opt in defectSuggestions[i]"
                                            :key="opt.defect_code"
                                            class="flex cursor-pointer items-center justify-between px-3 py-1.5 text-xs hover:bg-muted"
                                            @mousedown.prevent="
                                                selectDefect(i, opt)
                                            "
                                        >
                                            <span
                                                class="font-medium text-foreground"
                                                >{{ opt.defect_code }}</span
                                            >
                                            <span
                                                class="ml-2 truncate text-muted-foreground"
                                                >{{ opt.defect_name }}</span
                                            >
                                        </li>
                                    </ul>
                                </div>
                            </td>

                            <!-- Defect Flow (auto-resolved, read-only) -->
                            <td class="px-2 py-1.5">
                                <div
                                    class="flex h-8 items-center rounded border border-input bg-muted/30 px-2 text-sm text-foreground"
                                >
                                    <span
                                        v-if="row.defect_flow"
                                        class="font-medium"
                                        >{{ row.defect_flow }}</span
                                    >
                                    <span
                                        v-else
                                        class="text-muted-foreground/50"
                                        >Auto</span
                                    >
                                </div>
                            </td>

                            <!-- Model (read-only) -->
                            <td class="px-2 py-1.5">
                                <div
                                    class="flex h-8 items-center rounded border border-input bg-muted/30 px-2 text-sm text-foreground"
                                >
                                    <span v-if="row.model" class="truncate">{{
                                        row.model
                                    }}</span>
                                    <span
                                        v-else
                                        class="text-muted-foreground/40"
                                        >—</span
                                    >
                                </div>
                            </td>

                            <!-- Qty (read-only) -->
                            <td class="px-2 py-1.5">
                                <div
                                    class="flex h-8 items-center rounded border border-input bg-muted/30 px-2 text-sm text-foreground"
                                >
                                    <span v-if="row.lot_qty != null">{{
                                        row.lot_qty.toLocaleString()
                                    }}</span>
                                    <span
                                        v-else
                                        class="text-muted-foreground/40"
                                        >—</span
                                    >
                                </div>
                            </td>

                            <!-- Worktype (read-only) -->
                            <td class="px-2 py-1.5">
                                <div
                                    class="flex h-8 items-center rounded border border-input bg-muted/30 px-2 text-sm text-foreground"
                                >
                                    <span
                                        v-if="row.work_type"
                                        class="truncate"
                                        >{{ row.work_type }}</span
                                    >
                                    <span
                                        v-else
                                        class="text-muted-foreground/40"
                                        >—</span
                                    >
                                </div>
                            </td>

                            <!-- LIPAS (read-only) -->
                            <td class="px-2 py-1.5">
                                <div
                                    class="flex h-8 items-center justify-center rounded border border-input bg-muted/30 px-2 text-sm"
                                >
                                    <span
                                        v-if="row.lipas_yn"
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
                                        :class="
                                            row.lipas_yn === 'Y'
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400'
                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-950/40 dark:text-slate-400'
                                        "
                                        >{{ row.lipas_yn }}</span
                                    >
                                    <span
                                        v-else
                                        class="text-muted-foreground/40"
                                        >—</span
                                    >
                                </div>
                            </td>

                            <!-- Remove -->
                            <td
                                v-if="!isEditMode"
                                class="px-2 py-1.5 text-center"
                            >
                                <button
                                    type="button"
                                    class="flex h-6 w-6 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive disabled:opacity-30"
                                    :disabled="rows.length === 1"
                                    @click="removeRow(i)"
                                >
                                    <Minus class="h-3.5 w-3.5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div
                class="flex flex-shrink-0 items-center justify-between border-t border-border/50 bg-muted/10 px-6 py-3"
            >
                <div class="flex items-center gap-3">
                    <span class="text-xs text-muted-foreground">
                        {{
                            isEditMode
                                ? '1 record'
                                : `${filledCount()} of ${rows.length} rows filled`
                        }}
                    </span>
                    <button
                        v-if="!isEditMode"
                        type="button"
                        class="flex items-center gap-1 rounded border border-dashed border-primary/40 px-3 py-1 text-xs font-medium text-primary transition-colors hover:border-primary hover:bg-primary/5"
                        @click="addRow"
                    >
                        <Plus class="h-3.5 w-3.5" /> Add Row
                    </button>
                </div>
                <div class="flex gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-border bg-background px-5 py-2 text-sm font-semibold text-foreground shadow-sm transition-all hover:bg-muted"
                        @click="close"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-gradient-to-r from-primary to-primary/90 px-6 py-2 text-sm font-semibold text-primary-foreground shadow-lg transition-all hover:shadow-xl hover:shadow-primary/25 disabled:pointer-events-none disabled:opacity-50"
                        :disabled="submitting"
                        @click="save"
                    >
                        <span
                            v-if="submitting"
                            class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                        ></span>
                        {{ isEditMode ? 'Update Record' : 'Save All' }}
                        <span
                            class="ml-2 rounded bg-white/20 px-1.5 py-0.5 font-mono text-xs"
                            >Alt+S</span
                        >
                    </button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
