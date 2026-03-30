<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProcessWipController extends Controller
{
    private const WIP_STATUS_EXPR = "CASE
        WHEN w.status = 'Run' THEN 'Ongoing MC'
        WHEN w.process_name = 'Visual Inspection' AND w.status = 'Wait' THEN 'Newlot Standby'
        WHEN w.process_name = 'Receive in Visual' AND w.status = 'Wait' THEN 'Waiting Receive'
        WHEN w.process_name = 'Visual Finish' AND w.status = 'Wait' THEN 'Visual Finish'
        WHEN w.process_name = 'Visual Finish' AND w.status = 'Run' THEN 'Yeild Recovery'
        WHEN w.process_name = 'Manual Inspection' AND w.status = 'Wait' THEN 'Manual Inspection (QC)'
        WHEN w.process_name = 'OI Visual Inspection' AND w.status = 'Wait' THEN 'OI Visual Inspection (QC)'
        WHEN w.process_name IN (
            '2nd Visual Inspection',
            'Visual Inspection [Strict Setting Lv1]',
            'Visual Inspection [Strict Setting Lv2]',
            'Visual Inspection [Strict Setting Lv3]'
        ) AND w.status = 'Wait' THEN 'Rework Lot Standby'
        ELSE NULL
    END";

    private const WORK_TYPE_EXPR = "CASE
        WHEN w.warehouse_rework_yn = 'Y' THEN 'WH REWORK'
        WHEN w.rework_type = 'Normal' THEN 'NORMAL'
        WHEN w.rework_type = 'Process Rework' THEN 'PROCESS RW'
        WHEN w.rework_type = 'Outgoing NG' THEN 'OI REWORK'
        ELSE 'NORMAL'
    END";

    private const SIZE_DISPLAY_EXPR = "CASE w.size
        WHEN '0603' THEN '0603'
        WHEN '1005' THEN '1005'
        WHEN '1608' THEN '1608'
        WHEN '2012' THEN '2012'
        WHEN '3216' THEN '3216'
        WHEN '3225' THEN '3225'
        ELSE w.size
    END";

    private const QTY_CLASS_EXPR = "CASE
        WHEN w.size = '0603' AND w.current_qty >= 1000000 THEN 'L'
        WHEN w.size = '0603' AND w.current_qty <  1000000 THEN 'S'
        WHEN w.size = '1005' AND w.current_qty >=  800000 THEN 'L'
        WHEN w.size = '1005' AND w.current_qty <   800000 THEN 'S'
        WHEN w.size = '1608' AND w.current_qty >=  500000 THEN 'L'
        WHEN w.size = '1608' AND w.current_qty <   500000 THEN 'S'
        WHEN w.size = '2012' AND w.current_qty >=  300000 THEN 'L'
        WHEN w.size = '2012' AND w.current_qty <   300000 THEN 'S'
        WHEN w.size = '3216' AND w.current_qty >=  100000 THEN 'L'
        WHEN w.size = '3216' AND w.current_qty <   100000 THEN 'S'
        WHEN w.size = '3225' AND w.current_qty >=  500000 THEN 'L'
        WHEN w.size = '3225' AND w.current_qty <   500000 THEN 'S'
        ELSE NULL
    END";

    public function index(Request $request): Response
    {
        $query = $this->buildBaseQuery();
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $wips = $query
            ->select($this->selectColumns())
            ->paginate(15)
            ->appends($request->except('page'));

        return Inertia::render('dashboards/main/process-wip', [
            'wips'          => $wips,
            'stats'         => $this->getStatistics($request),
            'filterOptions' => $this->getFilterOptions(),
            'filters'       => $request->only([
                'wip_status', 'hold', 'work_type',
                'search', 'lipas', 'automotive', 'size',
                'sort_field', 'sort_direction',
            ]),
        ]);
    }

    public function export(Request $request)
    {
        $query = $this->buildBaseQuery();
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $wips = $query->select($this->selectColumns())->get();

        $filename = 'process_wip_' . date('Y-m-d_His') . '.csv';

        return response()->stream(function () use ($wips) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'Lot No.', 'Model', 'Quantity', 'TAT', 'Work Type',
                'WIP Status', 'Automotive', 'Lipas', 'Qty Class', 'Location',
            ]);

            foreach ($wips as $wip) {
                fputcsv($file, [
                    $wip->lot_id, $wip->model_15, $wip->lot_qty, $wip->stagnant_tat,
                    $wip->work_type, $wip->wip_status, $wip->auto_yn, $wip->lipas_yn,
                    $wip->qty_class, $wip->lot_location,
                ]);
            }

            fclose($file);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildBaseQuery()
    {
        return DB::table('mes_data.wip_status as w')
            ->leftJoin('mes_data.monthly_plan as mp', 'mp.chip_model', '=', 'w.model_id');
    }

    private function selectColumns(): array
    {
        return [
            'w.lot_no as lot_id',
            'w.model_id as model_15',
            DB::raw('CAST(w.current_qty AS UNSIGNED) as lot_qty'),
            DB::raw(self::SIZE_DISPLAY_EXPR . ' as lot_size'),
            DB::raw('CAST(w.major_process_elapsed_days AS DECIMAL(10,2)) as stagnant_tat'),
            DB::raw('(' . self::WORK_TYPE_EXPR . ') as work_type'),
            DB::raw('(' . self::WIP_STATUS_EXPR . ') as wip_status'),
            DB::raw("CASE w.automotive_yn WHEN 'Automative' THEN 'Y' ELSE 'N' END as auto_yn"),
            'mp.vi_lipas_yn as lipas_yn',
            'w.rack as lot_location',
        ];
    }

    private function applySort($query, Request $request): void
    {
        $sortField     = $request->input('sort_field');
        $sortDirection = in_array($request->input('sort_direction'), ['asc', 'desc'])
            ? $request->input('sort_direction')
            : 'asc';

        $allowed = ['lot_id', 'model_15', 'lot_qty', 'stagnant_tat', 'work_type', 'wip_status', 'auto_yn', 'lipas_yn', 'qty_class', 'lot_location'];

        if ($sortField && in_array($sortField, $allowed)) {
            $colMap = [
                'lot_id'       => 'w.lot_no',
                'model_15'     => 'w.model_id',
                'lot_qty'      => DB::raw('CAST(w.current_qty AS UNSIGNED)'),
                'stagnant_tat' => DB::raw('CAST(w.major_process_elapsed_days AS DECIMAL(10,2))'),
                'work_type'    => DB::raw('(' . self::WORK_TYPE_EXPR . ')'),
                'wip_status'   => DB::raw('(' . self::WIP_STATUS_EXPR . ')'),
                'auto_yn'      => DB::raw("CASE w.automotive_yn WHEN 'Automative' THEN 'Y' ELSE 'N' END"),
                'lipas_yn'     => 'mp.vi_lipas_yn',
                'qty_class'    => DB::raw('(' . self::QTY_CLASS_EXPR . ')'),
                'lot_location' => 'w.rack',
            ];
            $query->orderBy($colMap[$sortField], $sortDirection);
        } else {
            $query->orderBy('w.scraped_at', 'desc');
        }
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('wip_status') && $request->wip_status !== 'ALL') {
            $query->whereRaw('(' . self::WIP_STATUS_EXPR . ') = ?', [$request->wip_status]);
        }

        if ($request->filled('hold') && $request->hold !== 'ALL') {
            $query->where('w.hold_yn', $request->hold);
        }

        if ($request->filled('work_type') && $request->work_type !== 'ALL') {
            $query->whereRaw('(' . self::WORK_TYPE_EXPR . ') = ?', [$request->work_type]);
        }

        if ($request->filled('lipas') && $request->lipas !== 'ALL') {
            $query->where('mp.vi_lipas_yn', $request->lipas);
        }

        if ($request->filled('automotive') && $request->automotive !== 'ALL') {
            $val = $request->automotive === 'Y' ? 'Automative' : 'IT';
            $query->where('w.automotive_yn', $val);
        }

        if ($request->filled('size') && $request->size !== 'ALL') {
            $query->where('w.size', $request->size);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('w.lot_no', 'like', "%{$search}%")
                  ->orWhere('w.model_id', 'like', "%{$search}%")
                  ->orWhere('w.rack', 'like', "%{$search}%");
            });
        }

        if ($request->filled('wip_status_filter')) {
            $statuses = explode(',', $request->wip_status_filter);
            $query->whereRaw('(' . self::WIP_STATUS_EXPR . ') IN (' . implode(',', array_fill(0, count($statuses), '?')) . ')', $statuses);
        }
    }

    private function getStatistics(Request $request): array
    {
        $base = $this->buildBaseQuery();
        $this->applyFilters($base, $request);

        $lastUpdate = DB::table('mes_data.wip_status')->max('scraped_at');

        $normalLotsCount  = (clone $base)->whereRaw('(' . self::WORK_TYPE_EXPR . ') = ?', ['NORMAL'])->count();
        $normalLotsQty    = (clone $base)->whereRaw('(' . self::WORK_TYPE_EXPR . ') = ?', ['NORMAL'])->sum('w.current_qty');
        $processRwCount   = (clone $base)->whereRaw('(' . self::WORK_TYPE_EXPR . ') = ?', ['PROCESS RW'])->count();
        $processRwQty     = (clone $base)->whereRaw('(' . self::WORK_TYPE_EXPR . ') = ?', ['PROCESS RW'])->sum('w.current_qty');
        $warehouseCount   = (clone $base)->whereRaw('(' . self::WORK_TYPE_EXPR . ') = ?', ['WH REWORK'])->count();
        $warehouseQty     = (clone $base)->whereRaw('(' . self::WORK_TYPE_EXPR . ') = ?', ['WH REWORK'])->sum('w.current_qty');
        $outgoingNgCount  = (clone $base)->whereRaw('(' . self::WORK_TYPE_EXPR . ') = ?', ['OI REWORK'])->count();
        $outgoingNgQty    = (clone $base)->whereRaw('(' . self::WORK_TYPE_EXPR . ') = ?', ['OI REWORK'])->sum('w.current_qty');

        $statusMap = [
            'Waiting Receive'           => 'Waiting RCV',
            'Newlot Standby'            => 'Newlot STBY',
            'Rework Lot Standby'        => 'Rework STBY',
            'Ongoing MC'                => 'Ongoing MC',
            'Manual Inspection (QC)'    => 'Manual (QC)',
            'OI Visual Inspection (QC)' => 'OI VI (QC)',
            'Visual Finish'             => 'VI Finish',
            'Yeild Recovery'            => 'VI Finish',
        ];

        $mappedStatuses = array_values(array_unique($statusMap));

        $barDataRaw = (clone $base)
            ->select('w.size', DB::raw('(' . self::WIP_STATUS_EXPR . ') as derived_wip_status'), DB::raw('SUM(w.current_qty) as total_qty'))
            ->groupBy('w.size', DB::raw('(' . self::WIP_STATUS_EXPR . ')'))
            ->get();

        $sizeDisplayMap = ['0603' => '0603', '1005' => '1005', '1608' => '1608', '2012' => '2012', '3216' => '3216', '3225' => '3225'];
        $barSeriesData  = [];

        foreach ($barDataRaw as $row) {
            $mappedStatus = $statusMap[$row->derived_wip_status] ?? null;
            if (!$mappedStatus || !in_array($mappedStatus, $mappedStatuses)) {
                continue;
            }
            $displaySize = $sizeDisplayMap[$row->size] ?? null;
            if (!$displaySize) {
                continue;
            }
            if (!isset($barSeriesData[$displaySize])) {
                $barSeriesData[$displaySize] = array_fill_keys($mappedStatuses, 0);
            }
            $barSeriesData[$displaySize][$mappedStatus] += $row->total_qty;
        }

        $barSeries = [];
        foreach (['0603', '1005', '1608', '2012', '3216', '3225'] as $displaySize) {
            $data = [];
            foreach ($mappedStatuses as $status) {
                $data[] = round(($barSeriesData[$displaySize][$status] ?? 0) / 1000000, 1);
            }
            $barSeries[] = ['name' => $displaySize, 'type' => 'bar', 'barWidth' => '10%', 'barGap' => '10%', 'emphasis' => ['focus' => 'series'], 'data' => $data];
        }

        $standbyStatuses = ['Waiting Receive', 'Newlot Standby', 'Rework Lot Standby'];
        $ongoingStatuses = ['Ongoing MC'];
        $endlineStatuses = ['Manual Inspection (QC)', 'OI Visual Inspection (QC)', 'Visual Finish', 'Yeild Recovery'];

        $standbyQty = (clone $base)->whereRaw('(' . self::WIP_STATUS_EXPR . ') IN (' . implode(',', array_fill(0, count($standbyStatuses), '?')) . ')', $standbyStatuses)->sum('w.current_qty');
        $ongoingQty = (clone $base)->whereRaw('(' . self::WIP_STATUS_EXPR . ') IN (' . implode(',', array_fill(0, count($ongoingStatuses), '?')) . ')', $ongoingStatuses)->sum('w.current_qty');
        $endlineQty = (clone $base)->whereRaw('(' . self::WIP_STATUS_EXPR . ') IN (' . implode(',', array_fill(0, count($endlineStatuses), '?')) . ')', $endlineStatuses)->sum('w.current_qty');

        $donutData = [
            ['value' => round($standbyQty / 1000000, 1), 'name' => 'STANDBY'],
            ['value' => round($ongoingQty / 1000000, 1), 'name' => 'ONGOING'],
            ['value' => round($endlineQty / 1000000, 1), 'name' => 'ENDLINE'],
        ];

        $donutDataBySize = [];
        foreach (['0603', '1005', '1608', '2012', '3216', '3225'] as $size) {
            $sq = (clone $base)->where('w.size', $size)->whereRaw('(' . self::WIP_STATUS_EXPR . ') IN (' . implode(',', array_fill(0, count($standbyStatuses), '?')) . ')', $standbyStatuses)->sum('w.current_qty');
            $oq = (clone $base)->where('w.size', $size)->whereRaw('(' . self::WIP_STATUS_EXPR . ') IN (' . implode(',', array_fill(0, count($ongoingStatuses), '?')) . ')', $ongoingStatuses)->sum('w.current_qty');
            $eq = (clone $base)->where('w.size', $size)->whereRaw('(' . self::WIP_STATUS_EXPR . ') IN (' . implode(',', array_fill(0, count($endlineStatuses), '?')) . ')', $endlineStatuses)->sum('w.current_qty');
            $donutDataBySize[$size] = [
                ['value' => round($sq / 1000000, 1), 'name' => 'STANDBY'],
                ['value' => round($oq / 1000000, 1), 'name' => 'ONGOING'],
                ['value' => round($eq / 1000000, 1), 'name' => 'ENDLINE'],
            ];
        }

        return [
            'last_update'        => $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->format('m/d/Y, h:i:s A') : 'N/A',
            'cards'              => [
                'normal'      => ['mpcs' => round($normalLotsQty / 1000000, 1), 'lots' => $normalLotsCount],
                'rework'      => ['mpcs' => round($processRwQty / 1000000, 1), 'lots' => $processRwCount],
                'warehouse'   => ['mpcs' => round($warehouseQty / 1000000, 1), 'lots' => $warehouseCount],
                'outgoing_ng' => ['mpcs' => round($outgoingNgQty / 1000000, 1), 'lots' => $outgoingNgCount],
            ],
            'bar_chart'          => ['categories' => $mappedStatuses, 'series' => $barSeries],
            'donut_chart'        => $donutData,
            'donut_chart_by_size' => $donutDataBySize,
        ];
    }

    private function getFilterOptions(): array
    {
        $wipStatuses = collect([
            'Newlot Standby', 'Ongoing MC', 'Rework Lot Standby',
            'Waiting Receive', 'Visual Finish', 'Yeild Recovery',
            'Manual Inspection (QC)', 'OI Visual Inspection (QC)',
        ]);

        return [
            'wip_statuses' => $wipStatuses,
            'lot_statuses' => collect(),
            'eqp_types'    => collect(),
            'work_types'   => collect(['NORMAL', 'PROCESS RW', 'WH REWORK', 'OI REWORK']),
        ];
    }
}
