<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Endtime;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EndtimeController extends Controller
{
    public function index(Request $request)
    {
        $autoRefresh = filter_var($request->input('refresh', 'false'), FILTER_VALIDATE_BOOLEAN);
        $workType = $request->input('worktype', 'ALL');
        
        // Line filter - reset to ALL when auto-refresh is ON
        $line = $autoRefresh ? 'ALL' : $request->input('line', 'ALL');
        
        // Auto-refresh logic: determine shift, cutoff, and production date based on current time
        // Production Date Logic:
        // - DAY shift: Production date = same calendar date (07:00-18:59)
        // - NIGHT shift: Production date = date when shift STARTS
        //   - NIGHT 1ST (19:00-23:59): Production date = current day
        //   - NIGHT 2ND (00:00-03:59): Production date = previous day (shift started yesterday)
        //   - NIGHT 3RD (04:00-06:59): Production date = previous day (shift started yesterday)
        if ($autoRefresh) {
            $currentTime = now();
            $hour = $currentTime->hour;
            
            // Determine shift and cutoff based on time
            if ($hour >= 7 && $hour < 12) {
                $shift = 'DAY';
                $cutoff = '1ST';
                $date = $currentTime->format('Y-m-d');
            } elseif ($hour >= 12 && $hour < 16) {
                $shift = 'DAY';
                $cutoff = '2ND';
                $date = $currentTime->format('Y-m-d');
            } elseif ($hour >= 16 && $hour < 19) {
                $shift = 'DAY';
                $cutoff = '3RD';
                $date = $currentTime->format('Y-m-d');
            } elseif ($hour >= 19 && $hour < 24) {
                // NIGHT 1ST: 19:00-23:59, production date = current day (shift starts today)
                $shift = 'NIGHT';
                $cutoff = '1ST';
                $date = $currentTime->format('Y-m-d');
            } elseif ($hour >= 0 && $hour < 4) {
                // NIGHT 2ND: 00:00-03:59, production date = previous day (shift started yesterday)
                $shift = 'NIGHT';
                $cutoff = '2ND';
                $date = $currentTime->subDay()->format('Y-m-d');
            } else { // 4-6:59
                // NIGHT 3RD: 04:00-06:59, production date = previous day (shift started yesterday)
                $shift = 'NIGHT';
                $cutoff = '3RD';
                $date = $currentTime->subDay()->format('Y-m-d');
            }
        } else {
            // Manual mode: use provided values
            $shift = $request->input('shift', 'ALL');
            $cutoff = $request->input('cutoff', 'ALL');
            $date = $request->input('date', now()->format('Y-m-d'));
        }

        // Get Target Capacity with shift/cutoff logic
        $targetCapacity = Equipment::getTargetCapacity($workType, $shift, $cutoff);

        // Format for display
        $targetCapacityFormatted = number_format($targetCapacity['value'], 1) . ' M PCS';
        $ratio = $targetCapacity['total'] > 0 
            ? round(($targetCapacity['count'] / $targetCapacity['total']) * 100, 1) 
            : 0;
        $targetCapacitySubtitle = $targetCapacity['count'] . ' Eqp | ' . $ratio . '%';

        // Get Total Endtime data from endtime table
        $totalEndtime = Endtime::getTotalEndtime($date, $shift, $cutoff, $workType);
        $endtimeQty = $totalEndtime['quantity'];
        $endtimeLotCount = $totalEndtime['lotCount'];
        
        // Convert to millions
        $endtimeQtyInMillions = $endtimeQty / 1000000;
        $endtimeQtyFormatted = number_format($endtimeQtyInMillions, 1) . ' M PCS';
        
        // Calculate percentage against target capacity
        $targetCapacityValue = $targetCapacity['value'] * 1000000; // Convert back to actual value
        $endtimePercentage = $targetCapacityValue > 0 
            ? round(($endtimeQty / $targetCapacityValue) * 100, 1) 
            : 0;
        
        // Format subtitle as "Lot Count | Percentage"
        $endtimeSubtitle = number_format($endtimeLotCount) . ' Lot | ' . $endtimePercentage . '%';

        // Get Total Submitted Lots data from endtime table (status = "Submitted")
        $totalSubmitted = Endtime::getTotalSubmitted($date, $shift, $cutoff, $workType);
        $submittedQty = $totalSubmitted['quantity'];
        $submittedLotCount = $totalSubmitted['lotCount'];
        
        // Convert to millions
        $submittedQtyInMillions = $submittedQty / 1000000;
        $submittedQtyFormatted = number_format($submittedQtyInMillions, 1) . ' M PCS';
        
        // Calculate percentage against target capacity
        $submittedPercentage = $targetCapacityValue > 0 
            ? round(($submittedQty / $targetCapacityValue) * 100, 1) 
            : 0;
        
        // Format subtitle as "Lot Count | Percentage"
        $submittedSubtitle = number_format($submittedLotCount) . ' Lot | ' . $submittedPercentage . '%';

        // Get Total Remaining Lots data from endtime table (status = "Ongoing")
        // The getTotalRemaining method handles date adjustment for NIGHT 2ND/3RD cutoffs internally
        $totalRemaining = Endtime::getTotalRemaining($date, $shift, $cutoff, $workType);
        $remainingQty = $totalRemaining['quantity'];
        $remainingLotCount = $totalRemaining['lotCount'];
        
        // Convert to millions
        $remainingQtyInMillions = $remainingQty / 1000000;
        $remainingQtyFormatted = number_format($remainingQtyInMillions, 1) . ' M PCS';
        
        // Calculate percentage against target capacity
        $remainingPercentage = $targetCapacityValue > 0 
            ? round(($remainingQty / $targetCapacityValue) * 100, 1) 
            : 0;
        
        // Format subtitle as "Lot Count | Percentage"
        $remainingSubtitle = number_format($remainingLotCount) . ' Lot | ' . $remainingPercentage . '%';

        // Get production data per line from endtime table
        $productionByLine = Endtime::getProductionByLine($date, $shift, $cutoff, $workType);
        
        // Get line summary data from endtime table
        $lineSummary = Endtime::getLineSummary($date, $shift, $cutoff, $workType);
        
        // Get size summary data from endtime table
        $sizeSummary = Endtime::getSizeSummary($date, $shift, $cutoff, $workType);
        
        // Get machine utilization data
        $machineUtilization = Equipment::getMachineUtilization($workType, $line);

        return Inertia::render('dashboards/main/endtime', [
            'filters' => [
                'date' => $date,
                'shift' => $shift,
                'cutoff' => $cutoff,
                'worktype' => $workType,
                'refresh' => $autoRefresh,
                'line' => $line,
            ],
            'metrics' => [
                'targetCapacity' => $targetCapacity['value'],
                'targetCapacityFormatted' => $targetCapacityFormatted,
                'targetCapacitySubtitle' => $targetCapacitySubtitle,
                'targetCapacityCount' => $targetCapacity['count'],
                'targetCapacityTotal' => $targetCapacity['total'],
                'totalEndtime' => $endtimeQtyInMillions,
                'totalEndtimeFormatted' => $endtimeQtyFormatted,
                'totalEndtimeSubtitle' => $endtimeSubtitle,
                'totalEndtimeLotCount' => $endtimeLotCount,
                'totalSubmitted' => $submittedQtyInMillions,
                'totalSubmittedFormatted' => $submittedQtyFormatted,
                'totalSubmittedSubtitle' => $submittedSubtitle,
                'totalSubmittedLotCount' => $submittedLotCount,
                'totalRemaining' => $remainingQtyInMillions,
                'totalRemainingFormatted' => $remainingQtyFormatted,
                'totalRemainingSubtitle' => $remainingSubtitle,
                'totalRemainingLotCount' => $remainingLotCount,
            ],
            'productionData' => $productionByLine,
            'lineData' => $lineSummary,
            'sizeData' => $sizeSummary,
            'machineStats' => $machineUtilization,
        ]);
    }

    /**
     * Keep session alive endpoint
     * This endpoint is called periodically when auto-refresh is enabled
     * to prevent session timeout
     */
    public function keepAlive(Request $request)
    {
        // Simply touch the session to keep it alive
        $request->session()->put('last_activity', now());
        
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get equipment list for modal
     */
    public function getEquipmentList(Request $request)
    {
        $workType = $request->input('worktype', 'ALL');
        $line = $request->input('line', 'ALL');
        $type = $request->input('type', 'with'); // 'with' or 'without'
        
        $withLot = $type === 'with';
        $equipment = Equipment::getEquipmentList($workType, $line, $withLot);
        
        return response()->json([
            'equipment' => $equipment,
            'type' => $type,
        ]);
    }

    /**
     * Get remaining lots list for modal
     */
    public function getRemainingLotsList(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $shift = $request->input('shift', 'ALL');
        $cutoff = $request->input('cutoff', 'ALL');
        $workType = $request->input('worktype', 'ALL');
        $includePrevious = filter_var($request->input('includePrevious', 'false'), FILTER_VALIDATE_BOOLEAN);
        
        $remainingLots = Endtime::getRemainingLotsList($date, $shift, $cutoff, $workType, $includePrevious);
        
        return response()->json([
            'remainingLots' => $remainingLots,
        ]);
    }

    /**
     * Export endtime data to CSV
     */
    public function exportData(Request $request)
    {
        $dateFrom = $request->input('dateFrom', now()->format('Y-m-d'));
        $dateTo = $request->input('dateTo', now()->format('Y-m-d'));
        $shift = $request->input('shift', 'ALL');
        $cutoff = $request->input('cutoff', 'ALL');
        
        // Build query
        $query = Endtime::query();
        
        // Apply date range filter using est_endtime for ongoing, actual_submitted_at for submitted
        if ($dateFrom && $dateTo) {
            $query->where(function($q) use ($dateFrom, $dateTo) {
                $q->where(function($subQ) use ($dateFrom, $dateTo) {
                    $subQ->where('status', 'Ongoing')
                         ->whereDate('est_endtime', '>=', $dateFrom)
                         ->whereDate('est_endtime', '<=', $dateTo);
                })->orWhere(function($subQ) use ($dateFrom, $dateTo) {
                    $subQ->where('status', 'Submitted')
                         ->whereDate('actual_submitted_at', '>=', $dateFrom)
                         ->whereDate('actual_submitted_at', '<=', $dateTo);
                });
            });
        }
        
        // Apply shift filter
        if ($shift && $shift !== 'ALL') {
            $query->where(function($q) use ($shift) {
                $q->where(function($subQ) use ($shift) {
                    // For ongoing lots, use est_endtime
                    $subQ->where('status', 'Ongoing');
                    if ($shift === 'DAY') {
                        $subQ->whereRaw('HOUR(est_endtime) >= 7 AND HOUR(est_endtime) < 19');
                    } else {
                        $subQ->whereRaw('(HOUR(est_endtime) >= 19 OR HOUR(est_endtime) < 7)');
                    }
                })->orWhere(function($subQ) use ($shift) {
                    // For submitted lots, use actual_submitted_at
                    $subQ->where('status', 'Submitted');
                    if ($shift === 'DAY') {
                        $subQ->whereRaw('HOUR(actual_submitted_at) >= 7 AND HOUR(actual_submitted_at) < 19');
                    } else {
                        $subQ->whereRaw('(HOUR(actual_submitted_at) >= 19 OR HOUR(actual_submitted_at) < 7)');
                    }
                });
            });
        }
        
        // Apply cutoff filter
        if ($cutoff && $cutoff !== 'ALL') {
            $query->where(function($q) use ($cutoff) {
                $q->where(function($subQ) use ($cutoff) {
                    // For ongoing lots, use est_endtime
                    $subQ->where('status', 'Ongoing');
                    if ($cutoff === '1ST') {
                        $subQ->whereRaw('((HOUR(est_endtime) >= 7 AND HOUR(est_endtime) < 12) OR (HOUR(est_endtime) >= 19 AND HOUR(est_endtime) < 24))');
                    } elseif ($cutoff === '2ND') {
                        $subQ->whereRaw('((HOUR(est_endtime) >= 12 AND HOUR(est_endtime) < 16) OR (HOUR(est_endtime) >= 0 AND HOUR(est_endtime) < 4))');
                    } elseif ($cutoff === '3RD') {
                        $subQ->whereRaw('((HOUR(est_endtime) >= 16 AND HOUR(est_endtime) < 19) OR (HOUR(est_endtime) >= 4 AND HOUR(est_endtime) < 7))');
                    }
                })->orWhere(function($subQ) use ($cutoff) {
                    // For submitted lots, use actual_submitted_at
                    $subQ->where('status', 'Submitted');
                    if ($cutoff === '1ST') {
                        $subQ->whereRaw('((HOUR(actual_submitted_at) >= 7 AND HOUR(actual_submitted_at) < 12) OR (HOUR(actual_submitted_at) >= 19 AND HOUR(actual_submitted_at) < 24))');
                    } elseif ($cutoff === '2ND') {
                        $subQ->whereRaw('((HOUR(actual_submitted_at) >= 12 AND HOUR(actual_submitted_at) < 16) OR (HOUR(actual_submitted_at) >= 0 AND HOUR(actual_submitted_at) < 4))');
                    } elseif ($cutoff === '3RD') {
                        $subQ->whereRaw('((HOUR(actual_submitted_at) >= 16 AND HOUR(actual_submitted_at) < 19) OR (HOUR(actual_submitted_at) >= 4 AND HOUR(actual_submitted_at) < 7))');
                    }
                });
            });
        }
        
        $lots = $query->orderBy('created_at', 'desc')->get();
        
        // Add computed date, shift, and cutoff for each lot based on status
        $lots->each(function ($lot) {
            $referenceTime = null;
            
            // Determine which datetime to use based on status
            if ($lot->status === 'Ongoing') {
                // For Ongoing lots, use est_endtime
                $referenceTime = $lot->est_endtime;
            } elseif ($lot->status === 'Submitted') {
                // For Submitted lots, use actual_submitted_at
                $referenceTime = $lot->actual_submitted_at;
            }
            
            if ($referenceTime) {
                $datetime = \Carbon\Carbon::parse($referenceTime);
                $hour = $datetime->hour;
                
                // Extract date
                $lot->computed_date = $datetime->format('Y-m-d');
                
                // Determine shift and cutoff based on hour
                if ($hour >= 7 && $hour < 12) {
                    $lot->computed_shift = 'DAY';
                    $lot->computed_cutoff = '1ST';
                } elseif ($hour >= 12 && $hour < 16) {
                    $lot->computed_shift = 'DAY';
                    $lot->computed_cutoff = '2ND';
                } elseif ($hour >= 16 && $hour < 19) {
                    $lot->computed_shift = 'DAY';
                    $lot->computed_cutoff = '3RD';
                } elseif ($hour >= 19 && $hour < 24) {
                    $lot->computed_shift = 'NIGHT';
                    $lot->computed_cutoff = '1ST';
                } elseif ($hour >= 0 && $hour < 4) {
                    $lot->computed_shift = 'NIGHT';
                    $lot->computed_cutoff = '2ND';
                } else { // 4-6:59
                    $lot->computed_shift = 'NIGHT';
                    $lot->computed_cutoff = '3RD';
                }
            } else {
                $lot->computed_date = null;
                $lot->computed_shift = null;
                $lot->computed_cutoff = null;
            }
        });
        
        return response()->json([
            'lots' => $lots,
        ]);
    }

    /**
     * Lookup ongoing lot from endtime table for submission
     */
    public function lookupOngoingLot(Request $request)
    {
        $lotId = $request->input('lot_id');
        
        if (!$lotId) {
            return response()->json([
                'success' => false,
                'message' => 'Lot ID is required'
            ], 400);
        }

        // Check if lot exists with Ongoing status
        $ongoingLot = Endtime::where('lot_id', $lotId)
            ->where('status', 'Ongoing')
            ->first();

        if (!$ongoingLot) {
            return response()->json([
                'success' => false,
                'message' => 'Hindi naka ENDTIME !! 😤'
            ], 404);
        }

        // Get machine list
        $machines = [];
        for ($i = 1; $i <= 10; $i++) {
            $eqpField = 'eqp_' . $i;
            if (!empty($ongoingLot->$eqpField)) {
                $machines[] = $ongoingLot->$eqpField;
            }
        }
        $machineList = !empty($machines) ? implode(', ', $machines) : '-';

        // Return lot data
        return response()->json([
            'success' => true,
            'endtimeData' => [
                'id' => $ongoingLot->id,
                'lot_id' => $ongoingLot->lot_id,
                'lot_qty' => $ongoingLot->lot_qty,
                'lot_size' => $ongoingLot->lot_size,
                'lot_type' => $ongoingLot->lot_type,
                'model_15' => $ongoingLot->model_15,
                'work_type' => $ongoingLot->work_type,
                'lipas_yn' => $ongoingLot->lipas_yn,
                'machine_list' => $machineList,
                'est_endtime' => $ongoingLot->est_endtime,
            ]
        ]);
    }

    /**
     * Submit a lot (update status to Submitted and set actual endtime)
     */
    public function submitLot(Request $request, $id)
    {
        $endtime = Endtime::findOrFail($id);

        // Validate that lot is in Ongoing status
        if ($endtime->status !== 'Ongoing') {
            return back()->withErrors([
                'status' => 'Only lots with Ongoing status can be submitted.'
            ]);
        }

        // Validate the request
        $validated = $request->validate([
            'actual_endtime' => 'required|date',
            'remarks' => 'required|string|in:OK,EARLY,LATE',
            'submission_notes' => 'nullable|string|max:100',
            'employee_id' => 'required|string|max:10',
        ], [
            'employee_id.required' => 'The employee ID field is required!',
        ]);

        // Lookup employee name from employee_id
        $employee = User::where('emp_no', $validated['employee_id'])->first();
        $employeeName = $employee ? $employee->emp_name : $validated['employee_id'];

        // Update the lot entry
        $endtime->update([
            'status' => 'Submitted',
            'actual_submitted_at' => $validated['actual_endtime'],
            'remarks' => $validated['remarks'],
            'submission_notes' => $validated['submission_notes'] ?? null,
            'submitted_by' => $validated['employee_id'],
            'modified_by' => $employeeName,
        ]);

        // Clear ongoing_lot from equipment table
        for ($i = 1; $i <= 10; $i++) {
            $eqpField = 'eqp_' . $i;
            if (!empty($endtime->$eqpField)) {
                Equipment::where('eqp_no', $endtime->$eqpField)
                    ->where('ongoing_lot', $endtime->lot_id)
                    ->update(['ongoing_lot' => null]);
            }
        }

        return redirect()->route('endtime_add')->with('success', 'Lot submitted successfully!');
    }



    /**
     * Lookup lot information from updatewip table and check endtime table
     */
    public function lookupLot(Request $request)
    {
        $lotId = $request->input('lot_id');
        
        if (!$lotId) {
            return response()->json([
                'success' => false,
                'message' => 'Lot ID is required'
            ], 400);
        }

        // First, check if lot exists with Ongoing status (priority check)
        $ongoingLot = Endtime::where('lot_id', $lotId)
            ->where('status', 'Ongoing')
            ->first();

        if ($ongoingLot) {
            // Load existing data for update
            return response()->json([
                'success' => true,
                'existingEndtime' => true,
                'status' => 'Ongoing',
                'message' => 'Lot found in endtime with Ongoing status. Loading data for update.',
                'endtimeData' => [
                    'id' => $ongoingLot->id,
                    'lot_id' => $ongoingLot->lot_id,
                    'lot_qty' => $ongoingLot->lot_qty,
                    'lot_size' => $ongoingLot->lot_size,
                    'lot_type' => $ongoingLot->lot_type,
                    'model_15' => $ongoingLot->model_15,
                    'work_type' => $ongoingLot->work_type,
                    'lipas_yn' => $ongoingLot->lipas_yn,
                    'no_rl_enabled' => $ongoingLot->no_rl_enabled ?? false,
                    'no_rl_minutes' => $ongoingLot->no_rl_minutes ?? 60,
                    'equipment' => $this->extractEquipmentData($ongoingLot),
                ]
            ]);
        }

        // Then check if lot exists with Submitted status
        $submittedLot = Endtime::where('lot_id', $lotId)
            ->where('status', 'Submitted')
            ->first();

        if ($submittedLot) {
            // Lot exists but submitted - warn user to select WL/RW or RL/LY
            return response()->json([
                'success' => true,
                'existingEndtime' => true,
                'status' => 'Submitted',
                'message' => 'Lot already submitted. To create new entry, select WL/RW or RL/LY lot type.',
                'lot' => [
                    'lot_id' => $submittedLot->lot_id,
                    'lot_qty' => $submittedLot->lot_qty,
                    'lot_size' => $submittedLot->lot_size,
                    'model_15' => $submittedLot->model_15,
                    'work_type' => $submittedLot->work_type,
                    'lipas_yn' => $submittedLot->lipas_yn,
                ]
            ]);
        }

        // If not in endtime, query the updatewip table
        $lot = \DB::table('updatewip')
            ->where('lot_id', $lotId)
            ->first();

        if (!$lot) {
            return response()->json([
                'success' => false,
                'message' => 'Lot not found in database'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'existingEndtime' => false,
            'lot' => [
                'lot_id' => $lot->lot_id,
                'lot_qty' => $lot->lot_qty ?? 0,
                'lot_size' => $lot->lot_size ?? '10',
                'model_15' => $lot->model_15 ?? '',
                'work_type' => $lot->work_type ?? 'NORMAL',
                'lipas_yn' => $lot->lipas_yn ?? 'N',
            ]
        ]);
    }

    /**
     * Extract equipment data from endtime record
     */
    private function extractEquipmentData($endtime): array
    {
        $equipment = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $eqpField = 'eqp_' . $i;
            $ngField = 'ng_percent_' . $i;
            $startField = 'start_time_' . $i;
            
            if (!empty($endtime->$eqpField)) {
                $equipment[] = [
                    'eqp_no' => $endtime->$eqpField,
                    'eqp_no_display' => $endtime->$eqpField,
                    'oee_capa' => 0, // Will be looked up when displayed
                    'ng_percent' => $endtime->$ngField ?? 0,
                    'start_time' => $endtime->$startField ? date('Y-m-d\TH:i', strtotime($endtime->$startField)) : '',
                ];
            }
        }
        
        // If no equipment, return at least one empty row
        if (empty($equipment)) {
            $equipment[] = [
                'eqp_no' => '',
                'eqp_no_display' => '',
                'oee_capa' => 0,
                'ng_percent' => 0,
                'start_time' => '',
            ];
        }
        
        return $equipment;
    }

    /**
     * Search equipment by partial number
     */
    public function searchEquipment(Request $request)
    {
        $search = $request->input('search');
        
        if (!$search || strlen($search) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Search term must be at least 2 characters'
            ], 400);
        }

        // Search for equipment numbers containing the search term
        $equipment = Equipment::where('eqp_no', 'like', '%' . $search . '%')
            ->where('eqp_status', 'OPERATIONAL')
            ->select('eqp_no', 'oee_capa', 'eqp_line', 'eqp_area', 'size', 'alloc_type')
            ->orderBy('eqp_no')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'equipment' => $equipment
        ]);
    }

    /**
     * Lookup equipment information from equipment table
     */
    public function lookupEquipment(Request $request)
    {
        $eqpNo = $request->input('eqp_no');
        
        if (!$eqpNo) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment number is required'
            ], 400);
        }

        // Query the equipment table by exact match
        $equipment = Equipment::where('eqp_no', $eqpNo)->first();

        if (!$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'equipment' => [
                'eqp_no' => $equipment->eqp_no,
                'oee_capa' => $equipment->oee_capa ?? 0,
                'eqp_line' => $equipment->eqp_line ?? '',
                'eqp_area' => $equipment->eqp_area ?? '',
                'size' => $equipment->size ?? '',
                'alloc_type' => $equipment->alloc_type ?? '',
                'eqp_status' => $equipment->eqp_status ?? '',
            ]
        ]);
    }

    /**
     * Search employee by partial employee number or name
     */
    public function searchEmployee(Request $request)
    {
        try {
            $search = $request->input('search');
            
            if (!$search || strlen($search) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search term must be at least 2 characters'
                ], 400);
            }

            // Search for employees by emp_no or emp_name containing the search term
            $employees = User::where('emp_no', 'like', '%' . $search . '%')
                ->orWhere('emp_name', 'like', '%' . $search . '%')
                ->select('emp_no as employee_id', 'emp_name as employee_name')
                ->orderBy('emp_no')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'employees' => $employees
            ]);
        } catch (\Exception $e) {
            \Log::error('Employee search error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error searching employees: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lookup employee information from users table
     */
    public function lookupEmployee(Request $request)
    {
        try {
            $employeeId = $request->input('employee_id');
            
            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee ID is required'
                ], 400);
            }

            // Query the users table by emp_no
            $employee = User::where('emp_no', $employeeId)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'employee' => [
                    'employee_id' => $employee->emp_no,
                    'employee_name' => $employee->emp_name,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Employee lookup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error looking up employee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new lot entry
     */
    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'lot_id' => 'required|string|max:15',
            'lot_qty' => 'required|integer|min:1',
            'lot_size' => 'required|string|in:03,05,10,21,31,32',
            'lot_type' => 'required|string|in:MAIN,WL/RW,RL/LY',
            'model_15' => 'nullable|string|max:15',
            'lipas_yn' => 'required|string|in:Y,N',
            'work_type' => 'required|string|in:NORMAL,PROCESS RW,WH REWORK,OI REWORK',
            'no_rl_enabled' => 'boolean',
            'no_rl_minutes' => 'nullable|integer|min:1|max:999',
            'employee_id' => 'required|string|max:10',
            'equipment.*.eqp_no' => 'required|string',
            'equipment.*.ng_percent' => 'nullable|numeric|min:0|max:100',
            'equipment.*.start_time' => 'required|date',
        ], [
            'lot_qty.required' => 'The lot qty field should not be empty!',
            'lot_qty.min' => 'The lot qty field should not be empty!',
            'employee_id.required' => 'The employee ID field is required!',
        ]);

        // Check for duplicate lot_id if lot_type is MAIN
        if ($validated['lot_type'] === 'MAIN') {
            $existingLot = Endtime::where('lot_id', $validated['lot_id'])->first();
            
            if ($existingLot) {
                return back()->withErrors([
                    'lot_id' => 'This lot ID already exists with MAIN type. Cannot create duplicate MAIN entry.'
                ])->withInput();
            }
        }

        // If lot_type is WL/RW or RL/LY, check if there's an existing lot with status Submitted
        if (in_array($validated['lot_type'], ['WL/RW', 'RL/LY'])) {
            $existingLot = Endtime::where('lot_id', $validated['lot_id'])
                ->where('status', 'Ongoing')
                ->first();
            
            if ($existingLot) {
                return back()->withErrors([
                    'lot_id' => 'This lot ID already has an Ongoing entry. Please submit it first before creating a rework entry.'
                ])->withInput();
            }
        }

        // Extract equipment data
        $equipmentData = [];
        if (isset($validated['equipment'])) {
            foreach ($validated['equipment'] as $index => $equipment) {
                if (!empty($equipment['eqp_no'])) {
                    $equipmentData[] = $equipment;
                }
            }
        }

        // Calculate estimated endtime based on equipment and lot data
        $estEndtime = $this->calculateEstimatedEndtime(
            $validated['lot_qty'],
            $equipmentData,
            $validated['no_rl_enabled'] ?? false,
            $validated['no_rl_minutes'] ?? 60
        );

        // Determine equipment line and area from first equipment by looking up in equipment table
        $eqpLine = 'A'; // Default
        $eqpArea = '1'; // Default
        
        if (!empty($equipmentData) && !empty($equipmentData[0]['eqp_no'])) {
            $eqpNo = $equipmentData[0]['eqp_no'];
            
            // Lookup equipment in database to get line and area
            $equipment = Equipment::where('eqp_no', $eqpNo)->first();
            
            if ($equipment) {
                $eqpLine = $equipment->eqp_line ?? 'A';
                $eqpArea = $equipment->eqp_area ?? '1';
            }
        }

        // Lookup employee name from employee_id
        $employee = User::where('emp_no', $validated['employee_id'])->first();
        $employeeName = $employee ? $employee->emp_name : $validated['employee_id'];

        // Prepare lot data for insertion
        $lotData = [
            'lot_id' => $validated['lot_id'],
            'lot_qty' => $validated['lot_qty'],
            'lot_size' => $validated['lot_size'],
            'lot_type' => $validated['lot_type'],
            'model_15' => $validated['model_15'] ?? '',
            'lipas_yn' => $validated['lipas_yn'],
            'work_type' => $validated['work_type'],
            'est_endtime' => $estEndtime,
            'eqp_line' => $eqpLine,
            'eqp_area' => $eqpArea,
            'status' => 'Ongoing',
            'employee_id' => $validated['employee_id'],
            'modified_by' => $employeeName,
        ];

        // Add equipment assignments (up to 10)
        for ($i = 0; $i < 10; $i++) {
            if (isset($equipmentData[$i])) {
                $lotData['eqp_' . ($i + 1)] = $equipmentData[$i]['eqp_no'];
                $lotData['ng_percent_' . ($i + 1)] = $equipmentData[$i]['ng_percent'] ?? 0;
                $lotData['start_time_' . ($i + 1)] = $equipmentData[$i]['start_time'];
            } else {
                $lotData['eqp_' . ($i + 1)] = null;
                $lotData['ng_percent_' . ($i + 1)] = 0;
                $lotData['start_time_' . ($i + 1)] = null;
            }
        }

        // Store NO RL data if enabled
        if ($validated['no_rl_enabled'] ?? false) {
            $lotData['no_rl_enabled'] = 1;
            $lotData['no_rl_minutes'] = $validated['no_rl_minutes'] ?? 60;
        } else {
            $lotData['no_rl_enabled'] = 0;
            $lotData['no_rl_minutes'] = 0;
        }

        // Create the lot entry
        Endtime::create($lotData);

        // Update equipment table with ongoing_lot
        $this->updateEquipmentOngoingLot($equipmentData, $validated['lot_id']);

        return redirect()->route('endtime_add')->with('success', 'Lot entry created successfully!');
    }

    /**
     * Update an existing lot entry
     */
    public function update(Request $request, $id)
    {
        $endtime = Endtime::findOrFail($id);

        // Validate the request
        $validated = $request->validate([
            'lot_id' => 'required|string|max:15',
            'lot_qty' => 'required|integer|min:1',
            'lot_size' => 'required|string|in:03,05,10,21,31,32',
            'lot_type' => 'required|string|in:MAIN,WL/RW,RL/LY',
            'model_15' => 'nullable|string|max:15',
            'lipas_yn' => 'required|string|in:Y,N',
            'work_type' => 'required|string|in:NORMAL,PROCESS RW,WH REWORK,OI REWORK',
            'no_rl_enabled' => 'boolean',
            'no_rl_minutes' => 'nullable|integer|min:1|max:999',
            'employee_id' => 'required|string|max:10',
            'equipment.*.eqp_no' => 'required|string',
            'equipment.*.ng_percent' => 'nullable|numeric|min:0|max:100',
            'equipment.*.start_time' => 'required|date',
        ], [
            'lot_qty.required' => 'The lot qty field should not be empty!',
            'lot_qty.min' => 'The lot qty field should not be empty!',
            'employee_id.required' => 'The employee ID field is required!',
        ]);

        // Extract equipment data
        $equipmentData = [];
        if (isset($validated['equipment'])) {
            foreach ($validated['equipment'] as $index => $equipment) {
                if (!empty($equipment['eqp_no'])) {
                    $equipmentData[] = $equipment;
                }
            }
        }

        // Calculate estimated endtime based on equipment and lot data
        $estEndtime = $this->calculateEstimatedEndtime(
            $validated['lot_qty'],
            $equipmentData,
            $validated['no_rl_enabled'] ?? false,
            $validated['no_rl_minutes'] ?? 60
        );

        // Determine equipment line and area from first equipment by looking up in equipment table
        $eqpLine = 'A'; // Default
        $eqpArea = '1'; // Default
        
        if (!empty($equipmentData) && !empty($equipmentData[0]['eqp_no'])) {
            $eqpNo = $equipmentData[0]['eqp_no'];
            
            // Lookup equipment in database to get line and area
            $equipment = Equipment::where('eqp_no', $eqpNo)->first();
            
            if ($equipment) {
                $eqpLine = $equipment->eqp_line ?? 'A';
                $eqpArea = $equipment->eqp_area ?? '1';
            }
        }

        // Lookup employee name from employee_id
        $employee = User::where('emp_no', $validated['employee_id'])->first();
        $employeeName = $employee ? $employee->emp_name : $validated['employee_id'];

        // Prepare lot data for update
        $lotData = [
            'lot_id' => $validated['lot_id'],
            'lot_qty' => $validated['lot_qty'],
            'lot_size' => $validated['lot_size'],
            'lot_type' => $validated['lot_type'],
            'model_15' => $validated['model_15'] ?? '',
            'lipas_yn' => $validated['lipas_yn'],
            'work_type' => $validated['work_type'],
            'est_endtime' => $estEndtime,
            'eqp_line' => $eqpLine,
            'eqp_area' => $eqpArea,
            'employee_id' => $validated['employee_id'],
            'modified_by' => $employeeName,
        ];

        // Add equipment assignments (up to 10)
        for ($i = 0; $i < 10; $i++) {
            if (isset($equipmentData[$i])) {
                $lotData['eqp_' . ($i + 1)] = $equipmentData[$i]['eqp_no'];
                $lotData['ng_percent_' . ($i + 1)] = $equipmentData[$i]['ng_percent'] ?? 0;
                $lotData['start_time_' . ($i + 1)] = $equipmentData[$i]['start_time'];
            } else {
                $lotData['eqp_' . ($i + 1)] = null;
                $lotData['ng_percent_' . ($i + 1)] = 0;
                $lotData['start_time_' . ($i + 1)] = null;
            }
        }

        // Store NO RL data if enabled
        if ($validated['no_rl_enabled'] ?? false) {
            $lotData['no_rl_enabled'] = 1;
            $lotData['no_rl_minutes'] = $validated['no_rl_minutes'] ?? 60;
        } else {
            $lotData['no_rl_enabled'] = 0;
            $lotData['no_rl_minutes'] = 0;
        }

        // Update the lot entry
        $endtime->update($lotData);

        // Update equipment table with ongoing_lot
        $this->updateEquipmentOngoingLot($equipmentData, $validated['lot_id']);

        return redirect()->route('endtime_add')->with('success', 'Lot entry updated successfully!');
    }

    /**
     * Update equipment table with ongoing lot
     */
    private function updateEquipmentOngoingLot(array $equipmentData, string $lotId): void
    {
        foreach ($equipmentData as $equipment) {
            if (!empty($equipment['eqp_no'])) {
                Equipment::where('eqp_no', $equipment['eqp_no'])
                    ->update(['ongoing_lot' => $lotId]);
            }
        }
    }

    /**
     * Calculate estimated endtime based on equipment capacity and lot quantity
     * Uses weighted capacity calculation to account for machines starting at different times
     */
    private function calculateEstimatedEndtime(
        int $lotQty,
        array $equipmentData,
        bool $noRlEnabled = false,
        int $noRlMinutes = 60
    ): string {
        if (empty($equipmentData)) {
            // Default to current time + 8 hours if no equipment
            return now()->addHours(8)->format('Y-m-d H:i:s');
        }

        // Find the latest start time and prepare equipment data with capacities
        $latestStartTime = null;
        $equipmentWithCapacity = [];

        foreach ($equipmentData as $equipment) {
            $startTime = \Carbon\Carbon::parse($equipment['start_time']);
            
            if ($latestStartTime === null || $startTime->gt($latestStartTime)) {
                $latestStartTime = $startTime;
            }

            // Get equipment capacity from database
            $eqp = Equipment::where('eqp_no', $equipment['eqp_no'])->first();
            $dailyCapacity = $eqp ? $eqp->oee_capa : 100000; // Default 100k if not found
            
            // Adjust capacity based on NG percentage
            $ngPercent = $equipment['ng_percent'] ?? 0;
            $adjustedCapacity = $dailyCapacity * (1 - ($ngPercent / 100));
            
            // Convert to pieces per minute
            $capacityPerMinute = $adjustedCapacity / (24 * 60);
            
            $equipmentWithCapacity[] = [
                'startTime' => $startTime,
                'capacityPerMinute' => $capacityPerMinute,
            ];
        }

        if ($latestStartTime === null) {
            return now()->addHours(8)->format('Y-m-d H:i:s');
        }

        // Calculate weighted capacity
        // Each machine contributes based on how long it runs relative to the latest start time
        // Formula: qty = sum(capacity_i * (T + time_advantage_i))
        // Solving for T: T = (qty - sum(capacity_i * time_advantage_i)) / sum(capacity_i)
        
        $weightedCapacitySum = 0;
        $totalCapacityPerMinute = 0;
        
        foreach ($equipmentWithCapacity as $eq) {
            // Calculate time advantage in minutes (how much earlier this machine started)
            // Positive value means this machine started before the latest start time
            $timeAdvantageMinutes = $eq['startTime']->diffInMinutes($latestStartTime, false);
            
            // Add to weighted sum
            $weightedCapacitySum += $eq['capacityPerMinute'] * $timeAdvantageMinutes;
            $totalCapacityPerMinute += $eq['capacityPerMinute'];
        }

        // Calculate processing time from latest start time
        if ($totalCapacityPerMinute > 0) {
            $processingMinutesFromLatestStart = ($lotQty - $weightedCapacitySum) / $totalCapacityPerMinute;
        } else {
            $processingMinutesFromLatestStart = 480; // Default 8 hours if capacity is 0
        }

        // Apply NO RL adjustment if enabled
        if ($noRlEnabled) {
            $processingMinutesFromLatestStart -= $noRlMinutes;
            if ($processingMinutesFromLatestStart < 0) {
                $processingMinutesFromLatestStart = 0;
            }
        }

        // Calculate estimated endtime from the latest start time
        $estimatedEndtime = $latestStartTime->copy()->addMinutes($processingMinutesFromLatestStart);

        return $estimatedEndtime->format('Y-m-d H:i:s');
    }

    /**
     * Delete a lot entry
     */
    public function destroy($id)
    {
        $lot = Endtime::findOrFail($id);
        $lot->delete();

        return redirect()->back()->with('success', 'Lot entry deleted successfully!');
    }

    /**
     * Lot Entry page - displays all lots with filters
     */
    public function lotEntry(Request $request)
    {
        $search = $request->input('search', '');
        $line = $request->input('line', 'ALL');
        $area = $request->input('area', 'ALL');
        $workType = $request->input('worktype', 'ALL');
        $status = $request->input('status', 'Ongoing'); // Default to Ongoing
        
        // Determine default shift, cutoff, and date based on current time
        $currentTime = now();
        $hour = $currentTime->hour;
        
        if ($hour >= 7 && $hour < 12) {
            $defaultShift = 'DAY';
            $defaultCutoff = '1ST';
            $defaultDate = $currentTime->format('Y-m-d');
        } elseif ($hour >= 12 && $hour < 16) {
            $defaultShift = 'DAY';
            $defaultCutoff = '2ND';
            $defaultDate = $currentTime->format('Y-m-d');
        } elseif ($hour >= 16 && $hour < 19) {
            $defaultShift = 'DAY';
            $defaultCutoff = '3RD';
            $defaultDate = $currentTime->format('Y-m-d');
        } elseif ($hour >= 19 && $hour < 24) {
            $defaultShift = 'NIGHT';
            $defaultCutoff = '1ST';
            $defaultDate = $currentTime->format('Y-m-d');
        } elseif ($hour >= 0 && $hour < 4) {
            $defaultShift = 'NIGHT';
            $defaultCutoff = '2ND';
            $defaultDate = $currentTime->subDay()->format('Y-m-d'); // Previous day for NIGHT 2ND
        } else { // 4-6:59
            $defaultShift = 'NIGHT';
            $defaultCutoff = '3RD';
            $defaultDate = $currentTime->subDay()->format('Y-m-d'); // Previous day for NIGHT 3RD
        }
        
        $date = $request->input('date', $defaultDate);
        $shift = $request->input('shift', $defaultShift);
        $cutoff = $request->input('cutoff', $defaultCutoff);

        // If search is active, search across all data (ignore other filters)
        if ($search) {
            $query = Endtime::query()
                ->select('id', 'lot_id', 'model_15', 'lot_qty', 'lot_size', 'lipas_yn', 'est_endtime', 'actual_submitted_at', 'work_type', 'lot_type', 'eqp_line', 'eqp_area', 'eqp_1', 'eqp_2', 'eqp_3', 'eqp_4', 'eqp_5', 'eqp_6', 'eqp_7', 'eqp_8', 'eqp_9', 'eqp_10', 'status', 'created_at')
                ->where('lot_id', 'like', '%' . $search . '%');
            
            // Apply area filter even during search
            if ($area && $area !== 'ALL') {
                $query->where('eqp_area', $area);
            }
            
            $lots = $query->orderBy('created_at', 'desc')
                ->limit(500)
                ->get();
        } elseif ($status === 'ALL') {
            // When status is ALL, query ongoing and submitted separately with different date columns
            // Then combine the results
            
            // Helper function to apply date/shift/cutoff filters
            $applyDateFilters = function($query, $dateColumn) use ($date, $shift, $cutoff) {
                if ($shift === 'NIGHT' && $cutoff === '2ND') {
                    $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
                    $query->where(function($q) use ($date, $nextDay, $dateColumn) {
                        $q->where(function($subQ) use ($date, $dateColumn) {
                            $subQ->whereDate($dateColumn, $date)
                                 ->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                        })->orWhere(function($subQ) use ($nextDay, $dateColumn) {
                            $subQ->whereDate($dateColumn, $nextDay)
                                 ->whereRaw("HOUR($dateColumn) >= 0 AND HOUR($dateColumn) < 4");
                        });
                    });
                } elseif ($shift === 'NIGHT' && $cutoff === '3RD') {
                    $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
                    $query->whereDate($dateColumn, $nextDay)
                          ->whereRaw("HOUR($dateColumn) >= 4 AND HOUR($dateColumn) < 7");
                } else {
                    $query->whereDate($dateColumn, $date);
                    
                    if ($shift !== 'ALL' && $cutoff !== 'ALL') {
                        if ($shift === 'DAY' && $cutoff === '1ST') {
                            $query->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 12");
                        } elseif ($shift === 'DAY' && $cutoff === '2ND') {
                            $query->whereRaw("HOUR($dateColumn) >= 12 AND HOUR($dateColumn) < 16");
                        } elseif ($shift === 'DAY' && $cutoff === '3RD') {
                            $query->whereRaw("HOUR($dateColumn) >= 16 AND HOUR($dateColumn) < 19");
                        } elseif ($shift === 'NIGHT' && $cutoff === '1ST') {
                            $query->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                        }
                    } elseif ($shift !== 'ALL') {
                        if ($shift === 'DAY') {
                            $query->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 19");
                        } elseif ($shift === 'NIGHT') {
                            $query->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                        }
                    } elseif ($cutoff !== 'ALL') {
                        if ($cutoff === '1ST') {
                            $query->whereRaw("(HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 12) OR (HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24)");
                        } elseif ($cutoff === '2ND') {
                            $query->whereRaw("HOUR($dateColumn) >= 12 AND HOUR($dateColumn) < 16");
                        } elseif ($cutoff === '3RD') {
                            $query->whereRaw("HOUR($dateColumn) >= 16 AND HOUR($dateColumn) < 19");
                        }
                    }
                }
            };
            
            // Query ongoing lots (use est_endtime)
            $ongoingQuery = Endtime::query()
                ->select('id', 'lot_id', 'model_15', 'lot_qty', 'lot_size', 'lipas_yn', 'est_endtime', 'actual_submitted_at', 'work_type', 'lot_type', 'eqp_line', 'eqp_area', 'eqp_1', 'eqp_2', 'eqp_3', 'eqp_4', 'eqp_5', 'eqp_6', 'eqp_7', 'eqp_8', 'eqp_9', 'eqp_10', 'status', 'created_at')
                ->where('status', 'Ongoing');
            
            $applyDateFilters($ongoingQuery, 'est_endtime');
            
            if ($line && $line !== 'ALL') {
                $ongoingQuery->where('eqp_line', $line);
            }
            if ($area && $area !== 'ALL') {
                $ongoingQuery->where('eqp_area', $area);
            }
            if ($workType && $workType !== 'ALL') {
                $ongoingQuery->where('work_type', $workType);
            }
            
            // Query submitted lots (use actual_submitted_at)
            $submittedQuery = Endtime::query()
                ->select('id', 'lot_id', 'model_15', 'lot_qty', 'lot_size', 'lipas_yn', 'est_endtime', 'actual_submitted_at', 'work_type', 'lot_type', 'eqp_line', 'eqp_area', 'eqp_1', 'eqp_2', 'eqp_3', 'eqp_4', 'eqp_5', 'eqp_6', 'eqp_7', 'eqp_8', 'eqp_9', 'eqp_10', 'status', 'created_at')
                ->where('status', 'Submitted');
            
            $applyDateFilters($submittedQuery, 'actual_submitted_at');
            
            if ($line && $line !== 'ALL') {
                $submittedQuery->where('eqp_line', $line);
            }
            if ($area && $area !== 'ALL') {
                $submittedQuery->where('eqp_area', $area);
            }
            if ($workType && $workType !== 'ALL') {
                $submittedQuery->where('work_type', $workType);
            }
            
            // Combine results and sort by created_at
            $ongoingLots = $ongoingQuery->get();
            $submittedLots = $submittedQuery->get();
            $lots = $ongoingLots->merge($submittedLots)->sortByDesc('created_at')->take(500)->values();
        } else {
            // Single status filter (Ongoing or Submitted)
            $dateColumn = ($status === 'Submitted') ? 'actual_submitted_at' : 'est_endtime';
            
            $query = Endtime::query()
                ->select('id', 'lot_id', 'model_15', 'lot_qty', 'lot_size', 'lipas_yn', 'est_endtime', 'actual_submitted_at', 'work_type', 'lot_type', 'eqp_line', 'eqp_area', 'eqp_1', 'eqp_2', 'eqp_3', 'eqp_4', 'eqp_5', 'eqp_6', 'eqp_7', 'eqp_8', 'eqp_9', 'eqp_10', 'status', 'created_at')
                ->where('status', $status)
                ->orderBy('created_at', 'desc');
            
            // Apply date/shift/cutoff filters
            if ($shift === 'NIGHT' && $cutoff === '2ND') {
                $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
                $query->where(function($q) use ($date, $nextDay, $dateColumn) {
                    $q->where(function($subQ) use ($date, $dateColumn) {
                        $subQ->whereDate($dateColumn, $date)
                             ->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                    })->orWhere(function($subQ) use ($nextDay, $dateColumn) {
                        $subQ->whereDate($dateColumn, $nextDay)
                             ->whereRaw("HOUR($dateColumn) >= 0 AND HOUR($dateColumn) < 4");
                    });
                });
            } elseif ($shift === 'NIGHT' && $cutoff === '3RD') {
                $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
                $query->whereDate($dateColumn, $nextDay)
                      ->whereRaw("HOUR($dateColumn) >= 4 AND HOUR($dateColumn) < 7");
            } else {
                $query->whereDate($dateColumn, $date);
                
                if ($shift !== 'ALL' && $cutoff !== 'ALL') {
                    if ($shift === 'DAY' && $cutoff === '1ST') {
                        $query->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 12");
                    } elseif ($shift === 'DAY' && $cutoff === '2ND') {
                        $query->whereRaw("HOUR($dateColumn) >= 12 AND HOUR($dateColumn) < 16");
                    } elseif ($shift === 'DAY' && $cutoff === '3RD') {
                        $query->whereRaw("HOUR($dateColumn) >= 16 AND HOUR($dateColumn) < 19");
                    } elseif ($shift === 'NIGHT' && $cutoff === '1ST') {
                        $query->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                    }
                } elseif ($shift !== 'ALL') {
                    if ($shift === 'DAY') {
                        $query->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 19");
                    } elseif ($shift === 'NIGHT') {
                        $query->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                    }
                } elseif ($cutoff !== 'ALL') {
                    if ($cutoff === '1ST') {
                        $query->whereRaw("(HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 12) OR (HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24)");
                    } elseif ($cutoff === '2ND') {
                        $query->whereRaw("HOUR($dateColumn) >= 12 AND HOUR($dateColumn) < 16");
                    } elseif ($cutoff === '3RD') {
                        $query->whereRaw("HOUR($dateColumn) >= 16 AND HOUR($dateColumn) < 19");
                    }
                }
            }
            
            // Apply line filter
            if ($line && $line !== 'ALL') {
                $query->where('eqp_line', $line);
            }
            
            // Apply area filter
            if ($area && $area !== 'ALL') {
                $query->where('eqp_area', $area);
            }
            
            // Apply work type filter
            if ($workType && $workType !== 'ALL') {
                $query->where('work_type', $workType);
            }
            
            $lots = $query->limit(500)->get();
        }

        // Calculate stats - if searching, show search results stats, otherwise show date-based stats
        if ($search) {
            $totalLots = $lots->count();
            $ongoingLots = $lots->where('status', 'Ongoing')->count();
            $submittedLots = $lots->where('status', 'Submitted')->count();
            $mainLots = $lots->where('lot_type', 'MAIN')->count();
            $wlReworkLots = $lots->where('lot_type', 'WL/RW')->count();
            $rlReworkLots = $lots->where('lot_type', 'RL/LY')->count();
        } else {
            // For stats, apply all filters (date, shift, cutoff, line, area, worktype)
            // Helper function to apply date/shift/cutoff filters
            $applyStatsFilters = function($query, $dateColumn) use ($date, $shift, $cutoff, $line, $area, $workType) {
                if ($shift === 'NIGHT' && $cutoff === '2ND') {
                    $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
                    $query->where(function($q) use ($date, $nextDay, $dateColumn) {
                        $q->where(function($subQ) use ($date, $dateColumn) {
                            $subQ->whereDate($dateColumn, $date)
                                 ->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                        })->orWhere(function($subQ) use ($nextDay, $dateColumn) {
                            $subQ->whereDate($dateColumn, $nextDay)
                                 ->whereRaw("HOUR($dateColumn) >= 0 AND HOUR($dateColumn) < 4");
                        });
                    });
                } elseif ($shift === 'NIGHT' && $cutoff === '3RD') {
                    $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
                    $query->whereDate($dateColumn, $nextDay)
                          ->whereRaw("HOUR($dateColumn) >= 4 AND HOUR($dateColumn) < 7");
                } else {
                    $query->whereDate($dateColumn, $date);
                    
                    if ($shift !== 'ALL' && $cutoff !== 'ALL') {
                        if ($shift === 'DAY' && $cutoff === '1ST') {
                            $query->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 12");
                        } elseif ($shift === 'DAY' && $cutoff === '2ND') {
                            $query->whereRaw("HOUR($dateColumn) >= 12 AND HOUR($dateColumn) < 16");
                        } elseif ($shift === 'DAY' && $cutoff === '3RD') {
                            $query->whereRaw("HOUR($dateColumn) >= 16 AND HOUR($dateColumn) < 19");
                        } elseif ($shift === 'NIGHT' && $cutoff === '1ST') {
                            $query->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                        }
                    } elseif ($shift !== 'ALL') {
                        if ($shift === 'DAY') {
                            $query->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 19");
                        } elseif ($shift === 'NIGHT') {
                            $query->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                        }
                    } elseif ($cutoff !== 'ALL') {
                        if ($cutoff === '1ST') {
                            $query->whereRaw("(HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 12) OR (HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24)");
                        } elseif ($cutoff === '2ND') {
                            $query->whereRaw("HOUR($dateColumn) >= 12 AND HOUR($dateColumn) < 16");
                        } elseif ($cutoff === '3RD') {
                            $query->whereRaw("HOUR($dateColumn) >= 16 AND HOUR($dateColumn) < 19");
                        }
                    }
                }
                
                // Apply line filter
                if ($line && $line !== 'ALL') {
                    $query->where('eqp_line', $line);
                }
                
                // Apply area filter
                if ($area && $area !== 'ALL') {
                    $query->where('eqp_area', $area);
                }
                
                // Apply work type filter
                if ($workType && $workType !== 'ALL') {
                    $query->where('work_type', $workType);
                }
            };
            
            // Calculate total lots (ongoing + submitted with their respective date columns)
            $ongoingQuery = Endtime::query()->where('status', 'Ongoing');
            $applyStatsFilters($ongoingQuery, 'est_endtime');
            $ongoingLots = $ongoingQuery->count();
            
            $submittedQuery = Endtime::query()->where('status', 'Submitted');
            $applyStatsFilters($submittedQuery, 'actual_submitted_at');
            $submittedLots = $submittedQuery->count();
            
            $totalLots = $ongoingLots + $submittedLots;
            
            // Calculate lot type stats (use est_endtime for all)
            $mainLotsQuery = Endtime::query()->where('lot_type', 'MAIN');
            $applyStatsFilters($mainLotsQuery, 'est_endtime');
            $mainLots = $mainLotsQuery->count();
            
            $wlReworkQuery = Endtime::query()->where('lot_type', 'WL/RW');
            $applyStatsFilters($wlReworkQuery, 'est_endtime');
            $wlReworkLots = $wlReworkQuery->count();
            
            $rlReworkQuery = Endtime::query()->where('lot_type', 'RL/LY');
            $applyStatsFilters($rlReworkQuery, 'est_endtime');
            $rlReworkLots = $rlReworkQuery->count();
        }

        // Get distinct area values from endtime table
        $areaOptions = Endtime::select('eqp_area')
            ->distinct()
            ->whereNotNull('eqp_area')
            ->where('eqp_area', '!=', '')
            ->orderBy('eqp_area')
            ->pluck('eqp_area')
            ->toArray();

        // Calculate badge stats (Target, Endtime Qty, Submitted Qty, Achievement)
        // Target: Get from Equipment based on shift, cutoff, line, area filters
        $targetQuery = Equipment::where('eqp_status', 'OPERATIONAL');
        if ($line && $line !== 'ALL') {
            $targetQuery->where('eqp_line', $line);
        }
        if ($area && $area !== 'ALL') {
            $targetQuery->where('eqp_area', $area);
        }
        if ($workType && $workType !== 'ALL') {
            $targetQuery->where('alloc_type', $workType);
        }
        $targetCapacity = $targetQuery->sum('oee_capa');
        
        // Apply shift/cutoff division logic
        $divisor = 1;
        if ($shift !== 'ALL' && $cutoff === 'ALL') {
            $divisor = 2;
        } elseif ($shift !== 'ALL' && $cutoff !== 'ALL') {
            $divisor = 6;
        } elseif ($shift === 'ALL' && $cutoff !== 'ALL') {
            $divisor = 3;
        }
        $targetQty = $targetCapacity / $divisor;

        // Helper function to apply badge filters (date, shift, cutoff, line, area, worktype)
        $applyBadgeFilters = function($query, $dateColumn) use ($date, $shift, $cutoff, $line, $area, $workType) {
            // Apply date/shift/cutoff filters
            if ($shift === 'NIGHT' && $cutoff === '2ND') {
                $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
                $query->where(function($q) use ($date, $nextDay, $dateColumn) {
                    $q->where(function($subQ) use ($date, $dateColumn) {
                        $subQ->whereDate($dateColumn, $date)
                             ->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                    })->orWhere(function($subQ) use ($nextDay, $dateColumn) {
                        $subQ->whereDate($dateColumn, $nextDay)
                             ->whereRaw("HOUR($dateColumn) >= 0 AND HOUR($dateColumn) < 4");
                    });
                });
            } elseif ($shift === 'NIGHT' && $cutoff === '3RD') {
                $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
                $query->whereDate($dateColumn, $nextDay)
                      ->whereRaw("HOUR($dateColumn) >= 4 AND HOUR($dateColumn) < 7");
            } else {
                $query->whereDate($dateColumn, $date);
                
                if ($shift !== 'ALL' && $cutoff !== 'ALL') {
                    if ($shift === 'DAY' && $cutoff === '1ST') {
                        $query->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 12");
                    } elseif ($shift === 'DAY' && $cutoff === '2ND') {
                        $query->whereRaw("HOUR($dateColumn) >= 12 AND HOUR($dateColumn) < 16");
                    } elseif ($shift === 'DAY' && $cutoff === '3RD') {
                        $query->whereRaw("HOUR($dateColumn) >= 16 AND HOUR($dateColumn) < 19");
                    } elseif ($shift === 'NIGHT' && $cutoff === '1ST') {
                        $query->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                    }
                } elseif ($shift !== 'ALL') {
                    if ($shift === 'DAY') {
                        $query->whereRaw("HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 19");
                    } elseif ($shift === 'NIGHT') {
                        $query->whereRaw("HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24");
                    }
                } elseif ($cutoff !== 'ALL') {
                    if ($cutoff === '1ST') {
                        $query->whereRaw("(HOUR($dateColumn) >= 7 AND HOUR($dateColumn) < 12) OR (HOUR($dateColumn) >= 19 AND HOUR($dateColumn) < 24)");
                    } elseif ($cutoff === '2ND') {
                        $query->whereRaw("HOUR($dateColumn) >= 12 AND HOUR($dateColumn) < 16");
                    } elseif ($cutoff === '3RD') {
                        $query->whereRaw("HOUR($dateColumn) >= 16 AND HOUR($dateColumn) < 19");
                    }
                }
            }
            
            // Apply line filter
            if ($line && $line !== 'ALL') {
                $query->where('eqp_line', $line);
            }
            
            // Apply area filter
            if ($area && $area !== 'ALL') {
                $query->where('eqp_area', $area);
            }
            
            // Apply work type filter
            if ($workType && $workType !== 'ALL') {
                $query->where('work_type', $workType);
            }
        };

        // Endtime Qty: Total endtime qty (ongoing + submitted)
        $ongoingEndtimeQuery = Endtime::query()->where('status', 'Ongoing');
        $applyBadgeFilters($ongoingEndtimeQuery, 'est_endtime');
        $ongoingEndtimeQty = $ongoingEndtimeQuery->sum('lot_qty');
        
        $submittedEndtimeQuery = Endtime::query()->where('status', 'Submitted');
        $applyBadgeFilters($submittedEndtimeQuery, 'actual_submitted_at');
        $submittedEndtimeQty = $submittedEndtimeQuery->sum('lot_qty');
        
        $totalEndtimeQty = $ongoingEndtimeQty + $submittedEndtimeQty;

        // Submitted Qty: Total submitted qty
        $submittedQtyQuery = Endtime::query()->where('status', 'Submitted');
        $applyBadgeFilters($submittedQtyQuery, 'actual_submitted_at');
        $totalSubmittedQty = $submittedQtyQuery->sum('lot_qty');

        // Achievement: Submitted Qty / Target Qty
        $achievement = $targetQty > 0 ? round(($totalSubmittedQty / $targetQty) * 100, 1) : 0;

        return Inertia::render('dashboards/subs/endtime-add', [
            'lots' => $lots,
            'filters' => [
                'search' => $search,
                'line' => $line,
                'area' => $area,
                'worktype' => $workType,
                'status' => $status,
                'date' => $date,
                'shift' => $shift,
                'cutoff' => $cutoff,
            ],
            'stats' => [
                'total' => $totalLots,
                'ongoing' => $ongoingLots,
                'submitted' => $submittedLots,
                'mainLots' => $mainLots,
                'wlRework' => $wlReworkLots,
                'rlRework' => $rlReworkLots,
            ],
            'badgeStats' => [
                'targetQty' => round($targetQty),
                'endtimeQty' => $totalEndtimeQty,
                'submittedQty' => $totalSubmittedQty,
                'achievement' => $achievement,
            ],
            'areaOptions' => $areaOptions,
        ]);
    }

    /**
     * Achievement Ranking page
     */
    public function ranking(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        $shift = $request->input('shift', 'ALL');
        $cutoff = $request->input('cutoff', 'ALL');
        $rankingType = $request->input('rankingType', 'line');

        // Mock data for demonstration - replace with actual database queries
        $topPerformers = [
            [
                'rank' => 1,
                'name' => 'Line A',
                'achievement' => 105.2,
                'target' => 45.5,
                'endtime' => 47.9,
                'submitted' => 47.9,
                'lotCount' => 125,
                'trend' => 'up',
            ],
            [
                'rank' => 2,
                'name' => 'Line C',
                'achievement' => 98.7,
                'target' => 42.3,
                'endtime' => 41.8,
                'submitted' => 41.8,
                'lotCount' => 118,
                'trend' => 'same',
            ],
            [
                'rank' => 3,
                'name' => 'Line B',
                'achievement' => 95.4,
                'target' => 38.9,
                'endtime' => 37.1,
                'submitted' => 37.1,
                'lotCount' => 102,
                'trend' => 'up',
            ],
        ];

        $bottomPerformers = [
            [
                'rank' => 1,
                'name' => 'Line K',
                'achievement' => 72.5,
                'target' => 21.8,
                'endtime' => 15.8,
                'submitted' => 15.8,
                'lotCount' => 47,
                'trend' => 'down',
            ],
            [
                'rank' => 2,
                'name' => 'Line J',
                'achievement' => 76.2,
                'target' => 24.1,
                'endtime' => 18.4,
                'submitted' => 18.4,
                'lotCount' => 54,
                'trend' => 'down',
            ],
            [
                'rank' => 3,
                'name' => 'Line I',
                'achievement' => 79.8,
                'target' => 26.5,
                'endtime' => 21.1,
                'submitted' => 21.1,
                'lotCount' => 61,
                'trend' => 'same',
            ],
        ];

        $lineRankings = [
            ['rank' => 1, 'line' => 'A', 'inCharge' => 'John Smith', 'machineCount' => 15, 'achievement' => 105.2, 'target' => 45.5, 'endtime' => 47.9, 'submitted' => 47.9, 'lotCount' => 125],
            ['rank' => 2, 'line' => 'C', 'inCharge' => 'Maria Garcia', 'machineCount' => 14, 'achievement' => 98.7, 'target' => 42.3, 'endtime' => 41.8, 'submitted' => 41.8, 'lotCount' => 118],
            ['rank' => 3, 'line' => 'B', 'inCharge' => 'David Chen', 'machineCount' => 13, 'achievement' => 95.4, 'target' => 38.9, 'endtime' => 37.1, 'submitted' => 37.1, 'lotCount' => 102],
            ['rank' => 4, 'line' => 'D', 'inCharge' => 'Sarah Johnson', 'machineCount' => 14, 'achievement' => 92.1, 'target' => 40.2, 'endtime' => 37.0, 'submitted' => 37.0, 'lotCount' => 95],
            ['rank' => 5, 'line' => 'E', 'inCharge' => 'Michael Brown', 'machineCount' => 12, 'achievement' => 89.5, 'target' => 35.8, 'endtime' => 32.0, 'submitted' => 32.0, 'lotCount' => 88],
            ['rank' => 6, 'line' => 'F', 'inCharge' => 'Lisa Anderson', 'machineCount' => 11, 'achievement' => 87.3, 'target' => 33.5, 'endtime' => 29.2, 'submitted' => 29.2, 'lotCount' => 82],
            ['rank' => 7, 'line' => 'G', 'inCharge' => 'Robert Wilson', 'machineCount' => 11, 'achievement' => 85.1, 'target' => 31.2, 'endtime' => 26.6, 'submitted' => 26.6, 'lotCount' => 75],
            ['rank' => 8, 'line' => 'H', 'inCharge' => 'Jennifer Lee', 'machineCount' => 10, 'achievement' => 82.4, 'target' => 28.9, 'endtime' => 23.8, 'submitted' => 23.8, 'lotCount' => 68],
            ['rank' => 9, 'line' => 'I', 'inCharge' => 'James Martinez', 'machineCount' => 9, 'achievement' => 79.8, 'target' => 26.5, 'endtime' => 21.1, 'submitted' => 21.1, 'lotCount' => 61],
            ['rank' => 10, 'line' => 'J', 'inCharge' => 'Patricia Taylor', 'machineCount' => 8, 'achievement' => 76.2, 'target' => 24.1, 'endtime' => 18.4, 'submitted' => 18.4, 'lotCount' => 54],
            ['rank' => 11, 'line' => 'K', 'inCharge' => 'Thomas White', 'machineCount' => 7, 'achievement' => 72.5, 'target' => 21.8, 'endtime' => 15.8, 'submitted' => 15.8, 'lotCount' => 47],
        ];

        $areaRankings = [
            ['rank' => 1, 'line' => 'A', 'area' => '1', 'inCharge' => 'John Smith', 'machineCount' => 8, 'achievement' => 108.3, 'target' => 22.5, 'endtime' => 24.4, 'submitted' => 24.4, 'lotCount' => 65],
            ['rank' => 2, 'line' => 'A', 'area' => '2', 'inCharge' => 'John Smith', 'machineCount' => 7, 'achievement' => 102.1, 'target' => 23.0, 'endtime' => 23.5, 'submitted' => 23.5, 'lotCount' => 60],
            ['rank' => 3, 'line' => 'C', 'area' => '1', 'inCharge' => 'Maria Garcia', 'machineCount' => 7, 'achievement' => 99.5, 'target' => 21.2, 'endtime' => 21.1, 'submitted' => 21.1, 'lotCount' => 59],
            ['rank' => 4, 'line' => 'C', 'area' => '2', 'inCharge' => 'Maria Garcia', 'machineCount' => 7, 'achievement' => 97.9, 'target' => 21.1, 'endtime' => 20.7, 'submitted' => 20.7, 'lotCount' => 59],
            ['rank' => 5, 'line' => 'B', 'area' => '1', 'inCharge' => 'David Chen', 'machineCount' => 7, 'achievement' => 96.8, 'target' => 19.5, 'endtime' => 18.9, 'submitted' => 18.9, 'lotCount' => 52],
            ['rank' => 6, 'line' => 'B', 'area' => '2', 'inCharge' => 'David Chen', 'machineCount' => 6, 'achievement' => 94.0, 'target' => 19.4, 'endtime' => 18.2, 'submitted' => 18.2, 'lotCount' => 50],
            ['rank' => 7, 'line' => 'D', 'area' => '1', 'inCharge' => 'Sarah Johnson', 'machineCount' => 7, 'achievement' => 93.2, 'target' => 20.1, 'endtime' => 18.7, 'submitted' => 18.7, 'lotCount' => 48],
            ['rank' => 8, 'line' => 'D', 'area' => '2', 'inCharge' => 'Sarah Johnson', 'machineCount' => 7, 'achievement' => 91.0, 'target' => 20.1, 'endtime' => 18.3, 'submitted' => 18.3, 'lotCount' => 47],
            ['rank' => 9, 'line' => 'E', 'area' => '1', 'inCharge' => 'Michael Brown', 'machineCount' => 6, 'achievement' => 89.5, 'target' => 18.0, 'endtime' => 16.1, 'submitted' => 16.1, 'lotCount' => 44],
            ['rank' => 10, 'line' => 'E', 'area' => '2', 'inCharge' => 'Michael Brown', 'machineCount' => 6, 'achievement' => 89.5, 'target' => 17.8, 'endtime' => 15.9, 'submitted' => 15.9, 'lotCount' => 44],
            ['rank' => 11, 'line' => 'F', 'area' => '1', 'inCharge' => 'Lisa Anderson', 'machineCount' => 6, 'achievement' => 87.8, 'target' => 16.8, 'endtime' => 14.7, 'submitted' => 14.7, 'lotCount' => 41],
            ['rank' => 12, 'line' => 'F', 'area' => '2', 'inCharge' => 'Lisa Anderson', 'machineCount' => 5, 'achievement' => 86.8, 'target' => 16.7, 'endtime' => 14.5, 'submitted' => 14.5, 'lotCount' => 41],
            ['rank' => 13, 'line' => 'G', 'area' => '1', 'inCharge' => 'Robert Wilson', 'machineCount' => 6, 'achievement' => 85.5, 'target' => 15.6, 'endtime' => 13.3, 'submitted' => 13.3, 'lotCount' => 38],
            ['rank' => 14, 'line' => 'G', 'area' => '2', 'inCharge' => 'Robert Wilson', 'machineCount' => 5, 'achievement' => 84.7, 'target' => 15.6, 'endtime' => 13.3, 'submitted' => 13.3, 'lotCount' => 37],
            ['rank' => 15, 'line' => 'H', 'area' => '1', 'inCharge' => 'Jennifer Lee', 'machineCount' => 5, 'achievement' => 82.9, 'target' => 14.5, 'endtime' => 12.0, 'submitted' => 12.0, 'lotCount' => 34],
            ['rank' => 16, 'line' => 'H', 'area' => '2', 'inCharge' => 'Jennifer Lee', 'machineCount' => 5, 'achievement' => 81.9, 'target' => 14.4, 'endtime' => 11.8, 'submitted' => 11.8, 'lotCount' => 34],
            ['rank' => 17, 'line' => 'I', 'area' => '1', 'inCharge' => 'James Martinez', 'machineCount' => 5, 'achievement' => 80.2, 'target' => 13.3, 'endtime' => 10.6, 'submitted' => 10.6, 'lotCount' => 31],
            ['rank' => 18, 'line' => 'I', 'area' => '2', 'inCharge' => 'James Martinez', 'machineCount' => 4, 'achievement' => 79.4, 'target' => 13.2, 'endtime' => 10.5, 'submitted' => 10.5, 'lotCount' => 30],
            ['rank' => 19, 'line' => 'J', 'area' => '1', 'inCharge' => 'Patricia Taylor', 'machineCount' => 4, 'achievement' => 76.8, 'target' => 12.1, 'endtime' => 9.3, 'submitted' => 9.3, 'lotCount' => 27],
            ['rank' => 20, 'line' => 'J', 'area' => '2', 'inCharge' => 'Patricia Taylor', 'machineCount' => 4, 'achievement' => 75.6, 'target' => 12.0, 'endtime' => 9.1, 'submitted' => 9.1, 'lotCount' => 27],
            ['rank' => 21, 'line' => 'K', 'area' => '1', 'inCharge' => 'Thomas White', 'machineCount' => 4, 'achievement' => 73.2, 'target' => 10.9, 'endtime' => 8.0, 'submitted' => 8.0, 'lotCount' => 24],
            ['rank' => 22, 'line' => 'K', 'area' => '2', 'inCharge' => 'Thomas White', 'machineCount' => 3, 'achievement' => 71.8, 'target' => 10.9, 'endtime' => 7.8, 'submitted' => 7.8, 'lotCount' => 23],
        ];

        return Inertia::render('dashboards/subs/endtime-ranking', [
            'filters' => [
                'date' => $date,
                'shift' => $shift,
                'cutoff' => $cutoff,
                'rankingType' => $rankingType,
            ],
            'topPerformers' => $topPerformers,
            'bottomPerformers' => $bottomPerformers,
            'lineRankings' => $lineRankings,
            'areaRankings' => $areaRankings,
        ]);
    }
}
