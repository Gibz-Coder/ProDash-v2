<?php

namespace App\Http\Controllers;

use App\Models\Endtime;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class MemsDashboardController extends Controller
{
    /**
     * Get endtime remaining data grouped by time class
     * Based on ELAPSED/OVERDUE time from est_endtime to current time
     * Shows delayed lots that need attention from managers/supervisors
     * 
     * For current date: Shows all ongoing lots with elapsed time calculated from est_endtime to now
     * For previous dates: Shows lots that were delayed at the start of that date (00:00)
     */
    public function getEndtimeRemaining(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $workType = $request->input('workType', null);
        $lipas = $request->input('lipas', null);
        
        // Determine reference time
        $selectedDate = Carbon::parse($date)->startOfDay();
        $today = now()->startOfDay();
        
        // If selected date is today, use current time; otherwise use start of day
        if ($selectedDate->isSameDay($today)) {
            $referenceTime = now();
        } else {
            $referenceTime = Carbon::parse($date)->startOfDay();
        }
        
        // Get ALL ongoing lots (including overdue ones)
        // This shows lots that are delayed and need attention
        $query = Endtime::where('status', 'Ongoing');
        
        // Apply work type filter
        if ($workType && $workType !== 'ALL') {
            $query->where('work_type', $workType);
        }
        
        // Apply LIPAS filter
        if ($lipas && $lipas !== 'ALL') {
            $query->where('lipas_yn', $lipas);
        }
        
        $lots = $query->get();
        
        // Initialize result structure
        // A = 0-1 hrs overdue, B = 1-3 hrs overdue, C = 3-6 hrs overdue, D = 6+ hrs overdue
        $result = [
            'A' => ['count' => 0, 'qty' => 0, '0603' => 0, '1005' => 0, '1608' => 0, '2012' => 0, '3216' => 0, '3225' => 0],
            'B' => ['count' => 0, 'qty' => 0, '0603' => 0, '1005' => 0, '1608' => 0, '2012' => 0, '3216' => 0, '3225' => 0],
            'C' => ['count' => 0, 'qty' => 0, '0603' => 0, '1005' => 0, '1608' => 0, '2012' => 0, '3216' => 0, '3225' => 0],
            'D' => ['count' => 0, 'qty' => 0, '0603' => 0, '1005' => 0, '1608' => 0, '2012' => 0, '3216' => 0, '3225' => 0],
        ];
        
        // Map database size codes to display format
        $sizeMap = [
            '03' => '0603',
            '05' => '1005',
            '10' => '1608',
            '21' => '2012',
            '31' => '3216',
            '32' => '3225',
        ];
        
        // Process each lot
        foreach ($lots as $lot) {
            $estEndtime = Carbon::parse($lot->est_endtime);
            
            // Calculate elapsed time from est_endtime to reference time (positive = overdue)
            $hoursElapsed = $estEndtime->diffInHours($referenceTime, false);
            
            // Only include lots that are overdue (elapsed time > 0)
            if ($hoursElapsed < 0) {
                continue; // Skip lots that are not yet due
            }
            
            // Determine class based on hours elapsed (overdue)
            $class = null;
            if ($hoursElapsed >= 0 && $hoursElapsed < 1) {
                $class = 'A'; // 0-1 hours overdue
            } elseif ($hoursElapsed >= 1 && $hoursElapsed < 3) {
                $class = 'B'; // 1-3 hours overdue
            } elseif ($hoursElapsed >= 3 && $hoursElapsed < 6) {
                $class = 'C'; // 3-6 hours overdue
            } elseif ($hoursElapsed >= 6) {
                $class = 'D'; // 6+ hours overdue
            }
            
            if ($class === null) {
                continue;
            }
            
            // Add to result
            $result[$class]['count']++;
            $result[$class]['qty'] += $lot->lot_qty;
            
            // Add to size breakdown - map database size to display format
            $dbSize = $lot->lot_size;
            $displaySize = $sizeMap[$dbSize] ?? null;
            if ($displaySize && isset($result[$class][$displaySize])) {
                $result[$class][$displaySize] += $lot->lot_qty;
            }
        }
        
        // Format result for response
        $formattedResult = [];
        foreach ($result as $class => $data) {
            $formattedResult[] = [
                'class' => $class,
                'count' => $data['count'],
                'qty' => $data['qty'],
                '0603' => $data['0603'],
                '1005' => $data['1005'],
                '1608' => $data['1608'],
                '2012' => $data['2012'],
                '3216' => $data['3216'],
                '3225' => $data['3225'],
            ];
        }
        
        return response()->json([
            'date' => $date,
            'referenceTime' => $referenceTime->toIso8601String(),
            'isPastDate' => !$selectedDate->isSameDay($today),
            'data' => $formattedResult,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get raw lots data for MEMS dashboard
     * Shows ongoing lots with elapsed time calculation (overdue time)
     * Sorted by highest delay time first (most overdue at top)
     */
    public function getRawLotsData(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $workType = $request->input('workType', null);
        $lipas = $request->input('lipas', null);
        
        // Determine reference time
        $selectedDate = Carbon::parse($date)->startOfDay();
        $today = now()->startOfDay();
        
        // If selected date is today, use current time; otherwise use start of day
        if ($selectedDate->isSameDay($today)) {
            $referenceTime = now();
        } else {
            $referenceTime = Carbon::parse($date)->startOfDay();
        }
        
        // Get ALL ongoing lots (including overdue ones)
        $query = Endtime::where('status', 'Ongoing');
        
        // Apply work type filter
        if ($workType && $workType !== 'ALL') {
            $query->where('work_type', $workType);
        }
        
        // Apply LIPAS filter
        if ($lipas && $lipas !== 'ALL') {
            $query->where('lipas_yn', $lipas);
        }
        
        $lots = $query->orderBy('est_endtime', 'asc') // Oldest est_endtime first (most overdue)
            ->get();
        
        // Format data with elapsed time
        $result = $lots->map(function($lot) use ($referenceTime) {
            $estEndtime = Carbon::parse($lot->est_endtime);
            
            // Calculate elapsed time from est_endtime to reference time (positive = overdue)
            $minutesElapsed = $estEndtime->diffInMinutes($referenceTime, false);
            
            // If negative, lot is not yet due (future)
            $isOverdue = $minutesElapsed >= 0;
            
            // Collect all equipment numbers (eqp_1 to eqp_10) that are not empty
            $equipmentList = [];
            for ($i = 1; $i <= 10; $i++) {
                $eqpField = "eqp_$i";
                if (!empty($lot->$eqpField)) {
                    $equipmentList[] = $lot->$eqpField;
                }
            }
            
            // Join equipment numbers with comma
            $allEquipment = !empty($equipmentList) ? implode(', ', $equipmentList) : '-';
            
            return [
                'id' => $lot->id,
                'lot_no' => $lot->lot_id ?? '-',
                'lot_model' => $lot->model_15 ?? '-',
                'lot_worktype' => $lot->work_type ?? '-',
                'lipas' => $lot->lipas_yn ?? '-',
                'mc_no' => $allEquipment, // All equipment numbers
                'mc_line' => $lot->eqp_line ?? '-',
                'mc_area' => $lot->eqp_area ?? '-',
                'lot_size' => $lot->lot_size ?? '-',
                'lot_qty' => $lot->lot_qty ?? 0,
                'elapsed_minutes' => $isOverdue ? $minutesElapsed : 0, // Show 0 for not yet due
                'elapsed_time' => $isOverdue ? $this->formatElapsedTime($minutesElapsed) : '0m',
                'est_endtime' => $lot->est_endtime,
                'est_endtime_formatted' => $estEndtime->format('Y-m-d H:i'),
                'is_overdue' => $isOverdue,
            ];
        });
        
        // Sort by elapsed_minutes descending (highest delay first)
        $result = $result->sortByDesc('elapsed_minutes')->values();

        return response()->json([
            'date' => $date,
            'workType' => $workType,
            'lipas' => $lipas,
            'referenceTime' => $referenceTime->toIso8601String(),
            'data' => $result,
            'count' => $result->count(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get raw lots data for endtime per cutoff export
     * Returns detailed lot information filtered by date and cutoff criteria
     */
    public function getRawLotsForCutoffExport(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $workType = $request->input('workType', null);
        $lipas = $request->input('lipas', null);
        $statusFilter = $request->input('status', 'all'); // 'all', 'ongoing', 'submitted'
        
        // Build query using same approach as getEndtimePerCutoff
        $query = Endtime::query();
        
        // Apply date filter based on status
        if ($statusFilter === 'ongoing') {
            $query->where('status', 'Ongoing')
                  ->whereDate('est_endtime', $date);
            
            // Apply filters
            if ($workType && $workType !== 'ALL') {
                $query->where('work_type', $workType);
            }
            if ($lipas && $lipas !== 'ALL') {
                $query->where('lipas_yn', $lipas);
            }
        } elseif ($statusFilter === 'submitted') {
            $query->where('status', 'Submitted')
                  ->whereDate('actual_submitted_at', $date);
            
            // Apply filters
            if ($workType && $workType !== 'ALL') {
                $query->where('work_type', $workType);
            }
            if ($lipas && $lipas !== 'ALL') {
                $query->where('lipas_yn', $lipas);
            }
        } else {
            // For 'all', we need to get one record per lot_id
            // Use a subquery to find the max id for each lot_id that matches our criteria
            $subquery = Endtime::query()
                ->select(\DB::raw('MAX(id) as max_id'))
                ->where(function($q) use ($date) {
                    $q->where(function($subQ) use ($date) {
                        $subQ->where('status', 'Ongoing')
                             ->whereDate('est_endtime', $date);
                    })->orWhere(function($subQ) use ($date) {
                        $subQ->where('status', 'Submitted')
                             ->whereDate('actual_submitted_at', $date);
                    });
                });
            
            // Apply filters to subquery
            if ($workType && $workType !== 'ALL') {
                $subquery->where('work_type', $workType);
            }
            if ($lipas && $lipas !== 'ALL') {
                $subquery->where('lipas_yn', $lipas);
            }
            
            $subquery->groupBy('lot_id');
            
            // Now filter main query to only include these IDs
            $query->whereIn('id', $subquery->pluck('max_id'));
        }
        
        // Get lots
        $allLots = $query->orderBy('est_endtime', 'asc')->get();
        
        // Log for debugging
        \Log::info('Raw Lots Cutoff Export - Total lots fetched (unique by lot_id): ' . $allLots->count());
        
        // Format data
        $result = collect($allLots)->map(function($lot) {
            // Collect all equipment numbers
            $equipmentList = [];
            for ($i = 1; $i <= 10; $i++) {
                $eqpField = "eqp_$i";
                if (!empty($lot->$eqpField)) {
                    $equipmentList[] = $lot->$eqpField;
                }
            }
            $allEquipment = !empty($equipmentList) ? implode(', ', $equipmentList) : '-';
            
            // Use est_endtime for ongoing, actual_submitted_at for submitted
            $timeColumn = $lot->status === 'Submitted' ? $lot->actual_submitted_at : $lot->est_endtime;
            $estEndtime = $timeColumn ? Carbon::parse($timeColumn) : null;
            
            return [
                'id' => $lot->id,
                'lot_no' => $lot->lot_id ?? '-',
                'lot_model' => $lot->model_15 ?? '-',
                'lot_worktype' => $lot->work_type ?? '-',
                'lipas' => $lot->lipas_yn ?? '-',
                'mc_no' => $allEquipment,
                'mc_line' => $lot->eqp_line ?? '-',
                'mc_area' => $lot->eqp_area ?? '-',
                'lot_size' => $lot->lot_size ?? '-',
                'lot_qty' => $lot->lot_qty ?? 0,
                'est_endtime' => $timeColumn,
                'est_endtime_formatted' => $estEndtime ? $estEndtime->format('Y-m-d H:i') : '-',
                'status' => $lot->status,
            ];
        });
        
        return response()->json([
            'date' => $date,
            'workType' => $workType,
            'lipas' => $lipas,
            'status' => $statusFilter,
            'data' => $result,
            'count' => $result->count(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Format elapsed time in human-readable format
     */
    private function formatElapsedTime(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $mins);
        }
        
        return sprintf('%dm', $mins);
    }

    /**
     * Get available lots for assignment from mes_data.wip_status
     * Filters by derived wip_status and work_type
     */
    public function getAvailableLotsForAssignment(Request $request): JsonResponse
    {
        $query = \DB::table('mes_data.wip_status as w')
            ->leftJoin('mes_data.monthly_plan as mp', 'mp.chip_model', '=', 'w.model_id');

        // Exclude lots that are on hold
        $query->where(function ($q) {
            $q->where('w.hold_yn', 'N')
              ->orWhereNull('w.hold_yn');
        });

        $wipStatusExpr = "CASE
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

        // Apply WIP Status filter (default: Newlot Standby)
        $wipStatus = $request->input('wip_status', 'Newlot Standby');
        if ($wipStatus && $wipStatus !== 'ALL') {
            $query->whereRaw("({$wipStatusExpr}) = ?", [$wipStatus]);
        }

        // Apply Work Type filter (default: NORMAL)
        $workType = $request->input('work_type', 'NORMAL');
        if ($workType && $workType !== 'ALL') {
            $query->where(\DB::raw("CASE
                WHEN w.warehouse_rework_yn = 'Y' THEN 'WH REWORK'
                WHEN w.rework_type = 'Normal' THEN 'NORMAL'
                WHEN w.rework_type = 'Process Rework' THEN 'PROCESS RW'
                WHEN w.rework_type = 'Outgoing NG' THEN 'OI REWORK'
                ELSE 'NORMAL'
            END"), $workType);
        }

        // Apply optional model filter
        if ($request->filled('model')) {
            $query->where('w.model_id', 'like', '%' . $request->input('model') . '%');
        }

        $lots = $query->select([
            'w.lot_no as lot_no',
            'w.model_id as lot_model',
            \DB::raw('CAST(w.current_qty AS UNSIGNED) as lot_qty'),
            \DB::raw("CASE w.size
                WHEN '0603' THEN '03'
                WHEN '1005' THEN '05'
                WHEN '1608' THEN '10'
                WHEN '2012' THEN '21'
                WHEN '3216' THEN '31'
                WHEN '3225' THEN '32'
                ELSE '10'
            END as lot_size"),
            \DB::raw("CASE
                WHEN w.warehouse_rework_yn = 'Y' THEN 'WH REWORK'
                WHEN w.rework_type = 'Normal' THEN 'NORMAL'
                WHEN w.rework_type = 'Process Rework' THEN 'PROCESS RW'
                WHEN w.rework_type = 'Outgoing NG' THEN 'OI REWORK'
                ELSE 'NORMAL'
            END as work_type"),
            \DB::raw("({$wipStatusExpr}) as wip_status"),
            \DB::raw("CASE w.automotive_yn WHEN 'Automative' THEN 'Y' ELSE 'N' END as auto_yn"),
            'mp.vi_lipas_yn as lipas_yn',
            'w.rack as lot_location',
            \DB::raw('CAST(w.major_process_elapsed_days AS DECIMAL(10,2)) as stagnant_tat'),
            \DB::raw("CASE
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
            END as qty_class"),
        ])
        ->orderBy('w.major_process_elapsed_days', 'desc')
        ->orderBy('w.scraped_at', 'desc')
        ->limit(1000)
        ->get();

        return response()->json([
            'data' => $lots,
            'count' => $lots->count(),
            'total_available' => (clone $query)->count(),
            'filters' => [
                'wip_status' => $wipStatus,
                'work_type' => $workType,
            ],
        ]);
    }

    /**
     * Get distinct values for filters
     */
    public function getFilterOptions(): JsonResponse
    {
        $wipStatuses = collect([
            'Newlot Standby',
            'Ongoing MC',
            'Rework Lot Standby',
            'Waiting Receive',
            'Visual Finish',
            'Yeild Recovery',
            'Manual Inspection (QC)',
            'OI Visual Inspection (QC)',
        ]);

        $workTypes = collect(['NORMAL', 'PROCESS RW', 'WH REWORK', 'OI REWORK']);

        return response()->json([
            'wip_statuses' => $wipStatuses,
            'work_types' => $workTypes,
        ]);
    }

    /**
     * Create a new lot request
     */
    public function createLotRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mc_no' => 'required|string',
            'line' => 'required|string',
            'area' => 'required|string',
            'requestor_id' => 'required|string',
            'lot_no' => 'required|string',
            'model' => 'required|string',
            'quantity' => 'required|integer',
            'lipas' => 'required|string|in:Y,N',
            'lot_tat' => 'nullable|string',
            'lot_location' => 'nullable|string',
        ]);

        try {
            // Get requestor name from users table
            $user = \App\Models\User::where('emp_no', $validated['requestor_id'])->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee ID not found',
                ], 404);
            }

            $lotRequest = \App\Models\LotRequest::create([
                'request_no' => \App\Models\LotRequest::generateRequestNo(),
                'mc_no' => $validated['mc_no'],
                'line' => $validated['line'],
                'area' => $validated['area'],
                'requestor' => $user->name,
                'lot_no' => $validated['lot_no'],
                'model' => $validated['model'],
                'quantity' => $validated['quantity'],
                'lipas' => $validated['lipas'],
                'lot_tat' => $validated['lot_tat'],
                'lot_location' => $validated['lot_location'] ?? null,
                'requested' => now(),
                'status' => 'PENDING',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lot request created successfully',
                'data' => $lotRequest,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating lot request: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lot request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate employee ID
     */
    public function validateEmployeeId(Request $request): JsonResponse
    {
        $employeeId = $request->input('employee_id');
        
        if (!$employeeId) {
            return response()->json([
                'valid' => false,
                'message' => 'Employee ID is required',
            ], 400);
        }

        $user = \App\Models\User::where('emp_no', $employeeId)->first();

        if ($user) {
            return response()->json([
                'valid' => true,
                'name' => $user->name,
                'emp_no' => $user->emp_no,
            ]);
        }

        return response()->json([
            'valid' => false,
            'message' => 'Employee ID not found',
        ], 404);
    }

}
