<?php

namespace App\Http\Controllers;

use App\Models\UpdateWip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProcessWipController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->applyFilters(UpdateWip::query(), $request);

        // Get paginated data
        $wips = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get summary statistics
        $stats = $this->getStatistics($request);

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        return Inertia::render('dashboards/main/process-wip', [
            'wips' => $wips,
            'stats' => $stats,
            'filterOptions' => $filterOptions,
            'filters' => $request->only(['wip_status', 'lot_status', 'eqp_type', 'hold', 'work_type', 'search', 'lipas', 'automotive']),
        ]);
    }

    public function export(Request $request)
    {
        $query = $this->applyFilters(UpdateWip::query(), $request);
        
        $wips = $query->orderBy('created_at', 'desc')->get();

        $filename = 'process_wip_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($wips) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header row
            fputcsv($file, [
                'Lot No.',
                'Model',
                'Quantity',
                'TAT',
                'Work Type',
                'WIP Status',
                'Automotive',
                'Lipas',
                'MC Type',
                'MC Class',
                'Location'
            ]);

            // Data rows
            foreach ($wips as $wip) {
                fputcsv($file, [
                    $wip->lot_id,
                    $wip->model_15,
                    $wip->lot_qty,
                    $wip->stagnant_tat,
                    $wip->work_type,
                    $wip->wip_status,
                    $wip->auto_yn,
                    $wip->lipas_yn,
                    $wip->eqp_type,
                    $wip->qty_class,
                    $wip->lot_location,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function applyFilters($query, Request $request)
    {
        // Apply filters
        if ($request->filled('wip_status') && $request->wip_status !== 'ALL') {
            $query->where('wip_status', $request->wip_status);
        }

        if ($request->filled('lot_status') && $request->lot_status !== 'ALL') {
            $query->where('lot_status', $request->lot_status);
        }

        if ($request->filled('eqp_type') && $request->eqp_type !== 'ALL') {
            $query->where('eqp_type', $request->eqp_type);
        }

        if ($request->filled('hold') && $request->hold !== 'ALL') {
            $query->where('hold', $request->hold);
        }

        if ($request->filled('work_type') && $request->work_type !== 'ALL') {
            $query->where('work_type', $request->work_type);
        }

        if ($request->filled('lipas') && $request->lipas !== 'ALL') {
            $query->where('lipas_yn', $request->lipas);
        }

        if ($request->filled('automotive') && $request->automotive !== 'ALL') {
            $query->where('auto_yn', $request->automotive);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lot_id', 'like', "%{$search}%")
                  ->orWhere('model_15', 'like', "%{$search}%")
                  ->orWhere('lot_location', 'like', "%{$search}%");
            });
        }

        // Chart click filters
        if ($request->filled('lot_size')) {
            $query->where('lot_size', $request->lot_size);
        }

        if ($request->filled('wip_status_filter')) {
            $statuses = explode(',', $request->wip_status_filter);
            $query->whereIn('wip_status', $statuses);
        }

        return $query;
    }

    private function getStatistics(Request $request)
    {
        $query = $this->applyFilters(UpdateWip::query(), $request);

        // 1. Last Update
        $lastUpdate = UpdateWip::max('updated_at');

        // 2. Card Stats - Filter by work_type
        $normalLotsCount = (clone $query)->where('work_type', 'NORMAL')->count();
        $normalLotsQty = (clone $query)->where('work_type', 'NORMAL')->sum('lot_qty');

        $processReworkCount = (clone $query)->where('work_type', 'PROCESS RW')->count();
        $processReworkQty = (clone $query)->where('work_type', 'PROCESS RW')->sum('lot_qty');

        $warehouseCount = (clone $query)->where('work_type', 'WH REWORK')->count();
        $warehouseQty = (clone $query)->where('work_type', 'WH REWORK')->sum('lot_qty');

        $outgoingNgCount = (clone $query)->where('work_type', 'OI REWORK')->count();
        $outgoingNgQty = (clone $query)->where('work_type', 'OI REWORK')->sum('lot_qty');


        // 3. Bar Chart Data (WIP Breakdown)
        // Mappings - lot_size to display name
        $sizeMap = [
            '03' => '0603',
            '05' => '1005',
            '10' => '1608',
            '21' => '2012',
            '31' => '3216',
            '32' => '3225',
        ];

        $statusMap = [
            'Waiting Receive' => 'Waiting RCV',
            'Newlot Standby' => 'Newlot STBY',
            'Rework Lot Standby' => 'Rework STBY',
            'Ongoing MC' => 'Ongoing MC',
            'Manual Inspection (QC)' => 'Manual (QC)',
            'RL Rework' => 'Manual (QC)',
            'OI Visual Inspection (QC)' => 'OI VI (QC)',
            'Visual Finish' => 'VI Finish',
            'Yeild Recovery' => 'VI Finish', // Note: Typo in database - "Yeild" not "Yield"
        ];

        // Get raw data for aggregation - group by lot_size instead of qty_class
        $barDataRaw = (clone $query)->select('wip_status', 'lot_size', DB::raw('sum(lot_qty) as total_qty'))
            ->groupBy('wip_status', 'lot_size')
            ->get();

        // Process data into mapped structure
        $barSeriesData = []; // [display_size => [status => qty]]
        $mappedStatuses = array_values(array_unique($statusMap));

        foreach ($barDataRaw as $row) {
            $mappedStatus = $statusMap[$row->wip_status] ?? $row->wip_status;
            // Only process if status is in our map
            if (!in_array($mappedStatus, $mappedStatuses)) continue;

            // Map lot_size (03, 05, etc.) to display name (0603, 1005, etc.)
            $displaySize = $sizeMap[$row->lot_size] ?? null;
            if (!$displaySize) continue; // Skip if lot_size not in our map

            if (!isset($barSeriesData[$displaySize])) {
                $barSeriesData[$displaySize] = array_fill_keys($mappedStatuses, 0);
            }
            
            $barSeriesData[$displaySize][$mappedStatus] += $row->total_qty;
        }

        $barSeries = [];
        $colorIndex = 0;

        // Iterate over display sizes - use full names (0603, 1005, etc.) for legend
        $orderedDisplaySizes = ['0603', '1005', '1608', '2012', '3216', '3225'];
        
        foreach ($orderedDisplaySizes as $displaySize) {
            if (!isset($barSeriesData[$displaySize])) {
                // Add empty series if missing to maintain legend
                $data = array_fill(0, count($mappedStatuses), 0);
            } else {
                $data = [];
                foreach ($mappedStatuses as $status) {
                    // Convert to Mpcs with 1 decimal place
                    $data[] = round($barSeriesData[$displaySize][$status] / 1000000, 1);
                }
            }

            $barSeries[] = [
                'name' => $displaySize, // Use full name for legend (0603, 1005, etc.)
                'type' => 'bar',
                'barWidth' => '10%',
                'barGap' => '10%',
                'emphasis' => ['focus' => 'series'],
                'data' => $data,
            ];
            $colorIndex++;
        }


        // 4. Donut Chart (WIP Category)
        // STANDBY = Waiting Receive, Newlot Standby, Rework Lot Standby
        // ONGOING = Ongoing MC
        // ENDLINE = Manual Inspection (QC), RL Rework, OI Visual Inspection (QC), Visual Finish, Yeild Recovery
        
        $standbyStatuses = ['Waiting Receive', 'Newlot Standby', 'Rework Lot Standby'];
        $ongoingStatuses = ['Ongoing MC'];
        $endlineStatuses = ['Manual Inspection (QC)', 'RL Rework', 'OI Visual Inspection (QC)', 'Visual Finish', 'Yeild Recovery'];

        $standbyQty = (clone $query)->whereIn('wip_status', $standbyStatuses)->sum('lot_qty');
        $ongoingQty = (clone $query)->whereIn('wip_status', $ongoingStatuses)->sum('lot_qty');
        $endlineQty = (clone $query)->whereIn('wip_status', $endlineStatuses)->sum('lot_qty');

        $donutData = [
            ['value' => round($standbyQty / 1000000, 1), 'name' => 'STANDBY'],
            ['value' => round($ongoingQty / 1000000, 1), 'name' => 'ONGOING'],
            ['value' => round($endlineQty / 1000000, 1), 'name' => 'ENDLINE'],
        ];

        // 5. Donut Chart by Size
        $donutDataBySize = [];
        $sizes = ['03', '05', '10', '21', '31', '32'];
        $sizeDisplayMap = [
            '03' => '0603',
            '05' => '1005',
            '10' => '1608',
            '21' => '2012',
            '31' => '3216',
            '32' => '3225',
        ];

        foreach ($sizes as $size) {
            $standbyQtySize = (clone $query)->where('lot_size', $size)->whereIn('wip_status', $standbyStatuses)->sum('lot_qty');
            $ongoingQtySize = (clone $query)->where('lot_size', $size)->whereIn('wip_status', $ongoingStatuses)->sum('lot_qty');
            $endlineQtySize = (clone $query)->where('lot_size', $size)->whereIn('wip_status', $endlineStatuses)->sum('lot_qty');

            $displaySize = $sizeDisplayMap[$size];
            $donutDataBySize[$displaySize] = [
                ['value' => round($standbyQtySize / 1000000, 1), 'name' => 'STANDBY'],
                ['value' => round($ongoingQtySize / 1000000, 1), 'name' => 'ONGOING'],
                ['value' => round($endlineQtySize / 1000000, 1), 'name' => 'ENDLINE'],
            ];
        }

        return [
            'last_update' => $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->format('m/d/Y, h:i:s A') : 'N/A',
            'cards' => [
                'normal' => ['mpcs' => round($normalLotsQty / 1000000, 1), 'lots' => $normalLotsCount],
                'rework' => ['mpcs' => round($processReworkQty / 1000000, 1), 'lots' => $processReworkCount],
                'warehouse' => ['mpcs' => round($warehouseQty / 1000000, 1), 'lots' => $warehouseCount],
                'outgoing_ng' => ['mpcs' => round($outgoingNgQty / 1000000, 1), 'lots' => $outgoingNgCount],
            ],
            'bar_chart' => [
                'categories' => $mappedStatuses,
                'series' => $barSeries
            ],
            'donut_chart' => $donutData,
            'donut_chart_by_size' => $donutDataBySize
        ];
    }

    private function getFilterOptions()
    {
        return [
            'wip_statuses' => UpdateWip::distinct()->pluck('wip_status')->sort()->values(),
            'lot_statuses' => UpdateWip::distinct()->pluck('lot_status')->sort()->values(),
            'eqp_types' => UpdateWip::distinct()->pluck('eqp_type')->sort()->values(),
            'work_types' => UpdateWip::distinct()->pluck('work_type')->sort()->values(),
        ];
    }
}
