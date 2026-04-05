<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import axios from 'axios';
import { ImagePlus, Loader2, Microscope, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ViTechnicalRecord {
    id: number;
    lot_id: string;
    model: string | null;
    lot_qty: number | null;
    lipas_yn: string | null;
    work_type: string | null;
    defect_code: string | null;
    technical_start_at: string | null;
    technical_completed_at: string | null;
    technical_result: string | null;
    eqp_number: string | null;
    eqp_maker: string | null;
    remarks: string | null;
    total_tat: number | null;
    inspection_times: number | null;
    inspection_spl: number | null;
    analysis_result: string | null;
    mainlot_result: string | null;
    rr_result: string | null;
    ly_result: string | null;
    inspection_result: string | null;
    defect_details: any[] | null;
}

const RESULT_OPTIONS = [
    'Proceed',
    'Derive RL',
    'Scrap LY',
    'Rework',
    'For Decision',
    'DRB Approval',
] as const;

// ── Defect rows ───────────────────────────────────────────────────────────────
interface DefectOption {
    defect_code: string;
    defect_name: string;
    defect_class: string;
}
interface DefectRow {
    defect_input: string;
    defect_code: string;
    defect_name: string;
    bin: string[]; // multi-select: ['Main'], ['RR','LY'], etc.
    result: string;
    images: (string | null)[]; // 5 slots
    suggestions: DefectOption[];
    dropdownOpen: boolean;
}

const MAX_DEFECT_ROWS = 10;
const MAX_IMAGES_PER_ROW = 5;
const BIN_OPTIONS = ['Main', 'RR', 'LY'] as const;

function makeRow(): DefectRow {
    return {
        defect_input: '',
        defect_code: '',
        defect_name: '',
        bin: [],
        result: '',
        images: Array(MAX_IMAGES_PER_ROW).fill(null),
        suggestions: [],
        dropdownOpen: false,
    };
}

const defectRows = ref<DefectRow[]>([makeRow()]);
let defectTimers: (ReturnType<typeof setTimeout> | null)[] = [];

function addDefectRow() {
    if (defectRows.value.length < MAX_DEFECT_ROWS)
        defectRows.value.push(makeRow());
}

function removeDefectRow(i: number) {
    defectRows.value.splice(i, 1);
    if (defectRows.value.length === 0) defectRows.value.push(makeRow());
}

function onDefectInput(i: number, e: Event) {
    const val = (e.target as HTMLInputElement).value.toUpperCase();
    defectRows.value[i].defect_input = val;
    defectRows.value[i].defect_code = '';
    if (defectTimers[i]) clearTimeout(defectTimers[i]!);
    if (!val.trim()) {
        defectRows.value[i].suggestions = [];
        defectRows.value[i].dropdownOpen = false;
        return;
    }
    defectTimers[i] = setTimeout(async () => {
        const { data } = await axios.get<DefectOption[]>(
            '/api/endline-delay/defect-codes',
            { params: { q: val } },
        );
        defectRows.value[i].suggestions = data;
        defectRows.value[i].dropdownOpen = data.length > 0;
    }, 200);
}

function selectDefect(i: number, opt: DefectOption) {
    defectRows.value[i].defect_code = opt.defect_code;
    defectRows.value[i].defect_name = opt.defect_name;
    defectRows.value[i].defect_input = opt.defect_code;
    defectRows.value[i].dropdownOpen = false;
    defectRows.value[i].suggestions = [];
    // Auto-derive bin from lot bin results if not set
    if (!defectRows.value[i].bin.length && activeLot.value) {
        const ml = activeLot.value.mainlot_result;
        const rr = activeLot.value.rr_result;
        const ly = activeLot.value.ly_result;
        const bins: string[] = [];
        if (ml === 'NG') bins.push('Main');
        if (rr === 'NG') bins.push('RR');
        if (ly === 'NG') bins.push('LY');
        defectRows.value[i].bin = bins;
    }
}

function onDefectKeydown(i: number, e: KeyboardEvent) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const val = defectRows.value[i].defect_input.trim().toUpperCase();
        const exact = defectRows.value[i].suggestions.find(
            (s) => s.defect_code === val,
        );
        if (exact) {
            selectDefect(i, exact);
            return;
        }
        if (defectRows.value[i].suggestions.length === 1) {
            selectDefect(i, defectRows.value[i].suggestions[0]);
            return;
        }
        // Commit as-is
        defectRows.value[i].defect_code = val;
        defectRows.value[i].dropdownOpen = false;
    }
    if (e.key === 'Escape') defectRows.value[i].dropdownOpen = false;
    if (
        e.key === 'Tab' &&
        defectRows.value[i].defect_input &&
        i === defectRows.value.length - 1
    ) {
        addDefectRow();
    }
}

async function closeDefectDropdown(i: number) {
    await new Promise((r) => setTimeout(r, 150));
    defectRows.value[i].dropdownOpen = false;
}

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

async function onDefectPaste(i: number, slot: number, e: ClipboardEvent) {
    const items = e.clipboardData?.items;
    if (!items) return;
    for (const item of items) {
        if (item.type.startsWith('image/')) {
            const file = item.getAsFile();
            if (!file) continue;
            defectRows.value[i].images[slot] = await compressToBase64(file);
            break;
        }
    }
}

