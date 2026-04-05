import ApexCharts from 'apexcharts';
import axios from 'axios';
import type { Ref } from 'vue';
import { onBeforeUnmount, ref } from 'vue';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export type WorkTypeBucket = 'All' | 'Mainlot' | 'RL-Rework';
export type ChartCategory = 'Technical' | 'QC Analysis';

export interface UseEndlineChartsOptions {
    chartIdPrefix: string;
    defaultCategory: ChartCategory;
    getParams: () => Record<string, string | undefined>;
    chartDataUrl?: string;
    pieLabels?: string[];
    pieColors?: string[];
    barSeriesNames?: string[];
    barColors?: string[];
}

export interface UseEndlineChartsReturn {
    activeWorkType: Ref<WorkTypeBucket>;
    activeCategory: Ref<ChartCategory>;
    setWorkType: (wt: WorkTypeBucket) => void;
    setCategory: (cat: ChartCategory) => void;
    fetchChartData: () => Promise<void>;
    initCharts: () => void;
    destroyCharts: () => void;
}

// ---------------------------------------------------------------------------
// Shared chart styling constants
// ---------------------------------------------------------------------------

const BUCKET_COLORS = ['#3b82f6', '#10b981', '#f59e0b'] as const;
const SIZE_COLORS = [
    '#3b82f6',
    '#10b981',
    '#f59e0b',
    '#ef4444',
    '#8b5cf6',
    '#06b6d4',
] as const;
const AXIS_LABEL_STYLE = {
    colors: '#8c9097',
    fontSize: '11px',
    fontWeight: 600,
} as const;
const NO_DATA_TEXT = 'No data for selected filters';

// ---------------------------------------------------------------------------
// Composable
// ---------------------------------------------------------------------------

