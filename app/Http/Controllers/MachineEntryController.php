<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Endtime;
use App\Models\User;
use Illuminate\Http\Request;

class MachineEntryController extends Controller
{
    /**
     * Display the machine entry form
     */
    public function index()
    {
        return view('machine-entry');
    }

    /**
     * Store a new lot entry from machine entry page
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
            'employee_id' => 'required|string|max:10',
            'equipment.*.eqp_no' => 'required|string',
            'equipment.*.ng_percent' => 'nullable|numeric|min:0|max:100',
            'equipment.*.start_time' => 'required|string',
        ], [
            'lot_qty.required' => 'The lot qty field should not be empty!',
            'lot_qty.min' => 'The lot qty field should not be empty!',
            'employee_id.required' => 'The employee ID field is required!',
        ]);

        // Check if lot exists with Ongoing status (for machine sharing)
        $ongoingLot = Endtime::where('lot_id', $validated['lot_id'])
            ->where('status', 'Ongoing')
            ->first();

        if ($ongoingLot) {
            // Machine sharing: Add equipment to existing ongoing lot
            return $this->addEquipmentToOngoingLot($ongoingLot, $equipmentData, $validated);
        }

        // Check for duplicate lot_id if lot_type is MAIN
        if ($validated['lot_type'] === 'MAIN') {
            $existingLot = Endtime::where('lot_id', $validated['lot_id'])->first();
            
            if ($existingLot) {
                return back()->withErrors([
                    'lot_id' => 'This lot ID already exists with MAIN type. Cannot create duplicate MAIN entry.'
                ])->withInput();
            }
        }

        // Extract equipment data
        $equipmentData = [];
        if (isset($validated['equipment'])) {
            foreach ($validated['equipment'] as $index => $equipment) {
                if (!empty($equipment['eqp_no'])) {
                    // Convert datetime format from "YYYY-MM-DD HH:MM" to "YYYY-MM-DD HH:MM:SS"
                    $startTime = $equipment['start_time'];
                    if (strlen($startTime) == 16) { // "YYYY-MM-DD HH:MM" format
                        $startTime .= ':00';
                    }
                    
                    $equipmentData[] = [
                        'eqp_no' => $equipment['eqp_no'],
                        'ng_percent' => $equipment['ng_percent'] ?? 0,
                        'start_time' => $startTime,
                    ];
                }
            }
        }

        // Calculate estimated endtime based on equipment and lot data
        $estEndtime = $this->calculateEstimatedEndtime(
            $validated['lot_qty'],
            $equipmentData
        );

        // Determine equipment line and area from first equipment
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
            'no_rl_enabled' => 0,
            'no_rl_minutes' => 0,
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

        // Create the lot entry
        Endtime::create($lotData);

        // Update equipment table with ongoing_lot and est_endtime
        $this->updateEquipmentOngoingLot($equipmentData, $validated['lot_id'], $estEndtime);

        return redirect()->route('machine_entry')->with('success', 'Lot entry created successfully! Lot ID: ' . $validated['lot_id']);
    }

    /**
     * Update equipment table with ongoing lot and estimated endtime
     */
    private function updateEquipmentOngoingLot(array $equipmentData, string $lotId, ?string $estEndtime = null): void
    {
        foreach ($equipmentData as $equipment) {
            if (!empty($equipment['eqp_no'])) {
                $updateData = ['ongoing_lot' => $lotId];
                
                // Add est_endtime if provided
                if ($estEndtime) {
                    $updateData['est_endtime'] = $estEndtime;
                }
                
                Equipment::where('eqp_no', $equipment['eqp_no'])
                    ->update($updateData);
            }
        }
    }

    /**
     * Add equipment to existing ongoing lot (machine sharing)
     */
    private function addEquipmentToOngoingLot(Endtime $ongoingLot, array $newEquipmentData, array $validated)
    {
        // Get existing equipment from the ongoing lot
        $existingEquipment = [];
        for ($i = 1; $i <= 10; $i++) {
            $eqpField = 'eqp_' . $i;
            $startTimeField = 'start_time_' . $i;
            $ngPercentField = 'ng_percent_' . $i;
            
            if (!empty($ongoingLot->$eqpField)) {
                $existingEquipment[] = [
                    'eqp_no' => $ongoingLot->$eqpField,
                    'start_time' => $ongoingLot->$startTimeField,
                    'ng_percent' => $ongoingLot->$ngPercentField ?? 0,
                ];
            }
        }

        // Check for duplicate equipment numbers
        foreach ($newEquipmentData as $newEqp) {
            foreach ($existingEquipment as $existingEqp) {
                if ($newEqp['eqp_no'] === $existingEqp['eqp_no']) {
                    return back()->withErrors([
                        'equipment' => 'Equipment ' . $newEqp['eqp_no'] . ' is already assigned to this lot.'
                    ])->withInput();
                }
            }
        }

        // Merge equipment arrays
        $allEquipment = array_merge($existingEquipment, $newEquipmentData);

        // Check if total equipment exceeds 10
        if (count($allEquipment) > 10) {
            return back()->withErrors([
                'equipment' => 'Cannot add more equipment. Maximum 10 machines per lot.'
            ])->withInput();
        }

        // Recalculate estimated endtime with all equipment
        $estEndtime = $this->calculateEstimatedEndtime(
            $ongoingLot->lot_qty,
            $allEquipment
        );

        // Update the ongoing lot with new equipment
        $updateData = ['est_endtime' => $estEndtime];
        
        for ($i = 0; $i < count($allEquipment); $i++) {
            $updateData['eqp_' . ($i + 1)] = $allEquipment[$i]['eqp_no'];
            $updateData['ng_percent_' . ($i + 1)] = $allEquipment[$i]['ng_percent'] ?? 0;
            $updateData['start_time_' . ($i + 1)] = $allEquipment[$i]['start_time'];
        }

        $ongoingLot->update($updateData);

        // Update equipment table with ongoing_lot and est_endtime
        $this->updateEquipmentOngoingLot($newEquipmentData, $validated['lot_id'], $estEndtime);

        return redirect()->route('machine_entry')->with('success', 'Equipment added to ongoing lot! Lot ID: ' . $validated['lot_id'] . ' - New endtime calculated.');
    }