function removeDefectImage(i: number, slot: number) {
    defectRows.value[i].images[slot] = null;
}

const previewSrc = ref<string | null>(null);

function resetDefectRows() {
    defectRows.value = [makeRow()];
    defectTimers = [];
}

async function populateDefectRows(r: ViTechnicalRecord) {
    // Prefer defect_details JSON (preserves bin/result/images)
    if (
        r.defect_details &&
        Array.isArray(r.defect_details) &&
        r.defect_details.length
    ) {
        defectRows.value = (r.defect_details as any[]).map((d) => ({
            ...makeRow(),
            defect_code: d.code ?? '',
            defect_input: d.code ?? '',
            bin: Array.isArray(d.bin) ? d.bin : d.bin ? [d.bin] : [],
            result: d.result ?? '',
            images: [
                ...(d.images ?? []),
                ...Array(
                    Math.max(0, MAX_IMAGES_PER_ROW - (d.images?.length ?? 0)),
                ).fill(null),
            ].slice(0, MAX_IMAGES_PER_ROW),
        }));
        // Enrich defect names
        await Promise.all(
            defectRows.value.map(async (row, i) => {
                if (!row.defect_code) return;
                try {
                    const { data } = await axios.get<
                        { defect_code: string; defect_name: string }[]
                    >('/api/endline-delay/defect-codes', {
                        params: { q: row.defect_code },
                    });
                    const match = data.find(
                        (d) =>
                            d.defect_code.toUpperCase() ===
                            row.defect_code.toUpperCase(),
                    );
                    if (match)
                        defectRows.value[i].defect_name = match.defect_name;
                } catch {}
            }),
        );
        return;
    }
    // Fallback: parse comma-separated defect_code string
    if (!r.defect_code) {
        defectRows.value = [makeRow()];
        return;
    }
    const codes = r.defect_code
        .split(',')
        .map((c) => c.trim())
        .filter(Boolean);
    defectRows.value = codes.map((code) => ({
        ...makeRow(),
        defect_code: code,
        defect_input: code,
    }));
    if (defectRows.value.length === 0) {
        defectRows.value = [makeRow()];
        return;
    }
    await Promise.all(
        codes.map(async (code, i) => {
            try {
                const { data } = await axios.get<
                    { defect_code: string; defect_name: string }[]
                >('/api/endline-delay/defect-codes', { params: { q: code } });
                const match = data.find(
                    (d) => d.defect_code.toUpperCase() === code.toUpperCase(),
                );
                if (match && defectRows.value[i])
                    defectRows.value[i].defect_name = match.defect_name;
            } catch {}
        }),
    );
}

