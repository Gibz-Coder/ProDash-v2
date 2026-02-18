<?php

namespace App\Http\Controllers;

use App\Models\EquipmentSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MachineUtilizationTrendController extends Controller
{
    public function getTrendData(Request $request): JsonResponse
    {
        $timeRange = $request->input('timeRange', '1hour');
        $line = $request->input('line', null);
        $workType = $request->input('workType', null);
        $date = $request->input('date', null);
        $mcStatus = $request->input('mcStatus', null);
        $mcWorktype = $request->input('mcWorktype', null);

        $validRanges = ['1hour', '2hour', '4hour', '12hour', '1day'];
        if (!in_array($timeRange, $validRanges)) {
            return response()->json([
                'error' => 'Invalid time range. Must be one of: ' . implode(', ', $validRanges)
            ], 400);
        }

        $trendData = EquipmentSnapshot::getTrendData($timeRange, $line, $workType, $date, $mcStatus, $mcWorktype);

        return response()->json([
            'timeRange' => $timeRange,
            'line' => $line,
            'workType' => $workType,
            'date' => $date,
            'mcStatus' => $mcStatus,
            'mcWorktype' => $mcWorktype,
            'data' => $trendData,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function getLatestUtilization(Request $request): JsonResponse
    {
        $workType = $request->input('workType', null);
        $date = $request->input('date', null);
        $mcStatus = $request->input('mcStatus', null);
        $mcWorktype = $request->input('mcWorktype', null);
        
        $data = EquipmentSnapshot::getLatestUtilization($workType, $date, $mcStatus, $mcWorktype);

        return response()->json([
            'workType' => $workType,
            'date' => $date,
            'mcStatus' => $mcStatus,
            'mcWorktype' => $mcWorktype,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function getEquipmentList(Request $request): JsonResponse
    {
        $line = $request->input('line', null);
        $status = $request->input('status', 'run'); // run, wait, idle
        $date = $request->input('date', null);
        $mcStatus = $request->input('mcStatus', null);
        $mcWorktype = $request->input('mcWorktype', null);
        $lotWorktype = $request->input('lotWorktype', null);
        
        $data = EquipmentSnapshot::getEquipmentList($line, $status, $date, $mcStatus, $mcWorktype, $lotWorktype);

        return response()->json([
            'line' => $line,
            'status' => $status,
            'date' => $date,
            'mcStatus' => $mcStatus,
            'mcWorktype' => $mcWorktype,
            'lotWorktype' => $lotWorktype,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Trigger snapshot capture manually
     */
    public function captureSnapshot(): JsonResponse
    {
        try {
            EquipmentSnapshot::captureSnapshot();
            
            $count = EquipmentSnapshot::where('snapshot_at', now()->startOfMinute())->count();
            
            return response()->json([
                'success' => true,
                'message' => 'Snapshot captured successfully',
                'count' => $count,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to capture snapshot: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear old snapshots based on period
     */
    public function clearSnapshots(Request $request): JsonResponse
    {
        $period = $request->input('period', 'previous_day');
        
        try {
            $deletedCount = 0;
            
            switch ($period) {
                case 'previous_day':
                    $cutoffDate = now()->subDay()->endOfDay();
                    $deletedCount = EquipmentSnapshot::where('snapshot_at', '<', $cutoffDate)->delete();
                    break;
                    
                case 'previous_week':
                    $cutoffDate = now()->subWeek()->endOfDay();
                    $deletedCount = EquipmentSnapshot::where('snapshot_at', '<', $cutoffDate)->delete();
                    break;
                    
                case 'previous_month':
                    $cutoffDate = now()->subMonth()->endOfDay();
                    $deletedCount = EquipmentSnapshot::where('snapshot_at', '<', $cutoffDate)->delete();
                    break;
                    
                case 'all':
                    $deletedCount = EquipmentSnapshot::truncate();
                    break;
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid period. Use: previous_day, previous_week, previous_month, or all',
                    ], 400);
            }
            
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} snapshot(s)",
                'deleted_count' => $deletedCount,
                'period' => $period,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear snapshots: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get snapshot statistics
     */
    public function getSnapshotStats(): JsonResponse
    {
        try {
            $total = EquipmentSnapshot::count();
            $today = EquipmentSnapshot::whereDate('date', now()->toDateString())->count();
            $yesterday = EquipmentSnapshot::whereDate('date', now()->subDay()->toDateString())->count();
            $thisWeek = EquipmentSnapshot::where('snapshot_at', '>=', now()->startOfWeek())->count();
            $thisMonth = EquipmentSnapshot::where('snapshot_at', '>=', now()->startOfMonth())->count();
            
            $oldest = EquipmentSnapshot::oldest('snapshot_at')->first();
            $latest = EquipmentSnapshot::latest('snapshot_at')->first();
            
            return response()->json([
                'total' => $total,
                'today' => $today,
                'yesterday' => $yesterday,
                'this_week' => $thisWeek,
                'this_month' => $thisMonth,
                'oldest' => $oldest?->snapshot_at?->toIso8601String(),
                'latest' => $latest?->snapshot_at?->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available filter options from database
     */
    public function getFilterOptions(): JsonResponse
    {
        try {
            // Get distinct MC Status values
            $mcStatuses = \App\Models\Equipment::select('eqp_status')
                ->distinct()
                ->whereNotNull('eqp_status')
                ->where('eqp_status', '!=', '')
                ->orderBy('eqp_status')
                ->pluck('eqp_status')
                ->toArray();
            
            // Get distinct MC Worktype values (alloc_type)
            $mcWorktypes = \App\Models\Equipment::select('alloc_type')
                ->distinct()
                ->whereNotNull('alloc_type')
                ->where('alloc_type', '!=', '')
                ->orderBy('alloc_type')
                ->pluck('alloc_type')
                ->toArray();
            
            // Get distinct Lot Worktype values from endtime
            $lotWorktypes = \App\Models\Endtime::select('work_type')
                ->distinct()
                ->whereNotNull('work_type')
                ->where('work_type', '!=', '')
                ->orderBy('work_type')
                ->pluck('work_type')
                ->toArray();
            
            \Log::info('Filter options fetched', [
                'mcStatuses' => $mcStatuses,
                'mcWorktypes' => $mcWorktypes,
                'lotWorktypes' => $lotWorktypes,
            ]);
            
            return response()->json([
                'mcStatuses' => array_values($mcStatuses),
                'mcWorktypes' => array_values($mcWorktypes),
                'lotWorktypes' => array_values($lotWorktypes),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get filter options', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get filter options: ' . $e->getMessage(),
                'mcStatuses' => [],
                'mcWorktypes' => [],
                'lotWorktypes' => [],
            ], 500);
        }
    }

    /**
     * Get line utilization data for gauge display
     */
    public function getLineUtilization(Request $request): JsonResponse
    {
        $line = $request->input('line', null); // null = ALL, or specific line A-K
        $workType = $request->input('workType', null);
        $date = $request->input('date', null);
        $mcStatus = $request->input('mcStatus', null);
        $mcWorktype = $request->input('mcWorktype', null);
        
        try {
            $data = EquipmentSnapshot::getLineUtilizationForGauge($line, $workType, $date, $mcStatus, $mcWorktype);
            
            return response()->json([
                'line' => $line,
                'workType' => $workType,
                'date' => $date,
                'mcStatus' => $mcStatus,
                'mcWorktype' => $mcWorktype,
                'data' => $data,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get line utilization', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get line utilization: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get machine type status data (by eqp_maker)
     */
    public function getMachineTypeStatus(Request $request): JsonResponse
    {
        $workType = $request->input('workType', null);
        $date = $request->input('date', null);
        $mcStatus = $request->input('mcStatus', null);
        $mcWorktype = $request->input('mcWorktype', null);
        
        try {
            $data = EquipmentSnapshot::getMachineTypeStatus($workType, $date, $mcStatus, $mcWorktype);
            
            return response()->json([
                'workType' => $workType,
                'date' => $date,
                'mcStatus' => $mcStatus,
                'mcWorktype' => $mcWorktype,
                'data' => $data,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get machine type status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get machine type status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get endtime remaining data grouped by class (hour ranges)
     */
    public function getEndtimeRemaining(Request $request): JsonResponse
    {
        $date = $request->input('date', null);
        $workType = $request->input('workType', null);
        
        try {
            $data = \App\Models\Endtime::getEndtimeRemainingByClass($date, $workType);
            
            return response()->json([
                'date' => $date,
                'workType' => $workType,
                'data' => $data,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get endtime remaining', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get endtime remaining: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get endtime per cutoff data
     */
    public function getEndtimePerCutoff(Request $request): JsonResponse
    {
        $date = $request->input('date', null);
        $workType = $request->input('workType', null);
        $statusFilter = $request->input('status', 'all'); // 'all', 'ongoing', 'submitted'
        
        try {
            $data = \App\Models\Endtime::getEndtimePerCutoff($date, $workType, $statusFilter);
            
            return response()->json([
                'date' => $date,
                'workType' => $workType,
                'status' => $statusFilter,
                'data' => $data,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get endtime per cutoff', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get endtime per cutoff: ' . $e->getMessage(),
            ], 500);
        }
    }
}