export function useEndlineCharts(
    options: UseEndlineChartsOptions,
): UseEndlineChartsReturn {
    const {
        chartIdPrefix,
        defaultCategory,
        getParams,
        chartDataUrl = '/api/endline-delay/chart-data',
        pieLabels = ['Mainlot', 'R-rework', 'L-rework'],
        pieColors = ['#3b82f6', '#10b981', '#f59e0b'],
        barSeriesNames = ['Mainlot', 'R-rework', 'L-rework'],
        barColors = ['#3b82f6', '#10b981', '#f59e0b'],
    } = options;

    const activeWorkType = ref<WorkTypeBucket>('All');
    const activeCategory = ref<ChartCategory>(defaultCategory);

    let pieChart: ApexCharts | null = null;
    let barChart: ApexCharts | null = null;
    let columnChart: ApexCharts | null = null;
    let abortController: AbortController | null = null;

    // -----------------------------------------------------------------------
    // Chart initialisation
    // -----------------------------------------------------------------------

    function initCharts(): void {
        const pieEl = document.querySelector(`#${chartIdPrefix}-pie-chart`);
        const barEl = document.querySelector(`#${chartIdPrefix}-bar-chart`);
        const columnEl = document.querySelector(
            `#${chartIdPrefix}-column-chart`,
        );

        if (pieEl) {
            pieChart = new ApexCharts(pieEl, buildPieOptions());
            pieChart.render();
        }

        if (barEl) {
            barChart = new ApexCharts(barEl, buildBarOptions());
            barChart.render();
        }

        if (columnEl) {
            columnChart = new ApexCharts(columnEl, buildColumnOptions());
            columnChart.render();
        }
    }

    function destroyCharts(): void {
        pieChart?.destroy();
        barChart?.destroy();
        columnChart?.destroy();
        pieChart = null;
        barChart = null;
        columnChart = null;
    }

    onBeforeUnmount(destroyCharts);

    // -----------------------------------------------------------------------
    // Data fetching
    // -----------------------------------------------------------------------

    async function fetchChartData(): Promise<void> {
        // Cancel any in-flight request (Property 9 / Requirements 7.5, 8.4)
        abortController?.abort();
        abortController = new AbortController();

        const params: Record<string, string> = {};

        // Merge page-level filters, dropping undefined/empty values
        for (const [key, val] of Object.entries(getParams())) {
            if (val !== undefined && val !== '') {
                params[key] = val;
            }
        }

        params['defect_class'] =
            defaultCategory === 'Technical'
                ? "Tech'l Verification"
                : defaultCategory;
        if (activeWorkType.value !== 'All') {
            params['work_type_filter'] = activeWorkType.value;
        }
        params['category'] = activeCategory.value;

        try {
            const response = await axios.get(chartDataUrl, {
                params,
                signal: abortController.signal,
            });

            if (!response.data?.success) {
                return;
            }

            const data = response.data.data;
            updateCharts(data);
        } catch (err: unknown) {
            if (axios.isCancel(err)) {
                return; // Intentional abort — do not warn
            }
            console.warn('[useEndlineCharts] Failed to fetch chart data:', err);
            // Retain last valid chart data — no update on error (Requirement 9.3)
        }
    }

    // -----------------------------------------------------------------------
    // Chart updates (no destroy/re-init — Requirement 6.6)
    // -----------------------------------------------------------------------

    function updateCharts(data: {
        pie: { labels: string[]; series: number[] };
        bar: {
            categories: string[];
            series: { name: string; data: number[] }[];
        };
        column: {
            categories: string[];
            series: { name: string; data: number[] }[];
        };
    }): void {
        const isEmpty = (series: { data: number[] }[]): boolean =>
            series.every((s) => s.data.every((v) => v === 0));

        const noDataOpts = (empty: boolean) =>
            empty
                ? { noData: { text: NO_DATA_TEXT } }
                : { noData: { text: '' } };

        if (pieChart) {
            const pieEmpty = data.pie.series.every((v) => v === 0);
            pieChart.updateOptions({
                labels: data.pie.labels,
                ...noDataOpts(pieEmpty),
            });
            pieChart.updateSeries(data.pie.series);
        }

        if (barChart) {
            const barEmpty = isEmpty(data.bar.series);
            barChart.updateOptions({
                xaxis: { categories: data.bar.categories },
                ...noDataOpts(barEmpty),
            });
            barChart.updateSeries(data.bar.series);
        }

        if (columnChart) {
            const colEmpty = isEmpty(data.column.series);
            columnChart.updateOptions({
                xaxis: {
                    categories: data.column.categories,
                    labels: {
                        show: true,
                        rotate: -45,
                        rotateAlways: true,
                        style: {
                            colors: '#8c9097',
                            fontSize: '10px',
                            fontWeight: 600,
                        },
                    },
                },
                ...noDataOpts(colEmpty),
            });
            columnChart.updateSeries(data.column.series);
        }
    }

    // -----------------------------------------------------------------------
    // Toggle handlers (Requirements 7.1–7.4)
    // -----------------------------------------------------------------------

    function setWorkType(wt: WorkTypeBucket): void {
        activeWorkType.value = wt;
        fetchChartData();
    }

    function setCategory(cat: ChartCategory): void {
        activeCategory.value = cat;
        fetchChartData();
    }

    // -----------------------------------------------------------------------
    // Initial option builders (placeholder series — replaced on first fetch)
    // -----------------------------------------------------------------------

    function buildPieOptions(): object {
        return {
            series: pieLabels.map(() => 0),
            chart: { height: 300, type: 'pie' },
            colors: pieColors,
            labels: pieLabels,
            noData: { text: NO_DATA_TEXT },
            legend: {
                position: 'bottom',
                fontSize: '11px',
                horizontalAlign: 'center',
                markers: { width: 10, height: 10, radius: 2 },
                itemMargin: { horizontal: 8, vertical: 4 },
            },
            dataLabels: {
                enabled: true,
                formatter: (val: number, opts: any) => {
                    const count = opts.w.globals.series[opts.seriesIndex];
                    return `${val.toFixed(1)}%\n(${count})`;
                },
                style: { fontSize: '11px', fontWeight: 600 },
                dropShadow: { enabled: false },
            },
            tooltip: {
                y: {
                    formatter: (val: number, opts: any) => {
                        const total = opts.globals.seriesTotals.reduce(
                            (a: number, b: number) => a + b,
                            0,
                        );
                        const pct =
                            total > 0
                                ? ((val / total) * 100).toFixed(1)
                                : '0.0';
                        return `${val} lots (${pct}%)`;
                    },
                },
            },
            responsive: [
                {
                    breakpoint: 480,
                    options: {
                        chart: { width: '100%' },
                        legend: { position: 'bottom', fontSize: '10px' },
                    },
                },
            ],
        };
    }

    function buildBarOptions(): object {
        return {
            series: barSeriesNames.map((name) => ({
                name,
                data: [0, 0, 0, 0, 0, 0],
            })),
            chart: {
                type: 'bar',
                height: 300,
                stacked: true,
                toolbar: { show: false },
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 2,
                    dataLabels: {
                        total: {
                            enabled: true,
                            style: { fontSize: '11px', fontWeight: 700 },
                        },
                    },
                },
            },
            stroke: { width: 1, colors: ['#fff'] },
            colors: barColors,
            grid: { borderColor: '#f2f5f7' },
            noData: { text: NO_DATA_TEXT },
            xaxis: {
                categories: ['03', '05', '10', '21', '31', '32'],
                labels: { show: true, style: AXIS_LABEL_STYLE },
            },
            yaxis: { labels: { show: true, style: AXIS_LABEL_STYLE } },
            tooltip: {
                shared: true,
                intersect: false,
                y: { formatter: (val: number) => `${val} lots` },
            },
            fill: { opacity: 1 },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                fontSize: '11px',
                markers: { width: 10, height: 10, radius: 2 },
                itemMargin: { horizontal: 8, vertical: 4 },
            },
            dataLabels: {
                enabled: true,
                formatter: (val: number) => (val > 0 ? val.toString() : ''),
                style: { fontSize: '10px', fontWeight: 600, colors: ['#fff'] },
                dropShadow: { enabled: false },
            },
        };
    }

    function buildColumnOptions(): object {
        return {
            series: [],
            chart: {
                type: 'bar',
                height: 300,
                stacked: true,
                toolbar: { show: false },
                zoom: { enabled: true },
            },
            colors: [...SIZE_COLORS],
            plotOptions: { bar: { horizontal: false, borderRadius: 2 } },
            grid: { borderColor: '#f2f5f7' },
            noData: { text: NO_DATA_TEXT },
            xaxis: {
                categories: [],
                labels: {
                    show: true,
                    rotate: -45,
                    rotateAlways: true,
                    style: {
                        colors: '#8c9097',
                        fontSize: '10px',
                        fontWeight: 600,
                    },
                },
            },
            yaxis: { labels: { show: true, style: AXIS_LABEL_STYLE } },
            legend: {
                position: 'top',
                horizontalAlign: 'center',
                fontSize: '11px',
                markers: { width: 10, height: 10, radius: 2 },
                itemMargin: { horizontal: 8, vertical: 4 },
            },
            fill: { opacity: 1 },
            responsive: [
                {
                    breakpoint: 480,
                    options: {
                        legend: {
                            position: 'bottom',
                            offsetX: -10,
                            offsetY: 0,
                        },
                    },
                },
            ],
        };
    }

    // -----------------------------------------------------------------------

    return {
        activeWorkType,
        activeCategory,
        setWorkType,
        setCategory,
        fetchChartData,
        initCharts,
        destroyCharts,
    };
}
