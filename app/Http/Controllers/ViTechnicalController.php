<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ViTechnical;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ViTechnicalController extends Controller
{
    public function findByLot(Request $request): JsonResponse
    {
        $lotId = strtoupper(trim($request->input('lot_id', '')));
        if (!$lotId) {
            return response()->json(['success' => false, 'data' => null, 'error' => 'Lot ID required', 'meta' => null], 422);
        }

        $record = ViTechnical::where('lot_id', $lotId)->latest()->first();
        if (!$record) {
            return response()->json(['success' => false, 'data' => null, 'error' => 'Lot not found in VI Technical queue', 'meta' => null], 404);
        }

        // Only allow updating lots that are still pending or in-progress
        $completedStatuses = ['Proceed', 'Derive RL', 'Scrap LY', 'Rework'];
        if ($record->technical_result && in_array($record->technical_result, $completedStatuses, true)) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'error'   => "Lot {$lotId} is already completed with decision: {$record->technical_result}",
                'meta'    => null,
            ], 422);
        }

        // Enrich with qc_inspection data
        $inspection = DB::table('qc_inspection')
            ->where('lot_id', $lotId)
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data'    => array_merge($record->toArray(), [
                'analysis_result'   => $inspection?->analysis_result,
                'mainlot_result'    => $inspection?->mainlot_result,
                'rr_result'         => $inspection?->rr_result,
                'ly_result'         => $inspection?->ly_result,
                'inspection_result' => $inspection?->inspection_result,
                'defect_code'       => $record->defect_code ?? $inspection?->defect_code,
            ]),
            'error' => null,
            'meta'  => null,
        ]);
    }

    public function equipmentLookup(Request $request): JsonResponse
    {
        $eqpNo = strtoupper(trim($request->input('eqp_no', '')));
        if (!$eqpNo) {
            return response()->json(['success' => false, 'data' => null, 'error' => 'Equipment number required', 'meta' => null], 422);
        }

        $eqp = DB::table('equipment')
            ->where('eqp_no', $eqpNo)
            ->select('eqp_no', 'eqp_type', 'eqp_area', 'eqp_maker')
            ->first();

        if (!$eqp) {
            return response()->json(['success' => false, 'data' => null, 'error' => 'Equipment not found', 'meta' => null], 404);
        }

        return response()->json(['success' => true, 'data' => $eqp, 'error' => null, 'meta' => null]);
    }

    public function equipmentSearch(Request $request): JsonResponse
    {
        $q = strtoupper(trim($request->input('q', '')));
        if (!$q) {
            return response()->json([]);
        }

        $results = DB::table('equipment')
            ->where('eqp_no', 'like', "%{$q}%")
            ->select('eqp_no', 'eqp_type', 'eqp_area', 'eqp_maker')
            ->orderByRaw("CASE WHEN eqp_no = ? THEN 0 ELSE 1 END", [$q])
            ->orderBy('eqp_no')
            ->limit(10)
            ->get();

        return response()->json($results);
    }

    public function export(Request $request)
    {
        $query = ViTechnical::query();

        if ($request->filled('date_from')) {
            $query->whereDate('technical_start_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('technical_start_at', '<=', $request->date_to);
        }
        if ($request->filled('work_type')) {
            $query->where('work_type', $request->work_type);
        }
        if ($request->filled('lipas_yn')) {
            $query->where('lipas_yn', $request->lipas_yn);
        }

        $records = $query->orderByDesc('technical_start_at')->get();

        $columns = [
            'lot_id','model','lot_qty','lipas_yn','work_type',
            'inspection_times','inspection_spl','defect_code',
            'eqp_number','eqp_maker','technical_start_at',
            'technical_completed_at','technical_result',
            'total_tat','remarks','created_by','updated_by','created_at',
        ];

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="vi_technical_' . now()->format('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($records, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_map('strtoupper', $columns));
            foreach ($records as $row) {
                fputcsv($handle, array_map(fn($col) => $row->$col ?? '', $columns));
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function index(Request $request): JsonResponse
    {
        $query = ViTechnical::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('lot_id', 'like', "%{$s}%")
                  ->orWhere('model', 'like', "%{$s}%")
                  ->orWhere('defect_code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('date') && !$request->filled('search')) {
            $query->whereDate('technical_start_at', $request->date);
        }

        if ($request->filled('work_type')) {
            $query->where('work_type', $request->work_type);
        }

        if ($request->filled('lipas_yn')) {
            $query->where('lipas_yn', $request->lipas_yn);
        }

        if ($request->filled('shift')) {
            $shift = $request->shift;
            if ($shift === 'DAY') {
                $query->whereTime('technical_start_at', '>=', '07:00:00')
                      ->whereTime('technical_start_at', '<', '19:00:00');
            } elseif ($shift === 'NIGHT') {
                $query->where(function ($q) {
                    $q->whereTime('technical_start_at', '>=', '19:00:00')
                      ->orWhereTime('technical_start_at', '<', '07:00:00');
                });
            }
        }

        if ($request->filled('cutoff')) {
            $ranges = [
                '00:00~03:59' => ['00:00:00', '03:59:59'],
                '04:00~06:59' => ['04:00:00', '06:59:59'],
                '07:00~11:59' => ['07:00:00', '11:59:59'],
                '12:00~15:59' => ['12:00:00', '15:59:59'],
                '16:00~18:59' => ['16:00:00', '18:59:59'],
                '19:00~23:59' => ['19:00:00', '23:59:59'],
            ];
            if (isset($ranges[$request->cutoff])) {
                [$from, $to] = $ranges[$request->cutoff];
                $query->whereTime('technical_start_at', '>=', $from)
                      ->whereTime('technical_start_at', '<=', $to);
            }
        }

        $records = $query->orderByDesc('technical_start_at')->get();

        $today = now()->toDateString();
        $prevDayQuery = ViTechnical::where(function ($q) {
                $q->whereNull('technical_result')
                  ->orWhere('technical_result', 'In Progress');
            })
            ->whereDate('technical_start_at', '<', $today);

        if ($request->filled('work_type')) {
            $prevDayQuery->where('work_type', $request->work_type);
        }
        if ($request->filled('lipas_yn')) {
            $prevDayQuery->where('lipas_yn', $request->lipas_yn);
        }

        return response()->json([
            'success' => true,
            'data'    => $records,
            'error'   => null,
            'meta'    => [
                'prev_day_count' => $prevDayQuery->count(),
                'prev_day_qty'   => (int) $prevDayQuery->sum('lot_qty'),
            ],
        ]);
    }

    public function chartData(Request $request): JsonResponse
    {
        $base = DB::table('vi_technical');

        if ($request->filled('date')) {
            $base->whereDate('technical_start_at', $request->date);
        }
        if ($request->filled('work_type')) {
            $base->where('work_type', $request->work_type);
        }
        if ($request->filled('lipas_yn')) {
            $base->where('lipas_yn', $request->lipas_yn);
        }
        if ($request->filled('shift')) {
            $shift = $request->shift;
            if ($shift === 'DAY') {
                $base->whereTime('technical_start_at', '>=', '07:00:00')
                     ->whereTime('technical_start_at', '<', '19:00:00');
            } elseif ($shift === 'NIGHT') {
                $base->where(function ($q) {
                    $q->whereTime('technical_start_at', '>=', '19:00:00')
                      ->orWhereTime('technical_start_at', '<', '07:00:00');
                });
            }
        }
        if ($request->filled('cutoff')) {
            $ranges = [
                '00:00~03:59' => ['00:00:00', '03:59:59'],
                '04:00~06:59' => ['04:00:00', '06:59:59'],
                '07:00~11:59' => ['07:00:00', '11:59:59'],
                '12:00~15:59' => ['12:00:00', '15:59:59'],
                '16:00~18:59' => ['16:00:00', '18:59:59'],
                '19:00~23:59' => ['19:00:00', '23:59:59'],
            ];
            if (isset($ranges[$request->cutoff])) {
                [$from, $to] = $ranges[$request->cutoff];
                $base->whereTime('technical_start_at', '>=', $from)
                     ->whereTime('technical_start_at', '<=', $to);
            }
        }

        $statusNames = ['Pending', 'In Progress', 'Completed'];
        $sizeCodes   = ['03', '05', '10', '21', '31', '32'];

        // Pie
        $pieRows = (clone $base)
            ->select('technical_result', DB::raw('COUNT(*) as cnt'))
            ->groupBy('technical_result')
            ->get();

        $pieTotals = ['Pending' => 0, 'In Progress' => 0, 'Completed' => 0];
        foreach ($pieRows as $row) {
            if ($row->technical_result === 'In Progress') {
                $pieTotals['In Progress'] += (int) $row->cnt;
            } elseif ($row->technical_result) {
                $pieTotals['Completed'] += (int) $row->cnt;
            } else {
                $pieTotals['Pending'] += (int) $row->cnt;
            }
        }

        // Bar: per size stacked by status
        $barRows = (clone $base)
            ->select(
                DB::raw("SUBSTRING(model, 3, 2) as size"),
                'technical_result',
                DB::raw('COUNT(*) as cnt'),
            )
            ->whereRaw("SUBSTRING(model, 3, 2) IN ('03','05','10','21','31','32')")
            ->groupBy(DB::raw("SUBSTRING(model, 3, 2)"), 'technical_result')
            ->get();

        $barLookup = [];
        foreach ($barRows as $row) {
            if ($row->technical_result === 'In Progress') {
                $status = 'In Progress';
            } elseif ($row->technical_result) {
                $status = 'Completed';
            } else {
                $status = 'Pending';
            }
            $barLookup[$status][$row->size] = ($barLookup[$status][$row->size] ?? 0) + (int) $row->cnt;
        }
        $barSeries = [];
        foreach ($statusNames as $name) {
            $data = [];
            foreach ($sizeCodes as $s) {
                $data[] = $barLookup[$name][$s] ?? 0;
            }
            $barSeries[] = ['name' => $name, 'data' => $data];
        }

        // Column: per defect code stacked by size
        $allRows = (clone $base)
            ->select(DB::raw("SUBSTRING(model, 3, 2) as size"), 'defect_code')
            ->whereRaw("SUBSTRING(model, 3, 2) IN ('03','05','10','21','31','32')")
            ->whereNotNull('defect_code')
            ->get();

        $colLookup = [];
        foreach ($allRows as $row) {
            $codes = array_map('trim', explode(',', strtoupper((string) $row->defect_code)));
            foreach ($codes as $code) {
                if ($code === '') continue;
                $colLookup[$row->size][$code] = ($colLookup[$row->size][$code] ?? 0) + 1;
            }
        }

        $defectCodes = collect($colLookup)
            ->flatMap(fn($codes) => array_keys($codes))
            ->unique()->sort()->values()->all();

        if (empty($defectCodes)) {
            $colData = ['categories' => [], 'series' => []];
        } else {
            $colSeries = [];
            foreach ($sizeCodes as $size) {
                $data = [];
                foreach ($defectCodes as $code) {
                    $data[] = $colLookup[$size][$code] ?? 0;
                }
                $colSeries[] = ['name' => $size, 'data' => $data];
            }
            $colData = ['categories' => $defectCodes, 'series' => $colSeries];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'pie'    => ['labels' => $statusNames, 'series' => array_values($pieTotals)],
                'bar'    => ['categories' => $sizeCodes, 'series' => $barSeries],
                'column' => $colData,
            ],
            'error' => null,
            'meta'  => null,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = ViTechnical::findOrFail($id);

        $validated = $request->validate([
            'technical_result' => 'nullable|string|max:100',
            'eqp_number'       => 'nullable|string|max:100',
            'eqp_maker'        => 'nullable|string|max:100',
            'defect_code'      => 'nullable|string|max:500',
            'defect_details'   => 'nullable|array',
            'defect_details.*.code'    => 'nullable|string|max:50',
            'defect_details.*.bin'     => 'nullable|array',
            'defect_details.*.result'  => 'nullable|string|max:50',
            'defect_details.*.images'  => 'nullable|array',
            'remarks'          => 'nullable|string',
        ]);

        $user = auth()->user();
        $validated['updated_by'] = $user->emp_name ?? $user->name ?? 'system';

        $finalDecisions = ['Proceed', 'Derive RL', 'Scrap LY', 'Rework'];
        $decision = $validated['technical_result'] ?? null;

        if ($decision && in_array($decision, $finalDecisions, true) && empty($record->technical_completed_at)) {
            $validated['technical_completed_at'] = now();
            if ($record->technical_start_at) {
                $validated['total_tat'] = (int) $record->technical_start_at->diffInMinutes(now());
            }
        }

        $record->update($validated);

        // Route based on final decision
        $decision = $validated['technical_result'] ?? null;
        $inspection = \App\Models\QcInspection::where('lot_id', $record->lot_id)->latest()->first();

        $completedDecisions  = ['Proceed', 'Derive RL', 'Scrap LY', 'Rework'];
        $inProgressDecisions = ['For Decision', 'DRB Approval'];

        if (in_array($decision, $completedDecisions, true)) {
            DB::transaction(function () use ($record, $inspection, $validated, $decision) {
                $isRework   = $decision === 'Rework';
                $outputStatus = $isRework ? 'rework' : 'pending';

                // Update vi_technical completed timestamp
                $record->update([
                    'technical_completed_at' => now(),
                    'total_tat' => $record->technical_start_at
                        ? (int) $record->technical_start_at->diffInMinutes(now())
                        : null,
                ]);

                // Update qc_inspection
                if ($inspection) {
                    $inspUpdate = [
                        'technical_completd_at' => now(),
                        'technical_result'      => $decision,
                        'final_decision'        => $decision,
                        'output_status'         => $outputStatus,
                        'updated_by'            => $validated['updated_by'],
                    ];
                    if (!$isRework) {
                        $inspUpdate['production_start_at'] = now();
                    }
                    // Only stamp lot completion on Rework (lot ends here, no production stage)
                    if ($isRework) {
                        $inspUpdate['lot_completed_at'] = now();
                        $inspUpdate['total_tat'] = $inspection->created_at
                            ? (int) $inspection->created_at->diffInMinutes(now())
                            : null;
                    }
                    $inspection->update($inspUpdate);
                }

                // Route to qc_ok for Proceed / Derive RL / Scrap LY
                if (!$isRework) {
                    \App\Models\QcOk::updateOrCreate(
                        ['lot_id' => $record->lot_id],
                        [
                            'model'             => $record->model,
                            'lot_qty'           => $record->lot_qty,
                            'lipas_yn'          => $record->lipas_yn,
                            'work_type'         => $record->work_type,
                            'inspection_times'  => $record->inspection_times,
                            'inspection_spl'    => $record->inspection_spl,
                            'inspection_result' => $inspection?->inspection_result,
                            'analysis_result'   => $inspection?->analysis_result,
                            'technical_result'  => $decision,
                            'pending'           => 'production',
                            'output_status'     => 'pending',
                            'remarks'           => $record->remarks,
                            'created_by'        => $inspection?->created_by ?? $validated['updated_by'],
                            'updated_by'        => $validated['updated_by'],
                        ]
                    );
                }
            });
        } elseif (in_array($decision, $inProgressDecisions, true)) {
            // Not yet completed — mark as In Progress on both tables
            $record->update(['technical_result' => 'In Progress']);
            if ($inspection) {
                $inspection->update([
                    'technical_result' => 'In Progress',
                    'final_decision'   => $decision,
                    'output_status'    => 'pending',
                    'updated_by'       => $validated['updated_by'],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $record->fresh(),
            'error'   => null,
            'meta'    => null,
        ]);
    }
}
