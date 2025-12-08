<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Endtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EndtimeRankingController extends Controller
{
    /**
     * Display the ranking page
     */
    public function index(Request $request)
    {
        // Get default date and shift based on current time
        $defaultDate = $this->getShiftDate();
        $defaultShift = $this->getCurrentShift();
        
        $date = $request->input('date', $defaultDate);
        $shift = $request->input('shift', $defaultShift);
        $cutoff = $request->input('cutoff', 'ALL');
        $rankingType = $request->input('rankingType', 'line');

        // Get rankings based on type
        if ($rankingType === 'area') {
            $rankings = $this->getAreaRankings($date, $shift, $cutoff);
        } else {
            $rankings = $this->getLineRankings($date, $shift, $cutoff);
        }

        return Inertia::render('dashboards/subs/endtime-ranking', [
            'filters' => [
                'date' => $date,
                'shift' => $shift,
                'cutoff' => $cutoff,
                'rankingType' => $rankingType,
            ],
            'lineRankings' => $rankingType === 'line' ? $rankings : [],
            'areaRankings' => $rankingType === 'area' ? $rankings : [],
        ]);
    }

    /**
     * Get the current shift based on the current time
     * DAY: 07:00 AM - 06:59 PM
     * NIGHT: 07:00 PM - 06:59 AM
     */
    private function getCurrentShift(): string
    {
        $hour = now()->hour;
        
        // DAY shift: 7 AM (07:00) to 6:59 PM (18:59)
        // NIGHT shift: 7 PM (19:00) to 6:59 AM (06:59)
        if ($hour >= 7 && $hour < 19) {
            return 'DAY';
        } else {
            return 'NIGHT';
        }
    }

    /**
     * Get the date for the current shift
     * For NIGHT shift (7 PM to 6:59 AM), the shift "date" is the date when it started (at 7 PM)
     * - If current time is 00:00 AM to 06:59 AM → use previous date (shift started yesterday at 7 PM)
     * - If current time is 07:00 AM to 06:59 PM → use current date (DAY shift)
     * - If current time is 07:00 PM to 11:59 PM → use current date (NIGHT shift just started)
     */
    private function getShiftDate(): string
    {
        $now = now();
        $hour = $now->hour;
        
        // If time is between midnight (00:00) and 06:59 AM, we're in NIGHT shift that started yesterday
        // So use previous date as the shift date
        if ($hour >= 0 && $hour < 7) {
            return $now->subDay()->format('Y-m-d');
        }
        
        // Otherwise (7 AM onwards), use current date
        return $now->format('Y-m-d');
    }

    /**
     * Get line rankings
     */
    private function getLineRankings(string $date, string $shift, string $cutoff): array
    {
        $lines = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        $rankings = [];

        // Calculate divisor for target capacity
        $divisor = 1;
        if ($shift !== 'ALL' && $cutoff === 'ALL') {
            $divisor = 2;
        } elseif ($shift !== 'ALL' && $cutoff !== 'ALL') {
            $divisor = 6;
        } elseif ($shift === 'ALL' && $cutoff !== 'ALL') {
            $divisor = 3;
        }

        foreach ($lines as $line) {
            // Get target capacity
            $target = Equipment::where('eqp_status', 'OPERATIONAL')
                ->where('eqp_line', $line)
                ->sum('oee_capa') / $divisor;

            // Get machine count
            $machineCount = Equipment::where('eqp_status', 'OPERATIONAL')
                ->where('eqp_line', $line)
                ->count();

            // Get endtime (ongoing + submitted)
            $ongoingQuery = Endtime::query()->where('eqp_line', $line)->where('status', 'Ongoing');
            $this->applyFilters($ongoingQuery, $date, $shift, $cutoff, 'est_endtime');
            $ongoingEndtime = $ongoingQuery->sum('lot_qty');

            $submittedQuery = Endtime::query()->where('eqp_line', $line)->where('status', 'Submitted');
            $this->applyFilters($submittedQuery, $date, $shift, $cutoff, 'actual_submitted_at');
            $submittedEndtime = $submittedQuery->sum('lot_qty');

            $endtime = $ongoingEndtime + $submittedEndtime;

            // Get submitted (for display in table)
            $submittedOnlyQuery = Endtime::query()->where('eqp_line', $line)->where('status', 'Submitted');
            $this->applyFilters($submittedOnlyQuery, $date, $shift, $cutoff, 'actual_submitted_at');
            $lineSubmitted = $submittedOnlyQuery->sum('lot_qty');

            // Get lot count
            $lotCountQuery = Endtime::query()->where('eqp_line', $line);
            $this->applyFilters($lotCountQuery, $date, $shift, $cutoff, 'est_endtime');
            $lotCount = $lotCountQuery->count();

            // Calculate achievement
            $achievement = $target > 0 ? ($lineSubmitted / $target) * 100 : 0;

            // Get all unique operators from modified_by column for this line (for table display)
            // Only use actual_submitted_at for shift logic - shows operators who actually submitted during the shift
            $inChargeQuery = Endtime::query()
                ->select('modified_by', DB::raw('COUNT(*) as count'), DB::raw('SUM(lot_qty) as total_submitted'))
                ->where('eqp_line', $line)
                ->where('status', 'Submitted')
                ->whereNotNull('modified_by')
                ->where('modified_by', '!=', '');
            $this->applyFilters($inChargeQuery, $date, $shift, $cutoff, 'actual_submitted_at');
            $inChargeResults = $inChargeQuery->groupBy('modified_by')
                ->orderBy('total_submitted', 'desc')
                ->get();
            
            // Return array of all unique operators for table
            $inCharge = $inChargeResults->isNotEmpty() 
                ? $inChargeResults->pluck('modified_by')->toArray() 
                : ['-'];
            
            // Build operator stats with target, submitted, and achievement for modal display
            $operatorStats = [];
            $topPerformer = '-';
            $highestAchievement = 0;
            
            foreach ($inChargeResults as $operator) {
                $operatorName = $operator->modified_by;
                $operatorSubmitted = $operator->total_submitted;
                
                // Get operator's dominant area (area where they submitted the most)
                $operatorAreaBreakdown = Endtime::query()
                    ->select('eqp_area', DB::raw('SUM(lot_qty) as area_submitted'))
                    ->where('eqp_line', $line)
                    ->where('status', 'Submitted')
                    ->where('modified_by', $operatorName)
                    ->whereNotNull('eqp_area');
                $this->applyFilters($operatorAreaBreakdown, $date, $shift, $cutoff, 'actual_submitted_at');
                $areaBreakdown = $operatorAreaBreakdown->groupBy('eqp_area')
                    ->orderBy('area_submitted', 'desc')
                    ->first();
                
                // Use dominant area's target
                $operatorTarget = 0;
                if ($areaBreakdown) {
                    $dominantArea = $areaBreakdown->eqp_area;
                    $operatorDominantAreaSubmitted = $areaBreakdown->area_submitted;
                    
                    // Get total submitted for this area
                    $areaSubmittedQuery = Endtime::query()
                        ->where('eqp_line', $line)
                        ->where('eqp_area', $dominantArea)
                        ->where('status', 'Submitted');
                    $this->applyFilters($areaSubmittedQuery, $date, $shift, $cutoff, 'actual_submitted_at');
                    $areaTotalSubmitted = $areaSubmittedQuery->sum('lot_qty');
                    
                    // Get area target capacity
                    $areaTarget = Equipment::where('eqp_status', 'OPERATIONAL')
                        ->where('eqp_line', $line)
                        ->where('eqp_area', $dominantArea)
                        ->sum('oee_capa') / $divisor;
                    
                    // Operator's proportional target based on dominant area contribution
                    // Use only the submitted quantity in the dominant area, not total
                    if ($areaTotalSubmitted > 0) {
                        $operatorTarget = ($operatorDominantAreaSubmitted / $areaTotalSubmitted) * $areaTarget;
                    }
                }
                
                // Calculate achievement
                $operatorAchievement = $operatorTarget > 0 ? ($operatorSubmitted / $operatorTarget) * 100 : 0;
                
                $operatorStats[] = [
                    'name' => $operatorName,
                    'target' => round($operatorTarget / 1000000, 2),
                    'submitted' => round($operatorSubmitted / 1000000, 2),
                    'achievement' => round($operatorAchievement, 1),
                    'lotCount' => $operator->count,
                ];
                
                if ($operatorAchievement > $highestAchievement) {
                    $highestAchievement = $operatorAchievement;
                    $topPerformer = $operatorName;
                }
            }
            
            // Sort operator stats by achievement descending
            usort($operatorStats, function ($a, $b) {
                return $b['achievement'] <=> $a['achievement'];
            });
            
            $topPerformerName = $topPerformer;

            $rankings[] = [
                'line' => $line,
                'inCharge' => $inCharge,
                'operatorStats' => $operatorStats,
                'topPerformer' => $topPerformerName,
                'achievement' => round($achievement, 1),
                'target' => round($target / 1000000, 1),
                'endtime' => round($endtime / 1000000, 1),
                'submitted' => round($lineSubmitted / 1000000, 1),
                'lotCount' => $lotCount,
                'machineCount' => $machineCount,
            ];
        }

        // Sort by achievement descending and assign ranks
        usort($rankings, function ($a, $b) {
            return $b['achievement'] <=> $a['achievement'];
        });

        foreach ($rankings as $index => &$ranking) {
            $ranking['rank'] = $index + 1;
        }

        return $rankings;
    }

    /**
     * Get area rankings
     */
    private function getAreaRankings(string $date, string $shift, string $cutoff): array
    {
        $rankings = [];

        // Calculate divisor for target capacity
        $divisor = 1;
        if ($shift !== 'ALL' && $cutoff === 'ALL') {
            $divisor = 2;
        } elseif ($shift !== 'ALL' && $cutoff !== 'ALL') {
            $divisor = 6;
        } elseif ($shift === 'ALL' && $cutoff !== 'ALL') {
            $divisor = 3;
        }

        // Get all unique line-area combinations from equipment
        $lineAreas = Equipment::where('eqp_status', 'OPERATIONAL')
            ->select('eqp_line', 'eqp_area')
            ->distinct()
            ->get();

        foreach ($lineAreas as $lineArea) {
            $line = $lineArea->eqp_line;
            $area = $lineArea->eqp_area;

            // Get target capacity
            $target = Equipment::where('eqp_status', 'OPERATIONAL')
                ->where('eqp_line', $line)
                ->where('eqp_area', $area)
                ->sum('oee_capa') / $divisor;

            // Get machine count
            $machineCount = Equipment::where('eqp_status', 'OPERATIONAL')
                ->where('eqp_line', $line)
                ->where('eqp_area', $area)
                ->count();

            // Get endtime (ongoing + submitted)
            $ongoingQuery = Endtime::query()
                ->where('eqp_line', $line)
                ->where('eqp_area', $area)
                ->where('status', 'Ongoing');
            $this->applyFilters($ongoingQuery, $date, $shift, $cutoff, 'est_endtime');
            $ongoingEndtime = $ongoingQuery->sum('lot_qty');

            $submittedQuery = Endtime::query()
                ->where('eqp_line', $line)
                ->where('eqp_area', $area)
                ->where('status', 'Submitted');
            $this->applyFilters($submittedQuery, $date, $shift, $cutoff, 'actual_submitted_at');
            $submittedEndtime = $submittedQuery->sum('lot_qty');

            $endtime = $ongoingEndtime + $submittedEndtime;

            // Get submitted
            $submittedQuery = Endtime::query()
                ->where('eqp_line', $line)
                ->where('eqp_area', $area)
                ->where('status', 'Submitted');
            $this->applyFilters($submittedQuery, $date, $shift, $cutoff, 'actual_submitted_at');
            $submitted = $submittedQuery->sum('lot_qty');

            // Get lot count
            $lotCountQuery = Endtime::query()
                ->where('eqp_line', $line)
                ->where('eqp_area', $area);
            $this->applyFilters($lotCountQuery, $date, $shift, $cutoff, 'est_endtime');
            $lotCount = $lotCountQuery->count();

            // Calculate achievement
            $achievement = $target > 0 ? ($submitted / $target) * 100 : 0;

            // Get all unique operators from modified_by column for this line-area (for table display)
            // Only use actual_submitted_at for shift logic - shows operators who actually submitted during the shift
            $inChargeQuery = Endtime::query()
                ->select('modified_by', DB::raw('COUNT(*) as count'), DB::raw('SUM(lot_qty) as total_submitted'))
                ->where('eqp_line', $line)
                ->where('eqp_area', $area)
                ->where('status', 'Submitted')
                ->whereNotNull('modified_by')
                ->where('modified_by', '!=', '');
            $this->applyFilters($inChargeQuery, $date, $shift, $cutoff, 'actual_submitted_at');
            $inChargeResults = $inChargeQuery->groupBy('modified_by')
                ->orderBy('total_submitted', 'desc')
                ->get();
            
            // Return array of all unique operators for table
            $inCharge = $inChargeResults->isNotEmpty() 
                ? $inChargeResults->pluck('modified_by')->toArray() 
                : ['-'];
            
            // Build operator stats with target, submitted, and achievement for modal display
            $operatorStats = [];
            $topPerformer = '-';
            $highestAchievement = 0;
            
            foreach ($inChargeResults as $operator) {
                $operatorName = $operator->modified_by;
                $operatorSubmitted = $operator->total_submitted;
                
                // Calculate operator's proportional target based on their contribution
                // Operator Target = (Operator Submitted / Area Total Submitted) * Area Target
                $operatorTarget = $submitted > 0 ? ($operatorSubmitted / $submitted) * $target : 0;
                
                // Calculate achievement
                $operatorAchievement = $operatorTarget > 0 ? ($operatorSubmitted / $operatorTarget) * 100 : 0;
                
                $operatorStats[] = [
                    'name' => $operatorName,
                    'target' => round($operatorTarget / 1000000, 2),
                    'submitted' => round($operatorSubmitted / 1000000, 2),
                    'achievement' => round($operatorAchievement, 1),
                    'lotCount' => $operator->count,
                ];
                
                if ($operatorAchievement > $highestAchievement) {
                    $highestAchievement = $operatorAchievement;
                    $topPerformer = $operatorName;
                }
            }
            
            // Sort operator stats by achievement descending
            usort($operatorStats, function ($a, $b) {
                return $b['achievement'] <=> $a['achievement'];
            });
            
            $topPerformerName = $topPerformer;

            $rankings[] = [
                'line' => $line,
                'area' => $area,
                'inCharge' => $inCharge,
                'operatorStats' => $operatorStats,
                'topPerformer' => $topPerformerName,
                'achievement' => round($achievement, 1),
                'target' => round($target / 1000000, 1),
                'endtime' => round($endtime / 1000000, 1),
                'submitted' => round($submitted / 1000000, 1),
                'lotCount' => $lotCount,
                'machineCount' => $machineCount,
            ];
        }

        // Sort by achievement descending and assign ranks
        usort($rankings, function ($a, $b) {
            return $b['achievement'] <=> $a['achievement'];
        });

        foreach ($rankings as $index => &$ranking) {
            $ranking['rank'] = $index + 1;
        }

        return $rankings;
    }

    /**
     * Apply filters to query
     * 
     * Date Logic for NIGHT Shift:
     * - The "date" parameter represents when the NIGHT shift STARTED (at 7 PM)
     * - NIGHT shift spans from 7 PM on the given date to 6:59 AM the next day
     * - Example: date = 2025-11-28 means 2025-11-28 19:00 to 2025-11-29 06:59
     */
    private function applyFilters($query, string $date, string $shift, string $cutoff, string $dateColumn): void
    {
        // Special handling for NIGHT shift (spans across midnight from 7 PM to 6:59 AM)
        if ($date && $shift === 'NIGHT') {
            $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));

            if ($cutoff === 'ALL') {
                // NIGHT shift ALL: 7 PM on given date to 6:59 AM next day
                $query->where(function ($q) use ($date, $nextDay, $dateColumn) {
                    $q->where(function ($subQ) use ($date, $dateColumn) {
                        // 7 PM to 11:59 PM on the given date
                        $subQ->whereDate($dateColumn, $date)
                            ->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                    })->orWhere(function ($subQ) use ($nextDay, $dateColumn) {
                        // 12 AM to 6:59 AM on the next day
                        $subQ->whereDate($dateColumn, $nextDay)
                            ->whereRaw("HOUR($dateColumn) >= 0 AND HOUR($dateColumn) < 7");
                    });
                });
            } elseif ($cutoff === '1ST') {
                // NIGHT 1ST cutoff: 7 PM to 11:59 PM on the given date
                $query->whereDate($dateColumn, $date)
                    ->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
            } elseif ($cutoff === '2ND') {
                // NIGHT 2ND cutoff: 12 AM to 3:59 AM on the next day
                $query->whereDate($dateColumn, $nextDay)
                    ->whereRaw("HOUR($dateColumn) >= 0 AND HOUR($dateColumn) < 4");
            } elseif ($cutoff === '3RD') {
                // NIGHT 3RD cutoff: 4 AM to 6:59 AM on the next day
                $query->whereDate($dateColumn, $nextDay)
                    ->whereRaw("HOUR($dateColumn) >= 4 AND HOUR($dateColumn) < 7");
            }
        } elseif ($date && $shift === 'ALL') {
            // ALL shift: Include both DAY (7 AM - 6:59 PM) and NIGHT (7 PM - 6:59 AM next day)
            $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
            
            if ($cutoff === 'ALL') {
                // ALL shift ALL cutoff: Full 24 hours from 7 AM on given date to 6:59 AM next day
                $query->where(function ($q) use ($date, $nextDay, $dateColumn) {
                    $q->where(function ($subQ) use ($date, $dateColumn) {
                        // 7 AM to 11:59 PM on the given date (DAY + NIGHT 1ST)
                        $subQ->whereDate($dateColumn, $date)
                            ->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 24");
                    })->orWhere(function ($subQ) use ($nextDay, $dateColumn) {
                        // 12 AM to 6:59 AM on the next day (NIGHT 2ND + 3RD)
                        $subQ->whereDate($dateColumn, $nextDay)
                            ->whereRaw("HOUR($dateColumn) >= 0 AND HOUR($dateColumn) < 7");
                    });
                });
            } elseif ($cutoff === '1ST') {
                // ALL shift 1ST cutoff: DAY 1ST (7 AM - 11:59 AM) + NIGHT 1ST (7 PM - 11:59 PM)
                $query->where(function ($q) use ($date, $dateColumn) {
                    $q->where(function ($subQ) use ($date, $dateColumn) {
                        // DAY 1ST: 7 AM to 11:59 AM
                        $subQ->whereDate($dateColumn, $date)
                            ->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 12");
                    })->orWhere(function ($subQ) use ($date, $dateColumn) {
                        // NIGHT 1ST: 7 PM to 11:59 PM
                        $subQ->whereDate($dateColumn, $date)
                            ->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                    });
                });
            } elseif ($cutoff === '2ND') {
                // ALL shift 2ND cutoff: DAY 2ND (12 PM - 3:59 PM) + NIGHT 2ND (12 AM - 3:59 AM next day)
                $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
                $query->where(function ($q) use ($date, $nextDay, $dateColumn) {
                    $q->where(function ($subQ) use ($date, $dateColumn) {
                        // DAY 2ND: 12 PM to 3:59 PM
                        $subQ->whereDate($dateColumn, $date)
                            ->whereRaw("HOUR($dateColumn) >= 12 AND HOUR($dateColumn) < 16");
                    })->orWhere(function ($subQ) use ($nextDay, $dateColumn) {
                        // NIGHT 2ND: 12 AM to 3:59 AM next day
                        $subQ->whereDate($dateColumn, $nextDay)
                            ->whereRaw("HOUR($dateColumn) >= 0 AND HOUR($dateColumn) < 4");
                    });
                });
            } elseif ($cutoff === '3RD') {
                // ALL shift 3RD cutoff: DAY 3RD (4 PM - 6:59 PM) + NIGHT 3RD (4 AM - 6:59 AM next day)
                $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
                $query->where(function ($q) use ($date, $nextDay, $dateColumn) {
                    $q->where(function ($subQ) use ($date, $dateColumn) {
                        // DAY 3RD: 4 PM to 6:59 PM
                        $subQ->whereDate($dateColumn, $date)
                            ->whereRaw("HOUR($dateColumn) >= 16 AND HOUR($dateColumn) < 19");
                    })->orWhere(function ($subQ) use ($nextDay, $dateColumn) {
                        // NIGHT 3RD: 4 AM to 6:59 AM next day
                        $subQ->whereDate($dateColumn, $nextDay)
                            ->whereRaw("HOUR($dateColumn) >= 4 AND HOUR($dateColumn) < 7");
                    });
                });
            }
        } else {
            // Standard date filtering for DAY shift only
            if ($date) {
                $query->whereDate($dateColumn, $date);
            }

            // Apply shift filter
            if ($shift && $shift !== 'ALL') {
                if ($shift === 'DAY') {
                    $query->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 19");
                }
            }

            // Apply cutoff filter for DAY shift
            if ($cutoff && $cutoff !== 'ALL') {
                if ($cutoff === '1ST') {
                    // DAY 1ST: 7 AM to 11:59 AM
                    $query->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 12");
                } elseif ($cutoff === '2ND') {
                    // DAY 2ND: 12 PM to 3:59 PM
                    $query->whereRaw("HOUR($dateColumn) >= 12 AND HOUR($dateColumn) < 16");
                } elseif ($cutoff === '3RD') {
                    // DAY 3RD: 4 PM to 6:59 PM
                    $query->whereRaw("HOUR($dateColumn) >= 16 AND HOUR($dateColumn) < 19");
                }
            }
        }
    }


}
