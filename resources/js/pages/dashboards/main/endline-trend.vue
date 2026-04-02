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
            <!-- ROW 1: Title -->
            <div>
                <h1 class="text-xl leading-tight font-bold">Endline Trend</h1>
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

            <!-- ROW 2: 5 KPI cards -->
            <div class="grid grid-cols-5 gap-3">
                <div
                    v-for="kpi in kpiCards"
                    :key="kpi.label"
                    class="rounded-xl border border-border/50 px-4 py-5 shadow-sm"
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

            <!-- ROW 3: 4 panels — first 3 equal narrow, 4th slightly wider -->
            <div class="grid min-h-0 flex-1 grid-cols-9 gap-4">
                <!-- Panel 1: Monthly Trend -->
                <div
                    class="col-span-2 flex h-full flex-col rounded-xl border border-border/50 bg-card p-3 shadow-sm"
                >
                    <p
                        class="mb-2 shrink-0 text-[10px] font-bold tracking-widest text-muted-foreground uppercase"
                    >
                        Monthly Trend
                    </p>
                    <div
                        v-if="loading"
                        class="flex min-h-0 flex-1 items-center justify-center"
                    >
                        <div
                            class="h-7 w-7 animate-spin rounded-full border-4 border-primary border-r-transparent"
                        ></div>
                    </div>
                    <div
                        v-else
                        ref="chartEl"
                        id="trend-main-chart"
                        class="min-h-0 w-full flex-1"
                    ></div>
                </div>

                <!-- Panel 2: Weekly Trend -->
                <div
                    class="col-span-2 flex h-full flex-col rounded-xl border border-border/50 bg-card p-3 shadow-sm"
                >
                    <p
                        class="mb-2 shrink-0 text-[10px] font-bold tracking-widest text-muted-foreground uppercase"
                    >
                        Weekly Trend
                    </p>
                </div>

                <!-- Panel 3: Daily Trend -->
                <div
                    class="col-span-2 flex h-full flex-col rounded-xl border border-border/50 bg-card p-3 shadow-sm"
                >
                    <p
                        class="mb-2 shrink-0 text-[10px] font-bold tracking-widest text-muted-foreground uppercase"
                    >
                        Daily Trend
                    </p>
                </div>

                <!-- Panel 4: Every Cut-Off Trend -->
                <div
                    class="col-span-3 flex h-full flex-col rounded-xl border border-border/50 bg-card p-3 shadow-sm"
                >
                    <p
                        class="mb-2 shrink-0 text-[10px] font-bold tracking-widest text-muted-foreground uppercase"
                    >
                        Every Cut-Off Trend
                    </p>
                </div>
            </div>

            <!-- ROW 4: 4 cards, fixed height with scroll -->
            <div class="grid grid-cols-4 gap-4">
                <!-- Top Defect Code -->
                <div
                    class="flex h-[220px] flex-col rounded-xl border border-border/50 bg-card p-4 shadow-sm"
                >
                    <div class="mb-3 flex shrink-0 items-center gap-2">
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
                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <div v-if="topDefects.length" class="space-y-2">
                            <div
                                v-for="(d, i) in topDefects"
                                :key="d.code"
                                class="flex items-center gap-2"
                            >
                                <span
                                    class="w-4 text-[10px] font-bold text-muted-foreground"
                                    >{{ i + 1 }}</span
                                >
                                <div class="flex-1">
                                    <div
                                        class="flex items-center justify-between"
                                    >
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
                        <p v-else class="text-xs text-muted-foreground">
                            No data
                        </p>
                    </div>
                </div>

                <!-- QC Inspection Lot Distribution -->
                <div
                    class="flex h-[220px] flex-col rounded-xl border border-border/50 bg-card p-4 shadow-sm"
                >
                    <div class="mb-3 flex shrink-0 items-center gap-2">
                        <div
                            class="rounded-full bg-indigo-500/10 p-1.5 ring-1 ring-indigo-500/20"
                        >
                            <GitBranch class="h-4 w-4 text-indigo-600" />
                        </div>
                        <p
                            class="text-xs font-bold tracking-wide text-foreground uppercase"
                        >
                            QC Inspection Lot Distribution
                        </p>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <div class="space-y-3">
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
                                    <div
                                        class="h-4 w-full rounded-full bg-muted"
                                    >
                                        <div
                                            class="h-4 rounded-full transition-all"
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

                        <!-- Distribution insight -->
                        <div
                            v-if="qcAnalysisInsight"
                            class="mt-3 flex items-center gap-1.5 border-t border-border/40 pt-3"
                        >
                            <AlertCircle
                                v-if="
                                    qcAnalysisInsight.type === 'warn' ||
                                    qcAnalysisInsight.type === 'alert'
                                "
                                class="h-3.5 w-3.5 shrink-0"
                                :class="qcAnalysisInsight.color"
                            />
                            <CheckCircle2
                                v-else-if="qcAnalysisInsight.type === 'ok'"
                                class="h-3.5 w-3.5 shrink-0"
                                :class="qcAnalysisInsight.color"
                            />
                            <Info
                                v-else
                                class="h-3.5 w-3.5 shrink-0"
                                :class="qcAnalysisInsight.color"
                            />
                            <p
                                class="text-[10px] leading-snug"
                                :class="qcAnalysisInsight.color"
                            >
                                {{ qcAnalysisInsight.text }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Delay & Pending Lots -->
                <div
                    class="flex h-[220px] flex-col rounded-xl border border-border/50 bg-card p-4 shadow-sm"
                >
                    <div class="mb-3 flex shrink-0 items-center gap-2">
                        <div
                            class="rounded-full bg-amber-500/10 p-1.5 ring-1 ring-amber-500/20"
                        >
                            <Clock class="h-4 w-4 text-amber-600" />
                        </div>
                        <p
                            class="text-xs font-bold tracking-wide text-foreground uppercase"
                        >
                            Delay & Pending Lots
                        </p>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <div v-if="watchOutLots.length" class="space-y-2">
                            <div
                                v-for="lot in watchOutLots"
                                :key="lot.lot_id"
                                class="flex items-center justify-between rounded-lg bg-amber-50/50 px-2.5 py-1.5 dark:bg-amber-950/20"
                            >
                                <div>
                                    <p
                                        class="font-mono text-xs font-bold text-primary"
                                    >
                                        {{ lot.lot_id }}
                                    </p>
                                    <p
                                        class="text-[10px] text-muted-foreground"
                                    >
                                        {{ lot.defect_class || '—' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p
                                        class="text-xs font-semibold text-amber-600"
                                    >
                                        {{ lot.elapsed }}
                                    </p>
                                    <p
                                        class="text-[10px] text-muted-foreground"
                                    >
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

                <!-- Watch Out & Reminders -->
                <div
                    class="flex h-[220px] flex-col rounded-xl border border-border/50 bg-card p-4 shadow-sm"
                >
                    <div class="mb-3 flex shrink-0 items-center gap-2">
                        <div
                            class="rounded-full bg-sky-500/10 p-1.5 ring-1 ring-sky-500/20"
                        >
                            <Lightbulb class="h-4 w-4 text-sky-600" />
                        </div>
                        <p
                            class="text-xs font-bold tracking-wide text-foreground uppercase"
                        >
                            Watch Out & Reminders
                        </p>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <div class="space-y-2">
                            <div
                                v-for="insight in distributionInsights"
                                :key="insight.text"
                                class="flex items-center gap-2 rounded-lg px-2.5 py-2"
                                :class="insight.bg"
                            >
                                <AlertCircle
                                    v-if="insight.type === 'warn'"
                                    class="h-3.5 w-3.5 shrink-0 text-amber-600"
                                />
                                <AlertCircle
                                    v-else-if="insight.type === 'alert'"
                                    class="h-3.5 w-3.5 shrink-0 text-red-600"
                                />
                                <CheckCircle2
                                    v-else-if="insight.type === 'ok'"
                                    class="h-3.5 w-3.5 shrink-0 text-teal-600"
                                />
                                <Clock
                                    v-else-if="insight.type === 'pending'"
                                    class="h-3.5 w-3.5 shrink-0 text-orange-600"
                                />
                                <Info
                                    v-else
                                    class="h-3.5 w-3.5 shrink-0 text-indigo-600"
                                />
                                <p
                                    class="text-[10px] leading-snug"
                                    :class="insight.color"
                                >
                                    {{ insight.text }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import * as echarts from 'echarts';
import {
    AlertCircle,
    CheckCircle2,
    Clock,
    GitBranch,
    Info,
    Lightbulb,
    RefreshCw,
} from 'lucide-vue-next';
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
    const qcOk = rows.value.filter((r) => r.qc_result === 'OK').length;
    const qcAnalysis = rows.value.filter(
        (r) => r.defect_class === 'QC Analysis',
    ).length;
    const viTechnical = rows.value.filter(
        (r) => r.defect_class === "Tech'l Verification",
    ).length;
    // Average TAT in hours (from created_at to now, for completed lots)
    const completedWithTime = rows.value.filter(
        (r) =>
            (r.output_status === 'Completed' || r.output_status === 'Rework') &&
            r.created_at,
    );
    const avgTatHrs = completedWithTime.length
        ? Math.round(
              completedWithTime.reduce(
                  (s, r) =>
                      s +
                      (Date.now() - new Date(r.created_at!).getTime()) /
                          3600000,
                  0,
              ) / completedWithTime.length,
          )
        : 0;
    const avgTat =
        avgTatHrs >= 24
            ? `${Math.floor(avgTatHrs / 24)}d ${avgTatHrs % 24}h`
            : `${avgTatHrs}h`;

    return [
        {
            label: 'Total Inspected Lots',
            value: total.toLocaleString(),
            sub: `${(totalQty / 1000).toFixed(1)}K pcs`,
            bg: 'bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-950/30 dark:to-blue-900/20',
            labelColor: 'text-blue-700 dark:text-blue-300',
            valueColor: 'text-blue-900 dark:text-blue-100',
            subColor: 'text-blue-600/70',
        },
        {
            label: 'QC OK Lots',
            value: qcOk.toLocaleString(),
            sub: total ? `${Math.round((qcOk / total) * 100)}% pass rate` : '—',
            bg: 'bg-gradient-to-br from-emerald-50 to-emerald-100/50 dark:from-emerald-950/30 dark:to-emerald-900/20',
            labelColor: 'text-emerald-700 dark:text-emerald-300',
            valueColor: 'text-emerald-900 dark:text-emerald-100',
            subColor: 'text-emerald-600/70',
        },
        {
            label: 'QC Analysis Lots',
            value: qcAnalysis.toLocaleString(),
            sub: total
                ? `${Math.round((qcAnalysis / total) * 100)}% of total`
                : '—',
            bg: 'bg-gradient-to-br from-amber-50 to-amber-100/50 dark:from-amber-950/30 dark:to-amber-900/20',
            labelColor: 'text-amber-700 dark:text-amber-300',
            valueColor: 'text-amber-900 dark:text-amber-100',
            subColor: 'text-amber-600/70',
        },
        {
            label: 'Vi Technical Lots',
            value: viTechnical.toLocaleString(),
            sub: total
                ? `${Math.round((viTechnical / total) * 100)}% of total`
                : '—',
            bg: 'bg-gradient-to-br from-violet-50 to-violet-100/50 dark:from-violet-950/30 dark:to-violet-900/20',
            labelColor: 'text-violet-700 dark:text-violet-300',
            valueColor: 'text-violet-900 dark:text-violet-100',
            subColor: 'text-violet-600/70',
        },
        {
            label: 'Average TAT',
            value: avgTat,
            sub: 'avg turnaround time',
            bg: 'bg-gradient-to-br from-orange-50 to-orange-100/50 dark:from-orange-950/30 dark:to-orange-900/20',
            labelColor: 'text-orange-700 dark:text-orange-300',
            valueColor: 'text-orange-900 dark:text-orange-100',
            subColor: 'text-orange-600/70',
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
    ];
});

// ── Distribution insights ─────────────────────────────────────────────────────
const qcAnalysisInsight = computed(() => {
    const total = rows.value.length || 1;
    const qcA = flowStats.value.find((f) => f.label === 'QC Analysis');
    const viT = flowStats.value.find((f) => f.label === "Tech'l Verif.");
    const qcOk = flowStats.value.find((f) => f.label === 'QC OK');

    // Pick the most notable insight across all 3 categories
    if (qcA && qcA.pct >= 50)
        return {
            type: 'warn',
            text: `High QC Analysis rate (${qcA.pct}%) — review defect root causes.`,
            color: 'text-amber-700 dark:text-amber-300',
        };
    if (viT && viT.pct >= 20)
        return {
            type: 'warn',
            text: `Tech'l Verification at ${viT.pct}% — escalate if unresolved.`,
            color: 'text-amber-700 dark:text-amber-300',
        };
    if (qcOk && qcOk.pct >= 80)
        return {
            type: 'ok',
            text: `Strong QC OK rate at ${qcOk.pct}% — good quality trend.`,
            color: 'text-teal-700 dark:text-teal-300',
        };
    if (qcOk && qcOk.pct < 30 && total > 5)
        return {
            type: 'alert',
            text: `Low QC OK rate (${qcOk.pct}%) — quality attention needed.`,
            color: 'text-red-700 dark:text-red-300',
        };
    if (qcA && qcA.count > 0)
        return {
            type: 'info',
            text: `${qcA.count} lots under QC Analysis — monitor closely.`,
            color: 'text-indigo-700 dark:text-indigo-300',
        };
    return null;
});
const distributionInsights = computed(() => {
    const total = rows.value.length || 1;
    const qcA = flowStats.value.find((f) => f.label === 'QC Analysis');
    const viT = flowStats.value.find((f) => f.label === "Tech'l Verif.");
    const qcOk = flowStats.value.find((f) => f.label === 'QC OK');
    const pending = rows.value.filter(
        (r) => r.output_status === 'Pending',
    ).length;
    const insights: {
        type: string;
        text: string;
        color: string;
        bg: string;
    }[] = [];

    if (qcA && qcA.pct >= 50)
        insights.push({
            type: 'warn',
            text: `High QC Analysis rate (${qcA.pct}%) — review defect root causes.`,
            color: 'text-amber-700 dark:text-amber-300',
            bg: 'bg-amber-50/60 dark:bg-amber-950/20',
        });
    else if (qcA && qcA.pct > 0)
        insights.push({
            type: 'info',
            text: `${qcA.count} lots under QC Analysis — monitor closely.`,
            color: 'text-indigo-700 dark:text-indigo-300',
            bg: 'bg-indigo-50/60 dark:bg-indigo-950/20',
        });

    if (viT && viT.pct >= 20)
        insights.push({
            type: 'warn',
            text: `Tech'l Verification at ${viT.pct}% — escalate if unresolved.`,
            color: 'text-amber-700 dark:text-amber-300',
            bg: 'bg-amber-50/60 dark:bg-amber-950/20',
        });
    else if (viT && viT.count > 0)
        insights.push({
            type: 'info',
            text: `${viT.count} lots pending technical verification.`,
            color: 'text-purple-700 dark:text-purple-300',
            bg: 'bg-purple-50/60 dark:bg-purple-950/20',
        });

    if (qcOk && qcOk.pct >= 80)
        insights.push({
            type: 'ok',
            text: `Strong QC OK rate at ${qcOk.pct}% — good quality trend.`,
            color: 'text-teal-700 dark:text-teal-300',
            bg: 'bg-teal-50/60 dark:bg-teal-950/20',
        });
    else if (qcOk && qcOk.pct < 30 && total > 5)
        insights.push({
            type: 'alert',
            text: `Low QC OK rate (${qcOk.pct}%) — quality attention needed.`,
            color: 'text-red-700 dark:text-red-300',
            bg: 'bg-red-50/60 dark:bg-red-950/20',
        });

    if (pending > 10)
        insights.push({
            type: 'pending',
            text: `${pending} lots still pending — check for bottlenecks.`,
            color: 'text-orange-700 dark:text-orange-300',
            bg: 'bg-orange-50/60 dark:bg-orange-950/20',
        });

    if (insights.length === 0)
        insights.push({
            type: 'ok',
            text: 'All metrics look normal. No immediate action required.',
            color: 'text-muted-foreground',
            bg: 'bg-muted/30',
        });

    return insights;
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
const chartEl = ref<HTMLElement | null>(null);
let resizeObserver: ResizeObserver | null = null;

function buildChart() {
    const el = document.getElementById('trend-main-chart');
    if (!el) return;
    if (!chart) {
        chart = echarts.init(el);
    } else {
        chart.resize();
    }

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
    // ResizeObserver keeps chart sized to its flex container
    const el = document.getElementById('trend-main-chart');
    if (el) {
        resizeObserver = new ResizeObserver(() => chart?.resize());
        resizeObserver.observe(el);
    }
});
onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    chart?.dispose();
    window.removeEventListener('resize', onResize);
});
</script>