const props = defineProps<{
    open: boolean;
    lot?: ViTechnicalRecord | null;
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
const activeLot = ref<ViTechnicalRecord | null>(null);

// Equipment autocomplete state
const eqpInput = ref('');
const eqpArea = ref('');
const eqpType = ref('');
const eqpMakerAuto = ref('');
const eqpError = ref('');
const eqpSuggestions = ref<
    {
        eqp_no: string;
        eqp_type: string | null;
        eqp_area: string | null;
        eqp_maker: string | null;
    }[]
>([]);
const eqpDropdownOpen = ref(false);
let eqpTimer: ReturnType<typeof setTimeout> | null = null;

const isReadonly = computed(() => props.readonly === true);

interface TechForm {
    technical_result: string;
    eqp_number: string;
    eqp_maker: string;
    remarks: string;
}

const form = ref<TechForm>({
    technical_result: '',
    eqp_number: '',
    eqp_maker: '',
    remarks: '',
});

function resetForm() {
    form.value = {
        technical_result: '',
        eqp_number: '',
        eqp_maker: '',
        remarks: '',
    };
    eqpInput.value = '';
    eqpArea.value = '';
    eqpType.value = '';
    eqpMakerAuto.value = '';
    eqpError.value = '';
    eqpSuggestions.value = [];
    eqpDropdownOpen.value = false;
    resetDefectRows();
}

function populate(r: ViTechnicalRecord) {
    form.value = {
        technical_result: r.technical_result ?? '',
        eqp_number: r.eqp_number ?? '',
        eqp_maker: r.eqp_maker ?? '',
        remarks: r.remarks ?? '',
    };
    eqpInput.value = r.eqp_number ?? '';
    if (r.eqp_number) {
        axios
            .get('/api/vi-technical/equipment-lookup', {
                params: { eqp_no: r.eqp_number },
            })
            .then(({ data }) => {
                eqpType.value = data.data?.eqp_type ?? '';
                eqpArea.value = data.data?.eqp_area ?? '';
                eqpMakerAuto.value = data.data?.eqp_maker ?? '';
                if (!form.value.eqp_maker)
                    form.value.eqp_maker = data.data?.eqp_maker ?? '';
            })
            .catch(() => {});
    }
    populateDefectRows(r);
}

const eqpInputRef = ref<HTMLInputElement | null>(null);

watch(
    () => props.open,
    async (val) => {
        if (!val) return;
        errors.value = {};
        if (props.lot) {
            lotInput.value = props.lot.lot_id;
            // Always do a fresh lookup to get enriched qc_inspection data (bin results)
            lookupLoading.value = true;
            try {
                const { data } = await axios.get<{ data: ViTechnicalRecord }>(
                    '/api/vi-technical/find-by-lot',
                    { params: { lot_id: props.lot.lot_id } },
                );
                activeLot.value = data.data;
                populate(data.data);
            } catch {
                activeLot.value = props.lot;
                populate(props.lot);
            } finally {
                lookupLoading.value = false;
            }
            // Focus machine no. after data loads
            setTimeout(() => eqpInputRef.value?.focus(), 80);
        } else {
            lotInput.value = '';
            activeLot.value = null;
            resetForm();
        }
    },
);

watch(
    () => props.lot,
    (lot) => {
        if (!props.open || !lot) return;
        lotInput.value = lot.lot_id;
        activeLot.value = lot;
        populate(lot);
    },
);

function onLotInput(e: Event) {
    lotInput.value = (e.target as HTMLInputElement).value.toUpperCase();
    delete errors.value.lot_id;
    activeLot.value = null;
}

async function lookupLot() {
    const id = lotInput.value.trim().toUpperCase();
    if (!id) return;
    lookupLoading.value = true;
    delete errors.value.lot_id;
    try {
        const { data } = await axios.get<{ data: ViTechnicalRecord }>(
            '/api/vi-technical/find-by-lot',
            { params: { lot_id: id } },
        );
        activeLot.value = data.data;
        populate(data.data);
    } catch (err: any) {
        activeLot.value = null;
        resetForm();
        errors.value.lot_id =
            err.response?.data?.error ?? 'Lot not found in VI Technical queue';
    } finally {
        lookupLoading.value = false;
    }
}

function onEqpInput(e: Event) {
    const val = (e.target as HTMLInputElement).value.toUpperCase();
    eqpInput.value = val;
    form.value.eqp_number = val;
    eqpArea.value = '';
    eqpType.value = '';
    eqpMakerAuto.value = '';
    eqpError.value = '';
    if (eqpTimer) clearTimeout(eqpTimer);
    if (!val.trim()) {
        eqpSuggestions.value = [];
        eqpDropdownOpen.value = false;
        return;
    }
    eqpTimer = setTimeout(async () => {
        const { data } = await axios.get('/api/vi-technical/equipment-search', {
            params: { q: val },
        });
        eqpSuggestions.value = data;
        eqpDropdownOpen.value = data.length > 0;
    }, 200);
}

function selectEquipment(eqp: {
    eqp_no: string;
    eqp_type: string | null;
    eqp_area: string | null;
    eqp_maker: string | null;
}) {
    eqpInput.value = eqp.eqp_no;
    form.value.eqp_number = eqp.eqp_no;
    eqpType.value = eqp.eqp_type ?? '';
    eqpArea.value = eqp.eqp_area ?? '';
    eqpMakerAuto.value = eqp.eqp_maker ?? '';
    form.value.eqp_maker = eqp.eqp_maker ?? form.value.eqp_maker;
    eqpDropdownOpen.value = false;
    eqpSuggestions.value = [];
    eqpError.value = '';
    delete errors.value.eqp_number;
}

function onEqpKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const val = eqpInput.value.trim().toUpperCase();
        const exact = eqpSuggestions.value.find((s) => s.eqp_no === val);
        if (exact) {
            selectEquipment(exact);
            return;
        }
        if (eqpSuggestions.value.length === 1) {
            selectEquipment(eqpSuggestions.value[0]);
            return;
        }
        axios
            .get('/api/vi-technical/equipment-lookup', {
                params: { eqp_no: val },
            })
            .then(({ data }) => selectEquipment(data.data))
            .catch(() => {
                eqpError.value = 'Not found';
                eqpDropdownOpen.value = false;
            });
    }
    if (e.key === 'Escape') {
        eqpDropdownOpen.value = false;
    }
}

async function closeEqpDropdown() {
    await new Promise((r) => setTimeout(r, 150));
    eqpDropdownOpen.value = false;
}

function btnClass(current: string, opt: string) {
    if (current !== opt)
        return 'border-border bg-background text-muted-foreground hover:border-muted-foreground/30 hover:text-foreground';
    if (opt === 'Proceed')
        return 'border-emerald-500 bg-emerald-500 text-white shadow-sm shadow-emerald-500/30';
    if (opt === 'Rework')
        return 'border-red-500 bg-red-500 text-white shadow-sm shadow-red-500/30';
    if (opt === 'DRB Approval')
        return 'border-orange-500 bg-orange-500 text-white shadow-sm shadow-orange-500/30';
    if (opt === 'Derive RL')
        return 'border-violet-500 bg-violet-500 text-white shadow-sm shadow-violet-500/30';
    if (opt === 'Scrap LY')
        return 'border-pink-500 bg-pink-500 text-white shadow-sm shadow-pink-500/30';
    if (opt === 'For Decision')
        return 'border-sky-500 bg-sky-500 text-white shadow-sm shadow-sky-500/30';
}

