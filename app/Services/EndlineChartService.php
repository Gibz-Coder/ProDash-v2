<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EndlineChartService
{
    /** @var array<string, array{0: string, 1: string}> */
    private const CUTOFF_RANGES = [
        '00:00~03:59' => ['00:00:00', '03:59:59'],
        '04:00~06:59' => ['04:00:00', '06:59:59'],
        '07:00~11:59' => ['07:00:00', '11:59:59'],
        '12:00~15:59' => ['12:00:00', '15:59:59'],
        '16:00~18:59' => ['16:00:00', '18:59:59'],
        '19:00~23:59' => ['19:00:00', '23:59:59'],
    ];

    /** @var list<string> */
    private const SIZE_CODES = ['03', '05', '10', '21', '31', '32'];

    /** @var list<string> */
    private const BUCKET_NAMES = ['Mainlot', 'R-rework', 'L-rework'];

    /**
     * Map category toggle values to the defect_class stored in endline_delay.
     *
     * @var array<string, string>
     */
    private const CATEGORY_TO_DEFECT_CLASS = [
        'QC Analysis' => 'QC Analysis',
        'Technical'   => "Tech'l Verification",
    ];

    /**
     * Build and return the three chart datasets for the given filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *   pie: array{labels: list<string>, series: list<int>},
     *   bar: array{categories: list<string>, series: list<array{name: string, data: list<int>}>},
     *   column: array{categories: list<string>, series: list<array{name: string, data: list<int>}>}
     * }
     */
    public function getChartData(array $filters): array
    {
        return [
            'pie'    => $this->buildPieData($filters),
            'bar'    => $this->buildBarData($filters),
            'column' => $this->buildColumnData($filters),
        ];
    }

    // -------------------------------------------------------------------------
    // Pie chart — bucketed by qc_result content
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $filters */
    private function buildPieData(array $filters): array
    {
        $rows = $this->buildBaseQuery($filters)
            ->select('qc_result', DB::raw('COUNT(*) as cnt'))
            ->groupBy('qc_result')
            ->get();

        $totals = ['Mainlot' => 0, 'R-rework' => 0, 'L-rework' => 0];

        foreach ($rows as $row) {
            $bucket           = $this->classifyQcBucket((string) ($row->qc_result ?? ''));
            $totals[$bucket] += (int) $row->cnt;
        }

        return [
            'labels' => self::BUCKET_NAMES,
            'series' => array_values($totals),
        ];
    }

    // -------------------------------------------------------------------------
    // Bar chart — per size, bucketed by qc_result content
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $filters */
    private function buildBarData(array $filters): array
    {
        $rows = $this->buildBaseQuery($filters)
            ->tap(fn ($q) => $this->applyBucketFilter($q, $filters['work_type_filter'] ?? null))
            ->select(
                DB::raw("SUBSTRING(model, 3, 2) as size"),
                'qc_result',
                DB::raw('COUNT(*) as cnt'),
            )
            ->whereRaw("SUBSTRING(model, 3, 2) IN ('03','05','10','21','31','32')")
            ->groupBy(DB::raw("SUBSTRING(model, 3, 2)"), 'qc_result')
            ->get();

        $bucketed = $rows->map(fn (object $row): object => (object) [
            'key' => $this->classifyQcBucket((string) ($row->qc_result ?? '')),
            'cat' => $row->size,
            'cnt' => (int) $row->cnt,
        ]);

        return [
            'categories' => self::SIZE_CODES,
            'series'     => $this->pivotToStackedSeries($bucketed, self::SIZE_CODES, self::BUCKET_NAMES),
        ];
    }

    // -------------------------------------------------------------------------
    // Column chart — per defect code, stacked by size (only codes with data)
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $filters */
    private function buildColumnData(array $filters): array
    {
        $category       = (string) ($filters['category'] ?? 'QC Analysis');
        $workTypeFilter = $filters['work_type_filter'] ?? null;

        $colQuery = $this->buildBaseQuery($filters);

        $this->applyBucketFilter($colQuery, $workTypeFilter);

        $rows = $colQuery
            ->join(
                'qc_defect_class',
                DB::raw("TRIM(SUBSTRING_INDEX(endline_delay.qc_defect, ',', 1))"),
                '=',
                'qc_defect_class.defect_code',
            )
            ->select(
                DB::raw("SUBSTRING(endline_delay.model, 3, 2) as size"),
                'qc_defect_class.defect_code',
                DB::raw('COUNT(*) as cnt'),
            )
            ->whereRaw("SUBSTRING(endline_delay.model, 3, 2) IN ('03','05','10','21','31','32')")
            ->groupBy(DB::raw("SUBSTRING(endline_delay.model, 3, 2)"), 'qc_defect_class.defect_code')
            ->get();

        if ($rows->isEmpty()) {
            return ['categories' => [], 'series' => []];
        }

        $activeDefectCodes = $rows->pluck('defect_code')->unique()->sort()->values()->all();

        $mapped = $rows->map(fn (object $row): object => (object) [
            'key' => $row->size,
            'cat' => $row->defect_code,
            'cnt' => (int) $row->cnt,
        ]);

        return [
            'categories' => $activeDefectCodes,
            'series'     => $this->pivotToStackedSeries($mapped, $activeDefectCodes, self::SIZE_CODES),
        ];
    }

    // -------------------------------------------------------------------------
    // Shared helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $filters */
    private function buildBaseQuery(array $filters): Builder
    {
        $query = DB::table('endline_delay');

        if (!empty($filters['defect_class'])) {
            // For VI Technical, also include QC Analysis lots handed off via vi_techl_start
            if ($filters['defect_class'] === "Tech'l Verification") {
                $query->where(function (Builder $q): void {
                    $q->where('endline_delay.defect_class', "Tech'l Verification")
                      ->orWhere(function (Builder $q2): void {
                          $q2->where('endline_delay.defect_class', 'QC Analysis')
                             ->whereNotNull('endline_delay.vi_techl_start');
                      });
                });
            } else {
                $query->where('endline_delay.defect_class', $filters['defect_class']);
            }
        }

        if (!empty($filters['date'])) {
            $query->whereDate('endline_delay.created_at', $filters['date']);
        }

        if (!empty($filters['shift'])) {
            $shift = $filters['shift'];
            if ($shift === 'DAY') {
                $query->whereTime('endline_delay.created_at', '>=', '07:00:00')
                      ->whereTime('endline_delay.created_at', '<', '19:00:00');
            } elseif ($shift === 'NIGHT') {
                $query->where(function (Builder $q): void {
                    $q->whereTime('endline_delay.created_at', '>=', '19:00:00')
                      ->orWhereTime('endline_delay.created_at', '<', '07:00:00');
                });
            }
        }

        if (!empty($filters['cutoff']) && isset(self::CUTOFF_RANGES[$filters['cutoff']])) {
            [$from, $to] = self::CUTOFF_RANGES[$filters['cutoff']];
            $query->whereTime('endline_delay.created_at', '>=', $from)
                  ->whereTime('endline_delay.created_at', '<=', $to);
        }

        if (!empty($filters['work_type'])) {
            $query->where('endline_delay.work_type', $filters['work_type']);
        }

        if (!empty($filters['lipas_yn'])) {
            $query->where('endline_delay.lipas_yn', $filters['lipas_yn']);
        }

        if (!empty($filters['status_filter'])) {
            $today = now()->toDateString();
            match ($filters['status_filter']) {
                'pending'    => $query->whereNull('endline_delay.qc_ana_result')
                                      ->whereNull('endline_delay.qc_ana_prog')
                                      ->whereNull('endline_delay.vi_techl_result')
                                      ->whereNull('endline_delay.vi_techl_prog'),
                'inprogress' => $query->where(function (Builder $q): void {
                    $q->whereNotNull('endline_delay.qc_ana_prog')
                      ->orWhereNotNull('endline_delay.vi_techl_prog');
                })->where(function (Builder $q): void {
                    $q->whereNull('endline_delay.qc_ana_result')
                      ->whereNull('endline_delay.vi_techl_result');
                }),
                'completed'  => $query->where(function (Builder $q): void {
                    $q->whereNotNull('endline_delay.qc_ana_result')
                      ->orWhereNotNull('endline_delay.vi_techl_result');
                }),
                'prevday'    => $query->whereNull('endline_delay.qc_ana_result')
                                      ->whereNull('endline_delay.vi_techl_result')
                                      ->whereDate('endline_delay.created_at', '<', $today),
                default      => null,
            };
        }

        return $query;
    }

    /**
     * Apply a qc_result-based bucket filter to an existing query.
     * Used consistently across all three chart builders.
     */
    private function applyBucketFilter(Builder $query, ?string $bucket): void
    {
        if ($bucket === null || $bucket === 'All') {
            return;
        }

        match ($bucket) {
            'Mainlot'  => $query->where('qc_result', 'like', '%Main%'),
            'R-rework' => $query->where('qc_result', 'like', '%RR%')
                                ->where('qc_result', 'not like', '%Main%'),
            'L-rework' => $query->where(function (Builder $q): void {
                $q->where('qc_result', 'like', '%LY%')
                  ->where('qc_result', 'not like', '%RR%')
                  ->where('qc_result', 'not like', '%Main%');
            }),
            default => null,
        };
    }

    /**
     * Classify a qc_result string into one of the three chart buckets.
     *
     * - Mainlot  : contains "Main"
     * - R-rework : contains "RR" (with or without "LY")
     * - L-rework : contains "LY" but NOT "RR"
     * - Mainlot  : fallback
     */
    private function classifyQcBucket(string $qcResult): string
    {
        $hasMain = stripos($qcResult, 'Main') !== false;
        $hasRr   = stripos($qcResult, 'RR') !== false;
        $hasLy   = stripos($qcResult, 'LY') !== false;

        if ($hasMain) {
            return 'Mainlot';
        }

        if ($hasRr) {
            return 'R-rework';
        }

        if ($hasLy) {
            return 'L-rework';
        }

        return 'Mainlot';
    }

    /**
     * Pivot a flat collection into a stacked-series array, zero-filling gaps.
     *
     * Each item must have: `key` (series name), `cat` (x-axis category), `cnt` (int count).
     *
     * @param  Collection<int, object>  $rows
     * @param  list<string>             $xCategories
     * @param  list<string>             $seriesNames
     * @return list<array{name: string, data: list<int>}>
     */
    private function pivotToStackedSeries(Collection $rows, array $xCategories, array $seriesNames): array
    {
        /** @var array<string, array<string, int>> $lookup */
        $lookup = [];

        foreach ($rows as $row) {
            $lookup[$row->key][$row->cat] = ($lookup[$row->key][$row->cat] ?? 0) + (int) $row->cnt;
        }

        $series = [];

        foreach ($seriesNames as $name) {
            $data = [];
            foreach ($xCategories as $cat) {
                $data[] = $lookup[$name][$cat] ?? 0;
            }
            $series[] = ['name' => $name, 'data' => $data];
        }

        return $series;
    }
}
