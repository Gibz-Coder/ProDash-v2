<template>
    <AppLayout>
        <template #filters>
            <div class="flex items-center gap-3">
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >FROM:</span
                    >
                    <input
                        v-model="filterFrom"
                        type="date"
                        @change="fetchData"
                        class="cursor-pointer border-0 bg-transparent text-xs font-semibold text-foreground focus:ring-0 focus:outline-none"
                    />
                </div>
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >TO:</span
                    >
                    <input
                        v-model="filterTo"
                        type="date"
                        @change="fetchData"
                        class="cursor-pointer border-0 bg-transparent text-xs font-semibold text-foreground focus:ring-0 focus:outline-none"
                    />
                </div>
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >WORKTYPE:</span
                    >
                    <select
                        v-model="filterWorktype"
                        @change="fetchData"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background"
                    >
                        <option value="">ALL</option>
                        <option value="NORMAL">NORMAL</option>
                        <option value="PROCESS RW">PROCESS RW</option>
                        <option value="WH REWORK">WH REWORK</option>
                        <option value="OI REWORK">OI REWORK</option>
                    </select>
                </div>
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >LIPAS:</span
                    >
                    <select
                        v-model="filterLipas"
                        @change="fetchData"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background"
                    >
                        <option value="">ALL</option>
                        <option value="Y">Yes</option>
                        <option value="N">No</option>
                    </select>
                </div>
                <button
                    @click="fetchData"
                    class="flex items-center gap-1 rounded-lg border border-blue-600 bg-transparent px-3 py-1.5 text-xs font-medium text-blue-600 shadow-sm transition-colors hover:bg-blue-600 hover:text-white"
                >
                    <RefreshCw class="h-3.5 w-3.5" /> Refresh
                </button>
            </div>
        </template>

        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Z-ROW 1: Title + KPI cards -->
            <div class="flex items-start justify-between gap-4">
                <!-- Left: Title -->
                <div class="min-w-[200px]">
                    <h1 class="text-xl leading-tight font-bold">
                        Endline Trend
                    </h1>
                    <p class="text-[11px] text-muted-foreground">
                        QC inspection trend monitoring
                    </p>
                    <p
                        v-if="lastUpdated"
                        class="mt-0.5 text-[10px] text-muted-foreground/60"
                    >
                        Updated {{ lastUpdated }}
                    </p>
                </div>

                <!-- Right: KPI cards -->
                <div class="flex gap-3">
                    <div
                        v-for="kpi in kpiCards"
                        :key="kpi.label"
                        class="min-w-[130px] rounded-xl border border-border/50 px-4 py-3 shadow-sm"
                        :class="kpi.bg"
                    >
                        <p
                            class="text-[10px] font-semibold tracking-widest uppercase"
                            :class="kpi.labelColor"
                        >
                            {{ kpi.label }}
                        </p>
                        <p
                            class="mt-0.5 text-2xl leading-none font-bold"
                            :class="kpi.valueColor"
                        >
                            {{ kpi.value }}
                        </p>
                        <p class="mt-1 text-[10px]" :class="kpi.subColor">
                            {{ kpi.sub }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Z-ROW 2: Main trend chart (wide) -->
            <div
                class="rounded-xl border border-border/50 bg-card p-4 shadow-lg"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-foreground">
                        Daily Lot Volume Trend
                    </h2>
                    <div class="flex gap-1">
                        <button
                            v-for="m in chartModes"
                            :key="m.value"
                            @click="
                                chartMode = m.value;
                                buildChart();
                            "
                            class="rounded-md border px-2.5 py-1 text-xs font-medium transition-colors"
                            :class="
                                chartMode === m.value
                                    ? 'border-blue-600 bg-blue-600 text-white'
                                    : 'border-blue-600 bg-transparent text-blue-600 hover:bg-blue-600 hover:text-white'
                            "
                        >
                            {{ m.label }}
                        </button>
                    </div>
                </div>
                <div
                    v-if="loading"
                    class="flex h-[260px] items-center justify-center"
                >
                    <div
                        class="h-7 w-7 animate-spin rounded-full border-4 border-primary border-r-transparent"
                    ></div>
                </div>
                <div
                    v-else
                    id="trend-main-chart"
                    class="h-[260px] w-full"
                ></div>
            </div>

            <!-- Z-ROW 3: Detail cards -->
            <div class="grid grid-cols-3 gap-4">
                <!-- Top Defect Code -->
                <div
                    class="rounded-xl border border-border/50 bg-card p-4 shadow-sm"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <div
                            class="rounded-full bg-red-500/10 p-1.5 ring-1 ring-red-500/20"
                        >
                            <AlertCircle class="h-4 w-4 text-red-600" />
                        </div>
                        <p
                            class="text-xs font-bold tracking-wide text-foreground uppercase"
                        >
                            Top Defect Code
                        </p>
                    </div>
                    <div v-if="topDefects.length" class="space-y-2">
                        <div
                            v-for="(d, i) in topDefects.slice(0, 5)"
                            :key="d.code"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="w-4 text-[10px] font-bold text-muted-foreground"
                                >{{ i + 1 }}</span
                            >
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-xs font-semibold text-foreground"
                                        >{{ d.code }}</span
                                    >
                                    <span
                                        class="text-[10px] text-muted-foreground"
                                        >{{ d.count }} lots</span
                                    >
                                </div>
                                <div
                                    class="mt-0.5 h-1.5 w-full rounded-full bg-muted"
                                >
                                    <div
                                        class="h-1.5 rounded-full bg-red-500 transition-all"
                                        :style="{
                                            width: `${(d.count / topDefects[0].count) * 100}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-xs text-muted-foreground">No data</p>
                </div>

                <!-- Flow Distribution -->
                <div
                    class="rounded-xl border border-border/50 bg-card p-4 shadow-sm"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <div
                            class="rounded-full bg-indigo-500/10 p-1.5 ring-1 ring-indigo-500/20"
                        >
                            <GitBranch class="h-4 w-4 text-indigo-600" />
                        </div>
                        <p
                            class="text-xs font-bold tracking-wide text-foreground uppercase"
                        >
                            Flow Distribution
                        </p>
                    </div>
                    <div class="space-y-2.5">
                        <div
                            v-for="f in flowStats"
                            :key="f.label"
                            class="flex items-center gap-3"
                        >
                            <div class="w-24 shrink-0">
                                <p
                                    class="text-[10px] font-semibold"
                                    :class="f.color"
                                >
                                    {{ f.label }}
                                </p>
                            </div>
                            <div class="flex-1">
                                <div class="h-2 w-full rounded-full bg-muted">
                                    <div
                                        class="h-2 rounded-full transition-all"
                                        :class="f.bar"
                                        :style="{ width: `${f.pct}%` }"
                                    ></div>
                                </div>
                            </div>
                            <span
                                class="w-10 text-right text-[10px] font-bold text-foreground"
                                >{{ f.pct }}%</span
                            >
                            <span
                                class="w-12 text-right text-[10px] text-muted-foreground"
                                >{{ f.count }} lots</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Watch Out: Prev Day Pending -->
                <div
                    class="rounded-xl border border-border/50 bg-card p-4 shadow-sm"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <div
                            class="rounded-full bg-amber-500/10 p-1.5 ring-1 ring-amber-500/20"
                        >
                            <Clock class="h-4 w-4 text-amber-600" />
                        </div>
                        <p
                            class="text-xs font-bold tracking-wide text-foreground uppercase"
                        >
                            Watch Out
                        </p>
                    </div>
                    <div v-if="watchOutLots.length" class="space-y-2">
                        <div
                            v-for="lot in watchOutLots.slice(0, 5)"
                            :key="lot.lot_id"
                            class="flex items-center justify-between rounded-lg bg-amber-50/50 px-2.5 py-1.5 dark:bg-amber-950/20"
                        >
                            <div>
                                <p
                                    class="font-mono text-xs font-bold text-primary"
                                >
                                    {{ lot.lot_id }}
                                </p>
                                <p class="text-[10px] text-muted-foreground">
                                    {{ lot.defect_class || '—' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-semibold text-amber-600">
                                    {{ lot.elapsed }}
                                </p>
                                <p class="text-[10px] text-muted-foreground">
                                    {{ lot.final_decision }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-xs text-muted-foreground">
                        No pending lots from previous days
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import * as echarts from 'echarts';
import { AlertCircle, Clock, GitBranch, RefreshCw } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

// ── Filters ──────────────────────────────────────────────────────────────────
const today = new Date().toLocaleDateString('en-CA', {
    timeZone: 'Asia/Manila',
});
const thirtyDaysAgo = new Date(Date.now() - 29 * 86400000).toLocaleDateString(
    'en-CA',
    { timeZone: 'Asia/Manila' },
);

const filterFrom = ref(thirtyDaysAgo);
const filterTo = ref(today);
const filterWorktype = ref('');
const filterLipas = ref('');
const loading = ref(false);
const lastUpdated = ref('');

const chartModes = [
    { value: 'volume', label: 'Volume' },
    { value: 'defect', label: 'Defect' },
    { value: 'outcome', label: 'Outcome' },
];
const chartMode = ref('volume');

// ── Raw data ──────────────────────────────────────────────────────────────────
interface EndlineRow {
    id: number;
    lot_id: string;
    lot_qty: number | null;
    qc_result: string | null;
    qc_defect: string | null;
    defect_class: string | null;
    final_decision: string | null;
    output_status: string | null;
    created_at: string | null;
}

const rows = ref<EndlineRow[]>([]);

// ── KPI cards ─────────────────────────────────────────────────────────────────
const kpiCards = computed(() => {
    const total = rows.value.length;
    const totalQty = rows.value.reduce((s, r) => s + (r.lot_qty ?? 0), 0);
    const done = rows.value.filter(
        (r) => r.output_status === 'Completed' || r.output_status === 'Rework',
    ).length;
    const pending = rows.value.filter(
        (r) =>
            r.output_status === 'Pending' &&
            r.final_decision !== 'In Progress' &&
            r.final_decision !== 'Technical',
    ).length;
    const pct = total ? Math.round((done / total) * 100) : 0;

    return [
        {
            label: 'Total Lots',
            value: total.toLocaleString(),
            sub: `${(totalQty / 1000).toFixed(1)}K pcs`,
            bg: 'bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-950/30 dark:to-blue-900/20',
            labelColor: 'text-blue-700 dark:text-blue-300',
            valueColor: 'text-blue-900 dark:text-blue-100',
            subColor: 'text-blue-600/70',
        },
        {
            label: 'Pending',
            value: pending.toLocaleString(),
            sub: 'awaiting action',
            bg: 'bg-gradient-to-br from-amber-50 to-amber-100/50 dark:from-amber-950/30 dark:to-amber-900/20',
            labelColor: 'text-amber-700 dark:text-amber-300',
            valueColor: 'text-amber-900 dark:text-amber-100',
            subColor: 'text-amber-600/70',
        },
        {
            label: 'Completed',
            value: done.toLocaleString(),
            sub: `${pct}% completion rate`,
            bg: 'bg-gradient-to-br from-emerald-50 to-emerald-100/50 dark:from-emerald-950/30 dark:to-emerald-900/20',
            labelColor: 'text-emerald-700 dark:text-emerald-300',
            valueColor: 'text-emerald-900 dark:text-emerald-100',
            subColor: 'text-emerald-600/70',
        },
    ];
});

// ── Top defects ───────────────────────────────────────────────────────────────
const topDefects = computed(() => {
    const map: Record<string, number> = {};
    rows.value.forEach((r) => {
        if (!r.qc_defect) return;
        r.qc_defect
            .split(',')
            .map((s) => s.trim())
            .filter(Boolean)
            .forEach((code) => {
                map[code] = (map[code] ?? 0) + 1;
            });
    });
    return Object.entries(map)
        .map(([code, count]) => ({ code, count }))
        .sort((a, b) => b.count - a.count);
});

// ── Flow distribution ─────────────────────────────────────────────────────────
const flowStats = computed(() => {
    const total = rows.value.length || 1;
    const qcA = rows.value.filter(
        (r) => r.defect_class === 'QC Analysis',
    ).length;
    const viT = rows.value.filter(
        (r) => r.defect_class === "Tech'l Verification",
    ).length;
    const qcOk = rows.value.filter((r) => r.qc_result === 'OK').length;
    const other = total - qcA - viT - qcOk;

    return [
        {
            label: 'QC Analysis',
            count: qcA,
            pct: Math.round((qcA / total) * 100),
            color: 'text-indigo-600',
            bar: 'bg-indigo-500',
        },
        {
            label: "Tech'l Verif.",
            count: viT,
            pct: Math.round((viT / total) * 100),
            color: 'text-purple-600',
            bar: 'bg-purple-500',
        },
        {
            label: 'QC OK',
            count: qcOk,
            pct: Math.round((qcOk / total) * 100),
            color: 'text-teal-600',
            bar: 'bg-teal-500',
        },
        {
            label: 'Other',
            count: other,
            pct: Math.round((other / total) * 100),
            color: 'text-slate-500',
            bar: 'bg-slate-400',
        },
    ];
});

// ── Watch out: prev-day pending ───────────────────────────────────────────────
const watchOutLots = computed(() => {
    const todayStr = new Date().toLocaleDateString('en-CA', {
        timeZone: 'Asia/Manila',
    });
    return rows.value
        .filter(
            (r) =>
                r.output_status === 'Pending' &&
                r.created_at &&
                new Date(r.created_at).toLocaleDateString('en-CA', {
                    timeZone: 'Asia/Manila',
                }) < todayStr,
        )
        .map((r) => {
            const mins = r.created_at
                ? Math.floor(
                      (Date.now() - new Date(r.created_at).getTime()) / 60000,
                  )
                : 0;
            const d = Math.floor(mins / 1440);
            const h = Math.floor((mins % 1440) / 60);
            const m = mins % 60;
            const elapsed =
                d > 0
                    ? `${d}d ${h}h ${m}m`
                    : h > 0
                      ? `${h}h ${m}m`
                      : `${m} min`;
            return { ...r, elapsed };
        })
        .sort((a, b) => (b.created_at ?? '').localeCompare(a.created_at ?? ''));
});

// ── Chart ─────────────────────────────────────────────────────────────────────
let chart: echarts.ECharts | null = null;

function buildChart() {
    const el = document.getElementById('trend-main-chart');
    if (!el) return;
    if (!chart) chart = echarts.init(el);

    // Group by date
    const byDate: Record<string, EndlineRow[]> = {};
    rows.value.forEach((r) => {
        if (!r.created_at) return;
        const d = new Date(r.created_at).toLocaleDateString('en-CA', {
            timeZone: 'Asia/Manila',
        });
        if (!byDate[d]) byDate[d] = [];
        byDate[d].push(r);
    });
    const dates = Object.keys(byDate).sort();

    let series: echarts.SeriesOption[] = [];

    if (chartMode.value === 'volume') {
        series = [
            {
                name: 'Lots',
                type: 'bar',
                data: dates.map((d) => byDate[d].length),
                itemStyle: { color: '#3b82f6', borderRadius: [4, 4, 0, 0] },
                label: {
                    show: true,
                    position: 'top',
                    fontSize: 10,
                    color: '#64748b',
                },
            },
        ];
    } else if (chartMode.value === 'defect') {
        const qcA = dates.map(
            (d) =>
                byDate[d].filter((r) => r.defect_class === 'QC Analysis')
                    .length,
        );
        const viT = dates.map(
            (d) =>
                byDate[d].filter(
                    (r) => r.defect_class === "Tech'l Verification",
                ).length,
        );
        const qcOk = dates.map(
            (d) => byDate[d].filter((r) => r.qc_result === 'OK').length,
        );
        series = [
            {
                name: 'QC Analysis',
                type: 'bar',
                stack: 'a',
                data: qcA,
                itemStyle: { color: '#6366f1' },
            },
            {
                name: "Tech'l Verif.",
                type: 'bar',
                stack: 'a',
                data: viT,
                itemStyle: { color: '#a855f7' },
            },
            {
                name: 'QC OK',
                type: 'bar',
                stack: 'a',
                data: qcOk,
                itemStyle: { color: '#14b8a6' },
            },
        ];
    } else {
        const pending = dates.map(
            (d) =>
                byDate[d].filter((r) => r.output_status === 'Pending').length,
        );
        const done = dates.map(
            (d) =>
                byDate[d].filter(
                    (r) =>
                        r.output_status === 'Completed' ||
                        r.output_status === 'Rework',
                ).length,
        );
        series = [
            {
                name: 'Pending',
                type: 'bar',
                stack: 'o',
                data: pending,
                itemStyle: { color: '#f59e0b' },
            },
            {
                name: 'Completed',
                type: 'bar',
                stack: 'o',
                data: done,
                itemStyle: { color: '#10b981' },
            },
        ];
    }

    chart.setOption(
        {
            tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
            legend: { bottom: 0, textStyle: { fontSize: 11 } },
            grid: { top: 10, left: 40, right: 20, bottom: 40 },
            xAxis: {
                type: 'category',
                data: dates.map((d) => d.slice(5)), // MM-DD
                axisLabel: { fontSize: 10, rotate: dates.length > 14 ? 45 : 0 },
            },
            yAxis: { type: 'value', axisLabel: { fontSize: 10 } },
            series,
        },
        true,
    );
}

// ── Fetch ─────────────────────────────────────────────────────────────────────
async function fetchData() {
    loading.value = true;
    try {
        const { data } = await axios.get<EndlineRow[]>('/api/endline-delay', {
            params: {
                date_from: filterFrom.value || undefined,
                date_to: filterTo.value || undefined,
                work_type: filterWorktype.value || undefined,
                lipas_yn: filterLipas.value || undefined,
            },
        });
        rows.value = data ?? [];
        lastUpdated.value = new Date().toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
        });
        buildChart();
    } catch {
        console.error('Failed to load trend data');
    } finally {
        loading.value = false;
    }
}

function onResize() {
    chart?.resize();
}

onMounted(() => {
    fetchData();
    window.addEventListener('resize', onResize);
});
onBeforeUnmount(() => {
    chart?.dispose();
    window.removeEventListener('resize', onResize);
});
</script>