// All filled defect rows must have a decision before Proceed/Derive RL/Scrap LY/Rework can be selected
const allDefectsDecided = computed(() => {
    if (!form.value.eqp_number) return false;
    const filled = defectRows.value.filter((r) => r.defect_code.trim());
    return (
        filled.length > 0 &&
        filled.every((r) => r.result !== '' && r.bin.length > 0)
    );
});

const REQUIRES_ALL_DECIDED = [
    'Proceed',
    'Derive RL',
    'Scrap LY',
    'Rework',
] as const;

function buildRemarks() {
    const filled = defectRows.value.filter((r) => r.defect_code.trim());
    if (!filled.length && !form.value.technical_result) return;

    // Group by bin+decision: { 'Main-Proceed': ['CD','CH'], 'RR-Rework': ['BRT'] }
    const groups: Record<string, string[]> = {};
    for (const row of filled) {
        const bins = row.bin.length ? row.bin : ['—'];
        const decision = row.result || '—';
        for (const bin of bins) {
            const key = `${bin}-${decision}`;
            if (!groups[key]) groups[key] = [];
            groups[key].push(row.defect_code);
        }
    }

    // Build parts like "Main [CD, CH] - Proceed"
    const parts = Object.entries(groups).map(([key, codes]) => {
        const [bin, decision] = key.split('-');
        return `${bin} [${codes.join(', ')}] - ${decision}`;
    });

    const final = form.value.technical_result
        ? `Final: ${form.value.technical_result}`
        : '';
    form.value.remarks = [...parts, ...(final ? [final] : [])].join(' | ');
}

// Watch defect rows and final decision to auto-rebuild remarks
watch([defectRows, () => form.value.technical_result], () => buildRemarks(), {
    deep: true,
});

