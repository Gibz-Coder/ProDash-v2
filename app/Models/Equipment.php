<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    protected $table = 'equipment';

    protected $fillable = [
        'eqp_status',
        'alloc_type',
        'oee_capa',
        'remarks',
        'modified_by',
    ];

    /**
     * Get target capacity based on filters
     */
    public static function getTargetCapacity(?string $workType = null, ?string $shift = 'ALL', ?string $cutoff = 'ALL'): array
    {
        $query = self::where('eqp_status', 'OPERATIONAL');

        if ($workType && $workType !== 'ALL') {
            $query->where('alloc_type', $workType);
        }

        $total = $query->sum('oee_capa');
        $count = $query->count();
        
        // Get total operational equipment count for ratio
        $totalOperational = self::where('eqp_status', 'OPERATIONAL')->count();

        // Apply shift/cutoff division logic
        $divisor = 1;
        
        if ($shift !== 'ALL' && $cutoff === 'ALL') {
            // If specific shift (DAY or NIGHT) with ALL cutoff, divide by 2
            $divisor = 2;
        } elseif ($shift !== 'ALL' && $cutoff !== 'ALL') {
            // If specific shift and specific cutoff (1ST, 2ND, 3RD), divide by 6
            $divisor = 6;
        } elseif ($shift === 'ALL' && $cutoff !== 'ALL') {
            // If ALL shifts but specific cutoff, divide by 3 (cutoff applies to both shifts)
            $divisor = 3;
        }
        // If both ALL, divisor remains 1 (no division)

        $adjustedTotal = $total / $divisor;

        // Convert to millions
        return [
            'value' => round($adjustedTotal / 1000000, 1),
            'count' => $count,
            'total' => $totalOperational,
            'divisor' => $divisor,
        ];
    }

    /**
     * Get production data per line based on filters
     */
    public static function getProductionByLine(?string $workType = null, ?string $shift = 'ALL', ?string $cutoff = 'ALL'): array
    {
        $lines = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        $productionData = [];

        // Apply shift/cutoff division logic
        $divisor = 1;
        
        if ($shift !== 'ALL' && $cutoff === 'ALL') {
            $divisor = 2;
        } elseif ($shift !== 'ALL' && $cutoff !== 'ALL') {
            $divisor = 6;
        } elseif ($shift === 'ALL' && $cutoff !== 'ALL') {
            $divisor = 3;
        }

        foreach ($lines as $line) {
            $query = self::where('eqp_status', 'OPERATIONAL')
                        ->where('eqp_line', $line);

            if ($workType && $workType !== 'ALL') {
                $query->where('alloc_type', $workType);
            }

            $target = $query->sum('oee_capa') / $divisor;

            // Convert to millions and round to 1 decimal
            $productionData[] = [
                'month' => 'Line ' . $line,
                'target' => round($target / 1000000, 1),
                'endtime' => round($target / 1000000, 1), // TODO: Replace with actual endtime data
                'submitted' => round($target / 1000000, 1), // TODO: Replace with actual submitted data
                'remaining' => 0, // TODO: Replace with actual remaining data
            ];
        }

        return $productionData;
    }

    /**
     * Get line summary data (for Per Line Summary table)
     */
    public static function getLineSummary(?string $workType = null, ?string $shift = 'ALL', ?string $cutoff = 'ALL'): array
    {
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

        $lineData = [];
        $totalTarget = 0;
        
        foreach ($lines as $line) {
            $query = self::where('eqp_status', 'OPERATIONAL')
                        ->where('eqp_line', $line);

            if ($workType && $workType !== 'ALL') {
                $query->where('alloc_type', $workType);
            }

            $target = $query->sum('oee_capa') / $divisor;
            $lineData['line' . $line] = round($target / 1000000, 0); // Round to integer for M
            $totalTarget += $target;
        }

        // Calculate total in millions
        $total = round($totalTarget / 1000000, 0);

        return [
            [
                'metric' => 'Target',
                'total' => $total,
                ...$lineData
            ],
            [
                'metric' => 'ENDTIME',
                'total' => $total, // TODO: Replace with actual endtime total
                ...$lineData // TODO: Replace with actual endtime data
            ],
            [
                'metric' => 'SUBMITTED',
                'total' => $total, // TODO: Replace with actual submitted total
                ...$lineData // TODO: Replace with actual submitted data
            ],
            [
                'metric' => 'SUBMITTED %',
                'total' => 100,
                'lineA' => 100, 'lineB' => 100, 'lineC' => 100, 'lineD' => 100, 
                'lineE' => 100, 'lineF' => 100, 'lineG' => 100, 'lineH' => 100,
                'lineI' => 100, 'lineJ' => 100, 'lineK' => 100
            ],
            [
                'metric' => 'ENDTIME %',
                'total' => 100,
                'lineA' => 100, 'lineB' => 100, 'lineC' => 100, 'lineD' => 100,
                'lineE' => 100, 'lineF' => 100, 'lineG' => 100, 'lineH' => 100,
                'lineI' => 100, 'lineJ' => 100, 'lineK' => 100
            ],
        ];
    }

    /**
     * Get machine utilization statistics
     */
    public static function getMachineUtilization(?string $workType = null, ?string $line = null): array
    {
        $query = self::where('eqp_status', 'OPERATIONAL');

        if ($workType && $workType !== 'ALL') {
            $query->where('alloc_type', $workType);
        }

        if ($line && $line !== 'ALL') {
            $query->where('eqp_line', $line);
        }

        $total = $query->count();
        
        // Clone query for with lot count
        $withLotQuery = clone $query;
        $withLot = $withLotQuery->whereNotNull('ongoing_lot')
                                ->where('ongoing_lot', '!=', '')
                                ->count();
        
        $withoutLot = $total - $withLot;
        
        $utilizationPercentage = $total > 0 ? round(($withLot / $total) * 100) : 0;

        return [
            'total' => $total,
            'withLot' => $withLot,
            'withoutLot' => $withoutLot,
            'utilizationPercentage' => $utilizationPercentage,
        ];
    }

    /**
     * Get equipment list with or without ongoing lots
     */
    public static function getEquipmentList(?string $workType = null, ?string $line = null, bool $withLot = true): array
    {
        $query = self::where('eqp_status', 'OPERATIONAL')
                    ->select('eqp_line', 'eqp_area', 'eqp_no', 'size', 'alloc_type', 'ongoing_lot', 'updated_at')
                    ->orderBy('eqp_line')
                    ->orderBy('eqp_area')
                    ->orderBy('eqp_no');

        if ($workType && $workType !== 'ALL') {
            $query->where('alloc_type', $workType);
        }

        if ($line && $line !== 'ALL') {
            $query->where('eqp_line', $line);
        }

        if ($withLot) {
            $query->whereNotNull('ongoing_lot')
                  ->where('ongoing_lot', '!=', '');
        } else {
            $query->where(function($q) {
                $q->whereNull('ongoing_lot')
                  ->orWhere('ongoing_lot', '');
            });
        }

        return $query->get()->toArray();
    }

    /**
     * Get size summary data (for Per Size Summary table)
     */
    public static function getSizeSummary(?string $workType = null, ?string $shift = 'ALL', ?string $cutoff = 'ALL'): array
    {
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

        $sizeData = [];
        
        foreach ($sizeMap as $dbSize => $displaySize) {
            $query = self::where('eqp_status', 'OPERATIONAL')
                        ->where('size', $dbSize);

            if ($workType && $workType !== 'ALL') {
                $query->where('alloc_type', $workType);
            }

            $target = $query->sum('oee_capa') / $divisor;
            $targetInMillions = $target / 1000000;
            
            // Use 1 decimal place for values less than 10M to avoid showing 0
            if ($targetInMillions < 10) {
                $sizeData[$displaySize] = round($targetInMillions, 1);
            } else {
                $sizeData[$displaySize] = round($targetInMillions, 0);
            }
        }

        return [
            [
                'metric' => 'Target',
                ...$sizeData
            ],
            [
                'metric' => 'ENDTIME',
                ...$sizeData // TODO: Replace with actual endtime data
            ],
            [
                'metric' => 'SUBMITTED',
                ...$sizeData // TODO: Replace with actual submitted data
            ],
            [
                'metric' => 'SUBMITTED %',
                'size0603' => 100, 'size1005' => 100, 'size1608' => 100,
                'size2012' => 100, 'size3216' => 100, 'size3225' => 100
            ],
            [
                'metric' => 'ENDTIME %',
                'size0603' => 100, 'size1005' => 100, 'size1608' => 100,
                'size2012' => 100, 'size3216' => 100, 'size3225' => 100
            ],
        ];
    }
}
