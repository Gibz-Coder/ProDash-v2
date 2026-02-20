<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EquipmentStatusController extends Controller
{
    /**
     * Get real-time machine utilization status by line
     * Based on ongoing_lot field in equipment table
     */
    public function getMachineUtilizationStatus(Request $request): JsonResponse
    {
        $mcStatus = $request->input('mcStatus', null);
        $mcWorktype = $request->input('mcWorktype', null);
        
        $lines = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        $result = [];

        foreach ($lines as $line) {
            $query = Equipment::where('eqp_line', $line);

            // Apply filters
            if ($mcStatus && $mcStatus !== 'ALL') {
                $query->where('eqp_status', $mcStatus);
            }

            if ($mcWorktype && $mcWorktype !== 'ALL') {
                $query->where('alloc_type', $mcWorktype);
            }

            $equipment = $query->get();
            $total = $equipment->count();
            
            // RUN: Equipment with ongoing_lot (has lot)
            $run = $equipment->filter(function($item) {
                return !empty($item->ongoing_lot);
            })->count();
            
            // WAIT: Equipment without ongoing_lot but OPERATIONAL
            $wait = $equipment->filter(function($item) {
                return empty($item->ongoing_lot) && strtoupper($item->eqp_status) === 'OPERATIONAL';
            })->count();
            
            // IDLE: Equipment that is not OPERATIONAL
            $idle = $equipment->filter(function($item) {
                return strtoupper($item->eqp_status) !== 'OPERATIONAL';
            })->count();

            $runPercent = $total > 0 ? round(($run / $total) * 100, 1) : 0;
            $waitPercent = $total > 0 ? round(($wait / $total) * 100, 1) : 0;
            $idlePercent = $total > 0 ? round(($idle / $total) * 100, 1) : 0;

            $result[] = [
                'line' => $line,
                'count' => $total,
                'run' => ['value' => $run, 'percent' => $runPercent],
                'wait' => ['value' => $wait, 'percent' => $waitPercent],
                'idle' => ['value' => $idle, 'percent' => $idlePercent],
            ];
        }

        return response()->json([
            'mcStatus' => $mcStatus,
            'mcWorktype' => $mcWorktype,
            'data' => $result,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get real-time utilization for gauge (specific line or all)
     */
    public function getLineUtilization(Request $request): JsonResponse
    {
        $line = $request->input('line', null);
        $mcStatus = $request->input('mcStatus', null);
        $mcWorktype = $request->input('mcWorktype', null);
        
        $query = Equipment::query();

        // Apply line filter
        if ($line && $line !== 'ALL') {
            $query->where('eqp_line', $line);
        }

        // Apply status filter
        if ($mcStatus && $mcStatus !== 'ALL') {
            $query->where('eqp_status', $mcStatus);
        }

        // Apply worktype filter
        if ($mcWorktype && $mcWorktype !== 'ALL') {
            $query->where('alloc_type', $mcWorktype);
        }

        $equipment = $query->get();
        $total = $equipment->count();
        
        // RUN: Equipment with ongoing_lot
        $run = $equipment->filter(function($item) {
            return !empty($item->ongoing_lot);
        })->count();
        
        // WAIT: Equipment without ongoing_lot but OPERATIONAL
        $wait = $equipment->filter(function($item) {
            return empty($item->ongoing_lot) && strtoupper($item->eqp_status) === 'OPERATIONAL';
        })->count();
        
        // IDLE: Equipment that is not OPERATIONAL
        $idle = $equipment->filter(function($item) {
            return strtoupper($item->eqp_status) !== 'OPERATIONAL';
        })->count();

        $runPercent = $total > 0 ? round(($run / $total) * 100, 1) : 0;
        $waitPercent = $total > 0 ? round(($wait / $total) * 100, 1) : 0;
        $idlePercent = $total > 0 ? round(($idle / $total) * 100, 1) : 0;

        return response()->json([
            'line' => $line,
            'mcStatus' => $mcStatus,
            'mcWorktype' => $mcWorktype,
            'data' => [
                'count' => $total,
                'run' => $run,
                'wait' => $wait,
                'idle' => $idle,
                'runPercent' => $runPercent,
                'waitPercent' => $waitPercent,
                'idlePercent' => $idlePercent,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get real-time status per machine type (by eqp_maker)
     */
    public function getMachineTypeStatus(Request $request): JsonResponse
    {
        $mcStatus = $request->input('mcStatus', null);
        $mcWorktype = $request->input('mcWorktype', null);
        
        // Define fixed machine types in order
        $fixedMachineTypes = ['G1', 'G1-AI', 'G20', 'G3', 'TWA', 'WINTEC'];
        
        $query = Equipment::query();

        // Apply filters
        if ($mcStatus && $mcStatus !== 'ALL') {
            $query->where('eqp_status', $mcStatus);
        }

        if ($mcWorktype && $mcWorktype !== 'ALL') {
            $query->where('alloc_type', $mcWorktype);
        }

        $equipment = $query->get();
        
        // Group by eqp_maker
        $groupedByMaker = $equipment->groupBy('eqp_maker');
        
        $result = [];
        
        // Initialize all fixed machine types with zero values
        foreach ($fixedMachineTypes as $maker) {
            $result[$maker] = [
                'value' => 0,
                'run' => ['value' => 0, 'percent' => 0],
                'wait' => ['value' => 0, 'percent' => 0],
                'idle' => ['value' => 0, 'percent' => 0],
            ];
        }
        
        // Fill in actual data for machine types that exist
        foreach ($groupedByMaker as $maker => $items) {
            // Only process if it's in our fixed list
            if (!in_array($maker, $fixedMachineTypes)) {
                continue;
            }
            
            $total = $items->count();
            
            $run = $items->filter(function($item) {
                return !empty($item->ongoing_lot);
            })->count();
            
            $wait = $items->filter(function($item) {
                return empty($item->ongoing_lot) && strtoupper($item->eqp_status) === 'OPERATIONAL';
            })->count();
            
            $idle = $items->filter(function($item) {
                return strtoupper($item->eqp_status) !== 'OPERATIONAL';
            })->count();
            
            $result[$maker] = [
                'value' => $total,
                'run' => ['value' => $run, 'percent' => $total > 0 ? round(($run / $total) * 100, 1) : 0],
                'wait' => ['value' => $wait, 'percent' => $total > 0 ? round(($wait / $total) * 100, 1) : 0],
                'idle' => ['value' => $idle, 'percent' => $total > 0 ? round(($idle / $total) * 100, 1) : 0],
            ];
        }
        
        // Calculate totals
        $totalCount = $equipment->count();
        $totalRun = $equipment->filter(function($item) {
            return !empty($item->ongoing_lot);
        })->count();
        $totalWait = $equipment->filter(function($item) {
            return empty($item->ongoing_lot) && strtoupper($item->eqp_status) === 'OPERATIONAL';
        })->count();
        $totalIdle = $equipment->filter(function($item) {
            return strtoupper($item->eqp_status) !== 'OPERATIONAL';
        })->count();
        
        $result['TOTAL'] = [
            'value' => $totalCount,
            'run' => ['value' => $totalRun, 'percent' => $totalCount > 0 ? round(($totalRun / $totalCount) * 100, 1) : 0],
            'wait' => ['value' => $totalWait, 'percent' => $totalCount > 0 ? round(($totalWait / $totalCount) * 100, 1) : 0],
            'idle' => ['value' => $totalIdle, 'percent' => $totalCount > 0 ? round(($totalIdle / $totalCount) * 100, 1) : 0],
        ];

        return response()->json([
            'mcStatus' => $mcStatus,
            'mcWorktype' => $mcWorktype,
            'data' => $result,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get real-time status per machine size
     */
    public function getMachineSizeStatus(Request $request): JsonResponse
    {
        $mcStatus = $request->input('mcStatus', null);
        $mcWorktype = $request->input('mcWorktype', null);
        
        // Define fixed sizes in order (database values)
        $fixedSizes = ['03', '05', '10', '21', '31', '32'];
        
        $query = Equipment::query();

        // Apply filters
        if ($mcStatus && $mcStatus !== 'ALL') {
            $query->where('eqp_status', $mcStatus);
        }

        if ($mcWorktype && $mcWorktype !== 'ALL') {
            $query->where('alloc_type', $mcWorktype);
        }

        $equipment = $query->get();
        
        // Group by size
        $groupedBySize = $equipment->groupBy('size');
        
        $result = [];
        
        // Initialize all fixed sizes with zero values
        foreach ($fixedSizes as $size) {
            $result[$size] = [
                'value' => 0,
                'run' => ['value' => 0, 'percent' => 0],
                'wait' => ['value' => 0, 'percent' => 0],
                'idle' => ['value' => 0, 'percent' => 0],
            ];
        }
        
        // Fill in actual data for sizes that exist
        foreach ($groupedBySize as $size => $items) {
            // Only process if it's in our fixed list
            if (!in_array($size, $fixedSizes)) {
                continue;
            }
            
            $total = $items->count();
            
            $run = $items->filter(function($item) {
                return !empty($item->ongoing_lot);
            })->count();
            
            $wait = $items->filter(function($item) {
                return empty($item->ongoing_lot) && strtoupper($item->eqp_status) === 'OPERATIONAL';
            })->count();
            
            $idle = $items->filter(function($item) {
                return strtoupper($item->eqp_status) !== 'OPERATIONAL';
            })->count();
            
            $result[$size] = [
                'value' => $total,
                'run' => ['value' => $run, 'percent' => $total > 0 ? round(($run / $total) * 100, 1) : 0],
                'wait' => ['value' => $wait, 'percent' => $total > 0 ? round(($wait / $total) * 100, 1) : 0],
                'idle' => ['value' => $idle, 'percent' => $total > 0 ? round(($idle / $total) * 100, 1) : 0],
            ];
        }
        
        // Calculate totals
        $totalCount = $equipment->count();
        $totalRun = $equipment->filter(function($item) {
            return !empty($item->ongoing_lot);
        })->count();
        $totalWait = $equipment->filter(function($item) {
            return empty($item->ongoing_lot) && strtoupper($item->eqp_status) === 'OPERATIONAL';
        })->count();
        $totalIdle = $equipment->filter(function($item) {
            return strtoupper($item->eqp_status) !== 'OPERATIONAL';
        })->count();
        
        $result['TOTAL'] = [
            'value' => $totalCount,
            'run' => ['value' => $totalRun, 'percent' => $totalCount > 0 ? round(($totalRun / $totalCount) * 100, 1) : 0],
            'wait' => ['value' => $totalWait, 'percent' => $totalCount > 0 ? round(($totalWait / $totalCount) * 100, 1) : 0],
            'idle' => ['value' => $totalIdle, 'percent' => $totalCount > 0 ? round(($totalIdle / $totalCount) * 100, 1) : 0],
        ];

        return response()->json([
            'mcStatus' => $mcStatus,
            'mcWorktype' => $mcWorktype,
            'data' => $result,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get raw equipment data for the table
     */
    public function getRawEquipmentData(Request $request): JsonResponse
    {
        $line = $request->input('line', null);
        $status = $request->input('status', 'all'); // all, run, wait, idle
        $mcStatus = $request->input('mcStatus', null);
        $mcWorktype = $request->input('mcWorktype', null);
        
        $query = Equipment::select('eqp_no', 'eqp_line', 'eqp_area', 'eqp_maker', 'eqp_status', 'alloc_type', 'ongoing_lot', 'size', 'updated_at')
                          ->orderBy('eqp_line')
                          ->orderBy('eqp_area')
                          ->orderBy('eqp_no');

        // Apply line filter
        if ($line && $line !== 'ALL') {
            $query->where('eqp_line', $line);
        }

        // Apply status filter
        if ($mcStatus && $mcStatus !== 'ALL') {
            $query->where('eqp_status', $mcStatus);
        }

        // Apply worktype filter
        if ($mcWorktype && $mcWorktype !== 'ALL') {
            $query->where('alloc_type', $mcWorktype);
        }

        $equipment = $query->get();
        
        // Filter by run/wait/idle status
        if ($status === 'run') {
            $equipment = $equipment->filter(function($item) {
                return !empty($item->ongoing_lot);
            })->values();
        } elseif ($status === 'wait') {
            $equipment = $equipment->filter(function($item) {
                return empty($item->ongoing_lot) && strtoupper($item->eqp_status) === 'OPERATIONAL';
            })->values();
        } elseif ($status === 'idle') {
            $equipment = $equipment->filter(function($item) {
                return strtoupper($item->eqp_status) !== 'OPERATIONAL';
            })->values();
        }

        return response()->json([
            'line' => $line,
            'status' => $status,
            'mcStatus' => $mcStatus,
            'mcWorktype' => $mcWorktype,
            'data' => $equipment,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get raw machine data - machines without ongoing_lot sorted by waiting time
     * Shows machines with highest waiting time (current time - updated_at)
     */
    public function getRawMachineData(Request $request): JsonResponse
    {
        $mcStatus = $request->input('mcStatus', null);
        $mcWorktype = $request->input('mcWorktype', null);
        
        // Get ALL equipment (not just those without ongoing lots)
        $query = Equipment::select(
            'equipment.eqp_no',
            'equipment.eqp_maker',
            'equipment.eqp_line',
            'equipment.eqp_area',
            'equipment.size',
            'equipment.updated_at',
            'equipment.alloc_type',
            'equipment.remarks',
            'equipment.eqp_status',
            'equipment.ongoing_lot',
            'equipment.est_endtime'
        );

        // Apply filters
        if ($mcStatus && $mcStatus !== 'ALL') {
            $query->where('equipment.eqp_status', $mcStatus);
        }

        if ($mcWorktype && $mcWorktype !== 'ALL') {
            $query->where('equipment.alloc_type', $mcWorktype);
        }

        $equipment = $query->get();
        
        // Build a single optimized query to get all previous lots
        // Using UNION to check all eqp columns efficiently
        $eqpNumbers = $equipment->pluck('eqp_no')->toArray();
        
        if (empty($eqpNumbers)) {
            return response()->json([
                'mcStatus' => $mcStatus,
                'mcWorktype' => $mcWorktype,
                'data' => [],
                'timestamp' => now()->toIso8601String(),
            ]);
        }
        
        // Create a more efficient query using CASE statements
        $prevLotsQuery = \DB::table('endtime')
            ->select(
                'lot_id',
                'model_15',
                \DB::raw('CASE 
                    WHEN eqp_1 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') THEN eqp_1
                    WHEN eqp_2 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') THEN eqp_2
                    WHEN eqp_3 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') THEN eqp_3
                    WHEN eqp_4 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') THEN eqp_4
                    WHEN eqp_5 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') THEN eqp_5
                    WHEN eqp_6 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') THEN eqp_6
                    WHEN eqp_7 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') THEN eqp_7
                    WHEN eqp_8 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') THEN eqp_8
                    WHEN eqp_9 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') THEN eqp_9
                    WHEN eqp_10 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') THEN eqp_10
                END as matched_eqp'),
                'updated_at'
            )
            ->whereRaw('(
                eqp_1 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') OR
                eqp_2 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') OR
                eqp_3 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') OR
                eqp_4 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') OR
                eqp_5 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') OR
                eqp_6 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') OR
                eqp_7 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') OR
                eqp_8 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') OR
                eqp_9 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ') OR
                eqp_10 IN (' . implode(',', array_map(fn($n) => "'$n'", $eqpNumbers)) . ')
            )')
            ->orderBy('updated_at', 'desc')
            ->get();
        
        // Group by equipment number and get the most recent lot for each
        $prevLotsMap = [];
        foreach ($prevLotsQuery as $lot) {
            $eqpNo = $lot->matched_eqp;
            if ($eqpNo && !isset($prevLotsMap[$eqpNo])) {
                $prevLotsMap[$eqpNo] = [
                    'lot_id' => $lot->lot_id,
                    'model_15' => $lot->model_15,
                ];
            }
        }
        
        // Calculate waiting time and map previous lots
        $now = now();
        $result = $equipment->map(function($item) use ($now, $prevLotsMap) {
            $waitingMinutes = 0;
            $isNegative = false;
            
            // If machine has an ongoing lot, calculate time difference from est_endtime
            if ($item->ongoing_lot && trim($item->ongoing_lot) !== '') {
                if ($item->est_endtime) {
                    $estEndtime = \Carbon\Carbon::parse($item->est_endtime);
                    // Calculate: current time - est_endtime
                    // If est_endtime is in the past (delayed), result is positive
                    // If est_endtime is in the future (on time), result is negative
                    $waitingMinutes = $now->diffInMinutes($estEndtime, false) * -1;
                    $isNegative = true;
                }
            } else {
                // No ongoing lot - calculate idle time from updated_at
                $updatedAt = \Carbon\Carbon::parse($item->updated_at);
                $waitingMinutes = abs($now->diffInMinutes($updatedAt, false));
            }
            
            $prevLot = $prevLotsMap[$item->eqp_no] ?? null;
            
            return [
                'eqp_no' => $item->eqp_no,
                'eqp_maker' => $item->eqp_maker,
                'eqp_line' => $item->eqp_line,
                'eqp_area' => $item->eqp_area,
                'size' => $item->size,
                'est_endtime' => $item->est_endtime,
                'est_endtime_formatted' => $item->est_endtime ? \Carbon\Carbon::parse($item->est_endtime)->format('M d, Y H:i') : null,
                'waiting_minutes' => $waitingMinutes,
                'waiting_time' => $this->formatWaitingTime($waitingMinutes, $isNegative),
                'prev_lot' => $prevLot['lot_id'] ?? null,
                'prev_model' => $prevLot['model_15'] ?? null,
                'alloc_type' => $item->alloc_type,
                'remarks' => $item->remarks,
                'updated_at' => $item->updated_at,
                'eqp_status' => $item->eqp_status,
                'ongoing_lot' => $item->ongoing_lot,
            ];
        });
        
        // Sort by waiting time descending (highest first)
        $result = $result->sortByDesc('waiting_minutes')->values();

        return response()->json([
            'mcStatus' => $mcStatus,
            'mcWorktype' => $mcWorktype,
            'data' => $result,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get equipment details by equipment number
     */
    public function getEquipmentDetails(string $equipmentNo): JsonResponse
    {
        $equipment = Equipment::where('eqp_no', $equipmentNo)->first();

        if (!$equipment) {
            return response()->json([
                'error' => 'Equipment not found'
            ], 404);
        }
        
        // Calculate waiting time
        // Waiting time should be 0 if there's an ongoing lot
        // Only calculate waiting time if no ongoing lot (machine is idle/waiting)
        $now = now();
        $waitingMinutes = 0;
        if (!$equipment->ongoing_lot || trim($equipment->ongoing_lot) === '') {
            $updatedAt = \Carbon\Carbon::parse($equipment->updated_at);
            $waitingMinutes = abs($now->diffInMinutes($updatedAt, false));
        }
        
        // Add waiting time to the response
        $equipmentData = $equipment->toArray();
        $equipmentData['waiting_minutes'] = $waitingMinutes;
        $equipmentData['waiting_time'] = $this->formatWaitingTime($waitingMinutes);
        $equipmentData['remarks'] = $equipment->remarks ?? null;

        return response()->json($equipmentData);
    }

    /**
     * Update equipment remarks
     */
    public function updateRemarks(Request $request, string $equipmentNo): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
            'modified_by' => 'required|string|max:50',
            'reset_waiting_time' => 'boolean'
        ]);

        $equipment = Equipment::where('eqp_no', $equipmentNo)->first();

        if (!$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment not found'
            ], 404);
        }

        // Verify that the employee ID exists in users table
        $user = \App\Models\User::where('emp_no', $validated['modified_by'])->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Employee ID. User not found in the system.'
            ], 422);
        }

        // Update remarks and modified_by
        $equipment->remarks = $validated['remarks'];
        $equipment->modified_by = $validated['modified_by'];
        
        // If reset_waiting_time is true, update the updated_at timestamp
        // If false, save without updating timestamps
        if ($request->input('reset_waiting_time', false)) {
            // This will update both updated_at and the data
            $equipment->save();
        } else {
            // Save without updating timestamps
            $equipment->timestamps = false;
            $equipment->save();
            $equipment->timestamps = true;
        }

        return response()->json([
            'success' => true,
            'message' => 'Remarks updated successfully',
            'data' => [
                'remarks' => $equipment->remarks,
                'modified_by' => $equipment->modified_by,
                'waiting_time_reset' => $request->input('reset_waiting_time', false)
            ]
        ]);
    }



    /**
     * Format waiting time in human-readable format
     */
    private function formatWaitingTime(int $minutes, bool $isNegative = false): string
    {
        $absMinutes = abs($minutes);
        $prefix = '';
        
        // For negative values (ongoing lots), show - only for negative (on time/early)
        if ($isNegative) {
            // If minutes is positive, machine is delayed (past est_endtime) - no prefix
            // If minutes is negative, machine is on time (before est_endtime) - show "-"
            $prefix = $minutes < 0 ? '-' : '';
        }
        
        if ($absMinutes < 60) {
            return $prefix . $absMinutes . ' min';
        }
        
        $hours = floor($absMinutes / 60);
        $remainingMinutes = $absMinutes % 60;
        
        if ($hours < 24) {
            return $prefix . $hours . 'h ' . $remainingMinutes . 'm';
        }
        
        $days = floor($hours / 24);
        $remainingHours = $hours % 24;
        
        return $prefix . $days . 'd ' . $remainingHours . 'h';
    }
}
