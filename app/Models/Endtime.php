<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Endtime extends Model
{
    protected $table = 'endtime';

    protected $fillable = [
        'lot_id',
        'lot_qty',
        'lot_size',
        'lot_type',
        'model_15',
        'lipas_yn',
        'est_endtime',
        'actual_endtime',
        'actual_submitted_at',
        'work_type',
        'eqp_line',
        'eqp_area',
        'status',
        'remarks',
        'submission_notes',
        'eqp_1',
        'eqp_2',
        'eqp_3',
        'eqp_4',
        'eqp_5',
        'eqp_6',
        'eqp_7',
        'eqp_8',
        'eqp_9',
        'eqp_10',
        'ng_percent_1',
        'ng_percent_2',
        'ng_percent_3',
        'ng_percent_4',
        'ng_percent_5',
        'ng_percent_6',
        'ng_percent_7',
        'ng_percent_8',
        'ng_percent_9',
        'ng_percent_10',
        'start_time_1',
        'start_time_2',
        'start_time_3',
        'start_time_4',
        'start_time_5',
        'start_time_6',
        'start_time_7',
        'start_time_8',
        'start_time_9',
        'start_time_10',
        'no_rl_enabled',
        'no_rl_minutes',
        'modified_by',
    ];

    /**
     * Get total endtime quantity based on filters
     * Includes both Ongoing (filtered by est_endtime) and Submitted (filtered by actual_submitted_at)
     * 
     * @param string|null $date Filter by date
     * @param string|null $shift Filter by shift (DAY/NIGHT)
     * @param string|null $cutoff Filter by cutoff (1ST/2ND/3RD)
     * @param string|null $workType Filter by work type
     * @return array
     */
    public static function getTotalEndtime(
        ?string $date = null,
        ?string $shift = null,
        ?string $cutoff = null,
        ?string $workType = null
    ): array {
        // Get ongoing lots (use est_endtime for filtering)
        $ongoingQuery = self::query()->where('status', 'Ongoing');
        self::applyFilters($ongoingQuery, $date, $shift, $cutoff, $workType, 'est_endtime');
        $ongoingQty = $ongoingQuery->sum('lot_qty');
        $ongoingCount = $ongoingQuery->count();

        // Get submitted lots (use actual_submitted_at for filtering)
        $submittedQuery = self::query()->where('status', 'Submitted');
        self::applyFilters($submittedQuery, $date, $shift, $cutoff, $workType, 'actual_submitted_at');
        $submittedQty = $submittedQuery->sum('lot_qty');
        $submittedCount = $submittedQuery->count();

        // Combine results
        $totalQty = $ongoingQty + $submittedQty;
        $lotCount = $ongoingCount + $submittedCount;

        return [
            'quantity' => $totalQty,
            'lotCount' => $lotCount,
        ];
    }

    /**
     * Get total submitted lots based on filters
     * Uses actual_submitted_at for date/shift/cutoff filtering
     * 
     * @param string|null $date Filter by date
     * @param string|null $shift Filter by shift (DAY/NIGHT)
     * @param string|null $cutoff Filter by cutoff (1ST/2ND/3RD)
     * @param string|null $workType Filter by work type
     * @return array
     */
    public static function getTotalSubmitted(
        ?string $date = null,
        ?string $shift = null,
        ?string $cutoff = null,
        ?string $workType = null
    ): array {
        $query = self::query();

        // Filter by status = "Submitted"
        $query->where('status', 'Submitted');

        // Use applyFilters to handle date/shift/cutoff filtering with actual_submitted_at
        // This ensures proper handling of NIGHT shift 2ND and 3RD cutoffs that span midnight
        self::applyFilters($query, $date, $shift, $cutoff, $workType, 'actual_submitted_at');

        // Get total quantity and lot count
        $totalQty = $query->sum('lot_qty');
        $lotCount = $query->count();

        return [
            'quantity' => $totalQty,
            'lotCount' => $lotCount,
        ];
    }

    /**
     * Get total remaining lots (ongoing) based on filters
     * 
     * @param string|null $date Filter by date
     * @param string|null $shift Filter by shift (DAY/NIGHT)
     * @param string|null $cutoff Filter by cutoff (1ST/2ND/3RD)
     * @param string|null $workType Filter by work type
     * @return array
     */
    public static function getTotalRemaining(
        ?string $date = null,
        ?string $shift = null,
        ?string $cutoff = null,
        ?string $workType = null
    ): array {
        $query = self::query();

        // Filter by status = "Ongoing"
        $query->where('status', 'Ongoing');

        // Use applyFilters to handle date/shift/cutoff filtering with est_endtime
        // This ensures proper handling of NIGHT shift 2ND and 3RD cutoffs that span midnight
        self::applyFilters($query, $date, $shift, $cutoff, $workType, 'est_endtime');

        // Get total quantity and lot count
        $totalQty = $query->sum('lot_qty');
        $lotCount = $query->count();

        return [
            'quantity' => $totalQty,
            'lotCount' => $lotCount,
        ];
    }

    /**
     * Get production data per line for chart
     * Returns target, endtime, submitted, and remaining data for each line
     * 
     * @param string|null $date Filter by date
     * @param string|null $shift Filter by shift
     * @param string|null $cutoff Filter by cutoff
     * @param string|null $workType Filter by work type
     * @return array
     */
    public static function getProductionByLine(
        ?string $date = null,
        ?string $shift = null,
        ?string $cutoff = null,
        ?string $workType = null
    ): array {
        $lines = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        $productionData = [];

        foreach ($lines as $line) {
            // Get target capacity from Equipment model
            $targetCapacity = \App\Models\Equipment::where('eqp_status', 'OPERATIONAL')
                ->where('eqp_line', $line);
            
            if ($workType && $workType !== 'ALL') {
                $targetCapacity->where('alloc_type', $workType);
            }
            
            $target = $targetCapacity->sum('oee_capa');
            
            // Apply shift/cutoff division logic
            $divisor = 1;
            if ($shift !== 'ALL' && $cutoff === 'ALL') {
                $divisor = 2;
            } elseif ($shift !== 'ALL' && $cutoff !== 'ALL') {
                $divisor = 6;
            } elseif ($shift === 'ALL' && $cutoff !== 'ALL') {
                $divisor = 3;
            }
            
            $target = $target / $divisor;

            // Get total endtime for this line (ongoing + submitted with different date columns)
            $ongoingQuery = self::query()->where('eqp_line', $line)->where('status', 'Ongoing');
            self::applyFilters($ongoingQuery, $date, $shift, $cutoff, $workType, 'est_endtime');
            $ongoingEndtime = $ongoingQuery->sum('lot_qty');
            
            $submittedQuery = self::query()->where('eqp_line', $line)->where('status', 'Submitted');
            self::applyFilters($submittedQuery, $date, $shift, $cutoff, $workType, 'actual_submitted_at');
            $submittedEndtime = $submittedQuery->sum('lot_qty');
            
            $endtime = $ongoingEndtime + $submittedEndtime;

            // Get submitted for this line (use actual_submitted_at for filtering)
            $submittedQuery = self::query()->where('eqp_line', $line)->where('status', 'Submitted');
            self::applyFilters($submittedQuery, $date, $shift, $cutoff, $workType, 'actual_submitted_at');
            $submitted = $submittedQuery->sum('lot_qty');

            // Get remaining for this line
            $remainingQuery = self::query()->where('eqp_line', $line)->where('status', 'Ongoing');
            self::applyFilters($remainingQuery, $date, $shift, $cutoff, $workType, 'est_endtime');
            $remaining = $remainingQuery->sum('lot_qty');

            // Convert to millions and round to 1 decimal
            $productionData[] = [
                'month' => 'Line ' . $line,
                'target' => round($target / 1000000, 1),
                'endtime' => round($endtime / 1000000, 1),
                'submitted' => round($submitted / 1000000, 1),
                'remaining' => round($remaining / 1000000, 1),
            ];
        }

        return $productionData;
    }

    /**
     * Helper method to apply common filters to a query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * Calculate the cutoff end datetime based on shift and cutoff selection
     * Used for "Include Previous Date" feature to determine the upper bound
     * 
     * @param string $date Production date
     * @param string $shift Shift (DAY, NIGHT, ALL)
     * @param string $cutoff Cutoff (1ST, 2ND, 3RD, ALL)
     * @return string Datetime string (Y-m-d H:i:s)
     */
    private static function calculateCutoffEndDatetime(string $date, string $shift, string $cutoff): string
    {
        $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
        
        // Cutoff time ranges:
        // DAY shift: 1ST (07:00-11:59), 2ND (12:00-15:59), 3RD (16:00-18:59)
        // NIGHT shift: 1ST (19:00-23:59), 2ND (00:00-03:59 next day), 3RD (04:00-06:59 next day)
        
        if ($shift === 'NIGHT') {
            if ($cutoff === '1ST') {
                return $date . ' 23:59:59';
            } elseif ($cutoff === '2ND') {
                return $nextDay . ' 03:59:59';
            } elseif ($cutoff === '3RD' || $cutoff === 'ALL') {
                return $nextDay . ' 06:59:59';
            }
        } elseif ($shift === 'DAY') {
            if ($cutoff === '1ST') {
                return $date . ' 11:59:59';
            } elseif ($cutoff === '2ND') {
                return $date . ' 15:59:59';
            } elseif ($cutoff === '3RD' || $cutoff === 'ALL') {
                return $date . ' 18:59:59';
            }
        } else {
            // ALL shift - use the end of the full production day
            if ($cutoff === '1ST') {
                // DAY 1ST ends at 11:59, NIGHT 1ST ends at 23:59 - use the later one
                return $date . ' 23:59:59';
            } elseif ($cutoff === '2ND') {
                // DAY 2ND ends at 15:59, NIGHT 2ND ends at next day 03:59 - use the later one
                return $nextDay . ' 03:59:59';
            } elseif ($cutoff === '3RD' || $cutoff === 'ALL') {
                // Full production day ends at next day 06:59
                return $nextDay . ' 06:59:59';
            }
        }
        
        // Default: end of production day
        return $nextDay . ' 06:59:59';
    }

    /**
     * @param string|null $date
     * @param string|null $shift
     * @param string|null $cutoff
     * @param string|null $workType
     * @param string $dateColumn Column to use for date/time filtering (est_endtime or actual_submitted_at)
     * @return void
     */
    private static function applyFilters($query, $date, $shift, $cutoff, $workType, $dateColumn = 'est_endtime'): void
    {
        // Production Date Logic:
        // - DAY shift: Production date = same calendar date (07:00-18:59)
        // - NIGHT shift: Production date = date when shift STARTS
        //   Example: Production Date 12/01 + NIGHT → 12/01 19:00 - 12/02 06:59
        
        if ($date && $shift === 'NIGHT') {
            $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
            
            if ($cutoff === 'ALL') {
                // NIGHT ALL: Production date 19:00-23:59 + Next day 00:00-06:59
                $query->where(function($q) use ($date, $nextDay, $dateColumn) {
                    $q->where(function($subQ) use ($date, $dateColumn) {
                        // Production date 19:00-23:59
                        $subQ->whereDate($dateColumn, $date)
                             ->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                    })->orWhere(function($subQ) use ($nextDay, $dateColumn) {
                        // Next day 00:00-06:59
                        $subQ->whereDate($dateColumn, $nextDay)
                             ->whereRaw("HOUR($dateColumn) >= 0 AND HOUR($dateColumn) < 7");
                    });
                });
            } elseif ($cutoff === '1ST') {
                // NIGHT 1ST: Production date 19:00-23:59
                $query->whereDate($dateColumn, $date)
                      ->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
            } elseif ($cutoff === '2ND') {
                // NIGHT 2ND: Next day 00:00-03:59
                $query->whereDate($dateColumn, $nextDay)
                      ->whereRaw("HOUR($dateColumn) >= 0 AND HOUR($dateColumn) < 4");
            } elseif ($cutoff === '3RD') {
                // NIGHT 3RD: Next day 04:00-06:59
                $query->whereDate($dateColumn, $nextDay)
                      ->whereRaw("HOUR($dateColumn) >= 4 AND HOUR($dateColumn) < 7");
            }
        } else {
            // DAY shift or ALL shift - standard date filtering
            if ($date) {
                if ($shift === 'ALL' && $cutoff === 'ALL') {
                    // ALL shift + ALL cutoff: Full production day (07:00 - next day 06:59)
                    $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
                    $query->where(function($q) use ($date, $nextDay, $dateColumn) {
                        $q->where(function($subQ) use ($date, $dateColumn) {
                            // Same day 07:00-23:59
                            $subQ->whereDate($dateColumn, $date)
                                 ->whereRaw("HOUR($dateColumn) >= 7");
                        })->orWhere(function($subQ) use ($nextDay, $dateColumn) {
                            // Next day 00:00-06:59
                            $subQ->whereDate($dateColumn, $nextDay)
                                 ->whereRaw("HOUR($dateColumn) < 7");
                        });
                    });
                } elseif ($shift === 'ALL' && $cutoff !== 'ALL') {
                    // ALL shift + specific cutoff: Both DAY and NIGHT portions for the production date
                    $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
                    
                    if ($cutoff === '1ST') {
                        // DAY 1ST (07:00-11:59) + NIGHT 1ST (19:00-23:59) - both on production date
                        $query->whereDate($dateColumn, $date)
                              ->where(function($q) use ($dateColumn) {
                                  $q->whereRaw("(HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 12)")
                                    ->orWhereRaw("(HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24)");
                              });
                    } elseif ($cutoff === '2ND') {
                        // DAY 2ND (12:00-15:59 on prod date) + NIGHT 2ND (00:00-03:59 on next day)
                        $query->where(function($q) use ($date, $nextDay, $dateColumn) {
                            $q->where(function($subQ) use ($date, $dateColumn) {
                                $subQ->whereDate($dateColumn, $date)
                                     ->whereRaw("HOUR($dateColumn) >= 12 AND HOUR($dateColumn) < 16");
                            })->orWhere(function($subQ) use ($nextDay, $dateColumn) {
                                $subQ->whereDate($dateColumn, $nextDay)
                                     ->whereRaw("HOUR($dateColumn) >= 0 AND HOUR($dateColumn) < 4");
                            });
                        });
                    } elseif ($cutoff === '3RD') {
                        // DAY 3RD (16:00-18:59 on prod date) + NIGHT 3RD (04:00-06:59 on next day)
                        $query->where(function($q) use ($date, $nextDay, $dateColumn) {
                            $q->where(function($subQ) use ($date, $dateColumn) {
                                $subQ->whereDate($dateColumn, $date)
                                     ->whereRaw("HOUR($dateColumn) >= 16 AND HOUR($dateColumn) < 19");
                            })->orWhere(function($subQ) use ($nextDay, $dateColumn) {
                                $subQ->whereDate($dateColumn, $nextDay)
                                     ->whereRaw("HOUR($dateColumn) >= 4 AND HOUR($dateColumn) < 7");
                            });
                        });
                    }
                } else {
                    // DAY shift - same calendar date
                    $query->whereDate($dateColumn, $date);
                    
                    if ($shift === 'DAY') {
                        $query->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 19");
                        
                        if ($cutoff === '1ST') {
                            $query->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 12");
                        } elseif ($cutoff === '2ND') {
                            $query->whereRaw("HOUR($dateColumn) >= 12 AND HOUR($dateColumn) < 16");
                        } elseif ($cutoff === '3RD') {
                            $query->whereRaw("HOUR($dateColumn) >= 16 AND HOUR($dateColumn) < 19");
                        }
                    }
                }
            }
        }

        // Apply work type filter
        if ($workType && $workType !== 'ALL') {
            $query->where('work_type', $workType);
        }
    }

    /**
     * Get line summary data for the table
     * Returns Target, ENDTIME, SUBMITTED, SUBMITTED %, and ENDTIME % per line
     * 
     * @param string|null $date Filter by date
     * @param string|null $shift Filter by shift
     * @param string|null $cutoff Filter by cutoff
     * @param string|null $workType Filter by work type
     * @return array
     */
    public static function getLineSummary(
        ?string $date = null,
        ?string $shift = null,
        ?string $cutoff = null,
        ?string $workType = null
    ): array {
        $lines = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        
        // Apply shift/cutoff division logic
        $divisor = 1;
        if ($shift !== 'ALL' && $cutoff === 'ALL') {
            $divisor = 2;
        } elseif ($shift !== 'ALL' && $cutoff !== 'ALL') {
            $divisor = 6;
        } elseif ($shift === 'ALL' && $cutoff !== 'ALL') {
            $divisor = 3;
        }

        $targetData = [];
        $endtimeData = [];
        $submittedData = [];
        $submittedPercentData = [];
        $endtimePercentData = [];
        
        $totalTarget = 0;
        $totalEndtime = 0;
        $totalSubmitted = 0;

        foreach ($lines as $line) {
            // Get target capacity from Equipment model
            $targetQuery = \App\Models\Equipment::where('eqp_status', 'OPERATIONAL')
                ->where('eqp_line', $line);
            
            if ($workType && $workType !== 'ALL') {
                $targetQuery->where('alloc_type', $workType);
            }
            
            $target = $targetQuery->sum('oee_capa') / $divisor;
            $targetInMillions = round($target / 1000000, 0);
            $targetData['line' . $line] = $targetInMillions;
            $totalTarget += $target;

            // Get endtime for this line (ongoing + submitted with different date columns)
            $ongoingQuery = self::query()->where('eqp_line', $line)->where('status', 'Ongoing');
            self::applyFilters($ongoingQuery, $date, $shift, $cutoff, $workType, 'est_endtime');
            $ongoingEndtime = $ongoingQuery->sum('lot_qty');
            
            $submittedQuery = self::query()->where('eqp_line', $line)->where('status', 'Submitted');
            self::applyFilters($submittedQuery, $date, $shift, $cutoff, $workType, 'actual_submitted_at');
            $submittedEndtime = $submittedQuery->sum('lot_qty');
            
            $endtime = $ongoingEndtime + $submittedEndtime;
            $endtimeInMillions = round($endtime / 1000000, 0);
            $endtimeData['line' . $line] = $endtimeInMillions;
            $totalEndtime += $endtime;

            // Get submitted for this line (use actual_submitted_at for filtering)
            $submittedQuery = self::query()->where('eqp_line', $line)->where('status', 'Submitted');
            self::applyFilters($submittedQuery, $date, $shift, $cutoff, $workType, 'actual_submitted_at');
            $submitted = $submittedQuery->sum('lot_qty');
            $submittedInMillions = round($submitted / 1000000, 0);
            $submittedData['line' . $line] = $submittedInMillions;
            $totalSubmitted += $submitted;

            // Calculate percentages
            // SUBMITTED % = Submitted / Endtime
            $submittedPercent = $endtime > 0 ? round(($submitted / $endtime) * 100, 0) : 0;
            $submittedPercentData['line' . $line] = $submittedPercent;

            // ENDTIME % = Endtime / Target
            $endtimePercent = $target > 0 ? round(($endtime / $target) * 100, 0) : 0;
            $endtimePercentData['line' . $line] = $endtimePercent;
        }

        // Calculate totals
        $totalTargetM = round($totalTarget / 1000000, 0);
        $totalEndtimeM = round($totalEndtime / 1000000, 0);
        $totalSubmittedM = round($totalSubmitted / 1000000, 0);
        // SUBMITTED % = Total Submitted / Total Endtime
        $totalSubmittedPercent = $totalEndtime > 0 ? round(($totalSubmitted / $totalEndtime) * 100, 0) : 0;
        // ENDTIME % = Total Endtime / Total Target
        $totalEndtimePercent = $totalTarget > 0 ? round(($totalEndtime / $totalTarget) * 100, 0) : 0;

        return [
            [
                'metric' => 'Target',
                'total' => $totalTargetM,
                ...$targetData
            ],
            [
                'metric' => 'ENDTIME',
                'total' => $totalEndtimeM,
                ...$endtimeData
            ],
            [
                'metric' => 'SUBMITTED',
                'total' => $totalSubmittedM,
                ...$submittedData
            ],
            [
                'metric' => 'SUBMITTED %',
                'total' => $totalSubmittedPercent,
                ...$submittedPercentData
            ],
            [
                'metric' => 'ENDTIME %',
                'total' => $totalEndtimePercent,
                ...$endtimePercentData
            ],
        ];
    }

    /**
     * Get size summary data for the table
     * Returns Target, ENDTIME, SUBMITTED, SUBMITTED %, and ENDTIME % per size
     * 
     * @param string|null $date Filter by date
     * @param string|null $shift Filter by shift
     * @param string|null $cutoff Filter by cutoff
     * @param string|null $workType Filter by work type
     * @return array
     */
    public static function getSizeSummary(
        ?string $date = null,
        ?string $shift = null,
        ?string $cutoff = null,
        ?string $workType = null
    ): array {
        // Map database size codes to display format
        $sizeMap = [
            '03' => 'size0603',
            '05' => 'size1005',
            '10' => 'size1608',
            '21' => 'size2012',
            '31' => 'size3216',
            '32' => 'size3225',
        ];
        
        // Apply shift/cutoff division logic
        $divisor = 1;
        if ($shift !== 'ALL' && $cutoff === 'ALL') {
            $divisor = 2;
        } elseif ($shift !== 'ALL' && $cutoff !== 'ALL') {
            $divisor = 6;
        } elseif ($shift === 'ALL' && $cutoff !== 'ALL') {
            $divisor = 3;
        }

        $targetData = [];
        $endtimeData = [];
        $submittedData = [];
        $submittedPercentData = [];
        $endtimePercentData = [];

        foreach ($sizeMap as $dbSize => $displaySize) {
            // Get target capacity from Equipment model
            $targetQuery = \App\Models\Equipment::where('eqp_status', 'OPERATIONAL')
                ->where('size', $dbSize);
            
            if ($workType && $workType !== 'ALL') {
                $targetQuery->where('alloc_type', $workType);
            }
            
            $target = $targetQuery->sum('oee_capa') / $divisor;
            $targetInMillions = $target / 1000000;
            
            // Use 1 decimal place for values less than 10M
            if ($targetInMillions < 10) {
                $targetData[$displaySize] = round($targetInMillions, 1);
            } else {
                $targetData[$displaySize] = round($targetInMillions, 0);
            }

            // Get endtime for this size (ongoing + submitted with different date columns)
            $ongoingQuery = self::query()->where('lot_size', $dbSize)->where('status', 'Ongoing');
            self::applyFilters($ongoingQuery, $date, $shift, $cutoff, $workType, 'est_endtime');
            $ongoingEndtime = $ongoingQuery->sum('lot_qty');
            
            $submittedQuery = self::query()->where('lot_size', $dbSize)->where('status', 'Submitted');
            self::applyFilters($submittedQuery, $date, $shift, $cutoff, $workType, 'actual_submitted_at');
            $submittedEndtime = $submittedQuery->sum('lot_qty');
            
            $endtime = $ongoingEndtime + $submittedEndtime;
            $endtimeInMillions = $endtime / 1000000;
            
            if ($endtimeInMillions < 10) {
                $endtimeData[$displaySize] = round($endtimeInMillions, 1);
            } else {
                $endtimeData[$displaySize] = round($endtimeInMillions, 0);
            }

            // Get submitted for this size (use actual_submitted_at for filtering)
            $submittedQuery = self::query()->where('lot_size', $dbSize)->where('status', 'Submitted');
            self::applyFilters($submittedQuery, $date, $shift, $cutoff, $workType, 'actual_submitted_at');
            $submitted = $submittedQuery->sum('lot_qty');
            $submittedInMillions = $submitted / 1000000;
            
            if ($submittedInMillions < 10) {
                $submittedData[$displaySize] = round($submittedInMillions, 1);
            } else {
                $submittedData[$displaySize] = round($submittedInMillions, 0);
            }

            // Calculate percentages
            // SUBMITTED % = Submitted / Endtime
            $submittedPercent = $endtime > 0 ? round(($submitted / $endtime) * 100, 0) : 0;
            $submittedPercentData[$displaySize] = $submittedPercent;

            // ENDTIME % = Endtime / Target
            $endtimePercent = $target > 0 ? round(($endtime / $target) * 100, 0) : 0;
            $endtimePercentData[$displaySize] = $endtimePercent;
        }

        return [
            [
                'metric' => 'Target',
                ...$targetData
            ],
            [
                'metric' => 'ENDTIME',
                ...$endtimeData
            ],
            [
                'metric' => 'SUBMITTED',
                ...$submittedData
            ],
            [
                'metric' => 'SUBMITTED %',
                ...$submittedPercentData
            ],
            [
                'metric' => 'ENDTIME %',
                ...$endtimePercentData
            ],
        ];
    }

    /**
     * Get remaining lots list for modal
     * Uses the same production date logic as applyFilters
     * 
     * @param string|null $date Production date (date when shift ends)
     * @param string|null $shift Filter by shift
     * @param string|null $cutoff Filter by cutoff
     * @param string|null $workType Filter by work type
     * @param bool $includePrevious Include lots from previous dates
     * @return array
     */
    public static function getRemainingLotsList(
        ?string $date = null,
        ?string $shift = null,
        ?string $cutoff = null,
        ?string $workType = null,
        bool $includePrevious = false
    ): array {
        $query = self::query()
            ->select('eqp_line', 'eqp_area', 'eqp_1', 'lot_id', 'model_15', 'lot_qty', 'work_type', 'est_endtime')
            ->where('status', 'Ongoing')
            ->orderBy('eqp_line')
            ->orderBy('eqp_area')
            ->orderBy('eqp_1');

        // Apply filters using the same production date logic
        if ($includePrevious && $date) {
            // Include current cutoff and all previous dates
            // Calculate the cutoff end datetime based on shift/cutoff selection
            $cutoffEndDatetime = self::calculateCutoffEndDatetime($date, $shift, $cutoff);
            
            $query->where('est_endtime', '<=', $cutoffEndDatetime);
            
            // Apply work type filter
            if ($workType && $workType !== 'ALL') {
                $query->where('work_type', $workType);
            }
        } else {
            // Use the standard applyFilters logic for production date
            self::applyFilters($query, $date, $shift, $cutoff, $workType, 'est_endtime');
        }

        return $query->get()->toArray();
    }
}
