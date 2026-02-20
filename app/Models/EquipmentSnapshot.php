<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EquipmentSnapshot extends Model
{
    protected $table = 'equipment_snapshots';

    protected $fillable = [
        'operational',
        'breakdown',
        'full_stop',
        'plan_stop',
        'idle',
        'advance',
        'run',
        'wait',
        'not_use',
        'normal_run',
        'normal_wait',
        'normal_not_use',
        'process_rw_run',
        'process_rw_wait',
        'process_rw_not_use',
        'rl_rework_run',
        'rl_rework_wait',
        'rl_rework_not_use',
        'wh_rework_run',
        'wh_rework_wait',
        'wh_rework_not_use',
        'remarks',
        'date',
        'hour',
        'snapshot_at',
    ];

    protected $casts = [
        'date' => 'date',
        'snapshot_at' => 'datetime',
        'operational' => 'integer',
        'breakdown' => 'integer',
        'full_stop' => 'integer',
        'plan_stop' => 'integer',
        'idle' => 'integer',
        'advance' => 'integer',
        'run' => 'integer',
        'wait' => 'integer',
        'not_use' => 'integer',
    ];

    public $timestamps = false;

    /**
     * Capture current equipment snapshot for all operational equipment
     * Runs every 30 minutes
     */
    public static function captureSnapshot(): void
    {
        $now = now();
        $date = $now->toDateString();
        $hour = $now->hour;

        // Round down to nearest 5-minute mark to determine the snapshot window
        $minute = $now->minute;
        $roundedMinute = floor($minute / 5) * 5;
        $snapshotWindow = $now->copy()->setMinute($roundedMinute)->setSecond(0);
        
        // Check if a snapshot already exists within this 5-minute window
        $existingSnapshot = self::where('snapshot_at', '>=', $snapshotWindow)
            ->where('snapshot_at', '<', $snapshotWindow->copy()->addMinutes(5))
            ->first();

        if ($existingSnapshot) {
            // Snapshot already exists for this 5-minute window, skip
            \Log::info('Snapshot already exists for window: ' . $snapshotWindow->format('Y-m-d H:i:s'));
            return;
        }

        // Get ALL equipment
        $equipmentList = Equipment::select('eqp_no', 'eqp_status', 'ongoing_lot', 'alloc_type')
            ->get();

        // Initialize counters by status
        $statusCounts = [
            'operational' => 0,
            'breakdown' => 0,
            'full_stop' => 0,
            'plan_stop' => 0,
            'idle' => 0,
            'advance' => 0,
        ];
        
        // Initialize counters by lot assignment
        $run = 0;
        $wait = 0;
        
        // Initialize counters by worktype
        $worktypeCounts = [
            'NORMAL' => ['run' => 0, 'wait' => 0, 'not_use' => 0],
            'PROCESS RW' => ['run' => 0, 'wait' => 0, 'not_use' => 0],
            'RL REWORK' => ['run' => 0, 'wait' => 0, 'not_use' => 0],
            'WH REWORK' => ['run' => 0, 'wait' => 0, 'not_use' => 0],
        ];

        // Count equipment by status and worktype
        foreach ($equipmentList as $equipment) {
            $status = strtoupper(trim($equipment->eqp_status ?? ''));
            $hasLot = !empty($equipment->ongoing_lot);
            $worktype = strtoupper(trim($equipment->alloc_type ?? ''));
            
            // If worktype is empty or null, default to NORMAL
            if (empty($worktype)) {
                $worktype = 'NORMAL';
            }
            
            // Normalize worktype to match our fixed types
            if (!isset($worktypeCounts[$worktype])) {
                $worktype = 'NORMAL'; // Default to NORMAL if unknown
            }
            
            // Count by equipment status
            $isOperational = ($status === 'OPERATIONAL');
            
            switch ($status) {
                case 'OPERATIONAL':
                    $statusCounts['operational']++;
                    // Also count for run/wait
                    if ($hasLot) {
                        $run++;
                        $worktypeCounts[$worktype]['run']++;
                    } else {
                        $wait++;
                        $worktypeCounts[$worktype]['wait']++;
                    }
                    break;
                case 'BREAKDOWN':
                    $statusCounts['breakdown']++;
                    break;
                case 'FULL STOP':
                case 'FULLSTOP':
                    $statusCounts['full_stop']++;
                    break;
                case 'PLAN STOP':
                case 'PLANSTOP':
                    $statusCounts['plan_stop']++;
                    break;
                case 'IDLE':
                    $statusCounts['idle']++;
                    break;
                case 'ADVANCE':
                    $statusCounts['advance']++;
                    break;
                default:
                    // Unknown status - count as idle
                    $statusCounts['idle']++;
                    break;
            }
            
            // Count not_use by worktype (non-operational machines)
            if (!$isOperational) {
                $worktypeCounts[$worktype]['not_use']++;
            }
        }
        
        // Calculate not_use (all non-operational)
        $notUse = $statusCounts['breakdown'] + $statusCounts['full_stop'] + 
                  $statusCounts['plan_stop'] + $statusCounts['idle'] + 
                  $statusCounts['advance'];
        
        // Create summary remarks
        $remarks = sprintf(
            "Total: %d operational (%d running, %d waiting), %d not in use (BD:%d, FS:%d, PS:%d, IDLE:%d, ADV:%d)",
            $statusCounts['operational'],
            $run,
            $wait,
            $notUse,
            $statusCounts['breakdown'],
            $statusCounts['full_stop'],
            $statusCounts['plan_stop'],
            $statusCounts['idle'],
            $statusCounts['advance']
        );
        
        // Save only ONE record with the TOTAL counts
        self::create([
            'operational' => $statusCounts['operational'],
            'breakdown' => $statusCounts['breakdown'],
            'full_stop' => $statusCounts['full_stop'],
            'plan_stop' => $statusCounts['plan_stop'],
            'idle' => $statusCounts['idle'],
            'advance' => $statusCounts['advance'],
            'run' => $run,
            'wait' => $wait,
            'not_use' => $notUse,
            // Worktype-specific counts
            'normal_run' => $worktypeCounts['NORMAL']['run'],
            'normal_wait' => $worktypeCounts['NORMAL']['wait'],
            'normal_not_use' => $worktypeCounts['NORMAL']['not_use'],
            'process_rw_run' => $worktypeCounts['PROCESS RW']['run'],
            'process_rw_wait' => $worktypeCounts['PROCESS RW']['wait'],
            'process_rw_not_use' => $worktypeCounts['PROCESS RW']['not_use'],
            'rl_rework_run' => $worktypeCounts['RL REWORK']['run'],
            'rl_rework_wait' => $worktypeCounts['RL REWORK']['wait'],
            'rl_rework_not_use' => $worktypeCounts['RL REWORK']['not_use'],
            'wh_rework_run' => $worktypeCounts['WH REWORK']['run'],
            'wh_rework_wait' => $worktypeCounts['WH REWORK']['wait'],
            'wh_rework_not_use' => $worktypeCounts['WH REWORK']['not_use'],
            'remarks' => $remarks,
            'date' => $date,
            'hour' => $hour,
            'snapshot_at' => $now,
        ]);
    }

    /**
     * Get trend data for chart with 12 bars
     */
    public static function getTrendData(
        string $timeRange = '1hour',
        ?string $line = null,
        ?string $workType = null,
        ?string $date = null,
        ?string $mcStatus = null,
        ?string $mcWorktype = null
    ): array {
        $intervals = [
            '1hour' => ['minutes' => 5, 'total_minutes' => 60],
            '2hour' => ['minutes' => 10, 'total_minutes' => 120],
            '4hour' => ['minutes' => 20, 'total_minutes' => 240],
            '12hour' => ['minutes' => 60, 'total_minutes' => 720],
            '1day' => ['minutes' => 120, 'total_minutes' => 1440],
        ];

        $config = $intervals[$timeRange] ?? $intervals['1hour'];
        $intervalMinutes = $config['minutes'];
        $totalMinutes = $config['total_minutes'];
        
        // Use provided date or current time
        if ($date) {
            $providedDate = Carbon::parse($date)->startOfDay();
            $today = now()->startOfDay();
            
            // If the provided date is today, use current time
            if ($providedDate->equalTo($today)) {
                $endTime = now()->startOfMinute();
            } else {
                // For past dates, use end of day
                $endTime = Carbon::parse($date)->endOfDay()->startOfMinute();
            }
        } else {
            $endTime = now()->startOfMinute();
        }
        
        // Round down to nearest 5-minute mark for consistency
        $minutesToRound = $endTime->minute % 5;
        if ($minutesToRound > 0) {
            $endTime->subMinutes($minutesToRound);
        }
        
        $startTime = $endTime->copy()->subMinutes($totalMinutes);

        // Query all snapshots in the time range
        $snapshots = self::where('snapshot_at', '>=', $startTime)
                        ->where('snapshot_at', '<=', $endTime)
                        ->orderBy('snapshot_at', 'asc')
                        ->get();

        // Apply MC STATUS filter if specified
        if ($mcStatus && $mcStatus !== 'ALL') {
            $snapshots = $snapshots->map(function ($snapshot) use ($mcStatus, $mcWorktype) {
                $statusUpper = strtoupper($mcStatus);
                
                // Apply worktype filter first if specified
                if ($mcWorktype && $mcWorktype !== 'ALL') {
                    $worktypeUpper = strtoupper($mcWorktype);
                    
                    // Map worktype to column prefix
                    $prefix = match($worktypeUpper) {
                        'NORMAL' => 'normal',
                        'PROCESS RW' => 'process_rw',
                        'RL REWORK' => 'rl_rework',
                        'WH REWORK' => 'wh_rework',
                        default => null
                    };
                    
                    if ($prefix) {
                        // Use worktype-specific columns
                        $snapshot->run = $snapshot->{$prefix . '_run'};
                        $snapshot->wait = $snapshot->{$prefix . '_wait'};
                        $snapshot->not_use = $snapshot->{$prefix . '_not_use'};
                    }
                }
                
                if ($statusUpper === 'OPERATIONAL') {
                    // For OPERATIONAL, keep run and wait, set not_use to 0
                    $snapshot->not_use = 0;
                } else {
                    // For non-operational statuses, extract only that status count
                    $statusField = match($statusUpper) {
                        'BREAKDOWN' => 'breakdown',
                        'FULL STOP' => 'full_stop',
                        'PLAN STOP' => 'plan_stop',
                        'IDLE' => 'idle',
                        'ADVANCE' => 'advance',
                        default => null
                    };
                    
                    if ($statusField) {
                        // Show only the selected non-operational status
                        $count = $snapshot->$statusField;
                        $snapshot->run = 0;
                        $snapshot->wait = 0;
                        $snapshot->not_use = $count;
                    }
                }
                
                return $snapshot;
            });
        } else if ($mcWorktype && $mcWorktype !== 'ALL') {
            // Apply only worktype filter (no status filter)
            $snapshots = $snapshots->map(function ($snapshot) use ($mcWorktype) {
                $worktypeUpper = strtoupper($mcWorktype);
                
                // Map worktype to column prefix
                $prefix = match($worktypeUpper) {
                    'NORMAL' => 'normal',
                    'PROCESS RW' => 'process_rw',
                    'RL REWORK' => 'rl_rework',
                    'WH REWORK' => 'wh_rework',
                    default => null
                };
                
                if ($prefix) {
                    // Use worktype-specific columns
                    $snapshot->run = $snapshot->{$prefix . '_run'};
                    $snapshot->wait = $snapshot->{$prefix . '_wait'};
                    $snapshot->not_use = $snapshot->{$prefix . '_not_use'};
                }
                
                return $snapshot;
            });
        }

        $bars = [];
        for ($i = 0; $i < 12; $i++) {
            $barStartTime = $startTime->copy()->addMinutes($i * $intervalMinutes);
            $barEndTime = $barStartTime->copy()->addMinutes($intervalMinutes);
            
            $barSnapshots = $snapshots->filter(function ($snapshot) use ($barStartTime, $barEndTime) {
                return $snapshot->snapshot_at >= $barStartTime && $snapshot->snapshot_at < $barEndTime;
            });

            if ($barSnapshots->count() > 0) {
                // Calculate average counts across all snapshots in this bar
                $avgRun = round($barSnapshots->avg('run'));
                $avgWait = round($barSnapshots->avg('wait'));
                $avgNotUse = round($barSnapshots->avg('not_use'));
                $avgTotal = $avgRun + $avgWait + $avgNotUse;
                
                $utilization = $avgTotal > 0 ? round(($avgRun / $avgTotal) * 100, 1) : 0;
            } else {
                // No data for this time period
                $avgRun = 0;
                $avgWait = 0;
                $avgNotUse = 0;
                $avgTotal = 0;
                $utilization = 0;
            }

            // Use the END time of the bar for the label (more intuitive)
            $bars[] = [
                'label' => self::formatBarLabel($barEndTime, $timeRange),
                'time' => $barEndTime->format('Y-m-d H:i:s'),
                'utilization' => $utilization,
                'run' => $avgRun,
                'wait' => $avgWait,
                'idle' => $avgNotUse, // Using 'idle' key for backward compatibility with frontend
                'total' => $avgTotal,
            ];
        }

        return $bars;
    }

    private static function formatBarLabel(Carbon $time, string $timeRange): string
    {
        return match($timeRange) {
            '1hour' => $time->format('H:i'),  // e.g., "14:30"
            '2hour' => $time->format('H:i'),  // e.g., "14:30"
            '4hour' => $time->format('H:i'),  // e.g., "14:30"
            '1day' => $time->format('H:00'),  // e.g., "14:00"
            default => $time->format('H:i'),
        };
    }

    public static function getLatestUtilization(
        ?string $workType = null,
        ?string $date = null,
        ?string $mcStatus = null,
        ?string $mcWorktype = null
    ): array {
        $lines = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        $result = [];

        // Get latest snapshot for the specified date or current date
        $query = self::query();
        if ($date) {
            $query->whereDate('snapshot_at', $date);
        }
        
        $latestSnapshot = $query->latest('snapshot_at')->first();
        if (!$latestSnapshot) {
            return [];
        }

        $latestTime = $latestSnapshot->snapshot_at;

        // Get equipment filter if needed
        $equipmentNos = null;
        if (($mcStatus && $mcStatus !== 'ALL') || ($mcWorktype && $mcWorktype !== 'ALL')) {
            $equipmentQuery = Equipment::query();
            
            if ($mcStatus && $mcStatus !== 'ALL') {
                $equipmentQuery->where('eqp_status', $mcStatus);
            }
            
            if ($mcWorktype && $mcWorktype !== 'ALL') {
                $equipmentQuery->where('alloc_type', $mcWorktype);
            }
            
            $equipmentNos = $equipmentQuery->pluck('eqp_no')->toArray();
        }

        foreach ($lines as $line) {
            $query = self::where('line', $line)->where('snapshot_at', $latestTime);

            if ($workType && $workType !== 'ALL') {
                $query->where('work_type', $workType);
            }

            if ($equipmentNos !== null) {
                $query->whereIn('eqp_no', $equipmentNos);
            }

            $snapshots = $query->get();
            $total = $snapshots->count();
            $run = $snapshots->where('run', 1)->count();
            $wait = $snapshots->where('wait', 1)->count();
            $idle = $snapshots->where('idle', 1)->count();

            $utilizationPercent = $total > 0 ? round(($run / $total) * 100, 2) : 0;

            $result[] = [
                'line' => $line,
                'count' => $total,
                'run' => ['value' => $run, 'percent' => $utilizationPercent],
                'wait' => ['value' => $wait, 'percent' => $total > 0 ? round(($wait / $total) * 100, 2) : 0],
                'idle' => ['value' => $idle, 'percent' => $total > 0 ? round(($idle / $total) * 100, 2) : 0],
            ];
        }

        return $result;
    }

    public static function getEquipmentList(
        ?string $line = null,
        ?string $status = 'run',
        ?string $date = null,
        ?string $mcStatus = null,
        ?string $mcWorktype = null,
        ?string $lotWorktype = null
    ): array {
        // Get latest snapshot for the specified date or current date
        $snapshotQuery = self::query();
        if ($date) {
            $snapshotQuery->whereDate('snapshot_at', $date);
        }
        
        $latestSnapshot = $snapshotQuery->latest('snapshot_at')->first();
        if (!$latestSnapshot) {
            return [];
        }

        $query = self::where('snapshot_at', $latestSnapshot->snapshot_at)
                    ->select('eqp_no', 'line', 'work_type', 'run', 'wait', 'idle', 'remarks', 'snapshot_at')
                    ->orderBy('line')
                    ->orderBy('eqp_no');

        if ($line && $line !== 'ALL') {
            $query->where('line', $line);
        }

        if ($status === 'run') {
            $query->where('run', 1);
        } elseif ($status === 'wait') {
            $query->where('wait', 1);
        } elseif ($status === 'idle') {
            $query->where('idle', 1);
        }

        // Filter by lot worktype (from snapshot work_type)
        if ($lotWorktype && $lotWorktype !== 'ALL') {
            $query->where('work_type', $lotWorktype);
        }

        // Filter by equipment status and worktype if provided
        if (($mcStatus && $mcStatus !== 'ALL') || ($mcWorktype && $mcWorktype !== 'ALL')) {
            $equipmentQuery = Equipment::query();
            
            if ($mcStatus && $mcStatus !== 'ALL') {
                $equipmentQuery->where('eqp_status', $mcStatus);
            }
            
            if ($mcWorktype && $mcWorktype !== 'ALL') {
                $equipmentQuery->where('alloc_type', $mcWorktype);
            }
            
            $equipmentNos = $equipmentQuery->pluck('eqp_no')->toArray();
            $query->whereIn('eqp_no', $equipmentNos);
        }

        return $query->get()->toArray();
    }

    public static function cleanupOldSnapshots(int $daysToKeep = 7): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        return self::where('snapshot_at', '<', $cutoffDate)->delete();
    }

    /**
     * Get line utilization data for gauge display
     * Returns data for a specific line or total across all lines
     */
    public static function getLineUtilizationForGauge(
        ?string $line = null,
        ?string $workType = null,
        ?string $date = null,
        ?string $mcStatus = null,
        ?string $mcWorktype = null
    ): array {
        // Get latest snapshot for the specified date or current date
        $snapshotQuery = self::query();
        if ($date) {
            $snapshotQuery->whereDate('snapshot_at', $date);
        }
        
        $latestSnapshot = $snapshotQuery->latest('snapshot_at')->first();
        if (!$latestSnapshot) {
            return [
                'count' => 0,
                'run' => 0,
                'wait' => 0,
                'idle' => 0,
                'runPercent' => 0,
                'waitPercent' => 0,
                'idlePercent' => 0,
            ];
        }

        $latestTime = $latestSnapshot->snapshot_at;

        // Build query for the specific line or all lines
        $query = self::where('snapshot_at', $latestTime);

        if ($line && $line !== 'ALL') {
            $query->where('line', $line);
        }

        if ($workType && $workType !== 'ALL') {
            $query->where('work_type', $workType);
        }

        // Filter by equipment status and worktype if provided
        if (($mcStatus && $mcStatus !== 'ALL') || ($mcWorktype && $mcWorktype !== 'ALL')) {
            $equipmentQuery = Equipment::query();
            
            if ($mcStatus && $mcStatus !== 'ALL') {
                $equipmentQuery->where('eqp_status', $mcStatus);
            }
            
            if ($mcWorktype && $mcWorktype !== 'ALL') {
                $equipmentQuery->where('alloc_type', $mcWorktype);
            }
            
            $equipmentNos = $equipmentQuery->pluck('eqp_no')->toArray();
            $query->whereIn('eqp_no', $equipmentNos);
        }

        $snapshots = $query->get();
        $total = $snapshots->count();
        $run = $snapshots->where('run', 1)->count();
        $wait = $snapshots->where('wait', 1)->count();
        $idle = $snapshots->where('idle', 1)->count();

        $runPercent = $total > 0 ? round(($run / $total) * 100, 1) : 0;
        $waitPercent = $total > 0 ? round(($wait / $total) * 100, 1) : 0;
        $idlePercent = $total > 0 ? round(($idle / $total) * 100, 1) : 0;

        return [
            'count' => $total,
            'run' => $run,
            'wait' => $wait,
            'idle' => $idle,
            'runPercent' => $runPercent,
            'waitPercent' => $waitPercent,
            'idlePercent' => $idlePercent,
        ];
    }

    /**
     * Get machine type status data grouped by eqp_maker
     * Returns RUN, WAIT, IDLE counts and percentages for each machine type
     */
    public static function getMachineTypeStatus(
        ?string $workType = null,
        ?string $date = null,
        ?string $mcStatus = null,
        ?string $mcWorktype = null
    ): array {
        // Get latest snapshot for the specified date or current date
        $snapshotQuery = self::query();
        if ($date) {
            $snapshotQuery->whereDate('snapshot_at', $date);
        }
        
        $latestSnapshot = $snapshotQuery->latest('snapshot_at')->first();
        if (!$latestSnapshot) {
            return [];
        }

        $latestTime = $latestSnapshot->snapshot_at;

        // Get snapshots for the latest time
        $query = self::where('snapshot_at', $latestTime);
        
        if ($workType && $workType !== 'ALL') {
            $query->where('work_type', $workType);
        }
        
        // Apply equipment filters if needed
        if (($mcStatus && $mcStatus !== 'ALL') || ($mcWorktype && $mcWorktype !== 'ALL')) {
            $equipmentQuery = Equipment::query();
            
            if ($mcStatus && $mcStatus !== 'ALL') {
                $equipmentQuery->where('eqp_status', $mcStatus);
            }
            
            if ($mcWorktype && $mcWorktype !== 'ALL') {
                $equipmentQuery->where('alloc_type', $mcWorktype);
            }
            
            $equipmentNos = $equipmentQuery->pluck('eqp_no')->toArray();
            $query->whereIn('eqp_no', $equipmentNos);
        }
        
        $snapshots = $query->get();

        // Get all equipment with their makers for the snapshots we have
        $snapshotEqpNos = $snapshots->pluck('eqp_no')->unique()->toArray();
        $equipment = Equipment::select('eqp_no', 'eqp_maker')
            ->whereIn('eqp_no', $snapshotEqpNos)
            ->get()
            ->keyBy('eqp_no');

        // Get all unique machine types from the equipment, sorted alphabetically
        $machineTypes = $equipment->pluck('eqp_maker')
            ->filter() // Remove null/empty values
            ->unique()
            ->sort()
            ->values()
            ->toArray();
        
        $result = [];

        foreach ($machineTypes as $type) {
            // Get equipment numbers for this machine type
            $typeEqpNos = $equipment->where('eqp_maker', $type)->pluck('eqp_no')->toArray();
            
            // Filter snapshots for this machine type
            $typeSnapshots = $snapshots->whereIn('eqp_no', $typeEqpNos);
            
            $total = $typeSnapshots->count();
            $run = $typeSnapshots->where('run', 1)->count();
            $wait = $typeSnapshots->where('wait', 1)->count();
            $idle = $typeSnapshots->where('idle', 1)->count();
            
            $result[$type] = [
                'value' => $total,
                'run' => ['value' => $run, 'percent' => $total > 0 ? round(($run / $total) * 100, 1) : 0],
                'wait' => ['value' => $wait, 'percent' => $total > 0 ? round(($wait / $total) * 100, 1) : 0],
                'idle' => ['value' => $idle, 'percent' => $total > 0 ? round(($idle / $total) * 100, 1) : 0],
            ];
        }

        // Calculate totals from all snapshots (not just categorized ones)
        $totalCount = $snapshots->count();
        $totalRun = $snapshots->where('run', 1)->count();
        $totalWait = $snapshots->where('wait', 1)->count();
        $totalIdle = $snapshots->where('idle', 1)->count();

        $result['TOTAL'] = [
            'value' => $totalCount,
            'run' => ['value' => $totalRun, 'percent' => $totalCount > 0 ? round(($totalRun / $totalCount) * 100, 1) : 0],
            'wait' => ['value' => $totalWait, 'percent' => $totalCount > 0 ? round(($totalWait / $totalCount) * 100, 1) : 0],
            'idle' => ['value' => $totalIdle, 'percent' => $totalCount > 0 ? round(($totalIdle / $totalCount) * 100, 1) : 0],
        ];

        return $result;
    }
}