function validate() {
    errors.value = {};
    if (!activeLot.value) {
        errors.value.lot_id = 'Look up a lot first';
        return false;
    }
    if (!form.value.eqp_number) errors.value.eqp_number = 'Required';
    if (!form.value.technical_result)
        errors.value.technical_result = 'Required';
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
        const filledRows = defectRows.value.filter((r) => r.defect_code.trim());
        const defectCodeStr =
            filledRows.map((r) => r.defect_code).join(', ') || null;
        const defectPayload = filledRows.map((r) => ({
            code: r.defect_code,
            bin: r.bin,
            result: r.result,
            images: r.images.filter(Boolean),
        }));

        await axios.put(`/api/vi-technical/${activeLot.value.id}`, {
            technical_result: form.value.technical_result || null,
            eqp_number: form.value.eqp_number || null,
            eqp_maker: eqpMakerAuto.value || form.value.eqp_maker || null,
            defect_code: defectCodeStr,
            defect_details: defectPayload.length ? defectPayload : null,
            remarks: form.value.remarks || null,
        });
        showToast('VI Technical result saved successfully');
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

function elapsed(start: string | null): string {
    if (!start) return '—';
    const mins = Math.floor((Date.now() - new Date(start).getTime()) / 60_000);
    if (mins < 60) return `${mins} min`;
    const h = Math.floor(mins / 60),
        m = mins % 60;
    return `${h}h ${m}m`;
}

function binCardClass(
    result: string | null | undefined,
    color: 'red' | 'orange' | 'violet',
) {
    const map = {
        red: {
            ng: 'border-red-200 bg-red-50 dark:border-red-800/60 dark:bg-red-950/30',
            ok: 'border-emerald-200 bg-emerald-50 dark:border-emerald-800/60 dark:bg-emerald-950/30',
        },
        orange: {
            ng: 'border-orange-200 bg-orange-50 dark:border-orange-800/60 dark:bg-orange-950/30',
            ok: 'border-emerald-200 bg-emerald-50 dark:border-emerald-800/60 dark:bg-emerald-950/30',
        },
        violet: {
            ng: 'border-violet-200 bg-violet-50 dark:border-violet-800/60 dark:bg-violet-950/30',
            ok: 'border-emerald-200 bg-emerald-50 dark:border-emerald-800/60 dark:bg-emerald-950/30',
        },
    };
    if (result === 'NG') return map[color].ng;
    if (result === 'OK') return map[color].ok;
    return 'border-border bg-muted/40';
}

function binDotClass(
    result: string | null | undefined,
    color: 'red' | 'orange' | 'violet',
) {
    if (result === 'NG')
        return color === 'red'
            ? 'bg-red-500'
            : color === 'orange'
              ? 'bg-orange-500'
              : 'bg-violet-500';
    if (result === 'OK') return 'bg-emerald-500';
    return '';
}

function binTextClass(
    result: string | null | undefined,
    color: 'red' | 'orange' | 'violet',
) {
    if (result === 'NG')
        return color === 'red'
            ? 'text-red-700 dark:text-red-400'
            : color === 'orange'
              ? 'text-orange-700 dark:text-orange-400'
              : 'text-violet-700 dark:text-violet-400';
    if (result === 'OK') return 'text-emerald-700 dark:text-emerald-400';
    return 'text-muted-foreground/40';
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent
            class="flex !max-h-[92vh] !w-[860px] !max-w-[860px] flex-col gap-0 overflow-hidden p-0"
            @interact-outside.prevent
            @escape-key-down.prevent
        >
            <!-- Header -->
            <DialogHeader
                class="relative overflow-hidden rounded-t-lg border-b border-border/50 px-6 py-3.5"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-gradient-to-br from-violet-500/10 via-transparent to-primary/5"
                ></div>
                <div class="relative flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 ring-1 ring-violet-500/30"
                    >
                        <Microscope
                            class="h-4 w-4 text-violet-600 dark:text-violet-400"
                        />
                    </div>
                    <div>
                        <DialogTitle
                            class="text-sm leading-tight font-bold text-foreground"
                            >VI Technical Update</DialogTitle
                        >
                        <DialogDescription
                            class="text-[11px] text-muted-foreground"
                            >Record VI technical verification
                            result</DialogDescription
                        >
                    </div>
                </div>
            </DialogHeader>

            <div class="flex-1 space-y-3 overflow-y-auto px-6 py-4">
                <!-- ROW 1: Lot + lot details -->
                <div class="grid grid-cols-12 items-end gap-3">
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Lot No
                            <span class="text-destructive">*</span></label
                        >
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
                                        : 'border-input focus:border-violet-500 focus:ring-violet-500/20'
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
                                    class="h-3.5 w-3.5 animate-spin text-violet-500"
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
                                >{{ activeLot.lipas_yn }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                    </div>
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
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Tech Start</label
                        >
                        <div
                            class="flex h-9 items-center rounded-lg border border-input bg-muted/40 px-3 text-xs whitespace-nowrap text-muted-foreground"
                        >
                            {{ fmt(activeLot?.technical_start_at ?? null) }}
                        </div>
                    </div>
                </div>

                <!-- ROW 2: Main Lot, RR Bin, LY Bin, Defect Code, Elapsed -->
                <div class="grid grid-cols-12 items-stretch gap-3">
                    <!-- Main Lot -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Main Lot</label
                        >
                        <div
                            class="flex h-10 items-center gap-2.5 rounded-lg border px-3 transition-colors"
                            :class="
                                binCardClass(activeLot?.mainlot_result, 'red')
                            "
                        >
                            <span
                                v-if="activeLot?.mainlot_result"
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] leading-none font-black text-white"
                                :class="
                                    binDotClass(activeLot.mainlot_result, 'red')
                                "
                            >
                                {{
                                    activeLot.mainlot_result === 'NG'
                                        ? '✕'
                                        : '✓'
                                }}
                            </span>
                            <span
                                class="text-sm font-bold"
                                :class="
                                    binTextClass(
                                        activeLot?.mainlot_result,
                                        'red',
                                    )
                                "
                                >{{ activeLot?.mainlot_result ?? '—' }}</span
                            >
                        </div>
                    </div>
                    <!-- RR Bin -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >RR Bin</label
                        >
                        <div
                            class="flex h-10 items-center gap-2.5 rounded-lg border px-3 transition-colors"
                            :class="
                                binCardClass(activeLot?.rr_result, 'orange')
                            "
                        >
                            <span
                                v-if="activeLot?.rr_result"
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] leading-none font-black text-white"
                                :class="
                                    binDotClass(activeLot.rr_result, 'orange')
                                "
                            >
                                {{ activeLot.rr_result === 'NG' ? '✕' : '✓' }}
                            </span>
                            <span
                                class="text-sm font-bold"
                                :class="
                                    binTextClass(activeLot?.rr_result, 'orange')
                                "
                                >{{ activeLot?.rr_result ?? '—' }}</span
                            >
                        </div>
                    </div>
                    <!-- LY Bin -->
                    <div class="col-span-2">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >LY Bin</label
                        >
                        <div
                            class="flex h-10 items-center gap-2.5 rounded-lg border px-3 transition-colors"
                            :class="
                                binCardClass(activeLot?.ly_result, 'violet')
                            "
                        >
                            <span
                                v-if="activeLot?.ly_result"
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] leading-none font-black text-white"
                                :class="
                                    binDotClass(activeLot.ly_result, 'violet')
                                "
                            >
                                {{ activeLot.ly_result === 'NG' ? '✕' : '✓' }}
                            </span>
                            <span
                                class="text-sm font-bold"
                                :class="
                                    binTextClass(activeLot?.ly_result, 'violet')
                                "
                                >{{ activeLot?.ly_result ?? '—' }}</span
                            >
                        </div>
                    </div>
                    <!-- QC SPL -->
                    <div class="col-span-1">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >QC SPL</label
                        >
                        <div
                            class="flex h-10 items-center justify-center rounded-lg border border-input bg-muted/40 px-2 text-sm font-semibold tabular-nums"
                        >
                            <span
                                v-if="activeLot?.inspection_spl != null"
                                class="text-foreground"
                                >{{
                                    activeLot.inspection_spl.toLocaleString()
                                }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                    </div>
                    <!-- Defect Code -->
                    <div class="col-span-3">
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
                                    activeLot?.technical_start_at
                                        ? 'text-amber-600 dark:text-amber-400'
                                        : 'text-muted-foreground/40'
                                "
                            >
                                {{
                                    elapsed(
                                        activeLot?.technical_start_at ?? null,
                                    )
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ROW 3: Machine lookup -->
                <div class="grid grid-cols-12 items-end gap-3">
                    <!-- Machine No with autocomplete -->
                    <div class="col-span-3">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Machine No.</label
                        >
                        <div class="relative">
                            <input
                                ref="eqpInputRef"
                                :value="eqpInput"
                                type="text"
                                autocomplete="off"
                                placeholder="Type to search..."
                                :disabled="!activeLot || isReadonly"
                                class="h-9 w-full rounded-lg border border-input bg-background pr-8 pl-3 text-sm uppercase placeholder:font-normal placeholder:tracking-normal placeholder:text-muted-foreground/40 placeholder:normal-case focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 focus:outline-none disabled:opacity-40"
                                :class="eqpError ? 'border-destructive' : ''"
                                @input="onEqpInput"
                                @keydown="onEqpKeydown"
                                @blur="closeEqpDropdown"
                            />
                            <span
                                class="pointer-events-none absolute inset-y-0 right-2 flex items-center"
                            >
                                <Search
                                    class="h-3 w-3 text-muted-foreground/40"
                                />
                            </span>
                            <!-- Dropdown suggestions -->
                            <ul
                                v-if="eqpDropdownOpen && eqpSuggestions.length"
                                class="absolute top-full left-0 z-50 mt-1 max-h-48 w-64 overflow-auto rounded-lg border border-border bg-popover shadow-xl"
                            >
                                <li
                                    v-for="opt in eqpSuggestions"
                                    :key="opt.eqp_no"
                                    class="flex cursor-pointer items-center justify-between px-3 py-2 text-xs hover:bg-muted"
                                    @mousedown.prevent="selectEquipment(opt)"
                                >
                                    <span
                                        class="font-mono font-bold text-primary"
                                        >{{ opt.eqp_no }}</span
                                    >
                                    <span
                                        class="ml-2 truncate text-muted-foreground"
                                        >{{ opt.eqp_type }} ·
                                        {{ opt.eqp_area }}</span
                                    >
                                </li>
                            </ul>
                        </div>
                        <p
                            v-if="eqpError"
                            class="mt-0.5 text-[10px] text-destructive"
                        >
                            {{ eqpError }}
                        </p>
                        <p
                            v-else-if="errors.eqp_number"
                            class="mt-0.5 text-[10px] text-destructive"
                        >
                            {{ errors.eqp_number }}
                        </p>
                    </div>
                    <!-- Machine Type -->
                    <div class="col-span-3">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Machine Type</label
                        >
                        <div
                            class="flex h-9 items-center rounded-lg border border-input bg-muted/40 px-3 text-sm"
                        >
                            <span
                                v-if="eqpType"
                                class="truncate text-foreground"
                                >{{ eqpType }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                    </div>
                    <!-- Area -->
                    <div class="col-span-3">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Area</label
                        >
                        <div
                            class="flex h-9 items-center rounded-lg border border-input bg-muted/40 px-3 text-sm"
                        >
                            <span
                                v-if="eqpArea"
                                class="truncate text-foreground"
                                >{{ eqpArea }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                    </div>
                    <!-- Eqp Maker -->
                    <div class="col-span-3">
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Eqp Maker</label
                        >
                        <div
                            class="flex h-9 items-center rounded-lg border border-input bg-muted/40 px-3 text-sm"
                        >
                            <span
                                v-if="eqpMakerAuto"
                                class="truncate text-foreground"
                                >{{ eqpMakerAuto }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-muted-foreground/40"
                                >—</span
                            >
                        </div>
                    </div>
                </div>

                <div class="h-px bg-border/40"></div>

                <!-- ROW 4: Defect entries -->
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-xs font-semibold text-foreground">
                            Defect Entries
                            <span class="font-normal text-muted-foreground"
                                >(up to {{ MAX_DEFECT_ROWS }})</span
                            >
                        </p>
                        <button
                            v-if="
                                !isReadonly &&
                                defectRows.length < MAX_DEFECT_ROWS
                            "
                            type="button"
                            class="text-[10px] font-semibold text-violet-600 hover:underline"
                            @click="addDefectRow"
                        >
                            + Add Row
                        </button>
                    </div>
                    <!-- Header -->
                    <div
                        class="mb-1 grid grid-cols-[24px_120px_1fr_100px_180px_110px_32px] gap-1.5 px-1"
                    >
                        <span
                            class="text-[9px] font-bold tracking-wider text-muted-foreground uppercase"
                            >#</span
                        >
                        <span
                            class="text-[9px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Defect Code</span
                        >
                        <span
                            class="text-[9px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Defect Name</span
                        >
                        <span
                            class="text-[9px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Bin</span
                        >
                        <span
                            class="text-[9px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Img</span
                        >
                        <span
                            class="text-[9px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Decision</span
                        >
                        <span></span>
                    </div>
                    <div class="space-y-1.5">
                        <div
                            v-for="(row, i) in defectRows"
                            :key="i"
                            class="grid grid-cols-[24px_120px_1fr_100px_180px_110px_32px] items-center gap-1.5"
                        >
                            <!-- # row number -->
                            <span
                                class="text-center text-[10px] font-semibold text-muted-foreground"
                                >{{ i + 1 }}</span
                            >
                            <!-- Defect Code — wider with search icon -->
                            <div class="relative">
                                <input
                                    :value="row.defect_input"
                                    type="text"
                                    autocomplete="off"
                                    maxlength="10"
                                    placeholder="Search..."
                                    :disabled="!activeLot || isReadonly"
                                    class="h-8 w-full rounded-lg border border-input bg-background pr-6 pl-2 text-xs uppercase placeholder:font-normal placeholder:tracking-normal placeholder:text-muted-foreground/40 placeholder:normal-case focus:border-violet-500 focus:ring-1 focus:ring-violet-500/20 focus:outline-none disabled:opacity-40"
                                    @input="onDefectInput(i, $event)"
                                    @keydown="onDefectKeydown(i, $event)"
                                    @blur="closeDefectDropdown(i)"
                                />
                                <span
                                    class="pointer-events-none absolute inset-y-0 right-1.5 flex items-center"
                                >
                                    <Search
                                        class="h-3 w-3 text-muted-foreground/40"
                                    />
                                </span>
                                <ul
                                    v-if="
                                        row.dropdownOpen &&
                                        row.suggestions.length
                                    "
                                    class="absolute top-full left-0 z-50 mt-0.5 max-h-40 w-64 overflow-auto rounded-lg border border-border bg-popover shadow-xl"
                                >
                                    <li
                                        v-for="opt in row.suggestions"
                                        :key="opt.defect_code"
                                        class="flex cursor-pointer items-center justify-between px-2 py-1.5 text-[11px] hover:bg-muted"
                                        @mousedown.prevent="
                                            selectDefect(i, opt)
                                        "
                                    >
                                        <span class="font-bold text-primary">{{
                                            opt.defect_code
                                        }}</span>
                                        <span
                                            class="ml-1 truncate text-muted-foreground"
                                            >{{ opt.defect_name }}</span
                                        >
                                    </li>
                                </ul>
                            </div>
                            <!-- Defect Name (auto-filled, read-only) — narrower -->
                            <div
                                class="flex h-8 items-center rounded-lg border border-input bg-muted/40 px-2 text-xs"
                            >
                                <span
                                    v-if="row.defect_name"
                                    class="truncate text-foreground"
                                    :title="row.defect_name"
                                    >{{ row.defect_name }}</span
                                >
                                <span v-else class="text-muted-foreground/40"
                                    >—</span
                                >
                            </div>
                            <!-- Bin toggle buttons — multi-select -->
                            <div class="flex gap-0.5">
                                <button
                                    v-for="b in BIN_OPTIONS"
                                    :key="b"
                                    type="button"
                                    class="flex-1 rounded border px-1 py-1 text-[9px] font-bold transition-all"
                                    :class="
                                        row.bin.includes(b)
                                            ? b === 'Main'
                                                ? 'border-blue-500 bg-blue-500 text-white'
                                                : b === 'RR'
                                                  ? 'border-orange-500 bg-orange-500 text-white'
                                                  : 'border-violet-500 bg-violet-500 text-white'
                                            : 'border-border bg-background text-muted-foreground hover:border-muted-foreground/40'
                                    "
                                    :disabled="!activeLot || isReadonly"
                                    @click="
                                        row.bin.includes(b)
                                            ? row.bin.splice(
                                                  row.bin.indexOf(b),
                                                  1,
                                              )
                                            : row.bin.push(b)
                                    "
                                >
                                    {{ b }}
                                </button>
                            </div>
                            <!-- 5 Image paste slots -->
                            <div class="flex gap-1">
                                <div
                                    v-for="slot in MAX_IMAGES_PER_ROW"
                                    :key="slot"
                                    class="relative flex-1"
                                >
                                    <div
                                        v-if="row.images[slot - 1]"
                                        class="group relative h-8 w-full overflow-hidden rounded border-2 border-violet-400 bg-black"
                                    >
                                        <img
                                            :src="row.images[slot - 1]!"
                                            class="h-full w-full cursor-zoom-in object-cover"
                                            alt="Defect"
                                            @click="
                                                previewSrc =
                                                    row.images[slot - 1]
                                            "
                                        />
                                        <button
                                            v-if="!isReadonly"
                                            type="button"
                                            class="absolute top-0 right-0 flex h-3 w-3 items-center justify-center rounded-full bg-red-500 text-[8px] font-black text-white opacity-0 transition-opacity group-hover:opacity-100"
                                            @click.stop="
                                                removeDefectImage(i, slot - 1)
                                            "
                                        >
                                            ✕
                                        </button>
                                    </div>
                                    <div
                                        v-else
                                        tabindex="0"
                                        class="flex h-8 w-full cursor-text items-center justify-center rounded border-2 border-dashed border-violet-400/50 bg-violet-50/20 focus:border-violet-500 focus:outline-none dark:bg-violet-950/10"
                                        :class="
                                            !activeLot || isReadonly
                                                ? 'pointer-events-none opacity-40'
                                                : ''
                                        "
                                        @paste="
                                            onDefectPaste(i, slot - 1, $event)
                                        "
                                    >
                                        <ImagePlus
                                            class="h-2.5 w-2.5 text-violet-300"
                                        />
                                    </div>
                                </div>
                            </div>
                            <!-- Result toggle -->
                            <div class="flex gap-1">
                                <button
                                    type="button"
                                    class="flex-1 rounded-lg border px-2 py-1 text-[10px] font-bold transition-all"
                                    :class="
                                        row.result === 'Proceed'
                                            ? 'border-emerald-500 bg-emerald-500 text-white'
                                            : 'border-border bg-background text-muted-foreground hover:border-emerald-400'
                                    "
                                    :disabled="!activeLot || isReadonly"
                                    @click="
                                        row.result =
                                            row.result === 'Proceed'
                                                ? ''
                                                : 'Proceed'
                                    "
                                >
                                    Proceed
                                </button>
                                <button
                                    type="button"
                                    class="flex-1 rounded-lg border px-2 py-1 text-[10px] font-bold transition-all"
                                    :class="
                                        row.result === 'Rework'
                                            ? 'border-red-500 bg-red-500 text-white'
                                            : 'border-border bg-background text-muted-foreground hover:border-red-400'
                                    "
                                    :disabled="!activeLot || isReadonly"
                                    @click="
                                        row.result =
                                            row.result === 'Rework'
                                                ? ''
                                                : 'Rework'
                                    "
                                >
                                    Rework
                                </button>
                            </div>
                            <!-- Remove row — own column, no overlap -->
                            <button
                                v-if="!isReadonly && defectRows.length > 1"
                                type="button"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded text-muted-foreground/50 hover:text-red-500"
                                @click="removeDefectRow(i)"
                            >
                                ✕
                            </button>
                            <div v-else class="w-8 shrink-0"></div>
                        </div>
                    </div>
                </div>

                <div class="h-px bg-border/40"></div>

                <!-- ROW 5: Final Decision + Remarks -->
                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label
                            class="mb-2 block text-xs font-semibold text-foreground"
                        >
                            Final Decision
                            <span class="text-destructive">*</span>
                        </label>
                        <div class="flex flex-col gap-2">
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="opt in RESULT_OPTIONS"
                                    :key="opt"
                                    type="button"
                                    class="rounded-lg border px-3 py-1.5 text-[11px] font-bold tracking-wide whitespace-nowrap transition-all duration-150"
                                    :class="
                                        btnClass(form.technical_result, opt)
                                    "
                                    :disabled="
                                        !activeLot ||
                                        isReadonly ||
                                        (REQUIRES_ALL_DECIDED.includes(
                                            opt as any,
                                        ) &&
                                            !allDefectsDecided)
                                    "
                                    :title="
                                        REQUIRES_ALL_DECIDED.includes(
                                            opt as any,
                                        ) && !allDefectsDecided
                                            ? 'Machine number, bin, and decision required for all defect entries'
                                            : undefined
                                    "
                                    @click="
                                        form.technical_result =
                                            form.technical_result === opt
                                                ? ''
                                                : opt;
                                        delete errors.technical_result;
                                    "
                                >
                                    {{ opt }}
                                </button>
                            </div>
                        </div>
                        <p
                            v-if="errors.technical_result"
                            class="mt-1 text-[10px] text-destructive"
                        >
                            {{ errors.technical_result }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-foreground"
                            >Remarks</label
                        >
                        <textarea
                            v-model="form.remarks"
                            rows="4"
                            :disabled="!activeLot || isReadonly"
                            placeholder="Add technical notes..."
                            class="w-full resize-none rounded-lg border border-input bg-background px-3 py-2 text-xs placeholder:text-muted-foreground/50 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 focus:outline-none disabled:opacity-40"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Lightbox preview -->
            <div
                v-if="previewSrc"
                class="absolute inset-0 z-50 flex items-center justify-center rounded-lg"
            >
                <div
                    class="absolute inset-0 rounded-lg bg-black/85 backdrop-blur-sm"
                    @click="previewSrc = null"
                ></div>
                <img
                    :src="previewSrc"
                    class="relative z-10 max-h-[85vh] max-w-[95%] rounded-lg shadow-2xl"
                    alt="Preview"
                />
                <button
                    type="button"
                    class="absolute top-3 right-3 z-20 flex h-9 items-center gap-1.5 rounded-lg bg-white/20 px-3 text-sm font-semibold text-white transition-colors hover:bg-white/40"
                    @click="previewSrc = null"
                >
                    <span class="text-base leading-none font-black">✕</span>
                    Close
                </button>
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
                    class="inline-flex h-9 items-center rounded-lg bg-gradient-to-r from-violet-600 to-violet-500 px-6 text-sm font-semibold text-white shadow-md shadow-violet-500/25 transition-all hover:from-violet-700 hover:to-violet-600 disabled:pointer-events-none disabled:opacity-50"
                    :disabled="submitting || !activeLot"
                    @click="save"
                >
                    <Loader2
                        v-if="submitting"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    Save Result
                </button>
            </div>
        </DialogContent>
    </Dialog>
</template>
