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
        $lots = Endtime::where('status', 'Ongoing')->get();
        
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
        $lots = Endtime::where('status', 'Ongoing')
            ->orderBy('est_endtime', 'asc') // Oldest est_endtime first (most overdue)
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
            'referenceTime' => $referenceTime->toIso8601String(),
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
     * Get available lots for assignment from updatewip table
     * Filters by wip_status and work_type
     */
    public function getAvailableLotsForAssignment(Request $request): JsonResponse
    {
        $query = \App\Models\UpdateWip::query();

        // Apply WIP Status filter (default: Newlot Standby)
        $wipStatus = $request->input('wip_status', 'Newlot Standby');
        if ($wipStatus && $wipStatus !== 'ALL') {
            $query->where('wip_status', $wipStatus);
        }

        // Apply Work Type filter (default: NORMAL)
        $workType = $request->input('work_type', 'NORMAL');
        if ($workType && $workType !== 'ALL') {
            $query->where('work_type', $workType);
        }

        // Apply optional model filter
        if ($request->filled('model')) {
            $query->where('model_15', 'like', '%' . $request->input('model') . '%');
        }

        // Get the lots with all required fields
        $lots = $query->select([
            'lot_id as lot_no',
            'model_15 as lot_model',
            'lot_qty',
            'stagnant_tat',
            'work_type',
            'wip_status',
            'auto_yn',
            'lipas_yn',
            'lot_location',
            'lot_size'
        ])
        ->orderBy('created_at', 'desc')
        ->limit(100)
        ->get();

        return response()->json([
            'data' => $lots,
            'count' => $lots->count(),
            'filters' => [
                'wip_status' => $wipStatus,
                'work_type' => $workType,
            ]
        ]);
    }

    /**
     * Get distinct values for filters
     */
    public function getFilterOptions(): JsonResponse
    {
        $wipStatuses = \App\Models\UpdateWip::distinct()
            ->pluck('wip_status')
            ->filter()
            ->sort()
            ->values();

        $workTypes = \App\Models\UpdateWip::distinct()
            ->pluck('work_type')
            ->filter()
            ->sort()
            ->values();

        return response()->json([
            'wip_statuses' => $wipStatuses,
            'work_types' => $workTypes,
        ]);
    }
}
