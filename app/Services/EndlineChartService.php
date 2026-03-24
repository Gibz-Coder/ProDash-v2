<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EndlineChartService
{
    /** @var array<string, string> */
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
     * Map raw work_type values to their display bucket name.
     *
     * @var array<string, string>
     */
    private const WORK_TYPE_MAP = [
        'NORMAL'     => 'Mainlot',
        'PROCESS RW' => 'R-rework',
        'WH REWORK'  => 'R-rework',
        'OI REWORK'  => 'L-rework',
    ];

    /**
     * Map bucket names back to the raw work_type values they cover.
     *
     * @var array<string, list<string>>
     */
    private const BUCKET_TO_WORK_TYPES = [
        'Mainlot'  => ['NORMAL'],
        'R-rework' => ['PROCESS RW', 'WH REWORK'],
        'L-rework' => ['OI REWORK'],
    ];

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
     * @return array{pie: array{labels: list<string>, series: list<int>}, bar: array{categories: list<string>, series: list<array{name: string, data: list<int>}>}, column: array{categories: list<string>, series: list<array{name: string, data: list<int>}>}}
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
    // Pie chart
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $filters */
    private function buildPieData(array $filters): array
    {
        $rows = $this->buildBaseQuery($filters)
            ->select('work_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('work_type')
            ->get();

        return $this->aggregateByWorkTypeBucket($rows);
    }

    // -------------------------------------------------------------------------
    // Bar chart
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $filters */
    private function buildBarData(array $filters): array
    {
        $rows = $this->buildBaseQuery($filters)
            ->select(
                DB::raw("SUBSTRING(model, 9, 2) as size"),
                'work_type',
                DB::raw('COUNT(*) as cnt'),
            )
            ->whereRaw("SUBSTRING(model, 9, 2) IN ('03','05','10','21','31','32')")
            ->groupBy('size', 'work_type')
            ->get();

        // Normalise: map work_type → bucket name so pivotToStackedSeries can key on it
        $bucketed = $rows->map(fn (object $row): object => (object) [
            'key' => self::WORK_TYPE_MAP[$row->work_type] ?? 'Mainlot',
            'cat' => $row->size,
            'cnt' => (int) $row->cnt,
        ]);

        return [
            'categories' => self::SIZE_CODES,
            'series'     => $this->pivotToStackedSeries($bucketed, self::SIZE_CODES, self::BUCKET_NAMES),
        ];
    }

    // -------------------------------------------------------------------------
    // Column chart
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $filters */
    private function buildColumnData(array $filters): array
    {
        $category       = (string) ($filters['category'] ?? 'QC Analysis');
        $workTypeFilter = $filters['work_type_filter'] ?? null;

        // Resolve x-axis defect codes from qc_defect_class
        $defectCodes = DB::table('qc_defect_class')
            ->where('defect_flow', $category)
            ->orderBy('defect_code')
            ->pluck('defect_code')
            ->all();

        if ($defectCodes === []) {
            return [
                'categories' => [],
                'series'     => [],
            ];
        }

        $colQuery = $this->buildBaseQuery($filters);

        // Apply work_type_filter bucket
        if ($workTypeFilter !== null && isset(self::BUCKET_TO_WORK_TYPES[$workTypeFilter])) {
            $colQuery->whereIn('endline_delay.work_type', self::BUCKET_TO_WORK_TYPES[$workTypeFilter]);
        }

        // Apply category filter via defect_class on endline_delay
        if (isset(self::CATEGORY_TO_DEFECT_CLASS[$category])) {
            $colQuery->where('endline_delay.defect_class', self::CATEGORY_TO_DEFECT_CLASS[$category]);
        }

        $rows = $colQuery
            ->join('qc_defect_class', 'endline_delay.qc_defect', '=', 'qc_defect_class.defect_code')
            ->select(
                DB::raw("SUBSTRING(endline_delay.model, 9, 2) as size"),
                'qc_defect_class.defect_code',
                DB::raw('COUNT(*) as cnt'),
            )
            ->whereRaw("SUBSTRING(endline_delay.model, 9, 2) IN ('03','05','10','21','31','32')")
            ->groupBy('size', 'qc_defect_class.defect_code')
            ->get();

        $mapped = $rows->map(fn (object $row): object => (object) [
            'key' => $row->size,
            'cat' => $row->defect_code,
            'cnt' => (int) $row->cnt,
        ]);

        return [
            'categories' => $defectCodes,
            'series'     => $this->pivotToStackedSeries($mapped, $defectCodes, self::SIZE_CODES),
        ];
    }

    // -------------------------------------------------------------------------
    // Shared helpers
    // -------------------------------------------------------------------------

    /**
     * Build the base query scoped to defect_class and page-level filters.
     *
     * @param array<string, mixed> $filters
     */
    private function buildBaseQuery(array $filters): Builder
    {
        $query = DB::table('endline_delay');

        if (!empty($filters['defect_class'])) {
            $query->where('endline_delay.defect_class', $filters['defect_class']);
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

        return $query;
    }

    /**
     * Aggregate a collection of (work_type, cnt) rows into the three pie buckets.
     *
     * @param  Collection<int, object>  $rows  Each row has `work_type` (string|null) and `cnt` (int)
     * @return array{labels: list<string>, series: list<int>}
     */
    private function aggregateByWorkTypeBucket(Collection $rows): array
    {
        $totals = ['Mainlot' => 0, 'R-rework' => 0, 'L-rework' => 0];

        foreach ($rows as $row) {
            $bucket           = self::WORK_TYPE_MAP[$row->work_type ?? ''] ?? 'Mainlot';
            $totals[$bucket] += (int) $row->cnt;
        }

        return [
            'labels' => self::BUCKET_NAMES,
            'series' => array_values($totals),
        ];
    }

    /**
     * Pivot a flat collection into a stacked-series array, zero-filling gaps.
     *
     * Each item in $rows must have:
     *   - `key`  — the series name (e.g. bucket name or size code)
     *   - `cat`  — the x-axis category value
     *   - `cnt`  — the integer count
     *
     * @param  Collection<int, object>  $rows
     * @param  list<string>             $xCategories  Ordered x-axis labels
     * @param  list<string>             $seriesNames  Ordered series names
     * @return list<array{name: string, data: list<int>}>
     */
    private function pivotToStackedSeries(Collection $rows, array $xCategories, array $seriesNames): array
    {
        // Build a lookup: [seriesName][category] => count
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