    /**
     * Calculate estimated endtime based on equipment capacity and lot quantity
     */
    private function calculateEstimatedEndtime(
        int $lotQty,
        array $equipmentData
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
        $weightedCapacitySum = 0;
        $totalCapacityPerMinute = 0;
        
        foreach ($equipmentWithCapacity as $eq) {
            // Calculate time advantage in minutes
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

        // Calculate estimated endtime from the latest start time
        $estimatedEndtime = $latestStartTime->copy()->addMinutes($processingMinutesFromLatestStart);

        return $estimatedEndtime->format('Y-m-d H:i:s');
    }

    /**
     * Simple lookup endpoints for IE6 compatibility (no authentication required)
     */
    public function lookupLotSimple(Request $request)
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

        if ($ongoingLot) {
            // Get ongoing equipment list
            $ongoingEquipment = [];
            for ($i = 1; $i <= 10; $i++) {
                $eqpField = 'eqp_' . $i;
                $startTimeField = 'start_time_' . $i;
                $ngPercentField = 'ng_percent_' . $i;
                
                if (!empty($ongoingLot->$eqpField)) {
                    // Lookup equipment capacity
                    $equipment = Equipment::where('eqp_no', $ongoingLot->$eqpField)->first();
                    
                    $ongoingEquipment[] = [
                        'eqp_no' => $ongoingLot->$eqpField,
                        'start_time' => $ongoingLot->$startTimeField,
                        'ng_percent' => $ongoingLot->$ngPercentField ?? 0,
                        'oee_capa' => $equipment ? $equipment->oee_capa : 0,
                        'size' => $equipment ? $equipment->size : '',
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'ongoing' => true,
                'message' => 'Lot has ongoing entry. You can add more machines for sharing.',
                'lot' => [
                    'lot_id' => $ongoingLot->lot_id,
                    'lot_qty' => $ongoingLot->lot_qty,
                    'lot_size' => $ongoingLot->lot_size,
                    'model_15' => $ongoingLot->model_15,
                    'work_type' => $ongoingLot->work_type,
                    'lipas_yn' => $ongoingLot->lipas_yn,
                    'lot_type' => $ongoingLot->lot_type,
                    'est_endtime' => $ongoingLot->est_endtime,
                ],
                'ongoing_equipment' => $ongoingEquipment
            ]);
        }

        // Check if lot exists with Submitted status
        $submittedLot = Endtime::where('lot_id', $lotId)
            ->where('status', 'Submitted')
            ->first();

        if ($submittedLot) {
            return response()->json([
                'success' => true,
                'warning' => 'Lot already submitted. Select WL/RW or RL/LY to create rework entry.',
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

        // Query mes_data.wip_status
        $lot = \DB::table('mes_data.wip_status as w')
            ->leftJoin('mes_data.monthly_plan as mp', 'mp.chip_model', '=', 'w.model_id')
            ->where('w.lot_no', $lotId)
            ->select(
                'w.lot_no',
                'w.model_id as model_15',
                'w.current_qty as lot_qty',
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
                'mp.vi_lipas_yn as lipas_yn',
            )
            ->first();

        if (!$lot) {
            return response()->json([
                'success' => false,
                'message' => 'Lot not found in database'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'lot' => [
                'lot_id' => $lot->lot_no,
                'lot_qty' => $lot->lot_qty ?? 0,
                'lot_size' => $lot->lot_size ?? '10',
                'model_15' => $lot->model_15 ?? '',
                'work_type' => $lot->work_type ?? 'NORMAL',
                'lipas_yn' => $lot->lipas_yn ?? 'N',
            ]
        ]);
    }

    public function lookupEmployeeSimple(Request $request)
    {
        $employeeId = $request->input('employee_id');
        
        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'Employee ID is required'
            ], 400);
        }

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
    }

    public function lookupEquipmentSimple(Request $request)
    {
        $eqpNo = $request->input('eqp_no');
        
        if (!$eqpNo) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment number is required'
            ], 400);
        }

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
            ]
        ]);
    }
}
