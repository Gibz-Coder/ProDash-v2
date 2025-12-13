<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class McAllocationController extends Controller
{
    // =========================================================================
    // CONSTANTS
    // =========================================================================
    
    private const DEFAULT_STATUS = 'OPERATIONAL';
    private const DEFAULT_CAPA_TYPE = 'oee_capa';
    private const PER_PAGE = 15;
    
    private const SIZE_MAPPING = [
        '03' => '0603',
        '05' => '1005',
        '10' => '1608',
        '21' => '2012',
        '31' => '3216',
        '32' => '3225',
    ];
    
    private const STATUS_ORDER = [
        'OPERATIONAL',
        'BREAKDOWN', 
        'FULL STOP',
        'PLAN STOP',
        'IDDLE',
        'ADVANCE',
    ];

    // =========================================================================
    // PUBLIC METHODS
    // =========================================================================

    public function index(Request $request)
    {
        $filters = $this->getFilters($request);
        $capaColumn = $this->getCapaColumn($filters['capa_type']);
        
        $allocations = $this->getAllocations($filters);
        $filterOptions = $this->getFilterOptions();
        $stats = $this->getStats($filters, $capaColumn, $filterOptions['machine_types']);

        return Inertia::render('dashboards/main/mc-allocation', [
            'allocations' => $allocations,
            'stats' => $stats,
            'filterOptions' => $filterOptions,
            'filters' => $this->sanitizeFiltersForResponse($filters),
        ]);
    }

    public function destroy(int $id)
    {
        // Check if user has admin role
        if (strtolower(auth()->user()->role ?? '') !== 'admin') {
            return back()->with('error', 'Unauthorized action.');
        }

        DB::table('equipment')->where('id', $id)->delete();

        return back()->with('success', 'Machine deleted successfully.');
    }

    public function export(Request $request)
    {
        $filters = $this->getFilters($request);
        $data = $this->getExportData($filters);
        
        return $this->generateCsvResponse($data);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'eqp_line' => 'required|string|max:10',
            'eqp_area' => 'required|string|max:10',
            'size' => 'required|string|max:10',
            'alloc_type' => 'nullable|string|max:50',
            'eqp_status' => 'required|string|max:50',
            'operation_time' => 'required|integer|min:0',
            'loading_speed' => 'required|integer|min:0',
            'eqp_oee' => 'required|numeric|min:0|max:1',
            'eqp_passing' => 'required|numeric|min:0|max:1',
            'eqp_yield' => 'required|numeric|min:0|max:1',
        ]);

        // Calculate capacities
        $idealCapa = $validated['loading_speed'] * $validated['operation_time'];
        $oeeCapa = floor($validated['loading_speed'] * $validated['eqp_oee'] * $validated['operation_time']);
        $outputCapa = floor($validated['loading_speed'] * $validated['eqp_oee'] * $validated['eqp_passing'] * $validated['eqp_yield'] * $validated['operation_time']);

        DB::table('equipment')
            ->where('id', $id)
            ->update([
                'eqp_line' => $validated['eqp_line'],
                'eqp_area' => $validated['eqp_area'],
                'size' => $validated['size'],
                'alloc_type' => $validated['alloc_type'] ?: null,
                'eqp_status' => $validated['eqp_status'],
                'operation_time' => $validated['operation_time'],
                'loading_speed' => $validated['loading_speed'],
                'eqp_oee' => $validated['eqp_oee'],
                'eqp_passing' => $validated['eqp_passing'],
                'eqp_yield' => $validated['eqp_yield'],
                'ideal_capa' => $idealCapa,
                'oee_capa' => $oeeCapa,
                'output_capa' => $outputCapa,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Machine updated successfully.');
    }

    public function getReferenceData(Request $request)
    {
        $eqpType = $request->input('eqp_type');
        $size = $request->input('size');
        $allocType = $request->input('alloc_type');

        $data = [];

        // Get loading speed from eqp_speed_ref
        if ($eqpType && $size) {
            $speedRef = DB::table('eqp_speed_ref')
                ->where('eqp_type', $eqpType)
                ->where('size', $size)
                ->first();
            
            if ($speedRef) {
                $data['loading_speed'] = $speedRef->eqp_speed;
            }
        }

        // Get OEE parameters from eqp_capa_ref
        if ($size && $allocType) {
            $capaRef = DB::table('eqp_capa_ref')
                ->where('size', $size)
                ->where('work_type', $allocType)
                ->first();
            
            if ($capaRef) {
                $data['eqp_oee'] = $capaRef->oee;
                $data['eqp_passing'] = $capaRef->passing;
                $data['eqp_yield'] = $capaRef->yield;
            }
        }

        return response()->json([
            'success' => !empty($data),
            'data' => $data,
        ]);
    }

    // =========================================================================
    // PRIVATE METHODS - FILTERS
    // =========================================================================

    private function getFilters(Request $request): array
    {
        return [
            'machine_type' => $request->input('machine_type', 'ALL'),
            'status' => $request->input('status', self::DEFAULT_STATUS),
            'location' => $request->input('location', 'ALL'),
            'search' => $request->input('search', ''),
            'capa_type' => $request->input('capa_type', self::DEFAULT_CAPA_TYPE),
            'machine_type_filter' => $request->input('machine_type_filter'),
            'size_filter' => $request->input('size_filter'),
        ];
    }

    private function sanitizeFiltersForResponse(array $filters): array
    {
        return [
            'machine_type' => $filters['machine_type'],
            'status' => $filters['status'],
            'location' => $filters['location'],
            'search' => $filters['search'],
            'capa_type' => $filters['capa_type'],
        ];
    }

    private function getCapaColumn(string $capaType): string
    {
        return match($capaType) {
            'ideal_capa' => 'ideal_capa',
            'output_capa' => 'output_capa',
            default => 'oee_capa',
        };
    }

    private function getFilterOptions(): array
    {
        return [
            'machine_types' => $this->getDistinctValues('eqp_maker'),
            'statuses' => self::STATUS_ORDER,
            'locations' => $this->getDistinctValues('alloc_type'),
        ];
    }

    private function getDistinctValues(string $column): array
    {
        return DB::table('equipment')
            ->distinct()
            ->pluck($column)
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    // =========================================================================
    // PRIVATE METHODS - QUERIES
    // =========================================================================

    private function getAllocations(array $filters)
    {
        $query = DB::table('equipment')
            ->select([
                'id', 'eqp_no', 'eqp_line', 'eqp_area', 'eqp_maker', 'eqp_type',
                'size', 'alloc_type', 'eqp_status',
                'loading_speed', 'operation_time', 'eqp_oee', 'eqp_passing', 'eqp_yield',
                'ideal_capa', 'oee_capa', 'output_capa'
            ]);

        $this->applyFilters($query, $filters);
        
        return $query->get();
    }

    private function applyFilters($query, array $filters): void
    {
        // Main filters
        if ($filters['machine_type'] !== 'ALL') {
            $query->where('eqp_maker', $filters['machine_type']);
        }
        
        if ($filters['status'] !== 'ALL') {
            $query->where('eqp_status', $filters['status']);
        }
        
        if ($filters['location'] !== 'ALL') {
            $query->where('alloc_type', $filters['location']);
        }
        
        // Search filter
        if ($filters['search']) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('eqp_no', 'like', "%{$searchTerm}%")
                  ->orWhere('eqp_maker', 'like', "%{$searchTerm}%")
                  ->orWhere('eqp_status', 'like', "%{$searchTerm}%")
                  ->orWhere('alloc_type', 'like', "%{$searchTerm}%")
                  ->orWhere('eqp_line', 'like', "%{$searchTerm}%")
                  ->orWhere('eqp_area', 'like', "%{$searchTerm}%")
                  ->orWhere('size', 'like', "%{$searchTerm}%");
            });
        }

        // Chart filters
        if ($filters['machine_type_filter']) {
            $query->where('eqp_maker', $filters['machine_type_filter']);
        }
        
        if ($filters['size_filter']) {
            $query->where('size', $filters['size_filter']);
        }
    }

    // =========================================================================
    // PRIVATE METHODS - STATISTICS
    // =========================================================================

    private function getStats(array $filters, string $capaColumn, array $machineTypes): array
    {
        $statsQuery = $this->buildStatsQuery($filters);
        $donutQuery = $this->buildDonutQuery($filters); // Separate query without status filter
        
        return [
            'last_update' => now()->format('Y-m-d H:i:s'),
            'cards' => $this->getCardStats($statsQuery, $capaColumn),
            'bar_chart' => $this->getBarChartData($statsQuery, $capaColumn, $machineTypes),
            'donut_chart' => $this->getDonutChartData($donutQuery),
            'donut_chart_by_size' => $this->getDonutChartBySize($donutQuery),
        ];
    }

    private function buildStatsQuery(array $filters)
    {
        $query = DB::table('equipment');
        
        if ($filters['machine_type'] !== 'ALL') {
            $query->where('eqp_maker', $filters['machine_type']);
        }
        if ($filters['status'] !== 'ALL') {
            $query->where('eqp_status', $filters['status']);
        }
        if ($filters['location'] !== 'ALL') {
            $query->where('alloc_type', $filters['location']);
        }
        if ($filters['machine_type_filter']) {
            $query->where('eqp_maker', $filters['machine_type_filter']);
        }
        if ($filters['size_filter']) {
            $query->where('size', $filters['size_filter']);
        }
        
        return $query;
    }

    /**
     * Build query for donut chart - excludes status filter to show all statuses
     */
    private function buildDonutQuery(array $filters)
    {
        $query = DB::table('equipment');
        
        if ($filters['machine_type'] !== 'ALL') {
            $query->where('eqp_maker', $filters['machine_type']);
        }
        // NOTE: Status filter is intentionally excluded for donut chart
        if ($filters['location'] !== 'ALL') {
            $query->where('alloc_type', $filters['location']);
        }
        if ($filters['machine_type_filter']) {
            $query->where('eqp_maker', $filters['machine_type_filter']);
        }
        if ($filters['size_filter']) {
            $query->where('size', $filters['size_filter']);
        }
        
        return $query;
    }

    private function getCardStats($statsQuery, string $capaColumn): array
    {
        $total = (clone $statsQuery)
            ->selectRaw("COUNT(*) as count, COALESCE(SUM({$capaColumn}), 0) as capacity")
            ->first();

        $allocStats = (clone $statsQuery)
            ->select('alloc_type')
            ->selectRaw("COUNT(*) as count, COALESCE(SUM({$capaColumn}), 0) as capacity")
            ->groupBy('alloc_type')
            ->get()
            ->keyBy('alloc_type');

        return [
            'total_machines' => [
                'count' => $total->count ?? 0,
                'capacity' => $total->capacity ?? 0,
            ],
            'allocated' => [
                'count' => $allocStats->get('NORMAL')->count ?? 0,
                'capacity' => $allocStats->get('NORMAL')->capacity ?? 0,
            ],
            'available' => [
                'count' => $allocStats->get('WH REWORK')->count ?? 0,
                'capacity' => $allocStats->get('WH REWORK')->capacity ?? 0,
            ],
            'maintenance' => [
                'count' => ($allocStats->get('RL REWORK')->count ?? 0) + 
                           ($allocStats->get('PROCESS RW')->count ?? 0),
                'capacity' => ($allocStats->get('RL REWORK')->capacity ?? 0) + 
                              ($allocStats->get('PROCESS RW')->capacity ?? 0),
            ],
        ];
    }

    // =========================================================================
    // PRIVATE METHODS - CHARTS
    // =========================================================================

    private function getBarChartData($statsQuery, string $capaColumn, array $machineTypes): array
    {
        $barData = (clone $statsQuery)
            ->select('eqp_maker', 'size')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw("COALESCE(SUM({$capaColumn}), 0) as capacity")
            ->groupBy('eqp_maker', 'size')
            ->get();

        $categories = $this->buildBarCategories($barData);
        $series = $this->buildBarSeries($barData, $machineTypes, $categories);

        return compact('categories', 'series');
    }

    private function buildBarCategories($barData): array
    {
        $uniqueSizes = $barData->pluck('size')->unique()->filter();
        $categories = [];
        
        foreach (self::SIZE_MAPPING as $key => $label) {
            if ($uniqueSizes->contains($key)) {
                $categories[] = $label;
            }
        }
        
        return $categories;
    }

    private function buildBarSeries($barData, array $machineTypes, array $categories): array
    {
        $series = [];
        
        foreach ($machineTypes as $type) {
            $data = [];
            
            foreach (self::SIZE_MAPPING as $sizeKey => $sizeLabel) {
                if (!in_array($sizeLabel, $categories)) {
                    continue;
                }
                
                $item = $barData
                    ->where('eqp_maker', $type)
                    ->where('size', $sizeKey)
                    ->first();
                
                $data[] = $item 
                    ? ['value' => $item->count, 'capacity' => $item->capacity]
                    : ['value' => 0, 'capacity' => 0];
            }
            
            $series[] = [
                'name' => $type,
                'type' => 'bar',
                'data' => $data,
            ];
        }
        
        return $series;
    }

    private function getDonutChartData($statsQuery): array
    {
        $statusData = (clone $statsQuery)
            ->select('eqp_status as name')
            ->selectRaw('COUNT(*) as value')
            ->groupBy('eqp_status')
            ->get()
            ->keyBy('name');

        return array_map(fn($status) => [
            'name' => $status,
            'value' => $statusData->get($status)->value ?? 0,
        ], self::STATUS_ORDER);
    }

    private function getDonutChartBySize($statsQuery): array
    {
        $result = [];
        
        foreach (self::SIZE_MAPPING as $sizeKey => $sizeLabel) {
            $sizeData = (clone $statsQuery)
                ->where('size', $sizeKey)
                ->select('eqp_status as name')
                ->selectRaw('COUNT(*) as value')
                ->groupBy('eqp_status')
                ->get()
                ->keyBy('name');

            $result[$sizeLabel] = array_map(fn($status) => [
                'name' => $status,
                'value' => $sizeData->get($status)->value ?? 0,
            ], self::STATUS_ORDER);
        }
        
        return $result;
    }

    // =========================================================================
    // PRIVATE METHODS - EXPORT
    // =========================================================================

    private function getExportData(array $filters)
    {
        $query = DB::table('equipment')
            ->select([
                'eqp_no', 'eqp_line', 'eqp_area', 'eqp_maker',
                'size', 'alloc_type', 'eqp_status',
                'ideal_capa', 'oee_capa', 'output_capa', 'ongoing_lot'
            ]);

        $this->applyFilters($query, $filters);
        
        $data = $query->get();
        
        // Get last ongoing lot model for each machine
        $machineNumbers = $data->pluck('eqp_no')->toArray();
        $lastModels = $this->getLastOngoingLotModels($machineNumbers);
        
        // Attach model_15 to each row
        foreach ($data as $row) {
            $row->last_model = $lastModels[$row->eqp_no] ?? '';
        }
        
        return $data;
    }

    /**
     * Get the last lot's model_15 for each machine number
     * Checks eqp_1 through eqp_10 columns in endtime table
     * Includes both Ongoing and Submitted lots
     */
    private function getLastOngoingLotModels(array $machineNumbers): array
    {
        if (empty($machineNumbers)) {
            return [];
        }

        $results = [];
        
        // Build a query to find the latest lot for each machine
        // Machine can be in any of eqp_1 through eqp_10 columns
        $eqpColumns = ['eqp_1', 'eqp_2', 'eqp_3', 'eqp_4', 'eqp_5', 'eqp_6', 'eqp_7', 'eqp_8', 'eqp_9', 'eqp_10'];
        
        foreach ($machineNumbers as $machineNo) {
            $query = DB::table('endtime')
                ->select('model_15')
                ->whereIn('status', ['Ongoing', 'Submitted'])
                ->where(function ($q) use ($eqpColumns, $machineNo) {
                    foreach ($eqpColumns as $col) {
                        $q->orWhere($col, $machineNo);
                    }
                })
                ->orderByDesc('est_endtime')
                ->first();
            
            $results[$machineNo] = $query->model_15 ?? '';
        }
        
        return $results;
    }

    private function generateCsvResponse($data)
    {
        $filename = 'mc-allocation-' . date('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'MC No.', 'Line', 'Area', 'MC Type', 'Size',
                'Allocation', 'MC Status', 'Ideal Capa', 'OEE Capa', 'Output Capa', 'Model Allocation', 'Ongoing Lot',
            ]);

            foreach ($data as $row) {
                fputcsv($file, [
                    $row->eqp_no, $row->eqp_line, $row->eqp_area, $row->eqp_maker,
                    $row->size, $row->alloc_type, $row->eqp_status,
                    $row->ideal_capa, $row->oee_capa, $row->output_capa, $row->last_model, $row->ongoing_lot,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
